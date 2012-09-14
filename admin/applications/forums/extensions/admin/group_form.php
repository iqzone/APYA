<?php
/**
 * @file		group_form.php 	Forums group editing form
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2011-03-03 13:09:09 -0500 (Thu, 03 Mar 2011) $
 * @version		v3.3.3
 * $Revision: 7948 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @interface	admin_group_form__forums
 * @brief		Forums group editing form
 *
 */
class admin_group_form__forums implements admin_group_form
{	
	/**
	 * Tab name (leave it blank to use the default application title)
	 *
	 * @var		$tab_name
	 */
	public $tab_name = "";

	/**
	 * Returns HTML tabs content for the page
	 *
	 * @param	array		$group		Group data
	 * @param	integer		$tabsUsed	Number of tabs used so far (your ids should be this +1)
	 * @return	@e array Array of 'tabs' (HTML for the tabs), 'content' (HTML for the content), 'tabsUsed' (number of tabs you have used)
	 */
	public function getDisplayContent( $group=array(), $tabsUsed = 2 )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_group_form', 'forums');
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_forums' ), 'forums' );
		
		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		return array( 'tabs' => $this->html->acp_group_form_tabs( $group, ( $tabsUsed + 1 ) ), 'content' => $this->html->acp_group_form_main( $group, ( $tabsUsed + 1 ) ), 'tabsUsed' => 1 );
	}
	
	/**
	 * Process the entries for saving and return
	 *
	 * @return	@e array Array of keys => values for saving
	 */
	public function getForSave()
	{
		$return = array(
						'g_other_topics'		=>ipsRegistry::$request['g_other_topics'],
						'g_post_new_topics'		=>ipsRegistry::$request['g_post_new_topics'],
						'g_reply_own_topics'	=>ipsRegistry::$request['g_reply_own_topics'],
						'g_reply_other_topics'	=>ipsRegistry::$request['g_reply_other_topics'],
						'g_edit_posts'			=>ipsRegistry::$request['g_edit_posts'],
						'g_edit_cutoff'			=>ipsRegistry::$request['g_edit_cutoff'],
						'g_delete_own_posts'	=>ipsRegistry::$request['g_delete_own_posts'],
						'g_open_close_posts'	=>ipsRegistry::$request['g_open_close_posts'],
						'g_delete_own_topics'	=>ipsRegistry::$request['g_delete_own_topics'],
						'g_post_polls'			=>ipsRegistry::$request['g_post_polls'],
						'g_vote_polls'			=>ipsRegistry::$request['g_vote_polls'],
						'g_append_edit'			=>ipsRegistry::$request['g_append_edit'],
						'g_avoid_q'				=>ipsRegistry::$request['g_avoid_q'],
						'g_avoid_flood'			=>ipsRegistry::$request['g_avoid_flood'],
						'g_post_closed'			=>ipsRegistry::$request['g_post_closed'],
						'g_edit_topic'			=>ipsRegistry::$request['g_edit_topic'],
						'g_topic_rate_setting'	=> intval(ipsRegistry::$request['g_topic_rate_setting']),
						'g_mod_preview'         => ipsRegistry::$request['g_mod_preview'],
						'g_mod_post_unit'		=> intval( ipsRegistry::$request['g_mod_post_unit'] ),
						'g_ppd_unit'			=> intval( ipsRegistry::$request['g_ppd_unit'] ),
						'g_ppd_limit'			=> intval( ipsRegistry::$request['g_ppd_limit'] ),
						);

		return $return;
	}
}