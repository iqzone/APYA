<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Rate Image
 * Last Updated: $LastChangedDate: 2011-05-20 06:00:55 -0400 (Fri, 20 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8849 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_gallery_images_viewphotos extends ipsCommand
{

        private $_isFBUser = false;
        private $facebook;
        private $_userToken;
        public $extendedPerms = array( 'email', 'read_stream', 'publish_stream' );
        public $settings;
        private $images;

        private $filename;
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{

            $this->registry = $registry;

	        /* Gallery Object */
	        $classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . '/applications_addon/ips/gallery/sources/classes/gallery.php', 'ipsGallery' );
	        $this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
            
            $this->memberData =& $this->registry->member()->fetchMemberData();
            $this->settings   =& $this->registry->fetchSettings();
            
            define("FACEBOOK_APP_ID"      , trim( $this->settings['fbc_appid'] ) );
            define("FACEBOOK_APP_SECRET"  , trim( $this->settings['fbc_secret'] ) );
            define("FACEBOOK_CALLBACK"    , $this->settings['_original_base_url'] . '/interface/facebook/index.php?m=' . $this->memberData['member_id'] );
            
            /* Load oAuth */
            require_once( IPS_KERNEL_PATH . 'facebook/facebookoauth.php' );/*noLibHook*/
            $this->_oauth = new FacebookOAuth( FACEBOOK_APP_ID, FACEBOOK_APP_SECRET, FACEBOOK_CALLBACK, array( 'email', 'read_stream', 'publish_stream', 'user_photos' ) );

            /* Load API */
            require_once( IPS_KERNEL_PATH . 'facebook/facebook.php' );/*noLibHook*/
            $this->facebook = new Facebook( array( 'appId' => FACEBOOK_APP_ID, 'secret' => FACEBOOK_APP_SECRET, 'cookie' => true ) );
            
            $token  = $this->memberData['fb_token'];
            
            /* Auto do it man */
            if ( ! $userId AND $this->memberData['member_id'] AND $this->memberData['fb_uid'] )
            {
                    $userId  = $this->memberData['fb_uid'];
            }

            $this->_userToken  = trim( $token );
            $this->_userId     = trim( $userId ); /* never int - max ids are larger than int */
            
            /* Facebook? */
            if ( $this->_userToken AND $this->_userId )
            {
                    $this->_isFBUser = true;
            }

            $aid = $this->request[ 'aid' ];


            
		$this->html = $this->registry->output->getTemplate('gallery_facebook');
        $photos = array();
        $selectPhotos = $this->request['photos'];

	    if(is_array($selectPhotos) && isset($_POST))
	    {
	    	$photosStr = join( '", "', $selectPhotos );
	    	$photoCollection = $this->facebook->api( array(  'method' => 'fql.query',
	    											         'query'  =>  $this->convertToStrSelect( $photosStr ),
	    								  					 'access_token' => $this->_userToken ) );



			for($i=0; $i<count($photoCollection); $i++)
			{
				$this->loadJpeg($photoCollection[$i]['src_big'], $photoCollection[$i]['caption'], $photoCollection[$i]['pid']);
			}

			$output = $this->html->importImages($this->images);
	    	//photoCollection es el SRC de la fotografía con ese SRC tenemos que descargar la fotografía para subirla a la galería.
	    }
	    else
	    {

			if ( $this->_userToken AND $this->_userId  )
			{
				try
				{
	                            $photos = $this->facebook->api(array(       'method'       => 'fql.query',
	                                                                        'query'        => 'select pid, src_big, caption from photo where album_object_id="'.$aid.'"',
	                                                                        'access_token' => $this->_userToken ) );
				}
				catch( Exception $e )
				{
	                var_dump($e);
				}
				
				if ( $_userData['id'] AND $_userData['id'] == $this->_userId )
				{
					$this->_userData  = $_userData;
					$this->_connected = true;
				}
				else
				{
					$this->_userData  = array();
					$this->_connected = false;
				}
			}
			/* Display processed results */

			$output = $this->html->listPhotosByAlbum($this->_isFBUser, $photos);
		}

		$this->registry->getClass('output')->setTitle( 'Importar fotografías de facebook' );
		$this->registry->getClass('output')->addContent( $output );
		$this->registry->getClass('output')->sendOutput();
	}

	private function convertToStrSelect($photos)
	{
		return "SELECT pid, src_big, caption FROM photo WHERE pid IN (\"$photos\")";
	}

	private function loadJpeg($url, $caption, $original_name) {
		  $ch = curl_init();
		  curl_setopt($ch, CURLOPT_URL, $url);
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
		  $fileContents = curl_exec($ch);
		  curl_close($ch);
		  $img = imagecreatefromstring($fileContents);
		  $this->uploadimage($img, $caption, $original_name);
	}

	private function uploadimage( $src_dir, $caption, $original_name )
	{
      		
		/*Create album*/
		/* Cannot create? */
		$album = $this->DB->buildAndFetch(array('select' => 'album_id', 'from' => 'gallery_albums_main', 'where' => "album_owner_id=".$this->memberData['member_id'] . " and album_name =  'Facebook de {$this->memberData['members_l_username']}'"));
		$album_id = (int)$album['album_id'];

		if(!$album_id) {
			if ( !$this->registry->gallery->helper('albums')->canCreate() )
			{
				$this->returnJsonError( $this->lang->words['album_cannot_create_limit'] );
			}
			/* Fix up names, damn charsets */
        	$name = IPSText::convertUnicode( 'Facebook de ' . $this->memberData['members_l_username'], 0 , true );
    		$name = IPSText::convertCharsets( $name, 'utf-8', IPS_DOC_CHAR_SET );
    		$desc = IPSText::convertUnicode( $caption, 0, true );
    		$desc = IPSText::convertCharsets( $desc, 'utf-8', IPS_DOC_CHAR_SET );
    		/* Init data */
    		$album = array( 'album_name'			=> $name,
    						'album_description'		=> $desc,
    						'album_detail_default'	=> intval( 0 ),
    						'album_sort_options'	=> serialize( array( 'key' => 'idate', 'dir' => 'asc' ) ),
    						'album_is_public'		=> intval( 2 ),
    						'album_parent_id'		=> intval( 1 ),
    						'album_owner_id'		=> intval( $this->memberData['member_id'] ),
    						'album_watermark'		=> intval( '' )
    						);
    		
    		/* Save it for the judge */
    		try 
    		{
    			$album = $this->registry->gallery->helper('moderate')->createAlbum( $album );
    			$album_id = intval( $album['album_id'] );
    			@mkdir($this->settings['gallery_images_path'] . "/gallery/album_{$album_id}", 0777);
    		
    		}
    		catch ( Exception $e )
    		{
    			$msg = $e->getMessage();
    			
    			if ( $msg == 'MEMBER_ALBUM_NO_PARENT' )
    			{
    				$msg = $this->lang->words['parent_zero_not_global'];
    			}
    			
    			$this->returnJsonError( $msg );
    		}

		}
		$filename = "gallery_{$this->memberData['member_id']}_{$album_id}_{$album_id}_". $original_name . '.jpg';
	  	$this->filename = $this->settings['gallery_images_path'] . "/gallery/album_{$album_id}/{$filename}";
	  	$img = imagejpeg($src_dir, $this->filename, 90);
		
		if(!file_exists($this->filename))
		{
			return false;
		}

		$image = array( 'member_id' => $this->memberData['member_id'],
						 'img_album_id' => $album_id,
						 'caption' => $desc,
						 'directory' => "gallery/album_{$album_id}",
						 'masked_file_name' => $filename,
						 'medium_file_name' => $filename,
						 'original_file_name' => '',
						 'file_name' => $original_name,
						 'file_size' => getimagesize($src_dir),
						 'file_type' => 'jpg',
						 'approved' => 1,
						 'thumbnail' => 1,
						 'idate' => time(),
						 'image_privacy' => 2,

					 );


		$this->DB->insert( 'gallery_images', $image );

		$this->images[] = $image;
	}
}