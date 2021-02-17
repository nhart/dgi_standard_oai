<?php

namespace Drupal\dgi_standard_oai\Plugin\OaiMetadataMap;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\islandora\IslandoraUtils;
use Drupal\rest_oai_pmh\Plugin\OaiMetadataMapBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OAI implementation for the standard metadata profile.
 *
 * @OaiMetadataMap(
 *   id = "dgi_standard_oai",
 *   label = @Translation("DGI Standard (DPLAVA)"),
 *   metadata_format = "mdRecord",
 *   template = {
 *     "type" = "module",
 *     "name" = "rest_oai_pmh",
 *     "directory" = "templates",
 *     "file" = "oai-default",
 *   }
 * )
 */
class DgiStandard extends OaiMetadataMapBase implements ContainerFactoryPluginInterface {

  /**
   * Array of elements to be given to the OAI template.
   *
   * @var array
   */
  protected $elements = [];

  /**
   * Mapping of base fields to their OAI counterpart.
   *
   * @var string[]
   */
  protected $fieldMapping = [
    'field_member_of' => 'dcterms:isPartOf',
    'field_resource_type' => 'dcterms:type',
    'field_table_of_contents' => 'dcterms:description',
    'field_description' => 'dcterms:description',
    'field_language' => 'dc:language',
    'field_target_audience' => 'dcterms:educationLevel',
    'field_local_identifier' => 'dcterms:identifier',
    'field_purl' => 'edm:isShownAt',
    'field_doi' => 'edm:isShownAt',
    'field_handle' => 'edm:isShownAt',
    'field_ark' => 'edm:isShownAt',
    'field_isbn' => 'dcterms:identifier',
    'field_oclc_number' => 'dcterms:identifier',
    'field_organizations' => 'dcterms:contributer',
    'field_genre' => 'edm:hasType',
    'field_subject' => 'dcterms:subject',
    'field_temporal_subject' => 'dcterms:temporal',
    'field_geographic_subject' => 'dcterms:spatial',
    'field_coordinates' => 'dcterms:spatial',
    'field_geographic_code' => 'dcterms:spatial',
    'field_lcc_classification' => 'dcterms:subject',
    'field_extent' => 'dcterms:extent',
    'field_physical_form' => 'dcterms:medium',
    'field_restriction_on_access' => 'dcterms:accessRights',
    'field_use_and_reproduction' => 'dcterms:rights',
    'field_rights_statement' => 'dcterms:rights',
  ];

  /**
   * Mapping of paragraph subfields to pairs of their fields and OAI output.
   *
   * @var array
   */
  protected $paragraphMapping = [
    'field_faceted_subject' => [
      'field_topic_general_subdivision_' => 'dcterms:subject',
      'field_temporal_chronological_sub' => 'dcterms:temporal',
      'field_geographic_geographic_subd' => 'dcterms:spatial',
    ],
    'field_hierarchical_geographic_su' => [
      'field_territory' => 'dcterms:spatial',
      'field_county' => 'dcterms:spatial',
      'field_city' => 'dcterms:spatial',
      'field_city_section' => 'dcterms:spatial',
      'field_island' => 'dcterms:spatial',
      'field_area' => 'dcterms:spatial',
      'field_extraterrestrial_area' => 'dcterms:spatial',
    ],
    'field_origin_information' => [
      'field_date_created' => 'dcterms:created',
      'field_date_issued' => 'dcterms:issued',
      'field_date_captured' => 'dcterms:date',
      'field_date_valid' => 'dcterms:date',
      'field_date_modified' => 'dcterms:date',
      'field_copyright_date' => 'dcterms:date',
      'field_publisher' => 'dcterms:publisher',
    ],
    'field_related_item' => [
      'field_title' => 'dcterms:relation',
      'field_url' => 'dcterms:relation',
    ],
  ];

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Islandora utilities.
   *
   * @var \Drupal\islandora\IslandoraUtils
   */
  protected $utils;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, IslandoraUtils $utils) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->utils = $utils;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('islandora.utils'));
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataFormat() {
    return [
      'metadataPrefix' => 'mdRecord',
      'schema' => 'https://dplava.lib.virginia.edu/dplava.xsd',
      'metadataNamespace' => 'http://dplava.lib.virginia.edu',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataWrapper() {
    return [
      'mdRecord' => [
        '@xmlns:dc' => 'http://purl.org/dc/elements/1.1/',
        '@xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        '@xmlns:edm' => 'http://www.europeana.eu/schemas/edm/',
        '@xmlns' => 'http://dplava.lib.virginia.edu',
        '@xmlns:dcterms' => 'http://purl.org/dc/terms/',
        '@xmlns:rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        '@xsi:schemaLocation' => 'http://dplava.lib.virginia.edu/dplava.xsd',
      ],
    ];
  }

  /**
   * Transforms an entity into a metadata record.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being rendered.
   *
   * @return string
   *   The metadata record markup to be rendered.
   */
  public function transformRecord(ContentEntityInterface $entity) {
    $render_array = [];
    $this->addFields($entity);
    $render_array['elements'] = $this->elements;
    return parent::build($render_array);
  }

  /**
   * Maps fields to be rendered in the metadata record.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being rendered.
   */
  protected function addFields(ContentEntityInterface $entity) {
    foreach ($entity->getFields() as $field_name => $values) {
      if (!$values->access() || $values->isEmpty()) {
        continue;
      }
      $metadata_field = $this->getMetadataField($field_name);
      if (!$metadata_field) {
        // Determine if this is a paragraph.
        $paragraph_field = $this->getParagraphField($field_name);
        if (!$paragraph_field) {
          continue;
        }
        $this->addParagraph($paragraph_field, $values);
        continue;
      }

      foreach ($values as $value) {
        if ($metadata_field) {
          $this->addValue($value, $metadata_field);
        }
      }

      // Add a link to the item, if it exists.
      $term = $this->utils->getTermForUri('http://pcdm.org/use#OriginalFile');
      if ($term) {
        $media = $this->utils->getMediaWithTerm($entity, $term);
        if ($media) {
          $fid = $media->getSource()->getSourceFieldValue($media);
          $file = $this->entityTypeManager->getStorage('file')->load($fid);
          $this->elements['edm:preview'][] = $file->createFileUrl(FALSE);
        }
      }
    }
  }

  /**
   * Adds a paragraph to the elements.
   *
   * @param string[] $paragraph_mapping
   *   A mapping of fields in the paragraph to their OAI counterpart.
   * @param Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $values
   *   The paragraphs themselves.
   */
  protected function addParagraph(array $paragraph_mapping, EntityReferenceRevisionsFieldItemList $values) {
    foreach ($values as $value) {
      foreach ($value->entity->getFields() as $field_name => $field_values) {
        if (!$field_values->access() || $field_values->isEmpty()) {
          continue;
        }
        if (isset($paragraph_mapping[$field_name])) {
          foreach ($field_values as $field_value) {
            $this->addValue($field_value, $paragraph_mapping[$field_name]);
          }
        }
      }
    }
  }

  /**
   * Adds a value to the elements using the given metadata field.
   *
   * @param Drupal\Core\Field\FieldItemInterface $item
   *   The item to get the value to add from.
   * @param string $metadata_field
   *   The field to add to the elements array.
   */
  protected function addValue(FieldItemInterface $item, $metadata_field) {
    $index = $item->mainPropertyName();
    if ($index === 'alias') {
      return;
    }
    if ($index == 'target_id' && !empty($item->entity)) {
      $value = $item->entity->label();
    }
    else {
      $value = $item->getValue()[$index];
    }
    $this->elements[$metadata_field][] = $value;
  }

  /**
   * Helper to retrieve the metadata field for a Drupal field.
   *
   * @param string $field_name
   *   The Drupal field name to be rendered.
   *
   * @return false|string
   *   The field name if it exists in the mapping, FALSE otherwise.
   */
  protected function getMetadataField($field_name) {
    return $this->fieldMapping[$field_name] ?? FALSE;
  }

  /**
   * Helper to retrieve the paragraph field data for a paragraph.
   *
   * @param string $paragraph_name
   *   The paragraph to get renderable elements for.
   *
   * @return false|array
   *   The array of field mapping for that paragraph if one exists, or FALSE
   *   otherwise.
   */
  protected function getParagraphField($paragraph_name) {
    return $this->paragraphMapping[$paragraph_name] ?? FALSE;
  }

}
