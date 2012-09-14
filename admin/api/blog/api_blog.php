<?php
/**
 * @file		api_blog.php 	Blog API
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		2.2.0
 * $LastChangedDate: 2011-03-18 17:34:15 -0400 (Fri, 18 Mar 2011) $
 * @version		vVERSION_NUMBER
 * $Revision: 8129 $
 */

if ( ! class_exists('apiCore') )
{
	require_once( IPS_ROOT_PATH . 'api/api_core.php' );/*noLibHook*/
}

/**
 *
 * @class		apiBlog
 * @brief		Blog API
 */
class apiBlog extends apiCore
{
	/**
	 * Constructor: calls the parent init() method
	 * and initializes the blog functions class
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		$this->init();
		
		if( ! ipsRegistry::isClassLoaded( 'blogFunctions' ) )
		{
			/* Load the Blog functions library */
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
			$this->registry->setClass( 'blogFunctions', new $classToLoad( $this->registry ) );
		}
	}
	
	/**
	 * Retrieve blog IDs the member can author in
	 *
	 * @param	int		$member_id		Member ID
	 * @return	@e array	Array of ids with basic information, like: array( blog_id => array( 'blog_name', 'blog_seo_name' )
	 */
	public function fetchBlogIds( $member_id=0 )
	{
		$return = array();
		$member = IPSMember::load( intval( $member_id ) );
		$data   = $this->registry->getClass('blogFunctions')->fetchMyBlogs( $member );
		
		if ( is_array( $data ) AND count( $data ) )
		{
			foreach( $data as $id => $blog )
			{
				if ( $blog['_canPostIn'] )
				{
					$return[ $id ] = $blog;
				}
			}
		}
		
		return $return;
	}
	
	/**
	 * Retrieve the url for a specific Blog ID
	 *
	 * @param	int		$blog_id	Blog ID
	 * @return 	@e string	Blog URL
	 */
	public function getBlogUrl( $blog_id=0 )
	{
		//-----------------------------------------
		// We entered here on member id, find blog_id and redirect them to here
		//-----------------------------------------
		$blog = $this->DB->buildAndFetch( array ( 
												'select'	=> 'blog_id, blog_name, blog_seo_name',
												'from'		=> 'blog_blogs',
												'where'		=> 'blog_id=' . intval( $blog_id ) 
										)	);
		
		if( ! $blog['blog_id'] or ! $blog['blog_name'] )
		{
			return '';
   		}
		else
		{
			return $this->registry->output->buildSEOUrl( 'app=blog&amp;blogid=' . $blog_id, 'public', $blog['blog_seo_name'], 'showblog' );
		}
	}

	/**
	 * Retrieve the last x entries of a Blog
	 * Only public publishes entries are returned
	 *
	 * @param	string	$type		Either 'member' or 'blog'
	 * @param	int		$id			Blog ID or Member ID
	 * @param	int		$limit		Number of maximum entries to return
	 * @return 	@e array	Array of last X entries
	 */
	public function lastXEntries( $type, $id, $limit=10 )
	{
		/* INIT */
		$id           = intval( $id );
		$return_array = array();
		
        /* Build permissions */
		$modData = $this->registry->blogFunctions->buildPerms();

		/* Got permission to view blog entries? */
		if ( $this->memberData['g_blog_settings']['g_blog_allowview'] )
		{						
			$this->DB->build( array( 'select'	=> "e.*, b.*",
							         'from'		=> array('blog_entries' => 'e'),
							         'where'    => ( $type == 'member' ? "e.entry_author_id={$id}" : "b.blog_id={$id}" ) . " AND b.blog_type='local' AND e.entry_status='published'",
									 'order'	=> 'e.entry_date DESC',
									 'limit'	=> array( 0, intval( $limit ) ),
						             'add_join' => array( array( 'select' => 'b.blog_name, b.blog_seo_name',
																'from'   => array( 'blog_blogs' => 'b' ),
																'where'  => "e.blog_id=b.blog_id",
																'type'   => 'left' ) ) ) );
										 
			$outer = $this->DB->execute();
			
			while( $entry = $this->DB->fetch($outer) )
			{
				if ( !$this->memberData['member_id'] or !$modData['_blogmod']['moderate_can_view_private'] )
				{
					switch ( $entry['blog_view_level'] )
					{
						case 'public':
							if ( !$this->memberData['member_id'] and !$entry['blog_allowguests'] )
							{
								continue 2;
							}
							break;
							
						case 'private':
							if ( !$this->memberData['member_id'] or $this->memberData['member_id'] != $entry['member_id'] )
							{
								continue 2;
							}
							break;
							
						case 'privateclub':
							if ( !$this->memberData['member_id'] or !in_array( $this->memberData['member_id'], explode( ',', $entry['blog_authorized_users'] ) ) )
							{
								continue 2;
							}
							break;
					}
				}
			
				$entry['blog_url']  = $this->registry->blogFunctions->getBlogUrl( $entry['blog_id'] );
				$entry['entry_url'] = $entry['blog_url'] . 'showentry='.$entry['entry_id'];
				
				$return_array[] = $entry;
			}
			return $return_array;
	  	}
		else
		{
			return array();
		}
	}
}