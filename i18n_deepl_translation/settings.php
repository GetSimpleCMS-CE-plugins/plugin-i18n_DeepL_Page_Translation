<?php

require_once(GSPLUGINPATH.'i18n_deepl_translation/DeepL.class.php');


$error = null;
$xml = new SimpleXMLExtended($datasettingspath, 0, true);
$old_authkey = (string)$xml->authkey;

$default_language = (function_exists('return_i18n_default_language'))? 
	return_i18n_default_language() : 'en';
$default_postfix = '_'.$default_language;

$old_lang = [];
$languages = $xml->languages->language;
foreach($xml->languages->language as $lang) {
	$old_lang[''.$lang->cmd.''] = [];
	$old_lang[''.$lang->cmd.'']['menu'] = (string)$lang->menu;
	$old_lang[''.$lang->cmd.'']['postfix'] = (string)$lang->postfix;
	$old_lang[''.$lang->cmd.'']['active'] = (string)$lang->active;
}

if ((isset($_POST['submit-settings'])) && (isset($_POST['authkey']))) {
	unset($xml->authkey);
	$xml->addChild('authkey')->addCData($_POST['authkey'] ?: '');
}
$new_authkey = (string)$xml->authkey;

$status = DeepL::get_usage(''.$xml->authkey.'', $usage_array);

$status = DeepL::get_languages(''.$xml->authkey.'', $new_languages_array);

if ($status == 200) {

	unset($xml->languages);
	$languages = $xml->addChild('languages');
	foreach ($new_languages_array as $new_lang) {
		$language = $languages->addChild('language');
		$language->addChild('cmd')->addCData($new_lang['cmd']);
		$language->addChild('description')->addCData($new_lang['description']);
		/**/
		$cmd = ''.$language->cmd.'';
		if (isset($old_lang[$cmd])) {
			$language->addChild('menu')->addCData($old_lang[$cmd]['menu']);
			$language->addChild('postfix')->addCData($old_lang[$cmd]['postfix']);
			$language->addChild('active')->addCData($old_lang[$cmd]['active']);
		} else {
			$language->addChild('menu')->addCData($new_lang['menu']);
			$language->addChild('postfix')->addCData(($new_lang['postfix'] === $default_postfix)?
				'' : $new_lang['postfix']);
			$language->addChild('active')->addCData('Y');
		}
	}
	XMLsave($xml,$datasettingspath);

} else {

	$error = $status;
}


if (isset($_POST['submit-settings'])) {

// Get post parameters from settings form

	// split a comma-separated settings field into an array and back
	function reformat_settings_list(string $input) {
		preg_match_all( '/\s*("[^"]*(?:""[^"]*)*"|[^,]*)\s*,/u', $input.',', $matches ) ;	
		$fields = $matches[1];
		$values = [];
		foreach ($fields as $field) {
			if ( preg_match( '/^"(.*)"$/su', $field, $m ) ) {
				$field = strtr($m[1], array('""' => '"')) ;
			}
			$values[] = $field;
		}
		$output =[];
		foreach ( $values as $field ) {
			if (($field === null)||($field === '')) {
				continue;
			}
			if (preg_match( '/(?:,|"|\s)/u', $field ?: '' )) {
				$output[] = '"' . strtr($field, array('"' => '""')) . '"';
			}
			else {
				$output[] = $field;
			}
		}
	   return implode( ', ', $output );
	}


	unset($xml->authkey);
	$xml->addChild('authkey')->addCData(@$_POST['authkey'] ?: '');

	unset($xml->config);
	$config = $xml->addChild('config');
	$config->addChild('translate_fields')->addCData(reformat_settings_list(@$_POST['translate_fields'] ?: ''));
	$config->addChild('omit_translate')->addCData(reformat_settings_list(@$_POST['omit_translate'] ?: ''));
	$config->addChild('omit_placeholders')->addCData(@$_POST['omit_placeholders'] ?: '');
	$config->addChild('suggest_counter')->addCData(@$_POST['suggest_counter'] ?: '');

	unset($xml->deepl);
	$xdeepl = $xml->addChild('deepl');
	$xdeepl->addChild('tag_handling')->addCData(@$_POST['tag_handling'] ?: '');
	$xdeepl->addChild('ignore_tags')->addCData(reformat_settings_list(@$_POST['ignore_tags'] ?: ''));
	$xdeepl->addChild('split_sentences')->addCData(@$_POST['split_sentences'] ?: '');
	$xdeepl->addChild('outline_detection')->addCData(@$_POST['outline_detection'] ?: '');
	$xdeepl->addChild('preserve_formatting')->addCData(@$_POST['preserve_formatting'] ?: '');
	$xdeepl->addChild('formality')->addCData(@$_POST['formality'] ?: '');
	$xdeepl->addChild('context')->addCData(@$_POST['context'] ?: '');
	$xdeepl->addChild('splitting_tags')->addCData(reformat_settings_list(@$_POST['splitting_tags'] ?: ''));
	$xdeepl->addChild('non_splitting_tags')->addCData(reformat_settings_list(@$_POST['non_splitting_tags'] ?: ''));

	if ($old_authkey === $new_authkey) { 
		$post_langs = @$_POST['language'];
		unset($xml->languages);
		$languages = $xml->addChild('languages');
		foreach ($post_langs as $post_lang) {
			$language = $languages->addChild('language');
			$language->addChild('cmd')->addCData(@$post_lang['cmd'] ?: '');
			$language->addChild('description')->addCData(@$post_lang['description'] ?: '');
			$language->addChild('menu')->addCData(@$post_lang['menu'] ?: '');
			$language->addChild('postfix')->addCData(@$post_lang['postfix'] ?: '');
			$language->addChild('active')->addCData(@$post_lang['active'] ?: '');
		}
	}

    unset($_POST['submit-settings']);
    unset($_POST['authkey']);
	unset($_POST['tag_handling']);
	unset($_POST['split_sentences']);
	unset($_POST['outline_detection']);
	unset($_POST['splitting_tags']);
	unset($_POST['non_splitting_tags']);
	unset($_POST['ignore_tags']);
	unset($_POST['preserve_formatting']);
	unset($_POST['formality']);
	unset($_POST['context']);
	unset($_POST['translate_fields']);
	unset($_POST['omit_translate']);
	unset($_POST['omit_placeholders']);
	unset($_POST['suggest_counter']);
	unset($_POST['language']);

	XMLsave($xml,$datasettingspath);

}

echo '<br>';

// reload

$xml = getXML($datasettingspath);

// settings form

?>
<form method="POST">
<h3><?php ti18n('CONFIG_TITLE');?></h3>
<input type="text" name="tag_handling" value="<?php echo $xml->deepl->tag_handling;?>" hidden><table>
<tr>
    <td><label for="authkey"><?php ti18n('CONFIG_AUTHKEY'); ?></label></td>
    <td><input type="submit" class="submit" name="submit-settings" value="<?php i18n('BTN_SAVESETTINGS');?>" style="float:right; display:<?php if(($error == 403) || (strlen((string)$xml->authkey)<5)) echo 'block'; else echo 'none' ?>;">
	<input type="text" style="width: 20em;<?php if(($error == 403) || (strlen((string)$xml->authkey)<5)) echo 'border-color:#ff0000;border-style:solid;'; ?>" name="authkey" id="authkey" value="<?php echo $xml->authkey;?>"><?php if (isset($usage_array['USAGE_REMAINING'])) echo '<br><i>('.sprintf(ti18n_r('USAGE_REMAINING'),$usage_array['USAGE_REMAINING']).')</i>';?></td>
</tr>
<tr>
    <td><label for="translate_fields"><?php ti18n('CONFIG_TRANSLATEFLDS'); ?></label></td>
    <td><textarea name="translate_fields" id="translate_fields" rows="3" cols="40" style="height:4em;width:40em;padding:2px;"><?php echo $xml->config->translate_fields;?></textarea></td>
</tr>
<tr>
    <td><label for="ignore_tags"><?php ti18n('CONFIG_IGNORE_TAGS'); ?></label></td>
    <td><textarea name="ignore_tags" id="ignore_tags" rows="3" cols="40" style="height:4em;width:40em;padding:2px;"><?php echo $xml->deepl->ignore_tags;?></textarea></td>
</tr>
<tr>
    <td><label for="omit_translate"><?php ti18n('CONFIG_OMIT_TRANSLATE'); ?></label></td>
    <td><textarea name="omit_translate" id="omit_translate" rows="3" cols="40" style="height:4em;width:40em;padding:2px;"><?php echo $xml->config->omit_translate;?></textarea></td>
</tr>
<tr>
    <td><label for="omit_placeholders"><?php ti18n('CONFIG_PLACEHOLDERS_HANDLING'); ?></label></td>
    <td><label for="omit_placeholders" style="display:inline;"><?php ti18n('CONFIG_OMIT_PLACEHOLDERS'); ?></label> <input type="checkbox" name="omit_placeholders" id="omit_placeholders" value="Y"<?php if(!empty($xml->config->omit_placeholders)) echo " checked";?>></td>
</tr>
<tr>
    <td><label for="formality"><?php ti18n('CONFIG_FORMALITY'); ?></label></td>
    <td>
	<label for="formality_default" style="display:inline;"><?php ti18n('CONFIG_FORMALITY_DEFAULT'); ?></label>&nbsp;<input type="radio" name="formality" id="formality_default" value=""<?php if(empty($xml->deepl->formality)) echo " checked";?>>
	&nbsp;&nbsp;<label for="formality_more" style="display:inline;"><?php ti18n('CONFIG_FORMALITY_MORE'); ?></label>&nbsp;<input type="radio" name="formality" id="formality_more" value="more"<?php if(''.$xml->deepl->formality.'' === 'more') echo " checked";?>>
	&nbsp;&nbsp;<label for="formality_less" style="display:inline;"><?php ti18n('CONFIG_FORMALITY_LESS'); ?></label>&nbsp;<input type="radio" name="formality" id="formality_less" value="less"<?php if(''.$xml->deepl->formality.'' === 'less') echo " checked";?>>
	</td>
</tr>
<tr>
    <td><label for="context"><?php ti18n('CONFIG_CONTEXT'); ?></label></td>
    <td><textarea name="context" id="context" rows="4" cols="40" style="height:5em;width:40em;padding:2px;"><?php echo $xml->deepl->context;?></textarea></td>
</tr>
</table>

<p></p><h3 class="deepl_extended"><?php ti18n('CONFIG_EXTENDED');?></h3>
<button type="button" id="show_extended" name="show_extended"><?php ti18n('CONFIG_BTN_SHOWEXT'); ?></button>
<p class="deepl_extended"></p>
<table class="deepl_extended">
<tr>
    <td><label for="suggest_counter"><?php ti18n('CONFIG_NEWSLUG_HANDLING'); ?></label></td>
    <td><label for="suggest_counter" style="display:inline;"><?php ti18n('CONFIG_SUGGEST_COUNTER'); ?></label> <input type="checkbox" name="suggest_counter" id="suggest_counter" value="Y"<?php if(!empty($xml->config->suggest_counter)) echo " checked";?>></td>
</tr>
<tr>
    <td><label for="split_sentences"><?php ti18n('CONFIG_SPLIT_SENTENCES'); ?></label></td>
    <td>
	<label for="split_sentences_0" style="display:inline;"><?php ti18n('CONFIG_SPLIT_SENTENCES_0'); ?></label>&nbsp;<input type="radio" name="split_sentences" id="split_sentences_0" value="0"<?php if(''.$xml->deepl->split_sentences.'' === '0') echo " checked";?>>
	&nbsp;&nbsp;<label for="split_sentences_nonewlines" style="display:inline;"><?php ti18n('CONFIG_SPLIT_SENTENCES_NONEWLINES'); ?></label>&nbsp;<input type="radio" name="split_sentences" id="split_sentences_nonewlines" value="nonewlines"<?php if(''.$xml->deepl->split_sentences.'' === 'nonewlines') echo " checked";?>>
	&nbsp;&nbsp;<label for="split_sentences_1" style="display:inline;"><?php ti18n('CONFIG_SPLIT_SENTENCES_1'); ?></label>&nbsp;<input type="radio" name="split_sentences" id="split_sentences_1" value=""<?php if(empty($xml->deepl->split_sentences)) echo " checked";?>>

	</td>
</tr>
<tr>
    <td><label for="preserve_formatting"><?php ti18n('CONFIG_PRESERVE_FORMATTING'); ?></label></td>
    <td><input type="checkbox" name="preserve_formatting" id="preserve_formatting" value="1"<?php if(!empty($xml->deepl->preserve_formatting)) echo " checked";?>></td>
</tr>
<tr>
    <td><label for="outline_detection"><?php ti18n('CONFIG_OUTLINE_DETECTION'); ?></label></td>
    <td><input type="checkbox" name="outline_detection" id="outline_detection" value="0"<?php if(!empty($xml->deepl->outline_detection)) echo " checked";?>></td>
</tr>
<tr>
    <td><label for="splitting_tags"><?php ti18n('CONFIG_SPLITTING_TAGS'); ?></label></td>
    <td><textarea name="splitting_tags" id="splitting_tags" rows="3" cols="40" style="height:4em;width:40em;padding:2px;"><?php echo $xml->deepl->splitting_tags;?></textarea></td>
</tr>
<tr>
    <td><label for="non_splitting_tags"><?php ti18n('CONFIG_NON_SPLITTING_TAGS'); ?></label></td>
    <td><textarea name="non_splitting_tags" id="non_splitting_tags" rows="3" cols="40" style="height:4em;width:40em;padding:2px;"><?php echo $xml->deepl->non_splitting_tags;?></textarea></td>
</tr>
</table>
<p></p><h3><?php ti18n('CONFIG_LANGUAGES');?></h3>
<?php $num_langs = $xml->languages->language->count(); 
	if ($num_langs == 0) {
		echo ti18n_r('CONFIG_LANGUAGES_MISSING');
	} else {
		echo '<p><h4><b>'.sprintf(ti18n_r('CONFIG_LANGUAGE_DETAILS'),return_i18n_default_language()).'</b></h4></p>';
	}
?>
<table id="lang-table">
    <tr>
        <th><?php ti18n('CONFIG_LANGDESCR');?></th>
        <th><?php ti18n('CONFIG_LANGMENU');?></th>
        <th><?php ti18n('CONFIG_LANGCMD');?></th>
        <th><?php ti18n('CONFIG_LANGPOSTFIX');?></th>
        <th><?php ti18n('CONFIG_LANGACTIVE');?></th>
    </tr>
    <?php foreach($xml->languages->language as $lang): ?>
        <tr>
            <td><label for="<?php echo 'language[\''.$lang->cmd.'\']';?>[menu]"><?php echo $lang->description;?></label><input type="text" name="<?php echo 'language[\''.$lang->cmd.'\']';?>[description]" value="<?php echo $lang->description;?>" hidden></td>
            <td><input type="text" class="text" style="width: 150px;" name="<?php echo 'language[\''.$lang->cmd.'\']';?>[menu]" id="<?php echo 'language[\''.$lang->cmd.'\']';?>[menu]" value="<?php echo $lang->menu;?>"></td>
            <td><label for="<?php echo 'language[\''.$lang->cmd.'\']';?>[postfix]"><?php echo $lang->cmd;?></label><input type="text" name="<?php echo 'language[\''.$lang->cmd.'\']';?>[cmd]" value="<?php echo $lang->cmd;?>" hidden></td>
            <td><input type="text" class="text deepl_slug_postfix" style="width: 50px;" name="<?php echo 'language[\''.$lang->cmd.'\']';?>[postfix]" id="<?php echo 'language[\''.$lang->cmd.'\']';?>[postfix]" value="<?php echo $lang->postfix;?>"></td>
            <td><input type="checkbox" name="<?php echo 'language[\''.$lang->cmd.'\']';?>[active]" id="<?php echo 'language[\''.$lang->cmd.'\']';?>[active]" value="Y"<?php if(!empty((string)$lang->active)) echo " checked";?>></td>
        </tr>
    <?php endforeach;?>
</table>
<input type="submit" class="submit" name="submit-settings" value="<?php i18n('BTN_SAVESETTINGS');?>" style="display:block;">
</form>
<style>
	.deepl_extended {
		display:none;
	}
</style>
<script>
	$(".deepl_slug_postfix").bind("propertychange change click keyup input paste", function() {
		$(this).val($(this).val().replace(/\s/g,''));
		slugpostfix = $(this).val();
		if ((slugpostfix.length > 0) && (slugpostfix.indexOf("_") != 0)) {
			$(this).val('_'+slugpostfix);
		}
	});
	$("#show_extended").click(function() {
		if ($(this).text() == "<?php ti18n('CONFIG_BTN_SHOWEXT');?>") {
			$(this).text("<?php ti18n('CONFIG_BTN_HIDEEXT');?>");
			$(".deepl_extended").css("display","block");
			$("h3.deepl_extended").css("display","inline");
		} else {
			$(this).text("<?php ti18n('CONFIG_BTN_SHOWEXT');?>");
			$(".deepl_extended").css("display","none");
		}
    });
</script>

