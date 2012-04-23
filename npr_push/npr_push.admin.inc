<?php

function npr_push_admin_config() {
  $form = array();
  // When we open this up to more types (via a mapping module?)
  // We won't hard-code this
  // Probably use a hook here
  $available_types = array();
  foreach (node_type_get_types() as $type) {
    $available_types[$type->type] = $type->name;
  }

  $form['npr_push_types_to_push'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Content types to be pushed to the NPR API'),
    '#options' => $available_types,
    '#default_value' => variable_get('npr_push_types_to_push', array()),
  );
  return system_settings_form($form);
}