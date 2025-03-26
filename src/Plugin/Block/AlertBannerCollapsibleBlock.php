<?php

declare(strict_types=1);

namespace Drupal\localgov_alert_banner_collapsible\Plugin\Block;

use Drupal\localgov_alert_banner\Plugin\Block\AlertBannerBlock;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a collapsible alert banner block.
 *
 * @Block(
 *   id = "localgov_alert_banner_collapsible",
 *   admin_label = @Translation("Collapsible alert banner"),
 *   category = @Translation("Localgov Alert banner collapsible"),
 * )
 */
class AlertBannerCollapsibleBlock extends AlertBannerBlock {

  const INITIAL_STATE = 0;
  const INITIAL_OPEN_LABEL = 'Hide alert banners';
  const INITIAL_CLOSED_LABEL = 'Show alert banners';

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {

    $form = parent::blockForm($form, $form_state);

    $form['collapsible_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Collapsible options'),
    ];

    //@codingStandardsIgnoreStart
    // Default state.
    // $form['collapsible_options']['default_state'] = [
    //   '#type' => 'radios',
    //   '#title' => $this->t('Initial collapsible state'),
    //   '#options' => [
    //     '0' => $this->t('Closed'),
    //     '1' => $this->t('Open'),
    //     // '2' => $this->t('Open for new banners'),
    //   ],
    //   '#default_value' => $this->configuration['default_state'] ?? self::INITIAL_STATE,
    // ];.
    //@codingStandardsIgnoreEnd
    // Open label.
    $form['collapsible_options']['open_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Open label'),
      '#description' => $this->t('Label to display for the button when alert banners are expanded.'),
      '#default_value' => $this->configuration['open_label'] ?? self::INITIAL_OPEN_LABEL,
    ];

    // Closed label.
    $form['collapsible_options']['closed_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Closed label'),
      '#description' => $this->t('Label to display for the button when alert banners are collapsed.'),
      '#default_value' => $this->configuration['closed_label'] ?? self::INITIAL_CLOSED_LABEL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    foreach ($form_state->getValues()['collapsible_options'] as $key => $value) {
      $this->configuration[$key] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $options = [
      'type' => $this->mapTypesConfigToQuery(),
      'check_visible' => TRUE,
    ];

    // Fetch the current published banner.
    $published_alert_banners = $this->alertBannerManager->getCurrentAlertBanners($options);

    // If no banner found, return NULL so block is not rendered.
    if (empty($published_alert_banners)) {
      return NULL;
    }

    // Render the alert banner.
    $build = [];

    // Get the collapsible section.
    $html_id = Html::getUniqueId('localgov-alert-banner-collapsible');
    $collapsible_build = [];

    $collapsible_build['#attached']['library'][] = 'localgov_alert_banner_collapsible/alert_banner_collapsible';

    $collapsible_build['pane'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'js-alert-banner-pane',
        ],
        'id' => $html_id,
      ],
    ];
    $collapsible_build['pane']['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->configuration['open_label'] ?? self::INITIAL_OPEN_LABEL,
      '#attributes' => [
        'class' => [
          'js-alert-banner-pane-button',
        ],
        'aria-expanded' => 'true',
        'aria-controls' => $html_id . '--contents',
        'data-closed-label' => $this->configuration['closed_label'] ?? self::INITIAL_CLOSED_LABEL,
        'data-open-label' => $this->configuration['open_label'] ?? self::INITIAL_OPEN_LABEL,
      ],
    ];
    $collapsible_build['pane']['contents'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'js-alert-banner-pane-content',
        ],
        'id' => $html_id . '--contents',
      ],
    ];

    // Render the alert banner.
    foreach ($published_alert_banners as $alert_banner) {
      $collapsible_build['pane']['contents'][] = $this->entityTypeManager->getViewBuilder('localgov_alert_banner')
        ->view($alert_banner);
    }

    $build[] = $collapsible_build;
    return $build;
  }

}
