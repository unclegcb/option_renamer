<?php

namespace Drupal\option_renamer\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RenameOption.
 */
class RenameOption extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a KeyConfigOverrideAddForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_option_renamer';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $fieldmap = $this->entityFieldManager->getFieldMap();
    $entity_type_options = [];
    $field_options = [];
    foreach ($fieldmap as $entity_type_id => $fields) {
      $field_details = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
      $field_options[$entity_type_id] = ['' => 'None'];
      foreach ($field_details as $fieldname => $field_config) {
        if (method_exists($field_config, 'getTypeProvider') && $field_config->getTypeProvider() == 'options') {
          $field_option_settings = $field_config->getSettings();
          if (empty($field_option_settings['allowed_values_function'])) {
            $entity_type_options[$entity_type_id] = $entity_types[$entity_type_id]->getLabel();
            $field_options[$entity_type_id][$fieldname] = $fieldname;
            foreach ($field_option_settings['allowed_values'] as $key => $value) {
              $value_options[$entity_type_id][$fieldname][$key] = "$key|$value";
            }
          }
        }
      }
    }
    $form['entity_type'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Entity type'),
      '#options' => $entity_type_options,
      '#empty_option' => '-- Select --',
    ];
    foreach ($entity_type_options as $entity_type_id => $label) {
      $form[$entity_type_id] = [
        '#type' => 'radios',
        '#title' => $this->t('Options field'),
        '#options' => $field_options[$entity_type_id],
        '#empty_option' => '-- Select --',
        '#states' => [
          'visible' => [
            ':input[name="entity_type"]' => ['value' => $entity_type_id],
          ],
        ],
      ];
      foreach ($value_options[$entity_type_id] as $fieldname => $options) {
        $form[$entity_type_id . "__" . $fieldname] = [
          '#type' => 'radios',
          '#title' => $this->t('Value to rename'),
          '#options' => $options,
          '#states' => [
            'visible' => [
              ':input[name="' . $entity_type_id . '"]' => ['value' => $fieldname],
            ],
          ],
        ];
      }
    }
    $form['new_value'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('New value'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}