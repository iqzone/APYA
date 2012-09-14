<?php

/**
 * <pre>
 * Library/Main Gallery Library
 * Refactored by Matt "Sweet Lord, why Me"cham
 *
 * Tis Important mmhmm
 * Last Updated: $Date: 2011-12-09 15:24:24 -0500 (Fri, 09 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9978 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class ipsGallery 
{
	/**
	 * Classes array
	 * @access	protected
	 * @param	array
	 */
	protected $_classes = array();
	
	/**
	 * Classes array
	 * @access	public
	 * @param	array
	 */
	public $thumbSizes = array();
	
	/**
	 * User can upload flag
	 *
	 * @access	private
	 * @param	boolean
	 */
	private $_userCanUpload = false;
	
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
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
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
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
		
		define( 'GALLERY_MEDIA_FORCE_NO_FLASH_PLAYER', 1 );
		define( 'GALLERY_MEDIA_IMAGES_PER_ALBUM_PAGE', $this->settings['gallery_images_per_page'] );
		define( 'GALLERY_IMAGE_BYPASS_PERMS', true );
		define( 'GALLERY_PNG_QUALITY', $this->settings['gallery_image_quality_png'] );
		define( 'GALLERY_JPG_QUALITY', $this->settings['gallery_image_quality_jpg'] );
		
		if ( ! defined('IPS_MAPPING_SERVICE') )
		{
			define('IPS_MAPPING_SERVICE', 'bing');
			define('BING_API_KEY', ( ! empty( $this->settings['map_bing_api_key'] ) ) ? $this->settings['map_bing_api_key'] : false );
		}
		
		/* Clean member perm array */
		foreach( $this->member->perm_id_array as $k => $v )
		{
			if ( empty( $v ) )
			{
				unset( $this->member->perm_id_array[ $k ] );
			}
		}
		
		/* Ensure guests can't mod albums */
		if ( ! $this->memberData['member_id'] )
		{
			$this->memberData['g_mod_albums'] = 0;
		}
		
		/* Determine if current user can upload new images */
		$this->setCanUpload();
		
		/* Generate thumb sizes */
		$this->thumbSizes = array( 'large'  => '100',
								   'medium' => '75',
								   'small'  => '50',
								   'tiny'   => '30',
								   'teeny'  => '24', #The naming is clearly getting silly
								   'icon'   => '16' );
	}
	
	/**
	 * Set whether the current member can upload / add images
	 *
	 * @param	boolean	Force a value
	 * @return	@e void
	 */
	public function setCanUpload( $force=null )
	{
		if ( $force !== null )
		{
			$this->_userCanUpload = $force;
		}
		else
		{
			$this->_userCanUpload = false;
			
			if ( $this->memberData['member_id'] )
			{
				$perms = explode( ':', $this->memberData['gallery_perms'] );
				
				if ( empty($perms[1]) )
				{
					$this->_userCanUpload = false;
					return; #Block here
				}
			}
			
			if ( $this->memberData['g_max_diskspace'] != 0 )
			{
				$this->_userCanUpload = true;
			}
			
			if ( $this->memberData['g_album_limit'] == 0 || $this->memberData['g_img_album_limit'] == 0 )
			{
				/* Workaround to get global albums that allow upload - can't use the helper function here or you get a nice infinite loop */
				$_where   = array();
				$_where[] = '(album_is_global=1 AND ' . $this->DB->buildWherePermission( $this->member->perm_id_array, 'album_g_perms_view', true ) . ' AND ' . $this->DB->buildWherePermission( $this->member->perm_id_array, 'album_g_perms_images', true ) . ')';
				
				if ( $this->memberData['member_id'] )
				{
					$_where[] = '(album_is_global=0 AND album_owner_id=' . intval($this->memberData['member_id']) . ')';
				}
				
				$_albums = $this->DB->buildAndFetch( array( 'select' => 'count(album_id) as count',
															'from'   => 'gallery_albums_main',
															'where'  => implode( ' OR ', $_where )
													)		);
				
				$this->_userCanUpload = intval($_albums['count']) ? true : false;
			}
		}
	}
	
	/**
	 * Get whether the current member can upload / add images
	 *
	 * @access	public
	 */
	public function getCanUpload()
	{
		return $this->_userCanUpload;
	}
	
	/**
	 * Resets the user's has gallery flag
	 * 
	 * @param	int
	 */
	public function resetHasGallery( $memberId )
	{
		if ( $memberId )
		{
			$myAlbums = $this->helper('albums')->fetchAlbumsByOwner( $memberId );
			
			$val      = count( $myAlbums ) ? 1 : 0;
			
			IPSMember::save( $memberId, array( 'core' => array( 'has_gallery' => $val ) ) );
		}
	}
	
	/**
	 * Auto load classes fo'sho
	 *
	 * @access	public
	 * @param	string		Class Name
	 * @param	mixed		Any arguments
	 */
	public function helper( $name )
	{
		if ( isset( $this->_classes[ $name ] ) && is_object( $this->_classes[ $name ] ) )
		{
			return $this->_classes[ $name ];
		}
		else
		{
			if ( !ipsRegistry::isClassLoaded('gallery') )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
				$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
			}
			
			$_fn = IPSLib::getAppDir('gallery') . '/sources/classes/gallery/' . $name . '.php';
			
			if ( is_file( $_fn ) )
			{
				$classToLoad = IPSLib::loadLibrary( $_fn, 'gallery_' . $name, 'gallery' );
				
				$this->_classes[ $name ] = new $classToLoad( $this->registry );
				
				return $this->_classes[ $name ];
			}
			else
			{
				trigger_error( 'Cannot locate a class in /sources/classes/gallery/' . $name . '.php' );
			}
		}
	}
	
	/**
	 * Check global access
	 *
	 * @return	@e mixed	Boolean true, or outputs an error
	 */
	public function checkGlobalAccess()
	{
		$showError = false;
		
		/* Permission Check */
		if ( ! $this->memberData['g_gallery_use'] )
		{
			$showError = TRUE;
		}
		elseif ( $this->memberData['member_id'] )
		{
			$perms = explode( ':', $this->memberData['gallery_perms'] );
			
			if ( empty($perms[0]) )
			{
				$showError = TRUE;
			}
		}
		
        if ( $showError )
        {
        	if ( IPS_IS_AJAX )
        	{
				$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
				$ajax = new $classToLoad();
				
        		$ajax->returnString( 'nopermission' );
        	}
        	else
        	{
				/* Setup a few things manually, since we're stopping execution early */
				$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
				$this->registry->member()->finalizePublicMember();
	        	
	            $this->registry->output->showError( 'no_permission', 107187, null, null, 403 );
        	}
        }
        
		/* Offline */
		if( $this->settings['gallery_offline'] )
		{
			if ( $this->memberData['g_access_offline'] )
			{
				$warn_desc = str_replace( '<#MSG#>', $this->settings['gallery_offline_text'], $this->lang->words['warn_offline_desc'] );
				
				$offline_warning = $this->registry->output->getTemplate('gallery_global')->general_warning( array( 'title' => $this->lang->words['warn_offline_title'], 'body' => $warn_desc ) );
				
				$this->registry->output->addContent( $offline_warning );
			}
			else
			{
	        	if ( IPS_IS_AJAX )
	        	{
					$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
					$ajax = new $classToLoad();
					
	        		$ajax->returnString( 'nopermission' );
	        	}
	        	else
	        	{
					/* Setup a few things manually, since we're stopping execution early */
					$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
					$this->registry->member()->finalizePublicMember();
		        	
		            $this->registry->output->showError( $this->settings['gallery_offline_text'], 107188, null, null, 403 );
	        	}
			}
		}
		
		return true;
	}
		
	/**
	 * Rebuilds gallery statistics
	 *
	 * @return	@e void
	 */
	public function rebuildStatsCache()
	{
		/* Files and diskspace */
		$totals = $this->DB->buildAndFetch( array( 'select' => 'SUM(file_size) as diskspace, SUM(views) as views', 'from' => 'gallery_images' ) );
		$totals['diskspace'] = IPSLib::sizeFormat( $totals['diskspace'] );
		
		/* Fetch total comments */
		$commentsVisible = $this->DB->buildAndFetch( array( 'select' => 'SUM(album_count_comments) AS total',
														    'from'   => 'gallery_albums_main' ) );
		
		$commentsHidden  = $this->DB->buildAndFetch( array( 'select' => 'SUM(album_count_comments_hidden) AS total',
														    'from'   => 'gallery_albums_main' ) );
		
		$imagesVisible   = $this->DB->buildAndFetch( array( 'select' => 'SUM(album_count_imgs) AS total',
														    'from'   => 'gallery_albums_main' ) );
		
		$imagesHidden    = $this->DB->buildAndFetch( array( 'select' => 'SUM(album_count_imgs_hidden) AS total',
														    'from'   => 'gallery_albums_main' ) );

		/* Fetch total album count */
		$total_albums = $this->DB->buildAndFetch( array( 'select' => 'count(*) AS total', 'from' => 'gallery_albums_main' ) );
		
		/* Member albums */
		$memberAlbum = $this->DB->buildAndFetch(array( 'select' => 'album_node_left, album_node_right',
													   'from'   => 'gallery_albums_main',
													   'where'  => 'album_id=' . intval($this->settings['gallery_members_album']) ) );
		
		/* Now get 'em */
		if ( $memberAlbum['album_id'] )
		{
			$totalMember = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as albums',
															'from'   => 'gallery_albums_main',
															'where'  => 'album_is_public=1 AND album_node_left > ' . intval( $memberAlbum['album_node_left'] ) . ' AND album_node_right < ' . intval( $memberAlbum['album_node_right'] ) ) );
		}
		
		$cache = array( 'total_images_visible'		 => $imagesVisible['total'],
						'total_images_hidden'		 => $imagesHidden['total'],
						'total_diskspace'			 => $totals['diskspace'],
						'total_views'				 => $totals['views'],
						'total_comments_visible'	 => $commentsVisible['total'],
						'total_comments_hidden'		 => $commentsHidden['total'],
						'total_albums'				 => $total_albums['total'],
						'total_public_member_albums' => intval($totalMember['albums'] ) );
		
		/* Data Hook Location */
		IPSLib::doDataHooks( $cache, 'galleryRebuildStatsCache' );
	
		$this->cache->setCache( 'gallery_stats', $cache, array( 'array' => 1 ) );
	}

	/**
	 * Inline resizes thumbnails from one thumb size to another
	 *
	 * @access	public
	 * @param	string		<img tag
	 * @param	int			Width
	 * @param	int			Height
	 * @param	string		Class name to add
	 */
	public function inlineResize( $tag, $width, $height, $class='' )
	{			
		/* Init */
		$height = ( $height ) ? $height : $width;	
			
		if ( ! is_numeric( $width ) )
		{
			$width = intval( $this->thumbSizes[ str_replace( 'thumb_', '', $width ) ] );
		}
		
		if ( ! is_numeric( $height ) )
		{
			$height = intval( $this->thumbSizes[ str_replace( 'thumb_', '', $height ) ] );
		}	
		
		/* Quickly resize */
		if ( strstr( $tag, "width='" ) )
		{
			$tag = preg_replace( "#width='([^']+?)'#", "width='" . $width . "'", $tag );
		}
		
		if ( strstr( $tag, "height='" ) )
		{
			$tag = preg_replace( "#height='([^']+?)'#", "height='" . $height . "'", $tag );
		}
		
		if ( $class && strstr( $tag, "galattach") )
		{
			$tag = preg_replace( "#class='galattach#", "class='galattach " . $class . "'", $tag );
		}
		
		if ( strstr( $tag, "cover_img___xx___") )
		{
			$tag = str_ireplace( "cover_img___xx___", "cover_img_" . $width, $tag );
		}
		
		return $tag;
	}
	
}