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

  $products = cbisimport_get_products(array('templateId'=>81));
  $products = CbisTemplate::sanitize($products);
  $form['cbisimport_products'] = array(
    '#value' => var_export($products, TRUE),
    '#prefix' => '<pre>',
    '#suffix' => '</pre>',
  );

  return system_settings_form($form);
}
