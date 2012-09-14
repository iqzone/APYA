<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP members skin file
 * Last Updated: $Date: 2012-05-31 10:03:54 -0400 (Thu, 31 May 2012) $
 * </pre>
 *
 * @author 		$Author: AndyMillne $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10834 $
 *
 */
 
class cp_skin_member
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
 * Member editing screen
 *
 * @param	array 		Member data
 * @param	array 		Content
 * @param	string		Menu
 * @param	array 		Notification data
 * @return	string		HTML
 */
public function member_view( $member, $content, $menu, $notifications ) {

// Let's get to work..... :/
$_m_groups = array();
$_m_groups_others = array();

$member['_cache']	= @unserialize( $member['members_cache'] );

foreach( ipsRegistry::cache()->getCache('group_cache') as $id => $data )
{
	// If we are viewing our own profile, don't show non admin groups as a primary group option to prevent the user from
	// accidentally removing their own ACP access.  Groups without ACP access can still be selected as secondary groups.
	if ( $member['member_id'] == $this->memberData['member_id'] )
	{
		//-----------------------------------------
		// If we can't access cp via primary group
		//-----------------------------------------
		
		if( !$this->caches['group_cache'][ $member['member_group_id'] ]['g_access_cp'] )
		{
			//-----------------------------------------
			// Can this group access cp?
			//-----------------------------------------
			
			if ( ! $data['g_access_cp'] )
			{
				$_m_groups[] = array( $data['g_id'], $data['g_title'] );
				$_m_groups_others[] = array( $data['g_id'], $data['g_title'] );
			}
			
			//-----------------------------------------
			// It can?
			//-----------------------------------------
			
			else
			{
				$_m_groups_others[] = array( $data['g_id'], $data['g_title'] );
			}	
		}
		
		//-----------------------------------------
		// We can access acp, so whatever
		//-----------------------------------------
		
		else
		{
			if ( ! $data['g_access_cp'] )
			{
				$_m_groups_others[] = array( $data['g_id'], $data['g_title'] );
			}
			else
			{
				$_m_groups[] = array( $data['g_id'], $data['g_title'] );
				$_m_groups_others[] = array( $data['g_id'], $data['g_title'] );
			}	
		}
	}
	else
	{
		$_m_groups[] = array( $data['g_id'], $data['g_title'] );
		$_m_groups_others[] = array( $data['g_id'], $data['g_title'] );
	}
}

$years		= array( array( 0, '----' ) );
$months		= array( array( 0, '--------' ) );
$days		= array( array( 0, '--' ) );

foreach( range( 1, 31 ) as $_day )
{
	$days[] = array( $_day, $_day );
}

foreach( array_reverse( range( date( 'Y' ) - 100, date('Y') ) ) as $_year )
{
	$years[] = array( $_year, $_year );
}

foreach( range( 1, 12 ) as $_month )
{
	$months[] = array( $_month, $this->lang->words['M_' . $_month ] );
}

$time_zones = array();

foreach( $this->lang->words as $k => $v )
{
	if( strpos( $k, 'time_' ) === 0 )
	{
		if( preg_match( "/[\-0-9]/", substr( $k, 5 ) ) )
		{
			$offset = floatval( substr( $k, 5 ) );

			$time_zones[] = array( $offset, $v );
		}
	}
}

$languages	= array();

foreach( ipsRegistry::cache()->getCache( 'lang_data' ) as $language )
{
	$languages[] = array( $language['lang_id'], $language['lang_title'] );
}

$pm_options = array(
						0 => array( 0, $this->lang->words['mem_edit_pm_no'] ),
						1 => array( 1, $this->lang->words['mem_edit_pm_yes'] ),
						2 => array( 2, $this->lang->words['mem_edit_pm_yes_really'] ),
				   );

$_skin_list					= $this->registry->output->generateSkinDropdown();
array_unshift( $_skin_list, array( 0, $this->lang->words['sm_skinnone'] ) );

$skinList					= ipsRegistry::getClass('output')->formDropdown( "skin", $_skin_list, $member['skin'] );

$form_member_group_id		= ipsRegistry::getClass('output')->formDropdown( "member_group_id", $_m_groups, $member['member_group_id'] );
$form_mgroup_others			= ipsRegistry::getClass('output')->formMultiDropdown( "mgroup_others[]", $_m_groups_others, explode( ",", $member['mgroup_others'] ), 8, 'mgroup_others' );
$form_title					= ipsRegistry::getClass('output')->formInput( "title", $member['title'] );
$form_warn					= ipsRegistry::getClass('output')->formInput( "warn_level", $member['warn_level'] );
$_form_year					= ipsRegistry::getClass('output')->formDropdown( "bday_year", $years, $member['bday_year'] );
$_form_month				= ipsRegistry::getClass('output')->formDropdown( "bday_month", $months, $member['bday_month'] );
$_form_day					= ipsRegistry::getClass('output')->formDropdown( "bday_day", $days, $member['bday_day'] );
$form_time_offset			= ipsRegistry::getClass('output')->formDropdown( "time_offset", $time_zones, $member['time_offset'] ? floatval( $member['time_offset'] ) : 0 );
$form_auto_dst 				= ipsRegistry::getClass('output')->formCheckbox( "dstCheck", $member['members_auto_dst'], 1, "dst", "onclick='toggle_dst()'" );
$form_dst_now 				= ipsRegistry::getClass('output')->formCheckbox( "dstOption", $member['dst_in_use'], 1, "dstManual" );
$form_language				= ipsRegistry::getClass('output')->formDropdown( "language", $languages, $member['language'] );
$form_allow_admin_mails		= ipsRegistry::getClass('output')->formYesNo( "allow_admin_mails", $member['allow_admin_mails'] );
$form_members_disable_pm	= ipsRegistry::getClass('output')->formDropdown( "members_disable_pm", $pm_options, $member['members_disable_pm'] );
$form_view_sig				= ipsRegistry::getClass('output')->formYesNo( "view_sigs", $member['view_sigs'] );
$form_reputation_points		= ipsRegistry::getClass('output')->formInput( 'pp_reputation_points', $member['pp_reputation_points'] );
$bw_no_status_update		= ipsRegistry::getClass('output')->formYesNo( "bw_no_status_update", $member['bw_no_status_update'] );
$bw_disable_customization	= ipsRegistry::getClass('output')->formYesNo( "bw_disable_customization", $member['bw_disable_customization'] );
$form_uploader				= ipsRegistry::getClass('output')->formDropdown( "member_uploader", array( array( 'flash', $this->lang->words['mem__flashuploader'] ), array( 'default', $this->lang->words['mem__defuploader'] ) ), $member['member_uploader'] );
$form_popup					= ipsRegistry::getClass('output')->formYesNo( "show_notification_popup", $member['_cache']['show_notification_popup'] );
$form_autotrack				= ipsRegistry::getClass('output')->formYesNo( "auto_track", $member['auto_track'] ? 1 : 0 );
$form_autotrackmthd			= ipsRegistry::getClass('output')->formDropdown( "auto_track_method", array( 
																									array( 'none', $this->lang->words['mem__auto_none'] ), 
																									array( 'immediate', $this->lang->words['mem__auto_immediate'] ),
																									array( 'offline', $this->lang->words['mem__auto_delayed'] ),
																									array( 'daily', $this->lang->words['mem__auto_daily'] ),
																									array( 'weekly', $this->lang->words['mem__auto_weekly'] ),
																									), $member['auto_track'] );

$secure_key = ipsRegistry::getClass('adminFunctions')->getSecurityKey();

$ban_member_text	= $member['member_banned'] ? $this->lang->words['sm_unban'] : $this->lang->words['sm_ban'];

$spam_member_text	= $member['bw_is_spammer'] ? $this->lang->words['sm_unspam'] : $this->lang->words['sm_spam'];

$bw_disable_tagging		= ipsRegistry::getClass('output')->formYesNo( "bw_disable_tagging", $member['bw_disable_tagging'] );
$bw_disable_prefixes	= ipsRegistry::getClass('output')->formYesNo( "bw_disable_prefixes", $member['bw_disable_prefixes'] );

//-----------------------------------------
// Comments and friends..
//-----------------------------------------
$pp_visitors		= ipsRegistry::getClass('output')->formYesNo( "pp_setting_count_visitors", $member['pp_setting_count_visitors'] );
$pp_enable_comments	= ipsRegistry::getClass('output')->formYesNo( "pp_setting_count_comments", $member['pp_setting_count_comments'] );
$pp_enable_friends	= ipsRegistry::getClass('output')->formYesNo( "pp_setting_count_friends", $member['pp_setting_count_friends'] );

$_commentsApprove	= array(
						array( '0', $this->lang->words['sm_comments_app_none'] ),
						array( '1', $this->lang->words['sm_comments_app_on'] ),
						);
						
$_friendsApprove	= array(
						array( '0', $this->lang->words['sm_friends_app_none'] ),
						array( '1', $this->lang->words['sm_friends_app_on'] ),
						);

$pp_comments_approve= ipsRegistry::getClass('output')->formDropdown( "pp_setting_moderate_comments", $_commentsApprove, $member['pp_setting_moderate_comments'] );
$pp_friends_approve	= ipsRegistry::getClass('output')->formDropdown( "pp_setting_moderate_friends", $_friendsApprove, $member['pp_setting_moderate_friends'] );

$suspend_date		= '';

if( $member['temp_ban'] )
{

	$s_ban			= IPSMember::processBanEntry( $member['temp_ban'] );
	$suspend_date	= "<div class='warning'>" . $this->lang->words['member_supsended_til'] . ' ' . ipsRegistry::getClass('class_localization')->getDate( $s_ban['date_end'], 'LONG', 1 ) . "</div>";
}
			
$IPBHTML = "";

$IPBHTML .= <<<HTML
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.members.js"></script>

<div class='section_title'>
	<h2>{$this->lang->words['editing_member']} <a href='{$this->registry->output->buildSeoUrl( "showuser={$member['member_id']}", 'public', $member['members_seo_name'], 'showuser' )}' target='_blank'>{$member['members_display_name']}</a></h2>
	<ul class='context_menu'>
HTML;
if( $this->registry->getClass('class_permissions')->checkPermission( 'member_login', 'members', 'members' ) )
{
	if ( IPSMember::isInactive( $member ) )
	{
		$IPBHTML .= <<<HTML
		<li class='disabled'>
			<a href="#" onclick='alert("{$this->lang->words['member_login_ban_or_spam']}")'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/key.png' alt='{$this->lang->words['member_login']}' />
				{$this->lang->words['member_login']}
			</a>
		</li>
HTML;
	}
	else
	{
		$IPBHTML .= <<<HTML
		<li>
			<a href="{$this->settings['base_url']}app=members&amp;module=members&amp;section=members&amp;do=member_login&amp;member_id={$member['member_id']}" target="_blank">
				<img src='{$this->settings['skin_acp_url']}/images/icons/key.png' alt='{$this->lang->words['member_login']}' />
				{$this->lang->words['member_login']}
			</a>
		</li>
HTML;
	}
}
$IPBHTML .= <<<HTML
		<li>
			<a href='#' id='MF__ban2' title='{$this->lang->words['title_ban']}'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/user_warn.png' alt='{$this->lang->words['title_ban']}' />
				{$ban_member_text}
			</a>
			
			<script type='text/javascript'>
				$('MF__ban2').observe('click', acp.members.banManager.bindAsEventListener( this, "app=members&amp;module=ajax&amp;section=editform&amp;do=show&amp;name=inline_ban_member&amp;member_id={$member['member_id']}" ) );
			</script>
		</li>
		<li>
			<a href='#' class='ipbmenu' id='member_tasks' title='{$this->lang->words['title_tasks']}'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' /> {$this->lang->words['mem_tasks']} <img src='{$this->settings['skin_acp_url']}/images/useropts_arrow.png' /></a>
		</li>
		<li class='closed'>
			<a href="#" title='{$this->lang->words['title_delete']}' onclick="return acp.confirmDelete( '{$this->settings['base_url']}app=members&amp;module=members&amp;section=members&amp;do=member_delete&amp;member_id={$member['member_id']}')">
				<img src='{$this->settings['skin_acp_url']}/images/icons/delete.png' alt='{$this->lang->words['title_delete']}' />
				{$this->lang->words['form_deletemember']}
			</a>
		</li>
	</ul>
</div>

<ul class='ipbmenu_content' id='member_tasks_menucontent' style='display: none'>
	<li>
		<img src='{$this->settings['skin_acp_url']}/images/icons/flag_red.png' alt='{$spam_member_text}' /> <a style='text-decoration: none' href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toggleSpam&amp;member_id={$member['member_id']}&amp;secure_key={$secure_key}' title='{$this->lang->words['title_spam']}'>{$spam_member_text}</a>
	</li>
HTML;

if( $member['member_group_id'] == $this->settings['auth_group'] )
{
	$IPBHTML .= <<<HTML
		<li>
			<img src='{$this->settings['skin_acp_url']}/images/icons/tick.png' alt='{$this->lang->words['title_validate']}' /> <a style='text-decoration: none' href="{$this->settings['base_url']}app=members&amp;module=members&amp;section=tools&amp;do=do_validating&amp;mid_{$member['member_id']}=1&amp;type=approve&amp;_return={$member['member_id']}" title='{$this->lang->words['title_validate']}'>{$this->lang->words['button_validate']}</a>
		</li>
HTML;
}

if( is_array($menu) AND count($menu) )
{
	foreach( $menu as $app => $link )
	{
		if( is_array($link) AND count($link) )
		{
			foreach( $link as $alink )
			{
				$img = $alink['img'] ? $alink['img'] : $this->settings[ 'skin_acp_url' ] . '/images/icons/user.png';
				
				$thisLink = $alink['js'] ? 'href="#" onclick="' . $alink['url'] . '"' : "href='{$this->settings[ '_base_url' ]}app={$app}&amp;{$alink['url']}&amp;member_id={$member['member_id']}'";
				
				$IPBHTML .= <<<HTML
					<li><img src='{$img}' alt='-' /> <a {$thisLink} style='text-decoration: none' >{$alink['title']}</a></li>
HTML;
			}
		}
	}
}

$IPBHTML .= <<<HTML
</ul>
{$suspend_date}
<br style='clear: both' />
HTML;

$_public	= PUBLIC_DIRECTORY;

$IPBHTML .= <<<HTML
<div class='acp-box'>
<form style='display:block' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=member_edit&amp;member_id={$member['member_id']}&amp;secure_key={$secure_key}' method='post'>

<h3>{$this->lang->words['editing_member']}</h3>
<div id='tabstrip_members' class='ipsTabBar with_left with_right'>
	<span class='tab_left'>&laquo;</span>
	<span class='tab_right'>&raquo;</span>
	<ul>
		<li id='tab_MEMBERS_1'>{$this->lang->words['mem_tab_basics']}</li>
		<li id='tab_MEMBERS_2'>{$this->lang->words['mem_tab_profile']}</li>
		<li id='tab_MEMBERS_3'>{$this->lang->words['mem_tab_notifications']}</li>
HTML;

// Got blocks from other apps?
$IPBHTML .= implode( "\n", $content['tabs'] );

if ( $this->settings['auth_allow_dnames'] )
{
	$display_name = <<<HTML
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['mem_display_name']}</strong></td>
				<td class='field_field'>
					<span class='member_detail' id='MF__member_display_name'>{$member['members_display_name']}</span>
					<a class='change_icon' id='MF__member_display_name_popup' href='' style='cursor:pointer' title='{$this->lang->words['title_display_name']}'>{$this->lang->words['mem_change_button']}</a>
					
					<script type='text/javascript'>
						$('MF__member_display_name_popup').observe('click', acp.members.editField.bindAsEventListener( this, 'MF__member_display_name', "{$this->lang->words['sm_display']}", "app=members&amp;module=ajax&amp;section=editform&amp;do=show&amp;name=inline_form_display_name&amp;member_id={$member['member_id']}" ) );
					</script>
				</td>
			</tr>
HTML;
}
else
{
	$display_name = '';
}

/* Facebook doesn't pass a size, so in IPB we get around that by passing * as a width. This confuses the form here, so.. */
$_pp_box_width = ( $member['pp_main_width'] == '*' OR $member['pp_main_width'] < 100 ) ? '100' : $member['pp_main_width'];
$_pp_max_width = ( $member['pp_main_width'] == '*' ) ? ';max-width:100px' : '';

$IPBHTML .= <<<HTML
</ul>
 </div>
<div id='tabstrip_members_content' class='ipsTabBar_content'>
	<div id='tab_MEMBERS_1_content' class='row1'>
		<div style='float: left; width: 70%'>
			<table class='ipsTable double_pad'>
				{$display_name}
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['mem_login_name']}</strong></td>
					<td class='field_field'>
						<span class='member_detail' id='MF__name'>{$member['name']}</span>
						<a href='' class='change_icon' style='cursor:pointer' id='MF__name_popup' title='{$this->lang->words['title_login_name']}'>{$this->lang->words['mem_change_button']}</a>
						<script type='text/javascript'>
							$('MF__name_popup').observe('click', acp.members.editField.bindAsEventListener( this, 'MF__name', "{$this->lang->words['sm_loginname']}", "app=members&amp;module=ajax&amp;section=editform&amp;do=show&amp;name=inline_form_name&amp;member_id={$member['member_id']}" ) );
						</script>
					</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['mem_password']}</strong></td>
					<td class='field_field'>
						
						<span class='member_detail' id='MF__password'>************</span> 
						<a href='' class='change_icon' style='cursor:pointer' id='MF__password_popup' title='{$this->lang->words['title_password']}'>{$this->lang->words['mem_change_button']}</a>
						<script type='text/javascript'>
							$('MF__password_popup').observe('click', acp.members.editField.bindAsEventListener( this, 'MF__password', "{$this->lang->words['sm_password']}", "app=members&amp;module=ajax&amp;section=editform&amp;do=show&amp;name=inline_password&amp;member_id={$member['member_id']}" ) );
						</script>
					</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['mem_email']}</strong></td>
					<td class='field_field'>
						<span class='member_detail' id='MF__email'>{$member['email']}</span> 
						<a href='' class='change_icon' style='cursor:pointer' id='MF__email_popup' title='{$this->lang->words['title_email']}'>{$this->lang->words['mem_change_button']}</a>
						<script type='text/javascript'>
							$('MF__email_popup').observe('click', acp.members.editField.bindAsEventListener( this, 'MF__email', "{$this->lang->words['sm_email']}", "app=members&amp;module=ajax&amp;section=editform&amp;do=show&amp;name=inline_email&amp;member_id={$member['member_id']}" ) );
						</script>
					</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['mem_form_title']}</strong></td>
					<td class='field_field'>
						<span id='MF__title'>{$form_title}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['mem_p_group']}</strong></td>
					<td class='field_field'>
						<span id='MF__member_group_id'>{$form_member_group_id}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['mem_s_group']}</strong></td>
					<td class='field_field'>
						<span id='MF__mgroup_others'>{$form_mgroup_others}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'><strong class'title'>{$this->lang->words['mem_warn_level']}</strong></td>
					<td class='field_field'>		
						<span id='MF__warn_level'>{$form_warn}</span>&nbsp;&nbsp;<a href='#' onclick="return acp.openWindow('{$this->settings['board_url']}/index.php?app=members&amp;module=profile&amp;section=warnings&amp;member={$member['member_id']}','980','600'); return false;" title='{$this->lang->words['sm_viewnotes']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['sm_viewnotes']}' /></a>
					</td>
				 </tr>
			</table>
		</div>
		<div class='acp-sidebar'>
			<div style='width:{$_pp_box_width}px; max-width: 125px;' id='MF__pp_photo_container'>
				<img id='MF__pp_photo' src="{$member['pp_main_photo']}" style='{$_pp_max_width};max-width:125px' />
				<br />
				<ul class='photo_options'>
HTML;

if( $member['_has_photo'] )
{
$IPBHTML .= <<<HTML
				<li><a style='float:none;width:auto;text-align:center;cursor:pointer' id='MF__removephoto' title='{$this->lang->words['mem_remove_photo']}'><img src='{$this->settings['skin_acp_url']}/images/picture_delete.png' alt='{$this->lang->words['mem_remove_photo']}' /></a></li>
				<li><a style='float:none;width:auto;text-align:center;cursor:pointer' id='MF__newphoto' title='{$this->lang->words['sm_uploadnew']}'><img src='{$this->settings['skin_acp_url']}/images/picture_add.png' alt='{$this->lang->words['sm_uploadnew']}' /></a></li>
HTML;
}
else
{
$IPBHTML .= <<<HTML
				<li><a style='float:none;width:auto;text-align:center;cursor:pointer' id='MF__newphoto' title='{$this->lang->words['sm_uploadnew']}'><img src='{$this->settings['skin_acp_url']}/images/picture_add.png' alt='{$this->lang->words['sm_uploadnew']}' /></a></li>
HTML;
}

$IPBHTML .= <<<HTML
				</ul>
				<script type='text/javascript'>
					$('MF__newphoto').observe('click', acp.members.newPhoto.bindAsEventListener( this, "app=members&amp;module=ajax&amp;section=editform&amp;do=show&amp;name=inline_form_new_photo&amp;member_id={$member['member_id']}" ) );
				</script>
			</div>
			
			<div class='sidebar_box'>
				<ul>
					<li><strong>{$this->lang->words['mem_joined']}:</strong> <span>{$member['_joined']}</span></li>
					<li><strong>{$this->lang->words['mem_ip_address_f']}:</strong> <span><a href='{$this->settings['base_url']}&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$member['ip_address']}' title='{$this->lang->words['mem_ip_title']}'>{$member['ip_address']}</a></span></li>
				</ul>
			</div>
		</div>
		<div style='clear: both;'></div>
	</div>
HTML;



$IPBHTML .= <<<HTML
	<!-- PROFILE PANE-->
	<div id='tab_MEMBERS_2_content'>
	<table class='ipsTable double_pad'>
		<tr>
			<th colspan='2'>{$this->lang->words['sm_settings']}</th>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sm_timeoffset']}</strong></td>
			<td class='field_field'>
				<span id='MF__time_offset'>{$form_time_offset}</span>
				<div class='ipsPad_top_slimmer'>{$form_auto_dst} <label for='dst'>{$this->lang->words['sm_dst_auto']}</label></div>
				<div class='ipsPad_top_slimmer' id='dst-manual'>{$form_dst_now} <label for='dstManual'>{$this->lang->words['sm_dst_now']}</label></div>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sm_langchoice']}</strong></td>
			<td class='field_field'>
				<span id='MF__language'>{$form_language}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sm_skinchoice']}</strong></td>
			<td class='field_field'>
				<span id='MF__skin'>
					{$skinList}
				</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sm_uploaderchoice']}</strong></td>
			<td class='field_field'>
				<span id='MF__uploader'>
					{$form_uploader}
				</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sm_viewsig']}</strong></td>
			<td class='field_field'>
				<span id='MF__view_sig'>{$form_view_sig}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['bw_disable_customization']}</strong></td>
			<td class='field_field'>
				<span>{$bw_disable_customization}</span>
				<div class='ipsPad_top_slimmer'><input type="checkbox" name="removeCustomization" value="1" /> {$this->lang->words['remove_custom_stuff']}
			</td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['mf_t_tagging']}<!--You're it--></th>
		</tr>
	 	<tr>
	 		<td class='field_title'><strong class='title'>{$this->lang->words['bw_disable_tagging']}</strong></td>
	 		<td class='field_field'>
	 			{$bw_disable_tagging}<br />
				<span class='desctext'>{$this->lang->words['bw_disable_tagging_desc']}</span>
	 		</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['bw_disable_prefixes']}</strong></td>
	 		</td>
	 		<td class='field_field'>
	 			{$bw_disable_prefixes}<br />
				<span class='desctext'>{$this->lang->words['bw_disable_prefixesm_desc']}</span>
	 		</td>
	 	</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['sm_profile']}</th>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sm_bday']}</strong></td>
			<td class='field_field'>
				<span id='MF__birthday'>{$_form_month} {$_form_day} {$_form_year}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['frm_no_status']}</strong></td>
			<td class='field_field'>
				<span id='MF__frm_no_status'>{$bw_no_status_update}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sm_reputation']}</strong></td>
			<td class='field_field'>
				<span id='MF__pp_reputation_points'>
					{$form_reputation_points} 
				</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sm_latest_visitors']}</strong></td>
			<td class='field_field'>
				<span id='MF__visitors'>{$pp_visitors}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sm_enable_comments']}</strong></td>
			<td class='field_field'>
				<span id='MF__profile_comments'>{$pp_enable_comments}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sm_approve_comments']}</strong></td>
			<td class='field_field'>
				<span id='MF__comments_approve'>{$pp_comments_approve}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sm_friends_profile']}</strong></td>
			<td class='field_field'>
				<span id='MF__profile_friends'>{$pp_enable_friends}</span>
			</td>						
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['sm_approve_friends']}</strong></td>
			<td class='field_field'>
				<span id='MF__friends_approve'>{$pp_friends_approve}</span>
			</td>						
		</tr>
	</table>
HTML;
	if ( is_array( $member['custom_fields'] ) AND count( $member['custom_fields'] ) )
	{
		$IPBHTML .= <<<HTML
	<table class='ipsTable double_pad'>
		<tr>
			<th colspan='2'>{$this->lang->words['sm_custom']}</th>
		</tr>
HTML;
		foreach( $member['custom_fields'] as $_id => $_data )
		{
			$IPBHTML .= <<<HTML
		<tr>
			<td class='field_title'><strong class='title'>{$_data['name']}</strong></td>
			<td class='field_field'>
				<span id='custom_fields_{$_id}'>{$_data['data']}</span>
			</td>
		</tr>
HTML;
		}

		$IPBHTML .= <<<HTML
	</table>
HTML;
	}

$IPBHTML .= <<<HTML
	<!-- / CUSTOM FIELDS PANE -->
	
	<!-- SIGNATURE-->
	<table class='ipsTable double_pad'>
		<tr>
			<th colspan='2'>{$this->lang->words['sm_sigtab']}</th>
		</tr>
		<tr>
			<td>
				<div class='tablerow1 has_editor'>
					<div class='editor'>
						{$member['signature_editor']}
					</div>
				</div>
			</td>
		</tr>
	</table>
	<!-- / SIGNATURE-->
		
	<!-- ABOUT ME-->
	<table class='ipsTable double_pad'>
		<tr>
			<th>{$this->lang->words['sm_abouttab']}</th>
		</tr>
		<tr>
			<td>
				<div class='tablerow1 has_editor'>
					<div class='editor'>
						{$member['aboutme_editor']}
					</div>
				</div>
			</td>
		</tr>
	</table>
	<!-- / ABOUT ME-->
	
	</div>
	<div id='tab_MEMBERS_3_content'>
		<table class='ipsTable double_pad'>
			<tr>
				<th colspan='2'>{$this->lang->words['memt_privacysettings']}</th>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['sm_allowadmin']}</strong></td>
				<td class='field_field'>
					<span id='MF__allow_admin_mails'>{$form_allow_admin_mails}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['sm_disablepm']}</strong></td>
				<td class='field_field'>
					<span id='MF__members_disable_pm'>{$form_members_disable_pm}</span>
				</td>
			</tr>
			<tr>
				<th colspan='2'>{$this->lang->words['memt_boardprefs']}</th>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['show_notification_popup']}</strong></td>
				<td class='field_field'>
					<span id='MF__xxx'>{$form_popup}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['auto_track']}</strong></td>
				<td class='field_field'>
					<span id='MF__xxx'>{$form_autotrack}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['auto_track_type']}</strong></td>
				<td class='field_field'>
					<span id='MF__xxx'>{$form_autotrackmthd}</span>
				</td>
			</tr>
		</table>
HTML;

$notifyGroups = array(
	'topics_posts'		=> array( 'new_topic', 'new_reply', 'post_quoted' ),
	'status_updates'	=> array( 'reply_your_status', 'reply_any_status', 'friend_status_update' ),
	'profiles_friends'	=> array( 'profile_comment', 'profile_comment_pending', 'friend_request', 'friend_request_pending', 'friend_request_approve' ),
	'private_msgs'		=> array( 'new_private_message', 'reply_private_message', 'invite_private_message' )
);

		
$IPBHTML .= <<<HTML
	<table class='ipsTable double_pad'>
		<tr>
			<th width='20%'>&nbsp;</th>
			<th width='20%' style='text-align: center'>{$this->lang->words['notify_type_email']}</th>
			<th width='20%' style='text-align: center'>{$this->lang->words['notify_type_inline']}</th>
			<th width='20%' style='text-align: center'>{$this->lang->words['notify_type_mobile']}</th>
		</tr>
HTML;

		foreach( $notifyGroups as $groupKey => $group )
		{
			$IPBHTML .= <<<HTML
			<th colspan='4'>
				{$this->lang->words[ 'notifytitle_' . $groupKey ]}
			</th>
HTML;
			foreach( $group as $key )
			{
				if( $notifications[ $key ] )
				{
					$IPBHTML .= <<<HTML
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['notify__' . $key]}</strong></td>
						<td align='center'>
HTML;

							if( isset( $notifications[$key]['options']['email'] ) )
							{
								$_disabled	= $notifications[$key]['disabled'] ? " disabled='disabled'" : '';
								$_selected	= ( is_array($notifications[$key]['defaults']) AND in_array('email',$notifications[$key]['defaults']) ) ? " checked='checked'" : '';
								
								$IPBHTML .= <<<HTML
								<input type='checkbox' class='input_check' id='email_{$key}' name="config_{$key}[]" value="email"{$_selected}{$_disabled} />
HTML;
							}
							else
							{
								$IPBHTML .= <<<HTML
								<input type='checkbox' class='input_check' name='' disabled='disabled' />
HTML;
							}
							
							$IPBHTML .= <<<HTML
						</td>
						<td align='center'>
HTML;

							if( isset( $notifications[$key]['options']['inline'] ) )
							{
								$_disabled	= $notifications[$key]['disabled'] ? " disabled='disabled'" : '';
								$_selected	= ( is_array($notifications[$key]['defaults']) AND in_array('inline',$notifications[$key]['defaults']) ) ? " checked='checked'" : '';
								
								$IPBHTML .= <<<HTML
								<input type='checkbox' class='input_check' id='inline_{$key}' name="config_{$key}[]" value="inline"{$_selected}{$_disabled} />
HTML;
							}
							else
							{
								$IPBHTML .= <<<HTML
								<input type='checkbox' class='input_check' name='' disabled='disabled' />
HTML;
							}
							
							$IPBHTML .= <<<HTML
						</td>
						<td align='center'>
HTML;

							if( isset( $notifications[$key]['options']['mobile'] ) )
							{
								$_disabled	= $notifications[$key]['disabled'] ? " disabled='disabled'" : '';
								$_selected	= ( is_array($notifications[$key]['defaults']) AND in_array('mobile',$notifications[$key]['defaults']) ) ? " checked='checked'" : '';
								
								$IPBHTML .= <<<HTML
								<input type='checkbox' class='input_check' id='mobile_{$key}' name="config_{$key}[]" value="mobile"{$_selected}{$_disabled} />
HTML;
							}
							else
							{
								$IPBHTML .= <<<HTML
								<input type='checkbox' class='input_check' name='' disabled='disabled' />
HTML;
							}
							
							$IPBHTML .= <<<HTML
						</td>
					</tr>
HTML;
					
					$notifications[$key]['_done'] = 1;
				}
			}
		}
		
		$_lastApp	= '';

		foreach( $notifications as $key => $_config )
		{
			if( !isset( $_config['_done'] ) && $_config['_done'] != 1 )
			{
				if( $_lastApp != $_config['app'] )
				{
					$_title		= $_config['app'] == 'core' ? $this->lang->words['notifytitle_other'] : IPSLib::getAppTitle( $_config['app'] );
					$_lastApp	= $_config['app'];
					
					$IPBHTML .= <<<HTML
			<th colspan='4'>
				{$_title}
			</th>
HTML;
				}
				
			$IPBHTML .= <<<HTML
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['notify__' . $_config['key'] ]}</strong></td>
					<td align='center'>
HTML;

							if( isset( $_config['options']['email'] ) )
							{
								$_disabled	= $_config['disabled'] ? " disabled='disabled'" : '';
								$_selected	= ( is_array($_config['defaults']) AND in_array('email',$_config['defaults']) ) ? " checked='checked'" : '';
								
								$IPBHTML .= <<<HTML
								<input type='checkbox' class='input_check' id='email_{$key}' name="config_{$key}[]" value="email"{$_selected}{$_disabled} />
HTML;
							}
							else
							{
								$IPBHTML .= <<<HTML
								<input type='checkbox' class='input_check' name='' disabled='disabled' />
HTML;
							}
							
						$IPBHTML .= <<<HTML
					</td>
					<td align='center'>
HTML;

						if( isset( $_config['options']['inline'] ) )
						{
							$_disabled	= $_config['disabled'] ? " disabled='disabled'" : '';
							$_selected	= ( is_array($_config['defaults']) AND in_array('inline',$_config['defaults']) ) ? " checked='checked'" : '';
							
							$IPBHTML .= <<<HTML
							<input type='checkbox' class='input_check' id='inline_{$key}' name="config_{$key}[]" value="inline"{$_selected}{$_disabled} />
HTML;
						}
						else
						{
							$IPBHTML .= <<<HTML
							<input type='checkbox' class='input_check' name='' disabled='disabled' />
HTML;
						}
						
						$IPBHTML .= <<<HTML
					</td>
					<td align='center'>
HTML;

						if( isset( $_config['options']['mobile'] ) )
						{
							$_disabled	= $_config['disabled'] ? " disabled='disabled'" : '';
							$_selected	= ( is_array($_config['defaults']) AND in_array('mobile',$_config['defaults']) ) ? " checked='checked'" : '';
							
							$IPBHTML .= <<<HTML
							<input type='checkbox' class='input_check' id='mobile_{$key}' name="config_{$key}[]" value="mobile"{$_selected}{$_disabled} />
HTML;
						}
						else
						{
							$IPBHTML .= <<<HTML
							<input type='checkbox' class='input_check' name='' disabled='disabled' />
HTML;
						}
						
						$IPBHTML .= <<<HTML
					</td>
				</tr>
HTML;
			}
		}

	$IPBHTML .= <<<HTML
	</table>
</div>
HTML;

// Got blocks from other apps?
$IPBHTML .= implode( "\n", $content['area'] );

$IPBHTML .= <<<HTML
</div>
	<div class='acp-actionbar'>
		<input class='button primary' type='submit' value='{$this->lang->words['sm_savebutton']}' />
	</div>
</div>

<script type='text/javascript'>
	jQ("#tabstrip_members").ipsTabBar({tabWrap: "#tabstrip_members_content"});
</script>

</form>
</div>

<script type='text/javascript'>	
	if( $('MF__removephoto') )
	{
		$('MF__removephoto').observe( 'click', acp.members.removePhoto.bindAsEventListener( this, '{$member['member_id']}' ) );
	}
</script>

<script type="text/javascript">
function toggle_dst()
{
	if ( $( 'dst' ) )
	{
		if ( $( 'dst' ).checked ){
			$( 'dst-manual' ).hide();
		} else {
			$( 'dst-manual' ).show();
		}
	}
}
toggle_dst();
</script>
HTML;

return $IPBHTML;
}

/**
 * List of members
 *
 * @param	array 		Members
 * @param	string		Pages
 * @return	string		HTML
 */
public function members_list( $members, $pages='' ) {

$IPBHTML = "";
//--starthtml--//

//-----------------------------------------
// BADGE STYLEE
//-----------------------------------------

$IPBHTML .= <<<HTML
<br />
<style type='text/css'>
	.pagination .pagejump { display: none; }
</style>
{$pages}
<div class='acp-box'>
	<h3>
		<span class='right loading' id='members_loading' style='display: none'>
			<img src='{$this->settings['skin_acp_url']}/images/loading_white.gif' /> &nbsp;&nbsp;{$this->lang->words['gl_loading']}
		</span>
		{$this->lang->words['sm_members']}
	</h3>
	<div id='member_search_bar' class='clearfix'>
		<div class='left' id='m_search_info' style='display: none'>
			<span></span> {$this->lang->words['searchresultsstr']} &nbsp;&nbsp;&nbsp;<a href='#' id='m_search_cancel' class='ipsBadge badge_grey'>{$this->lang->words['memclearsearch']}</a>
		</div>
		<div class='ipsToggleBar left' id='member_types' style='overflow: hidden'>
			<ul>
				<li class='ipsActionButton active' data-type="all">
					<a href='#' title='{$this->lang->words['m_type_all_desc']}'>{$this->lang->words['m_type_all']}</a>
				</li>
HTML;
if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_banned', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
			<li class='ipsActionButton' data-type="banned">
				<a href='#' title='{$this->lang->words['m_type_banned_desc']}'>{$this->lang->words['m_type_banned']}</a>
			</li>
HTML;
}

if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_locked', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
			<li class='ipsActionButton' data-type="locked">
				<a href='#' title='{$this->lang->words['m_type_locked_desc']}'>{$this->lang->words['m_type_locked']}</a>
			</li>
HTML;
}

if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_spam', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
			<li class='ipsActionButton' data-type="spam">
				<a href='#' title='{$this->lang->words['m_type_spam_desc']}'>{$this->lang->words['m_type_spam']}</a>
			</li>
HTML;
}

if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_incomplete', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
			<li class='ipsActionButton' data-type="incomplete">
				<a href='#' title='{$this->lang->words['m_type_incomplete_desc']}'>{$this->lang->words['m_type_incomplete']}</a>
			</li>
HTML;
}

if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_validating', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
			<li class='ipsActionButton' data-type="validating">
				<a href='#' title='{$this->lang->words['m_type_validate_desc']}'>{$this->lang->words['m_type_validate']}</a>
			</li>
HTML;
}

$IPBHTML .= <<<HTML
			</ul>
		</div>
		<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=members_list' method='post' id='memberListForm' class='right'>
			<input type='hidden' name='__update' value='1' />				
			<input type='text' name='f_member_contains_text' class='textinput no_width' size='30' id='member_search' />
			<a href='#' id='advanced_member_search'>{$this->lang->words['m_search_advanced']}</a> 
		</form>
	</div>
<table class='ipsTable double_pad' id='member_list'>
	<thead>
		<tr class='member_column_titles' id='mheader_all' data-type="all">
			<th style='width: 5%'></th>
			<th style='width: 25%' class='sortable' data-key="members_display_name">
				<p>{$this->lang->words['list__dn']}<span></span></p>
			</th>
			<th style='width: 22%' class='sortable' data-key="email">
				<p>{$this->lang->words['list__email']}<span></span></p>
			</th>
			<th style='width: 18%' class='sortable active desc' data-key="joined"><p>{$this->lang->words['list__joined']}<span></span></p></th>
			<th style='width: 10%'>{$this->lang->words['list__group']}</th>
			<th style='width: 15%'>{$this->lang->words['list__ip']}</th>
			<th style='width:5%; text-align: right; padding-right: 15px'></th>
		</tr>
HTML;

if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_validating', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
		<tr class='member_column_titles' id='mheader_validating' style='display: none' data-type="validating">
			<th style='width: 5%'></th>
			<th style='width: 25%' class='sortable' data-key="members_display_name">
				<p>{$this->lang->words['list__dn']}<span></span></p>
			</th>
			<th style='width: 22%' class='sortable' data-key="email">
				<p>{$this->lang->words['list__email']}<span></span></p>
			</th>
			<th style='width: 10%'>{$this->lang->words['list__posts']}</th>
			<th style='width: 18%'>{$this->lang->words['list__info']}</th>
			<th style='width: 15%' class='sortable active desc' data-key="members_display_name"><p>{$this->lang->words['list__joined']}<span></span></p></th>
			<th style='width:5%; text-align: right; padding-right: 15px'><input type='checkbox' class='check_all' /></th>
		</tr>
HTML;
}

if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_locked', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
		<tr class='member_column_titles' id='mheader_locked' style='display: none' data-type="locked">
			<th style='width: 5%'></th>
			<th style='width: 25%' class='sortable' data-key="members_display_name">
				<p>{$this->lang->words['list__dn']}<span></span></p>
			</th>
			<th style='width: 22%' class='sortable' data-key="email">
				<p>{$this->lang->words['list__email']}<span></span></p>
			</th>
			<th style='width: 10%'>{$this->lang->words['list__posts']}</th>
			<th style='width: 10%'>{$this->lang->words['list__locked']}</th>
			<th style='width: 15%' class='sortable active desc' data-key="members_display_name"><p>{$this->lang->words['list__joined']}<span></span></p></th>
			<th style='width:5%; text-align: right; padding-right: 15px'><input type='checkbox' class='check_all' /></th>
		</tr>
HTML;
}

if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_banned', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
		<tr class='member_column_titles' id='mheader_banned' style='display: none' data-type="banned">
			<th style='width: 5%'></th>
			<th style='width: 25%' class='sortable' data-key="members_display_name">
				<p>{$this->lang->words['list__dn']}<span></span></p>
			</th>
			<th style='width: 22%' class='sortable' data-key="email">
				<p>{$this->lang->words['list__email']}<span></span></p>
			</th>
			<th style='width: 10%'>{$this->lang->words['list__posts']}</th>
			<th style='width: 15%'>{$this->lang->words['list__group']}</th>
			<th style='width: 15%' class='sortable active desc' data-key="members_display_name"><p>{$this->lang->words['list__joined']}<span></span></p></th>
			<th style='width:5%; text-align: right; padding-right: 15px'><input type='checkbox' class='check_all' /></th>
		</tr>
HTML;
}

if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_spam', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
		<tr class='member_column_titles' id='mheader_spam' style='display: none' data-type="spam">
			<th style='width: 5%'></th>
			<th style='width: 25%' class='sortable' data-key="members_display_name">
				<p>{$this->lang->words['list__dn']}<span></span></p>
			</th>
			<th style='width: 22%' class='sortable' data-key="email">
				<p>{$this->lang->words['list__email']}<span></span></p>
			</th>
			<th style='width: 10%'>{$this->lang->words['list__posts']}</th>
			<th style='width: 15%'>{$this->lang->words['list__group']}</th>
			<th style='width: 15%' class='sortable active desc' data-key="members_display_name"><p>{$this->lang->words['list__joined']}<span></span></p></th>
			<th style='width:5%; text-align: right; padding-right: 15px'><input type='checkbox' class='check_all' /></th>
		</tr>
HTML;
}

if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_incomplete', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
		<tr class='member_column_titles' id='mheader_incomplete' style='display: none' data-type="incomplete">
			<th style='width: 5%'></th>
			<th style='width: 25%' class='sortable' data-key="members_display_name">
				<p>{$this->lang->words['list__dn']}<span></span></p>
			</th>
			<th style='width: 22%' class='sortable' data-key="email">
				<p>{$this->lang->words['list__email']}<span></span></p>
			</th>
			<th style='width: 10%'>{$this->lang->words['list__group']}</th>
			<th style='width: 15%'>{$this->lang->words['list__ip']}</th>
			<th style='width: 15%' class='sortable active desc' data-key="members_display_name"><p>{$this->lang->words['list__joined']}<span></span></p></th>
			<th style='width:5%; text-align: right; padding-right: 15px'><input type='checkbox' class='check_all' /></th>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</thead>
	<tbody id='member_results'>
HTML;

if( count( $members ) )
{
	foreach( $members as $member )
	{
		switch( $this->request['type'] )
		{
			case 'all':
			default:
				$IPBHTML .= $this->memberListRow( $member );
			break;
			
			case 'spam':
				$IPBHTML .= $this->memberListRow_spam( $member );
			break;

			case 'banned':
				$IPBHTML .= $this->memberListRow_banned( $member );
			break;

			case 'locked':
				$IPBHTML .= $this->memberListRow_locked( $member );
			break;

			case 'validating':
				$IPBHTML .= $this->memberListRow_validating( $member );
			break;

			case 'incomplete':
				$IPBHTML .= $this->memberListRow_incomplete( $member );
			break;
		}
	}
}
else 
{
	$IPBHTML .= $this->memberListRow_empty();
}

$IPBHTML .= <<<HTML
	</tbody>
	<tfoot>
HTML;

if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_banned', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
		<tr id='mfooter_banned' class='disabled' data-type="banned" style='display: none'>
			<td colspan='7' style='text-align: right'>
				<strong>{$this->lang->words['m_with_checked']}</strong> <a href="#" data-action="delete" class='ipsBadge badge_red mass_action'>{$this->lang->words['m_but_delete_all']}</a> <a href="#" data-action="unban" class='ipsBadge badge_green mass_action'>{$this->lang->words['m_but_unban_all']}</a>
			</td>
		</tr>
HTML;
}

if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_spam', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
		<tr id='mfooter_spam' class='disabled' data-type="spam" style='display: none'>
			<td colspan='7' style='text-align: right'>
				<div id='s_initial'>
					<strong>{$this->lang->words['m_with_checked']}</strong> <a href="#" data-action="ban" class='ipsBadge badge_red mass_action'>{$this->lang->words['m_but_ban_all']}</a> <a href="#" data-action="unspam" class='ipsBadge badge_green mass_action'>{$this->lang->words['m_but_unflag_all']}</a>
				</div>
				<div id='s_unspam_confirm' style='display: none'>
					<strong>{$this->lang->words['m_approve']}</strong> <input type='button' id='s_unspam_yes' data-action="unspam_posts" value="{$this->lang->words['m_but_yes']}" class='realbutton' /> <input type='button' data-action="unspam" id='s_unspam_no' value="{$this->lang->words['m_but_no']}" class='realbutton' />
				</div>
				<div id='s_ban_confirm' style='display: none'>
					<strong>{$this->lang->words['m_blacklist']}</strong> <input type='button' id='s_ban_yes' data-action="ban_blacklist" value="{$this->lang->words['m_but_yes']}" class='realbutton' /> <input type='button' data-action="ban" id='s_ban_mp' value="{$this->lang->words['m_but_no']}" class='realbutton' />
				</div>
			</td>
		</tr>
HTML;
}

if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_validating', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
		<tr id='mfooter_validating' class='disabled' data-type="validating" style='display: none'>
			<td colspan='7' style='text-align: right'>
				<strong>{$this->lang->words['m_with_checked']}</strong> <a href="#" data-action="delete" class='ipsBadge badge_red mass_action'>{$this->lang->words['m_but_delete_all']}</a> <a href="#" data-action="approve" class='ipsBadge badge_green mass_action'>{$this->lang->words['m_but_approve_all']}</a> <a href="#" data-action="resend" class='ipsBadge badge_grey mass_action'>{$this->lang->words['m_but_resend_all']}</a> <a href="#" data-action="ban" class='ipsBadge badge_grey mass_action'>{$this->lang->words['m_but_ban_all']}</a> <a href="#" data-action="spam" class='ipsBadge badge_grey mass_action'>{$this->lang->words['m_but_mas_all']}</a>
			</td>
		</tr>
HTML;
}

if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_locked', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
		<tr id='mfooter_locked' class='disabled' data-type="locked" style='display: none'>
			<td colspan='7' style='text-align: right'>
				<strong>{$this->lang->words['m_with_checked']}</strong> <a href="#" data-action="delete" class='ipsBadge badge_red mass_action'>{$this->lang->words['m_but_delete_all']}</a> <a href="#" data-action="unlock" class='ipsBadge badge_green mass_action'>{$this->lang->words['m_but_unlock_all']}</a> <a href="#" data-action="ban" class='ipsBadge badge_grey mass_action'>{$this->lang->words['m_but_ban_all']}</a>
			</td>
		</tr>
HTML;
}

if( $this->registry->getClass('class_permissions')->checkPermission( 'membertools_incomplete', 'members', 'members' ) )
{
	$IPBHTML .= <<<HTML
		<tr id='mfooter_incomplete' class='disabled' data-type="incomplete" style='display: none'>
			<td colspan='7' style='text-align: right'>
				<strong>{$this->lang->words['m_with_checked']}</strong> <a href="#" data-action="delete" class='ipsBadge badge_red mass_action'>{$this->lang->words['m_but_delete_all']}</a> <a href="#" data-action="finalize" class='ipsBadge badge_green mass_action'>{$this->lang->words['m_but_finalize_all']}</a>
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</tfoot>
</table>
</div>
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=members_list' method='post' id='memberPruneMoveForm' style='display: none'>
	<input type='hidden' name='f_search_type' value='' id='f_search_type' />
	<input type='hidden' name='__update' value='0' />
</form>
<br style='clear: both' />
{$pages}
	
<script type='text/javascript'>
	jQ(document).ready(function() {
	 	memberlist.init( "{$this->settings['_base_url']}app=members&module=ajax&section=members&secure_key=" + ipb.vars['md5_hash'], "{$this->request['type']}" );
	});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * No member results
 *
 * @return	string		HTML
 */
public function memberListRow_empty() {

$IPBHTML = "";

$IPBHTML .= <<<HTML
<td class='no_messages' colspan='8'>
	{$this->lang->words['sm_nomemfound']}
</td>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Spam member loop for member list
 *
 * @param	array 		Member data
 * @return	string		HTML
 */
public function memberListRow_spam( $member ) {
$IPBHTML .= <<<HTML
	<tr id='member-{$member['member_id']}' data-mid="{$member['member_id']}" class='member_row'>
		<td><img src='{$this->settings['skin_acp_url']}/images/members/normal_alert.png' alt='*' /></td>
		<td class='member_name'>
			<a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$member['member_id']}' class='larger_text'>{$member['members_display_name']}</a>
			<span class='desctext'>{$member['ip_addresses']}</span>
		</td>
		<td>{$member['email']}</td>
		<td>{$member['posts']}</td>
		<td>
			{$member['group_title']}
		</td>
		<td>
			{$member['_joined']}
		</td>
		<td>
			<p class='member_controls'>
				<a href='#' class='ipsBadge badge_red member_action' data-action="ban" title='{$this->lang->words['form_deletemember']}'>{$this->lang->words['m_but_ban']}</a>
				<a href='#' class='ipsBadge badge_green member_action' data-action="unspam">{$this->lang->words['m_but_unflag']}</a>
				<input type='checkbox' />
			</p>
		</td>
	</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Banned member loop for member list
 *
 * @param	array 		Member data
 * @return	string		HTML
 */
public function memberListRow_banned( $member ) {
	
$IPBHTML .= <<<HTML
	<tr id='member-{$member['member_id']}' data-mid="{$member['member_id']}" class='member_row'>
		<td><img src='{$this->settings['skin_acp_url']}/images/members/normal_alert.png' alt='*' /></td>
		<td class='member_name'>
			<a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$member['member_id']}' class='larger_text'>{$member['members_display_name']}</a>
			<span class='desctext'>{$member['ip_addresses']}</span>
		</td>
		<td>{$member['email']}</td>
		<td>{$member['posts']}</td>
		<td>
			{$member['group_title']}
		</td>
		<td>
			{$member['_joined']}
		</td>
		<td>
			<p class='member_controls'>
				<a href='#' class='ipsBadge badge_red member_action' data-action="delete" title='{$this->lang->words['form_deletemember']}'>{$this->lang->words['m_but_delete']}</a>
				<a href='#' class='ipsBadge badge_green member_action' data-action="unban">{$this->lang->words['m_but_unban']}</a>
				<input type='checkbox' />
			</p>
		</td>
	</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Locked member loop for member list
 *
 * @param	array 		Member data
 * @return	string		HTML
 */
public function memberListRow_locked( $member ) {

$failuresatlife = sprintf( $this->lang->words['t_failures'], $member['oldest_fail'], $member['newest_fail'], $member['failed_login_count'] );

$IPBHTML .= <<<HTML
	<tr id='member-{$member['member_id']}' data-mid="{$member['member_id']}" class='member_row'>
		<td><img src='{$this->settings['skin_acp_url']}/images/members/normal_lock.png' alt='*' /></td>
		<td class='member_name'>
			<a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$member['member_id']}' class='larger_text'>{$member['members_display_name']}</a>
			<span class='desctext'>{$member['ip_addresses']}</span>
		</td>
		<td>{$member['email']}</td>
		<td>{$member['posts']}</td>
		<td>
			{$failuresatlife}
		</td>
		<td>
			{$member['_joined']}
		</td>
		<td>
			<p class='member_controls'>
				<a href='#' class='ipsBadge badge_red member_action' data-action="delete" title='{$this->lang->words['form_deletemember']}'>{$this->lang->words['m_but_delete']}</a>
				<a href='#' class='ipsBadge badge_green member_action' data-action="unlock">{$this->lang->words['m_but_unlock']}</a>
				<a href='#' class='ipsBadge badge_grey member_action' data-action="ban">{$this->lang->words['m_but_ban']}</a>
				<input type='checkbox' />
			</p>
		</td>
	</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Incomplete member loop for member list
 *
 * @param	array 		Member data
 * @return	string		HTML
 */
public function memberListRow_incomplete( $member ) {
$IPBHTML = "";

$member['group_title'] = IPSMember::makeNameFormatted( $member['group_title'], $member['member_group_id'] );

if( trim($member['members_display_name']) == '' )
{
	$member['members_display_name'] = "<em class='desctext'>{$this->lang->words['sm_nodisplayname']}</em>";
}

if( IPSText::mbstrlen( $member['email'] ) > 50 )
{
	$_email = htmlspecialchars( substr( html_entity_decode( $member['email'] ), 0, 20 ) ) . '...' . htmlspecialchars( substr( html_entity_decode( $member['email'] ), -25 ) );
	$member['email'] = '<span style="border-bottom:1px dotted gray; cursor:pointer" title="' . $member['email'] . "\">" . $_email . '</span>';
}

$_extraStyle = ( $member['member_banned'] ) ? '_red' : ( $member['bw_is_spammer'] ? '_amber' : '' );
$_extraText  = ( $member['member_banned'] ) ? '(' . $this->lang->words['m_f_showbanned'] . ')' : ( $member['bw_is_spammer'] ? '(' . $this->lang->words['m_f_showspam'] . ')' : '' );

$_serviceImg = "";

if ( $member['fb_uid'] )
{
	$_serviceImg = "<a href='http://www.facebook.com/profile.php?id={$member['fb_uid']}' target='_blank'><img src='{$this->settings['skin_acp_url']}/images/members/facebook.png' /></a>&nbsp;";
}

if ( $member['twitter_id'] )
{
	$_serviceImg .= "<img src='{$this->settings['skin_acp_url']}/images/members/twitter.png' />";
}

$IPBHTML .= <<<HTML
	<tr id='member-{$member['member_id']}' data-mid="{$member['member_id']}" class='member_row {$_extraStyle}'>
		<td><a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$member['member_id']}'><img src='{$member['pp_thumb_photo']}' style='width: 30px; height: 30px; border: 1px solid #d8d8d8' /></a></td>
		<td class='member_name'><a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$member['member_id']}' class='larger_text'>{$member['members_display_name']}</a></td>
		<td>{$_serviceImg} {$member['email']}</td>
		<td>{$member['group_title']} <span style='color:gray;font-size:0.8em'>{$_extraText}</span></td>
		<td><a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$member['ip_address']}'>{$member['ip_address']}</a></td>
		<td>
			{$member['_joined']}
		</td>
		<td>
			<p class='member_controls'>
				<a href='#' class='ipsBadge badge_red member_action' data-action='delete' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=members&amp;do=member_delete&amp;member_id={$member['member_id']}');" title='{$this->lang->words['form_deletemember']}'>{$this->lang->words['m_but_delete']}</a>
				<a href='#' class='ipsBadge badge_green member_action' data-action="finalize">{$this->lang->words['m_but_finalize']}</a>
				<input type='checkbox' />
			</p>
		</td>
	</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Validating member loop for member list
 *
 * @param	array 		Member data
 * @return	string		HTML
 */
public function memberListRow_validating( $member ) {
	
$IPBHTML = "";

$member['_hours']	= floor( ( time() - $member['entry_date'] ) / 3600 );
$member['_days']	= intval( $member['_hours'] / 24 );
$member['_rhours']	= intval( $member['_hours'] - ($member['_days'] * 24) );
$member['_entry']	= ipsRegistry::getClass( 'class_localization')->getDate( $member['entry_date'], 'TINY' );

$member['_where']	= ( $member['lost_pass'] ? $this->lang->words['t_lostpass'] : ( $member['new_reg'] ? $this->lang->words['t_userval'] : ( $member['email_chg'] ? $this->lang->words['t_emailchangecp'] : $this->lang->words['t_na'] ) ) );

if( isset($member['email_chg']) AND $member['email_chg'] )
{
	$member['_where'] .= " (<a href='" . $this->settings['base_url'] . "module=members&amp;section=tools&amp;do=unappemail&amp;mid={$member['member_id']}'>{$this->lang->words['t_unapprove']}</a>)";
}

if ( $member['new_reg'] AND ( $member['user_verified'] == 1 OR $this->settings['reg_auth_type'] == 'admin' ) )
{
	$member['_where'] = $this->lang->words['t_adminval'];
}

$member['_coppa']	= $member['coppa_user'] ? $this->lang->words['t_coppa'] : '';
						
$daysandhours = sprintf( $this->lang->words['t_daysandhours'], $member['_days'], $member['_rhours'] );
$image        = $member['spam_flag'] ? array( 'normal_spam.png', $this->lang->words['alt_spamuser'] ) : 
				( ( $member['user_verified'] ) ? array( 'normal_tick.png', $this->lang->words['alt_normal'] ) : 
				array( 'normal_lock.png', $this->lang->words['alt_unverifieduser'] ) );
$color		  = ( $member['user_verified'] ) ? 'red' : 'green';

$IPBHTML .= <<<HTML
	<tr id='member-{$member['member_id']}' class='member_row' data-mid="{$member['member_id']}"	>
		<td><img src='{$this->settings['skin_acp_url']}/images/members/{$image[0]}' alt='{$image[1]}' title='{$image[1]}' /></td>
		<td class='member_name'>
			<a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$member['member_id']}' class='larger_text'>{$member['members_display_name']}</a>
			{$member['_coppa']}<br />
			<span class='desctext'>{$this->lang->words['t_ipcolon']} <a href='{$this->settings['base_url']}&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$member['ip_address']}'>{$member['ip_address']}</a></span>
		</td>
		<td>{$member['email']}</td>
		<td>{$member['posts']}</td>
		<td>
			<span style='color:{$color}'>{$member['_where']}</span><br />
			{$member['_entry']}<br />
			<span class='desctext'>{$daysandhours}</span>
		</td>
		<td>
			{$member['_joined']}
		</td>
		<td>
			<p class='member_controls'>
				<a href='#' class='ipsBadge badge_red member_action' data-action="delete" title='{$this->lang->words['form_deletemember']}'>{$this->lang->words['m_but_delete']}</a>
				<a href='#' class='ipsBadge badge_green member_action' data-action="approve">{$this->lang->words['m_but_approve']}</a>
HTML;

if( $member['email_chg'] )
{
	$IPBHTML .= <<<HTML
				<a href='#' class='ipsBadge badge_grey member_action' data-action="resend">{$this->lang->words['m_but_resend']}</a>
HTML;
}

$IPBHTML .= <<<HTML
				<a href='#' class='ipsBadge badge_grey member_action' data-action="ban">{$this->lang->words['m_but_ban']}</a>
				<a href='#' class='ipsBadge badge_grey member_action' data-action="spam">{$this->lang->words['m_but_spammer']}</a>
				<input type='checkbox' />
			</p>
		</td>
	</tr>
HTML;

//--endhtml--//
return $IPBHTML;
	
}

/**
 * Regular member loop for member list
 *
 * @param	array 		Member data
 * @param	bool		Show checkbox
 * @return	string		HTML
 */
public function memberListRow( $member, $extraColumn=false ) {

$IPBHTML = "";

$member['group_title'] = IPSMember::makeNameFormatted( $member['group_title'], $member['member_group_id'] );

if( trim($member['members_display_name']) == '' )
{
	$member['members_display_name'] = "<em class='desctext'>{$this->lang->words['sm_nodisplayname']}</em>";
}

if( IPSText::mbstrlen( $member['email'] ) > 50 )
{
	$_email = htmlspecialchars( substr( html_entity_decode( $member['email'] ), 0, 20 ) ) . '...' . htmlspecialchars( substr( html_entity_decode( $member['email'] ), -25 ) );
	$member['email'] = '<span style="border-bottom:1px dotted gray; cursor:pointer" title="' . $member['email'] . "\">" . $_email . '</span>';
}

$_extraStyle = ( $member['member_banned'] ) ? '_red' : ( $member['bw_is_spammer'] ? '_amber' : '' );
$_extraText  = ( $member['member_banned'] ) ? '(' . $this->lang->words['m_f_showbanned'] . ')' : ( $member['bw_is_spammer'] ? '(' . $this->lang->words['m_f_showspam'] . ')' : '' );

$_serviceImg = "";

if ( $member['fb_uid'] )
{
	$_serviceImg = "<a href='http://www.facebook.com/profile.php?id={$member['fb_uid']}' target='_blank'><img src='{$this->settings['skin_acp_url']}/images/members/facebook.png' /></a>&nbsp;";
}

if ( $member['twitter_id'] )
{
	$_serviceImg .= "<img src='{$this->settings['skin_acp_url']}/images/members/twitter.png' />";
}

$IPBHTML .= <<<HTML
	<tr id='member-{$member['member_id']}' data-mid="{$member['member_id']}" class='member_row {$_extraStyle}'>
		<td><a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$member['member_id']}'><img src='{$member['pp_thumb_photo']}' style='width: 30px; height: 30px; border: 1px solid #d8d8d8' /></a></td>
		<td class='member_name'><a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$member['member_id']}' class='larger_text'>{$member['members_display_name']}</a></td>
		<td>{$_serviceImg} {$member['email']}</td>
		<td>
			{$member['_joined']}
		</td>
		<td>{$member['group_title']} <span style='color:gray;font-size:0.8em'>{$_extraText}</span></td>
		<td><a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$member['ip_address']}'>{$member['ip_address']}</a></td>
		<td>
			<p class='member_controls'>
				<a href='#' class='ipsBadge badge_red member_action' data-action='delete' title='{$this->lang->words['form_deletemember']}'>{$this->lang->words['m_but_delete']}</a>
HTML;

	if( $extraColumn )
	{
		$IPBHTML .= <<<HTML
		<input type='checkbox' />
HTML;
	}
	
	$IPBHTML .= <<<HTML
			</p>
		</td>
	</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Member list context menu
 *
 * @param	array 		Form elements
 * @param	object		Custom fields
 * @param   bool        Preset Fitlers
 * @return	string		HTML
 */
public function member_list_context_menu_filters( $form=array(), $fields=null, $filters_preset=0 ) {

$IPBHTML = "";
//--starthtml--//
$cur = false;
$left = $right = '';

// $form['_member_string']	is a simple search field with key 'string' which will search display name, email, name and IP address
// It is not included in this form because Rikki plans to implement separate from the advanced form, however it is available here for use if desired

if ( is_array( $fields->out_fields ) AND count( $fields->out_fields ) )
{
	foreach( $fields->out_fields as $id => $data )
	{
		$ignore = '';
		
		if( $fields->cache_data[ $id ]['type'] == 'radio' )
		{
			$ignore = "<div style='float:right;'> <input type='checkbox' name='ignore_field_{$id}' value='1' /> {$this->lang->words['sm_ignorehuh']}</div>";
		}
		
		if( $cur == true )
		{
			$right .= <<<HTML
				<li>
					<label for='{$id}'>{$fields->field_names[ $id ]}</label>
					{$ignore}{$data}
				</li>
HTML;
			$cur = false;
		}
		else
		{
			$left .= <<<HTML
				<li>
					<label for='{$id}'>{$fields->field_names[ $id ]}</label>
					{$ignore}{$data}
				</li>
HTML;
			$cur = true;
		} 
	}
}
	
$IPBHTML .= <<<HTML
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.jquery.members.js"></script>

<div class='section_title'>
	<h2>{$this->lang->words['member_management_h2']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}app=members&amp;module=members&amp;section=members&amp;do=add'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/user_add.png' alt='{$this->lang->words['add_new_member_button']}' />
					{$this->lang->words['add_new_member_button']}
				</a>
			</li>
			<li class='ipsActionButton disabled' id='memberList__prune'>
				<a href='#'><img src='{$this->settings['skin_acp_url']}/images/icons/user_delete.png' alt='' /> {$this->lang->words['prune_all_members']}</a>
			</li>
			<li class='ipsActionButton disabled' id='memberList__move'>
				<a href='#'><img src='{$this->settings['skin_acp_url']}/images/icons/user_assign.png' alt='' /> {$this->lang->words['move_all_members']}</a>
			</li>
		</ul>
		
	</div>
</div>

<div id='m_search_pane' style='display: none'>
	<form id='m_search_form'>
	<div class='acp-box'>
		<h3>
			<a href='#' id='close_adv_search' class='ipsBlock_titlelink'><strong>&times;</strong></a>
			{$this->lang->words['member_search_ttl']}
		</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='adv_search_main'>
					{$form['_member_contains']}
					{$form['_member_contains_type']}
					{$form['_member_contains_text']}
				</td>
			</tr>
		</table>
		<div id='m_search_pane_inner'>
			<table class='ipsTable double_pad'>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['search_type_member']}</strong>
					</td>
					<td class='field_field'>
						{$form['_member_status']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['search_prim_group']}</strong>
					</td>
					<td class='field_field'>
						{$form['_primary_group']}
						<p style='margin-top: 5px'>
							{$form['_include_secondary']} <label for='f_inc_secondary' class='desctext'>{$this->lang->words['check_secondarytoo']}</label>
						</p>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['search_secon_group']}</strong>
					</td>
					<td class='field_field'>
						{$form['_secondary_group']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['registered_between']}</strong>
					</td>
					<td class='field_field'>
						{$form['_date_reg_from']} {$this->lang->words['and']} {$form['_date_reg_to']}<br />
						<span class='desctext'>(MM-DD-YYYY)</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['last_posted_between']}</strong>
					</td>
					<td class='field_field'>
						{$form['_date_post_from']} {$this->lang->words['and']} {$form['_date_post_to']}<br />
						<span class='desctext'>(MM-DD-YYYY)</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['last_visit_between']}</strong>
					</td>
					<td class='field_field'>
						{$form['_date_active_from']} {$this->lang->words['and']} {$form['_date_active_to']}<br />
						<span class='desctext'>(MM-DD-YYYY)</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['post_count_is']}</strong>
					</td>
					<td class='field_field'>
						{$form['_post_count_type']} &nbsp;&nbsp;{$form['_post_count']}
					</td>
				</tr>
HTML;

			if ( is_array( $fields->out_fields ) AND count( $fields->out_fields ) )
			{
				foreach( $fields->out_fields as $id => $data )
				{
					$ignore = '';
					
					if( $fields->cache_data[ $id ]['type'] == 'radio' )
					{
						$ignore = "<div style='float:right;'> <input type='checkbox' name='ignore_field_{$id}' value='1' /> {$this->lang->words['sm_ignorehuh']}</div>";
					}
					
					$IPBHTML .= <<<HTML
					<tr>
						<td class='field_title'>
							<strong class='title'>{$fields->field_names[ $id ]}</strong>
						</td>
						<td class='field_field'>
							{$ignore}{$data}
						</td>
					</tr>
HTML;
				}
			}
			
			$IPBHTML .= <<<HTML
			</table>
		</div>
		<div class='acp-actionbar' style='position: relative'>
			<p class='required left desctext' style='position: absolute; left: 15px; top: 15px; font-size: 11px'>{$this->lang->words['m_all_fields_opt']}</p>
			<input type='button' id='do_advanced_search' value='{$this->lang->words['m_adv_search_members']}' class='realbutton' />
		</div>
	</div>
	</form>
</div>
HTML;

if( ( $filters_preset && !$this->request['f_search_type'] ) || $this->request['__update'] == 1 )
{
	/* This left for legacy compatibility, in case admins still have a cookie set from 3.1, to enable
		them to cancel out of it */
	$this->lang->words['mem_results_filtered'] = sprintf( $this->lang->words['mem_results_filtered'], "{$this->settings['base_url']}{$this->form_code}&amp;reset_filters=1" );
$IPBHTML .= <<<HTML
<div class='information-box'>
	{$this->lang->words['mem_results_filtered']}
</div>
<br />
HTML;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Member suspension form
 *
 * @param	array 		Member data
 * @return	string		HTML
 */
public function memberSuspension( $member ) {

$IPBHTML = "";
//--starthtml--//

$dropDown	= ipsRegistry::getClass('output')->formDropdown( 'units', array( array( 'h', $this->lang->words['dunit_hours'] ), array( 'd', $this->lang->words['dunit_days'] ) ), $member['units'] );
$yesNo		= ipsRegistry::getClass('output')->formYesNo( 'send_email', 0 );
$email		= ipsRegistry::getClass('output')->formTextarea( 'email_contents', $member['contents'], 40, 10, '', "style='width:70%;'" );
$susp_for = sprintf( $this->lang->words['sm_suspfor'], $member['members_display_name'] );

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['sm_acctsusp']}</h3>

	<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=dobanmember' method='post'>
		<input type='hidden' name='member_id' value='{$member['member_id']}' />
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
		
		<table class='ipsTable double_pad'>
			<tr>
				<th colspan='2'>{$this->lang->words['sm_suspnote']}</th>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$susp_for}</strong></td>
				<td class='field_field'><input type='text' size='5' name='timespan' value='{$member['timespan']}' /> 
					{$dropDown}
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['sm_suspnotify']}</strong></td>
				<td class='field_field'>{$yesNo}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['sm_suspemailnotify']}</strong></td>
				<td class='field_field'>{$email}</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' class='realbutton' value='{$this->lang->words['sm_suspendbutton']}' />
		</div>
	 </form>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Should this admin get ACP restrictions?
 *
 * @param	array 		Member data
 * @param	array 		Admin groups
 * @return	string		HTML
 */
public function memberAdminConfirm( $member, $admins ) {

$wedetectedthis = sprintf( $this->lang->words['sm_detectacp'], $member['members_display_name'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<!--SKINNOTE: Not yet skinned-->
<div class='acp-box'>
	<h3>{$this->lang->words['sm_configrest']}</h3>
HTML;
	if( $wedetectedthis ){
		$IPBHTML .= "<strong>{$wedetectedthis}</strong><br /><br />";
	}
	
$IPBHTML .= <<<HTML
	<div style='padding: 15px'>
		{$this->lang->words['sm_belongsto']}<br />
		<ul style='padding: 8px 8px 8px 15px'>
HTML;

	foreach( $admins as $group_id => $restricted )
	{
		$restrict_text	= $restricted ? $this->lang->words['sm_is'] : $this->lang->words['sm_isnot'];
		$group_title	= $this->caches['group_cache'][ $group_id ]['g_title'];
		$thisgroupisorisnot = sprintf ( $this->lang->words['sm_thisgroup'], $group_title, $restrict_text );
		$IPBHTML .= <<<HTML
			<li>{$thisgroupisorisnot}</li>
HTML;
	}
	
	$IPBHTML .= <<<HTML
		</ul>
		<br /><br />
		{$this->lang->words['sm_setrestrict']}
	</div>
	<div class='acp-actionbar' style='padding-top: 12px;'>
		<a class='button' href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=viewmember&amp;member_id={$member['member_id']}'>{$this->lang->words['sm_nothanks']}</a>
		<a class='button' href='{$this->settings['base_url']}&amp;module=restrictions&amp;section=restrictions&amp;do=acpperms-member-add-complete&amp;entered_name={$member['members_display_name']}'>{$this->lang->words['sm_yesplease']}</a>
	</div>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Form to add a member
 *
 * @param	array 		Groups
 * @param	object		Custom fields
 * @return	string		HTML
 */
public function memberAddForm( $groups, $fields ) {

$IPBHTML = "";
//--starthtml--//

//-----------------------------------------
// Got admin restrictions?
//-----------------------------------------

if ( $this->memberData['row_perm_cache'] )
{
	$IPBHTML .= "<div class='input-warn-content' style='color:black'>{$this->lang->words['sm_acpresinfo']}</div><br />";
}

$group		= ipsRegistry::getClass('output')->formDropdown( 'member_group_id', $groups, isset($_POST['member_group_id']) ? $_POST['member_group_id'] : $this->settings['member_group'] );
$email		= ipsRegistry::getClass('output')->formInput( 'email', isset($_POST['email']) ? IPSText::stripslashes($_POST['email']) : '' );
$name		= ipsRegistry::getClass('output')->formInput( 'name', isset($_POST['name']) ? IPSText::stripslashes($_POST['name']) : '' );
$password	= ipsRegistry::getClass('output')->formInput( 'password', isset($_POST['password']) ? IPSText::stripslashes($_POST['password']) : '', 'password', 30, 'password' );
$coppa		= ipsRegistry::getClass('output')->formYesNo( 'coppa', isset($_POST['coppa']) ? $_POST['coppa'] : 0 );
$send_email	= ipsRegistry::getClass('output')->formYesNo( 'sendemail', isset($_POST['sendemail']) ? $_POST['sendemail'] : 1 );

if( $this->settings['auth_allow_dnames'] )
{
	$display_name	= ipsRegistry::getClass('output')->formInput( 'members_display_name', isset($_POST['members_display_name']) ? IPSText::stripslashes($_POST['members_display_name']) : '' );
}


$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['add_new_member_button']}</h2>
</div>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=doadd' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['sm_registernew']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sm_loginname']}<span class='required'>*</span></strong>
				</td>
				<td class='field_field'>
					{$name}
				</td>
			</tr>
HTML;

if( $this->settings['auth_allow_dnames'] )
{
	$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sm_display']}<span class='required'>*</span></strong>
				</td>
				<td class='field_field'>
					<input type='checkbox' id='mirror_loginname' name='mirror_loginname' checked="checked" value='1' onclick="$('specify_displayname').toggle();" /> <label for='mirror_loginname' class='desctext'>{$this->lang->words['sameasloginname']}</label><br style='margin-bottom: 5px;'/>
					<div style='display:none;' id='specify_displayname'>{$display_name}</div>
				</td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sm_password']}<span class='required'>*</span></strong>
				</td>
				<td class='field_field'>
					{$password}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sm_email']}<span class='required'>*</span></strong>
				</td>
				<td class='field_field'>
					{$email}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sm_group']}</strong>
				</td>
				<td class='field_field'>
					{$group}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sm_coppauser']}</strong>
				</td>
				<td class='field_field'>
					{$coppa}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['sm_sendconf']}</strong>
				</td>
				<td class='field_field'>
					{$send_email}<br />
					<span class='desctext'>{$this->lang->words['sm_sendconf_info']}</span>
				</td>
			</tr>
HTML;

// Custom Fields
if ( count( $fields->out_fields ) )
{
	$IPBHTML .= <<<HTML
			<tr>
				<th colspan='2'>{$this->lang->words['sm_custfields']}</th>
			</tr>
HTML;

	foreach( $fields->out_fields as $id => $data )
	{
		$req   = '';
		
		if ( $fields->cache_data[ $id ]['pf_admin_only'] )
		{
			$ad_only   = "<span class='field_highlight'>" . $this->lang->words['add_cf_admin'] . '</span>';
		}
		
		$req   .= ( $fields->cache_data[ $id ]['pf_not_null'] ) ? "<span class='required'>*</span>" : '';
		
		$IPBHTML .= <<<HTML
 			<tr class='_cfields'>
 				<td class='field_title'>
 					<strong class='title'>{$fields->field_names[ $id ]}{$req}</strong>
					{$ad_only}
 				</td>
 				<td class='field_field'>
 					{$data}
					<p class='desctext'>{$fields->field_desc[ $id ]}</p>
 				</td>
 			</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
		</table>
		
		<div class='acp-actionbar'>
			 <input type='submit' class='button primary' value='{$this->lang->words['sm_regbutton']}' />
		</div>
	</div>
 </form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Confirm member pruning
 *
 * @param	int			Total count
 * @return	string		HTML
 */
public function pruneConfirm( $count ) {

$prune_button = sprintf( $this->lang->words['sm_prunebutton'], $count );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=doprune' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	<div class='warning'>
		<h4>{$this->lang->words['about_to_prune']}</h4>
		{$this->lang->words['sm_prunemem_info']}
	</div>
	<br />
	<div class='acp-box'>
		<h3>{$this->lang->words['sm_prunemem']}</h3>
		<table class='ipsTable'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['sm_prunenum']}</strong></td>
				<td class='field_field'>
					{$count}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' class='button primary redbutton' value='{$prune_button}' />
		</div>
	</div>
</form>
<br />

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Confirm moving members
 *
 * @param	int			Total count
 * @return	string		HTML
 */
public function moveConfirm( $count ) {

$IPBHTML = "";
//--starthtml--//

$member_groups = array();

foreach( ipsRegistry::cache()->getCache( 'group_cache' ) as $k => $v )
{
	$member_groups[] = array( $v['g_id'], $v['g_title'] );
}

$group		= ipsRegistry::getClass('output')->formDropdown( 'move_to_group', $member_groups, isset($_POST['member_group_id']) ? $_POST['member_group_id'] : $this->settings['member_group'] );
$move_button = sprintf( $this->lang->words['sm_movebutton'], $count );
$IPBHTML .= <<<HTML

<br />
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=domove' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	<div class='warning'>
		<h4>{$this->lang->words['about_to_move']}</h4>
		{$this->lang->words['sm_movemem_info']}
	</div>
	<br />
	<div class='acp-box'>
		<h3>{$this->lang->words['sm_movemem']}</h3>
		<table class='alternate_rows double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['sm_prunenum']}</strong></td>
				<td class='field_field'>
					{$count}
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['sm_movegroup']}</strong></td>
				<td class='field_field'>
					{$group}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' class='button primary redbutton' value='{$move_button}' />
		</div>
	</div>
</form>
<br />

HTML;

//--endhtml--//
return $IPBHTML;
}


}