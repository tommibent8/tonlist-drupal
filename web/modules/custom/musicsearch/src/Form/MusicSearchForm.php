<?php

namespace Drupal\musicsearch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
      ];

      $form['album'] = [
        '#type' => 'textfield',
        '#title' => 'Album',
      ];

      $form['track'] = [
        '#type' => 'textfield',
        '#title' => 'Track',
      ];

      // SEARCH BUTTON
      $form['actions']['search'] = [
        '#type' => 'submit',
        '#value' => 'Search',
        '#submit' => ['::submitSearch'],
      ];

      // If there are results, show metadata + creation buttons
      if ($merged) {

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

      $artist = $merged['artist'];

      $form['artist_metadata'] = [
        '#type' => 'details',
        '#title' => 'Artist Metadata',
        '#open' => TRUE,
      ];
      $form['artist_metadata']['table'] = [
        '#markup' => $this->buildArtistTable($artist),
      ];

      // Checkbox list
      $form['artist_fields'] = [
        '#type' => 'checkboxes',
        '#title' => 'Select fields to import',
        '#options' => [
          'name' => 'Name',
          'genres' => 'Genres',
          'spotify_id' => 'Spotify ID',
          'birth' => 'Birthdate',
          'death' => 'Deathdate',
          'website' => 'Website',
          'description' => 'Description',
          'images' => 'Images',
        ],
      ];

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

      $album = $merged['album'];

      $form['album_metadata'] = [
        '#type' => 'details',
        '#title' => 'Album Metadata',
        '#open' => TRUE,
      ];
      $form['album_metadata']['table'] = [
        '#markup' => $this->buildAlbumTable($album),
      ];

      $form['album_fields'] = [
        '#type' => 'checkboxes',
        '#title' => 'Select fields to import',
        '#options' => [
          'title' => 'Title',
          'label' => 'Label',
          'description' => 'Description',
          'release_date' => 'Release Date',
          'genres' => 'Genres',
          'image' => 'Image',
        ],
      ];

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

      $track = $merged['track'];

      $form['track_metadata'] = [
        '#type' => 'details',
        '#title' => 'Track Metadata',
        '#open' => TRUE,
      ];
      $form['track_metadata']['table'] = [
        '#markup' => $this->buildTrackTable($track),
      ];

      $form['track_fields'] = [
        '#type' => 'checkboxes',
        '#title' => 'Select fields to import',
        '#options' => [
          'title' => 'Title',
          'spotify_id' => 'Spotify ID',
          'duration_ms' => 'Length',
          'genres' => 'Genres',
        ],
      ];

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
  public function submitSearch(array &$form, FormStateInterface $form_state) {

    $artist = $form_state->getValue('artist');
    $album  = $form_state->getValue('album');
    $track  = $form_state->getValue('track');

    $spotify = \Drupal::service('musicsearch.spotify_lookup')
      ->fullSearch($artist, $album, $track);

    $form_state->set('merged_results', $spotify);
    $form_state->set('mode', 'search');
    $form_state->setRebuild(TRUE);
  }


  // ============================================================
  // SAVE NODE HANDLERS
  // ============================================================
  public function saveArtistNode(array &$form, FormStateInterface $form_state) {
    $fields = array_filter($form_state->getValue('artist_fields'));
    $data = $form_state->get('merged_results')['artist'];

    $node = \Drupal::service('musicsearch.node_creator')
      ->createArtist($data, $fields);

    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }

  public function saveAlbumNode(array &$form, FormStateInterface $form_state) {
    $fields = array_filter($form_state->getValue('album_fields'));
    $data = $form_state->get('merged_results')['album'];

    $artistNode
      = \Drupal::service('musicsearch.node_creator')->getLastCreatedArtist();

    $node = \Drupal::service('musicsearch.node_creator')
      ->createAlbum($data, $artistNode, $fields);

    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }

  public function saveTrackNode(array &$form, FormStateInterface $form_state) {
    $fields = array_filter($form_state->getValue('track_fields'));
    $data = $form_state->get('merged_results')['track'];

    $albumNode
      = \Drupal::service('musicsearch.node_creator')->getLastCreatedAlbum();

    $node = \Drupal::service('musicsearch.node_creator')
      ->createTrack($data, $albumNode, $fields);

    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }


  // ============================================================
  // TABLE + UTILS (unchanged from your version)
  // ============================================================
  protected function msToMinSec($ms) {
    if (!$ms) return '';
    $sec = floor($ms / 1000);
    return sprintf("%d:%02d", floor($sec / 60), $sec % 60);
  }

  protected function buildRow($label, $value) {
    return "<tr><td><strong>$label</strong></td><td>$value</td></tr>";
  }

  protected function buildArtistTable($a) {
    $rows = "";
    $rows .= $this->buildRow("Name", $a['name'] ?? '');
    $rows .= $this->buildRow("Genres", implode(', ', $a['genres'] ?? []));
    $rows .= $this->buildRow("Spotify ID", $a['id'] ?? '');
    $rows .= $this->buildRow("Birthdate", $a['birth'] ?? '');
    $rows .= $this->buildRow("Deathdate", $a['death'] ?? '');
    $rows .= $this->buildRow("Website", $a['website'] ?? '');
    $rows .= $this->buildRow("Description", $a['description'] ?? '');

    $img_html = "";
    if (!empty($a['images'])) {
      foreach ($a['images'] as $url) {
        $img_html .= "<img src='$url' width='120' style='margin-right:10px;'> ";
      }
    }
    $rows .= $this->buildRow("Images", $img_html);

    return "<table>$rows</table>";
  }

  protected function buildAlbumTable($a) {
    if (!$a) return "<p>No album data.</p>";
    $rows = "";
    $rows .= $this->buildRow("Title", $a['title'] ?? '');
    $rows .= $this->buildRow("Artist", $a['artist_name'] ?? '');
    $rows .= $this->buildRow("Label", $a['label'] ?? '');
    $rows .= $this->buildRow("Description", $a['description'] ?? '');
    $rows .= $this->buildRow("Release Date", $a['release_date'] ?? '');
    $rows .= $this->buildRow("Genres", implode(', ', $a['genres'] ?? []));
    $img = $a['image'] ?? '';
    $rows .= $this->buildRow("Image", $img ? "<img src='$img' width='120'>" : '');
    return "<table>$rows</table>";
  }

  protected function buildTrackTable($t) {
    if (!$t) return "<p>No track data.</p>";
    $rows = "";
    $rows .= $this->buildRow("Title", $t['title'] ?? '');
    $rows .= $this->buildRow("Spotify ID", $t['id'] ?? '');
    $rows .= $this->buildRow("Length", $this->msToMinSec($t['duration_ms'] ?? ''));
    $rows .= $this->buildRow("Genres", implode(', ', $t['genres'] ?? []));
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
