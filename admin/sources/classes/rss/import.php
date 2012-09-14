<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * RSS Import
 * Owner: Matt Mecham
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		24th November 2009
 * @version		$Revision: 10721 $
 */

/**
 * Simple Example: Import 20 articles that have #ipb in the title and 'ip.board' in the content
 * store the guids we imported to prevent duplicate imports
 *
 * $import = new rss_import( 'http://feed.com/feed.rss', 'blog-5' );
 * $import->load();
 * $import->build( array( 'title'   => '#ipb',
 *						  'content' => 'ip.board',
 *						  'limit'   => 20 ) );
 *
 * while( $row = $import->fetch() )
 * {
 *		# Clean up topic title. Parse BBCode in content and add link back to source
 *		print $import->cleanTitle( $row['title'] ) . '<br />' . $this->parseContent( $row['content'], $row['link'] );
 * }
 *
 * $import->finish();
 */
 
 /**
  * Advanced options
  *
  * Don't add a link back
  * $import->setLinkBack(false);
  *
  * Allow HTML to be imported
  * $import->setParseHtml(true);
  *
  * Set http_auth details
  * $import->setAuthUser('matt');
  * $import->setAuthPass('mypass');
  */	 
	 
class rss_import
{
	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $DB;
	
	/**
	 * Additional error messages
	 *
	 * @access	public
	 * @var		array
	 */
	public $errors = array();
	
	/**
	 * Unique key
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_key = '';
	
	/**
	 * RSS Class handle
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $_rssClass = null;
	
	/**
	 * Feed URL
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_url = '';
	
	/**
	 * Items to import
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_limit = 10;
	
	/**
	 * Items already imported
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_importedCount = 0;
	
	/**
	 * String to match in title
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_titleMatch = '';
	
	/**
	 * String to match in title
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_contentMatch = '';
	
	/**
	 * Array of items
	 *
	 * @access		protected
	 * @var			array
	 */
	protected	$_imported = array();
	
	/**
	 * Maps count to date
	 *
	 * @access		protected
	 * @var			array
	 */
	protected	$__map = array();
	
	/**
	 * GUIDS fetched
	 *
	 * @access		protected
	 * @var			array
	 */
	protected	$__usedGuids = array();
	
	/**
	 * Internal array pointer
	 *
	 * @access		protected
	 * @var			int
	 */
	protected	$__pointer = 0;
	
	/**
	 * Last validated URL
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_validatedUrl = '';
	
	/**
	 * Settings array
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_settings = array( 'AuthUser'    => '',
								  'AuthPass'    => '',
								  'ParseBbcode' => 1,
								  'ParseHtml'   => 0,
								  'LinkBack'    => "<a href=\"{url}\">Source</a>"
								 );
	
	/**
	 * Method constructor
	 *
	 * If you pass false as the key, it will not save out the imported GUIDs
	 * @access	public
	 * @param	string		URL of feed
	 * @param	string		Unique key for the app/page importing (blog-{member_id} for example)
	 * @return	@e void
	 * 
	 */
	public function __construct( $url, $key=false )
	{
		$this->_url = trim( $url );
		$this->_key = trim( $key );
		
		/* Core classes */
		if ( ! is_object( $this->registry ) )
		{
			$this->registry = ipsRegistry::instance();
			$this->DB		= $this->registry->DB();
		}
		
		/* Load RSS Class */
		if ( ! is_object( $this->_rssClass ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
			$this->_rssClass            	=  new $classToLoad();
			$this->_rssClass->rss_max_show	=  100;
			$this->_rssClass->orig_doc_type	= IPS_DOC_CHAR_SET;
		}
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		$this->_settings['LinkBack']	= "<a href=\"{url}\">" . $this->registry->getClass('class_localization')->words['_rssimportsource'] . "</a>";
		
		/* Reset class */
		$this->_reset();
	}
	
	/**
	 * Magic Call method
	 *
	 * @param	string	Method Name
	 * @param	mixed	Method arguments
	 * @return	mixed
	 */
	public function __call( $method, $arguments )
	{
		$firstBit = substr( $method, 0, 3 );
		$theRest  = substr( $method, 3 );
		
		if ( isset( $this->_settings[ $theRest ] ) )
		{
			if ( $firstBit == 'set' )
			{
				$this->_settings[ $theRest ] = $arguments[0];
			}
			else
			{
				return $this->_settings[ $theRest ];
			}
		}
	}
	
	/**
	 * Load
	 *
	 * EXCEPTION CODES
	 * -- Any from validate()
	 */
	public function load()
	{
		/* Reset class */
		$this->_reset();
		
		/* Validate feed */
		try
		{
			$this->validate( $this->_url );
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();
			
			throw new Exception( $msg );
		}
	}
	
	/**
	 * Build a RSS import query
	 *
	 * @access	public
	 * @param	array		Data
	 */
	public function build( $data )
	{
		/* Reset stuff */
		$this->_imported      = array();
		$this->__usedGuids    = array();
		$this->__pointer      = 0;
		$this->__map		  = array();
		$this->errors		  = array();
		
		/* Prep vars */
		$final_guids = array();
		$final_items = array();
		
		if ( isset( $data['title'] ) )
		{
			$this->_titleMatch = $data['title'];
		}
		
		if ( isset( $data['content'] ) )
		{
			$this->_contentMatch = $data['content'];
		}
		
		if ( isset( $data['limit'] ) )
		{
			$this->_limit = intval( $data['limit'] );
		}
		
		/* Ensure we got stuff */
		if ( ! $this->_limit )
		{
			$this->_limit = 50;
		}
		
		/* Loop through the channels */
		foreach ( $this->_rssClass->rss_channels as $channel_id => $channel_data )
		{
			if ( is_array( $this->_rssClass->rss_items[ $channel_id ] ) and count ($this->_rssClass->rss_items[ $channel_id ] ) )
			{
				/* Loop through the items in this channel */
				foreach( $this->_rssClass->rss_items[ $channel_id ] as $item_data )
				{
					/* Item Data */
					$item_data['content']  = $item_data['content']   ? $item_data['content']  : $item_data['description'];
					$item_data['guid']     = md5( $this->_key . ( $item_data['guid'] ? $item_data['guid']     : preg_replace( "#\s|\r|\n#is", "", $item_data['title'].$item_data['link'].$item_data['description'] ) ) );
					$item_data['unixdate'] = intval($item_data['unixdate'])  ? intval($item_data['unixdate']) : time();

					/*  Convert char set? */
					if ( $this->_rssClass->orig_doc_type != $this->_rssClass->doc_type )
					{
						$item_data['title']   = IPSText::convertCharsets( $item_data['title']  , "UTF-8", IPS_DOC_CHAR_SET );
						$item_data['content'] = IPSText::convertCharsets( $item_data['content'], "UTF-8", IPS_DOC_CHAR_SET );
					}

					/* Dates */
					if ( $item_data['unixdate'] < 1 OR $item_data['unixdate'] > time() )
					{
						$item_data['unixdate'] = time();
					}
					
					/* Error check */
					if ( ! $item_data['title'] OR ! $item_data['content'] )
					{
					 	$this->errors[] = $this->registry->getClass('class_localization')->words['_rssimportnotoc'];
						continue;
					}
					
					/* Content check? */
					if ( $this->_contentMatch )
					{
						if ( ! stristr( $item_data['content'], $this->_contentMatch ) )
						{
							continue;
						}
					}
					
					/* Title check? */
					if ( $this->_titleMatch )
					{
						if ( ! stristr( $item_data['title'], $this->_titleMatch ) )
						{
							continue;
						}
					}
					
					/* Add to array */
					$items[ $item_data['guid'] ] = $item_data;
					$check_guids[]               = $item_data['guid'];
				}
			}
		}
		
		/* Check GUIDs */
		if ( ! count( $check_guids ) )
		{
			$rss_error[] = $this->lang->words['im_noitems'];
			continue;
		}
		
		$this->DB->build( array( 'select' => 'rss_guid',
								 'from'   => 'core_rss_imported',
								 'where'  => "rss_foreign_key='" . $this->_key . "' AND rss_guid IN ('".implode( "','", $check_guids )."')" ) );
		$i = $this->DB->execute();
		
		while ( $guid = $this->DB->fetch( $i ) )
		{
			$final_guids[ $guid['rss_guid'] ] = $guid['rss_guid'];
		}
		
		/* Compare GUIDs */
		$item_count = 0;
		
		foreach( $items as $guid => $data )
		{
			if ( in_array( $guid, $final_guids ) )
			{
				continue;
			}
			else
			{
				$item_count++;
				
				/* Make sure each item has a unique date */
				$final_items[ $data['unixdate'].'.'.$item_count ] = $data;
			}
		}

		/* Sort Array */
		krsort( $final_items );
		
		/* Pick off last X */
		$count           = 1;
		$tmp_final_items = $final_items;
		$final_items     = array();
		
		foreach( $tmp_final_items as $date => $data )
		{
			$this->_imported[ $date ] = $data;
			
			if ( $count >= $this->_limit )
			{
				break;
			}
		}
		
		/* now sort it oldest first */
		ksort( $this->_imported );
		
		/* add in map */
		foreach( $this->_imported as $date => $data )
		{
			$this->__map[ $count - 1 ] = $date;
			
			$count++;
		}
		
		reset( $this->_imported );
	}
	
	/**
	 * Fetch a row
	 *
	 * @access	public
	 * @return	array (of data)
	 */
	public function fetch()
	{
		if ( ! count( $this->_imported ) )
		{
			return FALSE;
		}
		
		$item = $this->_imported[ $this->__map[ $this->__pointer ] ];
		
		if ( $item )
		{
			$this->__usedGuids[] = $item['guid'];
			$this->__pointer++;
			
			return $item;
		}
		
		return FALSE;
	}

	/**
	 * Finish up and record which GUIDS were imported
	 *
	 * @access	public
	 * @return	int			Number of feeds added
	 */
	public function finish()
	{
		if ( $this->_key AND count( $this->__usedGuids ) )
		{
			foreach( $this->__usedGuids as $guid )
			{
				$this->DB->insert( 'core_rss_imported', array( 'rss_guid' 		  => $guid,
														  	   'rss_foreign_key'  => $this->_key ) );
			}
		}
		
		$c = count( $this->__usedGuids );
		
		$this->__usedGuids = array();
		$this->_imported   = array();
		$this->__pointer   = 0;
		
		return $c;
	}
	
	/**
	 * Clean title
	 * Cleans up HTML entities, removes tags, etc
	 *
	 * @access		public
	 * @param		string
	 * @return		string - cleaned
	 */
	public function cleanTitle( $title )
	{
		if ( $title )
		{
			$title = str_replace( '&amp;', '&', $title );
			$title = str_replace( array( "\r", "\n" ), ' ', $title );
			$title = str_replace( array( "<br />", "<br>" ), ' ', $title );
			$title = trim( $title );
			$title = strip_tags( $title );
			$title = IPSText::parseCleanValue( $title );
			
			/* Fix up &amp;reg; */
			$title = str_replace( '&amp;reg;', '&reg;', $title );
		}
		
		return $title;
	}
	
	/**
	 * Parse BBCode, HTML, etc
	 *
	 *
	 * @access		public
	 * @param		string
	 * @param		string		[Optional URL for linkback]
	 * @return		string - parsed
	 */
	public function parseContent( $content, $link="" )
	{
		/* Get editor */
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor			= new $classToLoad();
		
		/* Sort post content: Convert HTML to BBCode */
		IPSText::getTextClass( 'bbcode' )->parse_smilies	= 1;
		IPSText::getTextClass( 'bbcode' )->parse_html		= $this->_settings['ParseHtml'];
		IPSText::getTextClass( 'bbcode' )->parse_bbcode		= $this->_settings['ParseBbcode'];
		IPSText::getTextClass( 'bbcode' )->parsing_section	= 'topics';
		
		/* Force the RTE */
		$editor->setRteEnabled( true );

		/* Clean up.. */
		$content = preg_replace( "#<br />(\r)?\n#is", "<br />", $content );
		
		if ( ! $this->_settings['ParseHtml'] )
		{
			$_raw	= stripslashes($content);

			$content = IPSText::getTextClass( 'bbcode' )->preDbParse( $editor->process( $_raw ) );
		}
		else
		{
			$content = stripslashes($content);
		}
		
		/* Add in Show link... */
		if ( $this->_settings['LinkBack'] AND $link )
		{
			$the_link = str_replace( '{url}', trim( $link ), $this->_settings['LinkBack'] );

			if ( $this->_settings['ParseHtml'] )
			{
				$_raw = IPSText::getTextClass( 'bbcode' )->preEditParse( stripslashes($the_link) );
				
				$the_link = "<br /><br />" . IPSText::getTextClass( 'bbcode' )->preDbParse( $editor->process( $_raw ) );
			}
			else
			{
				$the_link = "<br /><br />" . $the_link;
			}
			
			$content .= $the_link;
		}
		
		/* few final things */
		$content = str_replace( '&amp;raquo;', $this->lang->words['_raquo'], $content );
			
		return $content;
	}
	
	/**
	 * Attempt to validate feed
	 *
	 * @access	public
	 * @param	string		Feed URL (uses $this->_url if one is not passed )
	 * @return	bool
	 * EXCEPTION CODES:
	 * HTTP_STATUS_CODE				Incorrect http status code (code returned is added to $this->errors)
	 * RSS_CLASS_ERROR				Error returned from RSS class (errors added to $this->errors)
	 * NO_CHANNELS					RSS feed doesn't have any channels
	 * NO_ITEMS						RSS feed doesn't have any items
	 */
	public function validate( $url='' )
	{
		$url = ( $url ) ? $url : $this->_url;
		
		/* Reset the class */
		$this->_reset();
		
		/* Parse URL */
		$this->_rssClass->parseFeedFromUrl( $url );
			
		/* Validate Data - HTTP Status Code/Text */
		if ( $this->_rssClass->classFileManagement->http_status_code != "200" )
		{
			$this->errors[0] = $this->registry->getClass('class_localization')->words['_rssimportcode'] . $this->_rssClass->classFileManagement->http_status_code;
			//print_r( $this->errors );
			throw new Exception( 'HTTP_STATUS_CODE' );
		}
		
		/* Any errors found? */
		if ( is_array( $this->_rssClass->errors ) and count( $this->_rssClass->errors ) )
		{
			foreach( $this->_rssClass->errors as $error )
			{
				$this->errors[] = $error;
			}
			
			throw new Exception( 'RSS_CLASS_ERROR' );
		}
		
		/* Got any channels? */
		if ( ! is_array( $this->_rssClass->rss_channels ) or ! count( $this->_rssClass->rss_channels ) )
		{
			throw new Exception( 'NO_CHANNELS' );
		}
		
		/* Any Items */
		if ( ! is_array( $this->_rssClass->rss_items ) or ! count( $this->_rssClass->rss_items ) )
		{
			throw new Exception( 'NO_ITEMS' );
		}
		
		/* Last validated URL */
		$this->_validatedUrl = $url;
		
		return TRUE;
	}
	
	/**
	 * Reset RSS class
	 *
	 * @access	protected
	 */
	protected function _reset()
	{
		/* Reset imported count */
		$this->_importedCount = 0;
		$this->_imported      = array();
		$this->__usedGuids    = array();
		$this->__pointer      = 0;
		$this->__map		  = array();
		$this->errors		  = array();
		
		/* Set this imports doc type */
		$this->_rssClass->doc_type 		= IPS_DOC_CHAR_SET;
		
		/* Set this import's authentication */		
		$this->_rssClass->auth_req  = ( $this->_settings['AuthUser'] AND $this->_settings['AuthPass'] ) ? 1 : 0;
		$this->_rssClass->auth_user = $this->_settings['AuthUser'];
		$this->_rssClass->auth_pass = $this->_settings['AuthPass'];
		
		/* Clear RSS object's error cache */
		$this->_rssClass->errors 	= array();
		$this->_rssClass->rss_items = array();

		/* Reset the rss count */
		$this->_rssClass->rss_count =  0;
	}
	
}