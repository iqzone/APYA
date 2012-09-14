<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Installer output methods
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		1st December 2008
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * class output
 *
 * Class for managing skins, templates and printing output
 *
 * @author	Matt Mecham
 * @package	IP.Board
 * @version	3.0.0
 * @ignore
 */
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
	public $member;
	public $cache;
	/**#@-*/

	/**
	 * URLs array
	 *
	 * @access	public
	 * @var		array
	 */
	public $urls = array();

	/**
	 * Template
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $template		= '';

	/**
	 * Image url
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $imgUrl       = '';

	/**
	 * HTML to output
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_html        = '';

	/**
	 * Page title
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_title       = '';

	/**
	 * Error messages
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_errors		= array();

	/**
	 * Navigation information
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $__navigation = array();

	/**
	 * Currently in an error
	 *
	 * @access	protected
	 * @var		bool
	 */
	protected $_isError     = FALSE;

	/**
	 * Navigation information
	 *
	 * @access	pubic
	 * @var		array
	 */
	public    $_navigation  = array();

	/**
	 * Warnings
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_warnings    = array();

	/**
	 * Sequence data
	 *
	 * @access	public
	 * @var		array
	 */
	public $sequenceData = array();

	/**
	 * Current page
	 *
	 * @access	public
	 * @var		string
	 */
	public $currentPage  = '';

	/**
	 * Next page
	 *
	 * @access	public
	 * @var		string
	 */
	public $nextAction   = '';

	/**
	 * Hide continue button
	 *
	 * @access	public
	 * @var		bool
	 */
	public $_hideButton  = FALSE;

	/**
	 * Install steps
	 *
	 * @access	private
	 * @var		array
	 */
	private $_installStep = array();

	/**
	 * Internal array for messages
	 *
	 * @access	private
	 * @var		array
	 */
	private $_messages = array();

	private $_curVersion = 0;
	private $_curApp     = '';

   	/**
	 * Construct
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @param	bool	Whether to init
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $init=FALSE )
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();

		if ( $init === TRUE )
		{
			/* Load 'template' */
			require_once( IPS_ROOT_PATH . 'setup/templates/skin_setup.php' );/*noLibHook*/
			$this->template = new skin_setup( $registry );

			/* Images URL */
			$this->imageUrl = '../setup/' . PUBLIC_DIRECTORY . '/images';

			/* Fetch sequence data */
			require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
			$xml    = new classXML( IPSSetUp::charSet );
			$file   = ( IPS_IS_UPGRADER ) ? IPS_ROOT_PATH . 'setup/xml/upgrade_sequence.xml' : IPS_ROOT_PATH . 'setup/xml/sequence.xml';

			try
			{
				$xml->load( $file );

				foreach( $xml->fetchElements( 'action' ) as $xmlelement )
				{
					$data = $xml->fetchElementsFromRecord( $xmlelement );

					$_tmp[ $data['position'] ] = $data;

					ksort( $_tmp );

					foreach( $_tmp as $pos => $data )
					{
						$this->sequenceData[ $data['file'] ] = $data['menu'];
					}
				}
			}
			catch( Exception $error )
			{
				$this->addError( "Could not locate: " . $file );
			}


			/* Set up URLs */
			$this->settings['base_url']			= ( $this->settings['base_url'] ) ? $this->settings['base_url'] : IPSSetUp::getSavedData('install_url');
			$this->settings['public_dir']		= $this->settings['base_url'] . '/' . PUBLIC_DIRECTORY . '/';
			$this->settings['img_url_no_dir']	= $this->settings['base_url'] . '/' . PUBLIC_DIRECTORY . '/style_images/';

			/* Set Current Page */
			$this->currentPage = ( $this->request['section'] ) ? $this->request['section'] : 'index';

			if ( ! $this->sequenceData[ $this->currentPage ] )
			{
				$this->currentPage = 'index';
			}

			/* Set default next action */
			$_hit = 0;
			foreach( $this->sequenceData as $file => $text )
			{
				if ( $_hit )
				{
					$this->nextAction = $file;
					break;
				}

				if ( $file == $this->currentPage )
				{
					$_hit = 1;
				}
			}
			
			/* Build all skins array */
			if ( IPS_IS_UPGRADER )
			{
				/* For < 3.0.0 upgrades, they won't have this table, so check for it */
				if ( $this->DB->checkForTable( 'skin_collections' ) )
				{
					$this->DB->build( array( 'select' => '*',
											 'from'	  => 'skin_collections',
											 'order'  => 'set_id ASC' ) );
					$this->DB->execute();
					
					while( $_skinSets = $this->DB->fetch() )
					{
						$id = $_skinSets['set_id'];
						
						$this->allSkins[ $id ]					   = $_skinSets;
						$this->allSkins[ $id ]['_parentTree']      = unserialize( $_skinSets['set_parent_array'] );
						$this->allSkins[ $id ]['_childTree']       = unserialize( $_skinSets['set_child_array'] );
						$this->allSkins[ $id ]['_userAgents']      = unserialize( $_skinSets['set_locked_uagent'] );
						$this->allSkins[ $id ]['_cssGroupsArray']  = unserialize( $_skinSets['set_css_groups'] );
						$this->allSkins[ $id ]['_youCanUse']       = TRUE;
						$this->allSkins[ $id ]['_gatewayExclude']  = FALSE;
		
						/* Array groups */
						if ( is_array( $this->allSkins[ $id ]['_cssGroupsArray'] ) )
						{
							ksort( $this->allSkins[ $id ]['_cssGroupsArray'], SORT_NUMERIC );
						}
						else
						{
							$this->allSkins[ $id ]['_cssGroupsArray'] = array();
						}
					}
				}
			}
		}
	}
	
	/**
	 * Keeps upgrader happy
	 */
	public function buildSEOUrl( $url, $type, $title, $template )
	{
	
	}
	
	public function isLargeTouchDevice()
	{
		return false;
	}
	
	public function isSmallTouchDevice()
	{
		return false;
	}
	
	public function getReplacement()
	{
		
	}
	
	/**
	 * Add an message string
	 *
	 * @access	public
	 * @param	string	Message
	 * @return	@e void
	 */
	public function addMessage( $string )
	{
		$this->_messages[] = $string;
	}

	/**
	 * Add an error string
	 *
	 * @access	public
	 * @param	string	Error
	 * @return	@e void
	 */
	public function addError( $string )
	{
		$this->_errors[] = $string;
	}

	/**
	 * Add a warning string
	 *
	 * @access	public
	 * @param	string	Warning
	 * @return	@e void
	 */
	public function addWarning( $string )
	{
		$this->_warnings[] = $string;
	}

	/**
	 * Fetch errors
	 *
	 * @access	public
	 * @return	array 	Errors
	 */
	public function fetchErrors()
	{
		return $this->_errors;
	}

	/**
	 * Fetch warnings
	 *
	 * @access	public
	 * @return	array 	Warnings
	 */
	public function fetchWarnings()
	{
		return $this->_warnings;
	}

	/**
	 * Add content
	 *
	 * @access	public
	 * @param	string		content to add
	 * @param	boolean		Prepend isntead of append
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
	 * Set the current version and app
	 *
	 * @access	public
	 * @param	string		Current Human version
	 * @param	string		App key
	 * @return	@e void
	 */
	public function setVersionAndApp( $version, $app )
	{
		$this->_curVersion = $version;
		$this->_curApp     = $app;
	}

	/**
	 * Set the current install step
	 *
	 * @access	public
	 * @param	mixed		Current step
	 * @param	int			Total steps
	 * @return	@e void
	 */
	public function setInstallStep( $current, $total )
	{
		$this->_installStep = array( $current, $total );
	}

	/**
	 * Set the hide button value
	 *
	 * @access	public
	 * @param	boolean		TRUE = hide button, FALSE = show button
	 * @return	@e void
	 */
	public function setHideButton( $hide=FALSE )
	{
		$this->_hideButton = $hide;
	}

	/**
	 * Set the next action value
	 *
	 * @access	public
	 * @param	string		action URL
	 * @return	@e void
	 */
	public function setNextAction( $url )
	{
		$this->nextAction = $url;
	}

	/**
	 * Set the title of the document
	 *
	 * @access	public
	 * @param	string	Page title
	 * @return	@e void
	 */
	public function setTitle( $title )
	{
		$this->_title = $title;
	}

	/**
	 * Add navigational elements
	 *
	 * @access	public
	 * @param	string	Nav title
	 * @param	string	Nav URL
	 * @param	string	SEO title
	 * @param	string	SEO template
	 * @return	@e void
	 */
	public function addNavigation( $title, $url, $seoTitle='', $seoTemplate='' )
	{
		$this->_navigation[] = array( $title, $url, $seoTitle, $seoTemplate );
	}

	/**
	 * Set the is error flag
	 *
	 * @access	public
	 * @param	bool	Error yes/no
	 * @return	@e void
	 */
	public function setError( $boolean )
	{
		$this->_isError = $boolean;
	}

	/**
	 * Wrapper to return template function
	 *
	 * @access	public
	 * @return	object
	 */
	public function template()
	{
		return $this->template;
	}
	
	/**
	 * Replace IPS tags
	 * Converts over <#IMG_DIR#>, etc
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function replaceMacros( $text )
	{
		//-----------------------------------------
		// General replacements
		//-----------------------------------------
		
		$text = str_replace( "<#IMG_DIR#>"			, $this->skin['set_image_dir'], $text );
		$text = str_replace( "<#EMO_DIR#>"			, $this->skin['set_emo_dir']  , $text );
		$text = str_replace( "<% CHARSET %>"		, IPS_DOC_CHAR_SET            , $text );
		$text = str_replace( "{style_image_url}"	, $this->settings['img_url']  , $text );
		$text = str_replace( "{style_images_url}"	, $this->settings['img_url']  , $text );

		//-----------------------------------------
		// Fix up IPB image url
		//-----------------------------------------
		
		if ( $this->settings['ipb_img_url'] )
		{
			$text = preg_replace( "#img\s+?src=[\"']" . PUBLIC_DIRECTORY . "/style_(images|avatars|emoticons)(.+?)[\"'](.+?)?".">#is", "img src=\"" . $this->settings['ipb_img_url'] . PUBLIC_DIRECTORY . "/style_\\1\\2\"\\3>", $text );
		}

		
		return $text;
	}

    /**
	 * Main output function
	 *
	 * @access	public
	 * @param	boolean		TRUE - freeze data, FALSE, do not.
	 * @return	@e void	Nothin'
	 */
    public function sendOutput( $saveData=TRUE )
    {
        //-----------------------------------------
        // INIT
        //-----------------------------------------

		$_hit = 0;

		/* Options */
		$options['savedData']  = ( $saveData === TRUE ) ? IPSSetUp::freezeSavedData() : '';
		$options['hideButton'] = ( $this->_hideButton === TRUE ) ? TRUE : FALSE;
		$options['progress']   = array();

		/* Sequence progress */
		foreach( $this->sequenceData as $key => $page )
		{
			if ( $key == $this->currentPage )
			{
				$options['progress'][] = array( 'step_doing', $page );
				$_hit = 1;
			}
			else if( $_hit )
			{
				$options['progress'][] = array( 'step_notdone', $page );
			}
			else
			{
				$options['progress'][] = array( 'step_done', $page );
			}
		}

		//-----------------------------------------
		// Header
		//-----------------------------------------

		if ( isset( $_SERVER['SERVER_PROTOCOL'] ) AND strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) )
		{
			header("HTTP/1.0 200 OK" );
		}
		else
		{
			header("HTTP/1.1 200 OK" );
		}
		
		header( "Content-type: text/html;charset={$this->settings['gb_char_set']}" );
		header( "Cache-Control: no-cache, must-revalidate, max-age=0" );
		header( "Expires: 0" );
		header( "Pragma: no-cache" );

		$template = $this->template->globalTemplate( $this->_title, $this->_html, $options, $this->_errors, $this->_warnings, $this->_messages, $this->_installStep, $this->_curVersion, $this->_curApp );

		print $template;

        exit;
    }

    /**
	 * Print a redirect screen
	 * Wrapper function, really
	 *
	 * @access	public
	 * @param	string		Text to display on the redirect screen
	 * @param	string		URL to direct to
	 * @param	string		SEO Title
	 * @return	string		HTML to browser and exits
	 */
    public function redirectScreen( $text="", $url="", $seoTitle="", $seoTemplate='' )
    {
    	/* Use new inline notifications */
    	if ( ! defined('IPS_FORCE_HTML_REDIRECT') OR ! IPS_FORCE_HTML_REDIRECT )
    	{
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
	 * Immediate redirect
	 *
	 * @access	public
	 * @param	string		URL to redirect to
	 * @param	string		SEO Title
	 * @return	mixed
	 */
	public function silentRedirect( $url, $seoTitle='' )
	{
		return $this->outputFormatClass->silentRedirect( $url, $seoTitle );
	}

	/**
	 * Add content to the document <head>
	 *
	 * @access	public
	 * @param	string		Type of data to add: css, js, raw, rss, rsd, etc
	 * @param	string		Data to add
	 * @return	@e void
	 */
	public function addToDocumentHead( $type, $data )
	{
		$this->_documentHeadItems[ $type ][] = $data;
	}

	/**
	 * Process remap data
	 * For use with IN_DEV
	 *
	 * @access	public
	 * @return 	array 		Array of remap data
	 */
	public function buildRemapData()
	{
		$remapData = array();

		if ( is_file( DOC_IPS_ROOT_PATH . 'cache/skin_cache/masterMap.php' ) )
		{
			$REMAP = array();
			require( DOC_IPS_ROOT_PATH . 'cache/skin_cache/masterMap.php' );/*noLibHook*/

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
						$_skin = $this->_fetchSkinByKey( $REMAP['inDevDefault'] );
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
								'inDevDefault'	=> 'root'
							);
		}

		return $remapData;
	}

	/**
	 * Fetch a skin set via a key
	 *
	 * @access	private
	 * @param	string		Skin set key
	 * @return	array 		Array of skin data
	 */
	public function _fetchSkinByKey( $key )
	{
		if ( ! is_array( $this->allSkins ) )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'skin_collections' ) );
			$this->DB->execute();

			while( $skin = $this->DB->fetch() )
			{
				$this->allSkins[ $skin['set_id'] ] = $skin;
			}
		}

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
	 * Dummy method to prevent error in recache routines
	 * 
	 * @return	void
	 */
 	public function getTemplate( $file )
 	{
 		return null;
 	}
}