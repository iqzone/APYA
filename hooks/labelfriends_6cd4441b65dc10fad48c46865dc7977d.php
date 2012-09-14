<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 10-may-2012 -006  $
 * </pre>
 * @filename            labelfriends.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		PRI
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		10-may-2012
 * @timestamp           17:56:10
 * @version		$Rev:  $
 *
 */

/**
 * Description of labelfriends
 *
 * @author juliobarreraa@gmail.com
 */
class labelfriends {
    //Protected
    protected $registry;
    //Public
    public $lang;
    
    public function __construct() {
        $this->registry = ipsRegistry::instance();
        $this->lang = $this->registry->getClass('class_localization'); //Load language
        /* Gallery Object */
        $classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . '/applications_addon/ips/gallery/sources/classes/gallery.php', 'ipsGallery' );
        $this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
    }
    
    public function getOutput() {        
        $this->lang->loadLanguageFile(array('public_portal'), 'portal');

        /* Configure form elements */
        $sessionKey = $this->registry->gallery->helper('upload')->generateSessionKey();
        $stats      = $this->registry->gallery->helper('upload')->fetchStats();

        $album = $this->registry->gallery->helper('albums')->fetchAlbum( intval(1) );

        /* Wipe album if we can't actually upload directly inside */
        if ( ! $this->registry->gallery->helper('albums')->isUploadable( $album ) )
        {
                $album = array( '_parent_id' => 1 );
        }

        /* Output */
        $allowed_file_extensions = implode( ', ', array_merge( $this->registry->gallery->helper('image')->allowedExtensions(), ( $this->memberData['g_movies'] ? $this->registry->gallery->helper('media')->allowedExtensions() : array() ) ) );
        $upload = $this->registry->output->getTemplate('portal')->uploadForm( $sessionKey, $album, $stats, $allowed_file_extensions );
        
        return $this->registry->output->getTemplate('portal')->poststatus($upload, $sessionKey);
    }
}

?>
