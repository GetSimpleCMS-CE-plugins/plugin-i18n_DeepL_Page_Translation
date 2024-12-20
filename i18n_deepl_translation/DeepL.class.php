<?php


class DeepL {

public static function get_api_url($auth_key) {
	if ((!empty($auth_key)) && (substr($auth_key, -3) === ':fx')) {
		return 'https://api-free.deepl.com/v2/';
	}
	return 'https://api.deepl.com/v2/';
}

public static function is_free_api($auth_key) {
	if ((!empty($auth_key)) && (substr($auth_key, -3) === ':fx')) {
		return true;
	}
	return false;
}


public static function get_languages($auth_key, &$language_array = null) {
	$url = self::get_api_url($auth_key).'languages?type=target';

	$options = array(
		'http' => array(
			'method'  => 'POST',
			'header'  => array('Authorization: DeepL-Auth-Key '.$auth_key,
								'User-Agent: GetSimple DeepL Translator Plugin',
								'Accept: *'.'/'.'*'
						)
		)
	);
	$context  = stream_context_create($options);
	$lang_array = json_decode(file_get_contents($url, false, $context),true);
	$statuscode = intval(preg_replace('{HTTP\/\S*\s(\d{3}).*}', '${1}', $http_response_header[0]));

	$language_array = [];
	foreach($lang_array as $lang) {
		$language = [];
		$language['cmd'] = $lang['language'];
		$language['description'] = $lang['name'];
		$language['menu'] = $lang['name'];
		$language['postfix'] = '_'.mb_strtolower($lang['language']);
		$hyphenpos = mb_strpos($language['postfix'], '-', 0);
		if (!empty($hyphenpos)) {
			//$language['postfix'] = mb_substr($language['postfix'], 0, $hyphenpos).mb_strtoupper(mb_substr($language['postfix'], $hyphenpos));
			$language['postfix'] = mb_substr($language['postfix'], 0, $hyphenpos);
		}
		$language_array[] = $language;
	}
	return $statuscode;
}


public static function get_usage($auth_key, &$usage_array = null) {
	$url = self::get_api_url($auth_key).'usage';

	$options = array(
		'http' => array(
			'method'  => 'POST',
			'header'  => array('Authorization: DeepL-Auth-Key '.$auth_key,
								'User-Agent: GetSimple DeepL Translator Plugin',
								'Accept: *'.'/'.'*'
						)
		)
	);
	$context  = stream_context_create($options);
	$usage_arr = json_decode(file_get_contents($url, false, $context),true);
	$statuscode = intval(preg_replace('{HTTP\/\S*\s(\d{3}).*}', '${1}', $http_response_header[0]));
	if ($statuscode == 200) {
		$usage_array = [];
		$usage_array['USAGE_COUNT'] = $usage_arr['character_count'];
		$usage_array['USAGE_LIMIT'] = $usage_arr['character_limit'];
		$usage_array['USAGE_REMAINING'] = $usage_array['USAGE_LIMIT'] - $usage_array['USAGE_COUNT'];
	}
	return $statuscode;
}


public static function error_description($err) {
	$deepl_errors = [
		400 => 'DEEPL_ERR_400',
		403 => 'DEEPL_ERR_403',
		404 => 'DEEPL_ERR_404',
		413 => 'DEEPL_ERR_413',
		414 => 'DEEPL_ERR_414',
		429 => 'DEEPL_ERR_429',
		456 => 'DEEPL_ERR_456',
		503 => 'DEEPL_ERR_503',
		529 => 'DEEPL_ERR_529'
	];
	if (array_key_exists($err, $deepl_errors)) {
		return ti18n_r('DEEPL_ERR_'.$err.'');
	} else if ($err >= 500) {
		return ti18n_r('DEEPL_ERR_INTERNAL').' ('.$err.').';
	} else {
		return ti18n_r('DEEPL_ERR_OTHER').' ('.$err.').';
	}
}


public static function translate($auth_key, $dest_lang, $settings, $fields_arr, &$translated_array = null) {
	$url =self::get_api_url($auth_key).'translate';
	$fieldwrap_tag = '_@w';
	$fieldname_tag = '_@n';
	$fieldtext_tag = '_@t';
	$ignore_tag    = '_@i';
	$settings['deepl']['ignore_tags'] .= ','.$fieldname_tag.','.$ignore_tag;
	$settings['deepl']['splitting_tags'] .= ','.$fieldwrap_tag.','.$fieldname_tag.','.$fieldtext_tag;
	$settings['deepl']['non_splitting_tags'] .= ','.$ignore_tag;
	$fieldwrap_start = '<'.$fieldwrap_tag.'>';
	$fieldwrap_stop = '</'.$fieldwrap_tag.'>';
	$fieldname_start = '<'.$fieldname_tag.'>';
	$fieldname_stop = '</'.$fieldname_tag.'>';
	$fieldtext_start = '<'.$fieldtext_tag.'>';
	$fieldtext_stop = '</'.$fieldtext_tag.'>';
	$ignore_start = '<'.$ignore_tag.'>';
	$ignore_stop = '</'.$ignore_tag.'>';

	$ignorewords = self::config_explode($settings['config']['omit_translate']);
	$ignorewords_tagged = [];
	foreach ($ignorewords as $ignoreword) {
		$ignorewords_tagged[$ignoreword] = $ignore_start.$ignoreword.$ignore_stop;
	}

	$text='';
	foreach ($fields_arr as $fieldname => $fieldtext) {
		$fieldtext = strtr($fieldtext, $ignorewords_tagged);
		if ($settings['config']['omit_placeholders'] === 'Y') {
			$fieldtext = preg_replace('/(\(%.+?%\)|\{%.+?%\}|%[^ \t\r\n%]++%)/u', $ignore_start.'\1'.$ignore_stop, $fieldtext);
		}
		$text .= $fieldwrap_start.$fieldname_start.$fieldname.$fieldname_stop.$fieldtext_start.$fieldtext.$fieldtext_stop.$fieldwrap_stop;
	}
	$data = [];
	$data['text']         = $text;
	$data['target_lang']  = $dest_lang;
	$data['model_type'] = 'prefer_quality_optimized';
	foreach($settings['deepl'] as $param => $setting) {
		if ($setting != '') {

			if ($param == 'formality') {
				// new: always using prefer_more, prefer_less prevents from throwing 400
				switch($setting) {
					case 'more': 
					case 'less':
						$setting = 'prefer_'.$setting;
						break;
					default: break;
				}
			}

			$data[$param] = $setting;
		}
	}
	$data = http_build_query($data);

	$options = array(
		'http' => array(
			'method'  => 'POST',
			'header'  => array('Content-type: application/x-www-form-urlencoded',
								'Authorization: DeepL-Auth-Key '.$auth_key,
								'User-Agent: GetSimple DeepL Translator Plugin',
								'Accept: *'.'/'.'*',
								'Content-Length: ' . strlen($data)					
						),
			'content' => $data
		)
	);
	$context  = stream_context_create($options);
	$response_arr = json_decode(@file_get_contents($url, false, $context),true);
	$statuscode = intval(preg_replace('{HTTP\/\S*\s(\d{3}).*}', '${1}', $http_response_header[0]));
	$translated_array = [];
	if ($statuscode == 200) {
		$responsetext = $response_arr['translations'][0]['text'];
		$text_arr = explode($fieldwrap_start, $responsetext);
		foreach ($text_arr as $textfield) if ( $textfield !== '') {
			$textfield = strtr($textfield, [$fieldwrap_start => '', $fieldwrap_stop => '']);
			$t2 = explode($fieldname_stop, $textfield);
			$t2[0] = strtr($t2[0], array($fieldname_start => '', $fieldname_stop => ''));
			$t2[1] = strtr($t2[1], array($fieldtext_start => '', $fieldtext_stop => ''));

			// 2020-08-29: deepl "eats" necessary spaces directly around "ignore"-tagged text.
			// So in case of an adjacent word, add a space in between. Not always perfect, but probably better.
			$ignoretags = self::config_explode($settings['deepl']['ignore_tags']);
			foreach ($ignoretags as $ignoretag) {
				$ign_start = '<'.$ignoretag.'>';
				$ign_stop = '</'.$ignoretag.'>';
				$t2[1] = preg_replace('|(\w)'.preg_quote($ign_start,'|').'|u', '${1} '.$ign_start, $t2[1]);
				$t2[1] = preg_replace('|'.preg_quote($ign_stop,'|').'(\w)|u', $ign_stop.' ${1}', $t2[1]);
			}

			$t2[1] = strtr($t2[1], array($ignore_start => '', $ignore_stop => ''));
			$translated_array[$t2[0]] = $t2[1];
		}
	} else {
		$translated_array['error'] = ''.$statuscode.': '.deepl_error_description($statuscode);
		$translated_array['message'] = ($responsetext['message'])?: ti18n_r('DEEPL_ERR_NOMESSAGE');
	}
	return $statuscode;
}

// split a comma-separated settings field into an array
private static function config_explode( string $input) {

	preg_match_all( '/\s*("[^"]*(?:""[^"]*)*"|[^,]*)\s*,/u', $input.',', $matches ) ;	
	$fields = $matches[1];

	$values = [];
	foreach ($fields as $field) {
		if ( preg_match( '/^"(.*)"$/su', $field, $m ) ) {
			$field = strtr($m[1], array('""' => '"')) ;
		}
		$values[] = $field;
	}
	
	return $values;
}


} // DeepL class


