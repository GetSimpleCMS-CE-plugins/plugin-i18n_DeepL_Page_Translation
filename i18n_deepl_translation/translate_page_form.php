<?php

require_once(GSPLUGINPATH.'i18n_deepl_translation/DeepL.class.php');

function prepare_translate_menu($plugin_id, $current_page, $datasettingspath) {
	//return;
	//global $datasettingspath;
	if (!defined('GSADMIN')) define('GSADMIN', 'admin');

	if (isset($_GET['translate_err'])) {
		echo '<div id="'.$plugin_id.'-error" style="position:fixed;left:0;top:0;width:100%;height:100%;z-index:1000;background-color:rgba(200,200,200,.6);display:block;">';
		echo '<div style="position:relative;width:30em; margin-left:auto;margin-right:auto;top:50%;transform:translateY(-50%);border-color:rgba(120,120,120);background-color:#ffffff;border-style:solid;border-width:1px;border-radius:0.4em;padding:1em;">';
		echo '<center><h3>'.ti18n_r('TRANSLATION_ERROR').'</h3><big>'.$_GET['translate_err'].'</big></center></div></div>';
	}

	$xml = getXml($datasettingspath);
	//$status = DeepL::get_usage(''.$xml->authkey.'', $usage_array);

	$title = returnPageContent($current_page, 'title');
	$_pos = mb_strpos($current_page, '_');
	$page_root = ($_pos !== false)? substr($current_page, 0, $_pos) : $current_page;
	if ($page_root === '') return;

    echo '<div id="'.$plugin_id.'-wrap" style="position:fixed;left:0;top:0;width:100%;height:100%;z-index:1000;background-color:rgba(200,200,200,.6);display:none;">';
    echo '<div id="'.$plugin_id.'-menu" style="position:relative;width:30em; margin-left:auto;margin-right:auto;top:50%;transform:translateY(-50%);border-color:rgba(120,120,120);background-color:#ffffff;border-style:solid;border-width:1px;border-radius:0.4em;padding:1em;padding-left:2em;">';
	echo '<center><h3>'.ti18n_r('TRANSLATE_HEADLINE').'</h3></center><h4><big><b>'.$title.'</b></big></h4>';
	echo '<form method="POST" action = "/'.GSADMIN.'/load.php?id='.$plugin_id.'&page=1">';
	?>
	<input name="deepl_old_slug" id="deepl_old_slug" type="hidden" value="<?php echo $current_page;?>">
	<input name="deepl_default_lang" id="deepl_default_lang" type="hidden" value="">
	<br><p><label for="deepl_dest_lang"><?php ti18n('TRANSLATE_PULLDOWN'); ?>: </label>
	<select name="deepl_dest_lang" id="deepl_dest_lang" style="width:20em;top:5em;margin-left:auto;margin-right:auto;">
	<option value="" data-slug="" selected="selected">   </option>
    <?php foreach($xml->languages->language as $lang): if ((string)$lang->active === 'Y'):
	$menuopt = ((string)$lang->postfix === '')? '  ('.ti18n_r('TRANSLATE_DEFAULTLANG').')' : ''; ?>
	<option value="<?php echo $lang->cmd;?>" data-slug="<?php echo $page_root.$lang->postfix;?>"><?php echo $lang->menu.$menuopt;?></option>
	<?php endif; endforeach;?>
	</select></p>
	<p><label for="deepl_new_slug"><?php ti18n('TRANSLATE_NEW_SLUG'); ?>: </label>
	<input name="deepl_new_slug" id="deepl_new_slug" type="text" value="" style="background-color:#ffffff;" readonly="readonly"> <span id="deepl_thispage_span"></span></p>
	<p><label for="deepl_add_counter" style="display:inline;"><?php ti18n('TRANSLATE_ADD_COUNTER'); ?></label>
	<input name="deepl_add_counter" id="deepl_add_counter" type="checkbox" value="Y"<?php if (!empty((string)$xml->config->suggest_counter)) echo " checked";?>></p>
	<div style="padding: 1em;">
	<input type="submit" class="submit" name="submit-translate-page" id="submit-translate-page" value="<?php echo ti18n('TRANSLATE_SUBMIT'); ?>" style="display:inline;" disabled="disabled"> 
	</div>
	<br><i><span id="deepl_remaining">(0)</span></i>
	<?php
	echo '</form>';
	echo '</div></div>';
	echo '<style>
	.submit:disabled, button:disabled, input:disabled {
		color: #808080 !important;
	}
	#deepl_new_slug[readonly], #deepl_dest_lang {
		padding: 2px;
		padding-left: 4px;
		background-color: #ffffff;
	}
	</style>';
	echo '<script>
	function deepl_show_translate_menu(disp) {
		if (disp) {
			$( "#'.$plugin_id.'-wrap" ).css("display","block");
			$("#deepl_new_slug").val($($("#deepl_dest_lang")).find(":selected").attr("data-slug"));
			$.getJSON("'.DeepL::get_api_url((string)$xml->authkey).'usage?auth_key='.(string)$xml->authkey.'", function(data){
				var remaining = (data.character_limit-data.character_count);
				remaining = "'.ti18n_r('USAGE_REMAINING').'".replace("%d", remaining);
				 $("#deepl_remaining").html("("+remaining+")");
             });
		} else {
			$( "#'.$plugin_id.'-wrap" ).css("display","none");
		}
		$("#sb_'.$plugin_id.' a").toggleClass("current");
		$("#sb_pageedit a").toggleClass("current");
	}

	$( "#'.$plugin_id.'-error" ).click(function(e) {
		e.stopPropagation();
		$(this).css("display","none");
		$(this).unbind("click");
    });
	$( "#'.$plugin_id.'-wrap" ).click(function() {
		deepl_show_translate_menu(false);
    });
	$( "#'.$plugin_id.'-menu" ).click(function(e) {
		e.stopPropagation();
    });

	$("#deepl_dest_lang").change(function() {
		newslug = $(this).find(":selected").attr("data-slug");
		$("#deepl_default_lang").val((newslug.indexOf("_")>0)? "" : "Y");
		$("#deepl_thispage_span").text((newslug == "'.$current_page.'")? "('.ti18n_r('TRANSLATE_INPLACE').')" : ""); 

		$("#deepl_new_slug").val(newslug);
		if ($("#deepl_new_slug").val() == "") {
			$("#submit-translate-page").css("color","#808080").prop("disabled", true);
		} else {
			$("#submit-translate-page").css("color","#000000").prop("disabled", false);
		}
	});

	$(document).ready(function() {
		$("#'.$plugin_id.'-error").insertAfter($("#editform"));
		$("#'.$plugin_id.'-wrap").insertAfter($("#editform"));
		$("#sb_'.$plugin_id.' a").attr("href", "#");
		$("#sb_'.$plugin_id.'").click(function() {
			deepl_show_translate_menu(true);
		}); 
		$("#dropdown ul.dropdownmenu li:eq(1)").after("<li id=\'deepl_edit_dropdown\'><a href=\'#\'>'.ti18n_r('TRANSLATE_OTHERMENU').'</a></li>");
		$( "#deepl_edit_dropdown a" ).click(function() {
			deepl_show_translate_menu(true);
		});
		if ($("#deepl_new_slug").val() == "") {
			$("#submit-translate-page").css("color","#808080").prop("disabled", true);
		} else {
			$("#submit-translate-page").css("color","#000000").prop("disabled", false);
		}
	}); 
</script>';

}