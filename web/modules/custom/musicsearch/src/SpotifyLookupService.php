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

  /**
   * Get OAuth token from Spotify
   */
  protected function getToken() {
    $clientId = $this->config->get('spotify_client_id');
    $clientSecret = $this->config->get('spotify_client_secret');

    if (!$clientId || !$clientSecret) {
      return NULL;
    }

    $auth = base64_encode("$clientId:$clientSecret");

    try {
      $response = $this->httpClient->post('https://accounts.spotify.com/api/token', [
        'headers' => [
          'Authorization' => "Basic $auth",
          'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'form_params' => [
          'grant_type' => 'client_credentials',
        ],
      ]);
    } catch (\Exception $e) {
      // RETURN the actual error from Spotify
      return ['error' => 'Spotify error', 'details' => $e->getMessage()];
    }

    $data = json_decode($response->getBody(), TRUE);
    return $data['access_token'] ?? NULL;
  }

  /**
   * Perform search
   */
  public function search($query) {

    $token = $this->getToken();

    if (!$token) {
      return ['error' => 'Spotify token could not be retrieved.'];
    }

    $response = $this->httpClient->get('https://api.spotify.com/v1/search', [
      'headers' => [
        'Authorization' => "Bearer $token",
      ],
      'query' => [
        'q' => $query,
        'type' => 'artist,album,track',
        'limit' => 5,
      ],
    ]);

    return json_decode($response->getBody(), TRUE);
  }
}
