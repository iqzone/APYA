<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog display a blog
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

class public_blog_display_archive extends ipsCommand
{
	/**
	* Stored temporary output
	*
	* @access	protected
	* @var 		string 				Page output
	*/
	protected $output				= "";

	/**
	* Stored temporary page title
	*
	* @access	protected
	* @var 		string 				Page title
	*/
	protected $page_title			= "";

	/**
	* Blog id
	*
	* @access	protected
	* @var 		integer
	*/
	protected $blog_id				= 0;
	
	/**
	* Blog data
	*
	* @access	protected
	* @var 		array
	*/
	protected $blog					= array();

	/**
	* Database "start" point
	*
	* @access	protected
	* @var 		integer
	*/
	protected $first				= 0;

	/**
	* Comment count
	*
	* @access	protected
	* @var 		integer
	*/
	protected $comment_count		= 0;

	/**
	* Show drafts
	*
	* @access	protected
	* @var 		boolean
	*/
	protected $show_draft			= false;

	/**
	* Split date points
	*
	* @access	protected
	* @var 		array
	*/
	protected $date_select			= array();
	
	/**
	* Content blocks library
	*
	* @access	protected
	* @var 		object
	*/
	protected $cblock_plugins;
	
	/**
	* Blog parser library
	*
	* @access	protected
	* @var 		object
	*/
	protected $parser;

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// And the blog is...
		//-----------------------------------------

		if( empty( $this->request['blogid'] ) )
		{
			$mid = intval( $this->request['mid'] );

   			if( $mid )
   			{
				//-----------------------------------------
   				// Got blog based on mid
				//-----------------------------------------

				$blog = $this->DB->buildAndFetch( array( 
														'select'	=> 'blog_id, blog_name',
														'from'		=> 'blog_blogs',
														'where'		=> "member_id={$mid}" 
												)	);

				if( ! $blog['blog_id'] or ! $blog['blog_name'] )
				{
					//-----------------------------------------
					// No blog found...
					//-----------------------------------------

					if( $this->memberData['member_id'] == $this->request['mid'] )
					{
						$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=core&module=usercp&tab=blog&area=settings" );
					}
					else
					{
						$this->registry->output->showError( 'incorrect_use', 10671, null, null, 404 );
					}
				}

				//-----------------------------------------
				// Send the user to the correct blog
				//-----------------------------------------

				$this->registry->output->silentRedirect( $this->registry->getClass('blogFunctions')->getBlogUrl( $blog['blog_id'] ) );
			}
			else
			{
				$this->registry->output->showError( 'incorrect_use', 10672, null, null, 404 );
			}
		}
		
		/* Get blog */
		$this->blog = $this->registry->getClass('blogFunctions')->getActiveBlog();
		
		//-----------------------------------------
	    // Date selection?
		//-----------------------------------------

		$this->date_select = array( 'd' => $this->request['d'] ? intval( $this->request['d'] ) : 0,
									'm' => $this->request['m'] ? intval( $this->request['m'] ) : 0,
									'y' => $this->request['y'] ? intval( $this->request['y'] ) : 0 );

		//-----------------------------------------
		// Is the Blog enabled?
		//-----------------------------------------

		if ( !$this->blog['blog_name'] )
		{
			$this->registry->output->showError( 'blog_not_enabled', 10673, null, null, 404 );
		}

		if ( !$this->memberData['member_id'] && !$this->blog['blog_allowguests'] )
		{
			$this->registry->output->showError( 'blog_no_permission', 10674, null, null, 403 );
		}

		//-----------------------------------------
		// Get the Blog url
		//-----------------------------------------

		$this->settings['blog_url'] =  $this->registry->getClass('blogFunctions')->getBlogUrl( $this->blog['blog_id'] );

		//-----------------------------------------
		// Are we redirecting to an external Blog?
		//-----------------------------------------

		if ( $this->blog['blog_type'] == 'external' )
		{
			$this->DB->update( "blog_blogs", array( 'blog_num_exthits' => ( $this->blog['blog_num_exthits'] ? $this->blog['blog_num_exthits'] + 1 : 1 ) ), "blog_id={$this->blog['blog_id']}", true );

			$this->registry->output->silentRedirect( $this->blog['blog_exturl'] );
		}

		//-----------------------------------------
		// Are we allowed to see draft entries?
		//-----------------------------------------

		if ( $this->blog['allow_entry'] )
		{
			$this->show_draft	= true;
		}
		elseif ( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderate_can_view_draft'] )
		{
			$this->show_draft	= true;
		}
		else
		{
			$this->show_draft	= false;
		}

		//-----------------------------------------
		// Are we inline editing?
		//-----------------------------------------

		if ( $this->memberData['member_id'] == $this->blog['member_id'] )
		{
			$this->settings['blog_inline_edit'] = 1;
		}
		else
		{
			$this->settings['blog_inline_edit'] = 0;
		}
		
		$this->first	= $this->request['st'] ? intval($this->request['st']) : 0;
		
		//-----------------------------------------
		// Load the parsing library
		//-----------------------------------------

		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/parsing.php', 'blogParsing', 'blog' );
		$this->registry->setClass( 'blogParsing', new $classToLoad( $this->registry ) );

		//-----------------------------------------
	    // Navigation bar + page title
		//-----------------------------------------

		$this->registry->output->addNavigation( $this->lang->words['blog_title'], 'app=blog', 'blogs', 'blogs' );
		$this->registry->output->addNavigation( $this->blog['blog_name'], "app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}", $this->blog['blog_seo_name'], 'showblog' );
		$this->registry->output->addNavigation( $this->lang->words['blog_archive'], '' );
		
		$this->page_title	= $this->blog['blog_name'] . " " . $this->lang->words['blog_archive'] . ' - ' . $this->settings['board_name'];

		//-----------------------------------------
		// Collect the Entries
		//-----------------------------------------
		
		$extra = "";

		if ( !$this->show_draft )
		{
			$extra = " AND e.entry_status='published'";
		}

		//-----------------------------------------
		// History page will hold more results per page
		//-----------------------------------------

		$maxresults	= 50;

    	//---------------------------------------------
    	// Count...
    	//---------------------------------------------

    	$max	= $this->DB->buildAndFetch( array( 'select'	=> "count(*) as total_entries",
												   'from'	=> "blog_entries e",
												   'where'	=> "e.blog_id={$this->blog['blog_id']} " . $extra
										   )	  );

		$links	= $this->registry->output->generatePagination( array(
												'totalItems'		=> $max['total_entries'],
												'itemsPerPage'		=> $maxresults,
												'currentStartValue'	=> $this->first,
												'baseUrl'			=> 'app=blog&module=display&section=archive&blogid='.$this->blog['blog_id'],
												'seoTitle'			=> $this->blog['blog_name_seo'],
												'seoTemplate'		=> 'blogarchive'
											  )
									   );

		//---------------------------------------------
		// Get the results!
		//---------------------------------------------
		
		$prev_entrymonth = "";
		
		$this->DB->build( array('select'	=> 'e.*',
								'from'		=> array( 'blog_entries' => 'e' ),
								'where'		=> "e.blog_id={$this->blog['blog_id']} " . $extra,
								'order'		=> 'e.entry_date DESC',
								'limit'		=> array($this->first, $maxresults),
								'add_join'	=> array( array( 'select'	=> 'm.member_group_id, m.mgroup_others',
															 'from'		=> array( 'members' => 'm' ),
															 'where'		=> 'm.member_id=e.entry_author_id' ) )
						 )		 );
		$qid = $this->DB->execute();
		
		$entries = array();
		
		while ($entry = $this->DB->fetch($qid) )
		{
			$entry		= $this->registry->blogParsing->parseEntry($entry);

			$entries[]	= $entry;
		}
		
		//-----------------------------------------
		// More output
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_like = classes_like::bootstrap( 'blog', 'blog' );
		$likeData    = $this->_like->render( 'summary', $this->blog['blog_id'] );

		$this->output .= $this->registry->getClass('output')->getTemplate('blog_show')->blogArchive( $this->blog, $links, $entries, $this->registry->cblocks->show_blocks('left'),
		$this->registry->cblocks->show_blocks('right'), $likeData );

		//-----------------------------------------
		// Update the view count :)
		//-----------------------------------------

		if ( $this->settings['blog_update_views_immediately'] )
		{
			$this->DB->update("blog_blogs", array( 'blog_num_views' => ( $this->blog['blog_num_views'] ? $this->blog['blog_num_views'] + 1 : 1 ) ), "blog_id={$this->blog['blog_id']}", true );
		}
		else
		{
			$this->DB->insert( 'blog_views', array( 'blog_id' => intval( $this->blog['blog_id'] ) ), true );
		}

		//-----------------------------------------
		// And finally, output it
		//-----------------------------------------
		
		$this->registry->output->setTitle( $this->page_title );
		$this->registry->output->addContent( $this->output );
		$this->registry->getClass('blogFunctions')->sendBlogOutput( $this->blog );
	}
}