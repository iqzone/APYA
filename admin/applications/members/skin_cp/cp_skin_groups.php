<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP groups skin file
 * Last Updated: $Date: 2012-05-25 13:17:47 -0400 (Fri, 25 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10798 $
 *
 */
 
class cp_skin_groups extends output
{
	/**#@+
	 * Registry objects
	 *
	 * @var		object
	 */	
	public $registry;
	public $DB;
	public $settings;
	public $request;
	public $lang;
	public $member;
	public $memberData;
	public $cache;
	public $caches;
	/**#@-*/
	
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
 * Should this admin group get ACP restrictions?
 *
 * @return	string		HTML
 */
public function groupAdminConfirm( $group_id ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
 <h3>{$this->lang->words['sm_configrest']}</h3>
 <table class='ipsTable double_pad'>
	<tr>
		<td>
			<strong class='title'>{$this->lang->words['sm_detectacp']}</strong>
 			<br /><br />
 			{$this->lang->words['sm_setrestrict']}
		</td>
	</tr>
  	</table>
	<div class='acp-actionbar'>
		<a class='button' href='{$this->settings['base_url']}&amp;{$this->form_code}'>{$this->lang->words['sm_nothanks']}</a>&nbsp;&nbsp;
		<a class='button' href='{$this->settings['base_url']}&amp;module=restrictions&amp;section=restrictions&amp;do=acpperms-group-add-complete&amp;entered_group={$group_id}'>{$this->lang->words['sm_yesplease']}</a>
	</div>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Group form
 *
 * @param	string		Type (add|edit)
 * @param	array 		Group data
 * @param	array 		Permission masks
 * @param	array 		Extra tabs
 * @param	string		Default tab to open
 * @return	string		HTML
 */
public function groupsForm( $type, $group, $permission_masks, $content=array(), $firsttab='' ) {

//-----------------------------------------
// Format some of the data
//-----------------------------------------

list($group['g_promotion_id'], $group['g_promotion_posts'])	= explode( '&', $group['g_promotion'] );
list($p_max, $p_width, $p_height) 							= explode( ":", $group['g_photo_max_vars'] );

if ($group['g_promotion_posts'] < 1)
{
	$group['g_promotion_posts'] = '';
}

if( $type == 'edit' AND $group['g_attach_max'] == 0 )
{
	$group['g_attach_maxdis'] = $this->lang->words['gf_unlimited'];
}
else if( $type == 'edit' AND $group['g_attach_max'] == -1 )
{
	$group['g_attach_maxdis'] = $this->lang->words['gf_disabled'];
}
else
{
	$group['g_attach_maxdis'] = IPSLib::sizeFormat( $group['g_attach_max'] * 1024 );
}

if( $type == 'edit' AND $group['g_attach_per_post'] == 0 )
{
	$group['g_attach_per_postdis'] = $this->lang->words['gf_unlimited'];
}
else if( $type == 'edit' AND $group['g_attach_per_post'] == -1 )
{
	$group['g_attach_per_postdis'] = $this->lang->words['gf_disabled'];
}
else
{
	$group['g_attach_per_postdis'] = IPSLib::sizeFormat( $group['g_attach_per_post'] * 1024 );
}
		
//-----------------------------------------
// Set some of the form variables
//-----------------------------------------

$form_code			= $type == 'edit' ? 'doedit' : 'doadd';
$button				= $type == 'edit' ? $this->lang->words['g_compedit'] : $this->lang->words['g_addgroup'];
$ini_max			= @ini_get( 'upload_max_filesize' ) ? @ini_get( 'upload_max_filesize' ) : $this->lang->words['g_cannotobt'];
$secure_key			= ipsRegistry::getClass('adminFunctions')->getSecurityKey();

//-----------------------------------------
// Start off the form fields
//-----------------------------------------

$all_groups 		= array( 0 => array ( 'none', $this->lang->words['g_dontprom'] ) );

foreach( $this->cache->getCache('group_cache') as $group_data )
{
	$all_groups[]	= array( $group_data['g_id'], $group_data['g_title'] );
}

$gbw_unit_type      = array(
							 0 => array( 0, $this->lang->words['g_dd_apprp'] ),
							 1 => array( 1, $this->lang->words['g_dd_days'] ) );

$form							= array();
$form['g_title']				= $this->registry->output->formInput( "g_title", $group['g_title'], '', 32, 'text', '', '', 32 );
$form['permid']					= $this->registry->output->formMultiDropdown( "permid[]", $permission_masks, explode( ",", $group['g_perm_id'] ) );
$form['g_icon']					= $this->registry->output->formTextarea( "g_icon", htmlspecialchars( $group['g_icon'], ENT_QUOTES ) );
$form['prefix']					= $this->registry->output->formInput( "prefix", htmlspecialchars( $group['prefix'], ENT_QUOTES ) );
$form['suffix']					= $this->registry->output->formInput( "suffix", htmlspecialchars( $group['suffix'], ENT_QUOTES ) );
$form['g_hide_from_list']		= $this->registry->output->formYesNo( "g_hide_from_list", $group['g_hide_from_list'] );
$form['g_attach_max']			= $this->registry->output->formInput( "g_attach_max", $group['g_attach_max'] );
$form['g_attach_per_post']		= $this->registry->output->formInput( "g_attach_per_post", $group['g_attach_per_post'] );
$form['p_max']					= $this->registry->output->formInput( "p_max", $p_max );
$form['p_width']				= $this->registry->output->formSimpleInput( "p_width", $p_width, 3 );
$form['p_height']				= $this->registry->output->formSimpleInput( "p_height", $p_height, 3 );
$form['g_can_msg_attach']		= $this->registry->output->formYesNo( "g_can_msg_attach", $group['g_can_msg_attach'] );
$form['g_view_board']			= $this->registry->output->formYesNo( "g_view_board", $group['g_view_board'], 'g_view_board' );
$form['g_access_offline']		= $this->registry->output->formYesNo( "g_access_offline", $group['g_access_offline'] );
$form['g_mem_info']				= $this->registry->output->formYesNo( "g_mem_info", $group['g_mem_info'] );
$form['gbw_view_last_info']     = $this->registry->output->formYesNo( "gbw_view_last_info", $group['gbw_view_last_info'] );

$form['g_can_add_friends']		= $this->registry->output->formYesNo( "g_can_add_friends", $group['g_can_add_friends'] );
$form['g_hide_online_list']		= $this->registry->output->formYesNo( "g_hide_online_list", $group['g_hide_online_list'] );
$form['g_use_search']			= $this->registry->output->formYesNo( "g_use_search", $group['g_use_search'] );
$form['g_search_flood']			= $this->registry->output->formInput( "g_search_flood", $group['g_search_flood'] );
$form['g_edit_profile']			= $this->registry->output->formYesNo( "g_edit_profile", $group['g_edit_profile'] );
$form['g_use_pm'] 				= $this->registry->output->formYesNo( "g_use_pm", $group['g_use_pm'] );
$form['g_max_mass_pm']			= $this->registry->output->formInput( "g_max_mass_pm", $group['g_max_mass_pm'] );
$form['g_max_messages']			= $this->registry->output->formInput( "g_max_messages", $group['g_max_messages'] );
$form['g_pm_perday']			= $this->registry->output->formSimpleInput( "g_pm_perday", $group['g_pm_perday'], 4 );
$form['g_pm_flood_mins']		= $this->registry->output->formSimpleInput( "g_pm_flood_mins", $group['g_pm_flood_mins'], 3 );

$form['g_dohtml']				= $this->registry->output->formYesNo( "g_dohtml", $group['g_dohtml'] );
$form['g_bypass_badwords']		= $this->registry->output->formYesNo( "g_bypass_badwords", $group['g_bypass_badwords'] );
$form['g_dname_date']			= $this->registry->output->formSimpleInput( "g_dname_date", $group['g_dname_date'], 3 );
$form['g_dname_changes']		= $this->registry->output->formSimpleInput( "g_dname_changes", $group['g_dname_changes'], 3 );
$form['g_is_supmod']			= $this->registry->output->formYesNo( "g_is_supmod", $group['g_is_supmod'] );
$form['g_access_cp']			= $this->registry->output->formYesNo( "g_access_cp", $group['g_access_cp'] );

$form['g_promotion_id']			= $this->registry->output->formDropdown( "g_promotion_id", $all_groups, $group['g_promotion_id'] );
$form['g_promotion_posts']		= $this->registry->output->formSimpleInput( 'g_promotion_posts', $group['g_promotion_posts'] );

$form['g_new_perm_set']			= $this->registry->output->formInput( "g_new_perm_set", '' );
$form['g_rep_max_positive']		= $this->registry->output->formInput( "g_rep_max_positive", $group['g_rep_max_positive'] );
$form['g_rep_max_negative']		= $this->registry->output->formInput( "g_rep_max_negative", $group['g_rep_max_negative'] );
$form['gbw_view_reps']			= $this->registry->output->formYesNo( "gbw_view_reps", $group['gbw_view_reps'] );

$sig_limits						= explode( ':', $group['g_signature_limits'] );

$form['use_signatures']			= $this->registry->output->formYesNo( "use_signatures", $sig_limits[0] );
$form['max_images']				= $this->registry->output->formInput( "max_images", $sig_limits[1] );
$form['max_dims']				= $this->lang->words['g_upersonalpho_w'] . ' ' . $this->registry->output->formSimpleInput( "max_dims_x", $sig_limits[2] ) . ' x ' . $this->lang->words['g_upersonalpho_h'] . ' ' . $this->registry->output->formSimpleInput( "max_dims_y", $sig_limits[3] );
$form['max_urls']				= $this->registry->output->formInput( "max_urls", $sig_limits[4] );
$form['max_lines']				= $this->registry->output->formInput( "max_lines", $sig_limits[5] );

$form['g_displayname_unit']		= $this->registry->output->formSimpleInput( "g_displayname_unit", $group['g_displayname_unit'], 3 );
$form['gbw_displayname_unit_type']	= $this->registry->output->formDropdown( "gbw_displayname_unit_type", $gbw_unit_type, $group['gbw_displayname_unit_type'] );
$form['g_sig_unit']				= $this->registry->output->formSimpleInput( "g_sig_unit", $group['g_sig_unit'], 3 );
$form['gbw_sig_unit_type']		= $this->registry->output->formDropdown( "gbw_sig_unit_type", $gbw_unit_type, $group['gbw_sig_unit_type'] );

$form['gbw_promote_unit_type']	= $this->registry->output->formDropdown( "gbw_promote_unit_type", $gbw_unit_type, $group['gbw_promote_unit_type'] );

$form['gbw_no_status_update']	= $this->registry->output->formYesNo( "gbw_no_status_update", $group['gbw_no_status_update'] );
$form['gbw_no_status_import']	= $this->registry->output->formYesNo( "gbw_no_status_import", $group['gbw_no_status_import'] );

$form['g_max_notifications']	= $this->registry->output->formInput( "g_max_notifications", $group['g_max_notifications'] );						    
 
$form['gbw_allow_customization']	= $this->registry->output->formYesNo( "gbw_allow_customization" , $group['gbw_allow_customization'] );						    
$form['gbw_allow_url_bgimage']	    = $this->registry->output->formYesNo( "gbw_allow_url_bgimage"   , $group['gbw_allow_url_bgimage'] );						    
$form['gbw_allow_upload_bgimage']	= $this->registry->output->formYesNo( "gbw_allow_upload_bgimage", $group['gbw_allow_upload_bgimage'] );						    
$form['g_max_bgimg_upload']		    = $this->registry->output->formInput( "g_max_bgimg_upload", $group['g_max_bgimg_upload'] );

$form['gbw_disable_tagging']	= $this->registry->output->formYesNo( "gbw_disable_tagging" , $group['gbw_disable_tagging'] );	
$form['gbw_disable_prefixes']	= $this->registry->output->formYesNo( "gbw_disable_prefixes", $group['gbw_disable_prefixes'] );	


if( $type == 'edit' )
{
	$title	= $this->lang->words['g_editing'] . $group['g_title'];
}
else
{
	$title	= $this->lang->words['g_adding'];
}

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}ipsEditGroup.js'></script>
<script type='text/javascript'>
HTML;

foreach( $permission_masks as $d )
{
	$IPBHTML .= "	perms_{$d[0]} = '{$d[1]}';\n";
}

$IPBHTML .= <<<HTML
</script>
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$form_code}&amp;secure_key={$secure_key}' method='post' id='adform' name='adform' onsubmit='return checkform();'>
<input type='hidden' name='id' value='{$group['g_id']}' />

<div class='acp-box'>
	<h3>&nbsp;</h3>
	
<div id='tabstrip_group' class='ipsTabBar with_left with_right'>
	<span class='tab_left'>&laquo;</span>
	<span class='tab_right'>&raquo;</span>
	
	<ul>
		<li id='tab_GROUPS_1'>{$this->lang->words['g_globalsett']}</li>
		<li id='tab_GROUPS_2'>{$this->lang->words['g_globalperm']}</li>
HTML;

// Got blocks from other apps?
$IPBHTML .= implode( "\n", $content['tabs'] );

$pmlimit = sprintf( $this->lang->words['gf_pm_limit'], $form['g_pm_perday'] );
$pmflood = sprintf( $this->lang->words['gf_pm_flood'], $form['g_pm_flood_mins'] );

$_firstTab = ( $firsttab ) ? 'GROUPS_' . $firsttab : "GROUPS_1";

$dnc1 = sprintf( $this->lang->words['display_name_changes_1'], $form['g_displayname_unit'], $form['gbw_displayname_unit_type'] );
$dnc2 = sprintf( $this->lang->words['display_name_changes_3'], $form['g_dname_changes'], $form['g_dname_date'] );

$IPBHTML .= <<<HTML
	</ul>
</div>

<div id='tabstrip_group_content' class='ipsTabBar_content'>
	<div id='tab_GROUPS_1_content'>
		<table class='ipsTable double_pad'>
			<tr>
				<th colspan='2'>{$this->lang->words['gf_t_details']}</th>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_gtitle']}</strong>
				</td>
				<td class='field_field'>
					{$form['g_title']}
				</td>
			</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_gicon']}</strong>
				</td>
		 		<td class='field_field'>
					{$form['g_icon']}<br />
					<span class='desctext'>{$this->lang->words['gf_gicon_info']}</span>
		 		</td>
		 	</tr>
		 	
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_gformpre']}</strong>
				</td>
		 		<td class='field_field'>
					{$form['prefix']}<br />
					<span class='desctext'>{$this->lang->words['gf_gformpre_info']}</span>
		 		</td>
		 	</tr>
		 	
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_gformsuf']}</strong>
				</td>
		 		<td class='field_field'>
					{$form['suffix']}<br />
					<span class='desctext'>{$this->lang->words['gf_gformsuf_info']}</span>
		 		</td>
		 	</tr>
			<tr>
				<th colspan='2'>{$this->lang->words['gf_t_permissions']}</th>
			</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_permset']}</strong>
				</td>
		 		<td class='field_field'>
					{$form['permid']}<br />
					<span class='desctext'>{$this->lang->words['g_permset_info']}</span>
		 		</td>
		 	</tr>
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['g_newpermset']}</strong>
				</td>
		 		<td class='field_field'>
					{$form['g_new_perm_set']}<br />
					<span class='desctext'>{$this->lang->words['g_newpermset_info']}</span>
		 		</td>
		 	</tr>
		 	
		 	<tr class='guest_legend'>
		 		<td class='field_title'>
		 			<strong class='title'>{$this->lang->words['gf_hide']}</strong>
		 		</td>
		 		<td class='field_field'>
		 			{$form['g_hide_from_list']}
		 		</td>
		 	</tr>
		 	
		 	<tr class='guest_legend'>
		 		<td class='field_title'>
		 			<strong class='title'>{$this->lang->words['gf_hide_online']}</strong>
		 		</td>
		 		<td class='field_field'>
		 			{$form['g_hide_online_list']}<br />
		 			<span class='desctext'>{$this->lang->words['gf_hideonline_desc']}</span>
		 		</td>
		 	</tr>
		 	<tr>
				<th colspan='2'>{$this->lang->words['gf_t_display_name']}</th>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['display_name_changes']}</strong>
				</td>
				<td>
					{$dnc1}<br />
					<span class='desctext'>{$this->lang->words['display_name_changes_2']}</span><br />
					<br />
					{$dnc2}<br />
					<span class='desctext'>{$this->lang->words['display_name_changes_4']}</span>
				</td>
			</tr>
		 	<tr class='guest_legend'>
				<th colspan='2'>{$this->lang->words['gf_t_access_control']}</th>
			</tr>
		 	<tr class='guest_legend'>
		 		<td class='field_title'>
		 			<strong class='title'>{$this->lang->words['gf_msup']}</strong>
		 		</td>
		 		<td class='field_field'>
		 			{$form['g_is_supmod']}
		 		</td>
		 	</tr>
		 	
		 	<tr class='guest_legend'>
		 		<td class='field_title'>
		 			<strong class='title'>{$this->lang->words['gf_macp']}</strong>
		 		</td>
		 		<td class='field_field'>
		 			{$form['g_access_cp']}
		 		</td>
		 	</tr>
		 	<tr class='guest_legend'>
				<th colspan='2'>{$this->lang->words['gf_t_promotion']}</th>
			</tr>
		 	<tr class='guest_legend'>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_mpromote']}</strong>
					{$this->javascriptHelpLink('mg_promote')}
				</td>
		 		<td class='field_field'>		 		
HTML;
				if( $group['g_access_cp'] )
				{
					$IPBHTML .= "{$this->lang->words['g_mpromote_no']}";
				}
				else
				{
				 	$promotegrouptxt = sprintf( $this->lang->words['g_mpromote_to'], $form['g_promotion_id'], $form['g_promotion_posts'], $form['gbw_promote_unit_type'] );
					$IPBHTML .= "{$promotegrouptxt}";
				}
		$IPBHTML .= <<<HTML
		 		</td>
		 	</tr>
		</table>
 	</div>

 <div id='tab_GROUPS_2_content'>
	 <table class='ipsTable double_pad'>
		<tr>
			<th colspan='2'>{$this->lang->words['gf_t_access_permissions']}</th>
		</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_ssite']}</strong>
			</td>
	 		<td class='field_field'>
HTML;

if( $group['g_id'] == $this->settings['guest_group'] )
{
	$g_view_board_warning_message = sprintf( $this->lang->words['gf_view_board_warning'], $this->registry->output->buildURL( 'app=core&module=settings&section=settings', 'admin' ) );
	
	$IPBHTML .= <<<HTML
	 			<div class='information-box'>{$g_view_board_warning_message}</div>
	 			<input type='hidden' name='g_view_board' value='1' />
HTML;
}
else
{
	$IPBHTML .= $form['g_view_board'];
}

$IPBHTML .= <<<HTML
	 		</td>
	 	</tr>
	 	
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_soffline']}</strong>
			</td>
	 		<td class='field_field'>
	 			{$form['g_access_offline']}
	 		</td>
	 	</tr>
	 	
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_sprofile']}</strong>
			</td>
	 		<td class='field_field'>
	 			{$form['g_mem_info']}
	 		</td>
	 	</tr>
	 	
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gbw_view_last_info']}</strong>
			</td>
	 		<td class='field_field'>
	 			{$form['gbw_view_last_info']}
	 		</td>
	 	</tr>
	 	
	 	
	 	<tr class='guest_legend'>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_addfriends']}</strong>
			</td>
	 		<td class='field_field'>
	 			{$form['g_can_add_friends']}
	 		</td>
	 	</tr>
	 	
	 	<tr class='guest_legend'>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_editprofile']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['g_edit_profile']}
	 		</td>
	 	</tr>
	 	
	 	<tr class='guest_legend'>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_shtml']}</strong>
				{$this->javascriptHelpLink('mg_dohtml')}
	 		</td>
	 		<td class='field_field'>
	 			{$form['g_dohtml']}
	 		</td>
	 	</tr>
		<tr class='guest_legend'>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_maxnotify']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['g_max_notifications']}<br />
				<span class='desctext'>{$this->lang->words['gf_maxnotify_desc']}</span>
	 		</td>
	 	</tr>
		<tr class='guest_legend'>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_sbadword']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['g_bypass_badwords']}
	 		</td>
	 	</tr>
		<tr class='guest_legend'>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_no_status']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['gbw_no_status_update']}
	 		</td>
	 	</tr>
	 	<tr class='guest_legend'>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_no_status_import']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['gbw_no_status_import']}
	 			<br /><span class='desctext'>{$this->lang->words['gf_no_status_import_d']}</span>
	 		</td>
	 	</tr>
	 	
	 	<tr>
			<th colspan='2'>{$this->lang->words['gf_customization_title']}</th>
		</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_bw_allow_customization']}</strong>
			</td>
	 		<td class='field_field'>
	 			{$form['gbw_allow_customization']}				
				<br /><span class='desctext'>{$this->lang->words['gbw_allow_customization_d']}</span>
	 		</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_bw_allow_url_bgimage']}</strong>
			</td>
	 		<td class='field_field'>
	 			{$form['gbw_allow_url_bgimage']}
				<br /><span class='desctext'>{$this->lang->words['gbw_allow_url_bgimage_d']}</span>
	 		</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_bw_allow_upload_bgimage']}</strong>
			</td>
	 		<td class='field_field'>
	 			{$form['gbw_allow_upload_bgimage']}
	 			<div style='padding-top:4px'>{$this->lang->words['gf_max_bgimg_upload']} {$form['g_max_bgimg_upload']}</div>
				<br /><span class='desctext'>{$this->lang->words['gbw_allow_upload_bgimage_d']}</span>
	 		</td>
	 	</tr>
	 	<tr>
			<th colspan='2'>{$this->lang->words['gf_t_search']}</th>
		</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_ssearch']}</strong>
			</td>
	 		<td class='field_field'>
	 			{$form['g_use_search']}
	 		</td>
	 	</tr>
	 	
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_sflood']}</strong>
			</td>
	 		<td class='field_field'>
	 			{$form['g_search_flood']}<br />
				<span class='desctext'>{$this->lang->words['gf_sflood_info']}</span>
	 		</td>
	 	</tr>
	 	
	 	<tr class='guest_legend'>
			<th colspan='2'>{$this->lang->words['gf_t_pms']}</th>
		</tr>
	 	<tr class='guest_legend'>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_spm']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['g_use_pm']}
				<br />{$pmlimit}
	 			<br />{$pmflood}
				<br />
				<span class='desctext'>{$this->lang->words['gf_spmperday_info']}</span>
	 		</td>
	 	</tr>
	
	 	<tr class='guest_legend'>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_spmmax']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['g_max_mass_pm']}<br />
				<span class='desctext'>{$this->lang->words['gf_spmmax_info']}</span>
	 		</td>
	 	</tr>
	 	
	 	<tr class='guest_legend'>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_spmmaxstor']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['g_max_messages']}<br />
				<span class='desctext'>{$this->lang->words['gf_maxmessages_info']}</span>
	 		</td>
	 	</tr>
		<tr class='guest_legend'>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_upm']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['g_can_msg_attach']}
	 		</td>
	 	</tr>
	
		<tr>
			<th colspan='2'>{$this->lang->words['gf_t_reps']}</th>
		</tr>
	 	<tr>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_repmaxpos']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['g_rep_max_positive']}
				<br />
				<span class='desctext'>{$this->lang->words['gf_repnum_info']}</span>
	 		</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_repmaxneg']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['g_rep_max_negative']}<br />
				<span class='desctext'>{$this->lang->words['gf_repnum_info']}</span>
	 		</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_bw_view_reps']}</strong><br />
	 		</td>
	 		<td class='field_field'>
	 			{$form['gbw_view_reps']}
	 		</td>
	 	</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['gf_t_sigs']}</th>
		</tr>
	 	<tr>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_usesigs']}</strong>
	 		</td>
	 		<td class='field_field'>
			<p>{$form['use_signatures']} &nbsp; {$this->lang->words['g_until']} {$form['g_sig_unit']} {$form['gbw_sig_unit_type']}</p>
			<p style='color:gray;font-size:0.8em'>{$this->lang->words['g_limit_dd']}</p>
	 		</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_sigmaximages']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['max_images']}
	 		</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_sigmaxdims']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['max_dims']}
	 		</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_sigmaxurls']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['max_urls']}
	 		</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_sigmaxtext']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['max_lines']}
	 		</td>
	 	</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['gf_t_uploads']}</th>
		</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_uglobal']}</strong>
				{$this->javascriptHelpLink('mg_upload')}
			</td>
	 		<td class='field_field'>
	 			{$form['g_attach_max']}
	 			{$this->lang->words['g_inkb']} ({$this->lang->words['g_ucurrently']}{$group['g_attach_maxdis']})
	 			<br />{$this->lang->words['g_usingle']}{$ini_max}<br />
				<span class='desctext'>{$this->lang->words['gf_uglobal_info']}</span>
	 		</td>
	 	</tr>
	 	
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_upost']}</strong>
				{$this->javascriptHelpLink('mg_upload')}
			</td>
	 		<td class='field_field'>
	 			{$form['g_attach_per_post']}
	 			{$this->lang->words['g_inkb']} ({$this->lang->words['g_ucurrently']}{$group['g_attach_per_postdis']})
	 			<br />{$this->lang->words['g_usingle']}{$ini_max}<br />
				<span class='desctext'>{$this->lang->words['gf_upost_info']}</span>
	 		</td>
	 	</tr>
	 	
	 	<tr class='guest_legend'>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gf_upersonalpho']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['p_max']}{$this->lang->words['g_inkb']}
	 			<br />{$this->lang->words['g_upersonalpho_w']}{$form['p_width']} x {$this->lang->words['g_upersonalpho_h']}{$form['p_height']}<br />
				<span class='desctext'>{$this->lang->words['g_upersonalpho_l']}</span>
	 		</td>
	 	</tr>
	 	<tr class='guest_legend'>
			<th colspan='2'>{$this->lang->words['gf_t_tagging']}<!--You're it--></th>
		</tr>
	 	<tr class='guest_legend'>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gbw_disable_tagging']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['gbw_disable_tagging']}<br />
				<span class='desctext'>{$this->lang->words['gbw_disable_tagging_desc']}</span>
	 		</td>
	 	</tr>
	 	<tr class='guest_legend'>
	 		<td class='field_title'>
	 			<strong class='title'>{$this->lang->words['gbw_disable_prefixes']}</strong>
	 		</td>
	 		<td class='field_field'>
	 			{$form['gbw_disable_prefixes']}<br />
				<span class='desctext'>{$this->lang->words['gbw_disable_prefixes_desc']}</span>
	 		</td>
	 	</tr>
	</table>
</div>
HTML;

// Got blocks from other apps?
$IPBHTML .= implode( "\n", $content['area'] );

$IPBHTML .= <<<HTML

<script type='text/javascript'>
	jQ("#tabstrip_group").ipsTabBar({tabWrap: "#tabstrip_group_content", defaultTab: "tab_{$_firstTab}" });
</script>
</div>
<div class='acp-actionbar'>
	<input type='submit' value='{$button}' class='button primary' />
</div>
</form>

<script type="text/javascript">
setUpForm();

HTML;

if( $group['g_id'] == $this->settings['guest_group'] )
{
	$IPBHTML .= "stripGuestLegend();";
}

$IPBHTML .= <<<HTML
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Build a javascript help link
 *
 * @param	string		Help ID
 * @return	string		Help Link
 * @since	2.0
 * @see		admin_core_system_quickhelp
 */
public function javascriptHelpLink( $help="" )
{
	return "( <a href='#' onclick=\"window.open('" . str_replace( '&amp;', '&', $this->settings['_base_url'] ) . "&app=core&module=help&section=quickhelp&id={$help}','Help','width=450,height=400,resizable=yes,scrollbars=yes'); return false;\">{$this->lang->words['acp_quick_help']}</a> )";
}

/**
 * Overview of groups
 *
 * @param	string		Groups HTML
 * @param	array 		Groups
 * @return	string		HTML
 */
public function groupsOverviewWrapper( $content, $g_array ) {

$new_dd = $this->registry->output->formDropdown( 'id', $g_array, 3 );

$IPBHTML = "";
//--starthtml--//

$leSigh = ( $this->request['showSecondary'] ) ? 'closed' : '';
$leBigh = ( $this->request['showSecondary'] ) ? 0 : 1;

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['g_title']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton {$leSigh}'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=groups_overview&amp;showSecondary={$leBigh}'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' /> {$this->lang->words['g_calc_with_secondary']}</a>
			</li>
			<li class='ipsActionButton inDev'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=master_xml_export'><img src='{$this->settings['skin_acp_url']}/images/icons/export.png' alt='' /> Export XML</a>
			</li>
		</ul>
	</div>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['g_usergroupman']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='1%'>&nbsp;</th>
			<th width='30%'>{$this->lang->words['g_grouptitle']}</th>
			<th width='15%' align='center' style='text-align:center'>{$this->lang->words['g_canaccessacp']}</th>
			<th width='15%' align='center' style='text-align:center'>{$this->lang->words['g_issupermod']}</th>
			<th width='10%' align='center' style='text-align:center'>{$this->lang->words['g_membercount']}</th>
			<th width='1%'>&nbsp;</th>
		</tr>
		{$content}
	</table>
</div>
<br />

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=add' method='POST' >
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['g_createnew']}</h3>
		<table class='ipsTable'>
			<tr>
				<td class='field_title'>{$this->lang->words['g_basenewon']}</th>
				<td class='field_field'>{$new_dd}</th>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['g_createbutton']}' class='button primary' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Group row
 *
 * @param	array 		Group
 * @return	string		HTML
 */
public function groupsOverviewRow( $r="" ) {
$IPBHTML = "";
//--starthtml--//

$r['_can_acp_img']		= $r['_can_acp']    ? 'accept.png' : 'cross.png';
$r['_can_supmod_img']	= $r['_can_supmod'] ? 'accept.png' : 'cross.png';
$r['_title']			= IPSMember::makeNameFormatted( $r['g_title'], $r['g_id'] );

$IPBHTML .= <<<HTML
<tr class='ipsControlRow'>
  <td><img src='{$this->settings['skin_acp_url']}/images/icons/group.png' /></td>
  <td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit&amp;id={$r['g_id']}' style='font-weight:bold'>{$r['_title']}</a> <small>({$this->lang->words['g_id']}: {$r['g_id']})</small></td>
  <td align='center'><img src='{$this->settings['skin_acp_url']}/images/icons/{$r['_can_acp_img']}' alt='-' /></td>
  <td align='center'><img src='{$this->settings['skin_acp_url']}/images/icons/{$r['_can_supmod_img']}' alt='-' /></td>
  <td align='center'>
HTML;
if ( $r['g_id'] != $this->settings['guest_group'] )
{
	$_extraLink	= '';
	
	if( $this->request['showSecondary'] ) 
	{
		$_extraLink	= "&amp;f_inc_secondary=1";
	}
	
$IPBHTML .= <<<HTML
	<a href='{$this->settings['_base_url']}app=members&amp;section=members&amp;module=members#do_results=1&amp;__update=1&amp;f_primary_group={$r['g_id']}{$_extraLink}' title='{$this->lang->words['g_listusers']}'>{$r['count']}</a>
HTML;
}
else
{
$IPBHTML .= <<<HTML
    {$r['count']}
HTML;
}
$IPBHTML .= <<<HTML
  </td>												
  <td class='col_buttons'>
	<ul class='ipsControlStrip'>
		<li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit&amp;id={$r['g_id']}' title='{$this->lang->words['g_editg']}'>{$this->lang->words['g_editg']}</a></li>
HTML;
if ( ! in_array( $r['g_id'], array( $this->settings['auth_group'], $this->settings['guest_group'], $this->settings['member_group'] ) )  )
{
$IPBHTML .= <<<HTML
		<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=delete&amp;_admin_auth_key={$this->registry->getClass('adminFunctions')->_admin_auth_key}&amp;id={$r['g_id']}");' title='{$this->lang->words['g_deleteg']}'>{$this->lang->words['g_deleteg']}</a></li>
HTML;
}
else
{
$IPBHTML .= <<<HTML
		<li class='i_delete disabled'><a href='#' onclick='return false;' title='{$this->lang->words['g_cannotdel']}' title='{$this->lang->words['g_cannotdel']}'>{$this->lang->words['g_cannotdel']}</a></li>
HTML;
}
$IPBHTML .= <<<HTML
	</ul>
  </td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Delete group confirmation
 *
 * @param	array 		Group
 * @param	int			Members with this group as primary group
 * @param	int			Members with this group as secondary group
 * @return	string		HTML
 */
public function groupDelete( $group, $primary=0, $secondary=0 ) {

$IPBHTML = "";
//--starthtml--//

//-----------------------------------------
// Grab group, and other groups
//-----------------------------------------

$mem_groups				= array();

foreach( $this->caches['group_cache'] as $g_id => $r )
{
	if( $g_id == $group['g_id'] )
	{
		continue;
	}

	$mem_groups[] = array( $r['g_id'], $r['g_title'] );
}
		
$dropDown	= $this->registry->output->formDropdown( "to_id", $mem_groups, $this->settings['member_group'] );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['g_deleting']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['g_removeconf']}{$group['g_title']}</h3>
	
	<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=dodelete' method='post'>
		<input type='hidden' name='id' value='{$group['g_id']}' />
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
		
		<table class='ipsTable'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['g_numusers']}</strong></td>
				<td class='field_field'>
					<a href='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=members&amp;__update=1&amp;f_primary_group={$group['g_id']}'>{$primary}</a>
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['g_numusers_sec']}</strong></td>
				<td class='field_field'>
					<a href='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=members&amp;__update=1&amp;f_secondary_group={$group['g_id']}'>{$secondary}</a><br />
					<span class='desctext'>{$this->lang->words['g_secuserinfod']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['g_moveusersto']}</strong></td>
				<td class='field_field'>{$dropDown}</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' class='button primary' value='{$this->lang->words['g_deletebutton']}' />
		</div>
	</form>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}


}