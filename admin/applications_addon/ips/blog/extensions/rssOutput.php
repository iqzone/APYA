<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * RSS output plugin :: downloads
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

class rss_output_blog
{
	/**
	 * Expiration date
	 * 
	 * @var		integer			Expiration timestamp
	 */
	protected $expires	= 0;
	
	/**
	 * Requested blog id
	 * 
	 * @var		integer			Blog id
	 */
	protected $blog_id	= 0;
	
	/**
	 * Requested blog data
	 * 
	 * @var		array
	 */
	protected $blog		= array();
	
	/**
	 * RSS Generator
	 * 
	 * @var		object
	 */
	protected $class_rss;
	
	/**
	 * Registry Object Shortcuts
     * 
     * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	
	/**
	 * Grab the RSS links
	 * 
	 * @return	string		RSS document
	 */
	public function getRssLinks()
	{
		/* Make registry objects */
		$this->registry		=  ipsRegistry::instance();
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		
		$this->blog_id	= $this->request['blogid'] ? intval($this->request['blogid']) : 0;
		$return			= array();
		
		$this->lang->loadLanguageFile( array( 'public_blog' ), 'blog' );

		if ( $this->settings['blog_allow_rss'] )
		{
			$title = str_replace( "<#boardname#>", IPSText::htmlspecialchars( $this->settings['board_name'] ), $this->lang->words['bloglist_rsstitle'] );

	        $return[] = array( 'title' => $title, 'url' => $this->registry->getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . "/index.php?app=core&amp;module=global&amp;section=rss&amp;type=blog", true, 'section=rss' ) );
	    }

	    return $return;
	}
		
	/**
	 * Grab the RSS document content and return it
	 * 
	 * @return	string		RSS document
	 */
	public function returnRSSDocument()
	{		
		/* Make registry objects */
		$this->registry		=  ipsRegistry::instance();
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		
		ipsRegistry::getAppClass('blog');

		if ( !$this->settings['blog_allow_rss'] )
		{
			return $this->_returnError( $this->lang->words['rss_not_allowed'] );
		}

		if ( !$this->settings['blog_allow_rssguests'] && !$this->memberData['g_blog_allowview'] )
		{
			return $this->_returnError( $this->lang->words['rss_not_allowed'] );
		}
				
		$this->blog_id = $this->request['blogid'] ? intval($this->request['blogid']) : intval($this->request['id']);
		
		$this->_getGenerator();
		
		if ( $this->blog_id )
		{
			//-----------------------------------------
			// Get the Blog info
			//-----------------------------------------

			$this->blog = $this->registry->getClass('blogFunctions')->loadBlog( $this->blog_id );

			if ( !$this->blog['blog_settings']['allowrss'] )
			{
				return $this->_returnError( $this->lang->words['rss_not_allowed'] );
			}

			if ( $this->blog['blog_type'] != "local" )
			{
				return $this->_returnError( $this->lang->words['rss_not_available'] );
			}
		}

		$rsscache = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_rsscache', 'where' => "blog_id = {$this->blog_id}" ) );

		if ( !isset($rsscache['blog_id']) or $rsscache['blog_id'] != $this->blog_id )
		{
			$this->_recache();

			$new_cache = array( 'blog_id'		=> $this->blog_id,
								'rsscache_feed'	=> $this->rss_document
							  );

			$this->DB->insert( 'blog_rsscache', $new_cache, true );
		}
		elseif ( $rsscache['rsscache_refresh'] )
		{
			$this->_recache();

			$new_cache = array( 'rsscache_refresh'	=> 0,
								'rsscache_feed'		=> $this->rss_document
							  );

			$this->DB->update( 'blog_rsscache', $new_cache, "blog_id = {$this->blog_id}", true );
		}
		else
		{
			$this->rss_document = $rsscache['rsscache_feed'];
		}

		//-----------------------------------------
		// Do the output
		//-----------------------------------------
				
		return $this->rss_document;
	}
	
	/**
	 * Grab the RSS document expiration timestamp
	 * 
	 * @return	integer		Expiration timestamp
	 */
	public function grabExpiryDate()
	{
		// Bah, I don't even know since it's embedded in the doc
		// Just check again in an hour, kthx
		return time() + 3600;
	}
	
	/**
	 * Get the RSS library
	 * 
	 * @return	@e void
	 */
	protected function _getGenerator()
	{
		//--------------------------------------------
		// Require classes
		//--------------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
		$this->class_rss			= new $classToLoad();
		$this->class_rss->doc_type	= ipsRegistry::$settings['gb_char_set'];
	}
	
	/**
	 * Recache the RSS feed
	 * 
	 * @return	@e void
	 */
	protected function _recache()
	{
		$this->registry->class_localization->loadLanguageFile( array( 'public_blog' ), 'blog' );
				
		if ( $this->blog_id )
		{
			$channel_id = $this->class_rss->createNewChannel( array(
															'title'			=> $this->blog['blog_name'],
															'link'			=> $this->registry->output->buildSEOUrl( "app=blog&amp;blogid=" . $this->blog_id, 'publicNoSession', $this->blog['blog_seo_name'], 'showblog' ),
			 												'description'	=> $this->blog['blog_name'] . " Syndication",
			 												'pubDate'		=> $this->class_rss->formatDate( time() ),
			 												'webMaster'		=> $this->settings['email_in'] . " ({$this->settings['board_name']})",
			 												'generator'		=> 'IP.Blog'
			 												)		);
		}
		else
		{
			$channel_id = $this->class_rss->createNewChannel( array(
															'title'			=> "{$this->settings['board_name']} {$this->lang->words['blog_list']}",
															'link'			=> $this->registry->output->buildSEOUrl( "app=blog", 'public', true, 'app=blog' ),
			 												'description'	=> "{$this->lang->words['blog_list']} Syndication",
			 												'pubDate'		=> $this->class_rss->formatDate( time() ),
			 												'webMaster'		=> $this->settings['email_in'] . " ({$this->settings['board_name']})",
			 												'generator'		=> 'IP.Blog'
			 												)		);
		}

		//-----------------------------------------
		// Get the last 10 (published) entries
		//-----------------------------------------

		$limit	= intval($this->settings['blog_rss_count']);
		$limit	= $limit > 100 ? 100 : ( $limit < 1 ? 1 : $limit );
		
		if ( $this->blog_id )
		{
			$this->DB->build( array(
										'select'	=> 'e.*',
										'from'		=> array('blog_entries' => 'e'),
										'add_join'	=> array(
																array( 
																		'select' => 'b.blog_name, b.blog_seo_name, b.member_id, b.blog_settings',
													                  	'from'   => array( 'blog_blogs' => 'b' ),
													                  	'where'  => "e.blog_id=b.blog_id",
													                  	'type'   => 'left'
																	),
															),
										'where'		=> "e.entry_status='published' and e.blog_id={$this->blog_id}",
										'order'		=> 'e.entry_date DESC',
										'limit'		=> array( 0, $limit )
							)	);
		}
		else
		{
			$this->DB->build( array(
										'select'	=> "e.*",
										'from'		=> array('blog_entries' => 'e'),
										'add_join'	=> array(
																array( 
																		'select' => 'b.blog_name, b.blog_seo_name, b.member_id, b.blog_settings',
													                  	'from'   => array( 'blog_blogs' => 'b' ),
													                  	'where'  => "e.blog_id=b.blog_id",
													                  	'type'   => 'left'
																	),
															),
										'where'		=> "b.blog_id IS NOT NULL AND e.entry_status='published' AND b.blog_allowguests=1 AND b.blog_disabled=0 AND ( b.blog_owner_only=0 AND b.blog_authorized_users IS NULL ) AND (b.blog_settings LIKE '%s:8:\"allowrss\";i:1%' OR b.blog_settings LIKE '%s:8:\"allowrss\";s:1:\"1\"%')",
										'order'		=> 'e.entry_date DESC',
										'limit'		=> array( 0, $limit )
							)	);
		}

		$qid = $this->DB->execute();

		//-----------------------------------------
		// Build the items
		//-----------------------------------------

		while ( $entry = $this->DB->fetch( $qid ) )
		{
			/* Make sure that syndication is enabled */
			$entry['blog_settings'] = unserialize( $entry['blog_settings'] );
			
			IPSText::getTextClass('bbcode')->parse_html		= $entry['entry_html_state'] ? 1 : 0;
			IPSText::getTextClass('bbcode')->parse_bbcode	= 1;
			IPSText::getTextClass('bbcode')->parse_smilies	= $entry['entry_use_emo'] ? 1: 0;
			IPSText::getTextClass('bbcode')->parse_nl2br	= $entry['entry_html_state'] == 2 ? 1 : 0;

			$this->settings['__noTruncateUrl'] = 1;
			$entry['entry'] = $this->registry->blogFunctions->getEntryExcerpt( $entry );

			if ( $this->blog_id )
			{
				$title = $entry['entry_name'];
			}
			else
			{
				$title = $entry['blog_name'] . " - " . $entry['entry_name'];
			}

			if( $entry['entry_has_attach'] )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach       = new $classToLoad( $this->registry );
				$this->class_attach->type = 'blogentry';
				$this->class_attach->init();

				$_output = $this->class_attach->renderAttachments( $entry['entry'], array( $entry['entry_id'] ), 'blog_show' );
				$entry['entry'] = $_output[0]['html'];

				if( $_output[$entry['entry_id']]['attachmentHtml'] )
				{
					$entry['entry'] = str_replace( '<!--IBF.ATTACHMENT_' . $entry['entry_id'] . '-->', $_output[$entry['entry_id']]['attachmentHtml'], $entry['entry'] );
				}
			}
			
			$url = $this->registry->output->buildSEOUrl( "app=blog&blogid={$entry['blog_id']}&showentry={$entry['entry_id']}", 'publicNoSession', $entry['entry_name_seo'], 'showentry' );

			$this->class_rss->addItemToChannel( $channel_id, array( 
																'title'			=> $title,
																'link'			=> $url,
																'category'		=> '',
																'description'	=> $entry['entry'],
																'pubDate'		=> $this->class_rss->formatDate( $entry['entry_date'] ),
																'guid'			=> $url ) );
		}

		//-----------------------------------------
		// Do the output
		//-----------------------------------------
		
		$this->class_rss->createRssDocument();

		$this->class_rss->rss_document = $this->registry->output->replaceMacros( $this->class_rss->rss_document );
		$this->rss_document = $this->class_rss->rss_document;
	}
	
	/**
	 * Return an error document
	 * 
	 * @param	string			Error message
	 * @return	string			XML error document for RSS request
	 */
	protected function _returnError( $error='' )
	{
		$channel_id = $this->class_rss->createNewChannel( array( 
															'title'			=> "{$this->settings['board_name']} {$this->lang->words['blog_list']}",
															'link'			=> $this->registry->output->buildSEOUrl( "app=blog", 'public', true, 'app=blog' ),
				 											'description'	=> "{$this->lang->words['blog_list']} Syndication",
				 											'pubDate'		=> $this->class_rss->formatDate( time() ),
				 											'webMaster'		=> $this->settings['email_in'] . " ({$this->settings['board_name']})",
				 											'generator'		=> 'IP.Blog'
				 										)		);

		$this->class_rss->addItemToChannel( $channel_id, array( 
														'title'			=> $this->lang->words['rss_error_message'],
														'link'			=> $this->registry->output->buildSEOUrl( "app=blog", 'public', true, 'app=blog' ),
			 										    'description'	=> $error,
			 										    'pubDate'		=> $this->class_rss->formatDate( time() ),
			 										    'guid'			=> "{$this->settings['board_url']}/index.{$this->settings['php_ext']}?app=blog&error=1" ) );

		//-----------------------------------------
		// Do the output
		//-----------------------------------------

		$this->class_rss->createRssDocument();

		return $this->class_rss->rss_document;
	}
}