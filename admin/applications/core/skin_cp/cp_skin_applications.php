<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Applications skin file
 * Last Updated: $Date: 2012-05-21 09:09:36 -0400 (Mon, 21 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 10771 $
 */
 
class cp_skin_applications
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
 * Add/edit module form
 *
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		Module information
 * @param	array 		Application information
 * @return	string		HTML
 */
public function module_form( $form, $title, $formcode, $button, $module, $application ) {

$IPBHTML = "";
//--starthtml--//

$title	= $formcode == 'module_edit_do' ? $this->lang->words['module_form_edit_title'] : $this->lang->words['module_form_add_title'];

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form id='mainform' action='{$this->settings['base_url']}{$this->form_code}&amp;do={$formcode}&amp;app_id={$application['app_id']}&amp;sys_module_id={$module['sys_module_id']}' method='POST'>
	<div class='acp-box'>
	<h3>{$this->lang->words['a_modules']}</h3>
 
 	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['a_modtype']}</strong>
			</td>
			<td class='field_field'>
				{$form['sys_module_admin']}
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['a_modtitle']}</strong>
			</td>
			<td class='field_field'>
				{$form['sys_module_title']}
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['a_moddesc']}</strong>
			</td>
			<td class='field_field'>
				{$form['sys_module_description']}
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['a_modkey']}</strong>
			</td>
			<td class='field_field'>
				{$form['sys_module_key']}<br />
				<span class='desctext'>{$this->lang->words['a_modkey_info']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['a_modver']}</strong>
			</td>
			<td class='field_field'>
				{$form['sys_module_version']}<br />
				<span class='desctext'>{$this->lang->words['a_modver_info']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['a_moden']}</strong>
			</td>
			<td class='field_field'>
				{$form['sys_module_visible']}<br />
				<span class='desctext'>{$this->lang->words['a_moden_info']}</span>
			</td>
		</tr>
EOF;
if ( IN_DEV )
{
$IPBHTML .= <<<EOF
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['a_modprot']}</strong>
				</td>
				<td class='field_field'>
					{$form['sys_module_protected']}<br />
					<span class='desctext'>{$this->lang->words['a_modprot_info']}</span>
				</td>
			</tr>
EOF;
}
$IPBHTML .= <<<EOF
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
 * List the modules
 *
 * @param	array 		Modules
 * @param	array 		Application
 * @param	boolean		Is an admin module?
 * @return	string		HTML
 */
public function modules_list( $modules, $application, $sys_module_admin=true ) {

$IPBHTML = "";
//--starthtml--//

$_type = ( $sys_module_admin ) ? strtolower( $this->lang->words['a_admin'] ) : strtolower( $this->lang->words['a_public'] );

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$_type} {$application['app_title']} {$this->lang->words['a_modules']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}module=applications&amp;section=applications&amp;do=module_add&amp;app_id={$this->request['app_id']}&amp;sys_module_admin={$this->request['sys_module_admin']}'><img src='{$this->settings['skin_acp_url']}/images/icons/plugin_add.png' alt='' /> {$this->lang->words['add_new_mod_' . $_type]}</a></li>
		<li><a href='{$this->settings['base_url']}module=applications&amp;section=applications&amp;do=module_export&amp;app_id={$this->request['app_id']}&amp;sys_module_admin={$this->request['sys_module_admin']}'><img src='{$this->settings['skin_acp_url']}/images/icons/export.png' alt='' /> {$this->lang->words['export_mods_as_xml']}</a></li>
	</ul>
</div>

<div class='acp-box'>
	<h3>{$application['app_title']} &gt; {$_type} {$this->lang->words['a_modules']}</h3>
 	<table class='ipsTable' id='module_list'>
EOF;

if( count( $modules ) )
{
	foreach( $modules as $module )
	{
		$IPBHTML .= <<<EOF
		<tr id='modules_{$module['sys_module_id']}' class='ipsControlRow isDraggable'>
			<td class='col_drag'>
				<div class='draghandle'>&nbsp;</div>
			</td>
			<td style='width: 3%' style='text-align: center'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/plugin.png' alt='' />
			</td>
			<td style='width: 70%'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=module_edit&amp;sys_module_id={$module['sys_module_id']}&amp;app_id={$application['app_id']}&amp;sys_module_admin=$sys_module_admin'><strong>{$module['sys_module_title']}</strong></a>
EOF;
					if( $module['sys_module_description'] )
					{
						$IPBHTML .= <<<EOF
							<br /><span class='desctext'>{$module['sys_module_description']}</span>
EOF;
					}
			
					$IPBHTML .= <<<EOF
			</td>
			<td style='width: 10%'>
				{$module['sys_module_version']}
			</td>
			<td style='width: 10%'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/{$module['_sys_module_visible']}' />
			</td>
			<td class='col_buttons'>
EOF;
				if ( $module['sys_module_protected'] != 1 OR IN_DEV )
				{
					$IPBHTML .= <<<EOF
					<ul class='ipsControlStrip'>
						<li class='i_edit'>
							<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=module_edit&amp;sys_module_id={$module['sys_module_id']}&amp;app_id={$application['app_id']}&amp;sys_module_admin=$sys_module_admin' title='{$this->lang->words['a_editmod']}'>{$this->lang->words['a_editmod']}</a>
						</li>
						<li class='i_delete'>
							<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=module_remove&amp;sys_module_id={$module['sys_module_id']}&amp;app_id={$application['app_id']}&amp;sys_module_admin=$sys_module_admin");' title='{$this->lang->words['a_removemod']}'>{$this->lang->words['a_removemod']}</a>
						</li>
					</ul>
EOF;
				}
				else
				{
					$IPBHTML .= <<<EOF
							<!--<li class='icon view'>{$this->lang->words['a_protectedmod']}</li>-->
EOF;
				}

				$IPBHTML .= <<<EOF
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
			<td class='no_messages'>
				{$this->lang->words['a_nomods']}
			</td>
		</tr>
EOF;
}

$IPBHTML .= <<<EOF
</table>
</div>
<br />
<script type='text/javascript'>
	jQ("#module_list").ipsSortable('table', { 
		url: "{$this->settings['base_url']}{$this->form_code_js}&do=module_manage_position&app_id={$application['app_id']}&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=module_import' enctype='multipart/form-data' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['a_importxml']}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['a_uploadxml']}</strong></td>
				<td class='field_field'><input class='textinput' type='file' size='30' name='FILE_UPLOAD' /><br /><span class='desctext'>{$this->lang->words['a_uploadxml_info']}</span></td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['a_filexml']}</strong></td>
				<td class='field_field'><input class='textinput' type='text' size='30' name='file_location' /><br /><span class='desctext'>{$this->lang->words['a_filexml_info']}</span></td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' class='button primary' value='{$this->lang->words['a_import']}' />
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit an application
 *
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		Application information
 * @param	string		Default TAB to open on page load
 * @return	string		HTML
 */
public function application_form( $form, $title, $formcode, $button, $application, $defaultTab='' ) {

$IPBHTML = "";
//--starthtml--//

$defaultTab = $defaultTab ? $defaultTab : 'information';

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['a_apps']}</h2>
</div>

<form id='mainform' action='{$this->settings['base_url']}{$this->form_code}do={$formcode}&amp;app_id={$application['app_id']}' method='POST'>
	<div class='acp-box'>
		<h3>{$title}</h3>
		
		<div id='tabstrip_appform' class='ipsTabBar'>
			<ul>
				<li id='tab_information'>{$this->lang->words['app_tab_info']}</li>
				<li id='tab_restrictions'>{$this->lang->words['app_tab_permissions']}</li>
				<li id='tab_caches'>{$this->lang->words['app_tab_gcaches']}</li>
			</ul>
		</div>
		
		<div id='tabstrip_appform_content' class='ipsTabBar_content'>
		
			<!-- INFORMATION -->
			<div id='tab_information_content'>
				<table class='ipsTable double_pad'>
					<tr>
				 		<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_apptitle']}</strong>
						</td>
				 		<td class='field_field'>
				 			{$form['app_title']}
				 		</td>
				 	</tr>
					<tr>
				 		<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_appptitle']}</strong>
						</td>
				 		<td class='field_field'>
				 			{$form['app_public_title']}<br />
							<span class='desctext'>{$this->lang->words['a_appptitle_info']}</span>
				 		</td>
				 	</tr>
					<tr>
				 		<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_appen']}</strong>
						</td>
				 		<td class='field_field'>
				 			{$form['app_enabled']}
				 		</td>
				 	</tr>
					<tr>
				 		<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_appdesc']}</strong>
						</td>
				 		<td class='field_field'>
				 			{$form['app_description']}
				 		</td>
				 	</tr>
					<tr>
				 		<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_appauthor']}</strong>
						</td>
				 		<td class='field_field'>
				 			{$form['app_author']}
				 		</td>
				 	</tr>
					<tr>
				 		<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_appver']}</strong>
						</td>
				 		<td class='field_field'>
				 			{$form['app_version']}
				 		</td>
				 	</tr>
					<tr>
				 		<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_appdir']}</strong>
						</td>
				 		<td class='field_field'>
				 			{$form['app_directory']}<br />
							<span class='desctext'>{$this->lang->words['a_appdir_info']}</span>
				 		</td>
				 	</tr>
EOF;
		if ( IN_DEV )
		{
			$IPBHTML .= <<<EOF
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_appprot']}</strong>
						</td>
				 		<td class='field_field'>
				 			{$form['app_protected']}<br />
							<span class='desctext'>{$this->lang->words['a_appprot_info']}</span>
				 		</td>
					</tr>
EOF;
		}

$IPBHTML .= <<<EOF
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_hooksite']}</strong>
						</td>
						<td class='field_field'>
							{$form['app_website']}<br />
							<span class='desctext'>{$this->lang->words['a_hooksite_info']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_hookurl']}</strong>
						</td>
						<td class='field_field'>
							{$form['app_update_check']}<br />
							<span class='desctext'>{$this->lang->words['a_appurl_info']}</span>
						</td>
					</tr>
				</table>
			</div>
			
			<!-- TAB RESTRICTIONS -->
			<div id='tab_restrictions_content'>
				<table class='ipsTable double_pad'>
					<tr>
				 		<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_appphide']}</strong>
						</td>
				 		<td class='field_field'>
				 			{$form['app_hide_tab']}<br />
							<span class='desctext'>{$this->lang->words['a_appphide_desc']}</span>
				 		</td>
				 	</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['app_form_tabgroups']}</strong>
						</td>
						<td class='field_field'>
							{$form['app_tab_groups']}<br />
							<span class='desctext'>{$this->lang->words['app_form_tabgroups_desc']}</span>
						</td>
					</tr>
				</table>
			</div>
			
			<!-- GLOBAL CACHES -->
			<div id='tab_caches_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['hook_form_caches']}</strong>
						</td>
						<td class='field_field'>
							{$form['app_global_caches']}<br />
							<span class='desctext'>{$this->lang->words['hook_form_caches_desc']}</span>
						</td>
					</tr>
				</table>
			</div>
			
			<script type='text/javascript'>
				jQ("#tabstrip_appform").ipsTabBar({ tabWrap: "#tabstrip_appform_content", defaultTab: "tab_{$defaultTab}" });
			</script>
		</div>
		<div class='acp-actionbar'>
				<input type='submit' value='{$button}' class='realbutton' />
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}



/**
 * List the applications
 *
 * @param	array 		Application
 * @param	array 		Uninstalled applications
 * @return	string		HTML
 */
public function applications_list( $applications, $uninstalled=array(), $message=NULL ) {

$IPBHTML = "";
//--starthtml--//

$canInstall = $this->registry->class_permissions->checkPermission( 'app_install' );

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['a_apps']}</h2>
	
	<div class='ipsActionBar clearfix'>
		<ul>
EOF;
	
	if( $canInstall )
	{
		$IPBHTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}module=applications&amp;section=applications&amp;do=application_add'><img src='{$this->settings['skin_acp_url']}/images/icons/application_add.png' alt='' /> {$this->lang->words['a_addnewapp']}</a>
			</li>
EOF;
	}
	
	$IPBHTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}module=applications&amp;section=applications&amp;do=module_recache_all'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='' /> {$this->lang->words['recache_link']}</a>
			</li>
EOF;
				
	if( $this->settings['search_method'] == 'sphinx' && $this->registry->class_permissions->checkPermission( 'build_sphinx' ) )
	{
		$IPBHTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='#' class='ipbmenu' id='sphinx_conf' title='{$this->lang->words['sphinx_menu_desc']}'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' /> {$this->lang->words['sphinx_menu_title']} <img src='{$this->settings['skin_acp_url']}/images/useropts_arrow.png' /></a>
				<ul class='ipbmenu_content' id='sphinx_conf_menucontent' style='display: none'>
					<li>
						<img src='{$this->settings['skin_acp_url']}/images/icons/page_white_code.png' alt='' /> <a href='{$this->settings['base_url']}module=applications&amp;section=applications&amp;do=sphinxBuildConf'>{$this->lang->words['sphinx_build_conf']}</a>
					</li>
					<li>
						<img src='{$this->settings['skin_acp_url']}/images/icons/page_white_code.png' alt='' /> <a href="{$this->settings['base_url']}module=applications&amp;section=applications&amp;do=sphinxBuildCron">{$this->lang->words['sphinx_build_cronjob']}</a>
					</li>
				</ul>
			</li>
EOF;
	}
	
		$IPBHTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=applications_overview&amp;checkUpdates=1'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' /> {$this->lang->words['hook_check_updates']}</a>
			</li>
			<li class='ipsActionButton inDev'>
				<a href='{$this->settings['base_url']}module=applications&amp;section=applications&amp;do=inDevExportApps'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_rotate_anticlockwise.png' alt='' /> {$this->lang->words['export_apps_xml']}</a>
			</li>
			<li class='ipsActionButton inDev'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=inDevExportAll'><img src='{$this->settings['skin_acp_url']}/images/icons/export.png' alt='' /> {$this->lang->words['export_modules_xml']}</a>
			</li>
			<li class='ipsActionButton inDev'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=inDevRebuildAll'><img src='{$this->settings['skin_acp_url']}/images/icons/export.png' alt='' /> {$this->lang->words['import_modules_xml']}</a>
			</li>
		</ul>
	</div>
</div>

EOF;

if ( $message )
{
	$IPBHTML .= "<div class='information-box'>{$message}</div><br />";
}

$IPBHTML .= <<<EOF
<!-- LEFT SIDEBAR -->
<div class='acp-box left' style='width: 69%'>
 	<h3>{$this->lang->words['a_installedapps']}</h3>
 	
	<div id='tabstrip_appsList' class='ipsTabBar with_left with_right'>
		<span class='tab_left'>&laquo;</span>
		<span class='tab_right'>&raquo;</span>
		<ul>
			<li id='tab_appsEnabled'>{$this->lang->words['a_apps_enabled']}</li>
			<li id='tab_appsDisabled'>{$this->lang->words['a_apps_disabled']}</li>
		</ul>
	</div>
	
	<div id='tabstrip_appsList_content' class='ipsTabBar_content'>
		
		<div id='tab_appsEnabled_content'>
			<table class='ipsTable' id='apps_enabled'>
				<tr>
					<th width='1%'>&nbsp;</th>
					<th width='1%'>&nbsp;</th>
					<th width='55%'>{$this->lang->words['a_hookapp']}</th>
					<th width='15%' class='center'>{$this->lang->words['app_tab_permissions']}</th>
					<th width='20%' class='center'>{$this->lang->words['a_lastupdated']}</th>
					<th class='col_buttons'>&nbsp;</th>
				</tr>
EOF;
	
	if ( count( $applications['enabled'] ) )
	{
		foreach( $applications['enabled'] as $app )
		{
			$img = is_file( IPSLib::getAppDir( $app['app_directory'] ) . '/skin_cp/appIcon.png' ) ? $this->settings['base_acp_url'] . '/' . IPSLib::getAppFolder( $app['app_directory'] ) . '/' . $app['app_directory'] . '/skin_cp/appIcon.png' : "{$this->settings['skin_acp_url']}/images/applications/{$app['app_directory']}.png";
			
			# IPS app?
			$app['titlePrefix'] = in_array( $app['app_location'], array( 'root', 'ips' ) ) ? "<span class='ipsBadge badge_purple'>{$this->lang->words['gl_ipsapp']}</span>&nbsp;&nbsp;" : '';
			
			# Sort title
			$app['app_title'] = IN_DEV ? "<a href='{$this->settings['base_url']}{$this->form_code}do=application_edit&amp;app_id={$app['app_id']}'>{$app['app_title']}</a>" : $app['app_title'];
			$app['app_description'] = $app['app_description'] ? "<br /><span class='desctext'>{$app['app_description']}</span>" : '';
			
			# Tab Restrictions
			$app['_tab_restricted'] = ( $app['app_hide_tab'] || IPSText::cleanPermString($app['app_tab_groups']) ) ? "<a href='{$this->settings['base_url']}{$this->form_code}do=application_edit&amp;app_id={$app['app_id']}&amp;_tab=restrictions' title='{$this->lang->words['a_edit_restrictions']}'><img src='{$this->settings['skin_acp_url']}/images/icons/tick.png' alt='' /></a>" : '';
			
			# Update available?
			if( $app['app_update_available'][0] )
			{
				$_update = "<span class='ipsBadge badge_purple'>{$this->lang->words['hook_update_available']}</span>";
				
				if ( !empty($app['app_update_available'][1]) )
				{
					$_update = "<a href='{$app['app_update_available'][1]}' target='_blank'>{$_update}</a>";
				}
				elseif( $app['app_website'] )
				{
					$_update = "<a href='{$app['app_website']}' target='_blank'>{$_update}</a>";
				}
				
				$app['_updated'] = $_update;
			}
			elseif ( isset( $app['_long_version'] ) && $app['_long_version'] > $app['_long_current'] )
			{
				$app['_updated'] = "<a href='{$this->settings['board_url']}/" . CP_DIRECTORY . "/upgrade/' class='ipsBadge badge_green'>{$this->lang->words['a_upgradeavail']}</a>";
			}
			else
			{
				$app['_updated'] = "<span class='desctext'>{$this->lang->words['a_oh_kay']}</span>";
			}
			
if ( $app['app_directory'] == 'core' )
{
			$IPBHTML .= <<<EOF
			<tr class='ipsControlRow'>
				<td>&nbsp;</td>
EOF;
}
else
{
			$IPBHTML .= <<<EOF
			<tr class='ipsControlRow isDraggable' id='apps_{$app['app_id']}'>
				<td class='col_drag'>
					<span class='draghandle'>&nbsp;</span>
				</td>
EOF;
}

$IPBHTML .= <<<EOF
				<td>
					<img src='{$img}' alt='' />
				</td>
				<td>
					<strong><span class='larger_text'>{$app['titlePrefix']}{$app['app_title']}</span></strong><span class='desctext'>&nbsp;&nbsp;v{$app['_human_current']}</span>
					{$app['app_description']}
				</td>
				<td class='center'>{$app['_tab_restricted']}</td>
				<td class='center'>{$app['_updated']}</td>
				<td class='col_buttons'>
					<ul class='ipsControlStrip'>
EOF;

if ( !in_array( $app['app_directory'], array( 'core', 'forums', 'members' ) ) )
{
	$IPBHTML .= <<<EOF
						<li class='i_disable'><a href='{$this->settings['base_url']}{$this->form_code}do=toggle_app&amp;app_id={$app['app_id']}' title='{$this->lang->words['a_disable_app']}'>{$this->lang->words['a_disable_app']}</a></li>
EOF;
}

$IPBHTML .= <<<EOF
						<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}do=application_edit&amp;app_id={$app['app_id']}' title='{$this->lang->words['a_editapp_details']}'>{$this->lang->words['a_editapp_details']}</a></li>
						<li class='ipsControlStrip_more ipbmenu' id='menu_{$app['app_id']}'><a href='#'>{$this->lang->words['more']}</a></li>
					</ul>
					
					<ul class='acp-menu' id='menu_{$app['app_id']}_menucontent' style='display: none'>
						<li class='icon view'><a href='{$this->settings['base_url']}{$this->form_code}do=application_details&amp;app_id={$app['app_id']}'>{$this->lang->words['a_viewhook']}</a></li>
						<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=modules_overview&amp;app_id={$app['app_id']}&amp;sys_module_admin=1'>{$this->lang->words['a_manageadmin']}</a></li>
						<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=modules_overview&amp;app_id={$app['app_id']}&amp;sys_module_admin=0'>{$this->lang->words['a_managepublic']}</a></li>
EOF;

if ( $app['app_protected'] != 1 OR IN_DEV )
{
	$IPBHTML .= <<<EOF
						<li class='icon delete'><a href='{$this->settings['base_url']}{$this->form_code}do=application_remove_splash&amp;app_id={$app['app_id']}'>{$this->lang->words['a_removeapp']}</a></li>
EOF;
}

$IPBHTML .= <<<EOF
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
					<td colspan='4' class='no_messages'>{$this->lang->words['a_noapps_enabled']}</td>
				</tr>
EOF;
	}
	
$IPBHTML .= <<<EOF
			</table>
		</div>
		<script type='text/javascript'>
			jQ("#apps_enabled").ipsSortable( 'table', { 
				url: "{$this->settings['base_url']}{$this->form_code_js}do=application_manage_position&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ),
				serializeOptions: { key: 'apps[]' }
			} );
		</script>
		
		<div id='tab_appsDisabled_content'>
			<table class='ipsTable'>
				<tr>
					<th width='1%'>&nbsp;</th>
					<th width='1%'>&nbsp;</th>
					<th width='55%'>{$this->lang->words['a_hookapp']}</th>
					<th width='15%' class='center'>{$this->lang->words['app_tab_permissions']}</th>
					<th width='20%' class='center'>{$this->lang->words['a_lastupdated']}</th>
					<th class='col_buttons'>&nbsp;</th>
				</tr>
EOF;
	
	if ( count( $applications['disabled'] ) )
	{
		foreach( $applications['disabled'] as $app )
		{
			$img = is_file( IPSLib::getAppDir( $app['app_directory'] ) . '/skin_cp/appIcon.png' ) ? $this->settings['base_acp_url'] . '/' . IPSLib::getAppFolder( $app['app_directory'] ) . '/' . $app['app_directory'] . '/skin_cp/appIcon.png' : "{$this->settings['skin_acp_url']}/images/applications/{$app['app_directory']}.png";
			
			# IPS app?
			$app['titlePrefix'] = in_array( $app['app_location'], array( 'root', 'ips' ) ) ? "<span class='ipsBadge badge_purple'>{$this->lang->words['gl_ipsapp']}</span>&nbsp;&nbsp;" : '';
			
			# Sort title
			$app['app_title'] = IN_DEV ? "<a href='{$this->settings['base_url']}{$this->form_code}do=application_edit&amp;app_id={$app['app_id']}'>{$app['app_title']}</a>" : $app['app_title'];
			$app['app_description'] = $app['app_description'] ? "<br /><span class='desctext'>{$app['app_description']}</span>" : '';
			
			# Tab Restrictions
			$app['_tab_restricted'] = ( $app['app_hide_tab'] || IPSText::cleanPermString($app['app_tab_groups']) ) ? "<a href='{$this->settings['base_url']}{$this->form_code}do=application_edit&amp;app_id={$app['app_id']}&amp;_tab=restrictions' title='{$this->lang->words['a_edit_restrictions']}'><img src='{$this->settings['skin_acp_url']}/images/icons/tick.png' alt='' /></a>" : '';
			
			# Update available?
			if( $app['app_update_available'][0] )
			{
				$_update = "<span class='ipsBadge badge_purple'>{$this->lang->words['hook_update_available']}</span>";
				
				if ( !empty($app['app_update_available'][1]) )
				{
					$_update = "<a href='{$app['app_update_available'][1]}' target='_blank'>{$_update}</a>";
				}
				elseif( $app['app_website'] )
				{
					$_update = "<a href='{$app['app_website']}' target='_blank'>{$_update}</a>";
				}
				
				$app['_updated'] = $_update;
			}
			elseif ( isset( $app['_long_version'] ) && $app['_long_version'] > $app['_long_current'] )
			{
				$app['_updated'] = "<a href='{$this->settings['board_url']}/" . CP_DIRECTORY . "/upgrade/' class='ipsBadge badge_green'>{$this->lang->words['a_upgradeavail']}</a>";
			}
			else
			{
				$app['_updated'] = "<span class='desctext'>{$this->lang->words['a_oh_kay']}</span>";
			}
			
			$IPBHTML .= <<<EOF
			<tr class='ipsControlRow'>
				<td>&nbsp;</td>
				<td>
					<img src='{$img}' alt='' />
				</td>
				<td>
					<strong><span class='larger_text'>{$app['titlePrefix']}{$app['app_title']}</span></strong><span class='desctext'>&nbsp;&nbsp;v{$app['_human_current']}</span>
					{$app['app_description']}
				</td>
				<td class='center'>{$app['_tab_restricted']}</td>
				<td class='center'>{$app['_updated']}</td>
				<td class='col_buttons'>
					<ul class='ipsControlStrip'>
						<li class='i_add'><a href='{$this->settings['base_url']}{$this->form_code}do=toggle_app&amp;app_id={$app['app_id']}' title='{$this->lang->words['a_enable_app']}'>{$this->lang->words['a_enable_app']}</a></li>
						<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}do=application_edit&amp;app_id={$app['app_id']}' title='{$this->lang->words['a_editapp_details']}'>{$this->lang->words['a_editapp_details']}</a></li>
						<li class='ipsControlStrip_more ipbmenu' id='menu_{$app['app_id']}'><a href='#'>{$this->lang->words['more']}</a></li>
					</ul>
					
					<ul class='acp-menu' id='menu_{$app['app_id']}_menucontent' style='display: none'>
						<li class='icon view'><a href='{$this->settings['base_url']}{$this->form_code}do=application_details&amp;app_id={$app['app_id']}'>{$this->lang->words['a_viewhook']}</a></li>
						<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=modules_overview&amp;app_id={$app['app_id']}&amp;sys_module_admin=1'>{$this->lang->words['a_manageadmin']}</a></li>
						<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=modules_overview&amp;app_id={$app['app_id']}&amp;sys_module_admin=0'>{$this->lang->words['a_managepublic']}</a></li>
EOF;

if ( $app['app_protected'] != 1 OR IN_DEV )
{
	$IPBHTML .= <<<EOF
						<li class='icon delete'><a href='{$this->settings['base_url']}{$this->form_code}do=application_remove_splash&amp;app_id={$app['app_id']}'>{$this->lang->words['a_removeapp']}</a></li>
EOF;
}

$IPBHTML .= <<<EOF
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
					<td colspan='4' class='no_messages'>{$this->lang->words['a_noapps_disabled']}</td>
				</tr>
EOF;
	}
	
$IPBHTML .= <<<EOF
			</table>
		</div>
		
	</div>
</div>
<script type='text/javascript'>
	jQ("#tabstrip_appsList").ipsTabBar({ tabWrap: "#tabstrip_appsList_content" });
</script>

<!-- RIGHT SIDEBAR -->
<div class='right' style='width: 30%'>
EOF;

if ( is_array( $uninstalled ) AND count( $uninstalled ) )
{
$IPBHTML .= <<<EOF
	<div class='acp-box'>
		<h3>{$this->lang->words['a_unapps']}</h3>
	 	<table class='ipsTable'>
EOF;

foreach( $uninstalled as $app )
{
	if ( strstr( $app['path'], 'applications_addon/ips' ) )
	{
		$app['_location']   = 'ips';
		$img = "<img src='{$this->settings['skin_acp_url']}/images/icons/medal.png' title='{$this->lang->words['a_officialapp']}' />";
	}
	else if ( strstr( $app['path'], 'applications_addon/other' ) )
	{
		$app['_location']   = 'other';
		$img = '';
	}
	else
	{
		$app['_location']   = 'root';
		$img = "<img src='{$this->settings['skin_acp_url']}/images/icons/medal.png' title='{$this->lang->words['a_officialapp']}' />";
	}
	
	if ( $app['okToGo'] )
	{
		$warning = '';
		$install = <<<EOF
		<a href='{$this->settings['base_url']}module=applications&amp;section=setup&amp;do=install&amp;app_directory={$app['directory']}&amp;app_location={$app['_location']}' class='ipsBadge badge_purple'>{$this->lang->words['a_install']}</a>
EOF;
	}
	else
	{
		$install = "<span class='ipsBadge badge_grey'>{$this->lang->words['a_cannotinstall']}</span>";
		$warning = <<<EOF
				<br /><span style='color: red'>{$this->lang->words['a_cantinstall_info']}</span>
EOF;
	}

$install = $canInstall ? $install : '';

$IPBHTML .= <<<EOF
	<tr>
		<td style='width: 16px; padding: 10px 0 10px 6px; text-align: center; vertical-align: top'>
			{$img}
		</td>
		<td>
			<strong>{$app['title']}</strong><br />
			<span class='desctext'>{$app['author']}</span>
			{$warning}
		</td>
		<td style='width: 20%'>
			{$install}
		</td>
	</tr>
EOF;
}

$IPBHTML .= <<<EOF
	 </table>
	</div>
EOF;
}

if( 
	( !IPSLib::appIsInstalled('nexus', false) AND !isset($uninstalled['nexus']) ) OR 
	( !IPSLib::appIsInstalled('blog', false) AND !isset($uninstalled['blog']) ) OR 
	( !IPSLib::appIsInstalled('gallery', false) AND !isset($uninstalled['gallery']) ) OR 
	( !IPSLib::appIsInstalled('ccs', false) AND !isset($uninstalled['ccs']) ) OR 
	( !IPSLib::appIsInstalled('downloads', false) AND !isset($uninstalled['downloads']) ) OR 
	!$this->settings['ips_cp_purchase'] )
{
	$IPBHTML .= <<<EOF
	<br />
	<div class='acp-box'>
		<table class='ipsTable'>
EOF;
	foreach( array('nexus','blog','gallery','ccs','downloads') as $__app )
	{
		if( !IPSLib::appIsInstalled( $__app, false ) AND !isset($uninstalled[ $__app ]) )
		{
			$_app_name = $__app == 'ccs' ? 'Content' : ucfirst($__app);

			$IPBHTML .= <<<EOF
			<tr>
				<td style='width: 16px;'>
					<img src='{$this->settings['skin_acp_url']}/images/icon_components/{$__app}.png' alt='' />
				</td>
				<td>
					<a href='{$this->settings['base_url']}module=applications&amp;section={$__app}'>IP.{$_app_name}</a>
				</td>
			</tr>
EOF;
		}
	}

	if( !$this->settings['ips_cp_purchase'] )
	{
		$IPBHTML .= <<<EOF
			<tr>
				<td style='width: 16px'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/package.png' alt='' />
				</td>
				<td>
					<a href='{$this->settings['base_url']}module=applications&amp;section=copyright'>Copyright Removal</a>
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
EOF;

//--endhtml--//
return $IPBHTML;
}
/**
 * Show the application details
 *
 * @param	array		$appData		Application data
 * @param	array		$history		Upgrade data
 * @param	array		$hooks			Hooks data
 * @return	@e string	HTML
 */
public function application_details( $appData, $history, $hooks=array() )
{
$HTML = '';

$defaultTab = 'tab_appHistory'; #Default tab is history, if we have hooks later switch to them

/* Menu */
$HTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['a_app_details']}</h2>
EOF;

if ( !in_array( $appData['app_directory'], array( 'core', 'forums', 'members' ) ) )
{
	$HTML .= <<<EOF
	<div class='ipsActionBar clearfix'>
		<ul>
EOF;
	
	if( $appData['app_enabled'] )
	{
		$HTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=toggle_app&amp;app_id={$appData['app_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='' /> {$this->lang->words['a_disable_app']}</a>
			</li>
EOF;
	}
	else
	{
		$HTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=toggle_app&amp;app_id={$appData['app_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['a_enable_app']}</a>
			</li>
EOF;
	}
	
	$HTML .= <<<EOF
		</ul>
	</div>
EOF;
}

$HTML .= <<<EOF
</div>

<div class='acp-box'>
	<h3>{$appData['app_title']}</h3>
	<table class='ipsTable'>
EOF;

	if( $appData['app_description'] )
	{
		$HTML .= <<<EOF
		<tr>
			<th colspan='2'>{$appData['app_description']}</th>
		</tr>
EOF;
	}
	

$appData['app_public_title'] = $appData['app_public_title'] ? $appData['app_public_title'] : '--';

$HTML .= <<<EOF
		<tr>
			<td width='40%'>
				<strong class='title'>{$this->lang->words['a_appptitle']}</strong> 
			</td>
			<td width='60%'>
				{$appData['app_public_title']}
			</td>
 		</tr>
		<tr>
			<td width='40%'>
				<strong class='title'>{$this->lang->words['a_appver']}</strong> 
			</td>
			<td width='60%'>
				{$appData['app_version']} ({$appData['app_long_version']})
			</td>
 		</tr>
		<tr>
			<td>
				<strong class='title'>{$this->lang->words['a_appauthor']}</strong> 
			</td>
			<td>
				{$appData['app_author']}
			</td>
 		</tr>
EOF;
	
	if( $appData['app_website'] )
	{
		$HTML .= <<<EOF
		<tr>
			<td>
				<strong class='title'>{$this->lang->words['a_hooksite']}</strong> 
			</td>
			<td>
				<a href='{$appData['app_website']}' target='_blank'>{$appData['app_website']}</a>
			</td>
 		</tr>
EOF;
	}
	
	/* Update available? */
	if( $appData['app_update_available'][0] )
	{
		$appData['_updated'] = "<span class='ipsBadge badge_purple'>{$this->lang->words['hook_update_available']}</span>";
		
		if ( !empty($appData['app_update_available'][1]) )
		{
			$appData['_updated'] = "<a href='{$appData['app_update_available'][1]}' target='_blank'>{$appData['_updated']}</a>";
		}
		elseif( $appData['app_website'] )
		{
			$appData['_updated'] = "<a href='{$appData['app_website']}' target='_blank'>{$appData['_updated']}</a>";
		}
	}
	elseif ( isset( $appData['_long_version'] ) && $appData['_long_version'] > $appData['_long_current'] )
	{
		$appData['_updated'] = "<a href='{$this->settings['board_url']}/" . CP_DIRECTORY . "/upgrade/' class='ipsBadge badge_green'>{$this->lang->words['a_upgradeavail']}</a>";
	}
	
	$HTML .= <<<EOF
		<tr>
			<td>
				<strong class='title'>{$this->lang->words['a_installed_on']}</strong> 
			</td>
			<td>
				{$appData['_installed']}
			</td>
 		</tr>
		<tr>
			<td>
				<strong class='title'>{$this->lang->words['a_lastupdated']}</strong> 
			</td>
			<td>
				{$appData['_updated']}
			</td>
 		</tr>
EOF;
	
	if ( $appData['app_global_caches'] )
	{
		$cachesCount = count( explode(',', $appData['app_global_caches']) );
		
		$HTML .= <<<EOF
		<tr>
			<td>
				<strong class='title'>{$this->lang->words['hook_globalcaches']}</strong> 
			</td>
			<td>
				{$cachesCount}&nbsp;&nbsp;( {$appData['app_global_caches']} )
			</td>
 		</tr>
EOF;
	}
	
	$HTML .= <<<EOF
	</table>
</div>
<br />
<div class='acp-box'>
 	<h3>{$this->lang->words['a_other_details']}</h3>
 	
	<div id='tabstrip_appDetails' class='ipsTabBar with_left with_right'>
		<span class='tab_left'>&laquo;</span>
		<span class='tab_right'>&raquo;</span>
		<ul>
			<li id='tab_appHooks'>{$this->lang->words['a_related_hooks']}</li>
			<li id='tab_appHistory'>{$this->lang->words['a_upgrade_history']}</li>
		</ul>
	</div>
	
	<div id='tabstrip_appDetails_content' class='ipsTabBar_content'>
		
		<div id='tab_appHooks_content'>
			<table class='ipsTable'>
EOF;
		
		if( count( $hooks ) )
		{
			$defaultTab   = 'tab_appHooks';
			$warningBadge = "<span class='ipsBadge badge_red'>{$this->lang->words['hook_badge_warning']}</span>&nbsp;";
			
			foreach( $hooks as $r )
			{
				$statusBadge = $r['hook_enabled'] ? "<span class='ipsBadge badge_green'>{$this->lang->words['a_apps_enabled']}</span>" : "<span class='ipsBadge badge_red'>{$this->lang->words['a_apps_disabled']}</span>&nbsp;";
				$_warning    = count($r['_require_errors']) ? "<a href='{$this->settings['base_url']}module=applications&amp;section=hooks&amp;do=check_requirements&amp;id={$r['hook_id']}'>{$warningBadge}</a>" : '';
				
				$HTML .= <<<EOF
				<tr class='ipsControlRow'>
					<td width='1%' class='center'>
						{$statusBadge}
					</td>
					<td>
						{$_warning}<strong><span class='larger_text'>{$r['hook_name']}</span></strong><span class='desctext'>&nbsp;&nbsp;v{$r['hook_version_human']}</span>
					</td>
					<td>{$r['hook_author']}</td>
					<td class='col_buttons'>
						<ul class='ipsControlStrip'>
							<li class='i_view'><a href='{$this->settings['base_url']}module=applications&amp;section=hooks&amp;do=view_details&amp;id={$r['hook_id']}' title='{$this->lang->words['a_viewhook']}'>{$this->lang->words['a_viewhook']}</a></li>
						</ul>
					</td>
				</tr>
EOF;
			}
		}
		else
		{
			$HTML .= <<<EOF
				<tr>
					<td class='no_messages'><em>{$this->lang->words['a_no_related_hooks']}</em></td>
				</tr>
EOF;
		}
		
		$HTML .= <<<EOF
			</table>
		</div>
		
		<div id='tab_appHistory_content'>
			<table class='ipsTable'>
EOF;

foreach( $history as $upgradeRow )
{
	$upgradeRow['_date'] = $this->lang->getDate( $upgradeRow['upgrade_date'], 'SHORT' );
	
	$HTML .= <<<EOF
				<tr>
					<td class='field_title'>{$upgradeRow['_date']}</td>
					<td class='field_field'><strong class='title'>{$upgradeRow['upgrade_version_human']} ({$upgradeRow['upgrade_version_id']})</strong></td>
					<td class='field_field'>{$upgradeRow['upgrade_notes']}</td>
				</tr>
EOF;
}
	
$HTML .= <<<EOF
			</table>
		</div>
		
	</div>
</div>
<script type='text/javascript'>
	jQ("#tabstrip_appDetails").ipsTabBar({ tabWrap: "#tabstrip_appDetails_content", defaultTab: "{$defaultTab}" });
</script>
EOF;

return $HTML;
}

/**
 * Splash screen to remove an application
 *
 * @param	array 		Application
 * @return	string		HTML
 */
public function application_remove_splash( $data )
{
return <<<EOF
<div class='acp-box alt'>
	<h3>{$this->lang->words['a_remove']} {$data['app_title']} {$this->lang->words['a_app']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td><strong class='title'>{$this->lang->words['a_currentver']}</strong></td>
			<td>{$data['app_version']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['a_author']}</strong></td>
			<td>{$data['app_author']}</td>
		</tr>
	 	<tr>
	 		<th colspan='2'>{$this->lang->words['a_warning']}</th>
	 	</tr>
	 	<tr>
	 		<td colspan='2'>{$this->lang->words['a_warning_info']}</td>
	 	</tr>
	 </table>
	 <div class='acp-actionbar'>
	 	<a class='button redbutton primary' href='{$this->settings['base_url']}{$this->form_code}&do=application_remove&app_id={$data['app_id']}'><strong>{$this->lang->words['a_clickremove']}</strong></a>
	 </div>
</div>
EOF;
}

/**
 * List the hooks
 *
 * @param	array		$hooksFound		A list of the current enbled/disabled hooks
 * @param	integer		$warnings		Number of enabled hooks with warnings
 * @param	string		$message		Message to display
 * @return	@e string	HTML
 */
public function hooksOverview( $hooksFound, $warnings, $message )
{
$HTML = "";

/* Sort out some data */
$warningBadge= "<span class='ipsBadge badge_red'>{$this->lang->words['hook_badge_warning']}</span>&nbsp;";
$updateBadge = "<span class='ipsBadge badge_purple'>{$this->lang->words['hook_update_available']}</span>";
$cache		 = $this->cache->getCache('disabledHooksCache');


$HTML .= <<<EOF
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.hooksList.js'></script>
<script type='text/javascript'>
	ipb.templates['install_new_hook'] = new Template("<div class='acp-box'><h3 class='ipsBlock_title'>{$this->lang->words['a_installhook']}</h3><form action='{$this->settings['base_url']}{$this->form_code}do=install_hook' method='post' enctype='multipart/form-data'><table class='ipsTable double_pad'><tr><td class='field_title'><strong class='title'>{$this->lang->words['a_hookxml']}</strong></td><td class='field_field'><input type='file' name='FILE_UPLOAD' /></td></tr></table><div class='acp-actionbar'><input type='submit' value='{$this->lang->words['a_install']}' class='button primary' /></div></form></div>");
</script>

<div class='section_title'>
	<h2>{$this->lang->words['a_hooks']}</h2>
	
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton' id='install_new_hook'>
				<a href='javascript:void(0);'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['a_installhook']}</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=create_hook'><img src='{$this->settings['skin_acp_url']}/images/icons/application_add.png' alt='' /> {$this->lang->words['create_hook_link']}</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=reimport_apps'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='' /> {$this->lang->words['rebuild_app_hooks']}</a>
			</li>
EOF;

if( is_array( $cache ) && count( $cache ) )
{
	$HTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=reenable_all_hooks'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> {$this->lang->words['hook_reenable_all']}</a>
			</li>
EOF;
}
else
{
	$HTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=disable_all_hooks'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> {$this->lang->words['hook_disable_all']}</a>
			</li>
EOF;
}

$HTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=hooks_overview&amp;checkUpdates=1'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' /> {$this->lang->words['hook_check_updates']}</a>
			</li>
			<li class='ipsActionButton inDev'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=removeDeadCaches'><img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='' /> {$this->lang->words['remove_dead_caches']}</a>
			</li>
		</ul>
	</div>
</div>
EOF;

if ( $message )
{
	$HTML .= "<div class='information-box'>{$message}</div><br />";
}

/* Got warnings? */
if( $warnings > 0 )
{
	$_text = ( $warnings == 1 ) ? $this->lang->words['hook_overview_warning'] : sprintf( $this->lang->words['hook_overview_warnings'], $warnings );
	
	$HTML .= '<br />' . $this->registry->output->global_template->warning_box( $_text ) . '<br />';
}

$HTML .= <<<EOF
<div class='acp-box'>
	<h3>{$this->lang->words['a_installedhooks']}</h3>
	
	<div id='tabstrip_hookOverview' class='ipsTabBar with_left with_right'>
		<span class='tab_left'>&laquo;</span>
		<span class='tab_right'>&raquo;</span>
		<ul>
			<li id='tab_HookInstalled'>{$this->lang->words['a_apps_enabled']}</li>
			<li id='tab_HookDisabled'>{$this->lang->words['a_apps_disabled']}</li>
		</ul>
	</div>
	
	<div id='tabstrip_hookOverview_content' class='ipsTabBar_content'>
		
		<div id='tab_HookInstalled_content'>
			<table class='ipsTable' id='hooks_enabled'>
				<tr>
					<th class='col_drag'>&nbsp;</th>
					<th width='55%'>{$this->lang->words['a_hook']}</th>
					<th width='20%'>{$this->lang->words['a_author']}</th>
					<th width='20%'>{$this->lang->words['a_lastupdated']}</th>
					<th class="col_buttons">&nbsp;</th>
				</tr>
EOF;
		
		if( count( $hooksFound['installed'] ) )
		{
			foreach( $hooksFound['installed'] as $r )
			{
				$HTML .= <<<EOF
				<tr class='ipsControlRow isDraggable' id='hooks_{$r['hook_id']}'>
					<td class='col_drag'>
						<span class='draghandle'>&nbsp;</span>
					</td>
					<td>
EOF;
					
					$_warning = count($r['_require_errors']) ? "<a href='{$this->settings['base_url']}{$this->form_code}do=check_requirements&amp;id={$r['hook_id']}'>{$warningBadge}</a>" : '';
					
					$r['hook_name'] = IN_DEV ? "<a href='{$this->settings['base_url']}{$this->form_code}do=edit_hook&amp;id={$r['hook_id']}'>{$r['hook_name']}</a>" : $r['hook_name'];
					
					$HTML .= "{$_warning}<strong><span class='larger_text'>{$r['hook_name']}</span></strong><span class='desctext'>&nbsp;&nbsp;v{$r['hook_version_human']}</span>";
					
					if( $r['hook_desc'] )
					{
						$HTML .= "<br /><span class='desctext'>{$r['hook_desc']}</span>";
					}
					
					/* Update available? */
					if( $r['hook_update_available'][0] )
					{
						$r['_updated'] = $updateBadge;
						
						if ( !empty($r['hook_update_available'][1]) )
						{
							$r['_updated'] = "<a href='{$r['hook_update_available'][1]}' target='_blank'>{$r['_updated']}</a>";
						}
						elseif( $r['hook_website'] )
						{
							$r['_updated'] = "<a href='{$r['hook_website']}' target='_blank'>{$r['_updated']}</a>";
						}
					}
					
					$HTML .= <<<EOF
					</td>
					<td>
						{$r['hook_author']}
					</td>
					<td>
						{$r['_updated']}
					</td>
					<td class='col_buttons'>
						<ul class='ipsControlStrip'>
							<li class='i_disable'><a href='{$this->settings['base_url']}{$this->form_code}do=disable_hook&amp;id={$r['hook_id']}' title='{$this->lang->words['a_disablehook']}'>{$this->lang->words['a_disablehook']}</a></li>
							<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}do=edit_hook&amp;id={$r['hook_id']}' title='{$this->lang->words['a_edithook']}'>{$this->lang->words['a_edithook']}</a></li>
							<li class='ipsControlStrip_more ipbmenu' id='menu_{$r['hook_id']}'><a href='#'>&nbsp;</a></li>
						</ul>
						
						<ul class='acp-menu' id='menu_{$r['hook_id']}_menucontent' style='display: none'>
EOF;
	
	if( is_array($r['_has_setting_links']) && count($r['_has_setting_links']) )
	{
		foreach( $r['_has_setting_links'] as $sid => $sname )
		{
			$HTML .= <<<EOF
							<li class='icon edit'><a href='{$this->settings['base_url']}module=settings&&amp;section=settings&amp;do=setting_view&amp;conf_group={$sid}'>{$this->lang->words['h_edit_settings']}: {$sname}</a></li>
EOF;
		}
	}
	
	$HTML .= <<<EOF
							<li class='icon view'><a href='{$this->settings['base_url']}{$this->form_code}do=view_details&amp;id={$r['hook_id']}'>{$this->lang->words['a_viewhook']}</a></li>
							<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}do=check_requirements&amp;id={$r['hook_id']}'>{$this->lang->words['a_checkhook']}</a></li>
EOF;
	
	if( IN_DEV )
	{
		$HTML .= <<<EOF
							<li class='icon export'><a href='{$this->settings['base_url']}{$this->form_code}do=export_hook&amp;id={$r['hook_id']}'>{$this->lang->words['a_exporthook']}</a></li>
EOF;
	}
	
	$HTML .= <<<EOF
							<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=uninstall_hook&amp;id={$r['hook_id']}");'>{$this->lang->words['a_uninstallhook']}</a></li>
						</ul>
					</td>
				</tr>
EOF;
			}
		}
		else
		{
			$HTML .= <<<EOF
				<tr>
					<td colspan='6'>{$this->lang->words['a_nohooks']}</td>
				</tr>
EOF;
		}
		
		$HTML .= <<<EOF
			</table>
		</div>
		
		<script type='text/javascript'>
			jQ("#hooks_enabled").ipsSortable('table', { 
				url: "{$this->settings['base_url']}{$this->form_code_js}do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ),
				serializeOptions: { key: 'hooks[]' }
			} );
		</script>
		
		<div id='tab_HookDisabled_content'>
			<table class='ipsTable'>
				<tr>
					<th class='col_drag'>&nbsp;</th>
					<th width="55%">{$this->lang->words['a_hook']}</th>
					<th width="20%">{$this->lang->words['a_author']}</th>
					<th width="20%">{$this->lang->words['a_uptodate']}</th>
					<th class="col_buttons">&nbsp;</th>
				</tr>
EOF;
		
		if( count( $hooksFound['uninstalled'] ) )
		{
			foreach( $hooksFound['uninstalled'] as $r )
			{
				$HTML .= <<<EOF
				<tr class='ipsControlRow'>
					<td>&nbsp;</td>
					<td>
EOF;
					
					$_warning = count($r['_require_errors']) ? "<a href='{$this->settings['base_url']}{$this->form_code}do=check_requirements&amp;id={$r['hook_id']}'>{$warningBadge}</a>" : '';
					
					$r['hook_name'] = IN_DEV ? "<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit_hook&amp;id={$r['hook_id']}'>{$r['hook_name']}</a>" : $r['hook_name'];
					
					$HTML .= "{$_warning}<strong><span class='larger_text'>{$r['hook_name']}</span></strong><span class='desctext'>&nbsp;&nbsp;v{$r['hook_version_human']}</span>";
					
					if( $r['hook_desc'] )
					{
						$HTML .= <<<EOF
							<br /><span class='desctext'>{$r['hook_desc']}</span>
EOF;
					}
						
					$HTML .= <<<EOF
					</td>
					<td>{$r['hook_author']}</td>
EOF;
					
					/* Update available? */
					if( $r['hook_update_available'][0] )
					{
						$r['_updated'] = $updateBadge;
						
						if ( !empty($r['hook_update_available'][1]) )
						{
							$r['_updated'] = "<a href='{$r['hook_update_available'][1]}' target='_blank'>{$r['_updated']}</a>";
						}
						elseif( $r['hook_website'] )
						{
							$r['_updated'] = "<a href='{$r['hook_website']}' target='_blank'>{$r['_updated']}</a>";
						}
					}
					
					$HTML .= <<<EOF
					<td>{$r['_updated']}</td>
					<td class='col_buttons'>
						<ul class='ipsControlStrip'>
							<li class='i_add'><a href='{$this->settings['base_url']}{$this->form_code}do=enable_hook&amp;id={$r['hook_id']}' title='{$this->lang->words['a_enablehook']}' >{$this->lang->words['a_enablehook']}</a></li>
							<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}do=edit_hook&amp;id={$r['hook_id']}' title='{$this->lang->words['a_edithook']}'>{$this->lang->words['a_edithook']}</a></li>
							<li class='ipsControlStrip_more ipbmenu' id='menu_{$r['hook_id']}'><a href='#'>&nbsp;</a></li>
						</ul>
						
						<ul class='acp-menu' id='menu_{$r['hook_id']}_menucontent'>
EOF;
	
	if( is_array($r['_has_setting_links']) && count($r['_has_setting_links']) )
	{
		foreach( $r['_has_setting_links'] as $sid => $sname )
		{
			$HTML .= <<<EOF
							<li class='icon edit'><a href='{$this->settings['base_url']}module=settings&&amp;section=settings&amp;do=setting_view&amp;conf_group={$sid}'>{$this->lang->words['h_edit_settings']}: {$sname}</a></li>
EOF;
		}
	}
	
	$HTML .= <<<EOF
							<li class='icon view'><a href='{$this->settings['base_url']}{$this->form_code}do=view_details&amp;id={$r['hook_id']}'>{$this->lang->words['a_viewhook']}</a></li>
							<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}do=check_requirements&amp;id={$r['hook_id']}'>{$this->lang->words['a_checkhook']}</a>
EOF;

					if( IN_DEV )
					{
						$HTML .= <<<EOF
							<li class='icon export'><a href='{$this->settings['base_url']}{$this->form_code}do=export_hook&amp;id={$r['hook_id']}'>{$this->lang->words['a_exporthook']}</a></li>
EOF;
					}
					
					$HTML .= <<<EOF
							<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=uninstall_hook&amp;id={$r['hook_id']}");'>{$this->lang->words['a_uninstallhook']}</a></li>
						</ul>
					</td>
				</tr>
EOF;
			}
		}
		else
		{
			$HTML .= <<<EOF
				<tr>
					<td colspan='5' class='no_messages'>{$this->lang->words['a_nodishooks']}</td>
				</tr>
EOF;
		}
		
		$HTML .= <<<EOF
			</table>
		</div>
		<script type='text/javascript'>
			jQ("#tabstrip_hookOverview").ipsTabBar({ tabWrap: "#tabstrip_hookOverview_content", defaultTab: "tab_HookInstalled" });
		</script>
	</div>
</div>
EOF;

return $HTML;
}


/**
 * Form to add/edit a hook
 *
 * @param	array 		Form elements
 * @param	string		Action code
 * @param	array 		Hook data
 * @param	array 		Files in this hook
 * @return	string		HTML
 */
public function hookForm( $form, $action, $hookData, $files=array(), $requirements=array() )
{

/* Hook types */
$hookTypes = array( array( 0, $this->lang->words['a_selectone'] ),
				    array( 'commandHooks', $this->lang->words['a_aoverloader'] ),
				    array( 'skinHooks', $this->lang->words['a_soverloader'] ),
				    array( 'templateHooks', $this->lang->words['a_templatehook'] ),
				    array( 'dataHooks', $this->lang->words['a_datahook'] ),
				    array( 'libraryHooks', $this->lang->words['a_libraryhook'] )
				   );

/* Get apps to use for library hooks */
$appsArray = array( array( 0, $this->lang->words['a_selectone'] ) );

foreach( ipsRegistry::$applications as $dir => $appdata )
{
	$appsArray[] = array( $dir, $appdata['app_title'] );
}

$_removeInDev = IN_DEV ? '' : 'display: none;';

$HTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['a_setuphook']}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}ipb3Hooks.js'></script>
<form name='theForm' method='post' action='{$this->settings['base_url']}{$this->form_code}' id='mainform'>
	<input type='hidden' name='do' value='{$action}' />
	<input type='hidden' name='hook_id' value='{$hookData['hook_id']}' />
	<input type='hidden' name='secure_key' value='{$this->member->form_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['a_setuphook']}</h3>
		
		<div id='tabstrip_hookform' class='ipsTabBar with_left with_right'>
			<span class='tab_left'>&laquo;</span>
			<span class='tab_right'>&raquo;</span>
			<ul>
				<li id='tab_HookInfo'>{$this->lang->words['hook_form_info']}</li>
				<li id='tab_HookReq'>{$this->lang->words['a_hookrequirements']}</li>
				<li id='tab_HookCaches'>{$this->lang->words['hook_globalcaches']}</li>
				<li id='tab_HookFiles'>{$this->lang->words['a_hookfiles']}</li>
			</ul>
		</div>
		
		<div id='tabstrip_hookform_content' class='ipsTabBar_content'>
			
			<!-- INFORMATION -->
			<div id='tab_HookInfo_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<th colspan='2'>&nbsp;</th>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['hook_form_title']}</strong>
						</td>
						<td class='field_field'>
							{$form['hook_name']}<br />
							<span class='desctext'>{$this->lang->words['hook_form_title_help']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['hook_form_desc']}</strong>
						</td>
						<td class='field_field'>
							{$form['hook_desc']}<br />
							<span class='desctext'>{$this->lang->words['hook_form_desc_help']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['hook_form_key']}</strong>
						</td>
						<td class='field_field'>
							{$form['hook_key']}<br />
							<span class='desctext'>{$this->lang->words['hook_form_key_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['hook_form_version']}</strong>
						</td>
						<td class='field_field'>
							{$form['hook_version_human']}<br />
							<span class='desctext'>{$this->lang->words['hook_form_version_help']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_hookversion']}</strong>
						</td>
						<td class='field_field'>
							{$form['hook_version_long']}<br />
							<span class='desctext'>{$this->lang->words['a_hookversion_info']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_hookauthor']}</strong>
						</td>
						<td class='field_field'>
							{$form['hook_author']}<br />
							<span class='desctext'>{$this->lang->words['a_hookauthor_info']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_hookemail']}</strong>
						</td>
						<td class='field_field'>
							{$form['hook_email']}<br />
							<span class='desctext'>{$this->lang->words['a_hookemail_info']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_hooksite']}</strong>
						</td>
						<td class='field_field'>
							{$form['hook_website']}<br />
							<span class='desctext'>{$this->lang->words['a_hooksite_info']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_hookurl']}</strong>
						</td>
						<td class='field_field'>
							{$form['hook_update_check']}<br />
							<span class='desctext'>{$this->lang->words['a_hookurl_info']}</span>
						</td>
					</tr>
		 		</table>
			</div>
			
			<!-- REQUIREMENTS -->
			<div id='tab_HookReq_content'>
				<table class='ipsTable double_pad' id='RequirementsContainer'>
					<tr>
						<th colspan='2'>{$this->lang->words['a_phpver']}</th>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['a_phpver']}</strong>
						</td>
						<td class='field_field'>
							{$this->lang->words['a_min']}: {$form['hook_php_version_min']}<br /><br />
							{$this->lang->words['a_max']}: {$form['hook_php_version_max']}<br />
							<span class='desctext'>{$this->lang->words['a_enterzero']}  {$this->lang->words['a_phpminmax']}</span>
						</td>
					</tr>
EOF;
			
			$latestReqIndex = 0;
						
			if( is_array( $requirements ) && count( $requirements ) )
			{
				foreach( $requirements as $appKey => $require )
				{
					if ( is_array( $require['_versions'] ) )
					{
						$_requiredApp = $this->registry->output->formDropdown( "requireApp[{$latestReqIndex}]", $appsArray, $appKey, '', "onchange='getAppVersions({$latestReqIndex})'" );/*noLibHook*/
						$_minVersions = $this->registry->output->formDropdown( "minVersion[{$latestReqIndex}]", $require['_versions'], $require['min_version'] );
						$_maxVersions = $this->registry->output->formDropdown( "maxVersion[{$latestReqIndex}]", $require['_versions'], $require['max_version'] );
						
						$HTML .= <<<EOF
						<tr id='requirementTitleRow_{$latestReqIndex}'>
							<th colspan='2'>{$this->lang->words['hook_require_apptitle']}<span class='ipsBadge badge_red right' style='cursor: pointer;{$_removeInDev}' onclick='removeRequirement({$latestReqIndex})'>{$this->lang->words['hook_remove_requirement']}</span></th>
						</tr>
						<tr id='requirementRow_{$latestReqIndex}'>
							<td class='field_title'>
								<strong class='title'>{$this->lang->words['hook_require_apptitle']}</strong>
							</td>
							<td class='field_field'>
								{$_requiredApp}<br /><br />
								<span id='requirementRow_{$latestReqIndex}_versions'>
									{$this->lang->words['a_min']}: {$_minVersions}<br /><br />
									{$this->lang->words['a_max']}: {$_maxVersions}
								</span>
							</td>
						</tr>
EOF;
					
						$latestReqIndex++;
					}
				}
			}
			
			$HTML .= <<<EOF
		 		</table>
		 		
		 		<div class='acp-actionbar'>
					<input type='button' value='{$this->lang->words['hook_require_addanother']}' onclick='addAnotherRequirement()' class='button primary' />
				</div>
			</div>
			
			<!-- GLOBAL CACHES -->
			<div id='tab_HookCaches_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<th colspan='2'>{$this->lang->words['hook_globalcaches']}</th>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['hook_form_caches']}</strong>
						</td>
						<td class='field_field'>
							{$form['hook_global_caches']}<br />
							<span class='desctext'>{$this->lang->words['hook_form_caches_desc']}</span>
						</td>
					</tr>
		 		</table>
			</div>
			
			<!-- FILES -->
			<div id='tab_HookFiles_content'>
				<table class='ipsTable double_pad' id='fileTableContainer'>
EOF;
			
			$latestIndex = 0;
			
			if( count( $files ) )
			{
				foreach( $files as $index => $file )
				{
					$latestIndex = $index > $latestIndex ? $index : $latestIndex;
					
					$HTML .= <<<EOF
					<tr id='fileRow_{$index}'>
						<td style='margin:0px; padding:0px;'>
							<table class='ipsTable' id='fileTable_{$index}'>
								<tr>
									<th colspan='2'>{$this->lang->words['a_hookfile']} #{$index}<span class='ipsBadge badge_red right' style='cursor: pointer;{$_removeInDev}' onclick='removeFile({$index})'>{$this->lang->words['hook_remove_file']}</span></th>
								</tr>
EOF;

if( IN_DEV )
{
$HTML .= <<<EOF
								<tr>
									<td class='field_title'>
										<strong class='title'>{$this->lang->words['a_filenamedir']}</strong>
									</td>
									<td class='field_field'>
										<input type='text' name='file[{$index}]' value='{$file['hook_file_real']}' size='50' class='input_text' />
									</td>
								</tr>
								<tr>
									<td class='field_title'>
										<strong class='title'>{$this->lang->words['a_fileclassname']}</strong>
									</td>
									<td class='field_field'>
										<input type='text' name='hook_classname[{$index}]' value='{$file['hook_classname']}' size='50' class='input_text' />
									</td>
								</tr>
EOF;
}
else
{
$HTML .= <<<EOF
								<tr style='display:none'>
									<td colspan='2'>
										<input type='hidden' name='file[{$index}]' value='{$file['hook_file_real']}' />
										<input type='hidden' name='hook_classname[{$index}]' value='{$file['hook_classname']}' />
									</td>
								</tr>
EOF;
}

$_hookTypes = $this->registry->output->formDropdown( "hook_type[{$index}]", $hookTypes, $file['hook_type'], "hook_type[{$index}]", "onchange='selectHookType({$index});'" );

						$HTML .= <<<EOF
								<tr>
									<td class='field_title'>
										<strong class='title'>{$this->lang->words['a_filehooktype']}</strong>
									</td>
									<td class='field_field'>
										{$_hookTypes}
									</td>
								</tr>
EOF;
					if( $file['hook_type'] == 'dataHooks' )
					{
						$HTML .= <<<EOF
								<tr id='tr_dataLocation[{$index}]'>
									<td class='field_title'>
										<strong class='title'>{$this->lang->words['a_datalocation']}</strong>
									</td>
									<td class='field_field'>
										{$file['_dataLocationDropdown']}
									</td>
								</tr>
EOF;
					}
					else if( $file['hook_type'] == 'libraryHooks' )
					{
						$_applications = $this->registry->output->formDropdown( "libApplication[{$index}]", $appsArray, $file['hook_data']['libApplication'] );
						
						$HTML .= <<<EOF
								<tr id='tr_classToOverload[{$index}]'>
									<td class='field_title'>
										<strong class='title'>{$this->lang->words['a_classextend']}</strong>
									</td>
									<td class='field_field'>
										<input type='text' name='classToOverload[{$index}]' value='{$file['hook_data']['classToOverload']}' size='50' class='input_text' />
									</td>
								</tr>
								<tr id='tr_libApplication[{$index}]'>
									<td class='field_title'>
										<strong class='title'>{$this->lang->words['a_hookapp']}</strong>
									</td>
									<td class='field_field'>
										{$_applications}
									</td>
								</tr>
EOF;
					}
					else if( $file['hook_type'] != 'templateHooks' )
					{
						$HTML .= <<<EOF
								<tr id='tr_classToOverload[{$index}]'>
									<td class='field_title'>
										<strong class='title'>{$this->lang->words['a_classextend']}</strong>
									</td>
									<td class='field_field'>
										<input type='text' name='classToOverload[{$index}]' value='{$file['hook_data']['classToOverload']}' size='50' class='input_text' />
									</td>
								</tr>
EOF;
					}
					else
					{
				 		$HTML .= <<<EOF
								<tr id='tr_skinGroup[{$index}]'>
									<td class='field_title'>
										<strong class='title'>{$this->lang->words['a_skingroup']}</strong>
									</td>
									<td class='field_field'>
										{$file['_skinDropdown']}
									</td>
								</tr>
								<tr id='tr_skinFunction[{$index}]'>
									<td class='field_title'>
										<strong class='title'>{$this->lang->words['a_skinfunc']}</strong>
									</td>
									<td class='field_field'>
										{$file['_templateDropdown']}
									</td>
								</tr>
								<tr id='tr_type[{$index}]'>
									<td class='field_title'>
										<strong class='title'>{$this->lang->words['a_typeoftemp']}</strong>
									</td>
									<td class='field_field'>
										{$file['_hookTypeDropdown']}
									</td>
								</tr>
								<tr id='tr_id[{$index}]'>
									<td class='field_title'>
										<strong class='title'>{$this->lang->words['a_hookid']}</strong>
									</td>
									<td class='field_field'>
										{$file['_hookIdsDropdown']}
									</td>
								</tr>
								<tr id='tr_position[{$index}]'>
									<td class='field_title'>
										<strong class='title'>{$this->lang->words['a_hookloc']}</strong>
									</td>
									<td class='field_field'>
										{$file['_hookEPDropdown']}
									</td>
								</tr>
EOF;
					}
				
				$HTML .= <<<EOF
							</table>
						</td>
					</tr>
EOF;
				}
			}
			$HTML .= <<<EOF
				</table>
				
				<div class='acp-actionbar'>
					<input type='button' value='{$this->lang->words['a_addanother']}' onclick='addAnotherFile()' class='button primary' />
				</div>
			</div>
			
			<script type='text/javascript'>
				elementIndex = {$latestIndex};
				requireIndex = {$latestReqIndex};
				
				jQ("#tabstrip_hookform").ipsTabBar({ tabWrap: "#tabstrip_hookform_content" });
				
				// Requirements templates
				ipb.templates['new_app_require']      = new Template("<tr id='requirementTitleRow_#{index}'><th colspan='2'>{$this->lang->words['hook_require_apptitle']}<span class='ipsBadge badge_red right' style='cursor: pointer;{$_removeInDev}' onclick='removeRequirement(#{index})'>{$this->lang->words['hook_remove_requirement']}</span></th></tr><tr id='requirementRow_#{index}'><td class='field_title'><strong class='title'>{$this->lang->words['hook_require_apptitle']}</strong></td><td class='field_field'>#{dropdown}<br /><br /><span id='requirementRow_#{index}_versions'></span></td></tr>");
				
				// Hook templates
				ipb.templates['new_hook_file']        = new Template("<tr id='fileRow_#{index}'><td style='margin:0px; padding:0px;'><table class='ipsTable' id='fileTable_#{index}'><tr><th colspan='2'>{$this->lang->words['a_hookfile']} ##{index}<span class='ipsBadge badge_red right' style='cursor: pointer;{$_removeInDev}' onclick='removeFile(#{index})'>{$this->lang->words['hook_remove_file']}</span></th></tr><tr><td class='field_title'><strong class='title'>{$this->lang->words['a_filenamedir']}</strong></td><td class='field_field'><input type='text' name='file[#{index}]' value='' size='50' class='input_text' /></td></tr><tr><td class='field_title'><strong class='title'>{$this->lang->words['a_fileclassname']}</strong></td><td class='field_field'><input type='text' name='hook_classname[#{index}]' value='' size='50' class='input_text' /></td></tr><tr><td class='field_title'><strong class='title'>{$this->lang->words['a_filehooktype']}</strong></td><td class='field_field'><select name='hook_type[#{index}]' id='hook_type[#{index}]' onchange='selectHookType(#{index});'><option value='0'>{$this->lang->words['a_selectone']}</option><option value='commandHooks'>{$this->lang->words['a_aoverloader']}</option><option value='skinHooks'>{$this->lang->words['a_soverloader']}</option><option value='templateHooks'>{$this->lang->words['a_templatehook']}</option><option value='dataHooks'>{$this->lang->words['a_datahook']}</option><option value='libraryHooks'>{$this->lang->words['a_libraryhook']}</option></select></td></tr></table></td></tr>");
				ipb.templates['hook_skinGroup']       = new Template("<tr id='tr_skinGroup[#{index}]'><td class='field_title'><strong class='title'>{$this->lang->words['a_skingroup']}</strong></td><td class='field_field'>#{dropdown}</td></tr>");
				ipb.templates['hook_classToOverload'] = new Template("<tr id='tr_classToOverload[#{index}]'><td class='field_title'><strong class='title'>{$this->lang->words['a_classextend']}</strong></td><td class='field_field'><input type='text' name='classToOverload[#{index}]' value='' size='50' class='input_text' /></td></tr>");
				ipb.templates['hook_dataLocation']    = new Template("<tr id='tr_dataLocation[#{index}]'><td class='field_title'><strong class='title'>{$this->lang->words['a_datalocation']}</strong></td><td class='field_field'>#{dropdown}</td></tr>");
				ipb.templates['hook_libApplication']  = new Template("<tr id='tr_libApplication[#{index}]'><td class='field_title'><strong class='title'>{$this->lang->words['a_hookapp']}</strong></td><td class='field_field'>#{dropdown}</td></tr>");
				ipb.templates['hook_skinFunction']    = new Template("<tr id='tr_skinFunction[#{index}]'><td class='field_title'><strong class='title'>{$this->lang->words['a_skinfunc']}</strong></td><td class='field_field'>#{dropdown}</td></tr>");
				ipb.templates['hook_pointTypes']      = new Template("<tr id='tr_type[#{index}]'><td class='field_title'><strong class='title'>{$this->lang->words['a_typeoftemp']}</strong></td><td class='field_field'>{$form['jsDataTypes']}</td></tr>");
				ipb.templates['hook_pointIds']        = new Template("<tr id='tr_id[#{index}]'><td class='field_title'><strong class='title'>{$this->lang->words['a_hookid']}</strong></td><td class='field_field'>#{dropdown}</td></tr>");
				ipb.templates['hook_pointLocation']   = new Template("<tr id='tr_position[#{index}]'><td class='field_title'><strong class='title'>{$this->lang->words['a_hookloc']}</strong></td><td class='field_field'><select name='position[#{index}]' id='position[#{index}]'>#{hookPoints}</select></td></tr>");
			</script>
		</div>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['hook_form_button']}' class='button primary' />
		</div>
	</div>
</form>
EOF;

return $HTML;
}

/**
 * Show the hook details
 *
 * @param	array 		Hook data
 * @param	array 		Files in this hook
 * @return	string		HTML
 */
public function hookDetails( $hookData, $files=array() )
{
$HTML = '';

/* Menu */
$HTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['hook_view_details']}</h2>
	
	<div class='ipsActionBar clearfix'>
		<ul>
EOF;

if( $hookData['hook_enabled'] )
{
$HTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=disable_hook&amp;id={$hookData['hook_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='' /> {$this->lang->words['a_disablehook']}</a>
			</li>
EOF;
}
else
{
$HTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=enable_hook&amp;id={$hookData['hook_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['a_enablehook']}</a>
			</li>
EOF;
}
$HTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=check_requirements&amp;id={$hookData['hook_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> {$this->lang->words['hook_view_requirements']}</a>
			</li>
		</ul>
	</div>
</div>
<br />

<div class='acp-box'>
	<h3>{$hookData['hook_name']}</h3>
	<table class='ipsTable'>
EOF;

	if( $hookData['hook_desc'] )
	{
		$HTML .= <<<EOF
		<tr>
			<th colspan='2'>{$hookData['hook_desc']}</th>
		</tr>
EOF;
	}
	
$HTML .= <<<EOF
		<tr>
			<td width='40%'>
				<strong class='title'>{$this->lang->words['a_hookver']}</strong> 
			</td>
			<td width='60%'>
				{$hookData['hook_version_human']}
			</td>
 		</tr>
		<tr>
			<td>
				<strong class='title'>{$this->lang->words['a_hookauthor']}</strong> 
			</td>
			<td>
				{$hookData['hook_author']}
			</td>
 		</tr>
EOF;

	if( $hookData['hook_email'] )
	{
		$HTML .= <<<EOF
		<tr>
			<td>
				<strong class='title'>{$this->lang->words['a_hookemail']}</strong> 
			</td>
			<td>
				<a href='mailto:{$hookData['hook_email']}'>{$hookData['hook_email']}</a>
			</td>
 		</tr>
EOF;
	}

	if( $hookData['hook_website'] )
	{
		$HTML .= <<<EOF
		<tr>
			<td>
				<strong class='title'>{$this->lang->words['a_authorsite']}</strong> 
			</td>
			<td>
				<a href='{$hookData['hook_website']}' target='_blank'>{$hookData['hook_website']}</a>
			</td>
 		</tr>
EOF;
	}

	/* Update available? */
	if( $hookData['hook_update_available'][0] )
	{
		$hookData['_updated'] = "<span class='ipsBadge badge_purple'>{$this->lang->words['hook_update_available']}</span>";
		
		if ( !empty($hookData['hook_update_available'][1]) )
		{
			$hookData['_updated'] = "<a href='{$hookData['hook_update_available'][1]}' target='_blank'>{$hookData['_updated']}</a>";
		}
		elseif( $hookData['hook_website'] )
		{
			$hookData['_updated'] = "<a href='{$hookData['hook_website']}' target='_blank'>{$hookData['_updated']}</a>";
		}
	}
	
	$HTML .= <<<EOF
		<tr>
			<td>
				<strong class='title'>{$this->lang->words['a_lastupdated']}</strong> 
			</td>
			<td>
				{$hookData['_updated']}
			</td>
 		</tr>
		<tr>
			<td>
				<strong class='title'>{$this->lang->words['a_hookrequirements']}</strong> 
			</td>
			<td>
EOF;
	
	$HTML .= ( is_array($hookData['_require_errors']) && count($hookData['_require_errors']) ) ? "<a href='{$this->settings['base_url']}{$this->form_code}do=check_requirements&amp;id={$hookData['hook_id']}'><span class='ipsBadge badge_red'>{$this->lang->words['hook_require_error']}</span></a>" : "<span class='ipsBadge badge_green'>{$this->lang->words['hook_require_ok']}</span>";
	
	$HTML .= <<<EOF
			</td>
 		</tr>
EOF;
	
	if ( $hookData['hook_global_caches'] )
	{
		$cachesCount = count( explode(',', $hookData['hook_global_caches']) );
		
		$HTML .= <<<EOF
		<tr>
			<td>
				<strong class='title'>{$this->lang->words['hook_globalcaches']}</strong> 
			</td>
			<td>
				{$cachesCount}&nbsp;&nbsp;( {$hookData['hook_global_caches']} )
			</td>
 		</tr>
EOF;
	}
	
	$HTML .= <<<EOF
	</table>
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['a_fileuses']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='20%'>{$this->lang->words['a_realfile']}</th>
			<th width='20%'>{$this->lang->words['a_storedfile']}</th>
			<th width='10%'>{$this->lang->words['a_filehooktype']}</th>
			<th width='50%'>{$this->lang->words['a_wherehook']}</th>
		</tr>
EOF;

		foreach( $files as $index => $data )
		{
			$showsAt	= "";

			if( $data['hook_type'] == 'templateHooks' )
			{
				$showsAt = $this->lang->words['a_showsin'] . $data['hook_data']['skinGroup'] . ' -&gt; ' . $data['hook_data']['skinFunction'] . ' ';

				if( $data['hook_data']['type'] == 'if' )
				{
					switch( $data['hook_data']['position'] )
					{
						case 'pre.startif':
							$showsAt .= $this->lang->words['a_prestartif'];
						break;

						case 'post.startif':
							$showsAt .= $this->lang->words['a_poststartif'];
						break;

						case 'pre.else':
							$showsAt .= $this->lang->words['a_preelse'];
						break;

						case 'post.else':
							$showsAt .= $this->lang->words['a_postelse'];
						break;

						case 'pre.endif':
							$showsAt .= $this->lang->words['a_preendif'];
						break;

						case 'post.endif':
							$showsAt .= $this->lang->words['a_postendif'];
						break;
					}
				}
				else
				{
					switch( $data['hook_data']['position'] )
					{
						case 'outer.pre':
							$showsAt .= $this->lang->words['a_outerpre'];
						break;

						case 'inner.pre':
							$showsAt .= $this->lang->words['a_innerpre'];
						break;

						case 'inner.post':
							$showsAt .= $this->lang->words['a_innerpost'];
						break;

						case 'outer.post':
							$showsAt .= $this->lang->words['a_outerpost'];
						break;
					}
				}

				$showsAt .= $this->lang->words['a_labeled'] . $data['hook_data']['id'];
			}
			elseif ( $data['hook_type'] == 'libraryHooks' )
			{
				$showsAt = sprintf( $this->lang->words['a_libraryhookfor'], $data['hook_data']['classToOverload'], $data['hook_data']['libApplication'] );
			}
			elseif ( $data['hook_type'] == 'dataHooks' )
			{
				$showsAt = $this->lang->words['a_datahookfor'] . $data['hook_data']['dataLocation'];
			}
			else
			{
				$showsAt = $this->lang->words['a_willoverload'] . $data['hook_data']['classToOverload'];
			}

			switch( $data['hook_type'] )
			{
				case 'templateHooks':
					$hookType = $this->lang->words['a_templatehook'];
					break;
				case 'commandHooks':
					$hookType = $this->lang->words['a_aoverloader'];
					break;
				case 'skinHooks':
					$hookType = $this->lang->words['a_soverloader'];
					break;
				case 'libraryHooks':
					$hookType = $this->lang->words['a_libraryhook'];
					break;
				case 'dataHooks':
					$hookType = $this->lang->words['a_datahook'];
					break;
			}

			$HTML .= <<<EOF
		<tr>
			<td>{$data['hook_file_real']}</td>
			<td>{$data['hook_file_stored']}</td>
			<td>{$hookType}</td>
			<td>{$showsAt}</td>
 		</tr>
EOF;
		}
		$HTML .= <<<EOF
 	</table>
</div>
EOF;

return $HTML;
}

/**
 * Show the hook requirements
 *
 * @param	array 		Hook data
 * @param	array		Array of errors found
 * @param	array		Array of the app versions
 * @return	string		HTML
 */
public function hookRequirements( $hookData, $errors, $versions=array() )
{
$HTML = '';

/* Let's sort out the badges... */
$forceEnable = empty($this->request['fromInstall']) ? '' : '&amp;skipRequirements=1';
$_goodBadge  = "<span class='ipsBadge badge_green'>{$this->lang->words['hook_require_ok']}</span>";
$_errorBadge = "<span class='ipsBadge badge_red'>{$this->lang->words['hook_require_error']}</span>";

/* Menu */
$HTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['a_hookrequirements']}</h2>
	
	<div class='ipsActionBar clearfix'>
		<ul>
EOF;

if( $hookData['hook_enabled'] )
{
$HTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=disable_hook&amp;id={$hookData['hook_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='' /> {$this->lang->words['a_disablehook']}</a>
			</li>
EOF;
}
else
{
$HTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=enable_hook&amp;id={$hookData['hook_id']}{$forceEnable}'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['a_enablehook']}</a>
			</li>
EOF;
}
$HTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=view_details&amp;id={$hookData['hook_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='' /> {$this->lang->words['a_viewhook']}</a>
			</li>
		</ul>
	</div>
</div>
<br />
EOF;

/* Check if we got any requirements... */
if ( !$hookData['hook_requirements']['hook_php_version_min'] && !$hookData['hook_requirements']['hook_php_version_min'] && ( ( !is_array($hookData['hook_requirements']['required_applications']) || !count($hookData['hook_requirements']['required_applications']) ) ) )
{
$HTML .= <<<EOF
<div class="information-box">
	{$this->lang->words['hook_require_noreqs']}
</div>
EOF;

return $HTML;
}


/* Got errors? */
if( count($errors) )
{
	$HTML .= $this->registry->output->global_template->warning_box( $this->lang->words['a_noyoucant'], empty($this->request['fromInstall']) ? '' : '<br />'.$this->lang->words['hook_require_skip_reqs'] ) . '<br />';
	
	/* Replacement time? */
	if ( isset($errors['php_min']) )
	{
		$hookData['hook_requirements']['hook_php_version_min'] = $errors['php_min'];
	}
	if ( isset($errors['php_max']) )
	{
		$hookData['hook_requirements']['hook_php_version_max'] = $errors['php_max'];
	}
}

/* Still here? We do have some requirements then! */
$HTML .= <<<EOF
<div class='acp-box'>
	<h3>{$hookData['hook_name']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='21%'>{$this->lang->words['a_phpver']}</th>
			<th width='5%'>&nbsp;</th>
			<th width='70%'>&nbsp;</th>
		</tr>
		<tr>
			<td>
				<strong class='title'>{$this->lang->words['hook_require_current']}</strong>
			</td>
			<td>
				&nbsp;
			</td>
			<td>
EOF;

$HTML .= PHP_VERSION;

$HTML .= <<<EOF
			</td>
		</tr>
EOF;

if ( $hookData['hook_requirements']['hook_php_version_min'] )
{

	$HTML .= <<<EOF
		<tr>
			<td>
				<strong class='title'>{$this->lang->words['hook_require_min']}</strong>
			</td>
			<td class='center'>
EOF;
	
	$HTML .= isset($errors['php_min']) ? $_errorBadge : $_goodBadge;
	
	$HTML .= <<<EOF
			</td>
			<td>
				{$hookData['hook_requirements']['hook_php_version_min']}
			</td>
		</tr>
EOF;
}

if ( $hookData['hook_requirements']['hook_php_version_max'] )
{

	$HTML .= <<<EOF
		<tr>
			<td>
				<strong class='title'>{$this->lang->words['hook_require_max']}</strong>
			</td>
			<td class='center'>
EOF;
	
	$HTML .= isset($errors['php_max']) ? $_errorBadge : $_goodBadge;
	
	$HTML .= <<<EOF
			</td>
			<td>
				{$hookData['hook_requirements']['hook_php_version_max']}
			</td>
		</tr>
EOF;
}
	
	if ( is_array($hookData['hook_requirements']['required_applications']) && count($hookData['hook_requirements']['required_applications']) )
	{
		foreach( $hookData['hook_requirements']['required_applications'] as $appKey => $appData )
		{
			$_appName = ipsRegistry::$applications[ $appKey ]['app_title'];
			
			$HTML .= <<<EOF
					<tr>
						<th colspan='3'>{$this->lang->words['hook_require_apptitle']}: {$_appName}</th>
					</tr>
					<tr>
						<td>
							<strong class='title'>{$this->lang->words['hook_require_current']}</strong>
						</td>
						<td class='center'>
EOF;
				
				$HTML .= isset($errors[ $appKey.'_app' ]) ? $_errorBadge : $_goodBadge;
				
				$HTML .= <<<EOF
						</td>
						<td>
EOF;
			
			$HTML .= isset($errors[ $appKey.'_app' ]) ? $errors[ $appKey.'_app' ] : ipsRegistry::$applications[ $appKey ]['app_version'];
			
			$HTML .= <<<EOF
						</td>
					</tr>
EOF;
			/* Got a min version? */
			if ( ! isset($errors[ $appKey.'_app' ]) && $appData['min_version'] )
			{
			
				$HTML .= <<<EOF
					<tr>
						<td>
							<strong class='title'>{$this->lang->words['hook_require_min']}</strong>
						</td>
						<td class='center'>
EOF;
				
				$appData['min_version'] = isset($errors[ $appKey.'_min' ]) ? $errors[ $appKey.'_min' ] : $appData['min_version'];
				
				$HTML .= isset($errors[ $appKey.'_min' ]) ? $_errorBadge : $_goodBadge;
				
				$HTML .= <<<EOF
						</td>
						<td>
EOF;
				
				$HTML .= isset($versions[ $appKey ][ $appData['min_version'] ]) ? $versions[ $appKey ][ $appData['min_version'] ] : $appData['min_version'];
				
				$HTML .= <<<EOF
						</td>
					</tr>
EOF;
}
			/* Got a max version? */
			if ( ! isset($errors[ $appKey.'_app' ]) && $appData['max_version'] )
			{
			
				$HTML .= <<<EOF
					<tr>
						<td>
							<strong class='title'>{$this->lang->words['hook_require_max']}</strong>
						</td>
						<td class='center'>
EOF;
				
				$appData['max_version'] = isset($errors[ $appKey.'_max' ]) ? $errors[ $appKey.'_max' ] : $appData['max_version'];
				
				$HTML .= isset($errors[ $appKey.'_max' ]) ? $_errorBadge : $_goodBadge;
				
				$HTML .= <<<EOF
						</td>
						<td>
EOF;
				
				$HTML .= isset($versions[ $appKey ][ $appData['max_version'] ]) ? $versions[ $appKey ][ $appData['max_version'] ] : $appData['max_version'];
				
				$HTML .= <<<EOF
						</td>
					</tr>
EOF;
			}
		}
	}
	
	$HTML .= <<<EOF
 	</table>
</div>

EOF;

return $HTML;
}

/**
 * Page to export a hook
 *
 * @param	array 		Hook data
 * @return	string		HTML
 */
public function hooksExport( $hookData )
{

$hookData['hook_extra_data']['display']['settings']		= $hookData['hook_extra_data']['display']['settings'] ? $hookData['hook_extra_data']['display']['settings'] : $this->lang->words['a_nosettings'];
$hookData['hook_extra_data']['display']['modules']		= $hookData['hook_extra_data']['display']['modules'] ? $hookData['hook_extra_data']['display']['modules'] : $this->lang->words['a_nomodules'];
$hookData['hook_extra_data']['display']['help']			= $hookData['hook_extra_data']['display']['help'] ? $hookData['hook_extra_data']['display']['help'] : $this->lang->words['a_nohelp'];
$hookData['hook_extra_data']['display']['acphelp']		= $hookData['hook_extra_data']['display']['acphelp'] ? $hookData['hook_extra_data']['display']['acphelp'] : $this->lang->words['a_noacp'];
$hookData['hook_extra_data']['display']['tasks']		= $hookData['hook_extra_data']['display']['tasks'] ? $hookData['hook_extra_data']['display']['tasks'] : $this->lang->words['a_notasks'];
$hookData['hook_extra_data']['display']['database']		= $hookData['hook_extra_data']['display']['database'] ? $hookData['hook_extra_data']['display']['database'] : $this->lang->words['a_nodbchanges'];
$hookData['hook_extra_data']['display']['custom']		= $hookData['hook_extra_data']['display']['custom'] ? $hookData['hook_extra_data']['display']['custom'] : $this->lang->words['a_noinun'];

$hookData['hook_extra_data']['display']['language']		= $hookData['hook_extra_data']['display']['language'] ? $hookData['hook_extra_data']['display']['language'] : $this->lang->words['a_nolang'];
$hookData['hook_extra_data']['display']['templates']	= $hookData['hook_extra_data']['display']['templates'] ? $hookData['hook_extra_data']['display']['templates'] : $this->lang->words['a_noskin'];
$hookData['hook_extra_data']['display']['css']			= $hookData['hook_extra_data']['display']['css'] ? $hookData['hook_extra_data']['display']['css'] : $this->lang->words['a_nocss'];
$hookData['hook_extra_data']['display']['replacements']	= $hookData['hook_extra_data']['display']['replacements'] ? $hookData['hook_extra_data']['display']['replacements'] : $this->lang->words['a_noreplacements'];

$HTML .= <<<EOF

<div class='section_title'>
	<h2>{$this->lang->words['hook_h2_exporting']} {$hookData['hook_name']}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_main_url']}acp.inlineforms.js'></script>
<script type='text/javascript' src='{$this->settings['js_main_url']}acp.hooks.js'></script>

<script type='text/javascript'>
	acp.hooks.hookID = {$hookData['hook_id']};
</script>

<form name='theForm' method='post' action='{$this->settings['base_url']}{$this->form_code}' id='mainform'>
	<input type='hidden' name='do' value='do_export_hook' />
	<input type='hidden' name='id' value='{$hookData['hook_id']}' />
	<input type='hidden' name='secure_key' value='{$this->member->form_hash}' />

	<div class='acp-box'>
		<h3>{$this->lang->words['a_exportsettings']}</h3>
		
		<table class='ipsTable'>
			<tr>
				<th>{$this->lang->words['a_settings_info']}</th>
			</tr>
			<tr>
				<td>
					<p id='MF__settings'>{$hookData['hook_extra_data']['display']['settings']}</p>
					<div id='MF__settings_popup' style='text-align:center;'><span class='button'>{$this->lang->words['a_addsettings']}</span></div>
				</td>
 			</tr>
 		</table>
		<script type='text/javascript'>
			$('MF__settings_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__settings', "{$this->lang->words['a_addsettings']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=settings&amp;id={$hookData['hook_id']}" ) );
		</script>
 	</div>
 	<br />

	<div class='acp-box'>
		<h3>{$this->lang->words['a_exportlang']}</h3>
		
		<table class='ipsTable'>
			<tr>
				<th>{$this->lang->words['a_lang_info']}</th>
			</tr>
			<tr>
				<td valign='top'>
					<p id='MF__language'>{$hookData['hook_extra_data']['display']['language']}</p>
					<div id='MF__language_popup' style='text-align:center;'><span class='button'>{$this->lang->words['a_addlang']}</span></div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__language_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__language', "{$this->lang->words['a_addlang']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=languages&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />

	<div class='acp-box'>
		<h3>{$this->lang->words['a_exportmod']}</h3>
		
		<table class='ipsTable'>
			<tr>
				<th>{$this->lang->words['a_mod_info']}</th>
			</tr>
			<tr>
				<td valign='top'>
					<p id='MF__modules'>{$hookData['hook_extra_data']['display']['modules']}</p>
					<div id='MF__modules_popup' style='text-align:center;'><span class='button'>{$this->lang->words['a_addmod']}</span></div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__modules_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__modules', "{$this->lang->words['a_addmod']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=modules&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />

	<div class='acp-box'>
		<h3>{$this->lang->words['a_exporthelp']}</h3>
		
		<table class='ipsTable'>
			<tr>
				<th>{$this->lang->words['a_help_info']}</th>
			</tr>
			<tr>
				<td valign='top'>
					<p id='MF__help'>{$hookData['hook_extra_data']['display']['help']}</p>
					<div id='MF__help_popup' style='text-align:center;'><span class='button'>{$this->lang->words['a_addhelp']}</span></div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__help_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__help', "{$this->lang->words['a_addhelp']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=help&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />


	<div class='acp-box'>
		<h3>{$this->lang->words['a_exportskin']}</h3>
		
		<table class='ipsTable'>
			<tr>
				<th>{$this->lang->words['a_skin_info']}</th>
			</tr>
			<tr>
				<td valign='top'>
					<p id='MF__templates'>{$hookData['hook_extra_data']['display']['templates']}</p>
					<div id='MF__templates_popup' style='text-align:center;'><span class='button'>{$this->lang->words['a_addskin']}</span></div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__templates_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__templates', "{$this->lang->words['a_addskin']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=skins&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['a_exportcss']}</h3>

		<table class='ipsTable'>
			<tr>
				<th>{$this->lang->words['a_css_info']}</th>
			</tr>
			<tr>
				<td valign='top'>
					<p id='MF__css'>{$hookData['hook_extra_data']['display']['css']}</p>
					<div id='MF__css_popup' style='text-align:center;'><span class='button'>{$this->lang->words['a_addcss']}</span></div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__css_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__css', "{$this->lang->words['a_addcss']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=css&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />
	 
	<div class='acp-box'>
		<h3>{$this->lang->words['a_addreplacements']}</h3>

		<table class='ipsTable'>
			<tr>
				<th>{$this->lang->words['a_replacements_info']}</th>
			</tr>
			<tr>
				<td valign='top'>
					<p id='MF__replacements'>{$hookData['hook_extra_data']['display']['replacements']}</p>
					<div id='MF__replacements_popup' style='text-align:center;'><span class='button'>{$this->lang->words['a_addreplacements']}</span></div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__replacements_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__replacements', "{$this->lang->words['a_addreplacements']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=replacements&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />

	<div class='acp-box'>
		<h3>{$this->lang->words['a_exporttasks']}</h3>
		
		<table class='ipsTable'>
			<tr>
				<th>{$this->lang->words['a_tasks_info']}</th>
			</tr>
			<tr>
				<td valign='top'>
					<p id='MF__tasks'>{$hookData['hook_extra_data']['display']['tasks']}</p>
					<div id='MF__tasks_popup' style='text-align:center;'><span class='button'>{$this->lang->words['a_addtasks']}</span></div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__tasks_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__tasks', "{$this->lang->words['a_addtasks']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=tasks&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />

	<div class='acp-box'>
		<h3>{$this->lang->words['a_exportdb']}</h3>
		
		<table class='ipsTable'>
			<tr>
				<th>{$this->lang->words['a_db_info']}</th>
			</tr>
			<tr>
				<td valign='top'>
					<p id='MF__database'>{$hookData['hook_extra_data']['display']['database']}</p>
					<div id='MF__database_popup' style='text-align:center;'><span class='button'>{$this->lang->words['a_adddb']}</span></div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__database_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__database', "{$this->lang->words['a_adddb']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=database&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />

	<div class='acp-box'>
		<h3>{$this->lang->words['a_exportscript']}</h3>
		
		<table class='ipsTable'>
			<tr>
				<th>{$this->lang->words['a_script_info']}</th>
			</tr>
			<tr>
				<td valign='top'>
					<p id='MF__custom'>{$hookData['hook_extra_data']['display']['custom']}</p>
					<div id='MF__custom_popup' style='text-align:center;'><span class='button'>{$this->lang->words['a_addscript']}</span></div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__custom_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__custom', "{$this->lang->words['a_addscript']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=custom&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />
	 <div class='acp-box'>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['a_export']}' class='button primary'/>
		</div>
	</div>
</form>
EOF;

return $HTML;
}

/**
 * Show a form to create sphinx cronjobs strings
 *
 * @param	array 		$sphinxData		Sphinx data
 * @return	@e string	HTML
 */
public function sphinxConfForm( $sphinxData=array() )
{
$HTML = '';

/* Form path */
$sphinxPath = $this->registry->output->formInput( 'sphinx_conf_path', $this->request['sphinx_conf_path'] );

/* Init template */
$HTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['sphinx_cronjob_title']}</h2>
</div>
EOF;


/* Got a path? */
if ( $this->request['sphinx_conf_path'] && count($sphinxData) )
{
	/* Sort out some variables.. */
	$this->request['sphinx_conf_path'] = rtrim( $this->request['sphinx_conf_path'], '/' );
	$_file		= $this->request['sphinx_conf_path'] . '/sphinx.conf';
	$cronJobs	= "*/15 * * * * /usr/local/bin/indexer --config {$_file} " . implode( ' ', $sphinxData['deltas'] ) . " --rotate
0 4 * * * /usr/local/bin/indexer --config {$_file} --all --rotate";
	
	/* Doesn't exist? */
	if( !@is_file( $_file ) )
	{
		$HTML .= $this->registry->output->global_template->warning_box( $this->lang->words['sphinx_cronjob_nofile'], '<br />' . sprintf( $this->lang->words['sphinx_cronjob_nofiledesc'], $_file ) ) . '<br />';
	}
	
$HTML .= <<<EOF
<div class='information-box'>
	{$this->lang->words['sphinx_cronjob_details']}
	<br /><br />
	<textarea name='sphinx_cronjobs' rows='3' wrap='soft' class='multitext' style='width: 80%;'>{$cronJobs}</textarea>
	<br /><br />
	<strong>{$this->lang->words['sphinx_cronjob_note']}</strong>
</div>
<br /><br />
EOF;
}

$HTML .= <<<EOF
<form action='{$this->settings['base_url']}{$this->form_code}do=sphinxBuildCron' method='post' enctype='multipart/form-data'>
	<div class='acp-box'>
		<h3>{$this->lang->words['sphinx_cronjob_title']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sphinx_cronjob_path']}</strong> 
				</td>
				<td class='field_field'>
					{$sphinxPath}<br />
					<span class='desctext'>{$this->lang->words['sphinx_cronjob_path_desc']}</span>
				</td>
	 		</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['sphinx_build_cronjob']}' class='button primary' />
		</div>
	</div>
</form>
EOF;

return $HTML;
}

}