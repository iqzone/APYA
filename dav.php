<?php

/*
+---------------------------------------------------------------------------
|   IP.Board v3.3.3
|   ========================================
|   by Matthew Mecham
|   (c) 2008 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   Invision Power Board IS NOT FREE SOFTWARE!
+---------------------------------------------------------------------------
|   http://www.invisionpower.com/
|   > $Id: dav.php 9336 2011-08-01 10:17:11Z mmecham $
|   > $Revision: 9336 $
|   > $Date: 2011-08-01 06:17:11 -0400 (Mon, 01 Aug 2011) $
+---------------------------------------------------------------------------
*/
@set_time_limit( 3600 );

define( 'IPB_THIS_SCRIPT', 'admin' );

/* Some servers don't have mb_detect_encoding */
if ( ! function_exists('mb_detect_encoding') )
{
	function mb_detect_encoding()
	{
		return false;
	}
}

/**
* Main public executable wrapper.
*
* Set-up and load module to run
*
* @package	IP.Board
* @author   Matt Mecham
* @version	3.0
*/

require_once( './initdata.php' );/*noLibHook*/

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/

$reg = ipsRegistry::instance();
$reg->init();

$moo = new ipsDav( $reg );

exit();

class ipsDav
{
	function __construct( ipsRegistry $registry )
	{
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	
		/* Set require path to include sabre directory */
		@set_include_path( IPS_KERNEL_PATH . 'sabre/' );/*noLibHook*/
		
		ipsRegistry::$settings['use_friendly_urls'] = 0;
		
		/* Fetch authentication library */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
		$login = new $classToLoad( $registry );
		
		/* Require spl for sabre */
	    require_once('Sabre.autoload.php');/*noLibHook*/
	 
	   	/* Attempt authentication */ 
	    $auth = new Sabre_HTTP_BasicAuth();
	    $auth->setRealm("IP.Board WebDav");
	    
	    /* Enabled? */
	    if ( ! $this->settings['webdav_on'] )
	    {
	    	$auth->requireLogin();
	 		echo "Please visit your Admin CP - Look and Feel - Externally Edit Templates and CSS to enable this functionality";
	 		exit();
	 	}
	 	
	    /* Fetch details */
	    $authDetails = $auth->getUserPass();
	
	 	/* Check auth */
	 	$member = IPSMember::load( IPSText::parseCleanValue( $authDetails[0] ), 'all', 'username' );
	 	
	 	if ( ! $member['member_id'] )
	 	{
	 		$auth->requireLogin();
	 		print "Authentication Required (User doesn't exist)";
	 		exit();
	 	}
	 	
	 	/* Internal auth only */
	 	$result = IPSMember::authenticateMember( $member['member_id'], md5( IPSText::parseCleanValue( $authDetails[1] ) ) );
	 	
	 	if ( $result === false )
	 	{
	 		$auth->requireLogin();
	 		print "Authentication Required (Username or password incorrect)";
	 		exit();
		}
		
		if ( ! $member['g_access_cp'] )
		{
			$auth->requireLogin();
	 		print "Authentication Required (You are not an admin)";
	 		exit();
		}
	 	
	 	/* Require some files for our sabre implementation */
	 	require_once( IPS_ROOT_PATH . 'sources/classes/sabre/root/skins.php' );/*noLibHook*/
	 	require_once( IPS_ROOT_PATH . 'sources/classes/sabre/directory/templates.php' );/*noLibHook*/
	 	require_once( IPS_ROOT_PATH . 'sources/classes/sabre/directory/groups.php' );/*noLibHook*/
	 	require_once( IPS_ROOT_PATH . 'sources/classes/sabre/files/templates.php' );/*noLibHook*/
	    require_once( IPS_ROOT_PATH . 'sources/classes/sabre/lock/nolocks.php' );/*noLibHook*/
	 
	    $tree = new Sabre_DAV_ObjectTree( new sabre_root_skins() );
	
	    $server = new Sabre_DAV_Server($tree);
	 	$server->setBaseUri( $this->getBaseUrl() . '/');
	 	
	 	//$server->addPlugin( new Sabre_DAV_Browser_Plugin() );
	 	
	    $server->addPlugin( new Sabre_DAV_Locks_Plugin( new sabre_lock_nolocks() ) );
	 
	    /* Process */
	    $server->exec();
	}
	
/** Adapted from Zend **/
/**
     * Set the base URL of the request; i.e., the segment leading to the script name
     *
     * E.g.:
     * - /admin
     * - /myapp
     * - /subdir/index.php
     *
     * Do not use the full URI when providing the base. The following are
     * examples of what not to use:
     * - http://example.com/admin (should be just /admin)
     * - http://example.com/subdir/index.php (should be just /subdir/index.php)
     *
     * If no $baseUrl is provided, attempts to determine the base URL from the
     * environment, using SCRIPT_FILENAME, SCRIPT_NAME, PHP_SELF, and
     * ORIG_SCRIPT_NAME in its determination.
     *
     * @param mixed $baseUrl
     * @return Zend_Controller_Request_Http
     */
    public function getBaseUrl($baseUrl = null)
    {
        if ((null !== $baseUrl) && !is_string($baseUrl)) {
            return $this;
        }

        if ($baseUrl === null) {
            $filename = (isset($_SERVER['SCRIPT_FILENAME'])) ? basename($_SERVER['SCRIPT_FILENAME']) : '';

            if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $filename) {
                $baseUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $filename) {
                $baseUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
                $baseUrl = $_SERVER['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
            } else {
                // Backtrack up the script_filename to find the portion matching
                // php_self
                $path    = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
                $file    = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
                $segs    = explode('/', trim($file, '/'));
                $segs    = array_reverse($segs);
                $index   = 0;
                $last    = count($segs);
                $baseUrl = '';
                do {
                    $seg     = $segs[$index];
                    $baseUrl = '/' . $seg . $baseUrl;
                    ++$index;
                } while (($last > $index) && (false !== ($pos = strpos($path, $baseUrl))) && (0 != $pos));
            }

            // Does the baseUrl have anything in common with the request_uri?
            $requestUri = $this->getRequestUri();

            if (0 === strpos($requestUri, $baseUrl)) {
                // full $baseUrl matches
                $this->_baseUrl = $baseUrl;
                return $this->_baseUrl;
            }

            if (0 === strpos($requestUri, dirname($baseUrl))) {
                // directory portion of $baseUrl matches
                $this->_baseUrl = rtrim(dirname($baseUrl), '/');
                return $this->_baseUrl;
            }

            $truncatedRequestUri = $requestUri;
            if (($pos = strpos($requestUri, '?')) !== false) {
                $truncatedRequestUri = substr($requestUri, 0, $pos);
            }

            $basename = basename($baseUrl);
            if (empty($basename) || !strpos($truncatedRequestUri, $basename)) {
                // no match whatsoever; set it blank
                $this->_baseUrl = '';
                return $this->_baseUrl;
            }

            // If using mod_rewrite or ISAPI_Rewrite strip the script filename
            // out of baseUrl. $pos !== 0 makes sure it is not matching a value
            // from PATH_INFO or QUERY_STRING
            if ((strlen($requestUri) >= strlen($baseUrl))
                && ((false !== ($pos = strpos($requestUri, $baseUrl))) && ($pos !== 0)))
            {
                $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
            }
        }

        $this->_baseUrl = rtrim($baseUrl, '/');
        return $this->_baseUrl;
    }

    /**
     * Set the REQUEST_URI on which the instance operates
     *
     * If no request URI is passed, uses the value in $_SERVER['REQUEST_URI'],
     * $_SERVER['HTTP_X_REWRITE_URL'], or $_SERVER['ORIG_PATH_INFO'] + $_SERVER['QUERY_STRING'].
     *
     * @param string $requestUri
     * @return Zend_Controller_Request_Http
     */
    public function getRequestUri($requestUri = null)
    {
        if ($requestUri === null) {
            if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // check this first so IIS will catch
                $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
            } elseif (
                // IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem)
                isset($_SERVER['IIS_WasUrlRewritten'])
                && $_SERVER['IIS_WasUrlRewritten'] == '1'
                && isset($_SERVER['UNENCODED_URL'])
                && $_SERVER['UNENCODED_URL'] != ''
                ) {
                $requestUri = $_SERVER['UNENCODED_URL'];
            } elseif (isset($_SERVER['REQUEST_URI'])) {
                $requestUri = $_SERVER['REQUEST_URI'];
                // Http proxy reqs setup request uri with scheme and host [and port] + the url path, only use url path
                $schemeAndHttpHost = 'http' . '://' . $_SERVER['HTTP_HOST'];
                if (strpos($requestUri, $schemeAndHttpHost) === 0) {
                    $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
                }
            } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
                $requestUri = $_SERVER['ORIG_PATH_INFO'];
                if (!empty($_SERVER['QUERY_STRING'])) {
                    $requestUri .= '?' . $_SERVER['QUERY_STRING'];
                }
            } else {
                return $this->_requestUri;
            }
        } elseif (!is_string($requestUri)) {
            return $this->_requestUri;
        }

        $this->_requestUri = $requestUri;
        return $this->_requestUri;
    }
}


?>