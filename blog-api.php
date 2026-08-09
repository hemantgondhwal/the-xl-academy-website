<?php
/**
 * The XL Academy — compatibility shim.
 *
 * The blog API was merged into admin-api.php, which is now the single backend
 * for blog posts AND per-page SEO / GEO / AEO tags.
 *
 * This file only exists so pages still pointing at blog-api.php keep working.
 * Nothing new should reference it — use admin-api.php.
 */

$target = __DIR__ . '/admin-api.php';

if(!file_exists($target)){
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  echo json_encode(array('error' => 'admin-api.php is missing — upload it to the same folder as this file.'));
  exit;
}

require $target;
