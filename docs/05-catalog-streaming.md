# 🎧 05 — Catalogue & Streaming

> **Pipeline :** Upload client → Backblaze B2/R2 → Bunny Stream (transcodage HLS) → CDN
> **Player :** HLS.js (vidéo) + Howler.js (audio) — mini-player persistant
> **Protection :** Signed URLs + previews 30-45s + restriction domaine

---

## Table des matières

1. [Catalogue musical](#1-catalogue-musical)
2. [Spatie MediaLibrary](#2-spatie-medialibrary)
3. [Pipeline média](#3-pipeline-média)
4. [Upload direct client → Stockage objet](#4-upload-direct-client--stockage-objet)
5. [Transcodage HLS (Bunny Stream)](#5-transcodage-hls-bunny-stream)
6. [Player audio/vidéo](#6-player-audiovidéo)
7. [Protection du contenu (DRM léger)](#7-protection-du-contenu-drm-léger)
8. [Previews (30-45s)](#8-previews-30-45s)
9. [Découverte & recherche](#9-découverte--recherche)
10. [Comptage des écoutes](#10-comptage-des-écoutes)

---

## 1. Catalogue musical

### 1.1 Hiérarchie

```
Genre (afrobeat, makossa, bikutsi, hip-hop…)
  └── Album (type: album | single | ep)
        ├── Track 1
        ├── Track 2
        └── ...
  └── Vidéo (clip musical)
  └── Artiste (profil + catalogue)
```

### 1.2 Types de contenu

| Type | Stockage | Prix | Preview |
|------|----------|------|---------|
| **Album** | Ensemble de tracks | Prix global (ex: 5000 FCFA) | 30s par track |
| **Single** | 1 track | Prix unitaire (ex: 1000 FCFA) | 30s |
| **Vidéo (clip)** | 1 fichier HLS | Prix unitaire (ex: 1500 FCFA) | 30s |
| **Contenu gratuit** | HLS | Gratuit (streaming libre) | Complète |

### 1.3 Upload par l'artiste

```php
// Domain/Catalog/Actions/UploadTrackAction.php
class UploadTrackAction
{
    public function execute(User $artist, array $data, UploadedFile $file): Track
    {
        // 1. Créer le track
        $track = Track::create([
            'artist_id' => $artist->id,
            'album_id' => $data['album_id'] ?? null,
            'title' => $data['title'],
            'track_number' => $data['track_number'] ?? null,
            'duration' => 0, // Sera mis à jour après transcodage
            'file_path' => null, // Sera mis à jour
            'price' => $data['price'] ?? 0,
            'is_free' => $data['is_free'] ?? false,
            'is_published' => false,
        ]);
        
        // 2. Associer le média via Spatie MediaLibrary
        // Upload direct client → B2/R2 (via URL présignée, voir §4)
        // Le fichier original est stocké, les conversions sont gérées
        $track->addMedia($file)
            ->withCustomProperties([
                'artist_id' => $artist->id,
                'requires_transcoding' => true,
            ])
            ->toMediaCollection('track_audio');
        
        // 3. Déclencher le transcodage HLS (Bunny Stream)
        TranscodeMedia::dispatch($track);
        
        return $track;
    }
}
```

---

## 2. Spatie MediaLibrary

> **Package :** `spatie/laravel-medialibrary`
> Gère les uploads, conversions automatiques (WebP, miniatures), et l'association aux modèles Eloquent.

### 2.1 Collections de médias

```php
// app/Domain/Catalog/Models/Track.php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Track extends Model implements HasMedia
{
    use InteractsWithMedia;
    
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('track_audio')     // Fichier audio source
            ->singleFile()
            ->useDisk('b2');                         // Stockage sur B2/R2
        
        $this->addMediaCollection('track_cover')     // Pochette album
            ->singleFile()
            ->useDisk('b2');
    }
    
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->format('webp')
            ->nonQueued();
    }
}
```

### 2.2 Conversions automatiques

| Conversion | Taille | Format | Usage |
|-----------|--------|--------|-------|
| `thumb` | 300×300 | WebP | Miniature pochette |
| `small` | 150×150 | WebP | Avatar playlist |
| `cover` | 1200×1200 | WebP | Cover plein écran |

### 2.3 Stockage B2/R2

```php
// config/filesystems.php
'disks' => [
    'b2' => [
        'driver' => 's3',
        'key' => env('B2_KEY_ID'),
        'secret' => env('B2_APPLICATION_KEY'),
        'bucket' => env('B2_BUCKET'),
        'endpoint' => env('B2_ENDPOINT'),
        'region' => 'us-west-004',
        'visibility' => 'private',
    ],
],
```

---

## 3. Pipeline média

### 2.1 Schéma complet

```
1. Upload
   [Client] ──fichier──▶ [Backblaze B2 / R2] (URL signée pré-upload)
   
2. Transcodage
   [Webhook B2/R2] ──▶ [Laravel] ──dispatch──▶ [Bunny Stream API]
   [Bunny Stream] ──transcodage HLS──▶ [Bunny CDN] (multi-bitrate)
   
3. Diffusion
   [Client] ──▶ [Bunny CDN (Signed URL)] ──▶ [Player HLS]
   
4. Preview
   [Bunny] ──génération clip 30s──▶ [Preview File]
```

### 2.2 Services impliqués

```php
// app/Services/MediaService.php
class MediaService
{
    // Upload direct client → B2 (URL signée)
    public function generatePresignedUploadUrl(string $path, string $mimeType): string
    {
        // Générer URL signée B2/R2 pour upload direct
        // Le fichier ne transite JAMAIS par PHP
    }
    
    // Déclencher transcodage Bunny Stream
    public function triggerTranscoding(string $fileUrl, string $mediaType): string
    {
        // POST https://api.bunny.net/stream/{libraryId}/videos
        // Body: { url: $fileUrl, meta: { media_type: $mediaType } }
    }
    
    // Générer URL signée pour lecture
    public function generateSignedUrl(string $mediaPath, int $expiresIn = 3600): string
    {
        // Signature HMAC avec clé Bunny + expiration
        // ?token=expiry_signature
    }
}
```

---

## 4. Upload direct client → Stockage objet

### 4.1 Principe

Le fichier ne transite **jamais** par le serveur o2switch (contrainte I/O 16 Mo/s). L'upload se fait directement du navigateur du client vers Backblaze B2 (ou R2) via une URL présignée. Spatie MediaLibrary gère l'association au modèle une fois le fichier disponible.

### 4.2 Flux d'upload

```php
// 1. Frontend demande une URL présignée
$url = MediaService::generatePresignedUploadUrl(
    path: "artists/{$artist->id}/tracks/{$filename}",
    mimeType: 'audio/mpeg',
);

// 2. Frontend upload direct vers B2 via fetch/Ajax
// PUT {url} avec le fichier en body

// 3. Webhook B2 (optionnel) ou polling : fichier disponible
// 4. Associer le média au modèle Track via MediaLibrary
$track->addMediaFromDisk("artists/{$artist->id}/tracks/{$filename}", 'b2')
    ->toMediaCollection('track_audio');

// 5. Déclencher le transcodage Bunny Stream
```

---

## 5. Transcodage HLS (Bunny Stream)

### 4.1 Bunny Stream

Bunny Stream prend un fichier source et génère automatiquement :
- Plusieurs résolutions/bitrates (1080p, 720p, 480p, 360p)
- Playlist HLS (.m3u8) adaptative
- Miniatures (thumbnails) automatiques
- Preview clip (optionnel)

### 4.2 Intégration

```php
// app/Services/BunnyStreamService.php
class BunnyStreamService
{
    private string $apiKey;
    private string $libraryId;
    
    public function __construct()
    {
        $this->apiKey = config('bunny.api_key');
        $this->libraryId = config('bunny.stream_library_id');
    }
    
    public function createVideo(string $title, string $sourceUrl): array
    {
        $response = Http::withHeaders([
            'AccessKey' => $this->apiKey,
        ])->post("https://video.bunnycdn.com/library/{$this->libraryId}/videos", [
            'title' => $title,
        ]);
        
        $videoId = $response['guid'];
        
        // Déclencher l'import depuis l'URL source
        Http::withHeaders([
            'AccessKey' => $this->apiKey,
        ])->post("https://video.bunnycdn.com/library/{$this->libraryId}/videos/{$videoId}/fetch", [
            'url' => $sourceUrl,
        ]);
        
        return [
            'video_id' => $videoId,
            'status' => 'processing',
        ];
    }
    
    public function getVideoStatus(string $videoId): array
    {
        return Http::withHeaders([
            'AccessKey' => $this->apiKey,
        ])->get("https://video.bunnycdn.com/library/{$this->libraryId}/videos/{$videoId}")
            ->json();
    }
}
```

### 4.3 Webhook de fin de transcodage

```php
// Route : POST /api/webhooks/bunny
// Voir docs/07-api-endpoints.md

class BunnyWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Vérifier signature Bunny
        // Header: X-BunnyStream-Signature
        // Signing secret = Read-Only API Key de la bibliothèque
        $signature = $request->header('X-BunnyStream-Signature');
        $payload = $request->getContent();
        $expected = hash_hmac('sha256', $payload, config('bunny.read_only_api_key'));
        
        if (!hash_equals($expected, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }
        
        $data = $request->all();
        $videoId = $data['video_guid'];
        $status = $data['event_type'] ?? null;
        // Statuts : 
        //   3 = "Finished" (encodage terminé)
        //   4 = Résolution individuelle terminée (premier 4 = lecture possible)
        
        if ($status === 'video.ready' || $data['status'] === 3) {
            // Mettre à jour le Track / Video avec le path HLS
            // L'URL de lecture Bunny Stream suit ce format :
            // https://iframe.mediadelivery.net/embed/{libraryId}/{videoId}/playlist.m3u8
            $hlsUrl = "https://iframe.mediadelivery.net/embed/{$this->libraryId}/{$videoId}/playlist.m3u8";
            
            Track::where('bunny_video_id', $videoId)
                ->update([
                    'file_path' => $hlsUrl,
                    'duration' => $data['video_length'] ?? 0,
                    'is_published' => true,
                ]);
        }
        
        return response()->json(['status' => 'ok'], 200);
    }
}
```

---

## 6. Player audio/vidéo

### 5.1 Mini-player persistant (Audio)

```
┌──────────────────────────────────────────────────────────┐
│ ⏮️  ⏸️  ⏭️    🎵 Titre — Artiste    [♡]    [🔊────]   │
│              ████████████░░░░░░░ 2:34 / 4:12             │
│              [➖ Playlist]     [🔗 Partager]              │
└──────────────────────────────────────────────────────────┘
```

**Comportement :**
- Barre fixe en bas de l'écran (persistant entre les pages).
- Lecture audio en continu pendant la navigation.
- Contrôles : play/pause, next/prev, volume, progression.
- Miniature album + titre + artiste affichés.
- Clic → page complète du morceau.

**Technologie :** Howler.js (audio) — léger, performant, compatible mobile.

### 5.2 Player vidéo HLS

```html
<!-- Composant Livewire/Lade -->
<video id="hls-player" class="w-full aspect-video" controls></video>

<script>
import Hls from 'hls.js';

if (Hls.isSupported()) {
    const video = document.getElementById('hls-player');
    const hls = new Hls();
    hls.loadSource('{{ $signedUrl }}');
    hls.attachMedia(video);
    hls.on(Hls.Events.MANIFEST_PARSED, () => video.play());
}
</script>
```

**Comportement :**
- Lecture adaptative (qualité selon connexion : 3G/4G/WiFi).
- Mode plein écran natif.
- Compatible mobile (Android Chrome, iOS Safari).

---

## 7. Protection du contenu (DRM léger)

### 6.1 Signed URLs (Bunny CDN)

> **Formule Bunny Stream :** `SHA256_HEX(token_security_key + video_id + expiration)`

```php
// Génération d'URL signée Bunny Stream
public function generateSignedUrl(string $videoId, int $expiresIn = 3600): string
{
    $securityKey = config('bunny.security_key');
    $libraryId = config('bunny.stream_library_id');
    $expires = time() + $expiresIn;
    
    // La signature combine : token_security_key + video_id + expiration
    $token = hash('sha256', "{$securityKey}{$videoId}{$expires}");
    
    return "https://iframe.mediadelivery.net/embed/{$libraryId}/{$videoId}"
        . "?token={$token}&expires={$expires}";
}

// Utilisation dans le controller :
$signedUrl = $bunnyService->generateSignedUrl(
    videoId: $track->bunny_video_id,
    expiresIn: 3600 // 1 heure
);
```

### 6.2 Stratégie de protection

| Mesure | Implémentation | Efficacité |
|--------|---------------|------------|
| **Signed URLs** | Token HMAC + expiration (1h) | ✅ ✅ Empêche le hotlinking |
| **Restriction domaine** | Bunny CDN → Referer restriction | ✅ Empêche l'embed externe |
| **Session binding** | URL liée à session ID (optionnel) | ✅ ✅ Empêche le partage de lien |
| **Preview gratuite** | Contenu complet = achat/VIP requis | ✅ ✅ Modèle économique |
| **Watermark** | Filigrane sur previews (optionnel V2) | ✅ Dissuasion |

### 6.3 Vérification d'accès

```php
// Dans le controller de lecture
public function play(Track $track)
{
    // Vérifier accès
    $canAccess = $track->is_free
        || auth()->user()?->hasPurchased($track)
        || auth()->user()?->hasVipAccess($track->artist);
    
    if (!$canAccess) {
        // Donner la preview uniquement
        $url = $track->preview_path;
    } else {
        $url = $track->file_path;
    }
    
    $signedUrl = $this->bunnyService->generateSignedUrl($url);
    
    return view('player.track', [
        'track' => $track,
        'signedUrl' => $signedUrl,
        'canAccess' => $canAccess,
    ]);
}
```

---

## 8. Previews (30-45s)

- Générées automatiquement par Bunny Stream (option de clipping).
- Stockées séparément dans `tracks.preview_path` et `videos.preview_path`.
- Accessibles sans achat (gratuites pour tous).
- Utilisées dans les pages de découverte, recherche, et profils artiste.

---

## 9. Découverte & recherche

### 8.1 Pages de découverte

| Page | Source de données | Cache |
|------|------------------|-------|
| **Accueil** | Top écoutes, nouveautés, artistes en vedette | Redis (15 min) |
| **Charts** | Classement par écoutes/ventes | Redis (15 min) |
| **Genres** | Filtrage par genre | Redis (1h) |
| **Recherche** | MySQL FULLTEXT | Pas de cache |

### 8.2 Recherche MySQL FULLTEXT

```sql
ALTER TABLE tracks ADD FULLTEXT INDEX tracks_search (title);
ALTER TABLE artist_profiles ADD FULLTEXT INDEX artists_search (stage_name);

-- Requête :
SELECT * FROM tracks 
WHERE MATCH(title) AGAINST(:query IN BOOLEAN MODE)
  AND is_published = 1
LIMIT 20;
```

La recherche fulltext MySQL est suffisante pour le volume attendu au lancement. Une migration vers Meilisearch ou Algolia pourra être envisagée en V2 si nécessaire.

---

## 10. Comptage des écoutes

### 9.1 Table `plays`

```php
// Incrémenter le compteur (Livewire ou JavaScript)
public function trackPlay(Track $track): void
{
    // Dédoublonnage : ignorer si même IP < 30s
    $recentPlay = Play::where('track_id', $track->id)
        ->where('ip_address', request()->ip())
        ->where('played_at', '>', now()->subSeconds(30))
        ->exists();
    
    if ($recentPlay) {
        return;
    }
    
    Play::create([
        'track_id' => $track->id,
        'user_id' => auth()->id(),
        'ip_address' => request()->ip(),
    ]);
    
    // Incrémenter le cache
    $track->increment('play_count');
    $track->album?->increment('total_plays');
    $track->artist->profile->incrementStat('total_plays');
}
```

### 9.2 Affichage des stats

```php
// Dashboard artiste : agrégation par période
$playsByDay = Play::where('track_id', $track->id)
    ->where('played_at', '>=', now()->subDays(30))
    ->selectRaw('DATE(played_at) as date, COUNT(*) as count')
    ->groupBy('date')
    ->get();
```

---

> **Document suivant :** [`docs/06-monetization.md`](./06-monetization.md) — Monétisation avancée
