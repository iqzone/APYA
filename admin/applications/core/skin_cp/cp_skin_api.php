<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * API users skin file
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
 
class cp_skin_api
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
 * View api log details
 *
 * @param	array 		Log record
 * @return	string		HTML
 */
public function api_log_detail( $log ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='acp-box'>
	<h3>{$this->lang->words['a_detail']}</h3>
	<table class='ipsTable'>
		<tr>
			<th colaspan='2'>{$this->lang->words['a_basics']}</th>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['a_key']}</strong></td>
			<td class='field_field'>{$log['api_log_key']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['a_ip']}</strong></td>
			<td class='field_field'>{$log['api_log_ip']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['a_time']}</strong></td>
			<td class='field_field'>{$log['_api_log_date']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['a_success']}</strong></td>
			<td class='field_field'><img src='{$this->settings['skin_acp_url']}/images/icons/{$log['_api_log_allowed']}' alt='-' /></td>
		</tr>
		<tr>
			<th colaspan='2'>{$this->lang->words['a_formdata']}</th>
		</tr>
		<tr>
			<td colspan='2' class='field_field'>
				<div style='border:1px solid black;background-color:#FFF;padding:4px;white-space:pre;height:400px;overflow:auto'>
					{$log['_api_log_query']}
				</div>
			</td>
		</tr>
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * View API login logs
 *
 * @param	array 		Rows
 * @param	string 		Page links
 * @return	string		HTML
 */
public function api_login_view( $logs, $links ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['a_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['a_requestlog']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='1%'>&nbsp;</th>
			<th width='30%'>{$this->lang->words['a_key']}</th>
			<th width='20%'>{$this->lang->words['a_ip']}</th>
			<th width='44%' align='center'>{$this->lang->words['a_date']}</th>
			<th width='5%' align='center'>{$this->lang->words['a_status']}</th>
			<th width='5%' align='center'>{$this->lang->words['a_log']}</th>
		</tr>
EOF;

if ( is_array( $logs ) AND count( $logs ) )
{
	foreach( $logs as $r )
	{
$IPBHTML .= <<<EOF
		<tr>
			<td width='1%' valign='middle'>
				<img src='{$this->settings['skin_acp_url']}/images/folder_components/xmlrpc/log_row.png' alt='-' />
			</td>
			<td><strong>{$r['api_log_key']}</strong></td>
			<td><div class='desctext'>{$r['api_log_ip']}</div></td>
			<td>{$r['_api_log_date']}</td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$r['_api_log_allowed']}' alt='-' /></td>
			<td width='1' valign='middle'>
				<a href='#' onclick="return acp.openWindow('{$this->settings['base_url']}{$this->form_code}&amp;do=log_view_detail&amp;api_log_id={$r['api_log_id']}', 800, 600)" title='{$this->lang->words['a_viewdetails']}'><img src='{$this->settings['skin_acp_url']}/images/folder_components/index/view.png' alt='-' /></a>
			</td>
		</tr>
EOF;
	}
}
$IPBHTML .= <<<EOF
	</table>
	<div class='acp-actionbar'>
		<div class="left">{$links}</div>
		<br class='clear' />
	</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * List the api users
 *
 * @param	array 		Rows
 * @return	string		HTML
 */
public function api_list( $api_users ) {

$IPBHTML = "";
//--starthtml--//

$status = $this->settings['xmlrpc_enable'] ? "<span class='ipsBadge badge_green'>{$this->lang->words['perf_on']}</span>" : "<a href='{$this->settings['base_url']}&amp;module=settings&amp;section=settings&amp;do=setting_view&amp;conf_title_keyword=xmlrpcapi'><span class='ipsBadge badge_red'>{$this->lang->words['perf_off']}</span></a>";

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['a_users']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='non_button' style='float: right !important;'>
				{$status} &nbsp;<strong>{$this->lang->words['xmlrpc_status']}</strong>&nbsp;&nbsp;
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=api_add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['a_create']}</a>
			</li>
		</ul>
	</div>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['a_users']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th width='40%'>{$this->lang->words['a_user']}</th>
			<th width='30%'>{$this->lang->words['a_key']}</th>
			<th width='20%'>{$this->lang->words['a_ip']}</th>
			<th class='col_buttons'></th>
		</tr>
EOF;

if ( count( $api_users ) )
{
	foreach( $api_users as $user )
	{
$IPBHTML .= <<<EOF
		<tr class='ipsControlRow'>
			<td><strong>{$user['api_user_name']}</strong>
			<td><strong style='font-size:14px'>{$user['api_user_key']}</strong>
			<td><strong>{$user['api_user_ip']}</strong>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=api_edit&amp;api_user_id={$user['api_user_id']}'>{$this->lang->words['a_edit']}</a></li>
EOF;
	
	if ( $this->registry->class_permissions->checkPermission( 'api_remove' ) )
	{
		$IPBHTML .= <<<EOF
					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=api_remove&amp;api_user_id={$user['api_user_id']}");'>{$this->lang->words['a_remove']}</a></li>
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
			<td colspan='5' class='no_messages'>
				{$this->lang->words['a_nousers']} <a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=api_add' class='mini_button'>{$this->lang->words['a_createone']}</a>
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
 * Form to add/edit an API user
 *
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		API user record
 * @param	string		Type (add|edit)
 * @param	array 		Permission types
 * @return	string		HTML
 */
public function api_form( $form, $title, $formcode, $button, $api_user, $type, $permissions ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form id='mainform' action='{$this->settings['base_url']}{$this->form_code}&amp;do={$formcode}&amp;api_user_id={$api_user['api_user_id']}' method='post'>
	<div class='acp-box'>
 		<h3>{$title}</h3>
		
 		<table class='ipsTable double_pad'>
			<tr>
				<th colspan='2'>{$this->lang->words['a_userbasics']}</th>
			</tr>
EOF;

if ( $type == 'add' )
{
$IPBHTML .= <<<EOF
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['a_userkey']}</strong>
				</td>
				<td class='field_field'>
					<input type='hidden' name='api_user_key' value='{$form['_api_user_key']}' />
					<strong>{$form['_api_user_key']}</strong><br />
					<span class='desctext'>{$this->lang->words['a_key_info']}</span>
				</td>
			</tr>
EOF;
}

$IPBHTML .= <<<EOF
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['a_usertitle']}</strong>
				</td>
				<td class='field_field'>
					{$form['api_user_name']}<br />
					<span class='desctext'>{$this->lang->words['a_usertitle_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['a_restrictip']}</strong>
				</td>
				<td class='field_field'>
					{$form['api_user_ip']}<br />
					<span class='desctext'>{$this->lang->words['a_restrictip_info']}</span>
				</td>
			</tr>
			<tr>
				<th colspan='2'>{$this->lang->words['a_grant_functions']}</th>
			</tr>
		</table>
EOF;

if ( is_array( $permissions ) AND count( $permissions ) )
{
	$IPBHTML .= <<<HTML
		<div id='tabstrip_api' class='ipsTabBar with_left with_right'>
			<span class='tab_left'>&laquo;</span>
			<span class='tab_right'>&raquo;</span>
			<ul>
HTML;

	foreach( $permissions as $key => $data )
	{
$IPBHTML .= <<<EOF
				<li id='tab_{$key}'>{$data['title']}</li>
EOF;
	}

	$IPBHTML .= <<<EOF
			</ul>
		</div>
		
		<div id='tabstrip_api_content' class='ipsTabBar_content'>
EOF;

	foreach( $permissions as $key => $data )
	{
$IPBHTML .= <<<EOF
			<div id='tab_{$key}_content'>
				<table class='ipsTable'>
EOF;
		if ( is_array( $permissions[ $key ]['form_perms'] ) AND ( $permissions[ $key ]['form_perms'] ) )
		{
			foreach( $permissions[ $key ]['form_perms'] as $perm => $_data )
			{
$IPBHTML .= <<<EOF
					<tr>
						<td class='field_title'>
							<!--<strong class='title'>{$this->lang->words['a_allowaccess']}</strong>-->
						</td>
						<td class='field_field'>
							{$_data['form']} &nbsp;&nbsp;<strong>{$_data['title']}</strong>
						</td>
					</tr>
EOF;
			}
		}
$IPBHTML .= <<<EOF
				</table>
			</div>
EOF;
	}

}

$IPBHTML .= <<<EOF
		</div>
		<script type='text/javascript'>
			jQ("#tabstrip_api").ipsTabBar({tabWrap: "#tabstrip_api_content"});
		</script>
		</script>
		<div class='acp-actionbar'>
			<input type='submit' value='{$button}' class='button primary' />
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

}