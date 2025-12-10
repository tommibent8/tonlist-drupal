<?php

namespace Drupal\musicsearch\Controller;

use Drupal\Core\Controller\ControllerBase;

class MusicSearchController extends ControllerBase {

  public function searchPage() {

    // Call Spotify service manually for testing:
    $spotify = \Drupal::service('musicsearch.spotify_lookup');
    $results = $spotify->search('nirvana');

    return [
      '#type' => 'preformatted',
      '#markup' => print_r($results, TRUE),
    ];
  }

}
