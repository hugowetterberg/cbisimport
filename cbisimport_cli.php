<?php

$root = $argv[1];
chdir($root);

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

$_SERVER['HTTP_HOST'] = $argv[2];
$_SERVER['PHP_SELF'] = $drupal_base_url['path'].'/index.php';
$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'];
$_SERVER['REMOTE_ADDR'] = NULL;
$_SERVER['REQUEST_METHOD'] = NULL;

require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

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
  print "Running cbisimport cron\n";
  cbisimport_cron(TRUE);
}