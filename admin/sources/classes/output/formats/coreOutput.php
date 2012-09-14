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

interface interface_output
{
	/**
	 * Prints any header information for this output module
	 *
	 * @access	public
	 * @return	@e void		Prints header() information
	 */
	public function printHeader();
	
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
	public function fetchOutput( $output, $title, $navigation, $documentHeadItems, $jsLoaderItems, $extraData=array() );
	
	/**
	 * Finish / clean up after sending output
	 *
	 * @access	public
	 * @return	null
	 */
	public function finishUp();
	
	/**
	 * Adds more items into the document header like CSS / RSS, etc
	 *
	 * @access	public
	 * @return   null
	 */
	public function addHeadItems();
	
	/**
	 * Replace IPS tags
	 * Converts over <#IMG_DIR#>, etc
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function parseIPSTags( $text );
	
	/**
	 * Silent redirect (Redirects without a screen or other notification)
	 *
	 * @access	public
	 * @param	URL
	 * @return	mixed
	 */
	public function silentRedirect( $url );
	
}

class coreOutput
{
	/**
	 * Main output class
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $output;
	
	/**
	 * Header code and status
	 *
	 * @access	 protected
	 * @var	 	 int
	 */
	protected $_headerCode   = 200;
	protected $_headerStatus = 'OK';
	
	/**
	 * Header expiration
	 *
	 * @access	protected
	 * @var		int				Seconds
	 */
	protected $_headerExpire = 0;
	
	/**
	 * Meta tags
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_metaTags = array();
	
	/**
	 * Type of output (redirect / popup / normal)
	 * Some of which will have no meaning to some output engines of course
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_outputType = 'normal';
	
	/**
	 * Store canonical tag
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_canonicalUrl;
	
	/**
	 * Force download flag
	 *
	 * @access	protected
	 * @var		boolean
	 */
	protected $_forceDownload	= false;
	
	/**
	 * Temporary title holder
	 *
	 * @var		string
	 */
	public $_current_page_title	= '';
	
	/**
	 * Constructor
	 * We could use 'extends' and build the registry object up but
	 * we need to use the output handler attached to the registry to 
	 * save spawning new handlers for 'output' which will have different
	 * variables saved in navigation, addToHead, etc
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( output $output )
	{
		/* Make object */
		$this->output     =  $output;
		$this->registry   =  $output->registry;
		$this->DB	      =  $output->DB;
		$this->settings   =& $output->settings;
		$this->request    =& $output->request;
		$this->lang	      =  $output->lang;
		$this->member     =  $output->member;
		$this->memberData =  $output->memberData;
		$this->cache	  =  $output->cache;
		$this->caches	  =  $output->caches;
		$this->skin		  =  $output->skin;
		
		/* Check for forceDownload */
		if ( !empty( $_GET['forceDownload'] ) AND ( $_GET['_k'] == $this->member->form_hash ) )
		{
			$this->_forceDownload = true;
		}
	}
	
	/**
	 * Set forced download mode
	 *
	 * @access	protected
	 * @param	boolean
	 */
	protected function forceDownload( $bool )
	{
		$this->_forceDownload = ( $bool === true ) ? true : false;
	}
	
	/**
	 * Set the cache expiration in seconds
	 *
	 * @access	public
	 * @param	int			Seconds to expire (60 == 1 minute, etc)
	 */
	public function setCacheExpirationSeconds( $seconds='' )
	{
		$this->_headerExpire = intval( $seconds );
	}
	
	/**
	 * Set the header code
	 *
	 * @access	public
	 * @param	int			Header code (200/301, etc)
	 * @param	string		[Optional status if omitted, the function will best guess]
	 */
	public function setHeaderCode( $code, $status='' )
	{
		$this->_headerCode   = intval( $code );
		$this->_headerStatus = $status;
		
		if ( ! $this->_headerStatus )
		{
			switch( $this->_headerCode )
			{
				case 200:
					$this->_headerStatus = 'OK';
				break;
				
				case 301:
					$this->_headerStatus = 'Moved Permanently';
				break;
				
				case 302:
					$this->_headerStatus = 'Moved Temporarily';
				break;
				
				case 403:
					$this->_headerStatus = 'Forbidden';
				break;

				case 404:
					$this->_headerStatus = 'Not Found';
				break;
				
				case 500:
					$this->_headerStatus = 'Internal Server Error';
				break;
				
				case 503:
					$this->_headerStatus = 'Service Unavailable';
				break;
			}
		}
	}
	
	/**
	 * Add Canonical Tag
	 * <code>$output->addCanonicalTag( 'showtopic=xx', 'my-test-topic', 'showtopic' );</code>
	 *
	 * @access	public
	 * @param	string		URL bit (showtopic=x)
	 * @param	string		SEO Title (my-test-topic)
	 * @param	string		SEO Template (showtopic)
	 * @return	@e void
	 */
	public function addCanonicalTag( $urlBit, $seoTitle, $seoTemplate )
	{
		/* Build it */
		if ( $urlBit AND $seoTemplate )
		{
			$url = $this->registry->getClass('output')->buildSEOUrl( $urlBit, 'publicNoSession', $seoTitle, $seoTemplate );

			/* Strip off /index$ */
			if ( substr( $url, -6 ) == '/index' AND IPS_DEFAULT_PUBLIC_APP == 'forums' )
			{
				$url = substr_replace( $url, '', -5 );
			}
			
			/* Store it */
			$this->_canonicalUrl = $url;
			
			if ( $url )
			{ 
				$this->registry->getClass('output')->addToDocumentHead( 'raw' , '<link id="ipsCanonical" rel="canonical" href="' . $url . '" />' );
			}
		}
	}
	
	/**
	 * Fetch Canonical Tag
	 *
	 * @return	@e mixed	String if an url is availble otherwise FALSE
	 */
	public function getCanonicalUrl()
	{
		return ( $this->_canonicalUrl ) ? $this->_canonicalUrl : false;
	}
	
	/**
	 * Add meta tag
	 * <code>$output->addMetaTag( 'description', 'This is a short description' );</code>
	 *
	 * @param	string		$tag		Tag name
	 * @param	string		$content	Tag content
	 * @param	boolean		$encode		Encode content
	 * @param	integer		$trimLen	Length to trim to (default 500)
	 * @return	@e void
	 * @link	http://community.invisionpower.com/tracker/issue-22826-case-sensitivity-in-meta-tags/
	 * @link	http://community.invisionpower.com/tracker/issue-32572-bbcode-included-in-meta-description
	 */
	public function addMetaTag( $tag, $content, $encode=true, $trimLen=500 )
	{
		$encode		= ( $encode === FALSE ) ? FALSE : TRUE;
		$trimLen	= ( $trimLen - 3 );
		$tag		= strtolower($tag);		// 'ROBOTS' should overwrite 'robots'
		
		switch( $tag )
		{
			case 'description':
				/* Clear out 'Quote' if it's present in the bbcode parsed content */
				$content    = str_replace( array( '"', "'" ), '', $content );
				$content	= preg_replace( '/\<p class=\'citation\'\>.+?\<\/p\>/ims', '', $content );
				$content    = strip_tags( IPSText::stripAttachTag( $content ) );
				
				# Hebrew chars screw up Facebook sharer
				if ( $this->member->iAmFacebook )
				{
					$content = $this->encodeMetaTagContent( $content, true );
				}
				
				# There is no max value, but we trim so we don't bloat the output.
				# It's not just search engines that use this, also link sharing services
				# pick up the meta description.
				$content = IPSText::truncate( $content, $trimLen );
			break;
			case 'keywords':
				if ( $encode === TRUE )
				{
					$content	= IPSText::stripAttachTag( $content );
					
					//Bug #15323 breaks accented characters, etc
					//$content = strtolower( preg_replace( "/[^0-9a-zA-Z ]/", "", preg_replace( "/&([^;]+?);/", "", $content ) ) );
					$content = str_replace( array( '.', ',', '!', ':', ';', "'", '"', '@', '%', '*', '(', ')' ), '', preg_replace( "/&([^;]+?);/", "", $content ) );
					//Also breaks accented characters
					//$_vals   = preg_split( '/\s+?/', $content, -1, PREG_SPLIT_NO_EMPTY );
					$_vals   = explode( ' ', $content );
					$_sw     = explode( ',', $this->lang->words['_stopwords_'] );
					$_fvals  = array();
					$_limit  = 30;
					$_c      = 0;
					
					if ( is_array( $_vals ) )
					{
						foreach( $_vals as $_v )
						{
							if ( strlen( $_v ) >= 3 AND ! in_array( $_v, array_values( $_fvals ) ) AND ! in_array( $_v, $_sw ) )
							{
								$_fvals[] = $_v;
							}
							
							if ( $_c >= $_limit )
							{
								break;
							}
							
							$_c++;
						}
					}
					
					$content = implode( ',', $_fvals );
				}
				else
				{
					$content = str_replace( array( '.', ',', '!', ':', ';', "'", '"', '@', '%', '*', '(', ')' ), '', preg_replace( "/&([^;]+?);/", "", $content ) );
				}
				
				$content = IPSText::truncate( $content, $trimLen );
				
			break;
		}
				
		$this->_metaTags[ $tag ] = ( $encode === TRUE ) ? $this->encodeMetaTagContent( preg_replace( '/&amp;#(\d+?);/', "&#\\1;", htmlspecialchars( $content ) ) ) : $content;
	}
	
	/**
	 * Encodes meta tag content so its safe for readers, etc
	 * 
	 * @param	string		$content		Meta tag content
	 * @return	@e string
	 */
	public function encodeMetaTagContent( $content, $makeUTF8Safe=false )
	{
		$content = IPSText::htmlspecialchars( $content );
		
		# Hebrew chars screw up Facebook sharer
		if ( $makeUTF8Safe === true && IPS_DOC_CHAR_SET == 'UTF-8' && IPSText::isUTF8( $content ) )
		{
			$content = IPSText::utf8ToEntities( $content );
		}
		
		return $content;
	}
	
	/**
	 * Retrieve meta tags
	 * 
	 * <code>$output->getMetaTags( 'description' );
	 * $output->getMetaTags();</code>
	 *
	 * @param	string		$tag		Tag name (optional)
	 * @return	@e mixed	Array of meta tags, or meta tag specified if tag name is supplied
	 */
	public function getMetaTags( $tag='' )
	{
		if( $tag )
		{
			return isset( $this->_metaTags[ $tag ] ) ? $this->_metaTags[ $tag ] : '';
		}
		else
		{
			return $this->_metaTags;
		}
	}
	
	/**
	 * initiate
	 * Function to do global stuff
	 *
	 * @return	@e void
	 */
	public function core_initiate()
	{
		//-----------------------------------------
		// Server load
		//-----------------------------------------
		
		if ( ! ipsRegistry::$server_load  )
        {
        	ipsRegistry::$server_load = '--';
        }
        
        if( strpos( strtolower( PHP_OS ), 'win' ) === 0 )
		{
			ipsRegistry::$server_load = ipsRegistry::$server_load . '%';
		}
		
		//-----------------------------------------
		// Set up defaults
		//-----------------------------------------
		
		$this->memberData['msg_count_new']   = ( ! empty( $this->memberData['msg_count_new'] ) ) ? intval($this->memberData['msg_count_new']) : 0;
        $this->memberData['msg_count_total'] = ( ! empty( $this->memberData['msg_count_total'] ) ) ? intval($this->memberData['msg_count_total']) : 0;
	}
	
	/**
	 * Set output type
	 *
	 * @param	string		$type		Output type
	 * @return	@e void
	 */
	public function core_setOutputType( $type )
	{
		$this->_outputType = $type;
	}
	
	/**
	 * Fetch navigation tabs
	 *
	 * @return	@e array
	 */
	public function core_fetchApplicationData()
	{
		$tabs = array();
		
		/* Check for cache */
		if ( ! isset( $this->caches['navigation_tabs'] ) OR ! count( $this->caches['navigation_tabs'] ) )
		{
			$this->registry->cache()->rebuildCache( 'navigation_tabs', 'global' );
			
			$this->caches['navigation_tabs'] = $this->registry->cache()->getCache('navigation_tabs');
		}
		
		foreach( $this->caches['navigation_tabs'] as $tab )
		{
			if ( IPSLib::appIsInstalled( $tab['app'] ) !== TRUE )
			{
				continue;
			}
			
			/* Sort if we can view that tab */
			$show = TRUE;
			
			if ( $tab['app'] == 'core' OR ipsRegistry::$applications[ $tab['app'] ]['app_hide_tab'] OR ( count( $tab['groups'] ) AND !IPSMember::isInGroup( $this->memberData, array_diff( array_keys( $this->caches['group_cache'] ), $tab['groups'] ) ) ) )
			{
				$show = FALSE;
			}
			
			/* Mark tab as active? */
			$active = FALSE;
			
			if ( ipsRegistry::$current_application == $tab['app'] )
			{
				if ( $tab['module'] )
				{
					if ( ipsRegistry::$current_module == $tab['module'] )
					{
						$active = TRUE;
					}
				}
				else
				{
					$active = TRUE;
				}
			}
			
			/* Sort out link */
			$link     = '';
			$template = '';
			
			switch( $tab['app'] )
			{
				case 'forums':
					$link = 'act=idx';
					break;
				case 'members':
					$link     = 'app=members&amp;module=list';
					$template = 'members_list';
					break;
				default:
					$link = "app={$tab['app']}";
					
					if ( $tab['module'] )
					{
						$link .= "&amp;module={$tab['module']}";
					}
					break;
			}
			
			$tabs[ $tab['app'] ] = array( 'app_dir'		 => $tab['app'],
										  'app_module'	 => $tab['module'],
										  'app_title'	 => $tab['title'],
										  'app_show'	 => $show,
										  'app_active'	 => $active,
										  'app_link'	 => $link,
										  'app_seotitle' => 'false',
										  'app_template' => $template,
										  'app_base'	 => 'public',
										 );
											
		}
		
		return $tabs;
	}
	
	/**
	 * Add items into the document head
	 * Simple redirect function
	 *
	 * @access	protected
	 * @param	string		Type of head item
	 * @param	mixed 		Data
	 * @return	null
	 */
	protected function addToDocumentHead( $type, $data )
	{
		return $this->output->addToDocumentHead( $type, $data );
	}
	
	/**
	 * Add CSS files
	 *
	 * @access	public
	 * @param	string		inline or import
	 * @param	string		Data to add
	 * @return	@e void
	 */
	public function addCSS( $type, $data )
	{
		if( $type == 'inline' )
		{
			$this->_css['inline'][]	= array(
											'content'	=> $data,
											);
		}
		else if( $type == 'import' )
		{
			if( !isset($this->_css['import'][$data]) )
			{
				$this->_css['import'][$data] = array(
													'content'	=> $data,
												);
			}
		}
	}
}