<?php
/*
Plugin Name: i18n DeepL Page Translation
Description: Clones pages to a different language slug, while translating their content using DeepL
Version: 1.0.6
*/
 
# get correct id for plugin
$thisfile = basename(__FILE__, ".php");
$plugin_id = $thisfile;
$datasettingspath = GSDATAOTHERPATH . $plugin_id . '_settings.xml';



i18n_merge($plugin_id) || i18n_merge($plugin_id,'en_US');
 
register_plugin(
	$plugin_id, //Plugin id
	'i18n DeepL Page Translation', 	     //Plugin name
	'1.0.6', 		                     //Plugin version
	'Astrid Hanssen',                    //Plugin author
	'https://astrid-hanssen.de/',         //author website
	'Machine-translate page content and other fields using the DeepL API', //Plugin description
	'plugins',                            //pages type - on which admin tab to display
	'i18n_deepl_translation_do_dispatch'  //main function (administration)
);
 
if (substr(parse_url($_SERVER['PHP_SELF'],PHP_URL_PATH), -9) === '/edit.php') {
	add_action('pages-sidebar','createSideMenu',array('i18n_deepl_translation',ti18n_r('MENU_TRANSLATE_CURRENT_PAGE'),'page'));
	parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY)??"", $qs);
	if (isset($qs['id'])) {
		include(GSPLUGINPATH.'i18n_deepl_translation/translate_page_form.php');
		add_action('footer', 'prepare_translate_menu', array('i18n_deepl_translation', $qs['id'], GSDATAOTHERPATH.'i18n_deepl_translation_settings.xml'));
	}
}
add_action('plugins-sidebar','createSideMenu',array('i18n_deepl_translation', ti18n_r('MENU_CONFIGURE_PLUGIN'),'configure'));
 
// functions
 
function ti18n($text) {
	i18n('i18n_deepl_translation/'.$text);
}
 
function ti18n_r($text) {
	return i18n_r('i18n_deepl_translation/'.$text);
}
 
function i18n_deepl_translation_do_dispatch() {
	$plugin_id = $_GET['id'];
	$defaultsettingspath = GSPLUGINPATH.'i18n_deepl_translation/assets/defaultsettings.xml';
	$datasettingspath = GSDATAOTHERPATH.'i18n_deepl_translation_settings.xml';

	if (!file_exists($datasettingspath)) {
		  copy($defaultsettingspath, $datasettingspath);
	}


	require_once(GSPLUGINPATH.'i18n_deepl_translation/DeepL.class.php');

	$conf=getXML($datasettingspath);

	if( isset( $_GET[ 'page' ] ) ) {
		if (empty($conf->authkey)) {
			include(GSPLUGINPATH.'i18n_deepl_translation/settings.php');
		} else {
			include(GSPLUGINPATH.'i18n_deepl_translation/translate_page.php');
		}
	}
	else if (isset($_GET['configure'])) {
		include(GSPLUGINPATH.'i18n_deepl_translation/settings.php');
	}
	else {
		echo '<p>Translation plugin missing specific parameter.</p>';
	}
}

