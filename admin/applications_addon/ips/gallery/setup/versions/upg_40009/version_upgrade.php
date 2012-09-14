<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * IP.Gallery version upgrader
 * Last Updated: $Date: 2011-11-02 05:39:35 -0400 (Wed, 02 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		1st Dec 2009
 * @version		$Revision: 9732 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @var		string
	 */
	private $_output = '';
	
	/**
	 * fetchs output
	 * 
	 * @return	@e string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
		
	/**
	 * Execute selected method
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		
		/* Set time out */
		@set_time_limit( 3600 );
		
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			case 'faves':
				$this->convertFaves();
				break;
			case 'subs':
				$this->convertSubs();
				break;
			case 'resetMemberAlbums':
				$this->resetMemberAlbums();
				break;
			default:
				$this->convertAlbums();
				break;
		}
		
		/* Workact is set in the function, so if it has not been set, then we're done. The last function should unset it. */
		if ( $this->request['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Convert old album subscriptions
	 * 
	 * @return	@e void
	 */
	public function convertAlbums()
	{
		/* Got the table? */
		if( $this->DB->checkForTable('gallery_subscriptions') )
		{
			/* Init vars */
			$members		= array();
			$memberIds		= array();
			$realMembers	= array();
			
			/* Convert watched albums */
			$this->DB->build( array( 'select' => 'sub_mid, sub_toid', 'from' => 'gallery_subscriptions', 'where' => "sub_type='album'" ) );
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				$memberIds[ $r['sub_mid'] ] = $r['sub_mid'];
				
				$members[ $r['sub_toid'] ][ $r['sub_mid'] ]	= array('like_rel_id'		=> $r['sub_toid'],
																	'like_member_id'	=> $r['sub_mid'],
																	'like_notify_do'	=> 1
																	);
			}
			
			/* Create new like records */
			$realMembers = IPSMember::load( $memberIds, 'core' );
			
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like = classes_like::bootstrap( 'gallery', 'albums' );
			
			if( count($members) )
			{
				foreach( $members as $album => $_members )
				{
					foreach( $_members as $member )
					{
						if( !$member['like_member_id'] OR !isset($realMembers[ $member['like_member_id'] ]) )
						{
							continue;
						}
	
						$_like->add( $member['like_rel_id'], $member['like_member_id'], array( 'like_notify_do'	=> $member['like_notify_do'], 'like_notify_freq' => 'immediate' ), false );
					}
				}
				
				$this->registry->output->addMessage("Album subscriptions converted....");
			}
			else
			{
				$this->registry->output->addMessage("No album subscriptions found to convert....");
			}
		}
		else
		{
			$this->registry->output->addMessage("Subscriptions conversion skipped, no old table found....");
		}
		
		/* Continue with images */
		$this->request['workact'] = 'subs';
	}
	
	
	/**
	 * Convert old image favorites/subscriptions
	 * 
	 * @return	@e void
	 */
	public function convertSubs()
	{
		$st			       = trim( $this->request['st'] );
		list( $id, $done ) = explode( '-', $st );
		$cycleDone         = 0;

		/* Got the tables? */
		if( $this->DB->checkForTable('gallery_subscriptions') )
		{
			$total = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
											          'from'   => 'gallery_subscriptions',
											          'where'  => "sub_type='image'" ) );
											          
			/* Init vars */
			$members		= array();
			$memberIds		= array();
			$realMembers	= array();
			
			/* Convert favorites */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'gallery_subscriptions',
									 'where'  => 'sub_type=\'image\' AND sub_id > ' . intval( $id ),
									 'limit'  => array( 0, 500 ),
									 'order'  => 'sub_id asc' ) );
									 
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				$cycleDone++;
				$lastId = $r['sub_id'];
				
				$memberIds[ $r['sub_mid'] ] = $r['sub_mid'];
				
				if( isset($members[ $r['sub_toid'] ][ $r['sub_mid'] ]) )
				{
					$members[ $r['sub_toid'] ][ $r['sub_mid'] ]['like_notify_do']	= 1;
				}
				else
				{
					$members[ $r['sub_toid'] ][ $r['sub_mid'] ] = array('like_rel_id'		=> $r['sub_toid'],
																		'like_member_id'	=> $r['sub_mid'],
																		'like_notify_do'	=> 1
																		);
				}
			}
			
			/* Create new like records */
			$realMembers = IPSMember::load( $memberIds, 'core' );
			
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like = classes_like::bootstrap( 'gallery', 'images' );
			
			if( count($members) )
			{
				foreach( $members as $file => $_members )
				{
					foreach( $_members as $member )
					{
						if( !$member['like_member_id'] OR !isset($realMembers[ $member['like_member_id'] ]) )
						{
							continue;
						}
	
						$_like->add( $member['like_rel_id'], $member['like_member_id'], array( 'like_notify_do'	=> $member['like_notify_do'], 'like_notify_freq' => 'immediate' ), false );
					}
				}
			}
			else
			{
				$this->registry->output->addMessage("No images subscriptions found to convert....");
			}
			
			/* More? */
			if ( $cycleDone )
			{
				/* Reset */
				$done += $cycleDone;
				
				$this->registry->output->addMessage("Images subscriptions converted {$done}/{$total['count']}....");
				$this->request['st'] 	  = $lastId . '-' . $done;
				
				/* Reset data and go again */
				$this->request['workact'] = 'subs';
				return;
			}
			else
			{
				$this->registry->output->addMessage("All subscriptions converted....");
				$this->request['workact'] = 'faves';
				$this->request['st']      = '';
				
				return;
			}
		}
		else
		{
			$this->registry->output->addMessage("Favorites conversion skipped, no old tables found....");
		}
		
		/* Continue with reset albums */
		$this->request['workact'] = 'faves';
	}
	
	/**
	 * Convert old image favorites/subscriptions
	 * 
	 * @return	@e void
	 */
	public function convertFaves()
	{
		$id                = intval( $this->request['id'] );
		$st			       = trim( $this->request['st'] );
		list( $id, $done ) = explode( '-', $st );
		$cycleDone  = 0;

		/* Got the tables? */
		if( $this->DB->checkForTable('gallery_favorites') )
		{
			$total = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
											          'from'   => 'gallery_favorites' ) );
											          
			/* Init vars */
			$members		= array();
			$memberIds		= array();
			$realMembers	= array();
			
			/* Convert favorites */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'gallery_favorites',
									 'where'  => 'id > ' . intval( $id ),
									 'limit'  => array( 0, 500 ),
									 'order'  => 'id asc' ) );
									 
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				$cycleDone++;
				$lastId = $r['id'];
				
				$memberIds[ $r['member_id'] ]				= $r['member_id'];
				$members[ $r['img_id'] ][ $r['member_id'] ]	= array('like_rel_id'		=> $r['img_id'],
																	'like_member_id'	=> $r['member_id'],
																	'like_notify_do'	=> 0
																	);
			}
			
			/* Create new like records */
			$realMembers = IPSMember::load( $memberIds, 'core' );
			
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like = classes_like::bootstrap( 'gallery', 'images' );
			
			if( count($members) )
			{
				foreach( $members as $file => $_members )
				{
					foreach( $_members as $member )
					{
						if( !$member['like_member_id'] OR !isset($realMembers[ $member['like_member_id'] ]) )
						{
							continue;
						}
	
						$_like->add( $member['like_rel_id'], $member['like_member_id'], array( 'like_notify_do'	=> $member['like_notify_do'], 'like_notify_freq' => 'immediate' ), false );
					}
				}
			}
			else
			{
				$this->registry->output->addMessage("No images favorites found to convert....");
			}
			
			/* More? */
			if ( $cycleDone )
			{
				/* Reset */
				$done += $cycleDone;
				
				$this->registry->output->addMessage("Images favorites converted {$done}/{$total['count']}....");
				
				$this->request['st'] 	  = $lastId . '-' . $done;
				
				/* Reset data and go again */
				$this->request['workact'] = 'faves';
				return;
			}
			else
			{
				$this->registry->output->addMessage("Image favorites converted....");
				$this->request['workact'] = 'resetMemberAlbums';
				$this->request['st']      = '';
				return;
			}
		}
		else
		{
			$this->registry->output->addMessage("Favorites conversion skipped, no old tables found....");
		}
		
		/* Continue with reset albums */
		$this->request['workact'] = 'resetMemberAlbums';
	}
	
	
	/**
	 * Reset member albums without a parent and
	 * move them to a temporary one
	 * 
	 * @return	@e void
	 */
	public function resetMemberAlbums()
	{
		/* this is now done in 42000 */
		$this->registry->output->addMessage("Reset not required at this stage....");
		
		/* Continue with upgrade */
		$this->request['workact'] = '';
		
		return;
		
		
		
		
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(album_id) as albums', 'from' => 'gallery_albums_main', 'where' => 'album_parent_id=0 AND album_is_global=0' ) );
		
		if ( intval($count['albums']) )
		{
			/* Gallery Object */
			require_once( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php' );/*noLibHook*/
			$gallery = new ipsGallery( $this->registry );
			
			/* Update array */
			$save = array( 'album_name'				=> 'Temporary global album for root member albums',
						   'album_description'		=> "This is a temporary global album that holds the member albums that didn't have the proper parent album set. This album has NO permissions and is not visible from the public side, please move the albums in the proper location.",
						   'album_parent_id'		=> 0,
						   'album_is_public'		=> 0,
						   'album_is_global'		=> 1,
						   'album_g_container_only'	=> 1,
						   'album_allow_comments'	=> 0,
						   'album_g_approve_com'	=> 0,
						   'album_g_approve_img'	=> 0,
						   'album_sort_options'		=> serialize( array( 'key' => 'ASC', 'dir' => 'idate' ) ),
						   'album_detail_default'	=> 0,
						   'album_after_forum_id'	=> 0,
						   'album_watermark'		=> 0,
						  );
			
			$newAlbum = $gallery->helper('moderate')->createAlbum( $save );
			
			/* Update albums and reset permissions/node tree.. */
			$this->DB->update( 'gallery_albums_main', array( 'album_parent_id' => $newAlbum['album_id'] ), 'album_parent_id=0 AND album_is_global=0' );
			
			$gallery->helper('moderate')->resetPermissions(0);
			$gallery->helper('albums')->rebuildNodeTree();
			
			
			$this->registry->output->addMessage("Orphaned root member albums (" . intval($count['albums']) . ") moved to a temporary global album....");
		}
		else
		{
			$this->registry->output->addMessage("No orphaned root member albums found....");
		}
		
		/* Continue with upgrade */
		$this->request['workact'] = '';
	}
}