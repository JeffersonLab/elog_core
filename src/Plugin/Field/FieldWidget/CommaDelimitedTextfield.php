<?php

namespace Drupal\elog_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Specialized widget for entering multiple value using a single
 * text field that accepts comma-separated values.
 *
 *
 * @FieldWidget(
 *   id = "elog_comma_delimited_textfield",
 *   label = @Translation("Comma Delimited Textfield"),
 *   field_types = {
 *     "string",
 *     "integer"
 *   },
 *   multiple_values = TRUE
 * )
 *
 * NOTE: Setting multiple_values to true in the annotation above is what
 *   prevents Drupal from applying the "add another" wrapper to our widget.  It
 *   means that we are responsible for handling multiple values ourselves.
 *
 */
class CommaDelimitedTextfield extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default = parent::defaultSettings();
    $default['size'] = 100;
    $default['autocomplete_route'] = '';
    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = parent::settingsForm($form, $form_state);
    $settings['autocomplete_route'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Route to use for autocomplete'),
      '#default_value' => $this->getSetting('autocomplete_route'),
      '#required' => FALSE,
      '#element_validate' => [
        [static::class, 'validateRouteName'],
      ],
    ];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $route = $this->getSetting('autocomplete_route');
    if (!empty($route) && $this->routeExists($route)) {
      $summary[] = $this->t('Autocomplete Route: @route', ['@route' => $route]);
    }
    return $summary;
  }

  /**
   * Callback to validate that the provided route name actually exists.
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public static function validateRouteName($element, FormStateInterface $form_state) {
    $route = $element['#value'];
    if ( ! self::routeExists($route)){
      dpm(t('Non-existent Route name @route', ['@route' => $route]));
      $form_state->setError($element, t('Non-existent Route name'));
    }

  }

  /**
   * Verify if route exists.
   *
   * @param $route
   *
   * @return bool
   */
  public static function routeExists($route) {
    $route_provider = \Drupal::service('router.route_provider');
    return count($route_provider->getRoutesByNames([$route])) === 1;
  }

  /**
   *  Get values of ItemList as array of strings
   *
   * @return array
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function itemValues(FieldItemListInterface $items) {
     $values = [];
     for ($i=0; $i < $items->count(); $i++){
       $values[] = $items->get($i)->getString();
     }
     return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $formElement = parent::formElement($items, $delta, $element, $form, $form_state);

    // Squash the list of values into a comma-delimited string
    $values = $this->itemValues($items);
    $value = $items->isEmpty() ? '' : implode(', ', $values);
    $formElement['value']['#default_value'] = $value;

    if ($this->getSetting('autocomplete_route')){
      // Assign the provided auto_complete route.
      $formElement['value']['#autocomplete_route_name'] = $this->getSetting('autocomplete_route');
    }

    return $formElement;
  }


}
