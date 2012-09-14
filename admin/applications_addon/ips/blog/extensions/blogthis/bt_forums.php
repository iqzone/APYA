<?php
/**
* Blog This Module for FORUMS
*
* @package		IP.Blog
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/
class bt_forums implements iBlogThis
{
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	
	protected $incoming = array();
	protected $postId   = 0;
	protected $topicId  = 0;
	protected $forumId  = 0;
	protected $topicData = array();
	protected $postData  = array();
	protected $_permCheckDone = array();
	
	/**
	 * CONSTRUCTOR
	 *
	 * @param  array   $blog      Array of data from the current blog
	 * @param  object  $registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $app='core', $incoming=array() )
	{
		if ( ! $this->registry )
		{
			/* Make registry objects */
			$this->registry		=  $registry;
			$this->DB			=  $this->registry->DB();
			$this->settings		=& $this->registry->fetchSettings();
			$this->request		=& $this->registry->fetchRequest();
			$this->lang			=  $this->registry->getClass('class_localization');
			$this->member		=  $this->registry->member();
			$this->memberData	=& $this->registry->member()->fetchMemberData();
			$this->cache		=  $this->registry->cache();
			$this->caches		=& $this->registry->cache()->fetchCaches();
		}
		
		$this->_incoming = $incoming;
		
		/* More data */
		$this->postId  = intval( $this->_incoming['id2'] );
		$this->topicId = intval( $this->_incoming['id1'] );
		
		/* Load forums class */
		if ( ! $this->registry->isClassLoaded('class_forums' ) )
		{
			try
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('forums') . '/sources/classes/forums/class_forums.php', 'class_forums', 'forums' );
				$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
				$this->registry->getClass('class_forums')->strip_invisible = 1;
				$this->registry->getClass('class_forums')->forumsInit();
			}
			catch( Exception $error )
			{
				IPS_exception_error( $error );
			}
		}
		
		/* Load topic Data */
		if ( $this->topicId )
		{
			$this->topicData = $this->DB->buildAndFetch( array( 'select' => '*',
															    'from'   => 'topics',
															    'where'  => 'tid=' . $this->topicId ) );
															    
			$this->forumId = intval( $this->topicData['forum_id'] );
		}
	}
	
	/**
	 * check permission
	 *
	 * @return boolean
	 */
	public function checkPermission()
	{
		/* Cached? */
		if ( ! isset( $this->_permCheckDone[ $this->forumId . '-' . $this->topicId ] ) )
		{
			/* Got topic data? */
			if ( ! $this->postId OR ! $this->topicData['tid'] OR ! $this->topic['title'] OR $this->topic['state'] == 'closed' )
			{
				$this->_permCheckDone[ $this->forumId . '-' . $this->topicId ] = false;
			}
			
			/* First off, do we have forum perms */
			if ( ! $this->registry->getClass('class_forums')->forumsCheckAccess( $this->forumId, 0, 'forum', $this->topicData, true ) )
			{
				$this->_permCheckDone[ $this->forumId . '-' . $this->topicId ] = false;
			}
			else
			{
				$this->_permCheckDone[ $this->forumId . '-' . $this->topicId ] = true;
			}
		}
		
		return $this->_permCheckDone[ $this->forumId . '-' . $this->topicId ];
	}
	
	/**
	 * Returns the data for the items
	 * Data should be post textarea ready
	 *
	 * @return	array( title / content )
	 */
	public function fetchData()
	{
		/* INIT */
		$return = array( 'title' => '', 'content' => '', 'topicData' => array() );
		
		/* Check permission first */
		if ( ! $this->checkPermission() )
		{
			return $return;
		}
		
		IPSText::getTextClass('bbcode')->parsing_section		= 'topics';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];
		
		if ( !$this->postId )
		{
			$post = $this->DB->buildAndFetch( array( 'select' 	=> 'p.*' ,
													 'from'		=> array( 'posts' => 'p' ),
													 'where'	=> "p.new_topic=1 AND t.tid=" . $this->topicId,
													 'add_join'	=> array( array( 'select'	=> 't.forum_id',
																				 'from'		=> array( 'topics' => 't' ),
																				 'where'	=> 't.tid=p.topic_id',
																				 'type'		=> 'left' ),
																		  array( 'select'   => 'member_id, members_display_name',
																				 'from'     => array( 'members' => 'm' ),
																				 'where'    => 'p.author_id=m.member_id',
																				 'type'     => 'left' ) )
											 )		);
		}
		else
		{
			$post = $this->DB->buildAndFetch( array( 'select' 	=> 'p.*' ,
													 'from'		=> array( 'posts' => 'p' ),
													 'where'	=> "p.pid=" . $this->postId,
													 'add_join'	=> array( array( 'select'	=> 't.forum_id',
																				 'from'		=> array( 'topics' => 't' ),
																				 'where'	=> 't.tid=p.topic_id',
																				 'type'		=> 'left' ),
																		  array( 'select'   => 'member_id, members_display_name',
																				 'from'     => array( 'members' => 'm' ),
																				 'where'    => 'p.author_id=m.member_id',
																				 'type'     => 'left' ) )
											 )		);
		}
		
		if ( $post['pid'] )
		{
			$post['author_name'] = ( $post['members_display_name'] ) ? $post['members_display_name'] : $post['author_name'];
	
			$url = $this->registry->output->buildSEOUrl( "showtopic={$this->topicId}&amp;view=findpost&amp;p={$this->postId}", 'public', $this->topicData['title_seo'], 'showtopic' );
			
			$post['post'] = "[quote name='" . IPSText::getTextClass('bbcode')->makeQuoteSafe($post['author_name']) . "' date='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $this->registry->getClass('class_localization')->getDate( $post['post_date'], 'LONG', 1 ) ) . "' timestamp='{$post['post_date']}' post='{$post['pid']}']<br />{$post['post']}<br />" . "[/quote]";
			
			$post['post'] = trim($post['post']);
			
			/* Add on URL */
			$post['post'] .= "<br /><br /><br />{$this->lang->words['bt_source']} [url=\"{$url}\"]{$this->topicData['title']}[/url]<br />";
			
			/* return */
			$return['title']	= $this->lang->words['bt_from'] . ' ' . $this->topicData['title'];
			$return['content']	= IPSText::raw2form( $post['post'] );
			$return['topicData']= $this->topicData;
		}
		
		return $return;
	}
	
	/**
	 * Get IDs
	 *
	 * @param	string	URL
	 * @return	array	IDs
	 */
	public function getIds( $url, $furlRegex=NULL )
	{	
		if ( is_array( $url ) )
		{
			return array( 1 => $url['showtopic'] );
		}
		else
		{
			preg_match( $furlRegex, $url, $matches );
			return array( 1 => $matches[1] );
		}
	}
	
}