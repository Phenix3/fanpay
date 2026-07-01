# 🛡️ 08 — Sécurité & Conformité

> **Juridique :** Cameroun (loi 2024/017 protection des données, loi 2010/012 cybersécurité)
> **Régulation financière :** BEAC/CEMAC (via agrégateur agréé)
> **Bilinguisme :** FR/EN — documents légaux bilingues obligatoires

---

## Table des matières

1. [Sécurité applicative](#1-sécurité-applicative)
2. [Anti-fraude financière](#2-anti-fraude-financière)
3. [Protection des données (loi 2024/017)](#3-protection-des-données-loi-2024017)
4. [Conformité paiements BEAC](#4-conformité-paiements-beac)
5. [Droits d'auteur & SONACAM](#5-droits-dauteur--sonacam)
6. [Documents légaux](#6-documents-légaux)
7. [I18n & bilinguisme](#7-i18n--bilinguisme)
8. [Audit & traçabilité](#8-audit--traçabilité)

---

## 1. Sécurité applicative

### 1.1 Authentification & sessions

| Mesure | Implémentation |
|--------|---------------|
| **Hachage mots de passe** | Bcrypt (coût 12) ou Argon2id (natif Laravel) |
| **Vérification OTP** | OTP SMS obligatoire à l'inscription |
| **Rate limiting** | Laravel throttle (5 req/10min register, 3 req/5min OTP) |
| **Sessions** | httpOnly, secure, SameSite=Lax |
| **CSRF** | Protection native Laravel (tokens) |
| **Sanctum** | SPA authentication (cookies) + tokens API |
| **2FA optionnel** | OTP SMS avant retrait pour artistes |

### 1.2 Protection des endpoints

```php
// Middleware rate limiting
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,10'); // 5 requêtes / 10 minutes

Route::post('/send-otp', [AuthController::class, 'sendOtp'])
    ->middleware('throttle:3,5'); // 3 SMS / 5 minutes

Route::post('/api/webhooks/payment', [WebhookController::class, 'handle'])
    ->middleware('throttle:60,1'); // 60 webhooks / minute
```

### 1.3 Sécurité des webhooks

```php
// Vérification HMAC obligatoire
public function verifyWebhookSignature(Request $request): bool
{
    $signature = $request->header('X-Campay-Signature');
    $payload = $request->getContent();
    $expected = hash_hmac('sha256', $payload, config('campay.app_secret'));
    
    return hash_equals($expected, $signature);
}
```

### 1.4 Headers de sécurité

```php
// app/Http/Middleware/SecurityHeaders.php
public function handle(Request $request, Closure $next): Response
{
    $response = $next($request);
    
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'DENY');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    
    // CSP (Content Security Policy) — à ajuster selon besoins
    $response->headers->set('Content-Security-Policy', 
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' https://js.pusher.com; " .
        "style-src 'self' 'unsafe-inline'; " .
        "media-src 'self' https://*.bunnycdn.com; " .
        "connect-src 'self' https://*.pusher.com wss://*.pusher.com; " .
        "frame-ancestors 'none';"
    );
    
    return $response;
}
```

### 1.5 Validation des entrées

```php
// Toujours utiliser les Form Requests Laravel
class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^6\d{8}$/', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
            'role' => ['required', 'in:fan,artist'],
            'locale' => ['nullable', 'in:fr,en'],
        ];
    }
}
```

---

## 2. Anti-fraude financière

### 2.1 Protection des retraits (payouts)

| Mesure | Détail |
|--------|--------|
| **KYC obligatoire** | CNI + numéro MoMo au même nom |
| **Validation admin** | Pour tout montant > seuil configurable (ex: 50000 FCFA) |
| **Double vérification solde** | `lockForUpdate` dans transaction DB |
| **Rate limiting** | Max 1 demande de retrait / 24h ? (configurable) |
| **2FA optionnel** | OTP SMS avant validation retrait |
| **Seuil minimum** | Montant minimum retirable (ex: 5000 FCFA) |

### 2.2 Race condition sur le solde

```php
// Toujours utiliser lockForUpdate pour les opérations wallet
DB::transaction(function () {
    $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->firstOrFail();
    
    if ($wallet->balance < $amount) {
        throw new InsufficientBalanceException();
    }
    
    // Créer transaction avant de débiter
    Transaction::create([
        'wallet_id' => $wallet->id,
        'type' => 'debit',
        'amount' => $amount,
        'balance_before' => $wallet->balance,
        'balance_after' => $wallet->balance - $amount,
    ]);
    
    $wallet->decrement('balance', $amount);
});
```

### 2.3 Anti-triche votes

- **Vote payant** : chaque vote = 1 transaction financière → coût réel.
- **Limite par utilisateur** : configurable par concours.
- **Journalisation** : IP, user_agent, horodatage.
- **Détection d'anomalies** : alerte admin si > 1000 votes/heure pour un candidat.

### 2.4 Invariant financier (job quotidien)

```php
// Vérification quotidienne : Σ soldes wallets = Σ crédits - Σ débits
$totalBalances = Wallet::sum('balance');
$totalCredits = Transaction::where('type', 'credit')
    ->where('status', 'completed')
    ->sum('amount');
$totalDebits = Transaction::where('type', 'debit')
    ->where('status', 'completed')
    ->sum('amount');

$expectedBalances = $totalCredits - $totalDebits;

if ($totalBalances !== $expectedBalances) {
    Log::critical('FINANCIAL INVARIANT VIOLATION', [
        'total_balances' => $totalBalances,
        'expected' => $expectedBalances,
        'diff' => $totalBalances - $expectedBalances,
    ]);
    
    Notification::route('mail', config('app.admin_email'))
        ->notify(new FinancialInvariantAlert($totalBalances, $expectedBalances));
}
```

---

## 3. Protection des données (loi 2024/017)

### 3.1 Conformité RGPD-like camerounaise

| Obligation | Implémentation |
|------------|---------------|
| **Consentement explicite** | Bannière cookies + checkbox CGU obligatoire |
| **Droit d'accès** | Page "Mes données" (export JSON) |
| **Droit de rectification** | Modification profil utilisateur |
| **Droit de suppression** | Bouton "Supprimer mon compte" |
| **Portabilité** | Export des données au format JSON |
| **Information** | Politique de confidentialité claire |

### 3.2 Données sensibles

| Type de donnée | Stockage | Conservation |
|---------------|----------|-------------|
| Mot de passe | Hash bcrypt/argon2 | Jusqu'à changement |
| Numéro CNI | Chiffré AES-256 (optionnel) | Jusqu'à suppression compte + 1 an |
| Scan CNI | B2 bucket privé (pas d'accès public) | Jusqu'à suppression compte + 1 an |
| Numéro téléphone | Base de données (pseudonymisé si possible) | Jusqu'à suppression compte |
| Transactions | Base de données (append-only) | 10 ans (obligation comptable) |
| Logs d'audit | Base de données | 3 ans |

### 3.3 Page "Mes données"

```php
// Export des données utilisateur
class ExportUserDataAction
{
    public function execute(User $user): array
    {
        return [
            'profile' => $user->toArray(),
            'artist_profile' => $user->artistProfile?->toArray(),
            'orders' => $user->orders()->with('items')->get()->toArray(),
            'transactions' => $user->transactions()->get()->toArray(),
            'tickets' => $user->tickets()->with('ticketType.event')->get()->toArray(),
            'notifications' => $user->notifications()->get()->toArray(),
        ];
    }
}
```

---

## 4. Conformité paiements BEAC

### 4.1 Stratégie légale

> La plateforme reste une **SaaS technologique**. Le flux financier régulé passe par l'agrégateur agréé (Campay/Notch Pay). La plateforme ne gère **pas** de licence PSP au démarrage.

**Points de vigilance :**
- L'agrégateur est responsable de la conformité BEAC (KYC, AML, plafonds).
- La plateforme collecte des fonds pour le compte d'artistes → vérifier le régime juridique exact.
- **Cabinet comptable local obligatoire** pour la fiscalité (TVA 19,25% sur commission, retenues à la source).

### 4.2 Traçabilité des transactions

Chaque transaction contient :
- Référence unique (côté plateforme + côté agrégateur)
- Montant, frais, commission, net
- Horodatage
- Statut (pending → completed/failed)
- Payload brut du webhook (pour audit)

---

## 5. Droits d'auteur & SONACAM

### 5.1 Garantie de titularité

À l'upload de chaque contenu, l'artiste doit déclarer :
- Qu'il détient les droits sur le contenu uploadé
- S'il est membre SONACAM (optionnel)
- Acceptation des CGU

```php
// Domain/Content/Actions/AcceptContentRights.php
class AcceptContentRights
{
    public function execute(User $artist, $uploadable): ContentRight
    {
        return ContentRight::create([
            'artist_id' => $artist->id,
            'uploadable_type' => get_class($uploadable),
            'uploadable_id' => $uploadable->id,
            'declaration_accepted_at' => now(),
            'socam_member' => request('socam_member', false),
            'ip_address' => request()->ip(),
        ]);
    }
}
```

### 5.2 Notice & Takedown (DMCA-like)

- Signalement de contenu contrefait (formulaire dédié pour les ayants droit).
- Retrait sous 48h après notification valide.
- Réponse au plaignant + information de l'artiste.

---

## 6. Documents légaux

### 6.1 Liste des documents à produire

| Document | Description | Bilingue |
|----------|-------------|:--------:|
| **CGU (Conditions Générales d'Utilisation)** | Règles pour fans et artistes | ✅ |
| **CGV (Conditions Générales de Vente)** | Prix, taxes, livraison, remboursements | ✅ |
| **Politique de confidentialité** | Conforme loi 2024/017 | ✅ |
| **Contrat artiste / distribution numérique** | Garantie de droits, commission, retraits | ✅ |
| **Politique de remboursement** | Cas par cas, litiges | ✅ |
| **Mentions légales** | Éditeur, hébergeur o2switch | ✅ |
| **Politique de cookies** | Bannière de consentement | ✅ |

---

## 7. I18n & bilinguisme

### 7.1 Règle d'or

> **Aucune chaîne en dur dans le code.** Tout passe par `__('...')` ou `@lang('...')`.

### 7.2 Middleware de locale

```php
// app/Http/Middleware/SetLocale.php
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = 'fr'; // Défaut
        
        if ($request->user()?->locale) {
            $locale = $request->user()->locale; // 1. Préférence utilisateur
        } elseif ($request->cookie('locale')) {
            $locale = $request->cookie('locale'); // 2. Cookie
        } elseif ($request->getPreferredLanguage(['fr', 'en'])) {
            $locale = $request->getPreferredLanguage(['fr', 'en']); // 3. Accept-Language
        }
        
        app()->setLocale($locale);
        
        return $next($request);
    }
}
```

### 7.3 Fichiers de langue

```php
// resources/lang/fr/messages.php
return [
    'welcome' => 'Bienvenue sur FanPay',
    'login' => 'Connexion',
    'register' => 'Inscription',
    'buy_now' => 'Acheter maintenant',
    'listen_preview' => 'Écouter un extrait',
    'monthly' => 'Mensuel',
    'vip_access' => 'Accès VIP',
];

// resources/lang/en/messages.php
return [
    'welcome' => 'Welcome to FanPay',
    'login' => 'Log in',
    'register' => 'Sign up',
    'buy_now' => 'Buy now',
    'listen_preview' => 'Listen to preview',
    'monthly' => 'Monthly',
    'vip_access' => 'VIP Access',
];
```

### 7.4 Utilisation dans les templates

```blade
<h1>{{ __('messages.welcome') }}</h1>
<button>{{ __('messages.buy_now') }}</button>

<!-- Avec des paramètres -->
<p>{{ __('messages.vip_plan_price', ['price' => number_format($plan->price, 0, ',', ' ')]) }}</p>
```

---

## 8. Audit & traçabilité

### 8.1 Package Spatie Laravel ActivityLog

> On utilise **`spatie/laravel-activitylog`** pour l'audit trail — plus maintenu et plus riche que la table `audit_logs` custom.
> Il supporte les logs automatiques sur les événements Eloquent (created, updated, deleted).

**Installation :**
```bash
composer require spatie/laravel-activitylog
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="migrations"
php artisan migrate
```

### 8.2 Actions auditées

```php
// Dans les modèles sensibles, utiliser le trait LogsActivity
use Spatie\Activitylog\Traits\LogsActivity;

class Payout extends Model
{
    use LogsActivity;
    
    protected static $logName = 'payout';
    protected static $logOnlyDirty = true;  // Ne log que les changements
    protected static $logAttributes = ['status', 'amount', 'phone'];
    
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
        $activity->properties = $activity->properties->put('ip', request()->ip());
    }
}
```

**Actions auditées automatiquement :**
| Modèle | Événements | Attributs suivis |
|--------|-----------|-----------------|
| `Payout` | created, updated | status, amount, phone |
| `KycVerification` | updated | status, verified_by |
| `Payment` | created, updated | status, external_ref |
| `Dispute` | created, updated | status, resolution_note |
| `VipSubscription` | created, updated | status, expires_at |

**Actions manuelles :**
```php
// Pour les événements non liés à un modèle
activity()
    ->causedBy(auth()->user())
    ->withProperties(['ip' => request()->ip()])
    ->event('settings.changed')
    ->log('Commission rate changed from 10% to 12%');
```

### 8.3 Consultation des logs

- Dans Filament (admin) : widget ActivityLog pour les pages de gestion.
- Filtres par date, utilisateur, type d'événement.
- Rétention : 90 jours (configurable).

---

> **Document suivant :** [`docs/09-frontend-ux.md`](./09-frontend-ux.md) — Frontend & UX
