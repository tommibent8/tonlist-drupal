<?php

namespace Drupal\musicsearch;

class MergeService {

  protected function safe($value) {
    return is_array($value) ? $value : [];
  }

  public function merge($spotify, $discogs) {

    return [
      'artist' => [
        'name'        => array_merge($this->safe($spotify['artist']['name']), $this->safe($discogs['artist']['name'])),
        'id'          => array_merge($this->safe($spotify['artist']['id']), $this->safe($discogs['artist']['id'])),
        'image'       => array_merge($this->safe($spotify['artist']['image']), $this->safe($discogs['artist']['image'])),
        'genres'      => [
          'spotify' => $spotify['artist']['genres']['spotify'] ?? [],
          'discogs' => $discogs['artist']['genres']['discogs'] ?? [],
        ],
        'description' => [
          'spotify' => null,
          'discogs' => $discogs['artist']['description']['discogs'] ?? null,
        ],
        'popularity'  => $spotify['artist']['popularity']['spotify'] ?? null,
        'website'     => [
          'spotify' => $spotify['artist']['website']['spotify'] ?? null,
          'discogs' => $discogs['artist']['website']['discogs'] ?? null,
        ],
        'birth'       => $discogs['artist']['birth']['discogs'] ?? null,
        'death'       => $discogs['artist']['death']['discogs'] ?? null,
        'members'     => $discogs['artist']['members']['discogs'] ?? [],
      ],

      'album' => [
        'title'       => array_merge($this->safe($spotify['album']['title']), $this->safe($discogs['album']['title'])),
        'id'          => array_merge($this->safe($spotify['album']['id']), $this->safe($discogs['album']['id'])),
        'image'       => array_merge($this->safe($spotify['album']['image']), $this->safe($discogs['album']['image'])),
        'genres'      => [
          'spotify' => $spotify['album']['genres']['spotify'] ?? [],
          'discogs' => $discogs['album']['genres']['discogs'] ?? [],
        ],
        'year'        => array_merge($this->safe($spotify['album']['year']), $this->safe($discogs['album']['year'])),
        'label'       => $discogs['album']['label']['discogs'] ?? null,
        'description' => $discogs['album']['description']['discogs'] ?? null,
        'track_total' => $spotify['album']['track_total']['spotify'] ?? null,
      ],

      'track' => [
        'name'        => $spotify['track']['name'],
        'id'          => $spotify['track']['id'],
        'duration'    => $spotify['track']['duration'],
        'preview_url' => $spotify['track']['preview_url'],
        'genres'      => $spotify['track']['genres'],
      ],
    ];
  }
}
