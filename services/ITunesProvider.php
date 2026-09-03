<?php

require_once __DIR__ . '/MusicProvider.php';

/** Apple iTunes Search API client for music metadata and preview URLs. */
class ITunesProvider implements MusicProvider
{
    private string $endpoint = 'https://itunes.apple.com';

    public function searchSongs(string $query, int $limit = 20): array
    {
        $data = $this->request('/search', [
            'term' => $query,
            'media' => 'music',
            'entity' => 'song',
            'limit' => min(200, max(1, $limit)),
        ]);

        $songs = [];
        foreach (($data['results'] ?? []) as $item) {
            if (is_array($item)) {
                $songs[] = $this->normalize($item);
            }
        }
        return $songs;
    }

    public function getSong(string $id): ?array
    {
        $data = $this->request('/lookup', ['id' => $id, 'entity' => 'song']);
        foreach (($data['results'] ?? []) as $item) {
            if (is_array($item) && (string)($item['trackId'] ?? '') === $id) {
                return $this->normalize($item);
            }
        }
        return null;
    }

    public function getAlbum(string $id): ?array
    {
        $data = $this->request('/lookup', ['id' => $id, 'entity' => 'song']);
        foreach (($data['results'] ?? []) as $item) {
            if (is_array($item) && (string)($item['collectionId'] ?? '') === $id) {
                return $this->normalize($item);
            }
        }
        return null;
    }

    public function getArtist(string $id): ?array
    {
        $data = $this->request('/lookup', ['id' => $id, 'entity' => 'song']);
        foreach (($data['results'] ?? []) as $item) {
            if (is_array($item) && (string)($item['artistId'] ?? '') === $id) {
                return $this->normalize($item);
            }
        }
        return null;
    }

    public function getPlaylist(string $id): ?array
    {
        return null;
    }

    private function normalize(array $item): array
    {
        $artwork = (string)($item['artworkUrl100'] ?? '');
        if ($artwork !== '') {
            $artwork = preg_replace('/\/\d+x\d+bb\.(jpg|jpeg|png)$/i', '/600x600bb.$1', $artwork) ?: $artwork;
        }

        $trackId = (string)($item['trackId'] ?? '');
        return [
            'id' => $trackId,
            'provider' => 'itunes',
            'providerId' => $trackId,
            'type' => 'song',
            'title' => (string)($item['trackName'] ?? ''),
            'artistName' => (string)($item['artistName'] ?? ''),
            'artistId' => isset($item['artistId']) ? (string)$item['artistId'] : null,
            'albumName' => (string)($item['collectionName'] ?? ''),
            'albumId' => isset($item['collectionId']) ? (string)$item['collectionId'] : null,
            'artworkUrl' => $artwork,
            'duration' => isset($item['trackTimeMillis']) ? (int)round(((int)$item['trackTimeMillis']) / 1000) : null,
            'genre' => (string)($item['primaryGenreName'] ?? ''),
            'releaseDate' => $item['releaseDate'] ?? null,
            'isPlayable' => !empty($item['previewUrl']),
            'previewUrl' => (string)($item['previewUrl'] ?? ''),
            'streamUrl' => (string)($item['previewUrl'] ?? ''),
        ];
    }

    private function request(string $path, array $params): array
    {
        $url = $this->endpoint . $path . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'SOUND iTunes Search Client',
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $error !== '' || $status < 200 || $status >= 300) {
            throw new RuntimeException('iTunes request failed.');
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || !isset($decoded['results']) || !is_array($decoded['results'])) {
            throw new RuntimeException('Invalid iTunes response.');
        }
        return $decoded;
    }
}