<?php
// $Id$

function _cbisimport_product_updated_webhook($id) {
  $product = CbisTemplate::sanitize(cbisimport_get_product($id, array('templateId' => 91)));
  $attr = $product['Attributes'];
  $node = array(
    'type' => 'location',
    'title' => $product['Name'],
    'created' => $product['PublishedDate'],
    'teaser' => $attr['Introduction'],
    'body' => theme('cbis_product', $product),
  );

  if (!empty($attr['Latitude'])) {
    $node['simple_geo_position'] = sprintf('%s %s', $attr['Latitude'], $attr['Longitude']);
  }

  var_dump($product);
  var_dump($node); die;
}