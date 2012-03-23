<?php

class NPRAPI {

  const NPRAPI_STATUS_OK = 200;
  
  // NPRML CONSTANTS
  const NPRML_DATA = '<nprml></nprml>';
  const NPRML_NAMESPACE = 'xmlns:nprml=http://api.npr.org/nprml';
  const NPRML_VERSION = '0.92.2';

  function __construct() {
    $this->request = new stdClass;
    $this->request->method = NULL;
    $this->request->params = NULL;
    $this->request->data = NULL;
    $this->request->path = NULL;
    $this->request->base = NULL;
    
  
    $this->response = new stdClass;
    $this->response->code = NULL;
  }
  
  function request() {
  
  }

  function prepare_request() {

  }

  function send_request() {

  }

  function flatten() {

  }

  function create_NPRML() {

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
    
    if (!empty($object->list->story)) {
      foreach ($object->list->story as $story) {
        $parsed = new NPRMLEntity();
        foreach ($story as $k => $v) {
          $parsed->$k = $this->parse_simplexml_element($v);
        }
        add_simplexml_attributes($story, $parsed);
        $body ='';
        if (!empty($parsed->textWithHtml->paragraphs)) {
          foreach ($parsed->textWithHtml->paragraphs as $paragraph) {
            $body = $body . '<p>' . $paragraph->value . '</p>';
          }
        }
        $parsed->body = $body;
        $this->stories[] = $parsed;
      }
    }
  }

  function parse_simplexml_element($element) {
    $NPRMLElement = new NPRMLElement();
    add_simplexml_attributes($element, $NPRMLElement);
    if (count($element->children())) { // works for PHP5.2
      foreach ($element->children() as $i => $child) {
	      if ($i == 'paragraph') {
		      $paragraph = $this->parse_simplexml_element($child);
		      $NPRMLElement->paragraphs[$paragraph->num] = $paragraph; 
	      }
	      else {
          $NPRMLElement->$i = $this->parse_simplexml_element($child);
        }
      }
    }
    else {
      $NPRMLElement->value = (string)$element;
    }
    return $NPRMLElement;
  }

  function send_NPRML($xml, $path) {
    $xml = pi_hull_convert_html_entities($xml);
    return $this->send_request($params, $method = 'PUT', $xml, $path, $base);
  }
  
  function report() {
    if (isset($this->request->params)) {
      $return = 'Request params were';
      foreach ($this->request->params as $k=>$v) {
        $return .= " [$k => $v]";  
      }
      $return .= '.';
    }
    
    else {
      $return = 'Request had no parameters.';
    }
    
    if ($this->response->code == self::NPRAPI_STATUS_OK) {
      $return .= 'Response code was ' . $this->response->code . '.';
      if (isset($this->stories)) {
        $return .= ' Request returned ' . count($this->stories) .  ' stories.';
      }
    }
    elseif ($this->response->code != self::NPRAPI_STATUS_OK) {
      $return = 'Return code was ' . $this->response->code .  '.';
    }
    else {
      $return = 'No info available.';
    }
    return $return; 
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
