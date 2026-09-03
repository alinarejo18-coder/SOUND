<?php

require_once __DIR__ . '/MusicProvider.php';

/** Audius catalog/stream client. Audio is always played from Audius' stream endpoint. */
class AudiusProvider implements MusicProvider
{
    private string $appName  = 'SOUND';
    private string $gateway  = 'https://api.audius.co';
    private array  $fallbackHosts = [
        'https://discoveryprovider.audius.co',
        'https://discoveryprovider2.audius.co',
        'https://discoveryprovider3.audius.co',
    ];
    private ?string $apiKey;
    private ?string $resolvedHost = null;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?: (getenv('AUDIUS_API_KEY') ?: null);
    }

    public static function isConfigured(): bool
    {
        return true; // Audius public discovery endpoints do not require a secret key.
    }

    public function searchSongs(string $query, int $limit = 20): array
    {
        $data = $this->request('/tracks/search', ['query' => $query, 'limit' => min(50, max(1, $limit))]);
        $songs = [];
        foreach (($data['data'] ?? []) as $item) $songs[] = $this->normalize($item);
        return $songs;
    }

    public function getSong(string $id): ?array
    {
        $data = $this->request('/tracks/' . rawurlencode($id));
        return isset($data['data']) && is_array($data['data']) ? $this->normalize($data['data']) : null;
    }

    public function getAlbum(string $id): ?array { return null; }
    public function getArtist(string $id): ?array { return null; }
    public function getPlaylist(string $id): ?array { return null; }

    public function streamUrl(string $id): string
    {
        return $this->getHost() . '/v1/tracks/' . rawurlencode($id) . '/stream?app_name=' . urlencode($this->appName);
    }

    /** Fetch a live discovery node from the Audius gateway; fall back to a known host. */
    private function getHost(): string
    {
        if ($this->resolvedHost) return $this->resolvedHost;

        $url = $this->gateway . '?app_name=' . urlencode($this->appName);
        $ch  = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_FOLLOWLOCATION => true]);
        $body = curl_exec($ch);
        curl_close($ch);

        if ($body) {
            $decoded = json_decode($body, true);
            $hosts   = $decoded['data'] ?? [];
            if (is_array($hosts) && count($hosts) > 0) {
                $this->resolvedHost = rtrim($hosts[array_rand($hosts)], '/');
                return $this->resolvedHost;
            }
        }

        $this->resolvedHost = $this->fallbackHosts[0];
        return $this->resolvedHost;
    }

    private function normalize(array $item): array
    {
        $artwork = $item['artwork']['1000x1000'] ?? ($item['artwork']['480x480'] ?? ($item['artwork']['150x150'] ?? null));
        $id = (string)($item['id'] ?? '');
        return [
            'id'         => $id, 'provider' => 'audius', 'providerId' => $id,
            'type'       => 'songs', 'title' => $item['title'] ?? '',
            'artistName' => $item['user']['name'] ?? ($item['user']['handle'] ?? ''),
            'artistId'   => $item['user']['id'] ?? null,
            'albumName'  => $item['album_name'] ?? '', 'albumId' => null,
            'artworkUrl' => $artwork, 'duration' => isset($item['duration']) ? (int)$item['duration'] : null,
            'genre'      => $item['genre'] ?? '', 'releaseDate' => $item['release_date'] ?? null,
            'isPlayable' => (bool)($item['streamable'] ?? true), 'streamUrl' => $this->streamUrl($id),
        ];
    }

    private function request(string $path, array $params = []): array
    {
        $params['app_name'] = $this->appName;
        $url     = $this->getHost() . '/v1' . $path . '?' . http_build_query($params);
        $headers = ['Accept: application/json'];
        if ($this->apiKey) $headers[] = 'x-api-key: ' . $this->apiKey;
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 15, CURLOPT_FOLLOWLOCATION => true]);
        $body = curl_exec($ch); $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if (!$body || $status < 200 || $status >= 300) throw new RuntimeException('Audius request failed.');
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) throw new RuntimeException('Invalid Audius response.');
        return $decoded;
    }
}
