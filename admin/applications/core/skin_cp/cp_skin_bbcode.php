<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * BBcode skin file
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 10721 $
 */
 
class cp_skin_bbcode
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
 * BBCode wrapper
 *
 * @param	string		Content (compiled HTML rows)
 * @return	string		HTML
 */
public function bbcodeWrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

<style type="text/css">
	@import url('{$this->settings['public_dir']}style_css/prettify.css');
</style>
<script type="text/javascript" src="{$this->settings['public_dir']}js/3rd_party/prettify/prettify.js"></script>
<!-- By default we load generic code, php, css, sql and xml/html; load others here if desired -->
<script type="text/javascript">
	Event.observe( window, 'load', function(e){ prettyPrint() });
</script>
	
<div class='section_title'>
	<h2>{$this->lang->words['bbcode_header']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_add'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/style_add.png' alt='' />
					{$this->lang->words['addnew_bbcode']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_export'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/style_go.png' alt='' />
					{$this->lang->words['export_bbcode']}
				</a>
			</li>
			<li class='ipsActionButton inDev'>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_import_all'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/style_add.png' alt='' />
					{$this->lang->words['import_bbcode_all']}
				</a>
			</li>
			<li class='ipsActionButton inDev'>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_export_all'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/style_go.png' alt='' />
					{$this->lang->words['export_bbcode_all']}
				</a>
			</li>
		</ul>
	</div>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['your_bbcodes']}</h3>
	<table class="ipsTable">
		<tr>
			<th width='45%'>{$this->lang->words['bbcode_title']}</th>
			<th width='50%'>{$this->lang->words['bbcode_tag']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
		{$content}
	</table>
</div>
<br />

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_test' method='post'>
<div class="acp-box">
	<h3>{$this->lang->words['test_parse']}</h3>
	<p class="pad" align="center"><textarea name='bbtest' rows='10' cols='70'>
EOF;

$IPBHTML .= isset($_POST['bbtest']) ? $_POST['bbtest'] : $this->lang->words['enter_test_parse'];

$IPBHTML .= <<<EOF
</textarea>
	</p>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['test_parse']}' class="button primary"/>
	</div>
</div>
</form>
<br />

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_import' method='post' enctype='multipart/form-data'>
	<div class="acp-box">
		<h3>{$this->lang->words['import_new_bbcode']}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['upload_bbcode_xml']}</strong></td>
				<td class='field_field'><input type='file' name='FILE_UPLOAD' /> <br /><span class='desctext'>{$this->lang->words['upload_bbcode_dupe']}</span></td>
			</tr>
		</table>
		
		<div class="acp-actionbar">
			<input type='submit' value='{$this->lang->words['bbcode_import']}' class="button primary" />
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * BBCode record
 *
 * @param	array		BBCode info
 * @return	string		HTML
 */
public function bbcodeRow( $row ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr class='ipsControlRow'>
 <td>{$row['bbcode_title']}</td>
 <td><pre>{$row['bbcode_fulltag']}</pre></td>
 <td class='col_buttons'>
 	<ul class='ipsControlStrip'>
 		<li class='i_edit'> 
			<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_edit&id={$row['bbcode_id']}' title='{$this->lang->words['edit_bbcode']}'>{$this->lang->words['edit_bbcode']}</a>
		</li> 
EOF;
  if( IN_DEV OR !$row['bbcode_protected'] ) { $IPBHTML .= <<<EOF
 		<li class='i_delete'> 
			<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_delete&id={$row['bbcode_id']}");' title='{$this->lang->words['delete_bbcode']}'>{$this->lang->words['delete_bbcode']}</a>
		</li> 
EOF;
 }
 $IPBHTML .= <<<EOF
 		<li class='i_export'> 
			<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_export&id={$row['bbcode_id']}' title='{$this->lang->words['export_bbcode']}'>{$this->lang->words['export_bbcode']}</a>
		</li> 
	</ul>
 </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * BBCode add/edit form
 *
 * @param	string		Type (add|edit)
 * @param	array 		BBcode info
 * @param	array 		Sections to edit in
 * @return	string		HTML
 */
public function bbcodeForm( $type, $bbcode, $sections ) {

//-----------------------------------------
// Set some of the form variables
//-----------------------------------------

$form_code			= $type == 'edit' ? 'bbcode_doedit' : 'bbcode_doadd';
$button				= $type == 'edit' ? $this->lang->words['edit_bbcode'] : $this->lang->words['addnew_bbcode'];
$secure_key			= ipsRegistry::getClass('adminFunctions')->getSecurityKey();

$all_groups 		= array( 0 => array ( 'all', $this->lang->words['all_groups'] ) );

foreach( $this->cache->getCache('group_cache') as $group_data )
{
	$all_groups[]	= array( $group_data['g_id'], $group_data['g_title'] );
}

$ss_dropdown		= array( 0 => array ( 'all', $this->lang->words['available_sections'] ) );

if( is_array($sections) AND count($sections) )
{
	foreach( $sections as $sect_key => $sect_value )
	{
		$ss_dropdown[]	= array( $sect_key, $sect_value );
	}
}

$form								= array();
$form['bbcode_title']				= $this->registry->output->formInput( 'bbcode_title', $this->request['bbcode_title'] ? $this->request['bbcode_title'] : $bbcode['bbcode_title'] );
$form['bbcode_desc']				= $this->registry->output->formTextarea( 'bbcode_desc', $this->request['bbcode_desc'] ? $this->request['bbcode_desc'] : $bbcode['bbcode_desc'] );
$form['bbcode_example']				= $this->registry->output->formTextarea( 'bbcode_example', $this->request['bbcode_example'] ? $this->request['bbcode_example'] : $bbcode['bbcode_example'] );
$form['bbcode_tag']					= '[ ' . $this->registry->output->formSimpleInput( 'bbcode_tag', $this->request['bbcode_tag'] ? $this->request['bbcode_tag'] : $bbcode['bbcode_tag'], 10) . ' ]';
$form['bbcode_useoption']			= $this->registry->output->formYesNo( 'bbcode_useoption', $this->request['bbcode_useoption'] ? $this->request['bbcode_useoption'] : $bbcode['bbcode_useoption'] );
$form['bbcode_switch_option']		= $this->registry->output->formYesNo( 'bbcode_switch_option', $this->request['bbcode_switch_option'] ? $this->request['bbcode_switch_option'] : $bbcode['bbcode_switch_option'] );
$form['bbcode_replace']				= $this->registry->output->formTextarea( 'bbcode_replace', htmlspecialchars($_POST['bbcode_replace'] ? $_POST['bbcode_replace'] : $bbcode['bbcode_replace']) );
$form['bbcode_menu_option_text']	= $this->registry->output->formSimpleInput( 'bbcode_menu_option_text', $this->request['bbcode_menu_option_text'] ? $this->request['bbcode_menu_option_text'] : $bbcode['bbcode_menu_option_text'], 50);
$form['bbcode_menu_content_text']	= $this->registry->output->formSimpleInput( 'bbcode_menu_content_text', $this->request['bbcode_menu_content_text'] ? $this->request['bbcode_menu_content_text'] : $bbcode['bbcode_menu_content_text'], 50);
$form['bbcode_single_tag']			= $this->registry->output->formYesNo( 'bbcode_single_tag', $this->request['bbcode_single_tag'] ? $this->request['bbcode_single_tag'] : $bbcode['bbcode_single_tag'] );
$form['bbcode_groups']				= $this->registry->output->formMultiDropdown( "bbcode_groups[]", $all_groups, $this->request['bbcode_groups'] ? $this->request['bbcode_groups'] : explode( ",", $bbcode['bbcode_groups'] ) );
$form['bbcode_sections']			= $this->registry->output->formMultiDropdown( "bbcode_sections[]", $ss_dropdown, $this->request['bbcode_sections'] ? $this->request['bbcode_sections'] : explode( ",", $bbcode['bbcode_sections'] ) );
$form['bbcode_php_plugin']			= $this->registry->output->formInput( 'bbcode_php_plugin', $this->request['bbcode_php_plugin'] ? $this->request['bbcode_php_plugin'] : $bbcode['bbcode_php_plugin'] );
$form['bbcode_no_parsing']			= $this->registry->output->formYesNo( 'bbcode_no_parsing', $this->request['bbcode_no_parsing'] ? $this->request['bbcode_no_parsing'] : $bbcode['bbcode_no_parsing'] );
$form['bbcode_protected']			= $this->registry->output->formYesNo( 'bbcode_protected', $this->request['bbcode_protected'] ? $this->request['bbcode_protected'] : $bbcode['bbcode_protected'] );
$form['bbcode_custom_regex']		= $this->registry->output->formTextarea( 'bbcode_custom_regex', htmlspecialchars($_POST['bbcode_custom_regex'] ? $_POST['bbcode_custom_regex'] : $bbcode['bbcode_custom_regex']) );

$apps     = array();

/* Application drop down options */
foreach( ipsRegistry::$applications as $app_dir => $app_data )
{
	$apps[] = array( $app_dir, $app_data['app_title'] );
}
		
$form['bbcode_app']					= $this->registry->output->formDropdown( 'bbcode_app', $apps, $this->request['bbcode_app'] ? $this->request['bbcode_app'] : $bbcode['bbcode_app'] );

$form['bbcode_optional_option']		= $this->registry->output->formYesNo( 'bbcode_optional_option', $this->request['bbcode_optional_option'] ? $this->request['bbcode_optional_option'] : $bbcode['bbcode_optional_option'] );
$form['bbcode_aliases']				= $this->registry->output->formTextarea( 'bbcode_aliases', $this->request['bbcode_aliases'] ? $this->request['bbcode_aliases'] : $bbcode['bbcode_aliases'] );
$form['bbcode_image']				= $this->registry->output->formInput( 'bbcode_image', $this->request['bbcode_image'] ? $this->request['bbcode_image'] : $bbcode['bbcode_image'] );

/* Content cache is enabled? */
if ( $type == 'edit' AND IPSContentCache::isEnabled() )
{
	$_cacheCount        = IPSContentCache::count();
	$form['drop_cache'] = $this->registry->output->formYesNo( 'drop_cache', $this->request['drop_cache'] );
	
	$this->lang->words['bbcache_action'] = sprintf( $this->lang->words['bbcache_action'], $_cacheCount );
}

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['custom_bbcode_head']}</h2>
</div>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$form_code}&amp;secure_key={$secure_key}' method='post'>
<input type='hidden' name='id' value='{$bbcode['bbcode_id']}' />
EOF;

if ( $form['drop_cache'] )
{
	$IPBHTML .= <<<EOF
		<div class='warning'>
		 <h4>{$this->lang->words['bbcache_title']}</h4>
		 {$this->lang->words['bbcache_desc']}
		<p><strong>{$this->lang->words['bbcache_action']}</strong> {$form['drop_cache']}</p>
		</div>
		<br />
EOF;
}

$IPBHTML .= <<<EOF
<div class="acp-box">
	<h3>{$button}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_title']}</span></td>
			<td class='field_field'>{$form['bbcode_title']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_description']}</strong></td>
			<td class='field_field'>{$form['bbcode_desc']} <br /><span class='desctext'>{$this->lang->words['bbcode_usedinguide']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_example']}</strong>
			<td class='field_field'>{$form['bbcode_example']}<span class='desctext'>{$this->lang->words['bbcode_usedinguide']}<br />{$this->lang->words['bbcode_example_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_tag']}</strong></td>
			<td class='field_field'>{$form['bbcode_tag']} <br /><span class='desctext'>{$this->lang->words['bbcode_tag_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_aliases']}</strong></td>
			<td class='field_field'>{$form['bbcode_aliases']} <br /><span class='desctext'>{$this->lang->words['bbcode_aliases_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_singletag']}</strong></td>
			<td class='field_field'>{$form['bbcode_single_tag']} <br /><span class='desctext'>{$this->lang->words['bbcode_singletag_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_useoption']}</strong></td>
			<td class='field_field'>{$form['bbcode_useoption']} <br /><span class='desctext'>{$this->lang->words['bbcode_useoption_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_customregex']}</strong></td>
			<td class='field_field'>{$form['bbcode_custom_regex']} <br /><span class='desctext'>{$this->lang->words['bbcode_customregex_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_optional']}</strong></td>
			<td class='field_field'>{$form['bbcode_optional_option']} <br /><span class='desctext'>{$this->lang->words['bbcode_optional_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_switch']}</strong></td>
			<td class='field_field'>{$form['bbcode_switch_option']} <br /><span class='desctext'>{$this->lang->words['bbcode_switch_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_noparse']}</strong></td>
			<td class='field_field'>{$form['bbcode_no_parsing']} <br /><span class='desctext'>{$this->lang->words['bbcode_noparse_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_replace']}</strong></td>
			<td class='field_field'>{$form['bbcode_replace']} <br /><span class='desctext'>{$this->lang->words['bbcode_replace_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_php']}</strong></td>
			<td class='field_field'>{$form['bbcode_php_plugin']}<br /><span class='desctext'>{$this->lang->words['bbcode_php_info_loc']} <br />{$this->lang->words['bbcode_php_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_groups']}</strong></td>
			<td class='field_field'>{$form['bbcode_groups']} <br /><span class='desctext'>{$this->lang->words['bbcode_groups_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_whereused']}</strong></td>
			<td class='field_field'>{$form['bbcode_sections']} <br /><span class='desctext'>{$this->lang->words['bbcode_whereused_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_assoc_app']}</strong></td>
			<td class='field_field'>{$form['bbcode_app']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_image']}</strong></td>
			<td class='field_field'>{$form['bbcode_image']}<br /> <span class='desctext'>{$this->lang->words['bbcode_image_info_loc']} <br />{$this->lang->words['bbcode_image_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_optdial']}</strong></td>
			<td class='field_field'>{$form['bbcode_menu_option_text']} <br /><span class='desctext'>{$this->lang->words['bbcode_optdial_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_contdial']}</strong></td>
			<td class='field_field'>{$form['bbcode_menu_content_text']} <br /><span class='desctext'>{$this->lang->words['bbcode_contdial_infp']}</span></td>
		</tr>
EOF;


if( IN_DEV )
{
	$IPBHTML .= <<<EOF
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bbcode_protected']}</strong></td>
			<td class='field_field'>{$form['bbcode_protected']} <br /><span class='desctext'>{$this->lang->words['bbcode_protected_info']}</span></td>
		</tr>
EOF;
}

	$IPBHTML .= <<<EOF
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$button}' class="button primary" />
	</div>
</div>	
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Media tag add/edit form
 *
 * @param	string		Type (add|edit)
 * @param	array 		Tag info
 * @param	array 		Errors
 * @return	string		HTML
 */
public function mediaTagForm( $type, $data, $errors=array() ) {

//-----------------------------------------
// Set some of the form variables
//-----------------------------------------

$form_code			= $type == 'edit' ? 'domediatagedit' : 'domediatagadd';
$button				= $type == 'edit' ? $this->lang->words['media_edit'] : $this->lang->words['media_add'];
$title				= $type == 'edit' ? $this->lang->words['media_edit_replace'] : $this->lang->words['media_add_replace'];
$secure_key			= ipsRegistry::getClass('adminFunctions')->getSecurityKey();

$form								= array();
$form['mediatag_name']				= $this->registry->output->formInput( 'mediatag_name', $data['mediatag_name'], 'mediatag_name', 50  );
$form['mediatag_match']				= $this->registry->output->formInput( 'mediatag_match', $data['mediatag_match'], 'mediatag_match', 50 );
$form['mediatag_replace']			= $this->registry->output->formTextarea( 'mediatag_replace', $data['mediatag_replace']  );


$IPBHTML = "";
//--starthtml--//

if( is_array($errors) AND count($errors) )
{
	$error_string	= implode( '', $errors );
	
	$IPBHTML .= <<<EOF
	<div class='warning'><h4>{$this->lang->words['media_error']}</h4><ul>{$error_string}</ul></div><br />
EOF;
}

$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$form_code}&amp;secure_key={$secure_key}' method='post'>
<input type='hidden' name='id' value='{$data['mediatag_id']}' />
<div class="acp-box">
	<h3>{$title}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['media_title']}</strong></td>
			<td class='field_field'>{$form['mediatag_name']} <br /><span class='desctext'>{$this->lang->words['media_title_info']}</span></td>			
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['media_match']}</strong></td>
			<td class='field_field'>{$form['mediatag_match']} <br /><span class='desctext'>{$this->lang->words['media_match_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['media_html']}</strong></td>
			<td class='field_field'>{$form['mediatag_replace']} <br /><span class='desctext'>{$this->lang->words['media_html_info']}</span></td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$button}' class="button primary" />
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Media tags wrapper
 *
 * @param	string		Content (compiled HTML rows)
 * @return	string		HTML
 */
public function mediaTagWrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['media_tag_title']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=form_add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['media_add']}</a></li>
		<li><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=mediatag_export'><img src='{$this->settings['skin_acp_url']}/images/icons/export.png' alt='' /> {$this->lang->words['media_exports']}</a></li>
	</ul>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['media_current']}</h3>
	<table id='mediatag_container' class='ipsTable'>
		<tr>
		    <th class='col_drag'>&nbsp;</th>
			<th width='95%'>{$this->lang->words['media_name']}</th>
			<th width='5%'>{$this->lang->words['bbcode_options']}</th>
		</tr>
		{$content}
	</table>
</div>
<br />

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=mediatag_import' method='post' enctype='multipart/form-data'>
<div class="acp-box">
	<h3>{$this->lang->words['media_import']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['media_upload']}</strong></td>
			<td class='field_field'><input type='file' name='FILE_UPLOAD' /> <br /><span class='desctext'>{$this->lang->words['media_upload_desc']}</span></td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['media_import_button']}' class="button primary" />
	</div>
</div>
</form>

<script type='text/javascript'>
	jQ("#mediatag_container").ipsSortable( 'table', { 
		url: "{$this->settings['base_url']}&{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Media tag record
 *
 * @param	array		Row
 * @return	string		HTML
 */
public function mediaTagRow( $row ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

		<tr id='media_{$row['mediatag_id']}' class='ipsControlRow isDraggable'>
			<td class='col_drag'>
				<div class='draghandle'>&nbsp;</div>
			</td>
			<td style='width: 93%;'><strong class='title'>{$row['mediatag_name']}</td></td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=form_edit&id={$row['mediatag_id']}' title='{$this->lang->words['media_edit']}'>{$this->lang->words['media_edit']}</a></li>
					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=do_del&amp;id={$row['mediatag_id']}")' title='{$this->lang->words['media_delete']}'>{$this->lang->words['media_delete']}</a></li>
					<li class='i_export'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=mediatag_export&id={$row['mediatag_id']}' title='{$this->lang->words['media_export']}'>{$this->lang->words['export_bbcode']}</a></li>
				</ul>
			</td>
		</tr>

EOF;

//--endhtml--//
return $IPBHTML;
}


}