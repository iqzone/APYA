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

class public_gallery_images_import extends ipsCommand
{

        private $_isFBUser = false;
        private $facebook;
        private $_userToken;
        public $extendedPerms = array( 'email', 'read_stream', 'publish_stream' );
        public $settings;
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
            $this->registry = $registry;

            $this->html = $this->registry->output->getTemplate('gallery_facebook');

            if($this->request[ 'approvefacebook' ] == 'yes') {
            
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


                
    		$this->html = $this->registry->output->getTemplate('gallery_facebook');
                    $albumInfo = array();
    		if ( $this->_userToken AND $this->_userId  )
    		{
    			try
    			{
    				$albums = $this->facebook->api('me/albums', array( 'access_token' => $this->_userToken ) );
                                    foreach($albums as $album)
                                    {
                                        foreach($album as $a){
                                            $coverPhoto = $this->facebook->api(array(   'method'       => 'fql.query',
                                                                                        'query'        => 'select src_big from photo where object_id="'.@$a['cover_photo'].'"',
                                                                                        'access_token' => $this->_userToken ) );
                                            
                                            @$a['coverPhotoStr'] = $coverPhoto[0]['src_big'];
                                            @$a[ 'aid' ] = @$a[ 'id' ];
                                            $albumInfo[] = $a;
                                        }
                                    }
    			}
    			catch( Exception $e )
    			{
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

    		$output = $this->html->listPhotos($this->_isFBUser, $albumInfo);
    		$this->registry->getClass('output')->setTitle( 'Importar fotografías de facebook' );
    		$this->registry->getClass('output')->addContent( $output );
    		$this->registry->getClass('output')->sendOutput();
        }
        else
        {
            $output = $this->html->approvefacebook();
            $this->registry->getClass('output')->setTitle( 'Importar fotografías de facebook' );
            $this->registry->getClass('output')->addContent( $output );
            $this->registry->getClass('output')->sendOutput();
        }
	}
}