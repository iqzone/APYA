<?php
/**
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP settings skin file
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
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
 
class cp_skin_settings
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
 * Setting titles wrapper
 *
 * @param	array 		Setting groups
 * @param	string		Application tab to start on
 * @return	string		HTML
 */
public function settings_titles_wrapper($settings, $start_app='') {

$IPBHTML = "";
//--starthtml--//

$_default_tab = !empty( $this->request['_dtab'] ) ? $this->request['_dtab'] : 'System';

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['tol_settings']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=settinggroup_new'><img src='{$this->settings['skin_acp_url']}/images/icons/table_add.png' alt='' /> {$this->lang->words['tol_add_new_setting_group']}</a>
			</li>
			<!--<li class='ipsActionButton'>
				<a href='#'><img src='{$this->settings['skin_acp_url']}/images/icons/import.png' alt='' /> {$this->lang->words['tol_import_xml_settings']}</a>
			</li>-->
			<li class='ipsActionButton inDev'><a href='{$this->settings['base_url']}{$this->form_code}&do=settingsImportApps'><img src='{$this->settings['skin_acp_url']}/images/icons/import.png' alt='' /> Import All XML Settings</a></li>
			<li class='ipsActionButton inDev'><a href='{$this->settings['base_url']}{$this->form_code}&do=settingsExportApps'><img src='{$this->settings['skin_acp_url']}/images/icons/export.png' alt='' /> Export All XML Settings</a></li>
		</ul>
	</div>
</div>

<div class='acp-box'>
<h3>{$this->lang->words['tol_settings_groups']}</h3>	
<div id='tabstrip_settings' class='ipsTabBar with_left with_right'>
	<span class='tab_left'>&laquo;</span>
	<span class='tab_right'>&raquo;</span>
	<ul>
EOF;

foreach( $settings as $tab => $group )
{
	if ( ipsRegistry::$request['app'] AND $tab == ipsRegistry::$request['app'] )
	{
		$_default_tab = $tab;
	}
	
	$_tab	= IPSText::md5Clean( $tab );
	
$IPBHTML .= <<<EOF
	<li id='tab_{$_tab}'>{$tab}</li>
	
EOF;
}

$IPBHTML .= <<<EOF
	</ul>
</div>

<div id='tabstrip_settings_content' class='ipsTabBar_content'>
EOF;


foreach( $settings as $tab => $app_data )
{
	$_tab	= IPSText::md5Clean( $tab );
$IPBHTML .= <<<EOF
	<div id='tab_{$_tab}_content'>
		<table class='ipsTable double_pad'>
		
EOF;
		foreach( $app_data as $r )
		{
			
			if(IN_DEV)
			{
				$export_settings_group = "<li><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=MOD_export_setting&amp;conf_group={$r['conf_title_id']}' title='{$this->lang->words['export_group']}'><img src='{$this->settings['skin_acp_url']}/images/options_menu/export_settings_group.png' alt='' /> {$this->lang->words['export_group']}</a>
				</li>";
			}
			
			$img = is_file( IPSLib::getAppDir( $r['conf_title_app'] ) . '/skin_cp/appIcon.png' ) ? $this->settings['base_acp_url'] . '/' . IPSLib::getAppFolder( $r['conf_title_app'] ) . '/' . $r['conf_title_app'] . '/skin_cp/appIcon.png' : "{$this->settings['skin_acp_url']}/images/applications/{$r['conf_title_app']}.png";
			
$IPBHTML .= <<<EOF
		<tr class='ipsControlRow'>
		 	<td width='3%' style='text-align: center'><img src='{$img}' alt='{$this->lang->words['tol_folder']}' /></td>
		 	<td width='80%'>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=setting_view&amp;conf_group={$r['conf_title_id']}' class='larger_text'><b>{$r['conf_title_title']}</b></a>
				<span class='desctext'>({$r['conf_title_count']} {$this->lang->words['settings_suffix']})</span>
EOF;

if( $r['conf_title_desc'] )
{
	$IPBHTML .= <<<EOF
				<br /><span class='desctext'>{$r['conf_title_desc']}</span>
EOF;
}

$IPBHTML .= <<<EOF
			</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=settinggroup_showedit&amp;id={$r['conf_title_id']}' title='{$this->lang->words['tol_edit_settings_group']}'>{$this->lang->words['tol_edit_settings_group']}</a>
					</li>
					<li class='i_delete'>
						<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=settinggroup_delete&amp;id={$r['conf_title_id']}");' title='{$this->lang->words['tol_delete_settings_group']}'>{$this->lang->words['tol_delete_settings_group']}</a>
					</li>
					<li class='ipsControlStrip_more'>
						<a href='#' id="menu{$r['conf_title_id']}" class='ipbmenu'>{$this->lang->words['frm_options']}</a>
					</li>
				</ul>	
				<ul class='acp-menu' id='menu{$r['conf_title_id']}_menucontent' style='display: none'>
					<li><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=settinggroup_resync&amp;id={$r['conf_title_id']}' title='{$this->lang->words['tol_recount_settings_group']}'><img src='{$this->settings['skin_acp_url']}/images/options_menu/rebuild_settings_group.png' alt='Icon' /> {$this->lang->words['tol_recount_settings_group']}</a></li>
					{$export_settings_group}
				</ul>
			</td>
		</tr>
EOF;
		}
$IPBHTML .= <<<EOF
		</table>
	</div>
	
EOF;

}
	
$IPBHTML .= <<<EOF
</div>
</div>

<script type='text/javascript'>
	jQ("#tabstrip_settings").ipsTabBar({tabWrap: "#tabstrip_settings_content", defaultTab: "tab_{$_default_tab}" });
</script>

<br />

<form action='{$this->settings['base_url']}&{$this->form_code}&do=settings_do_import' enctype='multipart/form-data' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['tol_import_xml_settings']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['tol_upload_xml_settings_file_from_']}</strong></td>
				<td class='field_field'><input class='textinput' type='file' size='30' name='FILE_UPLOAD' /><br /><span class='desctext'>{$this->lang->words['tol_duplicate_entries_will_not_be_']}</span></td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['tol_or_enter_the_filename_of_the_x']}</strong></td>
				<td class='field_field'><input class='textinput' type='text' size='30' name='file_location' /><br /><span class='desctext'>{$this->lang->words['tol_the_file_must_be_uploaded_into']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' class='button primary' value='{$this->lang->words['t_import']}' />
		</div>
	</div>
</form>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit a setting group
 *
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @return	string		HTML
 */
public function settings_title_form($form, $title, $formcode, $button) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['s_title']}</h2>
</div>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;id={$this->request['id']}' method='post'>
	<div class='acp-box'>
		<h3>{$title}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_setting_group_title']}</strong>
				</td>
				<td class='field_field'>{$form['conf_title_title']}</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_setting_group_application']}</strong>
				</td>
				<td>{$form['conf_title_app']}</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_setting_group_tab']}</strong>
				</td>
				<td>{$form['conf_title_tab']}</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_setting_group_description']}</strong>
				</td>
				<td>{$form['conf_title_desc']}</td>
			</tr>
EOF;
//startif
if ( $form['conf_title_keyword'] != '' )
{		
$IPBHTML .= <<<EOF
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_setting_group_keyword']}</strong>
				</td>
				<td>
					{$form['conf_title_keyword']}<br />
					<span class='desctext'>{$this->lang->words['tol_used_to_pull_this_from_the_db_']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_hide_from_main_settings_list']}</strong>
				</td>
				<td>{$form['conf_title_noshow']}</td>
			</tr>
EOF;
}//endif
$IPBHTML .= <<<EOF
		</table>
		<div class='acp-actionbar'>
			<input type='submit' class='button primary' value='{$button}' />
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * View settings wrapper
 *
 * @param	string		Page title
 * @param	string		Content
 * @param	string		Search button
 * @param	string		Bounceback URL
 * @return	string		HTML
 */
public function settings_view_wrapper( $title, $content, $searchbutton, $bounceback='' ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['settings_h_prefix']} {$title}</h2>
EOF;

	if ( ipsRegistry::$request['search'] == '' AND $bounceback == '' )
	{
		if ( $searchbutton )
		{
	$IPBHTML .= <<<EOF
		<div class='ipsActionBar clearfix'>
			<ul>
				<li class='ipsActionButton'>
					<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=settingnew&amp;conf_group={$this->request['conf_group']}'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' />{$this->lang->words['add_setting_button']}</a>
				</li>
				
			</ul>
		</div>
EOF;
		}
	}//endif
	
$IPBHTML .= <<<EOF
</div>
<form action='{$this->settings['_base_url']}app=core&amp;module=settings&amp;section=settings&amp;do=setting_update&amp;id={$this->request['conf_group']}&amp;search={$this->request['search']}' method='post'>
	<!--HIDDEN.FIELDS-->
	<input type='hidden' name='bounceback' value='{$bounceback}' />
	<div class='acp-box'>
		<h3>{$title}</h3>
		<table class='form_table' id='settings_table'>
			{$content}
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['tol_update_settings']}' class='button primary' />
		</div>
	</div>
</form>
<script type='text/javascript'>
	jQ("#settings_table").ipsSortable( 'table', { 
		url: "{$this->settings['base_url']}{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ),
		serializeOptions: { key: 'settings[]' }
	});
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Setting that starts a setting group
 *
 * @param	array 		Setting
 * @return	string		HTML
 */
public function settings_row_start_group($r) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<tr class='isDraggable' id='setting_{$r['conf_id']}'>
		<td colspan='4' style='padding:0px;'>
			<table class='form_table'>
				<tr>
					<th colspan='4'>{$r['conf_start_group']}</th>
				</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * View a setting row
 *
 * @param	array 		Setting
 * @param	string		Edit button
 * @param	string		Delete button
 * @param	string		Form element
 * @param	string		Revert button
 * @return	string		HTML
 */
public function settings_view_row($r, $edit, $delete, $form_element, $revert_button, $elem_type) {

$IPBHTML = "";
//--starthtml--//

$this->row_class = $this->row_class == 'row1' ? 'row2' : 'row1';

$_isClass	= !$r['conf_start_group'] ? "isDraggable" : '';

$IPBHTML .= <<<EOF
  		<tr class='ipsControlRow {$this->row_class} {$_isClass}' id='setting_{$r['conf_id']}'>
  			<td class='col_drag'><div class='draghandle'>&nbsp;</div></td>
			
			<td class='field_title'>
				<strong class='title'>{$r['conf_title']}</strong>
			</td>
			<td class='field_field'>
				{$form_element}<br />
				<span class='desctext'>{$r['conf_description']}</span>
			</td>
				<td class='col_buttons' nowrap="true">
EOF;
				//startif
				if ( $edit or $delete or $revert_button )
				{		
				$IPBHTML .= <<<EOF
					
					<ul class='ipsControlStrip'>
						{$revert_button}
						{$edit}
						{$delete}
					</ul>
EOF;
				}//endif
$IPBHTML .= <<<EOF
			</td>
		</tr>
EOF;

if( $r['conf_start_group'] )
{
	$IPBHTML .= <<<EOF
		</table>
	</td>
</tr>
EOF;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Automcplete field
 *
 * @return	string		HTML
 */
public function nameAutoCompleteField( $key, $val ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<input type='text' id='{$key}' name='{$key}' value='{$val}' autocomplete='off' style='width:210px;' class='textinput' />

	<script type="text/javascript" defer="defer">
	document.observe("dom:loaded", function(){
		var search = new ipb.Autocomplete( $('{$key}'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
	});
	</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit a setting
 *
 * @param	array 		Setting
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @return	string		HTML
 */
public function settings_form($form, $title, $formcode, $button) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['add_setting_button']}</h2>
</div>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;id={$this->request['id']}' method='post'>
	<div class='acp-box'>
		<h3>{$title}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_setting_title']}</strong>
				</td>
				<td class='field_field'>
					{$form['conf_title']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_setting_position']}</strong>
				</td>
				<td class='field_field'>
					{$form['conf_position']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_setting_description']}</strong>
				</td>
				<td class='field_field'>
					{$form['conf_description']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_setting_group']}</strong>
				</td>
				<td class='field_field'>
					{$form['conf_group']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_setting_type']}</strong>
				</td>
				<td class='field_field'>
					{$form['conf_type']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_setting_key']}</strong>
				</td>
				<td class='field_field'>
					{$form['conf_key']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_setting_current_value']}</strong>
				</td>
				<td class='field_field'>
					{$form['conf_value']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_setting_default_value']}</strong>
				</td>
				<td class='field_field'>
					{$form['conf_default']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_setting_extra']}</strong>
				</td>
				<td class='field_field'>
					{$form['conf_extra']}<br />
					<span class='desctext'>{$this->lang->words['tol_use_for_creating_form_element_']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_raw_php_code_to_eval_before_sh']}</strong>
				</td>
				<td class='field_field'>
					{$form['conf_evalphp']}<br />
					<span class='desctext'>{$this->lang->words['tol_036show_1_is_set_when_showing_']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['setting_keywords']}</strong>
				</td>
				<td class='field_field'>
					{$form['conf_keywords']}<br />
					<span class='desctext'>{$this->lang->words['setting_keywords_desc']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_start_setting_group']}</strong>
				</td>
				<td class='field_field'>
					{$form['conf_start_group']}<br />
					<span class='desctext'>{$this->lang->words['tol_enter_title_here_or_leave_blan']}</span>
				</td>
			</tr>
EOF;
//startif
if ( $form['conf_protected'] != '' )
{		
$IPBHTML .= <<<EOF
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_make_a_default_setting_cannot_']}</strong>
				</td>
				<td class='field_field'>
					{$form['conf_protected']}
				</td>
			</tr>
EOF;
}//endif
$IPBHTML .= <<<EOF
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_add_this_option_into_the_setti']}</strong>
				</td>
				<td class='field_field'>
					{$form['conf_add_cache']}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' class='button primary' value='{$button}' />
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}
}