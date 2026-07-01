# 📋 Plan d'implémentation — FanPay

> **Projet :** Plateforme de monétisation musicale (Cameroun)
> **Stack :** Laravel 13 / PHP 8.3+ / MySQL 8 / Redis / Livewire 4 / Flux UI / TailwindCSS v4
> **Statut :** Packages installés, DB configurée, code à implémenter

---

## 📌 Résumé de l'état actuel (July 2026)

| Poste | Statut | Détail |
|-------|--------|--------|
| Laravel 13 | ✅ Installé | Livewire Starter Kit + Fortify |
| Packages métier | ✅ Installés | Spatie Permission, MediaLibrary, ActivityLog, Data, QueryBuilder |
| Packages Laravel | ✅ Installés | Sanctum, Pulse, Pennant, Horizon, Telescope |
| Auth (base) | ✅ Implémentée | Login, register, passkeys, 2FA, email verification |
| Vues de base | ✅ Présentes | Auth, dashboard, settings (profile, appearance, security) |
| **Domain DDD** | ❌ À créer | 9 modules (Users, Catalog, Store, Vip, Events, Dedications, Voting, Wallet, Payments) |
| **Migrations métier** | ❌ À créer | ~25 tables à créer |
| **Services externes** | ❌ À configurer | Campay, Notch Pay, Bunny, B2/R2, Brevo, Unimatrix, Pusher |
| **Tests** | ⚠️ Basiques | Tests Pest de base (auth surtout) |

---

## 🗺️ Phases d'implémentation

### Phase 0 — Fondations (Semaine 1)

**Objectif :** Structure DDD, permissions, i18n, layout de base

| # | Tâche | Fichiers/Détails | Dépendances |
|---|-------|-----------------|-------------|
| 0.1 | **Structure Domain DDD** | Créer `app/Domain/{Users,Catalog,Store,Vip,Events,Dedications,Voting,Wallet,Payments,Content}/` avec sous-dossiers Models, Actions, Enums, Events | — |
| 0.2 | **Configurer Spatie Permission** | Publier migration, créer rôles (admin, artist, fan) + permissions dans seeder, middleware | 0.1 |
| 0.3 | **Configurer Spatie MediaLibrary** | Publier migration, configurer disque `b2` dans `config/filesystems.php` | — |
| 0.4 | **Configurer Spatie ActivityLog** | Publier migration | — |
| 0.5 | **Publier config Laravel Pulse** | Publier migration + config | — |
| 0.6 | **Configurer i18n FR/EN** | Créer `lang/fr/` + `lang/en/`, middleware `SetLocale`, sélecteur de langue dans le header | — |
| 0.7 | **Migrer User existant → Domain/Users** | Déplacer User.php vers `Domain/Users/Models/User.php`, ajouter `phone`, `locale`, `role` | 0.1 |
| 0.8 | **Layout Blade mobile-first** | Finaliser layout app.blade.php, sidebar/header responsive, composants communs | — |
| 0.9 | **Configurer Laravel Horizon & Queue** | Config via provider, prévoir `--once --tries=3` pour o2switch | — |
| 0.10 | **Configurer Sanctum** | SPA auth config, middleware API | — |
| 0.11 | **Seeders** | Rôles + permissions + admin par défaut + settings initiaux | 0.2 |

---

### Phase 1 — Module Users & Auth (Semaine 2)

**Objectif :** Auth complète avec OTP SMS, profils artiste, KYC

| # | Tâche | Détails |
|---|-------|---------|
| 1.1 | **Migration `users` enrichie** | Ajouter colonnes : `phone`, `phone_verified_at`, `role` (enum), `locale`, `two_factor_enabled`, soft deletes |
| 1.2 | **Modèle User enrichi** | Attributs casts, relations (profile, wallet, kyc), méthodes `canRequestPayout()`, `hasVipAccess()` |
| 1.3 | **Migration `artist_profiles`** | Table profils artiste (stage_name, slug, bio, avatar, cover, genre_id, is_verified, social_links, stats, customization) |
| 1.4 | **Modèle ArtistProfile** | Relations, sluggable, accès au mini-site |
| 1.5 | **Migration `kyc_verifications`** | Table KYC (id_document, momo_phone, status, verified_by) |
| 1.6 | **Modèle KycVerification** | Status workflow (pending → approved/rejected) |
| 1.7 | **Migration `otp_codes`** | Table OTP (phone, code hashé, purpose, attempts, expires_at) |
| 1.8 | **Actions Domain/Users** | `RegisterUserAction`, `SendOtpAction`, `VerifyOtpAction`, `RequestKycAction`, `ApproveKycAction` |
| 1.9 | **Service SMS** | `SmsService` + `UnimatrixProvider` (interface SmsProvider) |
| 1.10 | **Job `SendOtpSms`** | Queue job pour envoi SMS OTP |
| 1.11 | **Fortify personnalisé** | Adapter Fortify (CreateNewUser + phone comme identifiant principal) |
| 1.12 | **Contrôleurs Auth** | Login OTP, Register avec OTP, Reset password avec OTP |
| 1.13 | **Middleware rôles** | `EnsureRole` (redirect si mauvais rôle) |
| 1.14 | **Tests Auth** | Inscription, connexion OTP, KYC workflow |

---

### Phase 2 — Wallet & Paiements (Semaines 3-4)

**Objectif :** Cœur financier (wallet append-only, Campay, Notch Pay)

| # | Tâche | Détails |
|---|-------|---------|
| 2.1 | **Migration `wallets`** | Table wallets (user_id unique, balance, currency XAF) |
| 2.2 | **Migration `transactions`** | Table append-only (reference, wallet_id, type credit/debit, amount, balance_before/after, source, status) |
| 2.3 | **Migration `payments`** | Table paiements (reference, user_id, amount, fees, phone, operator, gateway, external_ref, status, webhook_payload) |
| 2.4 | **Migration `payouts`** | Table retraits (reference, artist_id, amount, phone, operator, status, approved_by) |
| 2.5 | **Migration `revenue_splits`** | Table splits (splittable polymorphique, artist_id, percentage) |
| 2.6 | **Migration `settings`** | Table settings (key/value : commission_rate=10, min_payout=5000, momo_fee=2.5, etc.) |
| 2.7 | **Interface `PaymentGateway`** | Contrat : initiateCollect, initiatePayout, verifyWebhookSignature |
| 2.8 | **Service `CampayService`** | Implémentation Campay (API collect, withdraw, webhook JWT) |
| 2.9 | **Service `NotchPayService`** | Implémentation Notch Pay via SDK (fallback) |
| 2.10 | **Service `WalletService`** | Credit, debit, lockForUpdate, append-only invariant |
| 2.11 | **Service `CommissionService`** | Calcul commission 10%, frais MoMo 2.5%, net artiste |
| 2.12 | **Service `RevenueSplitService`** | Validation 100%, répartition automatique |
| 2.13 | **Action `ProcessPaymentAction`** | Orchestrateur avec fallback (Campay → Notch Pay) |
| 2.14 | **Action `RequestPayoutAction`** | Vérifications KYC, solde, montants min/max |
| 2.15 | **WebhookController** | POST `/api/webhooks/payment` → résolution gateway → dispatch `ProcessPaymentWebhook` |
| 2.16 | **Job `ProcessPaymentWebhook`** | Idempotence, update payment, credit wallet, splits, déblocage accès |
| 2.17 | **Job `ProcessPayout`** | Appel API payout, update statut |
| 2.18 | **Commande `ReconcilePayments`** | Polling paiements pending > 30 min |
| 2.19 | **Commande `CheckFinancialInvariant`** | Vérification quotidienne Σ soldes = Σ crédits - Σ débits |
| 2.20 | **Tests Wallet/Paiements** | Commission, splits, idempotence webhook, invariant |

---

### Phase 3 — Module Wallet UI (Semaine 5)

**Objectif :** Interface wallet pour artistes (solde, transactions, retraits)

| # | Tâche | Détails |
|---|-------|---------|
| 3.1 | **Livewire `ArtistWallet`** | Solde, historique transactions, formulaire retrait |
| 3.2 | **Page wallet artiste** | Route `/artist/wallet`, `/artist/wallet/request-payout` |
| 3.3 | **Admin Payout approval** | Liste retraits en attente, approuver/rejeter |
| 3.4 | **Notifications vente** | Notification in-app + email à l'artiste |

---

### Phase 4 — Catalogue & Streaming (Semaines 6-8)

**Objectif :** Upload musique, pipeline HLS, player, découverte

| # | Tâche | Détails |
|---|-------|---------|
| 4.1 | **Migration `genres`** | Table genres (name, name_en, slug) |
| 4.2 | **Migration `albums`** | Table albums (artist_id, title, slug, cover, type, price, is_published, etc.) |
| 4.3 | **Migration `tracks`** | Table tracks (album_id, artist_id, title, duration, file_path, preview_path, price, is_free, play_count, etc.) + FULLTEXT index |
| 4.4 | **Migration `videos`** | Table videos (artist_id, title, file_path, preview_path, price, view_count) |
| 4.5 | **Migration `plays`** | Table append-only pour écoutes (track_id, user_id, ip_address, played_at) |
| 4.6 | **Modèles Catalog** | Genre, Album, Track, Video, Play (avec relations, MediaLibrary collections) |
| 4.7 | **Actions Catalog** | `UploadTrackAction`, `PublishAlbumAction`, `TrackPlayAction` |
| 4.8 | **Service `BunnyStreamService`** | Création vidéo Bunny, fetch, vérification statut |
| 4.9 | **Service `MediaService`** | Upload direct client → B2/R2 via URL présignée |
| 4.10 | **Job `TranscodeMedia`** | Déclenchement transcodage Bunny Stream |
| 4.11 | **Webhook Bunny** | POST `/api/webhooks/bunny` → mise à jour track/video HLS |
| 4.12 | **Pages catalogue** | Pages publiques artiste, album, track, video |
| 4.13 | **Player audio/vidéo** | Mini-player persistant (Howler.js) + player HLS (HLS.js) |
| 4.14 | **Découverte/recherche** | Charts, genres, recherche FULLTEXT, nouveautés |
| 4.15 | **Tests Catalogue** | Upload, publication, player |

---

### Phase 5 — Store & Boutique (Semaine 9)

**Objectif :** Produits numériques/physiques, commandes

| # | Tâche | Détails |
|---|-------|---------|
| 5.1 | **Migration `products`** | Table products (artist_id, type digital/physical, name, price, stock, file_path) |
| 5.2 | **Migration `orders`** | Table orders (reference, user_id, artist_id, total, fees, commission, net_amount, status) |
| 5.3 | **Migration `order_items`** | Table order_items (order_id, product_id, purchasable polymorphique, qty, unit_price) |
| 5.4 | **Modèles Store** | Product, Order, OrderItem |
| 5.5 | **Actions Store** | `CreateOrderAction`, `ProcessOrderAction` |
| 5.6 | **Pages boutique** | Store artiste, page produit, panier |
| 5.7 | **Contrôle d'accès** | Vérification achat pour produits digitaux |

---

### Phase 6 — VIP Abonnements (Semaine 10)

**Objectif :** Plans VIP prepaid/top-up

| # | Tâche | Détails |
|---|-------|---------|
| 6.1 | **Migration `vip_plans`** | Table plans (artist_id, name, duration_days, price, perks JSON) |
| 6.2 | **Migration `vip_subscriptions`** | Table subscriptions (user_id, plan_id, starts_at, expires_at, status) |
| 6.3 | **Modèles VIP** | VipPlan, VipSubscription |
| 6.4 | **Actions VIP** | `SubscribeVipAction`, `RenewVipAction` |
| 6.5 | **Page VIP artiste** | Plans disponibles, abonnement |
| 6.6 | **Commande `CheckVipExpirations`** | CRON quotidien désactivation VIP expirés |
| 6.7 | **Commande `SendVipRenewalReminders`** | Rappel J-3 avant expiration |
| 6.8 | **Contenu VIP exclusif** | Colonne `is_vip_exclusive` sur tracks/albums/videos |

---

### Phase 7 — Dédicaces (Semaine 11)

**Objectif :** Dédicaces personnalisées audio/vidéo/texte

| # | Tâche | Détails |
|---|-------|---------|
| 7.1 | **Migration `dedications`** | Table dedicaces (artist_id, fan_id, message_request, price, status, response_type/media/message) |
| 7.2 | **Modèle Dedication** | Workflow pending → in_progress → delivered |
| 7.3 | **Actions Dedications** | `RequestDedicationAction`, `DeliverDedicationAction` |
| 7.4 | **Pages dédicaces** | Commande fan, dashboard réponse artiste |

---

### Phase 8 — Événements & Billetterie (Semaines 12-13)

**Objectif :** Création d'événements, vente billets, QR code scan

| # | Tâche | Détails |
|---|-------|---------|
| 8.1 | **Migration `events`** | Table events (artist_id, title, slug, venue, date, is_published) |
| 8.2 | **Migration `ticket_types`** | Table ticket_types (event_id, name, price, quantity, sold) |
| 8.3 | **Migration `tickets`** | Table tickets (ticket_type_id, user_id, qr_code unique, status) |
| 8.4 | **Migration `ticket_scans`** | Table scans (ticket_id, scanned_by, status, scanned_at) |
| 8.5 | **Modèles Events** | Event, TicketType, Ticket, TicketScan |
| 8.6 | **Actions Events** | `CreateEventAction`, `PurchaseTicketAction`, `ScanTicketAction` |
| 8.7 | **Génération QR code** | QR unique par billet avec UUID |
| 8.8 | **Scan billets (Livewire)** | Interface scan caméra (html5-qrcode) + validation |
| 8.9 | **Pages événements** | Page publique, dashboard artiste |

---

### Phase 9 — Vote & Concours (Semaine 14)

**Objectif :** Concours musicaux payants

| # | Tâche | Détails |
|---|-------|---------|
| 9.1 | **Migration `contests`** | Table contests (title, vote_price, starts_at, ends_at, status) |
| 9.2 | **Migration `candidates`** | Table candidates (contest_id, artist_id, vote_count) |
| 9.3 | **Migration `votes`** | Table votes (candidate_id, user_id, quantity, amount, ip_address) |
| 9.4 | **Modèles Voting** | Contest, Candidate, Vote |
| 9.5 | **Actions Voting** | `CreateContestAction`, `CastVoteAction` |
| 9.6 | **Pages concours** | Page publique classement, vote, gestion admin |

---

### Phase 10 — Tips (Semaine 15)

**Objectif :** Dons/soutien direct aux artistes

| # | Tâche | Détails |
|---|-------|---------|
| 10.1 | **Action `TipArtistAction`** | Initie paiement + crédit wallet artiste |
| 10.2 | **Bouton Tip** | Sur page artiste, montant libre (min 500 FCFA) |
| 10.3 | **Message optionnel** | Message du fan visible par l'artiste |

---

### Phase 11 — Admin Back-office (Semaines 15-16)

**Objectif :** Filament Resources pour l'admin

| # | Tâche | Détails |
|---|-------|---------|
| 11.1 | **Installer Filament** | `composer require filament/filament` |
| 11.2 | **Resources Filament** | UserResource, ArtistProfileResource, KycVerificationResource, AlbumResource, TrackResource, OrderResource, PayoutResource, DisputeResource, ContestResource, SettingsResource |
| 11.3 | **Dashboard admin** | Widgets : GMV, commissions, transactions, alertes |
| 11.4 | **Pages Filament** | KYC queue, Payouts approval, Disputes |
| 11.5 | **Protection accès** | Filament uniquement accessible aux admins |

---

### Phase 12 — Notifications (Semaine 16)

**Objectif :** Système complet de notifications

| # | Tâche | Détails |
|---|-------|---------|
| 12.1 | **Configurer Brevo** | SMTP, template emails transactionnels |
| 12.2 | **Notifications Laravel** | SaleReceived, KycApproved, PayoutProcessed, VipExpiring, DedicationDelivered |
| 12.3 | **Notifications in-app** | Badge header, liste déroulante |
| 12.4 | **Notifications email** | Reçus, confirmations via Brevo |

---

### Phase 13 — Content Rights & Légal (Semaine 17)

**Objectif :** Déclaration droits d'auteur, pages légales

| # | Tâche | Détails |
|---|-------|---------|
| 13.1 | **Migration `content_rights`** | Table déclarations (artist_id, uploadable, declaration_accepted_at, socam_member) |
| 13.2 | **Action `AcceptContentRights`** | Checkbox CGU obligatoire à l'upload |
| 13.3 | **Pages légales** | CGU, CGV, confidentialité, mentions légales (bilingues) |
| 13.4 | **Page "Mes données"** | Export JSON, suppression de compte |
| 13.5 | **Migration `disputes`** | Table litiges (user_id, transaction_id, reason, description, status) |
| 13.6 | **Workflow litige** | Ouverture, admin review, refund/reject |

---

### Phase 14 — Finitions, Tests & Déploiement (Semaines 18-20)

**Objectif :** Tests, sécurisation, déploiement o2switch

| # | Tâche | Détails |
|---|-------|---------|
| 14.1 | **Tests unitaires** | Commission, splits, wallet invariant, OTP, KYC workflow |
| 14.2 | **Tests feature** | Parcours complets : achat → paiement → déblocage, retrait |
| 14.3 | **Tests idempotence** | Rejeu webhook = pas de double crédit |
| 14.4 | **Tests sécurité** | Rate limiting, authentification, accès rôles |
| 14.5 | **Seeders complets** | Données de démo : artistes, albums, tracks, ventes |
| 14.6 | **Sécurité** | Headers CSP, validation entrées, sanitization |
| 14.7 | **Optimisations** | Cache Redis, Varnish config, OPcache |
| 14.8 | **Déploiement o2switch** | Script deploy.sh, CRON, .env production |
| 14.9 | **Health check** | Endpoint GET /api/health |
| 14.10 | **Monitoring** | Pulse dashboard + Horizon |
| 14.11 | **Documentation** | Mise à jour CLAUDE.md, README |

---

## 📊 Estimation des charges

| Phase | Semaines | Tâches | Complexité |
|-------|----------|--------|------------|
| P0 — Fondations | 1 | 11 | 🟡 Moyenne |
| P1 — Users & Auth | 1 | 14 | 🔴 Élevée |
| P2 — Wallet & Paiements | 2 | 20 | 🔴 Très élevée |
| P3 — Wallet UI | 1 | 4 | 🟢 Faible |
| P4 — Catalogue & Streaming | 3 | 15 | 🔴 Très élevée |
| P5 — Store | 1 | 7 | 🟡 Moyenne |
| P6 — VIP | 1 | 8 | 🟡 Moyenne |
| P7 — Dédicaces | 1 | 4 | 🟢 Faible |
| P8 — Événements | 2 | 9 | 🔴 Élevée |
| P9 — Vote & Concours | 1 | 6 | 🟡 Moyenne |
| P10 — Tips | 0.5 | 3 | 🟢 Faible |
| P11 — Admin Filament | 1.5 | 5 | 🟡 Moyenne |
| P12 — Notifications | 1 | 4 | 🟢 Faible |
| P13 — Content & Légal | 1 | 6 | 🟡 Moyenne |
| P14 — Finitions, Tests | 3 | 11 | 🔴 Élevée |

**Total estimé : ~20 semaines** (5 mois) — conforme au plan produit

---

## 🔗 Dépendances critiques entre phases

```
P0 (Fondations)
 └── P1 (Users & Auth)
      └── P2 (Wallet & Paiements) ← CŒUR FINANCIER
           ├── P3 (Wallet UI)
           ├── P4 (Catalogue)
           │    ├── P5 (Store)
           │    ├── P6 (VIP)
           │    ├── P7 (Dédicaces)
           │    └── P10 (Tips)
           ├── P8 (Événements)
           └── P9 (Vote)
P11 (Admin) ← parallèle après P2
P12 (Notifications) ← parallèle après P1
P13 (Légal) ← parallèle après P0
P14 (Finitions) ← après toutes les phases
```

---

## 🚀 Priorisation recommandée

### Par niveau de risque

| Priorité | Phase | Justification |
|----------|-------|---------------|
| 🔴 P0 | Fondations | Tout repose sur la structure DDD et l'i18n |
| 🔴 P2 | Wallet & Paiements | Risque principal = flux financier (Campay, webhooks) |
| 🔴 P4 | Catalogue & Streaming | Cœur métier (upload, HLS, player) |
| 🟡 P1 | Users & Auth | Auth OTP critique mais bien cadré |
| 🟡 P8 | Événements | QR code + scan = complexité technique |
| 🟢 P5-P10 | Store, VIP, Dédicaces, Vote, Tips | Modules métier bien isolés |

### Par valeur business

1. **P4 (Catalogue)** + **P2 (Paiements)** = MVP fonctionnel (l'artiste upload, le fan achète, écoute)
2. **P6 (VIP)** = Revenu récurrent
3. **P8 (Événements)** + **P9 (Vote)** = Engagement communautaire
4. **P7 (Dédicaces)** = Différenciation

---

## 📐 Ordre de démarrage recommandé

```
Semaine 1 : P0 (Fondations)
Semaine 2 : P1 (Users & Auth)
Semaines 3-4 : P2 (Wallet & Paiements)
Semaine 5 : P3 (Wallet UI)
Semaines 6-8 : P4 (Catalogue & Streaming)
Semaine 9 : P5 (Store)
Semaine 10 : P6 (VIP)
Semaine 11 : P7 (Dédicaces)
Semaines 12-13 : P8 (Événements)
Semaine 14 : P9 (Vote)
Semaine 15-16 : P10 (Tips) + P11 (Admin) + P12 (Notifications)
Semaine 17 : P13 (Légal)
Semaines 18-20 : P14 (Finitions, Tests, Déploiement)
```

---

## ✅ Prochaine étape

Voulez-vous que je commence par la **Phase 0** ? Voici ce que ça implique :

1. Créer la structure `app/Domain/` avec tous les modules
2. Publier les migrations Spatie (Permission, MediaLibrary, ActivityLog)
3. Configurer i18n FR/EN
4. Enrichir le modèle User avec phone, locale, role
5. Créer les rôles et permissions (admin, artist, fan)
6. Finaliser le layout Blade mobile-first
7. Configurer les queues et Horizon

Souhaitez-vous que je lance la Phase 0 maintenant, ou avez-vous des ajustements à apporter au plan ?
