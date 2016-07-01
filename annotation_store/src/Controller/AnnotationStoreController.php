<?php

namespace Drupal\annotation_store\Controller;

use Drupal\Component\Serialization\Json;

/**
 * Controller routines for annotation_store routes.
 */
class AnnotationStoreController {

  /**
   * Routing callback - annotation search.
   */
  public function videoAnnotationSearch() {
    $this->annotationReqType();
  }

  /**
   * Routing callback - annotation save.
   */
  public function videoAnnotationStore() {
    $this->annotationReqType();
  }

  /**
   * Routing callback - annotation update and delete.
   */
  public function videoAnnotationUpdateDelete($id) {
    $this->annotationReqType($id);
  }

  /**
   * Annotation search - Returns list of annotations.
   */
  public function videoAnnotationApiSearch() {
    $obj = entity_load_multiple('annotation_store');
    foreach ($obj as $annotations) {
      $res[] = array(
        'media' => $annotations->media->value,
        'text' => $annotations->text->value,
        'ranges' => array(),
        'uri' => $annotations->uri->value,
        'id' => $annotations->id->value,
        'target' => array(
          'container' => $annotations->target_container->value,
          'ext' => $annotations->target_ext->value,
          'src' => $annotations->target_src->value,
        ),
        'rangeTime' => array(
          'start' => $annotations->rangetime_start->value,
          "end" => $annotations->rangetime_end->value,
        ),
      );
    }
    $ars = array(
      'rows' => $res,
    );
    print json_encode($ars);
    exit;
  }

  /**
   * Annotation create as entity.
   */
  public function videoAnnotationApiCreate() {
    $annotation_data = $this->annotationApiFromStdin();
    if ($annotation_data->text) {
      $entity = entity_create('annotation_store', array(
        'text' => $annotation_data->text,
        'uri' => $annotation_data->uri,
        'media' => 'video',
        'type' => 'video annotation',
        'target_container' => $annotation_data->target['container'],
        'target_ext' => $annotation_data->target['ext'],
        'target_src' => $annotation_data->target['src'],
        'rangetime_start' => $annotation_data->rangeTime['start'],
        'rangetime_end' => $annotation_data->rangeTime['end'],
      ));
      $entity->save();
      $annotation_data->id = $entity->id();
      print_r(json_encode($annotation_data));
    }
    exit;
  }

  /**
   * Annotation update - loads posted data, returns data as JSON object.
   */
  public function videoAnnotationApiUpdate($id) {
    $annotation_data = $this->annotationApiFromStdin();
    if ($id) {
      $result = $this->updateAnnotation($id, $annotation_data);
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
  public function videoAnnotationApiDelete() {
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
  public function updateAnnotation($id, $data) {
    $ent = entity_load('annotation_store', $id);
    $ent->text->value = $data->text;
    $ent->rangetime_start->value = $data->rangeTime['start'];
    $ent->rangetime_end->value = $data->rangeTime['end'];
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
        $this->videoAnnotationApiSearch();
        break;

      case 'POST':
        $this->videoAnnotationApiCreate();
        break;

      case 'PUT':
        $this->videoAnnotationApiUpdate($id);
        break;

      case 'DELETE':
        $this->videoAnnotationApiDelete();
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
