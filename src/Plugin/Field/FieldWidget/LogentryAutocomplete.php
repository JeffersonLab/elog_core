<?php

namespace Drupal\elog_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Specialized widget for referring to logentries by their lognumber field.
 *
 *
 * @FieldWidget(
 *   id = "elog_logentry_autocomplete",
 *   label = @Translation("Logentry Autocomplete"),
 *   field_types = {
 *     "entity_reference",
 *     "string",
 *     "integer"
 *   },
 * )
 */
class LogentryAutocomplete extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $formElement = parent::formElement($items, $delta, $element, $form, $form_state);

    // We hardwire the autocomplete_route_name because it's only use of this widget.
    $formElement['value']['#autocomplete_route_name'] = 'elog_core.autocomplete_reference';
    // But we must validate the submitted values.
    $formElement['value']['#element_validate'] = [
      [static::class, 'validate'],
    ];
    return $formElement;
  }

  /**
   * Validate submitted log references
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public static function validate($element, FormStateInterface $form_state) {
    $currentLognumber = $form_state->getformObject()->getEntity()->get('field_lognumber')->first()->value;
    $input = trim($element['#value']);
    if (! empty($input)){  // Don't validate empty input fields
      if ($input == $currentLognumber){
        $form_state->setError($element, t('Logentry may not reference itself'));
      }else{
        $query = \Drupal::entityQuery('node')
          ->accessCheck(FALSE);  // Return all nodes
        $query->condition('type', 'logentry')
          ->condition("field_lognumber.value", $element['#value'], '=');
        $result = $query->execute();
        if (empty($result)){
          $form_state->setError($element, t('Logentry reference not found'));
        }
      }
    }
  }

}
