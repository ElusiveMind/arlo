<?php

namespace Drupal\arlo\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an 'Arlo Upcoming Events' block.
 *
 * @Block(
 *  id = "arlo_upcoming_events_block",
 *  admin_label = @Translation("Arlo Upcoming Events"),
 * )
 */
class ArloUpcomingEventsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'max_count' => 5,
      'slow_load_more' => 'true',
      'load_more_text' => $this->t('Show More'),
      'dev_mode' => 'false',
      'locations' => NULL,
      'tag' => NULL,
      'template_category_id' => NULL,
      'event_ids' => NULL,
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

    // Add our standard templates that come with this module.
    $options += [
      1 => 'Standard Template 1',
      2 => 'Standard Template 2',
      3 => 'Standard Template 3',
      4 => 'Standard Template 4',
      5 => 'Standard Template 5',
      6 => 'Standard Template 6',
    ];

    $form['template_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Upcoming Event Templates'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $config['template_id'],
      '#weight' => 5,
    ];

    $form['max_count'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum Number Of Events'),
      '#default_value' => $config['max_count'],
      '#required' => TRUE,
      '#maxlength' => 2,
      '#size' => 2,
      '#weight' => 10,
    ];
    $form['show_load_more'] = [
      '#type' => 'select',
      '#title' => $this->t('Show Load More Button'),
      '#description' => $this->t('Show the load more button if there are more than the maximum count.'),
      '#options' => [
        'true' => 'TRUE',
        'false' => 'FALSE',
      ],
      '#required' => TRUE,
      '#default_value' => $config['show_load_more'],
      '#size' => 1,
      '#weight' => 20,
    ];    
    $form['load_more_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Show More Button Text'),
      '#description' => $this->t('The text for the Show More button.'),
      '#default_value' => $config['load_more_text'],
      '#maxlength' => 32,
      '#weight' => 30,
    ];
    $form['dev_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Development Mode'),
      '#options' => [
        'true' => 'TRUE',
        'false' => 'FALSE',
      ],
      '#default_value' => $config['dev_mode'],
      '#required' => TRUE,
      '#size' => 1,
      '#weight' => 40,
    ]; 

    $form['filters'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('These are optional filters used to select events using different selectors. Use anyone or combinations of the selectors below.'),
      '#weight' => 50,
    ];
    $form['filters']['event_ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event IDs'),
      '#description' => $this->t("Enter an Event ID or set of IDs. Separate event IDs with commas."),
      '#maxlength' => 255,
      '#default_value' => $config['event_ids'],
      '#required' => FALSE,
      '#weight' => 0,
    ];
    $form['filters']['locations'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Locations'),
      '#description' => $this->t("Enter an event location or set of locations. Separate locations with commas."),
      '#maxlength' => 255,
      '#default_value' => $config['locations'],
      '#required' => FALSE,
      '#weight' => 10,
    ];   
    $form['filters']['template_category_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template Category ID'),
      '#description' => $this->t("The Template category ID specification for events."),
      '#maxlength' => 255,
      '#default_value' => $config['template_category_id'],
      '#required' => FALSE,
      '#weight' => 20,
    ];
    $form['filters']['tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tag'),
      '#description' => $this->t("The Arlo tag used to categorize events. Not a Drupal taxonomy term."),
      '#maxlength' => 255,
      '#default_value' => $config['tag'],
      '#required' => FALSE,
      '#weight' => 30,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) { 
    // Because the filters are in a fieldset, they come in as an array. We need
    // to get those elements to assign to our configuration.
    $filters = $form_state->getValue('filters');
    $this->configuration['platform_id'] = $form_state->getValue('platform_id');
    $this->configuration['max_count'] = $form_state->getValue('max_count');
    $this->configuration['show_load_more'] = $form_state->getValue('show_load_more');
    $this->configuration['load_more_text'] = $form_state->getValue('load_more_text');
    $this->configuration['template_id'] = $form_state->getValue('template_id');
    $this->configuration['event_ids'] = $filters['event_ids'];
    $this->configuration['locations'] = $filters['locations'];
    $this->configuration['template_category_id'] = $filters['template_category_id'];
    $this->configuration['tag'] = $filters['tag'];
    $this->configuration['dev_mode'] = $form_state->getValue('dev_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Retrieve existing configuration for this block.
    $block_config = $this->getConfiguration();

    $locations = explode(',', $block_config['locations']);
    $event_ids = explode(',', $block_config['event_ids']);
    return [
      '#theme' => 'arlo_events_' . $block_config['template_id'],
      '#platform_id' => $block_config['platform_id'],
      '#max_count' => $block_config['max_count'],
      '#show_load_more' => $block_config['show_load_more'],
      '#load_more_text' => $block_config['load_more_text'],
      '#event_ids' => $event_ids,
      '#locations' => $locations,
      '#template_category_id' => $block_config['template_category_id'],
      '#tag' => $block_config['tag'],
      '#dev_mode' => $block_config['dev_mode'],
    ];
  }
}