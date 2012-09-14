<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Group property updater (Gallery)
 * Last Updated: $Date: 2011-05-18 12:10:05 -0400 (Wed, 18 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @since		1st march 2002
 * @version		$Revision: 8829 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_group_form__gallery implements admin_group_form
{	
	/**
	 * Tab name
	 * This can be left blank and the application title will
	 * be used
	 *
	 * @access	public
	 * @var		string
	 */
	public $tab_name = "";

	
	/**
	 * Returns content for the page.
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @param	array 				Group data
	 * @param	integer				Number of tabs used so far
	 * @return	@e array 				Array of tabs, content
	 */
	public function getDisplayContent( $group=array(), $tabsUsed = 2 )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = ipsRegistry::getClass('output')->loadTemplate( 'cp_skin_gallery_group_form', 'gallery' );
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_gallery' ), 'gallery' );

		//-----------------------------------------
		// Show...
		//-----------------------------------------
		
		return array( 'tabs' => $this->html->acp_gallery_group_form_tabs( $group, ( $tabsUsed + 1 ) ), 'content' => $this->html->acp_gallery_group_form_main( $group, ( $tabsUsed + 1 ) ), 'tabsUsed' => 1 );
	}
	
	/**
	 * Process the entries for saving and return
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @return	@e array 				Array of keys => values for saving
	 */
	public function getForSave()
	{
		$return = array( 	'g_max_diskspace'		=> intval(ipsRegistry::$request['g_max_diskspace']),
							'g_max_upload'	  		=> intval(ipsRegistry::$request['g_max_upload']),
							'g_max_transfer'		=> intval(ipsRegistry::$request['g_max_transfer']),
							'g_max_views'			=> intval(ipsRegistry::$request['g_max_views']),
							'g_create_albums'		=> intval(ipsRegistry::$request['g_create_albums']),
							'g_album_limit'	 		=> intval(ipsRegistry::$request['g_album_limit']),
							'g_img_album_limit'  	=> intval(ipsRegistry::$request['g_img_album_limit']),
							'g_comment'				=> intval(ipsRegistry::$request['g_comment']),
							'g_del_own'				=> intval(ipsRegistry::$request['g_del_own']),
							'g_edit_own'			=> intval(ipsRegistry::$request['g_edit_own']),
							'g_mod_albums'	  		=> intval(ipsRegistry::$request['g_mod_albums']),
							'g_album_private'  		=> intval(ipsRegistry::$request['g_album_private']),
							'g_img_local'			=> intval(ipsRegistry::$request['g_img_local']),
							'g_movies'		 		=> intval(ipsRegistry::$request['g_movies']),
							'g_movie_size'	  		=> intval(ipsRegistry::$request['g_movie_size']),
							'g_gallery_use'	  		=> intval(ipsRegistry::$request['g_gallery_use']) );

		return $return;
	}
}