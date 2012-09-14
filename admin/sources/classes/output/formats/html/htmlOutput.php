<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Ouput format: HTML
 * (Matt Mecham)
 * Last Updated: $Date: 2012-06-08 14:41:59 -0400 (Fri, 08 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		9th March 2005 11:03
 * @version		$Revision: 10902 $
 *
 */

class htmlOutput extends coreOutput implements interface_output 
{
	/**
	 * Main output class
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $output;
	
	/**
	 * Print only flag
	 *
	 * @access	protected
	 * @var		bool
	 */
	protected $_printOnly = false;
	
	/**
	 * CSS array
	 *
	 * @access	protected
	 * @var 	array
	 */
	protected $_css = array( 'import' => array(), 'inline' => array() );
	
	/**
	 * Use a minimal wrapper instead of globalTemplate
	 * 
	 * @var		bool
	 */
 	protected $useMinimalWrapper	= false;

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Output object
	 * @return	@e void
	 */
	public function __construct( output $output )
	{
		/* Make object */
		parent::__construct( $output );
		
		/* Print only seems only relevant to html method, so... */
		if ( !empty( $_GET['forcePrint'] ) AND ( $_GET['_k'] == $this->member->form_hash ) )
		{
			$this->_printOnly = true;
		}
	}
	
	/**
	 * Prints any header information for this output module
	 *
	 * @access	public
	 * @return	@e void		Prints header() information
	 */
	public function printHeader()
	{
		//-----------------------------------------
		// Start GZIP compression
        //-----------------------------------------
      
		if ( $this->settings['disable_gzip'] != 1 )
		{
		    $buffer = "";
    
		    if ( count( ob_list_handlers() ) )
		    {
				$buffer = ob_get_contents();
				ob_end_clean();
			}
		
			if ( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) AND strstr( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') )
			{
				@ob_start('ob_gzhandler');
			}
			else
			{
				@ob_start();
			}
			
			print $buffer;
		}
		
		if ( isset( $_SERVER['SERVER_PROTOCOL'] ) AND strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) )
		{
			header("HTTP/1.0 " . $this->_headerCode . ' ' . $this->_headerStatus );
		}
		else
		{
			header("HTTP/1.1 " . $this->_headerCode . ' ' . $this->_headerStatus );
		}
			
		if ( $this->settings['print_headers'] )
    	{	
			/* Forcing a download? */
			if ( $this->_forceDownload )
			{
				header( "Content-type: unknown/unknown" );
				header( "Content-Disposition: attachment; filename=\"" . IPSText::alphanumericalClean( $this->registry->output->getTitle() )  . ".html\"" );
			}
			else
			{
				header( "Content-type: text/html;charset=" . IPS_DOC_CHAR_SET );
			}
			
			if ( $this->settings['nocache'] )
			{
				$expires	= ( $this->_headerExpire ) ? gmdate( "D, d M Y H:i:s", time() + $this->_headerExpire ) . " GMT" : gmdate( "D, d M Y H:i:s", time() - 86400 ) . " GMT";
				$maxAge		= $this->_headerExpire;
				$nocache	= ( ! $this->_headerExpire ) ? 'no-cache,' : '';
				
				header( "Cache-Control:  ". $nocache . "must-revalidate, max-age=" . $maxAge );
				header( "Expires: " . $expires );
				
				if ( ! $this->_headerExpire )
				{
					header( "Pragma: no-cache" );
				}
			}
        }
	}
	
	/**
	 * Display error
	 *
	 * @access	public
	 * @param	string		Error message
	 * @param	integer		Error code
	 * @return	mixed		You can print a custom message here, or return formatted data to be sent do registry->output->sendOutput
	 */
	public function displayError( $message, $code=0, $header=401 )
	{
		list( $em_1, $em_2 ) = explode( '@', $this->settings['email_in'] );
		
    	/* Figure out title */
    	$title = ( ! empty( $this->lang->words[ $header . '_error_title'] ) ) ? $this->lang->words[ $header . '_error_title'] : $this->lang->words['error_title'];
    	
		//-----------------------------------------
    	// Show error
    	//-----------------------------------------

    	$html = $this->registry->getClass('output')->getTemplate('global_other')->Error( $message, $code, $em_1, $em_2, $title );


		return $html;
	}
	
	/**
	 * Display board offline
	 *
	 * @access	public
	 * @param	string		Message
	 * @return	mixed		You can print a custom message here, or return formatted data to be sent do registry->output->sendOutput
	 */
	public function displayBoardOffline( $message )
	{
		$this->useMinimalWrapper	= true;

		return $this->registry->getClass('output')->getTemplate('global_other')->displayBoardOffline( $message );
	}
	
	/**
	 * Fetches the output
	 *
	 * @access	public
	 * @param	string		Output gathered
	 * @param	string		Title of the document
	 * @param	array 		Navigation gathered
	 * @param	array 		Array of document head items
	 * @param	array 		Array of JS loader items
	 * @param	array 		Array of extra data
	 * @return	string		Output to be printed to the client
	 */
	public function fetchOutput( $output, $title, $navigation, $documentHeadItems, $jsLoaderItems, $extraData=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$system_vars_cache  = $this->caches['systemvars'];
		$pmData			    = FALSE;
		$notificationLatest = array();
		
		//-----------------------------------------
		// NORMAL
		//-----------------------------------------
		
		if ( $this->_outputType == 'normal' )
		{
			//-----------------------------------------
			// Do we have a notification show?
			//-----------------------------------------

			if ( ! empty( $this->memberData['msg_show_notification'] ) AND $this->memberData['_cache']['show_notification_popup'] )
			{ 
				if ( ! $this->settings['board_offline'] OR $this->memberData['g_access_offline'] )
				{
					if ( strpos( ipsRegistry::$settings['query_string_real'], 'module=messaging' ) === false )
					{
						IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'msg_show_notification' => 0 ) ) );
						
						/* Grab inline notifications... */
						$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
						$notifyLibrary		= new $classToLoad( $this->registry );
						$notifyLibrary->setMember( $this->memberData );
	
						$tmp = $notifyLibrary->getLatestNotificationForInlinePopUp();
						$weNeed             = array( 'notify_title', 'member_member_id', 'member_PhotoTag', 'member_members_display_name', 'date_parsed', 'title', 'url', 'type', 'content' );
						
						foreach( $weNeed as $k )
						{
							$notificationLatest[ $k ] = $tmp[ $k ];
						}
					}
				}
			}

			//-----------------------------------------
			// Add identifier URL
			//-----------------------------------------
			
			$http = 'http://';

			if ( strpos( $this->settings['board_url'], 'https://' ) === 0 )
			{
				$http = 'https://';
			}
		
			$this->addMetaTag( 'identifier-url', $http . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			
			//-----------------------------------------
	        // Add in task image?
	        //-----------------------------------------

			$task	= '';
			$system_vars_cache['task_next_run'] = isset( $system_vars_cache['task_next_run'] ) ? $system_vars_cache['task_next_run'] : 0;
						
	        if ( ( !$this->settings['task_use_cron'] and time() >= $system_vars_cache['task_next_run'] ) OR ( defined('FORCE_TASK_KEY') ) )
	        {
				$_url = ( ! $this->registry->getClass('output')->isHTTPS ) ? $this->settings['base_url'] : $this->settings['base_url_https'];
	        	$task = "<div><img src='" . $_url . "app=core&amp;module=task' alt='' style='border: 0px;height:1px;width:1px;' /></div>";
	        }
	     
			//-----------------------------------------
			// Grab output
			//-----------------------------------------
			
	        /* Inline msg */
	        $inlineMsg     = $this->member->sessionClass()->getInlineMessage();
	        $templateName  = 'globalTemplateConditional';
	        $templateGroup = 'global';
	        
	        if ( $this->useMinimalWrapper )
	        {
	        	$templateName  = 'globalTemplateMinimal';
	        	$templateGroup = 'global_other';
	        }
	        
	        /* Do pagination */
	        if ( strstr( $title, '<%pageNumber%>' ) )
	        {
	        	$replace = ( $this->_current_page_title ) ? ' ' . $this->lang->words['page_title_page'] . ' ' . $this->_current_page_title : '';
		        $title   = str_replace( '<%pageNumber%>', $replace, $title );
	        }
	        else if ( $this->_current_page_title )
	        {
		        $title = $title . ' ' . $this->lang->words['page_title_page'] . ' '  . $this->_current_page_title;
	        }
	        
	        /*
	        * Right blocks
	        */
            //-----------------------------------------
            // Assign skeletal template ma-doo-bob
            //-----------------------------------------

            $this->DB->build(array('select' => '*', 'from' => 'portal_blocks', 'order' => 'position ASC'));
            $this->DB->execute();

            $leftBlocks = array();
            $mainBlocks = array();
            $rightBlocks = array();

            while ($r = $this->DB->fetch()) {
                $portalBlocks[] = $r;
            }

            if (is_array($portalBlocks) AND count($portalBlocks)) {
                foreach ($portalBlocks as $block) {
                    ob_start();
                    eval("?>" . $block['block_code'] . "<?php\n");
                    $block['block_code'] = ob_get_contents();
                    ob_end_clean();

                    if ($block['align'] == '1') {
                        $leftBlocks[] = $block;
                    } else if ($block['align'] == '2') {
                        $mainBlocks[] = $block;
                    } else if ($block['align'] == '3') {
                        $rightBlocks[] = $block;
                    }
                }
            }
	        $finalOutput = $this->output->getTemplate($templateGroup)->$templateName( $output, $documentHeadItems, $this->_css, $jsLoaderItems, $this->_metaTags,
																									   array( 'title'			=> $title,
																											  'applications'	=> $this->core_fetchApplicationData(),
																											  'page'			=> $this->_current_page_title,
																									   		  'notifications'	=>( $notificationLatest ) ? IPSText::jsonEncodeForTemplate( $notificationLatest ) : '',
																									   		  'inlineMsg'		=> $inlineMsg  ),
																									   array( 'navigation'		=> $navigation,
																									 		  'adHeaderCode'	=> ! empty( $extraData['adHeaderCode'] ) ? $extraData['adHeaderCode'] : '',
																											  'adFooterCode'	=> ! empty( $extraData['adFooterCode'] ) ? $extraData['adFooterCode'] : '' ),
																									   array( 'time'			=> $this->registry->getClass('class_localization')->getDate( time(), 'SHORT', 1 ),
																									   		  'mark_read_apps'  => IPSLib::getEnabledApplications('itemMarking'),
																											  'lang_chooser'	=> $this->html_buildLanguageDropDown(),
																											  'skin_chooser'	=> $this->html_fetchSetsDropDown(),
																											  'copyright'		=> $this->html_fetchCopyright() ),
																									   array( 'ex_time'			=> ( isset($this->request['faster']) AND $this->request['faster'] == 'yes' ) ? $this->_getFasterText() : sprintf( "%.4f", IPSDebug::endTimer() ),
																									          'gzip_status'		=> ( $this->settings['disable_gzip'] == 1 ) ? $this->lang->words['gzip_off'] : $this->lang->words['gzip_on'],
																									          'server_load'		=> ipsRegistry::$server_load,
																									          'queries'			=> $this->DB->getQueryCount(),
																									          'task'			=> $task ),
																				       $rightBlocks, $leftBlocks);
		}
		//-----------------------------------------
		// Grab output
		// REDIRECT
		//-----------------------------------------
		
		else if ( $this->_outputType == 'redirect' )
		{
			$extraData['full'] = 1;
			
			# SEO?
			if ( $extraData['seoTitle'] )
			{
				$extraData['url']  = $this->output->buildSEOUrl( $extraData['url'], 'none', $extraData['seoTitle'], $extraData['seoTemplate'] );
			}

			$finalOutput = $this->output->getTemplate('global_other')->redirectTemplate( $documentHeadItems, $this->_css, $jsLoaderItems, $extraData['text'], $extraData['url'], $extraData['full'] );
		}

		//-----------------------------------------
		// POP UP
		//-----------------------------------------
		
		else if ( $this->_outputType == 'popup' )
		{
			$finalOutput = $this->output->getTemplate('global_other')->displayPopUpWindow( $documentHeadItems, $this->_css, $jsLoaderItems, $title, $output );
		}
		
		//-----------------------------------------
		// Set a class on the body for print
		//-----------------------------------------
		
		if( $this->_printOnly )
		{
			$finalOutput	= str_replace( "<body", "<body class='printpreview'", $finalOutput );
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return $finalOutput;
	}
	
	/**
	 * Finish / clean up after sending output
	 *
	 * @access	public
	 * @return	null
	 */
	public function finishUp()
	{
	}
	
	/**
	 * Adds more items into the document header like CSS / RSS, etc
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function addHeadItems()
	{
		/* Ok, now a little hacky.. */
		if ( $this->registry->getClass('output')->isHTTPS )
		{
			$this->registry->getClass('output')->skin['set_css_inline'] = false;
			$this->settings['use_minify'] = 0;
			
			foreach( $this->registry->getClass('output')->skin['_cssGroupsArray'] as $position => $data )
			{
				/* Print only */
				if ( $this->_printOnly === true )
				{
					if ( $data['css_group'] != 'ipb_print' AND !preg_match( "/media=['\"]print[\"']/i", $this->registry->getClass('output')->skin['_css'][ $data['css_group'] ]['attributes'] ) )
					{
						continue;
					}
					else
					{
						$this->registry->getClass('output')->skin['_css'][ $data['css_group'] ]['attributes'] = 'media="screen,print"';
					}
				}
				else if ( $data['css_group'] == 'ipb_print' )
				{
					//$this->registry->getClass('output')->skin['_css'][ $data['css_group'] ]['attributes'] = 'media="print"';
					continue;
				}
			
				$this->registry->getClass('output')->skin['_css'][ $data['css_group'] ]['content'] = str_replace( 'http://', 'https://', $this->registry->getClass('output')->skin['_css'][ $data['css_group'] ]['content'] );
			}
		}
		
		//-----------------------------------------
		// CSS
		//-----------------------------------------

		foreach( $this->registry->getClass('output')->skin['_cssGroupsArray'] as $position => $data )
		{
			$name = $data['css_group'];
			
			/* Print only */
			if ( $this->_printOnly === true )
			{
				if ( $name != 'ipb_print' AND !preg_match( "/media=['\"]print[\"']/i", $this->registry->getClass('output')->skin['_css'][ $name ]['attributes'] ) )
				{
					continue;
				}
				else
				{
					$this->registry->getClass('output')->skin['_css'][ $name ]['attributes'] = 'media="screen,print"';
				}
			}
			else if ( $data['css_group'] == 'ipb_print' )
			{
				//$this->registry->getClass('output')->skin['_css'][ $name ]['attributes'] = 'media="print"';
				continue;
			}
			
			/* Did we skip it? */
			if ( ! isset( $this->registry->getClass('output')->skin['_css'][ $name ] ) )
			{
				continue;
			}
			
			/* Skip IE, print and lo-fi as it's hardcoded in the skin  */
			if  ( $name == 'ipb_ie' )
			{
				continue;
			}

			if ( ! SAFE_MODE_ON && $this->registry->getClass('output')->skin['set_css_inline'] AND @is_file( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_css/'. $this->registry->getClass('output')->skin['_csscacheid'] .'/'. $name . '.css' ) )
	        {
				$_cssFile = $this->settings['css_base_url'] . 'style_css/' . $this->registry->getClass('output')->skin['_csscacheid'] .'/'. $name . '.css';
	        	$this->_css['import'][$_cssFile] = array( 'attributes' => $this->registry->getClass('output')->skin['_css'][ $name ]['attributes'],
														  'content'    => $_cssFile );
	        }
	        else
	        {
				$this->_css['inline'][] = array( 'attributes' => $this->registry->getClass('output')->skin['_css'][ $name ]['attributes'],
												 'content'    => "\n/* CSS: " . $name . "*/\n" . $this->parseIPSTags( $this->registry->getClass('output')->skin['_css'][ $name ]['content'] ) );
	        }
		}
		
		//-----------------------------------------
		// RSS
		//-----------------------------------------

		$rssOutputCache	= $this->cache->getCache('rss_output_cache');

		if( is_array( $rssOutputCache ) AND count( $rssOutputCache ) )
		{
			foreach( $rssOutputCache as $rssEntry )
			{
				$data	= explode( ':|:', $rssEntry );
			
				if( $data[1] )
				{
					$this->output->addToDocumentHead( 'rss', array( 'title'	=> $data[0], 'url' => $data[1] ) );
				}
			}
		}

		$memberCache	= $this->memberData['_cache'];

		if( $this->memberData['member_id'] AND $memberCache['rc_rss_key'] )
		{
			$this->output->addToDocumentHead( 'rss', array( 
														'title'	=> $this->registry->class_localization->words['report_center_rss'], 
														'url'	=> ipsRegistry::$settings['base_url'] . "app=core&amp;module=global&amp;section=rss&amp;type=core&amp;member_id=" . $this->memberData['member_id'] . '&amp;rss_key=' . $memberCache['rc_rss_key'] 
											)			);
	    }
	}
	
	/**
	 * Silent redirect (Redirects without a screen or other notification)
	 *
	 * @access	public
	 * @param	string		URL
	 * @param	string		[SEO Title]
	 * @param	string		[Send a 301 redirect header first]
	 * @param	string		SEO Template
	 * @return	mixed
	 */
	public function silentRedirect( $url, $seoTitle='', $send301=FALSE, $seoTemplate='' )
	{
		# SEO?
		if ( $seoTitle OR $seoTemplate )
		{
			$url = $this->registry->getClass('output')->buildSEOUrl( $url, 'none', $seoTitle, $seoTemplate );
		}
		
		# Ensure &amp;s are taken care of
		$url = str_replace( "&amp;", "&", $url );
		
		# Dirty hack @todo look at real reason for this - happens when URL is already SEO and passed via buildSEOUrl above
		$url = str_replace( "?//", "?/", $url );
		
		# 301?
		if ( $send301 !== false )
		{
			/* Kill caching as Firefox loves to cache redirects */
			$this->settings['nocache'] = 1;
			$this->_headerExpire       = 0;
			
			/* Strip session URL if there wasn't one */
			if ( ! IN_ACP AND $this->member->session_type != 'cookie' AND ! $this->request['s'] )
			{
				$url = preg_replace( '/\?s=([a-zA-Z0-9]{32})(&amp;|&|$)/', '', $url );
			}
			
			/* Log it */
			IPSDebug::addLogMessage( "Redirecting: " . $_SERVER['REQUEST_URI'] . ' to ' . $url, '301log' );
			
			/* Set codes */
			$this->setHeaderCode( ( $send301 === true ) ? 301 : intval( $send301 ) );
			$this->printHeader();
		}

		if ( $this->settings['header_redirect'] == 'refresh' )
		{
			@header("Refresh: 0;url=".$url);
		}
		else if ( $this->settings['header_redirect'] == 'html' )
		{
			$url = str_replace( '&', '&amp;', str_replace( '&amp;', '&', $url ) );
			echo("<html><head><meta http-equiv='refresh' content='0; url=$url'></head><body></body></html>");
			exit();
		}
		else
		{
			@header( "Location: ".$url );
		}
		
		exit();
	}
	
	/**
	 * Replace IPS tags
	 * Converts over <#IMG_DIR#>, etc
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function parseIPSTags( $text )
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
			$text = preg_replace( '#img\s+?src=["\']' . PUBLIC_DIRECTORY . '/style_(images|avatars|emoticons)(.+?)["\'](.+?)?' . '>#is', "img src=\"".$this->settings['ipb_img_url'] . PUBLIC_DIRECTORY . "/style_\\1\\2\"\\3>", $text );
		}
		
		//-----------------------------------------
		// Fix up dates
		//-----------------------------------------
		
		if ( strstr( $text, '<!--{timestamp:' ) )
		{
			$text = preg_replace_callback( '#<!--\{timestamp:(\d+?):([^\}]+?)\}-->#', create_function( '$key', 'return ipsRegistry::getClass(\'class_localization\')->getDate($key[1], \'$key[2]\', 1);' ), $text );
		}
		
		return $text;
	}
	
	/**
	 * Fetch copyright notice
	 *
	 * @access	protected
	 * @return	string		Copyright HTML
	 */
	protected function html_fetchCopyright()
	{
		//-----------------------------------------
		// REMOVAL OF THIS WITHOUT PURCHASING COPYRIGHT REMOVAL WILL VIOLATE THE LICENCE YOU AGREED
		// TO WHEN DOWNLOADING THIS PRODUCT. THIS COULD MEAN REMOVAL OF YOUR BOARD AND EVEN
		// CRIMINAL CHARGES
		//-----------------------------------------
        
		$version = ( $this->settings['ipb_display_version'] AND $this->settings['ipb_display_version'] != 0 ) ? ' ' . IPB_VERSION : '';
		
        if ($this->settings['ipb_copy_number'] && $this->settings['ips_cp_purchase'])
        {
        	/**
        	 * @link	http://community.invisionpower.com/tracker/issue-23750-licensed-to-wont-work-if-copyright-removal-purchased/
        	 */
        	if ( $this->settings['ipb_reg_name'] )
        	{
        		$copyright = "<!-- Copyright Information -->
        				  <p id='copyright'>" .
        				  $this->lang->words['licensed_to'] . ' ' . $this->settings['ipb_reg_name'] .
        				  "</p>\n\t\t<!-- / Copyright -->";
        	}
        	else
        	{
        		$copyright = "";
    		}
        }
        else
        {
        	$copyright = "<!-- Copyright Information -->
        				  <p id='copyright'>
        				  	<a href='http://www.invisionpower.com/products/board/' title='Community Forum Software by Invision Power Services'>Community Forum Software by IP.Board{$version}</a>";
        				  
        	if ( $this->settings['ipb_reg_name'] )
        	{
        		$copyright .= "<br />" . $this->lang->words['licensed_to'] . ' ' . $this->settings['ipb_reg_name'];
        	}
        	
        	
        	$copyright .= "</p>\n\t\t<!-- / Copyright -->";
        }

		return $copyright;
	}
	
	/**
	 * Returns debug data
	 *
	 * @access	protected
	 * @return	string		Debug HTML
	 */
	public function html_showDebugInfo()
    {
    	$input   = "";
        $queries = "";
        $sload   = "";
        $stats   = "";

       //-----------------------------------------
       // Form & Get & Skin
       //-----------------------------------------
	
		/* Admins only */
		if ( ! $this->memberData['g_access_cp'] )
		{
			//return '';
		}
		
       if ($this->settings['debug_level'] >= 2)
       {
			$stats .= "<br />\n<div class='tableborder'>\n<div class='subtitle'>IPSDebug Messages</div><div class='row1' style='padding:6px'>\n";

			foreach( IPSDebug::getMessages() as $dx => $entry )
			{
				$stats .= "<strong>$entry</strong><br />\n";
			}

			$stats .= "</div>\n</div>";

			$stats .= "<br />\n<div class='tableborder'>\n<div class='subtitle'>IPSMember Cache Actions</div><div class='row1' style='padding:6px'>\n";

			if ( is_array( IPSMember::$debugData ) )
			{
				foreach( IPSMember::$debugData as $entry )
				{
					$stats .= "<strong>$entry</strong><br />\n";
				}
			}

			$stats .= "</div>\n</div>";
			
			/* Included Files */
			if( function_exists( 'get_included_files' ) )
			{
				$__files = get_included_files();
				
				$stats .= "<br />\n<div class='tableborder'>\n<div class='subtitle'>(".count($__files).") Included Files</div><div class='row1' style='padding:6px'>\n";				
								
				foreach( $__files as $__f )
				{
					$stats .= "<strong>{$__f}</strong><br />";
				}
				$stats .= '</div></div>';
			}
					

			/* Caches */
			$stats .= "<br />\n<div class='tableborder'>\n<div class='subtitle'>Loaded Caches</div><div class='row1' style='padding:6px'>\n";
        	$_total = 0;

			if ( is_array( $this->cache->debugInfo ) )
			{
				foreach( $this->cache->debugInfo as $key => $data )
				{
					$_size   = $data['size'];
					$_total += $_size;

					$stats .= "<strong>$key</strong> - " . IPSLib::sizeFormat( $_size ) . "<br />\n";
				}
			}

			$stats .= "<strong>TOTAL: " . IPSLib::sizeFormat( $_total ) . "</strong></div>\n</div>";

			/* Loaded classes */

			$loadedClasses = $this->registry->getLoadedClassesAsArray();

			$stats .= "<br />\n<div class='tableborder'>\n<div class='subtitle'>Loaded Classes In ipsRegistry::getClass()</div><div class='row1' style='padding:6px'>\n";

			if ( is_array( $loadedClasses ) )
			{
				foreach( $loadedClasses as $entry )
				{
					$stats .= "<strong>$entry</strong><br />\n";
				}
			}

			$stats .= "</div>\n</div>";

       		$stats .= "<br />\n<div class='tableborder'>\n<div class='subtitle'>FORM and GET Input</div><div class='row1' style='padding:6px'>\n";

			foreach( $this->request as $k => $v )
			{
				if ( in_array( strtolower( $k ), array( 'pass', 'password' ) ) )
				{
					$v = '*******';
				}

				$stats .= "<strong>$k</strong> = $v<br />\n";
			}

			$stats .= "</div>\n</div>";

			$stats .= "<br />\n<div class='tableborder'>\n<div class='subtitle'>SKIN, MEMBER & TASK Info</div><div class='row1' style='padding:6px'>\n";

			while( list($k, $v) = each($this->skin) )
			{
				if( is_array($v) )
				{
					continue;
				}

				if ( strlen($v) > 120 )
				{
					$v = substr( $v, 0, 120 ). '...';
				}

				$stats .= "<strong>$k</strong> = ".IPSText::htmlspecialchars($v)."<br />\n";
			}

			//-----------------------------------------
			// Stop E_ALL moaning...
			//-----------------------------------------
			$cache = $this->caches['systemvars'];

			$cache['task_next_run'] = $cache['task_next_run'] ? $cache['task_next_run'] : 0;

			$stats .= "<b>Next task</b> = ".$this->registry->getClass( 'class_localization')->getDate( $cache['task_next_run'], 'LONG' )."\n<br /><b>Time now</b> = ".$this->registry->getClass( 'class_localization')->getDate( time(), 'LONG' );
			$stats .= "<br /><b>Timestamp Now</b> = ".time();
			
			$stats .= "<p>MEMBER: last_visit: " . $this->memberData['last_visit'] . " / " . $this->registry->getClass( 'class_localization')->getDate( $this->memberData['last_visit'], 'LONG' ) . "</p>";
			$stats .= "<p>MEMBER: uagent_key: " . $this->memberData['userAgentKey'] . "</p>";
			$stats .= "<p>MEMBER: uagent_type: " . $this->memberData['userAgentType'] . "</p>";
			$stats .= "<p>MEMBER: uagent_version: " . $this->memberData['userAgentVersion'] . "</p>";

			$stats .= "</div>\n</div>";

			$stats .= "<br />\n<div class='tableborder'>\n<div class='subtitle'>Loaded PHP Templates</div><div class='row1' style='padding:6px'>\n";

			$stats .= "<strong>".implode(", ",array_keys($this->output->compiled_templates))."</strong><br />\n";
			$stats .= "<strong>".implode(", ",array_keys($this->output->loaded_templates))."</strong><br />\n";
			$stats .= "<strong>".implode(", ",array_values( $this->registry->getClass('class_localization')->loaded_lang_files ) )."</strong><br />\n";
			$stats .= "</div>\n</div>";

        }

        //-----------------------------------------
        // SQL
        //-----------------------------------------

        if ($this->settings['debug_level'] >= 3)
        {
           	$stats .= "<br />\n<div class='tableborder' style='overflow:auto'>\n<div class='subtitle'>Queries Used</div><div class='row1' style='padding:6px'>";

        	foreach($this->DB->obj['cached_queries'] as $q)
        	{
        		$q = htmlspecialchars($q);
        		$q = str_ireplace( "SELECT" , "<span style='color:red'>SELECT</span>"   , $q );
        		$q = preg_replace( "/^UPDATE/i" , "<span style='color:blue'>UPDATE</span>"  , $q );
        		$q = preg_replace( "/^DELETE/i" , "<span style='color:orange'>DELETE</span>", $q );
        		$q = preg_replace( "/^INSERT/i" , "<span style='color:green'>INSERT</span>" , $q );
        		$q = str_replace( "LEFT JOIN"   , "<span style='color:red'>LEFT JOIN</span>" , $q );

        		$stats .= "<p style='padding:6px;border-bottom:1px solid black'>$q</p>\n";
        	}

        	if ( count( $this->DB->obj['shutdown_queries'] ) )
        	{
				foreach($this->DB->obj['shutdown_queries'] as $q)
				{
					$q = htmlspecialchars($q);
					$q = preg_replace( "/^SELECT/i" , "<span style='color:red'>SELECT</span>"   , $q );
	        		$q = preg_replace( "/^UPDATE/i" , "<span style='color:blue'>UPDATE</span>"  , $q );
	        		$q = preg_replace( "/^DELETE/i" , "<span style='color:orange'>DELETE</span>", $q );
	        		$q = preg_replace( "/^INSERT/i" , "<span style='color:green'>INSERT</span>" , $q );
	        		$q = str_replace( "LEFT JOIN"   , "<span style='color:red'>LEFT JOIN</span>" , $q );

					//$q = preg_replace( "/(".$this->settings['sql_tbl_prefix'].")(\S+?)([\s\.,]|$)/", "<span class='purple'>\\1\\2</span>\\3", $q );

					$stats .= "<div style='background:#DEDEDE'><b>SHUTDOWN:</b> $q</div><br />\n";
				}
        	}

        	$stats .= "</div>\n</div>";
        }
        
		//-----------------------------------------
		// Memory usage
		//-----------------------------------------

		if ( IPS_MEMORY_DEBUG_MODE AND defined( 'IPS_MEMORY_START' ) AND $this->memberData['g_access_cp'] )
		{
			if ( is_array( IPSDebug::$memory_debug ) )
			{
				$memory .= "<br />\n<div class='tableborder'>\n<div class='subtitle'>MEMORY USAGE</div><div class='row1' style='padding:6px'>\n";
				$memory .= "<table cellpadding='4' cellspacing='0' border='0' width='100%'>\n";
				$_c      = 0;
				
				foreach( IPSDebug::$memory_debug as $usage )
				{
					$_col = ( $_c % 2 ) ? '#eee' : '#ddd';
					$_c++;
					
					if ( $usage[1] > 500 * 1024 )
					{
						$_col .= ";color:#D00000";
					}
					else if ( $usage[1] < 10 * 1024 )
					{
						$_col .= ";color:darkgreen";
					}
					else if ( $usage[1] < 100 * 1024 )
					{
						$_col .= ";color:darkorange";
					}
					
					$memory .= "<tr><td width='60%' style='background-color:{$_col}' align='left'>{$usage[0]}</td><td style='background-color:{$_col}' align='left'><strong>".IPSLib::sizeFormat( $usage[1] )."</strong></td></tr>";
				}
				
				$memory .= "</table></div></div>";
			}
			
			$end       = memory_get_usage();
			$peak_end  = function_exists('memory_get_peak_usage') ? memory_get_peak_usage() : memory_get_usage();
			$_used     = $end - IPS_MEMORY_START;
			$peak_used = $peak_end - IPS_MEMORY_START;
			
			$stats	.= $memory;
			$stats	.= "Total Memory Used: " . IPSLib::sizeFormat( $_used ) . " (Peak:" . IPSLib::sizeFormat( $peak_used ).")";
		}

        if ( $stats )
        {
			$stats = "
					  <div align='center' style='width:92%; margin:120px auto 20px auto; overflow:auto;'>
					   <div class='tableborder' align='left' id='debug'>
						<div class='maintitle'>Debug Information (<a href='#' onclick=\"$('debug').toggle(); return false;\">Hide Debug Information</a>)</div>
						 <div style='padding:5px;background:#8394B2;'>{$stats}</div>
					   </div>
					  </div>";
        }

        return $stats;
    }

	/**
	 * Fetch language drop down box
	 *
	 * @access	protected
	 * @return	array
	 */
	protected function html_buildLanguageDropDown()
    {
    	$lang_list = array();
    	$cache     = $this->caches['lang_data'];

    	//-----------------------------------------
		// Roots
		//-----------------------------------------
		
		if ( is_array( $cache ) AND count( $cache ) )
		{
			foreach( $cache as $data )
			{
				if ( $this->member->language_id == $data['lang_id'] )
				{
					$lang_list['options'][]	= array( 'id' => $data['lang_id'], 'title' => $data['lang_title'], 'selected' => true );
					$lang_list['default']	= $data['lang_title'];
				}
				else if( !$this->member->language_id AND $data['lang_default'] )
				{
					$lang_list['options'][]	= array( 'id' => $data['lang_id'], 'title' => $data['lang_title'], 'selected' => true );
					$lang_list['default']	= $data['lang_title'];
				}
				else
				{
					$lang_list['options'][]	= array( 'id' => $data['lang_id'], 'title' => $data['lang_title'], 'selected' => false );
				}
			}
		}
	
		return $lang_list;
    }

	/**
	 * Fetch skin list
	 *
	 * Does what is says up there a bit
	 *
	 * @access	protected
	 * @param	int			Parent id
	 * @param	int			Iteration
	 * @return	array
	 */
	protected function html_fetchSetsDropDown( $parent=0, $iteration=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$output       = array();
		$depthMarkers = "";
		
		if( $iteration )
		{
			for( $i=0; $i<$iteration; $i++ )
			{
				$depthMarkers .= '--';
			}
		}

		//-----------------------------------------
		// Go get 'em
		//-----------------------------------------

		foreach( $this->output->allSkins as $id => $data )
		{
			/* Allowed to use? */
			if ( $data['_youCanUse'] !== TRUE )
			{
				continue;
			}
		
			/* Root skins? */
			if ( count( $data['_parentTree'] ) AND $iteration == 0 )
			{
				continue;
			}
			else if( $iteration > 0 AND (!count( $data['_parentTree'] ) OR $data['_parentTree'][0] != $parent) )
			{
				continue;
			}

			/* Hide? */
			if( $data['set_hide_from_list'] )
			{
				continue;
			}
			
			$_selected = ( $this->skin['set_id'] == $data['set_id'] ) ? true : false;
			
			/* Ok to add... */
			$output[]	= array( 'id' => $data['set_id'], 'depth' => $depthMarkers, 'title' => $data['set_name'], 'selected' => $_selected );
			
			if ( is_array( $data['_childTree'] ) AND count( $data['_childTree'] ) )
			{
				$output	= array_merge( $output, $this->html_fetchSetsDropDown( $data['set_id'], $iteration + 1 ) );
			}
		}

		return $output;
	}
	
	/**
	 * Get faster text
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _getFasterText()
	{
		$texts	= array(
						'Zooom',
						'REALLY fast',
						'Quick',
						'Lightning',
						'Chop chop',
						'Expeditive',
						'Flash',
						'Lickety Split',
						'On the double',
						'Rapid',
						'Velocious',
						'Pronto',
						'Speedy Gonzales',
						'Warp Speed',
						'Double espresso',
						);

		return $texts[ array_rand($texts) ];
	}
}