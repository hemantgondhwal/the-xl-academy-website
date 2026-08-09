<?php
/**
 * The XL Academy — page renderer
 *
 * Every clean URL (/blog, /data-science, /branch-delhi …) is routed here by
 * .htaccess. This script reads the matching .html file, injects the managed
 * SEO / GEO / AEO tags from seo-data/pages.json, and outputs the page.
 *
 * ─── FAIL-OPEN BY DESIGN ────────────────────────────────────────────
 * The whole site depends on this one file, so it never blocks a page.
 * The original HTML is read into memory FIRST and echoed unchanged if
 * anything at all goes wrong — missing data file, bad JSON, or even a
 * PHP fatal error (see xl_failopen below). Worst case a page loses its
 * managed tags; it can never go blank or 500.
 * ────────────────────────────────────────────────────────────────────
 */

$XL_RAW  = '';     // original HTML — the fallback payload
$XL_SENT = false;  // set once the real response has been echoed

/**
 * If a fatal error kills this script mid-render, discard whatever partial
 * output exists and send the untouched original page instead.
 */
function xl_failopen(){
  global $XL_RAW, $XL_SENT;
  if($XL_SENT || $XL_RAW === '') return;
  $e = error_get_last();
  if(!$e) return;
  $fatal = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
  if(!in_array($e['type'], $fatal)) return;
  while(ob_get_level() > 0){ ob_end_clean(); }
  header('Content-Type: text/html; charset=UTF-8');
  echo $XL_RAW;
}
register_shutdown_function('xl_failopen');

ob_start();

/* ─── Resolve which page was asked for ───────────────────────────── */

$slug = isset($_GET['__page']) ? strtolower(trim((string)$_GET['__page'])) : 'index';
// Page slugs are filenames. Anything not matching this cannot reach the disk.
if(!preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $slug)) $slug = 'index';

$file   = __DIR__ . '/' . $slug . '.html';
$status = 200;

if(!file_exists($file)){
  $slug   = '404';
  $file   = __DIR__ . '/404.html';
  $status = 404;
}
if(!file_exists($file)){          // nothing to serve at all
  http_response_code(404);
  echo 'Not found';
  exit;
}

$XL_RAW = (string)file_get_contents($file);

http_response_code($status);
header('Content-Type: text/html; charset=UTF-8');

/* ─── Load the managed tag data ──────────────────────────────────── */

function xl_read_json($f){
  if(!file_exists($f)) return array();
  $d = json_decode((string)file_get_contents($f), true);
  return is_array($d) ? $d : array();
}

$store  = xl_read_json(__DIR__ . '/seo-data/pages.json');
$G      = isset($store['global']) && is_array($store['global']) ? $store['global'] : array();
$pages  = isset($store['pages'])  && is_array($store['pages'])  ? $store['pages']  : array();
$P      = isset($pages[$slug]) && is_array($pages[$slug]) ? $pages[$slug] : null;

// Nothing managed for this page, or it was switched off → serve it untouched.
if($P === null || (isset($P['enabled']) && $P['enabled'] === false)){
  $XL_SENT = true;
  ob_end_flush();
  echo $XL_RAW;
  exit;
}

function g($G, $k, $d = ''){ return isset($G[$k]) && $G[$k] !== '' ? $G[$k] : $d; }
function p($P, $k, $d = ''){ return isset($P[$k]) && $P[$k] !== '' ? $P[$k] : $d; }

$base     = rtrim(g($G, 'base_url', 'https://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '')), '/');
$siteName = g($G, 'site_name', 'The XL Academy');
$locale   = g($G, 'locale', 'en_IN');

/** Turn "images/x.png" into an absolute URL; leave real URLs alone. */
function abs_url($base, $u){
  if($u === '') return '';
  if(preg_match('#^https?://#i', $u)) return $u;
  return $base . '/' . ltrim($u, '/');
}

/* ─── /blog-post is dynamic: pull the real post's meta ───────────── */

$post = null;
if($slug === 'blog-post'){
  $want = isset($_GET['slug']) ? (string)$_GET['slug'] : '';
  if($want !== ''){
    foreach(xl_read_json(__DIR__ . '/blog-data/posts.json') as $row){
      if(!is_array($row)) continue;
      $pub = (isset($row['status']) && $row['status'] === 'published');
      if($pub && isset($row['slug']) && $row['slug'] === $want){ $post = $row; break; }
    }
  }
}

if($post){
  // A real article beats the generic /blog-post placeholder tags.
  $title    = $post['title'] . ' — ' . $siteName;
  $desc     = isset($post['excerpt']) ? $post['excerpt'] : '';
  $ogImage  = abs_url($base, isset($post['image']) ? $post['image'] : g($G, 'default_og_image'));
  $ogType   = 'article';
  $canon    = $base . '/blog-post?slug=' . rawurlencode($post['slug']);
  $robots   = 'index,follow';
} else {
  $title    = p($P, 'title');
  $desc     = p($P, 'description');
  $ogImage  = abs_url($base, p($P, 'og_image', g($G, 'default_og_image')));
  $ogType   = p($P, 'og_type', 'website');
  $canon    = p($P, 'canonical', $base . ($slug === 'index' ? '/' : '/' . $slug));
  $robots   = p($P, 'robots', 'index,follow');
}

$ogTitle = $post ? $title : p($P, 'og_title', $title);
$ogDesc  = $post ? $desc  : p($P, 'og_description', $desc);

/* ─── Build the tag block ────────────────────────────────────────── */

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$T = array();
$T[] = '<!-- managed by /admin — edit there, not here -->';

if($desc !== '')                 $T[] = '<meta name="description" content="' . e($desc) . '">';
if(p($P, 'keywords') !== '')     $T[] = '<meta name="keywords" content="' . e(p($P, 'keywords')) . '">';
if($robots !== '')               $T[] = '<meta name="robots" content="' . e($robots) . '">';
if($canon !== '')                $T[] = '<link rel="canonical" href="' . e($canon) . '">';

/* --- Open Graph --- */
$T[] = '<meta property="og:site_name" content="' . e($siteName) . '">';
$T[] = '<meta property="og:locale" content="' . e($locale) . '">';
$T[] = '<meta property="og:type" content="' . e($ogType) . '">';
if($ogTitle !== '') $T[] = '<meta property="og:title" content="' . e($ogTitle) . '">';
if($ogDesc  !== '') $T[] = '<meta property="og:description" content="' . e($ogDesc) . '">';
if($canon   !== '') $T[] = '<meta property="og:url" content="' . e($canon) . '">';
if($ogImage !== '') $T[] = '<meta property="og:image" content="' . e($ogImage) . '">';
if($post){
  $T[] = '<meta property="article:published_time" content="' . e(isset($post['date']) ? $post['date'] : '') . '">';
  if(!empty($post['category'])) $T[] = '<meta property="article:section" content="' . e($post['category']) . '">';
}

/* --- Twitter --- */
$T[] = '<meta name="twitter:card" content="' . e(p($P, 'twitter_card', 'summary_large_image')) . '">';
if(g($G, 'twitter_site') !== '') $T[] = '<meta name="twitter:site" content="' . e(g($G, 'twitter_site')) . '">';
if($ogTitle !== '') $T[] = '<meta name="twitter:title" content="' . e($ogTitle) . '">';
if($ogDesc  !== '') $T[] = '<meta name="twitter:description" content="' . e($ogDesc) . '">';
if($ogImage !== '') $T[] = '<meta name="twitter:image" content="' . e($ogImage) . '">';

/* --- GEO --- */
$geoRegion = p($P, 'geo_region');
$geoPlace  = p($P, 'geo_placename');
$geoPos    = p($P, 'geo_position');
if($geoRegion !== '') $T[] = '<meta name="geo.region" content="' . e($geoRegion) . '">';
if($geoPlace  !== '') $T[] = '<meta name="geo.placename" content="' . e($geoPlace) . '">';
if($geoPos    !== ''){
  $T[] = '<meta name="geo.position" content="' . e($geoPos) . '">';
  $T[] = '<meta name="ICBM" content="' . e(str_replace(';', ', ', $geoPos)) . '">';
}

/* ─── JSON-LD (AEO — what answer engines actually read) ──────────── */

/** Slashes stay escaped so a value can never break out of the script tag. */
function ld($data){
  $j = json_encode($data, JSON_UNESCAPED_UNICODE);
  if($j === false) return '';
  return '<script type="application/ld+json">' . $j . '</script>';
}

function postal_address($G, $street = '', $locality = '', $region = '', $postal = ''){
  return array(
    '@type'           => 'PostalAddress',
    'streetAddress'   => $street   !== '' ? $street   : g($G, 'org_street'),
    'addressLocality' => $locality !== '' ? $locality : g($G, 'org_locality'),
    'addressRegion'   => $region   !== '' ? $region   : g($G, 'org_region'),
    'postalCode'      => $postal   !== '' ? $postal   : g($G, 'org_postal'),
    'addressCountry'  => g($G, 'org_country', 'IN')
  );
}

$sameAs = array();
foreach(preg_split('/[\r\n,]+/', (string)g($G, 'org_sameas')) as $u){
  $u = trim($u);
  if($u !== '') $sameAs[] = $u;
}

$orgNode = array(
  '@type'    => 'EducationalOrganization',
  '@id'      => $base . '/#organization',
  'name'     => $siteName,
  'legalName'=> g($G, 'org_legal_name', $siteName),
  'url'      => $base . '/',
  'logo'     => abs_url($base, g($G, 'org_logo')),
  'address'  => postal_address($G)
);
if(g($G, 'org_phone') !== '') $orgNode['telephone'] = g($G, 'org_phone');
if(g($G, 'org_email') !== '') $orgNode['email']     = g($G, 'org_email');
if($sameAs)                   $orgNode['sameAs']    = $sameAs;

// Which schema to emit. 'auto' picks by URL shape; anything else is explicit.
$COURSE_PAGES = array('data-science','data-analytics','data-analytics-python','mis-reporting',
                      'power-bi','python','sql-server','tableau','advanced-excel','machine-learning');
$type = p($P, 'schema_type', 'auto');
if($type === 'auto'){
  if($post)                                  $type = 'Article';
  elseif($slug === 'index')                  $type = 'Organization';
  elseif(strpos($slug, 'branch-') === 0)     $type = 'LocalBusiness';
  elseif(in_array($slug, $COURSE_PAGES))     $type = 'Course';
  else                                       $type = 'WebPage';
}

$LD = array();

$rawSchema = p($P, 'schema_json');
if($rawSchema !== ''){
  // Hand-written JSON-LD wins outright, but only if it actually parses.
  $decoded = json_decode($rawSchema, true);
  if(is_array($decoded)) $LD[] = $decoded;
} elseif($type === 'Article' && $post){
  $LD[] = array(
    '@context'      => 'https://schema.org',
    '@type'         => 'BlogPosting',
    'headline'      => $post['title'],
    'description'   => isset($post['excerpt']) ? $post['excerpt'] : '',
    'image'         => $ogImage,
    'datePublished' => isset($post['date']) ? $post['date'] : '',
    'dateModified'  => isset($post['date']) ? $post['date'] : '',
    'author'        => array('@type' => 'Organization', 'name' => !empty($post['author']) ? $post['author'] : $siteName),
    'publisher'     => $orgNode,
    'mainEntityOfPage' => array('@type' => 'WebPage', '@id' => $canon)
  );
} elseif($type === 'Organization'){
  $node = array_merge(array('@context' => 'https://schema.org'), $orgNode);
  if($geoPos !== ''){
    $xy = explode(';', $geoPos);
    if(count($xy) === 2){
      $node['geo'] = array('@type' => 'GeoCoordinates',
        'latitude' => trim($xy[0]), 'longitude' => trim($xy[1]));
    }
  }
  $LD[] = $node;
} elseif($type === 'LocalBusiness'){
  $node = array(
    '@context'  => 'https://schema.org',
    '@type'     => 'EducationalOrganization',
    'name'      => $title !== '' ? $title : $siteName,
    'url'       => $canon,
    'logo'      => abs_url($base, g($G, 'org_logo')),
    'image'     => $ogImage,
    // geo.region is an ISO code like "IN-DL"; schema.org addressRegion wants just "DL".
    'address'   => postal_address($G, p($P, 'geo_street'), p($P, 'geo_locality', $geoPlace),
                                  preg_replace('/^[A-Za-z]{2}-/', '', $geoRegion), p($P, 'geo_postal')),
    'parentOrganization' => array('@type' => 'EducationalOrganization', '@id' => $base . '/#organization')
  );
  if(g($G, 'org_phone') !== '') $node['telephone'] = g($G, 'org_phone');
  if($geoPos !== ''){
    $xy = explode(';', $geoPos);
    if(count($xy) === 2){
      $node['geo'] = array('@type' => 'GeoCoordinates',
        'latitude' => trim($xy[0]), 'longitude' => trim($xy[1]));
    }
  }
  $LD[] = $node;
} elseif($type === 'Course'){
  $LD[] = array(
    '@context'    => 'https://schema.org',
    '@type'       => 'Course',
    'name'        => $title !== '' ? $title : $siteName,
    'description' => $desc,
    'url'         => $canon,
    'provider'    => $orgNode
  );
} elseif($type === 'WebPage'){
  $LD[] = array(
    '@context'    => 'https://schema.org',
    '@type'       => 'WebPage',
    'name'        => $title !== '' ? $title : $siteName,
    'description' => $desc,
    'url'         => $canon,
    'isPartOf'    => array('@type' => 'WebSite', 'name' => $siteName, 'url' => $base . '/'),
    'publisher'   => $orgNode
  );
}

/* --- speakable: tells voice/answer engines which text to read out --- */
$spk = array();
foreach(explode(',', (string)p($P, 'speakable')) as $sel){
  $sel = trim($sel);
  if($sel !== '') $spk[] = $sel;
}
if($spk && $LD){
  $LD[0]['speakable'] = array('@type' => 'SpeakableSpecification', 'cssSelector' => $spk);
}

/* --- FAQ: the single highest-value block for answer engines --- */
$faq = isset($P['faq']) && is_array($P['faq']) ? $P['faq'] : array();
$faqNodes = array();
foreach($faq as $row){
  if(!is_array($row)) continue;
  $q = isset($row['q']) ? trim((string)$row['q']) : '';
  $a = isset($row['a']) ? trim((string)$row['a']) : '';
  if($q === '' || $a === '') continue;
  $faqNodes[] = array(
    '@type' => 'Question', 'name' => $q,
    'acceptedAnswer' => array('@type' => 'Answer', 'text' => $a)
  );
}
if($faqNodes){
  $LD[] = array('@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqNodes);
}

/* --- breadcrumbs --- */
if($slug !== 'index' && $title !== ''){
  $crumbs = array(array('@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $base . '/'));
  if($post){
    $crumbs[] = array('@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => $base . '/blog');
    $crumbs[] = array('@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $canon);
  } else {
    $crumbs[] = array('@type' => 'ListItem', 'position' => 2, 'name' => $title, 'item' => $canon);
  }
  $LD[] = array('@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $crumbs);
}

foreach($LD as $node){
  $s = ld($node);
  if($s !== '') $T[] = $s;
}

/* ─── Inject into the page ───────────────────────────────────────── */

$html = $XL_RAW;

// Drop tags we are about to replace, so nothing is duplicated.
$html = preg_replace('/[ \t]*<meta\s+name=["\'](?:description|keywords|robots|geo\.region|geo\.placename|geo\.position|ICBM|twitter:[a-z:]+)["\'][^>]*>\s*/i', '', $html);
$html = preg_replace('/[ \t]*<meta\s+property=["\']og:[a-z:_]+["\'][^>]*>\s*/i', '', $html);
$html = preg_replace('/[ \t]*<link\s+rel=["\']canonical["\'][^>]*>\s*/i', '', $html);
if($html === null) $html = $XL_RAW;   // a regex failure must not lose the page

// Replace the page's <title>. substr_replace avoids all backreference escaping.
if($title !== '' && preg_match('/<title[^>]*>.*?<\/title>/is', $html, $m, PREG_OFFSET_CAPTURE)){
  $html = substr_replace($html, '<title>' . e($title) . '</title>', $m[0][1], strlen($m[0][0]));
}

// Insert the managed block immediately before </head>.
$block = "\n" . implode("\n", $T) . "\n";
$posHead = stripos($html, '</head>');
if($posHead !== false){
  $html = substr_replace($html, $block, $posHead, 0);
} else {
  $html = $XL_RAW;   // no <head> to inject into — serve the original
}

$XL_SENT = true;
ob_end_clean();
echo $html;
