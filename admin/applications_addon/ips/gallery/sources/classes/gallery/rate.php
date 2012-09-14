<?php
/**
 * Main/Rate
 *
 * Used to rate an image
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link			http://www.invisionpower.com
 * @version		$Rev: 9792 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class gallery_rate
{
	/**
	 * Can rate flag
	 *
	 * @access	public
	 * @var		bool
	 */
	public $can_rate		= false;
	
	/**
	 * Stored error message
	 *
	 * @access	public
	 * @var		string
	 */
	private $errorMessage;
	
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
	 * Constructor
	 *
	 * @access	public
	 * @param	ipsRegistry	$registry
	 * @return	@e void
	 */	
	public function __construct( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();

		$this->albums     = $this->registry->gallery->helper('albums');
		$this->images	  = $this->registry->gallery->helper('image');
	}
	
	/**
	 * Return error message
	 * @return	@e string
	 */
	public function getError()
	{
		return $this->errorMessage;
	}
	
	/**
	 * Add ALBUM rate
	 *
	 * @access	public
	 * @param	integer	$rating
	 * @param	integer	[$albumId]
	 * @param	bool	Return the album data too
	 * @return	@e bool
	 */	
	public function rateAlbum( $rating, $albumId, $returnData=false )
	{
		/* Fetch image */
		$album = $this->albums->fetchAlbumsById( $albumId );
		$save  = array();
		
		/* Can we rate */
		if ( $this->canRate( $album['album_id'] ) !== true )
		{
			$this->errorMessage = 'no_permission';
			return false;
		}
		
		/* Make sure this is a valid rating */
		if ( $rating > 5 OR $rating <= 0 )
		{
			$this->errorMessage = 'invalid_rating';
			return false;
		}
		
		/* Have we voted? */
		$myVote = $this->getMyRating( $albumId, 'album' );
		
		if ( $myVote !== false && ! empty( $myVote['id'] ) )
		{
			/* Update */
			$this->DB->delete( 'gallery_ratings', 'id=' . intval( $myVote['id'] ) );
			
			/* Tweak numbers */
			$album['album_rating_total'] = $album['album_rating_total'] - $myVote['rate'];
			$album['album_rating_count']--;
		}		

		/* Insert the rating */
		$this->DB->insert( 'gallery_ratings', array( 'member_id' 		 => $this->memberData['member_id'],
													 'rating_foreign_id' => $albumId,
													 'rating_where'		 => 'album',
													 'rdate' 			 => time(),
													 'rate' 			 => $rating ) );
		
		/* Update the image information */
		$save['album_rating_total']     = $album['album_rating_total'] + $rating;
		$save['album_rating_count']     = $album['album_rating_count'] + 1;
		$save['album_rating_aggregate'] = round( $save['album_rating_total'] / $save['album_rating_count'] );
		
		$this->DB->update( "gallery_albums_main", $save, 'album_id=' . $album['album_id'] );
		
		$return = array( 'total' => $save['album_rating_total'], 'aggregate' => $save['album_rating_aggregate'], 'count' => $save['album_rating_count'] );
		
		/* Add album data in the return array? */
		if ( $returnData )
		{
			$return['albumData'] = $album;
		}
		
		return $return;
	}
	
	/**
	 * Add IMAGE rate
	 *
	 * @access	public
	 * @param	integer	$rating
	 * @param	integer	[$imageId]
	 * @param	bool	Return the image data too
	 * @return	@e bool
	 */	
	public function rateImage( $rating, $imageId, $returnData=false )
	{
		/* Fetch image */
		$image = $this->images->fetchImage( $imageId );
		$save  = array();
		
		/* Can we rate */
		if ( $this->canRate( $image['img_album_id'] ) !== true )
		{
			$this->errorMessage = 'no_permission';
			return false;
		}

		/* Make sure this is a valid rating */
		if ( $rating > 5 OR $rating <= 0 )
		{
			$this->errorMessage = 'invalid_rating';
			return false;
		}
		
		/* Have we voted? */
		$myVote = $this->getMyRating( $imageId, 'image' );
	
		if ( $myVote !== false && ! empty( $myVote['id'] ) )
		{
			/* Update */
			$this->DB->delete( 'gallery_ratings', 'id=' . intval( $myVote['id'] ) );
			
			/* Tweak numbers */
			$image['ratings_total'] = $image['ratings_total'] - $myVote['rate'];
			$image['ratings_count']--;
		}			
	
		/* Insert the rating */
		$this->DB->insert( 'gallery_ratings', array( 'member_id' 		 => $this->memberData['member_id'],
													 'rating_foreign_id' => $imageId,
													 'rating_where'		 => 'image',
													 'rdate' 			 => time(),
													 'rate' 			 => $rating ) );
		
		/* Update the image information */
		$save['ratings_total'] = $image['ratings_total'] + $rating;
		$save['ratings_count'] = $image['ratings_count'] + 1;
		$save['rating']		   = round( $save['ratings_total'] / $save['ratings_count'] );
		
		$this->DB->update( "gallery_images", $save, 'id=' . $image['id'] );
		
		$return = array( 'total' => $save['ratings_total'], 'aggregate' => $save['rating'], 'count' => $save['ratings_count'] );
		
		/* Add image data in the return array? */
		if ( $returnData )
		{
			$return['imageData'] = $image;
		}
		
		return $return;
	}
	
	/**
	 * Fetch the rating we gave it
	 *
	 * @access	public
	 * @param	array	data
	 * @return	@e int
	 */		
	public function getMyRating( $foreignId, $where='image', $memberId=null )
	{
		$member = ( is_array( $memberId ) ) ? $memberId : ( $memberId === null ? $this->memberData : IPSMember::load( $memberId, 'all' ) );
		$where  = ( $where == 'album' ) ? 'album' : 'image';
		
		if ( ! $member['member_id'] )
		{
			return false;
		}
		
		$myRate = $this->DB->buildAndFetch( array( 'select' => '*',
												   'from'   => 'gallery_ratings',
												   'where'  => 'rating_foreign_id='. intval($foreignId).' AND member_id=' . intval( $member['member_id'] ) . ' AND rating_where=\'' . $where .'\'' ) );
		
		return ( $myRate['rate'] ) ? $myRate : false;
	}
	
	/**
	 * 
	 * Get table joins
	 * @param string $joinField
	 * @param string $where
	 * @param int $memberId
	 */
	public function getTableJoins( $joinField, $where, $memberId )
	{
		$where = ( $where == 'album' ) ? 'album' : 'image';
		
		if ( ! $memberId )
		{
			return false;
		}
		
		return array( 'select' => 'rating.rate, rating.rdate',
					  'from'   => array( 'gallery_ratings' => 'rating' ),
					  'where'  => "rating.rating_foreign_id={$joinField} AND rating.member_id=" . intval( $memberId ) . " AND rating.rating_where='{$where}'"
					 );
	}
	
	/**
	 * Can we rate?
	 * 
	 * @param	$albumId
	 * @param	$memberId
	 */
	public function canRate( $albumId, $memberId=null )
	{
		$member = ( is_array( $memberId ) ) ? $memberId : ( $memberId === null ? $this->memberData : IPSMember::load( $memberId, 'all' ) );
		$album  = ( is_numeric( $albumId ) ) ? $this->albums->fetchAlbumsById( $albumId ) : $albumId;
		
		if ( ! $member['member_id'] )
		{
			return false;
		}
		
		if ( ! $member['g_topic_rate_setting'] )
		{
			return false;
		}
		
		/* can check for albums at some point */
		return true;
	}
}