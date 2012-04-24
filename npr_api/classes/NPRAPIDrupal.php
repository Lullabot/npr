<?php

class NPRAPIDrupal extends NPRAPI {

  function request($params = array(), $method = 'GET', $data = NULL, $path = 'query', $base = self::NPRAPI_PULL_URL) {

    $this->request->method = $method;
    $this->request->params = $params;
    $this->request->data = $data;
    $this->request->path = $path;
    $this->request->base = $base;

    $queries = array();
    foreach ($this->request->params as $k => $v) {
      $queries[] = "$k=$v";
    }
    $request_url = $this->request->base . '/' . $this->request->path . '?' . implode('&', $queries);
    $this->request->request_url = $request_url;

    $response = drupal_http_request($request_url, array('method' => $this->request->method, 'data' => $this->request->data));
    $this->response = $response;

    if ($response->code == self::NPRAPI_STATUS_OK) {
      if ($response->data) {
        $this->xml = $response->data;
      }
      else {
        $this->notice[] = t('No data available.');
      }
    }
  }

  function flatten() {

  }

  function create_NPRML($node) {
    $language = $node->language;
    $xml = new DOMDocument();
    $xml->version = '1.0';
    $xml->encoding = 'UTF-8';
    
    //$xml->add_element($xml, 'nprml', array('version' => self::NPRML_VERSION), NULL,);
    $nprml_element = $xml->createElement('nprml');
    $nprml_version = $xml->createAttribute('version');
    $nprml_version-> value = self::NPRML_VERSION;
    $nprml_element->appendChild($nprml_version);
    
    $nprml = $xml->appendChild($nprml_element);
    $list = $nprml->appendChild($xml->createElement('list'));

    $story = $xml->createElement('story');

    //if the nprID field is set, (probably because this is an update) send that along too
    if (isset($node->npr_id)) {
      $id_element = $xml->createElement('id', $node->npr_id);
      $story->appendChild($id_element);
    }

    $title = substr(($node->title), 0, 100);
    $title_cdata = $xml->createCDATASection($title);
    $title_element = $xml->createElement('title');
    $title_element->appendChild($title_cdata);
    $story->appendChild($title_element);
    
    $story->appendChild($xml->createElement('title', $title));

    if (!empty($node->body[$language][0]['value'])) {
      $body = $node->body[$language][0]['value'];
      $body_cdata = $xml->createCDATASection($body);
      
      $text = $xml->createElement('text');
      $text->appendChild($body_cdata);
      $story->appendChild($text);
      
      // FIX!
      $teaser = $xml->createElement('teaser');
      $teaser->appendChild($body_cdata);
      $story->appendChild($teaser);
    }

    $now = format_date($node->created, 'custom', "D, d M Y G:i:s O ");

    $story->appendChild($xml->createElement('storyDate', $now));
    $story->appendChild($xml->createElement('pubDate', $now));

    //$landing_page = $story->addChild('link', $node->story_url);
    //$landing_page->addAttribute('type', 'html');

    //add the station's org ID
    $org_element = $xml->createElement('organization');
    $org_id = $xml->createAttribute('orgId');
    $org_id->value = variable_get('npr_push_org_id');
    $org_element->appendChild($org_id);
    $story->appendChild($org_element);
    
    $list->appendChild($story);
    dpm($xml->saveXML());
    return $xml->saveXML();
  }
  
  function push_NPRML($node) {
    $org_id = variable_get('npr_push_org_id');
    $api_key = variable_get('npr_api_api_key');
    $params = array(
      'orgId' => $org_id,
      'apiKey' => $api_key,
    );
    $method = 'PUT';
    $base = variable_get('npr_push_api_url');
    $path = 'story';

    $data = $this->create_NPRML($node);
    $this->request($params, $method, $data, $path, $base);
  }
}