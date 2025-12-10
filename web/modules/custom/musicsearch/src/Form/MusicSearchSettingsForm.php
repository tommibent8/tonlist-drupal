<?php

namespace Drupal\musicsearch\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class MusicSearchSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['musicsearch.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'musicsearch_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('musicsearch.settings');

    $form['spotify_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify Client ID'),
      '#default_value'=> $config->get('spotify_client_id'),
      '#required' => TRUE,
    ];

    $form['spotify_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify Client Secret'),
      '#default_value' => $config->get('spotify_client_secret'),
      '#required' => TRUE,
    ];

    $form['discogs_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discogs Personal Access Token'),
      '#default_value' => $config->get('discogs_token'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('musicsearch.settings')
      ->set('spotify_client_id', $form_state->getValue('spotify_client_id'))
      ->set('spotify_client_secret', $form_state->getValue('spotify_client_secret'))
      ->set('discogs_token', $form_state->getValue('discogs_token'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
