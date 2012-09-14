<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Public output methods
 * Last Updated: $Date: 2012-06-07 14:04:18 -0400 (Thu, 07 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		Who knows...
 * @version		$Revision: 10891 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class output
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	public
	 * @var		object
	 */
	public $registry;
	public $DB;
	public $settings;
	public $request;
	public $lang;
	public $member;
	public $cache;
	/**#@-*/
	
	/**
	 * SEO templates
	 *
	 * @access	public
	 * @var		array
	 */
	public $seoTemplates		= array();
	
	/**
	 * URLs array
	 *
	 * @access	public
	 * @var		array
	 */
	public $urls				= array();
	
	/**
	 * Compiled templates
	 *
	 * @access	public
	 * @var		array
	 */
	public $compiled_templates	= array();
	
	/**
	 * Loaded templates
	 *
	 * @access	public
	 * @var		array
	 */
    public $loaded_templates	= array();
	
	/**
	 * HTML variable
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_html			= '';
	
	/**
	 * Page title
	 *
	 * @access	protected
	 * @var		string
	 */	
	protected $_title			= '';
	
	/**
	 * Basic navigation elements
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $__navigation		= array();
	
	/**
	 * Is this an error page?
	 *
	 * @access	protected
	 * @var		bool
	 */
	protected $_isError			= FALSE;
	
	/**
	 * Is this a page we should use SSL for?
	 *
	 * @access	public
	 * @var		bool
	 */
	public $isHTTPS				= FALSE;
	
	/**
	 * Custom navigation elements
	 *
	 * @access	protected
	 * @var		array
	 */
	public $_navigation			= array();
	
	/**
	 * Skin array
	 *
	 * @access	public
	 * @var		array
	 */
	public $skin				= array();
	
	/**
	 * All skins
	 *
	 * @access	public
	 * @var		array
	 */
	public $allSkins = array();
	
	/**
	 * Offline message
	 *
	 * @access	public
	 * @var		string
	 */
	public $offlineMessage = '';
	
	/**
	 * Add content to the document <head>
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_documentHeadItems = array();
	
	/**
	 * Holds the JS modules to be loaded
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_jsLoader = array();
	
	/**
	 * CSS array to be passed to main library
	 *
	 * @access	protected
	 * @var 	array
	 */
	protected $_css = array( 'import' => array(), 'inline' => array() );	
	
	/**
	 * Do not load skin_global
	 *
	 * @access	protected
	 * @var		boolean
	 */
	protected $_noLoadGlobal = FALSE;
	
	/**
	 * Maintain an array of seen template bits to prevent
	 * infinite recursion when dealing with parse template tags
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_seenTemplates = array();
	
	/**
	 * Output format class
	 *
	 * @access	public
	 * @var		object
	 */
	public $outputFormatClass;

	/**
	 * Skin functions class, if needed
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $_skinFunctions;

	/**
	 * Are we using safe mode?
	 *
	 * @access	protected
	 * @var		bool
	 */
	protected $_usingSafeModeSkins = FALSE;
	
	/**
	 * Root doc URL (no pagination)
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_rootDocUrl;
	
	/**
	 * is mobile skin flag
	 *
	 * @access	protected
	 * @var		boolean
	 */
	protected $_isMobileSkin = false;
	
	/**
	* FURL cache storage.
	*
	* @access	protected
	* @var		boolean
	*/
	protected static $furlCache = array();
	
	/**
	* If you're generating a lot of FURLs, the cache will eventually slow you down beyond 
	*
	* @access	protected
	* @var		boolean
	*/
	protected static $furlCacheEnabled = true;
	
	/**
	 * Interally cache the results of isLargeTouchDevice() and isSmallTouchDevice()
	 * @var boolean
	 */
	protected $_isLargeTouchDevice = null;
	protected $_isSmallTouchDevice = null;
	
	/**
	 * Fetch skin generator session
	 * @var boolean
	 */
	public $skinGenSession = false;
	
	/**
	 * Anti-cache hash for CSS/JS
	 * @var string
	 */
	public $antiCacheHash;
	
	protected $_paginationProcessedData = array();
	
	/**
	 * Trap skin calls that could have incorrect names
	 *
	 * @access	public
	 * @param	string
	 * @param	mixed		void, or an array of arguments
	 * @return	mixed		string, or an error
	 */
	public function __call( $funcName, $args )
	{
		/* Output format stuff.. */
		switch ( $funcName )
		{
			case 'storeRootDocUrl':
				$this->_rootDocUrl = $args[0];
				return true;
			break;
			case 'fetchRootDocUrl':
				return $this->_rootDocUrl;
			break;
			case 'setCacheExpirationSeconds':
				if ( is_object( $this->outputFormatClass ) )
				{
					return $this->outputFormatClass->$funcName( $args[0] );
				}
			break;
			case 'setHeaderCode':
				if ( is_object( $this->outputFormatClass ) )
				{
					return $this->outputFormatClass->$funcName( $args[0], $args[1] );
				}
			break;
			case 'addMetaTag':
				if ( is_object( $this->outputFormatClass ) )
				{
					if ( isset( $args[3] ) )
					{
						return $this->outputFormatClass->$funcName( $args[0], $args[1], (boolean)$args[2], (integer)$args[3] );
					}
					else
					{
						return $this->outputFormatClass->$funcName( $args[0], $args[1], (boolean)$args[2] );
					}
				}
			break;
			case 'getMetaTags':
				if ( is_object( $this->outputFormatClass ) )
				{
					return $this->outputFormatClass->$funcName( $args[0] );
				}
			break;
			case 'encodeMetaTagContent':
				if ( is_object( $this->outputFormatClass ) )
				{
					return $this->outputFormatClass->$funcName( $args[0] );
				}
			break;
			case 'addCanonicalTag':
				if ( is_object( $this->outputFormatClass ) )
				{
					return $this->outputFormatClass->$funcName( $args[0], $args[1], $args[2] );
				}
			break;
			case 'getCanonicalUrl':
				if ( is_object( $this->outputFormatClass ) )
				{
					return $this->outputFormatClass->$funcName();
				}
			case 'forceDownload':
				if ( is_object( $this->outputFormatClass ) )
				{
					return $this->outputFormatClass->$funcName( $args[0] );
				}
			case 'parseIPSTags':
				if ( is_object( $this->outputFormatClass ) )
				{
					return $this->outputFormatClass->$funcName( $args[0] );
				}
			break;
		}
	}
	
	/**
	 * Returns the processed pagination data
	 */
	public function getPaginationProcessedData()
	{
		return $this->_paginationProcessedData;
	}
	
	/**
	 * Flag this skin as mobile
	 *
	 * @access	public
	 * @param	boolean
	 */
	public function setAsMobileSkin( $bool )
	{
		$this->_isMobileSkin = ( $bool ) ? true : false;
	}
	
	/**
	 * Flag this skin as mobile
	 *
	 * @access	public
	 * @param	boolean
	 */
	public function getAsMobileSkin()
	{
		return ( $this->_isMobileSkin ) ? true : false;
	}
	
	/**
	 * Is this a large touch device? ...
	 * Future expansion
	 */
	public function isLargeTouchDevice()
	{
		if ( $this->_isLargeTouchDevice === null )
		{
			$this->_isLargeTouchDevice = false;
			
			if ( $this->memberData['userAgentKey'] == 'transformer' )
			{
				$this->_isLargeTouchDevice = true;
			}
			else if ( $this->memberData['userAgentKey'] == 'iPad' )
			{
				$this->_isLargeTouchDevice = true;
			}
		}
		
		return $this->_isLargeTouchDevice;
	}
	
	/**
	 * Is this a small touch device? ...
	 * Future expansion
	 */
	public function isSmallTouchDevice()
	{
		if ( $this->_isSmallTouchDevice === null )
		{
			$this->_isSmallTouchDevice = false;
			
			if ( $this->memberData['userAgentKey'] == 'iphone' )
			{
				$this->_isSmallTouchDevice = true;
			}
			else if ( $this->memberData['userAgentKey'] == 'ipodtouch' )
			{
				$this->_isSmallTouchDevice = true;
			}
			else if ( $this->memberData['userAgentKey'] == 'android' )
			{
				$this->_isSmallTouchDevice = true;
			}
			else if ( $this->memberData['userAgentKey'] == 'operamini' )
			{
				$this->_isSmallTouchDevice = true;
			}
		}
		
		return $this->_isSmallTouchDevice;
	}
	
	/**
	 * Is this a ___ touch device? ...
	 * Future expansion
	 */
	public function isTouchDevice()
	{
		return ( $this->isLargeTouchDevice() || $this->isSmallTouchDevice() ) ? true : false;
	}
	
   	/**
	 * Construct
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @param	bool		Whether to init or not
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $initialize=FALSE )
	{
		/* Make object */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Safe mode skins... */
		$this->_usingSafeModeSkins = ( ( $this->settings['safe_mode_skins'] == 0 AND $this->settings['safe_mode'] == 0 ) OR IN_DEV ) ? FALSE : TRUE;
	
		if ( $initialize === TRUE )
		{
			//-----------------------------------------
	    	// INIT
	    	//-----------------------------------------
	    	
			$_outputFormat    = 'html';
			$_outputClassName = 'htmlOutput';
			
			$this->allSkins = $this->_fetchAllSkins();
			$skinSetID      = $this->_fetchUserSkin();
			$this->skin     = $this->allSkins[ $skinSetID ];
			
			/* Does it need a recache? */
			if ( $this->skin['set_updated'] == -2 )
			{
				/* Flag skins for recache */
				require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
				require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
				$skinCaching	= new skinCaching( $this->registry );
				
				/* Set it as caching */
				$skinCaching->flagSetAsRecaching( $skinSetID );
				
				/* Just recache this one skin set */
				$skinCaching->setIgnoreChildrenWhenRecaching( true );
				$skinCaching->rebuildPHPTemplates( $skinSetID );
				$skinCaching->rebuildCSS( $skinSetID );
				
				IPSDebug::addMessage( "Recached skin set: " . $skinSetID );
			}

			//-----------------------------------------
			// Get the skin caches
			//-----------------------------------------
   	
			$skinCaches = $this->cache->getWithCacheLib( 'Skin_Store_' . $skinSetID );
	
			if ( ! is_array($skinCaches) OR ! count($skinCaches) )
			{
				$_grab = "'css', 'replacements'";
				
				$this->DB->build( array( 'select' => '*',
										 'from'   => 'skin_cache',
										 'where'  => "cache_set_id=" . $skinSetID . " AND cache_type IN (" . $_grab . ")" ) );
				$this->DB->execute();
			
				while( $row = $this->DB->fetch() )
				{
					$skinCaches[ $row['cache_value_2'] . '.' . $row['cache_id'] ] = $row;
				}
				
				/* Put skin cache back if needed */
				$this->cache->putWithCacheLib( 'Skin_Store_' . $skinSetID, $skinCaches, 86400 );
			}
			
			/* Avoid SQL filesort */
			ksort( $skinCaches );
			
			/* Loop and build */
			foreach( $skinCaches as $row )
			{
				switch( $row['cache_type'] )
				{
					default:
					break;
					case 'css':
						$appDir  = '';
						$appHide = 0;
						if ( strstr( $row['cache_value_4'], '-' ) )
						{
							list( $appDir, $appHide ) = explode( '-', $row['cache_value_4'] );
							
							if ( ( $appDir ) AND $appDir != IPS_APP_COMPONENT AND $appHide )
							{
								continue;
							}
							/* @link http://community.invisionpower.com/tracker/issue-32175-disabled-app-css */
							else if( $appDir and !IPSLib::appIsInstalled( $appDir ) )
							{
								continue;
							}
						}
						
						/* Tied to specific modules within the app? */
						if ( $row['cache_value_6'] AND $this->request['module'] )
						{
							if ( ! in_array( $this->request['module'], explode( ',', str_replace( ' ', '', $row['cache_value_6'] ) ) ) )
							{
								continue;
							}
						}
					
						$skinCaches['css'][ $row['cache_value_1'] ] = array( 'content' => $row['cache_content'], 'attributes' => $row['cache_value_5'] );
					break;
					case 'replacements':
						$skinCaches['replacements'] = $row['cache_content'];
					break;
				}
			}
				
			$this->skin['_css']          = is_array( $skinCaches['css'] ) ? $skinCaches['css'] : array();
	    	$this->skin['_replacements'] = unserialize($skinCaches['replacements']);
	    	$this->skin['_skincacheid']  = $this->skin['set_id'];
			$this->skin['_csscacheid']   = 'css_' . $this->skin['set_id'];
			
			// Set a hash we can use to prevent client caching of CSS/JS
			$this->antiCacheHash = md5( IPB_VERSION . $this->settings['board_url'] . md5( $this->settings['sql_tbl_prefix'] . $this->settings['sql_pass'] ) );

			/* IN_DEV Stuff */
	    	if ( IN_DEV )
	    	{
				$this->skin['_css'] = array();
				
				if ( is_file( DOC_IPS_ROOT_PATH . 'cache/skin_cache/masterMap.php' ) )
				{
					$REMAP = $this->buildRemapData();
					
					$_setId = intval( $REMAP['inDevDefault'] );
					$_dir   = $REMAP['templates'][ $REMAP['inDevDefault'] ];
					$_cdir  = $REMAP['css'][ $REMAP['inDevDefault'] ];
					
					/* Reset master dir */
					$this->skin['set_image_dir'] = $REMAP['images'][ $REMAP['inDevDefault'] ];
					$this->skin['set_key']       = $REMAP['inDevDefault'];
				}
				else
				{
					$_setId = 0;
					$_dir   = 'master_skin';
					$_cdir  = 'master_css';
				}
				
				/* Using a custom master skin */
				if ( $_setId )
				{
					$this->skin = $this->allSkins[ $_setId ];
					
					$this->skin['_replacements'] = unserialize( $skinCaches['replacements'] );
				}
				
				/* Sort out CSS */
				if ( ! isset( $this->_skinFunctions ) || ! is_object( $this->_skinFunctions ) )
				{
					require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
					require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/

					$this->_skinFunctions = new skinCaching( $this->registry );
				}
				
				$css = $this->_skinFunctions->fetchDirectoryCSS( $_cdir );
				$tmp = array();
				$ord = array();
				
				foreach( $css as $name => $data )
				{				
					/* Tied to app? */
					if ( ( $data['css_app'] ) AND $data['css_app'] != IPS_APP_COMPONENT AND $data['css_app_hide'] )
					{
						continue;
					}
				
					/* Tied to specific modules within the app? */
					if ( $data['css_modules'] AND ( ! in_array( $this->request['module'], explode( ',', str_replace( ' ', '', $data['css_modules'] ) ) ) ) )
					{
						continue;
					}
					
					$tmp[ $data['css_position'] . '.' . $data['css_group'] ][ $name ] = array( 'content' => $data['css_content'], 'attributes' => $data['css_attributes'] );
				}
				
				ksort( $tmp );
				
				foreach( $tmp as $blah => $data )
				{
					foreach( $data as $name => $data )
					{
						$ord[ $blah ] = array( 'css_group' => $name, 'css_position' => 1 );
						$this->skin['_css'][ $name ] = $data;
					}
				}
				
				/* Other data */
				$this->skin['_cssGroupsArray'] = $ord;
				$this->skin['_skincacheid']    = is_dir( IPS_CACHE_PATH . 'cache/skin_cache/' . $_dir ) ? $_setId : $this->skin['set_id'];
				$this->skin['_csscacheid']     = $_cdir;
				$this->skin['set_css_inline']  = ( is_dir( IPS_PUBLIC_PATH . 'style_css/' . $_cdir ) ) ? 1 : 0;
				
				if ( is_file( IPS_CACHE_PATH . 'cache/skin_cache/' . $_dir . '/_replacements.inc' ) )
				{
					$replacements = array();
					include_once( IPS_CACHE_PATH . 'cache/skin_cache/' . $_dir . '/_replacements.inc' );/*noLibHook*/
					
					$this->skin['_replacements'] = $replacements;
				}
	    	}
			
			/* Is this a mobile skin? */
			if ( $this->skin['set_key'] == 'mobile' )
			{
				$this->setAsMobileSkin( true );
			}	
			
			//-----------------------------------------
			// Which output engine?
			//-----------------------------------------
			
			if ( $this->skin['set_output_format'] )
			{
				if ( file_exists( IPS_ROOT_PATH . 'sources/classes/output/formats/' . $this->skin['set_output_format'] ) )
				{
					$_outputFormat    = $this->skin['set_output_format'];
					$_outputClassName = $this->skin['set_output_format'] . 'Output';
				}
			}
		
			require_once( IPS_ROOT_PATH . 'sources/classes/output/formats/coreOutput.php' );/*noLibHook*/
			$outputClassToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/output/formats/' . $_outputFormat. '/' . $_outputClassName. '.php', $_outputClassName );
			
			$this->outputFormatClass = new $outputClassToLoad( $this );
			
			/* Build URLs */
			$this->_buildUrls();
			
			/* Special set up for mobile skin */
			if ( $this->getAsMobileSkin() === true )
			{
				$this->_mobileSkinSetUp();
			}
		}
	}
	
	/**
	 * Reload skin set data
	 * Some applications need to ensure they get 'fresh' skin data not just the data loaded during INIT
	 *
	 * @access public
	 */
	public function reloadSkinData()
	{
		/* Whack the cache */
		$this->caches['skinsets'] = array();
		
		$this->allSkins = $this->_fetchAllSkins();
		$skinSetID      = $this->_fetchUserSkin();
		$this->skin     = $this->allSkins[ $skinSetID ];
	}
	
	/**
	 * Build URLs
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _buildUrls()
	{
		//-----------------------------------------
		// Should we use HTTPS on this page?
		//-----------------------------------------
		
		$this->_setHTTPS();
		
		//-----------------------------------------
		// Board URLs and such
		//-----------------------------------------

		$this->settings['board_url']		= $this->settings['base_url'];
		$this->settings['js_main']		    = $this->settings['base_url'] . '/' . CP_DIRECTORY . '/js/';

		$this->settings['public_dir']		= $this->settings['base_url'] . '/' . PUBLIC_DIRECTORY . '/';
		$this->settings['cache_dir']		= $this->settings['ipb_cache_url'] ? $this->settings['ipb_cache_url'] . '/cache/' : $this->settings['base_url'] . '/cache/';

		$this->settings['base_url']		    = $this->settings['base_url'] .'/'.IPS_PUBLIC_SCRIPT.'?';
		$this->settings['base_url_ns']	    = $this->settings['base_url'] .'/'.IPS_PUBLIC_SCRIPT.'?';
		
		if ( $this->member->session_type != 'cookie' AND !$this->member->is_not_human )
		{
			$this->settings['base_url']	.= 's='.$this->member->session_id.'&amp;';
		}

		/* Create new URL */
		$this->settings['base_url_with_app'] = $this->settings['base_url'] . 'app=' . IPS_APP_COMPONENT . '&amp;';

		$this->settings['js_base']		    = $this->settings['_original_base_url'].'/index.'.$this->settings['php_ext'].'?s='.$this->member->session_id.'&';

		$this->settings['img_url']		    = $this->settings['ipb_img_url'] ? $this->settings['ipb_img_url'] . '/' . PUBLIC_DIRECTORY . '/style_images/' . $this->skin['set_image_dir'] : $this->settings['_original_base_url'] . '/' . PUBLIC_DIRECTORY . '/style_images/' . $this->skin['set_image_dir'];
		$this->settings['img_url_no_dir']	= $this->settings['ipb_img_url'] ? $this->settings['ipb_img_url'] . '/' . PUBLIC_DIRECTORY . '/style_images/' : $this->settings['_original_base_url'] . '/' . PUBLIC_DIRECTORY . '/style_images/';
		$this->settings['public_cdn_url']	= rtrim( $this->settings['ipb_img_url'] ? $this->settings['ipb_img_url'] : $this->settings['_original_base_url'], '/' ) . '/' . PUBLIC_DIRECTORY . '/';
		$this->settings['css_base_url']	    = rtrim( $this->settings['ipb_css_url'] ? $this->settings['ipb_css_url'] : $this->settings['_original_base_url'], '/' ) . '/' . PUBLIC_DIRECTORY . '/';
		$this->settings['js_base_url']	    = rtrim( $this->settings['ipb_js_url']  ? $this->settings['ipb_js_url']  : $this->settings['_original_base_url'], '/' ) . '/' . PUBLIC_DIRECTORY . '/';
		$this->settings['emoticons_url']  = $this->settings['ipb_img_url'] ? $this->settings['ipb_img_url'] . '/' . PUBLIC_DIRECTORY . '/style_emoticons/<#EMO_DIR#>' : $this->settings['_original_base_url'] . '/' . PUBLIC_DIRECTORY . '/style_emoticons/<#EMO_DIR#>';
		$this->settings['mime_img']       = $this->settings['ipb_img_url'] ? $this->settings['ipb_img_url'] . '/' . PUBLIC_DIRECTORY : $this->settings['_original_base_url'] . '/' . PUBLIC_DIRECTORY;
		
		/* HTTPS fixes */
		if( $this->isHTTPS )
		{
			$this->enableHTTPS();
		}
	}
	
	/**
	 * Enable HTTPS
	 *
	 * @return	@e void
	 */
	public function enableHTTPS()
	{
		$this->isHTTPS = TRUE;
		$this->settings['board_url_https']	= str_replace( 'http://', 'https://', $this->settings['board_url'] );
		$this->settings['base_url_https']	= str_replace( 'http://', 'https://', $this->settings['base_url'] );
		$this->settings['public_dir']		= str_replace( 'http://', 'https://', $this->settings['public_dir'] );
		$this->settings['cache_dir']		= str_replace( 'http://', 'https://', $this->settings['cache_dir'] );
		$this->settings['img_url']			= str_replace( 'http://', 'https://', $this->settings['img_url'] );
		$this->settings['css_base_url']		= str_replace( 'http://', 'https://', $this->settings['css_base_url'] );
		$this->settings['js_base_url']		= str_replace( 'http://', 'https://', $this->settings['js_base_url'] );
		$this->settings['img_url_no_dir']	= str_replace( 'http://', 'https://', $this->settings['img_url_no_dir'] );
		$this->settings['upload_url']		= str_replace( 'http://', 'https://', $this->settings['upload_url'] );
		$this->settings['fbc_xdlocation']	= str_replace( 'http://', 'https://', str_replace( 'xd_receiver.php', 'xd_receiver_ssl.php', $this->settings['fbc_xdlocation'] ) );
		$this->settings['emoticons_url']	= str_replace( 'http://', 'https://', $this->settings['emoticons_url'] );
		$this->settings['mime_img']			= str_replace( 'http://', 'https://', $this->settings['mime_img'] );
	}
	
	/**
	 * Any set up for the mobile skin
	 *
	 * @access	private
	 */
	private function _mobileSkinSetUp()
	{
		/* Ensure thumbnails are small */
		$this->settings['siu_width']  = 100;
		$this->settings['siu_height'] = 100;
	}
	
	/**
	 * Sets the isHTTPS class variable
	 *
	 * @access	private
	 * @return	@e void
	 * @todo 	[Future] Explore moving the https section definitions to app coreVariables.php
	 */
	private function _setHTTPS()
	{
		$this->isHTTPS = false;
		
		if ( !defined( 'SSL_PORT' ) )
		{
			define( 'SSL_PORT', 443 );
		}
				
		if ( $_SERVER['SERVER_PORT'] == SSL_PORT )
		{
			$this->isHTTPS = true;
			return;
		}
		
		if( $this->settings['logins_over_https'] && ( in_array( ipsRegistry::$request['section'], array( 'login', 'lostpass', 'register') ) || ipsRegistry::$request['module'] == 'usercp') )
		{
			/* Configure where we want HTTPS */
			$sectionsForHttps	= array(
										'core'	=> array(
														'global'	=> array(
																			'login'		=> array(),
																			'register'	=> array(),
																			'lostpass'	=> array(),
																			),
														'usercp'	=> array(
																			'core'	=> array( 'email', 'password', 'displayname' ),
																			),
														),
										);

			foreach( $sectionsForHttps as $app => $modules )
			{
				if( $app == ipsRegistry::$request['app'] )
				{
					foreach( $modules as $module => $sections )
					{
						if( $module == ipsRegistry::$request['module'] )
						{
							foreach( $sections as $section => $areas )
							{
								//-----------------------------------------
								// User cp is "special"
								//-----------------------------------------
								
								if( $module == 'usercp' )
								{
									if( ipsRegistry::$request['tab'] == $section )
									{
										foreach( $areas as $area )
										{
											if( ipsRegistry::$request['area'] == $area )
											{
												$this->isHTTPS	= true;
												break 4;
											}
										}
									}
								}
								else
								{
									if( ipsRegistry::$request['section'] == $section )
									{
										$this->isHTTPS	= true;
										break 3;
									}
								}
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * Return all skin sets from the cache and expand them
	 *
	 * @access	protected
	 * @return	Array if skin (array [id] => data
	 */
	protected function _fetchAllSkins()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$gatewayFile = '';
		
		//-----------------------------------------
		// Check skin caches
		//-----------------------------------------

		if ( ! is_array( $this->caches['skinsets'] ) OR ! count( $this->caches['skinsets'] ) )
		{
			$this->cache->rebuildCache( 'skinsets', 'global' );
		}
		
		//-----------------------------------------
		// Did we come in via a gateway file?
		//-----------------------------------------
	
		foreach( $this->caches['outputformats'] as $key => $conf )
		{
			if ( $conf['gateway_file'] == IPS_PUBLIC_SCRIPT )
			{
				IPSDebug::addMessage( "Gateway file confirmed: " . $key );
				
				$gatewayFile = $key;
				break;
			}
		}
		
		if( !$gatewayFile )
		{
			$gatewayFile	= 'html';
		}
		
		//-----------------------------------------
		// Get 'em
		//-----------------------------------------
		
		$_skinSets = $this->caches['skinsets'];

		if ( is_array( $_skinSets ) )
		{
			foreach( $_skinSets as $id => $data )
			{
				$_skinSets[ $id ]['_parentTree']      = unserialize( $_skinSets[ $id ]['set_parent_array'] );
				$_skinSets[ $id ]['_childTree']       = unserialize( $_skinSets[ $id ]['set_child_array'] );
				$_skinSets[ $id ]['_userAgents']      = unserialize( $_skinSets[ $id ]['set_locked_uagent'] );
				$_skinSets[ $id ]['_cssGroupsArray']  = unserialize( $_skinSets[ $id ]['set_css_groups'] );
				$_skinSets[ $id ]['_youCanUse']       = FALSE;
				$_skinSets[ $id ]['_gatewayExclude']  = FALSE;
			  
				/* Can we see it? */
				if ( $_skinSets[ $id ]['set_permissions'] == '*' )
				{
					$_skinSets[ $id ]['_youCanUse'] = TRUE;
				}
				else if ( $_skinSets[ $id ]['set_permissions'] )
				{
					$_perms = explode( ',', $_skinSets[ $id ]['set_permissions'] );
				
					if ( isset($this->memberData['member_group_id']) AND in_array( $this->memberData['member_group_id'], $_perms ) )
					{
						$_skinSets[ $id ]['_youCanUse'] = TRUE;
					}
					else if ( !empty($this->memberData['mgroup_others']) )
					{
						$_others = explode( ',', $this->memberData['mgroup_others'] );
					
						if ( count( array_intersect( $_others, $_perms ) ) )
						{
							$_skinSets[ $id ]['_youCanUse'] = TRUE;
						}
					}
				}
				
				/* Limit to output format? */
				if ( $gatewayFile AND ! IN_ACP )
				{
					if ( $_skinSets[ $id ]['set_output_format'] != $gatewayFile )
					{
						$_skinSets[ $id ]['_youCanUse']      = FALSE;
						$_skinSets[ $id ]['_gatewayExclude'] = TRUE;
					}
				}
			
				/* Array groups */
				if ( is_array( $_skinSets[ $id ]['_cssGroupsArray'] ) )
				{
					ksort( $_skinSets[ $id ]['_cssGroupsArray'], SORT_NUMERIC );
				}
				else
				{
					$_skinSets[ $id ]['_cssGroupsArray'] = array();
				}
			}
		}

		return $_skinSets;
	}
	
	/**
	 * Fetch a skin based on user's incoming data (user-agent, URL) or via other params
	 *
	 * The priority chain goes like this:
	 *
	 * Incoming Gateway file (index.php / xml.php / rss.php, etc) filters out some skins, then:
	 * - User Agent
	 * - URL Remap
	 * - App Specific
	 * - Member specific
	 * - Default skin
	 *
	 * @access	protected
	 * @return	int			ID of skin to use
	 */
	protected function _fetchUserSkin()
	{	
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$useSkinID = FALSE;
		
		/* Using the skin generator */
		if ( $this->memberData['g_access_cp'] && $this->memberData['bw_using_skin_gen'] )
		{
			/* Flag skins for recache */
			require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
			require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
			require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinGenerator.php' );/*noLibHook*/
			$skinGenerator	= new skinGenerator( $this->registry );
			
			$this->skinGenSession = $skinGenerator->getUserSession( $this->memberData['member_id'] );
			
			if ( $this->skinGenSession !== false )
			{
				/* Switch on live edit mode */
				define( 'IPS_LIVE_EDIT', true );
				
				return $this->skinGenSession['sg_skin_set_id'];
			}
		}
		
		/* Turn this off if required */
		if ( ! defined( 'IPS_LIVE_EDIT') )
		{
			define( 'IPS_LIVE_EDIT', false );
		}
	
		/* Force the full version */
		if ( $this->memberData['userAgentType'] != 'mobileApp' && ( $this->request['forceFullVersion'] || IPSCookie::get('uagent_bypass') ) )
		{
			/* Set cookie */
			IPSCookie::set("uagent_bypass", 1, -1);
				
			/* Got one set by default for this gateway? */
			foreach( $this->allSkins as $data )
			{
				/* Can use with this output format? */
				if ( $data['_gatewayExclude'] !== FALSE )
				{
					continue;
				}
			
				/* Is default for our current gateway? */
				if ( $data['set_is_default'] && $this->caches['outputformats'][ $data['set_output_format'] ]['gateway_file'] == IPS_PUBLIC_SCRIPT )
				{
					return $data['set_id'];
				}
			}
		}
		
		//-----------------------------------------
		// Ok, lets get a skin!
		//-----------------------------------------
		
		foreach( array( '_fetchByMobileApp', '_fetchSkinByUserAgent', '_fetchSkinByURLMap', '_fetchSkinByApp', '_fetchSkinByMemberPrefs', '_fetchSkinByDefault' ) as $function )
		{
			$useSkinID = $this->$function();
			
			if ( $useSkinID !== FALSE )
			{
				break;
			}
		}
		
		//-----------------------------------------
		// Return it...
		//-----------------------------------------

		return $useSkinID;
	}
	
	/**
	 * Attempt to get a skin choice based on mobile app
	 *
	 * @access	private
	 * @return	mixed		INT of a skin, FALSE if no skin found
	 */
	private function _fetchByMobileApp()
	{
		$key = '';
		
		/* Detect the app */
		if ( $this->memberData['userAgentType'] == 'mobileApp' )
		{
			$key = 'xmlskin';
		}
		else if ( $this->memberData['userAgentType'] == 'mobileBot' || $this->memberData['userAgentType'] == 'mobileAppLegacy' )
		{
			$key = 'mobile';
			
			$this->setAsMobileSkin( true );
		}
		else if ( IPSCookie::get("mobileBrowser") == 1 )
		{
			$key = 'mobile';
			
			$this->setAsMobileSkin( true );
		}
		
		if ( $key )
		{
			$useSkinID = false;
			
			foreach( $this->allSkins as $id => $data )
			{
				if ( $data['set_key'] == $key )
				{ 
					$useSkinID = $data['set_id'];
				}
			}
				
			if ( $useSkinID )
			{
				return $useSkinID;
			}
		}
		
		return false;
	}
	
	/**
	 * Attempt to get a skin choice based on user-agent
	 *
	 * @access	private
	 * @return	mixed		INT of a skin, FALSE if no skin found
	 */
	private function _fetchSkinByUserAgent()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$useSkinID = FALSE;
				
		if ( $this->memberData['userAgentKey'] AND ! $this->memberData['userAgentBypass'] )
		{ 
			foreach( $this->allSkins as $id => $data )
			{
				/* Got perms? */
				if ( $data['_youCanUse'] !== TRUE )
				{
					continue;
				}
				
				/* Can use with this output format? */
				if ( $data['_gatewayExclude'] !== FALSE )
				{
					continue;
				}
				
				/* Check user agents first */
				if ( is_array( $data['_userAgents']['uagents'] ) )
				{ 
					foreach( $data['_userAgents']['uagents'] as $_key => $_version )
					{
						if ( $this->memberData['userAgentKey'] == $_key )
						{
							if ( $_version )
							{
								$_versions = explode( ',', $_version );
							
								foreach( $_versions as $_v )
								{
									if ( strstr( $_v, '+' ) )
									{
										if ( $this->memberData['userAgentVersion'] >= intval( $_v ) )
										{
											$useSkinID = $id;
											break 3;
										}
									}
									else if ( strstr( $_v, '-' ) )
									{
										if ( $this->memberData['userAgentVersion'] <= intval( $_v ) )
										{
											$useSkinID = $id;
											break 3;
										}
									}
									else
									{
										if ( $this->memberData['userAgentVersion'] == intval( $_v ) )
										{
											$useSkinID = $id;
											break 3;
										}
									}
								}
							}
							else
							{
								/* We don't care about versions.. */
								$useSkinID = $id;
								break 2;
							}
						}
					}
				}
			
				/* Still here? */
				if ( is_array( $data['_userAgents']['groups'] ) AND $useSkinID === FALSE )
				{ 
					foreach( $data['_userAgents']['groups'] as $groupID )
					{
						$_group = $this->caches['useragentgroups'][ $groupID ];
						$_gData = unserialize( $_group['ugroup_array'] );
						
						if ( is_array( $_gData ) )
						{
							foreach( $_gData as $__key => $__data )
							{
								if ( $this->memberData['userAgentKey'] == $__key )
								{
									if ( $__data['uagent_versions'] )
									{
										$_versions = explode( ',', $__data['uagent_versions'] );
									
										foreach( $_versions as $_v )
										{
											if ( strstr( $_v, '+' ) )
											{
												if ( $this->memberData['userAgentVersion'] >= intval( $_v ) )
												{
													$useSkinID = $id;
													break 4;
												}
											}
											else if ( strstr( $_v, '-' ) )
											{
												if ( $this->memberData['userAgentVersion'] <= intval( $_v ) )
												{
													$useSkinID = $id;
													break 4;
												}
											}
											else
											{
												if ( $this->memberData['userAgentVersion'] == intval( $_v ) )
												{
													$useSkinID = $id;
													break 4;
												}
											}
										}
									}
									else
									{
										/* We don't care about versions.. */
										$useSkinID = $id;
										break 3;
									}
								}
							}
						}
					}
				}
			}
		}
		
		/* Did we automatically get set the mobile skin?
		 * If so, assign cookie
		 */
		
		if ( $this->allSkins[ $useSkinID ]['set_key'] == 'mobile' )
		{
			IPSCookie::set("mobileBrowser", 1, -1);
		}
		
		if ( $useSkinID !== FALSE )
		{
			$this->memberData['userAgentLocked'] = TRUE;
			IPSDebug::addMessage( "Skin set found via user agent. Using set #" . $useSkinID );
		}
		
		return $useSkinID;
	}
	
	/**
	 * Attempt to fetch a skin based on URL remap
	 *
	 * @access	private
	 * @return	mixed		INT skin ID or FALSE if none found
	 */
	private function _fetchSkinByURLMap()
	{
		$useSkinID = FALSE;
		
		//-----------------------------------------
		// Geddit?
		//-----------------------------------------
		
		if ( $this->caches['skin_remap'] and is_array( $this->caches['skin_remap'] ) AND count( $this->caches['skin_remap'] ) )
		{
			foreach( $this->caches['skin_remap'] as $id => $data )
			{
				if ( $data['map_match_type'] == 'exactly' )
				{
					if ( strtolower( $data['map_url'] ) == strtolower( $this->settings['query_string_real'] ) )
					{
						$useSkinID = $data['map_skin_set_id'];
						break;
					}
					
					if ( strtolower( $data['map_url'] ) == strtolower( $this->settings['this_url'] ) )
					{
						$useSkinID = $data['map_skin_set_id'];
						break;
					}
				}
				else if ( $data['map_match_type'] == 'contains' )
				{
					if ( stristr( $this->settings['query_string_real'], $data['map_url'] ) )
					{ 
						$useSkinID = $data['map_skin_set_id'];
						break;
					}
					
					if ( stristr( $this->settings['this_url'], $data['map_url'] ) )
					{ 
						$useSkinID = $data['map_skin_set_id'];
						break;
					}
				}
			}
		}
		
		/* Can use with this output format? */
		if ( $useSkinID !== FALSE )
		{
			if ( $this->allSkins[ $useSkinID ]['_gatewayExclude'] !== FALSE )
			{
				$useSkinID = FALSE;
			}
		}
		
		if ( $useSkinID !== FALSE )
		{
			IPSDebug::addMessage( "Skin set found via URL remap. Using set #" . $useSkinID );
		}
		
		return $useSkinID;
	}
	
	/**
	 * Attempt to fetch a skin based on APPlication
	 *
	 * @access	private
	 * @return	mixed		INT skin ID or FALSE if none found
	 */
	private function _fetchSkinByApp()
	{
		$useSkinID = FALSE;
		$file      = IPSLib::getAppDir( IPS_APP_COMPONENT ) . '/extensions/coreExtensions.php';
		
		if ( is_file( $file ) )
		{
			$classToLoad = IPSLib::loadLibrary( $file, 'fetchSkin__' . IPS_APP_COMPONENT, IPS_APP_COMPONENT );
			
			if ( class_exists( $classToLoad ) )
			{
				$_grabber  = new $classToLoad( $this->registry );
				$_grabber->allSkins = $this->allSkins;
				
				$useSkinID = $_grabber->fetchSkin();
			}
		}
		
		/* Can use with this output format? */
		if ( $useSkinID !== FALSE )
		{
			if ( $this->allSkins[ $useSkinID ]['_gatewayExclude'] !== FALSE )
			{
				$useSkinID = FALSE;
			}
		}
			
		if ( $useSkinID !== FALSE )
		{
			IPSDebug::addMessage( "Skin set found via APP. Using set #" . $useSkinID );
		}

		return $useSkinID;
	}
	
	/**
	 * Attempt to fetch a skin based on member's preferences
	 *
	 * @access	private
	 * @return	mixed		INT skin ID or FALSE if none found
	 */
	private function _fetchSkinByMemberPrefs()
	{
		$useSkinID = ( $this->memberData['member_id'] ) ? intval( $this->memberData['skin'] ) : intval( IPSCookie::get( 'guestSkinChoice' ) );
		
		if( !$useSkinID )
		{
			$useSkinID  = false;
		}
		
		/* Make sure it's legal */
		if ( $useSkinID )
		{
			$_test = $this->allSkins[ $useSkinID ];
			
			if ( $_test['_youCanUse'] !== TRUE )
			{
				$useSkinID = FALSE;
			}
		}
		
		if( ! $useSkinID )
		{
			$useSkinID = FALSE;
		}
			
		if ( $useSkinID !== FALSE )
		{
			IPSDebug::addMessage( "Skin set found via member's preferences. Using set #" . $useSkinID );
		}
		
		return $useSkinID;
	}
    
	/**
	 * Attempt to fetch a skin based on default settings
	 *
	 * @access	private
	 * @return	mixed		INT skin ID or FALSE if none found
	 */
	protected function _fetchSkinByDefault()
	{
		$useSkinID = FALSE;
		
		/* Got one set by default for this gateway? */
		foreach( $this->allSkins as $data )
		{
			/* Can use with this output format? */
			if ( $data['_gatewayExclude'] !== FALSE )
			{
				continue;
			}
			
			/* Is default for our current gateway? */
			if ( $data['set_is_default'] && $this->caches['outputformats'][ $data['set_output_format'] ]['gateway_file'] == IPS_PUBLIC_SCRIPT )
			{
				$useSkinID = $data['set_id'];
				break;
			}
		}
		
		/* Did we get anything? */
		if ( $useSkinID === FALSE )
		{
			foreach( $this->allSkins as $data )
			{
				/* Can use with this output format? */
				if ( $data['_gatewayExclude'] !== FALSE )
				{
					continue;
				}
				
				/* Grab the first HTML one */
				if ( $data['set_output_format'] == 'html' )
				{
					$useSkinID = $data['set_id'];
					break;
				}
			}
		}
		
		IPSDebug::addMessage( "Skin set not found, setting default. Using set #" . $useSkinID );
		
		return $useSkinID;
	}
	
	/**
	 * Returns a template class; loading if required
	 *
	 * @access	public
	 * @param	string	template name
	 * @param	boolean	[Test only, TRUE for yes, FALSE for no]
	 * @return	mixed	Object, or null
	 */
	public function getTemplate( $groupName )
	{
		if ( ! isset( $this->compiled_templates[ 'skin_' . $groupName ] ) || ! is_object( $this->compiled_templates[ 'skin_' . $groupName ] ) )
		{
			//-----------------------------------------
			// Using self:: so that we can load public
			//	skins inside ACP when necessary
			//-----------------------------------------
			
			self::loadTemplate( 'skin_' . $groupName );
		}
		
		return isset( $this->compiled_templates[ 'skin_' . $groupName ] ) ? $this->compiled_templates[ 'skin_' . $groupName ] : NULL;
	}
	
	/**
	 * Returns a replacement (aka macro)
	 *
	 * @access	public
	 * @param	string 		Replacement key
	 * @return	string		Replacement value
	 */
	public function getReplacement( $key )
	{
		if( is_array($this->skin['_replacements']) AND count($this->skin['_replacements']) )
		{
			if ( isset($this->skin['_replacements'][ $key ]) )
			{
				$value = $this->skin['_replacements'] [ $key ];
				
				if ( strstr( $value, '{lang:' ) )
				{
					$value = preg_replace_callback( '#\{lang:([^\}]+?)\}#', create_function( '$key', 'return ipsRegistry::getClass(\'class_localization\')->words[$key[1]];' ), $value );
				}
				else if ( strstr( $value, '{time:' ) )
				{
					$value = preg_replace_callback( '#\{time:([^\}]+?)\}#', create_function( '$key', 'return ipsRegistry::getClass(\'class_localization\')->getDate($key[1], \'LONG\');' ), $value );
				}
				
				/**
				 * Using HTTPS with our link as HTTP and we have a link match?
				 * 
				 * @link	http://community.invisionpower.com/tracker/issue-37285-https-and-easy-logo-changer/
				 */
				if ( $this->isHTTPS && strpos( $this->settings['board_url'], 'http://' ) === 0 && strpos( $value, $this->settings['board_url'] ) !== FALSE )
				{
					$value = str_replace( $this->settings['board_url'], $this->settings['board_url_https'], $value );
				}
				
				return $value;
			}
		}
	}
	
	/**
	 * Load a normal template file from either cached PHP file or
	 * from the DB. Populates $this->compiled_templates[ _template_name_ ]
	 *
	 * @access	public
	 * @param	string	Template name
	 * @param	integer	Template set ID
	 * @return	@e void
	 */
	public function loadTemplate( $name, $id='' )
	{
		//-----------------------------------------
		// Make sure we've not already tried to load
		//-----------------------------------------
		
		static $attempted	= array();
		
		if( in_array( md5( $name . $id ), $attempted ) )
		{
			return;
		}
		
		$attempted[]	= md5( $name . $id );
		
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$tags 	= 1;
		$loaded	= 0;
		
		//-----------------------------------------
		// Select ID
		//-----------------------------------------
		
		if ( ! $id )
		{
			$id = $this->skin['_skincacheid'];
		}
	
		//-----------------------------------------
		// Full name
		//-----------------------------------------
		
		$full_name            = $name.'_'.intval($id);
		$skin_global_name     = 'skin_global_'.$id;
		$new_skin_global_name = '';
		
		$_name            = $name;
		
		//-----------------------------------------
		// Already got this template loaded?
		//-----------------------------------------
	
		if ( !empty( $this->loaded_templates[ $full_name ] ) )
		{
			return;
		}

		//-----------------------------------------
		// Not running safemode skins?
		//-----------------------------------------
		
		if ( $this->_usingSafeModeSkins === FALSE )
		{
			//-----------------------------------------
			// Simply require and return
			//-----------------------------------------
			
			if ( $name != 'skin_global')
			{
				if ( ! ( isset( $this->loaded_templates[ $skin_global_name ] ) && $this->loaded_templates[ $skin_global_name ] ) AND $this->_noLoadGlobal === FALSE )
				{
					//-----------------------------------------
					// Suck in skin global..
					//-----------------------------------------
					
					if ( $this->load_template_from_php( 'skin_global', 'skin_global_'.$id, $id ) )
					{
						$loaded = 1;
					}
					
					//-----------------------------------------
					// Suck in normal file...
					//-----------------------------------------
					
					if ( ! $this->load_template_from_php( $_name, $name.'_'.$id, $id ) )
					{
						$loaded = 0;
					}
				}
				else
				{
					//-----------------------------------------
					// Suck in normal file...
					//-----------------------------------------
					
					if ( $this->load_template_from_php( $_name, $name.'_'.$id, $id ) )
					{
						$loaded = 1;
					}
				}
			}
			else
			{
				if ( $name == 'skin_global' )
				{
					//-----------------------------------------
					// Suck in skin global..
					//-----------------------------------------
					
					if ( $this->load_template_from_php( 'skin_global', 'skin_global_'.$id, $id ) )
					{
						$loaded = 1;
					}
					
					return;
				}
				else
				{
					//-----------------------------------------
					// Suck in normal file...
					//-----------------------------------------
					
					if ( $this->load_template_from_php( $_name, $name.'_'.$id, $id ) )
					{
						$loaded = 1;
					}
				}
			}
		}
		
		//-----------------------------------------
		// safe_mode_skins OR flat file load failed
		//-----------------------------------------
		
		if ( ! $loaded )
		{
			//-----------------------------------------
			// We're using safe mode skins, yippee
			// Load the data from the DB
			//-----------------------------------------
			
			$skin_global = "";
			$other_skin  = "";
			$this->skin['_type'] = 'Database Skins';
			
			if ( $this->loaded_templates[ $skin_global_name ] == "" and $name != 'skin_global'  AND $this->_noLoadGlobal === FALSE )
			{
				//-----------------------------------------
				// Skin global not loaded...
				//-----------------------------------------
				
				$this->DB->build( array( 'select' => '*',
										 'from'   => 'skin_cache',
										 'where'  => "cache_set_id=".$id." AND cache_value_1 IN ('skin_global', '$name')" ) );
									 
				$this->DB->execute();
				
				while ( $r = $this->DB->fetch() )
				{
					if ( $r['cache_value_1'] == 'skin_global' )
					{
						$skin_global = $r['cache_content'];
					}
					else
					{
						$other_skin  = $r['cache_content'];
					}
				}

				if ( empty( $new_skin_global_name ) OR ! class_exists( $new_skin_global_name ) )
				{
					eval($skin_global);
				}
				
				$new_skin_global_name	= $this->_getSkinHooks( 'skin_global', $skin_global_name, $id );
				
				$this->compiled_templates['skin_global'] =  new $new_skin_global_name( $this->registry );
				
				# Add to loaded templates
				$this->loaded_templates[ $skin_global_name ] = $new_skin_global_name;
			}
			else
			{
				//-----------------------------------------
				// Skin global is loaded..
				//-----------------------------------------
				
				if ( $name == 'skin_global' and in_array( $skin_global_name, $this->loaded_templates ) )
				{
					return;
				}
				
				//-----------------------------------------
				// Load the skin, man
				//-----------------------------------------
				
				$template   = $this->DB->buildAndFetch( array( 'select' => '*',
										 					   'from'   => 'skin_cache',
										 					   'where'  => "cache_set_id=".$id." AND cache_value_1='$name'" ) );
									 
				$other_skin = $template['cache_content'];
				
			}
			
			eval($other_skin);
			
			if ( $name == 'skin_global' )
			{
				$new_skin_global_name = $this->_getSkinHooks( 'skin_global', $skin_global_name, $id );
				
				$this->compiled_templates['skin_global']           =  new $new_skin_global_name( $this->registry );
				
				# Add to loaded templates
				$this->loaded_templates[ $skin_global_name ] = $new_skin_global_name;
			}
			else
			{
				$new_full_name = $this->_getSkinHooks( $name, $full_name, $id );

				if( class_exists( $new_full_name ) )
				{
					$this->compiled_templates[ $name ]           =  new $new_full_name( $this->registry );
					
					# Add to loaded templates
					$this->loaded_templates[ $full_name ] = $new_full_name;
				}
			}
		}
	}

    /**
	 * Load the template bit from the PHP file      
	 *
	 * @access	public
	 * @param	string	Name of the PHP file (sans .php)
	 * @param	string	Name of the class
	 * @param	int		Skin ID
	 * @return	boolean
	 */
	public function load_template_from_php( $name='skin_global', $full_name='skin_global_0', $id='root' )
	{
		$_NOW = IPSDebug::getMemoryDebugFlag();
		
		//-----------------------------------------
		// IN_DEV?
		//-----------------------------------------

		if ( IN_DEV )
		{
			//-----------------------------------------
			// Load functions and cache classes
			//-----------------------------------------
			
			if ( ! isset( $this->_skinFunctions ) || ! is_object( $this->_skinFunctions ) )
			{
				require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
				require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/

				$this->_skinFunctions = new skinCaching( $this->registry );
			}
			
			# Load the master skin template
			$this->_skinFunctions->loadMasterSkinTemplate( $name, $id );
		}
		else
		{
			//-----------------------------------------
			// File exist?
			//-----------------------------------------

			if ( ! is_file( IPS_CACHE_PATH.'cache/skin_cache/cacheid_'.$id.'/'.$name.'.php' ) )
			{
				return FALSE;
			}
			
			include_once( IPS_CACHE_PATH.'cache/skin_cache/cacheid_'.$id.'/'.$name.'.php' );/*noLibHook*/
		}
		
		$new_full_name = $this->_getSkinHooks( $name, $full_name, $id );
				
		if( class_exists( $new_full_name ) )
		{
			$this->compiled_templates[ $name ] =  new $new_full_name( $this->registry );
		
			# Add to loaded templates
			$this->loaded_templates[ $full_name ] = $new_full_name;
		}
	
		IPSDebug::setMemoryDebugFlag( "publicOutput: Loaded skin file - $name", $_NOW );
		
		return TRUE;
	}
	
	/**
	 * Builds a URL
	 *
	 * Example: $this->registry->output->buildUrl( 'showtopic=1', 'public' );
	 * Generates: 'http://www.board.com/forums/index.php?showtopic=1'
	 *
	 * @access	public
	 * @param	string		URL bit
	 * @param	string		Type of URL
	 * @param	string		Whether to apply http auth to the URL
	 * @return	string		Formatted URL
	 */
	public function buildUrl( $url, $urlBase='public', $httpauth="false" )
	{
		/* INIT */
		$base = '';
		
		//-----------------------------------------
		// Caching
		//-----------------------------------------
		
		$_md5	= md5( $url . $urlBase . intval($httpauth) );
		$cached	= $this->getCachedFurl($_md5);
		
		if(!is_null($cached))
		{
			return $cached;
		}

		if ( $urlBase )
		{
			switch ( $urlBase )
			{
				default:
				case 'none':
					$base = '';
				break;
				case 'public':
					if ( IN_ACP )
					{
						$base = $this->settings['public_url'];
					}
					else
					{
						$base = $this->settings['base_url'];
					}
				break;
				case 'publicWithApp':
					$base = $this->settings['base_url_with_app'];
				break;
				case 'publicNoSession':
					$base = $this->settings['_original_base_url'].'/index.'.$this->settings['php_ext'] . '?';
				break;
				case 'admin':
					$base = $this->settings['base_url'];
				break;
				case 'public_dir':
					$base = $this->settings['public_dir'];
					
					if( $this->isHTTPS )
					{
						$base = str_replace( 'http://', 'https://', $base );
					}
				break;
				case 'img_url':
					$base = $this->settings['img_url'];
					
					if( $this->isHTTPS )
					{
						$base = str_replace( 'http://', 'https://', $base );
					}
				break;
				case 'emoticons':
					$base = $this->settings['emoticons'];
				break;
				case 'mime':
					$base = $this->settings['mime'];
				break;
				case 'upload':
					$base = $this->settings['upload_url'];
				break;
				case 'https':
					$base = str_replace( 'http://', 'https://', $this->settings['base_url'] );
				break;
				case 'http':
					$base = str_replace( 'https://', 'http://', $this->settings['base_url'] );
				break;
			}
		}
		
		if ( strtolower( $httpauth ) == "true" AND ( $this->settings['http_auth_username'] AND $this->settings['http_auth_password'] ) )
		{
			$_auth_url = $this->settings['http_auth_username'] . ':' . $this->settings['http_auth_password'] . '@';
			
			$base = str_replace( array( 'http://', 'https://' ), array( 'http://' . $_auth_url, 'https://' . $_auth_url ), $base );
		}
		
		if ( $this->settings['logins_over_https'] )
		{
			if ( 
				stripos( $url, 'section=login' ) !== false OR 
				stripos( $url, 'section=register' ) !== false OR 
				stripos( $url, 'section=lostpass' ) !== false OR
				( stripos( $url, 'module=usercp' ) !== false AND stripos( $url, 'tab=core' ) !== false AND ( stripos( $url, 'area=email' ) !== false OR stripos( $url, 'area=displayname' ) !== false ) )
				)
			{
				$base = str_replace( 'http://', 'https://', $base );
			}
		}
		
		$this->setCachedFurl($_md5, $base.$url);

		return $base . $url;
	}
	
	/**
	 * Append session ID to URL
	 *
	 * @access	protected
	 * @param	string		URL
	 * @param	string		Session ID
	 * @return	string		URL with session ID
	 */
	protected function _appendSession( $url, $s )
	{
		/* Session ID? */
		if ( $s )
		{
			if ( ! strstr( $url, '?' ) )
			{
				$url .= '?s=' . $s;
			}
			else
			{
				if ( $this->settings['url_type'] == 'query_string' )
				{
					if ( substr_count( $url, '?' ) == 1 )
					{
						if ( substr_count( $url, '?/' ) == 1 )
						{
							$url .= '?s=' . $s;
						}
						else
						{
							$url .= '&amp;s=' . $s;
						}
					}
					else
					{
						$url .= '&amp;s=' . $s;
					}
				}
				else
				{
					if ( substr_count( $url, '?' ) == 0 )
					{
						$url .= '?s=' . $s;
					}
					else
					{
						$url .= '&amp;s=' . $s;
					}
				}
			}
			
			$url	= str_replace( '?&amp;', '?', $url );
			$url	= str_replace( '?/?', '?', $url );
		}
		
		return $url;
	}
	
	/**
	 * Formats the URL (.htaccess SEO, etc)
	 *
	 * @access	public
	 * @param	string	Raw URL
	 * @param	string	Any special SEO title passed
	 * @param	string	Any special SEO template to use. If none is passed but SEO is enabled, IPB will search all templates for a match
	 * @return	string	Formatted  URL
	 */
	public function formatUrl( $url, $seoTitle='', $seoTemplate='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		if ( ! ipsRegistry::$settings['use_friendly_urls'] )
 		{
 			return $url;
 		}
 		
		$_template		= FALSE;
		
		
		$_md5			= md5($url.$seoTitle.$seoTemplate);
		$_s				= '';
		
		$cached = $this->getCachedFurl($_md5);
		
		if(!is_null($cached))
		{
			return $cached;
		}


		//-----------------------------------------
		// If using URL sessions, fix the URL...  
		//-----------------------------------------
			
		if ( ! IN_ACP AND strstr( $url, 's=' ) )
		{
			preg_match( "/s=([a-zA-Z0-9]{32})(.*?)$/", $url, $matches );

			if( ! empty($matches[2]) )
			{
				$url	= preg_replace( "/s=([a-zA-Z0-9]{32})(&amp;|&)/", '', $url );
				$_s     = $matches[1];
			}
			
			if ( strstr( $url, 's=0' ) )
			{
				$url	= preg_replace( "/s=0(&amp;|&)/", '', $url );
				$_s     = '';
			}
		}
						
		if ( $this->settings['use_friendly_urls'] AND $seoTitle )
		{
			/* SEO Tweak - if default app is forums then don't bother with act=idx nonsense */
			if ( IPS_DEFAULT_APP == 'forums' AND !$this->settings['actidx_override'] )
			{
				if ( stristr( $url, 'act=idx' ) )
				{
					$url = str_ireplace( array( IPS_PUBLIC_SCRIPT . '?act=idx', '?act=idx', 'act=idx' ), '', $url );
				}
			}
			
			if ( $seoTemplate AND isset($this->seoTemplates[ $seoTemplate ]) )
			{
				$_template = $seoTemplate;
			}

			/* Need to search for one - fast? */
			if ( $_template === FALSE )
			{
				/* Search for one, then. Possibly a bit slower than we'd like! */
				foreach( $this->seoTemplates as $key => $data )
				{
					if ( stristr( str_replace( $this->settings['board_url'], '', $url ), $key ) )
					{ 
						$_template = $key;
						break;
					}
				}
			}

			/* Got one to work with? */
			if ( $_template !== FALSE )
			{
				if ( substr( $seoTitle, 0, 2 ) == '%%' AND substr( $seoTitle, -2 ) == '%%' )
				{
					$seoTitle = IPSText::makeSeoTitle( substr( $seoTitle, 2, -2 ) );
				}
				
				/* Do we need to encode? */
				if ( IPS_DOC_CHAR_SET != 'UTF-8' )
				{
					$seoTitle = urlencode( $seoTitle );
				}

				$replace    = str_replace( '#{__title__}', $seoTitle, $this->seoTemplates[ $_template ]['out'][1] );
		
				$url     = preg_replace( $this->seoTemplates[ $_template ]['out'][0], $replace, $url );
				$_anchor = '';
				$__url   = $url;
				
				/* Protect html entities */
				$url = preg_replace( '/&#(\d)/', "~|~\\1", $url );

				if ( strstr( $url, '&' ) )
				{
					$restUrl = substr( $url, strpos( $url, '&' ) );

					$url     = substr( $url, 0, strpos( $url, '&' ) );
				}
				else
				{
					$restUrl = '';
				}

				/* Anchor */
				if ( strstr( $restUrl, '#' ) )
				{
					$_anchor = substr( $restUrl, strpos( $restUrl, '#' ) );
					$restUrl = substr( $restUrl, 0, strpos( $restUrl, '#' ) );
				}

				switch ( $this->settings['url_type'] )
				{
					case 'path_info':
						if ( $this->settings['htaccess_mod_rewrite'] )
						{
							$url = str_replace( IPS_PUBLIC_SCRIPT . '?', '', $url );
						}
						else
						{
							$url = str_replace( IPS_PUBLIC_SCRIPT . '?', IPS_PUBLIC_SCRIPT . '/', $url );
						}
					break;
					default:
					case 'query_string':
						$url = str_replace( IPS_PUBLIC_SCRIPT . '?', IPS_PUBLIC_SCRIPT . '?/', $url );
					break;
				}

				/* Ensure that if the seoTitle is missing there is no double slash */
				# http://localhost/invisionboard3/user/1//
				# http://localhost/invisionboard3/user/1/mattm/
				if ( substr( $url, -2 ) == '//' )
				{
					$url = substr( $url, 0, -1 );
				}

				/* Others... */
				if ( $restUrl )
				{
					$_url  = str_replace( '&amp;', '&', str_replace( '?', '', $restUrl ) );
					$_data = explode( "&", $_url );
					$_add  = array();
				
					foreach( $_data as $k )
					{
						if ( strstr( $k, '=' ) )
						{
							list( $kk, $vv ) = explode( '=', $k );
						
							if ( $kk and $vv )
							{
								$_add[] = $kk . $this->seoTemplates['__data__']['varSep'] . $vv;
							}
						}
					} 
						
					/* Got anything to add?... */
					if ( count( $_add ) )
					{
						if ( strrpos( $url, $this->seoTemplates['__data__']['end'] ) + strlen( $this->seoTemplates['__data__']['end'] ) == strlen( $url ) )
						{
							$url = substr( $url, 0, -1 );
						}

						$url .= $this->seoTemplates['__data__']['varBlock'] . implode( $this->seoTemplates['__data__']['varSep'], $_add );
					}
				}

				/* Session ID? */
				$this->_appendSession( $url, $_s );

				/* anchor? */
				if ( $_anchor )
				{
					$url .= $_anchor;
				}

				/* Protect html entities */
				$url = str_replace( '~|~', '&#', $url );
				
				$this->setCachedFurl($_md5, $url);
							
				return $url;
			} # / template
			else
			{
				/* Session ID? */
				$this->_appendSession( $url, $_s );

				$this->setCachedFurl($_md5, $url);
				return $url;
			}
		} # / furl on
		else
		{
			/* Session ID? */
			$this->_appendSession( $url, $_s );

			$this->setCachedFurl($_md5, $url);
			return $url;
		}
	}
	
	/**
	 * Builds a fURL
	 * Wrapper of formatUrl and  buildUrl
	 *
	 * <code>$url = $this->registry->output->buildSEOUrl( 'section=foo&module=bar', 'public', 'Matts Link', 'showuser' );</code>
	 * @access	public
	 * @param	string		URL (typically, part of; without the 'base_url')
	 * @param	string		URL Type (corresponds with buildUrl, so 'public', 'publicWithApp', etc
	 * @param	string		SEO Title
	 * @param	string		SEO Template
	 * @return	string		SEO URL
	 */
	public function buildSEOUrl( $url, $urlType='public', $seoTitle='', $seoTemplate='' )
	{
		return $this->formatUrl( $this->buildUrl( $url, $urlType ), $seoTitle, $seoTemplate );
	}
	
	/**
	 * Check to ensure a permalink is correct
	 * Accepts a second value of TRUE to simply return a boolean (TRUE means permalink is OK, false means it is not)
	 * By default, it takes action based on your settings
	 *
	 * @access	public
	 * @param	string		Correct SEO title (app_dir)
	 * @param	boolean		[TRUE, return a boolean (true for OK, false for not). FALSE {default} simply take action based on settings]
	 * @return	boolean
	 */
	public function checkPermalink( $seoTitle, $return=FALSE )
	{
		/* Only serve GET requests */
		if ( $this->request['request_method'] != 'get' )
		{
			return FALSE;
		}
		
		if ( ! $this->settings['use_friendly_urls'] OR ! $seoTitle)
		{
			return FALSE;
		}
		
		$_st  = $this->seoTemplates['__data__']['start'];
		$_end = $this->seoTemplates['__data__']['end'];
		$_sep = $this->seoTemplates['__data__']['varSep'];
		$_blk = $this->seoTemplates['__data__']['varBlock'];
		$_qs  = $_SERVER['QUERY_STRING'] ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
		$_uri = $_SERVER['REQUEST_URI']  ? $_SERVER['REQUEST_URI']  : @getenv('REQUEST_URI');
		
		/* Bug Fix: #20279 */
		if( $this->settings['htaccess_mod_rewrite'] && strpos( $_uri, IPS_PUBLIC_SCRIPT . '?/') )
		{
			$this->registry->getClass('output')->silentRedirect( $this->settings['board_url'] . $_qs, $seoTitle, TRUE );
		}
		
		$_toTest = ( $_qs ) ? $_qs : $_uri;
		
		/* Now we need to strip off the beginning path so we are left with just the FURL part */
		$_path			  = parse_url( $this->settings['board_url'], PHP_URL_PATH );
		$_toTest		  = ( $_path AND $_path != '/' ) ? preg_replace( "#^{$_path}#", '', $_toTest ) : $_toTest;
		$_encodedManually = false;
		
		/* Shouldn't need to check this, but feel better for doing it: Friendly URL? */
		if ( ! strstr( $_toTest, $_end ) )
		{
			return FALSE;
		}
		
		/* If the SEO title has %hex but the incoming URL doesn't, convert the incoming URL */
		if ( strstr( $seoTitle, '%' ) && ! strstr( $_toTest, '%' ) )
		{
			$_toTest 		  = urlencode( $_toTest );
			$_encodedManually = true;
		}
		
		/* Does it contain unicode? */
		if ( strstr( $_toTest, '%' ) )
		{
			/* Lowercase it as some browsers send %E2 but it will be stored as %e2 */
			$_toTest = strtolower( $_toTest );
		}
		
		/* Try original */
		if ( $_encodedManually === false && ! preg_match( "#" . $_st . preg_quote( $seoTitle, '#' ) . '(' . $_end . "$|" . $_end . '\w+?' . $_end . "$|" . preg_quote( $_blk, '#' ) . ")#",  $_toTest ) )
		{
			/* Do we need to encode? */
			$_toTest = urldecode( $_toTest );
		}
		
#print '#\d+?' . $_st . preg_quote( $seoTitle, '#' ) . '(' . $_end . "$|" . $_end . "\w+?" . $_end . "$|" . preg_quote( $_blk, '#' ) . ")#";exit;
		if ( ! preg_match( '#\d+?' . $_st . preg_quote( $seoTitle, '#' ) . '(' . $_end . "$|" . $_end . '\w+?' . $_end . "$|" . preg_quote( $_blk, '#' ) . ")#",  $_toTest ) )
		{
			if ( $return === TRUE )
			{
				return FALSE;
			}
			
			$uri  = array();
			$storeKey  = '';
			$storeData = '';
			
			foreach( $this->seoTemplates as $key => $data )
			{
				if ( ! $data['in']['regex'] )
				{
					continue;
				}
				
				if ( preg_match( $data['in']['regex'], $_toTest, $matches ) )
				{
					$storeKey  = $key;
					$storeData = $data;
					
					if ( is_array( $data['in']['matches'] ) )
					{
						foreach( $data['in']['matches'] as $_replace )
						{
							$k = IPSText::parseCleanKey( $_replace[0] );

							if ( strstr( $_replace[1], '$' ) )
							{
								$v = IPSText::parseCleanValue( $matches[ intval( str_replace( '$', '', $_replace[1] ) ) ] );
							}
							else
							{
								$v = IPSText::parseCleanValue( $_replace[1] );
							}

							$uri[] = $k . '=' . $v;
						}
					}
					
					if ( strstr( $_toTest, $_blk ) )
					{
						$_parse = substr( $_toTest, strrpos( $_toTest, $_blk ) + strlen( $_blk ) );

						$_data = explode( $_sep, $_parse );
						$_c    = 0;

						foreach( $_data as $_v )
						{
							if ( ! $_c )
							{
								$k = IPSText::parseCleanKey( $_v );
								$v = '';
								$_c++;
							}
							else
							{
								$v  = IPSText::parseCleanValue( $_v );
								$_c = 0;

								$uri[] = $k . '=' . $v;
							}
						}
					}
					
					break;
				}
			}
			
			/* Got something? */
			if ( count( $uri ) )
			{
				$newurl	= $this->registry->getClass( 'output' )->formatUrl( $this->registry->getClass( 'output' )->buildUrl( implode( '&', $uri ), 'public' ), $seoTitle, $key );
				
				$base_url = ( ! IN_ACP AND $this->member->session_type != 'cookie' ) ? preg_replace( "/s=([a-zA-Z0-9]{32})(&amp;|&)/", '', $this->settings['base_url'] ) : $this->settings['base_url'];
				
				switch( $this->settings['url_type'] )
				{
					case 'path_info' :
						if ( $this->settings['htaccess_mod_rewrite'] )
						{
							$base_url = str_replace( IPS_PUBLIC_SCRIPT . '?', '', $base_url );
						}
						else
						{
							$base_url = str_replace( IPS_PUBLIC_SCRIPT . '?', IPS_PUBLIC_SCRIPT . '/', $base_url );
						}
						break;
					default :
					case 'query_string' :
						$base_url = str_replace( IPS_PUBLIC_SCRIPT . '?', IPS_PUBLIC_SCRIPT . '?/', $base_url );
					break;
				}
				
				$base_url = rtrim( $base_url, '/' ); 
 
				if ( $base_url . $_toTest != $newurl )
				{
					/* Load information file */ 
					if( $storeData['app'] && is_file( IPSLib::getAppDir( $storeData['app'] ) . '/extensions/furlRedirect.php' ) )
					{
						$_class   = IPSLib::loadLibrary( IPSLib::getAppDir( $storeData['app'] ) . '/extensions/furlRedirect.php', 'furlRedirect_' . $storeData['app'], $storeData['app'] );
						$_furl    = new $_class( ipsRegistry::instance() );
						$_testUrl = strstr( $this->settings['base_url'], '?' ) ? $this->settings['base_url'] . implode( '&', $uri ) : $this->settings['base_url'] . '?' . implode( '&', $uri );
						
						$_furl->setKeyByUri( $_testUrl );
						$_seoTitle = $_furl->fetchSeoTitle();
						
						if ( $_seoTitle && empty( $this->request['debug'] ) )
						{
							$this->registry->getClass('output')->silentRedirect( $_testUrl, $_seoTitle, true, $storeKey );
						}
					}
					else
					{
						$this->registry->getClass('output')->silentRedirect( $newurl, $seoTitle, TRUE, $key );
					}
				}
			}
			else
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Clear any loaded CSS
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function clearLoadedCss()
	{
		$this->_css	= array(
							'inline'	=> array(),
							'import'	=> array(),
							);
		$this->registry->getClass('output')->skin['_cssGroupsArray'] = array();
	}
	
	/**
	 * Add content to the document <head>
	 *
	 * @access	public
	 * @param	string		Type of data to add: inlinecss, importcss, js, javascript, raw, rss, rsd, etc
	 * @param	string		Data to add
	 * @return	@e void
	 */
	public function addToDocumentHead( $type, $data )
	{
		if( $type == 'js' )
		{
			$type = 'javascript';
		}

		if ( $type == 'inlinecss' )
		{
			$this->_css['inline'][]	= array( 'content'	=> $data );
		}
		else if ( $type == 'importcss' )
		{
			//-----------------------------------------
			// Use $data as key to prevent CSS being
			// included more than once (breaks Minify)
			//-----------------------------------------
			if ( $this->_usingSafeModeSkins and !IN_ACP )
			{
				/* Bug #33264 - If safe mode is on then we are going to have to load from DB. I hope you're happy safe mode users! */
				$cssName = IPSText::alphanumericalClean( @str_replace( '.css', '', @array_pop( @explode( '/', $data ) ) ), '-_' );
				
				if ( $cssName )
				{
					$cssData = $this->DB->buildAndFetch( array( 'select' => '*',
										 			 			'from'   => 'skin_cache',
										 			 			'where'  => "cache_set_id=" . intval( $this->skin['set_id'] ) . " AND cache_type='css' and cache_value_1='" . $this->DB->addSlashes( $cssName ) . "'" ) );
					if ( $cssData['cache_content'] )
					{
						return $this->addToDocumentHead( 'inlinecss', $this->parseIPSTags( $cssData['cache_content'] ) );
					}
				}
			}
			else
			{
				$this->_css['import'][$data]	= array( 'content'	=> $data );
			}
		}
		else
		{
			$this->_documentHeadItems[ $type ][] = $data;
		}
	}
	
	/**
	 * Passes a module name to the IPB JS loader script
	 *
	 * @access	public
	 * @param	string		Name of module to load
	 * @param	integer		High Priority
	 * @return	@e void
	 */
	public function addJSModule( $data, $priority )
	{
		$this->_jsLoader[$data] = $priority;
	}
	
	/**
	 * Add content
	 *
	 * @access	public
	 * @param	string		content to add
	 * @param	boolean		Prepend instead of append
	 * @return	@e void
	 */
	public function addContent( $content, $prepend=false )
	{
		if( $prepend )
		{
			$this->_html = $content . $this->_html;
		}
		else
		{
			$this->_html .= $content;
		}
	}
	
	/**
	 * Set the title of the document
	 *
	 * @access	public
	 * @param	string		Title
	 * @return	@e void
	 */
	public function setTitle( $title )
	{
		$this->_title = strip_tags( $title, '<%pageNumber%>' );
	}
	
	/**
	 * Get the currently set page title
	 *
	 * @access	public
	 * @return	string	Page title
	 */
	public function getTitle()
	{
		return $this->_title;
	}
	
	/**
	 * Add navigational elements
	 *
	 * @access	public
	 * @param	string		Title
	 * @param	string		URL
	 * @param	string		SEO Title
	 * @param	string		SEO Template
	 * @param	string		Base
	 * @return	@e void
	 */
	public function addNavigation( $title, $url='', $seoTitle='', $seoTemplate='', $base='public' )
	{
		$this->_navigation[] = array( $title, $url, $seoTitle, $seoTemplate, $base, count( $this->_navigation ) );
	}
	
	/**
	 * Set the is error flag
	 *
	 * @access	public
	 * @param	bool	Set it to true/false
	 * @return	@e void
	 */
    public function setError( $boolean )
	{
		$this->_isError = $boolean;
	}

	/**
	 * Global set up stuff
	 * Sorts the JS module array, calls initiate on the output engine, etc
	 *
	 * @access	private
	 * @param	string		Type of output (normal/popup/redirect)
	 * @return	@e void
	 */
	private function _sendOutputSetUp( $type )
	{
		//-----------------------------------------
        // INIT
        //-----------------------------------------
        
		$this->outputFormatClass->core_initiate();
		
		//-----------------------------------------
		// Type...
		//-----------------------------------------
		
		$this->outputFormatClass->core_setOutputType( $type );
				
		//----------------------------------------
		// Sort JS Modules
		//----------------------------------------
		
		arsort( $this->_jsLoader, SORT_NUMERIC );

		//-----------------------------------------
        // NAVIGATION
        //-----------------------------------------
        
        if ( $this->_isError === TRUE )
        {
			$this->_navigation = array();
        }

		//-----------------------------------------
		// Board offline?
		//-----------------------------------------
		
 		if ( $this->settings['board_offline'] == 1 )
 		{
 			$this->_title = $this->lang->words['warn_offline'] . " " . $this->_title;
 		}
 		
		//-----------------------------------------
        // Extra head items
        //-----------------------------------------
        
        $this->outputFormatClass->addHeadItems();
        
		//-----------------------------------------
        // And finally send the extra CSS
        //-----------------------------------------

		if( count($this->_css['import'] ) )
		{
			foreach( $this->_css['import'] as $data )
			{
				$this->outputFormatClass->addCSS( 'import', $data['content'] );
			}
		}

		if( count($this->_css['inline'] ) )
		{
			foreach( $this->_css['inline'] as $data )
			{
				$this->outputFormatClass->addCSS( 'inline', $data['content'] );
			}
		}
        
        //-----------------------------------------
        // Easter egg?  Or is it...mwahaha
        //-----------------------------------------
        
        if( isset( $this->request[ base64_decode('eWVhcg==') ] ) AND $this->request[ base64_decode('eWVhcg==') ] == base64_decode('aSZsdDszMTk5OQ==') )
        {
        	$this->_jsLoader['misc'] = 0;
        	$this->addToDocumentHead( 'raw', "<style type='text/css'>#content{ background-image: url(" . PUBLIC_DIRECTORY . "/style_captcha/captcha_backgrounds/captcha3.jpg); background-repeat: repeat; } *{ font-family: 'Comic Sans MS'; color: #ff9900; font-size: 1.05em; cursor: crosshair; }</style>" );
        }
        
	}
    
    /**
	 * Main output function
	 *
	 * @param	bool	Return finished output instead of printing
	 * @return	@e void 
	 */
    public function sendOutput( $return=false )
    {
        //-----------------------------------------
        // INIT
        //-----------------------------------------
        
		$_NOW = IPSDebug::getMemoryDebugFlag();

		$this->_sendOutputSetUp( 'normal' );
		
		//-----------------------------------------
		// Ad Code
		//-----------------------------------------
		$adCodeData = array();
		
		if( $this->registry->getClass('IPSAdCode')->userCanViewAds() )
		{
			$adCodeData['adHeaderCode'] = $this->registry->getClass('IPSAdCode')->getGobalCode( 'header' );
			$adCodeData['adFooterCode'] = $this->registry->getClass('IPSAdCode')->getGobalCode( 'footer' );
			
			$adCodeData['adHeaderCode'] = $adCodeData['adHeaderCode'] ? $adCodeData['adHeaderCode'] : $this->registry->getClass('IPSAdCode')->getAdCode('ad_code_global_header');
			$adCodeData['adFooterCode'] = $adCodeData['adFooterCode'] ? $adCodeData['adFooterCode'] : $this->registry->getClass('IPSAdCode')->getAdCode('ad_code_global_footer');
		}
		
		//-----------------------------------------
		// Gather output
		//-----------------------------------------

        $output = $this->outputFormatClass->fetchOutput( $this->_html, $this->_title, $this->_navigation, $this->_documentHeadItems, $this->_jsLoader, $adCodeData );
		
		$output = $this->templateHooks( $output );
				
		$output	= $this->replaceMacros( $output );
		
        //-----------------------------------------
        // Check for SQL Debug
        //-----------------------------------------
        
        $this->_checkSQLDebug();
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->outputFormatClass->printHeader();
		
		/* Remove unused hook comments */
		$output = preg_replace( '#<!--hook\.([^\>]+?)-->#', '', $output );
		
		/* Insert stats */
		$output = str_replace( '<!--DEBUG_STATS-->', $this->outputFormatClass->html_showDebugInfo(), $output );
		
		/* Return output instead of printing? */
		if( $return )
		{
			IPSDebug::setMemoryDebugFlag( "Output sent", $_NOW );
			
			$this->outputFormatClass->finishUp();
			
			return $output;
		}

		print $output;
		
		IPSDebug::setMemoryDebugFlag( "Output sent", $_NOW );
		
		$this->outputFormatClass->finishUp();
		
        exit;
    }

	/**
	 * Replace macros
	 * Left here as a reference 'cos other functions
	 * call it.. Must fix all that up at some point
	 *
	 * @access	public
	 * @param	string		Text
	 * @param	string		Parsed text
	 * @see		parseIPSTags
	 */
	public function replaceMacros( $text )
	{
		return $this->outputFormatClass->parseIPSTags( $text );
	}
    
    /**
	 * Print a redirect screen
	 * Wrapper function, really
	 *
	 * @access	public
	 * @param	string		Text to display on the redirect screen
	 * @param	string		URL to direct to
	 * @param	string		SEO Title
	 * @param	string		SEO Template
	 * @return	string		HTML to browser and exits
	 */
    public function redirectScreen( $text="", $url="", $seoTitle="", $seoTemplate='' )
    {
    	/* Use new inline notifications */
    	if ( ( ! defined('IPS_FORCE_HTML_REDIRECT') OR ! IPS_FORCE_HTML_REDIRECT ) and ( substr( $url, 0, strlen( $this->settings['board_url'] ) ) == $this->settings['board_url'] OR ( defined('CCS_GATEWAY_CALLED') AND CCS_GATEWAY_CALLED ) ) )
    	{    		
    		$this->member->sessionClass()->setInlineMessage( $text );
    		
    		$this->silentRedirect($url, $seoTitle, false, $seoTemplate );
    	}
    	else
    	{
			//-----------------------------------------
	        // INIT
	        //-----------------------------------------
	
			$this->_sendOutputSetUp( 'redirect' );
			
			//-----------------------------------------
			// Gather output
			//-----------------------------------------
			
	        $output = $this->outputFormatClass->fetchOutput( $this->_html, $this->_title, $this->_navigation, $this->_documentHeadItems, $this->_jsLoader, array( 'url' => $url, 'text' => $text, 'seoTitle' => $seoTitle, 'seoTemplate' => $seoTemplate ) );
			
			$output = $this->templateHooks( $output );
			
	        //-----------------------------------------
	        // Check for SQL Debug
	        //-----------------------------------------
	        
	        $this->_checkSQLDebug();
			
			//-----------------------------------------
			// Print it...
			//-----------------------------------------
			
			$this->outputFormatClass->printHeader();
			
			/* Remove unused hook comments */
			$output = preg_replace( '#<!--hook\.([^\>]+?)-->#', '', $output );		
			
			print $output;
			
			$this->outputFormatClass->finishUp();
    	}
		
        exit;
    }
    
    /**
	 * Displays a pop up window
	 *
	 * @access	public
	 * @param	string		Data to output (HTML, for example)
	 * @param	bool		Return finished output instead of printing
	 * @return	@e void		Prints data to browser and exits
	 */
	public function popUpWindow( $output, $return=false )
    {
		//-----------------------------------------
        // INIT
        //-----------------------------------------
        
		$this->_sendOutputSetUp( 'popup' );

		//-----------------------------------------
		// Gather output
		//-----------------------------------------

        $output = $this->outputFormatClass->fetchOutput( $output, $this->_title, $this->_navigation, $this->_documentHeadItems, $this->_jsLoader );

		$output = $this->templateHooks( $output );
		$output	= $this->replaceMacros( $output );
		
        //-----------------------------------------
        // Check for SQL Debug
        //-----------------------------------------
        
        $this->_checkSQLDebug();
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->outputFormatClass->printHeader();
		
		/* Remove unused hook comments */
		$output = preg_replace( '#<!--hook\.([^\>]+?)-->#', '', $output );			
		
		if( $return )
		{
			$this->outputFormatClass->finishUp();

			return $output;
		}

		print $output;
		
		$this->outputFormatClass->finishUp();
		
        exit;
    } 
    
	/**
	 * Immediate redirect
	 *
	 * @access	public
	 * @param	string		URL to redirect to
	 * @param	string		SEO Title
	 * @param	boolean		Send a 301 header first (Moved Permanently)
	 * @param	string		SEO Template
	 * @return	mixed
	 */
	public function silentRedirect( $url, $seoTitle='', $send301=FALSE, $seoTemplate='' )
	{
		return $this->outputFormatClass->silentRedirect( $url, $seoTitle, $send301, $seoTemplate );
	}
	
	
	/**
	 * Build up page span links.
	 * Example:
	 * 
	 *	<code>$pages = $this->generatePagination( array( 'totalItems'         => ($this->topic['posts']+1),					# The total number of items (posts, topics, etc)
	 *											   'itemsPerPage'       => $this->settings['display_max_posts'],		# Number of items per page
	 *											   'currentStartValue'  => $this->request['start'],						# The current 'start' value (usually 'st')
	 *											   'baseUrl'            => "showtopic=".$this->topic['tid'].$hl,		# The URL to which the st= is attached
	 *											   'anchor'				=> "myanchor",									# The anchor to append to the URL (makes url#anchor link)
	 * 											   'dotsSkip'           => 2,											# Number of pages to show per section( either side of current), IE: 1 ... 4 5 [6] 7 8 ... 10
	 *											   'noDropdown'         => true,										# Don't add the 'jump to page' dropdown
	 *											   'startValueKey'      => 'start'										# The st=x element if not 'st'.
	 *											   'seoTitle'			=> $this->topic['title_seo'],					# The SEO title to use for furls
	 *											   'seoTemplate'		=> 'showtopic',									# The FURL template to use
	 *											   'method'				=> 'pages',										# Show regular pagination (pages) or just 'next' and 'previous' links (nextPrevious)?
	 *											   'ajaxLoad'			=> '',											# An element ID to load the content into via AJAX (only supported with nextPrevious method)
	 *											   'disableSinglePage'	=> true,										# If true, shows nothing for single pages.  If false, shows 'Single Page' in place of pagination links.
	 * 											   'showNumbers'		=> false ) );								 	# Show individual page numbers?</code>
	 *
	 * @access	public
	 * @param	array	Page data
	 * @return	string	Parsed page links HTML
	 * @since	2.0
	 */
	public function generatePagination($data)
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$work = array();
		
		$data['dotsSkip']			= isset($data['dotsSkip'])				? $data['dotsSkip'] : '';
		$data['noDropdown']			= isset($data['noDropdown'])			? intval( $data['noDropdown'] ) : 0;
		$data['startValueKey']		= isset($data['startValueKey'])			? $data['startValueKey']	 : '';
		$data['currentStartValue']	= isset( $data['currentStartValue'] )	? $data['currentStartValue'] : $this->request['st'];
		$data['dotsSkip']			= ! $data['dotsSkip']					? intval($this->settings['show_x_page_link'] ) : $data['dotsSkip'];
		$data['startValueKey']		= ! $data['startValueKey']				? 'st' : $data['startValueKey'];
		$data['seoTitle']			= isset( $data['seoTitle'] )			? $data['seoTitle'] : '';
		$data['base']				= isset( $data['base'] )				? $data['base'] : 'public';
		$data['uniqid']				= substr( str_replace( array( ' ', '.' ), '', uniqid( microtime(), true ) ), 0, 10 );
		$data['showNumbers']		= $data['showNumbers'] === false		? false : true;
		$data['method']				= empty( $data['method'] )				? 'pages' : 'nextPrevious';
		$data['ajaxLoad']			= isset($data['ajaxLoad'])				? $data['ajaxLoad'] : '';
		$data['anchor']				= isset($data['anchor'])				? '#' . $data['anchor'] : '';
		$data['disableSinglePage']	= isset($data['disableSinglePage'])		? $data['disableSinglePage'] : true;
		$data['realTitle']			= isset($data['realTitle'])				? $data['realTitle'] : '';
		
		//-----------------------------------------
		// Are we on an actual page right now?
		//-----------------------------------------
								
		$modulus = $data['currentStartValue'] % $data['itemsPerPage'];
		
		if ( $modulus != 0 )
		{
			$this->silentRedirect( $this->settings['base_url'] . $data['baseUrl'] . '&amp;' . $data['startValueKey'] . '=' . ( $data['currentStartValue'] - $modulus ), $data['seoTitle'], TRUE, $data['seoTemplate'] );
			return;
		}
		
		//-----------------------------------------
		// Get the number of pages
		//-----------------------------------------
		
		if ( $data['totalItems'] > 0 )
		{
			$work['pages'] = ceil( $data['totalItems'] / $data['itemsPerPage'] );
		}
		
		$work['pages'] = isset( $work['pages'] ) ? $work['pages'] : 1;
		
		/* Are we on a page that doesn't exist? */
		if ( ( $data['totalItems'] + $data['itemsPerPage'] ) < ( $data['currentStartValue'] + $data['itemsPerPage'] ) )
		{
			$this->registry->output->showError( 'page_doesnt_exist', 'pag1', null, null, 404 );
		}
		
		//-----------------------------------------
		// Are we disabling single pages?
		//-----------------------------------------
		
		if( $data['disableSinglePage'] AND ( !$work['pages'] OR $work['pages'] == 1 ) )
		{
			return '';
		}
			
		/* Is this nextPrevious style? */
		if ( $data['method'] == 'nextPrevious' )
		{
			if ( $data['currentStartValue'] > 0 )
			{
				$data['_hasPrevious'] = true;
			}
			
			if ( isset( $data['totalItems'] ) && ( $data['totalItems'] > ( $data['currentStartValue'] + $data['itemsPerPage'] ) ) )
			{
				$data['_hasNext'] = true;
			}
			
			return $this->getTemplate('global')->nextPreviousTemplate( $data );
		}
		else
		{
			//-----------------------------------------
			// Set up
			//-----------------------------------------
			
			$work['total_page']   = $work['pages'];
			$work['current_page'] = $data['currentStartValue'] > 0 ? ($data['currentStartValue'] / $data['itemsPerPage']) + 1 : 1;
			
			//-----------------------------------------
			// Loppy loo
			//-----------------------------------------
			$work['_pageNumbers'] = array();
			
			if ($work['pages'] > 1)
			{
				for( $i = 0, $j = $work['pages'] - 1; $i <= $j; ++$i )
				{
					$RealNo = $i * $data['itemsPerPage'];
					$PageNo = $i+1;
					
					if ( $PageNo < ($work['current_page'] - $data['dotsSkip']) )
					{
						# Instead of just looping as many times as necessary doing nothing to get to the next appropriate number, let's just skip there now
						$i = $work['current_page'] - $data['dotsSkip'] - 2;
						continue;
					}
					
					if ( $PageNo > ($work['current_page'] + $data['dotsSkip']) )
					{
						$work['_showEndDots'] = 1;
						# Page is out of range... 
						break;
					}
					
					$work['_pageNumbers'][ $RealNo ] = ceil( $PageNo );
				}
			}
			
			if ( $work['pages'] > 1 AND $work['current_page'] > 1 )
			{
				$this->outputFormatClass->_current_page_title = $work['current_page'];
			}
			
			/**
			 * Meta data for certain browsers
			 */
			if( $work['current_page'] > 1 )
			{
				$this->addToDocumentHead( 'raw', "<link rel='first' href='" . $this->buildSEOUrl( $data['baseUrl'] . '&amp;' . $data['startValueKey'] . '=0', $data['base'], $data['seoTitle'], $data['seoTemplate'] ) . "' />" );
				$this->addToDocumentHead( 'raw', "<link rel='prev' href='" . $this->buildSEOUrl( $data['baseUrl'] . '&amp;' . $data['startValueKey'] . '=' . (intval( $data['currentStartValue'] - $data['itemsPerPage'] )), $data['base'], $data['seoTitle'], $data['seoTemplate'] ) . "' />" );
			}
			
			if( $work['current_page'] < $work['pages'] )
			{
				$this->addToDocumentHead( 'raw', "<link rel='next' href='" . $this->buildSEOUrl( $data['baseUrl'] . '&amp;' . $data['startValueKey'] . '=' . (intval( $data['currentStartValue'] + $data['itemsPerPage'] )), $data['base'], $data['seoTitle'], $data['seoTemplate'] ) . "' />" );
				$this->addToDocumentHead( 'raw', "<link rel='last' href='" . $this->buildSEOUrl( $data['baseUrl'] . '&amp;' . $data['startValueKey'] . '=' . (intval( ( $work['pages'] - 1 ) * $data['itemsPerPage'] )), $data['base'], $data['seoTitle'], $data['seoTemplate'] ) . "' />" );
			}
			
			/* Store the data */
			$this->_paginationProcessedData = $work;
			
			return $this->getTemplate('global')->paginationTemplate( $work, $data );
		}
	}
	
	/**
	 * Process remap data
	 * For use with IN_DEV
	 *
	 * @access	public
	 * @param	boolean		Override IN_DEV flag and load anyway
	 * @return 	array 		Array of remap data
	 */
	public function buildRemapData( $FORCE=FALSE )
	{
		$remapData = array();
		
		if ( ( IN_DEV or $FORCE ) and is_file( DOC_IPS_ROOT_PATH . 'cache/skin_cache/masterMap.php' ) )
		{
			$REMAP = array();
			include( DOC_IPS_ROOT_PATH . 'cache/skin_cache/masterMap.php' );/*noLibHook*/
			
			if ( is_array( $REMAP ) && count( $REMAP ) )
			{
				/* Master skins */
				foreach( array( 'templates', 'css' ) as $type )
				{
					foreach( $REMAP[ $type ] as $id => $dir )
					{
						if ( preg_match( "#^[a-zA-Z]#", $id ) )
						{
							if ( is_array( $REMAP['masterKeys'] ) AND in_array( $id, $REMAP['masterKeys'] ) )
							{
								$_skin = array( 'set_id' => $id );
							}
							else
							{
								/* we're using a key */
								$_skin = $this->_fetchSkinByKey( $id );
							}
							
							$remapData[ $type ][ $_skin['set_id'] ] = $dir;
						}
						else
						{
							/* ID */
							$remapData[ $type ][ $id ] = $dir;
						}
					}
				}
				
				/* IN DEV default */
				if ( preg_match( "#^[a-zA-Z]#", $REMAP['inDevDefault'] ) )
				{
					if ( is_array( $REMAP['masterKeys'] ) AND in_array( $REMAP['inDevDefault'], $REMAP['masterKeys'] ) )
					{
						$_skin	= array( 'set_id' => $REMAP['inDevDefault'] );
					}
					else
					{
						/* we're using a key */
						$_skin	= $this->_fetchSkinByKey( $REMAP['inDevDefault'] );
					}
					
					$remapData['inDevDefault'] = $_skin['set_id'];
				}
				else
				{
					$remapData['inDevDefault'] = $REMAP['inDevDefault'];
				}
				
				/* Master keys */
				$remapData['masterKeys'] = $REMAP['masterKeys'];
				
				/* Images */
				$remapData['images'] = $REMAP['images'];

				/* IN DEV export */
				foreach( $REMAP['export'] as $id => $key )
				{
					if ( preg_match( "#^[a-zA-Z]#", $key ) )
					{
						if ( is_array( $REMAP['masterKeys'] ) AND in_array( $key, $REMAP['masterKeys'] ) )
						{
							$_skin = array( 'set_id' => $key );
						}
						else
						{
							/* we're using a key */
							$_skin = $this->_fetchSkinByKey( $id );
						}
					
						$remapData['export'][ $id ] = $_skin['set_id'];
					}
					else
					{
						$remapData['export'][ $id ] = $id;
					}
				}
			}
		}
		else
		{
			$remapData = array( 'templates'		=> array( 'root' => 'master_skin' ),
								'css'			=> array( 'root' => 'master_css' ),
								'inDevDefault'	=>  'root'
							);
		}
		
		return $remapData;
	}
	
	/**
	 * Fetch a skin set via a key
	 *
	 * @access	protected
	 * @param	string		Skin set key
	 * @return	array 		Array of skin data
	 */
	protected function _fetchSkinByKey( $key )
	{
		foreach( $this->allSkins as $_id => $_data )
		{
			if ( $_data['set_key'] == $key )
			{
				return $_data;
			}
		}
		
		return array();
	}
	
	/**
	 * Show error message
	 *
	 * <code>$this->registry->output->showError( 'no_permission' );</code>
	 * <code>$this->registry->output->showError( 'hack_attempt', 505, TRUE );</code>
	 * <code>$this->registry->output->showError( array( 'Registration Error: %s', 'No password' ), 0, TRUE );</code>
	 * @access	public
	 * @param	mixed		Array if there is data to replace in the message string, or string message or key for error lang file
	 * @param	integer		Error code
	 * @param	boolean		Log error (use for possible hack attempts, fiddling, etc )
	 * @param   string      Additional data to log, but not display to the user
	 * @param	integer		Header code to send
	 * @return	@e void
	 * @since	3.0.0
	 */
    public function showError( $message, $code=0, $logError=FALSE, $logExtra='', $header=401 )
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$skipCodeNotifications = array( 404 );
    	
		$msg   	   	      = "";
		$extra			  = "";
		$skipNotification = false;
		$this->registry->getClass('class_localization')->loadLanguageFile( array( "public_error" ), 'core' );

    	//-----------------------------------------
    	// Error Message
    	//-----------------------------------------
		
		if ( is_array( $message ) )
		{
			$msg	= $message[0];
			$extra	= $message[1];
		}
		else
		{
			$msg	= $message;
		}
		
    	$msg = ( isset($this->lang->words[ $msg ]) ) ? $this->lang->words[ $msg ] : $msg;
    		
    	if ( $extra )
    	{
    		$msg = sprintf( $msg, $extra );
    	}
		
		//-----------------------------------------
    	// Update session
    	//-----------------------------------------
		
		$this->member->updateMySession( array( 'in_error' => 1 ) );
		
		//-----------------------------------------
    	// Log all errors above set level?
    	//-----------------------------------------
    	
    	if( $code )
    	{
    		if( $this->settings['error_log_level'] )
    		{
    			$level = substr( $code, 0, 1 );

				if( $this->settings['error_log_level'] == 1 )
				{
					$logError = true;
				}
				else if( $level > 1 )
				{
					if( $level >= $this->settings['error_log_level'] )
					{
						$logError = true;
					}
				}
			}
    	}
		
		/* if it's a 5030 (moderate no auth key) and we're a bot, skip it - bug #22402 */
		//if ( ( $code == 5031 OR $code == 5030 ) AND $this->member->is_not_human )
		/* Google and co are hitting all kinds of stuff and it's getting almost impossible
		   to keep manually updating IDs. So I figure just not log if it's a bot.Of course
		   anyone can spoof a user-agent but this isn't a mission critical piece of functionality. */
		if ( $this->member->is_not_human )
		{
			$logError         = false;
			$skipNotification = true;
		}
		
		/* Skipping it anyway? */
		if ( in_array( $code, $skipCodeNotifications ) )
		{
			$logError         = false;
			$skipNotification = true;
		}
		
		//-----------------------------------------
    	// Log the error, if needed
    	//-----------------------------------------
    	
		if( $logError )
		{
			$this->logErrorMessage( $msg . '<br /><br />' . $logExtra, $code );
		}
		
		//-----------------------------------------
    	// Send notification if needed
    	//-----------------------------------------
    	
    	if ( $skipNotification === false )
    	{
    		$this->sendErrorNotification( $msg, $code );
    	}
    	
    	//-----------------------------------------
    	// Set header response code
    	//-----------------------------------------
    	
    	$this->outputFormatClass->setHeaderCode( $header ? $header : 401 );
		
		//-----------------------------------------
		// Send to output engine
		//-----------------------------------------

        $this->addContent( $this->outputFormatClass->displayError( $msg, $code, $header ) );
		$this->setTitle( $this->lang->words['board_error_title']  . ' - ' . $this->settings['board_name'] );
		$this->sendOutput();
		
        exit;
    }

	/**
	 * Show board offline message
	 *
	 * @access	public
	 * @return	@e void
	 * @since	2.0
	 */
    public function showBoardOffline()
    {
    	//-----------------------------------------
    	// Get offline message (not cached)
    	//-----------------------------------------
    	
    	if( !$this->offlineMessage )
    	{
	    	$row = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => "conf_key='offline_msg'" ) );
	    	
	    	$this->registry->getClass( 'class_localization')->loadLanguageFile( array( "public_error" ), 'core' );
	    	
	    	$this->offlineMessage = $row['conf_value'];
    	}
    	
    	//-----------------------------------------
    	// Parse the bbcode
    	//-----------------------------------------
    	
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 1;
		IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'global';
		
		$this->offlineMessage = IPSText::getTextClass('bbcode')->preDisplayParse( IPSText::getTextClass('bbcode')->preDbParse( $this->offlineMessage ) );

		//-----------------------------------------
		// Send to output engine
		//-----------------------------------------
		
		$this->outputFormatClass->setHeaderCode( 503 );
        $this->addContent( $this->outputFormatClass->displayBoardOffline( $this->offlineMessage ) );
		$this->setTitle( $this->lang->words['board_offline_title'] );
		$this->sendOutput();
		
        exit;
    }
	
	/**
	 * Check if SQL debug is on, if so add the SQL debug data
	 *
	 * @access	protected
	 * @return	@e void
	 * @since	2.0
	 */
    protected function _checkSQLDebug()
    {
    	if ($this->DB->obj['debug'])
        {
        	flush();
        	print "<html><head><title>SQL Debugger</title><body bgcolor='white'><style type='text/css'> TABLE, TD, TR, BODY { font-family: verdana,arial, sans-serif;color:black;font-size:11px }</style>";
        	print "<h1 align='center'>SQL Total Time: {$this->DB->sql_time} for {$this->DB->query_count} queries</h1><br />".$this->DB->debug_html;
        	print "<br /><div align='center'><strong>Total SQL Time: {$this->DB->sql_time}</div></body></html>";
        	
			print "<br />SQL Fetch Total Memory: " . IPSLib::sizeFormat( $this->DB->_tmpT, TRUE );
			$this->outputFormatClass->finishUp();
			exit();
        }
    }

	/**
     * Runs all the registered hooks for the loaded template groups
     *
     * @access	public
     * @param	string		$text
     * @return	string
     */
    public function templateHooks( $text )
    {
    	/* Hook Output */
    	$hook_output = array();
    	
    	/* Get a list of skin groups */
    	$skin_groups = array();
    	
    	foreach( $this->compiled_templates as $group => $tpl )
    	{
    		$skin_groups[] = $group;
    	}
    	
    	/* Loop through the cache */
    	$hooksCache = ipsRegistry::cache()->getCache( 'hooks' );
    	
		foreach( $skin_groups as $skinGroup )
		{
			if( isset( $hooksCache['templateHooks'][$skinGroup] ) AND is_array( $hooksCache['templateHooks'][$skinGroup] ) AND count( $hooksCache['templateHooks'][$skinGroup] ) )
			{
				foreach( $hooksCache['templateHooks'][$skinGroup] as $tplHook )
				{
					/* Build hook point */
					$arr_key = $tplHook['type'] . '.' . $skinGroup . '.' . $tplHook['skinFunction'] . '.' . $tplHook['id'] . '.' . $tplHook['position'];
					
					/* Terabyte - hook point not available? Skip the hook */
					if ( !isset($hook_output[ $arr_key ]) && strpos( $text, '<!--hook.' . $arr_key . '-->' ) === FALSE )
					{
						continue;
					}
					
					/* Check for hook file */
					if( is_file( IPS_HOOKS_PATH . $tplHook['filename'] ) )
					{
						/* Check for hook class */
						include_once( IPS_HOOKS_PATH . $tplHook['filename'] );/*noLibHook*/
						
						if( class_exists( $tplHook['className'] ) )
						{
							if( ! isset( $hook_output[ $arr_key ] ) )
							{
								$hook_output[ $arr_key ] = '';
							}
							
							/* Create and run the hook */
							$_hook = new $tplHook['className'];
							
							if( method_exists( $_hook, 'replaceOutput' ) )
							{
								$text = $this->replaceMacros( $_hook->replaceOutput( $text, $arr_key ) );
							}
							
							$hook_output[ $arr_key ] .= $_hook->getOutput();
						}
					}
				}
			}
		}

		if ( count( $hook_output ) )
		{
			foreach( $hook_output as $hook_location => $hook_content )
			{
				$text = str_replace( '<!--hook.' . $hook_location . '-->', '<!--hook.' . $hook_location . '-->' . $this->replaceMacros( $hook_content ), $text );
			}
		}
		
	
		/* If a hook in skin_boards loads skin_profile, we now need to check skin_profile too, but we don't want to check skin_boards a second time */ 
		$_diff  = array_diff( array_keys($this->compiled_templates), $skin_groups );
		
		if ( count( $_diff ) )
		{
			$_toRestore = $this->compiled_templates;
			
			foreach( $skin_groups as $_group )
			{
				unset( $this->compiled_templates[$_group] );
			}
			
			$this->templateHooks( $text );
			
			$this->compiled_templates = $_toRestore;
		} 
 
		return $text;
    }

    /**
	 * Check if there is a skin hook registered here and 
	 * if so overload the skin file with this hook
	 *
	 * @access	protected
	 * @param	string		Skin group name
	 * @param	string		Class name
	 * @param	integer		Skin ID
	 * @return	string		Class name to instantiate
	 */
    protected function _getSkinHooks( $name, $classname, $id )
    {
		/* Hooks: Are we overloading this class? */
		$hooksCache	= ipsRegistry::cache()->getCache('hooks');
		
		if( isset($hooksCache['skinHooks'][ $name ]) && is_array($hooksCache['skinHooks'][ $name ]) && count($hooksCache['skinHooks'][ $name ]) )
		{
			foreach( $hooksCache['skinHooks'][ $name ] as $classOverloader )
			{
				if( is_file( IPS_HOOKS_PATH . $classOverloader['filename'] ) )
				{
					if( ! class_exists( $classOverloader['className'] ) )
					{
						/* Hooks: Do we have the hook file? */
						$thisContents = file_get_contents( IPS_HOOKS_PATH . $classOverloader['filename'] );
						$thisContents = str_replace( $name."(~id~)", $classname, $thisContents );
						
						ob_start();
						eval( $thisContents );
						ob_end_clean();
					}
					
					if( class_exists( $classOverloader['className'] ) )
					{
						/* Hooks: We have the hook file and the class exists - reset the classname to load */
						$classname = $classOverloader['className'];
					}
				}
			}
		}
		
		return $classname;
	}
	
	/**
	 * Destruct
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function __destruct()
	{
		//-----------------------------------------
		// Make sure only this class calls this
		//-----------------------------------------
		
		if ( get_class( $this ) != 'output' )
		{
			return;
		}
	}
	
	/**
	 * Log error messages to the error logs table
	 *
	 * @access	public
	 * @param	string		Error message
	 * @param	integer		Error code
	 * @return	@e void
	 */
	public function logErrorMessage( $message, $code=0 )
	{
		$toInsert	= array(
							'log_member'		=> $this->member->getProperty('member_id'),
							'log_date'			=> time(),
							'log_error'			=> $message,
							'log_error_code'	=> $code,
							'log_ip_address'	=> $this->member->ip_address,
							'log_request_uri'	=> my_getenv('REQUEST_URI'),
							);

		$this->DB->insert( 'error_logs', $toInsert );
	}
	
	/**
	 * Determine if notification needs to be sent, and send it
	 *
	 * @access	public
	 * @param	string		Error message
	 * @param	integer		Error code
	 * @return	boolean		Email sent or not
	 */
	public function sendErrorNotification( $message, $code=0 )
	{
		if( !$this->settings['error_log_notify'] )
		{
			return false;
		}
		
		if( $this->settings['error_log_notify'] > 1 )
		{
			$level = substr( $code, 0, 1 );
	
			if( $this->settings['error_log_notify'] > 1 )
			{
				if( $level < $this->settings['error_log_notify'] - 1 )
				{
					return false;
				}
			}
		}
		
		//-----------------------------------------
		// Still here?  Send email then.
		//-----------------------------------------
		
		IPSText::getTextClass( 'email' )->getTemplate( "error_log_notification" );

		IPSText::getTextClass( 'email' )->buildMessage( array( 
																'CODE'			=> $code,
																'MESSAGE'		=> $message,
																'VIEWER'		=> $this->member->getProperty('member_id') ? $this->member->getProperty('members_display_name') : $this->lang->words['global_guestname'],
																'IP_ADDRESS'	=> $this->member->ip_address,
														)		);

		IPSText::getTextClass( 'email' )->to		= $this->settings['email_in'];
		IPSText::getTextClass( 'email' )->from		= $this->settings['email_out'];
		IPSText::getTextClass( 'email' )->sendMail();
		
		return true;
	}
	
	/**
	* Is FURL caching enabled?
	*
	* @return boolean
	*/
	public function getFurlCacheEnabled()
	{
		return self::$furlCacheEnabled;
	}
	
	/**
	* Enable or disable the FURL cache:
	* 
	* @param $enabled boolean Enabled? True/False
	*/
	public function setFurlCacheEnabled($enabled = true)
	{
		// Enable:
		self::$furlCacheEnabled = $enabled;
	}
	
	/**
	* Get cached FURL:
	* 
	* @param $key string FURL key
	* @see buildUrl
	* @return string Cached FURL
	*/
	public function getCachedFurl($key)
	{
		// Return the value if the cache is enabled, and the key exists:
		if( self::$furlCacheEnabled && isset( self::$furlCache[ $key ] ) )
		{
			return self::$furlCache[$key];
		}
		
		return null;
	}
	
	/**
	* Set cached FURL:
	* 
	* @param $key string FURL key
	* @param 
	* @see buildUrl
	*/
	public function setCachedFurl($key, $value)
	{
		// Don't do anything if caching is disabled:
		if(!self::$furlCacheEnabled)
		{
			return;
		}

		// Set the value:
		self::$furlCache[$key] = $value;
	}
	
	/*
	* Empty FURL cache:
	*/
	public function emptyFurlCache()
	{
		self::$furlCache = array();
	}
}

/**
 * Skin master class. Allows shared methods between skins
 */
class skinMaster
{
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
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
	
	public function __call( $funcName, $args )
	{
		$className = get_class( $this );
		
		if ( strstr( $className, 'skin_' ) )
		{
			preg_match( '#^skin_(.*)_(\d+?)$#', $className, $matches );
			$skinName = $matches[1];
			$skinID   = $matches[2];
			
			/* If we're here it's because the template bit doesn't exist, so... */
			return "<div class='templateError'>Error: Could not load template '$funcName' from group '$skinName'</div>";
		}
		
		/* Still here... */
		trigger_error( "Method $funcName does not exist in $className", E_USER_ERROR );
	}
		
}