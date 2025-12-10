<?php

namespace Drupal\musicsearch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MusicSearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'musicsearch_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search for an artist, album, or track'),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

// Display results after form submit.
    $results = $form_state->get('results');

    if (!empty($results)) {
      $form['results'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['music-search-results']],
      ];

      // Spotify block.
      if (!empty($results['spotify']['tracks']['items'])) {
        $form['results']['spotify'] = [
          '#type' => 'details',
          '#title' => t('Spotify Results'),
          '#open' => TRUE,
        ];

        foreach ($results['spotify']['tracks']['items'] as $track) {
          $image = $track['album']['images'][1]['url'] ?? '';

          $form['results']['spotify'][] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['music-result']],
            'image' => [
              '#theme' => 'image',
              '#uri' => $image,
              '#alt' => $track['name'],
              '#attributes' => ['style' => 'width:100px;height:auto;margin-right:10px;'],
            ],
            'text' => [
              '#markup' => '<strong>' . $track['name'] . '</strong><br>' .
                $track['artists'][0]['name'] . '<br>' .
                $track['album']['name'],
            ],
          ];
        }

      }

      // Discogs block.
      if (!empty($results['discogs']['results'])) {
        $form['results']['discogs'] = [
          '#type' => 'details',
          '#title' => t('Discogs Results'),
          '#open' => TRUE,
        ];

        foreach ($results['discogs']['results'] as $item) {
          $image = $item['cover_image'] ?? '';

          $form['results']['discogs'][] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['music-result']],
            'image' => [
              '#theme' => 'image',
              '#uri' => $image,
              '#alt' => $item['title'],
              '#attributes' => ['style' => 'width:100px;height:auto;margin-right:10px;'],
            ],
            'text' => [
              '#markup' => '<strong>' . $item['title'] . '</strong><br>' .
                'Type: ' . ($item['type'] ?? 'n/a') . '<br>' .
                'Year: ' . ($item['year'] ?? 'n/a'),
            ],
          ];
        }

      }
    }


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = $form_state->getValue('query');

    // Spotify results.
    $spotify = \Drupal::service('musicsearch.spotify_lookup');
    $spotify_results = $spotify->search($query);

    // Discogs results.
    $discogs = \Drupal::service('musicsearch.discogs_lookup');
    $discogs_results = $discogs->search($query);

    // Store results so buildForm() can display them.
    $form_state->set('results', [
      'spotify' => $spotify_results,
      'discogs' => $discogs_results,
    ]);

    // Trigger form rebuild to show results.
    $form_state->setRebuild(TRUE);
  }

}
