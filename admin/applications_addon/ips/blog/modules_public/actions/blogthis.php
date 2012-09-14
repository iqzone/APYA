<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog Blog This
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

class public_blog_actions_blogthis extends ipsCommand
{
	/**
	* Main class entry point
	*
	* @access	public
	* @param	object		ipsRegistry reference
	* @return	@e void		[Outputs to screen]
	*/	
	public function doExecute( ipsRegistry $registry )
	{
		$this->lang->loadLanguageFile( array( 'public_emails' ), 'blog' );
		
		/* We can expand this in the future, but for now, we'll just re-direct to the blog form with some extra inline vars */
		
		if ( $this->request['id1'] AND $this->request['btapp'] && $this->settings['blog_allow_bthis'] )
		{
			/* Quick check */
			$blogs = $this->registry->blogFunctions->fetchMyBlogs();
			$ok    = 0;
			
			if ( is_array( $blogs ) AND count( $blogs ) )
			{
				foreach( $blogs as $id => $data )
				{
					if ( $data['_canPostIn'] )
					{
						$ok = 1;
					}
				}
				
				if ( $ok )
				{
					$this->registry->output->silentRedirect( $this->registry->output->buildUrl( 'app=blog&amp;module=post&amp;section=post&amp;do=showform&amp;id1=' . $this->request['id1'] . '&amp;id2=' . $this->request['id2'] . '&amp;btapp=' . $this->request['btapp'] ) );
				}
				else
				{
					$this->registry->output->showError( $this->lang->words['noblog_youcan_postto'], 106330.1, false, null, 403 );
				}
			}
			else
			{
				$this->registry->output->showError( $this->lang->words['noblog_youcan_postto'], 106330.2, false, null, 403 );
			}
		}
		else
		{
			$this->registry->output->showError( 'incorrect_use', 106330.3, false, null, 404 );
		}
	}
	
	
}