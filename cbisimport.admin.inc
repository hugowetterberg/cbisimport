<?php
// $Id$

/**
 * Callback for the cbisimport settings form.
 *
 * @return array
 */
function cbisimport_settings_form() {
  $form = array();

  $form['cbisimport_api_url'] = array(
    '#type' => 'textfield',
    '#title' => t('API Url'),
    '#description' => t('Url to the API'),
    '#size' => 32,
    '#maxlength' => 255,
    '#default_value' => variable_get('cbisimport_api_url', ''),
  );

  $form['cbisimport_api_key'] = array(
    '#type' => 'textfield',
    '#title' => t('CBIS API Key'),
    '#description' => t('The API key for CBIS.'),
    '#size' => 32,
    '#maxlength' => 32,
    '#default_value' => variable_get('cbisimport_api_key', ''),
  );

  $form['cbisimport_product_cache'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable'),
    '#default_value' => -1,
  );

  $form['cbisimport_product_cache'] = array(
    '#type' => 'select',
    '#title' => t('Product cache'),
    '#multiple' => FALSE,
    '#description' => t('The description appears usually below the item.'),
    '#options' => array(
      'off'   => t('Don\'t use cache'),
      'on'    => t('Build and use the cache'),
      'read'  => t('Use the the cache'),
    ),
    '#default_value' => variable_get('cbisimport_product_cache', 'off'),
  );

  $form['cbisimport_categories'] = array(
    '#type' => 'checkboxes',
    '#title' => t('CBIS import categories'),
    '#description' => t('Categories that should be imported.'),
    '#options' => array(),
    '#default_value' => variable_get('cbisimport_categories', array()),
  );

  $url = variable_get('cbisimport_api_url', '');
  if (!empty($url)) {
    try {
      $categories = cbisimport_get_categories();
      foreach ($categories as $category) {
        if ($category->Parent == 0) {
          $form['cbisimport_categories']['#options'][$category->Id] = $category->Name;
        }
      }
    }
    catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
  }

  return system_settings_form($form);
}
