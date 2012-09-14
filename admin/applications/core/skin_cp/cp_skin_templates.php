<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Look and feel skin file
 * Last Updated: $Date: 2012-05-24 16:20:59 -0400 (Thu, 24 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 10792 $
 */
 
class cp_skin_templates
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member   	= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang 		= $this->registry->class_localization;
	}


/**
 * Externally edit form
 *
 * @param	array		Form array
 * @return	string		HTML
 */
public function externalEditOverview( $form )
{
$IPBHTML = "";
//--starthtml--//

$url   = str_replace( array( 'http://', 'https://' ), '', $this->settings['_original_base_url'] );
$parts = explode( '/', $url );
$host  = array_shift( $parts );
$path  = ltrim( implode( '/', $parts ) . '/dav.php', '/' );

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['sk_ee_main_title']}</h2>
	<p class='section_info'>{$this->lang->words['sk_ee_main_desc']}</p>
</div>
EOF;

if ( stristr( @php_sapi_name(), 'fcgi' ) OR isset( $_SERVER['FCGI_ROLE'] ) )
{
	$IPBHTML .= <<<EOF
	<div class='information-box'>
		<strong>{$this->lang->words['sk_ee_webdav_fgci_title']}</strong>
		<p>{$this->lang->words['sk_ee_webdav_fgci_desc']}</p>
		<br />
		<pre>
&lt;IfModule mod_rewrite.c&gt;
RewriteEngine on
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]
&lt;/IfModule&gt;
		</pre>
	</div>
	<br />
EOF;
}

$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=save' method='post' enctype='multipart/form-data'>
<div class='acp-box'>
	<h3>{$this->lang->words['sk_ee_title']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_ee_enable']}</strong>
			</td>
			<td class='field_field'>{$form['webdav_on']}</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['sk_submit']}' class='realbutton' />
	</div>
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['sk_ee_webdav_title']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_ee_webdav_host']}</strong>
			</td>
			<td class='field_field'>{$host}</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_ee_webdav_path']}</strong>
			</td>
			<td class='field_field'>{$path}</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_ee_webdav_username']}</strong>
			</td>
			<td class='field_field'>{$this->memberData['name']}</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_ee_webdav_password']}</strong>
			</td>
			<td class='field_field desctext'>{$this->lang->words['sk_ee_webdav_ur_password']}</td>
		</tr>
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}
	
/**
 * Show form to add/edit skin set
 *
 * @param	array 		Form bits
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function skinGenerator( $form, $errors ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['skin_gen_new_vis_skin_title']}</h2>
	<p class='section_info'>{$this->lang->words['skin_gen_new_vis_skin_desc']}</p>
</div>

<form id='skinGen' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=skinGenStepOneComplete' method='post'>
<div class='acp-box'>
	<h3>Basic Settings</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<th colspan='2'>{$this->lang->words['sk_basics']}</th>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_settitle']}</strong>
			</td>
			<td class='field_field'>
				{$form['set_name']}
			</td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['sk_setauthor']}</th>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_setauthorname']}</strong>
			</td>
			<td class='field_field'>
				{$form['set_author_name']}
				<br /><span class='desctext'>*{$this->lang->words['sk_optional']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_setauthorurl']}</strong>
			</td>
			<td class='field_field'>
				{$form['set_author_url']}
				<br /><span class='desctext'>*{$this->lang->words['sk_optional']}</span>
			</td>
		</tr>				
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['skin_gen_next']}' class='button primary' />
    </div>
</div>
</form>
EOF;
//--endhtml--//
return $IPBHTML;
}

/**
 * Skin generator Ready
 *
 * @param	int 		Skin set
 * @return	string		HTML
 */
public function skinGeneratorReady( $skinSetId ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='acp-box' id='ipsSkinMessage'>
	<h3>{$this->lang->words['skin_gen_ready_title']}</h3>
	<div class='pad fixed_inner'>
		{$this->lang->words['skin_gen_ready_desc']}
		<br />
		<br />
		<a href='../' class='button redbutton primary' target='_blank'>{$this->lang->words['skin_gen_ready_button_launch']}</a>
		<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=setEdit&amp;set_id={$skinSetId}' class='button'>{$this->lang->words['skin_gen_ready_button_edit']}</a>
		<br />&nbsp;
	</div>
</div>
<script type="text/javascript">
setTimeout( function() { $('ipsSkinMessage').fade() }, 12000 );
</script>
<br />
EOF;
//--endhtml--//
return $IPBHTML;
}

/**
 * New skin popup dialogue
 *
 * @param	array		$album		Album data
 * @param	array		$last10		Last 10 album images
 * @return	@e string	HTML
 */
public function newSkinSetPopUp( $hasActiveLicense ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['skin_gen_new_title']}</h3>
	<table class='ipsTable'>
		<tr>
HTML;

	if ( $hasActiveLicense )
	{
		$IPBHTML .= <<<HTML
			<td><img src='{$this->settings['skin_acp_url']}/images/skins/visual.png' alt='' style='width:100px;height:100px' /></td>
			<td valign='top'>
				<strong>{$this->lang->words['skin_gen_new_visual_title']}</strong>
				<p class='desctext'>{$this->lang->words['skin_gen_new_visual_desc']}</p>
				<br /><br />
				<p><a href="{$this->settings['base_url']}{$this->form_code}&amp;do=skinGenStepOne" class="button primary">{$this->lang->words['skin_gen_next_dotdotdot']}</a></p>
			</td>
HTML;
	}
	else
	{
		$url  = "{$this->settings['base_url']}&amp;app=core&amp;module=tools&amp;section=licensekey";
		$lang = sprintf( $this->lang->words['vse_disabled_why'], $url );
		
		$IPBHTML .= <<<HTML
			<td><img src='{$this->settings['skin_acp_url']}/images/skins/visual.png' alt='' style='width:100px;height:100px' /></td>
			<td valign='top'>
				<strong>{$this->lang->words['skin_gen_new_visual_title']}</strong>
				<div class='warning'>
					<h4>{$this->lang->words['vse_disabled']}</h4>
					<p>$lang</p>
				</div>
			</td>
HTML;
	}
	
$IPBHTML .= <<<HTML
		</tr>
		<tr>
			<td><img src='{$this->settings['skin_acp_url']}/images/skins/manual.png' alt='' style='width:100px;height:100px' /></td>
			<td valign='top'>
				<strong>{$this->lang->words['skin_gen_manual_title']}</strong>
				<p class='desctext'>{$this->lang->words['skin_gen_manual_desc']}</p>
				<br /><br />
				<p><a href="{$this->settings['base_url']}{$this->form_code}&amp;do=setAdd" class="button primary">{$this->lang->words['skin_gen_next_dotdotdot']}</a></p>
			</td>
		</tr>
	</table>
</div>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the skin differences result
 *
 * @param	string 		Differences result
 * @return	string		HTML
 */
public function differenceResult( $difference )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='acp-box'>
	<h3>{$this->lang->words['sk_temp_diff']}</h3>
	<div class='pad fixed_inner'>
		{$difference}
	</div>
	<div style='padding: 4px;'><span class='diffred'>{$this->lang->words['sk_removedhtml']}</span> &middot; <span class='diffgreen'>{$this->lang->words['sk_addedhtml']}</span></div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Easy logo changer
 *
 * @param	string		Warning
 * @param	string		Current URL
 * @param	int			Current id
 * @return	string		HTML
 */
public function easyLogo( $warning, $currentUrl, $currentId )
{
$IPBHTML = "";
//--starthtml--//

$_skin_list		= $this->registry->output->generateSkinDropdown();
array_unshift( $_skin_list, array( 0, $this->lang->words['sm_skinnone'] ) );

$skinList		= ipsRegistry::getClass('output')->formDropdown( "skin", $_skin_list );

$urlField		= $this->registry->output->formInput( 'logo_url', !empty($_POST['logo_url']) ? htmlspecialchars( $_POST['logo_url'], ENT_QUOTES ) : $currentUrl );
$uploadField	= $this->registry->output->formUpload();

$IPBHTML .= <<<EOF
<script type="text/javascript" src="{$this->settings['js_app_url']}acp.easylogo.js"></script>
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=finish' method='post' enctype='multipart/form-data'>
<input type='hidden' name='replacementId' id='replacementId' value='{$currentId}' />
<div class='acp-box'>
	<h3>{$this->lang->words['sk_easylogochanger']}</h3>
EOF;

if( $warning )
{
	$IPBHTML .= <<<EOF
	<div class='redbox' style='padding:4px'>{$this->lang->words['sk_elc_warning']}</div>
EOF;
}

$IPBHTML .= <<<EOF
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_applywhichset']}</strong>
			</td>
			<td class='field_field'>{$skinList}<br /><span class="desctext">{$this->lang->words['sk_applywhichset_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_urlnewlogo']}</strong>
			</td>
			<td class='field_field'>{$urlField}<br /><span class="desctext">{$this->lang->words['sk_urlnewlogo_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_uploadlogo']}</strong>
			</td>
			<td class='field_field'>{$uploadField}<br /><span class="desctext">{$this->lang->words['sk_uploadlogo_info']}</span></td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['sk_submit']}' class='realbutton' />
	</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Search and replace list template groups
 *
 * @param	array 		Template groups
 * @param	array 		Skin set data
 * @param	array 		Session data
 * @return	string		HTML
 */
public function searchandreplace_listTemplateGroups( $templateGroups, $setData, $sessionData ) {

$IPBHTML = "";
//--starthtml--//

$_keys        = array_keys( $templateGroups );
$_json        = json_encode( array( 'groups' => $templateGroups ) );
$_first       = array_shift( $_keys );
$_setData     = json_encode( $setData );
$_sessionData = json_encode( $sessionData );

$IPBHTML .= <<<EOF
<form id='sandrForm' name='sandrForm'>
<div class='acp-box'>
	<h3>{$this->lang->words['sandr_search_results_for']} {$setData['set_name']}</h3>
	<table class='ipsTable' id='tplate_groupList'></table>
EOF;

if ( ! $sessionData['sandr_search_only'] )
{
	$IPBHTML .=<<<EOF
		<div class='acp-actionbar' style='text-align:right'>
			<input type='button' value='{$this->lang->words['sk_replaceselected']}' id='replaceButton' onclick='IPB3TemplatesSandR.performReplacement()' class='button primary' />
		</div>
EOF;
}

$IPBHTML .=<<<EOF
</div>
</form>

<!-- templates -->
<div style='display:none'>
	<table id='tplate_groupRow'>
		<tr>
			<td id='groupRow_#{groupName}' style='width:30%;'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/folder.png' />
				<span style='font-weight:bold'><a href='javascript:void(0);' onclick="IPB3TemplatesSandR.toggleTemplates('#{groupName}')">#{groupName}</a></span>
			</td>
			<td>
				<span style='font-size:9px'>(#{_matches} matches)</span>
			</td>
			<td>
				<div id='groupRowCbox_#{groupName}' style='float:right;display:none'>
					<input type='checkbox' id='cbox_group_#{groupName}' value='1' name='groups[#{groupName}]' onclick="IPB3TemplatesSandR.toggleGroupBox('#{groupName}')" />
				</div>
			</td>
		</tr>
		<tr id='groupRowTemplates_#{groupName}' style='display:none'>
			<td colspan='3'>
				<table class='ipsTable'>
				</table>
			</td>
		</tr>
	</table>

	<table id='tplate_templateRow'>
		<tr>
			<td style='padding-left: 28px;' id='tplate_templaterow_#{template_id}'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/template.png' />
				<span style='font-weight:bold'><a href='javascript:void(0);' onclick="IPB3TemplatesSandR.loadTemplateEditor('#{template_id}')">#{template_name}</a></span>
			</td>
			<td>
				<div id='templateRowCbox_#{template_id}' style='float:right;display:none'>
					<input type='checkbox' id='cbox_template_#{template_group}_#{template_id}' class='cboxGroup#{template_group}' onclick="IPB3TemplatesSandR.checkGroupBox('#{template_group}')" value='1' name='templates[#{template_id}]' />
				</div>
			</td>
		</tr>
	</table>

	<div id='tplate_templateEditor'>
		<div id='tplate_editor_#{template_id}' class='acp-box' style='padding:0px;margin:0px;width:800px'>
			<div id='inlineFormInnerTitle' style='padding:0px'><h3>{$this->lang->words['sk_editing']} "#{template_name}" {$this->lang->words['in']} "#{template_group}"</h3></div>
			<div id='inlineFormInnerContent' class='pad'>
				<input type='text' id='tplate_dataBox_#{template_id}' value='#{template_data}' style='width:100%;font-size:14px' />
				<br /><br />
				<textarea id='tplate_editBox_#{template_id}' style='width:100%;height:400px'>#{template_content}</textarea>
				<div style='text-align:right;padding:6px;'>
					<input type='button' class='realbutton' value='{$this->lang->words['sk_save']}' onclick="IPB3TemplatesSandR.saveTemplateBit('#{template_id}')" />
					&nbsp;
					<input type='button' class='realbutton' value='{$this->lang->words['sk_close']}' onclick="IPB3TemplatesSandR.cancelTemplateBit('#{template_id}')" />
				</div>
			</div>
			
		</div>
	</div>
</div>
<!-- / templates -->
<script type="text/javascript" src="{$this->settings['js_app_url']}ipb3TemplateSandR.js"></script>
<script type='text/javascript'>
	var IPB3TemplatesSandR             = new IPBTemplateSandR();
	IPB3TemplatesSandR.templateGroups  = $_json;
	IPB3TemplatesSandR.setData         = $_setData;
	IPB3TemplatesSandR.sessionData	   = $_sessionData;
	document.observe("dom:loaded", function(){
		IPB3TemplatesSandR.init();
	} );
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Form to perform a search and replace
 *
 * @param	string		Skin options list
 * @param	int			Number of bits
 * @param	int			Number to do per refresh
 * @return	string		HTML
 */
public function searchandreplace_form( $skinOptionList, $numberbits, $pergo ) {

$IPBHTML = "";
//--starthtml--//

/* Removed option :
<p><input type='checkbox' value='1' name='searchParents' /> {$this->lang->words['sk_searchininfo']}</p>
*/

$IPBHTML .= <<<EOF
<div class='information-box'>
 {$this->lang->words['sk_searchreplaceinfo']}
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['sk_searchandreplace']}</h3>
	<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=start' enctype='multipart/form-data' method='POST'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	<input type='hidden' value='1' name='searchParents' />
	<table class='ipsTable double_pad'>
	<tr>
		<td class='field_title'><strong class='title'>{$this->lang->words['sk_selectskinset']}</strong>
		<td class='field_field'>
			<select name='setID'>{$skinOptionList}</select>
			<!-- Option removed -->
		</td>
	</tr>
	<tr>
		<td class='field_title'><strong class='title'>{$this->lang->words['sk_searchfor']}</strong></td>
		<td class='field_field'><textarea name='searchFor' id='searchFor' style='height:100px;width:100%'></textarea></td>
	</tr>
	<tr>
		<td class='field_title'><strong class='title'>{$this->lang->words['sk_replacewith']}</strong><p>{$this->lang->words['sk_replacewith_info']}</td>
		<td class='field_field'>
			<textarea name='replaceWith' id='replaceWith' style='height:100px;width:100%'></textarea>
			<p><input type='checkbox' value='1' id='isRegex' name='isRegex'> {$this->lang->words['sk_regularexpression']}</p>
		</td>
	</tr>
	<tr>
	</table>
	 <div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['sk_continue']}' class='button primary' />
	 </div>
	</form>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Export a skin difference result
 *
 * @param	array 		Session data
 * @param	array 		Reports
 * @param	array 		Missing parts
 * @param	array 		Changed parts
 * @return	string		HTML
 */
public function skindiff_export( $sessionData, $reports, $missing, $changed ) {

$date = gmdate('r');
$howmany = sprintf( $this->lang->words['sk_howmanytemps'], $missing, $changed );
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<html>
 <head>
  <title>{$sessionData['_title']} {$this->lang->words['sk_title_export']}</title>
  <style type="text/css">
   BODY
   {
   	font-family: verdana;
   	font-size:11px;
   	color: #000;
   	background-color: #CCC;
   }
   
   del,
   .diffred
   {
	   background-color: #D7BBC8;
	   text-decoration:none;
   }
   
   ins,
   .diffgreen
   {
	   background-color: #BBD0C8;
	   text-decoration:none;
   }
   
   h1
   {
   	font-size: 18px;
   }
   
   h2
   {
   	font-size: 18px;
   }
  </style>
 </head>
<body>
  <div style='padding:4px;border:1px solid #000;background-color:#FFF;margin:4px;'>
  <h1>{$sessionData['diff_session_title']} ({$this->lang->words['sk_exported']}: {$date})</h1>
  <strong>{$howmany}</strong>
  </div>
  <br />
EOF;

	if ( count( $reports ) )
	{
		foreach( $reports as $group => $key )
		{
			foreach( $reports[ $group ] as $key => $report )
			{
				
				$report['change_data_content'] = str_replace( "\n", "<br>", $report['change_data_content']);
				$report['change_data_content'] = str_replace( "&gt;&lt;", "&gt;\n&lt;" ,$report['change_data_content']);
				$report['change_data_content'] = str_replace( "\t", "&nbsp; &nbsp; ", $report['change_data_content'] );
				$report['change_data_content'] = preg_replace( '#(?<!(\<del|\<ins)) {1}(?!:style)#i', "&nbsp;" ,$report['change_data_content']);
			
				//$prefix = ( $report['change_is_new'] ) ? $this->lang->words['mergeexportprefix'] : '';
				//removed {$prefix} from the html below since this line above is commented (terabyte)
				
				$IPBHTML .= <<<EOF
					<div style='padding:4px;border:1px solid #000;background-color:#FFF;margin:4px;'>
						<h2>{$report['change_data_group']} <span style='color:green'>&gt;</span> {$report['change_data_title']}</h2>
						<hr>
						{$report['change_data_content']}
					</div>
EOF;
			}
		}
	}

$IPBHTML .= <<<EOF
  <br />
  <div style='padding:4px;border:1px solid #000;background-color:#FFF;margin:4px;'>
   <span class='diffred'>{$this->lang->words['sk_removedhtml']}</span> &middot; <span class='diffgreen'>{$this->lang->words['sk_addedhtml']}</span>
  </div>
</body>
<html>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Overview of completed skin diff reports
 *
 * @param	array 		Session data
 * @param	array 		Reports
 * @param	array 		Missing bits
 * @param	array 		Changed bits
 * @return	string		HTML
 */
public function skindiff_reportOverview( $sessionData, $reports, $missing, $changed ) {

$IPBHTML = "";
//--starthtml--//

$textblob = '';
$tstats   = array( 'conflicts' => 0, 'needmerge' => 0, 'uncommitted' => 0, 'committed' => 0 );
		
$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['mergereporttitle']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='#' class='ipbmenu' id='mOptions'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' /> {$this->lang->words['mergerepoptions']} <img src='{$this->settings['skin_acp_url']}/images/useropts_arrow.png' /></a>
		</li>		
	</ul>
</div>

<ul class='ipbmenu_content' id='mOptions_menucontent' style='display: none'>
	<li><input type="checkbox" class='__cbx' id='_cb_nocustom' /> <img src='{$this->settings['skin_acp_url']}/images/icons/page_white_magnify.png' /> {$this->lang->words['merge__showall']}</li>
	<li><input type="checkbox" class='__cbx' id='_cb_merged' /> <img src='{$this->settings['skin_acp_url']}/images/icons/shape_move_front.png' /> {$this->lang->words['merge__showmerged']}</li>
	<li><input type="checkbox" class='__cbx' id='_cb_conflicted' /> <img src='{$this->settings['skin_acp_url']}/images/icons/exclamation.png' /> {$this->lang->words['merge__showconflict']}</li>
	<li><input type="checkbox" class='__cbx' id='_cb_uncommitted' /> <img src='{$this->settings['skin_acp_url']}/images/icons/bullet_red.png' /> {$this->lang->words['merge__showcommit']}</li>
	<li><input type="checkbox" class='__cbx' id='_cb_committed' /> <img src='{$this->settings['skin_acp_url']}/images/icons/bullet_green.png' /> {$this->lang->words['merge__showcommita']}</li>
</ul>
<script type="text/javascript" src="{$this->settings['js_app_url']}IPB3TemplateDiffResults.js"></script>
<script type="text/javascript">
ipb.templates['resolve_box'] = "<div class='mergeResolveBox'><ul><li class='_mrb#{id} _customWins'>{$this->lang->words['resolve__custom']}</li><li class='_mrb#{id} _newWins'>{$this->lang->words['resolve__new']}</li></ul></div>";
</script>
<form id='diffForm' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=multiManage&amp;merge_id={$sessionData['merge_id']}' method='post'>
<div class='acp-box'>
	<div style="float:right;padding-right:32px;padding-top:11px"><input type="checkbox" id="cboxall" /></div>
	<h3>{$sessionData['_title']}</h3>
	<table class='ipsTable double_pad'>

EOF;

if ( count( $reports ) )
{
	foreach( $reports as $group => $key )
	{
		$_group   = str_replace( array( ' ', '.', '_' ), '', $group );
		
		/* Group row */
		$IPBHTML .= <<<EOF
		<tr>
		 	<th class='sub double_pad __group _xgid{$_group}' colspan='7'>
		   		<strong>{$group}</strong>
		 	</th>
		</tr>
EOF;
	
		foreach( $reports[ $group ] as $key => $report )
		{
			$_group      = str_replace( array( ' ', '.', '_' ), '', $group );
			$_safe       = $report['change_id'];
			$_diffIs     = ( $report['_is'] == 'new' ) ? "<span style='color:green'>{$this->lang->words['sk_new']}</span>" : "<span style='color:red'>{$this->lang->words['sk_changed']}</span>";
			$_icon       = $report['change_data_type'] == 'css' ? 'text_rich_colored.png' : 'text.png';
			$_diffIcon   = ( $report['_is'] == 'new' ) ? 'page_add.png' : 'page_red.png';
			$_diffTitle  = ( $report['_is'] == 'new' ) ? $this->lang->words['mergereportnewitem'] : sprintf( $this->lang->words['mergereportdifferences'], $report['_diffs'] );
			$_mergeIcon  = ( $report['_is'] == 'new' OR ! $report['change_can_merge'] ) ? 'blank.gif' : ( ( intval( $report['_conflicts'] ) > 0 ) ? 'icons/exclamation.png' : 'blank.gif' );//'shape_move_front.png' );
			$_mergeTitle = ( intval( $report['_conflicts'] ) > 0 ) ? sprintf( $this->lang->words['mergereportconflicts'], $report['_conflicts'] ) : $this->lang->words['mergereportnoconflict'];
			$_mergeClass = ( intval( $report['_conflicts'] ) > 0 ) ? '__conflicts' : '';
			$_comIcon    = ( $report['_is'] == 'new'  OR ( ! $report['change_can_merge'] OR ( $report['change_final_content'] ) ) ) ? 'bullet_black.png' : ( ( $report['change_changes_applied'] ) ? 'bullet_green.png' : 'bullet_red.png' );
			$_comClass   = ( $report['_is'] == 'new'  OR ! $report['change_can_merge'] ) ? '_nochanges' : ( ( $report['change_changes_applied'] ) ? '__committed' : '_uncommitted' );
			$_mainClass  = ( $report['_is'] == 'new' ) ? '_off' : ( ( $report['change_changes_applied'] ) ? '_amber' : '_off' );
			$_cbExtra	 = ( $report['_is'] == 'new' ) ? ' disabled="disabled"' : '';
			$_isMergeClass = ( $report['change_can_merge'] ) ? '__canMerge' : '';
			
			/* Gather stats */
			if ( ! $report['change_final_content'] AND $report['_conflicts'] )
			{
				$tstats['conflicts']++;
			}
			
			if ( $report['change_can_merge'] )
			{
				$tstats['needmerge']++;
			}
			
			if ( $report['change_can_merge'] AND ! $report['change_changes_applied'] )
			{
				$tstats['uncommitted']++;
			}
			
			if ( $report['change_changes_applied'] )
			{
				$tstats['committed']++;
			}
			
			/* Final overrides */
			if ( $report['change_is_conflict'] AND $report['change_final_content'] )
			{
				/* Conflicts resolved */
				$_mergeIcon = 'blank.gif';//'shape_ungroup.png';
			}
			
			if ( $report['_is'] == 'new' )
			{
				$_desc = $this->lang->words['mergenewitemversion'];
			}
			else
			{
				$_desc = sprintf( $this->lang->words['mergereportdiffs'], $report['_diffs'] );
				
				if ( intval( $report['_conflicts'] ) > 0 )
				{
					if ( $report['change_final_content'] )
					{
						$_desc .= ', <strong>' . $report['_conflicts'] . '</strong> ' . $this->lang->words['resolvedconflicts'];
					}
					else
					{
						$_desc .= ', <strong>' . $report['_conflicts'] . '</strong> ' . $this->lang->words['mergeconflicts'];
					}
				}
				else
				{
					$_desc .= ', ' . $this->lang->words['noconflicts'];
				}
				
				if ( $report['change_changes_applied'] )
				{
					$_desc .= $this->lang->words['changescommitted'];
				}
			}
			
			$IPBHTML .= <<<EOF
				<tr class='ipsControlRow __diffRow __rowId{$report['change_id']} __xx{$report['_is']} {$_mergeClass} {$_comClass} _xgid{$_group} {$_mainClass} {$_isMergeClass}'>
				<td width='1%'><img src='{$this->settings['skin_acp_url']}/images/icons/{$_icon}' /></td>
				 <td width='84%'>
				   <img src='{$this->settings['skin_acp_url']}/images/icons/{$_comIcon}' /> <strong>{$report['change_data_title']}</strong>
				   <div class='desctext' style='font-size:0.8em' id='mDesc-{$report['change_id']}'>{$_desc}</div>
				 </td>
				 <td width='1%' nowrap='nowrap' align='center'><img src='{$this->settings['skin_acp_url']}/images/{$_mergeIcon}' title='{$_mergeTitle}' id='mMergeImage-{$report['change_id']}' class='__whenHasMerge{$report['change_id']}' /></td>
				 <td width='1%' nowrap='nowrap' align='center'><img src='{$this->settings['skin_acp_url']}/images/icons/{$_diffIcon}' title='{$_diffTitle}' /></td>
				 <td width='5%' nowrap='nowrap' align='center'>{$report['_size']}</td>
				 <td width='1%' nowrap='nowrap' align='center'><input type='checkbox' value='1' class='__cBox{$report['change_id']} __xBox' name='changeIds[{$report['change_id']}]' {$_cbExtra} /></td>
				 <td class='col_buttons'>
				 	<ul class='ipsControlStrip'>
					 	<li class='i_view'><a href='javascript:void(0);' onclick="return IPB3TemplateDiffResults.viewDiff({$report['change_id']}, 'diff')">{$this->lang->words['sk_viewdiffs']} {$this->lang->words['menu__oldnew']}...</a></li>
						<li class='ipsControlStrip_more ipbmenu' id='menu_{$report['change_id']}'>
							<a href='#'>&nbsp;</a>
						</li>
					</ul>
					<ul class='acp-menu' id='menu_{$report['change_id']}_menucontent'>
						<li class='icon view'><a href='javascript:void(0);' onclick="return IPB3TemplateDiffResults.viewVersion({$report['change_id']}, 'orig')">{$this->lang->words['menu__vieworigdef']} ({$sessionData['_oldHumanVersion']})...</a></li>
						<li class='icon view'><a href='javascript:void(0);' onclick="return IPB3TemplateDiffResults.viewVersion({$report['change_id']}, 'custom')">{$this->lang->words['menu__viewcustomized']} ({$sessionData['_oldHumanVersion']})...</a></li>
						<li class='icon view'><a href='javascript:void(0);' onclick="return IPB3TemplateDiffResults.viewVersion({$report['change_id']}, 'new')">{$this->lang->words['menu__viewnewdef']} ({$sessionData['_newHumanVersion']})...</a></li>
EOF;
			if ( intval( $report['_conflicts'] ) > 0 )
			{
				$IPBHTML .= <<<EOF
						<li class='icon view __whenHasMerge{$report['change_id']}'><a href='javascript:void(0);' onclick="return IPB3TemplateDiffResults.viewDiff({$report['change_id']}, 'merge')">{$this->lang->words['viewmergepreview']}</a></li>
						<li class='icon view __whenHasMerge{$report['change_id']}'><a href='javascript:void(0);' onclick="return IPB3TemplateDiffResults.editBit({$report['change_id']})">{$this->lang->words['manuallyresolveconflict']}</a></li>
EOF;
			}
			
			$IPBHTML .= <<<EOF
					</ul>
				 </td>
				</tr>		
EOF;
		}
	}
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
		 <td colspan='7'><em>{$this->lang->words['sk_nodiffs']}</em></td>
		</tr>
EOF;
}

/* Text blob, text blob, you're my text blob */
if ( $tstats['committed'] AND ! $tstats['uncommitted'] )
{
	$this->registry->getClass('output')->global_message = $this->lang->words['merge_done_desc'];
}
else if ( $tstats['conflicts'] )
{
	$this->registry->getClass('output')->global_error = sprintf( $this->lang->words['merge_conflicts_desc'], intval( $tstats['conflicts'] ) );
}
else if ( $tstats['uncommitted'] )
{
	$this->registry->getClass('output')->global_error = sprintf( $this->lang->words['merge_uncommitted_desc'], intval( $tstats['uncommitted'] ) );
}


$IPBHTML .= <<<EOF
 </table>
 <div class='acp-actionbar' style='text-align: right'>
 	{$this->lang->words['mergewithselected']}
 	<select name='mergeOption'>
 		<option value='resolve_custom'>{$this->lang->words['resolveallcustom']}</option>
 		<option value='resolve_new'>{$this->lang->words['resolveallnewdefault']}</option>
 		<option value='commit'>{$this->lang->words['resolveallcommit']}</option>
 		<option value='revert'>{$this->lang->words['resolvealluncommit']}</option>
 	</select>
 	<input type='submit' value='{$this->lang->words['resolve__go']}' class='button primary' />
 </div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show an ajax-style refresh screen for difference report
 *
 * @param	string		Session ID
 * @param	int			Total skin bits
 * @param	int			Skin bits per refresh
 * @return	string		HTML
 */
public function skindiff_ajaxScreen( $sessionID, $totalBits, $perGo ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<style type="text/css">
	@import url( "{$this->settings['skin_app_url']}skinDiff.css" );
</style>
<script type="text/javascript" src="{$this->settings['js_app_url']}ipb3TemplateDiff.js"></script>
<div class='acp-box'>
	<h3>{$this->lang->words['sk_processing']}</h3>
	<table class='ipsTable double_pad'>
	<tr>
		<td>
			<div id='diffLogDraw'>
				<div id='diffLowDrawInner'>
					<table cellspacing='0' cellpadding='0'>
					<tr>
						<td valign='top'>
							<div id='diffLogTitle'></div>
							<div id='diffLogText'></div>
							<div id='diffLogMsg'>{$this->lang->words['minutepatience']}</div>
						</td>
					</tr>
					</table>
					<div id='diffLogProgressWrap'>
						<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/mini-wait.gif' id='diffStatusImage' />
						<div id='diffLogProgressBar'>
							<div id='diffLogProgressBarInner'></div>
						</div>
					</div>
				</div>
			</div>
		</td>
	</tr>
 </table>
 <div class='acp-actionbar'>&nbsp;</div>
</div>
<!-- Preload the status images -->
<div style='display:none'>
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/receiving.png' />
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/sending.png' />
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/stop.png' />
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/warning.png' />
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/ready.png' />
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/mini-wait.gif' />
</div>
<script type='text/javascript'>
/* Add to the applications array */
IPB3TemplateDiff.baseUrl      = "{$this->settings['base_url']}&app=core&module=ajax&section=templatediff&sessionID={$sessionID}&perGo={$perGo}&secure_key={$this->member->form_hash}";
IPB3TemplateDiff.baseUrlMerge = "{$this->settings['base_url']}&app=core&module=ajax&section=templatediff&sessionID={$sessionID}&perGo={$perGo}&secure_key={$this->member->form_hash}&do=merge";
IPB3TemplateDiff.imageUrl     = "{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/";
IPB3TemplateDiff.doneUrl      = "{$this->settings['base_url']}&app=core&{$this->form_code}&do=viewReport&sessionID={$sessionID}";
IPB3TemplateDiff.totalBits    = {$totalBits};
IPB3TemplateDiff.init();
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Overview screen for skin diff reports
 *
 * @param	array 		Skin diff sessions
 * @return	string		HTML
 */
public function skindiff_overview( $sessions=array(), $skinOptionList, $canMerge ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='acp-box'>
	<h3>{$this->lang->words['sk_skindiffreports']}</h3>
	<table class='ipsTable double_pad'>
	<tr>
		<th width='1%'>&nbsp;</th>
		<th width='89%'><strong>{$this->lang->words['sk_difftitle']}</strong></th>
		<th width='5%'>{$this->lang->words['sk_created']}</th>
		<th width='5%'>&nbsp;</th>
	</tr>

EOF;

if ( count( $sessions ) )
{
	foreach( $sessions as $id => $data )
	{
		$IPBHTML .= <<<EOF
		<tr class='ipsControlRow'>
		 <td><img src='{$this->settings['skin_acp_url']}/images/icons/folder.png' /></td>
		 <td><strong>{$data['_title']}</strong></td>
		 <td nowrap='nowrap' align='center'>{$data['_date']}</td>
		 <td class='col_buttons'>
		 	<ul class='ipsControlStrip'>
				<li class='i_view'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=viewReport&amp;sessionID={$data['merge_id']}'>{$this->lang->words['sk_viewdiffresults']}...</a></li>
				<!--<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=removeReport&amp;sessionID={$data['merge_id']}");'>{$this->lang->words['sk_removediffresults']}...</a></li>-->
				<li class='i_export'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=exportReport&amp;sessionID={$data['merge_id']}'>{$this->lang->words['sk_createhtmlexport']}...</a></li>
			</ul>
		 </td>
		</tr>
EOF;
	}
}
else
{
	if ( $canMerge )
	{
		$IPBHTML .= <<<EOF
		<tr>
		 <td colspan='5'><em>{$this->lang->words['sk_nodiffs']}</em></td>
		</tr>
EOF;
	}
	else
	{
		$IPBHTML .= <<<EOF
		<tr>
		 <td colspan='5'><em>{$this->lang->words['nopreviouslystoredtemp']}</em></td>
		</tr>
EOF;

	}
}

$IPBHTML .= <<<EOF
 </table>
 <div class='acp-actionbar' style='text-align:right'>
EOF;
	if ( $canMerge )
	{
		$IPBHTML .= <<<EOF
		 	<form action="{$this->settings['base_url']}&amp;module=templates&amp;section=skindiff&amp;do=skinDiffStart" method="POST">
		 		{$this->lang->words['runnewreporton']} <select name='setID'>{$skinOptionList}</select> <input type='submit' class='button' value='{$this->lang->words['resolve__go']}' />
		 	</form>
EOF;
 	}
 $IPBHTML .= <<<EOF
 </div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show form to add/edit a skin url mapping
 *
 * @param	array 		Form bits
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array		Remap data
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function urlmap_showForm( $form, $title, $formcode, $button, $remap, $setData ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['sk_urlmapfor']} {$setData['set_name']}</h2>
</div>
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;map_id={$remap['map_id']}&amp;setID={$setData['set_id']}' id='mainform' method='POST'>
<div class='acp-box'>
	<h3>{$title}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<th colspan='2'>{$this->lang->words['sk_generalsettings']}</th>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_title']}</strong></td>
			</td>
			<td class='field_field'>
				{$form['map_title']}<br />
				<span class='desctext'>{$this->lang->words['sk_title_info']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_type']}</strong></td>
			</td>
			<td class='field_field'>
				{$form['map_match_type']}<br />
				<span class='desctext'>{$this->lang->words['sk_type_info']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_url']}</strong>
			</td>
			<td class='field_field'>
				{$form['map_url']}<br />
				<span class='desctext'>{$this->lang->words['sk_url_info']}</span>
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
 		<input type='submit' value='{$button}' class='button primary' />
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * List current URL mappings
 *
 * @param	array 		Current remaps
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function urlmap_showURLMaps( $remaps=array(), $skinSetData=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['sk_urlremappingfor']} {$skinSetData['set_name']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remapAdd&amp;setID={$skinSetData['set_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['sk_addnewurl']}</a>
		</li>
	</ul>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['sk_mappedurls']}</h3>
	<table class='ipsTable'>
		<tr>
            <th width='5%'>&nbsp;</th>
            <th width='45%'>{$this->lang->words['sk_title']}</th>
            <th width='45%'>{$this->lang->words['sk_added']}</th>
            <th width='5%'></th>
		</tr>
EOF;
if ( count( $remaps ) )
{
	foreach( $remaps as $data )
	{
$IPBHTML .= <<<EOF
        <tr class='ipsControlRow'>
            <td><img src='{$this->settings['skin_acp_url']}/images/folder_components/skinremap/remap_row.png' /></td>
            <td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remapEdit&amp;map_id={$data['map_id']}&amp;setID={$skinSetData['set_id']}'><strong>{$data['map_title']}</strong></a></td>
            <td>{$data['_date']}</td>
            <td class='col_buttons'>
                <ul class='ipsControlStrip'>
                    <li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remapEdit&amp;map_id={$data['map_id']}&amp;setID={$skinSetData['set_id']}' title="{$this->lang->words['sk_editmapping']}...">{$this->lang->words['sk_editmapping']}...</a></li>
                    <li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remapRemove&amp;map_id={$data['map_id']}&amp;setID={$skinSetData['set_id']}");' title="{$this->lang->words['sk_removemapping']}...">{$this->lang->words['sk_removemapping']}...</a></li>
                </ul>
            </td>
        </tr>
EOF;
	}
}
else
{
$IPBHTML .= <<<EOF
        <tr>
            <td colspan='4' align='center'>{$this->lang->words['sk_noremapping']}</td>
        </tr>
EOF;
}
$IPBHTML .= <<<EOF
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * User agent skin mappings
 *
 * @param	array 		User agent configs
 * @param	array		User agent groups
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function useragents_showUserAgents( $userAgents, $userAgentGroups, $setData ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['uagent_mapping']}</h2>
</div>
<div class='section_info'>
	{$this->lang->words['sk_useragent_info']}
</div>
<form id='uAgentsForm' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=saveAgents&amp;setID={$setData['set_id']}' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['sk_uagentmappingfor']} {$setData['set_name']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<th colspan='4'>{$this->lang->words['sk_groups']}</th>
			</tr>
EOF;
			foreach( $userAgentGroups as $id => $data )
			{
				$_selected = ( ( is_array( $setData['_userAgents']['groups'] ) ) AND in_array( $data['ugroup_id'], array_values( $setData['_userAgents']['groups'] ) ) ) ? 'checked="checked"' : '';

				$IPBHTML .= <<<EOF
				<tr>
					<td style='width: 2%; text-align: center'><input type='checkbox' name='uGroups[{$data['ugroup_id']}]' value='1' {$_selected} /></td>
					<td style='width: 2%; text-align: center'><img src="{$this->settings['skin_acp_url']}/images/folder_components/uagents/group.png" /></td>
					<td style='width: 56%' colspan='2'><strong>{$data['ugroup_title']}</strong></td>
				</tr>
EOF;
			}
			
			$IPBHTML .= <<<EOF
			<tr>
				<th colspan='4'>{$this->lang->words['sk_useragents']}</th>
			</tr>
EOF;
	foreach( $userAgents as $id => $data )
	{
		$_selected = ( ( is_array( $setData['_userAgents']['uagents'] ) ) AND in_array( $data['uagent_key'], array_keys( $setData['_userAgents']['uagents'] ) ) ) ? 'checked="checked"' : '';
		
		$IPBHTML .= <<<EOF
			<tr>
				<td style='width: 2%; text-align: center'><input type='checkbox' name='uAgents[{$data['uagent_id']}]' value='1' {$_selected} /></td>
				<td style='width: 2%; text-align: center'><img src="{$this->settings['skin_acp_url']}/images/folder_components/uagents/type_{$data['uagent_type']}.png" /></td>
				<td style='width: 56%;'><strong class='title'>{$data['uagent_name']}</strong></td>
				<td style='width: 40%'><strong class='title'>{$this->lang->words['sk_versions']}:</strong> <input type='text' name='uAgentVersion[{$data['uagent_id']}]' value='{$setData['_userAgents']['uagents'][ $data['uagent_key'] ]}' /></td>
			</tr>
EOF;
	}
$IPBHTML .= <<<EOF
		</table>
		<div class='acp-actionbar'>
		 	<input type='submit' value='{$this->lang->words['sk_save']}' class='realbutton' />
		</div>
	</div>
</div>
</form>
EOF;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show form to add/edit skin set
 *
 * @param	array 		Form bits
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function skinsets_setForm( $form, $title, $formcode, $button, $skinSet ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>$title</h2>
</div>
EOF;

if ( $skinSet['set_by_skin_gen'] )
{
	$IPBHTML .= <<<EOF
<div class='acp-box'>
	<h3>{$this->lang->words['skin_gen_convert_title']}</h3>
	<div class='pad fixed_inner'>
		{$this->lang->words['skin_gen_convert_desc']}
		<br />
		<br />
		<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=convertVisEditToFull&amp;set_id={$skinSet['set_id']}' class='button redbutton primary'>{$this->lang->words['skin_gen_convert_go']}</a>
		<br />&nbsp;
	</div>
</div>
<br />
EOF;
}

$IPBHTML .= <<<EOF
<form id='uAgentsForm' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;set_id={$skinSet['set_id']}' method='post'>
<div class='acp-box'>
	<h3>$title</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<th colspan='2'>{$this->lang->words['sk_basics']}</th>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_settitle']}</strong>
			</td>
			<td class='field_field'>
				{$form['set_name']}
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_setoutputformat']}</strong>
			</td>
			<td class='field_field'>
				{$form['set_output_format']}
			</td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['sk_setperms']}</th>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_selectallgroups']}</strong>
			</td>
			<td class='field_field'>
				<input type='checkbox' onclick="checkPermTickBox()" id='setPermissionsAll' name='set_permissions_all' value='1' {$form['set_permissions_all']} />
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_selectwhichgroups']}</strong>
			</td>
			<td class='field_field'>
				{$form['set_permissions']} <br /><span class='desctext'>{$this->lang->words['sk_selectmorethanone']}</span>
			</td>
		</tr>
EOF;
		if ( $skinSet['set_is_default'] )
		{
			$IPBHTML .= <<<EOF
	        <tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sk_setasdefault_info']}</strong>
				</td>
				<td class='field_field'>
	            	<em>{$this->lang->words['sk_defaultalready']}</em>
	            	<input type='hidden' name='set_is_default' value='1' />
				</td>
	        </tr>
EOF;
		}
		else if ( $skinSet['set_key'] != 'mobile' )
		{
			$IPBHTML .= <<<EOF
	        <tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sk_setasdefault_info']}</strong>
				</td>
				<td class='field_field'>
	            	{$form['set_is_default']}
				</td>
	        </tr>
EOF;
		}
	
	$_public	= PUBLIC_DIRECTORY;
	
	$IPBHTML .= <<<EOF
	
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_skinsetparent']}</strong>
			</td>
			<td class='field_field'>
				<select name='set_parent_id'>{$form['set_parent_id']}</select>
			</td>
		</tr>
EOF;
		if ( $skinSet['set_key'] != 'mobile' && $skinSet['set_key'] != 'default' )
		{
			$IPBHTML .= <<<EOF
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sk_skinsetkey']}</strong>
				</td>
				<td class='field_field'>
		            {$form['set_key']}<br /><span class='desctext'>*{$this->lang->words['sk_optional']}</span>
				</td>
			</tr>
EOF;
		}
		else {
			$IPBHTML .= <<<EOF
				<input type='hidden' name='set_key' value='{$skinSet['set_key']}' />
EOF;
		}
		
		$IPBHTML .= <<<EOF
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_hideskin']}</strong>
			</td>
			<td class='field_field'>
				{$form['set_hide_from_list']}
				<br /><span class='desctext'>{$this->lang->words['sk_hideskin_info']}</span>
			</td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['sk_imageoptions']}</th>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_useimgdir']}</strong>
			</td>
			<td class='field_field'>
	            {$_public}/style_images/ {$form['set_image_dir']}
				<br /><span class='desctext'>{$this->lang->words['sk_useimgdir_info']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_useemoset']}</strong>
			</td>
			<td class='field_field'>
	            {$_public}/style_emoticons/ {$form['set_emo_dir']}
				<br /><span class='desctext'>{$this->lang->words['sk_useemoset_info']}</span>
			</td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['sk_setauthor']}</th>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_setauthorname']}</strong>
			</td>
			<td class='field_field'>
				{$form['set_author_name']}
				<br /><span class='desctext'>*{$this->lang->words['sk_optional']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['sk_setauthorurl']}</strong>
			</td>
			<td class='field_field'>
				{$form['set_author_url']}
				<br /><span class='desctext'>*{$this->lang->words['sk_optional']}</span>
			</td>
		</tr>				
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$button}' class='button primary' />
    </div>
</div>
</form>
<script type='text/javascript'>
/* set it up */
checkPermTickBox();
checkMakeGlobal();

function checkMakeGlobal()
{
	var _val = $('setIsDefault').checked;

	if ( _val )
	{
		$('setPermissions').disabled   = true;
		$('setPermissionsAll').checked = true;
	}
	else
	{
		$('setPermissions').disabled = false;
	}
}

function checkPermTickBox()
{
	var _val  = $('setPermissionsAll').checked;
	var _val2 = $('setIsDefault').checked;
	
	if ( _val || _val2 )
	{
		$('setPermissions').disabled = true;
	}
	else
	{
		$('setPermissions').disabled = false;
	}
}
</script>
EOF;
//--endhtml--//
return $IPBHTML;
}

/**
 * Skin tools splash page
 *
 * @param	string		Skin options dropdown
 * @param	array 		App data
 * @param	array 		IN_DEV remap data
 * @return	string		HTML
 */
public function tools_splash( $skinOptionList, $appData, $remapData=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<style type='text/css'>
	.override_title { text-align: left !important; }
		.override_title .title { display: inline !important; font-size: 14px; font-weight: bold; }
	
	.tools_list { margin: 5px 0 0 15px; line-height: 1.5; }
</style>
<div class='section_title'>
	<h2>{$this->lang->words['to_templatetools']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['sk_recacheskinsets']}</h3>
	<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toolsRecache' enctype='multipart/form-data' method='POST'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	<table class='ipsTable double_pad'>
	<tr>
		<td class='field_title'><strong class='title'>{$this->lang->words['sk_selectskinset']}</strong></td>
		<td class='field_field'><select name='setID'><option value='0'>&lt; {$this->lang->words['sk_allskinsets']}&gt;</option>{$skinOptionList}</select></td>
	</tr>
	</table>
	 <div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['sk_recacheskinsets']}' class='button primary' />
	 </div>
	</form>
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['sk_resetskinusage']}</h3>
	<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toolsResetSkin' enctype='multipart/form-data' method='POST'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	<table class='ipsTable double_pad'>
	<tr>
		<td class='field_title override_title'>
			<strong class='title'>{$this->lang->words['reset_for']}</strong>
			<p class='tools_list'>
				<input type='checkbox' value='1' name='resetMembers' /> {$this->lang->words['sk_members']}<br />
				<input type='checkbox' value='1' name='resetForums' /> {$this->lang->words['sk_forums']}
			</p>
			<br />
			<strong class='title'>{$this->lang->words['sk_resetto']}</strong>
			<p class='tools_list'>
				<select name='resetSkinID'><option value='0'>&lt; {$this->lang->words['sk_usedefault']} &gt;</option>{$skinOptionList}</select>
			</p>
		</td>
		<td class='field_field override_title'>
			<strong class='title'>{$this->lang->words['sk_wheretheyuse']}</strong>
			<p class='tools_list'>
				<select name='setID[]' multiple="multiple" size="6" style='width: 150px'>{$skinOptionList}</select>
			</p>
		</td>
	</tr>
	</table>
	 <div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['sk_reset']}' class='button primary' />
	 </div>
	</form>
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['sk_rebuildmasterdata']}</h3>
	<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toolsRebuildMaster' enctype='multipart/form-data' method='POST'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	<table class='ipsTable double_pad'>
	<tr>
		<td class='field_title override_title'>
			<strong class='title'>{$this->lang->words['sk_rebuild']}:</strong>
			<p class='tools_list'>
				<input type='checkbox' value='1' name='rebuildHTML' /> {$this->lang->words['sk_rebuildhtml']}<br />
				<input type='checkbox' value='1' name='rebuildCSS' /> {$this->lang->words['sk_rebuildcss']}<br />
				<input type='checkbox' value='1' name='rebuildReplacements' /> {$this->lang->words['sk_rebuildreplacements']}
			</p>
		</td>
		<td class='field_field override_title'>
			<strong class='title'>{$this->lang->words['sk_forapps']}:</strong>
			<p class='tools_list'>
EOF;
	foreach( $appData as $appDir => $_appData )
	{
		$IPBHTML .= <<<EOF
				<input type='checkbox' value='1' name='apps[$appDir]'> <strong>{$_appData['app_title']}</strong> <span style='color:gray;font-size:0.9em'>({$this->lang->words['sk_templatexmllast']} - {$_appData['lastmTimeFormatted']})</span><br />
EOF;
	}
$IPBHTML .= <<<EOF
			</p>
		</td>
	</tr>
	</table>
	 <div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['sk_rebuild']}' class='button primary' />
	 </div>
	</form>
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['sk_cleanup_title']}</h3>
	<table class='ipsTable double_pad'>
	<tr>
		<td class='field_title'><strong class='title'>{$this->lang->words['sk_cleanup_templates']}</strong></td>
		<td class='field_field'>
			<a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=templateDbClean' class='realbutton'>{$this->lang->words['sk_run_tool']}</a>
			<br /><br />
			<span class='desctext'>
				{$this->lang->words['sk_cleanup_templates_exp']}
			</span>
		</td>
	</tr>
	<tr>
		<td class='field_title'><strong class='title'>{$this->lang->words['sk_cleanup_css']}</strong></td>
		<td class='field_field'>
			<a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=cssDbClean' class='realbutton'>{$this->lang->words['sk_run_tool']}</a>
			<br /><br />
			<span class='desctext'>
				{$this->lang->words['sk_cleanup_css_exp']}
			</span>
		</td>
	</tr>
	<tr>
		<td class='field_title'>
			<strong class='title'>{$this->lang->words['sk_clean_caches']}</strong>
		</td>
		<td class='field_field'>
			<form action='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=toolCacheClean' method='POST'>
			<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
			<p style='float: left; margin-right: 10px;'>
				<input type='checkbox' name='cleanCss' value='1' /> {$this->lang->words['sk_clean_caches_cb_css']}<br />
				<input type='checkbox' name='cleanTemplates' value='1' /> {$this->lang->words['sk_clean_caches_cb_tem']}
			</p>
			<select name='setID'>{$skinOptionList}</select> &nbsp; <input type='submit' value='{$this->lang->words['sk_run_tool']}' class='button primary' />
			</form>
		</td>
	</tr>
	</table>
</div>
EOF;

if  (IN_DEV )
{
	$IPBHTML .= <<<EOF
<br />
<div class='acp-box'>
	<h3>IPS Developer's Tools</h3>
	<!-- IN DEV -->
 	<table class='ipsTable double_pad'>
	<tr>
		<td class='field_title'>
			<strong class='title'>Build Skin Files For Release</strong>
		</td>
		<td class='field_field'>
			<div class='right'><a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=rebuildForRelease' class='realbutton'>{$this->lang->words['sk_run']}</a></div>
			<div class='desctext'>
				This tool:
				<ul>
				 	<li>Rebuilds your HTML/CSS/Replacements from the master_*/ disk files.</li>
					<li>Exports all template XML into the application directories</li>
					<li>Exports skinsData.xml, css.xml and replacements.xml for each default skin</li>
				</ul>
EOF;
	if ( ! is_writable( IPS_ROOT_PATH . 'setup/xml/skins' ) )
	{
		$_file = IPS_ROOT_PATH . 'setup/xml/skins';

		$IPBHTML .= <<<EOF
		<div style='color:red'>Cannot write to {$_file}</div>
EOF;
	}
	foreach( $appData as $appDir => $_appData )
	{
		if ( ! is_writable( IPSLib::getAppDir( $appDir ) . '/xml' ) )
		{
			$_file = IPSLib::getAppDir( $appDir ) . '/xml';
			
		$IPBHTML .= <<<EOF
				<div style='color:red'>Cannot write to {$_file}</div>
EOF;
		}
	}
$IPBHTML .= <<<EOF
			</div>
		</td>
	</tr>
	<tr>
		<td class='field_title'>
			<strong class='title'>Create Master PHP Templates Directory</strong>
		</td>
		<td class='field_field'>
			<div class='right'><a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=createMasterSkin&amp;set_id=0' class='realbutton'>{$this->lang->words['sk_run']}</a></div>
			<div class='desctext'>
				Note, this tool is for editing master set 0, not your own skin sets. Use the per-skin tool instead (see /cache/skin_cache/masterMap.php)
			</div>
EOF;
	if ( is_dir( IPS_CACHE_PATH . 'cache/skin_cache/master_skin' ) )
	{
		$IPBHTML .= <<<EOF
			<div class='desctext' style='color:red'>
				You already have a master_skin directory. Using this tool will overwrite the contents. Use with caution!
			</div>
EOF;

	}

$IPBHTML .= <<<EOF
		</td>
	</tr>
	<tr>
		<td class='field_title'><strong class='title'>Rebuild Master HTML From PHP Caches</strong></td>
		<td class='field_field'><a class='realbutton' href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=rebuildMasterSkin&amp;set_id=0'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	<tr>
		<td class='field_title'><strong class='title'>Rebuild Master CSS From Disk Files</strong></td>
		<td class='field_field'><a class='realbutton' href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=inDevMasterCSS'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	<tr>
		<td class='field_title'><strong class='title'>Rebuild Master Replacements From Disk File</strong></td>
		<td class='field_field'><a class='realbutton' href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=inDevMasterReplacements'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	<tr>
		<td class='field_title'><strong class='title'>Export HTML Templates Into Application Directories</strong></td>
		<td class='field_field'><a class='realbutton' href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=exportAPPTemplates'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	<tr>
		<td class='field_title'><strong class='title'>Export CSS Into Application Directories</strong></td>
		<td class='field_field'><a class='realbutton' href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=exportAPPCSS'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	<tr>
		<td class='field_title'><strong class='title'>Export Replacements To XML Files</strong></td>
		<td class='field_field'><a class='realbutton' href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=exportMasterReplacements'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	<tr>
		<td class='field_title'><strong class='title'>Import HTML Templates From Application Directories</strong></td>
		<td class='field_field'><a class='realbutton' href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=importAPPTemplates'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	</table>
</div>
EOF;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Results from running a skin tool
 *
 * @param	string		Page title
 * @param	array		Ok messages
 * @param	array 		Error messages
 * @return	string		HTML
 */
public function tools_toolResults( $title, $okMessages, $errorMessages=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='acp-box'>
	<h3>{$title}</h3>
	<table class='ipsTable'>
	<tr>
		<td>
EOF;
	if ( is_array( $errorMessages ) )
	{
		foreach( $errorMessages as $entry )
		{
			$IPBHTML .= <<<EOF
				<div class='input-warn-content' style='color:red'>{$entry}</div>
EOF;
		}
	}
	
	if ( is_array( $okMessages ) )
	{
		foreach( $okMessages as $entry )
		{
			$IPBHTML .= <<<EOF
				<div class='input-ok-content'>{$entry}</div>
EOF;
		}
	}
	
$IPBHTML .= <<<EOF
		</td>
	</tr>
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Splash page to remove customizations from a skin set
 *
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function skinsets_revertSplash( $setData, $counts ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=setRevert&amp;setID={$setData['set_id']}&amp;authKey={$this->member->form_hash}' method='post'>
<div class='section_title'>
	<h2>{$this->lang->words['sk_revert_title']} '{$setData['set_name']}'</h2>
</div>
<div class='acp-box'>
	<h3>{$this->lang->words['sk_pleaseconfirm']}</h3>
	<table class='ipsTable'>
		<tr>
			<td>
				<p>
					<p>{$this->lang->words['sk_revert_desc']}</p>
					<p><input type='checkbox' id='cbTemplates' name='templates' value='1' /> <strong>{$counts['templates']}</strong> {$this->lang->words['sk_revert_templates']}</p>
					<p><input type='checkbox' id='cbCss' name='css' value='1' /> <strong>{$counts['css']}</strong> {$this->lang->words['sk_revert_css']}</p>
					<p><input type='checkbox' id='cbReplacements' name='replacements' value='1' /> <strong>{$counts['replacements']}</strong> {$this->lang->words['sk_revert_replacements']}</p>
				</p>
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['sk_revert_button']}' id='doRevertSubmit' class='realbutton redbutton' />
	</div>
</div>
</form>
<script type="text/javascript">
	$('doRevertSubmit').on('click', function( e )
	{
		if ( ! $('cbTemplates').checked && ! $('cbCss').checked && ! $('cbReplacements').checked )
		{
			Event.stop(e);
			alert( ipb.lang['skin_revert_none'] );
		}
	} );
</script>
	
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Splash page to remove a skin set
 *
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function skinsets_removeSplash( $setData ) {

$IPBHTML = "";
//--starthtml--//
$pleaseconfirmthatyoureallywanttoremovethisskinset = sprintf( $this->lang->words['sk_pleaseconfirm_info'], $setData['set_name'] );

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['sk_removingset']} '{$setData['set_name']}'</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['sk_pleaseconfirm']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td>
				{$pleaseconfirmthatyoureallywanttoremovethisskinset}
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='button' value='{$this->lang->words['sk_removeskinset']}' class='realbutton redbutton' onclick='acp.redirect( "{$this->settings['base_url']}{$this->form_code}&do=setRemove&set_id={$setData['set_id']}&authKey={$this->member->form_hash}", 1 )' />
	</div>
</div>
	
EOF;

//--endhtml--//
return $IPBHTML;
}
	
/**
 * Skin sets overview (tab homepage)
 *
 * @param	array 		Skin sets
 * @param	array 		Caching data
 * @return	string		HTML
 */
public function skinsets_listSkinSets( $sets, $cacheData, $hasData, $canMerge, $skinGenSessions, $html, $skinGenIsSupported=FALSE ) {

$addButtonId = $skinGenIsSupported ? 'ipsNewSkin' : '';

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.skinsets.js"></script>
{$html}
<div class='section_title'>
	<h2>{$this->lang->words['sk_skinmanagement']}</h2>
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=setAdd' id='{$addButtonId}'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['sk_addnewrootskin']}</a></li>
		<li><a href='{$this->settings['base_url']}module=templates&amp;section=importexport&amp;do=overview'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['sk_importnewskin']}</a></li>
	</ul>
</div>
<div class='acp-box' id="forum_wrapper">
	<h3>{$this->lang->words['sk_skinsets']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<th width='3px'>&nbsp;</th>
			<th>{$this->lang->words['sk_setname']}</th>
			<th style='width: 3%'>&nbsp;</th>
			<th style='width: 3%'>&nbsp;</th>
		</tr>
	</table>
	<ul class='sortable_handle alternate_rows' id='sort_root'>
EOF;
	
	$children		= array( 'root' => array() );
	$ended			= array();
	$javascripts	= array();
	
	foreach( $sets as $idx => $data )
	{	
		if ( $data['set_output_format'] == 'xml' && $data['set_key'] == 'xmlskin' )
		{
			continue;
		}
	
		$subskin = ( $data['depthguide'] ) ? 'subforum' : '';
		
		/* on off stuffs */
		$isVisEditSkin = ( $data['set_by_skin_gen'] ) ? true : false;
		$preOFImage   = 'off_';
		$titleOFImage = $this->lang->words['tt_ss_of_off'];
		$badge		  = '';
		$desc		  = '';
		$canDelete    = true;
		
		if ( $data['set_is_default'] )
		{
			$badge = "<span class='ipsBadge badge_purple'>{$this->lang->words['skin_default']}</span>";
		}
		else
		{
			if ( $data['set_hide_from_list'] )
			{
				$badge = "<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=toggleHidden&amp;set_id={$data['set_id']}' title='{$this->lang->words['ss_hidden']}'><span class='ipsBadge badge_red'>{$this->lang->words['skin_hidden']}</span></a>";
			}
			else if ( ! $data['set_is_default'] )
			{
				$badge = "<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=toggleHidden&amp;set_id={$data['set_id']}' title='{$this->lang->words['ss_not_hidden']}'><span class='ipsBadge badge_green'>{$this->lang->words['skin_visible']}</span></a>";
			}
			else
			{
				$badge = "<span class='ipsBadge badge_green'>{$this->lang->words['skin_visible']}</span>";
			}
		}
		
		if ( $data['set_by_skin_gen'] )
		{
			$desc = '<span class="desctext">' . $this->lang->words['skin_gen_desc'] . '</span>';
			
			if ( ! empty( $skinGenSessions[ $data['set_id'] ] ) )
			{
				$canDelete = false;
				$desc .= "<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=cancelVisualEditor&amp;set_id={$data['set_id']}' class='mini_button secondary right ipsCancelEditor'>Cancel Visual Editor</a>";
			}
			else
			{
				$desc .= "<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=launchVisualEditor&amp;set_id={$data['set_id']}' target='_blank' class='mini_button right'>Launch visual editor</a>";
			}
		}
		
		$children[ $data['set_id'] ] = unserialize( $data['set_child_array'] );
		$closeLi = FALSE;
		
		//if ( !is_array( $children[ $data['set_parent_id'] ] ) )
		//{
		//	var_dump( $data['set_parent_id'] );
		//	exit;
		//}
		
		$parents = unserialize( $data['set_parent_array'] );
		
		if( is_array($parents) AND count($parents) )
		{
			foreach ( $parents as $pid )
			{
				foreach ( $children[ $pid ] as $k => $v )
				{
					if ( $v == $data['set_id'] )
					{
						unset( $children[ $pid ][ $k ] );
						break;
					}
				}
			}
		}
								
		$IPBHTML .= <<<EOF
		<li id='set_{$data['set_id']}' class='isDraggable'>
		<table class='double_pad' id='sort_{$data['set_id']}'>
		<tr class='ipsControlRow'>
			<td class='forum_row {$subskin}' width='1%' style='text-align: center'>
				<div class='draghandle'>&nbsp;</div>
			</td>
			 <td class='forum_row {$subskin}' style='width: 81%'>
				{$data['depthguide']}
			 	<img src='{$this->settings['skin_acp_url']}/images/icons/{$data['_setImg']}' />&nbsp;
			 	<strong><a title='{$data['bit_desc']}' href='{$this->settings['base_url']}module=templates&amp;section=templates&amp;do=list&amp;setID={$data['set_id']}'>{$data['set_name']}</a></strong>
			 	{$desc}
EOF;
		if ( ! $cacheData[ $data['set_id'] ]['db'] AND ! $cacheData[ $data['set_id'] ]['php'] )
		{
			$_depth = ( $data['cssDepthGuide'] * 20 ) + 30;
			$IPBHTML .= <<<EOF
			<br />
				<span class='desctext' style='margin-left: {$_depth}px'>
					{$this->lang->words['sk_notempcache']} <a href='{$this->settings['base_url']}&amp;module=templates&amp;section=tools&amp;do=rebuildPHPTemplates&amp;setID={$data['set_id']}'>{$this->lang->words['sk_pleasebuildthem']}</a>
				</span>
EOF;
		}
		
		$IPBHTML .= <<<EOF
			</td>
			<td class='forum_row {$subskin}' style='text-align: center' style='width: 3%'>
				{$badge}
			</td>
			<td class='forum_row {$subskin} col_buttons' style='width: 3%'>
				<ul class='ipsControlStrip'>
				    <li class='ipsControlStrip_more ipbmenu' id="menu_{$data['set_id']}">
						<a href='#'>&nbsp;</a>
					</li>
				</ul>
				<ul class='acp-menu' id='menu_{$data['set_id']}_menucontent'>
 					<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=setEdit&amp;set_id={$data['set_id']}' title='{$this->lang->words['sk_editsettings']}'>{$this->lang->words['sk_editsettings']}</a></li>
EOF;

			if ( ! $isVisEditSkin )
			{
				$IPBHTML .= <<<EOF
					<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=templates&amp;section=templates&amp;do=list&amp;setID={$data['set_id']}'>{$this->lang->words['sk_managetempcss']}</a></li>
					<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=templates&amp;section=replacements&amp;do=list&amp;setID={$data['set_id']}'>{$this->lang->words['sk_managereplacements']}</a></li>
					<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=templates&amp;section=useragents&amp;do=show&amp;setID={$data['set_id']}'>{$this->lang->words['sk_manageuagentmapping']}</a></li>
					<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=templates&amp;section=urlmap&amp;do=show&amp;setID={$data['set_id']}'>{$this->lang->words['sk_manageurlmapping']}</a></li>
					<li class='icon delete'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=revertSplash&amp;setID={$data['set_id']}'>{$this->lang->words['sk_revert_customizations']}</a></li>
EOF;
			}
			
			if ( $canMerge && ! $isVisEditSkin )
			{		
				$IPBHTML .= <<<EOF
					<li class='icon view'><a href='{$this->settings['base_url']}&amp;module=templates&amp;section=skindiff&amp;do=skinDiffStart&amp;setID={$data['set_id']}'>{$this->lang->words['runmergereports']}</a></li>
EOF;
			}

			if ( $data['_canRemove'] && $canDelete )
			{		
			$IPBHTML .= <<<EOF
					<li class='icon delete'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=setRemoveSplash&amp;set_id={$data['set_id']}'>{$this->lang->words['sk_removeskinset']}...</a></li>
EOF;
			}
			if ( $data['_canWriteMaster'] AND IN_DEV )
			{
			$IPBHTML .= <<<EOF
					<li class='icon export'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=setWriteMaster&amp;set_id={$data['set_id']}'>{$this->lang->words['exportmastertempl']}</a></li>
					<li class='icon add'><a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=rebuildMasterSkin&amp;set_id={$data['set_id']}'>{$this->lang->words['importmastertempl']}</a></li>
EOF;
			}
			
			if ( $data['_canWriteMasterCss'] AND IN_DEV )
			{
			$IPBHTML .= <<<EOF
					<li class='icon export'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=setWriteMasterCss&amp;set_id={$data['set_id']}'>{$this->lang->words['exportmastercss']}</a></li>
					<li class='icon add'><a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=rebuildMasterCss&amp;set_id={$data['set_id']}'>{$this->lang->words['importmastercss']}</a></li>
EOF;
			}

			$IPBHTML .= <<<EOF
				</ul>
			</td>
		</tr>
		</table>
EOF;
		
		if ( empty( $children[ $data['set_id'] ] ) and !in_array( $data['set_id'], $ended ) )
		{
			$IPBHTML .= "</li>";
			$ended[] = $children[ $data['set_id'] ];
			$javascripts[] = <<<EOF
				dropItLikeItsHot{$data['set_id']} = function( draggableObject, mouseObject )
				{
					var options = {
									method : 'post',
									parameters : Sortable.serialize( 'sort_{$data['set_id']}', { tag: 'li', name: 'skin_sets' } )
								};

					new Ajax.Request( "{$this->settings['base_url']}&app=core&module=templates&section=skinsets&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );
					return false;
				};

				Sortable.create( 'sort_{$data['set_id']}', { only: 'isDraggable', revert: true, format: 'set_([0-9]+)', onUpdate: dropItLikeItsHot{$data['set_id']}, handle: 'draghandle' } );
EOF;
		}
		
		if( is_array($parents) AND count($parents) )
		{
			foreach ( $parents as $pid )
			{		
				if ( empty( $children[ $pid ] ) and !in_array( $pid, $ended ) )
				{
					$IPBHTML .= "</li>";
					$ended[] = $pid;
					$javascripts[] = <<<EOF
						dropItLikeItsHot{$pid} = function( draggableObject, mouseObject )
						{
							var options = {
											method : 'post',
											parameters : Sortable.serialize( 'sort_{$pid}', { tag: 'li', name: 'skin_sets' } )
										};
						
							new Ajax.Request( "{$this->settings['base_url']}&app=core&module=templates&section=skinsets&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );
							return false;
						};
	
						Sortable.create( 'sort_{$pid}', { only: 'isDraggable', revert: true, format: 'set_([0-9]+)', onUpdate: dropItLikeItsHot{$pid}, handle: 'draghandle' } );
EOF;
				}
			}
		}
				
	}
	 
$IPBHTML .= <<<EOF
	</ul>
	<script type="text/javascript">
		var cancelEditorDialogue = new Template("<div class='acp-box'><h3>Close Visual Editor</h3><div class='pad'><p>This will cancel the open session and revert any unsaved changes.<br /><br /><a href='#{url}' class='button'>Close Editor</a></p></div></div>" );
		dropItLikeItsHotroot = function( draggableObject, mouseObject )
		{
			var options = {
							method : 'post',
							parameters : Sortable.serialize( 'sort_root', { tag: 'li', name: 'skin_sets' } )
						};

			new Ajax.Request( "{$this->settings['base_url']}&app=core&module=templates&section=skinsets&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );
			return false;
		};

		Sortable.create( 'sort_root', { only: 'isDraggable', revert: true, format: 'set_([0-9]+)', onUpdate: dropItLikeItsHotroot, handle: 'draghandle' } );
EOF;

if( count($javascripts) )
{
	$IPBHTML .= implode( "\n", $javascripts );
}

$IPBHTML .= <<<EOF
	</script>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * List replacements in a skin set
 *
 * @param	array 		Replacements data
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function replacements_listReplacements( $replacementsData, $setData ) {

$IPBHTML = "";
//--starthtml--//

$_keys    = array_keys( $replacementsData );
$_json    = json_encode( array( 'replacements' => $replacementsData ) );
$_first   = array_shift( $_keys );
$_setData = json_encode( $setData );

$IPBHTML .= <<<EOF
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.replacements.js"></script>

<div class='section_title'>
	<h2>{$this->lang->words['sk_replaceinset']}: {$setData['set_name']}</h2>
	<ul class='context_menu'>
		<li><a href='#' id='add_replacement'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['sk_replacementadd']}</a></li>
		<li><a href='{$this->settings['base_url']}app=core&amp;module=templates&amp;section=skinsets&amp;do=setEdit&amp;set_id={$setData['set_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/palette_edit.png' alt='' /> {$this->lang->words['sk_editskinsettings']}</a></li>
		<li><a href='{$this->settings['base_url']}app=core&amp;module=templates&amp;section=templates&amp;do=list&amp;setID={$setData['set_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/folder_palette.png' alt='' /> {$this->lang->words['sk_edittempcss']}</a></li>
	</ul>
</div>
<div class='information-box'>
	{$this->lang->words['sk_replace_info']}
</div>
<br />
<script type='text/javascript'>
	acp.replacements.allReplacements = $_json;
	acp.replacements.currentSetData = $_setData;
	acp.replacements.realImgDir  = '{$this->settings['public_dir']}style_images/{$setData['set_image_dir']}';
	acp.replacements.iconUrl     = '{$this->settings['skin_acp_url']}/images/icons/';
	acp.replacements.templateUrl = '{$this->settings['skin_acp_url']}/images/folder_components/templates/templates/';
	acp.replacements.icons       = { 'del-new'      : 'cross.png',
									 'del-modified' : 'arrow_rotate_anticlockwise.png',
									 'del-inherit'  : 'arrow_rotate_anticlockwise.png' };
									
	
	ipb.templates['edit_box'] = new Template("<textarea id='r_#{id}_textbox' class='input_text' style='width: 70%; font-family: arial; font-size: 12px;' rows='2'>#{content}</textarea><div class='replacement_save'><input type='submit' value='{$this->lang->words['sk_save']}' id='r_#{id}_save' class='realbutton' /> <input type='submit' value='{$this->lang->words['sk_cancel']}' id='r_#{id}_cancel' class='realbutton' /></div>");
	
	ipb.templates['add_replacement'] = "<div class='acp-box'><h3>{$this->lang->words['add_repl_f_tpl']}</h3><table class='ipsTable'><tr><td class='field_title'><strong class='title'>{$this->lang->words['rep_key_f_tpl']}</strong></td><td class='field_field'><input type='text' class='input_text' id='popup_key' /></td></tr><tr><td class='field_title'><strong class='title'>{$this->lang->words['rep_ctn_f_tpl']}</strong></td><td class='field_field'><textarea id='popup_content' style='width: 45%' rows='7' class='input_text'></textarea></td></tr></table><div class='acp-actionbar'><input type='submit' class='realbutton' value='{$this->lang->words['addrep_btn_f_tpl']}' id='popup_submit' /></div></div>";
	
	ipb.templates['revert_button'] = new Template("<a href='#' onclick='return false;' title='{$this->lang->words['sk_revertreplace']}' id='r_#{id}_revert'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_rotate_anticlockwise.png' alt='' /></a>");
	
	$('add_replacement').observe( 'click', acp.replacements.addReplacement );
</script>

<div class="acp-box">
	<h3>{$this->lang->words['sk_replacements']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th width='2%'>&nbsp;</td>
			<th width='20%'>{$this->lang->words['sk_replacekey']}</th>
			<th width='68%' class='center'>{$this->lang->words['sk_replacecontent']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
EOF;

foreach( $replacementsData as $replacement )
{
	$status = 'default';
	$revert = '';

	if ( ! $setData['_isMaster'] )
	{
		if ( $replacement['replacement_added_to'] == $setData['set_id'] )
		{
			$status = 'new';
			$revert = "<li class='i_delete' id='r_revert_wrap_{$replacement['replacement_key']}'><a href='#' onclick='return false;' title='{$this->lang->words['sk_removereplace']}' id='r_{$replacement['replacement_key']}_delete'>{$this->lang->words['sk_removereplace']}</a></li>";
		}
		elseif ( $replacement['replacement_set_id'] == $setData['set_id'] )
		{
			$status = 'modified';
			$revert = "<li class='i_refresh' id='r_revert_wrap_{$replacement['replacement_key']}'><a href='#' onclick='return false;' title='{$this->lang->words['sk_revertreplace']}' id='r_{$replacement['replacement_key']}_revert'>{$this->lang->words['sk_revertreplace']}</a></li>";
		}
		elseif ( in_array( $replacement['replacement_set_id'], array_values( $setData['_parentTree'] ) ) )
		{
			$status = 'inherit';
			$revert = "<li class='i_refresh' id='r_revert_wrap_{$replacement['replacement_key']}'><a href='#' onclick='return false;' title='{$this->lang->words['sk_revertreplace']}' id='r_{$replacement['replacement_key']}_revert'>{$this->lang->words['sk_revertreplace']}</a></li>";
		}
		else
		{
			$revert = "<li class='i_refresh' id='r_revert_wrap_{$replacement['replacement_key']}'></li>";
		}
	}
	
	$replacement['real_content'] = str_replace("{style_image_url}", $this->settings['public_dir'] . 'style_images/' . $setData['set_image_dir'], $replacement['replacement_content'] );
	
	$IPBHTML .= <<<EOF
	
		<tr class='ipsControlRow'>
			<td style='vertical-align: top'><img id='r_status_{$replacement['replacement_key']}' src='{$this->settings['skin_acp_url']}/images/folder_components/templates/templates/{$status}.png' alt='' /></td>
			<td style='vertical-align: top'><strong>{$replacement['replacement_key']}</strong></td>
			<td class='center' id='r_{$replacement['replacement_key']}_content'>{$replacement['real_content']}</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					{$revert}
					<li class='i_edit'><a href='#' onclick='return false;' title='{$this->lang->words['sk_editreplace']}' id='r_{$replacement['replacement_key']}_edit'>{$this->lang->words['sk_editreplace']}</a></li>	
				</ul>
				<script type='text/javascript'>
					acp.replacements.register('{$replacement['replacement_key']}');
				</script>
			</td>
		</tr>
EOF;
}

$IPBHTML .= <<<EOF
	</table>
	<div class='acp-actionbar'>
		<strong>{$this->lang->words['sk_legend']}:</strong> <img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/templates/default.png' alt='' title='{$this->lang->words['sk_l_default']}' />{$this->lang->words['sk_l_default_full']}&nbsp;&nbsp;&nbsp;
		<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/templates/modified.png' alt='' title='{$this->lang->words['sk_l_modified']}' />{$this->lang->words['sk_l_modified_full']}&nbsp;&nbsp;&nbsp;
		<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/templates/inherit.png' alt='' title='{$this->lang->words['sk_l_inherited']}' />{$this->lang->words['sk_l_inherited_full']}&nbsp;&nbsp;&nbsp;
		<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/templates/new.png' alt='' title='{$this->lang->words['sk_l_new']}' />{$this->lang->words['sk_l_new_full']}
	</div>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * List template groups (skin files)
 *
 * @param	array 		Template groups
 * @param	array		CSS files
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function templates_listTemplateGroups( $templateGroups, $css, $setData ) {

$IPBHTML = "";
//--starthtml--//

$_keys    = array_keys( $templateGroups );
$_json    = json_encode( array( 'groups' => $templateGroups ) );
$_first   = array_shift( $_keys );
$_setData = json_encode( $setData );
$_css 	  = json_encode( array( 'css' => $css ) );

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['sk_editingset']}: {$setData['set_name']}</h2>
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}app=core&amp;module=templates&amp;section=skinsets&amp;do=setEdit&amp;set_id={$setData['set_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/palette_edit.png' alt='' /> {$this->lang->words['sk_editskinsettings']}</a></li>
		<li><a href='{$this->settings['base_url']}app=core&amp;module=templates&amp;section=replacements&amp;do=list&amp;setID={$setData['set_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_switch.png' alt='' /> {$this->lang->words['sk_editreplacevar']}</a></li>
	</ul>
</div>

EOF;

if( IN_DEV )
{
	$IPBHTML .= <<<EOF
	<h3>Rikki's Magical Marvellously Devilish Debugging Bits</h3>
	<input type='button' id='debug_showArray' value='Log open file array' />
	<input type='button' id='debug_curFile' value='Log current file info' />
	<input type='button' id='debug_modMap' value='Log current modify map' />
	
	<script type='text/javascript'>
		$('debug_showArray').observe('click', function(e)
			{
				Debug.write( acp.template_editor.currentlyOpen.inspect() );
			});
			
		$('debug_curFile').observe('click', function(e)
			{
				Debug.dir( editAreaLoader.getAllFiles('editor_main') );
			});
			
		$('debug_modMap').observe('click', function(e)
			{
				Debug.dir( acp.template_editor.modifyMap );
			});			
	</script>
	
	<br /><br />
EOF;
}

$IPBHTML .= <<<EOF
<link rel="stylesheet" type="text/css" media='screen' href="{$this->settings['skin_acp_url']}/acp_templates.css" />
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.templates.js"></script>
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.tabbed_basic_editor.js"></script>
<div class='acp-box' id='template_editor'>  
	<h3>{$this->lang->words['sk_editingset']} {$setData['set_name']}</h3>
	<div id='template_toolbar'>
		<ul id='editor_buttons'>
			<li id='e_templates' class='left active' title='{$this->lang->words['sk_edittemplates']}'>{$this->lang->words['sk_templates']}</li>
			<li id='e_css' class='left' title='{$this->lang->words['sk_editcss']}'>{$this->lang->words['sk_css']}</li>
		</ul>
		<ul id='document_buttons'>
			<li id='t_save' class='left disabled'>{$this->lang->words['sk_save']}</li>
			<li id='t_status' class='left'></li>
			<!--<li id='t_saveall' class='left disabled'>Save All</li>-->
			<li id='t_revert' class='right disabled'>{$this->lang->words['sk_revert']}</li>
			<li id='t_compare' class='right disabled'>{$this->lang->words['sk_comparediff']}</li>
			<li id='t_variables' class='right disabled'>{$this->lang->words['sk_variables']}</li>
			<li id='t_properties' class='right disabled' style='display: none'>{$this->lang->words['sk_cssprops']}</li>
		</ul>
	</div>
	<div id='left_pane' style='width: 19%; float: left'>
		<div id='template_list_wrap'>
			<div id='menu_template' class='template_menu'>
				<ul>
					<li id='t_add_bit'>{$this->lang->words['sk_addbit']}</li>
				</ul>
			</div>
			<ul id='template_list' class='parts_list'>
			</ul>
		</div>
		<div id='css_list_wrap' style='display: none'>
			<div id='menu_css' class='template_menu'>
				<ul>
					<li id='css_add_css'>{$this->lang->words['sk_addcssfile']}</li>
				</ul>
			</div>
			<ul id='css_list' class='parts_list'>
			</ul>
		</div>
	</div>
	<div id='right_pane' style='width: 80%; float: right'>
		<div id='template_editor'></div>
	</div>
	
	<div id='template_footer' class='acp-actionbar'>
		<strong>{$this->lang->words['sk_legend']}:</strong> <img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/templates/default.png' alt='' title='{$this->lang->words['sk_l_default']}' />{$this->lang->words['sk_l_default_full']}&nbsp;&nbsp;&nbsp;
		<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/templates/modified.png' alt='' title='{$this->lang->words['sk_l_modified']}' />{$this->lang->words['sk_l_modified_full']}&nbsp;&nbsp;&nbsp;
		<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/templates/inherit.png' alt='' title='{$this->lang->words['sk_l_inherited']}' />{$this->lang->words['sk_l_inherited_full']}&nbsp;&nbsp;&nbsp;
		<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/templates/new.png' alt='' title='{$this->lang->words['sk_l_new']}' />{$this->lang->words['sk_l_new_full']}
	</div>
	
</div>

<script type='text/javascript'>
	ipb.templates['template_group'] = new Template("<li id='#{id}'>#{title}</li>");
	ipb.templates['template_bit'] = new Template("<li id='#{id}'><img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/templates/#{icon}.png' alt='' title='#{icon}' /> #{title} <img src='{$this->settings['skin_acp_url']}/images/icons/bullet_delete.png' alt='' class='delete_icon' style='display: none' id='delete_bit_#{id}' /></li>");
	ipb.templates['css_file'] = new Template("<li id='#{id}'><img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/templates/#{icon}.png' alt='' title='#{icon}' /> #{name} <img src='{$this->settings['skin_acp_url']}/images/icons/bullet_delete.png' alt='' class='delete_icon' style='display: none' id='delete_css_#{id}' /></li>");
	
	/* TEMPLATES FOR POPUPS */
	ipb.templates['form_add_bit'] = "<div class='acp-box'><h3>{$this->lang->words['sk_addbit']}</h3><table class='ipsTable'><tr><td class='field_title'><strong class='title'>{$this->lang->words['sk_bitname']}:</strong></td><td class='field_field'><input type='text' class='input_text' id='add_bit_name' /><br /><span class='desctext'>{$this->lang->words['sk_alphanumericonly']}</span></td></tr><tr><td class='field_title'><strong class='title'>{$this->lang->words['sk_group']}:</strong></td><td class='field_field'><select id='add_bit_group' class='input_select'></select></td></tr><tr><td class='field_title'><strong class='title'>{$this->lang->words['sk_newgroup']}:</strong></td><td class='field_field'>skin_<input type='text' class='input_text' id='add_bit_new_group' /></td></tr><tr><td class='field_title'><strong class='title'>{$this->lang->words['sk_datavariables']}</strong></td><td class='field_field'><input type='text' id='add_bit_variables' class='input_text' /></td></tr></table><div class='acp-actionbar'><input type='submit' value='{$this->lang->words['sk_add']}' class='realbutton' id='add_bit_submit' /></div></div>";
	
	ipb.templates['form_add_css'] = "<div class='acp-box'><h3>{$this->lang->words['sk_addcssfile']}</h3><table class='ipsTable'><tr><td class='field_title'><strong class='title'>{$this->lang->words['sk_cssname']}:</strong></td><td class='field_field'><input type='text' class='input_text' id='add_css_name' />.css<br /><span class='desctext'>{$this->lang->words['sk_alphanumericonly']}</span></td></tr></table><div class='acp-actionbar'><input type='submit' value='{$this->lang->words['sk_add']}' class='realbutton' id='add_css_submit' /></div></div>";
	
	ipb.templates['edit_variables'] = new Template("<div class='acp-box'><h3>{$this->lang->words['sk_editvariables']}</h3><table class='ipsTable'><tr><td><textarea class='input_text' id='variables_#{id}' rows='5' cols='30' style='width: 98%'>#{value}</textarea></td></tr></table><div class='acp-actionbar'><input type='submit' value='{$this->lang->words['um_savechanges']}' class='realbutton' id='edit_variables_#{id}' /></div></div>");
	
	ipb.templates['css_properties'] = new Template("<div class='acp-box'><h3>{$this->lang->words['sk_editcssprops']}</h3><table class='ipsTable'><tr><td class='field_title'><strong class='title'>{$this->lang->words['sk_cssposition']}</strong></td><td class='field_field'><select id='cssposition_#{id}'>#{cssposition}</select><br /><span class='desctext'>{$this->lang->words['sk_cssposition_desc']}</span></td></tr><tr><td class='field_title'><strong class='title'>{$this->lang->words['sk_cssattributes']}</strong></td><td class='field_field'><input type='text' class='input_text' size='35' id='cssattributes_#{id}' value='#{attributes}' /><br /><span class='desctext'>{$this->lang->words['sk_cssattributes_desc']}</span></td></tr><tr><td class='field_title'><strong class='title'>{$this->lang->words['sk_cssapp']}</strong></td><td class='field_field'><input type='text' class='input_text' size='15' id='cssapp_#{id}' value='#{app}' /><br /><span class='desctext'>{$this->lang->words['sk_cssapp_desc']}</span></td></tr><tr><td class='field_title'><strong class='title'>{$this->lang->words['sk_cssmodules']}</strong></td><td class='field_field'><input type='text' class='input_text' size='15' id='cssmodules_#{id}' value='#{modules}' /><br /><span class='desctext'>{$this->lang->words['sk_cssmodules_desc']}</span></td></tr><tr><td class='field_title'></td><td class='field_field'><input type='checkbox' id='cssapphide_#{id}' value='1' #{apphide} /> &nbsp;<strong>{$this->lang->words['sk_cssapphide']}</strong><br /><span class='desctext'>{$this->lang->words['sk_cssapphide_desc']}</span></td></tr></table><div class='acp-actionbar'><input type='submit' value='{$this->lang->words['sk_updateproperties']}' class='realbutton' id='save_properties_#{id}' /></div></div>");
	
	acp.tabbedEditor.wrapId = 'right_pane';
	acp.tabbedEditor.callbacks['open']   = acp.template_editor.CALLBACK_editor_loaded;
	acp.tabbedEditor.callbacks['close']  = acp.template_editor.CALLBACK_template_closed;
	acp.tabbedEditor.callbacks['switch'] = acp.template_editor.CALLBACK_file_switch;
	acp.tabbedEditor.initialize();
	

	acp.template_editor.templateGroups       = $_json;
	acp.template_editor.currentTemplateGroup = '{$_first}';
	acp.template_editor.currentSetData       = $_setData;
	acp.template_editor.cssFiles			 = $_css;
	acp.template_editor.initialize();
</script>

<div style='clear: both'></div>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Form to import/export skin sets, replacements, images
 *
 * @param	array 		Skin sets
 * @param	array		Form data
 * @param	array 		Warnings
 * @return	string		HTML
 */
public function importexport_form( $sets, $form, $warnings ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['ss_importexport']}</h2>
</div>

<a name='#tabpane'></a>
<div class='acp-box'>
	<h3>{$this->lang->words['ss_importexport']}</h3>
<div class='ipsTabBar' id='tabstrip'>
	<ul>
		<li id='tab_1' class='active'>
			{$this->lang->words['sk_import']}
		</li>
		<li id='tab_2'>
			{$this->lang->words['sk_export']}
		</li>
	</ul>
</div></div>
<br /><br />
<div id='tabContent' class='ipsTabBar_content'>
	<div id='tab_1_content'>
		<div class='acp-box'>
		<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=importSet' enctype="multipart/form-data" id='import1' method='POST'>
			<h3>{$this->lang->words['sk_importskinset']}</h3>
			<table class='ipsTable double_pad'>
EOF;
				if ( $warnings['importSkinCacheDir'] )
				{
					$IPBHTML .= <<<EOF
						<tr>
							<td colspan='2'><div class='warning'>{$this->lang->words['sk_fail_cache']}</div></td>
						</tr>
EOF;
				}
				
				$IPBHTML .= <<<EOF
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_uploadxmlarchive']}</strong>
					</td>
					<td class='field_field'>
						{$form['uploadField']}<br />
						<span class='desctext'>{$this->lang->words['sk_xmlorxmlgz']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_ornamearchive']}</strong>
					</td>
					<td class='field_field'>
						{$form['importLocation']}<br />
						<span class='desctext'>{$this->lang->words['sk_intoroot']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_newsetname']}</strong>
					</td>
					<td class='field_field'>
						{$form['importName']}<br />
						<span class='desctext'>{$this->lang->words['sk_leaveblank']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_upgrade']}</strong>
					</td>
					<td class='field_field'>
						{$form['importUpgrade']}<br />
						<span class='desctext'>{$this->lang->words['sk_doupgrade']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_useimageset']}</strong>
					</td>
					<td class='field_field'>
						{$form['importImgDirs']}<br />
						<span class='desctext'>{$this->lang->words['sk_useimageset_info']}</span>
					</td>
				</tr>
			</table>
			<div class="acp-actionbar">
				<input type='submit' value='{$this->lang->words['sk_importskinset']}' class='realbutton' />
			</div>
		</form>
		</div>
		<br />
		<div class='acp-box'>
		<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=importImages' enctype="multipart/form-data" id='import1' method='POST'>
			<h3>{$this->lang->words['sk_importimgset']}</h3>
			<table class='ipsTable double_pad'>
EOF;
				if ( $warnings['importImgDir'] )
				{
					$IPBHTML .= <<<EOF
						<tr>
							<td colspan='2'><div class='warning'>{$this->lang->words['sk_fail_images']}</div></td>
						</tr>
EOF;
				}
				
				$IPBHTML .= <<<EOF
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_uploadimgxml']}</strong>
					</td>
					<td class='field_field'>
						{$form['uploadField']}<br />
						<span class='desctext'>{$this->lang->words['sk_xmlorxmlgz']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_ornamearchive']}</strong>
					</td>
					<td class='field_field'>
						{$form['importLocation']}<br />
						<span class='desctext'>{$this->lang->words['sk_intoroot']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_newimgsetname']}</strong>
					</td>
					<td class='field_field'>
						{$form['importName']}<br />
						<span class='desctext'>{$this->lang->words['sk_leaveblank']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_applytoskin']}</strong>
					</td>
					<td class='field_field'>
						<select name='setID'><option value='0'>-{$this->lang->words['sk_none']}-</option>{$sets}</select><br />
						<span class='desctext'>{$this->lang->words['sk_applytoskin_info']}</span>
					</td>
				</tr>
			</table>
			<div class="acp-actionbar">
				<input type='submit' value='{$this->lang->words['sk_importimgset']}' class='realbutton' />
			</div>
		</form>
		</div>
		<br />
		<div class='acp-box'>
		<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=importReplacements' enctype="multipart/form-data" id='import1' method='POST'>
			<h3>{$this->lang->words['sk_importreplacements']}</h3>
			<table class='ipsTable double_pad'>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_uploadxmlreplace']}</strong>
					</td>
					<td class='field_field'>
						{$form['uploadField']}<br />
						<span class='desctext'>{$this->lang->words['sk_xmlorxmlgz']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_ornamearchive']}</strong>
					</td>
					<td class='field_field'>
						{$form['importLocation']}<br />
						<span class='desctext'>{$this->lang->words['sk_intoroot']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_applytoskin']}</strong>
					</td>
					<td class='field_field'>
						<select name='setID'>{$sets}</select>
					</td>
				</tr>
			</table>
			<div class="acp-actionbar">
				<input type='submit' value='{$this->lang->words['sk_importreplacements']}' class='realbutton' />
			</div>
		</form>
		</div>
	</div>
	<div id='tab_2_content'>
		<div class='acp-box'>
		<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=exportSet' id='export1' method='POST'>
			<h3>{$this->lang->words['sk_exporttemplates']}</h3>
			<table class='ipsTable double_pad'>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_skinset']}</strong>
					</td>
					<td class='field_field'>
						<select name='setID'>{$sets}</select><br />
						<span class='desctext'>{$this->lang->words['sk_xs_info']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_for_apps']}</strong>
					</td>
					<td class='field_field'>
						<p><input type='checkbox' name='exportApps[core]' value='1' checked='checked' /> IP.Board</p><br />
						<span class='desctext'>{$this->lang->words['sk_for_apps_desc']}</span>
EOF;

foreach( ipsRegistry::$applications as $appDir => $app_data )
{
	if ( $appDir != 'core' AND $appDir != 'forums' AND $appDir != 'members' )
	{
		$IPBHTML .= "<p><input type='checkbox' name='exportApps[{$appDir}]' value='1'  checked='checked' /> {$app_data['app_title']}</p>\n";
	}
}

$IPBHTML .= <<<EOF
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['a_options']}</strong>
					</td>
					<td class='field_field'>
						{$form['exportSetOptions']}<br />
						<span class='desctext'>{$this->lang->words['sk_xs_info2']}</span>
					</td>
				</tr>
			</table>
			<div class="acp-actionbar">
				<input type='submit' value='{$this->lang->words['sk_exporttemplates']}' class='realbutton' />
			</div>
		</form>
		</div>
		<br />
		<div class='acp-box'>
		<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=exportImages' id='export2' method='POST'>
			<h3>{$this->lang->words['sk_exportimages']}</h3>
			<table class='ipsTable double_pad'>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_imageset']}</strong>
					</td>
					<td class='field_field'>
						{$form['exportImgDirs']}<br />
						<span class='desctext'>{$this->lang->words['sk_xr_info']}</span>
					</td>
				</tr>
			</table>
			<div class="acp-actionbar">
				<input type='submit' value='{$this->lang->words['sk_exportimages']}' class='realbutton' />
			</div>
		</form>
		</div>
		<br />
		<div class='acp-box'>
		<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=exportReplacements' id='export3' method='POST'>
			<h3>{$this->lang->words['sk_exportreplaces']}</h3>
			<table class='ipsTable double_pad'>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['sk_fromskinset']}</strong>
					</td>
					<td class='field_field'>
						<select name='setID'>{$sets}</select><br />
						<span class='desctext'>{$this->lang->words['sk_xr_info']}</span>
					</td>
				</tr>
			</table>
			<div class="acp-actionbar">
				<input type='submit' value='{$this->lang->words['sk_exportreplaces']}' class='realbutton' />
			</div>
		</form>
		</div>
	</div>
</div>
<script type='text/javascript'>
	jQ("#tabstrip").ipsTabBar({tabWrap: "#tabContent"});
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

}