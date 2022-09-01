<?php

namespace Drupal\arlo\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an 'Arlo Event' block.
 *
 * @Block(
 *  id = "arlo_event_block",
 *  admin_label = @Translation("Arlo Event"),
 * )
 */
class ArloEventBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'platform_id' => NULL,
      'template_id' => 1,
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

    /**
     * Pull in any templates configured in custom hooks.
     */
    $options = [];
    $hook = 'custom_event_templates';
    \Drupal::moduleHandler()->invokeAllWith($hook, function (callable $hook, string $module) use (&$options) {
      $options = $hook();
    });

    // Add our standard templates that come with this module. We do it this way
    // so that the custom templates from hooks are on the top of the array list.
    $options += [
      1 => 'Standard Event Template 1',
      2 => 'Standard Event Template 2',
    ];
    $form['template_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Event Template'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $config['template_id'],
      '#weight' => 5,
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
    $this->configuration['template_id'] = $form_state->getValue('template_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Retrieve existing configuration for this block.
    $block_config = $this->getConfiguration();
    return [
      '#theme' => 'arlo_event_' . $block_config['template_id'],
      '#platform_id' => $block_config['platform_id'],
    ];
  }
}
