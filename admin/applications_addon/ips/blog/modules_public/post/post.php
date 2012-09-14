<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Post Blog
 * Last Updated: $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 4 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_blog_post_post extends ipsCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{	
		/* Make a action lookup table */
		$action_codes = array(
								'showform'	 	=> 'new_entry',
								'dopost' 	 	=> 'new_entry',
								'editentry'	 	=> 'edit_entry',
								'doeditentry'	=> 'edit_entry',
								'addcblock'	 	=> 'add_cblock',
								'doaddcblock'	=> 'add_cblock',
								'editcblock'	=> 'edit_cblock',
								'doeditcblock'	=> 'edit_cblock',
							);
		
		/* Make sure our input CODE element is legal. */
		if( ! isset( $action_codes[ $this->request['do'] ] ) )
		{
			$this->registry->output->showError( 'missing_files', 106144 );
		}
		
		//-----------------------------------------
		// Require and run our associated library file for this action.
		// this imports an extended class for this Post class.
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead('javascript', $this->settings['public_dir'] . 'js/ips.blog.js');
		
		require_once( IPSLib::getAppDir('blog') . "/sources/classes/post/blogPost.php" );/*noLibHook*/
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . "/sources/classes/post/blogPost_{$action_codes[ $this->request['do'] ]}.php", 'postFunctions', 'blog' );
		
		$post_functions = new $classToLoad( $registry );
		$post_functions->execute( $this->request['do'] );
	}
}
