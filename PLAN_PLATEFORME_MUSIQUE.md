# 🎵 Plan détaillé — Plateforme de monétisation musicale (type FanPay)

> **Client :** Plateforme dédiée aux **musiciens / industrie musicale** au **Cameroun**
> **Stack :** Laravel 13 (PHP 8.3+) + MySQL 8 + Redis + Livewire 3 + Filament v4 + TailwindCSS v4
> **Ampleur :** Plateforme complète (3-6 mois)
> **Inspiration :** FanPay Africa (by VotAfrica) + Bandcamp + Audiomack + Patreon

---

## 📌 Décisions verrouillées (arbitrages client)

> Ces choix sont **figés** pour éviter tout retour en arrière. Toute la conception en découle.

| Décision | Choix retenu |
|---|---|
| **Commission plateforme** | **10%** (l'artiste garde 90%) |
| **Frais agrégateur MoMo (~2-3%)** | **Payés par le fan** (ajoutés au montant à payer) |
| **Multi-plateforme** | **Web responsive mobile-first** uniquement (pas d'app native au départ) |
| **Langues** | **Bilingue FR/EN dès le lancement** (i18n natif Laravel) |
| **Splits de revenus (feat/groupes)** | **Splits automatiques** par pourcentage entre collaborateurs |
| **Remboursements** | **Cas par cas** via workflow admin (litige) |
| **Hébergement** | **o2switch — offre Grow** (mutualisé cPanel LiteSpeed, **Redis + Varnish dispo**) + Cloudflare + stockage objet externe |
| **KYC artiste** | **Léger** : CNI + numéro MoMo au même nom |
| **Moyens de paiement** | **Mobile Money uniquement** (Orange Money + MTN MoMo) |

> ✅ **o2switch Grow supporte Redis, Varnish (XtremCache), Memcached, LSCache, OPcache, APCu.** ⚠️ Les seules vraies limites restent : **pas de démon persistant** (worker de queue via CRON + `flock`), **pas de WebSocket auto-hébergé** (Reverb impossible → Pusher), et **pas de streaming média servi par le serveur** (I/O 16 Mo/s → médias externalisés sur stockage objet + CDN). Voir **§4.3**.

---

## 1. Vision & positionnement

### 1.1 Concept
Une plateforme web (mobile-first) qui permet aux **artistes musicaux camerounais** de :
- Diffuser et **vendre** leur musique (albums, singles, clips vidéo)
- Être **soutenus financièrement** par leurs fans (tips, VIP, dédicaces)
- Organiser et **vendre des billets** de concerts/événements
- Gérer un **mini-site personnel** (page artiste = vitrine)
- Participer à des **concours par vote** (SuperVote)
- Recevoir leurs revenus via un **wallet interne** et retirer sur Mobile Money

### 1.2 Différence avec FanPay
| Aspect | FanPay (généraliste) | Notre plateforme (spécifique musique) |
|---|---|---|
| Cible | Tous créateurs | 100% musiciens |
| Contenu central | Divers | Catalogue musical (streaming + vente) |
| Découverte | Générique | Charts, top écoutes, genres musicaux, nouveautés |
| Vote | SuperVote générique | Concours musicaux (artiste du mois, meilleur clip…) |
| Player | Basique | Lecteur audio/vidéo intégré persistant (mini-player) |

### 1.3 Proposition de valeur
- **Pour l'artiste :** « Monétise ta musique et ta communauté, sois payé en Mobile Money. »
- **Pour le fan :** « Écoute, achète et soutiens tes artistes camerounais préférés facilement. »

---

## 2. Fonctionnalités (périmètre confirmé)

### 2.1 Modules retenus
1. 🎧 **Streaming musique & vidéo** (albums, singles, clips, previews 30-45s)
2. 🛍️ **Boutique** (produits numériques : téléchargements ; physiques : merch, CD)
3. ⭐ **Abonnements VIP** (contenu exclusif, modèle "prepaid/top-up" — voir §5.4)
4. 🎫 **Événements & billetterie** (concerts, QR code, contrôle d'accès)
5. ✍️ **Dédicaces personnalisées** (message audio/vidéo à la demande, payant)
6. 🗳️ **Système de vote** (concours musicaux payants)
7. 👛 **Wallet interne** (crédit sur paiement, débit sur retrait)
8. 🌐 **Mini-site perso par artiste** (page publique personnalisable)

### 2.2 Modules transverses (implicites, nécessaires)
- 🔐 Authentification & gestion de comptes (Fan / Artiste / Admin)
- 💳 Intégration paiements Mobile Money (Campay / Notch Pay)
- 💸 Retraits (payouts) des artistes vers Mobile Money
- 📊 Tableaux de bord (artiste : revenus, écoutes ; admin : supervision)
- 🔎 Découverte / recherche (charts, genres, artistes, nouveautés)
- 🔔 Notifications (in-app + email/SMS)
- 🛡️ Back-office admin (modération, vérification artistes, gestion litiges)

---

## 3. Rôles & parcours utilisateurs

### 3.1 Les 3 rôles
- **Fan (auditeur)** : écoute, achète, tip, s'abonne VIP, achète billets, vote, commande dédicaces.
- **Artiste (créateur)** : tout ce que fait un fan + upload catalogue, crée événements/produits/VIP, consulte stats & revenus, demande retraits.
- **Admin** : validation des artistes (KYC), modération contenu, gestion des concours, supervision financière, gestion des retraits, paramétrage commissions.

### 3.2 Parcours clés (user stories)
**Fan achète un album :**
1. Découvre l'artiste → écoute preview → clique "Acheter" (2000 FCFA)
2. Choisit Orange Money / MTN MoMo → saisit numéro
3. Reçoit invite USSD → valide code secret
4. Webhook confirme → accès au téléchargement/streaming complet débloqué

**Artiste retire ses gains :**
1. Consulte solde wallet (ex : 45 000 FCFA)
2. Clique "Retirer" → saisit numéro MoMo → montant
3. Admin valide (ou auto si < seuil) → API Payout déclenchée
4. Confirmation → solde débité

---

## 4. Architecture technique

### 4.1 Stack (adaptée à o2switch Grow)
```
Frontend  : Blade + Livewire 3 + Filament v4 (admin) + TailwindCSS v4 (mobile-first, responsive)
Backend   : Laravel 13 (PHP 8.3+)
Base      : MySQL 8 (fournie par o2switch)
Cache     : Redis (phpredis, activable via cPanel) — driver `redis`
Queue     : Redis + CRON `flock` (worker via --once) — voir §4.3
Full-page : Varnish (XtremCache) sur pages publiques (accueil, profils, catalogue)
Stockage média : Backblaze B2 ou Cloudflare R2 (objet S3, 0 egress) — JAMAIS sur le serveur
CDN/Média : Bunny.net (Stream = transcodage HLS + diffusion) — o2switch NE sert PAS les médias
Paiement  : Campay (principal) + Notch Pay (fallback, SDK Laravel officiel)
Recherche : MySQL fulltext → PostgreSQL + pgvector (V2)
Temps réel: Pusher hébergé ou polling léger (⚠️ pas de Reverb auto-hébergé)
SMS       : Unimatrix (~90 FCFA/SMS) pour OTP
Email     : Brevo (offre gratuite, bonne délivrabilité Afrique)
Monitoring: Laravel Pulse + Horizon (queue)
Debug     : Laravel Telescope (dév. uniquement)
Permissions: Spatie Laravel Permission
Médias    : Spatie Laravel MediaLibrary
Audit     : Spatie Laravel ActivityLog
Data/DTO  : Spatie Laravel Data
Feature   : Laravel Pennant (feature flags)
```

### 4.2 Pourquoi ces choix
- **Laravel 13** : dernière version (mars 2026) — attributs PHP natifs, AI SDK, JSON:API, sécurité renforcée. Écosystème mature.
- **Filament v4** : standard de facto pour les back-offices Laravel — Tailwind v4, MFA, ressources imbriquées, éditeur Tiptap.
- **Livewire 3** : rapidité de dev fullstack sans SPA complexe (bon pour équipe PHP, idéal sur mutualisé).
- **Spatie Permission** : standard communautaire pour rôles et permissions (plus flexible que les gates simples).
- **Spatie MediaLibrary** : gestion complète des médias (uploads, conversions, associations aux modèles).
- **Spatie ActivityLog** : audit trail automatique — remplace l'audit_logs custom.
- **Spatie Laravel Data** : DTO + validation — remplace les Form Requests pour les data transfers.
- **Notch Pay SDK Laravel officiel** : intégration native (Facades, config) — simplifie le code.
- **B2/R2 + Bunny** : contrôle des coûts de bande passante ET contournement obligatoire de la limite I/O d'o2switch.
- **Redis (cache + queue) + CRON `flock`** : performant et fiable sur o2switch (méthode officielle).
- **Varnish (XtremCache)** : cache full-page des pages publiques → décharge PHP et accélère le SEO.

### 4.3 Capacités & contraintes o2switch Grow (CRITIQUE)

> L'offre **Grow** est un hébergement **mutualisé cPanel sous LiteSpeed** (8 threads / 16 Go RAM, I/O 16 Mo/s, NVMe illimité, 7€ HT/mois). PHP 8.3, SSH, Composer, Artisan, Node.js et Python disponibles.

**✅ Capacités disponibles (à exploiter) :**
| Capacité | Usage dans le projet |
|---|---|
| **Redis** (via cPanel + `phpredis`) | Driver de **cache** (`CACHE_STORE=redis`) et de **queue** (`QUEUE_CONNECTION=redis`) |
| **Varnish / XtremCache** | **Cache full-page** des pages publiques (accueil, profils artistes, catalogue) ; exclure zones dynamiques (dashboard, panier, paiement, `/livewire`) |
| **Memcached** | Alternative de cache (Redis privilégié) |
| **OPcache / APCu** | Accélération PHP native |
| **CRON** | Planification (`schedule:run`) + worker de queue |

**⚠️ Contraintes réelles & solutions :**
| Contrainte o2switch | Impact | Solution retenue |
|---|---|---|
| **Pas de démon persistant / Supervisor** | Worker de queue Laravel | **CRON chaque minute** avec `flock` (méthode officielle o2switch) : `/opt/alt/php83/usr/bin/php artisan queue:work --once --tries=3` dans le CRON → traite un job par minute, évite fuites mémoire |
| **Limite I/O 16 Mo/s** (streaming déconseillé) | Impossible de servir audio/vidéo | **100% des médias sur B2/R2 + Bunny CDN**. La DB ne stocke que des URLs. o2switch ne sert que le HTML/CSS/JS applicatif |
| **Pas de transcodage lourd** (CPU partagé) | Conversion HLS | **Déléguée à Bunny Stream** (transcodage automatique à l'upload) |
| **Pas de WebSocket auto-hébergé** (Reverb impossible) | Temps réel (scan billets, notifs live) | **Pusher hébergé** (Laravel Echo → Pusher) ou **polling léger** Livewire |
| **Timeout d'exécution PHP** | Uploads volumineux | Upload **direct client → stockage objet** (URL signée pré-upload), le fichier ne transite pas par PHP |
| **Scheduler Laravel** | Tâches planifiées | Un seul CRON o2switch `php artisan schedule:run` chaque minute pilote tout (réconciliation, expirations VIP, rappels) |

**Déploiement sur o2switch :** `git` via SSH (dépôt bare + hook post-receive) ou push manuel, puis `composer install --no-dev`, `php artisan migrate --force`, `php artisan config:cache`, OPcache activé. Cloudflare en frontal + Varnish (XtremCache) sur les pages publiques.

> 💡 **Scalabilité** : Grow (7€) convient au lancement. Montée possible vers les offres **Cloud** (12 threads/48 Go) ou **Pro** (24 threads/64 Go) chez o2switch **sans changer d'architecture**. Si besoin d'un vrai daemon (Reverb, workers lourds), migration VPS + Supervisor **sans refonte du code** (seul le `.env` change).

### 4.4 Schéma modulaire (Laravel)
```
app/
├── Domain/
│   ├── Catalog/      (Album, Track, Video, Genre)
│   ├── Store/        (Product, Order, OrderItem)
│   ├── Vip/          (Plan, Subscription)
│   ├── Events/       (Event, Ticket, TicketScan)
│   ├── Dedications/  (DedicationRequest)
│   ├── Voting/       (Contest, Candidate, Vote)
│   ├── Wallet/       (Wallet, Transaction, Payout)
│   ├── Payments/     (PaymentGateway, Campay, NotchPay)
│   └── Users/        (User, ArtistProfile, KycVerification)
├── Http/
├── Jobs/             (ProcessWebhook, ProcessPayout, TranscodeMedia)
└── Services/
```

---

## 5. Modèle de données (schéma principal)

### 5.1 Utilisateurs & artistes
```
users            : id, name, email, phone, password, role, email_verified_at
artist_profiles  : id, user_id, stage_name, bio, avatar, cover, genre_id,
                   slug (mini-site), is_verified, social_links(json), stats(json)
kyc_verifications: id, user_id, id_document, status, verified_by, verified_at
```

### 5.2 Catalogue musical
```
genres   : id, name, slug
albums   : id, artist_id, title, cover, type(album/single/ep), price,
           release_date, is_published
tracks   : id, album_id, artist_id, title, duration, file_path(HLS),
           preview_path, price, play_count, is_free
videos   : id, artist_id, title, thumbnail, file_path(HLS), price, view_count
plays    : id, track_id, user_id, played_at  (statistiques d'écoute)
```

### 5.3 Boutique
```
products    : id, artist_id, type(digital/physical), name, description,
              price, stock, file_path(si digital)
orders      : id, user_id, artist_id, total, status, payment_ref
order_items : id, order_id, product_id, qty, unit_price
```

### 5.4 VIP (modèle "prepaid/top-up")
> ⚠️ Le MoMo ne gère pas nativement les débits récurrents automatiques.
> **Solution :** abonnement "prépayé" — le fan paie 1 mois d'accès, notification de renouvellement à l'échéance.
```
vip_plans        : id, artist_id, name, duration_days, price, perks(json)
vip_subscriptions: id, user_id, plan_id, starts_at, expires_at, status
```

### 5.5 Événements & billetterie
```
events       : id, artist_id, title, venue, date, description, cover
ticket_types : id, event_id, name(standard/vip), price, quantity, sold
tickets      : id, ticket_type_id, user_id, qr_code(unique), status(valid/used)
ticket_scans : id, ticket_id, scanned_by, scanned_at
```

### 5.6 Dédicaces
```
dedications : id, artist_id, fan_id, message_request, price, status,
              response_media(audio/video), delivered_at
```

### 5.7 Vote / concours
```
contests   : id, title, description, vote_price, starts_at, ends_at, status
candidates : id, contest_id, artist_id, vote_count
votes      : id, candidate_id, user_id, quantity, amount, payment_ref
```

### 5.8 Wallet & paiements (cœur financier)
```
wallets      : id, user_id, balance, currency(XAF)
transactions : id, wallet_id, user_id, type(credit/debit), amount,
               source(tip/sale/vip/ticket/dedication/vote/payout),
               reference, status(pending/completed/failed), meta(json)
payments     : id, user_id, gateway(campay/notchpay), amount, phone,
               operator(orange/mtn), external_ref, status, webhook_payload(json)
payouts      : id, artist_id, amount, phone, operator, status,
               approved_by, external_ref
```

---

## 6. Intégration des paiements (Cameroun)

### 6.1 Agrégateurs
| Agrégateur | Rôle | Points forts |
|---|---|---|
| **Campay** | Principal | Simple, focalisé Cameroun, MTN + Orange |
| **Notch Pay** | Fallback / alternative | API moderne, bonne DX |

> **Stratégie légale :** rester une plateforme SaaS technologique et laisser le flux financier régulé passer par l'agrégateur agréé (BEAC/CEMAC). Ne pas gérer soi-même une licence PSP au démarrage.

### 6.2 Flux de collecte (Collect / paiement entrant)
```
1. [Backend] POST vers API agrégateur : {montant, XAF, phone, référence unique}
2. [Client]  Reçoit invite USSD → valide avec code secret
3. [Webhook] Agrégateur POST → /webhooks/payment (route Laravel)
4. [Job]     ProcessWebhook (queue) :
             - vérifie signature HMAC du header
             - vérifie transaction en DB
             - met à jour payment.status
             - crédite le wallet du bénéficiaire (moins commission)
             - débloque l'accès (téléchargement / VIP / billet)
```

**Exemple route + controller :**
```php
// routes/api.php
Route::post('/webhooks/payment', [WebhookController::class, 'handle']);

// WebhookController.php
public function handle(Request $request) {
    // 1. Vérifier signature HMAC (header)
    // 2. Dispatcher un Job en queue (réponse rapide 200)
    ProcessPaymentWebhook::dispatch($request->all());
    return response()->json(['status' => 'received'], 200);
}
```

### 6.3 Flux de retrait (Payout / disbursement)
```
1. Artiste clique "Retirer" → montant + numéro MoMo
2. Système vérifie : wallet.balance >= montant
3. Crée transaction (type=debit, status=pending)
4. Validation admin (ou auto si < seuil configurable)
5. Appel API Payout de l'agrégateur
6. Webhook payout → transaction=completed, solde débité
```
> ⚠️ **Ne jamais automatiser** les payouts sans contrôle : validation admin + double vérification du solde pour éviter la fraude.

### 6.4 Commissions & répartition (modèle verrouillé)

**Règle de calcul (figée) :**
```
Prix affiché (fixé par l'artiste)      = P
Frais agrégateur (~2,5%)              = F  → PAYÉS PAR LE FAN
Montant total payé par le fan         = P + F
Commission plateforme (10%)           = 0,10 × P
Montant crédité au wallet artiste      = P - (0,10 × P) = 0,90 × P
```

**Exemple concret — album à 2 000 FCFA :**
| Poste | Montant |
|---|---|
| Prix affiché (artiste fixe) | 2 000 FCFA |
| Frais MoMo (~2,5%) ajoutés au fan | +50 FCFA |
| **Total payé par le fan** | **2 050 FCFA** |
| Commission plateforme (10% de 2 000) | 200 FCFA (revenu plateforme) |
| **Crédité au wallet de l'artiste** | **1 800 FCFA** |

**Paramétrage admin :**
- Taux de commission **configurable** en base (`settings`) — défaut 10%.
- Devise unique : **XAF (FCFA)**, aucun décimal (arrondi à l'entier supérieur pour les frais).
- Seuil de retrait minimum configurable (ex : 5 000 FCFA).

### 6.5 Splits de revenus (featurings / groupes)

> Choix verrouillé : **splits automatiques**. Une œuvre peut avoir plusieurs ayants droit.

```
revenue_splits : id, splittable_type, splittable_id (track/album/video/product),
                 artist_id, percentage (ex: 60.00)
```
- À chaque vente, après déduction de la commission, le montant net est **réparti** entre les `revenue_splits` et crédité sur chaque wallet.
- La somme des pourcentages doit = 100% (validation à la publication).
- Chaque collaborateur voit sa part dans son dashboard et retire indépendamment.
- **Traçabilité** : chaque `transaction` porte le `split_id` d'origine pour l'audit.

---

## 7. Streaming & gestion des médias

### 7.1 Pipeline média
```
Upload → Stockage R2/B2 → Transcodage HLS (plusieurs bitrates) →
Génération preview (30-45s) → Diffusion via CDN Bunny.net avec Signed URLs
```

### 7.2 Protection du contenu ("DRM léger")
- **Signed URLs temporaires** (expiration + liaison session/IP)
- **Restriction de domaine** (empêcher l'embed du player sur sites tiers)
- Previews gratuites, contenu complet réservé à l'achat/VIP
- Pas de DRM lourd (Widevine/FairPlay) au démarrage → trop coûteux

### 7.3 Player
- Mini-player persistant (audio) qui suit la navigation
- Player vidéo HLS adaptatif (qualité selon connexion 3G/4G/WiFi)
- Comptage des écoutes (table `plays`) pour statistiques artiste

---

## 8. Bilinguisme (i18n FR/EN)

> Choix verrouillé : **bilingue dès le lancement** (Cameroun officiellement bilingue + diaspora).

### 8.1 Implémentation
- Système natif Laravel : `resources/lang/fr/` et `resources/lang/en/` (fichiers de traduction) + fichiers JSON.
- **Middleware de locale** : détecte la langue via (1) préférence utilisateur en DB, (2) cookie, (3) header `Accept-Language`, (4) FR par défaut.
- Colonne `locale` sur `users` ; sélecteur de langue dans le header (FR / EN).

### 8.2 Contenu bilingue vs contenu libre
- **Interface** (menus, boutons, emails, SMS, erreurs) : **traduite** FR/EN.
- **Contenu artiste** (titres, bios, descriptions) : saisi par l'artiste dans **sa** langue (pas de double saisie imposée). Champs optionnels `bio_en` possibles en V2.
- **Dates, montants** : formatage localisé (FCFA, format numérique).

### 8.3 Règle d'or
> ⚠️ **Aucune chaîne en dur** dans le code dès la Phase 0. Tout passe par `__('...')`. Rétrofiter l'i18n plus tard coûte 3× plus cher — d'où le choix de partir bilingue.

---

## 9. Aspects juridiques & conformité (Cameroun)

> ⚠️ **À valider avec un avocat d'affaires camerounais avant mise en ligne.** Ces éléments conditionnent l'onboarding artiste et les écrans légaux — à prévoir dès la conception.

### 9.1 Droits d'auteur musicaux (POINT CRITIQUE)
- **SONACAM** (Société Nationale Camerounaise de l'Art Musical) = organisme agréé de gestion collective. Une plateforme qui diffuse/vend de la musique doit en principe **signer une convention de représentation** et verser des **redevances** (% du CA).
- **Droits voisins** (producteurs, interprètes) distincts des droits d'auteur.
- **Stratégie retenue (protectrice)** : exiger de chaque artiste, à l'inscription, une **déclaration/garantie de titularité des droits** (case à cocher + contrat de distribution numérique acceptant les CGU). L'artiste **garantit** qu'il détient les droits sur le contenu uploadé et **décharge** la plateforme.
  ```
  content_rights : id, artist_id, uploadable_type, uploadable_id,
                   declaration_accepted_at, socam_member (bool), ip_address
  ```
- Procédure **DMCA-like** de retrait sur signalement (notice & takedown).

### 9.2 Protection des données
- **Loi n° 2024/017 (23 déc. 2024)** — proche du RGPD : information, **consentement explicite**, droits d'accès/rectification/suppression.
- **Loi n° 2010/012** (cybersécurité) : déclaration des traitements auprès de l'**ANTIC** ; HTTPS obligatoire.
- **À implémenter** : bannière de consentement, politique de confidentialité, page "mes données" (export + suppression de compte), registre des traitements.

### 9.3 Structure & fiscalité
- Forme conseillée : **SARL**. Régulation monétique du flux = **BEAC** (gérée par l'agrégateur agréé, pas par la plateforme).
- **TVA 19,25%** sur la commission plateforme (le service facturé). Reversement à l'administration fiscale.
- **Retenues à la source** possibles sur les revenus artistes selon statut → **cabinet comptable local obligatoire**.
- Vérifier une éventuelle déclaration "service numérique à valeur ajoutée" auprès du MINPOSTEL/ART.

### 9.4 Documents légaux à produire (écrans dédiés)
- **CGU** (fans + artistes) — bilingues
- **CGV** (règles de vente, prix, taxes)
- **Politique de confidentialité** (loi 2024/017)
- **Contrat artiste / distribution numérique** (garantie de droits, commission, retraits)
- **Politique de remboursement** (voir §10)
- **Mentions légales** (éditeur, hébergeur o2switch)

---

## 10. Litiges, remboursements & réconciliation

> Choix verrouillé : **remboursement au cas par cas via l'admin**.

### 10.1 Workflow de litige
```
disputes : id, user_id, transaction_id, reason(content_not_delivered/duplicate/other),
           description, status(open/reviewing/refunded/rejected), resolved_by, resolved_at
```
1. Le fan ouvre un litige depuis son historique d'achats (motif + description).
2. L'admin examine (contenu livré ? doublon ? fraude ?).
3. Décision : **remboursement** (via API refund/payout vers le MoMo du fan) ou **rejet** (avec motif).
4. Si remboursement : le montant est **repris** sur le wallet de l'artiste (transaction inverse) + trace d'audit.

### 10.2 Gestion des paiements échoués / en attente
- Statuts clairs : `pending` → `completed` / `failed` / `expired`.
- **Job de réconciliation** (via CRON o2switch) : polling du statut des paiements restés `pending` > X minutes (au cas où un webhook a été manqué).
- **Idempotence** : chaque webhook traité une seule fois (clé `external_ref` unique + verrou).

### 10.3 Réconciliation comptable
- Export mensuel : total collecté, commissions, payouts, soldes wallets, TVA due.
- **Invariant financier** : `Σ soldes wallets = Σ crédits - Σ débits confirmés`. Un job de contrôle vérifie cet invariant chaque nuit et alerte l'admin en cas d'écart.
- Toutes les écritures wallet sont **immuables** (append-only) — jamais de mise à jour destructive.

---

## 11. Sécurité & anti-fraude

### 11.1 Authentification
- Mots de passe hachés (bcrypt/argon2), **vérification par OTP SMS** à l'inscription (numéro = identité au Cameroun).
- **2FA optionnel** (OTP SMS) pour les artistes avant un retrait.
- Sessions sécurisées, cookies httpOnly/secure, protection CSRF (native Laravel).

### 11.2 Protection financière
- **Rate limiting** sur endpoints de paiement/retrait/OTP (throttle Laravel).
- Vérification du solde **dans une transaction DB** (`lockForUpdate`) pour éviter les double-retraits (race condition).
- Validation HMAC des webhooks (signature agrégateur).
- Retraits : **KYC validé obligatoire** + numéro MoMo au **même nom** que la CNI + validation admin au-delà d'un seuil.

### 11.3 Anti-triche votes
- Vote **payant** (une voix = un paiement) → limite naturelle au spam.
- Journalisation IP + user_id ; détection d'anomalies (pics suspects).

### 11.4 Journaux & audit
```
audit_logs : id, user_id, action, subject_type, subject_id, ip, user_agent, created_at
```
- Trace de toutes les actions sensibles (retrait, validation KYC, remboursement, changement de commission).
- Gestion des secrets via `.env` (jamais en repo) ; clés API agrégateurs chiffrées.
- Sauvegardes DB automatiques (cPanel/o2switch + export externe hebdo).

---

## 12. Notifications & communication

### 12.1 Canaux
| Canal | Fournisseur | Usage |
|---|---|---|
| **In-app** | Table `notifications` (natif Laravel) | Nouvelles ventes, litiges, VIP expirant |
| **Email** | **Brevo** (offre gratuite, bon en Afrique) | Confirmations, reçus, réinitialisation MDP |
| **SMS** | **Unimatrix / fournisseur local** (~90 FCFA/SMS) | **OTP uniquement** (coûteux → réservé au critique) |

### 12.2 Événements déclencheurs (exemples)
- Fan : reçu d'achat, billet émis (QR), VIP bientôt expiré (rappel de renouvellement prepaid), dédicace livrée.
- Artiste : nouvelle vente/tip, retrait approuvé/payé, KYC validé, litige ouvert.
- Admin : demande de retrait en attente, litige, écart de réconciliation.

> ⚠️ Tous les envois passent par la **queue database + CRON** (pas de blocage de la requête HTTP).

---

## 13. Spécifications UX (écrans clés)

> Mobile-first, responsive. ~20 écrans structurants à maquetter avant dev.

### 13.1 Fan
1. **Accueil / découverte** : hero, charts (top écoutes), nouveautés, genres, artistes en vedette.
2. **Recherche** : par artiste, titre, genre.
3. **Profil artiste (mini-site)** : cover, bio, onglets (Musique / Boutique / Événements / VIP), bouton Tip.
4. **Player** : mini-player persistant (audio) + player vidéo plein écran.
5. **Tunnel de paiement** : choix Orange/MTN → saisie numéro → écran "validez sur votre téléphone" → confirmation.
6. **Mes achats / bibliothèque** : contenus débloqués, billets (QR), abonnements VIP actifs.
7. **Compte** : profil, langue, historique, litiges.

### 13.2 Artiste (dashboard)
8. **Vue d'ensemble** : revenus, solde wallet, écoutes, ventes récentes.
9. **Gestion catalogue** : upload album/single/clip, previews, splits collaborateurs.
10. **Boutique** : produits numériques/physiques, stock.
11. **VIP** : création de plans prepaid.
12. **Événements** : création, types de billets, scan.
13. **Dédicaces** : commandes reçues, upload réponse audio/vidéo.
14. **Retrait** : solde → numéro MoMo → demande.
15. **Personnalisation mini-site** : couleurs, cover, liens sociaux.

### 13.3 Admin (back-office)
16. **Dashboard supervision** : GMV, commissions, transactions, alertes.
17. **Validation KYC** : file d'attente, documents, approuver/rejeter.
18. **Payouts** : approbation des retraits.
19. **Litiges** : traitement remboursements.
20. **Concours & paramètres** : commissions, seuils, plans par défaut, gestion des concours.

### 13.4 Design system
- Palette + typographie à définir (identité musicale, contrastée, mobile-first).
- Composants Tailwind réutilisables : cartes artiste/album, boutons de paiement opérateur (logos Orange/MTN), lecteur, badges (vérifié, VIP).
- États vides, chargements (skeletons), messages d'erreur clairs et bilingues.

---

## 14. Back-office & modération

- **Vérification artistes (KYC léger)** : CNI + numéro MoMo au même nom, validation avant premier retrait.
- **Modération contenu** : signalements, retrait de contenu illicite (notice & takedown).
- **Gestion concours** : création, prix du vote, résultats transparents.
- **Supervision financière** : dashboard revenus, transactions, approbation payouts, réconciliation.
- **Paramétrage** : commissions, seuils de retrait, plans VIP par défaut, taux de frais.

---

## 15. Analytics & KPIs

### 15.1 KPIs business (dashboard admin)
- **GMV** (volume total des transactions), revenu commission plateforme.
- Nombre d'artistes actifs, fans actifs, taux de conversion (visiteur → acheteur).
- Panier moyen, top artistes/contenus, taux de renouvellement VIP.
- Taux de litige / remboursement.

### 15.2 Analytics artiste
- Écoutes par titre, ventes, revenus par source (musique/boutique/VIP/événements/tips), évolution mensuelle.

### 15.3 Outillage
- Statistiques internes (tables `plays`, `transactions`) + tableaux Livewire.
- Web analytics léger et respectueux (Plausible ou Umami) plutôt que GA — conforme loi 2024/017.

---

## 16. Roadmap de développement (3-6 mois)

### 🟢 Phase 0 — Fondations (Semaines 1-2)
- Setup Laravel 13 (PHP 8.3+), MySQL, **Redis (cache + queue) + CRON `flock`**, Varnish sur pages publiques, environnements (local + o2switch staging)
- **i18n FR/EN dès le départ** (règle : aucune chaîne en dur)
- Auth + OTP SMS, rôles (Fan/Artiste/Admin), profils artistes + slug
- Structure DB de base, design system (Tailwind), layout mobile-first, bannière consentement RGPD

### 🔵 Phase 1 — Cœur financier (Semaines 3-6)
- Wallet interne + transactions (append-only) + **splits de revenus**
- Intégration Campay (collect) + webhooks en queue (idempotents) + calcul commission/frais
- Payouts + validation admin + KYC léger
- Job de réconciliation + invariant financier
- Tests de bout en bout du flux de paiement (sandbox)

### 🟣 Phase 2 — Catalogue & streaming (Semaines 7-11)
- Upload **direct client → B2/R2** + pipeline média (Bunny Stream + HLS)
- Albums, singles, tracks, vidéos, previews
- Player audio/vidéo + comptage écoutes
- Découverte : charts, genres, recherche, nouveautés

### 🟠 Phase 3 — Monétisation avancée (Semaines 12-16)
- Boutique (numérique + physique)
- Abonnements VIP (modèle prepaid + rappels de renouvellement)
- Dédicaces personnalisées
- Tips / dons directs

### 🔴 Phase 4 — Événements & vote (Semaines 17-20)
- Événements + billetterie + QR codes
- Interface de scan billets (contrôle d'accès)
- Concours de vote payants (anti-triche)

### ⚫ Phase 5 — Finitions & lancement (Semaines 21-24)
- Mini-sites personnalisés artistes
- Notifications complètes (in-app, email Brevo, SMS OTP)
- Dashboards stats + analytics
- Litiges/remboursements, pages légales, tests de sécurité, beta test, lancement

### 🔮 Roadmap post-lancement (V2 — pour mémoire, non bloquant)
- App mobile native (l'archi web-first n'empêche pas d'ajouter une API plus tard)
- Paiement par carte (diaspora), autres pays (UEMOA), autres opérateurs (Wave, Moov)
- Playlists collaboratives, recommandations, contenu bio multilingue, migration VPS+Redis si scale.

---

## 17. Stratégie de test & qualité

- **Tests unitaires** : calcul commission/frais/splits (logique financière = priorité #1).
- **Tests feature** (Laravel) : parcours achat, retrait, litige, expiration VIP.
- **Sandbox paiement** : Campay/Notch Pay en environnement test dès Phase 1.
- **Tests d'idempotence** des webhooks (rejeu du même webhook = aucun double crédit).
- **Recette manuelle** bilingue (FR/EN) sur mobile réel (Android bas de gamme, 3G).
- **Contrôle de charge léger** avant lancement (le média étant sur CDN, l'app o2switch tient bien).
- CI simple : `php artisan test` à chaque push (via GitHub Actions ou hook local).

---

## 18. Stratégie de lancement (problème poule/œuf)

> Une marketplace échoue si fans et artistes manquent simultanément. On amorce **côté artistes d'abord**.

1. **Pré-lancement** : recruter 10-20 artistes camerounais "ancres" (avec communauté existante), onboarding assisté, catalogue prêt le jour J.
2. **Lancement** : chaque artiste ancre amène ses fans → premiers achats/tips.
3. **Boucle virale** : partage des mini-sites artistes (SEO + réseaux sociaux), concours de vote comme aimant d'acquisition.
4. **Incitation** : ex. commission réduite/nulle les premiers mois pour les artistes ancres (cf. modèle FanPay).
5. **SEO** : mini-sites artistes indexables (URLs propres `/artiste/slug`, meta, sitemap) = acquisition organique.

---

## 19. Budget indicatif (coûts récurrents)

| Poste | Ordre de grandeur | Note |
|---|---|---|
| Hébergement o2switch Grow | ~forfait annuel | Déjà choisi |
| Cloudflare | Gratuit → Pro (~20 USD/mo) | CDN frontal |
| Stockage objet B2/R2 | ~5-6 USD/To/mois | 0 egress |
| Bunny Stream (média) | à l'usage (stockage + diffusion) | Poste variable #1 |
| SMS OTP | ~90 FCFA/SMS | Maîtriser via OTP uniquement |
| Email (Brevo) | Gratuit → payant selon volume | |
| Frais agrégateur | ~2,5%/transaction | Payés par le fan |
| Comptable/juridique | à prévoir | **Indispensable** (fiscalité, SONACAM, CGU) |

> Le coût marginal principal = **bande passante média (Bunny)** + SMS OTP. Tout le reste est quasi fixe.

---

## 20. Risques & points d'attention

| Risque | Mitigation |
|---|---|
| Débits récurrents MoMo impossibles | Modèle "prepaid/top-up" pour VIP |
| Coûts de bande passante (streaming) | R2/B2 (0 egress) + CDN Bunny |
| Fraude sur retraits | Validation admin + KYC obligatoire |
| Conformité réglementaire (BEAC) | Passer par agrégateur agréé, pas de licence PSP au départ |
| Piratage du contenu | Signed URLs + previews + restriction domaine |
| Échecs de paiement / webhooks manqués | Queue Redis + CRON `flock` + réconciliation périodique (polling status) |
| Latence / faible connexion | HLS adaptatif, transcodage multi-bitrate, previews légères |
| **Limites o2switch (I/O, pas de démon/WebSocket)** | **Médias externalisés (CDN) + queue Redis via CRON `flock` + Pusher pour le temps réel ; migration Cloud/Pro/VPS sans refonte** |
| **Droits d'auteur / SONACAM** | **Garantie de titularité par l'artiste + notice & takedown + conseil juridique** |
| **Conformité données (loi 2024/017)** | **Consentement, page "mes données", déclaration ANTIC** |
| **Marketplace poule/œuf** | **Amorçage côté artistes ancres + incitations** |

---

## 21. Estimation d'équipe

| Rôle | Charge |
|---|---|
| Lead Dev Laravel / Architecte | Temps plein |
| Dev Backend (paiements, wallet) | Temps plein |
| Dev Frontend (Livewire/Blade, player) | Temps plein |
| Designer UI/UX | Mi-temps |
| DevOps (déploiement o2switch, CDN, média) | Ponctuel |
| QA / Testeur | Phases 3-5 |
| **Comptable/juriste local** | **Ponctuel mais indispensable (fiscalité, SONACAM, CGU)** |

---

## 22. Prochaines étapes recommandées (checklist de démarrage)

1. **Valider ce plan** avec le client (toutes les décisions §0 sont figées).
2. **Créer les comptes** : Campay + Notch Pay (sandbox), Backblaze B2/R2, Bunny, Brevo, fournisseur SMS, Cloudflare.
3. **Vérifier l'accès o2switch** : SSH, PHP 8.3, MySQL, création du CRON `schedule:run`.
4. **Cadrage juridique** : contacter avocat/comptable (SONACAM, CGU, TVA, ANTIC).
5. **Maquettes UI/UX** des ~20 écrans clés (§13), bilingues.
6. **Initialiser le projet Laravel** (Phase 0) avec i18n + queue database dès le départ.
7. **Prototyper le flux de paiement** en sandbox Campay dès la Phase 1 (risque #1).

---

> ✅ **Ce plan est désormais "abouti"** : les décisions structurantes (financières, techniques, légales, hébergement, i18n, sécurité, UX, lancement) sont verrouillées. Les seuls choix restants sont opérationnels (identité visuelle précise, rédaction des textes légaux par un juriste) et n'impliquent aucun retour en arrière sur l'architecture.
```
