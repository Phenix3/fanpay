# 💰 06 — Monétisation

> **Modules :** Boutique, VIP prepaid, Dédicaces, Événements/Billetterie, Vote/Concours, Tips
> **Tous les modules s'appuient sur :** Wallet + Campay/Notch Pay + Revenue Splits

---

## Table des matières

1. [Boutique](#1-boutique)
2. [Abonnements VIP (prepaid/top-up)](#2-abonnements-vip-prepaidtop-up)
3. [Dédicaces personnalisées](#3-dédicaces-personnalisées)
4. [Événements & billetterie](#4-événements--billetterie)
5. [Vote & concours](#5-vote--concours)
6. [Tips / Dons](#6-tips--dons)

---

## 1. Boutique

### 1.1 Types de produits

| Type | Exemples | Stock | Fichier |
|------|----------|-------|---------|
| **Digital** | Téléchargement MP3, WAV, beats, instrumentaux | Illimité | URL fichier |
| **Physique** | T-shirts, CDs, posters, goodies | Limité | N/A |

### 1.2 Flux d'achat

```
Fan → Page produit → Ajouter au panier → Paiement MoMo
    → Succès : accès téléchargement (digital) ou confirmation livraison (physique)
    → Échec : réessayer
```

### 1.3 Modèle de données (voir docs/02-database.md)

- `products` : informations produit
- `orders` : commande (regroupement de plusieurs items)
- `order_items` : lignes de commande

### 1.4 Contrôle d'accès (produits digitaux)

```php
// Vérification : le fan a-t-il acheté ce produit ?
$purchased = OrderItem::whereHas('order', function ($q) use ($user) {
    $q->where('user_id', $user->id)->where('status', 'completed');
})->where('product_id', $product->id)->exists();
```

---

## 2. Abonnements VIP (prepaid/top-up)

### 2.1 Contexte

> ⚠️ Mobile Money (Orange Money, MTN MoMo) ne gère pas les débits récurrents automatiques.
> **Solution :** abonnement "prépayé" — le fan paie d'avance pour une durée fixe.

### 2.2 Fonctionnement

```
1. Artiste crée un plan VIP (ex: "Fan Premium" — 3000 FCFA / 30 jours)
2. Fan consulte la page VIP de l'artiste
3. Fan choisit un plan → paie via MoMo
4. Accès immédiat au contenu exclusif
5. J-3 avant expiration : rappel par notification (in-app + email)
6. J+0 expiration : accès révoqué, statut = expired
7. Fan peut renouveler (nouveau paiement) depuis sa bibliothèque
```

### 2.3 Accès VIP

```php
// Vérification : le fan a-t-il un abonnement VIP actif chez cet artiste ?
public function hasVipAccess(User $fan, User $artist): bool
{
    return VipSubscription::where('user_id', $fan->id)
        ->whereHas('plan', fn($q) => $q->where('artist_id', $artist->id))
        ->where('status', 'active')
        ->where('expires_at', '>', now())
        ->exists();
}
```

### 2.4 Contenu exclusif

- Les artistes marquent certaines tracks/albums/vidéos comme "VIP uniquement".
- Marquage via colonne `is_vip_exclusive` (à ajouter sur tracks, albums, videos).

### 2.5 Job d'expiration

```php
// app/Console/Commands/CheckVipExpirations.php
class CheckVipExpirations extends Command
{
    protected $signature = 'vip:check-expirations';
    
    public function handle(): void
    {
        VipSubscription::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);
    }
}
```

---

## 3. Dédicaces personnalisées

### 3.1 Concept

Un fan paie pour recevoir une dédicace personnalisée de son artiste préféré (message audio, vidéo ou texte).

### 3.2 Flux

```
Fan :
1. Va sur la page dédicaces de l'artiste
2. Saisit le message souhaité + nom du destinataire
3. Paie le prix fixé par l'artiste (ex: 5000 FCFA)
4. Statut = pending

Artiste (dashboard) :
1. Voit la commande dans "Dédicaces en attente"
2. Enregistre un audio/vidéo ou écrit un message
3. Statut = delivered
4. Notification au fan

Fan :
1. Reçoit notification "Votre dédicace est prête !"
2. Consulte et télécharge le contenu
```

### 3.3 Prix & durée

- Prix fixé par l'artiste (configurable par artiste).
- Délai de livraison : affiché sur la page (ex: "livraison sous 48h").
- Si dépassement, le fan peut ouvrir un litige.

---

## 4. Événements & billetterie

### 4.1 Schéma complet

```
Événement (concert, showcase)
  ├── Type de billet Standard (5000 FCFA — 200 places)
  ├── Type de billet VIP (15000 FCFA — 50 places)
  └── Ventes
        ├── Billet #1 → QR code unique → Fan X
        ├── Billet #2 → QR code unique → Fan Y
        └── ...

Soir de l'événement :
  → Fan présente son QR code
  → Artiste/Staff scanne → Billet marqué "used"
  → Contrôle d'accès
```

### 4.2 Création d'événement (artiste)

```php
// Domain/Events/Actions/CreateEventAction.php
class CreateEventAction
{
    public function execute(User $artist, array $data): Event
    {
        $event = Event::create([
            'artist_id' => $artist->id,
            'title' => $data['title'],
            'venue' => $data['venue'],
            'date' => $data['date'],
            'description' => $data['description'],
        ]);
        
        foreach ($data['ticket_types'] as $type) {
            TicketType::create([
                'event_id' => $event->id,
                'name' => $type['name'],
                'price' => $type['price'],
                'quantity' => $type['quantity'],
            ]);
        }
        
        return $event;
    }
}
```

### 4.3 Achat de billet

```php
// Domain/Events/Actions/PurchaseTicketAction.php
class PurchaseTicketAction
{
    public function execute(User $fan, TicketType $ticketType, int $quantity): array
    {
        // Vérifier stock disponible
        $available = $ticketType->quantity - $ticketType->sold;
        if ($quantity > $available) {
            throw new InsufficientTicketsException();
        }
        
        // Créer les billets avec QR codes uniques
        $tickets = [];
        for ($i = 0; $i < $quantity; $i++) {
            $tickets[] = Ticket::create([
                'ticket_type_id' => $ticketType->id,
                'user_id' => $fan->id,
                'qr_code' => Str::uuid(),
                'status' => 'valid',
            ]);
        }
        
        // Lancer le paiement (via Campay/Notch Pay)
        $paymentUrl = $this->initiatePayment(
            amount: $ticketType->price * $quantity,
            user: $fan,
            metadata: ['tickets' => array_map(fn($t) => $t->id, $tickets)],
        );
        
        return [
            'tickets' => $tickets,
            'payment_url' => $paymentUrl,
        ];
    }
}
```

### 4.4 Scan de billets (contrôle d'accès)

- Interface Livewire sur mobile (responsive).
- Scan du QR code via caméra (html5-qrcode ou équivalent).
- Vérification : `Ticket::where('qr_code', $code)->where('status', 'valid')->exists()`.
- 3 statuts de scan : `valid`, `invalid`, `duplicate`.
- Enregistrement dans `ticket_scans`.

### 4.5 QR Code

```php
// Génération du QR (utiliser simple-qrcode ou équivalent)
use SimpleSoftwareIO\QrCode\Facades\QrCode;

$qrSvg = QrCode::size(200)
    ->generate(route('ticket.verify', $ticket->qr_code));
```

---

## 5. Vote & concours

### 5.1 Concept

Concours musicaux payants (ex: "Artiste du mois", "Meilleur clip") où :
- **Admin** crée le concours, fixe le prix du vote, les dates.
- **Artistes** sont candidats (inscrits par admin ou auto-inscription).
- **Fans** paient pour voter (1 vote = X FCFA).
- **Classement** en temps réel (public).

### 5.2 Anti-fraude

| Mesure | Détail |
|--------|--------|
| **Vote payant** | Chaque vote = 1 paiement → coût real |
| **Limite par user** | Configurable (ex: 100 votes max par concours) |
| **Journalisation IP** | `votes.ip_address` pour détection d'anomalies |
| **Détection de pic** | Alerte admin si > 1000 votes/heure pour un candidat |

### 5.3 Résultats

- Affichage en temps réel du classement (Livewire polling ou Pusher).
- Proclamation automatique à la date de fin.
- Résultats archivés dans le concours.

---

## 6. Tips / Dons

### 6.1 Concept

Un fan peut soutenir un artiste financièrement sans acheter de contenu spécifique.

### 6.2 Flux

```
Page artiste → Bouton "Tip" → Montant libre (min 500 FCFA)
    → Paiement MoMo → Crédit wallet artiste (net après commission)
    → Message optionnel du fan
```

### 6.3 Implémentation

```php
// Domain/Wallet/Actions/TipArtistAction.php
class TipArtistAction
{
    public function execute(User $fan, User $artist, int $amount, ?string $message): void
    {
        // 1. Paiement (via Campay/Notch Pay)
        $payment = $this->paymentService->initiateCollect([
            'amount' => $amount,
            'phone' => $fan->phone,
            'description' => "Tip pour {$artist->name}",
        ]);
        
        // 2. Après confirmation webhook → crédit wallet
        // (traité par ProcessPaymentWebhook)
    }
}
```

### 6.4 Affichage

- Montant total des tips reçus visible sur le profil artiste (optionnel).
- Message du fan visible par l'artiste uniquement.
- Pas de mur des donateurs public (respect de la vie privée).

---

> **Document suivant :** [`docs/07-api-endpoints.md`](./07-api-endpoints.md) — Routes API & Webhooks
