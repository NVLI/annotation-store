Annotation Store Module:
------------------------

This module contains improvements and extensions to the Drupal 8 Entity system.The goal is to store and search the entity data's.This module contains 'Video_annotation' table which is used to retrive and fetch corresponding entity data.
We can able to see all entity data in this path '/video_annotation/list'.This module is in development version.

Annotation Store Module is mainly focused on storing the video annotation data [Open Video Annotation Library] locally in drupal.
This modules creates the annotation data as an entity in drupal in the name of "annotation_store".

The entities which are created is listed at "/annotation_store/list".

This information includes Annotation text, type, URI, user, created and changed time stamp.

NOTE: To work around open video annotation in drupal 8 using videojs module. Follow the issue at https://www.drupal.org/node/2748851.

Future Enhancements:
--------------------

1. To store mirador data and compatible with much more annotation
   plugins.
