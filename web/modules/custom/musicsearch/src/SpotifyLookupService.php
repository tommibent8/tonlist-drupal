<?php

namespace Drupal\musicsearch;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;

class SpotifyLookupService {

  protected $config;
  protected $httpClient;

  public function __construct(ConfigFactoryInterface $config_factory, Client $http_client) {
    $this->config = $config_factory->get('musicsearch.settings');
    $this->httpClient = $http_client;
  }

  protected function getToken() {
    $id = $this->config->get('spotify_client_id');
    $secret = $this->config->get('spotify_client_secret');

    if (!$id || !$secret) return null;

    $auth = base64_encode("$id:$secret");

    $res = $this->httpClient->post('https://accounts.spotify.com/api/token', [
      'headers' => [
        'Authorization' => "Basic $auth",
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
      'form_params' => [
        'grant_type' => 'client_credentials',
      ],
    ]);

    $body = json_decode($res->getBody(), TRUE);
    return $body['access_token'] ?? null;
  }


  /**
   * FULL SEARCH PIPELINE
   * Uses all helper methods depending on user input.
   */
  public function fullSearch($artistInput, $albumInput, $trackInput) {

    $artistInput = trim($artistInput);
    $albumInput  = trim($albumInput);
    $trackInput  = trim($trackInput);

    $result = [
      'artist' => [],
      'album'  => [],
      'track'  => [],
    ];

    /* ============================================================
       CASE 1 — TRACK ONLY
       ============================================================ */
    if ($trackInput && !$artistInput && !$albumInput) {

      $trackId = $this->searchTrackId($trackInput);
      $track   = $this->getTrackDetails($trackId);

      // Album details
      $albumId = $track['album']['id'] ?? null;
      $album   = $this->getAlbumDetails($albumId);

      // Artist details
      $artistId = $track['artists'][0]['id'] ?? null;
      $artist   = $this->getArtistDetails($artistId);

      return $this->normalizeSpotifyResult($artist, $album, $track);
    }


    /* ============================================================
       CASE 2 — ALBUM ONLY
       ============================================================ */
    if ($albumInput && !$artistInput && !$trackInput) {

      $albumId = $this->searchAlbumId($albumInput);
      $album   = $this->getAlbumDetails($albumId);

      $artistId = $album['artists'][0]['id'] ?? null;
      $artist   = $this->getArtistDetails($artistId);

      return $this->normalizeSpotifyResult($artist, $album, null);
    }


    /* ============================================================
       CASE 3 — ARTIST ONLY
       ============================================================ */
    if ($artistInput && !$albumInput && !$trackInput) {

      $artistId = $this->searchArtistId($artistInput);
      $artist   = $this->getArtistDetails($artistId);

      return $this->normalizeSpotifyResult($artist, null, null);
    }


    /* ============================================================
       CASE 4 — ARTIST + ALBUM
       ============================================================ */
    if ($artistInput && $albumInput && !$trackInput) {

      $artistId = $this->searchArtistId($artistInput);
      $artist   = $this->getArtistDetails($artistId);

      $albums = $this->getArtistAlbums($artistId);

      // exact title match
      $albumHit = null;
      foreach ($albums as $a) {
        if (strcasecmp($a['name'], $albumInput) === 0) {
          $albumHit = $this->getAlbumDetails($a['id']);
        }
      }

      return $this->normalizeSpotifyResult($artist, $albumHit, null);
    }


    /* ============================================================
       CASE 5 — ARTIST + TRACK
       ============================================================ */
    if ($artistInput && $trackInput && !$albumInput) {

      $artistId = $this->searchArtistId($artistInput);
      $artist   = $this->getArtistDetails($artistId);

      $albums = $this->getArtistAlbums($artistId);

      $trackHit = null;
      $albumHit = null;

      foreach ($albums as $a) {

        $albumDetails = $this->getAlbumDetails($a['id']);

        foreach ($albumDetails['tracks']['items'] as $t) {
          if (strcasecmp($t['name'], $trackInput) === 0) {

            // Full track details from ID
            $trackHit = $this->getTrackDetails($t['id']);
            $albumHit = $albumDetails;
          }
        }
      }

      return $this->normalizeSpotifyResult($artist, $albumHit, $trackHit);
    }

    /* ============================================================
   CASE 6 — ALBUM + TRACK (NO ARTIST)
   ============================================================ */
    if ($albumInput && $trackInput && !$artistInput) {

      // 1. Get Album Details
      $albumId = $this->searchAlbumId($albumInput);
      $album   = $this->getAlbumDetails($albumId);

      if (!$album) {
        return $this->normalizeSpotifyResult(null, null, null);
      }

      // 2. Loop through album tracks to find match
      $trackHit = null;
      foreach ($album['tracks']['items'] as $t) {
        if (strcasecmp($t['name'], $trackInput) === 0) {
          $trackHit = $this->getTrackDetails($t['id']);
          break;
        }
      }

      // 3. Get artist based on album metadata
      $artistId = $album['artists'][0]['id'] ?? null;
      $artist   = $this->getArtistDetails($artistId);

      return $this->normalizeSpotifyResult($artist, $album, $trackHit);
    }


    /* ============================================================
       CASE 7 — ARTIST + ALBUM + TRACK
       ============================================================ */
    if ($artistInput && $albumInput && $trackInput) {

      $artistId = $this->searchArtistId($artistInput);
      $artist   = $this->getArtistDetails($artistId);

      $albums = $this->getArtistAlbums($artistId);

      $albumHit = null;
      $trackHit = null;

      foreach ($albums as $a) {
        if (strcasecmp($a['name'], $albumInput) === 0) {

          $albumHit = $this->getAlbumDetails($a['id']);

          foreach ($albumHit['tracks']['items'] as $t) {
            if (strcasecmp($t['name'], $trackInput) === 0) {
              $trackHit = $this->getTrackDetails($t['id']);
            }
          }
        }
      }

      return $this->normalizeSpotifyResult($artist, $albumHit, $trackHit);
    }


    return $result;
  }

  /**
   * Convert raw Spotify data into the flat structure needed by the form.
   */
  protected function normalizeSpotifyResult($artist, $album, $track) {

    // Artist images
    $artistImages = [];
    if (!empty($artist['images'])) {
      foreach ($artist['images'] as $img) {
        $artistImages[] = $img['url'];
      }
    }

    return [
      'artist' => [
        'name'        => $artist['name'] ?? '',
        'genres'      => $artist['genres'] ?? [],
        'id'          => $artist['id'] ?? '',
        'birth'       => null,       // Spotify does NOT provide this
        'death'       => null,       // Spotify does NOT provide this
        'website'     => $artist['external_urls']['spotify'] ?? '',
        'images'      => $artistImages,
        'description' => '',         // Spotify does not give bios
      ],

      'album' => $album ? [
        'title'         => $album['name'] ?? '',
        'artist_name'   => $album['artists'][0]['name'] ?? '',
        'label'         => $album['label'] ?? '',
        'description'   => '',
        'release_date'  => $album['release_date'] ?? '',
        'image'         => $album['images'][0]['url'] ?? '',
        'genres'        => $album['genres'] ?? [],
      ] : [],

      'track' => $track ? [
        'title'       => $track['name'] ?? '',
        'id'          => $track['id'] ?? '',
        'duration_ms' => $track['duration_ms'] ?? '',
        'genres'      => $artist['genres'] ?? [],
      ] : [],
    ];
  }



  // ------------------------------
  // MAIN SEARCH
  // ------------------------------
  /**
   * Step 1 — Perform search and return first match ID.
   */
  protected function searchArtistId($name) {
    $token = $this->getToken();
    if (!$token || !$name) return null;

    $response = $this->httpClient->get('https://api.spotify.com/v1/search', [
      'headers' => [
        'Authorization' => "Bearer $token",
      ],
      'query' => [
        'q'     => $name,
        'type'  => 'artist',
        'limit' => 1,
        'market' => 'IS',
      ],
    ]);

    $json = json_decode($response->getBody(), TRUE);
    return $json['artists']['items'][0]['id'] ?? null;
  }

  protected function searchAlbumId($name) {
    $token = $this->getToken();
    if (!$token || !$name) return null;

    $response = $this->httpClient->get('https://api.spotify.com/v1/search', [
      'headers' => [
        'Authorization' => "Bearer $token",
      ],
      'query' => [
        'q'     => $name,
        'type'  => 'album',
        'limit' => 1,
        'market' => 'IS',
      ],
    ]);

    $json = json_decode($response->getBody(), TRUE);
    return $json['albums']['items'][0]['id'] ?? null;
  }

  protected function searchTrackId($name) {
    $token = $this->getToken();
    if (!$token || !$name) return null;

    $response = $this->httpClient->get('https://api.spotify.com/v1/search', [
      'headers' => [
        'Authorization' => "Bearer $token",
      ],
      'query' => [
        'q'     => $name,
        'type'  => 'track',
        'limit' => 1,
        'market' => 'IS',
      ],
    ]);

    $json = json_decode($response->getBody(), TRUE);
    return $json['tracks']['items'][0]['id'] ?? null;
  }

  /**
   * Step 2 — Get full artist details using ID.
   */
  public function getArtistDetails($artistId) {
    if (!$artistId) return null;

    $token = $this->getToken();
    if (!$token) return null;

    $response = $this->httpClient->get("https://api.spotify.com/v1/artists/$artistId", [
      'headers' => [
        'Authorization' => "Bearer $token",
      ],
      'query' => [
        'market' => 'IS',
      ],
    ]);

    return json_decode($response->getBody(), TRUE);
  }

  /**
   * Step 3 — Get all albums for an artist.
   */
  public function getArtistAlbums($artistId) {
    if (!$artistId) return [];

    $token = $this->getToken();
    if (!$token) return [];

    $response = $this->httpClient->get("https://api.spotify.com/v1/artists/$artistId/albums", [
      'headers' => [
        'Authorization' => "Bearer $token",
      ],
      'query' => [
        'include_groups' => 'album,single',
        'market' => 'IS',
        'limit' => 12,
      ],
    ]);

    $json = json_decode($response->getBody(), TRUE);
    return $json['items'] ?? [];
  }

  /**
   * Step 4 — Get full album details using album ID.
   */
  public function getAlbumDetails($albumId) {
    if (!$albumId) return null;

    $token = $this->getToken();
    if (!$token) return null;

    $response = $this->httpClient->get("https://api.spotify.com/v1/albums/$albumId", [
      'headers' => [
        'Authorization' => "Bearer $token",
      ],
      'query' => [
        'market' => 'IS',
      ],
    ]);

    return json_decode($response->getBody(), TRUE);
  }

  /**
   * Step 5 — Get full track details using track ID.
   */
  public function getTrackDetails($trackId) {
    if (!$trackId) return null;

    $token = $this->getToken();
    if (!$token) return null;

    $response = $this->httpClient->get("https://api.spotify.com/v1/tracks/$trackId", [
      'headers' => [
        'Authorization' => "Bearer $token",
      ],
      'query' => [
        'market' => 'IS',
      ],
    ]);

    return json_decode($response->getBody(), TRUE);
  }

}
