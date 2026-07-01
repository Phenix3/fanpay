# 🎨 09 — Frontend & UX

> **Framework :** TailwindCSS v4 + Blade + Livewire 3 + Filament v4 (admin) + Alpine.js
> **Approche :** Mobile-first, responsive, bilingue FR/EN
> **Player :** HLS.js (vidéo) + Howler.js (audio)

---

## Table des matières

1. [Design system](#1-design-system)
2. [Filament v4 — Back-office](#2-filament-v4--back-office)
3. [Liste des écrans](#3-liste-des-écrans)
4. [Composants clés](#4-composants-clés)
5. [Notifications](#5-notifications)
6. [Analytics & mesures](#6-analytics--mesures)
7. [États & feedback](#7-états--feedback)

---

## 1. Design system

### 1.1 Palette de couleurs (à valider)

```css
/* theme/fanpay */
--color-primary: #6B21A8;    /* Violet — identité musicale */
--color-primary-dark: #581C87;
--color-primary-light: #7E22CE;
--color-accent: #F59E0B;     /* Ambre — CTA, highlights */
--color-accent-dark: #D97706;
--color-success: #10B981;    /* Vert — succès paiement */
--color-error: #EF4444;      /* Rouge — erreur */
--color-warning: #F59E0B;    /* Orange — avertissement */
--color-surface: #FFFFFF;
--color-surface-alt: #F9FAFB;
--color-text: #111827;
--color-text-secondary: #6B7280;
--color-border: #E5E7EB;
```

### 1.2 Typographie

```css
/* Police système (performante, pas de Google Fonts en production) */
--font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
--font-heading: 'Inter', sans-serif;

/* Hiérarchie */
--text-xs: 0.75rem;    /* 12px */
--text-sm: 0.875rem;   /* 14px */
--text-base: 1rem;     /* 16px */
--text-lg: 1.125rem;   /* 18px */
--text-xl: 1.25rem;    /* 20px */
--text-2xl: 1.5rem;    /* 24px */
--text-3xl: 1.875rem;  /* 30px */
```

### 1.3 Espacement

```css
--spacing-unit: 4px; /* Multiples de 4 */
/* Utiliser les utilitaires Tailwind : p-2, p-4, p-6, p-8, gap-4, etc. */
```

### 1.4 Composants Tailwind réutilisables

```blade
<!-- Carte artiste (grille découverte) -->
<x-card.artist :artist="$artist" />

<!-- Carte album -->
<x-card.album :album="$album" />

<!-- Bouton de paiement opérateur -->
<x-button.payment-operator operator="orange" :amount="2000" />

<!-- Badges -->
<x-badge type="verified" />
<x-badge type="vip" />

<!-- Player controls -->
<x-player.mini :track="$currentTrack" />

<!-- État vide -->
<x-empty-state icon="music" title="Aucun achat" message="Explorez le catalogue..." />

<!-- Skeleton loader -->
<x-skeleton.card />
<x-skeleton.list count="3" />
```

---

## 2. Filament v4 — Back-office

> **Filament v4** est utilisé pour tout le back-office admin (et éventuellement les dashboards artistes).
> Il remplace les écrans Livewire du dashboard admin.

### 2.1 Pourquoi Filament

| Avantage | Détail |
|----------|--------|
| **Rapidité de dev** | CRUD complet en quelques lignes (Resources) |
| **Tailwind v4 natif** | Design cohérent, responsive |
| **MFA intégré** | Multi-factor authentication prêt à l'emploi |
| **Éditeur Tiptap** | Riche text editing pour bios, descriptions |
| **Ressources imbriquées** | Gestion des relations (ex: tracks dans album) |
| **Widgets & tables** | Dashboard analytics, filtres avancés |
| **Notifications** | Système de notifications intégré |

### 2.2 Structure Filament

```php
// app/Filament/Resources/
├── UserResource.php              # Gestion utilisateurs
├── ArtistProfileResource.php     # Profils artistes
├── KycVerificationResource.php   # File KYC (actions approve/reject)
├── AlbumResource.php             # Albums
├── TrackResource.php             # Pistes
├── OrderResource.php             # Commandes
├── PayoutResource.php            # Retraits (actions approve/reject)
├── DisputeResource.php           # Litiges (actions resolve/reject)
├── ContestResource.php           # Concours
└── SettingsResource.php          # Paramètres plateforme
```

### 2.3 Pages admin remplacées par Filament

| # | Écran (docs/09-frontend-ux.md v1) | Remplacé par Filament Resource
|---|-----------------------------------|------------------------------|
| 29 | Dashboard supervision | Filament Dashboard + Widgets |
| 30 | File KYC | `KycVerificationResource` + Actions |
| 31 | Payouts | `PayoutResource` + Actions |
| 32 | Litiges | `DisputeResource` + Actions |
| 33 | Concours | `ContestResource` |
| 34 | Utilisateurs | `UserResource` |
| 35 | Paramètres | `SettingsResource` (single record) |

## 3. Liste des écrans

### 2.1 Écrans publics (non connecté)

| # | Écran | Éléments Livewire |
|---|-------|-------------------|
| 01 | **Accueil/Découverte** | Hero, charts (top écoutes), nouveautés, genres, artistes en vedette |
| 02 | **Recherche** | Barre recherche, filtres (genre, artiste, titre), résultats |
| 03 | **Page Artiste (mini-site)** | Cover, bio, onglets (musique/boutique/events/VIP), bouton Tip |
| 04 | **Page Album** | Pochette, tracklist, previews, bouton acheter |
| 05 | **Page Track/Video (Player)** | Player plein écran, infos, achat |
| 06 | **Page Événement** | Infos, types billets, achat |
| 07 | **Page Concours** | Classement, voter |
| 08 | **Pages légales** | CGU, CGV, confidentialité, mentions |

### 2.2 Écrans authentification

| # | Écran | Description |
|---|-------|-------------|
| 09 | **Connexion** | Phone/email + password OU OTP |
| 10 | **Inscription** | Phone + email (opt) + password + rôle |
| 11 | **OTP Verification** | Saisie code 6 chiffres |
| 12 | **Réinitialisation mot de passe** | OTP → nouveau mot de passe |

### 2.3 Dashboard Fan (connecté)

| # | Écran | Description |
|---|-------|-------------|
| 13 | **Mon espace** | Récapitulatif : derniers achats, abonnements VIP |
| 14 | **Bibliothèque** | Contenus débloqués (albums, tracks, vidéos) |
| 15 | **Mes billets** | QR codes pour événements à venir |
| 16 | **Historique** | Commandes, paiements |
| 17 | **Mon compte** | Profil, langue, paramètres, sécurité |

### 2.4 Dashboard Artiste (connecté)

| # | Écran | Description |
|---|-------|-------------|
| 18 | **Vue d'ensemble** | Revenus (graphiques), écoutes, ventes récentes, solde wallet |
| 19 | **Catalogue** | Liste albums/singles/vidéos, upload, publication |
| 20 | **Upload** | Upload fichier + formulaire métadonnées + splits collaborateurs |
| 21 | **Boutique** | Produits numériques/physiques, stock |
| 22 | **Plans VIP** | Création/gestion plans, vue des abonnés |
| 23 | **Événements** | Création, types de billets, QR scan |
| 24 | **Scan billets** | Interface scan caméra + validation |
| 25 | **Dédicaces** | Commandes en attente, réponse audio/vidéo/texte |
| 26 | **Wallet / Retrait** | Solde, historique transactions, formulaire retrait |
| 27 | **Personnalisation** | Couleurs, cover, liens sociaux |
| 28 | **KYC** | Upload document, statut vérification |

### 2.5 Back-office Admin (connecté)

| # | Écran | Description |
|---|-------|-------------|
| 29 | **Dashboard supervision** | GMV, commissions, alertes, graphiques |
| 30 | **File KYC** | Liste artistes, documents, approuver/rejeter |
| 31 | **Payouts** | Retraits en attente, approuver/rejeter |
| 32 | **Litiges** | Liste, détails, résoudre (rembourser/rejeter) |
| 33 | **Concours** | Création, gestion, résultats |
| 34 | **Utilisateurs** | Liste, recherche, actions |
| 35 | **Paramètres** | Commissions, seuils, taux frais, plans |

---

## 4. Composants clés

### 3.1 Mini-player persistant (Audio)

```
▸ Barre fixe en bas de l'écran (z-index élevé)
▸ Présent sur toutes les pages (sauf player vidéo plein écran)
▸ Composants :
  ┌──────────────────────────────────────────────────────────────┐
  │ ⏮ ⏸ ⏭  🎵 Titre — Artiste    [♡]    [🔊 ████████░░] 2:34 │
  │            ████████████████░░░░░░░░░░░░░ 4:12             │
  │            [📋 Playlist]     [🔗 Partager]                 │
  └──────────────────────────────────────────────────────────────┘
▸ Persistance : stocker la track en cours dans le state Livewire + localStorage
▸ Technologie : Howler.js
```

### 3.2 Player vidéo (plein écran)

```
▸ Page dédiée / écran modal plein écran
▸ Contrôles : play/pause, volume, qualité (HLS adaptatif)
▸ Sous la vidéo : infos, bouton like, partage, commentaires (V2)
▸ Technologie : HLS.js
```

### 3.3 Tunnel de paiement

```
Étape 1 : Récapitulatif
  Produit : Album "Muna" (2000 FCFA)
  Frais MoMo : +50 FCFA
  Total : 2 050 FCFA

Étape 2 : Choix opérateur
  [Orange Money]  [MTN MoMo]

Étape 3 : Saisie numéro
  📱 6XXXXXXXX

Étape 4 : Confirmation
  ✅ Veuillez valider la demande sur votre téléphone
  Code reçu ? → [J'ai validé]

Étape 5 (succès) :
  🎉 Paiement réussi !
  Accès débloqué → [Écouter maintenant]
```

### 3.4 QR Code billet

```
┌──────────────────────┐
│  🎫 Concert XX      │
│  Salle : Yaoundé     │
│  Date : 15/08/2026   │
│  Type : VIP          │
│                      │
│  ┌──────────────┐    │
│  │ ██ ▄▄▄▄ ██   │    │
│  │   ██▄▄██     │    │
│  │  ▄▄████▄▄    │    │
│  │ ██ ▀▀▀▀ ██   │    │
│  └──────────────┘    │
│                      │
│  Réf: TKT-ABC123     │
└──────────────────────┘
```

---

## 5. Notifications

### 4.1 Canaux

| Canal | Technologie | Usage |
|-------|-------------|-------|
| **In-app** | Laravel Notifications + Livewire | Liste de notifications + badge header |
| **Email** | Brevo (via Laravel Mail) | Reçus, confirmations, rappels |
| **SMS** | Unimatrix | OTP uniquement (coûteux) |
| **Push navigateur** | Pusher (temps réel) | Notifications live (optionnel) |

### 4.2 Types de notifications

```php
class SaleReceived extends Notification
{
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.sale_received.subject'))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.sale_received.line', ['amount' => $this->amount]))
            ->action(__('notifications.view_wallet'), url('/artist/wallet'));
    }
    
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'sale',
            'amount' => $this->amount,
            'reference' => $this->reference,
            'message' => __('notifications.sale_received.message', [
                'amount' => number_format($this->amount, 0, ',', ' '),
            ]),
        ];
    }
}
```

---

## 6. Analytics & mesures

### 5.1 KPIs internes (dashboard admin)

| KPI | Source | Fréquence |
|-----|--------|-----------|
| GMV (volume transactions) | `payments` | Temps réel |
| Revenu commission | `transactions` | Temps réel |
| Artistes actifs | `users` | Quotidien |
| Fans actifs (30j) | `plays`, `orders` | Quotidien |
| Taux conversion visiteurs | Sessions vs paiements | Hebdomadaire |
| Panier moyen | `orders` | Hebdomadaire |
| Taux litiges | `disputes` / `orders` | Mensuel |

### 5.2 Web analytics

- **Plausible** ou **Umami** (auto-hébergé) plutôt que Google Analytics.
- Conforme loi camerounaise 2024/017 (pas de transfert de données hors UE/CEMAC).
- Mesures : pages vues, visites, sources de trafic, durées de session.

### 5.3 Dashboard artiste

```blade
<livewire:artist.stats-overview />

<!-- Graphiques Livewire -->
<livewire:artist.earnings-chart period="30_days" />
<livewire:artist.plays-chart period="30_days" />

<!-- Tableaux -->
<livewire:artist.top-tracks limit="10" />
<livewire:artist.recent-sales limit="10" />
```

---

## 7. États & feedback

### 6.1 États vides

```blade
<x-empty-state
    icon="shopping-bag"
    :title="__('store.empty_title')"
    :message="__('store.empty_message')"
    :action="__('store.browse_catalog')"
    :action-url="route('catalog.index')"
/>
```

### 6.2 États de chargement (skeletons)

```blade
<!-- Pendant le chargement Livewire -->
<div wire:loading>
    <x-skeleton.card count="6" />
</div>
<div wire:loading.remove>
    <!-- Contenu réel -->
</div>
```

### 6.3 Messages d'erreur

```blade
<x-alert type="error" :message="$message" />

<!-- Paiement échoué -->
<x-alert type="warning" title="Paiement en attente">
    {{ __('payment.pending_message') }}
</x-alert>
```

### 6.4 Transitions & micro-interactions

- Loading spinner sur les boutons de paiement.
- Animation de confirmation (checkmark) après un paiement réussi.
- Transition fluide du mini-player (slide-up) lors du démarrage d'une piste.
- Tooltips sur les badges et icônes.

---

> **Document suivant :** [`docs/10-devops-testing.md`](./10-devops-testing.md) — Déploiement & DevOps
