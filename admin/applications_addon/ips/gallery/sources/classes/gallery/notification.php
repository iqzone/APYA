<?php
/**
* Library/Notifications
*
* Helper functions for sending email subscription notifications
*
* @author 		$Author: ips_terabyte $
* @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
* @license		http://www.invisionpower.com/community/board/license.html
* @package		IP.Gallery
* @link			http://www.invisionpower.com
* @version		$Rev: 9574 $
*
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class gallery_notification
{
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
	}	
	
	/**
	 * Sends the album notifications
	 *
	 * @access	public
	 * @param	mixed	$albumid or array or albums
	 * @param	integer	$author
	 * @param	string	$author_name
	 * @return	@e bool
	 */
	public function sendAlbumNotifications( $albumid=0, $author=0, $author_name='' )
	{
		/* Init */
		$albums      = array();
		$count       = 0;
		$date_update = array();
		
		if ( ! is_array( $albumid ) )
		{
			$albums[] = $albumid;
		}
		else
		{
			$albums = $albumid;
		}
		
		$albumid = intval($albumid);
		
		if ( ! count( $albums ) )
		{
			return false;
		}
		
		if ( ! $author )
		{
			$author = $this->memberData['member_id'];
		}
		
		if ( ! $author_name )
		{
			$author_name = $this->memberData['members_display_name'];
		}
		
		/* Load language files */
		$this->lang->loadLanguageFile( array( 'public_global' ), 'gallery' );
		
		/* load like bootstrap */
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like = classes_like::bootstrap( 'gallery', 'albums' );
		
		/* Go loopy */
		foreach( $albums as $albumid )
		{
			$album 	 = $this->registry->gallery->helper('albums')->fetchAlbumsById( $albumid );
			
			if ( ! count( $album ) )
			{
				return false;
			}
		
			$url  = $this->registry->output->buildSEOUrl( 'app=gallery&amp;album=' . $album['album_id'], 'public', $album['album_name_seo'], 'viewalbum' );
			
			/* Fetch like class */
			try
			{
				$_like->sendNotifications( $album['album_id'], array( 'immediate', 'offline' ),   array( 'notification_key'		=> 'new_image',
																									     'notification_url'		=> $url,
																									     'email_template'		=> 'gallery_new_aimage',
																									     'email_subject'		=> $this->lang->words['subject__gallery_new_aimage'],
																									     'build_message_array'	=> array( 'NAME'  		=> '-member:members_display_name-',
																																	      'AUTHOR'		=> $this->memberData['members_display_name'],
																																	      'TITLE' 		=> $album['album_name'],
																																	      'URL'			=> $url ) ) );
			}
			catch( Exception $e )
			{
				/* No like class for this comment class */
			}
		}
		
		return true;
	}
	
}