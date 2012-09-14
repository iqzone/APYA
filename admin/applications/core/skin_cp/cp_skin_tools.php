<?php
/**
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP Tools skin file
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
 
class cp_skin_tools
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

//===========================================================================
// Manage EMail Rules
//===========================================================================
public function manageEmailRules( $rows ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['ie_title']}</h2>
	
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}module=tools&amp;section=incomingEmails&amp;do=add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='{$this->lang->words['ie_add']}' />{$this->lang->words['ie_add']}</a>
			</li>
HTML;
	if ( $this->settings['pop3_user'] )
	{
		$IPBHTML .= <<<HTML
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}module=tools&amp;section=incomingEmails&amp;do=test'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' />{$this->lang->words['ie_test_pop3']}</a>
			</li>
HTML;
	}
	$IPBHTML .= <<<HTML
		</ul>
	</div>
</div>


<div class='acp-box'>
	<h3>{$this->lang->words['ie_title']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<th width='2%'>&nbsp;</th>
			<th>{$this->lang->words['ie_criteria']}</th>
			<th>{$this->lang->words['ie_action']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if ( !empty( $rows ) )
{

	foreach( $rows as $r )
	{
	$IPBHTML .= <<<HTML
			<tr class='ipsControlRow'>
				<td>&nbsp;</td>
				<td>{$r['criteria']}</td>
				<td>{$r['action']}</td>
				<td class='col_buttons'>
					<ul class='ipsControlStrip'>
						<li class='i_edit'>
							<a href='{$this->settings['base_url']}module=tools&amp;section=incomingEmails&amp;do=edit&amp;id={$r['id']}' title='{$this->lang->words['edit']}'>{$this->lang->words['edit']}</a>
						</li>
						<li class='i_delete'>
							<a href='{$this->settings['base_url']}module=tools&amp;section=incomingEmails&amp;do=delete&amp;id={$r['id']}' title='{$this->lang->words['delete']}'>{$this->lang->words['delete']}</a>
						</li>
					</ul>
				</td>
			</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
			<tr>
				<td colspan='4' style='text-align:center'><em>{$this->lang->words['ie_none']}</em></td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Add/Edit Email Rules
//===========================================================================
function emailRuleForm( $current, $applications=array() ) {

if ( empty( $current ) )
{
	$title = $this->lang->words['ie_add'];
	$id = 0;
}
else
{
	$title = $this->lang->words['ie_edit'];
	$id = $current['rule_id'];
}

$form['criteria_field'] = $this->registry->output->formDropdown( 'criteria_field', array(
	array( 'to', $this->lang->words['ie_cf_to'] ),
	array( 'from', $this->lang->words['ie_cf_from'] ),
	array( 'sbjt', $this->lang->words['ie_cf_sbjt'] ),
	array( 'body', $this->lang->words['ie_cf_body'] ),
	), empty( $current ) ? NULL : $current['rule_criteria_field'] );
	
$form['criteria_type'] = $this->registry->output->formDropdown( 'criteria_type', array(
	array( 'ctns', $this->lang->words['ie_ct_ctns'] ),
	array( 'eqls', $this->lang->words['ie_ct_eqls'] ),
	array( 'regx', $this->lang->words['ie_ct_regx'] ),
	), empty( $current ) ? NULL : $current['rule_criteria_type'] );

$form['criteria_value'] = $this->registry->output->formInput( 'criteria_value', ( empty( $current ) ? '' : $current['rule_criteria_value'] ) );

$form['action'] = $this->registry->output->formDropdown( 'action', array_merge(
	array( array( '--', $this->lang->words['ie_ignore'] ) ),
	$applications
	), empty( $current ) ? NULL : $current['rule_app'] );


$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML

<div class='section_title'>
	<h2>{$title}</h2>
</div>

<div class='acp-box'>
	<h3>{$title}</h3>
	<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=save' method='post'>
	<input type='hidden' name='id' value='{$id}' />
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['ie_rule']}</strong>
			</td>
			<td class='field_field'>
				{$form['criteria_field']} {$form['criteria_type']} {$form['criteria_value']}
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['ie_action']}</strong>
			</td>
			<td class='field_field'>
				{$form['action']}
			</td>
		</tr>
	</table>	
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['ie_save']}' class='realbutton'>
	</div>
	</form>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// License Activation Form
//===========================================================================
public function activateForm( $error ){

$keyInput = $this->registry->output->formInput( 'license_key', $this->request['license_key'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['ipssoftware_license']}</h2>
</div>

HTML;

if ( $error )
{
	$IPBHTML .= <<<HTML
<div class='warning'>
	{$error}
</div>
<br /><br />
HTML;
}

$IPBHTML .= <<<HTML

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='activate'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />

	<div class='acp-box'>
		<h3>{$this->lang->words['license_activate']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['license_key']}</strong>
				</td>
				<td class='field_field'>
					{$keyInput}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['license_activate']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// License Key Status
//===========================================================================
public function licenseKeyStatusScreen( $licenseKey, $licenseData ){
$IPBHTML = "";
//--starthtml--//

$expires	= ( $licenseData['key']['_expires'] == 9999999999 ) ? $this->lang->words['license_lifetime'] : $this->registry->class_localization->getDate( $licenseData['key']['_expires'], 'DATE', true );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['license_key']}</h2>
	
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=refresh'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='' />{$this->lang->words['license_refresh']}</a>
			</li>
			<li class='ipsActionButton'>
				<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=remove");'><img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='' />{$this->lang->words['removelicensekey']}</a>
			</li>
		</ul>
	</div>
</div>
<div class='acp-box'> 
	<h3>{$this->lang->words['license_key']}: {$licenseKey}</h3> 
	<table class='ipsTable alternate_rows double_pad'> 
		<tr> 
			<td style='width: 3%'> 
				<img src='{$this->settings['skin_acp_url']}/images/icons/{$licenseData['key']['status_icon']}' /> 
			</td> 
			<td style='width: 77%'> 
				<strong>{$licenseData['key']['name']}</strong><br /> 
				<span class='desctext'>{$licenseData['key']['msg']}</span> 
			</td> 
			<td style='width: 20%;'> 
				{$this->lang->words['expires_prefix']} {$expires}
			</td> 
		</tr>
HTML;

if( is_array( $licenseData['ipbMain'] ) && count( $licenseData['ipbMain'] ) )
{
$IPBHTML .= <<<HTML
		<tr>
			<th colspan='3'>{$this->lang->words['license_services']}</td>
		</tr>
HTML;

	foreach( $licenseData['ipbMain'] as $r )
	{
		$r['expires']	= $r['_expires'] ? $this->lang->words['expires_prefix'] . ' ' . $this->registry->class_localization->getDate( $r['_expires'], 'DATE', true ) : $r['expires'];
		
$IPBHTML .= <<<HTML
	<tr> 
		<td style='width: 3%'> 
			<img src='{$this->settings['skin_acp_url']}/images/icons/{$r['status_icon']}' /> 
		</td> 
		<td style='width: 77%'> 
			<strong>{$r['name']}</strong><br /> 
			<span class='desctext'>{$r['msg']}</span> 
		</td> 
		<td style='width: 20%'> 
			{$r['expires']}
		</td> 
	</tr>
HTML;
	}
}

if( is_array( $licenseData['addons'] ) && count( $licenseData['addons'] ) )
{
$IPBHTML .= <<<HTML
		<tr>
			<th colspan='3'>{$this->lang->words['license_addons']}</td>
		</tr>
HTML;

	foreach( $licenseData['addons'] as $r )
	{
		$r['expires']	= $r['_expires'] ? $this->lang->words['expires_prefix'] . ' ' . $this->registry->class_localization->getDate( $r['_expires'], 'DATE', true ) : $r['expires'];

$IPBHTML .= <<<HTML
	<tr> 
		<td style='width: 3%'> 
			<img src='{$this->settings['skin_acp_url']}/images/icons/{$r['status_icon']}' alt='information' /> 
		</td> 
		<td style='width: 77%'> 
			<strong>{$r['name']}</strong><br /> 
			<span class='desctext'>{$r['msg']}</span> 
		</td> 
		<td style='width: 20%'> 
			{$r['expires']}
		</td> 
	</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table> 
HTML;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Sharelinks Form
//===========================================================================
public function sharelinksForm( $id, $do, $title, $button, $form ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['sl_form_main_title']}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sl_form_enabled']}</strong>
				</td>
				<td class='field_field'>
					{$form['share_enabled']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sl_form_title']}</strong>
				</td>
				<td class='field_field'>
					{$form['share_title']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sl_form_key']}</strong>
				</td>
				<td class='field_field'>
					{$form['share_key']}<br />
					<span class='desctext'>{$this->lang->words['sl_form_key_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sl_form_canonical']}</strong>
				</td>
				<td class='field_field'>
					{$form['share_canonical']}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$button}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// Share links Index
//===========================================================================
public function shareLinksIndex( $rows ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['sl_m_title']}</h2>
	
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' />{$this->lang->words['sl_add']}</a>
			</li>
		</ul>
	</div>
</div>



<div class='acp-box'>
	<h3>{$this->lang->words['sl_current_title']}</h3>
	
	<table id='share_services' class='ipsTable alternate_rows'>
		<tr>
			<th class='col_drag'>&nbsp;</th>
			<th width='2%'>&nbsp;</th>
			<th>{$this->lang->words['sl_title']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
	<!--<ul id='share_handle' class='alternate_rows'>-->
HTML;

$_public	= PUBLIC_DIRECTORY;

foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
		<tr class='ipsControlRow isDraggable' id='share_{$r['share_id']}'>
			<td class='col_drag'>
				<span class='draghandle'></span>
			</td>
			<td>
				<img src='{$this->settings['board_url']}/{$_public}/style_extra/sharelinks/{$r['share_key']}.png'>
			</td>
			<td style='width: 100%'>
				<strong>{$r['share_title']}</strong>
			</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;share_id={$r['share_id']}' title='{$this->lang->words['sl_editbook']}...'>{$this->lang->words['sl_editbook']}...</a>
					</li>
					<li class='i_delete'>
						<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;share_id={$r['share_id']}");' title='{$this->lang->words['sl_deletebook']}...'>{$this->lang->words['sl_deletebook']}...</a>
					</li>
				</ul>
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<script type='text/javascript'>
	jQ("#share_services").ipsSortable('table', { 
		url: "{$this->settings['base_url']}&{$this->form_code_js}do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	} );
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * User agents group form
 *
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		User agent group data
 * @param	array 		User agents
 * @return	string		HTML
 */
public function uagents_groupForm($form, $title, $formcode, $button, $ugroup, $userAgents) {

$IPBHTML = "";
//--starthtml--//

$_json      = json_encode( array( 'uagents' => $userAgents ) );
$_groupJSON = ( is_array( $ugroup['_groupArray'] ) AND count( $ugroup['_groupArray'] ) ) ? json_encode( $ugroup['_groupArray'] ) : '{}';

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<script type="text/javascript" src="{$this->settings['js_app_url']}ipb3uAgents.js"></script>
<form id='uAgentsForm' action='{$this->settings['base_url']}{$this->form_code}&amp;do={$formcode}&amp;ugroup_id={$ugroup['ugroup_id']}' method='post'>
<input id='uAgentsData' type='hidden' name='uAgentsData' value='{$_groupJSON}' />
<div class='acp-box'>
	<h3>{$title}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['t_uatitle']}</strong>
			</td>
			<td class='field_field'>
				{$form['ugroup_title']}
			</td>
		</tr>
		<tr>
			<td colspan='2' class='no_pad'>
					<table class='ipsTable'>
						<tr>
							<th style='width: 50%'>{$this->lang->words['t_uaallavail']}</th>
							<th style='width: 50%'>{$this->lang->words['t_uagroups']}</th>
						</tr>
						<tr>
							<td style='padding: 8px; vertical-align: top;'>
								<div id='tplate_agentsList' class='uagent_list'></div>
							</td>
							<td style='vertical-align: top;'>
								<div id='tplate_groupList' class='uagent_list'></div>
							</td>
						</tr>
					</table>
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
	 	<input type='button'  class="button primary" value='{$button}' onclick='IPB3UAgents.saveGroupForm()' />
	</div>
</div>
</form>
<!-- templates -->
<div style='display:none'> 
	<div id='tplate_agentRow'>
		<div id='tplate_agentrow_#{uagent_id}' onmouseover='IPB3UAgents.groupMouseEvent(event)' onmouseout='IPB3UAgents.groupMouseEvent(event)' onclick='IPB3UAgents.groupMouseEvent(event)' class='#{_cssClass}'>
			<div>
				<img id='tplate_agentimg_#{uagent_id}' src="{$this->settings['skin_acp_url']}/images/folder_components/uagents/" />
				<span style='font-weight:bold'>#{uagent_name}</span>
			</div>
		</div>	
	</div>
	<div id='tplate_groupRow'>
		<div id='tplate_grouprow_#{uagent_id}' onmouseover='IPB3UAgents.groupUsedMouseEvent(event)' onmouseout='IPB3UAgents.groupUsedMouseEvent(event)' onclick='IPB3UAgents.groupUsedMouseEvent(event)' class='#{_cssClass}'>
			<div id='tplate_grouprow_#{uagent_id}_remove' style='float:right;margin-right:10px;cursor:pointer'>[ {$this->lang->words['t_uaremove']} ]</div>
			<div id='tplate_grouprow_#{uagent_id}_configure' style='float:right;margin-right:10px;cursor:pointer;display:none;font-size:10px'>{$this->lang->words['t_uaversions']} #{uagent_versions} [ {$this->lang->words['t_uaconfigure']} ]</div>
			<div>
				<img id='tplate_groupimg_#{uagent_id}' src="{$this->settings['skin_acp_url']}/images/folder_components/uagents/" />
				<span style='font-weight:bold'>#{uagent_name}</span>
			</div>
		</div>	
	</div>
	<div id='tplate_versionsEditor'>
		<div id='tplate_versions_#{uagent_id}' class='acp-box' style='width:500px'>
			<h3>{$this->lang->words['t_uaediting']} #{uagent_name}</h3>
			<div class='row2' style='padding:10px'>
				<div>
					{$this->lang->words['t_ua_info']}
				</div>
				{$this->lang->words['uaversions']}
				<input type='text' id='tplate_versionsBox_#{uagent_id}' value='#{uagent_versions}' style='width:100%;' />
			</div>
			<div class='acp-actionbar' style='text-align:right;'>
				<input type='button' class="button primary" value='{$this->lang->words['t_uasave']}' onclick='IPB3UAgents.saveAgentVersion(#{uagent_id})' />
				&nbsp;
				<input type='button' class="button" value='{$this->lang->words['t_uaclose']}' onclick='IPB3UAgents.cancelAgentVersion(#{uagent_id})' />
			</div>
		</div>
	</div>
</div>
<!-- /templates -->
<script type='text/javascript'>
	var IPB3UAgents              = new IPBUAgents();
	IPB3UAgents.uAgentsData      = {$_json};
	IPB3UAgents.uAgentsGroupData = {$_groupJSON};
	IPB3UAgents.groupFormInit();
 //]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * User agents groups wrapper
 *
 * @param	array 		User agent groups
 * @return	string		HTML
 */
public function uagents_listUagentGroups( $userAgentGroups ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['t_ua_groups']}</h2>
EOF;

if ( $this->registry->class_permissions->checkPermission( 'ua_manage' ) )
{
	$IPBHTML .= <<<EOF
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}&amp;module=tools&amp;section=uagents&amp;do=groupAdd'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['ua_addnewbutton']}</a></li>
		<li><a href='{$this->settings['base_url']}{$this->form_code}' title='{$this->lang->words['tua_manage']}'><img src='{$this->settings['skin_acp_url']}/images/icons/world.png' alt='' />{$this->lang->words['tua_manage']}</a></li>
	</ul>
EOF;
}
$IPBHTML .= <<<EOF
</div>
<div class='acp-box'>
	<h3>{$this->lang->words['t_ua_groups']}</h3>
	<table class='ipsTable double_pad alternate_rows'>
		
EOF;

if ( is_array( $userAgentGroups ) AND count( $userAgentGroups ) )
{
	foreach( $userAgentGroups as $ugroup_id => $data )
	{
		$IPBHTML .= <<<EOF
		<tr class='ipsControlRow'>
			<td>
				<img src='{$this->settings['skin_acp_url']}/images/folder_components/uagents/group.png' style='vertical-align: bottom;' />
				<strong>{$data['ugroup_title']}</strong>
				
				<div class='col_buttons right'>
					<span class='desctext'>{$data['_arrayCount']}</span> &nbsp;
					&nbsp; 
					<ul class='ipsControlStrip'>
EOF;
				if ( $this->registry->class_permissions->checkPermission( 'ua_manage' ) )
				{
					$IPBHTML .= <<<EOF
						<li class='i_edit'><a style="z-index: 10000;" href='{$this->settings['base_url']}{$this->form_code}&amp;do=groupEdit&amp;ugroup_id={$data['ugroup_id']}' title='{$this->lang->words['t_ua_editg']}'>{$this->lang->words['t_ua_editg']}</a></li>
EOF;
				}
				if ( $this->registry->class_permissions->checkPermission( 'ua_remove' ) )
				{
					$IPBHTML .= <<<EOF
						<li class='i_delete'><a style="z-index: 10000;" href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=groupRemove&amp;ugroup_id={$data['ugroup_id']}");' title='{$this->lang->words['t_ua_removeg']}'>{$this->lang->words['t_ua_removeg']}</a></li>
EOF;
				}
					$IPBHTML .= <<<EOF
					</ul>
				</div>
			</td>
		</tr>
EOF;
	}
}
else
{
 	$nonesetup = sprintf( $this->lang->words['t_ua_none'], $this->settings['base_url'] );
	$IPBHTML .= <<<EOF
		<tr>
			<td class='no_messages'>
				{$nonesetup}
EOF;
			if ( $this->registry->class_permissions->checkPermission( 'ua_manage' ) )
			{
				$IPBHTML .= <<<EOF
				 <a href='{$this->settings['base_url']}&amp;module=tools&amp;section=uagents&amp;do=groupAdd' class='mini_button'>{$this->lang->words['ua_addnewbutton']}</a>
EOF;
			}
				$IPBHTML .= <<<EOF
			</td>
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
 * List the user agents
 *
 * @param	array 		User agents
 * @return	string		HTML
 */
public function uagents_listUagents( $userAgents ) {

$IPBHTML = "";
//--starthtml--//
$_json    = json_encode( array( 'uagents' => $userAgents ) );

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['t_uamanagement']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='#' id='add_uagent' title='{$this->lang->words['t_uaaddnew']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/world_add.png' alt='' />
					{$this->lang->words['t_uaaddnew']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=groupsList' title='{$this->lang->words['t_uamanagegroup']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' />
					{$this->lang->words['t_uamanagegroup']}
				</a>
			</li>
			<li class='ipsActionButton inDev'>
				<a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=rebuildMaster"><img src='{$this->settings['skin_acp_url']}/images/icons/import.png' alt='' /> {$this->lang->words['ua_rebuild_master']}</a>
			</li>
		</ul>
	</div>
</div>

EOF;


$IPBHTML .= <<<EOF
	<script tyle='text/javascript' src='{$this->settings['js_main_url']}acp.uagents.js'></script>
	<div class='acp-box'>
		<h3>{$this->lang->words['t_uamanagement']}</h3>
EOF;
		if( !count( $userAgents ) )
		{
			$none = sprintf( $this->lang->words['t_ua_nonein'], $this->settings['base_url'], $this->form_code );
			$IPBHTML .= <<<EOF
			<div class='no_messages'>
				{$none}
			</div>
EOF;
		}
		else
		{
			//print_r( $userAgents );
			$IPBHTML .= <<<EOF
				<ul class='sortable_handle alternate_rows' id='sortable_handle'>
EOF;
			foreach( $userAgents as $agent )
			{
			$IPBHTML .= <<<EOF
				<li id='uagent_{$agent['uagent_id']}' class='isDraggable'>
					<table class='ipsTable'>
						<tr class='ipsControlRow'>
							<td>
								<div class='draghandle'>&nbsp;</div>
							</td>
							<td style='width: 2%; text-align: center'>
								<img src='{$this->settings['skin_acp_url']}/images/folder_components/uagents/type_{$agent['uagent_type']}.png' alt='' />
							</td>
							<td style='width: 78%'>
								<strong>{$agent['uagent_name']}</strong>
							</td>
							<td class='col_buttons'>
								<ul class='ipsControlStrip'>
									<li class='i_edit'>
										<a href='#' title='{$this->lang->words['t_uaedit']}: {$agent['uagent_name']}' id='agent_{$agent['uagent_id']}_edit' title='{$this->lang->words['t_uaedit']}: {$agent['uagent_name']}'>{$this->lang->words['t_uaedit']}: {$agent['uagent_name']}</a>
									</li>
EOF;

								if ( $agent['uagent_regex'] != $agent['uagent_default_regex'] )
								{
									$IPBHTML .= <<<EOF
									<li class='i_revert'>
										<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=revert&amp;id={$agent['uagent_id']}' title='{$this->lang->words['t_uarevert']}'>{$this->lang->words['t_uarevert']}</a>
									</li>
EOF;
								}

$IPBHTML .= <<<EOF
									<li class='i_delete'>
										<a href='#' title='{$this->lang->words['t_uaremove']}: {$agent['uagent_name']}' id='agent_{$agent['uagent_id']}_delete' title='{$this->lang->words['t_uaremove']}: {$agent['uagent_name']}'>{$this->lang->words['t_uaremove']}: {$agent['uagent_name']}</a>
									</li>
								</ul>
							</td>
						</tr>
					</table>
					<script type='text/javascript'>
						$('agent_{$agent['uagent_id']}_edit').observe('click', acp.uagents.editAgent.bindAsEventListener( this, {$agent['uagent_id']} ) );
						$('agent_{$agent['uagent_id']}_delete').observe('click', acp.uagents.deleteAgent.bindAsEventListener( this, {$agent['uagent_id']} ) );
					</script>
				</li>
EOF;
			}
			
			$IPBHTML .= <<<EOF
				</ul>
				<script type='text/javascript'>
					acp.uagents.updateURL = "{$this->settings['base_url']}&{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}";
					acp.uagents.json = {$_json};
					
					ipb.templates['add_uagent'] = new Template("<div class='acp-box'><h3>#{box_title}</h3><table class='ipsTable double_pad'><tr><td class='field_title' ><strong class='title'><label for='uagent_capture_#{id}'>{$this->lang->words['t_uaname']}</label></strong></td><td class='field_field'><input type='text' class='input_text' id='uagent_name_#{id}' value='#{a_name}' /><br /><span class='desctext'>{$this->lang->words['t_uaname_desc']}</span></td></tr><tr><td class='field_title'><strong class='field_title'><label for='uagent_capture_#{id}'>{$this->lang->words['t_uakey']}</value></strong></td><td class='field_field'><input type='text' class='input_text' id='uagent_key_#{id}' value='#{a_key}' /><br /><span class='desctext'>{$this->lang->words['t_uakey_desc']}</span></td></tr><tr><td class='field_title'><strong class='title'><label for='uagent_capture_#{id}'>{$this->lang->words['t_uatype']}</value></strong></td><td class='field_field'><select id='uagent_type_#{id}'><option value='browser' #{type_browser}>{$this->lang->words['t_uabrowser']}</option><option value='search' #{type_search}>{$this->lang->words['t_uasearchengine']}</option><option value='other' #{type_other}>{$this->lang->words['t_uaother']}</option></select></td></tr><tr><td class='field_title'><strong class='title'><label for='uagent_capture_#{id}'>{$this->lang->words['t_uaregex']}</label></strong></td><td class='field_field'><textarea id='uagent_regex_#{id}' class='input_text' style='width: 40%' rows='5'>#{a_regex}</textarea></td></tr><tr><td class='field_title'><strong class='title'><label for='uagent_capture_#{id}'>{$this->lang->words['t_uacapture']}</label></strong></td><td class='field_field'><input type='text' class='input_text' id='uagent_capture_#{id}' value='#{a_capture}' /><br /><span class='desctext'>{$this->lang->words['t_uacapture_desc']}</span></td></tr></table><div class='acp-actionbar'><input type='hidden' id='uagent_position_#{id}' value='#{a_position}' /><input type='submit' class='realbutton' value='{$this->lang->words['t_uasave']}' id='uagent_#{id}_save' /></div></div>");
					
					ipb.templates['agent_row'] = new Template("<li id='uagent_#{id}' class='isDraggable'><table class='ipsTable'><tr class='ipsControlRow'><td><div class='draghandle'>&nbsp;</div></td><td style='width: 2%; text-align: center'><img src='{$this->settings['skin_acp_url']}/images/folder_components/uagents/type_#{type}.png' alt='{$this->lang->words['icon']}' /></td><td style='width: 78%'><strong>#{name}</strong></td><td class='col_buttons'><ul class='ipsControlStrip'><li class='i_edit'><a href='#' title='{$this->lang->words['t_uaedit']}' id='agent_#{id}_edit'>{$this->lang->words['t_uaedit']}</a></li><li class='i_delete'><a href='#' title='{$this->lang->words['t_uaremove']}: #{name}' id='agent_#{id}_delete'>{$this->lang->words['t_uaremove']}: #{name}</a></li></ul></td></tr></table></li>");
				</script>
EOF;
		}
		
		$IPBHTML .= <<<EOF
	</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Form to configure login method 
 *
 * @param	array 		Login method
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function login_conf_form( $login, $form ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form id='mainform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_save_conf&amp;login_id={$login['login_id']}' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['t_configdetails']} {$login['login_title']}</h3>
	<table class='ipsTable double_pad'>
EOF;

foreach( $form as $form_entry )
{
	$IPBHTML .= <<<EOF
		<tr>
			<td class='field_title'>
   				<strong class='title'>{$form_entry['title']}</strong>
			</td>
			<td class='field_field'>
				{$form_entry['control']}
EOF;

	if( $form_entry['description'] )
	{
		$IPBHTML .= "<br /><span class='desctext'>{$form_entry['description']}</span>";
	}
	
$IPBHTML .= <<<EOF
			</td>
		</tr>
EOF;
}

$IPBHTML .= <<<EOF
	</table>
	<div class='acp-actionbar'>
 		<input type='submit' value='{$this->lang->words['t_uasave']}' class='button primary' />
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Login methods overview page
 *
 * @param	string		Login method rows
 * @return	string		HTML
 */
public function login_overview( $content ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['l_title']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}&amp;module=tools&amp;section=login&amp;do=login_add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['tol_register_new_log_in_method']}</a></li>
	</ul>	
</div>

<div class='acp-box'>
    <h3>{$this->lang->words['tol_registered_log_in_authenticati']}</h3>
	<table class='ipsTable' id='login_sortable'>
    	{$content}
    </table>
</div>
<script type='text/javascript'>
	jQ("#login_sortable").ipsSortable('table', { 
		url: "{$this->settings['base_url']}&{$this->form_code_js}&do=login_reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ),
		sendType: 'post'
	} );
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Login method diagnostic results
 *
 * @param	array 		Login method
 * @return	string		HTML
 */
public function login_diagnostics( $login=array() ) {

$IPBHTML = "";
//--starthtml--//

$enabled = ( $login['login_enabled'] ) ? "<span class='ipsBadge badge_green'>{$this->lang->words['perf_on']}</span>" : "<span class='ipsBadge badge_red'>{$this->lang->words['perf_off']}</span>";
$file_exists = ( $login['_file_auth_exists'] == 'tick.png' ) ? "<span class='ipsBadge badge_green'>{$this->lang->words['login_exists']}</span>" : "<span class='ipsBadge badge_red'>{$this->lang->words['login_notexists']}</span>";

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['tol_diagnostics_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['tol_diagnostics_for']}: {$login['login_title']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['tol_log_in_enabled']}</strong>
			</td>
			<td class='field_field'>
				{$enabled}
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['tol_auth_exists']}</strong>
			</td>
			<td class='field_field'>
				{$file_exists}<br />
				<span class='desctext'>./sources/loginauth/{$login['login_folder_name']}/auth.php</span>
			</td>
		</tr>
EOF;

if( $login['_missingModules'] )
{
	$_missing = sprintf( $this->lang->words['login_method_missing_functions'], $login['_missingModules'] );
$IPBHTML .= <<<EOF
	<tr>
		<td colspan='5' class='no_messages' style='color:red;'><strong>{$_missing}</strong></td>
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
 * Form to add/edit a login method
 *
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		Login method
 * @return	string		HTML
 */
public function login_form($form, $title, $formcode, $button, $login) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['l_title']}</h2>
</div>

<form id='mainform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;login_id={$login['login_id']}' method='post'>
	<div class='acp-box'>
		<h3>{$title}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_log_in_title']}</strong>
				</td>
				<td class='field_field'>
					{$form['login_title']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_log_in_description']}</strong>
				</td>
				<td class='field_field'>
					{$form['login_description']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_log_in_files_folder_name']}</strong>
				</td>
				<td class='field_field'>	
					{$form['login_folder_name']}<br />
					<span class='desctext'>{$this->lang->words['tol_the_main_folder_the_php_files_']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_log_in_enabled']}</strong>
				</td>
				<td class='field_field'>
					{$form['login_enabled']}<br />
					<span class='desctext'>{$this->lang->words['tol_if_yes_this_log_in_will_be_ena']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['t_l_type']}</strong>
				</td>
				<td class='field_field'>
					{$form['login_user_id']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['t_l_html']}</strong>
				</td>
				<td class='field_field'>
					{$form['login_alt_login_html']}<br />
					<span class='desctext'>{$this->lang->words['t_l_html_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['t_l_html2']}</strong>
				</td>
				<td class='field_field'>
					{$form['login_alt_acp_html']}<br />
					<span class='desctext'>{$this->lang->words['t_l_html2_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['t_l_html3']}</strong>
				</td>
				<td class='field_field'>	
					{$form['login_replace_form']}<br />
					<span class='desctext'>{$this->lang->words['t_l_html3_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_log_in_user_maintenance_url']}</strong>
				</td>
				<td class='field_field'>
					{$form['login_maintain_url']}<br />
					<span class='desctext'>{$this->lang->words['tol_the_url_for_the_place_they_can']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_log_in_user_register_url']}</strong>
				</td>
				<td class='field_field'>	
					{$form['login_register_url']}<br />
					<span class='desctext'>{$this->lang->words['tol_the_url_for_the_place_to_regis']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_log_in_user_log_in_url']}</strong>
				</td>
				<td class='field_field'>	
					{$form['login_login_url']}<br />
					<span class='desctext'>{$this->lang->words['tol_the_url_for_the_place_to_log_i']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_log_in_user_log_out_url']}</strong>
				</td>
				<td class='field_field'>		
					{$form['login_logout_url']}<br />
					<span class='desctext'>{$this->lang->words['tol_the_url_for_the_place_to_log_o']}</span>
				</td>
			</tr>
			
EOF;
//startif
if ( $form['login_safemode'] != '' )
{		
$IPBHTML .= <<<EOF
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['tol_enable_safemode']}</strong>
				</td>
				<td class='field_field'>
					{$form['login_safemode']}<br />
					<span class='desctext'>{$this->lang->words['tol_cannot_be_deleted_or_edited_by']}</span>
				</td>
			</tr>
EOF;
}//endif
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
 * Sub header for login methods
 *
 * @param	string		Subheader label
 * @return	string		HTML
 */
public function login_subheader( $label ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
	<th colspan='4'>{$label}</th>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * No login methods of this type row
 *
 * @param	string		Row label
 * @return	string		HTML
 */
public function login_norow( $label ) {

$IPBHTML = "";
//--starthtml--//
$nomethods = sprintf( $this->lang->words['t_l_nomethods'], $label ); 
$IPBHTML .= <<<EOF
<tr>
	<td colspan='2' class='no_messages'>
		{$nomethods}
	</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}
	
/**
 * Login method row
 *
 * @param	array 		Login method data
 * @return	string		HTML
 */
public function login_row( $data ) {

$IPBHTML = "";
//--starthtml--//

$className	= '';

if( $data['login_installed'] )
{
	$dragbit = "<div class='draghandle'>&nbsp;</div>";
}

$IPBHTML .= <<<EOF
<tr id='logins_{$data['login_id']}' class='ipsControlRow isDraggable'>
	<td class='col_drag'>
EOF;
		if( $data['login_installed'] )
		{
			$IPBHTML .= <<<EOF
			 		<div class='draghandle'>&nbsp;</div>
EOF;
		}
		else
		{
			$IPBHTML .= <<<EOF
					<img src='{$this->settings['skin_acp_url']}/images/icons/lock.png' alt='--' />
EOF;
		}
		
		$IPBHTML .= <<<EOF
	</td>
	<td style='width: 80%'>
		<strong class='larger_text'>{$data['login_title']}</strong>
EOF;
			if( $data['login_description'] )
			{
				$IPBHTML .= <<<EOF
					<br /><span class='desctext'>{$data['login_description']}</span>
EOF;
			}
		
		$IPBHTML .= <<<EOF
	</td>
	<td style='width: 14%; text-align: center'>
EOF;
			if( $data['login_installed'] )
			{
				$toggle_text = $data['login_enabled'] ? $this->lang->words['t_l_disable'] : $this->lang->words['t_l_enable'];
				$IPBHTML .= "<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_toggle&amp;login_id={$data['login_id']}' title='{$toggle_text}'>";
				
				if( $data['login_enabled'] ){
					$IPBHTML .= "<span class='ipsBadge badge_green'>{$this->lang->words['perf_on']}</span>";
				} else {
					$IPBHTML .= "<span class='ipsBadge badge_red'>{$this->lang->words['perf_off']}</span>";
				}
				
				$IPBHTML .= "</a>";
			}
			
			$IPBHTML .= <<<EOF
	</td>
	<td class='col_buttons'>
		<ul class='ipsControlStrip'>
EOF;
		if ( $data['login_installed'] )
		{		
		$IPBHTML .= <<<EOF
			<li class='i_edit'>
				<a style="z-index: 10000;" href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_edit_details&amp;login_id={$data['login_id']}' title='{$this->lang->words['tol_edit_details']}'>{$this->lang->words['tol_edit_details']}</a>
			</li>
EOF;

			if( $data['acp_plugin'] )
			{
				$IPBHTML .= <<<EOF
						<li class='i_cog'>
							<a style="z-index: 10000;" href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_acp_conf&amp;login_id={$data['login_id']}' title='{$this->lang->words['t_l_configdetails']}'>{$this->lang->words['t_l_configdetails']}</a>
						</li>
EOF;
			}
			
			$IPBHTML .= <<<EOF
				<li class='ipsControlStrip_more ipbmenu' id="menu{$data['login_id']}">
					<a href='#'>{$this->lang->words['t_l_moredetails']}</a>
				</li>
EOF;
		}
		else
		{
			$IPBHTML .= <<<EOF
				<li class='i_add'>
					<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_install&amp;login_folder={$data['login_folder_name']}' title='{$this->lang->words['tol_install']}'>{$this->lang->words['tol_install']}</a>
				</li>
EOF;
		}
		
		$IPBHTML .= <<<EOF
		</ul>
		
EOF;
				//startif
				if ( $data['login_installed'] )
				{		

				$IPBHTML .= <<<EOF
					<ul style="position: absolute; display: none; z-index: 9999;" class="acp-menu" id="menu{$data['login_id']}_menucontent">
						<li style="z-index: 10000;"class='icon export'><a style="z-index: 10000;" href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_export&amp;login_id={$data['login_id']}'>{$this->lang->words['t_l_export']}</a></li>
						<li style="z-index: 10000;"class='icon delete'><a style="z-index: 10000;" href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_uninstall&amp;login_id={$data['login_id']}'>{$this->lang->words['t_l_uninstall']}</a></li>
						<li style="z-index: 10000;"class='icon view'><a style="z-index: 10000;" href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_diagnostics&amp;login_id={$data['login_id']}'>{$this->lang->words['tol_diagnostics']}</a></li>
					</ul>
EOF;
				}//endif
				$IPBHTML .= <<<EOF
			</td>
		</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Cache manager popup screen
 *
 * @param	string		Title
 * @param	string		Cache content
 * @return	string		HTML
 */
public function cache_pop_up($title, $content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div style='padding:4px'>
<h2>{$title}</h2>
<div style='padding: 10px'>
	<pre>{$content}</pre>
</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Wrapper for a cache entry
 *
 * @param	array 		Caches
 * @param	int			Total caches
 * @return	string		HTML
 */
public function cache_entry_wrapper( $caches, $total, $cacheContent=array() ) {

$IPBHTML = "";
//--starthtml--//

$_applications = array_merge( array( 'global' => array( 'app_title' => $this->lang->words['tol_global_caches'] ) ), ipsRegistry::$applications );
$__default_tab = 'global';

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['tol_cache_management']}</h2>
	
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=cache_recache&amp;id=__all__&amp;__notabsave=1'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='' /> {$this->lang->words['tol_recache_all']}</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=globalCachesRecache'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='' /> {$this->lang->words['gcaches_rebuild_cache']}</a>
			</li>
EOF;

if( ALLOW_FURLS )
{
$IPBHTML .= <<<EOF
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;module=applications&amp;section=applications&amp;do=seoRebuild'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='' /> {$this->lang->words['rebuild_furl_link']}</a>
			</li>
EOF;
}

$IPBHTML .= <<<EOF
		</ul>
	</div>
</div>
<br />
EOF;

/* CONTENT CACHE */
if ( count( $cacheContent ) )
{
	$this->lang->words['cc_remove_x_posts'] = sprintf( $this->lang->words['cc_remove_seven'], $this->settings['cc_posts'] );
	$this->lang->words['cc_remove_y_posts'] = sprintf( $this->lang->words['cc_remove_seven'], $this->settings['cc_sigs'] );
	
	$IPBHTML .= <<<EOF
	<div class='acp-box'>
		<h3>{$this->lang->words['cc_header']}</h3>
		
		<table class='ipsTable double_pad'>
			<tr class='ipsControlRow'>
				<td width='40%'>
					<strong>{$this->lang->words['cc_posts']}</strong>
				</td>
				<td width='35%'>
				   <strong>{$cacheContent['cachedPosts']['count']}</strong> {$this->lang->words['cache__of']} <strong>{$cacheContent['posts']['count']}</strong> {$this->lang->words['cache__posts']} ({$cacheContent['postPercent']}%)
				</td>
				<td class='center'>
					<ul class='ipsControlStrip'>
						<li class='i_delete ipbmenu' id='menu-cc_posts'><a href='#'>&nbsp;</a></li>
					</ul>
					<ul class='acp-menu' id='menu-cc_posts_menucontent' style='display: none'>
						<li class='icon delete'><a href='{$this->settings['base_url']}{$this->form_code}do=contentCache&amp;type=post&amp;method=seven'>{$this->lang->words['cc_remove_x_posts']}</a></li>
						<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=contentCache&amp;type=post&amp;method=all")'>{$this->lang->words['cc_remove_all']}...</a></li>
					</ul>
				</td>
			</tr>
			<tr class='ipsControlRow'>
				<td width='40%'>
					<strong>{$this->lang->words['cc_sigs']}</strong>
				</td>
				<td>
					<strong>{$cacheContent['cachedSigs']['count']}</strong> of <strong>{$cacheContent['members']['count']}</strong> {$this->lang->words['cache__signatures']} ({$cacheContent['sigPercent']}%)
				</td>
				<td style='text-align: center'>
					<ul class='ipsControlStrip'>
						<li class='i_delete ipbmenu' id='menu-cc_sigs'><a href='#'>&nbsp;</a></li>
					</ul>
					<ul class='acp-menu' id='menu-cc_sigs_menucontent' style='display: none'>
						<li class='icon delete'><a href='{$this->settings['base_url']}{$this->form_code}do=contentCache&amp;type=sig&amp;method=seven'>{$this->lang->words['cc_remove_y_posts']}</a></li>
						<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=contentCache&amp;type=sig&amp;method=all")'>{$this->lang->words['cc_remove_all']}...</a></li>
					</ul>
				</td>
			</tr>
		</table>
	</div>
	<br />
EOF;
}

$IPBHTML .= <<<EOF
<div class='acp-box'>
	<h3>{$this->lang->words['tol_caches']}</h3>
	<div id='tabstrip_caches' class='ipsTabBar with_left with_right'>
		<span class='tab_left'>&laquo;</span>
		<span class='tab_right'>&raquo;</span>
		<ul>
EOF;

$_default_tab = 'global';

foreach( $_applications as $app_dir => $app_data )
{
	if ( ipsRegistry::$request['cacheapp'] AND $app_dir == ipsRegistry::$request['cacheapp'] )
	{
		$_default_tab = $app_dir;
	}
	
	if ( is_array( $caches[ $app_dir ] ) and count( $caches[ $app_dir ] ) )
	{
$IPBHTML .= <<<EOF
		<li id='tab_{$app_dir}'>{$app_data['app_title']}</li>
EOF;
	}
}

$IPBHTML .= <<<EOF
		</ul>
	</div>

<div id='tabContent' class='ipsTabBar_content'>
EOF;

foreach( $_applications as $app_dir => $app_data )
{
	if ( is_array( $caches[ $app_dir ] ) and count( $caches[ $app_dir ] ) )
	{
$IPBHTML .= <<<EOF
	<div id='tab_{$app_dir}_content'>
		<table class='ipsTable double_pad'>
			<tr>
				<th style='width: 60%'>Cache</th>
				<th style='width: 10%; text-align: center'>{$this->lang->words['tol_size']}</th>
				<th>{$this->lang->words['tol_init_state']}</th>
				<th class='col_buttons'>&nbsp;</th>
			</tr>
			
EOF;
		foreach( $caches[ $app_dir ] as $data )
		{
$IPBHTML .= <<<EOF
<tr class='ipsControlRow'>
	<td>
		<img src='{$this->settings['skin_acp_url']}/images/icons/database.png' />&nbsp;&nbsp;
		<a href='#' onclick='return acp.openWindow("{$this->settings['base_url']}{$this->form_code}do=cache_view&amp;id={$data['cache_name']}&amp;cache_app={$app_dir}", 400, 600)'>
			<strong class='larger_text'>{$data['cache_name']}</strong>
		</a>
	</td>
	<td style='text-align: center'><span class='desctext'>{$data['_cache_size']}</span></td>
	<td>
EOF;

if ( $data['default_load'] AND $data['allow_unload'] )
{
$IPBHTML .= <<<EOF
<img src='{$this->settings['skin_acp_url']}/images/folder_components/cache/cache-loadtime.png' title='{$this->lang->words['tol_loaded_on_initialization']}' />
EOF;
}
else if ( $data['default_load'] AND ! $data['allow_unload'] )
{
$IPBHTML .= <<<EOF
<img src='{$this->settings['skin_acp_url']}/images/folder_components/cache/cache-loadtime-set.png' title='{$this->lang->words['tol_loaded_on_initialization_canno']}' />
EOF;
}
else
{
$IPBHTML .= <<<EOF
<img src='{$this->settings['skin_acp_url']}/images/folder_components/cache/cache-loadtime-none.gif' title='{$this->lang->words['tol_not_loaded_on_initialization']}' />
EOF;
}
$IPBHTML .= <<<EOF

	 </td>
	<td class='col_buttons'>
		<ul class='ipsControlStrip'>
			<li class='i_refresh'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=cache_recache&amp;id={$data['cache_name']}&amp;cacheapp={$app_dir}' title='{$this->lang->words['tol_recache_cache']}'>{$this->lang->words['tol_recache_cache']}</a>
			</li>
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
}

$IPBHTML .= <<<EOF
</div>
<div class='acp-actionbar'>
	{$this->lang->words['tol_total_cache_size']}: {$total}
</div>
</div>

<script type='text/javascript'>
	jQ("#tabstrip_caches").ipsTabBar({tabWrap: "#tabContent", defaultTab: 'tab_{$_default_tab}'});
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

}