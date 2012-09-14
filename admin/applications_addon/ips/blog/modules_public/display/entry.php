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

class public_blog_display_entry extends ipsCommand
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
	* Attachments class
	*
	* @access	protected
	* @var 		object
	*/
	protected $class_attach;
	
	/**
	* Content blocks library
	*
	* @access	protected
	* @var 		object
	*/
	protected $cblock_plugins;

	/**
	* Database "start" point
	*
	* @access	protected
	* @var 		integer
	*/
	protected $first				= 0;

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
	* @var 		aray
	*/
	protected $date_select			= array();
	
	/**
	 * Like object reference
	 *
	 * @var	object
	 */
	protected $_like;

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Otherwise find the blog..
		//-----------------------------------------

		if( ! intval($this->request['blogid']) )
		{
			$mid = intval($this->request['mid']);

			if ( $mid )
			{
				//-----------------------------------------
   				// Got blog based on mid
				//-----------------------------------------

				$blog = $this->DB->buildAndFetch( array(
														'select'	=> 'blog_id, blog_name',
														'from'		=> 'blog_blogs',
														'where'		=> "member_id={$mid}" 
												)	);

				if ( !$blog['blog_id'] or !$blog['blog_name'] )
				{
					//-----------------------------------------
					// No blog found...
					//-----------------------------------------

					if ( $this->member->getProperty('member_id') == $this->request['mid'] )
					{
						$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=blog&module=manage" );
					}
					else
					{
						$this->registry->output->showError( 'incorrect_use', 10679, null, null, 404 );
					}
				}

				//-----------------------------------------
				// Send the user to the correct blog
				//-----------------------------------------

				$this->registry->output->silentRedirect( $this->registry->getClass('blogFunctions')->getBlogUrl( $blog['blog_id'] ) );
			}
			else if( intval( $this->request['showentry'] ) )
			{
				/* ID */
				$eid = intval( $this->request['showentry'] );
				
				/* Attempt to get the blog id from the entry table */
				$_find_blog_id = $this->DB->buildAndFetch( array( 'select' => 'blog_id', 'from' => 'blog_entries', 'where' => "entry_id={$eid}" ) );
				
				if( $_find_blog_id['blog_id'] )
				{
					$blog_to_load = $_find_blog_id['blog_id'];
					$this->request['blogid'] = $blog_to_load;
				}
				else
				{
					$this->registry->output->showError( 'incorrect_use', 10680, null, null, 404 );
				}
			}
			else
			{
				$this->registry->output->showError( 'incorrect_use', 1068, null, null, 404 );
			}
		}		
	
		//-----------------------------------------
		// Date selection?
		//-----------------------------------------

		$this->date_select = array( 'd' => $this->request['d'] ? intval( $this->request['d'] ) : 0,
									'm' => $this->request['m'] ? intval( $this->request['m'] ) : 0,
									'y' => $this->request['y'] ? intval( $this->request['y'] ) : 0 );

		//-----------------------------------------
		// Get the Blog info
		//-----------------------------------------
		if($blog_to_load)
		{
			$this->registry->getClass('blogFunctions')->setActiveBlog( intval($blog_to_load) );
		}
		else
		{
			$this->registry->getClass('blogFunctions')->setActiveBlog( intval($this->request['blogid']) );
		}
		
		$this->blog = $this->registry->getClass('blogFunctions')->getActiveBlog();
		
		//-----------------------------------------
		// Is the Blog enabled?
		//-----------------------------------------

		if ( !$this->blog['blog_name'] )
		{
			$this->registry->output->showError( 'blog_not_enabled', 10681, null, null, 404 );
		}

		if ( ! $this->memberData['member_id'] && !$this->blog['blog_allowguests'] )
		{
			$this->registry->output->showError( 'blog_no_permission', 10682, null, null, 403 );
		}
		
		//-----------------------------------------
		// Reputation Cache
		//-----------------------------------------
		
		if( $this->settings['reputation_enabled'] )
		{
			/* Load the class */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
			$this->registry->setClass( 'repCache', new $classToLoad() );
		
			/* Update the filter? */
			if( isset( $this->request['rep_filter'] ) && $this->request['rep_filter'] == 'update' )
			{
				$_mem_cache = IPSMember::unpackMemberCache( $this->memberData['members_cache'] );
				
				if( $this->request['rep_filter_set'] == '*' )
				{
					$_mem_cache['rep_filter'] = '*';
				}
				else
				{
					$_mem_cache['rep_filter'] = intval( $this->request['rep_filter_set'] );
				}
				
				IPSMember::packMemberCache( $this->memberData['member_id'], $_mem_cache );
				
				$this->memberData['_members_cache'] = $_mem_cache;
			}
			else
			{
				$this->memberData['_members_cache'] = IPSMember::unpackMemberCache( $this->memberData['members_cache'] );
			}
			
			$this->memberData['_members_cache']['rep_filter'] = isset( $this->memberData['_members_cache']['rep_filter'] ) ? $this->memberData['_members_cache']['rep_filter'] : '*';
		}
		
		//-----------------------------------------
		// Get the Blog g
		//-----------------------------------------

		$this->settings['blog_url'] = $this->registry->getClass('blogFunctions')->getBlogUrl( $this->blog['blog_id'] );

		//-----------------------------------------
		// Are we allowed to see draft entries?
		//-----------------------------------------

		if ( $this->blog['allow_entry'] OR $this->memberData['g_is_supmod'] OR $this->memberData['_blogmod']['moderate_can_view_draft'] )
		{
			$this->show_draft = true;
		}
		else
		{
			$this->show_draft = false;
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
		
		$this->first = $this->request['st'] ? intval($this->request['st']) : 0;
		
		//-----------------------------------------
		// Load the parsing library
		//-----------------------------------------

		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/parsing.php', 'blogParsing', 'blog' );
		$this->registry->setClass( 'blogParsing', new $classToLoad( $this->registry ) );
						
		//-----------------------------------------
		// Redirect?
		//-----------------------------------------
		
		$this->_checkRedirect();

		//-----------------------------------------
	    // Navigation bar + page title
		//-----------------------------------------
		
		$this->registry->output->addNavigation( $this->lang->words['blog_title'], 'app=blog', 'blogs', 'blogs' );
		$this->registry->output->addNavigation( $this->blog['blog_name'], "app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}", $this->blog['blog_seo_name'], 'showblog' );
		
		//-----------------------------------------
		// Do we have all info we need?
		//-----------------------------------------
		
		$eid = intval($this->request['eid']);
		
		if( !$eid )
		{
			$this->registry->output->showError( 'incorrect_use', 10683, null, null, 404 );
		}

		//-----------------------------------------
		// Grab teh entry
		//-----------------------------------------
		
		$_entry_joins = array( array('select' => 'r.rating_id, r.rating as current_rating',
									 'from'	  => array( 'blog_ratings' => 'r' ),
									 'where'  => "e.blog_id=r.blog_id AND e.entry_id=r.entry_id AND r.member_id=" . $this->memberData['member_id'],
									 'type'	  => 'left' ),
	  						  array( 'select' => 'm.member_id, m.members_display_name as entry_author_name, m.member_group_id, m.mgroup_others, m.members_seo_name', 
									 'from'   => array( 'members' => 'm' ),
									 'where'  => 'm.member_id=e.entry_author_id',
									 'tye'    => 'left' ),
						  	  array( 'select' => 'pp.*', 
									 'from'   => array( 'profile_portal' => 'pp' ),
									 'where'  => 'pp.pp_member_id=m.member_id',
									 'tye'    => 'left' )
							  );
		
		/* Reputation enabled? */						
		if ( $this->settings['reputation_enabled'] )
		{
			$_entry_joins[] = $this->registry->getClass('repCache')->getTotalRatingJoin('entry_id', $eid, 'blog');
			$_entry_joins[] = $this->registry->getClass('repCache')->getUserHasRatedJoin('entry_id', $eid, 'blog');
		}
		
		$entry = $this->DB->buildAndFetch( array( 'select'	    => 'e.*',
												  'from'		=> array( 'blog_entries' => 'e' ),
												  'where'		=> "e.blog_id={$this->blog['blog_id']} and e.entry_id={$eid}",
												  'add_join'	=> $_entry_joins
										  )		 );
		
		if ( !$entry['entry_id'] )
		{
			$this->registry->output->showError( 'incorrect_use', 10684, null, null, 404 );
		}
		
		/* We need the display name for hovercards.. */
		$entry['members_display_name'] = $entry['entry_author_name'];
		
		//-----------------------------------------
		// Is it a draft and are we allowed to view it?
		//-----------------------------------------

		if ( $entry['entry_status'] == 'draft' && !$this->show_draft )
		{
            $this->registry->output->showError( 'blog_no_permission', 10685, null, null, 403 );
		}
		
		/* Build member display data */
		$entry = IPSMember::buildDisplayData( $entry );
		
		//-----------------------------------------
		// Feature/unfeature?
		//-----------------------------------------

		if( $this->request['feature'] AND ( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderator_can_feature'] ) )
		{
			if( $this->blog['blog_view_level'] == 'public' )
			{
				$feature = array( 'entry_featured' => $entry['entry_featured'] ? 0 : 1 );
				
				// If we are featuring this entry, unfeature any others
				if( $feature['entry_featured'] )
				{
					$this->DB->update( 'blog_entries', array( 'entry_featured' => 0 ), 'blog_id=' . $this->blog['blog_id'] . ' AND entry_featured=1' );
				}
				
				$this->DB->update( 'blog_entries', $feature, 'blog_id=' . $this->blog['blog_id'] . ' AND entry_id=' . $entry['entry_id'] );
				
				$entry['entry_featured'] = $feature['entry_featured'];
				
				$this->registry->blogFunctions->rebuildStats();
				
				$this->registry->output->redirectScreen( $this->lang->words['thanks_feature'], "{$this->settings['base_url']}app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}&amp;showentry={$eid}" );
			}
		}
		
		//-----------------------------------------
		// Banish/unbanish?
		//-----------------------------------------

		if( $this->request['banish'] AND ( $this->member->getProperty('g_is_supmod') ) )
		{
			if( $this->blog['blog_view_level'] == 'public' )
			{
				$feature = array( 'entry_banish' => $entry['entry_banish'] ? 0 : 1 );
				
				$this->DB->update( 'blog_entries', $feature, 'blog_id=' . $this->blog['blog_id'] . ' AND entry_id=' . $entry['entry_id'] );
				
				$entry['entry_banish'] = $feature['entry_banish'];
				
				$this->registry->blogFunctions->rebuildBlog( $entry['blog_id'] );
				$this->registry->blogFunctions->rebuildStats();
				
				if ( isset( $this->request['return'] ) AND $this->request['return'] == 'home')
				{
					$this->registry->output->redirectScreen( $this->lang->words['bentry_unbanish_redirect'], $this->settings['base_url'] . "app=blog" );
				}
				else
				{
					$this->registry->output->redirectScreen( $this->lang->words['bentry_unbanish_redirect'], "{$this->settings['base_url']}app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}&amp;showentry={$eid}" );
				}
			}
		}
		
		//-----------------------------------------
		// Are we redirecting to an external Blog?
		//-----------------------------------------

		if ( $this->blog['blog_type'] == "external" )
		{
			$this->DB->update( "blog_blogs", array( 'blog_num_exthits' => ( $this->blog['blog_num_exthits'] ? $this->blog['blog_num_exthits'] + 1 : 1 ) ), "blog_id={$this->blog['blog_id']}", true );

			$this->registry->output->silentRedirect( $this->blog['blog_exturl'] );
		}
		
		/* Parse the blog entry */
		$entry = $this->registry->blogParsing->parseEntry( $entry );
		
		// Update views
		if( $this->settings['blog_update_views_immediately'] )
		{
			$this->DB->update( 'blog_blogs', array( 'blog_num_views' => ( $this->blog['blog_num_views'] ? $this->blog['blog_num_views'] + 1 : 1 ) ), "blog_id={$this->blog['blog_id']}", true );
			$this->DB->update( 'blog_entries', array( 'entry_views' => ( $entry['entry_views'] ? $entry['entry_views'] + 1 : 1 ) ), "entry_id={$entry['entry_id']}", true );
		}
		else
		{
			$this->DB->insert( 'blog_views', array( 'blog_id' => intval( $this->blog['blog_id'] ), 'entry_id' => $entry['entry_id'] ), true );
		}

		//-----------------------------------------
		// Page title and navigation
		//-----------------------------------------

		$this->registry->output->addNavigation( $entry['entry_name'], "app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}&amp;showentry={$entry['entry_id']}", $entry['entry_name_seo'], 'showentry' );
		
		$this->page_title	= $entry['entry_name'] . ' - ' . $this->settings['board_name'];


		require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
		$this->_comments = classes_comments_bootstrap::controller( 'blog-entries' );
		$this->_comments->setEntryData($entry);

		/* Got anything to say? */
		$comment_output = $this->_comments->fetchFormatted( $entry, array( 'offset' => intval( $this->request['st'] ) ) );
		
		$stored_ids		= array( $entry['entry_id'] );
		
		/* Parse Gallery Albums */
		if( $entry['entry_gallery_album'] )
		{
			$entry['entry'] = $this->registry->blogParsing->fetchGalleryAlbum( $entry['entry_gallery_album'] ) . $entry['entry'];
		}
		
		/* Get trackbacks, if enabled */
		$trackbacks_output = '';
		
		if( $this->settings['blog_allow_trackback'] && $this->blog['blog_settings']['allowtrackback'] )
		{
			/* INIT */	
			$allow_trackback = $this->registry->getClass('blogFunctions')->allowDelTrackback( $this->blog );
			$extra           = ( !$allow_trackback ? ' AND trackback_queued=0' : '' );
			$trackback_rows  = array();
			
			/* Query trackbacks */
			$this->DB->build( array ( 'select' => '*', 'from' => 'blog_trackback', 'where' => "entry_id = {$entry['entry_id']}" . $extra, 'order' => 'trackback_id ASC' ) );
			$this->DB->execute();

			while( $tb = $this->DB->fetch() )
			{
				$tb['trackback_title']    = $tb['trackback_title'] ? $tb['trackback_title'] : $tb['trackback_url'];
				$tb['trackback_date']     = $this->registry->getClass('class_localization')->getDate( $tb['trackback_date'], 'SHORT' );
				$tb['allow_deltrackback'] = $allow_trackback;
				$tb['key']                = $this->member->form_hash;
				$tb['blog_id']            = $this->blog['blog_id'];
				
				$trackback_rows[] = $tb;
			}
			
			$trackbacks_output = $this->registry->getClass('output')->getTemplate('blog_show')->entryTrackbacks( $entry['entry_id'], $trackback_rows );
		}
		
		/* Load language File */
		$this->lang->loadLanguageFile( array( 'public_topic' ), 'forums' );
		
		/* Parse Poll */
		if( $entry['entry_poll_state'] )
		{
			$poll_output = $this->registry->blogParsing->parsePoll( $entry );
		}
		
		/* Reputation */
		if ( is_null($entry['has_given_rep']) )
		{
			$entry['has_given_rep'] = 0;
		}
		
		if ( is_null($entry['rep_points']) )
		{
			$entry['rep_points'] = 0;
		}
		
		/*  Rating */
		$this->blog['_allow_rating'] = ( $this->settings['blog_enable_rating'] &&
								   	   ( $this->settings['blog_allow_multirate'] or $entry['rating_id'] == '' ) &&
								   	 	$entry['entry_author_id'] != $this->memberData['member_id'] && $this->memberData['member_id'] != 0 ) ? 1 : 0;
		
		if ( $this->blog['_allow_rating'] )
		{
			$entry['_rate_int'] = $entry['entry_rating_count'] ? round( $entry['entry_rating_total'] / $entry['entry_rating_count'], 0 ) : 0;
			$entry['_my_rate']  = !empty( $entry['rating_id'] ) ? $entry['current_rating'] : 0;
		}
		
		//-----------------------------------------
		// Get sidebars
		//-----------------------------------------
		$classToLoad   = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
		$this->cBlocks = new $classToLoad($this->registry, $this->blog);
		
		$cblocks          = array();
		$cblocks['left']  = $this->cBlocks->show_blocks('left');
		$cblocks['right'] = $this->cBlocks->show_blocks('right');
		
		$follow = $this->_getFollowData($entry);
		
		if ( $this->settings['reputation_enabled'] )
		{
			$entry['like']	= $this->registry->repCache->getLikeFormatted( array( 'app' => 'blog', 'type' => 'entry_id', 'id' => $entry['entry_id'], 'rep_like_cache' => $entry['rep_like_cache'] ) );
		}
		
		/* Some mods magic! ;D */
		$entry['_can_approve'] = $this->registry->blogFunctions->allowApprove( $this->blog );
		
		/* Get Tags */
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
		$tags = classes_tags_bootstrap::run( 'blog', 'entries' )->getTagsByMetaId( $entry['entry_id'] );
		
		$this->output .= $this->registry->getClass('output')->getTemplate('blog_show')->blogEntryView(  $this->blog,
																										$comment_output,
																										'', // TODO: this needs to be removed in a future major skin upgrade
																										$entry,
																										$poll_output,
																										$trackbacks_output,
																										'', // TODO: this needs to be removed in a future major skin upgrade
																										$cblocks,
																										$follow,
																										$tags
																									  );
																										
		/* Parse Attachments */
		if( $entry['entry_has_attach'] )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php', 'class_attach' );
			$this->class_attach       = new $classToLoad( $this->registry );
			$this->class_attach->type = 'blogentry';
			$this->class_attach->init();
        
			$_output = $this->class_attach->renderAttachments( $this->output, array( $entry['entry_id'] ), 'blog_show' );
			$this->output = $_output[0]['html'];
			
			if( $_output[$entry['entry_id']]['attachmentHtml'] )
			{
				$this->output = str_replace( '<!--IBF.ATTACHMENT_' . $entry['entry_id'] . '-->', $_output[ $entry['entry_id'] ]['attachmentHtml'], $this->output );
			}
		}
		
		/* Attachments */
		if ( stristr( $this->output, '[attach' ) )
		{
			$this->output = preg_replace_callback( "#\[attachment=(.+?):(.+?)\]#", array( $this->registry->output->getTemplate('blog_global'), 'short_attach_tag' ), $this->output );
		}
		
		/* Output! */
		$this->registry->output->addMetaTag( 'keywords', $entry['entry_name'] . ' ' . str_replace( "\n", " ", str_replace( "\r", "", strip_tags( $entry['entry'] ) ) ), TRUE );
		$this->registry->output->addMetaTag( 'description', str_replace( "\n", " ", str_replace( "\r", "", $entry['entry'] ) ), FALSE, 155 );
		$this->registry->output->setTitle( $this->page_title );
		$this->registry->output->addContent( $this->output );
		$this->registry->getClass('blogFunctions')->sendBlogOutput( $this->blog );
	}
	
	/**
	 * Get IP.Board "follow" system data
	 * 
	 * @return	@e string	Formatted HTML
	 */
	protected function _getFollowData($entry)
	{
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_follow = classes_like::bootstrap( 'blog', 'entries' );
		
		return $this->_follow->render( 'summary', $entry['entry_id'] );
	}

	/**
	 * Determine if this is a redirect request
	 *
	 * @return	@e void
	 */	
	protected function _checkRedirect()
	{
		if ( $this->request['show'] && $this->request['showentry'] )
		{
			$eid = intval($this->request['showentry']);

			//-----------------------------------------
			// Are we showing drafts
			//-----------------------------------------

			if ( !$this->show_draft )
			{
				$extra	= " AND entry_status='published'";
			}

			if ( $this->request['show'] == 'nextnewest' )
			{
				$currentry	= $this->DB->buildAndFetch( array(
																'select'	=> 'entry_date',
																'from'		=> 'blog_entries',
																'where'		=> "blog_id={$this->blog['blog_id']} and entry_id={$eid}"
													)		);

				$newentry	= $this->DB->buildAndFetch( array( 
																'select'	=> 'entry_id',
																'from'		=> 'blog_entries',
																'where'		=> "blog_id={$this->blog['blog_id']} and entry_date>".intval($currentry['entry_date']).$extra,
																'order'		=> "entry_date ASC",
																'limit'		=> array(0, 1)
														)	);

				if ( $newentry['entry_id'] )
				{
	       			$this->registry->output->silentRedirect( "{$this->settings['base_url']}app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}&amp;showentry={$newentry['entry_id']}" );
				}
				else
				{
	    			$this->registry->output->showError( 'entry_no_newer', 10686, null, null, 404 );
				}
			}

			if ( $this->request['show'] == 'nextoldest' )
			{
				$currentry	= $this->DB->buildAndFetch( array( 
																'select'	=> 'entry_date',
																'from'		=> 'blog_entries',
																'where'		=> "blog_id={$this->blog['blog_id']} and entry_id={$eid}"
														)	);

				$newentry	= $this->DB->buildAndFetch( array( 
																'select'	=> 'entry_id',
																'from'		=> 'blog_entries',
																'where'		=> "blog_id={$this->blog['blog_id']} and entry_date<".intval($currentry['entry_date']).$extra,
																'order'		=> "entry_date DESC",
																'limit'		=> array(0, 1)
																)	);

				if ( $newentry['entry_id'] )
				{
	       			$this->registry->output->silentRedirect( "{$this->settings['base_url']}app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}&amp;showentry={$newentry['entry_id']}" );
				}
				else
				{
	    			$this->registry->output->showError( 'entry_no_older', 10687, null, null, 404 );
				}
			}
			
			/* Blog has any comments? */
			if ( $this->blog['blog_num_comments'] )
			{
				if ( $this->request['show'] == 'newcomment' )
				{
					$entrylastread	= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'blogID' => $this->blog['blog_id'], 'itemID' => $eid ) );
	
					$newcomment		= $this->DB->buildAndFetch( array( 'select' => 'comment_id', 'from' => 'blog_comments', 'where' => "entry_id = {$eid} and comment_date>{$entrylastread}", 'order' => 'comment_date asc', 'limit' => array(0, 1) ) );
					
					if ( $newcomment['comment_id'] )
					{
						$st		= $this->_findCommentStart( $eid, $newcomment['comment_id'] );
						$st		= ($st > 0) ? "&amp;st=" . $st : "";
						
						$entry = $this->DB->buildAndFetch( array( 'select' => 'entry_name_seo', 'from' => 'blog_entries', 'where' => "entry_id={$eid}" ) );
						
		       			$this->registry->output->silentRedirect( "{$this->settings['base_url']}app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}&amp;showentry={$eid}{$st}&#comment_" . $newcomment['comment_id'], $entry['entry_name_seo'] );
		       		}
		       		else
		       		{
		       			$this->request['show'] =  'lastcomment' ;
		       		}
				}
				
				if ( $this->request['show'] == 'lastcomment' )
				{
					$newcomment	= $this->DB->buildAndFetch( array( 'select' => 'comment_id', 'from' => 'blog_comments', 'where' => "entry_id = {$eid}", 'order' => 'comment_date desc', 'limit' => array(0, 1) ) );
	
					if ( $newcomment['comment_id'] )
					{
						$st		= $this->_findCommentStart( $eid, $newcomment['comment_id'] );
						$st		= ($st > 0) ? "&amp;st=" . $st : "";
		       			$this->registry->output->silentRedirect( "{$this->settings['base_url']}app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}&amp;showentry={$eid}{$st}&#comment_" . $newcomment['comment_id'] );
		       		}
		       		else
		       		{
		       			$this->registry->output->silentRedirect( "{$this->settings['base_url']}app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}&amp;showentry={$eid}&#comments" );
		       		}
				}
	
				if ( $this->request['show'] == 'comment' )
				{
					$comment_id	= intval($this->request['cid']);
	
					if ( $comment_id )
					{
						$st		= $this->_findCommentStart( $eid, $comment_id );
						$st		= ($st > 0) ? "&amp;st=" . $st : "";
		       			$this->registry->output->silentRedirect( "{$this->settings['base_url']}app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}&amp;showentry={$eid}{$st}&#comment_" . $comment_id );
		       		}
		       		else
		       		{
		       			$this->registry->output->silentRedirect( "{$this->settings['base_url']}app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}&amp;showentry={$eid}&#comments" );
		       		}
				}
			}
		}
	}
	
	/**
	 * Find comment start point
	 *
	 * @param	integer		$eid		Entry ID
	 * @param	integer		$cid		Comment ID
	 * @return	@e integer	Value to use for 'st'
	 */	
	protected function _findCommentStart( $eid, $cid )
	{
		$maxresults = 20;

		$count = $this->DB->buildAndFetch( array( 
													'select'	=> 'COUNT(*) as comments',
													'from'		=> 'blog_comments',
													'where'		=> "entry_id={$eid} AND comment_id <= {$cid}",
										)	);

		if ( ( $count['comments'] % $maxresults ) == 0 )
		{
			$pages	= ($count['comments']) / $maxresults;
		}
		else
		{
			$number	= ( ($count['comments']) / $maxresults );
			$pages	= ceil( $number);
		}

		$st	= ($pages - 1) * $maxresults;

		return $st;
	}
}