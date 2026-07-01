# 🚀 10 — Déploiement, DevOps & Testing

> **Hébergement :** o2switch Grow (mutualisé cPanel LiteSpeed)
> **Queue :** Redis + CRON `flock`
> **Cache :** Varnish (pages publiques) + Redis (applicatif)
> **Monitoring :** Laravel Pulse + Horizon
> **Debug :** Laravel Telescope (dev uniquement)
> **CI :** GitHub Actions (tests automatiques)

---

## Table des matières

1. [Déploiement sur o2switch](#1-déploiement-sur-o2switch)
2. [Configuration CRON](#2-configuration-cron)
3. [Cache stratifié](#3-cache-stratifié)
4. [Variables d'environnement](#4-variables-denvironnement)
5. [Stratégie de tests](#5-stratégie-de-tests)
6. [Monitoring & logging](#6-monitoring--logging)
7. [Sauvegardes](#7-sauvegardes)
8. [Checklist de mise en production](#8-checklist-de-mise-en-production)

---

## 1. Déploiement sur o2switch

### 1.1 Prérequis o2switch

| Vérification | Action |
|-------------|--------|
| PHP 8.3 | MultiPHP Manager → sélectionner PHP 8.3 |
| Extensions PHP | Vérifier : bcmath, ctype, curl, dom, fileinfo, gd, json, mbstring, openssl, pdo_mysql, redis, tokenizer, xml, zip |
| MySQL 8 | Créer la base "fanpay" + utilisateur dédié |
| Redis | cPanel → Redis → Activer |
| Node.js | cPanel → Setup Node.js → version 20+ (pour Vite build) |
| Cloudflare | Ajouter le domaine dans Cloudflare (DNS proxied) |
| SSH | Activer l'accès SSH dans cPanel |

### 1.2 Déploiement initial

```bash
# 1. Connexion SSH
ssh user@o2switch -p 2222

# 2. Aller dans le dossier public_html
cd ~/public_html

# 3. Cloner le dépôt
git clone https://github.com/org/fanpay.git .

# 4. Dépendances PHP
composer install --no-dev --optimize-autoloader

# 5. Dépendances JS + build
npm ci --production
npm run build

# 6. Fichier environnement
cp .env.production .env
# → Éditer .env avec les valeurs réelles

# 7. Générer APP_KEY
php artisan key:generate

# 8. Migrations
php artisan migrate --force

# 9. Optimisations Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 10. Permissions
chmod -R 755 storage bootstrap/cache

# 11. CRON (voir section 2)
```

### 1.3 Mise à jour (déploiement continu)

```bash
# Script deploy.sh
#!/bin/bash
set -e

echo "🔄 Déploiement en cours..."
cd ~/public_html

# Mode maintenance
php artisan down --retry=60

# Pull du code
git pull origin main

# Dépendances
composer install --no-dev --optimize-autoloader

# Build frontend
npm ci --production
npm run build

# Migrations
php artisan migrate --force

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Permissions
chmod -R 755 storage bootstrap/cache

# Fin maintenance
php artisan up

echo "✅ Déploiement terminé !"
```

### 1.4 Dépôt Git bare + hook (alternative)

```bash
# Sur o2switch (une fois)
mkdir ~/fanpay.git && cd ~/fanpay.git
git init --bare

# Créer le hook post-receive
cat > hooks/post-receive << 'DEPLOY'
#!/bin/bash
GIT_WORK_TREE=/home/user/public_html git checkout -f
cd /home/user/public_html
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
DEPLOY
chmod +x hooks/post-receive

# En local
git remote add production ssh://user@o2switch:2222/~/fanpay.git
git push production main
```

---

## 2. Configuration CRON

### 2.1 CRON principal (unique)

```bash
# Dans cPanel → Cron Jobs
# Commande (chemin PHP complet pour la version 8.3) :
* * * * * /opt/alt/php83/usr/bin/php /home/{user}/public_html/artisan schedule:run >> /dev/null 2>&1
```

> **Note :** Le chemin exact du binaire PHP doit être celui de la version sélectionnée dans MultiPHP Manager. Pour PHP 8.3 : `/opt/alt/php83/usr/bin/php`.

> **Un seul CRON** suffit. Tout le scheduling est géré dans `app/Console/Kernel.php`.

### 2.2 Kernel.php (planification)

```php
// app/Console/Kernel.php
class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // ─── Queue Worker ───────────────────────────────────
        // Worker Redis via CRON + sansOverlapping (verrou)
        // --once : traite UN SEUL job par minute — recommandation o2switch
        //           pour éviter de monopoliser le CPU
        // --tries=3 : max 3 tentatives par job avant échec
        // Le worker se termine après chaque job, libérant la mémoire
        $schedule->command('queue:work --once --tries=3')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/queue-worker.log'));
        
        // ─── Réconciliation paiements ───────────────────────
        // Paiements restés "pending" > 30 min
        $schedule->command('payments:reconcile')
            ->everyFiveMinutes()
            ->withoutOverlapping();
        
        // ─── Expiration VIP ────────────────────────────────
        // Désactiver les abonnements VIP expirés
        $schedule->command('vip:check-expirations')
            ->dailyAt('02:00')
            ->withoutOverlapping();
        
        // ─── Rappels renouvellement VIP ────────────────────
        // Notifier les fans J-3 avant expiration
        $schedule->command('vip:send-renewal-reminders')
            ->dailyAt('10:00')
            ->withoutOverlapping();
        
        // ─── Invariant financier ───────────────────────────
        // Vérifier Σ soldes = Σ crédits - Σ débits
        $schedule->command('wallet:check-invariant')
            ->dailyAt('03:00')
            ->withoutOverlapping();
        
        // ─── Backup DB (optionnel, via o2switch aussi) ────
        $schedule->command('backup:run --only-db')
            ->dailyAt('04:00')
            ->withoutOverlapping();
    }
}
```

### 2.3 Fonctionnement du worker queue

```
Minute 00 : CRON déclenche schedule:run
  → Kernel lit la schedule
  → queue:work --once --tries=3
  → Traite UN SEUL job (--once) puis s'arrête
  → Si worker précédent encore actif : sansOverlapping bloque
  → Évite les fuites mémoire (le worker se termine après chaque job)
  → Propre sur mutualisé o2switch

Minute 01 : CRON relance
  → Si nouveau job dans la queue : nouveau worker démarre
  → Sinon : rien ne se passe
```

> **Recommandation o2switch :** Utiliser `--once --tries=3` plutôt que `--stop-when-empty` pour éviter de monopoliser les ressources processeur. Chaque minute, le CRON traite un job, se termine, et libère la mémoire.

---

## 3. Cache stratifié

### 3.1 Architecture du cache

```
Niveau 1 : Cloudflare CDN
  └── Fichiers statiques (CSS, JS, images, fonts)
  └── Cache edge (TTL long : 1 an avec hash dans l'URL)

Niveau 2 : Varnish (XtremCache) — cache full-page
  └── Pages publiques : accueil, profils artistes, catalogue
  └── Exclure : /livewire/*, /dashboard/*, /admin/*, /checkout/*, /artist/*/wallet
  └── TTL : 1 heure (ou invalidation manuelle via purge)

Niveau 3 : Redis — cache applicatif
  └── Cache DB queries
  └── Sessions utilisateurs
  └── Top charts / classements (TTL 15 min)
  └── Config settings (TTL infini)
  └── Statistiques agrégées (TTL 5 min)

Niveau 4 : OPcache + APCu
  └── Cache bytecode PHP (automatique)
  └── Cache local (APCu pour données partagées intra-requête)
```

### 3.2 Configuration Varnish (XtremCache)

```apache
# Règles XtremCache (cPanel → XtremCache)
# Inclure :
URL pattern: /          → Cache: 3600s
URL pattern: /artist/*  → Cache: 3600s
URL pattern: /album/*   → Cache: 3600s
URL pattern: /chart     → Cache: 900s
URL pattern: /search    → Cache: 0s (pas de cache)

# Exclure (ne jamais cacher) :
URL pattern: /livewire/*     → Bypass
URL pattern: /dashboard/*    → Bypass
URL pattern: /artist/*/wallet → Bypass
URL pattern: /checkout/*     → Bypass
URL pattern: /login*         → Bypass
URL pattern: /register*      → Bypass
URL pattern: /admin/*        → Bypass
URL pattern: /api/*          → Bypass
```

### 3.3 Cache Laravel

```env
# config/cache.php
'default' => env('CACHE_STORE', 'redis'),

# config/queue.php
'default' => env('QUEUE_CONNECTION', 'redis'),

# config/database.php
'redis' => [
    'client' => 'phpredis',
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => 0,
    ],
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => 1, // Base Redis dédiée au cache
    ],
    'queue' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => 2, // Base Redis dédiée à la queue
    ],
],
```

### 3.4 Cache des pages Livewire (important)

Livewire utilise des requêtes AJAX pour les composants interactifs. Ces requêtes **ne doivent pas** être cachées par Varnish. La configuration XtremCache ci-dessus exclut `/livewire/*`.

---

## 4. Variables d'environnement

### 4.1 Fichier .env.production (template)

```env
# ─── Application ───────────────────────────────────────────
APP_NAME="FanPay"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://fanpay.cm
APP_KEY=base64:...  # Généré avec php artisan key:generate

# ─── Base de données ──────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=fanpay
DB_USERNAME=fanpay_user
DB_PASSWORD=***  # Mot de passe fort

# ─── Cache & Queue (Redis) ────────────────────────────────
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ─── Session ──────────────────────────────────────────────
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

# ─── Campay (paiement principal) ──────────────────────────
CAMPAY_PERMANENT_TOKEN=***       # Permanent Access Token (recommandé)
# OU (alternative) :
CAMPAY_APP_KEY=***                # username pour Temporary Token
CAMPAY_APP_SECRET=***             # password pour Temporary Token
CAMPAY_WEBHOOK_KEY=***            # Clé secrète JWT pour webhooks
CAMPAYMODE=sandbox                # sandbox ou production

# ─── Notch Pay (paiement fallback) ────────────────────────
NOTCHPAY_PUBLIC_KEY=test_pk_***   # ou live_pk_*** en production
NOTCHPAY_HASH_KEY=***             # Clé HMAC pour vérifier webhooks
NOTCHPAY_MODE=sandbox             # sandbox ou production

# ─── Bunny CDN / Stream ───────────────────────────────────
BUNNY_API_KEY=***
BUNNY_STORAGE_ZONE=fanpay-media
BUNNY_PULL_ZONE=fanpay-cdn
BUNNY_VIDEO_LIBRARY_ID=***
BUNNY_SECURITY_KEY=***

# ─── Backblaze B2 (stockage objet) ───────────────────────
B2_KEY_ID=***
B2_APPLICATION_KEY=***
B2_BUCKET=fanpay-media
B2_ENDPOINT=https://s3.us-west-004.backblazeb2.com

# ─── Brevo (email) ────────────────────────────────────────
BREVO_API_KEY=***
BREVO_SENDER_EMAIL=noreply@fanpay.cm
BREVO_SENDER_NAME="FanPay"
MAIL_MAILER=brevo

# ─── SMS (Unimatrix / local) ─────────────────────────────
SMS_PROVIDER=unimatrix
UNIMATRIX_ACCESS_KEY_ID=***       # API Key (accessKeyId en paramètre query)
UNIMATRIX_SENDER=FANPAY           # Signature expéditeur (2-16 caractères)
# API URL: https://api.unimtx.com/?action=sms.message.send
# Prix: ~0.1498 USD/SMS (soit ~90 FCFA) — OTP uniquement

# ─── Pusher (temps réel) ─────────────────────────────────
PUSHER_APP_ID=***
PUSHER_APP_KEY=***
PUSHER_APP_SECRET=***
PUSHER_APP_CLUSTER=eu
BROADCAST_DRIVER=pusher

# ─── Cloudflare ──────────────────────────────────────────
CLOUDFLARE_API_TOKEN=***
CLOUDFLARE_ZONE_ID=***
CLOUDFLARE_EMAIL=***

# ─── Sécurité ────────────────────────────────────────────
APP_DEBUG=false
APP_ENV=production
```

### 4.2 Gestion des secrets

- **Jamais** de secrets dans le code ou dans git.
- Fichier `.env` exclu via `.gitignore`.
- Chaque développeur a son `.env` local.
- Pour le déploiement : copie manuelle via SSH ou injection via CI.

---

## 5. Stratégie de tests

### 5.1 Types de tests

| Type | Priorité | Cible |
|------|----------|-------|
| **Unitaires** | 🔴 Critique | Logique financière (commissions, splits, frais) |
| **Feature** | 🔴 Critique | Parcours complets (achat → paiement → déblocage) |
| **Idempotence** | 🔴 Critique | Rejeu webhook = pas de double crédit |
| **Validation** | 🟡 Important | Règles métier (KYC requis pour retrait, etc.) |
| **Sécurité** | 🟡 Important | Rate limiting, authentification, accès rôles |
| **Navigation** | 🟢 Secondaire | Routes, redirections, pages |
| **Frontend** | 🟢 Secondaire | Composants Livewire (tests unitaires Livewire) |

### 5.2 Tests unitaires (logique financière)

```php
// tests/Unit/Services/CommissionServiceTest.php
class CommissionServiceTest extends TestCase
{
    private CommissionService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CommissionService::class);
    }
    
    public function test_calculates_standard_commission(): void
    {
        $result = $this->service->calculate(2000);
        
        $this->assertEquals(2000, $result->amount);
        $this->assertEquals(50, $result->fees);      // 2.5%
        $this->assertEquals(200, $result->commission); // 10%
        $this->assertEquals(1800, $result->netAmount);
        $this->assertEquals(2050, $result->totalPaidByFan);
    }
    
    public function test_fees_rounded_up(): void
    {
        // 100 FCFA × 2.5% = 2.5 → ceil = 3
        $result = $this->service->calculate(100);
        $this->assertEquals(3, $result->fees);
    }
    
    public function test_zero_commission_rate(): void
    {
        Setting::set('commission_rate', 0);
        $result = $this->service->calculate(2000);
        
        $this->assertEquals(0, $result->commission);
        $this->assertEquals(2000, $result->netAmount);
    }
}
```

### 5.3 Tests feature (parcours complets)

```php
// tests/Feature/PaymentFlowTest.php
class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_complete_purchase_flow(): void
    {
        // Arrangement
        $artist = User::factory()->artist()->create();
        $fan = User::factory()->fan()->create();
        $album = Album::factory()->for($artist)->create(['price' => 2000]);
        
        // Agir : initier un paiement
        $payment = $this->actingAs($fan)
            ->post('/api/payment/initiate', [
                'purchasable_type' => Album::class,
                'purchasable_id' => $album->id,
                'phone' => $fan->phone,
            ])
            ->assertStatus(200)
            ->json();
        
        // Simuler le webhook Campay
        $webhookData = [
            'external_reference' => $payment['reference'],
            'reference' => 'CAMPAY-REF-123',
            'status' => 'SUCCESSFUL',
            'amount' => 2050,
            'currency' => 'XAF',
        ];
        
        Http::fake([
            'campay.net/*' => Http::response(['token' => 'test-token']),
        ]);
        
        $this->post('/api/webhooks/payment', $webhookData, [
            'X-Campay-Signature' => 'valid-signature',
        ])->assertStatus(200);
        
        // Vérifier : wallet crédité
        $this->assertEquals(1800, $artist->wallet->fresh()->balance);
        
        // Vérifier : accès débloqué
        $this->assertTrue($fan->hasPurchased($album));
    }
    
    public function test_duplicate_webhook_does_not_double_credit(): void
    {
        // Envoyer 2× le même webhook
        // Vérifier que le wallet n'est crédité qu'une fois
    }
}
```

### 5.4 Tests d'idempotence

```php
public function test_webhook_idempotency(): void
{
    // Envoyer 3 fois le même webhook
    $webhookData = [/* ... */];
    
    foreach (range(1, 3) as $i) {
        $this->post('/api/webhooks/payment', $webhookData, $headers);
    }
    
    // Traiter la queue (--once pour éviter blocage)
    Artisan::call('queue:work --once --tries=3');
    
    // Vérifier : une seule transaction créée
    $this->assertEquals(1, Transaction::count());
    $this->assertEquals(1800, $artist->wallet->balance);
}
```

### 5.5 CI / GitHub Actions

```yaml
# .github/workflows/tests.yml
name: Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8
        env:
          MYSQL_ALLOW_EMPTY_PASSWORD: yes
          MYSQL_DATABASE: fanpay_test
        ports: ['3306:3306']
      
      redis:
        image: redis:7
        ports: ['6379:6379']
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          extensions: mbstring, bcmath, curl, gd, json, pdo_mysql, redis
      
      - name: Install dependencies
        run: composer install --no-interaction
      
      - name: Copy .env
        run: cp .env.testing .env
      
      - name: Generate key
        run: php artisan key:generate
      
      - name: Run migrations
        run: php artisan migrate --env=testing
      
      - name: Run tests
        run: php artisan test
```

### 5.6 Commandes utiles

```bash
# Lancer tous les tests
php artisan test

# Lancer un fichier spécifique
php artisan test --filter CommissionServiceTest

# Lancer un test spécifique
php artisan test --filter test_calculates_standard_commission

# Générer couverture (si xdebug installé)
php artisan test --coverage
```

---

## 6. Monitoring & logging

### 6.0 Stack de monitoring Laravel

| Outil | Usage | Environnement |
|-------|-------|---------------|
| **Laravel Pulse** | Monitoring applicatif temps réel | Production |
| **Laravel Horizon** | Monitoring queue Redis | Production |
| **Laravel Telescope** | Debug (requêtes, jobs, logs) | Dev uniquement |
| **Plausible / Umami** | Web analytics | Production |

### 6.1 Laravel Pulse

```bash
composer require laravel/pulse
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
php artisan migrate
```

**Métriques surveillées :**
- Requêtes lentes (slow queries)
- Usages du cache (hit/miss ratio)
- Taille de la queue
- Utilisateurs connectés
- Erreurs 404/500

**Dashboard :** `/pulse` (accessible uniquement aux admins, via Filament ou route protégée)

### 6.2 Laravel Horizon

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon:publish
```

**Configuration :**
```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'payments', 'notifications', 'media'],
            'balance' => 'simple',
            'processes' => 1,  // Sur o2switch, 1 process suffit
            'tries' => 3,
        ],
    ],
],
```

> ⚠️ **Horizon sur o2switch :** Le **dashboard Horizon** est consultable (métriques, jobs, échecs) car il lit les données depuis Redis. En revanche, le **contrôle des supervisors** (scale, pause) n'est pas disponible car Horizon nécessite un démon permanent (`php artisan horizon`). Sur o2switch, les workers sont gérés par le CRON + `flock`. La configuration des queues (connexion Redis, tentatives) se fait via `.env` et `config/horizon.php`.

### 6.3 Laravel Telescope (dev)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

> ⚠️ **Ne jamais activer Telescope en production.** Utiliser Pulse à la place.
> Telescope est idéal en dev pour inspecter : requêtes, jobs, logs, exceptions, modèles.

### 6.1 Logs Laravel

```env
# .env
LOG_CHANNEL=daily  # Rotation quotidienne
LOG_LEVEL=warning  # En production : warning+ uniquement
```

Les logs sont stockés dans `storage/logs/laravel-YYYY-MM-DD.log` et accessibles via cPanel → File Manager.

### 6.2 Logs o2switch

- **Error log** : cPanel → Metrics → Errors
- **Raw access log** : cPanel → Raw Access
- **PHP error log** : accessible via MultiPHP Manager

### 6.3 Alertes admin (email + in-app)

| Événement | Canal |
|-----------|-------|
| Invariant financier violé | Email critique |
| Taux d'échec paiement > 10% | Email + in-app |
| Erreur de traitement queue | Log + in-app |
| Nouvelle version déployée | Email |

### 6.4 Health check

```php
// Route : GET /api/health
Route::get('/api/health', function () {
    $status = [
        'app' => 'ok',
        'database' => DB::connection()->getPdo() ? 'ok' : 'error',
        'redis' => Cache::store('redis')->set('health', true) ? 'ok' : 'error',
        'queue' => Queue::size() < 100 ? 'ok' : 'warning', // Queue < 100
    ];
    
    return response()->json([
        'status' => in_array('error', $status) ? 'degraded' : 'healthy',
        'checks' => $status,
        'timestamp' => now(),
    ]);
});
```

---

## 7. Sauvegardes

### 7.1 Sauvegarde base de données

- **Automatique o2switch** : cPanel → Backup (quotidienne, 7 jours).
- **Sauvegarde manuelle via commande** :
  ```bash
  php artisan backup:run --only-db
  ```
- **Export externe** : backup hebdomadaire vers B2/R2 (bucket séparé).

### 7.2 Sauvegarde fichiers

- Code source : via git (GitHub).
- Médias (B2/R2) : réplication intégrée du provider.
- Uploads artistes : backup configurable du bucket B2/R2.

---

## 8. Checklist de mise en production

### 8.1 Avant le lancement

- [ ] Domaine pointé vers Cloudflare (DNS proxied)
- [ ] HTTPS activé (Cloudflare SSL/TLS Full)
- [ ] PHP 8.3 vérifié sur o2switch
- [ ] Redis activé (cache + queue)
- [ ] Varnish/XtremCache configuré (exclusions Livewire)
- [ ] CRON configuré (1 ligne)
- [ ] Variables .env toutes renseignées
- [ ] Campay/Notch Pay en mode production
- [ ] Bunny CDN configuré (pull zone + security key)
- [ ] Backblaze B2 / R2 bucket créé
- [ ] Brevo vérifié (domaine validé, SPF/DKIM)
- [ ] Comptes SMS (Unimatrix) actifs
- [ ] Migrations DB exécutées
- [ ] Caches générés (config, route, view)
- [ ] Tests passés en production
- [ ] Sauvegarde DB configurée
- [ ] Health check opérationnel
- [ ] Pages légales en ligne (CGU, CGV, confidentialité)
- [ ] Bannière consentement cookies active

### 8.2 Post-lancement (J+1)

- [ ] Vérifier les logs (aucune erreur PHP)
- [ ] Tester un paiement réel (sandbox → production)
- [ ] Vérifier le worker queue (logs queue-worker.log)
- [ ] Vérifier le cache Varnish (headers)
- [ ] Vérifier l'envoi d'emails (Brevo)
- [ ] Vérifier l'envoi SMS (OTP)

---

> **Document suivant :** [`docs/00-index.md`](./00-index.md) ← Retour au sommaire
