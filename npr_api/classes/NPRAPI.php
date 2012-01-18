<?php

class NPRAPI {

  const NPRAPI_STATUS_OK = 200;

  function __construct() {

  }
  
  function prepare_request() {

  }

  function send_request() {

  }

  function flatten() {

  }

  function parse() {
    if (!empty($this->xml)) {
      $xml = $this->xml;
    }

    else {
      $this->notices[] = t('No XML to parse.');
      return;
    }

    $object = simplexml_load_string($xml);
    add_simplexml_attributes($object, $this);

    foreach ($object->list->story as $story) {
      $parsed = new NPRMLEntity();
      foreach ($story as $k => $v) {
        $parsed->$k = $this->parse_simplexml_element($v);
      }
      $this->stories[] = $parsed;
    }
  }

  function parse_simplexml_element($element) {
    $NPRMLElement = new NPRMLElement();
    add_simplexml_attributes($element, $NPRMLElement);
    if (count($element->children())) { // works for PHP5.2
      foreach ($element->children() as $i => $child) {
        $NPRMLElement->$i = $this->parse_simplexml_element($child); 
      }
    }
    else {
      $NPRMLElement->value = (string)$element;
    }
    return $NPRMLElement;
  }

  function create_NPRML($node) {
    $language = $node->language;
    $root = new SimpleXMLElement('<nprml></nprml>', 0, FALSE, 'xmlns:nprml=http://api.npr.org/nprml', TRUE);
    $root->addAttribute('version', '0.92.2');
    $list = $root->addChild('list');
    $story = $list->addChild('story');

    if ($node->type == 'blog') {
      $story->addAttribute('type', 'blog');
    }

    // FIX
    $story->addChild('title', substr(htmlentities($node->title), 0, 100));

    if (isset($node->body) {
      $story->addChild('text', htmlentities($node->body);
    }

    if (isset($node->summary)) {
      $story->addChild('teaser', htmlentities($node->summary));
    }
    $story->addChild('storyDate',  format_date($node->created, 'custom', "D, d M Y G:i:s O "));
	$story->addChild('pubDate', $now);

    //if the nprID field is set, (probably because this is an update) send that along too
	if (isset($node->npr_id){
      $story->addAttribute('id', $node->npr_id);
	}

    $landing_page = $story->addChild('link', $node->story_url);
	$landing_page->addAttribute('type', 'html');
    
    //add the station's org ID 
	$org = $story->addChild('organization');
	$org->addAttribute('orgId', $node->org_id);

	return $root;
  }

  function send_NPRML($xml, $path) {
	$xml = pi_hull_convert_html_entities($xml);
    return $this->send_request($params, $method = 'PUT', $xml, $path, $base);	
  }

  function 

}

  
}

function add_simplexml_attributes($element, $object) {
  if (count($element->attributes())) {
    foreach ($element->attributes() as $attr => $value) {
      $object->$attr = (string)$value; 
    }
  } 
}

class NPRMLEntity {
  
}

class NPRMLElement {
  function __toString() {
    return $this->value;
  }
}
