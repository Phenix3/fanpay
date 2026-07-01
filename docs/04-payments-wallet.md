# 💳 04 — Paiements Mobile Money & Wallet Interne

> **Agrégateurs :** Campay (principal, service custom) + Notch Pay (fallback, SDK Laravel officiel)
> **Devise :** XAF (FCFA)
> **Commission :** 10% plateforme (verrouillé), frais agrégateur payés par le fan

---

## Table des matières

1. [Architecture des paiements](#1-architecture-des-paiements)
2. [Intégration Campay](#2-intégration-campay)
3. [Intégration Notch Pay (fallback)](#3-intégration-notch-pay-fallback)
4. [Flux de collecte (Collect)](#4-flux-de-collecte-collect)
5. [Flux de retrait (Payout)](#5-flux-de-retrait-payout)
6. [Wallet interne](#6-wallet-interne)
7. [Revenue Splits](#7-revenue-splits)
8. [Calcul des commissions & frais](#8-calcul-des-commissions--frais)
9. [Réconciliation & jobs CRON](#9-réconciliation--jobs-cron)
10. [Webhooks & idempotence](#10-webhooks--idempotence)

---

## 1. Architecture des paiements

### 1.1 Schéma général

```
┌──────────┐     ┌──────────────┐     ┌─────────────┐
│   Fan    │────▶│  Plateforme  │────▶│  Agrégateur  │
│ (Mobile) │     │  (Laravel)   │     │ (Campay/NP)  │
└──────────┘     └──────────────┘     └─────────────┘
     ▲                  │                    │
     │                  │  Webhook           │  API
     │                  ▼                    ▼
     │           ┌──────────────┐     ┌─────────────┐
     │           │  Wallet DB   │     │  Opérateur  │
     │           │  (append)    │     │  (Orange/MTN)│
     │           └──────────────┘     └─────────────┘
     └──────────────────────────────────────────┘
         Invite USSD + validation code secret
```

### 1.2 Choix d'architecture

| Décision | Justification |
|----------|---------------|
| **Campay = principal** | Focalisé Cameroun, MTN + Orange, API simple |
| **Notch Pay = fallback** | Si Campay down, route basculée automatiquement |
| **Webhooks = mode unique** | Pas de polling systématique (sauf réconciliation) |
| **Queue Redis** | Webhook → 200 immédiat + traitement async |
| **Wallet append-only** | Toute opération = nouvelle ligne, traçabilité |

### 1.3 Interface commune

```php
// Domain/Payments/Contracts/PaymentGateway.php
interface PaymentGateway
{
    // Collecte (paiement entrant)
    public function initiateCollect(array $params): CollectResponse;
    public function verifyPayment(string $reference): PaymentStatus;
    
    // Payout (retrait sortant)
    public function initiatePayout(array $params): PayoutResponse;
    public function verifyPayout(string $reference): PayoutStatus;
    
    // Webhook
    public function verifyWebhookSignature(Request $request): bool;
    public function parseWebhookPayload(Request $request): WebhookPayload;
}

// Value Objects
class CollectResponse {
    public function __construct(
        public readonly string $reference,
        public readonly string $externalRef,
        public readonly string $operatorUrl, // URL USSD / redirect
        public readonly string $status,
    ) {}
}

class PayoutResponse {
    public function __construct(
        public readonly string $reference,
        public readonly string $externalRef,
        public readonly string $status,
    ) {}
}
```

---

## 2. Intégration Campay

### 2.1 Configuration

```env
CAMPAY_WEBHOOK_KEY=campay_webhook_key
CAMPAY_PERMANENT_TOKEN=campay_permanent_token  # Permanent Access Token (recommandé)
# OU (alternative) :
CAMPAY_APP_KEY=campay_app_key
CAMPAY_APP_SECRET=campay_app_secret
CAMPAYMODE=sandbox  # sandbox ou production
```

> **Authentification :** Campay propose deux méthodes :
> 1. **Permanent Access Token** (recommandé) — jeton fixe qui n'expire pas. À utiliser dans le header `Authorization: Token VotreToken`.
> 2. **Temporary Access Token** — obtenu via `POST /api/token/` avec `username`/`password` (vos clés d'application). Jeton qui expire.

### 2.2 Collect (paiement)

```php
// app/Services/CampayService.php
class CampayService implements PaymentGateway
{
    private string $baseUrl;
    
    public function __construct(
        private string $permanentToken,
        private string $webhookKey,
    ) {
        $this->baseUrl = config('campay.mode') === 'production'
            ? 'https://www.campay.net/api'
            : 'https://demo.campay.net/api';
        // En sandbox : https://demo.campay.net
        // En production : https://campay.net
    }
    
    public function initiateCollect(array $params): CollectResponse
    {
        // POST https://{base}/api/collect/
        // Body: { amount, currency, from, description, external_reference }
        // Response: { reference, operator_url, status, ... }
        // Les montants doivent être en entier (pas de décimales)
        $response = Http::withToken($this->permanentToken, 'Token')
            ->post("{$this->baseUrl}/collect/", [
                'amount' => (string) $params['amount'],
                'currency' => 'XAF',
                'from' => $params['phone'],
                'description' => $params['description'],
                'external_reference' => $params['reference'],
            ]);
        
        return new CollectResponse(
            reference: $response['reference'],
            externalRef: $response['external_reference'],
            operatorUrl: $response['operator_url'] ?? '',
            status: $response['status'],
        );
    }
    
    private function getAccessToken(): string
    {
        // Alternative : Temporary Access Token via app_key/app_secret
        // POST https://demo.campay.net/api/token/
        // Body: { username: app_key, password: app_secret }
        return Cache::remember('campay_token', 3500, function () {
            $response = Http::post("{$this->baseUrl}/token/", [
                'username' => config('campay.app_key'),
                'password' => config('campay.app_secret'),
            ]);
            return $response['token'];
        });
    }
}
```

### 2.3 Payout (retrait)

```php
public function initiatePayout(array $params): PayoutResponse
{
    // POST https://{base}/api/withdraw/
    // Body: { amount, currency, to, description, external_reference }
    $response = Http::withToken($this->permanentToken, 'Token')
        ->post("{$this->baseUrl}/withdraw/", [
            'amount' => (string) $params['amount'],
            'currency' => 'XAF',
            'to' => $params['phone'],
            'description' => $params['description'],
            'external_reference' => $params['reference'],
        ]);
    
    return new PayoutResponse(
        reference: $response['reference'],
        externalRef: $response['external_reference'],
        status: $response['status'],
    );
}
```

### 2.4 Vérification webhook Campay

Campay utilise la validation par **JWT (JSON Web Token)** pour ses webhooks. La clé secrète de webhook (`webhook_key`) est disponible dans le dashboard Campay.

```php
public function verifyWebhookSignature(Request $request): bool
{
    // Campay utilise JWT pour signer ses webhooks
    // La clé secrète (webhook_key) est configurable dans le dashboard Campay
    try {
        $payload = $request->all();
        // Vérifier que le jeton JWT présent dans le payload
        // est valide avec la clé secrète de webhook
        // (utilisation de firebase/php-jwt ou équivalent)
        $decoded = \Firebase\JWT\JWT::decode(
            $request->header('X-Campay-Signature'),
            new \Firebase\JWT\Key($this->webhookKey, 'HS256')
        );
        return true;
    } catch (\Exception $e) {
        Log::warning('Campay webhook JWT verification failed', [
            'error' => $e->getMessage(),
        ]);
        return false;
    }
}
```

---

## 3. Intégration Notch Pay (fallback) — SDK Officiel Laravel

> Notch Pay fournit un **SDK Laravel officiel** (`notchpay/laravel-sdk`) avec Facades, configuration native et intégration directe.
> Voir https://developer.notchpay.co/sdks/laravel

### 3.1 Installation

```bash
composer require notchpay/laravel-sdk
php artisan vendor:publish --provider="NotchPay\NotchPayServiceProvider"
```

### 3.2 Configuration

```env
# .env — les clés sandbox commencent par test_, production par live_
NOTCHPAY_PUBLIC_KEY=test_pk_...
NOTCHPAY_HASH_KEY=***  # Clé HMAC pour webhooks (Settings > API Keys)
NOTCHPAY_MODE=sandbox
```

### 3.3 Implémentation

```php
// Le SDK fournit une Facade NotchPay
try {
    $payment = \NotchPay::checkout([
        'amount' => 2050,
        'currency' => 'XAF',
        'description' => "Achat album Muna",
        'reference' => 'PAY-20260701-ABCD',
        'payment_options' => ['mobile_money'],
        'callback_url' => route('webhooks.payment'),
    ]);
} catch (\NotchPay\Exceptions\ApiException $e) {
    // Logguer l'erreur et basculer vers Campay
}
```

### 3.4 Vérification webhook

```php
// SDK gère la validation HMAC via le Hash Key
// Header: x-notch-signature
// Algorithme: HMAC-SHA256(body + hash_key)

// Utilisation avec le service custom de résolution :
public function verifyWebhookSignature(Request $request): bool
{
    $signature = $request->header('x-notch-signature');
    $payload = $request->getContent();
    $expected = hash_hmac('sha256', $payload, config('notchpay.hash_key'));
    
    return hash_equals($expected, $signature);
}
```

### 3.5 Références

| Ressource | URL |
|-----------|-----|
| SDK Laravel officiel | https://developer.notchpay.co/sdks/laravel |
| API Reference | https://developer.notchpay.co/api-reference |
| Webhooks | https://developer.notchpay.co/get-started/webhooks/verify |
| Transfers (Payout) | https://developer.notchpay.co/send-money |

### 3.3 Passage de relais (fallback)

```php
// Domain/Payments/Actions/ProcessPaymentAction.php
class ProcessPaymentAction
{
    public function execute(array $params): CollectResponse
    {
        $gateway = $this->getPrimaryGateway();
        
        try {
            return $gateway->initiateCollect($params);
        } catch (GatewayException $e) {
            Log::warning('Campay failed, falling back to Notch Pay', [
                'error' => $e->getMessage(),
                'reference' => $params['reference'],
            ]);
            return $this->getFallbackGateway()->initiateCollect($params);
        }
    }
    
    private function getPrimaryGateway(): PaymentGateway
    {
        return app(CampayService::class);
    }
    
    private function getFallbackGateway(): PaymentGateway
    {
        return app(NotchPayService::class);
    }
}
```

---

## 4. Flux de collecte (Collect)

### 4.1 Séquence détaillée

```
1. Fan clique "Acheter" / "S'abonner VIP" / "Voter" etc.
2. Backend :
   a. Crée un Payment (status=pending)
   b. Crée une Transaction (status=pending)
   c. Appelle l'API agrégateur avec {montant, phone, ref}
3. Agrégateur :
   a. Envoie une invite USSD au téléphone du fan
   b. Répond avec reference + operator_url
4. Fan reçoit USSD → ouvre → valide avec code secret MoMo
5. Agrégateur → POST /api/webhooks/payment (notre serveur)
6. Notre webhook :
   a. Vérifie signature HMAC
   b. Dispatch ProcessPaymentWebhook (queue Redis)
   c. Répond 200 immédiatement
7. Queue ProcessPaymentWebhook :
   a. Vérifie idempotence (external_ref déjà traité ?)
   b. Vérifie que Payment existe
   c. Met à jour Payment (status=completed, external_ref)
   d. Calcule commission + frais
   e. Crédite wallet artiste (via WalletService)
   f. Crée Transaction (type=credit, montant_net)
   g. Gère les splits si nécessaire
   h. Débloque l'accès (téléchargement, VIP, billet...)
   i. Notifie l'artiste (in-app)
```

### 4.2 Code webhook

```php
// routes/api.php
Route::post('/webhooks/payment', [WebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('webhooks.payment');

// app/Http/Controllers/Api/WebhookController.php
class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Déterminer la gateway via le header
        $gateway = $this->resolveGateway($request);
        
        // Vérifier signature
        if (! $gateway->verifyWebhookSignature($request)) {
            Log::warning('Invalid webhook signature', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }
        
        // Dispatcher en queue (réponse rapide)
        ProcessPaymentWebhook::dispatch(
            $gateway::class,
            $request->all()
        );
        
        return response()->json(['status' => 'received'], 200);
    }
    
    private function resolveGateway(Request $request): PaymentGateway
    {
        // Campay utilise JWT (header X-Campay-Signature)
        if ($request->header('X-Campay-Signature')) {
            return app(CampayService::class);
        }
        // Notch Pay utilise HMAC (header x-notch-signature)
        if ($request->header('x-notch-signature')) {
            return app(NotchPayService::class);
        }
        abort(400, 'Unknown gateway');
    }
}
```

### 4.3 Job de traitement

```php
// app/Jobs/ProcessPaymentWebhook.php
#[Job]
class ProcessPaymentWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $tries = 3;
    public int $backoff = 30; // secondes
    
    public function __construct(
        private string $gatewayClass,
        private array $webhookData
    ) {}
    
    public function handle(
        PaymentService $paymentService,
        WalletService $walletService,
        RevenueSplitService $splitService,
    ): void {
        DB::transaction(function () use ($paymentService, $walletService, $splitService) {
            // 1. Vérifier idempotence
            $externalRef = $this->webhookData['external_reference'];
            $existingPayment = Payment::where('external_ref', $externalRef)->first();
            
            if ($existingPayment && $existingPayment->status === 'completed') {
                return; // Déjà traité, ignoré
            }
            
            // 2. Récupérer le payment par référence interne
            $payment = Payment::where('reference', $this->webhookData['external_reference'])
                ->orWhere('reference', $this->webhookData['reference'])
                ->firstOrFail();
            
            // 3. Mettre à jour le payment
            $payment->update([
                'status' => 'completed',
                'external_ref' => $externalRef,
                'webhook_payload' => $this->webhookData,
                'webhook_received_at' => now(),
                'paid_at' => now(),
            ]);
            
            // 4. Créditer le wallet (avec prise en compte des splits)
            $walletService->creditFromSale(
                payment: $payment,
                splits: $splitService->getSplitsForPayment($payment),
            );
        }, 5); // 5 tentatives pour deadlock
    }
}
```

---

## 5. Flux de retrait (Payout)

### 5.1 Workflow

```
1. Artiste consulte son solde wallet
2. Clique "Retirer" → formulaire (montant, numéro MoMo)
3. Backend vérifie : solde >= montant, KYC ok, montant >= min (5000 FCFA)
4. Crée Payout (status=pending)
5. Crée Transaction (type=debit, status=pending)
6. SI montant < auto_payout_threshold (50000 FCFA) :
   → auto-validation
   SINON :
   → notification admin pour approbation
7. Admin approuve (ou rejette)
8. Si approuvé → dispatch ProcessPayout (queue)
9. Queue appelle API payout agrégateur
10. Webhook payout → Payout = completed, Transaction = completed
```

### 5.2 Vérifications de sécurité

```php
// Domain/Wallet/Actions/RequestPayoutAction.php
class RequestPayoutAction
{
    public function execute(User $artist, int $amount, string $phone): Payout
    {
        // 1. Vérifier KYC
        if (!$artist->canRequestPayout()) {
            throw new PayoutException('KYC required');
        }
        
        // 2. Vérifier solde (avec verrou)
        $wallet = Wallet::where('user_id', $artist->id)
            ->lockForUpdate()
            ->firstOrFail();
        
        if ($wallet->balance < $amount) {
            throw new PayoutException('Insufficient balance');
        }
        
        // 3. Vérifier montant minimum
        $minAmount = Setting::get('min_payout_amount', 5000);
        if ($amount < $minAmount) {
            throw new PayoutException("Minimum: {$minAmount} FCFA");
        }
        
        // 4. Vérifier montant maximum
        $maxAmount = Setting::get('max_payout_amount', 500000);
        if ($amount > $maxAmount) {
            throw new PayoutException("Maximum: {$maxAmount} FCFA");
        }
        
        // 5. Créer le payout
        // ...
    }
}
```

---

## 6. Wallet interne

### 6.1 Règles métier

- **1 wallet par utilisateur** (fan comme artiste).
- **Append-only** : les transactions ne sont jamais modifiées ou supprimées.
- **Solde = cache** reconstruit à partir des transactions si nécessaire.
- **Toute opération en transaction DB** avec `lockForUpdate` pour éviter les race conditions.
- **Invariant financier** : `Σ soldes wallets = Σ crédits - Σ débits confirmés`.

### 6.2 WalletService

```php
// app/Services/WalletService.php
class WalletService
{
    public function creditFromSale(Payment $payment, array $splits): void
    {
        DB::transaction(function () use ($payment, $splits) {
            $netAmount = $payment->amount - $this->calculateCommission($payment->amount);
            
            if (empty($splits)) {
                // Paiement direct à l'artiste
                $this->credit($payment->artist_id, $netAmount, [
                    'source' => 'sale',
                    'source_id' => $payment->id,
                    'reference' => 'TX-' . Str::random(16),
                    'description' => "Vente #{$payment->reference}",
                ]);
            } else {
                // Revenue splits
                foreach ($splits as $split) {
                    $splitAmount = (int) ($netAmount * $split->percentage / 100);
                    $this->credit($split->artist_id, $splitAmount, [
                        'source' => 'split',
                        'split_id' => $split->id,
                        'reference' => 'TX-' . Str::random(16),
                        'description' => "Split vente #{$payment->reference} ({$split->percentage}%)",
                    ]);
                }
            }
        });
    }
    
    private function credit(int $userId, int $amount, array $meta): Transaction
    {
        $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->firstOrFail();
        
        $transaction = Transaction::create([
            'reference' => $meta['reference'],
            'wallet_id' => $wallet->id,
            'user_id' => $userId,
            'type' => 'credit',
            'amount' => $amount,
            'balance_before' => $wallet->balance,
            'balance_after' => $wallet->balance + $amount,
            'source' => $meta['source'],
            'split_id' => $meta['split_id'] ?? null,
            'description' => $meta['description'],
            'status' => 'completed',
        ]);
        
        $wallet->increment('balance', $amount);
        
        return $transaction;
    }
}
```

---

## 7. Revenue Splits

### 7.1 Fonctionnement

- Un artiste peut ajouter des collaborateurs sur une œuvre (track, album, video, produit).
- Chaque collaborateur a un pourcentage défini.
- Validation : la somme des pourcentages = 100%.
- À chaque vente, après commission plateforme, le net est réparti.
- Chaque collaborateur voit sa part dans son dashboard et retire indépendamment.

### 7.2 Gestion des splits

```php
// app/Services/RevenueSplitService.php
class RevenueSplitService
{
    public function validateSplits(array $splits): bool
    {
        $total = array_sum(array_column($splits, 'percentage'));
        return abs($total - 100.00) < 0.01; // Tolérance flottant
    }
    
    public function getSplitsForPayment(Payment $payment): array
    {
        // Trouver le modèle source (album, track, produit)
        $source = $payment->source;
        
        return RevenueSplit::where('splittable_type', $source->getMorphClass())
            ->where('splittable_id', $source->id)
            ->with('artist.wallet')
            ->get()
            ->toArray();
    }
}
```

---

## 8. Calcul des commissions & frais

### 8.1 Formule (verrouillée)

```
Prix affiché (fixé par l'artiste)      = P
Frais agrégateur (~2,5%)              = F → payés par le fan
Montant total payé par le fan         = P + F
Commission plateforme (10%)           = 0,10 × P
Montant crédité au wallet artiste      = 0,90 × P
```

### 8.2 Implémentation

```php
// app/Services/CommissionService.php
class CommissionService
{
    public function calculate(int $amount): CommissionBreakdown
    {
        $commissionRate = (int) Setting::get('commission_rate', 10);
        $feeRate = (float) Setting::get('momo_fee_rate', 2.5);
        
        $commission = (int) ($amount * $commissionRate / 100);
        $fees = (int) ceil($amount * $feeRate / 100); // Arrondi supérieur
        $net = $amount - $commission;
        
        return new CommissionBreakdown(
            amount: $amount,
            fees: $fees,        // Payé par le fan
            commission: $commission,  // Plateforme
            netAmount: $net,    // Reverse à l'artiste
            totalPaidByFan: $amount + $fees,
        );
    }
}

class CommissionBreakdown
{
    public function __construct(
        public readonly int $amount,
        public readonly int $fees,
        public readonly int $commission,
        public readonly int $netAmount,
        public readonly int $totalPaidByFan,
    ) {}
}
```

### 8.3 Exemple concret

```php
$breakdown = $commissionService->calculate(2000);
// amount: 2000
// fees: 50 (2.5% de 2000, arrondi supérieur)
// commission: 200 (10% de 2000)
// netAmount: 1800 (2000 - 200)
// totalPaidByFan: 2050 (2000 + 50)
```

---

## 9. Réconciliation & Jobs CRON

### 9.1 Jobs planifiés

```php
// app/Console/Kernel.php
class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Worker de queue (CRON + flock)
        $schedule->command('queue:work --stop-when-empty --max-time=55')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/queue-worker.log'));
        
        // Réconciliation : paiements restés "pending" > 30 min
        $schedule->command('payments:reconcile')
            ->everyFiveMinutes()
            ->withoutOverlapping();
        
        // Vérification de l'invariant financier (quotidien)
        $schedule->command('wallet:check-invariant')
            ->dailyAt('03:00')
            ->withoutOverlapping();
        
        // Expiration des abonnements VIP
        $schedule->command('vip:check-expirations')
            ->dailyAt('02:00')
            ->withoutOverlapping();
        
        // Rappels de renouvellement VIP (3 jours avant)
        $schedule->command('vip:send-renewal-reminders')
            ->dailyAt('10:00')
            ->withoutOverlapping();
    }
}
```

### 9.2 Commande de réconciliation

```php
// app/Console/Commands/ReconcilePayments.php
class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile';
    
    public function handle(PaymentGateway $gateway): void
    {
        // Trouver les paiements pending > 30 minutes
        $pendingPayments = Payment::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(30))
            ->get();
        
        foreach ($pendingPayments as $payment) {
            try {
                $status = $gateway->verifyPayment($payment->external_ref);
                
                if ($status->isCompleted()) {
                    // Traiter comme un webhook reçu
                    ProcessPaymentWebhook::dispatch($payment->gateway, [
                        'external_reference' => $payment->external_ref,
                        'reference' => $payment->reference,
                        'status' => 'SUCCESSFUL',
                    ]);
                } elseif ($status->isFailed()) {
                    $payment->update(['status' => 'failed']);
                }
            } catch (\Exception $e) {
                Log::error('Reconciliation failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
```

---

## 10. Webhooks & Idempotence

### 10.1 Règles

- **Toujours répondre 200** (même si erreur de traitement).
- **Idempotence** : un même `external_ref` ne peut être traité qu'une fois.
- **HMAC** : vérifier la signature avant tout traitement.
- **Queue** : ne jamais traiter le webhook dans la requête HTTP.
- **Logging** : stocker le payload brut dans `payments.webhook_payload`.

### 10.2 Protection contre le double traitement

```php
// Dans ProcessPaymentWebhook
$existing = Payment::where('external_ref', $externalRef)
    ->where('status', 'completed')
    ->exists();

if ($existing) {
    Log::info('Duplicate webhook received', ['external_ref' => $externalRef]);
    return; // Déjà traité
}
```

---

> **Document suivant :** [`docs/05-catalog-streaming.md`](./05-catalog-streaming.md) — Catalogue & Streaming
