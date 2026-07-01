# 📋 Cahier des Charges Technique — FanPay Musique

> **Plateforme de monétisation musicale (Cameroun)**
> Stack : Laravel 11 + PHP 8.3 + MySQL 8 + Redis + Livewire + TailwindCSS
> Hébergement : o2switch Grow (mutualisé cPanel LiteSpeed)

---

## Sommaire des documents

| # | Document | Description |
|---|----------|-------------|
| **00** | [`docs/00-index.md`](./00-index.md) | **← Vous êtes ici** — Sommaire, conventions & glossaire |
| **01** | [`docs/01-architecture.md`](./01-architecture.md) | Architecture technique : stack, o2switch, modules Laravel, déploiement |
| **02** | [`docs/02-database.md`](./02-database.md) | Schéma complet de la base de données (tables, colonnes, relations, index) |
| **03** | [`docs/03-auth-users-kyc.md`](./03-auth-users-kyc.md) | Authentification, rôles (Fan/Artiste/Admin), OTP SMS, KYC, profils artiste |
| **04** | [`docs/04-payments-wallet.md`](./04-payments-wallet.md) | Paiements Mobile Money (Campay/Notch Pay), wallet interne, collect, payout, revenue splits, réconciliation |
| **05** | [`docs/05-catalog-streaming.md`](./05-catalog-streaming.md) | Catalogue musical, upload médias, pipeline HLS, player, DRM léger |
| **06** | [`docs/06-monetization.md`](./06-monetization.md) | Boutique, abonnements VIP prepaid, dédicaces, événements/billetterie, vote/concours, tips |
| **07** | [`docs/07-api-endpoints.md`](./07-api-endpoints.md) | Routes web (Laravel), endpoints API, webhooks, endpoints de paiement |
| **08** | [`docs/08-security-compliance.md`](./08-security-compliance.md) | Sécurité, anti-fraude, conformité juridique (Cameroun), i18n, protection des données |
| **09** | [`docs/09-frontend-ux.md`](./09-frontend-ux.md) | Écrans, design system (Tailwind), notifications, analytics |
| **10** | [`docs/10-devops-testing.md`](./10-devops-testing.md) | Déploiement o2switch, CRON/flock, cache, stratégie de tests, variables d'environnement |

---

## Conventions générales du projet

### 1. Conventions de code

| Règle | Valeur |
|-------|--------|
| **Langage** | PHP 8.3+ avec typage strict (`declare(strict_types=1)`) |
| **Framework** | Laravel 13 — attributs PHP natifs pour modèles, contrôleurs, jobs. PSR-4, PSR-12 |
| **Attributs Laravel 13** | Utiliser les attributs PHP plutôt que les propriétés de classe : `#[Model]`, `#[Middleware]`, `#[Queueable]`, etc. |
| **Frontend** | Blade + Livewire 3 + Filament v4 (admin) + TailwindCSS v4 (mobile-first) |
| **Permissions** | Spatie Laravel Permission (rôles & permissions) |
| **Médias** | Spatie Laravel MediaLibrary (uploads, conversions) |
| **Audit** | Spatie Laravel ActivityLog (audit trail automatique) |
| **Paiement** | Notch Pay SDK Laravel officiel pour fallback |
| **Monitoring** | Laravel Pulse (prod) + Horizon (queue) + Telescope (dev) |
| **Base de données** | MySQL 8 — migrations Laravel uniquement (pas de SQL manuel) |
| **Nommage tables** | Pluriel, snake_case : `users`, `artist_profiles`, `revenue_splits` |
| **Nommage colonnes** | snake_case : `stage_name`, `is_verified`, `payment_ref` |
| **Nommage routes** | kebab-case : `/artist/{slug}/vip`, `/api/webhooks/payment` |
| **Clés étrangères** | `{table}_id` → `id` sur la table référencée |
| **Index** | Index sur toutes les clés étrangères + colonnes de recherche fréquentes |
| **Timestamps** | `created_at`, `updated_at` sur toutes les tables (Laravel auto) |
| **Soft deletes** | `deleted_at` sur les tables concernées (catalogue, produits, événements) |

### 2. Conventions i18n (bilingue FR/EN)

- **Aucune chaîne en dur dans le code.** Tout passe par `__('...')` ou `@lang('...')`.
- Fichiers de langue : `resources/lang/{fr,en}/` (fichiers PHP) + `resources/lang/{fr,en}.json` (fichiers JSON).
- Middleware de locale : détection (1) utilisateur connecté → `users.locale`, (2) cookie, (3) `Accept-Language`, (4) FR par défaut.
- Sélecteur de langue dans le header principal (FR | EN).
- Colonne `locale` sur `users` (enum: 'fr', 'en', défaut 'fr').
- Contenu artiste (titres, bios) : saisi librement par l'artiste — pas de double saisie obligatoire.

### 3. Conventions de nommage des commits

```
feat: Ajout du module wallet avec transactions
fix: Correction du calcul des frais MoMo
docs: Mise à jour du schéma DB
chore: Mise à jour des dépendances Composer
wip: [phase] Description du travail en cours
```

### 4. Conventions de déploiement

- Branche `main` = production. Branche `develop` = intégration.
- Branches de fonctionnalité : `feature/{nom}`.
- Un seul CRON o2switch : `* * * * * php artisan schedule:run >> /dev/null 2>&1`.
- Worker de queue : CRON + `flock` (détaillé dans [`docs/10-devops-testing.md`](./10-devops-testing.md)).
- Configuration via `.env` uniquement (jamais en dur dans le code).

### 5. Conventions de test

- Tests unitaires : logique métier (commissions, splits, calculs financiers).
- Tests feature : parcours complets (achat → paiement → déblocage).
- Commande : `php artisan test` ou `php vendor/bin/phpunit`.
- Base de test : SQLite en mémoire (`:memory:`) ou MySQL de test.

---

## Glossaire

| Terme | Définition |
|-------|------------|
| **MoMo** | Mobile Money (Orange Money, MTN MoMo) |
| **Campay** | Agrégateur de paiement Mobile Money Cameroun (principal) |
| **Notch Pay** | Agrégateur de paiement Mobile Money (fallback) |
| **XAF / FCFA** | Franc CFA (CEMAC) — devise unique de la plateforme |
| **OTP** | One-Time Password (authentification) |
| **KYC** | Know Your Customer (vérification d'identité artiste) |
| **HLS** | HTTP Live Streaming (protocole de streaming adaptatif) |
| **DRM** | Digital Rights Management (protection de contenu) |
| **GMV** | Gross Merchandise Volume (volume total des transactions) |
| **Payout** | Retrait des fonds du wallet vers Mobile Money |
| **Revenue Split** | Répartition automatique des revenus entre collaborateurs |
| **Prepaid / Top-up** | Modèle d'abonnement VIP payé à l'avance (pas de récurrence auto) |
| **SONACAM** | Société Nationale Camerounaise de l'Art Musical (gestion collective des droits) |
| **ANTIC** | Agence Nationale des Technologies de l'Information et de la Communication |
| **o2switch** | Hébergeur français (mutualisé cPanel LiteSpeed) |
| **Bunny Stream** | Service de transcodage et diffusion HLS via CDN |
| **Backblaze B2** | Stockage objet S3-compatible (0 egress) |
| **Cloudflare R2** | Stockage objet S3-compatible (0 egress) |
| **Signed URL** | URL temporaire signée (protection de contenu) |
| **Varnish / XtremCache** | Cache full-page disponible sur o2switch (LiteSpeed) |
| **flock** | Utilitaire Linux de verrouillage de fichier (pour worker queue sans démon) |
| **Brevo** | Service d'emails transactionnels (ex-Sendinblue) |
| **Unimatrix** | Fournisseur SMS local Cameroun |
| **Bilinguisme** | FR/EN — l'interface est entièrement bilingue dès le lancement |

---

## Documents liés

- [Plan produit complet](../PLAN_PLATEFORME_MUSIQUE.md) — document source de référence

---

> **Prochain document :** [`docs/01-architecture.md`](./01-architecture.md) — Architecture technique détaillée
