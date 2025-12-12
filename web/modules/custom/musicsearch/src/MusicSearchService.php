<?php

namespace Drupal\musicsearch;

class MusicSearchService {

  protected $spotify;
  protected $discogs;

  public function __construct($spotify, $discogs) {
    $this->spotify = $spotify;
    $this->discogs = $discogs;
  }

  public function search(array $criteria): array {
    return [
      'spotify' => $this->spotify->search($criteria),
      'discogs' => $this->discogs->search($criteria),
    ];
  }
}
