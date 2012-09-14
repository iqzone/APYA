<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * RSS handler: handles importing of RSS documents and exporting of RSS v2 documents
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Kernel
 * @link		http://www.invisionpower.com
 * @since		Monday 5th May 2008 14:00
 * @version		$Revision: 10721 $
 *
 * EXAMPLE: (CREATING AN RSS FEED)
 * <code>
 * $rss = new classRss();
 * 
 * $channel_id = $rss->createNewChannel( array( 'title'       => 'My RSS Feed',
 * 											  	 'link'        => 'http://www.mydomain.com/rss/',
 * 											     'description' => 'The latest news from my <blog>',
 * 											     'pubDate'     => $rss->formatDate( time() ),
 * 											     'webMaster'   => 'me@mydomain.com (Matt Mecham)' ) );
 * 											   
 * $rss->addItemToChannel( $channel_id, array( 'title'       => 'Hello World!',
 * 										     'link'        => 'http://www.mydomain.com/blog/helloworld.html',
 * 										     'description' => 'The first ever post!',
 * 										     'content'     => 'Hello world! This is the blog content',
 * 										     'pubDate'	   => $rss->formatDate( time() ) ) );
 * 										   
 * $rss->addItemToChannel( $channel_id, array( 'title'       => 'Second Blog!!',
 * 										     'link'        => 'http://www.mydomain.com/blog/secondblog.html',
 * 										     'description' => 'The second ever post!',
 * 										     'content'     => 'More content',
 * 										     'pubDate'	   => $rss->formatDate( time() ) ) );
 * 										   
 * $rss->addImageToChannel( $channel_id, array( 'title'     => 'My Image',
 * 											   'url'       => 'http://mydomain.com/blog/image.gif',
 * 											   'width'     => '110',
 * 											   'height'    => '400',
 * 											   'description' => 'Image title text' ) );
 * 											 
 * $rss->createRssDocument();
 * 
 * print $rss->rss_document;
 * </code>
 * EXAMPLE: (READ AN RSS FEED)
 * <code>
 * $rss = new classRss();
 *
 * $rss->parseFeedFromUrl( 'http://www.mydomain.com/blog/rss/' );
 *
 * foreach( $rss->rss_channels as $channel_id => $channel_data )
 * {
 * 	print "Title: ".$channel_data['title']."<br />";
 * 	print "Description; ".$channel_data['description']."<br />";
 * 	
 * 	foreach( $rss->rss_items[ $channel_id ] as $item_id => $item_data )
 * 	{
 * 		print "Item title: ".$item_data['title']."<br />";
 * 		print "Item URL: ".$item_data['link']."<br />";
 * 		print $item_data['content']."<hr>";
 * 	}
 * 	
 * 	print $rss->formatImage( $rss->rss_images[ $channel_id ] );
 * }
 * </code>
 */

if ( ! defined( 'IPS_KERNEL_PATH' ) )
{
	/**
	 * Define classes path
	 */
	define( 'IPS_KERNEL_PATH', dirname(__FILE__) );
}

class classRss
{	
	/**
	 * Class file management object
	 *
	 * @var 	classFileManagement
	 */
	public $classFileManagement;
	
	/**
	 * DOC type
	 *
	 * @var 	string
	 */
	public $doc_type 		= 'UTF-8';
	
	/**
	 * Original DOC type
	 *
	 * @var 	string
	 */
	public $orig_doc_type	= "";
	
	/**
	 * Error capture
	 *
	 * @var 	array
	 */
	public $errors 			= array();
	
	/**
	 * Use sockets flag
	 *
	 * @var 	integer
	 */
	public $use_sockets		= 1;
	
	/**#@+
	 * Work item
	 *
	 * @var 	integer 
	 */
	protected $in_item		= 0;
	protected $in_image		= 0;
	protected $in_channel		= 0;
	public  $rss_count		= 0;
	public  $rss_max_show	= 3;
	protected $cur_item		= 0;
	protected $cur_channel	= 0;
	protected $set_ttl		= 60;
	protected $tag			= "";
	/**#@-*/
	
	/**#@+
	 * RSS Items
	 *
	 * @var 	array 
	 */
	public $rss_items		= array();
	public $rss_channels	= array();
	protected $rss_headers   = array();
	protected $rss_images    = array();
	protected $rss_tag_names = array();
	/**#@-*/
	
	/**#@+
	 * RSS Parse Items
	 *
	 * @var 	string 
	 */
	protected $rss_title;
	protected $rss_description;
	protected $rss_link;
	protected $rss_date;
	protected $rss_creator;
	protected $rss_content;
	protected $rss_category;
	protected $rss_guid;
	/**#@-*/
	
	/**#@+
	 * RSS Parse Images
	 *
	 * @var 	string 
	 */
	protected $rss_img_url;
	protected $rss_img_title;
	protected $rss_img_link;
	protected $rss_img_width;
	protected $rss_img_height;
	protected $rss_img_desc;
	/**#@-*/
	
	/**#@+
	 * RSS Channel items
	 *
	 * @var 	string 
	 */
	protected $rss_chan_title;
	protected $rss_chan_link;
	protected $rss_chan_desc;
	protected $rss_chan_date;
	protected $rss_chan_lang;
	/**#@-*/
	
	/**#@+
	 * Create: Channels
	 *
	 * @var 	array 
	 */
	protected $channels       = array();
	protected $items          = array();
	protected $channel_images = array();
	/**#@-*/
	
	/**#@+
	 * Set Authentication
	 *
	 * @var 	string
	 */
	public $auth_req 			= 0;
	public $auth_user;
	public $auth_pass;
	/**#@-*/
	
	/**
	 * Final RSS Document
	 *
	 * @var		string
	 */
	public $rss_document		= '';
	
	/**
	 * Convert char set
	 *
	 * @var		integer
	 */
	public $convert_charset		= 1;
	
	/**
	 * Convert newlines
	 *
	 * @var		integer
	 */
	public $collapse_newlines	= 0;

	/**
	 * HTML decoding needed
	 *
	 * @var		bool
	 */
	protected $htmlDecodingNeeded	= false;
	
	/**
	 * Specify user agent string for request
	 *
	 * @var		string
	 */
	public $userAgent			= '';
	
	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		$this->rss_tag_names = array( 'ITEM'            => 'ITEM',
									  'IMAGE'           => 'IMAGE',
									  'URL'             => 'URL',
									  'CONTENT:ENCODED' => 'CONTENT:ENCODED',
									  'CONTENT'			=> 'CONTENT',
									  'DESCRIPTION'     => 'DESCRIPTION',
									  'TITLE'			=> 'TITLE',
									  'LINK'		    => 'LINK',
									  'CREATOR'         => 'CREATOR',
									  'PUBDATE'		    => 'DATE',
									  'DATE'		    => 'DATE',
									  'DC:CREATOR'      => 'CREATOR',
									  'DC:DATE'	        => 'DATE',
									  'DC:LANGUAGE'     => 'LANGUAGE',
									  'WEBMASTER'       => 'WEBMASTER',
									  'LANGUAGE'        => 'LANGUAGE',
									  'CHANNEL'         => 'CHANNEL',
									  'CATEGORY'	    => 'CATEGORY',
									  'GUID'			=> 'GUID',
									  'WIDTH'			=> 'WIDTH',
									  'HEIGHT'			=> 'HEIGHT',
									);
	}
	
	/**
	 * Create the RSS document
	 *
	 * @return	@e void
	 */
	public function createRssDocument()
	{
		if ( ! count( $this->channels ) )
		{
			$this->errors[] = "No channels defined";
		}
		
		$this->rss_document  = '<?xml version="1.0" encoding="'.$this->doc_type.'" ?'.'>'."\n";
		$this->rss_document .= '<rss version="2.0">'."\n";
		
		//-------------------------------
		// Add channels
		//-------------------------------
		
		foreach( $this->channels as $idx => $channel )
		{
			$tmp_data = "";
			$had_ttl  = 0;
			
			//-------------------------------
			// Add channel data
			//-------------------------------
			
			foreach( $channel as $tag => $data )
			{
				if ( strtolower($tag) == 'ttl' )
				{
					$had_ttl = 1;
				}
				$tmp_data .= "\t<" . $tag . ">" . $this->_xmlEncodeString($data) . "</" . $tag . ">\n";
			}
			
			//-------------------------------
			// Added TTL?
			//-------------------------------
			
			if ( ! $had_ttl )
			{
				$tmp_data .= "\t<ttl>" . intval($this->set_ttl) . "</ttl>\n";
			}
			
			//-------------------------------
			// Got image?
			//-------------------------------
			
			if ( isset($this->channel_images[ $idx ]) AND is_array( $this->channel_images[ $idx ] ) AND count( $this->channel_images[ $idx ] ) )
			{
				foreach( $this->channel_images[ $idx ] as $image )
				{
					$tmp_data .= "\t<image>\n";
					
					foreach( $image as $tag => $data )
					{
						$tmp_data .= "\t\t<" . $tag . ">" . $this->_xmlEncodeString($data) . "</" . $tag . ">\n";
					}
					
					$tmp_data .= "\t</image>\n";
				}
			}
			
			//-------------------------------
			// Add item data
			//-------------------------------
			
			if ( is_array( $this->items[ $idx ] ) and count( $this->items[ $idx ] ) )
			{
				foreach( $this->items[ $idx ] as $item )
				{
					$tmp_data .= "\t<item>\n";
					
					foreach( $item as $tag => $data )
					{
						$extra = "";
						
						if ( $tag == 'guid' AND ! strstr( $data, 'http://' ) )
						{
							$extra = ' isPermaLink="false"';
						}
						
						$tmp_data .= "\t\t<" . $tag . $extra . ">" . $this->_xmlEncodeString($data) . "</" . preg_replace( '#^(\S+?)(\s.*$|$)#', "\\1", $tag ) . ">\n";
					}
					
					$tmp_data .= "\t</item>\n";
				}
			}
			
			//-------------------------------
			// Put it together...
			//-------------------------------
			
			$this->rss_document .= "<channel>\n";
			$this->rss_document .= $tmp_data;
			$this->rss_document .= "</channel>\n";
		}
		
		$this->rss_document .= "</rss>";
		
		//-------------------------------
		// Clean up
		//-------------------------------
		
		$this->channels       = array();
		$this->items          = array();
		$this->channel_images = array();
	}
	
	/**
	 * Create RSS 2.0 document: Add channel and return its ID
	 *
	 * title, link, description,language,pubDate,lastBuildDate,docs,generator
	 * managingEditor,webMaster
	 *
	 * @param	array 		Data to add
	 * @return	@e integer
	 */
	public function createNewChannel( $in=array() )
	{
		$this->channels[ $this->cur_channel ] = $in;
		
		//-------------------------------
		// Inc. and return
		//-------------------------------
		
		$return = $this->cur_channel;
		
		$this->cur_channel++;
		
		return $return;
	}
	
	/**
	 * Create RSS 2.0 document: Add channel image item
	 *
	 *
	 * @param	integer		Channel ID
	 * @param	string		Image tag (formatted through formatImage)
	 * @return	@e void
	 * @see		formatImage()
	 */
	public function addImageToChannel( $channel_id=0, $in='' )
	{
		$this->channel_images[ $channel_id ][] = $in;
	}
	
	/**
	 * Create RSS 2.0 document: Add item
	 *
	 * title,description,pubDate,guid,content,category,link
	 *
	 * @param	integer 	Channel ID
	 * @param	array		Array of item variables
	 * @return	@e void
	 */
	public function addItemToChannel( $channel_id=0, $in=array() )
	{
		$this->items[ $channel_id ][] = $in;
	}
	
	/**
	 * Create RSS 2.0 document: Format Image and return the HTML
	 *
	 * @param	array		Array of item variables
	 * @return	@e string
	 */
	public function formatImage( $in=array() )
	{
		if ( ! $in['url'] )
		{
			$this->errors[] = "Cannot format image, not enough input";
		}
		
		$title  = "";
		$alt    = "";
		$width  = "";
		$height = "";
		
		if ( $in['description'] )
		{
			$title = " title='".$this->_xmlEncodeAttribute( $in['description'] )."' ";
		}
		
		if ( $in['title'] )
		{
			$alt = " alt='".$this->_xmlEncodeAttribute( $in['title'] )."' ";
		}
		
		if ( $in['width'] )
		{
			if ( $in['width'] > 144 )
			{
				$in['width'] = 144;
			}
			
			$width = " width='".$this->_xmlEncodeAttribute( $in['width'] )."' ";
		}
		
		if ( $in['height'] )
		{
			if ( $in['height'] > 400 )
			{
				$in['height'] = 400;
			}
			
			$height = " height='".$this->_xmlEncodeAttribute( $in['height'] )."' ";
		}
		
		//-------------------------------
		// Draw image
		//-------------------------------
		
		$img = "<img src='" . $in['url'] . "' {$title} {$alt} {$width} {$height} />";
		
		//-------------------------------
		// Linked?
		//-------------------------------
		
		if ( $in['link'] )
		{
			$img = "<a href='" . $in['link'] . "'>" . $img . "</a>";
		}
		
		return $img;
	}
	
	/**
	 * Create RSS 2.0 document: Format unixdate to rfc date
	 *
	 * @param	integer		Unix timestamp
	 * @return	@e string
	 */
	public function formatDate( $time )
	{
		return date( 'r', $time );
	}
	
	/**
	 * Extract: Parse RSS document from URL
	 *
	 * @param	string		URI
	 * @return	@e boolean
	 */
	public function parseFeedFromUrl( $feed_location )
	{
		//-----------------------------------------
		// Load file management class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'/classFileManagement.php' );/*noLibHook*/
		$this->classFileManagement = new classFileManagement();
		
		$this->classFileManagement->use_sockets = $this->use_sockets;
		
		$this->classFileManagement->auth_req  = $this->auth_req;
		$this->classFileManagement->auth_user = $this->auth_user;
		$this->classFileManagement->auth_pass = $this->auth_pass;
		$this->classFileManagement->userAgent = $this->userAgent;
		
		//-------------------------------
		// Reset arrays
		//-------------------------------
		
		$this->rss_items    = array();
		$this->rss_channels = array();
		
		//-------------------------------
		// Get data
		//-------------------------------
		
		$data = $this->classFileManagement->getFileContents( $feed_location );
		
		if ( count( $this->classFileManagement->errors ) )
		{
			$this->errors = $this->classFileManagement->errors;
			return FALSE;
		}
		
		$_foundIn	= '';

		if( preg_match( "#encoding=[\"'](\S+?)[\"']#si", $data, $matches ) )
		{
			$this->orig_doc_type	= strtoupper($matches[1]);
			$_foundIn				= 'encoding';
		}
		
		if( !$this->orig_doc_type AND preg_match( "#charset=(\S+?)#si", $data, $matches ) )
		{
			$this->orig_doc_type	= strtoupper($matches[1]);
			$_foundIn				= 'charset';
		}
		
		//-----------------------------------------
		// If feed charset isn't supportd by XML lib,
		// convert to UTF-8
		// Edit - ALWAYS use utf-8.  A wide html entity (&#8216;) is
		// valid in iso-8859-1 output because it is an entity, however
		// the XML library tries to convert all entities to their appropriate
		// character which corrupts it.
		// @link	http://community.invisionpower.com/tracker/issue-33588-rss-import-character-issue
		// @link	http://us.php.net/manual/en/function.xml-set-character-data-handler.php#35065
		//-----------------------------------------
		
		$supported_encodings	= array( "UTF-8"/*, "ISO-8859-1", "US-ASCII"*/ );
		$charset				= ( $this->orig_doc_type AND in_array( $this->orig_doc_type, $supported_encodings ) ) ? $this->orig_doc_type : "UTF-8";

		if ( $this->convert_charset AND $data )
		{
			if ( $charset != $this->orig_doc_type )
			{
				$data = IPSText::convertCharsets( $data, $this->orig_doc_type, $charset );
				
				# Replace any char-set= data
				if( $_foundIn == 'encoding' )
				{
					$data = preg_replace( "#encoding=[\"'](\S+?)[\"']#si", "encoding=\"" . $charset . "\"", $data );
				}
				else
				{
					$data = preg_replace( "#charset=(\S+?)#si"           , "charset=" . $charset, $data );
				}
			}
		}

		//-------------------------------
		// Generate XML parser
		//-------------------------------

		$xml_parser = xml_parser_create( $charset );
		xml_set_element_handler(       $xml_parser, array( &$this, "_parseStartElement" ), array( &$this, "_parseEndElement") );
		xml_set_character_data_handler($xml_parser, array( &$this, "_parseCharacterData" ) );
		
		//-------------------------------
		// Parse data
		//-------------------------------
		
		if ( ! xml_parse( $xml_parser, $data ) )
		{
			$this->errors[] = sprintf("XML error: %s at line %d",  xml_error_string( xml_get_error_code($xml_parser) ), xml_get_current_line_number($xml_parser) );
		}
		
		//-------------------------------
		// Free memory used by XML parser
		//-------------------------------
		
		@xml_parser_free($xml_parser);

		return TRUE;
	}
	
	/**
	 * Extract: Parse RSS document from file
	 *
	 * @param	string		Path
	 * @return	@e void
	 */
	public function parseFeedFromFile( $feed_location )
	{
		//-------------------------------
		// Alias...
		//-------------------------------
		
		$this->parseFeedFromUrl( $feed_location );
	}
	
	/**
	 * Extract: Parse RSS document from data
	 *
	 * @param	string		Raw RSS data
	 * @return	@e void
	 */
	public function parseFeedFromData( $data )
	{
		//-------------------------------
		// Reset arrays
		//-------------------------------
		
		$this->rss_items    = array();
		$this->rss_channels = array();
		$this->cur_channel  = 0;
		
		//-------------------------------
		// Generate XML parser
		//-------------------------------
		
		$xml_parser = xml_parser_create( $this->doc_type );
		xml_set_element_handler(       $xml_parser, array( &$this, "_parseStartElement" ), array( &$this, "_parseEndElement") );
		xml_set_character_data_handler($xml_parser, array( &$this, "_parseCharacterData" ) );
		
		
		if ( ! xml_parse( $xml_parser, $data, TRUE ) )
		{
			$this->errors[] = sprintf("XML error: %s at line %d",  xml_error_string( xml_get_error_code($xml_parser) ), xml_get_current_line_number($xml_parser) );
		}
		
		//-------------------------------
		// Free memory used by XML parser
		//-------------------------------
		
		xml_parser_free($xml_parser);
	}
	
	/**
	 * Extract: Call back function for element handler
	 *
	 * @param	object		Parser object
	 * @param	string		Tag name
	 * @param	array		Attributes
	 * @return	@e void
	 */
	protected function _parseStartElement( $parser, $name, $attrs )
	{
		//-------------------------------
		// Just in case
		//-------------------------------
		
		$name = strtoupper($name);
		
		if ( $this->in_item )
		{
			$this->in_item++;
			$this->tag = $this->rss_tag_names[ $name ];
		}
		
		if ( $this->in_image )
		{
			$this->in_image++;
			$this->tag = $this->rss_tag_names[ $name ];
		}
		
		if ( $this->in_channel )
		{
			$this->in_channel++;
			$this->tag = isset($this->rss_tag_names[ $name ]) ? $this->rss_tag_names[ $name ] : '';
		}
		
		if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "ITEM" )
		{
			$this->in_item = 1;
		} 
		else if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "IMAGE")
		{
			$this->in_image = 1;
		}
		else if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "CHANNEL")
		{
			$this->in_channel = 1;
		}
		
		if( isset( $attrs['html'] ) AND $attrs['html'] == 1 )
		{
			$this->htmlDecodingNeeded	= true;
		}
	}
	
	/**
	 * Extract: Call back function for element handler
	 *
	 * @param	object		Parser object
	 * @param	string		Tag name
	 * @return	@e void
	 */
	protected function _parseEndElement( $parser, $name )
	{
		//-------------------------------
		// Just in case
		//-------------------------------
		
		$name = strtoupper($name);
		
		if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "IMAGE" )
		{
			$this->rss_images[ $this->cur_channel ]['url']         = $this->rss_img_image;
			$this->rss_images[ $this->cur_channel ]['title']       = $this->rss_img_title;
			$this->rss_images[ $this->cur_channel ]['link']        = $this->rss_img_link;
			$this->rss_images[ $this->cur_channel ]['width']       = $this->rss_img_width;
			$this->rss_images[ $this->cur_channel ]['height']      = $this->rss_img_height;
			$this->rss_images[ $this->cur_channel ]['description'] = $this->rss_img_desc;
			
			$this->_killImageElements();
			$this->in_image = 0;
		}
		else if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "CHANNEL" )
		{
			//-------------------------------
			// Add data
			//-------------------------------
			
			$this->rss_channels[ $this->cur_channel ]['title']       = $this->_formatString($this->rss_chan_title);
			$this->rss_channels[ $this->cur_channel ]['link']        = $this->_formatString($this->rss_chan_link);
			$this->rss_channels[ $this->cur_channel ]['description'] = $this->_formatString($this->rss_chan_desc);
			$this->rss_channels[ $this->cur_channel ]['date']        = $this->_formatString($this->rss_chan_date);
			$this->rss_channels[ $this->cur_channel ]['unixdate']    = @strtotime($this->_formatString($this->rss_chan_date));
			$this->rss_channels[ $this->cur_channel ]['language']    = $this->_formatString($this->rss_chan_lang);
			
			//-------------------------------
			// Increment item
			//-------------------------------
			
			$this->cur_channel++;
			
 			//-------------------------------
			// Clean up
			//-------------------------------
			
			$this->_killChannelElements();
			$this->in_channel = 0;
		}
		else if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "ITEM" )
		{
			if ( $this->rss_count < $this->rss_max_show )
			{
				$this->rss_count++;
				
				//-------------------------------
				// Kludge for RDF which closes
				// channel before first item
				// I'm staring at you Typepad
				//-------------------------------
				
				if ( $this->cur_channel > 0 AND ( ! is_array($this->rss_items[ $this->cur_channel ] ) ) )
				{
					$this->cur_channel--;
				}
				
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['title']       = $this->rss_title;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['link']        = $this->rss_link;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['description'] = $this->rss_description;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['content']     = $this->rss_content;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['creator']     = $this->rss_creator;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['date']        = $this->rss_date;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['unixdate']    = trim($this->rss_date) != "" ? strtotime($this->rss_date) : time();
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['category']    = $this->rss_category;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['guid']        = $this->rss_guid;
				
				//-------------------------------
				// Increment item
				//-------------------------------
				
				$this->cur_item++;
				
				//-------------------------------
				// Clean up
				//-------------------------------
				
				$this->_killElements();
				$this->in_item = 0;
			}
			else if ($this->rss_count >= $this->rss_max_show)
			{
				//-------------------------------
				// Clean up
				//-------------------------------
				
				$this->_killElements();
				$this->in_item = 0;
			}
		}

		if ( $this->in_channel )
		{
			$this->in_channel--;
		}
		
		if ( $this->in_item )
		{
			$this->in_item--;
		}
		
		if ( $this->in_image )
		{
			$this->in_image--;
		}
		
		$this->htmlDecodingNeeded	= false;
	}
	
	/**
	 * Extract: Call back function for element handler
	 *
	 * @param	object		Parser object
	 * @param	string		CDATA
	 * @return	@e void
	 */
	protected function _parseCharacterData( $parser, $data )
	{
		//-----------------------------------------
		// Decode HTML if necessary
		//-----------------------------------------
		
		//if( $this->htmlDecodingNeeded )
		//{
		//	$data	= html_entity_decode( $data, ENT_QUOTES );
		//}

		if ( $this->in_image == 2 )
		{
			switch ($this->tag)
			{
				case "URL":
					$this->rss_img_image .= $data;
					break;
				case "TITLE":
					$this->rss_img_title .= $data;
					break;
				case "LINK":
					$this->rss_img_link .= $data;
					break;
				case "WIDTH":
					$this->rss_img_width .= $data;
					break;
				case "HEIGHT":
					$this->rss_img_height .= $data;
					break;
				case "DESCRIPTION":
					$this->rss_img_desc .= $data;
					break;
			}
		}
		
		if ( $this->in_item == 2)
		{
			switch ($this->tag)
			{
				case "TITLE":
					$this->rss_title .= $data;
					break;
				case "DESCRIPTION":
					$this->rss_description .= $data;
					break;
				case "LINK":
					if ( ! is_string($this->rss_link) )
					{
						$this->rss_link = "";
					}
					$this->rss_link .= $data;
					break;
				case "CONTENT:ENCODED":
					$this->rss_content .= $data;
					break;
				case "CONTENT":
					$this->rss_content .= $data;
					break;
				case "DATE":
					$this->rss_date .= $data;
					break;
				case "DC:DATE":
					$this->rss_date .= $data;
					break;
				case "CREATOR":
					$this->rss_creator .= $data;
					break;
				case "CATEGORY":
					$this->rss_category .= $data;
					break;
				case "GUID":
					$this->rss_guid .= $data;
					break;
			}
		}
		
		if ( $this->in_channel == 2)
		{
			switch ($this->tag)
			{
				case "TITLE":
					$this->rss_chan_title .= $data;
					break;
				case "DESCRIPTION":
					$this->rss_chan_desc .= $data;
					break;
				case "LINK":
					if ( ! is_string($this->rss_chan_link) )
					{
						$this->rss_chan_link="";
					}
					$this->rss_chan_link .= $data;
					break;
				case "DATE":
					$this->rss_chan_date .= $data;
					break;
				case "LANGUAGE":
					$this->rss_chan_lang .= $data;
					break;
			}
		}
	}
	
	/**
	 * Internal: Encode attribute
	 *
	 * @param	string		Raw Text
	 * @return	@e string
	 */
	protected function _xmlEncodeAttribute( $t )
	{
		$t = preg_replace("/&(?!#[0-9]+;)/s", '&amp;', $t );
		$t = str_replace( "<", "&lt;"  , $t );
		$t = str_replace( ">", "&gt;"  , $t );
		$t = str_replace( '"', "&quot;", $t );
		$t = str_replace( "'", '&#039;', $t );
		
		return $t;
	}

	/**
	 * Internal: Dencode attribute
	 *
	 * @param	string		Raw Text
	 * @return	@e string
	 */
	protected function _xmlDecodeAttribute( $t )
	{
		$t = str_replace( "&amp;" , "&", $t );
		$t = str_replace( "&lt;"  , "<", $t );
		$t = str_replace( "&gt;"  , ">", $t );
		$t = str_replace( "&quot;", '"', $t );
		$t = str_replace( "&#039;", "'", $t );
		
		return $t;
	}

	/**
	 * Internal: Encode string
	 *
	 * @param	string		Raw Text
	 * @return	@e string
	 */
	protected function _xmlEncodeString( $v )
	{
		# Fix up encoded & " ' and any other funnky IPB data
		$v = str_replace( '&amp;'         , '&'          , $v );
		$v = str_replace( "&#60;&#33;--"  , "&lt!--"     , $v );
		$v = str_replace( "--&#62;"		  , "--&gt;"     , $v );
		$v = str_replace( "&#60;script"   , "&lt;script" , $v );
		$v = str_replace( "&quot;"        , "\""         , $v );
		$v = str_replace( "&#036;"        , '$'          , $v );
		$v = str_replace( "&#33;"         , "!"          , $v );
		$v = str_replace( "&#39;"         , "'"          , $v );
		
		if ( preg_match( "/['\"\[\]<>&]/", $v ) )
		{
			$v = "<![CDATA[" . $this->_xmlConvertSafeCdata($v) . "]]>";
		}
		
		if ( $this->collapse_newlines )
		{
			$v = str_replace( "\r\n", "\n", $v );
		}
		
		return $v;
	}
	
	/**
	 * Encode CDATA XML attribute (Make safe for transport)
	 *
	 * @param	string		Raw data
	 * @return	@e string
	 */
	protected function _xmlConvertSafeCdata( $v )
	{
		# Legacy
		//$v = str_replace( "<![CDATA[", "<!¢|CDATA|", $v );
		//$v = str_replace( "]]>"      , "|¢]>"      , $v );
		
		# New
		$v = str_replace( "<![CDATA[", "<!#^#|CDATA|", $v );
		$v = str_replace( "]]>"      , "|#^#]>"      , $v );
		
		return $v;
	}

	/**
	 * Decode CDATA XML attribute (Make safe for transport)
	 *
	 * @param	string		Raw data
	 * @return	@e string
	 */
	protected function _xmlUnconvertSafeCdata( $v )
	{
		# Legacy
		$v = str_replace( "<!¢|CDATA|", "<![CDATA[", $v );
		$v = str_replace( "|¢]>"      , "]]>"      , $v );
		
		# New
		$v = str_replace( "<!#^#|CDATA|", "<![CDATA[", $v );
		$v = str_replace( "|#^#]>"      , "]]>"      , $v );
		
		return $v;
	}
	
	/**
	 * Format text string
	 *
	 * @param	string		Raw data
	 * @return	@e string
	 */
	protected function _formatString( $t )
	{
		return trim( $t );
	}
	
	/**
	 * Internal: Reset arrays
	 *
	 * @return	@e void
	 */
	protected function _killElements()
	{
		$this->rss_link        = "";
		$this->rss_title       = "";
		$this->rss_description = "";
		$this->rss_content     = "";
		$this->rss_date        = "";
		$this->rss_creator     = "";
		$this->rss_category    = "";
		$this->rss_guid        = "";
	}
	
	/**
	 * Internal: Reset arrays
	 *
	 * @return	@e void
	 */
	protected function _killImageElements()
	{
		$this->rss_img_image  = "";
		$this->rss_img_title  = "";
		$this->rss_img_link   = "";
		$this->rss_img_width  = "";
		$this->rss_img_height = "";
		$this->rss_img_desc   = "";
	}
	
	/**
	 * Internal: Reset arrays
	 *
	 * @return	@e void
	 */
	protected function _killChannelElements()
	{
		$this->rss_chan_title = "";
		$this->rss_chan_link  = "";
		$this->rss_chan_desc  = "";
		$this->rss_chan_date  = "";
	}
}