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

class public_blog_display_blog extends ipsCommand
{
	/**
	* Stored temporary output
	*
	* @access	protected
	* @var 		string 				Page output
	*/
	protected $output			= "";

	/**
	* Stored temporary page title
	*
	* @access	protected
	* @var 		string 				Page title
	*/
	protected $page_title		= "";

	/**
	* Blog id
	*
	* @access	protected
	* @var 		integer
	*/
	protected $blog_id			= 0;
	
	/**
	* Blog data
	*
	* @access	protected
	* @var 		array
	*/
	protected $blog				= array();

	/**
	* Database "start" point
	*
	* @access	protected
	* @var 		integer
	*/
	protected $first			= 0;

	/**
	* Comment count
	*
	* @access	protected
	* @var 		integer
	*/
	protected $comment_count	= 0;

	/**
	* Show drafts
	*
	* @access	protected
	* @var 		boolean
	*/
	protected $show_draft		= false;

	/**
	* Split date points
	*
	* @access	protected
	* @var 		array
	*/
	protected $date_select		= array();
	
	/**
	* Content blocks library
	*
	* @access	protected
	* @var 		object
	*/
	protected $cblock_plugins;
	
	/**
	* Attachments library
	*
	* @access	protected
	* @var 		object
	*/
	protected $class_attach;
	
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
		if ( ! $this->registry->isClassLoaded('blogFunctions') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
			$this->registry->setClass('blogFunctions', new $classToLoad($this->registry));
		}
		
		$this->blogFunctions = $this->registry->getClass('blogFunctions');
		
		//-----------------------------------------
		// And the blog is...
		//-----------------------------------------

		if( !intval($this->request['blogid']) )
		{
			$mid = intval($this->request['mid']);

			if ( $mid )
			{
				//-----------------------------------------
				// Got blog based on mid
				//-----------------------------------------

				$blog = $this->DB->buildAndFetch( array( 
														'select'	=> 'blog_id, blog_seo_name',
														'from'		=> 'blog_blogs',
														'where'		=> "member_id={$mid}" 
												)	);

				if ( !$blog['blog_id'] or !$blog['blog_seo_name'] )
				{
					//-----------------------------------------
					// No blog found...
					//-----------------------------------------

					if ( $this->memberData['member_id'] == $this->request['mid'] )
					{
						$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=blog&module=manage" );
					}
					else
					{
						$this->registry->output->showError( 'blog_billy_noblogs', 10675, null, null, 404 );
					}
				}

				//-----------------------------------------
				// Send the user to the correct blog
				//-----------------------------------------

				$this->registry->output->silentRedirect( $this->blogFunctions->getBlogUrl( $blog['blog_id'], $blog['blog_seo_name'] ) );
			}
			else
			{
				$this->registry->output->showError( 'incorrect_use', 10676, null, null, 404 );
			}
		}
		
		/* Get blog */
		$this->blog = $this->blogFunctions->loadBlog($this->request['blogid']);
		$this->blogFunctions->setActiveBlog($this->blog);
		
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
			$this->registry->output->showError( 'blog_not_enabled', 10677, null, null, 404 );
		}

		if ( !$this->memberData['member_id'] && !$this->blog['blog_allowguests'] )
		{
			$this->registry->output->showError( 'blog_no_permission', 10678, null, null, 403 );
		}

		//-----------------------------------------
		// Get the Blog url
		//-----------------------------------------

		$this->settings['blog_url'] = $this->blogFunctions->getBlogUrl( $this->blog['blog_id'] );

		//-----------------------------------------
		// Are we redirecting to an external Blog?
		//-----------------------------------------

		if( $this->blog['blog_type'] == "external" )
		{
			$this->DB->update( "blog_blogs", array( 'blog_num_exthits' => ( $this->blog['blog_num_exthits'] ? $this->blog['blog_num_exthits'] + 1 : 1 ) ), "blog_id={$this->blog['blog_id']}", true );
			
			$this->registry->output->silentRedirect( $this->blog['blog_exturl'] );
		}

		//-----------------------------------------
		// Are we allowed to see draft entries?
		//-----------------------------------------

		if( $this->blog['allow_entry'] )
		{
			$this->show_draft = true;
		}
		elseif( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderate_can_view_draft'] )
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
			$this->settings['blog_inline_edit'] =  1 ;
		}
		else
		{
			$this->settings['blog_inline_edit'] =  0 ;
		}
		
		$this->first = $this->request['st'] ? intval($this->request['st']) : 0;
		
		//-----------------------------------------
		// Load the parsing library
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/parsing.php', 'blogParsing', 'blog' );
		$this->registry->setClass( 'blogParsing', new $classToLoad( $this->registry ) );
		
		//-----------------------------------------
	    // Navigation bar + page title
		//-----------------------------------------

		$this->registry->output->addNavigation( $this->lang->words['blog_title'], 'app=blog', 'false', 'app=blog' );
		$this->registry->output->addNavigation( $this->blog['blog_name'], "app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}", $this->blog['blog_seo_name'], 'showblog' );
		
		$this->page_title = $this->blog['blog_name'] . ' - ' . $this->settings['board_name'];

		//-----------------------------------------
		// Collect the Entries
		//-----------------------------------------
		
		$extra = "";

		if ( !$this->blogFunctions->ownsBlog( $this->blog['blog_id'], $this->memberData['member_id'] ) AND !$this->show_draft )
		{
			$extra = " AND entry_status='published'";
		}

		//-----------------------------------------
		// Did we select something from the calendar?
		//-----------------------------------------

		if ( $this->date_select['m'] && $this->date_select['y'] )
		{
			$offset =  $this->registry->getClass('class_localization')->getTimeOffset();

			if ( $this->date_select['d'] )
			{
				//-----------------------------------------
				// We selected a day
				//-----------------------------------------

		       	$begin_datestamp	= mktime( 0,0,0, $this->date_select['m'], $this->date_select['d'], $this->date_select['y'] ) - $offset;
		       	$end_datestamp		= $begin_datestamp + (60*60*24);
			}
			else
			{
				//-----------------------------------------
				// We selected a month
				//-----------------------------------------
		       	$begin_datestamp	= mktime( 0,0,0, $this->date_select['m'], 1, $this->date_select['y'] ) - $offset;
		       	$end_datestamp		= mktime( 0,0,0, $this->date_select['m']+1, 1, $this->date_select['y'] ) - $offset;
		    }

			$extra	.= " AND entry_date BETWEEN {$begin_datestamp} AND {$end_datestamp}";
		}

		//---------------------------------------------
		// Set url pieces
		//---------------------------------------------

		$url = array();
		
		if ( $this->date_select['d'] )
		{
			$url[] = "d={$this->date_select['d']}";
		}
		
		if ( $this->date_select['m'] )
		{
			$url[] = "m={$this->date_select['m']}";
		}
		
		if ( $this->date_select['y']  )
		{
			$url[] = "y={$this->date_select['y']}";
		}

		if ( isset( $this->request['cat'] ) AND ( $this->request['cat'] != '' OR $this->request['cat'] == 0 ) )
		{
			$url[] = "cat={$this->request['cat']}";
			$extra .= ' AND entry_category LIKE \'%,' . intval( $this->request['cat'] ) . '%\'';
			
			$this->registry->output->addNavigation( $this->blog['_categories'][ $this->request['cat'] ]['category_title'], "app=blog&amp;blogid={$this->blog['blog_id']}&amp;cat=" . $this->request['cat'], $this->blog['_categories'][ $this->request['cat'] ]['category_title_seo'], 'blogcatview' );
		}
		
		if ( $this->request['tag'] )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=core&amp;module=search&amp;do=search&amp;search_tags={$this->request['tag']}&amp;search_app=blog", '', true );
		}

		//---------------------------------------------
		// Counts + page links
		//---------------------------------------------
		
		$maxresults	= $this->blog['blog_settings']['entriesperpage'] ? $this->blog['blog_settings']['entriesperpage'] : 8;

    	$max	= $this->DB->buildAndFetch( array( 
													'select'	=> "count(*) as total_entries",
													'from'		=> "blog_entries",
													'where'		=> "blog_id={$this->blog['blog_id']} " . $extra
										)	);
		
		$urlExtra = '';
		if( count( $url ) )
		{
			$urlExtra = '&amp;' . implode( '&amp;', $url );
		}

		$links = $this->registry->output->generatePagination( array('totalItems'		=> $max['total_entries'],
																	'itemsPerPage'		=> $maxresults,
																	'currentStartValue'	=> $this->first,
																	'baseUrl'			=> "app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}" . $urlExtra,
																	'seoTitle'			=> $this->blog['blog_seo_name'],
																	'seoTemplate'		=> 'showblog'
																)	);

		//---------------------------------------------
		// Get the results!
		//---------------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
		$tagsClass = classes_tags_bootstrap::run( 'blog', 'entries' );
		
		$attachments		= 0;
		$stored_ids			= array();
		$entries			= array();
		
		$this->DB->build( array(
								'select'	=> 'e.*',
								'from'		=> array( 'blog_entries' => 'e' ),
								'where'		=> "e.blog_id={$this->blog['blog_id']} ".$extra,
								'order'		=> 'e.entry_date DESC',
								'limit'		=> array($this->first, $maxresults),
								'add_join'	=> array( array( 'select'	=> 'bl.*', 
															 'from'		=> array( 'blog_lastinfo' => 'bl' ),
															 'where'		=> 'bl.blog_id=e.blog_id',
															 'type'		=> 'left' ),
													
													  array( 'select'	=> 'm.member_id as member_mid, m.members_display_name as entry_author_name, m.member_group_id, m.mgroup_others, m.members_seo_name, m.members_seo_name', 
															 'from'		=> array( 'members' => 'm' ),
															 'where'	=> 'm.member_id=e.entry_author_id',
															 'type'		=> 'left' ),
															
											    	  array( 'select'	=> 'pp.*', 
													 		'from'		=> array( 'profile_portal' => 'pp' ),
													 		'where'	=> 'pp.pp_member_id=m.member_id',
													 		'type'		=> 'left' ) ) ) );

		$qid = $this->DB->execute();
		
		/* Blog moderator? */
		$_can_approve = $this->registry->blogFunctions->allowApprove( $this->blog );
		
		while( $entry = $this->DB->fetch( $qid ) )
		{
			/* Reset the blog ID, for some odd reason it is NULL sometimes.. */
			$entry['blog_id'] = $this->blog['blog_id'];
			
			/* We need the original vars as well for the hovercard there... */
			$entry['member_id']				= $entry['member_mid'];
			$entry['members_display_name']	= $entry['entry_author_name'];
			
			/* Are we a mod? */
			$entry['_can_approve']= $_can_approve;
			
			//---------------------------------------------
			// Parse it
			//---------------------------------------------
		
			$entry			= $this->registry->blogParsing->parseEntry( $entry, true, array( 'polls' => true ) );
			$entry			= IPSMember::buildDisplayData( $entry );
			
			/* Tags */
			$entry['tags'] = $tagsClass->getTagsByMetaId( $entry['entry_id'] );
			
			$stored_ids[]	= $entry['entry_id'];

			//---------------------------------------------
			// Parse gallery album
			//---------------------------------------------
			
			if ( $entry['entry_gallery_album'] )
			{
				$entry['entry'] = $this->registry->blogParsing->fetchGalleryAlbum( $entry['entry_gallery_album'] ) . $entry['entry'];
			}
					
			$entries[] = $entry;
	
			//---------------------------------------------
			// Store ids for attachments
			//---------------------------------------------

			$entry_ids[] = $entry['entry_id'];
			$attachments += $entry['entry_has_attach'] ? $entry['entry_has_attach'] : 0;
		}
		
		//-----------------------------------------
		// Get sidebars
		//-----------------------------------------
		$classToLoad   = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
		$this->cBlocks = new $classToLoad($this->registry, $this->blog);
		
		$cblocks          = array();
		$cblocks['left']  = $this->cBlocks->show_blocks('left');
		$cblocks['right'] = $this->cBlocks->show_blocks('right');
				
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_like = classes_like::bootstrap( 'blog', 'blog' );
		$likeData    = $this->_like->render( 'summary', $this->blog['blog_id'] );
		
		//-----------------------------------------
		// And output
		//-----------------------------------------
		
		$this->output .= $this->registry->getClass('output')->getTemplate('blog_show')->blogView( $this->blog, $links, $entries, $this->date_select, $cblocks, $likeData );
		
		//---------------------------------------------
		// Parse attachments if we got em
		//---------------------------------------------

		if ( $attachments && $this->settings['blog_list_full'] )
		{
			//-----------------------------------------
			// Grab render attach class
			//-----------------------------------------

			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php', 'class_attach' );
			$this->class_attach       = new $classToLoad( $this->registry );
			$this->class_attach->type = 'blogentry';
			$this->class_attach->init();

			$_output = $this->class_attach->renderAttachments( $this->output, $entry_ids, 'blog_show' );
			$this->output = $_output[0]['html'];

			foreach( $_output as $entry_id => $_attachData )
			{
				if( $entry_id && $_attachData['attachmentHtml'] )
				{
					$this->output = str_replace( '<!--IBF.ATTACHMENT_' . $entry_id . '-->', $_attachData['attachmentHtml'], $this->output );
				}
			}
		}

		//-----------------------------------------
		// Update the view count :)
		//-----------------------------------------

		if( $this->settings['blog_update_views_immediately'] )
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
		$this->blogFunctions->sendBlogOutput( $this->blog, $this->blog['blog_name'] );
	}
}