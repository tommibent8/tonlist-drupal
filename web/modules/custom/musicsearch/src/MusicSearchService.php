<?php

namespace Drupal\musicsearch;

class MusicSearchService {

  protected $spotify;
  protected $discogs;

  public function __construct($spotify, $discogs) {
    $this->spotify = $spotify;
    $this->discogs = $discogs;
  }

  public function search($query) {
    return [
      'spotify' => $this->spotify->search($query),
      'discogs' => $this->discogs->search($query),
    ];
  }
}
