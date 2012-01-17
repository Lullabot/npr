<?php
// Add audio to xml
// @todo don't send if not new audio!
// @todo what is the id attribute on the audio tag????
// @todo what is the format for multiple audio items? <audio></audio><audio></audio> ????
// @todo find out what happens if we don't include audio w/updates, are they deleted? should we be using the download attribute?
if (isset($node->field_local_audio[$lang][0])) {
  $reingest = 'true';

  foreach ($node->field_local_audio[$lang] as $delta => $file) {
    $file = (object) $file;
    if (isset($file->fid)) {
      $file_obj = file_load($file->fid);

      $wrapper = file_stream_wrapper_get_instance_by_scheme('npraudio');
      $target = file_uri_target($file_obj->uri);
      $url = $wrapper->getExternalUrl() . $target;
      // API cannot handle spaces in file names, so we encode them here.
      if (preg_match('/\/([^\/]*)$/', $url, $matches)) {
        $url = preg_replace('/\/([^\/]*)$/', '/' . rawurlencode($matches[1]), $url);
      }

      // we need to not send the base_url version of the file url if that differs from the canonical_url
      // or this will bother the listener

      $url = npr_ingest_convert_to_canonical_url($url);

      $audio_xml = $story->addChild('audio');
      //make the first file be the primary file
      if ($delta == 0) {
        $audio_xml->addAttribute('type', 'primary');
      }

      $audio_xml->addAttribute('download', 'true');

      //because we can't persist the new_file field, we need to check the reingest flag that we set in cron
      //check this for every file, because we may reset it for the new_file flag.
		if (isset($node->field_local_audio['reingest']) && $node->field_local_audio['reingest'] == FALSE) {
		  $reingest = 'false';
		} 

      //make sure we check to see if the new_file flag was set by pi_npr_ingest_move_audio()
      if (isset($file->new_file) && $file->new_file == TRUE) {
        $reingest = 'true';
      }

      $audio_xml->addAttribute('reingest', $reingest);

      if (isset($file->title)) {
        $audio_xml->addChild('title', $file->title);
      }

      $audio_format = $audio_xml->addChild('format');
      $audio_format->addChild('mp3', $url);
      $audio_xml->addChild('rightsHolder');
    }
  }
}


// remember this when creating byline field

//if we don't get a byline, just use the logged in user's name
if (isset($node->field_credit[$lang][0])) {
  $story->addChild('byline', $node->field_credit[$lang][0]['value']);
}
else {
  $story->addChild('byline', $node->name);
}



//Date stuff

	
    //fill in the required date fields with the current date.
	$now = gmDate("D, d M Y G:i:s O ");
	  if ($node->changed == $node->created) {
	    watchdog('_pi_npr_ingest_create_story_from_node', 'This is an insert for Story = '.$node->title);
		$story->addChild('storyDate', $now);
	  }
	  else {
	    watchdog('_pi_npr_ingest_create_story_from_node', 'This is an update for Story = '.$node->title);
		$story->addChild('storyDate',  format_date($node->created, 'custom', "D, d M Y G:i:s O "));
	  }
	  $story->addChild('pubDate', $now);
	
	// CATEGORY STUFF
	
	 //if there's a category that has an NPR Topic ID associated with it, send that along
	  $categories = array();
	  if (isset($node->field_category[$lang][0])) {
	    $term = taxonomy_term_load($node->field_category[$lang][0]['tid']);
	    if (isset($term->field_npr_topic_id['und'][0]['value'])) { 
	      $full_category_id = $term->field_npr_topic_id['und'][0]['value'];
	      if (strstr($full_category_id, '_')) {
	        $id_parts = explode('_', $full_category_id);
	        $tid = $id_parts[1];
	      }
	      else {
	        $tid = $full_category_id;
	      }
	      if (isset($tid)) {
	        $topic = $story->addChild('parent');
		    $topic->addAttribute('id',$tid);
		    $topic->addAttribute('type', "topic");
	      }
	    }		
	  }
	
	
	
	// IMAGE
	
	
	
	  //send along an image URL if we have one.
	  //NOTE:  We do not ingest images, the image will be served to others that use the API from this site
	  if (isset($node->field_image_gallery[$lang])){
	    $index = 0;
	    foreach ($node->field_image_gallery[$lang] as $image_entry) {
		  if (isset($node->field_image_gallery[$lang][$index]['fid'])){ 
		  //do some file stuff for the image
		  $image_file = file_load($node->field_image_gallery[$lang][$index]['fid']);
		  $image_alt = $node->field_image_gallery[$lang][$index]['alt'];
		  $image_title = $node->field_image_gallery[$lang][$index]['title'];

		  $image_url = file_create_url($image_file->uri);
		  $image_field = $story->addChild('image');
		  $image_field->addAttribute('src',$image_url);
		  $index ++;
		  }
	    }
	  }
	
	
// NPR ID stuff

$full_story_id = $node->field_npr_id[$lang][0]['value'];
if (strstr($full_story_id, '_')) {
  $id_parts = explode('_', $full_story_id);
  $story_id = $id_parts[1];
}
else {
  $story_id = $full_story_id;
}



//assign a landing page
$options = array(); 
$options['absolute'] = TRUE;
$story_url = url(drupal_get_path_alias("node/".$node->nid),$options);

//this is a major hack to get ingest to work when we're doing development on localhost.
//the API will not accept stories that are redirected to http://localhost.BLAH....
$env = variable_get('the_wolf_env');
if ($env != 'stage' && $env != 'production') {
  if (strpos($story_url, 'http://localhost') !== FALSE){
	  $story_url = str_replace('localhost', 'localhost.publicbroadcasting.net', $story_url);
	}
  else {
	  global $base_url;
	  if (strpos($base_url, '.') === false) {
	    //here's where we have to do some fancy foot work
	    $url_words = explode('/',$story_url);
	  	$story_url = str_replace($url_words[2], $url_words[2].'.publicbroadcasting.net', $story_url);
	  }
	}
}


    
    //add the station's org ID 
	$org = $story->addChild('organization');
	$org->addAttribute('orgId', _pi_npr_api_get_org_id());
	
	
//CHANGE PATH WHEN SENDING

// WTF!?!?!?!?!??



$base_url = _pi_npr_api_get_ingest_url().'?';

if($type == 'person'){
  $base_url = str_replace('story', 'bio', $base_url);
}	
		
	