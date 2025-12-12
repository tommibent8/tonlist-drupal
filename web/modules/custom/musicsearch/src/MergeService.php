<?php

namespace Drupal\musicsearch;

/**
 * Service to merge music metadata results from different lookup providers.
 *
 * This service takes raw data from Spotify and Discogs lookup services
 * and merges them into a single structure where each field's value
 * is wrapped in an array keyed by the source ('spotify' or 'discogs').
 *
 * This structure is required by MusicSearchForm to allow per-field source selection.
 */
class MusicSearchMergeService {

  /**
   * Helper to ensure a value is wrapped in an array if it's not already.
   * This is used for fields like 'genres' which might be arrays.
   *
   * @param mixed $value
   * The value to check.
   * @return array
   * The value, or an empty array if null.
   */
  protected function ensureArray($value): array {
    return is_array($value) ? $value : [];
  }

  /**
   * Merges raw search results from Spotify and Discogs into a consistent,
   * source-keyed structure.
   *
   * @param array $spotify
   * The raw results from the Spotify lookup service.
   * @param array $discogs
   * The raw results from the Discogs lookup service.
   *
   * @return array
   * The merged result array, structured as:
   * ['entity_type' => ['field_name' => ['spotify' => value, 'discogs' => value]]]
   */
  public function merge(array $spotify, array $discogs): array {

    // Helper function to safely get a value for a source.
    $safeGet = function (array $source, string $entity, string $field) {
      return $source[$entity][$field] ?? null;
    };

    // --- ARTIST MERGE ---
    $mergedArtist = [];
    $artistFields = [
      'name', 'id', 'images', 'birth', 'death', 'website', 'description', 'popularity', 'members',
    ];

    foreach ($artistFields as $field) {
      // Note: 'id' is mapped to 'spotify_id' in the form, but we'll use 'id' here for simplicity.
      // The form's save handlers will need to adjust for this.
      $spotifyValue = $safeGet($spotify, 'artist', $field);
      $discogsValue = $safeGet($discogs, 'artist', $field);

      // Handle the 'images' field (renamed from 'image' in your original code)
      if ($field === 'images') {
        $mergedArtist[$field] = [
          'spotify' => $this->ensureArray($spotifyValue),
          'discogs' => $this->ensureArray($discogsValue),
        ];
      }
      // Handle 'genres' (which are generally lists/arrays)
      elseif ($field === 'genres') {
        $mergedArtist[$field] = [
          'spotify' => $this->ensureArray($spotifyValue),
          'discogs' => $this->ensureArray($discogsValue),
        ];
      }
      // Handle single-value fields
      else {
        $mergedArtist[$field] = [
          'spotify' => $spotifyValue,
          'discogs' => $discogsValue,
        ];
      }
    }

    // Add genres (which you handled separately)
    $mergedArtist['genres'] = [
      'spotify' => $this->ensureArray($safeGet($spotify, 'artist', 'genres')),
      'discogs' => $this->ensureArray($safeGet($discogs, 'artist', 'genres')),
    ];


    // --- ALBUM MERGE ---
    $mergedAlbum = [];
    $albumFields = [
      'title', 'id', 'image', 'year', 'label', 'description', 'track_total', 'artist_name'
    ];

    foreach ($albumFields as $field) {
      $spotifyValue = $safeGet($spotify, 'album', $field);
      $discogsValue = $safeGet($discogs, 'album', $field);

      if ($field === 'genres') {
        $mergedAlbum[$field] = [
          'spotify' => $this->ensureArray($spotifyValue),
          'discogs' => $this->ensureArray($discogsValue),
        ];
      } else {
        $mergedAlbum[$field] = [
          'spotify' => $spotifyValue,
          'discogs' => $discogsValue,
        ];
      }
    }

    // Add genres
    $mergedAlbum['genres'] = [
      'spotify' => $this->ensureArray($safeGet($spotify, 'album', 'genres')),
      'discogs' => $this->ensureArray($safeGet($discogs, 'album', 'genres')),
    ];


    // --- TRACK MERGE ---
    // Tracks often have less complementary data. We will structure them the same
    // way to allow source selection, even if one source is often null.
    $mergedTrack = [];
    $trackFields = [
      'title', 'id', 'duration_ms', 'preview_url',
    ];

    foreach ($trackFields as $field) {
      $spotifyValue = $safeGet($spotify, 'track', $field);
      $discogsValue = $safeGet($discogs, 'track', $field);

      if ($field === 'genres') {
        $mergedTrack[$field] = [
          'spotify' => $this->ensureArray($spotifyValue),
          'discogs' => $this->ensureArray($discogsValue),
        ];
      } else {
        $mergedTrack[$field] = [
          'spotify' => $spotifyValue,
          'discogs' => $discogsValue,
        ];
      }
    }

    // Add genres
    $mergedTrack['genres'] = [
      'spotify' => $this->ensureArray($safeGet($spotify, 'track', 'genres')),
      'discogs' => $this->ensureArray($safeGet($discogs, 'track', 'genres')),
    ];


    return [
      'artist' => $mergedArtist,
      'album' => $mergedAlbum,
      'track' => $mergedTrack,
    ];
  }
}
