<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.2.2
 * Rebuild post content plugin
 * Last Updated: $Date: 2011-05-18 12:10:05 -0400 (Wed, 18 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 8829 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class postRebuild_gallery
{
	/**
	 * New content parser
	 *
	 * @access	public
	 * @var		object
	 */
	public $parser;

	/**
	 * Old content parser
	 *
	 * @access	public
	 * @var		object
	 */
	public $oldparser;
	
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
	/**#@-*/
	
	/**
	 * I'm a constructor, twisted constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang	    = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		ipsRegistry::getAppClass('gallery');
	}
	
	/**
	 * Grab the dropdown options
	 *
	 * @access	public
	 * @return	@e array 		Multidimensional array of contents we can rebuild
	 */
	public function getDropdown()
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_gallery' ), 'gallery' );

		$return		= array( array( 'gal_images', ipsRegistry::getClass('class_localization')->words['rebuild_gal_images'] ) );
		$return[]	= array( 'gal_comments', ipsRegistry::getClass('class_localization')->words['rebuild_gal_comms'] );
	    return $return;
	}
	
	/**
	 * Find out if there are any more
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	integer		Start point
	 * @return	@e integer
	 */
	public function getMax( $type, $dis )
	{
		switch( $type )
		{
			case 'gal_images':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'id as nextid', 'from' => 'gallery_images', 'where' => 'id > ' . $dis, 'order' => 'id ASC', 'limit' => array(1)  ) );
			break;
			
			case 'gal_comments':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'pid as nextid', 'from' => 'gallery_comments', 'where' => 'pid > ' . $dis, 'order' => 'pid ASC', 'limit' => array(1)  ) );
			break;
		}

		return intval( $tmp['nextid'] );
	}
	
	/**
	 * Execute the database query to return the results
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	integer		Start point
	 * @param	integer		End point
	 * @return	@e integer
	 */
	public function executeQuery( $type, $start, $end )
	{
		switch( $type )
		{
			case 'gal_images':
				$this->DB->build( array( 'select' 	=> 'i.*',
														 'from' 	=> array( 'gallery_images' => 'i' ),
														 'order' 	=> 'i.id ASC',
														 'where'	=> 'i.id > ' . $start,
														 'limit' 	=> array($end),
														 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
														  									'select'	=> 'm.member_group_id, m.mgroup_others',
														  								  	'from'		=> array( 'members' => 'm' ),
														  								  	'where' 	=> "m.member_id=i.member_id"
														  						)	)
												) 		);
			break;
			
			case 'gal_comments':
				$this->DB->build( array( 'select' 	=> 'c.*',
														 'from' 	=> array( 'gallery_comments' => 'c' ),
														 'order' 	=> 'c.pid ASC',
														 'where'	=> 'c.pid > ' . $start,
														 'limit' 	=> array($end),
														 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
														  									'select'	=> 'm.member_group_id, m.mgroup_others',
														  								  	'from'		=> array( 'members' => 'm' ),
														  								  	'where' 	=> "m.member_id=c.author_id"
														  						),
														  						2 => array( 'type'		=> 'left',
														  									'select'	=> 'i.img_album_id',
														  								  	'from'		=> array( 'gallery_images' => 'i' ),
														  								  	'where' 	=> "i.id=c.img_id"
														  						)	)
												) 		);
			break;
		}
	}
	
	/**
	 * Get preEditParse of the content
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	array 		Database record from while loop
	 * @return	@e string		Content preEditParse
	 */
	public function getRawPost( $type, $r )
	{
		$this->parser->parse_smilies	= $this->oldparser->parse_smilies	= 1;
		$this->parser->parse_html		= $this->oldparser->parse_html		= 0;
		$this->parser->parse_bbcode		= $this->oldparser->parse_bbcode	= 1;
		$this->parser->parse_nl2br		= $this->oldparser->parse_nl2br		= 1;

		switch( $type )
		{
			case 'gal_images':
				$this->parser->parsing_section	= 'gallery_image';

				$rawpost = $this->oldparser->preEditParse( $r['description'] );
			break;
			
			case 'gal_comments':
				$this->parser->parsing_section	= 'gallery_comment';

				$rawpost = $this->oldparser->preEditParse( $r['comment'] );
			break;
		}

		return $rawpost;
	}
	
	/**
	 * Store the newly converted content
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	array 		Database record from while loop
	 * @param	string		Newly parsed post
	 * @return	@e string		Content preEditParse
	 */
	public function storeNewPost( $type, $r, $newpost )
	{
		$lastId	= 0;
		
		switch( $type )
		{
			case 'gal_images':
				$this->DB->update( 'gallery_images', array( 'description' => $newpost ), 'id='.$r['id'] );
				$lastId = $r['id'];
			break;
			
			case 'gal_comments':
				$this->DB->update( 'gallery_comments', array( 'comment' => $newpost ), 'pid='.$r['pid'] );
				$lastId = $r['pid'];
			break;
		}

		return $lastId;
	}
}