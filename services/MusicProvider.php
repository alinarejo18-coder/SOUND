<?php

/** Provider-neutral music metadata contract. */
interface MusicProvider
{
    public function searchSongs(string $query, int $limit = 20): array;
    public function getSong(string $id): ?array;
    public function getAlbum(string $id): ?array;
    public function getArtist(string $id): ?array;
    public function getPlaylist(string $id): ?array;
}
