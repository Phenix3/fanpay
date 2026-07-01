# 🎵 FanPay — Plateforme de monétisation musicale (Cameroun)

Plateforme web (mobile-first) permettant aux artistes musicaux camerounais de diffuser, vendre leur musique et monétiser leur communauté via Mobile Money.

> **Stack :** Laravel 13 / PHP 8.3+ / MySQL 8 / Redis / Livewire 3 / Filament v4 / TailwindCSS v4
> **Hébergement :** o2switch Grow (mutualisé cPanel LiteSpeed)
> **Devise :** XAF (FCFA) — aucun décimal

## 📁 Structure des dossiers clés

Architecture Domain-Driven :

```
app/
├── Domain/                          # Cœur métier
│   ├── Users/                       # Auth, rôles, profils, KYC
│   ├── Catalog/                     # Albums, Tracks, Videos, Genres
│   ├── Store/                       # Produits, Commandes
│   ├── Vip/                         # Plans & abonnements VIP
│   ├── Events/                      # Événements & billets
│   ├── Dedications/                 # Dédicaces personnalisées
│   ├── Voting/                      # Concours & votes
│   ├── Wallet/                      # Portefeuille & transactions
│   ├── Payments/                    # Campay, Notch Pay, interfaces
│   └── Content/                     # Droits d'auteur, déclarations
├── Http/
│   ├── Controllers/Web/{Auth,Artist,Fan,Admin}
│   ├── Controllers/Api/WebhookController
│   ├── Livewire/{Artist,Fan,Payment}
│   └── Middleware/
├── Jobs/                            # ProcessPaymentWebhook, SendOtpSms, etc.
├── Console/Kernel.php               # CRON schedule
├── Services/                        # CampayService, NotchPayService, etc.
└── Filament/Resources/              # Admin panel (KYC, Payouts, Litiges...)
```

## ⚙️ Commandes principales

```bash
# Installation
composer install
npm install

# Développement
npm run dev              # Vite dev server
php artisan serve        # Serveur local
php artisan telescope    # Debug (dev uniquement)

# Base de données
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed

# Tests
php artisan test
php artisan test --filter PaymentTest

# Queue (local)
php artisan queue:work --once --tries=3

# Cache (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Production (déploiement o2switch)
composer install --no-dev --optimize-autoloader
npm ci --production && npm run build
```

## 🧩 Packages Laravel 13 (officiels + communauté)

| Package | Usage | Type |
|---------|-------|------|
| `laravel/sanctum` | Auth SPA + API | Officiel |
| `laravel/pulse` | Monitoring production | Officiel |
| `laravel/horizon` | Monitoring queue Redis | Officiel |
| `laravel/telescope` | Debug (dev only) | Officiel |
| `laravel/pennant` | Feature flags | Officiel |
| `spatie/laravel-permission` | Rôles & permissions | Communauté |
| `spatie/laravel-medialibrary` | Gestion médias & conversions | Communauté |
| `spatie/laravel-activitylog` | Audit trail automatique | Communauté |
| `spatie/laravel-data` | DTO & validation | Communauté |
| `spatie/laravel-query-builder` | API filtrables | Communauté |
| `notchpay/laravel-sdk` | Paiement MoMo (fallback) | SDK officiel |

## 🔐 Décisions verrouillées

| Décision | Valeur |
|----------|--------|
| Commission plateforme | **10%** (artiste garde 90%) |
| Frais MoMo (~2,5%) | **Payés par le fan** |
| Multi-plateforme | **Web mobile-first uniquement** |
| Langues | **Bilingue FR/EN dès le lancement** |
| Splits de revenus | **Automatiques** par pourcentage |
| Remboursements | **Cas par cas** via workflow admin |
| Hébergement | **o2switch Grow** |
| KYC artiste | **Léger** : CNI + numéro MoMo |
| Paiements | **Mobile Money uniquement** (Orange + MTN) |

## 📐 Conventions

- **Typage strict** : `declare(strict_types=1)` sur tous les fichiers PHP
- **Attributs PHP** : utiliser les attributs Laravel 13 (modèles, contrôleurs, jobs)
- **i18n** : AUCUNE chaîne en dur — tout via `__('...')`
- **Montants** : INT (centimes FCFA, pas de décimaux)
- **Permissions** : Spatie Permission (plus de gates manuelles)
- **Append-only wallet** : transactions jamais modifiées/supprimées
- **Git** : `main` = production, `develop` = intégration, `feature/{nom}` = fonctionnalités

## 🌐 Services externes

| Service | Usage | Méthode d'auth |
|---------|-------|----------------|
| **Campay** (principal) | Paiement MoMo | `Authorization: Token {permanent_token}` |
| **Notch Pay** (fallback) | Paiement MoMo | SDK officiel Laravel (`notchpay/laravel-sdk`) |
| **Bunny Stream** | Transcodage HLS + CDN | API Key + Signed URLs |
| **Backblaze B2 / R2** | Stockage objet (via MediaLibrary) | S3-compatible |
| **Brevo** | Emails transactionnels | SMTP (`smtp-relay.brevo.com:587`) |
| **Unimatrix** | SMS OTP | `accessKeyId` en paramètre query |
| **Pusher** | Temps réel | App Key + Secret |
| **Cloudflare** | DNS + CDN frontal | API Token |

## ⚠️ o2switch — Contraintes & Solutions

| Contrainte | Solution |
|------------|----------|
| Pas de Supervisor (daemon) | CRON + `flock` avec `queue:work --once --tries=3` |
| I/O 16 Mo/s | 100% des médias sur B2/R2 + Bunny CDN |
| Pas de transcodage lourd | Bunny Stream (transcodage automatique) |
| Pas de WebSocket natif | Pusher (hébergé) ou polling Livewire |
| Timeout PHP | Upload direct client → stockage objet (URL présignée) |

**CRON unique :**
```bash
* * * * * /opt/alt/php83/usr/bin/php /home/user/public_html/artisan schedule:run >> /dev/null 2>&1
```

## 💰 Modèle financier

```
Prix affiché (P) = ex: 2000 FCFA
Frais MoMo (2,5%) = +50 FCFA → payé par le fan
Total payé = 2050 FCFA
Commission plateforme (10% de P) = 200 FCFA
Net artiste = 1800 FCFA (P - commission)
```
