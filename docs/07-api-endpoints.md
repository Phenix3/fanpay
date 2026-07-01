# 🌐 07 — Routes API & Webhooks

> **Web :** Laravel routes (Blade + Livewire)
> **API :** Sanctum (SPA ou tokens)
> **Webhooks :** Campay, Notch Pay, Bunny Stream

---

## Table des matières

1. [Routes Web (Blade/Livewire)](#1-routes-web-bladelivewire)
2. [Routes API (webhooks)](#2-routes-api-webhooks)
3. [Routes API (publiques)](#3-routes-api-publiques)
4. [Routes API (authentifiées)](#4-routes-api-authentifiées)
5. [Webhooks détaillés](#5-webhooks-détaillés)
6. [Références externes](#6-références-externes)

---

## 1. Routes Web (Blade/Livewire)

### 1.1 Pages publiques (guest)

| Méthode | URI | Controller/Action | Description |
|---------|-----|------------------|-------------|
| GET | `/` | HomeController | Accueil / découverte |
| GET | `/search` | SearchController | Recherche |
| GET | `/search?q={query}` | SearchController | Résultats recherche |
| GET | `/chart` | ChartController | Top écoutes / ventes |
| GET | `/genre/{slug}` | GenreController | Pages par genre |
| GET | `/artist/{slug}` | ArtistProfileController | Page publique artiste (mini-site) |
| GET | `/artist/{slug}/music` | ArtistMusicController | Catalogue musique artiste |
| GET | `/artist/{slug}/store` | ArtistStoreController | Boutique artiste |
| GET | `/artist/{slug}/events` | ArtistEventsController | Événements artiste |
| GET | `/artist/{slug}/vip` | ArtistVipController | Plans VIP artiste |
| GET | `/artist/{slug}/dedications` | ArtistDedicationsController | Dédicaces artiste |
| GET | `/album/{slug}` | AlbumController | Page album |
| GET | `/track/{id}/play` | TrackPlayController | Player track |
| GET | `/video/{id}/play` | VideoPlayController | Player vidéo |
| GET | `/event/{slug}` | EventController | Page événement |
| GET | `/contest/{id}` | ContestController | Page concours |
| GET | `/legal/terms` | LegalController | CGU/CGV |
| GET | `/legal/privacy` | LegalController | Politique confidentialité |
| GET | `/legal/mentions` | LegalController | Mentions légales |

### 1.2 Authentification (guest)

| Méthode | URI | Controller | Description |
|---------|-----|-----------|-------------|
| GET | `/login` | Auth\LoginController | Page connexion |
| POST | `/login` | Auth\LoginController | Connexion |
| GET | `/register` | Auth\RegisterController | Page inscription |
| POST | `/register` | Auth\RegisterController | Inscription |
| POST | `/send-otp` | Auth\OtpController | Envoi OTP SMS |
| POST | `/verify-otp` | Auth\OtpController | Vérification OTP |
| GET | `/password/reset` | Auth\PasswordResetController | Page réinit MDP |
| POST | `/password/send-otp` | Auth\PasswordResetController | Envoi OTP réinit |
| POST | `/password/reset` | Auth\PasswordResetController | Réinit MDP |
| GET | `/locale/{locale}` | LocaleController | Changer langue (FR/EN) |

### 1.3 Dashboard Fan (auth:fan,artist)

| Méthode | URI | Livewire Component | Description |
|---------|-----|-------------------|-------------|
| GET | `/dashboard` | Dashboard\Fan\Overview | Accueil fan |
| GET | `/library` | Dashboard\Fan\Library | Bibliothèque / achats |
| GET | `/library/tickets` | Dashboard\Fan\Tickets | Mes billets (QR codes) |
| GET | `/library/vip` | Dashboard\Fan\VipSubscriptions | Mes abonnements VIP |
| GET | `/orders` | Dashboard\Fan\Orders | Historique commandes |
| GET | `/profile` | Dashboard\Fan\Profile | Profil & paramètres |
| GET | `/settings` | Dashboard\Fan\Settings | Paramètres compte |

### 1.4 Dashboard Artiste (auth:artist,admin)

| Méthode | URI | Livewire Component | Description |
|---------|-----|-------------------|-------------|
| GET | `/artist/dashboard` | Dashboard\Artist\Overview | Vue d'ensemble (revenus, stats) |
| GET | `/artist/catalog` | Dashboard\Artist\Catalog | Gestion catalogue |
| GET | `/artist/catalog/create-album` | Dashboard\Artist\CreateAlbum | Création album |
| GET | `/artist/catalog/upload-track` | Dashboard\Artist\UploadTrack | Upload track |
| GET | `/artist/catalog/upload-video` | Dashboard\Artist\UploadVideo | Upload clip |
| GET | `/artist/store` | Dashboard\Artist\Store | Gestion boutique |
| GET | `/artist/vip` | Dashboard\Artist\VipPlans | Gestion plans VIP |
| GET | `/artist/events` | Dashboard\Artist\Events | Gestion événements |
| GET | `/artist/events/{id}/scan` | Dashboard\Artist\ScanTickets | Scan billets |
| GET | `/artist/dedications` | Dashboard\Artist\Dedications | Gestion dédicaces |
| GET | `/artist/wallet` | Dashboard\Artist\Wallet | Wallet & retraits |
| GET | `/artist/wallet/request-payout` | Dashboard\Artist\RequestPayout | Demande retrait |
| GET | `/artist/settings` | Dashboard\Artist\Settings | Personnalisation mini-site |
| GET | `/artist/kyc` | Dashboard\Artist\Kyc | Vérification KYC |

### 1.5 Back-office Admin (auth:admin)

| Méthode | URI | Livewire Component | Description |
|---------|-----|-------------------|-------------|
| GET | `/admin/dashboard` | Dashboard\Admin\Overview | Supervision GMV, transactions |
| GET | `/admin/kyc` | Dashboard\Admin\KycQueue | File d'attente KYC |
| GET | `/admin/payouts` | Dashboard\Admin\Payouts | Approbation retraits |
| GET | `/admin/disputes` | Dashboard\Admin\Disputes | Gestion litiges |
| GET | `/admin/contests` | Dashboard\Admin\Contests | Gestion concours |
| GET | `/admin/users` | Dashboard\Admin\Users | Gestion utilisateurs |
| GET | `/admin/settings` | Dashboard\Admin\Settings | Paramètres plateforme |

**Actions AJAX/Livewire (admin) :**
| Action | Méthode | Description |
|--------|---------|-------------|
| `approve-kyc` | POST | Approuver KYC artiste |
| `reject-kyc` | POST | Rejeter KYC avec motif |
| `approve-payout` | POST | Approuver retrait |
| `reject-payout` | POST | Rejeter retrait avec motif |
| `resolve-dispute` | POST | Résoudre litige (refund/reject) |

---

## 2. Routes API (webhooks)

### 2.1 Routes webhooks (externes)

| Méthode | URI | Controller | Middleware | Description |
|---------|-----|-----------|------------|-------------|
| POST | `/api/webhooks/payment` | Api\WebhookController | throttle:60,1 | Webhook Campay / Notch Pay |
| POST | `/api/webhooks/bunny` | Api\BunnyWebhookController | throttle:60,1 | Webhook Bunny Stream (transcodage terminé) |
| POST | `/api/webhooks/brevo` | Api\BrevoWebhookController | throttle:60,1 | Webhook Brevo (bounces, opens) |

### 2.2 Détail du webhook paiement

```php
// Route : POST /api/webhooks/payment
// Headers : X-Campay-Signature (Campay) ou X-NotchPay-Signature (Notch Pay)

// Payload Campay (exemple) :
{
    "reference": "CAMPAY-REF-123",
    "external_reference": "PAY-20260701-ABCD",
    "status": "SUCCESSFUL",
    "amount": 2050,
    "currency": "XAF",
    "operator": "MTN",
    "phone": "691234567",
    "transaction_id": "MTN-TX-98765",
    "reason": "payment"
}

// Traitement :
→ Vérifier HMAC signature
→ Dispatch ProcessPaymentWebhook (queue Redis)
→ Répondre 200 ({"status": "received"})
```

### 2.3 Détail du webhook Bunny

```php
// Route : POST /api/webhooks/bunny
// Headers : AccessKey

// Payload Bunny (exemple) :
{
    "video_guid": "abc-123-def",
    "video_name": "Track Title",
    "event_type": "video.ready",
    "video_length": 245,
    "thumbnail_url": "https://...",
    "available_resolutions": ["360p", "480p", "720p", "1080p"]
}

// Traitement :
→ Vérifier AccessKey
→ Mettre à jour Track/Video avec file_path HLS
→ Déclencher génération preview si besoin
→ Répondre 200
```

---

## 3. Routes API (publiques)

Ces endpoints sont utilisés par des requêtes AJAX côté client (pas d'authentification requise).

| Méthode | URI | Controller | Description |
|---------|-----|-----------|-------------|
| GET | `/api/artists/search` | Api\ArtistController | Recherche artistes |
| GET | `/api/tracks/search` | Api\TrackController | Recherche musique |
| GET | `/api/charts/top` | Api\ChartController | Top écoutes |
| GET | `/api/charts/new` | Api\ChartController | Nouveautés |
| GET | `/api/genres` | Api\GenreController | Liste genres |
| GET | `/api/artist/{slug}/catalog` | Api\ArtistController | Catalogue complet artiste |
| GET | `/api/events/upcoming` | Api\EventController | Événements à venir |
| GET | `/api/contest/{id}/ranking` | Api\ContestController | Classement concours (public) |

---

## 4. Routes API (authentifiées)

### 4.1 Endpoints fan

| Méthode | URI | Description |
|---------|-----|-------------|
| GET | `/api/fan/library` | Bibliothèque (achats) |
| GET | `/api/fan/tickets` | Mes billets |
| POST | `/api/fan/tip` | Tip un artiste |
| POST | `/api/payment/initiate` | Initier un paiement (achat, etc.) |
| GET | `/api/payment/{ref}/status` | Vérifier statut paiement |

### 4.2 Endpoints artiste

| Méthode | URI | Description |
|---------|-----|-------------|
| GET | `/api/artist/stats` | Statistiques dashboard |
| GET | `/api/artist/wallet` | Solde wallet |
| POST | `/api/artist/upload-url` | Demander URL présignée upload |
| POST | `/api/artist/payout` | Demander retrait |
| GET | `/api/artist/sales` | Ventes récentes |

---

## 5. Webhooks détaillés

### 5.1 ProcessPaymentWebhook (Job queue)

```php
class ProcessPaymentWebhook implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 30;
    
    public function handle(): void
    {
        DB::transaction(function () {
            // 1. Idempotence : vérifier si déjà traité
            // 2. Mettre à jour Payment
            // 3. Créditer wallet (avec splits si applicable)
            // 4. Mettre à jour Order/Ticket/Subscription
            // 5. Débloquer accès
            // 6. Notifier l'artiste
        });
    }
}
```

### 5.2 Liste des événements déclencheurs

| Événement | Notification | Canal |
|-----------|-------------|-------|
| Paiement reçu (vente) | Artiste | in-app + email |
| Paiement reçu (tip) | Artiste | in-app |
| Billet acheté | Fan | in-app + email (QR) |
| Abonnement VIP souscrit | Fan (confirmation) + Artiste | in-app |
| VIP expiration J-3 | Fan (rappel) | in-app + email |
| Dédicace livrée | Fan | in-app + email |
| Dédicace commandée | Artiste | in-app |
| Réponse KYC | Artiste | in-app + email |
| Retrait approuvé/rejeté | Artiste | in-app + email |
| Litige ouvert | Admin | in-app + email |
| Paiement échoué | Fan | in-app |

---

## 6. Références externes

| Service | Documentation |
|---------|--------------|
| **Campay API** | https://docs.campay.net |
| **Notch Pay API** | https://docs.notchpay.co |
| **Bunny Stream API** | https://docs.bunny.net/reference/bunny-stream |
| **Backblaze B2 (S3)** | https://www.backblaze.com/docs/cloud-storage-s3-compatible-api |
| **Brevo API** | https://developers.brevo.com |
| **Pusher Channels** | https://pusher.com/docs/channels |

---

> **Document suivant :** [`docs/08-security-compliance.md`](./08-security-compliance.md) — Sécurité & Conformité
