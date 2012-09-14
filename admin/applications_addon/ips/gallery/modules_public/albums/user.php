<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Category Listing
 * Last Updated: $LastChangedDate: 2011-11-07 04:35:19 -0500 (Mon, 07 Nov 2011) $
 * </pre>
 *
 * @author		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9767 $
 *
 */
 
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_albums_user extends ipsCommand
{
	/**
	 * Generated Output
	 *
	 * @access	private
	 * @var		string
	 */
	private $output;
	
	/**
	 * Navigation
	 * @var array
	 */
	private $nav = array();
	
	/**
	 * Document title
	 * @var string
	 */
	private $title = '';
	
	/**
	 * Album helper
	 * 
	 * @access	private
	 * @var		object
	 */
	private $albums;
	
	/**
	 * Image helper
	 * 
	 * @access	private
	 * @var		object
	 */
	private $images;
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */ 
	public function doExecute( ipsRegistry $registry )
	{
		/* Get member ID */
		if ( !empty($this->request['member_id']) )
		{
			$memberId = $this->request['member_id'];
		}
		elseif ( !empty($this->request['user']) )
		{
			$memberId = $this->request['user'];
		}
		else
		{
			/* Fallback to logged in user */
			$memberId = $this->memberData['member_id'];
		}
		
		/* Set up class vars */
		$this->albums  = $this->registry->gallery->helper('albums');
		$this->images  = $this->registry->gallery->helper('image');
		
		/* Favorites */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_like = classes_like::bootstrap( 'gallery', 'albums' );
		
		/* What the hell are we doing? */
		switch( $this->request['do'] )
		{
			case 'list':
			default:
				$output = $this->_showMemberAlbum( $memberId );
			break;
		}
		
		/* Output */
		$this->registry->getClass('output')->setTitle( $this->title );
		$this->registry->getClass('output')->addContent( $output );
		
		if ( is_array( $this->nav ) AND count( $this->nav ) )
		{
			foreach( $this->nav as $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}
		
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Display a member album
	 * 
	 * @access	protected
	 * @param	int			$albumId
	 * @return 	string		H T M L
	 */
	protected function _showMemberAlbum( $memberId )
	{
		/* Display method */
		$owner    = IPSMember::buildProfilePhoto( IPSMember::load( $memberId ) );
		
		if ( ! $owner['member_id'] )
		{
			$this->registry->getClass('output')->showError( $this->lang->words['user_album_no_member'], 'albums_user_show_1', null, null, 404 );
		}
	
		$data     = array( 'album_name' => sprintf( $this->lang->words['member_x_albums'], $owner['members_display_name'] ), 'album_name_userview' => sprintf( $this->lang->words['member_x_albums'], IPSMember::makeProfileLink( $owner['members_display_name'], $owner['member_id'], $owner['members_seo_name'] ) ) );
		$start    = intval( $this->request['st'] );
		
		/* First stop, fetch the album */
		$children = $this->albums->fetchAlbumsByOwner( $owner['member_id'], array( 'limitDescendants' => 5 ) );
		
		$data['owner'] = $owner;
		
		/* Fetch random medium image */
		$feature  = $this->images->fetchImages( $this->memberData['member_id'], array( 'ownerId' => $owner['member_id'], 'featured' => true, 'limit' => 1, 'sortKey' => 'random' ) );
		$feature  = array_pop( $feature );
		
		if ( ! empty( $feature['description'] ) )
		{
			$feature['description'] = IPSText::truncate( IPSText::getTextClass('bbcode')->stripAllTags( $feature['description'] ), 300 );
		}
		
		$feature['tag'] = $this->images->makeImageLink( $feature, array( 'type' => 'medium', 'link-type' => 'page' ) );
		
		/* Fetch member images */
		$images = $this->images->fetchImages( $this->memberData['member_id'], array( 'ownerId' => $owner['member_id'], 'getTotalCount' => true, 'offset' => $start, 'limit' => 30, 'sortKey' => 'date', 'sortOrder' => 'desc' ) );
		$count  = $this->images->getCount();
		
		if ( $count > 30 )
		{
			$data['_pages'] = $this->registry->output->generatePagination(  array(  'totalItems'		=> $count,
																		   			'itemsPerPage'		=> 30,
																		   			'currentStartValue'	=> $start,
																		   			'seoTitle'			=> $owner['members_seo_name'],
																		   			'seoTemplate'		=> 'useralbum',
																		   			'baseUrl'			=> 'app=gallery&amp;user=' . $owner['member_id'] ) );
		}
			
		/* Fetch recent comments */
		$comments = $this->images->fetchImages( $this->memberData['member_id'], array( 'ownerId' => $owner['member_id'], 'getLatestComment' => true, 'hasComments' => true, 'sortKey' => 'lastcomment', 'sortOrder' => 'desc', 'offset' => 0, 'limit' => 5 ) );
		
		/* Seriously, what? */
		$output = $this->registry->output->getTemplate('gallery_albums')->albumFeatureView( $feature, $images, $data, $children, $data['album_name'], array(), $comments, 0, true, $children );
		
		$this->title = $data['album_name'] . ' - ' . IPSLIb::getAppTitle('gallery') . ' - ' . $this->settings['board_name'];
		
		$this->nav   = array( array( IPSLIb::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' ) );	
		
		$this->nav[] = array( $data['album_name'], '' );
		
		return $output;
	}
}