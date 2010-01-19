<?php
// $Id$

function _cbisimport_product_updated_webhook($id) {
  $product = CbisTemplate::sanitize(cbisimport_get_product($id));
  cbisimport_product_recieved($product);
}