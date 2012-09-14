<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog mark blogs as read
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_blog_actions_mark extends ipsCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		if( !$this->memberData['member_id'] )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=blog', 'false', false, 'app=blog' );
		}

		switch( $this->request['do'] )
		{
			case 'markallread':
				$this->_markAllRead();
			break;
			
			case 'markread':
				$this->_markRead();
			break;
		}
		
		$this->registry->output->showError( 'incorrect_use', 10642 );
	}
	
	/**
	* Mark all blogs read and return to index
	*
	* @access	protected
	* @author	Brandon Farber
	* @return   void
	*/
	protected function _markAllRead()
	{
		$this->registry->classItemMarking->markAppAsRead('blog');

		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=blog&amp;type=all', 'false', false, 'app=blog' );
	}
	
	/**
	* Mark a blog read and return to the index
	*
	* @access	protected
	* @author	Brandon Farber
	* @return   void
	*/
	protected function _markRead()
	{
		$blog_id	= intval( $this->request['blogid'] );
		$blog		= $this->DB->buildAndFetch( array ( 'select' => 'blog_id, blog_seo_name', 'from' => 'blog_blogs', 'where' => "blog_id={$blog_id}" ) );

		if ( !$blog['blog_id'] )
		{
			$this->registry->output->showError( 'incorrect_use', 10643, null, null, 404 );
		}
		
		$this->registry->classItemMarking->markRead( array( 'blogID' => $blog_id ) );
		
		if ( isset( $this->request['return'] ) AND $this->request['return'] == 'blog' )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=blog&amp;module=display&amp;section=blog&amp;blogid=' . $blog['blog_id'], $blog['blog_seo_name'], false, 'showblog' );
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=blog', 'false', false, 'app=blog' );
		}
	}
}