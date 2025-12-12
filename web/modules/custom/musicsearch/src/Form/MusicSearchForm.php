<?php

namespace Drupal\musicsearch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MusicSearchForm extends FormBase {

  public function getFormId() {
    return 'musicsearch_search_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'musicsearch/styles';

    $mode   = $form_state->get('mode') ?? 'search';
    $merged = $form_state->get('merged_results');

    // ============================================================
    // SEARCH MODE
    // ============================================================
    if ($mode === 'search') {

      $form['artist'] = [
        '#type' => 'textfield',
        '#title' => 'Artist',
      ];

      $form['album'] = [
        '#type' => 'textfield',
        '#title' => 'Album',
      ];

      $form['track'] = [
        '#type' => 'textfield',
        '#title' => 'Track',
      ];

      $form['actions']['search'] = [
        '#type' => 'submit',
        '#value' => 'Search',
        '#submit' => ['::submitSearch'],
      ];

      if ($merged) {

        // -------------------------------
        // ARTIST
        // -------------------------------
        if (!empty($merged['artist']['spotify']) || !empty($merged['artist']['discogs'])) {
          $form['artist_section'] = [
            '#type' => 'details',
            '#title' => 'Artist Metadata',
            '#open' => TRUE,
          ];
          $form['artist_section']['table'] = [
            '#markup' => $this->buildArtistTable(
              $merged['artist']['spotify'] ?? [],
              $merged['artist']['discogs'] ?? []
            ),
          ];
          $form['artist_section']['create_artist'] = [
            '#type' => 'submit',
            '#value' => 'Create Artist Node',
            '#submit' => ['::switchToArtistCreateMode'],
            '#limit_validation_errors' => [],
          ];
        }

        // -------------------------------
        // ALBUM
        // -------------------------------
        if (!empty($merged['album']['spotify']) || !empty($merged['album']['discogs'])) {
          $form['album_section'] = [
            '#type' => 'details',
            '#title' => 'Album Metadata',
            '#open' => TRUE,
          ];
          $form['album_section']['table'] = [
            '#markup' => $this->buildAlbumTable(
              $merged['album']['spotify'] ?? [],
              $merged['album']['discogs'] ?? []
            ),
          ];
          $form['album_section']['create_album'] = [
            '#type' => 'submit',
            '#value' => 'Create Album Node',
            '#submit' => ['::switchToAlbumCreateMode'],
            '#limit_validation_errors' => [],
          ];
        }

        // -------------------------------
        // TRACK
        // -------------------------------
        if (!empty($merged['track']['spotify']) || !empty($merged['track']['discogs'])) {
          $form['track_section'] = [
            '#type' => 'details',
            '#title' => 'Track Metadata',
            '#open' => TRUE,
          ];
          $form['track_section']['table'] = [
            '#markup' => $this->buildTrackTable(
              $merged['track']['spotify'] ?? [],
              $merged['track']['discogs'] ?? []
            ),
          ];
          $form['track_section']['create_track'] = [
            '#type' => 'submit',
            '#value' => 'Create Track Node',
            '#submit' => ['::switchToTrackCreateMode'],
            '#limit_validation_errors' => [],
          ];
        }
      }

      return $form;
    }

    // ============================================================
    // CREATE MODES (unchanged UX)
    // ============================================================

    if ($mode === 'create_artist') {
      $form['artist_metadata'] = [
        '#type' => 'details',
        '#title' => 'Artist Metadata',
        '#open' => TRUE,
      ];
      $form['artist_metadata']['table'] = [
        '#markup' => $this->buildArtistTable(
          $merged['artist']['spotify'] ?? [],
          $merged['artist']['discogs'] ?? []
        ),
      ];

      $form['artist_fields'] = [
        '#type' => 'fieldset',
        '#title' => 'Select fields to import',
      ];

      $this->addSourceRadios($form['artist_fields'], [
        'name' => 'Name',
        'genres' => 'Genres',
        'description' => 'Description',
        'website' => 'Website',
        'images' => 'Images',
      ], $merged['artist']);

      $form['actions']['save'] = [
        '#type' => 'submit',
        '#value' => 'Save Artist Node',
        '#submit' => ['::saveArtistNode'],
      ];

      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => 'Back to Results',
        '#submit' => ['::returnToSearch'],
        '#limit_validation_errors' => [],
      ];

      return $form;
    }

    if ($mode === 'create_album') {
      $form['album_metadata'] = [
        '#type' => 'details',
        '#title' => 'Album Metadata',
        '#open' => TRUE,
      ];
      $form['album_metadata']['table'] = [
        '#markup' => $this->buildAlbumTable(
          $merged['album']['spotify'] ?? [],
          $merged['album']['discogs'] ?? []
        ),
      ];

      $form['album_fields'] = [
        '#type' => 'fieldset',
        '#title' => 'Select fields to import',
      ];

      $this->addSourceRadios($form['album_fields'], [
        'title' => 'Title',
        'label' => 'Label',
        'description' => 'Description',
        'release_date' => 'Release Date',
        'genres' => 'Genres',
        'image' => 'Image',
      ], $merged['album']);

      $form['actions']['save'] = [
        '#type' => 'submit',
        '#value' => 'Save Album Node',
        '#submit' => ['::saveAlbumNode'],
      ];

      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => 'Back to Results',
        '#submit' => ['::returnToSearch'],
        '#limit_validation_errors' => [],
      ];

      return $form;
    }

    if ($mode === 'create_track') {
      $form['track_metadata'] = [
        '#type' => 'details',
        '#title' => 'Track Metadata',
        '#open' => TRUE,
      ];
      $form['track_metadata']['table'] = [
        '#markup' => $this->buildTrackTable(
          $merged['track']['spotify'] ?? [],
          $merged['track']['discogs'] ?? []
        ),
      ];

      $form['track_fields'] = [
        '#type' => 'fieldset',
        '#title' => 'Select fields to import',
      ];

      $this->addSourceRadios($form['track_fields'], [
        'title' => 'Title',
        'duration_ms' => 'Length',
        'genres' => 'Genres',
      ], $merged['track']);

      $form['actions']['save'] = [
        '#type' => 'submit',
        '#value' => 'Save Track Node',
        '#submit' => ['::saveTrackNode'],
      ];

      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => 'Back to Results',
        '#submit' => ['::returnToSearch'],
        '#limit_validation_errors' => [],
      ];

      return $form;
    }
  }

  // ============================================================
  // RADIO BUILDER
  // ============================================================

  protected function addSourceRadios(array &$parent, array $fields, array $data) {
    foreach ($fields as $key => $label) {
      $options = [];
      if (!empty($data['spotify'][$key])) $options['spotify'] = 'Spotify';
      if (!empty($data['discogs'][$key])) $options['discogs'] = 'Discogs';
      if (!$options) continue;

      $options['none'] = 'Do not import';

      $parent[$key] = [
        '#type' => 'radios',
        '#title' => $label,
        '#options' => $options,
        '#default_value' => isset($options['spotify']) ? 'spotify' : array_key_first($options),
      ];
    }
  }

  // ============================================================
  // SEARCH
  // ============================================================

  public function submitSearch(array &$form, FormStateInterface $form_state) {
    $artist = $form_state->getValue('artist');
    $album  = $form_state->getValue('album');
    $track  = $form_state->getValue('track');

    $spotify = \Drupal::service('musicsearch.spotify_lookup')->fullSearch($artist, $album, $track);
    $discogs = \Drupal::service('musicsearch.discogs_lookup')->fullSearch($artist, $album, $track);

    $form_state->set('merged_results', [
      'artist' => ['spotify'=>$spotify['artist']??[], 'discogs'=>$discogs['artist']??[]],
      'album'  => ['spotify'=>$spotify['album']??[],  'discogs'=>$discogs['album']??[]],
      'track'  => ['spotify'=>$spotify['track']??[],  'discogs'=>$discogs['track']??[]],
    ]);

    $form_state->setRebuild(TRUE);
  }

  // ============================================================
  // TABLE HELPERS (COMPARE VIEW)
  // ============================================================

  protected function displayValue($value) {
    if (is_array($value)) {
      return $value ? implode(', ', $value) : '—';
    }
    return !empty($value) ? $value : '—';
  }

  protected function buildCompareRow($label, $spotifyValue, $discogsValue) {

    $wrap = ($label === 'Description');

    $spotifyContent = $wrap
      ? "<div class=\"musicsearch-description\">{$this->displayValue($spotifyValue)}</div>"
      : $this->displayValue($spotifyValue);

    $discogsContent = $wrap
      ? "<div class=\"musicsearch-description\">{$this->displayValue($discogsValue)}</div>"
      : $this->displayValue($discogsValue);

    return "<tr>
    <td><strong>$label</strong></td>
    <td>$spotifyContent</td>
    <td>$discogsContent</td>
  </tr>";
  }

  protected function renderImages(array $images) {
    if (empty($images)) return '—';
    $html = '';
    foreach ($images as $url) {
      $html .= "<img src='$url' width='80' style='margin-right:6px;'>";
    }
    return $html;
  }

  protected function buildArtistTable(array $spotify, array $discogs) {
    $rows = '';
    $rows .= $this->buildCompareRow('Name', $spotify['name'] ?? '', $discogs['name'] ?? '');
    $rows .= $this->buildCompareRow('Genres', $spotify['genres'] ?? '', $discogs['genres'] ?? '');
    $rows .= $this->buildCompareRow('Spotify ID', $spotify['id'] ?? '', '');
    $rows .= $this->buildCompareRow('Discogs ID', '', $discogs['discogs_id'] ?? '');
    $rows .= $this->buildCompareRow('Website', $spotify['website'] ?? '', $discogs['website'] ?? '');
    $rows .= $this->buildCompareRow('Description', $spotify['description'] ?? '', $discogs['description'] ?? '');
    $rows .= "<tr>
      <td><strong>Images</strong></td>
      <td>{$this->renderImages($spotify['images'] ?? [])}</td>
      <td>{$this->renderImages($discogs['images'] ?? [])}</td>
    </tr>";

    return "<table class='musicsearch-compare'>
      <thead><tr><th>Field</th><th>Spotify</th><th>Discogs</th></tr></thead>
      <tbody>$rows</tbody>
    </table>";
  }

  protected function buildAlbumTable(array $spotify, array $discogs) {

    // Defensive normalization (prevents undefined key warnings)
    $spotify['image'] = $spotify['image'] ?? null;
    $discogs['image'] = $discogs['image'] ?? null;

    $rows = '';

    $rows .= $this->buildCompareRow(
      'Title',
      $spotify['title'] ?? null,
      $discogs['title'] ?? null
    );

    $rows .= $this->buildCompareRow(
      'Artist',
      $spotify['artist_name'] ?? null,
      $discogs['artist_name'] ?? null
    );

    $rows .= $this->buildCompareRow(
      'Spotify ID',
      $spotify['id'] ?? null,
      null
    );

    $rows .= $this->buildCompareRow(
      'Discogs ID',
      null,
      $discogs['discogs_id'] ?? null
    );

    $rows .= $this->buildCompareRow(
      'Release Date',
      $spotify['release_date'] ?? null,
      $discogs['release_date'] ?? null
    );

    $rows .= $this->buildCompareRow(
      'Genres',
      $spotify['genres'] ?? null,
      $discogs['genres'] ?? null
    );

    $rows .= $this->buildCompareRow(
      'Label',
      $spotify['label'] ?? null,
      $discogs['label'] ?? null
    );

    // Image row (safe for missing keys)
    $rows .= "<tr>
    <td><strong>Image</strong></td>
    <td>{$this->renderImages(
      !empty($spotify['image']) ? [$spotify['image']] : []
    )}</td>
    <td>{$this->renderImages(
      !empty($discogs['image']) ? [$discogs['image']] : []
    )}</td>
  </tr>";

    return "
    <table class='musicsearch-compare'>
      <thead>
        <tr>
          <th>Field</th>
          <th>Spotify</th>
          <th>Discogs</th>
        </tr>
      </thead>
      <tbody>
        $rows
      </tbody>
    </table>
  ";
  }


  protected function buildTrackTable(array $spotify, array $discogs) {
    $rows = '';
    $rows .= $this->buildCompareRow('Title', $spotify['title'] ?? '', $discogs['title'] ?? '');
    $rows .= $this->buildCompareRow('Spotify ID', $spotify['id'] ?? '', '');
    $rows .= $this->buildCompareRow('Discogs ID', '', $discogs['discogs_id'] ?? '');
    $rows .= $this->buildCompareRow('Genres', $spotify['genres'] ?? '', $discogs['genres'] ?? '');

    return "<table class='musicsearch-compare'>
      <thead><tr><th>Field</th><th>Spotify</th><th>Discogs</th></tr></thead>
      <tbody>$rows</tbody>
    </table>";
  }

  public function switchToArtistCreateMode(array &$form, FormStateInterface $form_state) {
    $form_state->set('mode', 'create_artist')->setRebuild(TRUE);
  }
  public function switchToAlbumCreateMode(array &$form, FormStateInterface $form_state) {
    $form_state->set('mode', 'create_album')->setRebuild(TRUE);
  }
  public function switchToTrackCreateMode(array &$form, FormStateInterface $form_state) {
    $form_state->set('mode', 'create_track')->setRebuild(TRUE);
  }
  public function returnToSearch(array &$form, FormStateInterface $form_state) {
    $form_state->set('mode', 'search')->setRebuild(TRUE);
  }

  public function saveArtistNode(array &$form, FormStateInterface $form_state) {
    $merged = $form_state->get('merged_results');

    if (empty($merged['artist'])) {
      \Drupal::messenger()->addError('No artist data available.');
      return;
    }

    $spotify = $merged['artist']['spotify'] ?? [];
    $discogs = $merged['artist']['discogs'] ?? [];

    $data = [];
    $fields = [];

    // Map form field keys to NodeCreatorService expected keys
    $fieldMap = [
      'name'        => 'name',
      'genres'      => 'genres',
      'description' => 'description',
      'website'     => 'website',
      'images'      => 'images',
    ];

    foreach ($fieldMap as $formKey => $dataKey) {
      $source = $form_state->getValue($formKey);

      if ($source === 'spotify' && !empty($spotify[$dataKey])) {
        $data[$dataKey] = $spotify[$dataKey];
        $fields[] = $dataKey;
      }
      elseif ($source === 'discogs' && !empty($discogs[$dataKey])) {
        $data[$dataKey] = $discogs[$dataKey];
        $fields[] = $dataKey;
      }
    }

    // Always pass IDs if available (no radios for these)
    if (!empty($spotify['id'])) {
      $data['id'] = $spotify['id'];
      $fields[] = 'spotify_id';
    }

    if (!empty($discogs['discogs_id'])) {
      $data['discogs_id'] = $discogs['discogs_id'];
      $fields[] = 'discogs_id';
    }

    if (empty($fields)) {
      \Drupal::messenger()->addError('No fields selected for import.');
      return;
    }

    /** @var \Drupal\musicsearch\NodeCreatorService $creator */
    $creator = \Drupal::service('musicsearch.node_creator');

    $node = $creator->createArtist($data, $fields);

    if ($node) {
      \Drupal::messenger()->addStatus('Artist node created successfully.');
      $form_state
        ->set('mode', 'search')
        ->setRebuild(TRUE);
    }
    else {
      \Drupal::messenger()->addError('Failed to create artist node.');
    }
  }


  public function saveAlbumNode(array &$form, FormStateInterface $form_state) {
    $merged = $form_state->get('merged_results');

    if (empty($merged['album'])) {
      \Drupal::messenger()->addError('No album data available.');
      return;
    }

    $spotify = $merged['album']['spotify'] ?? [];
    $discogs = $merged['album']['discogs'] ?? [];

    $data = [];
    $fields = [];

    // Map form field keys to NodeCreatorService expected keys
    $fieldMap = [
      'title'        => 'title',
      'label'        => 'label',
      'description'  => 'description',
      'release_date' => 'release_date',
      'genres'       => 'genres',
      'image'        => 'image',
    ];

    foreach ($fieldMap as $formKey => $dataKey) {
      $source = $form_state->getValue($formKey);

      if ($source === 'spotify' && !empty($spotify[$dataKey])) {
        $data[$dataKey] = $spotify[$dataKey];
        $fields[] = $dataKey;
      }
      elseif ($source === 'discogs' && !empty($discogs[$dataKey])) {
        $data[$dataKey] = $discogs[$dataKey];
        $fields[] = $dataKey;
      }
    }

    // Always pass IDs if available
    if (!empty($spotify['id'])) {
      $data['id'] = $spotify['id'];
      $fields[] = 'spotify_id';
    }

    if (!empty($discogs['discogs_id'])) {
      $data['discogs_id'] = $discogs['discogs_id'];
      $fields[] = 'discogs_id';
    }

    if (empty($fields)) {
      \Drupal::messenger()->addError('No fields selected for import.');
      return;
    }

    /** @var \Drupal\musicsearch\NodeCreatorService $creator */
    $creator = \Drupal::service('musicsearch.node_creator');

    $artistNode = $creator->getLastCreatedArtist();

    $node = $creator->createAlbum($data, $artistNode, $fields);

    if ($node) {
      \Drupal::messenger()->addStatus('Album node created successfully.');
      $form_state
        ->set('mode', 'search')
        ->setRebuild(TRUE);
    }
    else {
      \Drupal::messenger()->addError('Failed to create album node.');
    }
  }
  public function saveTrackNode(array &$form, FormStateInterface $form_state) {
    $merged = $form_state->get('merged_results');

    if (empty($merged['track'])) {
      \Drupal::messenger()->addError('No track data available.');
      return;
    }

    $spotify = $merged['track']['spotify'] ?? [];
    $discogs = $merged['track']['discogs'] ?? [];

    $data = [];
    $fields = [];

    // Map form field keys to NodeCreatorService expected keys
    $fieldMap = [
      'title'       => 'title',
      'duration_ms' => 'duration_ms',
      'genres'      => 'genres',
    ];

    foreach ($fieldMap as $formKey => $dataKey) {
      $source = $form_state->getValue($formKey);

      if ($source === 'spotify' && !empty($spotify[$dataKey])) {
        $data[$dataKey] = $spotify[$dataKey];
        $fields[] = $dataKey;
      }
      elseif ($source === 'discogs' && !empty($discogs[$dataKey])) {
        $data[$dataKey] = $discogs[$dataKey];
        $fields[] = $dataKey;
      }
    }

    // Always pass IDs if available
    if (!empty($spotify['id'])) {
      $data['id'] = $spotify['id'];
      $fields[] = 'spotify_id';
    }

    if (!empty($discogs['discogs_id'])) {
      $data['discogs_id'] = $discogs['discogs_id'];
      $fields[] = 'discogs_id';
    }

    if (empty($fields)) {
      \Drupal::messenger()->addError('No fields selected for import.');
      return;
    }

    /** @var \Drupal\musicsearch\NodeCreatorService $creator */
    $creator = \Drupal::service('musicsearch.node_creator');

    $albumNode = $creator->getLastCreatedAlbum();

    $node = $creator->createTrack($data, $albumNode, $fields);

    if ($node) {
      \Drupal::messenger()->addStatus('Track node created successfully.');
      $form_state
        ->set('mode', 'search')
        ->setRebuild(TRUE);
    }
    else {
      \Drupal::messenger()->addError('Failed to create track node.');
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
