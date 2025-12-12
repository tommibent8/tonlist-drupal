<?php

namespace Drupal\musicsearch;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;

class DiscogsLookupService {

  protected $config;
  protected $httpClient;

  public function __construct(ConfigFactoryInterface $config_factory, Client $http_client) {
    $this->config = $config_factory->get('musicsearch.settings');
    $this->httpClient = $http_client;
  }

  protected function token() {
    return $this->config->get('discogs_token');
  }

  protected function apiGet($url, $params = []) {
    $params['token'] = $this->token();

    try {
      $res = $this->httpClient->get($url, ['query' => $params]);
      return json_decode($res->getBody(), TRUE);
    }
    catch (\Exception $e) {
      return null;
    }
  }

  // ============================================================
  // MAIN SEARCH ENTRY POINT
  // ============================================================
  public function fullSearch($artistInput, $albumInput, $trackInput) {

    $artistInput = trim($artistInput);
    $albumInput  = trim($albumInput);
    $trackInput  = trim($trackInput);

    $result = [
      'artist' => [],
      'album'  => [],
      'track'  => [],
    ];

    // ------------------------------------------------------------
    // 1. ARTIST ONLY
    // ------------------------------------------------------------
    if ($artistInput && !$albumInput && !$trackInput) {
      $artist = $this->getArtistDetails($this->searchArtistId($artistInput));
      return $this->normalizeDiscogsResult($artist, null, null);
    }

    // ------------------------------------------------------------
    // 2. ALBUM ONLY
    // ------------------------------------------------------------
    if ($albumInput && !$trackInput) {
      $album = $this->getAlbumDetails($this->searchAlbumId($albumInput));
      return $this->normalizeDiscogsResult(
        $this->getArtistFromAlbum($album),
        $album,
        null
      );
    }

    // ------------------------------------------------------------
    // 3. ALBUM + TRACK  (CORRECTLY HANDLED)
    // ------------------------------------------------------------
    if ($albumInput && $trackInput) {
      $album = $this->getAlbumDetails($this->searchAlbumId($albumInput));
      if (!$album) return $result;

      $track = $this->extractTrackFromAlbum($album, $trackInput);

      return $this->normalizeDiscogsResult(
        $this->getArtistFromAlbum($album),
        $album,
        $track
      );
    }

    // ------------------------------------------------------------
    // 4. ARTIST + TRACK  (THIS WAS MISSING)
    // ------------------------------------------------------------
    if ($artistInput && $trackInput) {

      $artist = $this->getArtistDetails(
        $this->searchArtistId($artistInput)
      );

      if (!$artist || empty($artist['releases_url'])) {
        return $result;
      }

      // Look through artist releases (album-first)
      $releases = $this->apiGet($artist['releases_url'], [
        'per_page' => 10,
      ]);

      foreach ($releases['releases'] ?? [] as $release) {
        $album = $this->getAlbumDetails($release['id']);
        if (!$album) continue;

        $track = $this->extractTrackFromAlbum($album, $trackInput);
        if ($track) {
          return $this->normalizeDiscogsResult($artist, $album, $track);
        }
      }

      // Artist found, but no matching track
      return $this->normalizeDiscogsResult($artist, null, null);
    }

    // ------------------------------------------------------------
    // 5. TRACK ONLY (WEAK, BUT KEEP AS FALLBACK)
    // ------------------------------------------------------------
    if ($trackInput) {
      $trackSearch = $this->searchTrackId($trackInput);
      if (!$trackSearch) return $result;

      $album = $trackSearch['master_id']
        ? $this->getAlbumDetails($trackSearch['master_id'])
        : $this->getReleaseDetails($trackSearch['release_id']);

      return $this->normalizeDiscogsResult(
        $this->getArtistFromAlbum($album),
        $album,
        $this->extractTrackFromAlbum($album, $trackInput)
      );
    }

    return $result;
  }

  // ============================================================
  // SEARCH HELPERS
  // ============================================================
  protected function searchArtistId($name) {
    $json = $this->apiGet('https://api.discogs.com/database/search', [
      'q' => $name,
      'type' => 'artist',
      'per_page' => 1,
    ]);
    return $json['results'][0]['id'] ?? null;
  }

  protected function searchAlbumId($name, $artistId = null) {
    $params = [
      'q' => $name,
      'type' => 'master',
      'per_page' => 1,
    ];
    if ($artistId) $params['artist_id'] = $artistId;

    $json = $this->apiGet('https://api.discogs.com/database/search', $params);
    return $json['results'][0]['id'] ?? null;
  }

  protected function searchTrackId($name) {
    $json = $this->apiGet('https://api.discogs.com/database/search', [
      'q' => $name,
      'type' => 'track',
      'per_page' => 1,
    ]);

    if (empty($json['results'][0])) return null;

    return [
      'master_id'  => $json['results'][0]['master_id'] ?? null,
      'release_id' => $json['results'][0]['id'] ?? null,
    ];
  }

  // ============================================================
  // FETCHERS
  // ============================================================
  protected function getArtistDetails($id) {
    return $id ? $this->apiGet("https://api.discogs.com/artists/$id") : null;
  }

  protected function getAlbumDetails($id) {
    return $id ? $this->apiGet("https://api.discogs.com/masters/$id") : null;
  }

  protected function getReleaseDetails($id) {
    return $id ? $this->apiGet("https://api.discogs.com/releases/$id") : null;
  }

  protected function getArtistFromAlbum($album) {
    return !empty($album['artists'][0]['id'])
      ? $this->getArtistDetails($album['artists'][0]['id'])
      : null;
  }

  protected function extractTrackFromAlbum($album, $trackName) {
    if (!$album || empty($album['tracklist'])) return null;

    foreach ($album['tracklist'] as $t) {
      if (strcasecmp($t['title'], $trackName) === 0) {
        return $t;
      }
    }
    return null;
  }

  // ============================================================
  // NORMALIZER (UNCHANGED CONTRACT)
  // ============================================================
  protected function normalizeDiscogsResult($artist, $album, $track) {

    return [
      'artist' => $artist ? [
        'name'        => $artist['name'] ?? '',
        'genres'      => $artist['genres'] ?? [],
        'discogs_id'  => $artist['id'] ?? '',
        'website'     => $artist['urls'][0] ?? '',
        'images'      => array_column($artist['images'] ?? [], 'uri'),
        'description' => $artist['profile'] ?? '',
      ] : [],

      'album' => $album ? [
        'title'        => $album['title'] ?? '',
        'artist_name'  => $album['artists'][0]['name'] ?? '',
        'discogs_id'   => $album['id'] ?? '',
        'release_date' => $album['year'] ?? '',
        'genres'       => $album['styles'] ?? $album['genres'] ?? [],
        'image'        => !empty($album['images'][0]['uri'])
          ? $album['images'][0]['uri']
          : null,
        'label'         => $album['label'] ?? '',
      ] : [],

      'track' => $track ? [
        'title'      => $track['title'] ?? '',
        'discogs_id' => $track['position'] ?? null,
        'genres'     => $album['genres'] ?? [],
      ] : [],
    ];
  }
}
