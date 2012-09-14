<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Forum member form plugin
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		14th May 2003
 * @version		$Rev: 10721 $
 */

class cp_skin_member_form
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
 * Main ACP member form
 *
 * @param	array 	Member data
 * @return	string	HTML
 */
public function acp_member_form_main( $member, $tabID ) {

$masks = array();

ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'forum_perms', 'order' => 'perm_name' ) );
ipsRegistry::DB()->execute();

while( $data = ipsRegistry::DB()->fetch() )
{	
	$masks[] = array( $data['perm_id'], $data['perm_name'] );
}

$_restrict_tick		= '';
$_restrict_timespan	= '';
$_restrict_units	= '';
$units				= array( 0 => array( 'h', $this->lang->words['m_hours'] ), 1 => array( 'd', $this->lang->words['m_days'] ) );

if ( $member['restrict_post'] == 1 )
{
	$_restrict_tick = 'checked="checked"';
}
elseif ($member['restrict_post'] > 0)
{
	$rest_arr = IPSMember::processBanEntry( $member['restrict_post'] );

	$hours  = ceil( ( $rest_arr['date_end'] - time() ) / 3600 );

	if( $hours < 0 )
	{
		$rest_arr['units']		= '';
		$rest_arr['timespan']	= '';
	}
	else if ( $hours > 24 and ( ($hours / 24) == ceil($hours / 24) ) )
	{
		$rest_arr['units']		= 'd';
		$rest_arr['timespan']	= $hours / 24;
	}
	else
	{
		$rest_arr['units']		= 'h';
		$rest_arr['timespan']	= $hours;
	}
}

$_restrict_timespan		= ipsRegistry::getClass('output')->formSimpleInput('post_timespan', $rest_arr['timespan'] );
$_restrict_units		= ipsRegistry::getClass('output')->formDropdown('post_units', $units, $rest_arr['units'] );

$_mod_tick		= '';
$_mod_timespan	= '';
$_mod_units		= '';

if ( $member['mod_posts'] == 1 )
{
	$_mod_tick = 'checked="checked"';
}
elseif ($member['mod_posts'] > 0)
{
	$mod_arr = IPSMember::processBanEntry( $member['mod_posts'] );
	
	$hours  = ceil( ( $mod_arr['date_end'] - time() ) / 3600 );
		
	if( $hours < 0 )
	{
		$mod_arr['units']		= '';
		$mod_arr['timespan']	= '';
	}
	else if ( $hours > 24 and ( ($hours / 24) == ceil($hours / 24) ) )
	{
		$mod_arr['units']		= 'd';
		$mod_arr['timespan']	= $hours / 24;
	}
	else
	{
		$mod_arr['units']		= 'h';
		$mod_arr['timespan']	= $hours;
	}
}

$_mod_timespan			= ipsRegistry::getClass('output')->formSimpleInput('mod_timespan', $mod_arr['timespan'] );
$_mod_units				= ipsRegistry::getClass('output')->formDropdown('mod_units', $units, $mod_arr['units'] );

$form_override_masks	= ipsRegistry::getClass('output')->formMultiDropdown( "org_perm_id[]", $masks, explode( ",", $member['org_perm_id'] ), 8, 'org_perm_id' );
$form_posts				= ipsRegistry::getClass('output')->formInput( "posts", $member['posts'] );

$IPBHTML = "";

$IPBHTML .= <<<EOF
	
	<div id='tab_MEMBERS_{$tabID}_content'>
		<table class='ipsTable double_pad'>
			
			<tr>
				<th colspan='2'>{$this->lang->words['sm_settings']}</th>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['mem_posts']}</strong></td>
				<td class='field_field'>
					<span id='MF__posts'>{$form_posts}</span>
				</td>
			</tr>
		</table>
	
		<table class='ipsTable double_pad'>
			<tr>
				<th colspan='2'>{$this->lang->words['sm_access']}</th>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['m_overrride']}</strong></td>
				<td class='field_field'>
					<span id='MF__ogpm'>{$form_override_masks}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['m_modprev']}</strong></td>
				<td class='field_field'>
					<input type='checkbox' name='mod_indef' id='mod_indef' value='1' {$_mod_tick}> {$this->lang->words['m_modindef']}
					<br />
					{$this->lang->words['m_orfor']}
					{$_mod_timespan} {$_mod_units}
				</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['m_restrict']}</strong></td>
				<td class='field_field'>
					<input type='checkbox' name='post_indef' id='post_indef' value='1' {$_restrict_tick}> {$this->lang->words['m_restrictindef']}
					<br />
					{$this->lang->words['m_orfor']}
					{$_restrict_timespan} {$_restrict_units}
				</td>
			</tr>
		</table>
	</div>

EOF;

return $IPBHTML;
}

/**
 * Forums member tabs
 *
 * @param	array 	Member data
 * @return	string	HTML
 */
public function acp_member_form_tabs( $member, $tabID ) {

$IPBHTML = "";

$IPBHTML .= <<<EOF
	<li id='tab_MEMBERS_{$tabID}'>{$this->lang->words['m_details']}</li>
EOF;

return $IPBHTML;
}

/**
 * Delete posts confirmation page
 *
 * @param	array 	Member data
 * @param	integer	Number of topics
 * @param	integer	Number of posts
 * @return	string	HTML
 */
public function deletePostsStart( $member, $topics, $posts ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=deleteposts_process&amp;member_id={$member['member_id']}&amp;name={$member['name']}' method='POST'>
<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
<div class='acp-box'>
 <h3>{$this->lang->words['mem_delete_posts_title']} {$member['members_display_name']}</h3>
 <table class='ipsTable'>
 <tr>
  <td class='field_title'><strong class='title'>{$this->lang->words['mem_delete_delete_posts']}</strong></td>
  <td class='field_field'><input type='checkbox' value='1' name='dposts' class='input_text' /><br /><span class='desctext'>{$this->lang->words['mem_delete_delete_posts_desc']}</span></td>
 </tr>
 <tr>
  <td class='field_title'><strong class='title'>{$this->lang->words['mem_delete_delete_topics']}</strong></td>
  <td class='field_field'><input type='checkbox' value='1' name='dtopics' class='input_text' /><br /><span class='desctext'>{$this->lang->words['mem_delete_delete_topics_desc']}</span></td>
 </tr>
 <tr>
  <td class='field_title'><strong class='title'>{$this->lang->words['mem_delete_delete_pergo']}</strong></td>
  <td class='field_field'><input type='input' value='50' size='3' name='dpergo' class='input_text' /><br /><span class='desctext'>{$this->lang->words['mem_delete_delete_pergo_desc']}</span></td>
 </tr>
 </table>
 <div class='acp-actionbar'><input type='submit' class='button primary' value='{$this->lang->words['mem_delete_process']}' /></div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

}