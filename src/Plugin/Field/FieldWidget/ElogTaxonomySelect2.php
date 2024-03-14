<?php

namespace Drupal\elog_core\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\select2\Plugin\Field\FieldWidget\Select2EntityReferenceWidget;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

/**
 * Specialized implementation of the 'select2' widget.
 *
 * Used to display logbooks and tags vocabularies with option groups.
 *
 * @FieldWidget(
 *   id = "elog_taxonomy_select2",
 *   label = @Translation("Elog Taxonomy Select2"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class ElogTaxonomySelect2 extends Select2EntityReferenceWidget{

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    return $this->optionsGrouped(parent::getOptions($entity));
  }

  /**
   * Takes a flat options list produced by parent select2 widget and
   * turns it into a grouped list that will be rendered using <optiongroup>
   * by select2.
   *
   * @param array $optionsList
   *
   * @return array
   */
  protected function optionsGrouped($optionsList) {
    // Options comes in as an array of tid -> name
    // with second-level terms prefixed with a dash -
    $options = [];
    $key = null;
    foreach ($optionsList as $tid => $option) {
      if (is_array($option)){
        $options[] = $option;
        continue;
      }
      if ($key && str_starts_with($option, '-')){
        // The dash means option is a child.  Replace the dash with two spaces
        // so that it will appear indented in the select box
        $options[$key][$tid] = str_replace('-','  ',$option);
      }else{
        $key = $option;
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareFieldValues(array $values, array $element): array {
    $items = parent::prepareFieldValues($values, $element);
    return $items;
  }


  /**
   * Indicates whether the widgets support optgroups.
   *
   * @return bool
   *   TRUE if the widget supports optgroups, FALSE otherwise.
   */
  protected function supportsGroups() {
    return TRUE;
  }


}
