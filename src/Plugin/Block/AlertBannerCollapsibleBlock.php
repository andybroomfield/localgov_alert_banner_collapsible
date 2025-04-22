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
  const INITIAL_MAX_TITLES_BEFORE_SUMMARY = 3;

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {

    $form = parent::blockForm($form, $form_state);

    $form['collapsible_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Collapsible options'),
    ];

    // Default state.
    $form['collapsible_options']['default_state'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default state'),
      '#options' => [
        '0' => $this->t('Closed'),
        '1' => $this->t('Open'),
        // '2' => $this->t('Open for new banners'),
      ],
      '#default_value' => $this->configuration['default_state'] ?? self::INITIAL_STATE,
    ];

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

    // Max number of banners to show in summary.
    $form['collapsible_options']['max_titles_before_summary'] = [
      '#type' => 'number',
      '#title' => $this->t('Titles to display before before showing a summary.'),
      '#description' => $this->t('Display banner titles as ... and x more after this numnber of banners.'),
      '#default_value' => $this->configuration['max_titles_before_summary'] ?? self::INITIAL_MAX_TITLES_BEFORE_SUMMARY,
      '#min' => 1,
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
    $build['#theme'] = 'localgov_alert_banner_collapsible';

    // Get the collapsible section.
    $html_id = Html::getUniqueId('localgov-alert-banner-collapsible');
    $build['#html_id'] = $html_id;

    // Library.
    $build['#attached']['library'][] = 'localgov_alert_banner_collapsible/alert_banner_collapsible';

    // Closed and open labels.
    $default_state = $this->configuration['default_state'] ?? self::INITIAL_STATE;
    $closed_label = $this->configuration['closed_label'] ?? self::INITIAL_CLOSED_LABEL;
    $open_label = $this->configuration['open_label'] ?? self::INITIAL_OPEN_LABEL;
    $build['#default_state'] = $default_state;
    $build['#attached']['drupalSettings']['localgov_alert_banner_collapsible']['default_state'] = $default_state;
    $build['#closed_label'] = $closed_label;
    $build['#open_label'] = $open_label;

    // Control button.
    $build['#control'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $default_state ? $open_label : $closed_label,
      '#attributes' => [
        'class' => [
          'js-alert-banner-pane-button',
        ],
        'aria-expanded' => $default_state ? 'true' : 'false',
        'aria-controls' => $html_id . '--contents',
        'data-closed-label' => $closed_label,
        'data-open-label' => $open_label,
      ],
    ];

    // Render the alert banners.
    $banner_titles = [];
    $build['#persistent_alert_banners'] = [];
    $build['#alert_banners'] = [];
    foreach ($published_alert_banners as $alert_banner) {
      $rendered_banner = $this->entityTypeManager->getViewBuilder('localgov_alert_banner')
        ->view($alert_banner);

      // Group alert banners depending on if the banner should be persitently
      // displayed (nominally the hide link was disabled).
      // If so, place in the peristent banners area.
      if ($alert_banner->hasField('remove_hide_link') && $alert_banner->remove_hide_link->value) {
        $build['#persistent_alert_banners'][] = $rendered_banner;
      }

      // Otherwise the banner should be in the collapsible section.
      // Add the banner title so the summary of collapsed banners
      // can be generated.
      else {
        $build['#alert_banners'][] = $rendered_banner;
        $banner_titles[] = $alert_banner->label();
      }

    }

    $build['#count'] = count($build['#alert_banners']);
    $build['#published_alert_banners'] = $published_alert_banners;
    $build['#alert_banner_titles'] = $banner_titles;

    // Summarise the banners based on the title.
    // Use the configured value to show a summary, which is the max number of
    // titles that can be displayed, with x more added to the end.
    $max_titles_before_summary = intval($this->configuration['max_titles_before_summary'] ?? self::INITIAL_MAX_TITLES_BEFORE_SUMMARY);
    if (count($banner_titles) === 1) {
      $build['#summary'] = reset($banner_titles);
    }
    elseif (count($banner_titles) === 2 && $max_titles_before_summary >= 2) {
      $build['#summary'] = reset($banner_titles) . ' and ' . end($banner_titles);
    }
    elseif (count($banner_titles) <= $max_titles_before_summary) {
      $build['#summary'] = implode(', ', array_slice($banner_titles, 0, -1)) . ' and ' . end($banner_titles);
    }
    else {
      $build['#summary'] = implode(', ', array_slice($banner_titles, 0, $max_titles_before_summary)) . ' and ' . count(array_slice($banner_titles, $max_titles_before_summary)) . ' more';
    }

    return $build;
  }

}
