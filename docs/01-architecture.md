# 🏗️ 01 — Architecture Technique

> **Stack :** Laravel 13 / PHP 8.3+ / MySQL 8 / Redis / Livewire 3 / Filament v4 / TailwindCSS v4
> **Hébergement :** o2switch Grow (mutualisé cPanel LiteSpeed)
> **Dernière mise à jour :** Juillet 2026 — Mise à jour vers Laravel 13 + Filament v4 + Spatie packages

---

## Table des matières

1. [Stack technique complète](#1-stack-technique-complète)
2. [Architecture modulaire (Domain-Driven)](#2-architecture-modulaire-domain-driven)
3. [Capacités & contraintes o2switch Grow](#3-capacités--contraintes-o2switch-grow)
4. [Déploiement](#4-déploiement)
5. [Services externes](#5-services-externes)

---

## 1. Stack technique complète

### 1.1 Frontend

| Technologie | Usage | Justification |
|-------------|-------|---------------|
| **Blade** (Laravel) | Templating serveur | Natif Laravel 13, rapide, simple |
| **Livewire 3** | Composants interactifs (fullstack) | Pas de complexité SPA, idéal sur mutualisé |
| **Filament v4** | Admin panel (back-office) | Standard de facto — Tailwind v4, MFA, ressources imbriquées, éditeur Tiptap |
| **TailwindCSS v4** | Design system utility-first | Mobile-first, rapide à itérer |
| **Alpine.js** | Interactivité légère (complément Livewire) | Pour les micro-interactions non critiques |
| **HLS.js** | Player vidéo HLS côté client | Lecture adaptative (3G/4G/WiFi) |
| **Howler.js** | Player audio (mini-player persistant) | Léger, performant |

### 1.2 Backend

| Technologie | Usage | Justification |
|-------------|-------|---------------|
| **Laravel 13** | Framework PHP | Dernière version (mars 2026) — attributs PHP natifs, AI SDK, JSON:API, sécurité renforcée |
| **PHP 8.3+** | Langage serveur | Requis par Laravel 13 |
| **Laravel Sanctum** | Auth API + SPA | Léger, natif Laravel 13 |
| **Laravel Pennant** | Feature flags | Officiel Laravel — déploiements progressifs |
| **Laravel Echo** | Temps réel (via Pusher) | Webhooks, notifications live |
| **Laravel Horizon** | Dashboard queue (Redis) | Monitoring des jobs (obligatoire) |
| **Laravel Pulse** | Monitoring applicatif | Temps réel : santé, lenteurs, cache |
| **Laravel Telescope** | Debug (dev uniquement) | Introspection requêtes, logs, jobs |

### 1.3 Packages communautaires (Spatie)

| Package | Usage | Justification |
|---------|-------|---------------|
| **Spatie Laravel Permission** | Rôles & permissions | Standard communautaire, flexible, supporte les gates |
| **Spatie Laravel MediaLibrary** | Gestion des médias | Uploads, conversions auto (WebP, miniatures), associations aux modèles |
| **Spatie Laravel ActivityLog** | Audit trail | Remplace l'audit_logs custom — logs auto des événements modèles |
| **Spatie Laravel Data** | DTO + validation | Standard pour les Data Transfer Objects |
| **Spatie Laravel Query Builder** | API filtrables | Filtres, tris, includes pour endpoints |

### 1.4 Base de données & Cache

| Technologie | Usage | Spécificités o2switch |
|-------------|-------|----------------------|
| **MySQL 8** | Base de données principale | Fournie par o2switch (cPanel) |
| **Redis** (phpredis) | Cache + Queue | Activable via cPanel, driver `redis` |
| **Varnish / XtremCache** | Cache full-page pages publiques | Exclure `/livewire`, dashboards, paiement |
| **OPcache** | Accélération PHP | Activé par défaut sur o2switch |
| **APCu** | Cache applicatif local | Alternative légère |

### 1.5 Médias & Streaming

| Technologie | Usage | Raison |
|-------------|-------|--------|
| **Spatie MediaLibrary** | Gestion uploads + conversions | Intégré aux modèles Eloquent |
| **Backblaze B2** ou **Cloudflare R2** | Stockage objet (fichiers média) | 0 egress, S3-compatible |
| **Bunny CDN / Bunny Stream** | Transcodage HLS + diffusion | Transcodage automatique, CDN mondial |
| **Signed URLs** | Protection contenu temporaire | DRM léger sans coût Widevine |

### 1.6 Monitoring & Analytics

| Technologie | Usage |
|-------------|-------|
| **Laravel Pulse** | Monitoring applicatif temps réel |
| **Laravel Horizon** | Monitoring queue Redis |
| **Plausible** ou **Umami** | Web analytics (conforme loi 2024/017) |
| **logs o2switch** | Logs serveur (cPanel) |

### 1.7 Services Externes

| Service | Usage | Package |
|---------|-------|---------|
| **Campay** | Paiement MoMo (principal) | Service custom (GuzzleHttp) |
| **Notch Pay** | Paiement MoMo (fallback) | `notchpay/laravel-sdk` (SDK officiel) |
| **Brevo** | Emails transactionnels | SMTP (`smtp-relay.brevo.com`) |
| **Unimatrix** | SMS OTP | Service custom (GuzzleHttp) |
| **Pusher** | Temps réel (WebSocket hébergé) | Laravel Broadcasting |
| **Cloudflare** | DNS, CDN frontal, DDoS | — |

---

## 2. Architecture modulaire (Domain-Driven)

### 2.1 Structure des dossiers

```
app/
├── Domain/                          # 🎯 Cœur métier (Domain-Driven)
│   ├── Users/                       # Utilisateurs, rôles, profils
│   │   ├── Models/
│   │   ├── Actions/                 # Actions métier (RegisterUser, VerifyKyc)
│   │   ├── Enums/                   # UserRole, KycStatus
│   │   └── Events/                  # UserRegistered, KycVerified
│   ├── Catalog/                     # Catalogue musical
│   │   ├── Models/                  # Album, Track, Video, Genre
│   │   ├── Actions/                 # UploadTrack, PublishAlbum
│   │   └── Events/                  # TrackPlayed, AlbumPurchased
│   ├── Store/                       # Boutique
│   │   ├── Models/                  # Product, Order, OrderItem
│   │   └── Actions/                 # CreateOrder, ProcessOrder
│   ├── Vip/                         # Abonnements VIP
│   │   ├── Models/                  # VipPlan, VipSubscription
│   │   └── Actions/                 # SubscribeVip, RenewVip
│   ├── Events/                      # Événements & billetterie
│   │   ├── Models/                  # Event, TicketType, Ticket, TicketScan
│   │   └── Actions/                 # CreateEvent, ScanTicket
│   ├── Dedications/                 # Dédicaces
│   │   ├── Models/                  # Dedication
│   │   └── Actions/                 # RequestDedication, DeliverDedication
│   ├── Voting/                      # Vote & concours
│   │   ├── Models/                  # Contest, Candidate, Vote
│   │   └── Actions/                 # CastVote, CreateContest
│   ├── Wallet/                      # Wallet & transactions
│   │   ├── Models/                  # Wallet, Transaction
│   │   └── Actions/                 # CreditWallet, DebitWallet, Transfer
│   ├── Payments/                    # Paiements
│   │   ├── Models/                  # Payment, Payout
│   │   ├── Contracts/               # PaymentGateway (interface)
│   │   ├── Services/                # CampayService, NotchPayService
│   │   └── Actions/                 # ProcessPayment, ProcessPayout
│   └── Content/                     # Droits & déclarations
│       ├── Models/                  # ContentRight
│       └── Actions/                 # AcceptContentRights
│
├── Http/
│   ├── Controllers/
│   │   ├── Web/                     # Contrôleurs Blade/Livewire
│   │   │   ├── Auth/                # Login, Register, OTP
│   │   │   ├── Artist/              # Dashboard artiste
│   │   │   ├── Fan/                 # Dashboard fan
│   │   │   └── Admin/               # Back-office admin
│   │   └── Api/                     # Contrôleurs API
│   │       └── WebhookController    # Webhooks agrégateurs
│   ├── Livewire/                    # Composants Livewire
│   │   ├── Artist/                  # Catalogue management, stats
│   │   ├── Fan/                     # Player, bibliothèque
│   │   ├── Payment/                 # Tunnel de paiement
│   │   └── Admin/                   # Dashboard, KYC, litiges
│   ├── Middleware/                  # locale, role, throttle
│   └── Requests/                    # Form requests (validation)
│
├── Jobs/                            # Jobs de queue
│   ├── ProcessPaymentWebhook.php    # Traitement webhook paiement
│   ├── ProcessPayout.php            # Traitement retrait
│   ├── SendOtpSms.php               # Envoi SMS OTP
│   ├── SendEmailNotification.php    # Email via Brevo
│   ├── ProcessRevenueSplit.php      # Répartition splits après vente
│   └── ReconcilePendingPayments.php # Réconciliation paiements
│
├── Console/
│   ├── Commands/
│   │   ├── ReconcilePayments.php    # Polling paiements pending
│   │   ├── CheckVipExpirations.php  # Désactiver VIP expirés
│   │   ├── FinancialInvariant.php   # Vérifier invariant comptable
│   │   └── SendVipRenewalReminder.php # Rappels renouvellement VIP
│   └── Kernel.php                   # Schedule (planification CRON)
│
├── Services/                        # Services transverses
│   ├── CampayService.php            # Client API Campay
│   ├── NotchPayService.php          # Client API Notch Pay
│   ├── MediaService.php             # Upload B2/R2, gestion médias
│   ├── SmsService.php               # Envoi SMS (Unimatrix)
│   ├── WalletService.php            # Logique wallet (solde, verrous)
│   └── RevenueSplitService.php      # Calcul splits collaborateurs
│
└── Providers/
    └── AppServiceProvider.php       # Binding interfaces
```

### 2.2 Flux de données (schéma simplifié)

```
[Client] ←→ [Cloudflare CDN]
    ↓
[o2switch Grow] — PHP/Laravel/Livewire
    ↓
[Redis] ← Cache + Queue (worker via CRON + flock)
    ↓
[MySQL 8] ← Base de données principale
    ↓
[Services Externes]
    ├── Campay / Notch Pay (paiements)
    ├── Backblaze B2 / R2 (stockage média)
    ├── Bunny Stream (transcodage HLS + CDN)
    ├── Brevo (emails)
    ├── Unimatrix (SMS OTP)
    └── Pusher (temps réel)
```

### 2.3 Flux de paiement (schéma)

```
1. Fan clique "Acheter" (2 000 F CFA)
2. Backend → POST Campay API : {montant, phone, ref}
3. Fan reçoit invite USSD → valide avec code secret
4. Campay → POST /api/webhooks/payment (HMAC signé)
5. Laravel reçoit → dispatch ProcessPaymentWebhook (queue)
6. Queue vérifie signature, reference, statut
7. WalletService::credit(artiste, montant_net)  // 2000 - 10% = 1800
8. Accès débloqué (téléchargement/stream/VIP/billet)
```

---

## 3. Capacités & contraintes o2switch Grow

### 3.1 Capacités disponibles

| Capacité | Commande/Configuration | Usage |
|----------|----------------------|-------|
| **PHP 8.3** | Sélectionnable dans MultiPHP Manager | `composer install --no-dev` |
| **MySQL 8** | cPanel → MySQL Databases | Migrations Laravel |
| **Redis** | cPanel → Redis (phpredis) | `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis` |
| **Varnish / XtremCache** | cPanel → XtremCache | Cache full-page (exclure Livewire) |
| **CRON Jobs** | cPanel → Cron Jobs | `php artisan schedule:run` |
| **SSH** | Accès terminal | Déploiement Git, Artisan |
| **Node.js** | cPanel → Setup Node.js | Build frontend (Vite) |
| **Composer** | Terminal SSH | Gestion dépendances PHP |
| **OPcache** | Automatique | Accélération PHP |
| **LiteSpeed** | Serveur web natif | Compatible .htaccess, rewrite |

### 3.2 Contraintes & solutions

| Contrainte | Solution retenue | Détail technique |
|------------|-----------------|------------------|
| **Pas de démon persistant (Supervisor)** | Worker queue via CRON + `flock` | Voir §3.3 ci-dessous |
| **I/O limité (16 Mo/s)** | Médias 100% externalisés | B2/R2 + Bunny CDN |
| **Pas de transcodage lourd** | Bunny Stream (transcodage auto) | Upload direct client → Bunny |
| **Pas de WebSocket auto-hébergé** | Pusher (hébergé) ou polling | Laravel Echo → Pusher |
| **Timeout PHP exécution** | Upload direct client → objet | URL signée pré-upload |
| **Ressources partagées** | Cache agressif + Varnish | Pages publiques en cache |

### 3.3 Worker Queue — CRON + flock (MÉTHODE OFFICIELLE o2switch)

```bash
# Un seul CRON o2switch :
* * * * * php artisan schedule:run >> /dev/null 2>&1

# Dans app/Console/Kernel.php :
$schedule->command('queue:work --stop-when-empty --max-time=55')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/queue-worker.log'));

# sansOverlapping() utilise le verrou Redis/flock
# Recommandation o2switch : utiliser --once --tries=3 pour éviter les fuites mémoire
# Voir docs/10-devops-testing.md pour les détails
```

> **Fonctionnement :** chaque minute, le CRON lance un worker qui traite un seul job (`--once`) puis se termine. Si le worker précédent est encore en cours, le nouveau ne démarre pas (`withoutOverlapping`). Cette approche évite les fuites mémoire sur mutualisé o2switch.

### 3.4 Cache stratifié

```
[Cloudflare] → Cache edge (CSS/JS/images statiques)
    ↓
[Varnish / XtremCache] → Cache full-page (pages publiques)
    ↓
[Laravel Cache (Redis)] → Cache applicatif (DB queries, sessions)
    ↓
[OPcache] → Cache bytecode PHP
```

### 3.5 Scalabilité (sans refonte)

| Évolution | Option o2switch | Changement requis |
|-----------|----------------|-------------------|
| + de trafic | Cloud (12 threads/48 Go) | Aucun (même cPanel) |
| + de trafic | Pro (24 threads/64 Go) | Aucun (même cPanel) |
| Daemon permanent | Migration VPS + Supervisor | `.env` uniquement |
| WebSocket natif | Migration VPS + Laravel Reverb | `.env` uniquement |

---

## 4. Déploiement

### 4.1 Prérequis o2switch

1. PHP 8.3 sélectionné dans MultiPHP Manager
2. Extension PHP : `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `redis`, `tokenizer`, `xml`, `zip`
3. Redis activé dans cPanel
4. Variable d'environnement : `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`
5. CRON configuré : `* * * * * php /home/{user}/public_html/artisan schedule:run`

### 4.2 Pipeline de déploiement

```bash
# 1. Connexion SSH
ssh user@o2switch

# 2. Pull du code
cd ~/public_html
git pull origin main

# 3. Dépendances
composer install --no-dev --optimize-autoloader

# 4. Build frontend
npm ci && npm run build

# 5. Migrations
php artisan migrate --force

# 6. Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Permissions
chmod -R 755 storage bootstrap/cache
```

### 4.3 Déploiement Git (bare repo + hook)

Alternative : dépôt bare sur o2switch avec hook post-receive :

```bash
# Sur o2switch (une fois)
mkdir ~/repo.git && cd ~/repo.git
git init --bare
# Créer hooks/post-receive :
cat > hooks/post-receive << 'EOF'
#!/bin/bash
GIT_WORK_TREE=/home/{user}/public_html git checkout -f
cd /home/{user}/public_html
php artisan migrate --force
php artisan config:cache
EOF
chmod +x hooks/post-receive

# Local : git remote add production ssh://user@o2switch/~/repo.git
```

---

## 5. Services externes

### 5.1 Tableau récapitulatif

| Service | Usage | URL | Compte requis |
|---------|-------|-----|---------------|
| **Campay** | Paiement Mobile Money (principal) | https://campay.net | Sandbox + Production |
| **Notch Pay** | Paiement Mobile Money (fallback) | https://notchpay.co | Sandbox + Production |
| **Backblaze B2** | Stockage objet (médias) | https://backblaze.com | Bucket privé |
| **Cloudflare R2** | Stockage objet (alternative) | https://cloudflare.com | Bucket privé |
| **Bunny** | CDN + Stream (transcodage HLS) | https://bunny.net | Compte Bunny |
| **Brevo** | Emails transactionnels | https://brevo.com | API Key |
| **Unimatrix** | SMS (OTP) | https://unimatrix.cm | Compte local |
| **Pusher** | WebSocket temps réel | https://pusher.com | App + Key |
| **Cloudflare** | DNS + CDN frontal | https://cloudflare.com | Zone ajoutée |

### 5.2 Variables d'environnement

> Voir [`docs/10-devops-testing.md`](./10-devops-testing.md) pour la liste exhaustive.

```env
# Application
APP_NAME="FanPay"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://fanpay.cm

# Base de données
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=fanpay
DB_USERNAME=fanpay_user
DB_PASSWORD=***

# Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Campay (custom service)
CAMPAY_PERMANENT_TOKEN=***
CAMPAY_WEBHOOK_KEY=***
CAMPAYMODE=sandbox

# Notch Pay (SDK Laravel officiel)
NOTCHPAY_PUBLIC_KEY=test_pk_***
NOTCHPAY_HASH_KEY=***
NOTCHPAY_MODE=sandbox

# Bunny
BUNNY_API_KEY=***
BUNNY_STORAGE_ZONE=fanpay-media
BUNNY_PULL_ZONE=fanpay-cdn
BUNNY_VIDEO_LIBRARY_ID=***
BUNNY_SECURITY_KEY=***

# Backblaze B2 (via MediaLibrary)
B2_KEY_ID=***
B2_APPLICATION_KEY=***
B2_BUCKET=fanpay-media
B2_ENDPOINT=https://s3.us-west-004.backblazeb2.com

# Brevo (email)
BREVO_API_KEY=***
BREVO_SENDER_EMAIL=noreply@fanpay.cm
BREVO_SENDER_NAME="FanPay"
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=noreply@fanpay.cm
MAIL_PASSWORD=***

# SMS (Unimatrix)
SMS_PROVIDER=unimatrix
UNIMATRIX_ACCESS_KEY_ID=***
UNIMATRIX_SENDER=FANPAY

# Pusher (temps réel)
PUSHER_APP_ID=***
PUSHER_APP_KEY=***
PUSHER_APP_SECRET=***
PUSHER_APP_CLUSTER=eu

# Cloudflare
CLOUDFLARE_API_TOKEN=***
CLOUDFLARE_ZONE_ID=***

# Spatie Permission
CACHE_STORE=redis  # Permissions mises en cache Redis

# Laravel Pulse
PULSE_ENABLED=true

# Laravel Horizon
HORIZON_ENABLED=true

# Feature Flags (Pennant)
PENNANT_STORE=redis
```

---

> **Document suivant :** [`docs/02-database.md`](./02-database.md) — Schéma complet de la base de données
