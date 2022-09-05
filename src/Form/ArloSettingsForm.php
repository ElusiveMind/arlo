<?php

namespace Drupal\arlo\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\Messenger;

/**
 * Form controller for Arlo settings.
 * While each block will have its own settings, these are the
 * global settings for Arlo configuration
 *
 * @ingroup arlo
 */
class ArloSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'arlo.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'arlo_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $feed_machine_name = NULL) {
    $config = $this->config('arlo.settings');

    $form['arlo_platform_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Platform ID'),
      '#description' => t('The platform ID for your integration. This is usually the domain name (xxx.arlo.co) of your Arlo control panel.'),
      '#default_value' => $config->get('platform_id'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => 1,
    );
    $form['arlo_webhook_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Webhook Key'),
      '#description' => t('The key assigned by Arlo when you set up the Webhook.'),
      '#default_value' => $config->get('webhook_key'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => 1,
    );

    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();
    $config = $this->config('arlo.settings');
    $this->config('arlo.settings')
      ->set('platform_id', $values['arlo_platform_id'])
      ->set('webhook_key', $values['arlo_webhook_key'])
      ->save();
  }
}
