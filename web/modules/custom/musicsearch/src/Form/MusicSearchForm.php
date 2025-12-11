<?php

namespace Drupal\musicsearch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MusicSearchForm extends FormBase {

  public function getFormId() {
    return 'musicsearch_search_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    // --- Search fields ---
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

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Search',
    ];

    // --- Display results ---
    $results = $form_state->get('results');

    if ($results) {

      /* ==========================
         ARTIST SECTION
         ========================== */
      if (!empty($results['artist'])) {
        $form['artist_section'] = [
          '#type' => 'details',
          '#title' => 'Artist Metadata',
          '#open' => TRUE,
        ];

        $form['artist_section']['table'] = [
          '#type' => 'markup',
          '#markup' => $this->buildArtistTable($results['artist']),
        ];
      }

      /* ==========================
         ALBUM SECTION
         ========================== */
      if (!empty($results['album'])) {
        $form['album_section'] = [
          '#type' => 'details',
          '#title' => 'Album Metadata',
          '#open' => TRUE,
        ];

        $form['album_section']['table'] = [
          '#type' => 'markup',
          '#markup' => $this->buildAlbumTable($results['album']),
        ];
      }

      /* ==========================
         TRACK SECTION
         ========================== */
      if (!empty($results['track'])) {
        $form['track_section'] = [
          '#type' => 'details',
          '#title' => 'Track Metadata',
          '#open' => TRUE,
        ];

        $form['track_section']['table'] = [
          '#type' => 'markup',
          '#markup' => $this->buildTrackTable($results['track']),
        ];
      }
    }

    return $form;
  }


  /* --------------------------------------------------------------------
     HELPER: Convert ms → "mm:ss"
     -------------------------------------------------------------------- */
  protected function msToMinSec($ms) {
    if (!$ms) return '';
    $sec = floor($ms / 1000);
    return sprintf("%d:%02d", floor($sec / 60), $sec % 60);
  }


  /* --------------------------------------------------------------------
     ARTIST TABLE
     -------------------------------------------------------------------- */
  protected function buildArtistTable($a) {

    $rows = "";

    $rows .= $this->buildRow("Name", $a['name'] ?? '');
    $rows .= $this->buildRow("Genres", implode(', ', $a['genres'] ?? []));
    $rows .= $this->buildRow("Spotify ID", $a['id'] ?? '');
    $rows .= $this->buildRow("Date of Birth", $a['birth'] ?? '');
    $rows .= $this->buildRow("Date of Death", $a['death'] ?? '');
    $rows .= $this->buildRow("Website", $a['website'] ?? '');
    $rows .= $this->buildRow("Description", $a['description'] ?? '');

     // Artist images
    $img_html = "";
    if (!empty($a['images'])) {
      foreach ($a['images'] as $url) {
        $img_html .= "<img src='$url' width='120' style='margin-right:10px;'> ";
      }
    }

    $rows .= $this->buildRow("Images", $img_html);

    return "<table>$rows</table>";
  }


  /* --------------------------------------------------------------------
     ALBUM TABLE
     -------------------------------------------------------------------- */
  protected function buildAlbumTable($a) {

    $rows = "";

    $rows .= $this->buildRow("Title", $a['title'] ?? '');
    $rows .= $this->buildRow("Artist", $a['artist_name'] ?? '');
    $rows .= $this->buildRow("Label (Útgefandi)", $a['label'] ?? '');
    $rows .= $this->buildRow("Description", $a['description'] ?? '');
    $rows .= $this->buildRow("Release Date", $a['release_date'] ?? '');
    $rows .= $this->buildRow("Genres", implode(', ', $a['genres'] ?? []));

    // Album image
    $img_html = $a['image'] ? "<img src='{$a['image']}' width='120'>" : '';
    $rows .= $this->buildRow("Image", $img_html);

    return "<table>$rows</table>";
  }


  /* --------------------------------------------------------------------
     TRACK TABLE
     -------------------------------------------------------------------- */
  protected function buildTrackTable($t) {

    $rows = "";

    $rows .= $this->buildRow("Title", $t['title'] ?? '');
    $rows .= $this->buildRow("Spotify ID", $t['id'] ?? '');
    $rows .= $this->buildRow("Length", $this->msToMinSec($t['duration_ms'] ?? null));
    $rows .= $this->buildRow("Genres", implode(', ', $t['genres'] ?? []));

    return "<table>$rows</table>";
  }


  /* --------------------------------------------------------------------
     GENERAL ROW BUILDER
     -------------------------------------------------------------------- */
  protected function buildRow($label, $value) {
    return "
      <tr>
        <td><strong>$label</strong></td>
        <td>$value</td>
      </tr>
    ";
  }


  /* --------------------------------------------------------------------
     SUBMIT HANDLER
     -------------------------------------------------------------------- */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $artistInput = $form_state->getValue('artist');
    $albumInput  = $form_state->getValue('album');
    $trackInput  = $form_state->getValue('track');

    // Call your NEW Spotify pipeline service (you will create this)
    $spotify = \Drupal::service('musicsearch.spotify_lookup')
      ->fullSearch($artistInput, $albumInput, $trackInput);

    // Only Spotify for now
    $form_state->set('results', $spotify);
    $form_state->setRebuild(TRUE);
  }
}
