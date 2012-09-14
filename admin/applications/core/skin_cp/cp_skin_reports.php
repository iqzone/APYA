<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Report center skin file
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
 
class cp_skin_reports
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
 * Show the plugins overview page
 *
 * @param	string		Content
 * @return	string		HTML
 */
public function report_plugin_overview($content) {

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['r_plugmanager']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=create_plugin' title='{$this->lang->words['r_regnew']}'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/plugin_add.png' alt='' />
				{$this->lang->words['r_regnew']}
			</a>
		</li>
	</ul>				
</div>

<div class="acp-box">
	<h3>{$this->lang->words['r_regplugins']}</h3>
	<table class="ipsTable">
		<tr>
			<th width='35%'>{$this->lang->words['r_name']}</th>
			<th width='20%'>{$this->lang->words['r_author']}</th>
			<th width='5%' style='text-align: center'>{$this->lang->words['r_enabled']}</th>
			<th width='5%' style='text-align: center'>{$this->lang->words['r_options']}</th>
		</tr>
 		{$content}
	</table>
</div>
EOF;

return $IPBHTML;
}

/**
 * Show a report plugin row
 *
 * @param	array 		Data for the plugin record
 * @return	string		HTML
 */
public function report_plugin_row( $data ) {

$IPBHTML .= <<<EOF
<tr class='ipsControlRow'>
	<td><strong>{$data['class_title']}
EOF;
if( $data['pversion'] )
{
$IPBHTML .= ' ' . $data['pversion'];
}
$IPBHTML .= <<<EOF
</strong><br /><span class='desctext'>{$data['class_desc']}</span></td>
 <td>
EOF;
if( $data['author_url'] != '' )
{
$IPBHTML .= "<a href=\"{$data['author_url']}\" target=\"_blank\">{$data['author']}</a>";
}
else
{
$IPBHTML .= $data['author'];
}
$IPBHTML .= <<<EOF
</td>
 <td style='text-align: center'>
  <a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=plugin_toggle&amp;plugin_id={$data['com_id']}" title='{$this->lang->words['r_toggleendis']}'><img src='{$this->settings['skin_acp_url']}/images/icons/{$data['_enabled_img']}' alt='YN' /></a>
 </td>
 <td class='col_buttons'>
 	<ul class='ipsControlStrip'>
		<li class='i_edit'>
		   <a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit_plugin&amp;com_id={$data['com_id']}' title='{$this->lang->words['r_editsettings']}'>{$this->lang->words['r_editsettings']}</a>
		</li>
EOF;
if( $data['lockd'] != 1 || IN_DEV == 1 )
{
$IPBHTML .= <<<EOF
		<li class='i_delete'>
			<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remove_plugin&amp;com_id={$data['com_id']}");' title='{$this->lang->words['r_removeplugin']}'>{$this->lang->words['r_removeplugin']}</a>
		</li>
EOF;
}
$IPBHTML .= <<<EOF
	</ul>
 </td>
</tr>
EOF;

return $IPBHTML;
}

/**
 * Show the status overview page
 *
 * @param	array		$statusRows		Statuses data
 * @return	@e string	HTML
 */
public function report_status_overview($statusRows) {

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['r_statmanager']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=create_status'><img src='{$this->settings['skin_acp_url']}/images/icons/page_add.png' alt=''>{$this->lang->words['r_createnew']}</a></li>
	</ul>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['r_repstatsev']}</h3>
	<table class='ipsTable' id='statuses_list'>
EOF;

if ( is_array($statusRows) && count($statusRows) )
{
	foreach( $statusRows as $data )
	{
		$IPBHTML .= <<<EOF
		<tr id='status_{$data['status']}' class='ipsControlRow isDraggable'>
			<td class='col_drag' valign='top' style='padding-top: 20px;'>
				<span class='draghandle'>&nbsp;</span>
			</td>
			<td>
				<table class='ipsTable'>
					<tr>
						<td style='width: 61%'>
							<strong>{$data['title']}</strong>
						</td>
						<td style='width: 34%; text-align: right'>
							<a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=set_status&amp;status=new&amp;id={$data['status']}" title="{$this->lang->words['r_new_report']}"><img src='{$this->settings['skin_acp_url']}/images/report_new_{$data['_is_new']}.png' alt='{$this->lang->words['r_new_report']}' /></a>
							<a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=set_status&amp;status=complete&amp;id={$data['status']}" title="{$this->lang->words['r_complete_report']}"><img src='{$this->settings['skin_acp_url']}/images/report_complete_{$data['_is_complete']}.png' alt='{$this->lang->words['r_complete_report']}' /></a>
							<a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=set_status&amp;status=active&amp;id={$data['status']}" title="{$this->lang->words['r_active_report']}"><img src='{$this->settings['skin_acp_url']}/images/report_active_{$data['_is_active']}.png' alt='{$this->lang->words['r_active_report']}' /></a>
						</td>
						<td class='col_buttons'>
						    <ul class='ipsControlStrip'>
				    			<li class='i_add'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add_image&amp;id={$data['status']}' title='{$this->lang->words['r_addimg']}' title='{$this->lang->words['r_addimg']}'>{$this->lang->words['r_addimg']}</a></li>
								<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit_status&amp;id={$data['status']}' title='{$this->lang->words['r_editstatus']}' title='{$this->lang->words['r_editstatus']}'>{$this->lang->words['r_editstatus']}</a></li>
								<li class='ipsControlStrip_more ipbmenu' id="stat_menu{$data['status']}">
									<a href='#'>&nbsp;</a>
								</li>
						    </ul>
						
							<ul class='acp-menu' id='stat_menu{$data['status']}_menucontent'>
								<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=remove_status&amp;id={$data['status']}");' title='{$this->lang->words['r_removestatus']}'>{$this->lang->words['r_removestatus']}</a></li>
							</ul>
						</td>
					</tr>
					{$data['status_images']}
				</table>
			</td>
		</tr>
EOF;
	}
}

$IPBHTML .= <<<EOF
	</table>
</div>

<script type='text/javascript'>
	jQ("#statuses_list").ipsSortable( 'table', { 
		url: "{$this->settings['base_url']}{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>
EOF;

return $IPBHTML;
}

/**
 * Show a report status image
 *
 * @param	array 		Status image data
 * @return	string		HTML
 */
public function report_status_image( $data ) {

$IPBHTML .= <<<EOF
<tr>
	<td colspan='2'>
		<img src='{$this->settings['public_dir']}{$data['img']}' alt='{$data['points']}' title="{$data['points']} {$this->lang->words['r_points_sufix']}" width="{$data['width']}" height="{$data['height']}" /> {$data['points']} {$this->lang->words['pointssuffix']}
	</td>
	<td class='col_buttons'>
		<ul class='ipsControlStrip' id='statimg{$data['id']}_menucontent'>
			<li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit_image&amp;id={$data['id']}' title='{$this->lang->words['r_editimg']}'>{$this->lang->words['r_editimg']}</a></li>
			<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remove_image&amp;id={$data['id']}");' title='{$this->lang->words['r_removeimg']}'>{$this->lang->words['r_removeimg']}</a></li>
		</ul>
	</td>
</tr>
EOF;

return $IPBHTML;
}

/**
 * Show the form to add/edit a plugin
 *
 * @param	array 		Plugin data
 * @return	string		HTML
 */
public function pluginForm( $plug_data=array() ) {

//-----------------------------------------
// Set some of the form variables
//-----------------------------------------

$secure_key			= ipsRegistry::getClass('adminFunctions')->getSecurityKey();

if( $plug_data['pversion'] )
{
	$plug_data['pversion'] = substr($plug_data['pversion'], 1);
}

$code = 'create_plugin';

if( $plug_data['class_title'] )
{
	$code = 'change_plugin';
}

//-----------------------------------------
// Build applications drop down
//-----------------------------------------

foreach( ipsRegistry::$applications as $app_dir => $app_data )
{
	$apps[] = array( $app_dir, $app_data['app_title'] );
}

$plug_data			= (is_array($plug_data) AND count($plug_data)) ? $plug_data : $this->request;

$form								= array();
$form['plugi_title']				= $this->registry->output->formInput('plugi_title', $plug_data['class_title']);
$form['plugi_version']				= $this->registry->output->formInput('plugi_version', $plug_data['pversion']);
$form['plugi_desc']					= $this->registry->output->formInput('plugi_desc', htmlspecialchars($plug_data['class_desc'], ENT_QUOTES));
$form['plugi_author_url']			= $this->registry->output->formInput('plugi_author_url', $plug_data['author_url']);
$form['plugi_author']				= $this->registry->output->formInput('plugi_author', $plug_data['author']);
$form['plugi_file']					= $this->registry->output->formInput('plugi_file', ( $plug_data['my_class'] ? $plug_data['my_class'] : 'default' ));
$form['plugi_app']					= $this->registry->output->formDropdown( 'appPlugin', $apps, $plug_data['app'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['r_rcpluginstitle']}</h2>
</div>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$code}&amp;secure_key={$secure_key}' method='post'>
<input type='hidden' name='finish' value='1' />
<input type='hidden' name='com_id' value='{$plug_data['com_id']}' />
<div class="acp-box">
	<h3>{$this->lang->words['r_registernew']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_app']}</strong></td>
			<td class='field_field'>{$form['plugi_app']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_plugintitle']}</strong></td>
			<td class='field_field'>{$form['plugi_title']}<br /><span class="desctext">{$this->lang->words['r_whatcall']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_version']}</strong></td>
			<td class='field_field'>{$form['plugi_version']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_description']}</strong></td>
			<td class='field_field'>{$form['plugi_desc']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_authorname']}</strong></td>
			<td class='field_field'>{$form['plugi_author']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_authorurl']}</strong></td>
			<td class='field_field'>{$form['plugi_author_url']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_pluginfile']}</strong></td>
			<td class='field_field'>/admin/applications/YOURAPP/extensions/reportPlugins/&nbsp;{$form['plugi_file']}&nbsp;.php</td>
		</tr>
	</table>
	<div class="acp-actionbar">
    	<input type='submit' value='{$this->lang->words['r_save']}' class="button primary" />
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Add a row
 *
 * @param	string		Title
 * @param	string		Description
 * @param	string		Form field
 * @return	string		HTML
 */
public function addRow( $title, $desc='', $form_field='' ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
		<tr><td class='field_title'>
			<strong class='title'>{$title}</strong></td>
EOF;

$IPBHTML .= <<<EOF
        <td class='field_field'>{$form_field}
EOF;

if( $desc )
{
$IPBHTML .= <<<EOF
	<br /><span class='desctext'>{$desc}</span>
EOF;

$IPBHTML .= <<<EOF
        </td></tr>
EOF;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the form to "finish" adding the plugin
 *
 * @param	array 		Plugin data
 * @param	array 		Groups that can report
 * @param	array 		Groups that can moderate
 * @param	string		Extra form data
 * @return	string		HTML
 */
public function finishPluginForm( $plug_data, $sel_can_report, $sel_group_perm, $extraForm ) {

//-----------------------------------------
// Set some of the form variables
//-----------------------------------------

$secure_key			= ipsRegistry::getClass('adminFunctions')->getSecurityKey();

foreach( $this->cache->getCache('group_cache') as $g )
{
	$groups[] = array( $g['g_id'], $g['g_title'] );
}

//-----------------------------------------
// Build applications drop down
//-----------------------------------------

foreach( ipsRegistry::$applications as $app_dir => $app_data )
{
	$apps[] = array( $app_dir, $app_data['app_title'] );
}
		
$form								= array();
$form['plugi_can_report']			= $this->registry->output->formMultiDropdown('plugi_can_report[]', $groups, $sel_can_report );
$form['plugi_gperm']				= $this->registry->output->formMultiDropdown('plugi_gperm[]', $groups, $sel_group_perm);
$form['plugi_onoff']				= $this->registry->output->formYesNo('plugi_onoff', $plug_data['onoff']);
$form['plugi_lockd']				= $this->registry->output->formYesNo('plugi_lockd', $plug_data['lockd']);
$form['plugi_enabled']				= $this->registry->output->formYesNo('plugi_onoff', $plug_data['onoff']);
$form['plugi_app']					= $this->registry->output->formDropdown( 'appPlugin', $apps, $plug_data['app'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit_plugin&amp;secure_key={$secure_key}&amp;com_id={$plug_data['com_id']}' method='post'>
<input type='hidden' name='finish' value='1' />
<div class="acp-box">
	<h3>{$this->lang->words['r_editplugin']}: {$plug_data['class_title']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_app']}</strong></td>
			<td class='field_field'>{$form['plugi_app']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_groups_submit']}</strong></td>
			<td class='field_field'>{$form['plugi_can_report']}<br /><span class="desctext">{$this->lang->words['r_groups_submit_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_groups']}</strong></td>
			<td class='field_field'>{$form['plugi_gperm']}<br /><span class="desctext">{$this->lang->words['r_groups_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_pluginenabled']}</strong></td>
			<td class='field_field'>{$form['plugi_enabled']}</td>
		</tr>
EOF;

		if( IN_DEV == 1 )
		{
			$IPBHTML .= <<<EOF
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_safemode']}</strong></td>
			<td class='field_field'>{$form['plugi_lockd']}</td>
		</tr>
EOF;
		}
		
		$IPBHTML .= <<<EOF
		{$extraForm}
	</table>
    <div class='acp-actionbar'>
        <input type='submit' value='{$this->lang->words['r_save']}' class="button primary" />
    </div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit a status image
 *
 * @param	string		Code
 * @param	array 		Status record
 * @param	array 		Image data
 * @return	string		HTML
 */
public function imageForm( $code, $status, $image_data=array() ) {

//-----------------------------------------
// Set some of the form variables
//-----------------------------------------

$secure_key			= ipsRegistry::getClass('adminFunctions')->getSecurityKey();

$image_data			= ( is_array( $image_data ) && count( $image_data ) ) ? $image_data : $this->request;

$form								= array();
$form['img_filename']				= $this->registry->output->formInput('img_filename',str_replace("#", "&#35;", $image_data['img'] ));
$form['img_width']					= $this->registry->output->formInput('img_width',$image_data['width']);
$form['img_height']					= $this->registry->output->formInput('img_height',$image_data['height']);
$form['img_points']					= $this->registry->output->formInput('img_points', $image_data['points']);

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$code}&amp;secure_key={$secure_key}' method='post'>
<input type='hidden' name='finish' value='1' />
<input type='hidden' name='id' value='{$image_data['id']}' />
<div class="acp-box">
	<h3>{$status['title']} : {$this->lang->words['r_confimage']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_image']}</strong></td>
			<td class='field_field'>{$form['img_filename']}<br /><span class='desctext'>{$this->lang->words['r_image_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_width']}</strong></td>
			<td class='field_field'>{$form['img_width']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_height']}</strong></td>
			<td class='field_field'>{$form['img_height']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_points']}</strong></td>
			<td class='field_field'>{$form['img_points']}<br /><span class='desctext'>{$this->lang->words['r_points_info']}</span></td>
		</tr>
	</table>
	<div class='acp-actionbar'>
    	<input type='submit' value='{$this->lang->words['r_save']}' class='button primary' />
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit a status
 *
 * @param	string		Code
 * @param	array 		Status data
 * @return	string		HTML
 */
public function statusForm( $code='create_status', $status=array() ) {

//-----------------------------------------
// Set some of the form variables
//-----------------------------------------

$secure_key			= ipsRegistry::getClass('adminFunctions')->getSecurityKey();

$status				= ( is_array( $status ) AND count( $status ) ) ? $status : $this->request;

$form				= array();
$form['stat_title']	= $this->registry->output->formInput( 'stat_title' ,$status['title'] );
$form['stat_ppr']	= $this->registry->output->formInput( 'stat_ppr'   ,$status['points_per_report'] );
$form['stat_pph']	= $this->registry->output->formInput( 'stat_pph'   ,$status['minutes_to_apoint'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$code}&amp;secure_key={$secure_key}' method='post'>
<input type='hidden' name='finish' value='1' />
<input type='hidden' name='id' value='{$status['status']}' />
<div class="acp-box">
	<h3>{$this->lang->words['r_' . $code ]}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_name']}</strong></td>
			<td class='field_field'>{$form['stat_title']}<br /><span class='desctext'>{$this->lang->words['r_name_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_pointsper']}</strong></td>
			<td class='field_field'>{$form['stat_ppr']}<br /><span class='desctext'>{$this->lang->words['r_pointsper_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['r_minutes']}</strong></td>
			<td class='field_field'>{$form['stat_pph']}<br /><span class='desctext'>{$this->lang->words['r_minutes_info']}</span></td>
		</tr>
	</table>
	<div class='acp-actionbar'>
    	<input type='submit' value='{$this->lang->words['r_save']}' class="button primary"/>
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

}