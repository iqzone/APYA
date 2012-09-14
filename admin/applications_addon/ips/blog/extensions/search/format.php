<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Formats blog search results
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Blog
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_format_blog extends search_format
{
	/**
	 * Constructor
	 *
	 * @param	object	ipsRegistry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Language needed */
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_portal', 'public_blog' ), 'blog' );

		parent::__construct( $registry );
		
		/* Set up wrapper */
		$this->templates = array( 'group' => 'blog_portal', 'template' => 'blogFollowedWrapper' );
	}
	
	/**
	 * Parse search results
	 *
	 * @access	private
	 * @param	array 	$r				Search result
	 * @return	array 	$html			Blocks of HTML
	 */
	public function parseAndFetchHtmlBlocks( $rows )
	{
		$search_term = IPSSearchRegistry::get('in.clean_search_term');
		
		/* Go through and build HTML */
		foreach( $rows as $id => $data )
		{
			/* Format content */
			list( $html, $sub ) = $this->formatContent( $data );
			
			/* Format */
			if ( $data['content'] )
			{
				IPSText::getTextClass('bbcode')->parse_html				= $data['entry_html_state'] ? 1 : 0;
				IPSText::getTextClass('bbcode')->parse_nl2br			= $data['entry_html_state'] == 2 ? 1 : 0;
				IPSText::getTextClass('bbcode')->parse_smilies			= $data['entry_use_emo'] ? 1: 0;
				IPSText::getTextClass('bbcode')->parsing_section		= 'blog_entry';
				IPSText::getTextClass('bbcode')->parsing_mgroup			= $data['member_group_id'];
				IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $data['mgroup_others'];
				
				$data['content'] = IPSText::getTextClass('bbcode')->preDisplayParse( $data['content'] );
				$data['content'] = IPSText::searchHighlight( $data['content'], $search_term );
			}
			
			$results[ $id ] = array( 'html' => $html, 'app' => $data['app'], 'type' => $data['type'], 'sub' => $sub, '_followData' => !empty($data['_followData']) ? $data['_followData'] : array() );
		}
		
		return $results;
	}
	
	/**
	 * Formats the forum search result for display
	 *
	 * @access	public
	 * @param	array   $search_row		Array of data
	 * @return	mixed	Formatted content, ready for display, or array containing a $sub section flag, and content
	 */
	public function formatContent( $data )
	{
		/* INIT */
		$template = ( IPSSearchRegistry::get('blog.searchInKey') == 'entries' ) ? 'entrySearchResult' : 'commentSearchResult';
		
		return array( ipsRegistry::getClass('output')->getTemplate('blog_portal')->$template( $data, IPSSearchRegistry::get('opt.searchType') == 'titles' ? true : false, 0 ) );
	}
	
	/**
	 * Return the output for the followed content results
	 *
	 * @param	array 	$results	Array of results to show
	 * @param	array 	$followData	Meta data from follow/like system
	 * @return	@e string
	 */
	public function parseFollowedContentOutput( $results, $followData )
	{
		/* Entries? */
		if( IPSSearchRegistry::get('in.followContentType') == 'entries' )
		{
			return $this->registry->output->getTemplate('blog_portal')->blogFollowedWrapper( $this->parseAndFetchHtmlBlocks( $this->processResults( $results, $followData ) ) );
		}
		/* Or blogs? */
		else
		{
			/* Init vars */
			$blogs   = array();
			$memIds  = array();
			$members = array();

			if( count($results) )
			{
				/* Load our main library */
				if ( ! $this->registry->isClassLoaded('blogFunctions') )
				{
					$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
					$this->registry->setClass('blogFunctions', new $classToLoad($this->registry));
				}
				
				$this->DB->build( array( 'select'	=> 'b.*',
										 'from'		=> array( 'blog_blogs' => 'b'),
										 'where'	=> 'b.blog_id IN(' . implode( ',', $results ) . ')',
										 'add_join' => array( array( 'select' => 'i.*',
										 							 'from'   => array( 'blog_lastinfo' => 'i' ),
																	 'where'  => 'b.blog_id=i.blog_id',
																	 'type'   => 'left' ),
															  array( 'select' => 'e.entry_author_id, e.entry_name_seo',
										 							 'from'   => array( 'blog_entries' => 'e' ),
																	 'where'  => 'i.blog_last_entry=e.entry_id',
																	 'type'   => 'left' ) )
								 )		);
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					/* Got author but no member data? */
					if ( ! empty( $r['entry_author_id'] ) )
					{
						$memIds[ $r['entry_author_id'] ] = $r['entry_author_id'];
					}
					
					/* Got updates? */
					$r['_lastRead']  = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'blogID' => $r['blog_id'] ), 'blog' );
					$r['newpost']    = ( $r['blog_last_udate'] > $r['_lastRead'] ) ? true : false;
					
					/* Drafts? */
					$r['_hasDrafts'] = ( $r['blog_num_drafts'] && $this->registry->blogFunctions->allowPublish( $r ) ) ? true : false;
					
					$blogs[ $r['blog_id'] ]	= $r;
				}

				/* Merge in follow data */
				foreach( $followData as $_follow )
				{
					$blogs[ $_follow['like_rel_id'] ]['_followData'] = $_follow;
				}
				
				/* Need to load members? */
				if ( count($memIds) )
				{
					$members = IPSMember::load( $memIds, 'extendedProfile', 'id' );
				}
				
				$members[0] = IPSMember::setUpGuest();
				
				foreach( $blogs as $id => $data )
				{
					if ( !empty($data['entry_author_id']) AND isset($members[ $data['entry_author_id'] ]) )
					{
						$blogs[ $id ]['member'] = IPSMember::buildDisplayData( $members[ $data['entry_author_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );
					}
				}
			}
			
			return $this->registry->output->getTemplate('blog_portal')->followedContentBlogs( $blogs );
		}
	}
	
	/**
	 * Decides which type of search this was
	 *
	 * @param	array 	$ids			Ids
	 * @param	array	$followData		Retrieve the follow meta data
	 * @return array
	 */
	public function processResults( $ids, $followData=array() )
	{
		if ( IPSSearchRegistry::get('blog.searchInKey') == 'entries' )
		{
			/* Set up wrapper */
			return $this->_processEntriesResults( $ids, $followData );
		}
		else
		{
			return $this->_processCommentResults( $ids );
		}
	}
	
	/**
	 * Formats / grabs extra data for results
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @access public
	 * @return array
	 */
	public function _processCommentResults( $ids )
	{
		/* INIT */
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$sortKey			= 'comment_date';
		$_rows				= array();
		$members			= array();
		$results			= array();
		
		/* Got some? */
		if ( count( $ids ) )
		{
			/* Set vars */
			IPSSearch::$ask = $sortKey;
			IPSSearch::$aso = strtolower( $sort_order );
			IPSSearch::$ast = 'numerical';
			
			$this->DB->build( array(
									'select'	=> 'c.*, c.member_id as comment_member_id',
									'from'		=> array( 'blog_comments' => 'c' ),
		 							'where'		=> 'c.comment_id IN( ' . implode( ',', $ids ) . ')',
									'limit'		=> array(0, IPSSearchRegistry::get('set.hardLimit') + 1),
									'add_join'	=> array(
											 				array(
												 					'select'	=> 'b.*',
																	'from'		=> array( 'blog_entries' => 'b' ),
																	'where'		=> 'c.entry_id=b.entry_id',
																	'type'		=> 'left'
											 					),
															array(
																	'select'	=> 'bl.*',
																	'from'		=> array( 'blog_blogs' => 'bl' ),
																	'where'		=> "bl.blog_id=b.blog_id",
																	'type'		=> 'left',
																)
														)													
					)	);
			$this->DB->execute();	
			
			/* Grab the results */
			while( $row = $this->DB->fetch() )
			{
				$_rows[] = $row;
			}

			/* Sort */
			if ( count( $_rows ) )
			{
				usort( $_rows, array("IPSSearch", "usort") );
		
				foreach( $_rows as $id => $row )
				{
					/* Got author but no member data? */
					if ( ! empty( $row['comment_member_id'] ) )
					{
						$members[ $row['comment_member_id'] ] = $row['comment_member_id'];
					}
					
					$results[ $row['comment_id'] ] = $this->genericizeResults( $row );
				}
			}

			/* Need to load members? */
			if ( count( $members ) )
			{
				$mems = IPSMember::load( $members, 'all' );
				
				foreach( $results as $id => $r )
				{
					if ( ! empty( $r['comment_member_id'] ) AND isset( $mems[ $r['comment_member_id'] ] ) )
					{
						$_mem = IPSMember::buildDisplayData( $mems[ $r['comment_member_id'] ], array( 'reputation' => 0, 'warn' => 0 ) );
						$results[ $id ] = array_merge( $results[ $id ], $_mem );
					}
				}
			}
		}

		return $results;
	}

	/**
	 * Formats / grabs extra data for results
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @access public
	 * @return array
	 */
	public function _processEntriesResults( $ids, $followData=array() )
	{
		/* INIT */
		$sort_by	= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order	= IPSSearchRegistry::get('in.search_sort_order');
		$sortKey	= '';
		$sortType	= '';
		$_rows		= array();
		$members	= array();
		$results	= array();
		
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
		$tagsClass = classes_tags_bootstrap::run( 'blog', 'entries' );

		/* Got some? */
		if ( count( $ids ) )
		{
			/* Sorting */
			switch( $sort_by )
			{
				default:
				case 'date':
					$sortKey	= 'entry_date';
					$sortType	= 'numerical';
				break;
				case 'title':
					$sortKey	= 'entry_name';
					$sortType	= 'string';
				break;
				case 'comments':
					$sortKey	= 'entry_num_comments';
					$sortType	= 'numerical';
				break;
			}

			/* Set vars */
			IPSSearch::$ask = $sortKey;
			IPSSearch::$aso = strtolower( $sort_order );
			IPSSearch::$ast = $sortType;
			
			/* Fetch data */
			$this->DB->build( array('select'   => 'b.*',
									'from'	   => array( 'blog_entries' => 'b' ),
		 							'where'	   => 'b.entry_id IN( ' . implode( ',', $ids ) . ')',
									'add_join' => array( array( 'select'	=> 'bl.*',
																'from'		=> array( 'blog_blogs' => 'bl' ),
																'where'		=> "bl.blog_id=b.blog_id",
																'type'		=> 'left' ) )
							 )		);
			$this->DB->execute();
			
			/* Grab the results */
			while( $row = $this->DB->fetch() )
			{
				$_rows[] = $row;
			}

			/* Sort */
			if ( count( $_rows ) )
			{
				usort( $_rows, array("IPSSearch", "usort") );
				
				foreach( $_rows as $id => $row )
				{
					/* Got author but no member data? */
					if ( ! empty( $row['entry_author_id'] ) )
					{
						$members[ $row['entry_author_id'] ] = $row['entry_author_id'];
					}
					
					$row['_lastRead'] = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'blogID' => $row['blog_id'], 'itemID' => $row['entry_id'] ), 'blog' );
					
					if( $row['entry_num_comments'] AND $row['entry_last_comment_date'] > $row['_lastRead'] )
					{
						$row['newpost'] = true;
					}
					else
					{
						$row['newpost'] = false;
					}
					
					$row['tags'] = $tagsClass->getTagsByMetaId( $row['entry_id'] );
					
					$results[ $row['entry_id'] ] = $this->genericizeResults( $row );
				}
			}
			
			/* Get the 'follow' meta data? */
			if( count($followData) )
			{
				$followData = classes_like_meta::get( $followData );

				/* Merge the data from the follow class into the results */
				foreach( $followData as $_formatted )
				{
					$results[ $_formatted['like_rel_id'] ]['_followData'] = $_formatted;
				}
			}

			/* Need to load members? */
			if ( count( $members ) )
			{
				$mems = IPSMember::load( $members, 'all' );
				
				foreach( $results as $id => $r )
				{
					if ( ! empty( $r['entry_author_id'] ) AND isset( $mems[ $r['entry_author_id'] ] ) )
					{
						$results[ $id ] = array_merge( $results[ $id ], IPSMember::buildDisplayData( $mems[ $r['entry_author_id'] ], array( 'reputation' => 0, 'warn' => 0 ) ) );
					}
				}
			}
		}
		
		return $results;
	}
	
	/**
	 * Reassigns fields in a generic way for results output
	 *
	 * @param  array  $r
	 * @return array
	 */
	public function genericizeResults( $r )
	{
		if ( IPSSearchRegistry::get('blog.searchInKey') == 'entries' )
		{
			$r['app']                 = 'blog';
			$r['content']             = $r['entry_short'];
			$r['content_title']       = $r['entry_name'];
			$r['updated']             = $r['entry_date'];
			$r['type_2']              = 'entry';
			$r['type_id_2']           = $r['entry_id'];
			$r['member_id']           = $r['entry_author_id'];
			$r['misc']                = '';
		}
		else
		{
			$r['app']                 = 'blog';
			$r['content']             = $r['comment_text'];
			$r['content_title']       = $r['entry_name'];
			$r['updated']             = $r['comment_date'];
			$r['type_2']              = 'comment';
			$r['type_id_2']           = $r['comment_id'];
			$r['member_id']           = $r['member_id'];
			$r['misc']                = '';
		}
		
		return $r;
	}
}