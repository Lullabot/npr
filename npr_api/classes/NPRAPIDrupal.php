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
    $root = new SimpleXMLElement(self::NPRML_DATA, 0, FALSE, self::NPRML_NAMESPACE, TRUE);
    $root->addAttribute('version', self::NPRML_VERSION);
    $list = $root->addChild('list');
    $story = $list->addChild('story');

    // FIX
    $story->addChild('title', substr(htmlentities($node->title), 0, 100));

    if (!empty($node->body[$language][0]['value'])) {
      $story->addChild('text', htmlentities($node->body[$language][0]['value']));
    }

    if (!empty($node->body[$language][0]['value'])) {
      $story->addChild('teaser', htmlentities($node->body[$language][0]['value']));
    }
    $now = format_date($node->created, 'custom', "D, d M Y G:i:s O ");

    $story->addChild('storyDate', $now);
    $story->addChild('pubDate', $now);

    //if the nprID field is set, (probably because this is an update) send that along too
    if (isset($node->npr_id)) {
      $story->addAttribute('id', $node->npr_id);
    }

    //$landing_page = $story->addChild('link', $node->story_url);
    //$landing_page->addAttribute('type', 'html');

    //add the station's org ID
    $org = $story->addChild('organization');
    $org->addAttribute('orgId', variable_get('npr_push_org_id'));

    return $root->asXML();
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
