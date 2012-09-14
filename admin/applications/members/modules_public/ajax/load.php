<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Profile AJAX Tab Loader
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_ajax_load extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id = intval( ipsRegistry::$request['member_id'] );
		$md5check  = IPSText::md5Clean( $this->request['md5check'] );
		$CONFIG    = array();
		
		$tab = explode( ':', ipsRegistry::$request['tab'] );
		$app = substr( IPSText::alphanumericalClean( str_replace( '..', '', trim( $tab[0] ) ) ), 0, 20 );
		$tab = substr( IPSText::alphanumericalClean( str_replace( '..', '', trim( $tab[1] ) ) ), 0, 20 );

		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );

		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->member->form_hash )
    	{
			$this->returnString( 'error' );
    	}

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id );
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['member_id'] )
    	{
			$this->returnString( 'error' );
    	}
		
		//-----------------------------------------
		// Load config
		//-----------------------------------------

		if( !is_file( IPSLib::getAppDir( $app ) . '/extensions/profileTabs/' . $tab . '.conf.php' ) )
		{
			$this->returnString( 'error' );
		}
		
		require( IPSLib::getAppDir( $app ) . '/extensions/profileTabs/' . $tab . '.conf.php' );/*noLibHook*/
		
		//-----------------------------------------
		// Active?
		//-----------------------------------------
		
		if ( ! $CONFIG['plugin_enabled'] )
		{
			$this->returnString( 'error' );
		}
		
		//-----------------------------------------
		// Load main class...
		//-----------------------------------------
		
		if( !is_file( IPSLib::getAppDir( $app ) . '/extensions/profileTabs/' . $tab . '.php' ) )
		{
			$this->returnString( 'error' );
		}
		
		require( IPSLib::getAppDir( 'members' ) . '/sources/tabs/pluginParentClass.php' );/*noLibHook*/
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( $app ) . '/extensions/profileTabs/' . $tab . '.php', 'profile_' . $tab, $app );
		$plugin      = new $classToLoad( $this->registry );

		$html = $plugin->return_html_block( $member );
		
		//-----------------------------------------
		// Return it...
		//-----------------------------------------

		$this->returnHtml( $html );
	}
}