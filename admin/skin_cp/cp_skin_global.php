<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Admin CP global skin templates
 * Last Updated: $Date: 2012-06-06 12:23:15 -0400 (Wed, 06 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: AndyMillne $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10877 $
 * @since		3.0.0
 *
 */
 
class cp_skin_global extends output
{

/**
 * Prevent our main destructor being called by this class
 *
 * @access	public
 * @return	@e void
 */
public function __destruct()
{
}

/**
 * Redirector page
 *
 * @param	string	$url	URL to send to
 * @param	string	$text	Text to display
 * @return	@e string
 */
public function temporaryRedirect( $url, $text, $time=2 )
{

$time = floatval( $time ) * 1000;

$IPBHTML = "";
//--starthtml--//

$url	= str_replace( '&amp;', '&', $url );

$IPBHTML .= <<<EOF
<div class='information-box'>{$text}</div>
<script type='text/javascript'>
jQ(document).ready(function() {
	setTimeout( "window.location = '{$url}';", {$time} );
});
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// <ips:ips_editor:desc::trigger:>
//===========================================================================
function editor($formField='post', $content='', $options=array(), $autoSaveData=array() ) {
$IPBHTML = "";
//--starthtml--//

$options['type'] = ( $options['type'] ) ? $options['type'] : "''";
$options['minimize']       = intval( $options['minimize'] );
$options['smilies']        = IPSText::jsonEncodeForTemplate( is_array($options['smilies']) ? $options['smilies'] : array() );
$options['noSmilies']	   = intval( $options['noSmilies'] );
$bbcode = IPSLib::fetchBbcodeAsJson();

$defaultSkin = $this->registry->output->_fetchSkinByDefault();

if ( ! $this->_editorJsLoaded )
{
	$this->_editorJsLoaded = true;
	
	if ( defined("CK_LOAD_SOURCE") AND CK_LOAD_SOURCE )
	{
		$IPBHTML .= <<<EOF
		<script type="text/javascript" src="{$this->settings['public_dir']}js/3rd_party/ckeditor/ckeditor_source.js"></script>
EOF;
	}
	else
	{
		$IPBHTML .= <<<EOF
		<script type="text/javascript" src="{$this->settings['public_dir']}js/3rd_party/ckeditor/ckeditor.js"></script>
EOF;
	}
	
	$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->settings['cache_dir']}lang_cache/{$this->lang->lang_id}/ipb.lang.js' charset='{$this->settings['gb_char_set']}'></script>
<script type="text/javascript" src="{$this->settings['public_dir']}js/ips.textEditor.js"></script>
<script type="text/javascript">
	/* Dynamic items */
	CKEDITOR.config.IPS_BBCODE          = {$bbcode};
	CKEDITOR.config.IPS_BBCODE_IMG_URL  = "{$this->settings['public_dir']}style_extra/bbcode_icons";
	CKEDITOR.config.IPS_BBCODE_BUTTONS  = [];
	
	/* Has to go before config load */
	var IPS_smiley_path			= "{$this->settings['emoticons_url']}/";
	var IPS_smiles       		= {$options['smilies']};
	var IPS_remove_plugins      = [];

	/* Load our configuration */
	CKEDITOR.config.customConfig  = '{$this->settings['public_dir']}js/3rd_party/ckeditor/ips_config.js';
</script>
<style type="text/css">
@import url("{$this->settings['css_base_url']}style_css/css_{$defaultSkin}/ipb_ckeditor.css");
</style>
EOF;
}

$options['noSmilies'] = intval($options['noSmilies']);

$IPBHTML .= <<<EOF
<input type='hidden' name='noSmilies' id='noSmilies_{$options['editorName']}' value='{$options['noSmilies']}' />
<textarea id="{$options['editorName']}" name="{$formField}" class='ips_EditorTextArea'>{$content}</textarea>
<a id="ips_switchEditor" style="display:none" href="javascript:void()">Switch to Rich Text Editor</a>
<script type="text/javascript">
	ipb.textEditor.initialize('{$options['editorName']}', { type: '{$options['type']}',
															minimize: {$options['minimize']},
															bypassCKEditor: {$options['bypassCKEditor']},
															isRte: {$options['isRte']},
															noSmilies: {$options['noSmilies']},
															ips_AutoSaveKey: '',
											                ips_AutoSaveData: {} } );
</script>
EOF;
//--endhtml--//
return $IPBHTML;
}

/**
 * Editor template for ACP
 *
 * @access	public
 * @param	string 		From field name
 * @param	string		Initial content for the editor
 * @param	string		Path to the images
 * @param	integer		Whether RTE is enabled (1) or not (0)
 * @param	string		Editor id
 * @param	string		Emoticon data
 * @return	string		HTML
 */
public function ips_editor($form_field="",$initial_content="",$images_path="",$rte_mode=0,$editor_id='ed-0',$smilies='') {

$IPBHTML = "";
//--starthtml--//

$this->settings['extraJsModules']	.= ",editor";
$bbcodes 							= IPSLib::fetchBbcodeAsJson();
$show_sidebar						= IPSCookie::get('emoticon_sidebar');
$show_sidebar_class 				= $show_sidebar && $this->settings['_remove_emoticons'] == 0 ? 'with_sidebar' : '';
$show_sidebar_style					= $show_sidebar && $this->settings['_remove_emoticons'] == 0 ? '' : "style='display:none'";
$show_sidebar_link					= $show_sidebar && $this->settings['_remove_emoticons'] == 0 ? 'true' : 'false';

$IPBHTML .= <<<EOF
	<!--top-->
	<input type='hidden' name='{$editor_id}_wysiwyg_used' id='{$editor_id}_wysiwyg_used' value='0' />
	<input type='hidden' name='editor_ids[]' value='{$editor_id}' />
	<div class='ips_editor {$show_sidebar_class}' id='editor_{$editor_id}'>
EOF;
	if( $this->settings['_remove_emoticons'] == 0 )
	{
		$IPBHTML .= <<<EOF
		<div class='sidebar row1 altrow' id='{$editor_id}_sidebar' {$show_sidebar_style}>
			<h4><img src='{$this->settings['img_url']}/close_popup.png' alt='{$this->lang->words['icon']}' id='{$editor_id}_close_sidebar' /><span>{$this->lang->words['emoticons_template_title']}</span></h4>
			<div id='{$editor_id}_emoticon_holder' class='emoticon_holder'></div>
			<div class='show_all_emoticons' id='{$editor_id}_showall_bar'>
				<input type='button' value='{$this->lang->words['show_all_emotes']}' id='{$editor_id}_showall_emoticons' class='input_submit emoticons' />
			</div>
		</div>
EOF;
	}
	
	$IPBHTML .= <<<EOF
		<div id='{$editor_id}_controls' class='controls'>
			<ul id='{$editor_id}_toolbar_1' class='toolbar' style='display: none'>
				<li class='left'>
					<span id='{$editor_id}_cmd_removeformat' class='rte_control rte_button' title='{$this->lang->words['js_tt_noformat']}'><img src='{$this->settings['img_url']}/rte_icons/remove_formatting.png' alt='{$this->lang->words['js_tt_noformat']}' /></span>
				</li>
				<li class='left'>
					<span id='{$editor_id}_cmd_togglesource' class='rte_control rte_button' title='{$this->lang->words['js_tt_htmlsource']}'><img src='{$this->settings['img_url']}/rte_icons/toggle_source.png' alt='{$this->lang->words['js_tt_htmlsource']}' /></span>
				</li>
				<li class='left'>
					<span id='{$editor_id}_cmd_otherstyles' class='rte_control rte_menu rte_special' title='{$this->lang->words['box_other_desc']}' style='display: none'>{$this->lang->words['box_other']}</span>
				</li>
				<li class='left'>
					<span id='{$editor_id}_cmd_fontname' class='rte_control rte_menu rte_font' title='{$this->lang->words['box_font_desc']}'>{$this->lang->words['box_font']}</span>
				</li>
				<li class='left'>
					<span id='{$editor_id}_cmd_fontsize' class='rte_control rte_menu rte_fontsize' title='{$this->lang->words['box_size_desc']}'>{$this->lang->words['box_size']}</span>
				</li>
				<li class='left'>
					<span id='{$editor_id}_cmd_forecolor' class='rte_control rte_palette' title='{$this->lang->words['js_tt_font_col']}'><img src='{$this->settings['img_url']}/rte_icons/font_color.png' alt='{$this->lang->words['js_tt_font_col']}' /></span>
				</li>
				<!--<li class='left'>
					<span id='{$editor_id}_cmd_backcolor' class='rte_control rte_palette' title='{$this->lang->words['js_tt_back_col']}'><img src='{$this->settings['img_url']}/rte_icons/background_color.png' alt='{$this->lang->words['js_tt_back_col']}' /></span>
				</li>-->

				<li class='right'>
					<span id='{$editor_id}_cmd_spellcheck' class='rte_control rte_button' title='{$this->lang->words['js_tt_spellcheck']}'><img src='{$this->settings['img_url']}/rte_icons/spellcheck.png' alt='{$this->lang->words['js_tt_spellcheck']}' /></span>
				</li>
				<li class='right'>
					<span id='{$editor_id}_cmd_r_small' class='rte_control rte_button' title='{$this->lang->words['js_tt_resizesmall']}'><img src='{$this->settings['img_url']}/rte_icons/resize_small.png' alt='{$this->lang->words['js_tt_resizesmall']}' /></span>
				</li>
				<li class='right'>
					<span id='{$editor_id}_cmd_r_big' class='rte_control rte_button' title='{$this->lang->words['js_tt_resizebig']}'><img src='{$this->settings['img_url']}/rte_icons/resize_big.png' alt='{$this->lang->words['js_tt_resizebig']}' /></span>
				</li>
				<li class='right sep'>
					<span id='{$editor_id}_cmd_help' class='rte_control rte_button' title='{$this->lang->words['js_tt_help']}'><a href='{$this->settings['board_url']}/index.php?app=forums&amp;module=extras&amp;section=legends&amp;do=bbcode' title='{$this->lang->words['js_tt_help']}'><img src='{$this->settings['img_url']}/rte_icons/help.png' alt='{$this->lang->words['js_tt_help']}' /></a></span>
				</li>			
				<li class='right sep'>
					<span id='{$editor_id}_cmd_undo' class='rte_control rte_button' title='{$this->lang->words['js_tt_undo']}'><img src='{$this->settings['img_url']}/rte_icons/undo.png' alt='{$this->lang->words['js_tt_undo']}' /></span>
				</li>
				<li class='right'>
					<span id='{$editor_id}_cmd_redo' class='rte_control rte_button' title='{$this->lang->words['js_tt_redo']}'><img src='{$this->settings['img_url']}/rte_icons/redo.png' alt='{$this->lang->words['js_tt_redo']}' /></span>
				</li>
			</ul>
			<ul id='{$editor_id}_toolbar_2' class='toolbar' style='display: none'>
				<li>
					<span id='{$editor_id}_cmd_bold' class='rte_control rte_button' title='{$this->lang->words['js_tt_bold']}'><img src='{$this->settings['img_url']}/rte_icons/bold.png' alt='{$this->lang->words['js_tt_bold']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_italic' class='rte_control rte_button' title='{$this->lang->words['js_tt_italic']}'><img src='{$this->settings['img_url']}/rte_icons/italic.png' alt='{$this->lang->words['js_tt_italic']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_underline' class='rte_control rte_button' title='{$this->lang->words['js_tt_underline']}'><img src='{$this->settings['img_url']}/rte_icons/underline.png' alt='{$this->lang->words['js_tt_underline']}' /></span>
				</li>
				<li class='sep'>
					<span id='{$editor_id}_cmd_strikethrough' class='rte_control rte_button' title='{$this->lang->words['js_tt_strike']}'><img src='{$this->settings['img_url']}/rte_icons/strike.png' alt='{$this->lang->words['js_tt_strike']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_subscript' class='rte_control rte_button' title='{$this->lang->words['js_tt_sub']}'><img src='{$this->settings['img_url']}/rte_icons/subscript.png' alt='{$this->lang->words['js_tt_sub']}' /></span>
				</li>
				<li class='sep'>
					<span id='{$editor_id}_cmd_superscript' class='rte_control rte_button' title='{$this->lang->words['js_tt_sup']}'><img src='{$this->settings['img_url']}/rte_icons/superscript.png' alt='{$this->lang->words['js_tt_sup']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_insertunorderedlist' class='rte_control rte_button' title='{$this->lang->words['js_tt_list']}'><img src='{$this->settings['img_url']}/rte_icons/unordered_list.png' alt='{$this->lang->words['js_tt_list']}' /></span>
				</li>
				<li class='sep'>
					<span id='{$editor_id}_cmd_insertorderedlist' class='rte_control rte_button' title='{$this->lang->words['js_tt_list']}'><img src='{$this->settings['img_url']}/rte_icons/ordered_list.png' alt='{$this->lang->words['js_tt_list']}' /></span>
				</li>
EOF;

			if( $this->settings['_remove_emoticons'] == 0 )
			{
$IPBHTML .= <<<EOF
				<li>
					<span id='{$editor_id}_cmd_emoticons' class='rte_control rte_button' title='{$this->lang->words['js_tt_emoticons']}'><img src='{$this->settings['img_url']}/rte_icons/emoticons.png' alt='{$this->lang->words['js_tt_emoticons']}' /></span>
				</li>
EOF;
			}

$IPBHTML .= <<<EOF
				<li>
					<span id='{$editor_id}_cmd_link' class='rte_control rte_palette' title='{$this->lang->words['js_tt_link']}'><img src='{$this->settings['img_url']}/rte_icons/link.png' alt='{$this->lang->words['js_tt_link']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_image' class='rte_control rte_palette' title='{$this->lang->words['js_tt_image']}'><img src='{$this->settings['img_url']}/rte_icons/picture.png' alt='{$this->lang->words['js_tt_image']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_email' class='rte_control rte_palette' title='{$this->lang->words['js_tt_email']}'><img src='{$this->settings['img_url']}/rte_icons/email.png' alt='{$this->lang->words['js_tt_email']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_ipb_quote' class='rte_control rte_button' title='{$this->lang->words['js_tt_quote']}'><img src='{$this->settings['img_url']}/rte_icons/quote.png' alt='{$this->lang->words['js_tt_quote']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_ipb_code' class='rte_control rte_button' title='{$this->lang->words['js_tt_code']}'><img src='{$this->settings['img_url']}/rte_icons/code.png' alt='{$this->lang->words['js_tt_code']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_media' class='rte_control rte_palette' title='{$this->lang->words['js_tt_media']}'><img src='{$this->settings['img_url']}/rte_icons/media.png' alt='{$this->lang->words['js_tt_media']}' /></span>
				</li>
				<li class='right'>
					<span id='{$editor_id}_cmd_justifyright' class='rte_control rte_button' title='{$this->lang->words['js_tt_right']}'><img src='{$this->settings['img_url']}/rte_icons/align_right.png' alt='{$this->lang->words['js_tt_right']}' /></span>
				</li>
				<li class='right'>
					<span id='{$editor_id}_cmd_justifycenter' class='rte_control rte_button' title='{$this->lang->words['js_tt_center']}'><img src='{$this->settings['img_url']}/rte_icons/align_center.png' alt='{$this->lang->words['js_tt_center']}' /></span>
				</li>
				<li class='right'>
					<span id='{$editor_id}_cmd_justifyleft' class='rte_control rte_button' title='{$this->lang->words['js_tt_left']}'><img src='{$this->settings['img_url']}/rte_icons/align_left.png' alt='{$this->lang->words['js_tt_left']}' /></span>
				</li>
				<li class='right sep'>
					<span id='{$editor_id}_cmd_indent' class='rte_control rte_button' title='{$this->lang->words['js_tt_indent']}'><img src='{$this->settings['img_url']}/rte_icons/indent.png' alt='{$this->lang->words['js_tt_indent']}' /></span>
				</li>
				<li class='right'>
					<span id='{$editor_id}_cmd_outdent' class='rte_control rte_button' title='{$this->lang->words['js_tt_outdent']}'><img src='{$this->settings['img_url']}/rte_icons/outdent.png' alt='{$this->lang->words['js_tt_outdent']}' /></span>
				</li>
			</ul>
		</div>
		<div id='{$editor_id}_wrap' class='editor'>
			<textarea name="{$form_field}" class="input_rte" id="{$editor_id}_textarea" rows="10" cols="60" tabindex="0">{$initial_content}</textarea>
		</div>
	</div>

	<!-- Toolpanes -->
	<script type="text/javascript">
	//<![CDATA[
	$('{$editor_id}_toolbar_1').show();
	$('{$editor_id}_toolbar_2').show();
	// Rikki: Had to remove <form>... </form> because Opera would see </form> and not pass the topic icons / hidden fields properly. Tried "</" + "form>" but when it is parsed, it had the same affect
	ipb.editor_values.get('templates')['link'] = new Template("<label for='#{id}_url'>{$this->lang->words['js_template_url']}</label><input type='text' class='input_text' id='#{id}_url' value='http://' tabindex='10' /><label for='#{id}_urltext'>{$this->lang->words['js_template_link']}</label><input type='text' class='input_text _select' id='#{id}_urltext' value='{$this->lang->words['js_template_default']}' tabindex='11' /><input type='submit' class='input_submit' value='{$this->lang->words['js_template_insert_link']}' tabindex='12' />");

	ipb.editor_values.get('templates')['image'] = new Template("<label for='#{id}_img'>{$this->lang->words['js_template_imageurl']}</label><input type='text' class='input_text' id='#{id}_img' value='http://' tabindex='10' /><input type='submit' class='input_submit' value='{$this->lang->words['js_template_insert_img']}' tabindex='11' />");

	ipb.editor_values.get('templates')['email'] = new Template("<label for='#{id}_email'>{$this->lang->words['js_template_email_url']}</label><input type='text' class='input_text' id='#{id}_email' tabindex='10' /><label for='#{id}_emailtext'>{$this->lang->words['js_template_link']}</label><input type='text' class='input_text _select' id='#{id}_emailtext' value='{$this->lang->words['js_template_email_me']}' tabindex='11' /><input type='submit' class='input_submit' value='{$this->lang->words['js_template_insert_email']}' tabindex='12' />");

	ipb.editor_values.get('templates')['media'] = new Template("<label for='#{id}_media'>{$this->lang->words['js_template_media_url']}</label><input type='text' class='input_text' id='#{id}_media' value='http://' tabindex='10' /><input type='submit' class='input_submit' value='{$this->lang->words['js_template_insert_media']}' tabindex='11' />");

	ipb.editor_values.get('templates')['generic'] = new Template("<div class='rte_title'>#{title}</div><strong>{$this->lang->words['js_template_example']}</strong><pre>#{example}</pre><label for='#{id}_option' class='optional'>#{option_text}</label><input type='text' class='input_text optional' id='#{id}_option' tabindex='10' /><label for='#{id}_text' class='tagcontent'>#{value_text}</label><input type='text' class='input_text _select tagcontent' id='#{id}_text' tabindex='11' /><input type='submit' class='input_submit' value='{$this->lang->words['js_template_add']}' tabindex='12' />");

	ipb.editor_values.get('templates')['toolbar'] = new Template("<ul id='#{id}_toolbar_#{toolbarid}' class='toolbar' style='display: none'>#{content}</ul>");

	ipb.editor_values.get('templates')['button'] = new Template("<li><span id='#{id}_cmd_custom_#{cmd}' class='rte_control rte_button specialitem' title='#{title}'><img src='{$this->settings['img_url']}/rte_icons/#{img}' alt='{$this->lang->words['icon']}' /></span></li>");

	ipb.editor_values.get('templates')['menu_item'] = new Template("<li id='#{id}_cmd_custom_#{cmd}' class='specialitem clickable'>#{title}</li>");

	ipb.editor_values.get('templates')['togglesource'] = new Template("<fieldset id='#{id}_ts_controls' class='submit' style='text-align: left'><input type='button' class='input_submit' value='{$this->lang->words['js_template_update']}' id='#{id}_ts_update' />&nbsp;&nbsp;&nbsp; <a href='#' id='#{id}_ts_cancel' class='cancel'>{$this->lang->words['js_template_cancel_source']}</a></fieldset>");

	ipb.editor_values.get('templates')['emoticons_showall'] = new Template("<input class='input_submit emoticons' type='button' id='#{id}_all_emoticons' value='{$this->lang->words['show_all_emoticons']}' />");

	ipb.editor_values.get('templates')['emoticon_wrapper'] = new Template("<h4><span>{$this->lang->words['emoticons_template_title']}</span></h4><div id='#{id}_emoticon_holder' class='emoticon_holder'></div>");

	// Add smilies into the mix
	ipb.editor_values.set( 'show_emoticon_link', true );
	ipb.editor_values.set( 'emoticons', \$H({ $smilies }) );
	ipb.editor_values.set( 'bbcodes', \$H( $bbcodes ) );

	ipb.vars['emoticon_url'] = "{$this->settings['emoticons_url']}";

	Event.observe(window, 'load', function(e){
		ipb.editors[ '{$editor_id}' ] = new ipb.editor( '{$editor_id}', USE_RTE );
	});

	//]]>
	</script>

EOF;
//--endhtml--//
return $IPBHTML;
}

/**
 * Page wrapper for popup windows
 *
 * @access	public
 * @param	string		Document character set
 * @param	array 		CSS Files
 * @return	string		HTML
 */
public function global_main_popup_wrapper($IPS_DOC_CHAR_SET=IPS_DOC_CHAR_SET, $cssFiles=array() ) {

$IPBHTML = "";
//--starthtml--//

$_path		= IPS_PUBLIC_SCRIPT;
$boardurl = ($this->registry->output->isHTTPS) ? $this->settings['board_url_https'] : $this->settings['board_url'];

$IPBHTML .= <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset={$IPS_DOC_CHAR_SET}" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Cache-Control" content="no-cache" />
	<meta http-equiv="Expires" content="Fri, 01 January 1999 01:00:00 GMT" />
	<link rel="shortcut icon" href='{$boardurl}/favicon.ico' />

	<title><%TITLE%></title>
	<script type='text/javascript'>
		jsDebug = 1;
		USE_RTE = 1;
		inACP   = true;
		isRTL	= false;
	</script>
EOF;

/** CSS ----------------------------------------- */
/*if ( $this->settings['use_minify'] )
{
	$_basics  = CP_DIRECTORY . '/skin_cp/acp.css,' . CP_DIRECTORY . '/skin_cp/acp_editor.css';
	$_others  = '';

	if ( is_array( $cssFiles['import'] ) AND count( $cssFiles['import'] ) )
	{
		foreach( $cssFiles['import'] as $data )
		{
			$_others .= ',' . preg_replace( "#^(.*)/(" . CP_DIRECTORY . "/.*)$#", "$2", $data['content'] );
		}
	}

	$IPBHTML .= "\n\t<link rel=\"stylesheet\" type=\"text/css\" media='screen' href=\"{$this->settings['public_dir']}min/index.php?f={$_basics}{$_others}\">\n";
}
else
{*/	
	$IPBHTML .= <<<HTML
	<style type='text/css' media='all'>
		@import url( "{$this->settings['skin_acp_url']}/acp.css" );
		@import url( "{$this->settings['skin_acp_url']}/acp_editor.css" );
	</style>
HTML;

	if( is_array($cssFiles['import']) AND count($cssFiles['import']) )
	{
		foreach( $cssFiles['import'] as $data )
		{
			$IPBHTML .= <<<EOF
			<link rel="stylesheet" type="text/css" {$data['attributes']} href="{$data['content']}" />
EOF;
		}
	}
//}

$IPBHTML .= <<<HTML
	<!--[if IE]>
		<style type='text/css' media='all'>
			@import url( "{$this->settings['skin_acp_url']}/acp_ie_tweaks.css" );
		</style>
	<![endif]-->
HTML;

if( IN_DEV )
{
	$IPBHTML .= <<<HTML
		<style type='text/css' media='all'>
			.ipsActionBar > ul > li.inDev {
				display: inline;
			}
		</style>
HTML;
}

if( is_array($cssFiles['inline']) AND count($cssFiles['inline']) )
{
	$IPBHTML .= <<<EOF
		<style type='text/css' media="all">
EOF;

	foreach( $cssFiles['inline'] as $data )
	{
		$IPBHTML .= $data['content'];
	}

	$IPBHTML .= <<<EOF
		</style>
EOF;
}

/** JS ----------------------------------------- */
/*if ( $this->settings['use_minify'] )
{
	$_others = ',' . CP_DIRECTORY . '/js/acp.js,' . CP_DIRECTORY . '/js/acp.' . implode('.js,' . CP_DIRECTORY . '/js/acp.', array( 'menu', 'tabs' ) ) . '.js';

	$IPBHTML .= <<<HTML

	<script type='text/javascript' src='{$this->settings['public_dir']}min/index.php?g=js&amp;ipbv={$this->registry->output->antiCacheHash}'></script>
HTML;

	$IPBHTML .= "\n\t<script type='text/javascript' src='{$this->settings['public_dir']}min/index.php?ipbv={$this->registry->output->antiCacheHash}&amp;f=" . PUBLIC_DIRECTORY . "/js/ipb.js" . $_others;

	if ( $this->settings['extraJsModules'] )
	{
		$_modules		= explode( ',', $this->settings['extraJsModules'] );
		$_loadModules	= '';
		$_seenModules	= array();

		foreach( $_modules as $_jsModule )
		{
			if( !$_jsModule )
			{
				continue;
			}

			if( in_array( $_jsModule, $_seenModules ) )
			{
				continue;
			}

			$_seenModules[] = $_jsModule;

			$_loadModules	.= "," . PUBLIC_DIRECTORY . "/js/ips." . $_jsModule . ".js";
		}

		$IPBHTML .= $_loadModules . "'></script>\n";
	}
	else
	{
		$IPBHTML .= "'></script>\n";
	}
}
else
{*/
	$IPBHTML .= <<<HTML
		<script type="text/javascript" src="{$this->settings['public_dir']}js/3rd_party/prototype.js?ipbv={$this->registry->output->antiCacheHash}"></script>
		<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/scriptaculous/scriptaculous-cache.js?ipbv={$this->registry->output->antiCacheHash}'></script>
		<script type="text/javascript" src='{$this->settings['public_dir']}js/ipb.js?ipbv={$this->registry->output->antiCacheHash}&amp;load={$this->settings['extraJsModules']}'></script>
		<script type='text/javascript' src='{$this->settings['js_main_url']}acp.menu.js?ipbv={$this->registry->output->antiCacheHash}'></script>
		<script type='text/javascript' src='{$this->settings['js_main_url']}acp.js?ipbv={$this->registry->output->antiCacheHash}'></script>
		<script type="text/javascript" src='{$this->settings['js_main_url']}acp.tabs.js?ipbv={$this->registry->output->antiCacheHash}'></script>
HTML;
//}

$mem_info = json_encode( array( 'g_mem_info' => $this->memberData['g_mem_info'] ) );

$IPBHTML .= <<<EOF
	<script type='text/javascript'>
	//<![CDATA[
		ipb.vars['st']	= "{$this->request['st']}";
		ipb.vars['base_url']	= "{$this->settings['_base_url']}";
		ipb.vars['front_url']	= "{$this->settings['board_url']}/index.php?";
		ipb.vars['app_url']		= "{$this->settings['base_url']}";
		ipb.vars['image_url'] 	= "{$this->settings['skin_app_url']}/images/";
		ipb.vars['md5_hash']	= "{$this->member->form_hash}";
		ipb.vars['is_touch']	= false;
		ipb.vars['member_group'] = {$mem_info};
		/* ---- cookies ----- */
		ipb.vars['cookie_id'] 			= '{$this->settings['cookie_id']}';
		ipb.vars['cookie_domain'] 		= '{$this->settings['cookie_domain']}';
		ipb.vars['cookie_path']			= '{$this->settings['cookie_path']}';
		ipb.templates['close_popup']	= "<img src='{$this->settings['img_url']}/close_popup.png' alt='x' />";
		ipb.templates['page_jump']		= new Template("<div id='#{id}_wrap' class='ipbmenu_content'><h3 class='bar'>{$this->lang->words['gl_pagejump']}</h3><input type='text' class='input_text' id='#{id}_input' size='8' /> <input type='submit' value='Go' class='input_submit add_folder' id='#{id}_submit' /></div>");
		ipb.templates['ajax_loading'] 	= "<div id='ajax_loading'>{$this->lang->words['gl_loading']}</div>";
	//]]>
	</script>
	<!--<script type='text/javascript' src='http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js'></script>-->
	<script type='text/javascript'>
		Loader.boot();
		acp = new IPBACP;
	</script>
	<script type="text/javascript" src="{$this->settings['cache_dir']}lang_cache/{$this->lang->lang_id}/acp.lang.js?ipbv={$this->registry->output->antiCacheHash}" charset="{$IPS_DOC_CHAR_SET}"></script>
</head>
<body<%BODYEXTRA%> id='ipboard_body' class='popupwindow'>
<div id='loading-layer' style='display:none'>
	<div id='loading-layer-shadow'>
	   <div id='loading-layer-inner' >
		   <img src='{$this->settings['skin_acp_url']}/images/loading_anim.gif' style='vertical-align:middle' />
		   <span style='font-weight:bold' id='loading-layer-text'>{$this->lang->words['ajax_please_wait']}</span>
	   </div>
	</div>
</div>
<div id='main_content'>
	<div id='content_wrap'>
		<%CONTENT%>
	</div>
</div>
</body>
</html>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Primary page wrapper - used for all full pages
 *
 * @access	public
 * @param	string 		Document character set
 * @param	array 		CSS Files
 * @param	string		Global submenu HTML (@see global_menu_sub_navigation)
 * @param	array 		Order of tabs
 * @return	string		HTML
 */
public function global_main_wrapper($IPS_DOC_CHAR_SET=IPS_DOC_CHAR_SET, $cssFiles=array(), $gbl_sub_menu='', $tabOrder=array() ) {

$IPBHTML = "";
//--starthtml--//

//$_encoded = base64_encode( $this->settings['query_string_safe'] );
$_url	= str_replace( '&amp;'   , '&', $this->settings['query_string_safe'] );
$_url	= preg_replace( '#&{1,}#', ';', $_url );
$_url	= preg_replace( '#={1,}#', ':', $_url );
$_url	= ltrim( $_url, ';' );

$_path	= IPS_PUBLIC_SCRIPT;
$year	= date('Y');

/* Open Tab */
$__tabs	= ( is_array( $this->member->acp_tab_data ) and count( $this->member->acp_tab_data ) )
		? "'" . implode( "','", array_keys( $this->member->acp_tab_data ) ) . "'"
		: '';

$_apptitle	= ipsRegistry::$applications[ ipsRegistry::$current_application ]['app_title'];

$defaultFakeApp		= '';
$defaultFakeModule	= '';

$curApp				= array();

switch( ipsRegistry::$current_application )
{
	case 'forums':
		$curApp['forums'] = 'active';
		break;
	case 'core':
		$curApp['core']	= 'active';
		break;
	case 'members':
		$curApp['members'] = 'active';
		break;
	default:
		$curApp['other'] = 'active';
		break;
}

$fakeApps	= $this->registry->output->fetchFakeApps();

foreach( $fakeApps as $fa => $data )
{
	foreach( $data as $appData )
	{
		if ( ! $defaultFakeApp )
		{
			$defaultFakeApp    = $appData['app'];
			$defaultFakeModule = $appData['module'];
		}
		
		if ( $appData['app'] == ipsRegistry::$current_application && $appData['module'] == ipsRegistry::$current_module )
		{
			$curApp = array();
			$curApp[ $fa ] = 'active';
			break 2;
		}
	}
}

if( !$this->settings['ipb_reg_number'] )
{
	$this->lang->words['license_missing_info']	= sprintf( $this->lang->words['license_missing_info'], $this->settings['base_url'] . 'app=core&amp;module=tools&amp;section=licensekey' );
	$extra_class = 'force_license';
	$license_html = <<<HTML
		<div id='license_notice_force'>
			<h4>{$this->lang->words['license_missing_header']}</h4>
			<p>{$this->lang->words['license_missing_info']}</p>
		</div>
HTML;
}
else
{
	$licenseData	= $this->cache->getCache( 'licenseData' );
	
	if( ( !$licenseData OR !$licenseData['key']['_expires'] OR $licenseData['key']['_expires'] < IPS_UNIX_TIME_NOW and $licenseData['key']['_expires'] != -1 ) AND !IPSCookie::get( 'ignore-license-notice' ) )
	{
		$extra_class = 'expired_license';
		$license_html = <<<HTML
			<div id='license_notice_expired'>
				<div class='right'><a id='license-close' href='#'>Close</a></div>
				<h4>{$this->lang->words['license_expired_header']}</h4>
				<p>{$this->lang->words['license_expired_info']}</p>
			</div>
HTML;
	}
}

$boardurl = ($this->registry->output->isHTTPS) ? $this->settings['board_url_https'] : $this->settings['board_url'];

$IPBHTML .= <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset={$IPS_DOC_CHAR_SET}" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Cache-Control" content="no-cache" />
	<meta http-equiv="Expires" content="Fri, 01 January 1999 01:00:00 GMT" />
	<link rel="shortcut icon" href='{$boardurl}/favicon.ico' />

	<title><%TITLE%></title>
	<script type='text/javascript'>
		jsDebug = 1;
		USE_RTE = 1;
		inACP   = true;
		isRTL	= false;
	</script>
HTML;

/** CSS ----------------------------------------- */
/*if ( $this->settings['use_minify'] )
{
	$_basics  = CP_DIRECTORY . '/skin_cp/acp.css,' . CP_DIRECTORY . '/skin_cp/acp_editor.css';
	$_others  = '';
	
	if ( is_array( $cssFiles['import'] ) AND count( $cssFiles['import'] ) )
	{
		foreach( $cssFiles['import'] as $data )
		{
			$_others .= ',' . preg_replace( "#^(.*)/(" . CP_DIRECTORY . "/.*)$#", "$2", $data['content'] );
		}
	}
	
	$IPBHTML .= "\n\t<link rel=\"stylesheet\" type=\"text/css\" media='screen' href=\"{$this->settings['public_dir']}min/index.php?ipbv={$this->registry->output->antiCacheHash}&amp;f={$_basics}{$_others}\">\n";
}
else
{*/
	$IPBHTML .= <<<HTML
	<style type='text/css' media='all'>
		@import url( "{$this->settings['skin_acp_url']}/acp.css?ipbv={$this->registry->output->antiCacheHash}" );
		@import url( "{$this->settings['skin_acp_url']}/acp_editor.css?ipbv={$this->registry->output->antiCacheHash}" );
	</style>
HTML;

	if( is_array($cssFiles['import']) AND count($cssFiles['import']) )
	{
		foreach( $cssFiles['import'] as $data )
		{
			$IPBHTML .= <<<EOF
			<link rel="stylesheet" type="text/css" {$data['attributes']} href="{$data['content']}?ipbv={$this->registry->output->antiCacheHash}" />
EOF;
		}
	}
//}

$IPBHTML .= <<<HTML
	<!--[if IE]>
		<style type='text/css' media='all'>
			@import url( "{$this->settings['skin_acp_url']}/acp_ie_tweaks.css" );
		</style>
	<![endif]-->
HTML;

if( IN_DEV )
{
	$IPBHTML .= <<<HTML
		<style type='text/css' media='all'>
			.ipsActionBar > ul > li.inDev {
				display: inline;
			}
		</style>
HTML;
}

if( is_array($cssFiles['inline']) AND count($cssFiles['inline']) )
{
	$IPBHTML .= <<<EOF
		<style type='text/css' media="all">
EOF;

	foreach( $cssFiles['inline'] as $data )
	{
		$IPBHTML .= $data['content'];
	}

	$IPBHTML .= <<<EOF
		</style>
EOF;
}

/** JS ----------------------------------------- */
/*if ( $this->settings['use_minify'] )
{
	$_others = ',' . CP_DIRECTORY . '/js/acp.js,' . CP_DIRECTORY . '/js/acp.' . implode('.js,' . CP_DIRECTORY . '/js/acp.', array( 'menu', 'tabs' ) ) . '.js';
	
	if ( $this->settings['remote_load_js'] )
	{
		$IPBHTML .= <<<HTML
		<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/prototype/1.7/prototype.js'></script>
		<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/scriptaculous/1.8/scriptaculous.js?load=effects,dragdrop,builder'></script>
HTML;

	}
	else
	{
			$IPBHTML .= <<<HTML
	
	<script type='text/javascript' src='{$this->settings['public_dir']}min/index.php?g=js&amp;ipbv={$this->registry->output->antiCacheHash}'></script>
HTML;
	}

	$IPBHTML .= "\n\t<script type='text/javascript' src='{$this->settings['public_dir']}min/index.php?ipbv={$this->registry->output->antiCacheHash}&amp;f=" . PUBLIC_DIRECTORY . "/js/ipb.js" . $_others;
	
	if ( $this->settings['extraJsModules'] )
	{
		$_modules		= explode( ',', $this->settings['extraJsModules'] );
		$_loadModules	= '';
		$_seenModules	= array();
		
		foreach( $_modules as $_jsModule )
		{
			if( !$_jsModule )
			{
				continue;
			}
			
			if( in_array( $_jsModule, $_seenModules ) )
			{
				continue;
			}
			
			$_seenModules[] = $_jsModule;
			
			$_loadModules	.= "," . PUBLIC_DIRECTORY . "/js/ips." . $_jsModule . ".js";
		}
		
		$IPBHTML .= $_loadModules . "'></script>\n";
	}
	else
	{
		$IPBHTML .= "'></script>\n";
	}
}
else
{*/
	$IPBHTML .= <<<HTML
		<script type="text/javascript" src="{$this->settings['public_dir']}js/3rd_party/prototype.js?ipbv={$this->registry->output->antiCacheHash}"></script>
		<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/scriptaculous/scriptaculous-cache.js?ipbv={$this->registry->output->antiCacheHash}'></script>
		<script type="text/javascript" src='{$this->settings['public_dir']}js/ipb.js?ipbv={$this->registry->output->antiCacheHash}&amp;load={$this->settings['extraJsModules']}'></script>
		<script type='text/javascript' src='{$this->settings['js_main_url']}acp.menu.js?ipbv={$this->registry->output->antiCacheHash}'></script>
		<script type='text/javascript' src='{$this->settings['js_main_url']}acp.js?ipbv={$this->registry->output->antiCacheHash}'></script>
		<script type="text/javascript" src='{$this->settings['js_main_url']}acp.tabs.js?ipbv={$this->registry->output->antiCacheHash}'></script>
HTML;
//}

/* SIDEBAR */
if( $this->settings['hide_sidebar'] || IPSCookie::get("acp_sidebar") == 'closed' )
{
	if( $this->settings['hide_sidebar'] )
	{
		//$sidebar['toggle_class'] = "style='display: none'";
	}
	$sidebar['content_class'] = "close_menu";
	$sidebar['menu_style'] = "style='display: none'";
}
else
{
	$sidebar['content_class'] = "open_menu";
	$sidebar['menu_style'] = "";
}

$mem_info = json_encode( array( 'g_mem_info' => $this->memberData['g_mem_info'] ) );

$IPBHTML .= <<<HTML
	<script type="text/javascript" src="{$this->settings['cache_dir']}lang_cache/{$this->lang->lang_id}/acp.lang.js?ipbv={$this->registry->output->antiCacheHash}" charset="{$IPS_DOC_CHAR_SET}"></script>
	<script type='text/javascript' src='{$this->settings['js_main_url']}3rd_party/jquery.min.js?ipbv={$this->registry->output->antiCacheHash}'></script>
	<script type='text/javascript' src='{$this->settings['js_main_url']}3rd_party/jquery-ui.min.js?ipbv={$this->registry->output->antiCacheHash}'></script>
	<script type='text/javascript'>
		var jQ = jQuery.noConflict();
	</script>
	<script type='text/javascript' src='{$this->settings['js_main_url']}acp.jquery.js?ipbv={$this->registry->output->antiCacheHash}'></script>
	<script type='text/javascript'>
	//<![CDATA[
		ipb.vars['st']	= "{$this->request['st']}";
		ipb.vars['base_url']	= "{$this->settings['_base_url']}";
		ipb.vars['front_url']	= "{$this->settings['board_url']}/index.php?";
		ipb.vars['app_url']		= "{$this->settings['base_url']}";
		ipb.vars['image_url'] 	= "{$this->settings['skin_app_url']}images/";
		ipb.vars['image_acp_url'] 	= "{$this->settings['skin_acp_url']}/images/";
		ipb.vars['md5_hash']	= "{$this->member->form_hash}";
		ipb.vars['is_touch']    = false;
		ipb.vars['member_group'] = {$mem_info};
		ipb.vars['member_id']    = parseInt("{$this->memberData['member_id']}");
		
		/* ---- cookies ----- */
		ipb.vars['cookie_id'] 			= '{$this->settings['cookie_id']}';
		ipb.vars['cookie_domain'] 		= '{$this->settings['cookie_domain']}';
		ipb.vars['cookie_path']			= '{$this->settings['cookie_path']}';
		ipb.templates['close_popup']	= "<img src='{$this->settings['img_url']}/close_popup.png' alt='x' />";
		ipb.templates['page_jump']		= new Template("<div id='#{id}_wrap' class='ipbmenu_content'><h3 class='bar'>{$this->lang->words['gl_pagejump']}</h3><input type='text' class='input_text' id='#{id}_input' size='8' /> <input type='submit' value='Go' class='realbutton' id='#{id}_submit' /></div>");
		ipb.templates['ajax_loading'] 	= "<div id='ajax_loading'>{$this->lang->words['gl_loading']}</div>";
		ipb.templates['global_notify'] 	= new Template("<div class='popupWrapper'><div class='popupInner'><div class='ipsPad'>#{message} #{close}</div></div></div>");
		ipb.templates['global_notify_close']	= "<span id='ipsGlobalNotification_close' class='realbutton'>{$this->lang->words['gbl_ok']}</span>";
	//]]>
	</script>
	<script type='text/javascript'>
		Loader.boot();
		acp = new IPBACP;
	</script>
</head>
<body id='ipboard_body' class='{$extra_class} clearfix'>
<!-- Inline Form Box -->
<div id='modal' style='display: none'></div>
{$license_html}
<div id='inlineFormWrap' style='display: none;'>
	<div id='inlineFormInnerWrap'>
		<div id='inlineFormInnerClose' onclick="Effect.Fade( 'inlineFormWrap', { duration: .5 } );"></div>
		<div id='inlineFormInnerTitle'></div>
		<div id='inlineErrorBox'>
			<img src='{$this->settings['skin_acp_url']}/images/stopLarge.png' />
			<strong>{$this->lang->words['gl_error']}</strong>
			<div id='inlineErrorText'></div>
		</div>
		<div id='inlineFormInnerContent'></div>
		<div id='inlineFormLoading'>
			{$this->lang->words['gl_pleasewait']}...
			<br /><br />
			<img src='{$this->settings['skin_acp_url']}/images/loading_big.gif' alt='loading' id='search_loading' />
		</div>
	</div>
</div>
<!-- / Inline Form Box -->
	<div id='header'>
		<div id='search' class='right'>
			<img src='{$this->settings['skin_acp_url']}/images/search_icon_white.png' alt='' /> <input type='text' value='{$this->lang->words['gl_livesearch']}' id='acpSearchKeyword' class='inactive' /><img src='{$this->settings['skin_acp_url']}/images/loading.gif' id='acp_loading' style='display: none' />
		</div>
		<a href='{$this->settings['_base_url']}' title='{$this->lang->words['home']}'>
			<img src='{$this->settings['skin_acp_url']}/images/logo.png' alt='Logo' />
		</a>
		<div class='logged_in'>
			{$this->lang->words['gl_loggedinas']} {$this->memberData['members_display_name']}
			<ul id='user_links' class='ipsList_inline'>
				<li>
					<a href='../' target='_blank'>{$this->lang->words['gbl_view_site']}</a>
				</li>
				<li>
					<a href='{$this->settings['_base_url']}app=core&amp;module=mycp&amp;section=dashboard'>{$this->lang->words['gbl_dashboard']}</a>
				</li>
				<li>
					<a href='{$this->settings['_base_url']}&amp;module=login&amp;do=login-out'>{$this->lang->words['gbl_log_out']}</a>
				</li>
			</ul>
		</div>
	</div>
	<div id='live_search_results' style='display: none'>
		<div id='ls_sections'>
			<ul>
HTML;
			if ( $this->registry->getClass('class_permissions')->checkPermission( 'settings_manage', 'core', 'settings' ) )
			{
				$IPBHTML .= <<<HTML
				<li id='ls_settings'>{$this->lang->words['livesearch_settings']}<span class='count'></span></li>
HTML;
			}
			if ( $this->registry->getClass('class_permissions')->checkPermission( 'member_edit', 'members', 'members' ) )
			{
				$IPBHTML .= <<<HTML
				<li id='ls_members'>{$this->lang->words['livesearch_members']}<span class='count'></span></li>
HTML;
			}
			if ( $this->registry->getClass('class_permissions')->checkPermission( 'groups_edit', 'members', 'groups' ) )
			{
				$IPBHTML .= <<<HTML
				<li id='ls_groups'>{$this->lang->words['livesearch_groups']}<span class='count'></span></li>
HTML;
			}
			if ( $this->registry->getClass('class_permissions')->checkPermission( 'forums_edit', 'forums', 'forums' ) )
			{
				$IPBHTML .= <<<HTML
				<li id='ls_forums'>{$this->lang->words['livesearch_forums']}<span class='count'></span></li>
HTML;
			}
				$IPBHTML .= <<<HTML
				<li id='ls_location'>{$this->lang->words['livesearch_pages']}<span class='count'></span></li>
HTML;
				if( IPSLib::appIsInstalled('nexus') and $this->registry->getClass('class_permissions')->checkForAppAccess('nexus') )
				{
					$IPBHTML .= "<li id='ls_nexus'>{$this->lang->words['livesearch_nexus']}<span class='count'></span></li>";
				}
				
$IPBHTML .= <<<HTML
				<li id='ls_marketplace' title='{$this->lang->words['search_marketplace_more']}'>{$this->lang->words['search_ipsmarketplace']}</li>
			</ul>
		</div>
		<div id='ls_results'>
			<span id='ls_no_results' style='display: none'>{$this->lang->words['live_search_no_results']}</span>
			<div id='ls_settings_panel'></div>
			<div id='ls_members_panel'></div>
			<div id='ls_groups_panel'></div>
			<div id='ls_forums_panel'></div>
			<div id='ls_location_panel'></div>
HTML;
			if( IPSLib::appIsInstalled('nexus') ){
				$IPBHTML .= "<div id='ls_nexus_panel'></div>";
			}
			
$IPBHTML .= <<<HTML
			<div id='ls_marketplace_panel'>
				<div class='pad'>
					{$this->lang->words['ipsmarketplace_is_best']}
					<br /><br />

					<a href='http://community.invisionpower.com/files/' class='realbutton'>{$this->lang->words['gotomarketplace']}</a>
				</div>
			</div>
		</div>
		<img src='{$this->settings['skin_acp_url']}/images/live_search_stem.png' id='ls_stem' />
	</div>
	<div id='app_bar'>
		<!--<a class='right' id='edit_tabs' href='#' title="Set up the app bar just the way you want">Edit Tabs</a>-->
HTML;
		$IPBHTML .= $this->global_app_menu_html( $gbl_sub_menu, $tabOrder );
		
$IPBHTML .= <<<HTML
	</div>

	<div id='page_body' class='{$sidebar['content_class']} clearfix'>
		<a href='#' id='toggle_sidebar' title='Close the sidebar' {$sidebar['toggle_class']}>&larr;</a>
		<div id='section_navigation' {$sidebar['menu_style']}>			
			<%SIDEBAR_EXTRA%>
			<%MENU%>
		</div>
		<div id='main_content' class='clearfix'>
			<%NAV%>				
			<%CONTENT%>
		</div>
	</div>
	<div id='footer' class='clear'>
		<a href='http://www.invisionpower.com'>IP.Board 3</a> &copy; {$year} IPS, Inc. &nbsp;&nbsp;|&nbsp;&nbsp; <a href='http://www.invisionpower.com/clients/' target='_blank' title='{$this->lang->words['gl_getsupport_title']}'>{$this->lang->words['gl_getsupport']}</a> &nbsp;&nbsp;|&nbsp;&nbsp; <a href='http://community.invisionpower.com/index.php?app=ccs' target='_blank' title='{$this->lang->words['gl_resources_title']}'>{$this->lang->words['gl_resources']}</a>
HTML;

if ( IN_DEV )
{
$count = count( $this->DB->obj['cached_queries'] );
$files = count( get_included_files() );

$IPBHTML .= <<<HTML
	&nbsp;&nbsp;|&nbsp;&nbsp; <a href='#' onclick="$('acpQueries').toggle(); return false;">{$count} Queries and {$files} Included Files</a>
	
HTML;
}

$IPBHTML .= <<<HTML
	</div>

</body>
</html>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Global page primary template - fits in content area
 *
 * @access	public
 * @return	string		HTML
 */
public function global_frame_wrapper() {

$year = date('Y');

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<%CONTEXT_MENU%>

<%MSG%>
<%SECTIONCONTENT%>

<div id='acpQueries' style='display:none'>
	<%QUERIES%>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Generate sub navigation menu for global use (all apps)
 *
 * @access	public
 * @param	array 		Menu data
 * @param	array 		Application and module titles data
 * @return	string		array
 */
public function global_menu_sub_navigation( $menu ) {

$seen = array();
$fakeApps = $this->registry->output->fetchFakeApps();

foreach( $menu['menu'] as $app => $modules )
{
	$this_menu = "<ul id='menu_{$app}' style='display: none'>\r\n";

	foreach( $modules as $key => $items )
	{	
		$this_menu .= "\t<li>\r\n";

		if( count( $items['items'] ) == 1 )
		{
			$real_app	= $items['items'][0]['app_dir'];
			
			if ( !ipsRegistry::getClass('class_permissions')->checkForSectionAccess( $real_app, $items['items'][0]['module'], $items['items'][0]['section'] ) )
			{
				continue;
			}
			
			$_url		= ( $items['items'][0]['url'] ) ? "&amp;{$items['items'][0]['url']}" : "";
			$_title		= $menu['titles'][ $app ][ $key ] ? $menu['titles'][ $app ][ $key ]['title'] : $items['items'][0]['title'];
			$this_menu .= "\t\t<a href='{$this->settings['_base_url']}app={$real_app}&amp;module={$items['items'][0]['module']}&amp;section={$items['items'][0]['section']}{$_url}'>{$_title}</a>\r\n";
		}
		else
		{
			$haveItems = FALSE;
			$_this_menu = "";
			
			if( $menu['titles'][ $app ][ $key ] )
			{
				$_this_menu .= "\t\t<span>" . $menu['titles'][ $app ][ $key ]['title'] . "</span>\r\n";
			}
			else
			{
				$_this_menu .= "\t\t<span>" . $key . "</span>\r\n";
			}
			
			$_this_menu .= "\t\t<ul>\r\n";
			foreach( $items['items'] as $i => $info )
			{
				$real_app	= $info['app_dir'];
				
				if ( !ipsRegistry::getClass('class_permissions')->checkForSectionAccess( $real_app, $info['module'], $info['section'] ) )
				{
					continue;
				}
				if( is_array( $seen[ $app ][ $info['module'] ] ) && in_array( $info['pos'], $seen[ $app ][ $info['module'] ] ) ){
					// Decided to show these because we need to retain access to the options for 3rd party apps
					//continue; // don't show sub-sub items
				}
				
				$haveItems = TRUE;
				$_this_menu .= "\t\t\t<li>\r\n";
				$_this_menu .= "\t\t\t\t<a href='{$this->settings['_base_url']}app={$real_app}&amp;module={$info['module']}&amp;section={$info['section']}&amp;{$info['url']}'>{$info['title']}</a>\r\n";
				$_this_menu .= "\t\t\t</li>\r\n";
				
				$seen[ $app ][ $info['module'] ][] = $info['pos'];
			}
			$_this_menu .= "\t\t</ul>\r\n";
			
			if ( $haveItems )
			{
				$this_menu .= $_this_menu;
			}
		}
		
		$this_menu .= "\t</li>\r\n";
	}	
	
	$this_menu .= "</ul>\r\n";
	
	$return[ $app ] = $this_menu;
}

//print_r( $seen );
//print_r($menu);exit;
//print_r($titles);exit;

//--endhtml--//
return $return;
}

/**
 * Generate the application menu HTML
 *
 * @access	public
 * @param	string		Raw menu data
 * @param	array 		Tab order
 * @return	string		HTML
 */
public function global_app_menu_html( $raw_menu, $tabOrder ) {

$fakeAppAccess = array();

$IPBHTML = "";
//--starthtml--//

$menus = $this->global_menu_sub_navigation( $raw_menu );

$curApp        	 	= array();
$defaultFakeApp		= array();
$defaultFakeModule	= array();

switch( ipsRegistry::$current_application )
{
	case 'forums':
		$curApp['forums'] = 'active';
		break;
	case 'core':
		$curApp['core']	= 'active';
		break;
	case 'members':
		$curApp['members'] = 'active';
		break;
	default:
		$curApp['other'] = 'active';
		break;
}

//print_r( $menus );
$fakeApps = $this->registry->output->fetchFakeApps();

foreach( $fakeApps as $fa => $data )
{
	$fakeAppAccess[ $fa ] = FALSE;
	foreach( $data as $appData )
	{
		if ( !$fakeAppAccess[ $fa ] and ipsRegistry::getClass('class_permissions')->checkForModuleAccess( $appData['app'], $appData['module'] ) )
		{
			$fakeAppAccess[ $fa ] = TRUE;
		}
	
		if ( ! $defaultFakeApp[ $fa ] )
		{
			$defaultFakeApp[ $fa ]    = $appData['app'];
			$defaultFakeModule[ $fa ] = $appData['module'];
		}
		
		if ( $appData['app'] == ipsRegistry::$current_application && $appData['module'] == ipsRegistry::$current_module )
		{
			$curApp = array();
			$curApp[ $fa ] = 'active';
		}
	}
}

$IPBHTML .= <<<HTML
<ul id='app_menu' class='app_menu'>
HTML;

$applications	= ipsRegistry::$applications;
$count			= 0;
$this->registry->getClass('class_permissions')->return = 1;
$other_menu = "";

/* Loop */
foreach( $applications as $app_dir => $app_data )
{
	$tag = '';

	if ( in_array( $app_data['app_directory'], array( 'core', 'forums', 'members' ) ) || $this->registry->getClass('class_permissions')->checkForAppAccess( $app_data['app_directory'] ) !== TRUE || ! $applications[ $app_dir ]['app_enabled'] )
	{
		continue;
	}

	if( $app_data['app_location'] == 'ips' )
	{
		$tag = "<span class='ipsBadge badge_purple'>{$this->lang->words['gl_ipsapp']}</span>&nbsp;&nbsp;";
	}

	$other_menu .= <<<EOF
	<li id='app_{$app_dir}'>
		<a href='{$this->settings['_base_url']}app={$app_data['app_directory']}'>
			{$tag}
			{$app_data['app_title']}
		</a>
		{$menus[ $app_dir ]}
	</li>
EOF;
	$count++;
}

//-----------------------------------------
// Draw tabs based on order preference
//-----------------------------------------

foreach( $tabOrder as $tabkey )
{
	if( $tabkey == 'other' AND !$count )
	{
		continue;
	}
	
	$permCheck = TRUE;
	switch( $tabkey )
	{
		case 'core':
			$title	= $this->lang->words['gl_system'];
			$link	= "{$this->settings['_base_url']}app=core";
			$permCheck = ipsRegistry::getClass('class_permissions')->checkForAppAccess( 'core' );
		break;
		
		case 'forums':
			$title	= $this->lang->words['gl_forums'];
			$link	= "{$this->settings['_base_url']}app=forums";
			$permCheck = ipsRegistry::getClass('class_permissions')->checkForAppAccess( 'forums' );
		break;
		
		case 'members':
			$title	= $this->lang->words['gl_members'];
			$link	= "{$this->settings['_base_url']}app=members";
			$permCheck = ipsRegistry::getClass('class_permissions')->checkForAppAccess( 'members' );
		break;
		
		case 'lookfeel':
			$title	= $this->lang->words['gl_lookandfeel'];
			$link	= "{$this->settings['_base_url']}app={$defaultFakeApp['lookfeel']}&amp;module={$defaultFakeModule['lookfeel']}";
			$permCheck = $fakeAppAccess['lookfeel'];
		break;
		
		case 'support':
			$title	= $this->lang->words['gl_support'];
			$link	= "{$this->settings['_base_url']}app=core&amp;module=diagnostics";
			$permCheck = $fakeAppAccess['support'];
		break;
		
		case 'reports':
			$title	= $this->lang->words['gl_reportsmenu'];
			$link	= "{$this->settings['_base_url']}app={$defaultFakeApp['reports']}&amp;module={$defaultFakeModule['reports']}";
			$permCheck = $fakeAppAccess['reports'];
		break;
	}

	if ( !$permCheck )
	{
		continue;
	}

	if( $tabkey == 'other' )
	{
	$IPBHTML .= <<<HTML
		<li class='{$curApp['other']}'>
			<a href='{$this->settings['_base_url']}app=core&amp;module=applications&amp;section=applications&amp;do=applications_overview'>{$this->lang->words['other_applications']}</a>
			<ul id='menu__other' style='display: none'>
				{$other_menu}
			</ul>
		</li>	
HTML;
	}
	else
	{
	$IPBHTML .= <<<HTML
	<li class='{$curApp[$tabkey]}'>
		<a href='{$link}'>{$title}</a>
		{$menus[ $tabkey ]}
	</li>
HTML;
	}
}

$IPBHTML .= "</ul>";

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the information box on the page
 *
 * @access	public
 * @param	string 		Box title
 * @param	string		Box content
 * @return	string		HTML
 */
public function information_box($title="", $content="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$title}</h2>
</div>
<div class='section_info'>{$content}</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show a warning box
 *
 * @access	public
 * @param	string 		Title
 * @param	string		Content
 * @return	string		HTML
 */
public function warning_box($title="", $content="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='warning'>
 <h4>{$title}</h4>
 {$content}
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Shows the debug query output at the bottom of the page
 *
 * @access	public
 * @param	string 		Queries to show
 * @return	string		HTML
 */
public function global_query_output($queries="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<br /><br />
<div align='center' style='margin-left:auto;margin-right:0'>
<div class='acp-box' style='text-align:left;'>
 <h3>{$this->lang->words['gbl_queries']}</h3>
 <div style='overflow:auto'>{$queries}</div>
</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Shows the debug included files output at the bottom of the page
 *
 * @param	int			Number of files
 * @param	string 		Files to show
 * @return	string		HTML
 */
public function global_if_output($count=0,$files="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<br /><br />
<div align='center' style='margin-left:auto;margin-right:0'>
<div class='acp-box' style='text-align:left;'>
 <h3>{$count} {$this->lang->words['gbl_inc_files']}</h3>
 <div style='padding: 4px;'>{$files}</div>
</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Shows the debug memory output at the bottom of the page
 *
 * @access	public
 * @param	string 		Memory to show
 * @param	string		Total memory used
 * @param	string		Peak memory used
 * @return	string		HTML
 */
public function global_memory_output($memory="", $total=0, $peak=0 ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<br /><br />
<div align='center' style='margin-left:auto;margin-right:0'>
<div class='acp-box' style='text-align:left;'>
	<h3>{$this->lang->words['gbl_memory']}</h3>
	<table class='ipsTable'>
		{$memory}
	</table>
	<div class='acp-actionbar' style='text-align: left;'>
		<strong>{$this->lang->words['ttlmemoryused']} {$total} ({$this->lang->words['peakmemoryused']} {$peak})</strong>
	</div>
</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the login form
 *
 * @access	public
 * @param	string 		Query string to remember
 * @param	string		Message to show
 * @param	bool		Replace the form (deprecated)
 * @param	array 		Additional data to add to the form
 * @return	string		HTML
 */
public function log_in_form( $query_string="", $message="", $replace_form=false, $additional_data=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPS_DOC_CHAR_SET	= IPS_DOC_CHAR_SET;
$publicDirectory	= PUBLIC_DIRECTORY;

$IPBHTML .= <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset={$IPS_DOC_CHAR_SET}" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Cache-Control" content="no-cache" />
	<meta http-equiv="Expires" content="Fri, 01 January 1999 01:00:00 GMT" />
	<link rel="shortcut icon" href='favicon.ico' />

	<title><%TITLE%></title>
	<script type='text/javascript'>
		jsDebug = 1;
		USE_RTE = 0;
		isRTL	= false;
		
		if ( top != self )
		{
			top.location.href = window.location.href;
		}
	</script>
HTML;


/** CSS ----------------------------------------- */
/*if ( $this->settings['use_minify'] )
{
	$_basics	= CP_DIRECTORY . '/skin_cp/acp.css,' . CP_DIRECTORY . '/skin_cp/acp_editor.css';

	$IPBHTML	.= "\n\t<link rel=\"stylesheet\" type=\"text/css\" media='screen' href=\"{$this->settings['public_dir']}min/index.php?ipbv={$this->registry->output->antiCacheHash}&amp;f={$_basics}\">\n";
}
else
{*/
	$IPBHTML .= <<<HTML
	<style type='text/css' media='all'>
		@import url( "{$this->settings['skin_acp_url']}/acp.css?ipbv={$this->registry->output->antiCacheHash}" );
		@import url( "{$this->settings['skin_acp_url']}/acp_editor.css?ipbv={$this->registry->output->antiCacheHash}" );
	</style>
HTML;
//}

$IPBHTML .= <<<HTML
	<!--[if IE]>
		<style type='text/css' media='all'>
			@import url( "{$this->settings['skin_acp_url']}/acp_ie_tweaks.css?ipbv={$this->registry->output->antiCacheHash}" );
		</style>
	<![endif]-->
HTML;

if( IN_DEV )
{
	$IPBHTML .= <<<HTML
		<style type='text/css' media='all'>
			.ipsActionBar > ul > li.inDev {
				display: inline;
			}
		</style>
HTML;
}

/** JS ----------------------------------------- */
/*if ( $this->settings['use_minify'] )
{
	$_others = ',' . CP_DIRECTORY . '/js/acp.js,' . CP_DIRECTORY . '/js/acp.' . implode('.js,' . CP_DIRECTORY . '/js/acp.', array( 'menu', 'tabs' ) ) . '.js';
	
	$IPBHTML .= <<<HTML
	
	<script type='text/javascript' src='{$this->settings['public_dir']}min/index.php?g=js&amp;ipbv={$this->registry->output->antiCacheHash}'></script>
	<script type='text/javascript' src='{$this->settings['public_dir']}min/index.php?ipbv={$this->registry->output->antiCacheHash}&amp;f={$publicDirectory}/js/ipb.js{$_others}'></script>
HTML;
}
else
{*/
	$IPBHTML .= <<<HTML
		<script type="text/javascript" src="{$this->settings['public_dir']}js/3rd_party/prototype.js?ipbv={$this->registry->output->antiCacheHash}"></script>
		<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/scriptaculous/scriptaculous-cache.js?ipbv={$this->registry->output->antiCacheHash}'></script>
		<script type="text/javascript" src='{$this->settings['public_dir']}js/ipb.js?ipbv={$this->registry->output->antiCacheHash}&amp;load={$this->settings['extraJsModules']}'></script>
		<script type='text/javascript' src='{$this->settings['js_main_url']}acp.menu.js?ipbv={$this->registry->output->antiCacheHash}'></script>
		<script type='text/javascript' src='{$this->settings['js_main_url']}acp.js?ipbv={$this->registry->output->antiCacheHash}'></script>
		<script type="text/javascript" src='{$this->settings['js_main_url']}acp.tabs.js?ipbv={$this->registry->output->antiCacheHash}'></script>
HTML;
//}

$IPBHTML .= <<<HTML
	<script type='text/javascript'>
	//<![CDATA[
		ipb.vars['st']	= "{$this->request['st']}";
		ipb.vars['base_url']	= "{$this->settings['_base_url']}";
		ipb.vars['front_url']	= "{$this->settings['board_url']}/index.php?";
		ipb.vars['app_url']		= "{$this->settings['base_url']}";
		ipb.vars['image_url'] 	= "{$this->settings['skin_app_url']}/images/";
		ipb.vars['md5_hash']	= "{$this->member->form_hash}";
		/* ---- cookies ----- */
		ipb.vars['cookie_id'] 			= '{$this->settings['cookie_id']}';
		ipb.vars['cookie_domain'] 		= '{$this->settings['cookie_domain']}';
		ipb.vars['cookie_path']			= '{$this->settings['cookie_path']}';
		ipb.templates['close_popup']	= "<img src='{$this->settings['img_url']}/close_popup.png' alt='x' />";
		ipb.templates['page_jump']		= new Template("<div id='#{id}_wrap' class='ipbmenu_content'><h3 class='bar'>{$this->lang->words['gl_pagejump']}</h3><input type='text' class='input_text' id='#{id}_input' size='8' /> <input type='submit' value='Go' class='input_submit add_folder' id='#{id}_submit' /></div>");
		ipb.templates['ajax_loading'] 	= "<div id='ajax_loading'>{$this->lang->words['gl_loading']}</div>";
	//]]>
	</script>
	<script type='text/javascript'>
		Loader.boot();
		acp = new IPBACP;

		Event.observe( window, 'load', function(e){
			$('username').focus();
		});
	</script>
	<script type="text/javascript" src="{$this->settings['cache_dir']}lang_cache/{$this->lang->lang_id}/acp.lang.js?ipbv={$this->registry->output->antiCacheHash}" charset="{$IPS_DOC_CHAR_SET}"></script>
</head>
<body id='ipboard_body' class='login_screen'>
<div id='loading-layer' style='display:none'>
	<div id='loading-layer-shadow'>
	   <div id='loading-layer-inner' >
		   <img src='{$this->settings['skin_acp_url']}/images/loading_anim.gif' style='vertical-align:middle' />
		   <span style='font-weight:bold' id='loading-layer-text'>{$this->lang->words['ajax_please_wait']}</span>
	   </div>
	</div>
</div>
HTML;

$extraClass = ( $message ) ? 'with_message' : '';

if( $replace_form )
{
	$IPBHTML .= $additional_data[0];
}
else
{
	$url = "{$this->settings['_base_url']}app=core&amp;module=login&amp;do=login-complete";
	if ( $this->settings['logins_over_https'] )
	{
		$url = str_replace( 'http://', 'https://', $url );
	}

	$IPBHTML .= <<<HTML
<form action='{$url}' method='post'>
<input type='hidden' name='qstring' id='qstring' value='{$query_string}' />
<div id='login' class='{$extraClass}'>
	<img src='{$this->settings['skin_acp_url']}/images/login_logo.png' id='login_logo' />
HTML;

if ( $message )
{
	$IPBHTML .= <<<HTML
		<div id='login_error'>{$message}</div>
HTML;
}

$IPBHTML .= <<<HTML
	<div id='login_controls'>
		<label for='username'>{$this->lang->words['gl_signinname']}</label>
		<input type='text' size='20' id='username' name='username' value='' class='textinput'>
		
		<label for='password'>{$this->lang->words['gl_password']}</label>
		<input type='password' size='20' id='password' name='password' value='' class='textinput'>
HTML;

		if( count($additional_data) > 0 )
		{
			foreach( $additional_data as $form_html )
			{
				$IPBHTML .= $form_html;
			}
		}
		
$IPBHTML .= <<<HTML
	</div>
	<div id='login_submit'>
		<input type='submit' class='button' value="{$this->lang->words['gl_signin']}" />
	</div>
</div>
</form>
HTML;

$IPBHTML .= <<<HTML
		</div>
	</div>
	</form>
	<script type='text/javascript'>
		$('username').focus();
	</script>
</body>
</html>
HTML;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Redirect hit for auto-redirecting pages (e.g. "recache all caches")
 *
 * @access	public
 * @param	string 		URL to send to
 * @param	string		Text to show
 * @param	integer		Number of seconds to wait
 * @return	string		HTML
 */
public function global_redirect_hit($url, $text="", $time=1) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type='text/javascript'>
	jsDebug = 0;
	USE_RTE = 0;
</script>
<script type="text/javascript" src="{$this->settings['public_dir']}js/3rd_party/prototype.js?ipbv={$this->registry->output->antiCacheHash}"></script>
<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/scriptaculous/scriptaculous-cache.js?ipbv={$this->registry->output->antiCacheHash}'></script>
<script type="text/javascript" src='{$this->settings['public_dir']}js/ipb.js?ipbv={$this->registry->output->antiCacheHash}&amp;load={$this->settings['extraJsModules']}'></script>
<script type='text/javascript' src='{$this->settings['js_main_url']}acp.js?ipbv={$this->registry->output->antiCacheHash}'></script>
<script type="text/javascript" src='{$this->settings['js_main_url']}acp.tabs.js?ipbv={$this->registry->output->antiCacheHash}'></script>
<script type="text/javascript">
//<![CDATA[

ipb.vars['st']	= "{$this->request['st']}";

ipb.vars['base_url']	= "{$this->settings['_base_url']}";
ipb.vars['front_url']	= "{$this->settings['board_url']}/index.php?";
ipb.vars['app_url']	= "{$this->settings['base_url']}";
ipb.vars['image_url']	= "{$this->settings['skin_app_url']}/images/";
ipb.vars['md5_hash']	= "{$this->member->form_hash}";
/* ---- cookies ----- */
ipb.vars['cookie_id'] 			= '{$this->settings['cookie_id']}';
ipb.vars['cookie_domain'] 		= '{$this->settings['cookie_domain']}';
ipb.vars['cookie_path']			= '{$this->settings['cookie_path']}';

Loader.boot();
acp = new IPBACP;
//]]>
</script>

<style type='text/css' media='all'>
	@import url( "{$this->settings['skin_acp_url']}/acp.css?ipbv={$this->registry->output->antiCacheHash}" );
</style>

<meta http-equiv='refresh' content='{$time}; url={$url}' />

<div class='information-box'>
	<h4>{$this->lang->words['gbl_page_redirecting']}</h4>
	{$this->lang->words['page_will_refresh']} <a href='$url'>{$this->lang->words['refresh_dont_wait']}</a>
</div>
<br />
<div class='redirector'>
	<div class='info'>{$text}</div>	
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Initialize global redirection javascript for AJAX redirecting
 *
 * @access	public
 * @param	string 		URL to redirect to
 * @param	string		Text to show
 * @param	string		Additional text to add
 * @return	string		HTML
 */
public function global_ajax_redirect_init($url='', $text='', $addtotext='') {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='redirector'>
	<div class='info' id='refreshbox'>{$this->lang->words['gbl_initializing']}</div>	
</div>
<script type='text/javascript'>
//<![CDATA[
acp.ajaxRefresh( '$url', '$text', $addtotext );
//]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Global redirection completed page
 *
 * @access	public
 * @param	string 		Text to show
 * @return	string		HTML
 */
public function global_redirect_done($text='This function has now finished executing') {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<style type='text/css' media='all'>
	@import url( "{$this->settings['skin_acp_url']}/acp.css?ipbv={$this->registry->output->antiCacheHash}" );
</style>

<div class='redirector complete'>
	<div class='info'>{$text}</div>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * General redirect page with message
 *
 * @access	public
 * @param	string 		URL to send to
 * @param	integer		Number of seconds to wait before redirecting
 * @param	string		Text to display
 * @return	string		HTML
 */
public function global_redirect_halt($url) {

$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
<div class='warning'>
 <h4>{$this->lang->words['redirect_halt_title']}</h4>
 	<p><strong>{$this->registry->output->global_error}</strong></p>
	<br />
	<ul>
		<li style='font-weight:bold'><a href='$url'>{$this->lang->words['redirect_halt_continue']}</a></a>
		<li><a href='{$this->settings['this_url']}'>{$this->lang->words['redirect_repeat_step']}</a>
	</ul>
</div>
EOF;

$this->registry->output->global_error = '';

//--endhtml--//
return $IPBHTML;
}

/**
 * Generate sub navigation menu for sidebar
 *
 * @access	public
 * @param	array 		Menu data
 * @return	string		HTML
 */
public function menu_sub_navigation( $menu ) {

$main_html = array();
$IPBHTML   = "";
//--starthtml--//

if( is_array($menu[ ipsRegistry::$current_application ]) AND count($menu[ ipsRegistry::$current_application ]) )
{
foreach( $menu[ ipsRegistry::$current_application ] as $id => $data )
{
	$links = "";
	$_id   = preg_replace( '/^\d+?_(.*)$/', "\\1", $id );

	if ( $_id != ipsRegistry::$current_module )
	{
		continue;
	}

	foreach( $data['items'] as $_id => $_data )
	{
		$_url   = ( $_data['url'] ) ? "&amp;{$_data['url']}" : "";
$links .= <<<EOF
		<div class='menulinkwrapBlock'>
			<a href="{$this->settings['base_url']}module={$_data['module']}&amp;section={$_data['section']}{$_url}">{$_data['title']}</a>
		</div>
EOF;
	}
	
	if ( $links )
	{
$main_html[] = <<<EOF
<!-- MENU FOR {$data['title']}-->
<div class='menuouterwrap'>
  <div class='menucatwrapBlock'>{$data['title']}</div>
  {$links}
</div>
<!-- / MENU FOR {$data['title']}-->
EOF;
	}
}
}

if ( is_array( $main_html ) AND count( $main_html ) )
{
$IPBHTML .= <<<EOF
<div id='subMenuWrap'>
EOF;
$IPBHTML .= implode( "<br />", $main_html );
$IPBHTML .= <<<EOF
</div>
EOF;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Menu category wrapper for sidebar "categories"
 *
 * @access	public
 * @param	array 		Links to show
 * @param	string		Module (cleaned)
 * @param	array 		Menu items to show
 * @return	string		HTML
 */
public function menu_cat_wrap( $links=array(), $clean_module="", $menu=array() ) {

$IPBHTML = "";
$seen    = 0;
$titles  = 0;

//--starthtml--//

	foreach( $links as $app => $module )
	{
		$IPBHTML	.= "<ul>\n";
		$_CHILD		= '';
		$_MENU		= '';
		
		foreach( $module as $data )
		{
			$class = '';

			if ( $app == ipsRegistry::$current_application AND $clean_module == $data['module'] )
			{
				$class = 'active';
			}

			if( isset( $menu[ $app ] ) && is_array( $menu[ $app ] ) )
			{
				foreach( $menu[ $app ] as $id => $__data )
				{
					//print_r($__data);exit;
					preg_match( '/^(\d+?)_(.*)$/', $id, $result );

					if ( $result[2] != $data['module'] )
					{
						continue;
					}

					/* Heres where we check whether this is a single item */
					if( intval($result[1]) === 0 )
					{
						$_single_item	= true;
						$_count			= 0;
						
						foreach( $menu[ $app ] as $__k => $__v )
						{
							if ( preg_match( '/(\d+?)_' . $result[2] . "/", $__k ) )
							{
								$_count++;
							}
						}

						if( $_count > 1 )
						{
							$_single_item	= false;
						}

						if( $_single_item )
						{
							$_url   = ( $__data['items'][0]['url'] ) ? "&amp;{$__data['items'][0]['url']}" : "";
							
							$_MENU .=  <<<EOF
								<!-- UHM MENU FOR {$data['title']}-->
								<li class='{$class}'>
									<a href='{$this->settings['_base_url']}app={$app}&amp;module={$__data['items'][0]['module']}&amp;section={$__data['items'][0]['section']}{$_url}'>{$__data['title']}</a>
								</li>
EOF;

							continue(2);
						}
					}
					/* /end */

					if ( count( $__data['items'] ) > 1 )
					{
	$_CHILD .= <<<EOF
					<li>
						<a href='{$this->settings['_base_url']}app={$app}&amp;module={$__data['items'][0]['module']}&amp;section={$__data['items'][0]['section']}&amp;{$__data['items'][0]['url']}'>{$__data['items'][0]['title']}</a>
						<ul>
EOF;
						
						$_seen				= 0;
						$seen_in_this_group	= 0;
						foreach( $__data['items'] as $_id => $_data )
						{
							$_seen++;
							
							if( $seen_in_this_group == 0 )
							{
								$seen_in_this_group++;
								continue;
							}
							
							$_class = '';
							$_url   = ( $_data['url'] ) ? "&amp;{$_data['url']}" : "";

							if ( $_seen == count( $__data['items'] ) )
							{
								$_class = 'last';
							}

	$_CHILD .= <<<EOF
							<li class='{$_class}'><a href="{$this->settings['_base_url']}app={$app}&amp;module={$_data['module']}&amp;section={$_data['section']}{$_url}">{$_data['title']}</a></li>
EOF;
						}

	$_CHILD .= <<<EOF
						</ul>
					</li>
EOF;
					####### / MORE THAN 1 CHILD ITEM	#######
					}
					else
					{
						$_url = ( $__data['items'][0]['url'] ) ? "&amp;{$__data['items'][0]['url']}" : "";
	$_CHILD .= <<<EOF
					<li>
						<a href="{$this->settings['_base_url']}app={$app}&amp;module={$__data['items'][0]['module']}&amp;section={$__data['items'][0]['section']}{$_url}">{$__data['items'][0]['title']}</a>
					</li>

EOF;
					}
				}
				
				if( $_CHILD )
				{
				$_MENU .= <<<EOF
						<!-- MENU FOR {$data['title']}-->
						<li class='{$class} has_sub'>
							{$data['title']}
							<ul>
								{$_CHILD}
							</ul>
						</li>
EOF;
				}
				$_CHILD = '';

			}
		}

		$IPBHTML .= <<<EOF

		{$_MENU}
		</ul>
EOF;

	}

//--endhtml--//
	return $IPBHTML;
}

/**
 * Navigation HTML wrapper
 *
 * @access	public
 * @param	string 		Menu content
 * @return	string		HTML
 */
public function wrap_nav($content="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<ol id='breadcrumb'>
	{$content}
</ol>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Global informational message to display
 *
 * @access	public
 * @return	string		HTML
 */
public function global_message() {
$IPBHTML = "";
//--starthtml--//

if( !$this->registry->getClass('output')->persistent_message )
{
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	document.observe("dom:loaded", function(){
		ipb.global.showInlineNotification( "{$this->registry->getClass('output')->global_message}", { showClose: false } );
	});
</script>
EOF;
}
else
{
$IPBHTML .= <<<EOF
<div class='information-box'>
EOF;
 	$IPBHTML .= $this->registry->getClass('output')->global_message;

$IPBHTML .= <<<EOF
</div>
<br />
EOF;
}

//--endhtml--//
return $IPBHTML;
}


/**
 * Global error message to display
 *
 * @access	public
 * @return	string		HTML
 */
public function global_error_message() {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='warning'>
	<h4>{$this->lang->words['ipb_message']}</h4>
	{$this->registry->output->global_error}
</div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Pagination wrapper
 *
 * @access	public
 * @param	array 		Work data
 * @param	array 		Pagination data
 * @return	string		HTML
 */
public function paginationTemplate( $work, $data ) {
$IPBHTML = "";
//--starthtml--//

if( $work['pages'] > 1 )
{
$IPBHTML .= <<<EOF
	<ul class='pagination'>
EOF;

	if( !$data['noDropdown'] )
	{
		$IPBHTML .= <<<EOF
		<li class='pagejump pj{$data['uniqid']}'>
			<img src='{$this->settings['skin_acp_url']}/images/dropdown.png' alt='+' />
			<script type='text/javascript'>
				ipb.global.registerPageJump( '{$data['uniqid']}', { url: "{$data['baseUrl']}", stKey: '{$data['startValueKey']}', perPage: {$data['itemsPerPage']}, totalPages: {$work['pages']} } );
			</script>
		</li>
EOF;
	}

if( 1 < ($work['current_page'] - $data['dotsSkip']) )
{
$IPBHTML .= <<<EOF
	<li class='first'><a href='{$data['baseUrl']}&amp;{$data['startValueKey']}=0' title='{$this->lang->words['tpl_gotofirst']}' rel='start'>{$this->lang->words['_laquo']} {$this->lang->words['tpl_isfirst']}</a></li>
EOF;
}

if( $work['current_page'] > 1 )
{
	$stkey = intval( $data['currentStartValue'] - $data['itemsPerPage'] );
$IPBHTML .= <<<EOF
	<li class='prev'><a href="{$data['baseUrl']}&amp;{$data['startValueKey']}={$stkey}" title="{$this->lang->words['tpl_prev']}" rel='prev'>{$this->lang->words['pg_prev']}</a></li>
EOF;
}

if( count($work['_pageNumbers']) AND is_array($work['_pageNumbers']) )
{
	foreach( $work['_pageNumbers'] as $_real => $_page )
	{
		if( $_real == $data['currentStartValue'] )
		{
		$IPBHTML .= <<<EOF
			<li class='active'>{$_page}</li>
EOF;
		}
		else
		{
		$IPBHTML .= <<<EOF
			<li><a href="{$data['baseUrl']}&amp;{$data['startValueKey']}={$_real}" title="{$_page}">{$_page}</a></li>
EOF;
		}
	}
}

if( $work['current_page'] < $work['pages'] )
{
	$stkey = intval( $data['currentStartValue'] + $data['itemsPerPage'] );
$IPBHTML .= <<<EOF
	<li class='next'><a href="{$data['baseUrl']}&amp;{$data['startValueKey']}={$stkey}" title="{$this->lang->words['tpl_next']}" rel='next'>{$this->lang->words['pg_next']}</a></li>
EOF;
}

if( !empty( $work['_showEndDots'] ) )
{
	$stkey = intval( ( $work['pages'] - 1 ) * $data['itemsPerPage'] );
$IPBHTML .= <<<EOF
	<li class='last'><a href="{$data['baseUrl']}&amp;{$data['startValueKey']}={$stkey}" title="{$this->lang->words['tpl_gotolast']}" rel='last'>{$this->lang->words['tpl_islast']} {$this->lang->words['_raquo']}</a></li>
EOF;
}

$_tplpages	= sprintf( $this->lang->words['tpl_pages_acp'], $work['current_page'], $work['pages'] );

$IPBHTML .= <<<EOF
		<li class='total'>({$_tplpages})</li>
	</ul>
EOF;
}
else
{
	$IPBHTML .= <<<EOF
	<span class='pagination no_pages'>{$this->lang->words['page_1_of_1']}</span>
EOF;
}
//--endhtml--//
return $IPBHTML;
}


/**
 * System error page
 *
 * @access	public
 * @param	string 		Error message to show
 * @param	integer		Error code
 * @param	string		Error title
 * @param	string		Document character set
 * @return	string		HTML
 */
public function system_error( $msg, $code=0, $title='', $IPS_DOC_CHAR_SET=IPS_DOC_CHAR_SET )
{
$title = !empty( $title ) ? $title : $this->lang->words['gbl_system_error'];

if( $code )
{
	$finalMessage = "[#{$code}] " . ( is_array( $msg ) ? implode( "<br />", $msg ) : $msg );
}
else
{
	$finalMessage = is_array( $msg ) ? implode( "<br />", $msg ) : $msg;
}

$HTML .= <<<EOF
<div class='warning'>
 <h4>{$title}</h4>
 	<p><strong>{$finalMessage}</strong></p>
	<br />
	<ul>
		<li><a href='javascript:history.go(-1)'>{$this->lang->words['gbl_go_back']}</a>
		<li><a href='{$this->settings['_base_url']}'>{$this->lang->words['gbl_go_to_dashboard']}</a>
		<li><a href='{$this->settings['base_url']}'>{$this->lang->words['gbl_go_to_module_home']}</a>
	</ul>
</div>
EOF;

return $HTML;
}

/**
 * HTML for quick help popup boxes
 *
 * @access	public
 * @param	string 		Title
 * @param	string		Help contents
 * @return	string		HTML
 */
public function quickHelp( $title, $body ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='acp-box'>
 <h3>{$title}</h3>
	<div style='padding: 4px; line-height: 1.5'>{$body}</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

}