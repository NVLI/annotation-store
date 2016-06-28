<?php

namespace Drupal\video_annotation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a VideoAnnotation entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup video_annotation
 */
interface VideoAnnotationInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
