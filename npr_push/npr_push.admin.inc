<?php

function npr_push_admin_config() {
  $form = array();
  $available_types = array();
  foreach (node_type_get_types() as $type) {
    $available_types[$type->type] = $type->name;
  }

  $required_fields = array('body');
  $nprml_fields = npr_api_get_nprml_fields();
  $nprml_options = array();
  foreach ($nprml_fields as $key => $nprml_field) {
    $nprml_options[$key] = $key;
  }
  array_unshift($nprml_options, t('None'));

  foreach (node_type_get_types() as $type) {
    $form[$type->type . '_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t($type->name . ' settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $map = variable_get('npr_push_field_map_' . $type->type, array());
    $all_fields = field_info_instances('node', $type->type);
    $fields = array();
    $form[$type->type . '_settings']['npr_push_types_' . $type->type] = array(
      '#type' => 'checkbox',
      '#title' => t('Push ' . $type->name . ' nodes to the NPR API'),
      '#default_value' => variable_get('npr_push_types_' . $type->type, 0),
    );
    foreach ($all_fields as $key => $all_field) {
      if (!in_array($all_field['field_name'], $required_fields)) {
        $form[$type->type . '_settings']['npr_push_mapping_' . $all_field['field_name']] = array(
          '#type' => 'select',
          '#title' => t('Map "' . $all_field['field_name'] . '" to the following NPRML field:'),
          '#options' => $nprml_options,
          '#default_value' => !empty($map[$all_field['field_name']]) ? $map[$all_field['field_name']] : 0,
        );
      }
    }
  }
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save settings'),
  );
  return $form;
}

function npr_push_admin_config_validate($form, &$form_state) {
  $required_fields = array('body');

  $nprml_fields = npr_api_get_nprml_fields();
  foreach (node_type_get_types() as $type) {
      variable_set('npr_push_types_' . $type->type, $form_state['values']['npr_push_types_' . $type->type]);
    $all_fields = field_info_instances('node', $type->type);
    $taken = array();
    foreach ($all_fields as $key => $all_field) {
      $name = $all_field['field_name'];
      if (!in_array($name, $required_fields)) {
        $value = $form_state['values']['npr_push_mapping_' . $name];
        if ($value !== '0') {
          if (!in_array($all_field['widget']['type'], $nprml_fields[$value]['accepted_types'])) {
            form_set_error('npr_push_mapping_' . $all_field['field_name'], 'Incompatible field types.');
          }
          if (in_array($value, $taken)) {
            form_set_error('npr_push_mapping_' . $all_field['field_name'], 'Multiple fields cannot be mapped to the same NPRML field.');
          }
          $taken[] = $value;
        }
      }
    }
    $required_nprml_fields = array('id');
    foreach ($required_nprml_fields as $nprml_field) {
      if (!in_array($nprml_field, $taken)) {
        form_set_error('npr_push', "A field must map to the NPRML field: $nprml_field");  
      }
    }
  }
}

function npr_push_admin_config_submit($form, &$form_state) {

  $required_fields = array('body');

  foreach (node_type_get_types() as $type) {

    variable_set('npr_push_types_' . $type->type, $form_state['values']['npr_push_types_' . $type->type]);

    $all_fields = field_info_instances('node', $type->type);
    $mapping = array();
    foreach ($all_fields as $key => $all_field) {
      if (!in_array($all_field['field_name'], $required_fields)) {
        $mapping[$all_field['field_name']] = $form_state['values']['npr_push_mapping_' . $all_field['field_name']];
      }
    }
    variable_set('npr_push_field_map_' . $type->type, $mapping);
  }
}
