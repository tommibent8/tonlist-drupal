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

  /**
   * Search Discogs using Personal Access Token.
   */
  public function search($query, $type = 'artist') {
    $token = $this->config->get('discogs_token');

    if (!$token) {
      return ['error' => 'No Discogs token configured.'];
    }

    // Map our types to Discogs.
    $discogsType = match ($type) {
      'artist' => 'artist',
      'album'  => 'release',
      'track'  => 'release',
      default  => 'artist',
    };

    try {
      $response = $this->httpClient->get('https://api.discogs.com/database/search', [
        'headers' => [
          'Authorization' => 'Discogs token=' . $token,
          'User-Agent' => 'musicsearch/1.0',
        ],
        'query' => [
          'q' => $query,      // No more artist: prefix
          'type' => $discogsType,
          'per_page' => 5,
        ],
      ]);

      return json_decode($response->getBody(), TRUE);

    } catch (\Exception $e) {
      return [
        'error' => 'Discogs API request failed',
        'details' => $e->getMessage(),
      ];
    }
  }
}
