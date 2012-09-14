<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Group plugin skin functions
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
 
class cp_skin_group_form
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
 * Show forums group form
 *
 * @param	array 	Group data
 * @param	string	Tab ID
 * @return	string	HTML
 */
public function acp_group_form_main( $group, $tabId ) {

$guest_legend		= $group['g_id'] == $this->settings['guest_group'] ? $this->lang->words['g_applyguest'] : '';

$gbw_unit_type      = array(
							 0 => array( 0, $this->lang->words['g_dd_apprp'] ),
							 1 => array( 1, $this->lang->words['g_dd_days'] ) );
$dd_topic_rate 		= array( 
						0 => array( 0, $this->lang->words['g_no'] ), 
						1 => array( 1, $this->lang->words['g_yes1'] ), 
						2 => array( 2, $this->lang->words['g_yes2'] ) 
					);

$form							= array();
$form['g_other_topics']			= $this->registry->output->formYesNo( "g_other_topics", $group['g_other_topics'] );
$form['g_post_new_topics']		= $this->registry->output->formYesNo( "g_post_new_topics", $group['g_post_new_topics'] );
$form['g_topic_rate_setting']	= $this->registry->output->formDropdown( "g_topic_rate_setting", $dd_topic_rate, $group['g_topic_rate_setting'] );
$form['g_reply_own_topics']		= $this->registry->output->formYesNo( "g_reply_own_topics", $group['g_reply_own_topics'] );
$form['g_reply_other_topics']	= $this->registry->output->formYesNo( "g_reply_other_topics", $group['g_reply_other_topics'] );
$form['g_edit_posts']			= $this->registry->output->formYesNo( "g_edit_posts", $group['g_edit_posts'] );
$form['g_edit_cutoff']			= $this->registry->output->formInput( "g_edit_cutoff", $group['g_edit_cutoff'] );
$form['g_append_edit']			= $this->registry->output->formYesNo( "g_append_edit", $group['g_append_edit'] );
$form['g_delete_own_posts']		= $this->registry->output->formYesNo( "g_delete_own_posts", $group['g_delete_own_posts'] );
$form['g_open_close_posts']		= $this->registry->output->formYesNo( "g_open_close_posts", $group['g_open_close_posts'] );
$form['g_edit_topic']			= $this->registry->output->formYesNo( "g_edit_topic", $group['g_edit_topic'] );
$form['g_delete_own_topics']	= $this->registry->output->formYesNo( "g_delete_own_topics", $group['g_delete_own_topics'] );
$form['g_post_polls']			= $this->registry->output->formYesNo( "g_post_polls", $group['g_post_polls'] );
$form['g_vote_polls']			= $this->registry->output->formYesNo( "g_vote_polls", $group['g_vote_polls'] );
$form['g_avoid_flood']			= $this->registry->output->formYesNo( "g_avoid_flood", $group['g_avoid_flood'] );
$form['g_avoid_q']				= $this->registry->output->formYesNo( "g_avoid_q", $group['g_avoid_q'] );
$form['g_post_closed']			= $this->registry->output->formYesNo( "g_post_closed", $group['g_post_closed'] );
$form['g_mod_preview']			= $this->registry->output->formYesNo( "g_mod_preview", $group['g_mod_preview'] );
$form['g_mod_post_unit']		= $this->registry->output->formSimpleInput( "g_mod_post_unit", $group['g_mod_post_unit'], 3 );
$form['gbw_mod_post_unit_type']	= $this->registry->output->formDropdown( "gbw_mod_post_unit_type", $gbw_unit_type, $group['gbw_mod_post_unit_type'] );
$form['g_ppd_limit']			= $this->registry->output->formSimpleInput( "g_ppd_limit", $group['g_ppd_limit'], 3 );
$form['g_ppd_unit']				= $this->registry->output->formSimpleInput( "g_ppd_unit", $group['g_ppd_unit'], 3 );
$form['gbw_ppd_unit_type']		= $this->registry->output->formDropdown( "gbw_ppd_unit_type", $gbw_unit_type, $group['gbw_ppd_unit_type'] );

$form['gbw_soft_delete']			= $this->registry->output->formYesNo( "gbw_soft_delete", $group['gbw_soft_delete'] );
$form['gbw_soft_delete_own']		= $this->registry->output->formYesNo( "gbw_soft_delete_own", $group['gbw_soft_delete_own'] );
$form['gbw_soft_delete_own_topic']	= $this->registry->output->formYesNo( "gbw_soft_delete_own_topic", $group['gbw_soft_delete_own_topic'] );
$form['gbw_un_soft_delete']			= $this->registry->output->formYesNo( "gbw_un_soft_delete", $group['gbw_un_soft_delete'] );
$form['gbw_soft_delete_see']		= $this->registry->output->formYesNo( "gbw_soft_delete_see", $group['gbw_soft_delete_see'] );
$form['gbw_soft_delete_topic']		= $this->registry->output->formYesNo( "gbw_soft_delete_topic", $group['gbw_soft_delete_topic'] );
$form['gbw_un_soft_delete_topic']	= $this->registry->output->formYesNo( "gbw_un_soft_delete_topic", $group['gbw_un_soft_delete_topic'] );
$form['gbw_soft_delete_topic_see']	= $this->registry->output->formYesNo( "gbw_soft_delete_topic_see", $group['gbw_soft_delete_topic_see'] );
$form['gbw_soft_delete_reason']		= $this->registry->output->formYesNo( "gbw_soft_delete_reason", $group['gbw_soft_delete_reason'] );
$form['gbw_soft_delete_see_post']	= $this->registry->output->formYesNo( "gbw_soft_delete_see_post", $group['gbw_soft_delete_see_post'] );
$form['gbw_view_online_lists']    	= $this->registry->output->formYesNo( "gbw_view_online_lists", $group['gbw_view_online_lists'] );

/* Follow the. . . */
$modCount = $this->DB->buildAndFetch( array( 'select' => 'count(*) as cnt',
										     'from'	  => 'moderators',
										     'where'  => 'group_id=' . $group['g_id'] ) );
										     
$IPBHTML = "";

$IPBHTML .= <<<EOF
<div id='tab_GROUPS_{$tabId}_content'>
	<div>
		<table class='ipsTable'>
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gf_t_rating']}</strong></th>
			</tr>
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_topic_rate_setting']}</strong>
				</td>
				<td class='field_field'>
		 			{$form['g_topic_rate_setting']}
				</td>
		 	</tr>
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gf_t_viewing']}</strong></th>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_other_topics']}</strong>
				</td>
				<td class='field_field'>
		 			{$form['g_other_topics']}
				</td>
		 	</tr>
		 	<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['gbw_view_online_lists']}</strong>
				</td>
				<td class='field_field'>
		 			{$form['gbw_view_online_lists']}
				</td>
		 	</tr>
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gf_t_posting']}</strong></th>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_post_new_topics']}</strong>
				</td>
				<td class='field_field'>
		 			{$form['g_post_new_topics']}
				</td>
		 	</tr>
		 	
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_reply_own_topics']}</strong>
				</td>
				<td class='field_field'>
		 			{$form['g_reply_own_topics']}
				</td>
		 	</tr>
		 	
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_reply_other_topics']}</strong>
				</td>
				<td class='field_field'>
		 			{$form['g_reply_other_topics']}
				</td>
		 	</tr>
		 	<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gf_t_editing']}</strong></th>
			</tr>
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_edit_posts']}</strong>
				</td>
				<td class='field_field'>
					{$form['g_edit_posts']}
				</td>
		 	</tr>
		 	
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_edit_cutoff']}</strong>
				</td>
				<td class='field_field'>
		 			{$form['g_edit_cutoff']}
					<div class='desctext'>{$this->lang->words['gf_edit_cuttoff_info']}</div>
				</td>
		 	</tr>
		 	
		 	<tr>
				<td class='field_title'>
		 			<strong class='title'>{$this->lang->words['gf_append_edit']}</strong>
				</td>
				<td class='field_field'>
		 			{$form['g_append_edit']}
				</td>
		 	</tr>
		 	
			<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_edit_topic']}</strong>
				</td>
				<td class='field_field'>
					{$form['g_edit_topic']}
				</td>
		 	</tr>
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gf_t_deleting']}</strong></th>
			</tr>
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_delete_own_posts']}</strong>
				</td>
		 		<td class='field_field'>
					{$form['g_delete_own_posts']}
					<p class='desctext'>{$this->lang->words['hard_delete_info']}</p>
					<span class='desctext'><strong>{$this->lang->words['sup_mod_already']}</strong></span>
				</td>
		 	</tr>
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_delete_own_topics']}</strong>
				</td>
				<td class='field_field'>
		 			{$form['g_delete_own_topics']}
					<p class='desctext'>{$this->lang->words['hard_delete_info']}</p>
						<span class='desctext'><strong>{$this->lang->words['sup_mod_already']}</strong></span>
				</td>
		 	</tr>
		 	
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_bw_soft_delete_own']}</strong>
				</td>
				<td class='field_field'>
		 			{$form['gbw_soft_delete_own']}
					<p class='desctext'>{$this->lang->words['soft_delete_info']}</p>
						<span class='desctext'><strong>{$this->lang->words['sup_mod_already']}</strong></span>
				</td>
		 	</tr>
		 	
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gf_t_openclose']}</strong></th>
			</tr>
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['g_open_close_posts']}</strong>
				</td>
				<td class='field_field'>
		 			{$form['g_open_close_posts']}
				</td>
		 	</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_post_closed']}</strong>
				</td>
				<td class='field_field'>
					{$form['g_post_closed']}
				</td>
		 	</tr>
		 	<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gf_t_polling']}</strong></th>
			</tr>
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_post_polls']}</strong>
				</td>
				<td class='field_field'>
					{$form['g_post_polls']}
				</td>
		 	</tr>
		 	
		 	<tr class='guest_legend'>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_vote_polls']}</strong>
				</td>
				<td class='field_field'>
					{$form['g_vote_polls']}<br />
					{$guest_legend}
				</td>
		 	</tr>
		 	<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gf_t_avoidance']}</strong></th>
			</tr>
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_avoid_flood']}</strong>
				</td>
				<td class='field_field'>
					{$form['g_avoid_flood']}
				</td>
		 	</tr>
		 	
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_avoid_q']}</strong>
				</td>
				<td class='field_field'>
					{$form['g_avoid_q']}
				</td>
		 	</tr>
		 	
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gf_t_restrictions']}</strong></th>
			</tr>
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_mod_preview']}</strong>
				</td>
				<td class='field_field'>
					<p>{$form['g_mod_preview']}<span class='guest_legend'> &nbsp; {$this->lang->words['g_until']} {$form['g_mod_post_unit']} {$form['gbw_mod_post_unit_type']}</span></p>
					<p class='guest_legend' style='color:gray;font-size:0.8em'>{$this->lang->words['g_limit_dd']}</p>
				</td>
		 	</tr>
			<tr class='guest_legend'>
		 		<td class='field_title'>
					<strong class='title'>
							{$this->lang->words['gf_ppd_limit']}
						   <p style='color:gray;font-size:0.8em'>{$this->lang->words['g_limit_no']}</p>
					</strong>
				</td>
				<td class='field_field'>
					<p>{$this->lang->words['g_max']} {$form['g_ppd_limit']} {$this->lang->words['g_ppd']} {$form['g_ppd_unit']} {$form['gbw_ppd_unit_type']}</p>
					<p style='color:gray;font-size:0.8em'>{$this->lang->words['g_limit_dd']}</p>
				</td>
		 	</tr>
EOF;

if ( $modCount['cnt'] )
{
	$form['gbw_hide_leaders_page']	= $this->registry->output->formYesNo( "gbw_hide_leaders_page", $group['gbw_hide_leaders_page'] );
	
	$IPBHTML .= <<<EOF
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gf_mod_title']}</strong></th>
			</tr>
		 	<tr>
		 		<td class='field_title'>
					<strong class='title'>{$this->lang->words['gf_mod_remove']}</strong>
				</td>
				<td class='field_field'>
					<p>{$form['gbw_hide_leaders_page']}</p>
					<p style='color:gray;font-size:0.8em'>{$this->lang->words['gf_mod_remove_desc']}</p>
				</td>
		 	</tr>
EOF;
}
else
{
$IPBHTML .= <<<EOF
			<input type='hidden' name='gbw_hide_leaders_page' value='{$group['gbw_hide_leaders_page']}' />
EOF;
}

$IPBHTML .= <<<EOF
		</table>
	</div>
</div>

EOF;

return $IPBHTML;
}

/**
 * Display forum group form tabs
 *
 * @param	array 	Group data
 * @param	string	Tab id
 * @return	string	HTML
 */
public function acp_group_form_tabs( $group, $tabId ) {

$IPBHTML = "";

$IPBHTML .= <<<EOF
	<li id='tab_GROUPS_{$tabId}'>{$this->lang->words['g_forperm']}</li>
EOF;

return $IPBHTML;
}

}