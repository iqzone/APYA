<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Grab attachments and return via AJAX (AJAX)
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_ajax_attachments extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$topic_id		= intval( $this->request['tid'] );
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_stats' ), 'forums' );
		
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if ( ! $topic_id )
        {
        	$this->returnJsonError( $this->lang->words['notopic_attach'] );
        }
        
        //-----------------------------------------
        // get topic..
        //-----------------------------------------
        
        $topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $topic_id ) );
        
        if ( ! $topic['topic_hasattach'] )
        {
        	$this->returnJsonError( $this->lang->words['topic_noattach'] );
        }
        
        //-----------------------------------------
        // Check forum..
        //-----------------------------------------
        
        if ( $this->registry->getClass('class_forums')->forumsCheckAccess( $topic['forum_id'], 0, 'forum', $topic, true ) === false )
		{
			$this->returnJsonError( $this->lang->words['topic_noperms'] );
		}
		
		$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('forums') . '/modules_public/forums/attach.php', 'public_forums_forums_attach' );
		$attach	= new $classToLoad( $this->registry );
		$attach->makeRegistryShortcuts( $this->registry );
		
		$attachHTML	= $attach->getAttachments( $topic );
		
		if ( !$attachHTML )
		{
			$this->returnJsonError( $this->lang->words['ajax_nohtml_return'] );
		}
		else
		{
			$this->returnHtml( $attachHTML );
		}
	}
}