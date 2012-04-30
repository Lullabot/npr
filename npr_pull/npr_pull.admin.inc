<?php

/**
 * Menu callback. Generates a form to choose blog types.
 */

function npr_pull_cron_config() {
  $form['npr_pull_cron_query_ids'] = array(
    '#type' => 'checkboxes',
    '#title' => t('IDs'),
    '#default_value' => variable_get('npr_pull_cron_query_ids', array()),
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

  $form['node_settings'] = array(
    '#title' => t('Node settings'),
    '#type' => 'fieldset',
  );
    
  $options = variable_get('node_options_' . NPR_PULL_MAPPING_CONTENT_TYPE, array('status', 'promote'));
  
  // Skipping 'publish', as that value
  // is overridden by 'npr_pull_cron_publish_flag' (below)
  $labels = array(
    'promote' => t('Promoted to front page'),
    'sticky' => t('Sticky at top of lists'),
    'revision' => t('Create a new revision'),  
  );
  
  $items = array();
  foreach($labels as $key => $label) {
    $setting = in_array($key, $options) ? t('Yes') : t('No');
    $items[] = array('data' => $label . ": " . $setting);
  } 
  
  $markup = t("Stories pulled down from the NPR API will be turned into !type nodes, with the following additional settings", array('!type' => NPR_PULL_MAPPING_CONTENT_TYPE));
  
  if (user_access('administer content types')) {
    $markup .= ' [' . l('Edit settings', 'admin/structure/types/manage/' . str_replace('-', '_', NPR_PULL_MAPPING_CONTENT_TYPE)) . ']';  
  }
  
  $markup .= ':' . theme('item_list', array('items' => $items));
  
  $form['node_settings']['npr_pull_config_explanation'] = array(
    '#markup' => $markup,
  );

  $form['node_settings']['npr_pull_cron_publish_flag'] = array(
    '#type' => 'checkbox',
    '#title' => t('Publish stories upon retrieval?'),
    '#default_value' => variable_get('npr_pull_cron_publish_flag', 1),
    '#description' => t('If checked stories will automatically be published. If not, stories will still be retrieved and saved in your database - but not published. This setting overrides the default Publish setting for this content type.')
  );
  
  return system_settings_form($form);
}


/**
 * Menu callback. Generates a form to retrieve an NPR story by URL.
 */
function npr_pull_get_story() {
  $form = array();

  $form['url'] = array(
    '#type' => 'textfield',
    '#title' => t('NPR API story URL'),
    '#required' => TRUE,
    '#description' => t('Full URL for a story on NPR.org.'),
  );

  $form['date_flag'] = array(
    '#type' => 'checkbox',
    '#title' => t('Publish stories upon retrieval?'),
    '#default_value' => variable_get('npr_pull_cron_publish_flag', 1),
    '#description' => t('If checked stories will automatically be published. If not, stories will still be retrieved and saved in your database - but not published.'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Get story'),
  );

  return $form;
}

/**
 * Validation handler for npr_pull_get_story().
 */
function npr_pull_get_story_validate($form, &$form_state) {

  $story_id = 0;
  $url_value = $form_state['values']['url'];

  if (!valid_url($url_value, TRUE)) {
    form_set_error('url', t('Does not appear to be a valid URL.'));
    return;
  }

  if (!npr_pull_extract_id($url_value)) {
    form_set_error('url', t('Could not extract an NPR ID from given URL.'));
  }
}

/**
 * Submit handler for npr_pull_get_story().
 */
function npr_pull_get_story_submit($form, &$form_state) {
  $story_id = 0;
  $url_value = $form_state['values']['url'];
  $story_id = npr_pull_extract_id($url_value);

  //build the params to query the API
  $params = array(
    'id' => $story_id,
  );

  $NPR = npr_api_fetch_object($params);
  $failure = !empty($NPR->message) && $NPR->message->id == 201;

  if (is_object($NPR) && !empty($NPR->stories) && !$failure) {
    $story = array_shift($NPR->stories);
    $date_flag = $form_state['values']['date_flag'];
    $story->created = ($date_flag) ? strtotime($story->pubDate->value) : REQUEST_TIME;
    npr_pull_insert_story($story);
  }
  else {
    drupal_set_message(t('No story could be found in the NPR API.'));
  }
}

/**
 * Extract a NPR ID from an NPR URL
 */
function npr_pull_extract_id($url) {
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
