<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * AJAX Sharelinks
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_ajax_sharelinks extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
    	/* load language */
    	$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_emails' ), 'core' );
    	
    	/* Do it */
    	switch( $this->request['do'] )
    	{
    		case 'twitterForm':
    			return $this->_twitterForm();
    		break;
    		case 'twitterGo':
    			return $this->_twitterGo();
    		break;
    		case 'facebookForm':
    			return $this->_facebookForm();
    		break;
    		case 'facebookGo':
    			return $this->_facebookGo();
    		break;
    		case 'savePostPrefs':
    			return $this->_savePostPrefs();
    		break;
    	}
	}
	
	/**
	 * Stores post prefs for share links
	 */
	protected function _savePostPrefs()
	{
		IPSMember::setToMemberCache( $this->memberData, array( 'postSocialPrefs' => array( 'facebook' => intval( $_POST['facebook'] ), 'twitter' => intval( $_POST['twitter'] ) ) ) );
		
		$this->returnJsonArray( array( 'status' => 'ok' ) );
	}
	
	/**
	 * Displays a form of facebook stuff. It's really that exciting.
	 *
	 * @Deprecated as of 3.3 - now using Facebook standard share button
	 * @return	@e void		[Outputs HTML to browser AJAX call]
	 */
	protected function _facebookForm()
	{
		/* Ensure we have a twitter account and that */
		if ( $this->memberData['member_id'] AND $this->memberData['fb_uid'] AND $this->memberData['fb_token'] )
		{
			/* Connect to the Facebook */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php', 'facebook_connect' );
			$connect	 = new $classToLoad( $this->registry );
			
			try
			{
				$userData = $connect->fetchUserData();
				
				$this->returnHtml( $this->registry->output->getTemplate('global_other')->facebookPop( $userData ) );
			}
			catch( Exception $e )
			{
				$this->returnHtml( '.' );
			}
		}
		else
		{
			/* Oh go on then */
			$this->returnHtml( $this->registry->output->getTemplate('global_other')->facebookPop( array() ) );
		}
	}
	
	/**
	 * Go go Facebook go
	 * 
	 * @Deprecated as of 3.3 - now using Facebook standard share button
	 * @return	@e void		[Outputs HTML to browser AJAX call]
	 */
	protected function _facebookGo()
	{
		/* INIT */
		$comment = trim( urldecode( $_POST['comment'] ) );
		$url     = trim( urldecode( $_POST['url'] ) );
		$title   = trim( urldecode( $_POST['title'] ) );
		$comment = ( $comment == $this->lang->words['fb_share_default'] ) ? '' : $comment;
		
		/* Ensure title is correctly de-html-ized */
		$title = IPSText::UNhtmlspecialchars( $title );
		
		/* Ensure we have a twitter account and that */
		if ( $this->memberData['member_id'] AND $this->memberData['fb_uid'] AND $this->memberData['fb_token'] )
		{
			/* Connect to the Facebook */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/facebook/connect.php', 'facebook_connect' );
			$connect	 = new $classToLoad( $this->registry );
			
			try
			{
				$userData = $connect->fetchUserData();				
				
				if ( $userData['first_name'] )
				{
					/* Log it */
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/share/links.php', 'share_links' );
					$share = new $classToLoad( $this->registry, 'facebook' );
					$share->log( $url, $title );
					
					$connect->postLinkToWall( $url, $comment );
					
					$this->returnHtml( $this->registry->output->getTemplate('global_other')->facebookDone( $userData ) );
				}
				else
				{
					$this->returnHtml( 'finchersaysno' );
				}
				
			}
			catch( Exception $e )
			{
				$this->returnHtml( 'finchersaysno' );
			}
		}
		else
		{
			/* Bog off */
			$this->returnString( 'finchersaysno' );
		}
	}

		
	/**
	 * Go go twitter go
	 * 
	 * @return	@e void		[Outputs HTML to browser AJAX call]
	 */
	protected function _twitterGo()
	{
		/* INIT */
		$tweet = trim( urldecode( $_POST['tweet'] ) );
		$url   = trim( urldecode( $_POST['url'] ) );
		$title = trim( urldecode( $_POST['title'] ) );
		
		/* Ensure title is correctly de-html-ized */
		$title = IPSText::UNhtmlspecialchars( $title );
		
		/* Ensure we have a twitter account and that */
		if ( $this->memberData['member_id'] AND $this->memberData['twitter_id'] AND $this->memberData['twitter_token'] AND $this->memberData['twitter_secret'] )
		{
			/* Connect to the twitter */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/twitter/connect.php', 'twitter_connect' );
			$connect = new $classToLoad( $this->registry, $this->memberData['twitter_token'], $this->memberData['twitter_secret'] );
			$user    = $connect->fetchUserData();
			
			if ( $user['id'] )
			{
				$sid = $connect->updateStatusWithUrl( $tweet, $url );
				
				if ( $sid )
				{	
					/* Log it */
					$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/share/links.php', 'share_links' );
					$share = new $classToLoad( $this->registry, 'twitter' );
					$share->log( $url, $title );
					
					$user['status']['id'] = $sid;
					$this->returnHtml( $this->registry->output->getTemplate('global_other')->twitterDone( $user ) );
				}
				else
				{
					/* Bog off */
					$this->returnString( 'failwhale' );
				}
			}
			else
			{
				/* Bog off */
				$this->returnString( 'failwhale' );
			}
		}
		else
		{
			/* Bog off */
			$this->returnString( 'failwhale' );
		}
	}
	
	/**
	 * Displays a form of twitter stuff. It's really that exciting.
	 *
	 * @return	@e void		[Outputs HTML to browser AJAX call]
	 */
	protected function _twitterForm()
	{
		/* Ensure we have a twitter account and that */
		if ( $this->memberData['member_id'] AND $this->memberData['twitter_id'] AND $this->memberData['twitter_token'] AND $this->memberData['twitter_secret'] )
		{
			/* Connect to the twitter */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/twitter/connect.php', 'twitter_connect' );
			$connect = new $classToLoad( $this->registry, $this->memberData['twitter_token'], $this->memberData['twitter_secret'] );
			$user    = $connect->fetchUserData();
			
			if ( $user['id'] )
			{
				$this->returnHtml( $this->registry->output->getTemplate('global_other')->twitterPop( $user ) );
			}
			else
			{
				/* Bog off */
				$this->returnHtml( 'x' );
			}
		}
		else
		{
			/* Bog off */
			$this->returnHtml( 'x' );
		}
	}
}