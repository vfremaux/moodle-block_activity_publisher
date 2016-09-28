<?php
  /**
  * This files servs as map for fields with files 
  * that are a paths or embeded within question_type text.
  */
  
 
  global  $qt_filemap;
  
  $qt_filemap['default']['question']['questiontext'] = 'embedded';
  $qt_filemap['default']['question']['generalfeedback'] = 'embedded';
  $qt_filemap['default']['question']['image'] = 'filepath';
  $qt_filemap['default']['__qid'] = 'id';
  $qt_filemap['imagetarget']['question_imagetarget']['qimage'] = 'filepath';
  $qt_filemap['dragdrop']['question_dragdrop_media']['media'] = 'filepath';
  $qt_filemap['dragdrop']['question_dragdrop_media']['questiontext'] = 'embedded';
  $qt_filemap['dragdrop']['question_dragdrop']['backgroundmedia'] = 'filepath';
  $qt_filemap['dragdrop']['question_dragdrop']['feedbackok'] = 'embedded';
  $qt_filemap['dragdrop']['question_dragdrop']['feedbackmissed'] = 'embedded';
  $qt_filemap['dragdrop']['__qid'] = 'question';
  $qt_filemap['ddmatch']['question_ddmatch_sub']['questiontext'] = 'embedded';
  $qt_filemap['match']['question_match_sub']['questiontext'] = 'embedded';
  $qt_filemap['order']['question_order_sub']['questiontext'] = 'embedded';
  $qt_filemap['slitset']['question_splitset']['feedbackok'] = 'embedded';
  $qt_filemap['splitset']['question_splitset']['feedbackmissed'] = 'embedded';
  $qt_filemap['splitset']['question_splitset_sub']['item'] = 'embedded';
  $qt_filemap['splitset']['__qid'] = 'question';
  
?>
