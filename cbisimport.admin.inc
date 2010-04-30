<?php
// $Id$

/**
 * Callback for the cbisimport settings form.
 *
 * @return array
 */
function cbisimport_settings_form() {
  $form = array();

  foreach (cbisimport_available_languages() as $code) {
    $form['edit_tags_for_' . $code] = array(
      '#type' => 'item',
      '#title' => t('Edit tags for @lang', array(
        '@lang' => cbisimport_language_code($code),
      )),
      '#value' => l(t('Edit'), 'admin/settings/cbisimport/category-tags/' . $code),
    );
  }

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
      foreach (cbisimport_available_languages() AS $lang) {
        cbisimport_get_categories($lang, TRUE);
        cbisimport_get_geotree($lang, TRUE);
      }

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

function cbisimport_category_tags_form($form_state, $language) {
  $form = array(
    '#pre_render' => array('cbisimport_category_tags_form_effects'),
  );

  $form['language'] = array(
    '#type' => 'value',
    '#value' => $language
  );

  $categories = cbisimport_get_categories($language);
  $category_tags = db_result(db_query("SELECT tags FROM {cbisimport_category_tags} WHERE language = %d", array(
    ':language' => $language,
  )));
  if ($category_tags) {
    $category_tags = unserialize($category_tags);
  }
  else {
    $category_tags = array();
  }

  foreach ($categories as $category) {
    $name = _cbisimport_category_hierarchical_name($category, $categories);
    $value = empty($category_tags[$category->Id]) ? '' : $category_tags[$category->Id];
    $classes = array(
      'cbis-category',
      'category-id-' . $category->Id,
      'category-parent-' . $category->Parent,
    );
    if (!empty($category->Children)) {
      $classes[] = 'has-children';
    }
    $form['tags_' . $category->Id] = array(
      '#attributes' => array(
        'class' => join($classes, ' '),
      ),
      '#type' => 'textfield',
      '#title' => '<span>' . join($name, '</span> / <span>') . '</span>',
      '#default_value' => $value,
      '#size' => 40,
      '#maxlength' => 255,
    );
  }

  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

function cbisimport_category_tags_form_submit($form, $form_state) {
  $values = $form_state['values'];
  $translations = array();
  $language = $values['language'];

  $categories = cbisimport_get_categories($language);
  foreach ($categories as $category) {
    if (!empty($values['tags_' . $category->Id])) {
      $translations[$category->Id] = $values['tags_' . $category->Id];
    }
  }

  $record = array(
    'language' => $language,
    'tags' => $translations,
  );
  $update = db_result(db_query("SELECT COUNT(language) FROM {cbisimport_category_tags} WHERE language = %d", array(
    ':language' => $language,
  ))) ? array('language') : array();
  drupal_write_record('cbisimport_category_tags', $record, $update);

  drupal_set_message(t('Category tag translations have been updated'));
}

function cbisimport_category_tags_form_effects($form) {
  drupal_add_js(drupal_get_path('module', 'cbisimport') . '/js/categories.js');
  return $form;
}

function _cbisimport_category_hierarchical_name($category, $categories) {
  $name = array();
  while ($category) {
    $name[] = $category->Name;
    $category = $category->Parent ? $categories[$category->Parent] : NULL;
  }
  return array_reverse($name);
}
