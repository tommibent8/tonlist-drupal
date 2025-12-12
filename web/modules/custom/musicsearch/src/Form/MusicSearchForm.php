<?php

namespace Drupal\musicsearch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class MusicSearchForm extends FormBase {

  public function getFormId() {
    return 'musicsearch_search_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $mode = $form_state->get('mode') ?? 'search';
    $merged = $form_state->get('merged_results');

    // ============================================================
    // SEARCH MODE (default)
    // ============================================================
    if ($mode === 'search') {

      // Input fields
      $form['artist'] = [
        '#type' => 'textfield',
        '#title' => 'Artist',
        '#default_value' => $form_state->getValue('artist', ''),
      ];

      $form['album'] = [
        '#type' => 'textfield',
        '#title' => 'Album',
        '#default_value' => $form_state->getValue('album', ''),
      ];

      $form['track'] = [
        '#type' => 'textfield',
        '#title' => 'Track',
        '#default_value' => $form_state->getValue('track', ''),
      ];

      // SEARCH BUTTON
      $form['actions']['search'] = [
        '#type' => 'submit',
        '#value' => 'Search',
        '#submit' => ['::submitSearch'],
      ];

      // If there are results, show metadata + creation buttons
      if (!empty($merged)) {

        // -------------------------------
        // ARTIST SECTION
        // -------------------------------
        if (!empty($merged['artist'])) {
          $form['artist_section'] = [
            '#type' => 'details',
            '#title' => 'Artist Metadata',
            '#open' => TRUE,
          ];
          $form['artist_section']['table'] = [
            '#markup' => $this->buildArtistTable($merged['artist']),
          ];

          $form['artist_section']['create_artist'] = [
            '#type' => 'submit',
            '#value' => 'Create Artist Node',
            '#submit' => ['::switchToArtistCreateMode'],
            '#limit_validation_errors' => [],
          ];
        }

        // -------------------------------
        // ALBUM SECTION
        // -------------------------------
        if (!empty($merged['album'])) {
          $form['album_section'] = [
            '#type' => 'details',
            '#title' => 'Album Metadata',
            '#open' => TRUE,
          ];
          $form['album_section']['table'] = [
            '#markup' => $this->buildAlbumTable($merged['album']),
          ];

          $form['album_section']['create_album'] = [
            '#type' => 'submit',
            '#value' => 'Create Album Node',
            '#submit' => ['::switchToAlbumCreateMode'],
            '#limit_validation_errors' => [],
          ];
        }

        // -------------------------------
        // TRACK SECTION
        // -------------------------------
        if (!empty($merged['track'])) {
          $form['track_section'] = [
            '#type' => 'details',
            '#title' => 'Track Metadata',
            '#open' => TRUE,
          ];
          $form['track_section']['table'] = [
            '#markup' => $this->buildTrackTable($merged['track']),
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
    // CREATE ARTIST MODE
    // ============================================================
    if ($mode === 'create_artist') {

      $artist = $merged['artist'] ?? [];

      $form['artist_metadata'] = [
        '#type' => 'details',
        '#title' => 'Artist Metadata',
        '#open' => TRUE,
      ];
      $form['artist_metadata']['table'] = [
        '#markup' => $this->buildArtistTable($artist),
      ];

      // Per-field checkbox + source selection.
      $form['artist_fields'] = [
        '#type' => 'details',
        '#title' => $this->t('Select fields to import'),
        '#open' => TRUE,
      ];

      $artistFieldOptions = [
        'name' => $this->t('Name'),
        'genres' => $this->t('Genres'),
        // Still named spotify_id for UI/backwards compatibility,
        // but in save handler we map it to $finalData['id'].
        'spotify_id' => $this->t('Spotify ID'),
        'birth' => $this->t('Birthdate'),
        'death' => $this->t('Deathdate'),
        'website' => $this->t('Website'),
        'description' => $this->t('Description'),
        'images' => $this->t('Images'),
      ];

      foreach ($artistFieldOptions as $key => $label) {
        $form['artist_fields'][$key] = [
          '#type' => 'checkbox',
          '#title' => $label,
          '#default_value' => $form_state->getValue(['artist_fields', $key], FALSE),
        ];

        $form['artist_fields'][$key . '_source'] = [
          '#type' => 'select',
          '#title' => $this->t('Source'),
          '#options' => [
            'spotify' => $this->t('Spotify'),
            'discogs' => $this->t('Discogs'),
          ],
          '#default_value' => $form_state->getValue(['artist_fields', $key . '_source'], 'spotify'),
          '#states' => [
            'visible' => [
              ':input[name="artist_fields[' . $key . ']"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }

      // SAVE BUTTON
      $form['actions']['save'] = [
        '#type' => 'submit',
        '#value' => 'Save Artist Node',
        '#submit' => ['::saveArtistNode'],
      ];

      // BACK BUTTON
      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => 'Back to Results',
        '#submit' => ['::returnToSearch'],
        '#limit_validation_errors' => [],
      ];

      return $form;
    }

    // ============================================================
    // CREATE ALBUM MODE
    // ============================================================
    if ($mode === 'create_album') {

      $album = $merged['album'] ?? [];

      $form['album_metadata'] = [
        '#type' => 'details',
        '#title' => 'Album Metadata',
        '#open' => TRUE,
      ];
      $form['album_metadata']['table'] = [
        '#markup' => $this->buildAlbumTable($album),
      ];

      // Per-field checkbox + source selection.
      $form['album_fields'] = [
        '#type' => 'details',
        '#title' => $this->t('Select fields to import'),
        '#open' => TRUE,
      ];

      $albumFieldOptions = [
        'title' => $this->t('Title'),
        'label' => $this->t('Label'),
        'description' => $this->t('Description'),
        'release_date' => $this->t('Release Date'),
        'genres' => $this->t('Genres'),
        'image' => $this->t('Image'),
      ];

      foreach ($albumFieldOptions as $key => $label) {
        $form['album_fields'][$key] = [
          '#type' => 'checkbox',
          '#title' => $label,
          '#default_value' => $form_state->getValue(['album_fields', $key], FALSE),
        ];

        $form['album_fields'][$key . '_source'] = [
          '#type' => 'select',
          '#title' => $this->t('Source'),
          '#options' => [
            'spotify' => $this->t('Spotify'),
            'discogs' => $this->t('Discogs'),
          ],
          '#default_value' => $form_state->getValue(['album_fields', $key . '_source'], 'spotify'),
          '#states' => [
            'visible' => [
              ':input[name="album_fields[' . $key . ']"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }

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

    // ============================================================
    // CREATE TRACK MODE
    // ============================================================
    if ($mode === 'create_track') {

      $track = $merged['track'] ?? [];

      $form['track_metadata'] = [
        '#type' => 'details',
        '#title' => 'Track Metadata',
        '#open' => TRUE,
      ];
      $form['track_metadata']['table'] = [
        '#markup' => $this->buildTrackTable($track),
      ];

      // Per-field checkbox + source selection.
      $form['track_fields'] = [
        '#type' => 'details',
        '#title' => $this->t('Select fields to import'),
        '#open' => TRUE,
      ];

      $trackFieldOptions = [
        'title' => $this->t('Title'),
        // UI label kept as Spotify ID, but source selector allows Discogs too if you store it.
        'spotify_id' => $this->t('Spotify ID'),
        'duration_ms' => $this->t('Length'),
        'genres' => $this->t('Genres'),
      ];

      foreach ($trackFieldOptions as $key => $label) {
        $form['track_fields'][$key] = [
          '#type' => 'checkbox',
          '#title' => $label,
          '#default_value' => $form_state->getValue(['track_fields', $key], FALSE),
        ];

        $form['track_fields'][$key . '_source'] = [
          '#type' => 'select',
          '#title' => $this->t('Source'),
          '#options' => [
            'spotify' => $this->t('Spotify'),
            'discogs' => $this->t('Discogs'),
          ],
          '#default_value' => $form_state->getValue(['track_fields', $key . '_source'], 'spotify'),
          '#states' => [
            'visible' => [
              ':input[name="track_fields[' . $key . ']"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }

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

    // Fallback
    return $form;
  }

  // ============================================================
  // MODE SWITCH HANDLERS
  // ============================================================
  public function switchToArtistCreateMode(array &$form, FormStateInterface $form_state) {
    $form_state->set('mode', 'create_artist');
    $form_state->setRebuild(TRUE);
  }

  public function switchToAlbumCreateMode(array &$form, FormStateInterface $form_state) {
    $form_state->set('mode', 'create_album');
    $form_state->setRebuild(TRUE);
  }

  public function switchToTrackCreateMode(array &$form, FormStateInterface $form_state) {
    $form_state->set('mode', 'create_track');
    $form_state->setRebuild(TRUE);
  }

  public function returnToSearch(array &$form, FormStateInterface $form_state) {
    $form_state->set('mode', 'search');
    $form_state->setRebuild(TRUE);
  }

  // ============================================================
  // SUBMIT: SEARCH ONLY
  // ============================================================
  /**
   * Submits the form for searching and merges results from multiple sources.
   */
  public function submitSearch(array &$form, FormStateInterface $form_state) {

    $artist = $form_state->getValue('artist');
    $album  = $form_state->getValue('album');
    $track  = $form_state->getValue('track');

    // To allow per-field selection between Spotify and Discogs,
    // we must call a service that fetches data from both and merges
    // the results into the expected format:
    // ['field_name' => ['spotify' => '...', 'discogs' => '...']]
    // We assume 'musicsearch.music_search' service is responsible for this.

    // NOTE: Switched to standard \Drupal::service('id') notation.
    $merged_results = \Drupal::service('musicsearch.music_search')
      ->fullSearchAndMerge($artist, $album, $track);

    $form_state->set('merged_results', $merged_results);
    $form_state->set('mode', 'search');
    $form_state->setRebuild(TRUE);
  }

  // ============================================================
  // SAVE NODE HANDLERS
  // ============================================================
  public function saveArtistNode(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('artist_fields') ?? [];
    $merged = $form_state->get('merged_results');
    $artist = $merged['artist'] ?? [];

    $fieldKeys = ['name','genres','spotify_id','birth','death','website','description','images'];

    $selectedFields = [];
    $finalData = [];

    foreach ($fieldKeys as $field) {
      if (empty($values[$field])) {
        continue;
      }
      $selectedFields[] = $field;
      // Get the selected source (spotify or discogs).
      $source = $values[$field . '_source'] ?? 'spotify';

      // Expect $artist[$field] = ['spotify' => ..., 'discogs' => ...]
      $finalData[$field] = $artist[$field][$source] ?? NULL;
    }

    // NodeCreatorService expects 'id' for spotify_id.
    if (in_array('spotify_id', $selectedFields, TRUE)) {
      $finalData['id'] = $finalData['spotify_id'] ?? '';
    }

    if (in_array('genres', $selectedFields, TRUE) && !is_array($finalData['genres'] ?? NULL)) {
      $finalData['genres'] = [];
    }

    if (in_array('images', $selectedFields, TRUE)) {
      $imgs = $finalData['images'] ?? [];
      // Handle cases where the data source might return a single string URL
      if (is_string($imgs) && $imgs !== '') {
        $imgs = [$imgs];
      }
      if (!is_array($imgs)) {
        $imgs = [];
      }
      $finalData['images'] = $imgs;
    }

    $node = \Drupal::service('musicsearch.node_creator')
      ->createArtist($finalData, $selectedFields);

    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }

  public function saveAlbumNode(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('album_fields') ?? [];
    $merged = $form_state->get('merged_results');
    $album = $merged['album'] ?? [];

    $fieldKeys = ['title','label','description','release_date','genres','image'];

    $selectedFields = [];
    $finalData = [];

    foreach ($fieldKeys as $field) {
      if (empty($values[$field])) {
        continue;
      }
      $selectedFields[] = $field;
      $source = $values[$field . '_source'] ?? 'spotify';

      $finalData[$field] = $album[$field][$source] ?? NULL;
    }

    if (in_array('genres', $selectedFields, TRUE) && !is_array($finalData['genres'] ?? NULL)) {
      $finalData['genres'] = [];
    }

    // Get the last created artist node, if applicable for linking.
    $artistNode = \Drupal::service('musicsearch.node_creator')->getLastCreatedArtist();

    $node = \Drupal::service('musicsearch.node_creator')
      ->createAlbum($finalData, $artistNode, $selectedFields);

    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }

  public function saveTrackNode(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('track_fields') ?? [];
    $merged = $form_state->get('merged_results');
    $track = $merged['track'] ?? [];

    $fieldKeys = ['title','spotify_id','duration_ms','genres'];

    $selectedFields = [];
    $finalData = [];

    foreach ($fieldKeys as $field) {
      if (empty($values[$field])) {
        continue;
      }
      $selectedFields[] = $field;
      $source = $values[$field . '_source'] ?? 'spotify';

      $finalData[$field] = $track[$field][$source] ?? NULL;
    }

    // NodeCreatorService expects 'id' for spotify_id.
    if (in_array('spotify_id', $selectedFields, TRUE)) {
      $finalData['id'] = $finalData['spotify_id'] ?? '';
    }

    if (in_array('genres', $selectedFields, TRUE) && !is_array($finalData['genres'] ?? NULL)) {
      $finalData['genres'] = [];
    }

    // Get the last created album node, if applicable for linking.
    $albumNode = \Drupal::service('musicsearch.node_creator')->getLastCreatedAlbum();

    $node = \Drupal::service('musicsearch.node_creator')
      ->createTrack($finalData, $albumNode, $selectedFields);

    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }

  // ============================================================
  // TABLE + UTILS
  // ============================================================
  protected function msToMinSec($ms) {
    if (!$ms) return '';
    $sec = floor($ms / 1000);
    return sprintf("%d:%02d", floor($sec / 60), $sec % 60);
  }

  protected function buildRow($label, $value) {
    return "<tr><td><strong>$label</strong></td><td>$value</td></tr>";
  }

  /**
   * Helper: if a field is ['spotify'=>..., 'discogs'=>...], display spotify if present.
   * This is used for displaying *previews* in search mode.
   */
  protected function displayMergedValue($field) {
    if (is_array($field)) {
      if (array_key_exists('spotify', $field) && $field['spotify'] !== NULL && $field['spotify'] !== '') {
        return $field['spotify'];
      }
      if (array_key_exists('discogs', $field) && $field['discogs'] !== NULL && $field['discogs'] !== '') {
        return $field['discogs'];
      }
      // Fallback for nested structures or simple arrays (like genres).
      return reset($field);
    }
    return $field;
  }

  protected function buildArtistTable($a) {
    $rows = "";

    $name = $this->displayMergedValue($a['name'] ?? '');
    $genres = $this->displayMergedValue($a['genres'] ?? []);
    // Note: display id from either source
    $id = $this->displayMergedValue($a['id'] ?? $a['spotify_id'] ?? '');
    $birth = $this->displayMergedValue($a['birth'] ?? '');
    $death = $this->displayMergedValue($a['death'] ?? '');
    $website = $this->displayMergedValue($a['website'] ?? '');
    $description = $this->displayMergedValue($a['description'] ?? '');
    $images = $a['images'] ?? [];

    $rows .= $this->buildRow("Name", $name);

    if (is_array($genres)) {
      $rows .= $this->buildRow("Genres", implode(', ', $genres));
    }
    else {
      $rows .= $this->buildRow("Genres", (string) $genres);
    }

    $rows .= $this->buildRow("ID", $id); // Changed label from Spotify ID
    $rows .= $this->buildRow("Birthdate", $birth);
    $rows .= $this->buildRow("Deathdate", $death);
    $rows .= $this->buildRow("Website", $website);
    $rows .= $this->buildRow("Description", $description);

    $img_html = "";
    // Support either plain array of urls (older format) or the merged structure.
    if (is_array($images)) {
      $urls = [];
      if (isset($images['spotify']) || isset($images['discogs'])) {
        // Use the merged source (Spotify takes priority for display)
        $urls = $images['spotify'] ?? ($images['discogs'] ?? []);
      } else {
        // Assume it's an old-style array of URLs if no source keys are present.
        $urls = $images;
      }

      if (is_string($urls) && $urls !== '') $urls = [$urls];

      if (is_array($urls)) {
        foreach ($urls as $url) {
          if (is_string($url) && $url !== '') {
            $img_html .= "<img src='$url' width='120' style='margin-right:10px;'> ";
          }
        }
      }
    }
    $rows .= $this->buildRow("Images", $img_html);

    return "<table>$rows</table>";
  }

  protected function buildAlbumTable($a) {
    if (!$a) return "<p>No album data.</p>";
    $rows = "";

    $title = $this->displayMergedValue($a['title'] ?? '');
    $artistName = $this->displayMergedValue($a['artist_name'] ?? '');
    $label = $this->displayMergedValue($a['label'] ?? '');
    $description = $this->displayMergedValue($a['description'] ?? '');
    $releaseDate = $this->displayMergedValue($a['release_date'] ?? '');
    $genres = $this->displayMergedValue($a['genres'] ?? []);
    $image = $this->displayMergedValue($a['image'] ?? '');

    $rows .= $this->buildRow("Title", $title);
    $rows .= $this->buildRow("Artist", $artistName);
    $rows .= $this->buildRow("Label", $label);
    $rows .= $this->buildRow("Description", $description);
    $rows .= $this->buildRow("Release Date", $releaseDate);

    if (is_array($genres)) {
      $rows .= $this->buildRow("Genres", implode(', ', $genres));
    }
    else {
      $rows .= $this->buildRow("Genres", (string) $genres);
    }

    $rows .= $this->buildRow("Image", $image ? "<img src='$image' width='120'>" : '');

    return "<table>$rows</table>";
  }

  protected function buildTrackTable($t) {
    if (!$t) return "<p>No track data.</p>";
    $rows = "";

    $title = $this->displayMergedValue($t['title'] ?? '');
    // Note: display id from either source
    $id = $this->displayMergedValue($t['id'] ?? $t['spotify_id'] ?? '');
    $duration = $this->displayMergedValue($t['duration_ms'] ?? '');
    $genres = $this->displayMergedValue($t['genres'] ?? []);

    $rows .= $this->buildRow("Title", $title);
    $rows .= $this->buildRow("ID", $id); // Changed label from Spotify ID
    $rows .= $this->buildRow("Length", $this->msToMinSec($duration));

    if (is_array($genres)) {
      $rows .= $this->buildRow("Genres", implode(', ', $genres));
    }
    else {
      $rows .= $this->buildRow("Genres", (string) $genres);
    }

    return "<table>$rows</table>";
  }

  /**
   * Required by FormBase, but unused because all submit actions
   * are handled by custom submit handlers.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

}
