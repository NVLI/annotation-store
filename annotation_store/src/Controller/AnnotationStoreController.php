<?php

namespace Drupal\annotation_store\Controller;

use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for annotation_store routes.
 */
class AnnotationStoreController {

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
    // $request_path should not /node/nid. $request_path should be alias.
    $path_alias = \Drupal::service('path.alias_storage')->load(array('alias' => $request_path));
    $split = explode('/', $path_alias['source']);
    return $split[2];
  }

  /**
   * Annotation search - Returns list of annotations.
   */
  public function annotationApiSearch() {
    $ids = \Drupal::entityQuery('annotation_store')->condition('resource_entity_id', $this->getResourceEntityId())->execute();
    $obj = entity_load_multiple('annotation_store', $ids);
    $res = '';
    if ($obj) {
      foreach ($obj as $annotations) {
        $res .= $annotations->data->value;
        if ($annotations !== end($obj)) {
          $res .= ',';

        }
      }
      $res = rtrim($res, ",");
    }
    else {
      // Dummy data for player initialization when data is absent.
      // Temporary Fix - for Open Video Annotation.
      $res = '{"permissions":{"read":[],"update":[],"delete":[],"admin":[]},"ranges":[],"quote":"","text":"dummy","media":"video","target":{"container":"vjs_video_dummy","src":"http:\/\/dummy.com\/dummy.mp4","ext":".mp4"},"rangeTime":{"start":3.14796,"end":4.65196},"updated":"2016-07-08T07:23:10.147Z","created":"2016-07-08T07:23:10.147Z","uri":"http:\/\/dummy.com\/dummy\/1","id":"1"}';
    }
    $ars = '{"rows":[' . $res . ']}';
    print $ars;
    exit;
  }

  /**
   * Annotation create as entity.
   */
  public function annotationApiCreate() {
    $annotation_data = $this->annotationApiFromStdin();
    $annotation_data_save = $annotation_data;
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if ($annotation_data->text) {
      $entity = entity_create('annotation_store', array(
        'type' => $annotation_data->media,
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
    $ent = entity_load('annotation_store', $id);
    if ($flag == 'onUpdate') {
      $ent->text->value = $data->text;
      $ent->changed->value = time();
    }
    $ent->data->value = json_encode($data);
    $ent->save();
    return 'updated';
  }

  /**
   * Annotation API main endpoint.
   */
  public function annotationReqType($id = NULL) {
    $method = $_SERVER['REQUEST_METHOD'];
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
