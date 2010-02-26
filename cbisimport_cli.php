<?php

$root = $argv[1];
chdir($root);

require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// function pcat($categories, $category, $indent = 0) {
//   print str_repeat('--', $indent) . " {$category->Id}:" . $category->Name . "\n";
//   foreach ($category->Children as $cid) {
//     $child = $categories[$cid];
//     pcat($categories, $child, $indent+1);
//   }
// }
// 
// $categories = cbisimport_get_categories();
// foreach ($categories as $category) {
//   if ($category->Parent == 0) {
//     pcat($categories, $category);
//   }
// }

$since = variable_get('cbisimport_last_updated', 0);
$update_status = cbisimport_product_update_status();
$last_update = $since;
$updates = array();
foreach ($update_status as $id => $updated) {
  if ($updated > $since) {
    $last_update = max($updated, $last_update);
    $updates[$id] = $updated;
    cbisimport_queue_update($id, $updated);
  }
}
variable_set('cbisimport_last_updated', $last_update);

while (db_result(db_query("SELECT COUNT(pid) FROM {cbisimport_queue} WHERE fail_count=0"))) {
  print "Getting a batch of ten products\n";
  $res = db_query_range("SELECT * FROM {cbisimport_queue} WHERE fail_count=0", 0, 10);
  $success = array();
  $fail = array();
  while ($qi = db_fetch_array($res)) {
    $product = CbisTemplate::sanitize(cbisimport_get_product($qi['pid']));
    if ($product && !empty($product['Name'])) {
      print "{$product['Name']}\n";
      cbisimport_product_recieved($product);
      $success[] = $qi['pid'];
    }
    else {
      $fail[] = $qi['pid'];
    }
  }
  if (!empty($success)) {
    print "Success " . join($success, ', ') . "\n";
    $plc = db_placeholders($success);
    db_query("DELETE FROM {cbisimport_queue} WHERE pid IN ({$plc})", $success);
  }
  if (!empty($fail)) {
    print "Fail " . join($fail, ', ') . "\n";
    $plc = db_placeholders($fail);
    db_query("UPDATE {cbisimport_queue} SET fail_count = fail_count + 1 WHERE pid IN ({$plc})", $fail);
  }
}