<?php

namespace Drupal\annotation_store\Controller;

use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for annotation_store routes.
 */
class AnnotationStoreController extends ControllerBase {

  /**
   * Routing callback - annotation search.
   */
  public function annotationStoreSearch() {
    $this->annotationReqType();
  }

  /**
   * Routing callback - annotation save.
   */
  public function annotationStoreSave() {
    $this->annotationReqType();
  }

  /**
   * Routing callback - annotation update and delete.
   */
  public function annotationStoreUpdateDelete($id) {
    $this->annotationReqType($id);
  }

  /**
   * Get Resource Entity ID.
   */
  public function getResourceEntityId() {
    $request_uri = \Drupal::request()->server->get('HTTP_REFERER');
    $request_path = parse_url($request_uri, PHP_URL_PATH);
    // This method is not used in localhost while testing.
    $path_alias = \Drupal::service('path.alias_storage')->load(array('alias' => $request_path));
    // If url is from path alias.
    if (is_array($path_alias)) {
      $split = explode('/', $path_alias['source']);
      $resource_entity_id = $split[2];
    }
    // If url is without alias.
    else {
      $split = explode('/', $request_path);
      $resource_entity_id = $split[2];
    }
    return $resource_entity_id;
  }

  /**
   * Annotation search - Returns list of annotations.
   */
  public function annotationApiSearch() {

    $output = array();
    $resource_entity_id = \Drupal::request()->query->get('resource_entity_id');
    
    $entity = \Drupal::entityTypeManager()->getStorage('annotation_store')->load($resource_entity_id);
    $annotations = $this->getSearchAnnotation($resource_entity_id);
    $entity->content['data']['annotations'] = $annotations;
    
    \Drupal::moduleHandler()->invokeAll('annotation_store_search_endpoint_output_alter', array(&$entity));
    $output = $entity->content['data']['annotations'];
    
    print $output;
    exit;
  }

  public function getSearchAnnotation($resource_entity_id) {
  $ids = \Drupal::entityQuery('annotation_store')->condition('resource_entity_id', $resource_entity_id)->execute();
    foreach ($ids as $key => $value) {
      $records = \Drupal::entityTypeManager()->getStorage('annotation_store')->load($value);
      $annotation_object = json_decode($records->data->value);
      $annotations[] = array(
        'data' => $records->data->value,
        'id' => $value,
        'text' => $annotation_object->text,
      );
    }
    return $annotations;
  }

  /**
   * Annotation create as entity.
   */
  public function annotationApiCreate() {
    $annotation_data = $this->annotationApiFromStdin();
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if ($annotation_data->text) {
      $entity = \Drupal::entityManager()->getStorage('annotation_store')->create(array(
        'type' =>  $annotation_data->media,
        'language' => $language,
        'data' => json_encode($annotation_data),
        'uri' => $annotation_data->uri,
        'text' => $annotation_data->text,
        'resource_entity_id' => $this->getResourceEntityId(),
      ));
      $entity->save();
      $annotation_data->id = $entity->id();
      $this->updateAnnotation($entity->id(), $annotation_data, 'onCreate');
      print_r(json_encode($annotation_data));
    }
    exit;
  }

  /**
   * Annotation update - loads posted data, returns data as JSON object.
   */
  public function annotationApiUpdate($id) {
    $annotation_data = $this->annotationApiFromStdin();
    if ($id) {
      $this->updateAnnotation($id, $annotation_data, 'onUpdate');
      print_r(json_encode($annotation_data));
    }
    else {
      print_r('failed');
    }
    exit;
  }

  /**
   * Annotation update - deletes the entity based on the id passed.
   */
  public function annotationApiDelete() {
    $data = $this->annotationApiFromStdin();
    $id = $data->id;
    if ($id) {
      entity_delete_multiple('annotation_store', array($id));
      print_r(1);
    }
    else {
      print_r(0);
    }
    exit;
  }

  /**
   * Annotation update callback.
   */
  public function updateAnnotation($id, $data, $flag) {

    $entity = \Drupal::entityTypeManager()->getStorage('annotation_store')->load($id);
    if ($flag == 'onUpdate') {
      $entity->text->value = $data->text;
      $entity->changed->value = time();
    }
    $entity->data->value = json_encode($data);
    $entity->save();
    return 'updated';
  }

  /**
   * Annotation API main endpoint.
   */
  public function annotationReqType($id = NULL) {

    $method =  \Drupal::request()->server->get('REQUEST_METHOD');
    switch ($method) {
      case 'GET':
        $this->annotationApiSearch();
        break;

      case 'POST':
        $this->annotationApiCreate();
        break;

      case 'PUT':
        $this->annotationApiUpdate($id);
        break;

      case 'DELETE':
        $this->annotationApiDelete();
        break;

    }
  }

  /**
   * Get data from stdin.
   */
  public function annotationApiFromStdin() {
    $json = '';
    // PUT data comes in on the stdin stream.
    $put = fopen('php://input', 'r');
    // Read the data 1 KB at a time and write to the file.
    while ($chunk = fread($put, 1024)) {
      $json .= $chunk;
    }
    fclose($put);
    $entity = (object) Json::decode($json);
    return $entity;
  }

}
