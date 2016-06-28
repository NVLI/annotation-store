<?php

/**
 * @file
 * Contains \Drupal\book\Controller\BookController.
 */

namespace Drupal\video_annotation\Controller;

use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for book routes.
 */
class VideoAnnotationController {

  /**
   * Returns an administrative overview of all books.
   *
   * @return array
   *   A render array representing the administrative page content.
   */
  public function video_annotation_store() {    
    $this->store_annotations();    
    return array(
        '#type' => 'markup',
        '#markup' => 'annotation stored successfully',
    );
  }
  
  public function video_annotation_search() {    
    $this->get_annotation_datas();
    return array(
        '#type' => 'markup',
        '#markup' => 'annotation stored successfully',
    );
  }
  
  public function video_annotation_update($id) {    
    $this->update_annotation($id);
    return array(
        '#type' => 'markup',
        '#markup' => $id,
    );
  }
  
  public function update_annotation($id) {
    $update_data = annotation_api_from_stdin();
    print_r($update_data);
    exit;
    
    $ent = entity_load('video_annotation', $id);  
    $ent->text->value = 'nice video';      
    $ent->save();
    exit;    
  }  
  
  public function get_annotation_datas() {
    $obj = entity_load_multiple('video_annotation');    
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
                                      "end" => $annotations->rangetime_end->value
                                    ),
               );
    }
    $ars = array('rows' => $res);
    print json_encode($ars);
    exit;
  }
  
  /**
  * Annotation API main endpoint
  */
  public function store_annotations($id = NULL) {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
      case 'GET':
        $this->video_annotation_api_create();
        break;
      case 'POST':
        $this->video_annotation_api_create();
        break;
      case 'PUT':
        $this->video_annotation_api_update();
        break;
      case 'DELETE':
        $this->video_annotation_api_delete();
        break;
    }
  }
  
  public function video_annotation_api_create(){
    $annotation_data = $this->annotation_api_from_stdin();
    if($annotation_data->text) {
      $entity = entity_create('video_annotation', array(
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
      print_r(new JsonResponse($annotation_data));
    }
    exit;
  }
  
  /**
  * Get data from stdin
  * @see http://php.net/manual/en/features.file-upload.put-method.php
  */
  function annotation_api_from_stdin() {
    $json = '';
    // PUT data comes in on the stdin stream
    $put = fopen('php://input', 'r');
    // Read the data 1 KB at a time and write to the file
    while ($chunk = fread($put, 1024)) {
      $json .= $chunk;
    }
    fclose($put);
  
    $entity = (object) Json::decode($json);
    return $entity;
  }

}