<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Admin Output Library
 * Last Updated: $LastChangedDate: 2012-05-29 10:44:35 -0400 (Tue, 29 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10809 $
 *
 */

class adminOutput extends output
{
	/**
	 * Output started
	 *
	 * @var		boolean
	 */
	protected $_IS_PRINTED;

	/**
	 * Global ACP template
	 *
	 * @var		object
	 */
	public $global_template = '';

	/**#@+
	 * HTML variables
	 *
	 * @var		string
	 */
	public $html = '';
	public $html_main       = '';
	public $body_extra      = '';
	public $cm_output		= '';
	/**#@-*/

	/**#@+
	 * Navigation array entries
	 *
	 * @var		array
	 */
	public $extra_nav = array();
	public $nav       = array();
	public $core_nav  = array();
	/**#@-*/

	/**
	 * Do not build nav, we will do manually
	 *
	 * @var		bool
	 */
	public $ignoreCoreNav	= false;

	/**
	 * Page titles
	 *
	 * @var		array
	 */
	public $extra_title = array();

	/**#@+
	 * Global messages
	 *
	 * @var		string
	 */
	public $global_message;
	public $persistent_message	= 0;
	public $global_error;
	/**#@-*/
	
	/**
	 * Any extra HTML to stick in the sidebar
	 *
	 * @var		string
	 */
	public $sidebar_extra     = '';
	
	/**
	 * Valid tab keys
	 *
	 * @var		array
	 */
	protected $tabKeys	= array( 'core', 'forums', 'members', 'lookfeel', 'support', 'reports', 'other' );

	/**
	 * Constructor
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		parent::__construct( $registry, TRUE );
		
		$_app = ( $this->request['app'] ) ? $this->request['app'] : IPS_APP_COMPONENT;
		
		/* Update paths and such */
		$this->settings['base_url']		= $this->settings['_original_base_url'];
		$this->settings['public_url']   = $this->settings['_original_base_url'] . '/index.php?';
		$this->settings['public_dir']	= $this->settings['_original_base_url'] . '/' . PUBLIC_DIRECTORY . '/';
	
		$this->settings['base_acp_url']	= $this->settings['base_url'] . '/' . CP_DIRECTORY;
		$this->settings['skin_acp_url']	= $this->settings['base_url'] . '/' . CP_DIRECTORY . "/skin_cp";
		$this->settings['skin_app_url']	= $this->settings['skin_acp_url'] ;
		$this->settings['js_main_url' ]	= $this->settings['base_url'] . '/' . CP_DIRECTORY . '/js/';

		$this->settings['js_app_url']	= $this->settings['base_url'] . '/' . CP_DIRECTORY . '/' . IPSLib::getAppFolder( $_app ) . '/' . $_app . '/js/';

		if ( ipsRegistry::$request['app'] )
		{
			$this->settings['skin_app_url']	= $this->settings['base_url'] . '/' . CP_DIRECTORY . '/' . IPSLib::getAppFolder( $_app ) . '/' . $_app . "/skin_cp/";
		}

		/* Update base URL */
		if ( $this->member->session_type == 'cookie' )
		{
			$this->settings['base_url']	= $this->settings['base_url'] . '/' . CP_DIRECTORY . '/index.php?';

		}
		else
		{
			$this->settings['base_url']	= $this->settings['base_url'] . '/' . CP_DIRECTORY . '/index.php?adsess=' . $this->request['adsess'] . '&amp;';
		}

		$this->settings['_base_url']	= $this->settings['base_url'];

		$this->settings['base_url'] =  $this->settings['base_url'] . 'app=' . IPS_APP_COMPONENT . '&amp;';

		$this->settings['extraJsModules']	= '';
	}
	
	/**
	 * Set a global message to be used
	 *
	 * @param	string	$message	Message to show
	 * @param	int		$persistent	Whether to show message inline (1) or not (0)
	 * @return	@e void
	 */
	public function setMessage( $message, $persistent=0 )
	{
		$this->global_message		= $message;
		$this->persistent_message	= intval($persistent);
	}

	/**
	 * Load a root (non-application) template
	 *
	 * @param	string		Template name
	 * @return	object
	 */
	public function loadRootTemplate( $template )
	{
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'skin_cp/' . $template . '.php', $template );
		return new $classToLoad( ipsRegistry::instance() );
	}

	/**
	 * Load a template file
	 *
	 * @param	string		Template name
	 * @param	string		Application [defaults to current application]
	 * @return	object
	 */
	public function loadTemplate( $template, $app='' )
	{
		$app = $app ? $app : IPS_APP_COMPONENT;

		/* Skin file exists? */
		if ( is_file( IPSLib::getAppDir( $app ) . "/skin_cp/".$template.".php" ) )
		{
			$_pre_load = IPSDebug::getMemoryDebugFlag();
			
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( $app ) . '/skin_cp/'.$template.'.php', $template, $app );
			
			IPSDebug::setMemoryDebugFlag( "CORE: Template Loaded ({$classToLoad})", $_pre_load );

			return new $classToLoad( $this->registry );
		}
		else
		{
			$this->showError( sprintf( $this->lang->words['notemplatefiletoload'],  $template ), 4100, true );
		}
	}

	/**
	 * Show a download dialog box
	 *
	 * @param	string		Data for the download
	 * @param	string		Filename
	 * @param	string		Mime-type to send to browser
	 * @param	boolean		Compress the download
	 * @return	@e void
	 */
	public function showDownload( $data, $name, $type="unknown/unknown", $compress=true )
	{
		if ( $compress and @function_exists('gzencode') )
		{
			$name .= '.gz';
			//$type = 'application/x-gzip';
		}
		else
		{
			$compress = false;
		}

		header('Content-Type: '.$type);
		header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Content-Disposition: attachment; filename="' . $name . '"');

		if ( ! $compress )
		{
			@header('Content-Length: ' . strlen($data) );
		}

		@header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		@header('Pragma: public');

		if ( $compress )
		{
			print gzencode($data);
		}
		else
		{
			print $data;
		}

		exit();
	}

	/**
	 * Print a popup window - wraps HTML page in minimalized output
	 *
	 * @return	@e void
	 */
	public function printPopupWindow()
	{
		$this->_sendOutputSetUp( 'popup' );

		//-----------------------------------------
		// Figure out title...
		//-----------------------------------------
		$this->html_title = "IP.Board:";

		if ( ipsRegistry::$current_application )
		{
			$this->html_title .= " " . IPSLIb::getAppTitle( ipsRegistry::$current_application );

			if ( ipsRegistry::$current_module )
			{
				$this->html_title .= " &gt; " . ( isset($this->lang->words[ 'module__' . ipsRegistry::$current_application . '_' . ipsRegistry::$current_module ]) ? $this->lang->words[ 'module__' . ipsRegistry::$current_application . '_' . ipsRegistry::$current_module ] : ipsRegistry::$modules_by_section[ ipsRegistry::$current_application ][ ipsRegistry::$current_module ]['sys_module_title'] );
			}
		}

		$html = str_replace( '<%CONTENT%>'	, $this->html				, $this->global_template->global_main_popup_wrapper() );
		$html = str_replace( "<%TITLE%>"	, $this->html_title			, $html );
		$html = str_replace( "<%BODYEXTRA%>", ' ' . $this->body_extra	, $html );
		print $html;

		exit();
	}

	/**
	 * Redirect user to another page
	 *
	 * @param	string		Page to redirect to
	 * @param	string		Text to show during redirect
	 * @param	integer		Number of seconds between page loads
	 * @param	boolean		Allow a populated $this->registry to stop the redirect with the option to continue
	 * @param	boolean		Force full redirect
	 * @return	@e void
	 * @deprecated	Now just redirects to silentRedirectWithMessage
	 */
	public function redirect( $url, $text, $time=2, $allowErrorToHalt=FALSE, $forcePage=FALSE )
	{
		/* Fix board URL if missing */
		if( !$url )
		{
			$url	= $this->settings['_base_url'];
		}

		if ( strpos( $url, $this->settings['_original_base_url'] ) === false )
		{
			$url	= $this->settings['_original_base_url'] . '&' . $url;
		}
		
		/* If message is particularly long, use older-style redirect screen */
		if( strlen($text) > 512 OR $forcePage )
		{
			$this->html			= $this->global_template->temporaryRedirect( $url, $text, $time );
			$this->html_main	.= $this->global_template->global_frame_wrapper();
			$this->sendOutput();
			return;
		}

		/* Otherwise use new inline message and fast redirect */
		$this->global_message	= $text;
		$this->silentRedirectWithMessage( $url, $allowErrorToHalt );
	}

	/**
	 * Redirect user to another page with no intermediary screen
	 *
	 * @param	string		Url to send the user to
	 * @param	boolean		Allow a populated $this->registry to stop the redirect with the option to continue
	 * @return	@e void
	 */
	public function silentRedirectWithMessage($url, $allowErrorToHalt=false)
	{
		/* Check for an error message */
		if ( $allowErrorToHalt !== FALSE AND $this->registry->output->global_error )
		{
			$this->html_title	= $this->lang->words['redirect_halt_title'];
			$this->html_main	= $this->global_template->global_redirect_halt( $url );
			
			// This was in redirect() previously, and is different from this method, so I copied it here for reference
			//$this->html		= $this->global_template->global_redirect_halt( $url );
			//$this->html_main	= $this->global_template->global_frame_wrapper();
	
			$this->sendOutput();
			exit();
		}
		
		/* Check for a redirect message */
		$extra = "";

		if ( $this->global_message )
		{
			$extra = '&messageinabottleacp='.urlencode( str_replace( array( "\r", "\n" ), ' ', $this->global_message ) ) . '&messagepersistent=' . intval($this->persistent_message);
		}

		$url = str_replace( "&amp;", "&", $url ) . $extra;

		/* Do the redirect */
		$this->silentRedirect( $url );
	}

	/**
	 * Initialize a multi-redirect.  Creates an iframe that continuously adds the last status to the content of the iframe.
	 *
	 * @param	string		Url to initialize
	 * @param	string		Text to initialize with
	 * @param	boolean		Add to the text
	 * @return	@e void
	 */
	public function multipleRedirectInit( $url )
	{
		$this->_sendOutputSetUp( 'redirect' );

		$this->html .= "<iframe src='{$url}' scrolling='auto' border='0' frameborder='0' width='100%' height='400'></iframe>";

		$this->html_main .= $this->global_template->global_frame_wrapper();
		$this->sendOutput();
	}

	/**
	 * Hit a multi-redirect.  Uses AJAX or redirect page appropriately
	 *
	 * @param	string		Url to initialize
	 * @param	string		Text to initialize with
	 * @param	boolean		Add to the text
	 * @return	@e void
	 */
	public function multipleRedirectHit( $url, $text='', $time=1 )
	{
		if ( !is_numeric( $time ) or $time <= 0 )
		{
			$time = 1;
		}
	
		print $this->global_template->global_redirect_hit( $url, $text, $time );
		exit();
	}

	/**
	 * Finish a multi-redirect session
	 *
	 * @param	string		Text to display
	 * @return	@e void
	 */
	public function multipleRedirectFinish($text='Completed!')
	{
		print $this->global_template->global_redirect_done( $text );
		exit();
	}

	/**
	 * Display an error page
	 *
	 * @param	string		Text to display
	 * @param	integer		Error code
	 * @param	boolean		Log error message
	 * @param   string		Extra log data
	 * @param	integer		Header code to send (ignored for ACP)
	 * @return	@e void
	 */
	public function showError( $message, $code=0, $logError=FALSE, $logExtra='', $header=500 )
	{
		$this->_sendOutputSetUp( 'error' );

		$message	= $message ? $message : 'no_permission';
		$message	= ( isset($this->lang->words[ $message ]) ) ? $this->lang->words[ $message ] : $message;
		
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
					if( $level >= $this->settings['error_log_level'] - 1 )
					{
						$logError = true;
					}
				}
			}
    	}

		//-----------------------------------------
    	// Log the error, if needed
    	//-----------------------------------------

		if( $logError )
		{
			$_logMessage	= $message;
			
			if( $logExtra )
			{
				$_logMessage	.= ' (' . $logExtra . ')';
			}
			
			$this->logErrorMessage( $_logMessage, $code );
		}

		//-----------------------------------------
    	// Send notification if needed
    	//-----------------------------------------

    	$this->sendErrorNotification( $message, $code );

		//-----------------------------------------
    	// Finally, output
    	//-----------------------------------------

		$this->html_main	= $this->global_template->global_frame_wrapper();
		$this->html			= $this->global_template->system_error( $message, $code );
		$this->sendOutput();
	}

	/**
	 * Output the HTML to the browser
	 *
	 * @param	bool	Return finished output instead of printing
	 * @return	@e void
	 */
	public function sendOutput( $return=false )
	{
		$this->_sendOutputSetUp( 'normal' );

		//---------------------------------------
		// INIT
		//-----------------------------------------

		$clean_module  = IPSText::alphanumericalClean( ipsRegistry::$current_module );
		$navigation    = array();
		$_seen_nav     = array();
		$_last_nav     = '';
		$no_wrapper    = FALSE;

		//-----------------------------------------
		// Inline pop-up?
		//-----------------------------------------

		if ( ipsRegistry::$request['_popup'] )
		{
			$this->printPopupWindow();
			exit();
		}

		//-----------------------------------------
		// Debug?
		//-----------------------------------------

		if ( $this->DB->obj['debug'] )
        {
        	flush();
        	print "<html><head><title>SQL Debugger</title><body bgcolor='white'><style type='text/css'> TABLE, TD, TR, BODY { font-family: verdana,arial, sans-serif;color:black;font-size:11px }</style>";
        	print "<h1 align='center'>SQL Total Time: {$this->DB->sql_time} for {$this->DB->query_cnt} queries</h1><br />".$this->DB->debug_html;
        	print "<br /><div align='center'><strong>Total SQL Time: {$this->DB->sql_time}</div></body></html>";
        	exit();
        }

		//-----------------------------------------
		// Context sensitive stuff
		//-----------------------------------------

		if( !$this->cm_output )
		{
			$_file  = IPSLib::getAppDir( IPS_APP_COMPONENT ) . '/skin_cp/cp_skin_' . $clean_module . '_context_menu.php';
			
			if ( is_file( $_file ) )
			{
				$_class = IPSLib::loadLibrary( $_file, 'cp_skin_' . $clean_module . '_context_menu', IPS_APP_COMPONENT );
				
				$context_menu     = new $_class( $this->registry );

				$cm_function_full = ipsRegistry::$request['do'] ? 'context_menu__' . $clean_module.'__'.ipsRegistry::$request['section'].'__'.ipsRegistry::$request['do'] : 'context_menu__' . $clean_module.'__'.ipsRegistry::$request['section'];
				$cm_function      = 'context_menu__' . $clean_module.'__'.ipsRegistry::$request['section'];
				$cm_module		  = 'context_menu__' . $clean_module;

				if ( method_exists( $_class, $cm_function_full ) )
				{
					$this->cm_output = $context_menu->__wrap( $context_menu->$cm_function_full() );
				}
				else if ( method_exists( $_class, $cm_function ) )
				{
					$this->cm_output = $context_menu->__wrap( $context_menu->$cm_function() );
				}
				else if ( method_exists( $_class, $cm_module ) )
				{
					$this->cm_output = $context_menu->__wrap( $context_menu->$cm_module() );
				}
			}
		}
		
		//-----------------------------------------
		// Get tab order
		//-----------------------------------------
		
		$_tabOrder	= ipsRegistry::getClass('adminFunctions')->staffGetCookie( 'tabOrder' );
		
		if( !count($_tabOrder) OR !is_array($_tabOrder) )
		{
			$_tabOrder	= $this->tabKeys;
		}

		$gbl_sub_menu	= $this->_buildGlobalSubMenu();
		$html			= str_replace( '<%CONTENT%>', $this->html_main, $this->global_template->global_main_wrapper( IPS_DOC_CHAR_SET, $this->_css, $gbl_sub_menu, $_tabOrder ) );

		//------------------------------------------------
		// Message in a bottle?
		//------------------------------------------------
	
		$message = '';
		
		if ( $this->global_error )
		{
			$message = $this->global_template->global_error_message();
		}
		
		if ( $this->global_message )
		{
			$message .= ( $message ) ? '<br />' . $this->global_template->global_message() : $this->global_template->global_message();
		}

		//-----------------------------------------
		// Figure out title...
		//-----------------------------------------

		$this->html_title = "IP.Board:";

		if ( ipsRegistry::$current_application )
		{
			$this->html_title .= " " . IPSLIb::getAppTitle( ipsRegistry::$current_application );

			if ( ipsRegistry::$current_module )
			{
				$this->html_title .= " &gt; " . ( isset($this->lang->words[ 'module__' . ipsRegistry::$current_application . '_' . ipsRegistry::$current_module ]) ? $this->lang->words[ 'module__' . ipsRegistry::$current_application . '_' . ipsRegistry::$current_module ] : ipsRegistry::$modules_by_section[ ipsRegistry::$current_application ][ ipsRegistry::$current_module ]['sys_module_title'] );
			}
		}

		if( count($this->extra_title) )
		{
			$this->html_title .= " &gt; " . implode( ' &gt; ', $this->extra_title );
		}

		//-----------------------------------------
		// Got app menu cache?
		//-----------------------------------------

		if ( ! is_array( ipsRegistry::cache()->getCache('app_menu_cache') ) OR ! count( ipsRegistry::cache()->getCache('app_menu_cache') ) )
		{
			$this->cache->rebuildCache( 'app_menu_cache', 'global' );
		}

		//-----------------------------------------
		// Other tags...
		//-----------------------------------------

		// Can set the second one to none to hide left menu when no context nav is available
		$html = str_replace( "<%DISPLAY_SUB_MENU%>"   , $this->cm_output ? '' : 'none'   , $html );

		$html = str_replace( "<%TITLE%>"              , $this->html_title, $html );
		$html = str_replace( "<%SUBMENU%>"            , $this->_buildSubMenu()    , $html ); # Must be called first
		$html = str_replace( "<%MENU%>"               , $this->_buildMenu()        , $html );
		$html = str_replace( "<%SIDEBAR_EXTRA%>"      , $this->sidebar_extra       , $html );
		$html = str_replace( "<%CONTEXT_MENU%>"       , $this->cm_output                 , $html );
		$html = str_replace( "<%SECTIONCONTENT%>"     , $this->html      , $html );
		# This has to be called after the menu has been set so that query_string is set correctly

		$html = str_replace( "<%MSG%>"                , $message                   , $html );

		//-----------------------------------------
		// Fix up navigation
		//-----------------------------------------

		if ( count( $this->core_nav ) )
		{
			foreach( $this->core_nav as $data )
			{
				if ( isset( $_seen_nav[ $data[1] ] ) )
				{
					continue;
				}
				else
				{
					$_seen_nav[ $data[1] ] = 1;
				}

				$_nav = ( isset( $_last_nav['nav'] ) ) ? $_last_nav['nav'] . ' &gt; ' . $data[1] : $data[1];
				
				# Append last nav...
				$_last_nav = array( 'url'   => $data[0],
								 	'title' => $data[1],
								    'nav'   => $_nav );
				if ( $data[0] )
				{
					$navigation[] = "<a href='" . $data[0] . "'>" . $data[1] . "</a>";
				}
				else
				{
					$navigation[] = $data[1];
				}
			}
		}

		if ( count( $this->extra_nav ) )
		{
			foreach( $this->extra_nav as $data )
			{
				if ( isset( $_seen_nav[ $data[1] ] ) )
				{
					continue;
				}
				else
				{
					$_seen_nav[ $data[1] ] = 1;
				}

				$_nav      = ( $_last_nav['nav'] ) ? $_last_nav['nav'] . ' &gt; ' . $data[1] : $data[1];

				# Append last nav...
				$_last_nav = array( 'url'   => $data[0],
								 	'title' => $data[1],
								    'nav'   => $_nav );

				if ( $data[0] )
				{
					$navigation[] = "<a href='" . $data[0] . "'>" . $data[1] . "</a>";
				}
				else
				{
					$navigation[] = $data[1];
				}
			}
		}

		//------------------------------------------------
		// Navigation?
		//------------------------------------------------

		if ( count($navigation) > 0 )
		{
			$html = str_replace( "<%NAV%>", $this->global_template->wrap_nav( "<li>" . implode( "&nbsp; &gt; &nbsp;</li><li>", $navigation ) . "</li>" ), $html );
		}
		else
		{
			$html = str_replace( "<%NAV%>", '', $html );
		}

		//-----------------------------------------
		// Last thing, the nav element...
		//-----------------------------------------

		$html = str_replace( "<%PAGE_NAV%>", $_last_nav['title'], $html );

		$query_html = "";

		//-----------------------------------------
		// Show SQL queries
		//-----------------------------------------

		if ( IN_DEV and count( $this->DB->obj['cached_queries']) )
		{
			$queries = "";

			foreach( $this->DB->obj['cached_queries'] as $q )
			{
				$queries .= "<div style='padding:6px; border-bottom:1px solid #000'>" . htmlspecialchars($q) . '</div>';
			}

			$query_html .= $this->global_template->global_query_output($queries);

			/* Included Files */
			if ( function_exists( 'get_included_files' ) )
			{
				$__files = get_included_files();

				$files		= '';
				
				foreach( $__files as $__f )
				{
					$files .= "<strong>{$__f}</strong><br />";
				}

				$query_html .= $this->global_template->global_if_output( count($__files), $files );
			}
		}

		//-----------------------------------------
		// Memory usage
		//-----------------------------------------

		if ( IPS_MEMORY_DEBUG_MODE AND defined( 'IPS_MEMORY_START' ) AND IN_DEV )
		{
			if ( is_array( IPSDebug::$memory_debug ) )
			{
				$memory	= '';
				$_c		= 0;

				foreach( IPSDebug::$memory_debug as $usage )
				{
					$_c++;

					if ( $usage[1] > 500 * 1024 )
					{
						$_col = "color:#D00000";
					}
					else if ( $usage[1] < 10 * 1024 )
					{
						$_col = "color:darkgreen";
					}
					else if ( $usage[1] < 100 * 1024 )
					{
						$_col = "color:darkorange";
					}

					$memory .= "<tr><td width='60%' style='{$_col}' align='left'>{$usage[0]}</td><td style='{$_col}' align='left'><strong>" . IPSLib::sizeFormat( $usage[1] ) . "</strong></td></tr>";
				}
			}

			$_used		= memory_get_usage() - IPS_MEMORY_START;
			$peak_used	= memory_get_peak_usage() - IPS_MEMORY_START;
			
			$query_html .= $this->global_template->global_memory_output( $memory, IPSLib::sizeFormat( $_used ), IPSLib::sizeFormat( $peak_used ) );
		}

		$html = str_replace( "<%QUERIES%>"            , $query_html                , $html );

		//-----------------------------------------
		// Got BODY EXTRA?
		//-----------------------------------------

		if ( $this->body_extra )
		{
			$html = str_replace( "<body", "<body ".$this->body_extra, $html );
		}

		//-----------------------------------------
		// Emoticons fix
		//-----------------------------------------
		
		$html = str_replace( "<#EMO_DIR#>"			, 'default'  , $html );
		
		//-----------------------------------------
		// Gzip?
		//-----------------------------------------

		if ( IPB_ACP_USE_GZIP )
		{
        	$buffer = "";

	        if( count( ob_list_handlers() ) )
	        {
        		$buffer = ob_get_contents();
        		ob_end_clean();
    		}

        	ob_start('ob_gzhandler');
        	print $buffer;
    	}

    	@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		@header("Cache-Control: no-cache, must-revalidate");
		@header("Pragma: no-cache");
		@header("Content-type: text/html; charset=" . IPS_DOC_CHAR_SET );

		//-----------------------------------------
		// OUTPUT
		//-----------------------------------------

		if( $return )
		{
			$this->_IS_PRINTED = 1;
			
			return $html;
		}

    	print $html;

		$this->_IS_PRINTED = 1;

    	exit();
	}

	/**
	 * Global set up stuff
	 * Sorts the JS module array, calls initiate on the output engine, etc
	 *
	 * @param	string		Type of output (normal/popup/redirect/error)
	 * @return	@e void
	 */
	protected function _sendOutputSetUp( $type )
	{
		//----------------------------------------
		// Sort JS Modules
		//----------------------------------------

		arsort( $this->_jsLoader, SORT_NUMERIC );

		foreach( $this->_jsLoader as $k => $v )
		{
			$this->settings['extraJsModules'] .= ',' . $k;
		}

		$this->settings['extraJsModules']	= trim( $this->settings['extraJsModules'], ',' );
	}

	/**
	 * Build the primary menu
	 *
	 * @return	string		Menu HTML
	 */
	protected function _buildMenu()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$html          = '';
		$tabs          = array();
		$children      = array();
		$clean_module  = IPSText::alphanumericalClean( ipsRegistry::$current_module );
		$app           = ipsRegistry::$current_application;
		$link_array    = array();

		/* Fetch fake apps */
		$fakeApps  = $this->registry->output->fetchFakeApps();
		$inFakeApp = FALSE;
		$fakeApp   = '';

		//-----------------------------------------
		// In a fake app?
		//-----------------------------------------

		foreach( $fakeApps as $_app => $_fdata )
		{
			foreach( $_fdata as $__fdata )
			{
				if ( ipsRegistry::$current_application == $__fdata['app'] AND $__fdata['module'] == ipsRegistry::$current_module )
				{
					$inFakeApp = TRUE;
					$fakeApp   = $_app;
					break 2;
				}
			}
		}

		//-----------------------------------------
		// Apps we're going to look through
		//-----------------------------------------
		
		$_appsToCheck	= array();
		
		if( $inFakeApp )
		{
			foreach( $fakeApps[ $fakeApp ] as $_fdata )
			{
				$_appsToCheck[ $_fdata['app'] ]	= $_fdata['app'];
			}
		}
		else
		{
			$_appsToCheck[ ipsRegistry::$current_application ]	= ipsRegistry::$current_application;
		}

		//-----------------------------------------
		// Loop through all menus...
		//-----------------------------------------

		foreach( $_appsToCheck as $_checkApp )
		{
			if ( is_array( ipsRegistry::$modules[ $_checkApp ] ) )
			{
				foreach( ipsRegistry::$modules[ $_checkApp ] as $data )
				{
					# Skip non-ACP module
					if ( ! $data['sys_module_admin'] )
					{
						continue;
					}
	
					$skip = TRUE;
	
					/* Fake app content? If so.. remove.. */
					foreach( $fakeApps as $_app => $_fdata )
					{
						if( $fakeApp AND $_app != $fakeApp )
						{
							continue;
						}

						foreach( $_fdata as $__fdata )
						{
							/* If the fake app matches the menu we're gonna show... */
							if ( $__fdata['app'] == $data['sys_module_application'] AND $__fdata['module'] == $data['sys_module_key'] )
							{
								$skip = ( $inFakeApp === TRUE AND in_array( $__fdata['app'], $_appsToCheck ) ) ? FALSE : TRUE;
								break 2;
							}
							else
							{
								/* If we're in a fake app, skip non fake apps */
								$skip = ( $inFakeApp !== TRUE ) ? FALSE : TRUE;
							}
						}
					}

					if ( $skip === TRUE )
					{
						continue;
					}
					
					$_tab_title = $this->lang->words[ 'module__' . $_checkApp . '_' . $data['sys_module_key'] ] ? $this->lang->words[ 'module__' . $_checkApp . '_' . $data['sys_module_key'] ] : $data['sys_module_title'];
					$_tab_key   = $data['sys_module_key'];
					
					$tabs[ $_checkApp ]['items'][ $_tab_key ]	= array( 'tab_title'	=> $_tab_title,
																		 'tab_key'		=> $_tab_key );
					$tabs[ $_checkApp ]['data']					= $data;
				}
			}
		}

		//-----------------------------------------
		// Build main menu
		//-----------------------------------------

		foreach( $tabs as $dir_name => $_data )
		{
			$_main_key   = isset( ipsRegistry::$applications[ $tabs[ $dir_name ]['data']['sys_module_application'] ]['app_directory'] ) ? ipsRegistry::$applications[ $tabs[ $dir_name ]['data']['sys_module_application'] ]['app_directory'] : '';

			//-----------------------------------------
			// Got access for this application?
			//-----------------------------------------

			if ( ipsRegistry::getClass('class_permissions')->checkForAppAccess( $_main_key ) !== TRUE )
			{
				continue;
			}

			//-----------------------------------------
			// Only show this menu block, now.
			//-----------------------------------------

			if ( !in_array( $_main_key, $_appsToCheck ) )
			{
				continue;
			}

			//-----------------------------------------
			// Loop through...
			//-----------------------------------------

			foreach( $_data['items'] as $key => $data )
			{
				$title = $data['tab_title'];
				$url   = $this->settings['_base_url'] . 'app=' . $_main_key . '&amp;module=' . $data['tab_key'];

				//-----------------------------------------
				// Got access for this module?
				//-----------------------------------------

				ipsRegistry::getClass('class_permissions')->return = 1;

				if ( ipsRegistry::getClass('class_permissions')->checkForModuleAccess( $_main_key, $data['tab_key'] ) !== TRUE )
				{
					continue;
				}

				//-----------------------------------------
				// Set navigation
				//-----------------------------------------

				if ( $_main_key == ipsRegistry::$current_application AND $clean_module == $data['tab_key'] )
				{
					// Changed this to add to the beginning of the array instead of the end, seems
					// to work better in most cases...but will have to check more.

					if( !$this->ignoreCoreNav )
					{
						array_unshift( $this->core_nav, array( $this->settings['base_url'] . 'module=' . ipsRegistry::$current_module, $title ) );
					}
				}

				//-----------------------------------------
				// Continue
				//-----------------------------------------

				$link_array[ $_main_key ][ $data['tab_key'] ] = array( 'url'    => $url,
																	   'title'  => $title,
																	   'module' => $data['tab_key'] );
			}
		}

		$html .= $this->global_template->menu_cat_wrap( $link_array, $clean_module, $this->menu );

		//-----------------------------------------
		// OK... return
		//-----------------------------------------

		return $html;
	}

	/**
	 * Build the secondary menu
	 *
	 * @return	string		Menu HTML
	 */
	protected function _buildSubMenu()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$clean_module  = IPSText::alphanumericalClean( ipsRegistry::$current_module );
		$this->menu    = array();
		$_nav_main_done = 0;

		/* Fetch fake apps */
		$fakeApps  = $this->registry->output->fetchFakeApps();
		$inFakeApp = FALSE;
		$fakeApp   = '';

		//-----------------------------------------
		// In a fake app?
		//-----------------------------------------

		foreach( $fakeApps as $_app => $_fdata )
		{
			foreach( $_fdata as $__fdata )
			{
				if ( ipsRegistry::$current_application == $__fdata['app'] AND $__fdata['module'] == ipsRegistry::$current_module )
				{
					$fakeApp   = $_app;
					$inFakeApp = TRUE;
					break 2;
				}
			}
		}
		
		//-----------------------------------------
		// Apps we're going to look through
		//-----------------------------------------
		
		$_appsToCheck	= array();
		
		if( $inFakeApp )
		{
			foreach( $fakeApps[ $fakeApp ] as $_fdata )
			{
				$_appsToCheck[ $_fdata['app'] ]	= $_fdata['app'];
			}
		}
		else
		{
			$_appsToCheck[ ipsRegistry::$current_application ]	= ipsRegistry::$current_application;
		}

		//-----------------------------------------
		// Got a cache?
		//-----------------------------------------

		if ( IN_DEV )
		{
			ipsRegistry::cache()->updateCacheWithoutSaving( 'app_menu_cache', array() );
		}

		if ( ! is_array( ipsRegistry::cache()->getCache('app_menu_cache') ) OR ! count( ipsRegistry::cache()->getCache('app_menu_cache') ) )
		{
			$this->cache->rebuildCache( 'app_menu_cache', 'global' );
		}

		//-----------------------------------------
		// Get child XML tabs
		//-----------------------------------------

		if ( count($_appsToCheck) AND $clean_module )
		{
			//-----------------------------------------
			// Do stuff
			//-----------------------------------------

			foreach( ipsRegistry::cache()->getCache('app_menu_cache') as $app_dir => $data )
			{
				if ( ! ipsRegistry::$applications[ $app_dir ]['app_enabled'] )
				{
					continue;
				}

				/* Not in this app? */
				if ( !in_array( $app_dir, $_appsToCheck ) )
				{
					continue;
				}
				
				/* Load menu language file, if present */
				$this->lang->loadLanguageFile( array( 'admin_menulang' ), $app_dir );

				foreach( $data as $_current_module => $module_data )
				{
					$skip = TRUE;
					$__current_module = $_current_module;
					$_current_module  = preg_replace( '/^\d+?_(.*)$/', "\\1", $_current_module );
					
					/* Fix language abstraction for module title */
					$module_data['title'] = isset($this->lang->words[ 'module__' . $app_dir . '_' . $_current_module ]) ? $this->lang->words[ 'module__' . $app_dir . '_' . $_current_module ] : $module_data['title'];
					
					/* Fake app content? If so.. remove.. */
					foreach( $fakeApps as $_app => $_fdata )
					{
						foreach( $_fdata as $__fdata )
						{
							/* If the fake app matches the menu we're gonna show... */
							if ( $__fdata['app'] == $app_dir AND $__fdata['module'] == $_current_module )
							{
								if ( $inFakeApp === TRUE && $_app == $fakeApp )
								{
									$skip = FALSE;
								}
							}
							else
							{
								/* If we're in a fake app, skip non fake apps */
								if ( $inFakeApp !== TRUE )
								{
									$skip = FALSE;
								}
							}
						}
					}

					if ( $skip === TRUE )
					{
						continue;
					}

					if ( ( $app_dir == ipsRegistry::$request['app'] ) AND ! stristr( $this->settings['query_string_safe'], 'module=' ) )
					{
						$this->settings['query_string_safe'] =  $this->settings[ 'query_string_safe' ] . '&amp;module=' . $clean_module ;
					}

					foreach( $module_data['items'] as $id => $item )
					{
						//-----------------------------------------
						// Title
						//-----------------------------------------
						
						$item['title']	= ( $item['langkey'] AND $this->lang->words[ 'menu__' . $item['langkey'] ] ) ? $this->lang->words[ 'menu__' . $item['langkey'] ] : $item['title'];
						
						//-----------------------------------------
						// Permission mask?
						//-----------------------------------------

						if ( $item['rolekey'] )
						{
							ipsRegistry::getClass('class_permissions')->return = 1;

							if ( ipsRegistry::getClass('class_permissions')->checkPermission( $item['rolekey'], $app_dir, $_current_module ) !== TRUE )
							{
								continue;
							}
						}

						//-----------------------------------------
						// Force a module/section parameter into the input array
						//-----------------------------------------

						if ( ( $app_dir == ipsRegistry::$current_application ) AND ( ipsRegistry::$current_module == $item['module'] ) AND ! ipsRegistry::$request['section'] AND $item['section'] )
						{
							ipsRegistry::$request['section'] =  $item['section'] ;
						}

						//-----------------------------------------
						// Add to nav?
						//-----------------------------------------

						if ( $app_dir == ipsRegistry::$current_application AND ipsRegistry::$request['section'] AND ( ipsRegistry::$request['section'] == $item['section'] ) AND ( ipsRegistry::$current_module == $item['module'] ) )
						{
							//-----------------------------------------
							// Sure?
							//-----------------------------------------

							$_ok            = 1;
							$__sub_item_url = ( $item['url'] ) ? '&amp;' . $item['url'] : '';

							if ( ! $_nav_main_done )
							{
								if( !$this->ignoreCoreNav )
								{
									$this->core_nav[] = array( $this->settings['base_url'] . 'module=' . $_current_module . '&amp;section=' . $item['section'], $module_data['title'] );
								}

								$_nav_main_done   = 1;

								//-----------------------------------------
								// Sort out do param?
								//-----------------------------------------

								if ( $item['url'] AND ! isset( $_GET['do'] ) )
								{
									$_do = str_replace( "do=", "", $item['url'] );

									ipsRegistry::$request['do'] = $_do;

									if ( ! stristr( $this->settings['query_string_safe'], 'section=' ) )
									{
										$this->settings['query_string_safe'] = $this->settings['query_string_safe'] . '&amp;section=' . ipsRegistry::$request['section'];
									}

									$this->settings['query_string_safe'] = '&amp;do=' . $_do;
								}
							}

							if ( $item['url'] )
							{
								/* Reset */
								$_ok = 0;

								/* Trying something a little different with the nav */
								$_url = explode( '=', $item['url'] );

								/* Now we're first going to check for an exact do match */
								$_ok = ( $_url[1] == ipsRegistry::$request['do'] );

								/* No?  Check the Query string then */
								if( ! $_ok )
								{
									$_n = str_replace( '&amp;', '&', strtolower( $item['url'] ) );
									$_h = str_replace( '&amp;', '&', strtolower( my_getenv('QUERY_STRING') ) );

									if ( strstr( $_h, $_n ) )
									{
										$_ok = 1;
									}
								}
							}

							if ( !$this->ignoreCoreNav AND $_ok )
							{
								$this->core_nav[] = array( $this->settings['base_url'] . 'module=' . $_current_module . '&amp;section=' . $item['section'] . $__sub_item_url, $item['title'] );
							}
						}

						//-----------------------------------------
						// Continue...
						//-----------------------------------------

						if ( $item['title'] AND $item['section'] )
						{
							$this->menu[ $app_dir ][ $__current_module ]['items'][]	= array( 'title'	=> $item['title'],
																							 'module'	=> $_current_module,
																							 'section'	=> $item['section'],
																							 'url'		=> $item['url'],
																							 'redirect'	=> $item['redirect']
																							);
							
							
							$this->menu[ $app_dir ][ $__current_module ]['title']	= ( count($this->menu[ $app_dir ][ $__current_module ]['items']) > 1 ) ? $module_data['title'] : ( isset($this->lang->words[ 'module__' . $app_dir . '_' . $_current_module ]) ? $this->lang->words[ 'module__' . $app_dir . '_' . $_current_module ] : $item['title'] );
						}
					}
				}
			}
		}
		#echo "<pre>";print_r($this->menu);exit;
		if ( isset( $this->menu ) && count( $this->menu ) )
		{
			return $this->global_template->menu_sub_navigation( $this->menu );
		}
	}
	
	/**
	 * Build the secondary menu globally
	 *
	 * @return	string		Menu HTML
	 */
	protected function _buildGlobalSubMenu()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$_subMenu	= array();
		$_titleMenu	= array();
		$fakeApps	= $this->fetchFakeApps();

		//-----------------------------------------
		// Got a cache?
		//-----------------------------------------

		if ( IN_DEV )
		{
			ipsRegistry::cache()->updateCacheWithoutSaving( 'app_menu_cache', array() );
		}

		if ( ! is_array( ipsRegistry::cache()->getCache('app_menu_cache') ) OR ! count( ipsRegistry::cache()->getCache('app_menu_cache') ) )
		{
			$this->cache->rebuildCache( 'app_menu_cache', 'global' );
		}
		
		//-----------------------------------------
		// Get child XML tabs
		//-----------------------------------------

		if ( ipsRegistry::$current_application )
		{
			//-----------------------------------------
			// Do stuff
			//-----------------------------------------

			foreach( ipsRegistry::cache()->getCache('app_menu_cache') as $app_dir => $data )
			{
				if ( ! ipsRegistry::$applications[ $app_dir ]['app_enabled'] )
				{
					continue;
				}
				
				$this->lang->loadLanguageFile( array( 'admin_menulang' ), $app_dir );
				
				foreach( $data as $_current_module => $module_data )
				{				
					$_thisApp			= $app_dir;
					$__current_module	= $_current_module;
					$_current_module	= preg_replace( '/^\d+?_(.*)$/', "\\1", $_current_module );
					
					//-----------------------------------------
					// Sort out fake apps
					//-----------------------------------------
					
					foreach( $fakeApps as $_newApp => $_appData )
					{
						foreach( $_appData as $__appData )
						{
							if( $_thisApp == $__appData['app'] AND $_current_module == $__appData['module'] )
							{
								$_thisApp	= $_newApp;
								break;
							}
						}
					}
					
					foreach( $module_data['items'] as $id => $item )
					{
						//-----------------------------------------
						// Title
						//-----------------------------------------
												
						$item['title']	= ( $item['langkey'] AND $this->lang->words[ 'menu__' . $item['langkey'] ] ) ? $this->lang->words[ 'menu__' . $item['langkey'] ] : $item['title'];

						//-----------------------------------------
						// Permission mask?
						//-----------------------------------------

						if ( $item['rolekey'] )
						{
							ipsRegistry::getClass('class_permissions')->return = 1;

							if ( ipsRegistry::getClass('class_permissions')->checkPermission( $item['rolekey'], $app_dir, $_current_module ) !== TRUE )
							{
								continue;
							}
						}

						//-----------------------------------------
						// Continue...
						//-----------------------------------------

						if ( $item['title'] AND $item['section'] )
						{
							$_subMenu[ $_thisApp ][ $_current_module ]['items'][]	= array( 'title'	=> $item['title'],
																							 'module'	=> $_current_module,
																							 'section'	=> $item['section'],
																							 'url'		=> $item['url'],
																							 'redirect'	=> $item['redirect'],
																							 'app_dir'	=> $app_dir,
																							 'pos'		=> preg_replace( '/^(\d+)?_.*$/', "\\1", $__current_module )
																							);
							
							$_subMenu[ $_thisApp ][ $_current_module ]['title']		= ( count($_subMenu[ $_thisApp ][ $_current_module ]['items']) > 1 ) ? $module_data['title'] : $item['title'];
						}
					}
				}
				
				//-----------------------------------------
				// Build titles array
				//-----------------------------------------
				
				if( is_array(ipsRegistry::$modules[ $app_dir ]) AND count(ipsRegistry::$modules[ $app_dir ]) )
				{
					foreach( ipsRegistry::$modules[ $app_dir ] as $data )
					{
						if ( !$data['sys_module_admin'] )
						{
							continue;
						}
						
						$_thisApp			= $app_dir;

						//-----------------------------------------
						// Sort out fake apps
						//-----------------------------------------
						
						foreach( $fakeApps as $_newApp => $_appData )
						{
							foreach( $_appData as $__appData )
							{
								if( $_thisApp == $__appData['app'] AND $data['sys_module_key'] == $__appData['module'] )
								{
									$_thisApp	= $_newApp;
								}
							}
						}
						
						//-----------------------------------------
						// Title
						//-----------------------------------------
												
						$data['sys_module_title'] = isset($this->lang->words[ 'module__' . $app_dir . '_' . $data['sys_module_key'] ]) ? $this->lang->words[ 'module__' . $app_dir . '_' . $data['sys_module_key'] ] : $data['sys_module_title'];
						
						$_titleMenu[ $_thisApp ][ $data['sys_module_key'] ] = array(	'url'		=> $this->settings['_base_url'] . 'app=' . $app_dir . '&amp;module=' . $data['sys_module_key'],
																						'title'		=> $data['sys_module_title'],
																						'module'	=> $data['sys_module_key'] );
					}
				}
			}
		}

		return array('menu' => $_subMenu, 'titles' => $_titleMenu);
		/*if ( isset( $_subMenu ) && count( $_subMenu ) )
		{
			return $this->global_template->global_menu_sub_navigation( $_subMenu, $_titleMenu );
		}*/
	}

	/**
	 * Show a page inside an iframe
	 *
	 * @param	string		URL
	 * @param	string		Optional HTML to show inside the iframe
	 * @return	@e void
	 */
	public function showInsideIframe($url="", $html="")
	{
		if ( $url )
		{
			$this->html .= "<iframe src='{$url}' scrolling='auto' style='border:1px solid #000' border='0' frameborder='0' width='100%' height='500'></iframe>";
		}
		else
		{
			$this->html .= "<iframe scrolling='auto' style='border:1px solid #000' border='0' frameborder='0' width='100%' height='500'>{$html}</iframe>";
		}

		$this->html_main .= $this->global_template->global_frame_wrapper();
		$this->sendOutput();
	}

	/**
	 * Generate a drop down list of groups
	 *
	 * @param	string		Form field name
	 * @param	mixed 		Selected ID(s)
	 * @param	boolean 	Multiselect (TRUE is yes)
	 * @param	string		HTML id attribute value
	 * @param	integer 	Multiselect size
	 * @return	string		HTML dropdown menu
	 */
	public function generateGroupDropdown( $formFieldName, $selected, $multiselect=FALSE, $formFieldID='', $multiselectSize=5 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$groups = array();

		//-----------------------------------------
		// Get 'em
		//-----------------------------------------

		$cache = $this->caches['group_cache'];

		foreach( $cache as $id => $data )
		{
			$groups[] = array( $data['g_id'], $data['g_title'] );
		}

		if ( $multiselect === TRUE )
		{
			return $this->formMultiDropdown( $formFieldName, $groups, $selected, $multiselectSize, $formFieldID );
		}
		else
		{
			return $this->formDropdown( $formFieldName, $groups, $selected, $formFieldID );
		}
	}

	/**
	 * Generate a drop down list of skins
	 *
	 * @param	array 		Skin array
	 * @param	int			Parent id
	 * @param	int			Iteration
	 * @return	array 		Array of skins to add to dropdown
	 */
	public function generateSkinDropdown( $skin_array=array(), $parent=0, $iteration=0 )
	{
		//$skin_array		= array();
		$depthMarkers	= "";
		
		if( $iteration )
		{
			for( $i=0; $i<$iteration; $i++ )
			{
				$depthMarkers .= '--';
			}
		}

		foreach( $this->allSkins as $id => $data )
		{
			/* Root skins? */
			if ( count( $data['_parentTree'] ) AND $iteration == 0 )
			{
				continue;
			}
			else if( $iteration > 0 AND (!count( $data['_parentTree'] ) OR $data['_parentTree'][0] != $parent) )
			{
				continue;
			}

			$skin_array[] = array( $data['set_id'], $depthMarkers . $data['set_name'] );

			if ( is_array( $data['_childTree'] ) AND count( $data['_childTree'] ) )
			{
				$skin_array = $this->generateSkinDropdown( $skin_array, $id, $iteration + 1 );
			}
		}

		return $skin_array;
	}

	/**
	 * Create a form text input field
	 *
	 * @param	string		Field name
	 * @param	string		Field ID
	 * @param	string		Javascript code to add to field
	 * @param	string		CSS class(es) to add to field
	 * @return	string		HTML
	 */
	public function formUpload( $name="FILE_UPLOAD", $id='', $js="", $css="" )
	{
		if ( $js )
		{
			$js = ' ' . $js;
		}

		if ( $css )
		{
			$css = ' ' . $css;
		}

		return "<input class='textinput{$css}' type='file'{$js} size='30' name='{$name}' id='{$id}'>";
	}

	/**
	 * Create a form text input field
	 *
	 * @param	string		Field name
	 * @param	string		Field current value
	 * @param	string		Field ID [defaults to value for $name]
	 * @param	integer		Field size [defaults to 30]
	 * @param	string		Field type [defaults to 'text']
	 * @param	string		Javascript code to add to field
	 * @param	string		CSS class(es) to add to field
	 * @return	string		Form input field
	 */
	public function formInput( $name, $value="", $id="", $size="30", $type='text', $js="", $css="", $maxLength='' )
	{
		if ( $js )
		{
			$js = ' ' . $js;
		}

		if ( $css )
		{
			$css = ' ' . $css;
		}
		
		if ( $maxLength )
		{
			$maxLength = " maxlength='" . intval( $maxLength ) . "'";
		}

		$id   = $id ? $id : $name;
		$size = $size ? $size : 30;

		return "<input type='{$type}' name='{$name}' id='{$id}' value='{$value}' size='{$size}'{$js} class='input_text{$css}'{$maxLength} />";
	}

	/**
	 * Create a simpl(er) form text input field
	 *
	 * @param	string		Field name
	 * @param	string		Field current value
	 * @param	integer		Field size [defaults to 5]
	 * @return	string		Form input field
	 * @see 	formInput()
	 */
	public function formSimpleInput( $name, $value="", $size='5' )
    {
		return $this->formInput( $name, $value, $name, $size );
	}

	/**
	 * Create a form textarea field
	 *
	 * @param	string		Field name
	 * @param	string		Field current value
	 * @param	integer		Number of columns [defaults to 40]
	 * @param	integer		Number of rows [defaults to 5]
	 * @param	string		HTML id to assign to field [defaults to $name]
	 * @param	string		Javascript code to add to field
	 * @param	string		CSS class(es) to add to field
	 * @param	string		Wrap type [defaults to soft]
	 * @return	string		Form textarea field
	 */
	public function formTextarea( $name, $value="", $cols='40', $rows='5', $id="", $js="", $css="", $wrap='soft' )
	{
		$id = $id ? $id : $name;

		if ( $css )
		{
			$css = ' ' . $css;
		}

		if ( $js )
		{
			$js = ' ' . $js;
		}
		
		$cols = $cols ? $cols : 40;
		$rows = $rows ? $rows : 5;

		return "<textarea name='{$name}' cols='{$cols}' rows='{$rows}' wrap='{$wrap}' id='{$id}'{$js} class='multitext{$css}'>{$value}</textarea>";
	}

	/**
	 * Create a form dropdown/select list
	 *
	 * @param	string		Field name
	 * @param	array		Options.  Multidimensional array in format of array( array( 'value', 'display' ), array( 'value', 'display', 'optgroup_key' ) )
	 * @param	string		Default value
	 * @param	string		HTML id attribute [defaults to $name]
	 * @param	string		Javascript to add to list
	 * @param	string		CSS class(es) to add to field
	 * @param	array 		Optgroups
	 * @return	string		Form dropdown list
	 */
	public function formDropdown( $name, $list=array(), $default_val="", $id="", $js="", $css="", $optgroups=array() )
	{
		if ( $js )
		{
			$js = ' ' . $js;
		}

		if ( $css )
		{
			$css = ' ' . $css;
		}

		$id = $id ? $id : $name;

		$html = "<select name='{$name}'{$js} id='{$id}' class='dropdown{$css}'>\n";
		
		if ( is_string( $list ) )
		{
			if ( $list == '--groups--' )
			{
				$options = array();
				$groups  = $this->caches['group_cache'];
				
				foreach( $groups as $id => $data )
				{
					$options[] = array( $id, $data['g_title'] );
				}
				
				$list = $options;
			}
			else
			{
				$list = array();
			}
		}
		
		//-----------------------------------------
		// Support for optgroups
		//-----------------------------------------
		
		if( count($optgroups) )
		{
			$_printed	= 0;
			
			foreach ( $list as $v )
			{
				if( $v[2] != '_root_' )
				{
					continue;
				}
				
				$selected = "";
	
				if ( ($default_val !== "") and ($v[0] == $default_val) )
				{
					$selected = ' selected="selected"';
				}
				
				$disabled = '';
				if ( isset( $v['disabled'] ) and $v['disabled'] === TRUE )
				{
					$disabled = ' disabled="disabled"';
				}
				
				$html .= "<option value='" . $v[0] . "'" . $selected . $disabled . ">" . $v[1] . "</option>\n";
				
				$_printed++;
			}
				
			foreach( $optgroups as $_key => $value )
			{
				$html .= "<optgroup label='{$value}'>\n";
				
				foreach ( $list as $v )
				{
					if( !$v[2] OR $v[2] != $_key )
					{
						continue;
					}
					
					$selected = "";
		
					if ( ($default_val !== "") and ($v[0] == $default_val) )
					{
						$selected = ' selected="selected"';
					}
					
					$disabled = '';
					if ( isset( $v['disabled'] ) and $v['disabled'] === TRUE )
					{
						$disabled = ' disabled="disabled"';
					}
					
					$html .= "<option value='" . $v[0] . "'" . $selected . $disabled . ">" . $v[1] . "</option>\n";
					
					$_printed++;
				}
				
				$html .= "</optgroup>\n";
			}
			
			if( $_printed < count($list) )
			{
				$html .= "<optgroup label='{$this->lang->words['optgroup_other']}'>\n";
				
				foreach ( $list as $v )
				{
					if( $v[2] )
					{
						continue;
					}
					
					$selected = "";
		
					if ( ($default_val !== "") and ($v[0] == $default_val) )
					{
						$selected = ' selected="selected"';
					}
					
					$disabled = '';
					if ( isset( $v['disabled'] ) and $v['disabled'] === TRUE )
					{
						$disabled = ' disabled="disabled"';
					}
					
					$html .= "<option value='" . $v[0] . "'" . $selected . $disabled . ">" . $v[1] . "</option>\n";
				}
				
				$html .= "</optgroup>\n";
			}
		}
		
		//-----------------------------------------
		// Normal
		//-----------------------------------------
		
		else
		{
			foreach ( $list as $v )
			{
				$selected = "";
				if ( ($default_val !== "") and ($v[0] == $default_val) )
				{
					$selected = ' selected="selected"';
				}
				
				$disabled = '';
				if ( isset( $v['disabled'] ) and $v['disabled'] === TRUE )
				{
					$disabled = ' disabled="disabled"';
				}
	
				$html .= "<option value='" . $v[0] . "'" . $selected . $disabled . ">" . $v[1] . "</option>\n";
			}
		}

		$html .= "</select>\n\n";

		return $html;
	}

	/**
	 * Create a multiselect form field
	 *
	 * @param	string		Field name
	 * @param	array		Options.  Multidimensional array in format of array( array( 'value', 'display' ), array( 'value', 'display' ) )
	 * @param	array		Default values
	 * @param	integer		Number of items to show [defaults to 5]
	 * @param	string		HTML id attribute [defaults to $name]
	 * @param	string		Javascript to apply to field
	 * @param	string		CSS class(es) to add to field
	 * @return	string		Form multiselect field
	 */
	public function formMultiDropdown( $name, $list=array(), $default=array(), $size=5, $id="", $js="", $css='' )
	{
		if ( $js )
		{
			$js = ' ' . $js;
		}

		$id = $id ? $id : $name;

		if ( $css )
		{
			$css = ' ' . $css;
		}
		
		$size = $size ? $size : 5;
		
		$html = "<select name='{$name}'{$js} id='{$id}' class='dropdown{$css}' multiple='multiple' size='{$size}'>\n";
		
		if ( is_string( $list ) )
		{
			if ( $list == '--groups--' )
			{
				$options = array();
				$groups  = $this->caches['group_cache'];
				
				foreach( $groups as $id => $data )
				{
					$options[] = array( $id, $data['g_title'] );
				}
				
				$list = $options;
			}
			else
			{
				$list = array();
			}
		}
		
		if ( !is_array($default) )
		{
			$default = is_string( $list ) ? array( $list ) : array();
		}
		
		foreach ($list as $v)
		{
			$selected = "";
			
			if ( count($default) && in_array( $v[0], $default ) )
			{
				$selected = ' selected="selected"';
			}
			
			$disabled = '';
			if ( isset( $v['disabled'] ) and $v['disabled'] === TRUE )
			{
				$disabled = ' disabled="disabled"';
			}
			
			$html .= "<option value='" . $v[0] . "'" . $selected . $disabled . ">" . $v[1] . "</option>\n";
		}

		$html .= "</select>\n\n";

		return $html;
	}

	/**
	 * Create yes/no radio buttons
	 *
	 * @param	string		Field name
	 * @param	string		Default values
	 * @param	string		HTML id attribute (appended with "_yes" and "_no" on the respective fields) [defaults to $name]
	 * @param	array 		Javascript to add to the fields.  Array keys should be 'yes' and 'no', values being the javascript to add.
	 * @param	string		CSS class(es) to add to field
	 * @return	string		Form yes/no radio buttons
	 */
	public function formYesNo( $name, $default_val="", $id='', $js=array(), $css='' )
	{
		$y_js   = '';
		$n_js   = '';
		$id_yes = '';
		$id_no  = '';

		if ( $js['yes'] != "" )
		{
			$y_js = $js['yes'];
		}

		if ( $js['no'] != "" )
		{
			$n_js = $js['no'];
		}

		$id = $id ? $id : $name;

		if ( $id )
		{
			$id_yes = " id='{$id}_yes'";
			$id_no  = " id='{$id}_no'";
		}

		$yes = "<span class='yesno_yes {$css}'><input type='radio' name='{$name}' value='1' {$y_js}{$id_yes} /><label for='{$id}_yes'>{$this->lang->words['yesno_yes']}</label></span>";
		$no  = "<span class='yesno_no {$css}'><input type='radio' name='{$name}' value='0' {$n_js}{$id_no} /><label for='{$id}_no'>{$this->lang->words['yesno_no']}</label></span>";

		if ( $default_val == 1 )
		{
			$yes = "<span class='yesno_yes {$css}'><input type='radio'{$id_yes} name='{$name}' value='1' {$y_js} checked='checked' /><label for='{$id}_yes'>{$this->lang->words['yesno_yes']}</label></span>";
		}
		else
		{
			$no  = "<span class='yesno_no {$css}'><input type='radio'{$id_no} name='{$name}' value='0' checked='checked' {$n_js} /><label for='{$id}_no'>{$this->lang->words['yesno_no']}</label></span>";
		}
		
		return $yes . $no;
	}

	/**
	 * Create a checkbox form field
	 *
	 * @param	string		Field name
	 * @param	boolean		Field checked or not
	 * @param	string 		Field value
	 * @param	string		HTML id attribute [defaults to $name]
	 * @param	string		Javascript to add to the checkbox
	 * @param	string		CSS class(es) to add to field
	 * @return	string		Form checkbox field
	 */
	public function formCheckbox( $name, $checked=false, $val=1, $id='', $js="", $css='' )
	{
		$id = $id ? $id : $name;

		if( $css )
		{
			$css = "class='{$css}' ";
		}

		if ( $js )
		{
			$js = ' ' . $js;
		}

		if ( $checked == 1 )
		{
			return "<input type='checkbox' name='{$name}' value='{$val}' {$css}{$js} id='{$id}' checked='checked' />";
		}
		else
		{
			return "<input type='checkbox' name='{$name}' value='{$val}' {$css}{$js} id='{$id}' />";
		}
	}

    /**
	 * Build up page span links
	 *
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

		$data['dotsSkip']			= isset($data['dotsSkip'])       ? $data['dotsSkip'] : '';
		$data['noDropdown']			= isset($data['noDropdown'])     ? intval( $data['noDropdown'] ) : 0;
		$data['startValueKey']		= isset($data['startValueKey'])	 ? $data['startValueKey']	 : '';
		$data['currentStartValue']	= isset( $data['currentStartValue'] ) ? $data['currentStartValue'] : $this->request['st'];
		$data['dotsSkip']			= ! $data['dotsSkip']            ? 2    : $data['dotsSkip'];
		$data['startValueKey']		= ! $data['startValueKey']       ? 'st' : $data['startValueKey'];
		$data['seoTitle']			= isset( $data['seoTitle'] )     ? $data['seoTitle'] : '';
		$data['uniqid']				= substr( str_replace( array( ' ', '.' ), '', uniqid( microtime(), true ) ), 0, 10 );

		//-----------------------------------------
		// Get the number of pages
		//-----------------------------------------

		if ( $data['totalItems'] > 0 )
		{
			$work['pages'] = ceil( $data['totalItems'] / $data['itemsPerPage'] );
		}

		$work['pages'] = isset( $work['pages'] ) ? $work['pages'] : 1;

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$work['total_page']   = $work['pages'];
		$work['current_page'] = $data['currentStartValue'] > 0 ? ($data['currentStartValue'] / $data['itemsPerPage']) + 1 : 1;

		//-----------------------------------------
		// Loppy loo
		//-----------------------------------------

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

		return $this->global_template->paginationTemplate( $work, $data );
	}

    /**
	 * Retrieve list of "fake apps" to generate tabs for them in ACP
	 *
	 * @return	array
	 * @since	3.0
	 */
	public function fetchFakeApps()
	{
		return array(
						'lookfeel'	=> array(
											array(	'app'		=> 'core',
													'module'	=> 'templates' ),
											array(	'app'		=> 'core',
													'module'	=> 'posts' ),
											array(	'app'		=> 'core',
													'module'	=> 'languages' ),
											array(	'app'		=> 'core',
													'module'	=> 'mobileapp' )
											),
						'support'	=> array(
											array(	'app'		=> 'core',
													'module'	=> 'diagnostics' ),
											array(	'app'		=> 'core',
													'module'	=> 'sql' ),
											array(	'app'		=> 'core',
													'module'	=> 'help' )
											),
						'reports'	=> array(
											array(	'app'		=> 'forums',
													'module'	=> 'statistics' ),
											array(	'app'		=> 'core',
													'module'	=> 'logs' ),
											),
			);
	}
}