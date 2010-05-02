<?php
// $Id$

function _cbisimport_product_updated_webhook($id) {
  foreach (cbisimport_available_languages() as $language) {
    $product = CbisTemplate::sanitize(cbisimport_get_product($id, array(
      'languageId' => $language,
    )));
    cbisimport_product_recieved($product);
  }
}