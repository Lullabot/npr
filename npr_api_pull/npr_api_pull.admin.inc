<?php

/**
 * Menu callback. Generates a form to choose blog types.
 */
 
function npr_api_pull_cron_config() {
  $form['npr_api_pull_cron_query_ids'] = array(
    '#type' => 'checkboxes',
    '#title' => t('IDs'),
    '#default_value' => variable_get('npr_api_pull_cron_query_ids', array()),
    '#options' => array(
      '15709577'  => t('All Songs Considered'),
      '102920358' => t('All Tech Considered'),
      '7282089'   => t('Blog of the Nation'),
      '93568166'  => t('Monkey See'),
      '103943429' => t('The Two Way'),
      '1001'      => t('NPR Top Stories'),
    ),
    '#description' => t('Choose one or more topics. New stories from these topic(s) will be be pulled down at every cron run.')
  );
  
  $form['npr_api_pull_cron_publish_flag'] = array(
    '#type' => 'checkbox',
    '#title' => t('Publish stories upon retrieval?'),
    '#default_value' => variable_get('npr_api_pull_cron_publish_flag', 1),
    '#description' => t('If checked stories will automatically be published. If not, stories will still be retrieved and saved in your database - but not published.')
  );
  
  return system_settings_form($form);
}


/**
 * Menu callback. Generates a form to retrieve an NPR story by URL.
 */
function npr_api_pull_get_story() {
  $form = array();

  $form['url'] = array(
    '#type' => 'textfield',
    '#title' => t('NPR API story URL'),
    '#required' => TRUE,
    '#description' => t('Provide full URL for a story on NPR.org (or simply the story id)'),
  );

  $form['date_flag'] = array(
    '#type' => 'radios',
    '#title' => t('Publish story date'),
    '#options' => array(
      '0' => t('Now'),
      '1' => t('API Publish Date'),
    ),
    '#description' => t('Choose either current time or the time it was published on NPR as publish date that will appear on your node.'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Get story'),
  );

  return $form;
}

/**
 * Validation handler for npr_api_pull_get_story().
 */
function npr_api_pull_get_story_validate($form, &$form_state) {

  $story_id = 0;
  $url_value = $form_state['values']['url'];

  if ((int)$url_value > 0) {
    if (drupal_strlen($url_value) < 8) {
      form_set_error('url', t('Does not appear to be a valid NPR ID.')); 
    }
    return;
  }

  if (!valid_url($url_value, TRUE)) {
    form_set_error('url', t('Does not appear to be a valid URL.'));
    return;
  }

  if (!npr_api_pull_extract_id($url_value)) {
    form_set_error('url', t('Could not extract an NPR ID from given URL.'));
  }
}

/**
 * Submit handler for npr_api_pull_get_story().
 */
function npr_api_pull_get_story_submit($form, &$form_state) {

  $story_id = 0;
  $url_value = $form_state['values']['url'];

  if ((int)$url_value > 0 &&drupal_strlen($url_value) >= 8) {
    $story_id = (int)$url_value;
  }
  else {
    $story_id = npr_api_pull_extract_id($url_value);
  }

  //build the params to query the API
  $params = array(
    'id' => $story_id,
  );

  $NPR = npr_api_fetch_object($params);
  if (is_object($NPR) && !empty($NPR->stories)) {
    $story = array_shift($NPR->stories);
    $date_flag = $form_state['values']['date_flag'];
    $story->created = ($date_flag) ? strtotime($story->pubDate->value) : REQUEST_TIME;
    npr_api_pull_insert_story($story);
  }
  else {
    drupal_set_message(t('No story could be found in the NPR API.'));
  }
}

/**
 * Extract a NPR ID from an NPR URL
 */
function npr_api_pull_extract_id($url) {
  // url format: /yyyy/mm/dd/id
  // url format: /blogs/name/yyyy/mm/dd/id
  preg_match('/http\:\/\/[^\s\/]*npr\.org\/((([^\/]*\/){3,5})([0-9]{8,12}))\/.*/', $url, $date_matches);
  if (!empty($date_matches[4])) {
    return $date_matches[4];
  }
  else {
    // url format: /templates/story/story.php?storyId=id
    preg_match('/http\:\/\/[^\s\/]*npr\.org\/([^&\s\<]*storyId\=([0-9]+)).*/', $url, $template_matches);
    if (!empty($template_matches[2])) {
      return $template_matches[2];
    }
  }
}