<?php

class NPRAPIDrupal extends NPRAPI {

  function __construct() {

  }

  function request($params = array(), $method = 'GET', $data = NULL, $path = 'query', $base = 'http://api.npr.org') {

    $this->request->method = $method;
    $this->request->params = $params;
    $this->request->data = $data;
    $this->request->path = $path;
    $this->request->base = $base;

    $queries = array();
    foreach($this->request->params as $k => $v) {
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

}