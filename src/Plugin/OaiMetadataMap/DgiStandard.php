<?php

namespace Drupal\dgi_standard_oai\Plugin\OaiMetadataMap;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dgi_image_discovery\ImageDiscovery;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\rest_oai_pmh\Plugin\OaiMetadataMapBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OAI implementation for the standard metadata profile.
 *
 * @OaiMetadataMap(
 *   id = "dgi_standard_oai",
 *   label = @Translation("DPLAVA"),
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
      'field_continent' => 'dcterms:spatial',
      'field_country' => 'dcterms:spatial',
      'field_region' => 'dcterms:spatial',
      'field_state' => 'dcterms:spatial',
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
      'field_other_date' => 'dcterms:date',
    ],
    'field_related_item' => [
      'field_title' => 'dcterms:relation',
      'field_url' => 'dcterms:relation',
    ],
  ];

  /**
   * Mapping of linked agent types to terms.
   *
   * @var string[]
   */
  protected $linkedAgentMap = [
    'relators:aut' => 'dcterms:creator',
    'relators:ato' => 'dcterms:contributor',
    'relators:cmp' => 'dcterms:creator',
    'relators:cnd' => 'dcterms:contributor',
    'relators:ctb' => 'dcterms:contributor',
    'relators:crp' => 'dcterms:contributor',
    'relators:cre' => 'dcterms:creator',
    'relators:dpc' => 'dcterms:contributor',
    'relators:drt' => 'dcterms:contributor',
    'relators:edt' => 'dcterms:contributor',
    'relators:ive' => 'dcterms:creator',
    'relators:ivr' => 'dcterms:contributor',
    'relators:prf' => 'dcterms:contributor',
    'relators:pht' => 'dcterms:creator',
    'relators:cph' => 'dcterms:rightsHolder',
    'relators:pbl' => 'dcterms:contributor',
    'relators:sgn' => 'dcterms:contributor',
    'relators:spk' => 'dcterms:contributor',
    'relators:spn' => 'dcterms:contributor',
    'realtors:vdg' => 'dcterms:contributor',
  ];

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Islandora utilities.
   *
   * @var \Drupal\islandora\IslandoraUtils
   */
  protected IslandoraUtils $utils;

  /**
   * Discovery Garden Image Discovery.
   *
   * @var \Drupal\dgi_image_discovery\ImageDiscovery
   */
  protected ImageDiscovery $imageDiscovery;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = is_subclass_of(parent::class, ContainerFactoryPluginInterface::class) ?
      parent::create($container, $configuration, $plugin_id, $plugin_definition) :
      new static($configuration, $plugin_id, $plugin_definition);
    $plugin->entityTypeManager = $container->get('entity_type.manager');
    $plugin->utils = $container->get('islandora.utils');
    $plugin->imageDiscovery = $container->get('dgi_image_discovery.service');
    return $plugin;
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
      if ($field_name == 'field_linked_agent' || $field_name == 'field_organizations') {
        $this->addLinkedAgentValues($values);
        continue;
      }
      elseif ($field_name == 'field_title') {
        $this->handleTitleParagraphs($values);
      }
      elseif ($field_name == 'field_note_paragraph') {
        $this->handleNoteParagraphs($values);
      }
      $metadata_field = $this->getMetadataField($field_name);
      if ($metadata_field && !$values->isEmpty() && $values->access()) {
        $this->addValues($values, $metadata_field);
      }
      // Determine if this is a paragraph.
      elseif ($this->isParagraphField($field_name) && !$values->isEmpty() && $values->access()) {
        $this->addParagraph($field_name, $values);
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

  /**
   * Adds a paragraph to the elements.
   *
   * @param string $paragraph_name
   *   The name of the paragraph field being processed.
   * @param Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $values
   *   The paragraphs themselves.
   */
  protected function addParagraph($paragraph_name, EntityReferenceRevisionsFieldItemList $values) {
    foreach ($values as $value) {
      foreach ($value->entity->getFields() as $field_name => $field_values) {
        $mapped_field = $this->getParagraphField($paragraph_name, $field_name);
        if ($mapped_field && !$field_values->isEmpty() && $field_values->access()) {
          $this->addValues($field_values, $mapped_field);
        }
      }
    }
  }

  /**
   * Adds a title paragraph to the elements.
   *
   * @param Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $values
   *   The list of title paragraphs.
   */
  protected function handleTitleParagraphs(EntityReferenceRevisionsFieldItemList $values) {
    foreach ($values as $value) {
      if ($value->entity->access('view')) {
        $title = $value->entity->get('field_title');
        if (!$title->isEmpty() && $title->access()) {
          $alt = $value->entity->get('field_title_type');
          $dest = !$alt->isEmpty() ? 'dcterms:alternative' : 'dcterms:title';
          $this->elements[$dest][] = $title->getString();
        }
      }
    }
  }

  /**
   * Adds a note paragraph to the elements.
   *
   * @param Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $values
   *   The list of title paragraphs.
   */
  protected function handleNoteParagraphs(EntityReferenceRevisionsFieldItemList $values) {
    foreach ($values as $value) {
      if ($value->entity->access('view')) {
        $note = $value->entity->get('field_note');
        if (!$note->isEmpty() && $note->access()) {
          $dest = 'dcterms:description';
          $note_type = $value->entity->get('field_note_type');
          if (!$note_type->isEmpty() && $note_type->getString() == 'provenance') {
            $dest = 'dc:provenance';
          }
          $this->elements[$dest][] = $note->getString();
        }
      }
    }
  }

  /**
   * Adds a value to the elements using the given metadata field.
   *
   * @param Drupal\Core\Field\FieldItemListInterface $items
   *   The item list to get the values to add from.
   * @param string $metadata_field
   *   The field to add to the elements array using these values.
   */
  protected function addValues(FieldItemListInterface $items, $metadata_field) {
    foreach ($items as $item) {
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
      $this->elements[$metadata_field][] = strip_tags($value);
    }
  }

  /**
   * Adds values for a linked agent to the elements.
   *
   * @param Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The item list to get values from.
   */
  protected function addLinkedAgentValues(EntityReferenceFieldItemListInterface $items) {
    foreach ($items as $item) {
      $metadata_field = $this->getLinkedAgentMetadataField($item->getValue()['rel_type']);
      if ($metadata_field) {
        $this->elements[$metadata_field][] = $item->entity->label();
      }
    }
  }

  /**
   * Adds persistent URL to the elements.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being rendered.
   * @param string $dest
   *   The destination index for the thumbnail.
   * @param bool $alias
   *   If the uri should be an alias.
   */
  protected function addPersistentUrl(ContentEntityInterface $entity, $dest, $alias) {
    $optons = [
      'absolute' => TRUE,
      'alias' => $alias,
    ];
    $this->elements[$dest][] = $entity->toUrl('canonical', $optons)->toString();
  }

  /**
   * Adds thumbnail to the elements.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being rendered.
   * @param string $dest
   *   The destination index for the thumbnail.
   */
  public function addThumbnail(ContentEntityInterface $entity, $dest) {
    $event = $this->imageDiscovery->getImage($entity);

    if ($event->hasMedia()) {
      $media = $event->getMedia();

      $fid = $media->getSource()->getSourceFieldValue($media);
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      $this->elements[$dest][] = $file->createFileUrl(FALSE);
    }
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
   * Helper to retrieve the metadata field for a Drupal field in a paragraph.
   *
   * @param string $paragraph_name
   *   The paragraph to get the metadata field for.
   * @param string $field_name
   *   The name of the field in the paragraph to get the metadata field for.
   *
   * @return false|string
   *   The field mapping for that field within the paragraph if one exists, or
   *   FALSE otherwise.
   */
  protected function getParagraphField($paragraph_name, $field_name) {
    return $this->paragraphMapping[$paragraph_name][$field_name] ?? FALSE;
  }

  /**
   * Helper to retrieve the mapped field for a linked agent.
   *
   * @param string $rel_type
   *   The linked agent type.
   *
   * @return false|string
   *   The mapped field, or FALSE if the linked agent type is unhandled.
   */
  protected function getLinkedAgentMetadataField($rel_type) {
    return $this->linkedAgentMap[$rel_type] ?? FALSE;
  }

  /**
   * Determines if a given paragraph, by name, has mapped metadata fields.
   *
   * @param string $paragraph_name
   *   The name of the field to check.
   *
   * @return bool
   *   Whether the given $paragraph_name has mapped metadata fields.
   */
  protected function isParagraphField($paragraph_name) {
    return isset($this->paragraphMapping[$paragraph_name]);
  }

}
