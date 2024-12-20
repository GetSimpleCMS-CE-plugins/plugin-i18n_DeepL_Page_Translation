<?php
/*
- invoke getXML on each modified page,
- modify the xml data,
- save it with XMLsave
- when done for each file, call create_pagesxml to recreate the pages.xml file
*/

if (!defined('GSADMIN')) define('GSADMIN', 'admin');
require_once(GSADMINPATH.'inc/common.php');

require_once(GSPLUGINPATH.'i18n_deepl_translation/DeepL.class.php');

global $USR, $reservedSlugs;

if (!isset($_POST['submit-translate-page'])) {
	redirect('/');
	die();
}

$new_slug = @$_POST['deepl_new_slug'] ?: '';
$old_slug = @$_POST['deepl_old_slug'] ?: '';
$base_slug = (strpos($new_slug,'_') > 0)? substr($new_slug, 0, strrpos($new_slug,'_')) : $new_slug;

$cmd = @$_POST['deepl_dest_lang'] ?: '';
$add_counter = (!empty(@$_POST['deepl_add_counter'] ?: '')) ? true : false;
if (($new_slug === '') || ($cmd === '')) {
	redirect('/'.GSADMIN.'/edit.php?id='.$old_slug);
	die();
}

$old_path = GSDATAPAGESPATH.$old_slug.".xml";
$new_path = GSDATAPAGESPATH.$new_slug.".xml";
if (file_exists($old_path)) {
	$oldpage = new SimpleXMLExtended($old_path, 0, true);
} else {
	redirect('/'.GSADMIN.'/edit.php?id='.$old_slug);
	die();
}


$defaultfields = [];
// target exists already, so untranslated values can be preserved
if (file_exists($new_path)) {
	$newpage = new SimpleXMLExtended($new_path, 0, true);
	$defaultfields['menuStatus'] = (string)$newpage->menuStatus ?: '';
	$defaultfields['menuOrder'] = (string)$newpage->menuOrder ?: '';
	$defaultfields['parent'] = (string)$newpage->parent ?: '';
	$defaultfields['private'] = (string)$newpage->private ?: '';
} else if ($new_slug == $base_slug) {
	// target is the base (default) page but doesnt exist already
	// so oldpage is an existing language page
	$defaultfields['menuStatus'] = (string)$oldpage->menuStatus ?: '';
	$defaultfields['menuOrder'] = (empty($oldpage->menuOrder))? '99': $oldpage->menuOrder;
	$defaultfields['parent'] = (string)$oldpage->parent ?: '';
	$defaultfields['private'] = (string)$oldpage->private ?: '';
} else {
	// target is a new language page and doesnt exist
	$defaultfields['menuStatus'] = (string)$oldpage->menuStatus ?: '';
	$defaultfields['menuOrder'] = '';
	$defaultfields['parent'] = '';
	$defaultfields['private'] = '';
}

// some fields must always be copied from old page
$defaultfields['template'] = (string)$oldpage->template ?: '';
$defaultfields['author'] = (string)$oldpage->author ?: '';
$defaultfields['creDate'] = (string)$oldpage->creDate ?: '';
$defaultfields['user'] = (string)$USR ?: '';
$defaultfields['pubDate'] = date('r');

if ( ((file_exists($new_path)) && ($add_counter)) || (in_array($new_slug, $reservedSlugs)) ) {
	$count = 0;
	do {
		$count++;
		$new_slug_count = $new_slug .'-'.$count;
		$new_path_count = GSDATAPAGESPATH . $new_slug_count.'.xml';
	} while (file_exists($new_path_count));

	$new_slug = $new_slug_count;
	$new_path = $new_path_count;
}
$defaultfields['url'] = $new_slug;

if ( file_exists($new_path) ) { // happens only when no add_counter
	$bak_path = GSBACKUPSPATH."pages/". $new_slug .".bak.xml";
	copy($new_path, $bak_path);
}

$settings = getXml($datasettingspath);
$settings = json_decode( json_encode($settings), true); // makes an array of settings
foreach ($settings['config'] as $key => $value) {
	if ((is_array($value)) && (empty($value))) {
		$settings['config'][$key] = '';
	}
}
foreach ($settings['deepl'] as $key => $value) {
	if ((is_array($value)) && (empty($value))) {
		$settings['deepl'][$key] = '';
	}
	$settings['deepl'][$key] = preg_replace('/\s/u','', $settings['deepl'][$key]); // compress
}
$translatelist = explode(',', preg_replace('/\s/u','', $settings['config']['translate_fields'])); // compress
$translatefields = [];

foreach($oldpage->children() as $key=>$value) {
	if (!array_key_exists((string)$key, $defaultfields)) {
		if (!in_array((string)$key, $translatelist)) {
			$defaultfields[(string)$key] = (string)$value;
		} else {
			$translatefields[(string)$key] = htmlspecialchars_decode((string)$value);
		}
	}
}

$status = DeepL::translate($settings['authkey'], $_POST['deepl_dest_lang'], $settings, $translatefields, $translated_array);
if ($status !== 200) {
	//echo '<br><pre>'.print_r($translated_array, true).'</pre><br>';
	//$errormsg = urlencode(implode('<br>', $translated_array));
	redirect('/'.GSADMIN.'/edit.php?id='.$old_slug.'&translate_err='.urlencode(implode('<br>', $translated_array)));

}

if ($status == 200) {

foreach($translated_array as $key=>$text) {
	$translated_array[$key] = htmlspecialchars($text);
}

$new_page = new SimpleXMLExtended('<?xml version="1.0" encoding="UTF-8"?><item></item>');
foreach ($defaultfields as $field=>$value) {
	$new_page->addChild($field)->addCData($value);
}
foreach ($translated_array as $field=>$value) {
	$new_page->addChild($field)->addCData($value);
}

if (! XMLsave($new_page, $new_path) ) {
	$kill = i18n_r('CHMOD_ERROR');
}

if(create_pagesxml(true)) getPagesXmlValues(false);
i18n_clear_cache();
return_i18n_pages();
redirect('/'.GSADMIN.'/edit.php?id='.$new_slug);

} // status == 200
