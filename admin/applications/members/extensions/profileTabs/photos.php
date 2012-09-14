<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.4
 * Reputation Profile Tab
 * Last Updated: $Date: 2012-07-09 12:56:26 -0400 (Mon, 09 Jul 2012) $
 * </pre>
 *
 * @author 		$Author: AndyMillne $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		4th January 2012
 * @version		$Revision: 11047 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class profile_photos extends profile_plugin_parent
{
        private $_isFBUser = false;
        private $facebook;
        private $_userToken;
        public $extendedPerms = array( 'email', 'read_stream', 'publish_stream' );
        public $settings;

        public function __construct(ipsRegistry $registry) {
            $this->registry = $registry;
            
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
            
            parent::__construct($registry);
        }
	/**
	 * Feturn HTML block
	 *
	 * @param	array		Member information
	 * @return	string		HTML block
	 */
	public function return_html_block( $member=array() ) 
	{	
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
                                                                                    'query'        => 'select src_small from photo where object_id="'.$a['cover_photo'].'"',
                                                                                    'access_token' => $this->_userToken ) );
                                        
                                        $a['coverPhotoStr'] = $coverPhoto[0]['src_small'];
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
		return $this->registry->getClass('output')->getTemplate('profile')->tabImportPhotos( $this->_isFBUser, $albumInfo );
	}

}