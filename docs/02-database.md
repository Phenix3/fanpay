# 🗄️ 02 — Schéma de la Base de Données

> **Moteur :** MySQL 8 (InnoDB)
> **Convention :** Migrations Laravel uniquement
> **Devise :** XAF (FCFA) — aucun décimal, stocké en `INT` (centimes ou entiers)

---

## Table des matières

1. [Conventions générales](#1-conventions-générales)
2. [Tables du module Users](#2-tables-du-module-users)
3. [Tables du module Catalog](#3-tables-du-module-catalog)
4. [Tables du module Store](#4-tables-du-module-store)
5. [Tables du module Vip](#5-tables-du-module-vip)
6. [Tables du module Events](#6-tables-du-module-events)
7. [Tables du module Dedications](#7-tables-du-module-dedications)
8. [Tables du module Voting](#8-tables-du-module-voting)
9. [Tables du module Wallet/Payments](#9-tables-du-module-walletpayments)
10. [Tables du module Content](#10-tables-du-module-content)
11. [Tables transverses](#11-tables-transverses)
12. [Index & performances](#12-index--performances)
13. [Relations entre tables](#13-relations-entre-tables)
14. [Tables des packages Spatie & Laravel](#14-tables-des-packages-spatie--laravel)

---

## 1. Conventions générales

| Règle | Valeur |
|-------|--------|
| **Moteur** | InnoDB (intégrité référentielle, transactions) |
| **Charset** | `utf8mb4` / `utf8mb4_unicode_ci` (émojis, accents) |
| **Auto-incrément** | `BIGINT UNSIGNED AUTO_INCREMENT` pour toutes les PK |
| **Clés étrangères** | `ON DELETE CASCADE` ou `ON DELETE SET NULL` selon contexte |
| **Timestamps** | `created_at`, `updated_at` (Laravel auto) |
| **Soft deletes** | `deleted_at` nullable |
| **Montants** | `INT UNSIGNED` (centimes FCFA, pas de décimaux) |
| **Statuts** | `ENUM` ou `VARCHAR` avec contrainte CHECK |
| **JSON** | `JSON` pour données flexibles (meta, social_links, stats) |
| **Index** | Index sur toutes les FK + colonnes de recherche |

---

## 2. Tables du module Users

### 2.1 `users`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Identifiant unique |
| `name` | VARCHAR(255) | NOT NULL | Nom complet |
| `email` | VARCHAR(255) | UNIQUE, NULLABLE | Email (nullable si phone only) |
| `phone` | VARCHAR(20) | UNIQUE, NOT NULL | Numéro téléphone (identifiant principal) |
| `phone_verified_at` | TIMESTAMP | NULLABLE | Date vérification OTP |
| `password` | VARCHAR(255) | NOT NULL | Hash bcrypt/argon2 |
| `role` | ENUM('fan','artist','admin') | NOT NULL, DEFAULT 'fan' | Rôle utilisateur |
| `locale` | ENUM('fr','en') | NOT NULL, DEFAULT 'fr' | Langue préférée |
| `avatar` | VARCHAR(255) | NULLABLE | URL avatar |
| `two_factor_enabled` | BOOLEAN | NOT NULL, DEFAULT FALSE | 2FA OTP activé |
| `two_factor_verified_at` | TIMESTAMP | NULLABLE | Dernière vérification 2FA |
| `remember_token` | VARCHAR(100) | NULLABLE | Laravel remember token |
| `email_verified_at` | TIMESTAMP | NULLABLE | Vérification email |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete |

**Index :**
- PRIMARY (`id`)
- UNIQUE (`email`)
- UNIQUE (`phone`)
- INDEX (`role`)
- INDEX (`locale`)

### 2.2 `artist_profiles`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | BIGINT UNSIGNED | FK → users.id, UNIQUE | Référence user (role=artist) |
| `stage_name` | VARCHAR(255) | NOT NULL | Nom de scène |
| `slug` | VARCHAR(255) | UNIQUE, NOT NULL | Slug URL (mini-site) |
| `bio` | TEXT | NULLABLE | Biographie |
| `bio_en` | TEXT | NULLABLE | Biographie anglaise (V2) |
| `avatar` | VARCHAR(255) | NULLABLE | URL avatar artiste |
| `cover` | VARCHAR(255) | NULLABLE | URL cover bannière |
| `genre_id` | BIGINT UNSIGNED | FK → genres.id, NULLABLE | Genre principal |
| `is_verified` | BOOLEAN | NOT NULL, DEFAULT FALSE | Compte vérifié (KYC ok) |
| `social_links` | JSON | NULLABLE | `{"instagram":"...","twitter":"...","youtube":"..."}` |
| `stats` | JSON | NULLABLE | `{"total_plays":0,"total_sales":0,"total_followers":0}` |
| `customization` | JSON | NULLABLE | `{"primary_color":"#...","font":"..."}` (mini-site) |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |
| `deleted_at` | TIMESTAMP | NULLABLE | |

**Index :**
- PRIMARY (`id`)
- UNIQUE (`user_id`)
- UNIQUE (`slug`)
- INDEX (`genre_id`)
- INDEX (`is_verified`)
- INDEX (`stage_name`) — pour recherche fulltext (optionnel)

**Relations :**
- `user_id` → `users.id` (ON DELETE CASCADE)

### 2.3 `kyc_verifications`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | BIGINT UNSIGNED | FK → users.id | Artiste concerné |
| `id_document` | VARCHAR(255) | NOT NULL | URL scan CNI/passeport |
| `id_document_type` | ENUM('cni','passport','residence') | NOT NULL, DEFAULT 'cni' | Type de document |
| `id_number` | VARCHAR(50) | NULLABLE | Numéro CNI (optionnel, stocké chiffré) |
| `momo_phone` | VARCHAR(20) | NOT NULL | Numéro MoMo (doit correspondre à CNI) |
| `momo_operator` | ENUM('orange','mtn') | NULLABLE | Opérateur MoMo |
| `selfie` | VARCHAR(255) | NULLABLE | URL selfie (optionnel) |
| `status` | ENUM('pending','approved','rejected') | NOT NULL, DEFAULT 'pending' | Statut vérification |
| `rejection_reason` | TEXT | NULLABLE | Motif rejet |
| `verified_by` | BIGINT UNSIGNED | FK → users.id, NULLABLE | Admin qui a vérifié |
| `verified_at` | TIMESTAMP | NULLABLE | Date vérification |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

**Index :**
- PRIMARY (`id`)
- INDEX (`user_id`)
- INDEX (`status`)
- INDEX (`verified_by`)

**Relations :**
- `user_id` → `users.id` (ON DELETE CASCADE)
- `verified_by` → `users.id` (ON DELETE SET NULL)

### 2.4 `otp_codes`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | BIGINT UNSIGNED | FK → users.id, NULLABLE | Utilisateur (nullable si non connecté) |
| `phone` | VARCHAR(20) | NOT NULL | Destinataire |
| `code` | VARCHAR(6) | NOT NULL | Code OTP (6 chiffres) |
| `purpose` | ENUM('register','login','2fa','payout','password_reset') | NOT NULL | Motif envoi |
| `attempts` | TINYINT UNSIGNED | NOT NULL, DEFAULT 0 | Tentatives de validation |
| `max_attempts` | TINYINT UNSIGNED | NOT NULL, DEFAULT 3 | Maximum tentatives |
| `expires_at` | TIMESTAMP | NOT NULL | Expiration (5 min) |
| `verified_at` | TIMESTAMP | NULLABLE | Validation réussie |
| `created_at` | TIMESTAMP | NOT NULL | |

**Index :**
- PRIMARY (`id`)
- INDEX (`phone`, `purpose`, `verified_at`)
- INDEX (`user_id`)

---

## 3. Tables du module Catalog

### 3.1 `genres`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(100) | NOT NULL | Nom (ex: "Afrobeat") |
| `name_en` | VARCHAR(100) | NULLABLE | Nom anglais |
| `slug` | VARCHAR(100) | UNIQUE, NOT NULL | Slug URL |
| `description` | TEXT | NULLABLE | Description |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

### 3.2 `albums`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `artist_id` | BIGINT UNSIGNED | FK → users.id | Artiste principal |
| `title` | VARCHAR(255) | NOT NULL | Titre album |
| `slug` | VARCHAR(255) | UNIQUE, NOT NULL | Slug URL |
| `cover` | VARCHAR(255) | NULLABLE | URL pochette |
| `type` | ENUM('album','single','ep') | NOT NULL, DEFAULT 'single' | Type |
| `genre_id` | BIGINT UNSIGNED | FK → genres.id, NULLABLE | Genre |
| `price` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Prix (0 = gratuit) |
| `release_date` | DATE | NULLABLE | Date de sortie |
| `is_published` | BOOLEAN | NOT NULL, DEFAULT FALSE | Publié |
| `is_free` | BOOLEAN | NOT NULL, DEFAULT FALSE | Gratuit (streaming libre) |
| `description` | TEXT | NULLABLE | Description |
| `track_count` | SMALLINT UNSIGNED | NOT NULL, DEFAULT 0 | Cache : nombre de pistes |
| `total_plays` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Cache : total écoutes |
| `total_sales` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Cache : total ventes |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |
| `deleted_at` | TIMESTAMP | NULLABLE | |

**Index :**
- PRIMARY (`id`)
- UNIQUE (`slug`)
- INDEX (`artist_id`)
- INDEX (`genre_id`)
- INDEX (`is_published`)
- INDEX (`release_date`)

### 3.3 `tracks`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `album_id` | BIGINT UNSIGNED | FK → albums.id, NULLABLE | Album parent (nullable pour singles indépendants) |
| `artist_id` | BIGINT UNSIGNED | FK → users.id | Artiste principal |
| `title` | VARCHAR(255) | NOT NULL | Titre du morceau |
| `track_number` | SMALLINT UNSIGNED | NULLABLE | Numéro dans l'album |
| `duration` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Durée en secondes |
| `file_path` | VARCHAR(500) | NULLABLE | URL HLS (Bunny) |
| `preview_path` | VARCHAR(500) | NULLABLE | URL preview (30-45s) |
| `price` | INT UNSIGNED | NULLABLE | Prix unitaire (si différent de l'album) |
| `lyrics` | TEXT | NULLABLE | Paroles |
| `play_count` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Cache : compteur écoutes |
| `is_free` | BOOLEAN | NOT NULL, DEFAULT FALSE | Preview gratuite ou non |
| `is_published` | BOOLEAN | NOT NULL, DEFAULT FALSE | Publié |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |
| `deleted_at` | TIMESTAMP | NULLABLE | |

**Index :**
- PRIMARY (`id`)
- INDEX (`album_id`)
- INDEX (`artist_id`)
- INDEX (`is_published`)
- INDEX (`play_count`) — pour classement "top écoutes"
- FULLTEXT (`title`) — recherche plein texte

### 3.4 `videos`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `artist_id` | BIGINT UNSIGNED | FK → users.id | Artiste |
| `title` | VARCHAR(255) | NOT NULL | Titre |
| `description` | TEXT | NULLABLE | Description |
| `thumbnail` | VARCHAR(255) | NULLABLE | URL miniature |
| `file_path` | VARCHAR(500) | NULLABLE | URL HLS (Bunny) |
| `preview_path` | VARCHAR(500) | NULLABLE | URL preview (30s) |
| `duration` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Durée en secondes |
| `price` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Prix (0 = gratuit) |
| `view_count` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Nombre de vues |
| `is_published` | BOOLEAN | NOT NULL, DEFAULT FALSE | |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |
| `deleted_at` | TIMESTAMP | NULLABLE | |

**Index :**
- PRIMARY (`id`)
- INDEX (`artist_id`)
- INDEX (`is_published`)

### 3.5 `plays`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `track_id` | BIGINT UNSIGNED | FK → tracks.id | Morceau écouté |
| `user_id` | BIGINT UNSIGNED | FK → users.id, NULLABLE | NULL si non connecté |
| `ip_address` | VARCHAR(45) | NULLABLE | IP (anti-fraude) |
| `played_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |

**Index :**
- PRIMARY (`id`)
- INDEX (`track_id`, `played_at`)
- INDEX (`user_id`)

> **Note :** `plays` est une table append-only de type "event sourcing". On ne supprime jamais d'écoutes. Les stats sont agrégées périodiquement.

---

## 4. Tables du module Store

### 4.1 `products`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `artist_id` | BIGINT UNSIGNED | FK → users.id | Artiste vendeur |
| `type` | ENUM('digital','physical') | NOT NULL | Type de produit |
| `name` | VARCHAR(255) | NOT NULL | Nom du produit |
| `description` | TEXT | NULLABLE | Description |
| `price` | INT UNSIGNED | NOT NULL | Prix en FCFA |
| `stock` | INT UNSIGNED | NULLABLE | Stock (NULL = illimité pour digital) |
| `file_path` | VARCHAR(500) | NULLABLE | URL fichier (produit digital) |
| `cover_image` | VARCHAR(255) | NULLABLE | Image produit |
| `is_published` | BOOLEAN | NOT NULL, DEFAULT FALSE | |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |
| `deleted_at` | TIMESTAMP | NULLABLE | |

**Index :**
- PRIMARY (`id`)
- INDEX (`artist_id`)
- INDEX (`type`)

### 4.2 `orders`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `reference` | VARCHAR(50) | UNIQUE, NOT NULL | Référence unique (ex: `ORD-20260701-XXXX`) |
| `user_id` | BIGINT UNSIGNED | FK → users.id | Acheteur |
| `artist_id` | BIGINT UNSIGNED | FK → users.id | Bénéficiaire principal |
| `total` | INT UNSIGNED | NOT NULL | Total en FCFA (hors frais) |
| `fees` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Frais MoMo |
| `commission` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Commission plateforme |
| `net_amount` | INT UNSIGNED | NOT NULL | Montant net versé (total - commission) |
| `status` | ENUM('pending','completed','failed','refunded') | NOT NULL, DEFAULT 'pending' | Statut |
| `payment_ref` | VARCHAR(100) | NULLABLE | Référence agrégateur |
| `gateway` | ENUM('campay','notchpay') | NULLABLE | Agrégateur utilisé |
| `paid_at` | TIMESTAMP | NULLABLE | Date paiement |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

### 4.3 `order_items`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `order_id` | BIGINT UNSIGNED | FK → orders.id | Commande parent |
| `product_id` | BIGINT UNSIGNED | FK → products.id, NULLABLE | Produit (NULL pour achats spéciaux) |
| `purchasable_type` | VARCHAR(100) | NULLABLE | Polymorphique (album, track, video) |
| `purchasable_id` | BIGINT UNSIGNED | NULLABLE | ID polymorphique |
| `qty` | SMALLINT UNSIGNED | NOT NULL, DEFAULT 1 | Quantité |
| `unit_price` | INT UNSIGNED | NOT NULL | Prix unitaire |

---

## 5. Tables du module Vip

### 5.1 `vip_plans`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `artist_id` | BIGINT UNSIGNED | FK → users.id | Artiste |
| `name` | VARCHAR(255) | NOT NULL | Nom du plan (ex: "Fan Premium") |
| `description` | TEXT | NULLABLE | Avantages décrits |
| `duration_days` | SMALLINT UNSIGNED | NOT NULL | Durée (ex: 30) |
| `price` | INT UNSIGNED | NOT NULL | Prix plan |
| `perks` | JSON | NOT NULL | `{"exclusive_content":true,"badge":true,"early_access":false}` |
| `is_active` | BOOLEAN | NOT NULL, DEFAULT TRUE | |
| `sort_order` | SMALLINT UNSIGNED | NOT NULL, DEFAULT 0 | Ordre d'affichage |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

### 5.2 `vip_subscriptions`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | BIGINT UNSIGNED | FK → users.id | Fan abonné |
| `plan_id` | BIGINT UNSIGNED | FK → vip_plans.id | Plan souscrit |
| `starts_at` | TIMESTAMP | NOT NULL | Début abonnement |
| `expires_at` | TIMESTAMP | NOT NULL | Fin abonnement |
| `status` | ENUM('active','expired','cancelled') | NOT NULL, DEFAULT 'active' | Statut |
| `payment_ref` | VARCHAR(100) | NULLABLE | Référence paiement |
| `cancelled_at` | TIMESTAMP | NULLABLE | Date annulation |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

---

## 6. Tables du module Events

### 6.1 `events`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `artist_id` | BIGINT UNSIGNED | FK → users.id | Organisateur |
| `title` | VARCHAR(255) | NOT NULL | Titre événement |
| `slug` | VARCHAR(255) | UNIQUE, NOT NULL | Slug URL |
| `description` | TEXT | NULLABLE | Description |
| `venue` | VARCHAR(255) | NOT NULL | Lieu |
| `address` | TEXT | NULLABLE | Adresse complète |
| `date` | DATETIME | NOT NULL | Date/heure |
| `cover` | VARCHAR(255) | NULLABLE | Image couverture |
| `is_published` | BOOLEAN | NOT NULL, DEFAULT FALSE | |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |
| `deleted_at` | TIMESTAMP | NULLABLE | |

### 6.2 `ticket_types`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `event_id` | BIGINT UNSIGNED | FK → events.id | |
| `name` | VARCHAR(100) | NOT NULL | Ex: "Standard", "VIP" |
| `price` | INT UNSIGNED | NOT NULL | Prix |
| `quantity` | INT UNSIGNED | NOT NULL | Quantité totale |
| `sold` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Quantité vendue (cache) |
| `description` | TEXT | NULLABLE | |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

### 6.3 `tickets`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `ticket_type_id` | BIGINT UNSIGNED | FK → ticket_types.id | |
| `user_id` | BIGINT UNSIGNED | FK → users.id | Acheteur |
| `qr_code` | VARCHAR(100) | UNIQUE, NOT NULL | Code QR unique |
| `status` | ENUM('valid','used','cancelled','refunded') | NOT NULL, DEFAULT 'valid' | Statut |
| `payment_ref` | VARCHAR(100) | NULLABLE | |
| `checked_in_at` | TIMESTAMP | NULLABLE | Date entrée |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

### 6.4 `ticket_scans`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `ticket_id` | BIGINT UNSIGNED | FK → tickets.id | |
| `scanned_by` | BIGINT UNSIGNED | FK → users.id | Scanneur (artiste ou staff) |
| `status` | ENUM('valid','invalid','duplicate') | NOT NULL | Résultat scan |
| `scanned_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |

---

## 7. Tables du module Dedications

### 7.1 `dedications`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `artist_id` | BIGINT UNSIGNED | FK → users.id | Artiste |
| `fan_id` | BIGINT UNSIGNED | FK → users.id | Fan demandeur |
| `recipient_name` | VARCHAR(255) | NULLABLE | Nom du destinataire de la dédicace |
| `message_request` | TEXT | NOT NULL | Message demandé |
| `price` | INT UNSIGNED | NOT NULL | Prix payé |
| `status` | ENUM('pending','in_progress','delivered','cancelled') | NOT NULL, DEFAULT 'pending' | |
| `response_type` | ENUM('audio','video','text') | NULLABLE | Type de réponse |
| `response_media` | VARCHAR(500) | NULLABLE | URL fichier réponse |
| `response_message` | TEXT | NULLABLE | Réponse texte |
| `delivered_at` | TIMESTAMP | NULLABLE | Date livraison |
| `payment_ref` | VARCHAR(100) | NULLABLE | |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

---

## 8. Tables du module Voting

### 8.1 `contests`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `title` | VARCHAR(255) | NOT NULL | Titre concours |
| `description` | TEXT | NULLABLE | |
| `vote_price` | INT UNSIGNED | NOT NULL | Prix par vote |
| `starts_at` | DATETIME | NOT NULL | Début |
| `ends_at` | DATETIME | NOT NULL | Fin |
| `max_votes_per_user` | INT UNSIGNED | NULLABLE | Limite (NULL = illimité) |
| `status` | ENUM('draft','active','ended','cancelled') | NOT NULL, DEFAULT 'draft' | |
| `created_by` | BIGINT UNSIGNED | FK → users.id | Admin créateur |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

### 8.2 `candidates`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `contest_id` | BIGINT UNSIGNED | FK → contests.id | |
| `artist_id` | BIGINT UNSIGNED | FK → users.id | Artiste candidat |
| `vote_count` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Cache : total votes |
| `created_at` | TIMESTAMP | NOT NULL | |

### 8.3 `votes`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `candidate_id` | BIGINT UNSIGNED | FK → candidates.id | |
| `user_id` | BIGINT UNSIGNED | FK → users.id | Votant |
| `quantity` | SMALLINT UNSIGNED | NOT NULL, DEFAULT 1 | Nombre de votes achetés |
| `amount` | INT UNSIGNED | NOT NULL | Montant total payé |
| `payment_ref` | VARCHAR(100) | NULLABLE | |
| `ip_address` | VARCHAR(45) | NULLABLE | Anti-fraude |
| `created_at` | TIMESTAMP | NOT NULL | |

---

## 9. Tables du module Wallet/Payments

### 9.1 `wallets`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | BIGINT UNSIGNED | FK → users.id, UNIQUE | 1 wallet par user |
| `balance` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Solde en FCFA |
| `currency` | CHAR(3) | NOT NULL, DEFAULT 'XAF' | Devise |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

> **⚠️ Règle d'or :** `balance` est un cache. La source de vérité est la table `transactions`. Toute opération sur le wallet utilise une transaction DB (`lockForUpdate`) pour éviter les race conditions.

### 9.2 `transactions`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `reference` | VARCHAR(50) | UNIQUE, NOT NULL | Référence unique |
| `wallet_id` | BIGINT UNSIGNED | FK → wallets.id | Wallet impacté |
| `user_id` | BIGINT UNSIGNED | FK → users.id | Utilisateur |
| `type` | ENUM('credit','debit') | NOT NULL | Sens |
| `amount` | INT UNSIGNED | NOT NULL | Montant |
| `balance_before` | INT UNSIGNED | NOT NULL | Solde avant (audit) |
| `balance_after` | INT UNSIGNED | NOT NULL | Solde après (audit) |
| `source` | ENUM('sale','tip','vip','ticket','dedication','vote','payout','refund','commission','split') | NOT NULL | Origine |
| `source_id` | BIGINT UNSIGNED | NULLABLE | ID de l'entité source (polymorphique) |
| `source_type` | VARCHAR(100) | NULLABLE | Type d'entité source |
| `split_id` | BIGINT UNSIGNED | NULLABLE | Si issu d'un revenue split |
| `description` | VARCHAR(500) | NULLABLE | Description lisible |
| `status` | ENUM('pending','completed','failed') | NOT NULL, DEFAULT 'completed' | |
| `meta` | JSON | NULLABLE | Données supplémentaires |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

**Index :**
- PRIMARY (`id`)
- UNIQUE (`reference`)
- INDEX (`wallet_id`, `created_at`)
- INDEX (`user_id`)
- INDEX (`source`, `source_id`, `source_type`)
- INDEX (`status`)

> **⚠️ Append-only :** jamais de UPDATE destructif sur les transactions. Les corrections passent par des transactions de correction (new entry).

### 9.3 `payments`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `reference` | VARCHAR(50) | UNIQUE, NOT NULL | Référence interne |
| `user_id` | BIGINT UNSIGNED | FK → users.id | Payeur |
| `amount` | INT UNSIGNED | NOT NULL | Montant |
| `fees` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Frais agrégateur |
| `phone` | VARCHAR(20) | NOT NULL | Numéro MoMo payeur |
| `operator` | ENUM('orange','mtn') | NULLABLE | Opérateur |
| `gateway` | ENUM('campay','notchpay') | NOT NULL | Agrégateur |
| `external_ref` | VARCHAR(100) | UNIQUE, NULLABLE | Référence agrégateur |
| `status` | ENUM('pending','completed','failed','expired') | NOT NULL, DEFAULT 'pending' | |
| `webhook_payload` | JSON | NULLABLE | Payload brut du webhook (audit) |
| `webhook_received_at` | TIMESTAMP | NULLABLE | |
| `paid_at` | TIMESTAMP | NULLABLE | |
| `meta` | JSON | NULLABLE | Données additionnelles |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

**Index :**
- PRIMARY (`id`)
- UNIQUE (`reference`)
- UNIQUE (`external_ref`)
- INDEX (`user_id`)
- INDEX (`status`, `created_at`) — pour job de réconciliation
- INDEX (`gateway`, `external_ref`)

### 9.4 `payouts`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `reference` | VARCHAR(50) | UNIQUE, NOT NULL | Référence interne |
| `artist_id` | BIGINT UNSIGNED | FK → users.id | Bénéficiaire |
| `amount` | INT UNSIGNED | NOT NULL | Montant |
| `phone` | VARCHAR(20) | NOT NULL | Numéro MoMo destinataire |
| `operator` | ENUM('orange','mtn') | NULLABLE | Opérateur |
| `status` | ENUM('pending','approved','processing','completed','failed','rejected') | NOT NULL, DEFAULT 'pending' | |
| `approved_by` | BIGINT UNSIGNED | FK → users.id, NULLABLE | Admin approbateur |
| `approved_at` | TIMESTAMP | NULLABLE | |
| `gateway` | ENUM('campay','notchpay') | NULLABLE | Agrégateur utilisé |
| `external_ref` | VARCHAR(100) | NULLABLE | Référence agrégateur |
| `rejection_reason` | TEXT | NULLABLE | Motif rejet |
| `completed_at` | TIMESTAMP | NULLABLE | |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

**Index :**
- PRIMARY (`id`)
- UNIQUE (`reference`)
- INDEX (`artist_id`)
- INDEX (`status`)

### 9.5 `revenue_splits`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `splittable_type` | VARCHAR(100) | NOT NULL | Polymorphique (album, track, video, product) |
| `splittable_id` | BIGINT UNSIGNED | NOT NULL | ID entité |
| `artist_id` | BIGINT UNSIGNED | FK → users.id | Ayant droit |
| `percentage` | DECIMAL(5,2) | NOT NULL | Pourcentage (ex: 60.00) |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

**Index :**
- PRIMARY (`id`)
- INDEX (`splittable_type`, `splittable_id`)
- INDEX (`artist_id`)
- UNIQUE (`splittable_type`, `splittable_id`, `artist_id`) — 1 split par artiste par œuvre

> **Contrainte métier :** La somme des `percentage` pour un même `(splittable_type, splittable_id)` doit être = 100%.

---

## 10. Tables du module Content

### 10.1 `content_rights`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `artist_id` | BIGINT UNSIGNED | FK → users.id | Artiste déclarant |
| `uploadable_type` | VARCHAR(100) | NOT NULL | Polymorphique |
| `uploadable_id` | BIGINT UNSIGNED | NOT NULL | |
| `declaration_accepted_at` | TIMESTAMP | NOT NULL | Date acceptation CGU |
| `socam_member` | BOOLEAN | NOT NULL, DEFAULT FALSE | Membre SONACAM ? |
| `ip_address` | VARCHAR(45) | NOT NULL | IP souscription |
| `created_at` | TIMESTAMP | NOT NULL | |

---

## 11. Tables transverses

### 11.1 `notifications`

Table native Laravel : `notifications` (polymorphique)

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | CHAR(36) | PK | UUID |
| `type` | VARCHAR(255) | NOT NULL | Classe notification |
| `notifiable_type` | VARCHAR(255) | NOT NULL | |
| `notifiable_id` | BIGINT UNSIGNED | NOT NULL | |
| `data` | JSON | NOT NULL | Contenu |
| `read_at` | TIMESTAMP | NULLABLE | |
| `created_at` | TIMESTAMP | NOT NULL | |

### 11.2 `disputes`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | BIGINT UNSIGNED | FK → users.id | Plaignant |
| `transaction_id` | BIGINT UNSIGNED | FK → transactions.id | Transaction concernée |
| `reason` | ENUM('content_not_delivered','duplicate','fraud','other') | NOT NULL | Motif |
| `description` | TEXT | NOT NULL | Description |
| `status` | ENUM('open','reviewing','refunded','rejected') | NOT NULL, DEFAULT 'open' | |
| `resolved_by` | BIGINT UNSIGNED | FK → users.id, NULLABLE | Admin résolveur |
| `resolved_at` | TIMESTAMP | NULLABLE | |
| `resolution_note` | TEXT | NULLABLE | Note interne |
| `created_at` | TIMESTAMP | NOT NULL | |
| `updated_at` | TIMESTAMP | NOT NULL | |

### 11.3 `settings`

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `key` | VARCHAR(100) | PK | Clé unique |
| `value` | TEXT | NOT NULL | Valeur (JSON encodée) |
| `description` | VARCHAR(500) | NULLABLE | Description |
| `updated_at` | TIMESTAMP | NOT NULL | |

**Valeurs par défaut :**
| Clé | Valeur | Description |
|-----|--------|-------------|
| `commission_rate` | `10` | Commission plateforme (%) |
| `min_payout_amount` | `5000` | Seuil minimum retrait (FCFA) |
| `auto_payout_threshold` | `50000` | Seuil retrait auto (sans validation admin) |
| `momo_fee_rate` | `2.5` | Frais agrégateur (%) |
| `otp_expiry_minutes` | `5` | Durée validité OTP |
| `max_payout_amount` | `500000` | Montant max par retrait |
| `vip_reminder_days` | `3` | Jours avant expiration pour rappel |

---

## 12. Index & performances

### 12.1 Index recommandés (par priorité)

| Table | Index | Type | Raison |
|-------|-------|------|--------|
| `tracks` | `(title)` | FULLTEXT | Recherche musique |
| `artist_profiles` | `(stage_name)` | FULLTEXT | Recherche artiste |
| `plays` | `(track_id, played_at)` | BTREE | Stats écoutes (rapports) |
| `transactions` | `(wallet_id, created_at)` | BTREE | Historique wallet |
| `payments` | `(status, created_at)` | BTREE | Job réconciliation |
| `votes` | `(candidate_id)` | BTREE | Comptage votes |
| `vip_subscriptions` | `(status, expires_at)` | BTREE | Job expiration VIP |

### 12.2 Stratégie de cache

| Type | Cache | Durée | Invalidation |
|------|-------|-------|-------------|
| Pages publiques (accueil, profils) | Varnish (full-page) | 1h | Sur publication contenu |
| Top charts / classements | Redis | 15 min | CRON de rafraîchissement |
| Stats artiste (dashboard) | Redis | 5 min | Sur nouvelle vente/écoute |
| Sessions utilisateurs | Redis | 24h | Auto-expiration |
| Catalogue (genres, albums) | Redis | 1h | Sur event model |
| Config settings | Redis (forever) | ∞ | Sur mise à jour setting |

---

## 13. Relations entre tables

### 13.1 Diagramme textuel des relations

```
users (1) ──→ (0..1) artist_profiles
users (1) ──→ (0..n) kyc_verifications
users (1) ──→ (1) wallets
users (1) ──→ (0..n) transactions
users (1) ──→ (0..n) orders (as buyer)
users (1) ──→ (0..n) orders (as artist)
users (1) ──→ (0..n) dedications (as artist)
users (1) ──→ (0..n) dedications (as fan)
users (1) ──→ (0..n) votes

users (1) ──→ (0..n) albums / tracks / videos (as artist)
users (1) ──→ (0..n) vip_plans (as artist)
users (1) ──→ (0..n) events (as artist)

artist_profiles (1) ──→ genres (0..1)

albums (1) ──→ (0..n) tracks
albums (1) ──→ genres (0..1)

orders (1) ──→ (0..n) order_items

events (1) ──→ (0..n) ticket_types
ticket_types (1) ──→ (0..n) tickets
tickets (1) ──→ (0..n) ticket_scans

vip_plans (1) ──→ (0..n) vip_subscriptions

contests (1) ──→ (0..n) candidates
candidates (1) ──→ (0..n) votes

payments (1) ──→ transactions (0..n)
payouts ──→ wallet (débit)

revenue_splits (polymorphique) → albums / tracks / videos / products
```

### 13.2 Résumé des clés étrangères

| Table | FK | Cible | DELETE |
|-------|-----|------|--------|
| artist_profiles | user_id | users.id | CASCADE |
| kyc_verifications | user_id | users.id | CASCADE |
| kyc_verifications | verified_by | users.id | SET NULL |
| albums | artist_id | users.id | CASCADE |
| albums | genre_id | genres.id | SET NULL |
| tracks | album_id | albums.id | SET NULL |
| tracks | artist_id | users.id | CASCADE |
| videos | artist_id | users.id | CASCADE |
| orders | user_id | users.id | CASCADE |
| orders | artist_id | users.id | CASCADE |
| transactions | wallet_id | wallets.id | CASCADE |
| payments | user_id | users.id | CASCADE |
| payouts | artist_id | users.id | CASCADE |
| revenue_splits | artist_id | users.id | CASCADE |

---

## 14. Tables des packages Spatie & Laravel

### 14.1 Spatie Laravel Permission

Package : `spatie/laravel-permission` — Gestion des rôles et permissions (remplace les Gates manuelles).

**Table `permissions`**

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(255) | NOT NULL | Nom permission (ex: 'upload-catalog') |
| `guard_name` | VARCHAR(255) | NOT NULL, DEFAULT 'web' | Guard Laravel |
| `created_at` | TIMESTAMP | NULLABLE | |
| `updated_at` | TIMESTAMP | NULLABLE | |

**Table `roles`**

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(255) | NOT NULL | Nom rôle (ex: 'artist') |
| `guard_name` | VARCHAR(255) | NOT NULL, DEFAULT 'web' | Guard Laravel |
| `created_at` | TIMESTAMP | NULLABLE | |
| `updated_at` | TIMESTAMP | NULLABLE | |

**Table `model_has_permissions`** (polymorphique)

| Colonne | Type | Contraintes |
|---------|------|-------------|
| `permission_id` | BIGINT UNSIGNED | FK → permissions.id |
| `model_type` | VARCHAR(255) | NOT NULL |
| `model_id` | BIGINT UNSIGNED | NOT NULL |

**Table `model_has_roles`** (polymorphique)

| Colonne | Type | Contraintes |
|---------|------|-------------|
| `role_id` | BIGINT UNSIGNED | FK → roles.id |
| `model_type` | VARCHAR(255) | NOT NULL |
| `model_id` | BIGINT UNSIGNED | NOT NULL |

**Table `role_has_permissions`**

| Colonne | Type | Contraintes |
|---------|------|-------------|
| `permission_id` | BIGINT UNSIGNED | FK → permissions.id |
| `role_id` | BIGINT UNSIGNED | FK → roles.id |

---

### 14.2 Spatie Laravel MediaLibrary

Package : `spatie/laravel-medialibrary` — Gestion des médias et conversions (remplace le stockage manuel).

**Table `media`**

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `model_type` | VARCHAR(255) | NOT NULL | Modèle parent (polymorphique) |
| `model_id` | BIGINT UNSIGNED | NOT NULL | ID du modèle parent |
| `uuid` | CHAR(36) | UNIQUE, NULLABLE | UUID unique |
| `collection_name` | VARCHAR(255) | NOT NULL | Collection (ex: 'track_audio', 'track_cover') |
| `name` | VARCHAR(255) | NOT NULL | Nom du fichier (sans extension) |
| `file_name` | VARCHAR(255) | NOT NULL | Nom du fichier (avec extension) |
| `mime_type` | VARCHAR(255) | NULLABLE | Type MIME |
| `disk` | VARCHAR(255) | NOT NULL | Disque de stockage (ex: 'b2') |
| `conversions_disk` | VARCHAR(255) | NULLABLE | Disque pour les conversions |
| `size` | BIGINT UNSIGNED | NOT NULL | Taille en octets |
| `manipulations` | JSON | NOT NULL | Manipulations sérialisées |
| `custom_properties` | JSON | NOT NULL | Propriétés personnalisées |
| `generated_conversions` | JSON | NOT NULL | Conversions générées |
| `responsive_images` | JSON | NOT NULL | Images responsives |
| `order_column` | INT UNSIGNED | NULLABLE | Ordre d'affichage |
| `created_at` | TIMESTAMP | NULLABLE | |
| `updated_at` | TIMESTAMP | NULLABLE | |

**Index :**
- PRIMARY (`id`)
- UNIQUE (`uuid`)
- INDEX (`model_type`, `model_id`)
- INDEX (`collection_name`)
- INDEX (`order_column`)

---

### 14.3 Spatie Laravel ActivityLog

Package : `spatie/laravel-activitylog` — Audit trail automatique (remplace la table `audit_logs` personnalisée).

**Table `activity_log`**

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `log_name` | VARCHAR(255) | NULLABLE | Nom du log (ex: 'payout', 'kyc') |
| `description` | TEXT | NOT NULL | Description de l'action |
| `subject_type` | VARCHAR(255) | NULLABLE | Type d'entité concernée |
| `subject_id` | BIGINT UNSIGNED | NULLABLE | ID de l'entité |
| `event` | VARCHAR(255) | NULLABLE | Événement (ex: 'created', 'updated') |
| `causer_type` | VARCHAR(255) | NULLABLE | Type d'utilisateur acteur |
| `causer_id` | BIGINT UNSIGNED | NULLABLE | ID de l'acteur |
| `properties` | JSON | NULLABLE | Données contextuelles (IP, user_agent, meta) |
| `batch_uuid` | CHAR(36) | NULLABLE | UUID de lot (regroupe plusieurs logs) |
| `created_at` | TIMESTAMP | NULLABLE | |
| `updated_at` | TIMESTAMP | NULLABLE | |

**Index :**
- PRIMARY (`id`)
- INDEX (`log_name`)
- INDEX (`subject_type`, `subject_id`)
- INDEX (`causer_type`, `causer_id`)
- INDEX (`event`)

---

### 14.4 Laravel Pulse

Package : `laravel/pulse` — Monitoring applicatif temps réel (production).

**Table `pulse_entries`**

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `timestamp` | INT UNSIGNED | NOT NULL | Timestamp Unix |
| `type` | VARCHAR(255) | NOT NULL | Type d'entrée (slow_query, etc.) |
| `key` | TEXT | NOT NULL | Clé (ex: requête SQL) |
| `key_hash` | VARCHAR(255) | NOT NULL | Hash SHA-256 de la clé |
| `value` | BIGINT | NULLABLE | Valeur associée |

**Table `pulse_aggregates`**

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `bucket` | INT UNSIGNED | NOT NULL | Intervalle de temps |
| `period` | MEDIUMINT UNSIGNED | NOT NULL | Période (secondes) |
| `type` | VARCHAR(255) | NOT NULL | Type |
| `key` | TEXT | NOT NULL | Clé |
| `key_hash` | VARCHAR(255) | NOT NULL | Hash SHA-256 de la clé |
| `aggregate` | VARCHAR(255) | NOT NULL | Type d'agrégat (count, sum, avg, max) |
| `value` | DECIMAL(20,2) | NOT NULL | Valeur agrégée |
| `count` | INT UNSIGNED | NULLABLE | Compteur |

**Table `pulse_values`**

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `timestamp` | INT UNSIGNED | NOT NULL | Timestamp Unix |
| `type` | VARCHAR(255) | NOT NULL | Type |
| `key` | TEXT | NOT NULL | Clé |
| `key_hash` | VARCHAR(255) | NOT NULL | Hash SHA-256 de la clé |
| `value` | TEXT | NOT NULL | Valeur (JSON encodé) |

---

> **Note :** Ces tables sont créées automatiquement par les migrations des packages. Il n'est pas nécessaire de les créer manuellement.
> Les commandes pour publier et exécuter leurs migrations :
> ```bash
> # Spatie Permission
> php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="migrations"
> 
> # Spatie MediaLibrary
> php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="migrations"
> 
> # Spatie ActivityLog
> php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="migrations"
> 
> # Laravel Pulse
> php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
> 
> # Puis : php artisan migrate
> ```

---

> **Document suivant :** [`docs/03-auth-users-kyc.md`](./03-auth-users-kyc.md) — Authentification, rôles, OTP, KYC, profils artiste
