<?php

class NPRAPI {

  const NPRAPI_STATUS_OK = 200;

  const NPRAPI_PULL_URL = 'http://api.npr.org';

  // NPRML CONSTANTS
  const NPRML_DATA = '<?xml version="1.0" encoding="UTF-8"?><nprml></nprml>';
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
      $this->notices[] = 'No XML to parse.';
      return;
    }

    $object = simplexml_load_string($xml);
    add_simplexml_attributes($object, $this);

    if (!empty($object->message)) {
      $this->message->id = $this->get_attribute($object->message, 'id');
      $this->message->level = $this->get_attribute($object->message, 'level');
    }

    if (!empty($object->list->story)) {
      foreach ($object->list->story as $story) {
        $parsed = new NPRMLEntity();
        add_simplexml_attributes($story, $parsed);

        //Iterate trough the XML document and list all the children
        $xml_iterator = new SimpleXMLIterator($story->asXML());
        $key = NULL;
        $current = NULL;
        for($xml_iterator->rewind(); $xml_iterator->valid(); $xml_iterator->next()) {
          $current = $xml_iterator->current();
          $key = $xml_iterator->key();

          if (!empty($parsed->{$key})) {
            // images
            if ($key == 'image') {
              if (!is_array($parsed->{$key})) {
                $temp = $parsed->{$key};
                $parsed->{$key} = NULL;
                $parsed->{$key}[] = $temp;
              }
              $parsed->{$key}[] = $this->parse_simplexml_element($current);
            }
            // links
            if ($key == 'link') {
              if (!is_array($parsed->{$key})) {
                $temp = $parsed->{$key};
                $parsed->{$key} = NULL;
              }
              $type = $this->get_attribute($current, 'type');
              $parsed->{$key}[$type] = $this->parse_simplexml_element($current);
            }
          }
          else {
            $parsed->{$key} = $this->parse_simplexml_element($current);
          }
        }
        $body ='';
        if (!empty($parsed->textWithHtml->paragraphs)) {
          foreach ($parsed->textWithHtml->paragraphs as $paragraph) {
            $body = $body . $paragraph->value . "\n\n";
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

  function get_attribute($element, $attribute) {
    foreach ($element->attributes() as $k => $v) {
      if ($k == $attribute) {
        return (string)$v;
      }
    }
  }

  function send_NPRML($xml, $path) {
    $xml = pi_hull_convert_html_entities($xml);
    return $this->send_request($params, $method = 'PUT', $xml, $path, $base);
  }

  function report() {
    $msg = array();
    $params = '';
    if (isset($this->request->params)) {
      foreach ($this->request->params as $k => $v) {
        $params .= " [$k => $v]";
      }
      $msg[] =  'Request params were: ' . $params;
    }

    else {
      $msg[] = 'Request had no parameters.';
    }

    if ($this->response->code == self::NPRAPI_STATUS_OK) {
      $msg[] = 'Response code was ' . $this->response->code . '.';
      if (isset($this->stories)) {
        $msg[] = ' Request returned ' . count($this->stories) . ' stories.';
      }
    }
    elseif ($this->response->code != self::NPRAPI_STATUS_OK) {
      $msg[] = 'Return code was ' . $this->response->code . '.';
    }
    else {
      $msg[] = 'No info available.';
    }
    return $msg;
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
