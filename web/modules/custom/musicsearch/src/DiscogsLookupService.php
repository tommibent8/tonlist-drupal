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


  protected function normalize($raw) {

    $item = $raw['results'][0] ?? null;

    return [
      'artist' => [
        'name'        => ['discogs' => $item['title'] ?? null],
        'id'          => ['discogs' => $item['id'] ?? null],
        'image'       => ['discogs' => $item['cover_image'] ?? null],
        'genres'      => ['discogs' => $item['genre'] ?? []],
        'description' => ['discogs' => $item['notes'] ?? null],
        'popularity'  => ['discogs' => null],
        'website'     => ['discogs' => $item['resource_url'] ?? null],
        'birth'       => ['discogs' => $item['year'] ?? null],
        'death'       => ['discogs' => null],
        'members'     => ['discogs' => []], // Would require another endpoint
      ],

      'album' => [
        'title'       => ['discogs' => $item['title'] ?? null],
        'id'          => ['discogs' => $item['id'] ?? null],
        'image'       => ['discogs' => $item['cover_image'] ?? null],
        'genres'      => ['discogs' => $item['genre'] ?? []],
        'year'        => ['discogs' => $item['year'] ?? null],
        'label'       => ['discogs' => $item['label'][0] ?? null],
        'description' => ['discogs' => $item['notes'] ?? null],
        'track_total' => ['discogs' => null],
      ],

      'track' => [
        'name'        => ['discogs' => null],
        'id'          => ['discogs' => null],
        'duration'    => ['discogs' => null],
        'preview_url' => ['discogs' => null],
        'genres'      => ['discogs' => []],
      ],
    ];
  }


  public function search($query) {

    $token = $this->config->get('discogs_token');

    $res = $this->httpClient->get('https://api.discogs.com/database/search', [
      'headers' => [
        'Authorization' => 'Discogs token=' . $token,
        'User-Agent'    => 'musicsearch/1.0',
      ],
      'query' => [
        'q' => $query,
        'per_page' => 5,
      ],
    ]);

    return $this->normalize(json_decode($res->getBody(), TRUE));
  }
}
