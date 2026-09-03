<?php

/** Server-side YouTube Data API client; video bytes are never downloaded. */
class YouTubeProvider
{
    private string $baseUrl = 'https://www.googleapis.com/youtube/v3';
    private ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->loadEnvironment();
        $this->apiKey = $apiKey ?: self::configuredKey();
    }

    public static function isConfigured(): bool
    {
        return self::configuredKey() !== null;
    }

    public function searchVideos(string $query, int $limit = 20): array
    {
        $search = $this->request('/search', ['part' => 'snippet', 'type' => 'video', 'q' => $query, 'maxResults' => min(25, max(1, $limit))]);
        if (!isset($search['items']) || !is_array($search['items'])) return [];
        $ids = array_values(array_filter(array_map(fn($x) => $x['id']['videoId'] ?? null, $search['items'] ?? [])));
        if (!$ids) return [];
        $details = $this->request('/videos', ['part' => 'snippet,contentDetails,status', 'id' => implode(',', $ids)]);
        if (!isset($details['items']) || !is_array($details['items'])) return [];
        return array_map(fn($item) => $this->normalize($item), $details['items']);
    }

    public function getVideo(string $id): ?array
    {
        $data = $this->request('/videos', ['part' => 'snippet,contentDetails,status', 'id' => $id]);
        return isset($data['items'][0]) ? $this->normalize($data['items'][0]) : null;
    }

    private function normalize(array $item): array
    {
        $s = $item['snippet'] ?? [];
        $videoId = (string)($item['id'] ?? '');
        return ['id' => $videoId, 'provider' => 'youtube', 'providerId' => $videoId, 'title' => $s['title'] ?? '', 'description' => $s['description'] ?? '', 'thumbnailUrl' => $s['thumbnails']['high']['url'] ?? ($s['thumbnails']['default']['url'] ?? ''), 'channelName' => $s['channelTitle'] ?? '', 'publishedAt' => $s['publishedAt'] ?? null, 'duration' => $this->duration($item['contentDetails']['duration'] ?? null), 'videoUrl' => $videoId !== '' ? 'https://www.youtube.com/watch?v=' . rawurlencode($videoId) : '', 'isPlayable' => ($item['status']['embeddable'] ?? true) !== false];
    }

    private function request(string $path, array $params): array
    {
        if (!$this->apiKey) throw new RuntimeException('YouTube is not configured.');
        $params['key'] = $this->apiKey;
        $ch = curl_init($this->baseUrl . $path . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'SOUND YouTube Search Client',
        ]);
        $body = curl_exec($ch); $curlError = curl_error($ch); $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($body === false || $curlError !== '') {
            throw new RuntimeException('YouTube network request failed: ' . ($curlError ?: 'Unknown cURL error.'));
        }
        if ($status < 200 || $status >= 300) {
            $api_error = json_decode($body, true);
            $message = $api_error['error']['message'] ?? 'YouTube API request failed.';
            $reason = $api_error['error']['errors'][0]['reason'] ?? '';
            throw new RuntimeException('YouTube API error: ' . $message . ($reason !== '' ? ' (' . $reason . ')' : ''));
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) throw new RuntimeException('Invalid YouTube response.');
        return $decoded;
    }

    private function loadEnvironment(): void
    {
        $path = dirname(__DIR__) . '/.env';
        if (is_file($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines ?: [] as $line) {
                if (preg_match('/^\s*([A-Z][A-Z0-9_]*)\s*=\s*(.*)\s*$/', $line, $matches)) {
                    $value = trim($matches[2]);
                    if (($value[0] ?? '') === '"' && substr($value, -1) === '"') $value = substr($value, 1, -1);
                    if (($value[0] ?? '') === "'" && substr($value, -1) === "'") $value = substr($value, 1, -1);
                    if (getenv($matches[1]) === false && !isset($_ENV[$matches[1]]) && !isset($_SERVER[$matches[1]])) {
                        putenv($matches[1] . '=' . $value);
                        $_ENV[$matches[1]] = $value;
                        $_SERVER[$matches[1]] = $value;
                    }
                }
            }
        }
    }

    private static function configuredKey(): ?string
    {
        foreach ([getenv('YOUTUBE_API_KEY'), $_ENV['YOUTUBE_API_KEY'] ?? null, $_SERVER['YOUTUBE_API_KEY'] ?? null, self::readEnvironmentValue('YOUTUBE_API_KEY')] as $value) {
            $value = is_string($value) ? trim($value) : '';
            if ($value !== '') return $value;
        }
        return null;
    }

    private static function readEnvironmentValue(string $name): ?string
    {
        $path = dirname(__DIR__) . '/.env';
        if (!is_file($path)) return null;
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (preg_match('/^\s*' . preg_quote($name, '/') . '\s*=\s*["\']?([^"\']*)["\']?\s*$/', $line, $matches)) {
                return trim($matches[1]);
            }
        }
        return null;
    }

    private function duration(?string $iso): ?int
    {
        if (!$iso || !preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $iso, $m)) return null;
        return ((int)($m[1] ?? 0) * 3600) + ((int)($m[2] ?? 0) * 60) + (int)($m[3] ?? 0);
    }
}
