<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * RSS output plugin :: posts
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class furlRedirect_forums
{	
	/**
	 * Key type: Type of action (topic/forum)
	 *
	 * @var		string
	 */
	protected $_type = '';
	
	/**
	 * Key ID
	 *
	 * @var		int
	 */
	protected $_id = 0;
	
	/**
	* Constructor
	*
	* @return	@e void
	*/
	public function __construct( ipsRegistry $registry )
	{
		$this->registry =  $registry;
		$this->DB       =  $registry->DB();
		$this->settings =& $registry->fetchSettings();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}

	/**
	 * Set the key ID
	 *
	 * @param	string	Type
	 * @param	mixed	Value
	 * @return	@e void
	 */
	public function setKey( $name, $value )
	{
		$this->_type = $name;
		$this->_id   = $value;
	}
	
	/**
	 * Set up the key by URI
	 *
	 * @param	string		URI (example: index.php?showtopic=5&view=getlastpost)
	 * @return	@e boolean
	 */
	public function setKeyByUri( $uri )
	{
		$uri = str_replace( '&amp;', '&', $uri );

		if ( strstr( $uri, '?' ) )
		{
			list( $_chaff, $uri ) = explode( '?', $uri );
		}
		
		foreach( explode( '&', $uri ) as $bits )
		{
			list( $k, $v ) = explode( '=', $bits );
			
			if ( $k )
			{
				if ( $k == 'showtopic' )
				{
					$this->setKey( 'topic', intval( $v ) );
					return TRUE;
				}
				else if( $k == 'act' AND $v == 'ST' )
				{
					$this->setKey( 'topic', intval( $_REQUEST['t'] ) );
					return TRUE;
				}
				else if ( $k == 'showforum' )
				{
					$this->setKey( 'forum', intval( $v ) );
					return TRUE;
				}
				else if( $k == 'showannouncement' )
				{
					$this->setKey( 'announcement', intval( $v ) );
					return TRUE;
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	* Return the SEO title
	*
	* @return	@e mixed		The SEO friendly name or boolean false
	*/
	public function fetchSeoTitle()
	{
		switch ( $this->_type )
		{
			default:
				return FALSE;
			break;
			case 'topic':
				return $this->_fetchSeoTitle_topic();
			break;
			case 'forum':
				return $this->_fetchSeoTitle_forum();
			break;
			case 'announcement':
				return $this->_fetchSeoTitle_announcement();
			break;
		}
	}
	
	/**
	 * Return the SEO title for a topic
	 *
	 * @return	@e mixed		The SEO friendly name or boolean false
	 */
	public function _fetchSeoTitle_topic()
	{
		$topic = $this->DB->buildAndFetch( array( 'select' => 'tid, title_seo, title, forum_id',
												  'from'   => 'topics',
												  'where'  => 'tid=' . intval( $this->_id ) ) );
												
		if ( $topic['tid'] )
		{
			/* Check permission */
			if ( ! $this->registry->getClass('class_forums')->forumsCheckAccess( $topic['forum_id'], 0, 'topic', $topic, TRUE ) )
			{
				return FALSE;
			}
						
			return ( $topic['title_seo'] ) ? $topic['title_seo'] : IPSText::makeSeoTitle( $topic['title'] );
		}
		
		return FALSE;
	}
	
	/**
	 * Return the SEO title for a forum
	 *
	 * @return	@e mixed		The SEO friendly name or boolean false
	 */
	public function _fetchSeoTitle_forum()
	{
		$forum = $this->registry->getClass('class_forums')->getForumById( intval( $this->_id ) );
														
		if ( $forum['id'] )
		{
			/* Check permission */
			if ( ! $this->registry->getClass('class_forums')->forumsCheckAccess( $forum['id'], 0, 'forum', array(), TRUE ) )
			{
				return FALSE;
			}
			
			return ( $forum['name_seo'] ) ? $forum['name_seo'] : IPSText::makeSeoTitle( $forum['name'] );
		}
		
		return FALSE;
	}
	
	/**
	 * Return the SEO title for an announcement
	 *
	 * @return	@e mixed		The SEO friendly name or boolean false
	 */
	public function _fetchSeoTitle_announcement()
	{
		$announce	= $this->caches['announcements'][ intval( $this->_id ) ];

		if ( $announce['announce_id'] )
		{
			return $announce['announce_seo_title'] ? $announce['announce_seo_title'] : IPSText::makeSeoTitle( $announce['announce_title'] );
		}
		
		return FALSE;
	}
}