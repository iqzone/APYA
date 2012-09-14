<?php
/**
 * @file		navigation.php 	navigation AJAX retrieve (Matt Mecham)
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		2/14/2011
 * $LastChangedDate: 2011-04-27 19:56:50 -0400 (Wed, 27 Apr 2011) $
 * @version		v3.3.3
 * $Revision: 8514 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		public_core_ajax_navigation
 * @brief		Navigation builder
 * 
 */
class public_core_ajax_navigation extends ipsAjaxCommand
{	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Set up */
		$inapp = trim( $this->request['inapp'] );
		$do    = ( ! empty( $this->request['do'] ) ) ? $this->request['do'] : 'all';
		
		/* Load navigation stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/navigation/build.php', 'classes_navigation_build' );
		$navigation = new $classToLoad( $inapp );
		
		/* Show warning if offline */
		if ( $this->settings['board_offline'] AND !$this->memberData['g_access_offline'] )
		{
			$row = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => "conf_key='offline_msg'" ) );
			
			IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
			IPSText::getTextClass('bbcode')->parse_html			= 1;
			IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
			IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
			IPSText::getTextClass('bbcode')->parsing_section	= 'global';
			
			$row['conf_value']	= IPSText::getTextClass('bbcode')->preDisplayParse( IPSText::getTextClass('bbcode')->preDbParse( $row['conf_value'] ) );

			return $this->returnHtml( $this->registry->output->getTemplate( 'global_other' )->quickNavigationOffline( $row['conf_value'] ) );
		}
				
		/* Return */
		if ( $do == 'all' )
		{
			return $this->returnHtml( $this->registry->output->getTemplate( 'global_other' )->quickNavigationWrapper( $navigation->loadApplicationTabs(), $navigation->loadNavigationData(), $navigation->getApp() ) );
		}
		else
		{
			return $this->returnHtml( $this->registry->output->getTemplate( 'global_other' )->quickNavigationPanel( $navigation->loadNavigationData(), $navigation->getApp() ) );
		}
	}
	
	
}