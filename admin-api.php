<?php
/**
 * The XL Academy — Unified Admin API
 * ONE backend for everything: blog posts + per-page SEO / GEO / AEO tags.
 *
 * Data lives on THIS server, in two folders that must NEVER be uploaded
 * from a local machine (they are the live database):
 *     blog-data/posts.json    blog posts
 *     seo-data/pages.json     per-page meta tags + global settings
 *
 * Consumed by:  admin.html (manage, at /admin)   blog.html / blog-post.html (read)
 *               seo-render.php (renders the managed tags into every page)
 *
 * ─── SETUP ─────────────────────────────────────────────────────────
 * The ONLY thing to change is ADMIN_PASSWORD below.
 * ───────────────────────────────────────────────────────────────────
 */

// ===== CONFIG — change this password =====
$ADMIN_PASSWORD = 'XLacademy@Blog2026';
// =========================================

$BLOG_DIR  = __DIR__ . '/blog-data';
$BLOG_FILE = $BLOG_DIR . '/posts.json';
$SEO_DIR   = __DIR__ . '/seo-data';
$SEO_FILE  = $SEO_DIR . '/pages.json';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
// Blog and SEO data change whenever something is saved, so no layer may hold
// a copy. Without this, /blog and the admin panel can show records that no
// longer exist.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/* ═══════════════════════════════════════════════════════════════════
   Shared storage helpers
   ═══════════════════════════════════════════════════════════════════ */

function read_json($file, $fallback){
  if(!file_exists($file)) return $fallback;
  $data = json_decode(file_get_contents($file), true);
  return is_array($data) ? $data : $fallback;
}

function write_json($dir, $file, $data){
  if(!is_dir($dir)) mkdir($dir, 0755, true);
  // Never leave a data folder web-readable, even if its .htaccess was not uploaded.
  $ht = $dir . '/.htaccess';
  if(!file_exists($ht)){
    file_put_contents($ht,
      "# Raw data — PHP reads it from disk; HTTP access is denied.\n" .
      "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n" .
      "<IfModule !mod_authz_core.c>\n  Order deny,allow\n  Deny from all\n</IfModule>\n");
  }
  file_put_contents($file, json_encode($data,
    JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), LOCK_EX);
  snapshot($dir, $file);
}

/**
 * Snapshot after every write, under a filename an FTP upload of the data
 * folder can never overwrite. If the live file is ever clobbered by a
 * re-upload, the newest snapshot still holds the real data.
 * Each data folder's .htaccess keeps these unreachable over HTTP.
 */
function snapshot($dir, $file){
  if(!file_exists($file)) return;
  $base = basename($file, '.json');
  @copy($file, $dir.'/snapshot-'.$base.'-'.date('Ymd-His').'.json');
  $old = glob($dir.'/snapshot-'.$base.'-*.json');
  if(!is_array($old)) return;
  sort($old);                                   // filenames sort chronologically
  for($i = 0; $i < count($old) - 30; $i++){     // keep the 30 most recent
    @unlink($old[$i]);
  }
}

function out($obj){
  echo json_encode($obj, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

/* ═══════════════════════════════════════════════════════════════════
   Blog helpers
   ═══════════════════════════════════════════════════════════════════ */

function slugify($s){
  $s = strtolower(trim((string)$s));
  $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
  $s = preg_replace('/\s+/', '-', $s);
  $s = preg_replace('/-+/', '-', $s);
  return substr($s, 0, 80);
}

function normalize_post($p){
  $keys = array('id','title','slug','author','category','date','image','excerpt','content','status');
  $o = array();
  foreach($keys as $k){ $o[$k] = isset($p[$k]) ? $p[$k] : ''; }
  if($o['status'] === '') $o['status'] = 'draft';
  return $o;
}

/* ═══════════════════════════════════════════════════════════════════
   SEO / GEO / AEO helpers
   ═══════════════════════════════════════════════════════════════════ */

/** Page slugs are filenames — never let a request reach outside this folder. */
function safe_slug($s){
  $s = strtolower(trim((string)$s));
  return preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $s) ? $s : '';
}

function seo_page_defaults(){
  return array(
    'enabled'       => true,          // false = leave this page's tags untouched
    // --- SEO ---
    'title'         => '',
    'description'   => '',
    'keywords'      => '',
    'canonical'     => '',            // blank = base_url + slug
    'robots'        => 'index,follow',
    'og_title'      => '',            // blank = falls back to title
    'og_description'=> '',            // blank = falls back to description
    'og_image'      => '',            // blank = global default_og_image
    'og_type'       => 'website',
    'twitter_card'  => 'summary_large_image',
    // --- GEO ---
    'geo_region'    => '',            // e.g. IN-DL
    'geo_placename' => '',            // e.g. New Delhi
    'geo_position'  => '',            // "28.5921;77.0460"
    'geo_street'    => '',
    'geo_locality'  => '',
    'geo_postal'    => '',
    // --- AEO (answer engines) ---
    'schema_type'   => 'auto',        // auto | WebPage | LocalBusiness | Course | Article | none
    'schema_json'   => '',            // raw JSON-LD; overrides schema_type when set
    'faq'           => array(),       // [{q,a}] -> FAQPage schema
    'speakable'     => ''             // CSS selectors, comma separated
  );
}

function seo_global_defaults(){
  return array(
    'base_url'         => 'https://thexlacademy.com',
    'site_name'        => 'The XL Academy',
    'locale'           => 'en_IN',
    'default_og_image' => 'images/logo.png',
    'twitter_site'     => '',
    'org_schema'       => true,
    'org_legal_name'   => 'The XL Academy',
    'org_logo'         => 'images/logo.png',
    'org_phone'        => '+917303609096',
    'org_email'        => 'support@thexlacademy.com',
    'org_street'       => 'YC Co-Working Space, Dwarka Sec-13',
    'org_locality'     => 'New Delhi',
    'org_region'       => 'DL',
    'org_postal'       => '110078',
    'org_country'      => 'IN',
    'org_sameas'       => "https://www.facebook.com/thexla\nhttps://www.linkedin.com/company/thexlacademy/\nhttps://www.youtube.com/@thexlacademy\nhttps://instagram.com/academythexl"
  );
}

function normalize_seo_page($p){
  $o = seo_page_defaults();
  foreach($o as $k => $v){
    if(!isset($p[$k])) continue;
    if($k === 'faq'){
      $faq = array();
      if(is_array($p['faq'])){
        foreach($p['faq'] as $row){
          $q = isset($row['q']) ? trim((string)$row['q']) : '';
          $a = isset($row['a']) ? trim((string)$row['a']) : '';
          if($q !== '' && $a !== '') $faq[] = array('q' => $q, 'a' => $a);
        }
      }
      $o['faq'] = $faq;
    } elseif($k === 'enabled'){
      $o['enabled'] = !($p['enabled'] === false || $p['enabled'] === 'false' || $p['enabled'] === 0);
    } else {
      $o[$k] = is_string($p[$k]) ? trim($p[$k]) : $p[$k];
    }
  }
  return $o;
}

function seo_store($file){
  $s = read_json($file, array());
  if(!isset($s['global']) || !is_array($s['global'])) $s['global'] = array();
  if(!isset($s['pages'])  || !is_array($s['pages']))  $s['pages']  = array();
  $s['global'] = array_merge(seo_global_defaults(), $s['global']);
  return $s;
}

/** Page files that are fragments or admin screens — never SEO-managed. */
function seo_skiplist(){
  // 'blog-admin' stays listed in case an old copy is still on the server.
  return array('header', 'footer', 'admin', 'blog-admin');
}

/**
 * Read the <title> and <meta name="description"> already hardcoded in a page
 * so the admin panel starts pre-filled instead of blank.
 */
function seo_extract_existing($html){
  $out = array('title' => '', 'description' => '');
  if(preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)){
    $out['title'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
  }
  if(preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']\s*\/?>/is', $html, $m)){
    $out['description'] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
  }
  return $out;
}

/* ═══════════════════════════════════════════════════════════════════
   Routing
   ═══════════════════════════════════════════════════════════════════ */

$method = $_SERVER['REQUEST_METHOD'];

/* ---------------- GET: public reads + admin reads ---------------- */
if($method === 'GET'){
  $action = isset($_GET['action']) ? $_GET['action'] : 'list';

  if($action === 'ping'){ // open in a browser to confirm PHP is running
    out(array('ok' => true, 'php' => 'working', 'api' => 'unified'));
  }

  if($action === 'auth'){
    $given = isset($_GET['password']) ? trim($_GET['password']) : '';
    out(array('ok' => ($given === $ADMIN_PASSWORD)));
  }

  $authed = (isset($_GET['password']) && $_GET['password'] === $ADMIN_PASSWORD);

  /* ----- SEO reads (admin only) ----- */
  if($action === 'seo_all'){
    if(!$authed) out(array('error' => 'unauthorized'));
    $store = seo_store($SEO_FILE);
    $pages = array();
    foreach($store['pages'] as $slug => $p){ $pages[$slug] = normalize_seo_page($p); }
    out(array('global' => $store['global'], 'pages' => $pages, 'defaults' => seo_page_defaults()));
  }

  /* ----- Blog reads ----- */
  $posts = read_json($BLOG_FILE, array());

  if($action === 'all'){ // admin — everything incl. drafts + full content
    if(!$authed) out(array('error' => 'unauthorized'));
    out(array('posts' => array_values($posts)));
  }

  if($action === 'post'){ // single published post, full content
    $slug = isset($_GET['slug']) ? $_GET['slug'] : '';
    $id   = isset($_GET['id'])   ? $_GET['id']   : '';
    foreach($posts as $p){
      $isPub = (isset($p['status']) && $p['status'] === 'published');
      if($isPub && (($slug !== '' && $p['slug'] === $slug) || ($id !== '' && (string)$p['id'] === (string)$id))){
        out(array('post' => $p));
      }
    }
    out(array('post' => null));
  }

  // default: published list (summary, no full content)
  $list = array();
  foreach($posts as $p){
    if(isset($p['status']) && $p['status'] === 'published'){
      $list[] = array(
        'id' => $p['id'], 'title' => $p['title'], 'slug' => $p['slug'], 'author' => $p['author'],
        'category' => $p['category'], 'date' => $p['date'], 'image' => $p['image'], 'excerpt' => $p['excerpt']
      );
    }
  }
  usort($list, function($a, $b){ return strcmp((string)$b['date'], (string)$a['date']); });
  out(array('posts' => $list));
}

/* ---------------- POST: admin writes ---------------- */
if($method === 'POST'){
  $d = json_decode(file_get_contents('php://input'), true);
  if(!is_array($d)) out(array('error' => 'bad request'));
  $given = isset($d['password']) ? trim($d['password']) : '';
  if($given !== $ADMIN_PASSWORD) out(array('error' => 'unauthorized'));

  $action = isset($d['action']) ? $d['action'] : '';

  /* ═════════ SEO / GEO / AEO writes ═════════ */

  if($action === 'seo_save_global'){
    $store = seo_store($SEO_FILE);
    $in = isset($d['global']) && is_array($d['global']) ? $d['global'] : array();
    foreach(seo_global_defaults() as $k => $v){
      if(!isset($in[$k])) continue;
      $store['global'][$k] = ($k === 'org_schema')
        ? !($in[$k] === false || $in[$k] === 'false' || $in[$k] === 0)
        : (is_string($in[$k]) ? trim($in[$k]) : $in[$k]);
    }
    write_json($SEO_DIR, $SEO_FILE, $store);
    out(array('result' => 'success'));
  }

  if($action === 'seo_save_page'){
    $slug = safe_slug(isset($d['slug']) ? $d['slug'] : '');
    if($slug === '') out(array('error' => 'invalid page slug'));
    if(in_array($slug, seo_skiplist())) out(array('error' => 'this page cannot be SEO-managed'));
    $store = seo_store($SEO_FILE);
    $store['pages'][$slug] = normalize_seo_page(isset($d['page']) ? $d['page'] : array());
    ksort($store['pages']);
    write_json($SEO_DIR, $SEO_FILE, $store);
    out(array('result' => 'success'));
  }

  if($action === 'seo_delete_page'){
    $slug = safe_slug(isset($d['slug']) ? $d['slug'] : '');
    $store = seo_store($SEO_FILE);
    if($slug === '' || !isset($store['pages'][$slug])) out(array('error' => 'not found'));
    unset($store['pages'][$slug]);
    write_json($SEO_DIR, $SEO_FILE, $store);
    out(array('result' => 'success'));
  }

  /**
   * Discover every page file on the server and import the title/description
   * it already has. Pages already managed are left completely alone, so this
   * is safe to run repeatedly and can never overwrite your edits.
   */
  if($action === 'seo_scan'){
    $store = seo_store($SEO_FILE);
    $files = glob(__DIR__ . '/*.html');
    if(!is_array($files)) $files = array();
    $added = array(); $existing = array();
    foreach($files as $f){
      $slug = safe_slug(basename($f, '.html'));
      if($slug === '' || in_array($slug, seo_skiplist())) continue;
      if(isset($store['pages'][$slug])){ $existing[] = $slug; continue; }
      $found = seo_extract_existing((string)file_get_contents($f));
      $p = seo_page_defaults();
      $p['title']       = $found['title'];
      $p['description'] = $found['description'];
      if($slug === 'blog-post' || $slug === '404'){ $p['robots'] = 'noindex,follow'; }
      $store['pages'][$slug] = $p;
      $added[] = $slug;
    }
    ksort($store['pages']);
    write_json($SEO_DIR, $SEO_FILE, $store);
    out(array('result' => 'success', 'added' => $added, 'already_managed' => $existing));
  }

  /* ═════════ Blog writes ═════════ */

  $posts = read_json($BLOG_FILE, array());

  if($action === 'create'){
    $p = isset($d['post']) ? $d['post'] : array();
    $p['id'] = 'p' . round(microtime(true) * 1000);
    if(empty($p['date']))   $p['date']   = date('Y-m-d');
    if(empty($p['slug']))   $p['slug']   = slugify(isset($p['title']) ? $p['title'] : $p['id']);
    if(empty($p['status'])) $p['status'] = 'draft';
    $posts[] = normalize_post($p);
    write_json($BLOG_DIR, $BLOG_FILE, array_values($posts));
    out(array('result' => 'success', 'id' => $p['id']));
  }

  if($action === 'update'){
    $id = isset($d['id']) ? $d['id'] : '';
    $p2 = isset($d['post']) ? $d['post'] : array();
    $p2['id'] = $id;
    if(empty($p2['slug'])) $p2['slug'] = slugify(isset($p2['title']) ? $p2['title'] : $id);
    $found = false;
    foreach($posts as $i => $p){
      if((string)$p['id'] === (string)$id){ $posts[$i] = normalize_post($p2); $found = true; break; }
    }
    if(!$found) out(array('error' => 'not found'));
    write_json($BLOG_DIR, $BLOG_FILE, array_values($posts));
    out(array('result' => 'success'));
  }

  if($action === 'delete'){
    $id = isset($d['id']) ? $d['id'] : '';
    $new = array(); $found = false;
    foreach($posts as $p){
      if((string)$p['id'] === (string)$id){ $found = true; continue; }
      $new[] = $p;
    }
    if(!$found) out(array('error' => 'not found'));
    write_json($BLOG_DIR, $BLOG_FILE, array_values($new));
    out(array('result' => 'success'));
  }

  out(array('error' => 'unknown action'));
}

out(array('error' => 'unsupported method'));
