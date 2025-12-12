<?php

namespace Drupal\musicsearch;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;

class DiscogsLookupService {

  protected $config;
  protected $httpClient;

  protected function normalize(array $raw): array {
    $artistItem = null;
    $albumItem  = null;

    foreach (($raw['results'] ?? []) as $r) {
      if (!$artistItem && ($r['type'] ?? '') === 'artist') {
        $artistItem = $r;
      }
      if (!$albumItem && in_array(($r['type'] ?? ''), ['master','release'], true)) {
        $albumItem = $r;
      }
      if ($artistItem && $albumItem) break;
    }

    return [
      'artist' => [
        'name'   => ['discogs' => $artistItem['title'] ?? null],
        'id'     => ['discogs' => $artistItem['id'] ?? null],
        'image'  => ['discogs' => $artistItem['thumb'] ?? ($artistItem['cover_image'] ?? null)],
        'genres' => ['discogs' => $artistItem['genre'] ?? []],
        'website'=> ['discogs' => $artistItem['resource_url'] ?? null],
        'description' => ['discogs' => null],
        'birth'  => ['discogs' => null],
        'death'  => ['discogs' => null],
        'members'=> ['discogs' => []],
        'popularity' => ['discogs' => null],
      ],
      'album' => [
        'title' => ['discogs' => $albumItem['title'] ?? null],
        'id'    => ['discogs' => $albumItem['id'] ?? null],
        'image' => ['discogs' => $albumItem['thumb'] ?? ($albumItem['cover_image'] ?? null)],
        'genres'=> ['discogs' => $albumItem['genre'] ?? []],
        'year'  => ['discogs' => $albumItem['year'] ?? null],
        'label' => ['discogs' => $albumItem['label'][0] ?? null],
        'description' => ['discogs' => null],
        'track_total' => ['discogs' => null],
      ],
      'track' => [
        'name' => ['discogs' => null],
        'id'   => ['discogs' => null],
        'duration' => ['discogs' => null],
        'preview_url' => ['discogs' => null],
        'genres' => ['discogs' => []],
      ],
    ];
  }


  public function search(array $criteria): array {

    $parts = [];

    if (!empty($criteria['artist'])) {
      $parts[] = $criteria['artist'];
    }
    if (!empty($criteria['album'])) {
      $parts[] = $criteria['album'];
    }
    if (!empty($criteria['track'])) {
      $parts[] = $criteria['track'];
    }

    $query = trim(implode(' ', $parts));

    if ($query === '') {
      return $this->normalize(['results' => []]);
    }

    $token = $this->config->get('discogs_token');

    $res = $this->httpClient->get('https://api.discogs.com/database/search', [
      'headers' => [
        'User-Agent' => 'musicsearch/1.0',
      ],
      'query' => [
        'q' => $query,
        'token' => $token,
        'per_page' => 5,
      ],
    ]);

    $raw = json_decode($res->getBody()->getContents(), TRUE);

    return $this->normalize($raw);
  }

}
