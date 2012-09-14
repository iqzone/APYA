<?php
/**
 * @file		modcp.php 	Moderator control panel AJAX retrieve tab
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mark $
 * @since		2/14/2011
 * $LastChangedDate: 2011-11-16 11:36:38 -0500 (Wed, 16 Nov 2011) $
 * @version		v3.3.3
 * $Revision: 9829 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		public_core_ajax_modcp
 * @brief		Moderator control panel
 * 
 */
class public_core_ajax_modcp extends ipsAjaxCommand
{	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load basic things
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_modcp' ) );

		//-----------------------------------------
		// Which road are we going to take?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'index':
				$this->_getTab();
			break;
			
			case 'getmembers':
				$this->_getMembers();
			break;
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->getClass('output')->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Returns possible matches for the string input
	 *
	 * @return	@e void		Outputs to screen
	 */
	protected function _getMembers()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
	
		$name = IPSText::convertUnicode( $this->convertAndMakeSafe( $this->request['name'], 0 ), true );
		$name = IPSText::convertCharsets( $name, 'utf-8', IPS_DOC_CHAR_SET );
	
		//-----------------------------------------
		// Check length
		//-----------------------------------------
	
		if ( IPSText::mbstrlen( $name ) < 3 )
		{
			$this->returnJsonError( 'requestTooShort' );
		}
	
		//-----------------------------------------
		// Try query...
		//-----------------------------------------
	
		$this->DB->build( array( 'select'	=> 'm.members_display_name, m.member_id, m.members_seo_name, m.member_group_id',
								 'from'	    => array( 'members' => 'm' ),
								 'where'	=> "m.members_l_display_name LIKE '" . $this->DB->addSlashes( strtolower( $name ) ) . "%'",
								 'order'	=> $this->DB->buildLength( 'm.members_display_name' ) . ' ASC',
								 'limit'	=> array( 0, 15 ),
									 'add_join' => array( array( 'select' => 'p.*',
														     'from'   => array( 'profile_portal' => 'p' ),
														     'where'  => 'p.pp_member_id=m.member_id',
														     'type'   => 'left' ) ) ) );
		$this->DB->execute();
	
		//-----------------------------------------
		// Got any results?
		//-----------------------------------------
	
		if ( ! $this->DB->getTotalRows() )
		{
			$this->returnJsonArray( array( ) );
		}
	
		$return = array();
	
		while( $r = $this->DB->fetch() )
		{
			$url	= $this->registry->output->buildSEOUrl( "app=core&amp;module=modcp&amp;do=editmember&amp;mid={$r['member_id']}", 'public' );
			$photo	= IPSMember::buildProfilePhoto( $r );
			$group	= IPSMember::makeNameFormatted( '' , $r['member_group_id'] );
	
			$return[ $r['member_id'] ] = array( 'name' 	=> $r['members_display_name'],
												'showas'=> '<strong>' . $r['members_display_name'] . '</strong> (' . $group . ')',
												'img'	=> $photo['pp_thumb_photo'],
												'img_w'	=> $photo['pp_mini_width'],
												'img_h'	=> $photo['pp_mini_height'],
												'url'	=> $url,
											);
		}
	
		$this->returnJsonArray( $return );
	}
	
	/**
	 * Retrieve modcp tab based
	 *
	 * @return	@e void
	 */
	protected function _getTab()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		ipsRegistry::getAppClass( 'forums' );
		
		$app		= trim($this->request['fromapp']);
		$plugin		= trim($this->request['tab']);
		$moderator	= $this->registry->class_forums->getModerator();
		$_output	= '';
		
		//-----------------------------------------
		// Get plugin output
		//-----------------------------------------

		if( is_file( IPSLib::getAppDir( $app ) . '/extensions/modcp/plugin_' . $app . '_' . $plugin . '.php' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( $app ) . '/extensions/modcp/plugin_' . $app . '_' . $plugin . '.php', 'plugin_' . $app . '_' . $plugin, $app );
			$_thisPlugin	= new $classToLoad( $this->registry );
			
			$_output		= $_thisPlugin->executePlugin( $moderator );
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		if( $_output )
		{
			$this->returnJsonArray( array( 'html' => $_output ) );
		}
		else
		{
			$this->returnJsonError( $this->lang->words['plugin_not_found'] );
		}
	}
}