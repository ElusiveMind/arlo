<?php

namespace Drupal\arlo\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an 'Arlo Calendar' block.
 *
 * @Block(
 *  id = "arlo_calenadar_block",
 *  admin_label = @Translation("Arlo Calendar"),
 * )
 */
class ArloCalendarBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'platform_id' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();
    $form['platform_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Arlo Platform ID'),
      '#default_value' => $config['platform_id'],
      '#required' => TRUE,
      '#maxlength' => 32,
      '#weight' => 1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) { 
    // Because the filters are in a fieldset, they come in as an array. We need
    // to get those elements to assign to our configuration.
    // $filters = $form_state->getValue('filters');
    $this->configuration['platform_id'] = $form_state->getValue('platform_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Retrieve existing configuration for this block.
    $block_config = $this->getConfiguration();
    return [
      '#theme' => 'arlo_calendar',
      '#platform_id' => $block_config['platform_id'],
    ];
  }
}
