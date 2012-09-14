<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * AJAX input parsing and handling
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Kernel
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 10721 $
 */


class classAjax
{
	/**
	 * XML output
	 *
	 * @var		string			Output
	 */
	public $xml_output;

	/**
	 * XML Header
	 *
	 * @var		string			XML doctype
	 */
	public $xml_header;
	
	/**
	 * Character sets
	 *
	 * @var		array			Charsets supported by html_entity_decode
	 */
	protected $decodeCharsets = array('iso-8859-1'	=> 'ISO-8859-1',
									'iso8859-1' 	=> 'ISO-8859-1',
									'iso-8859-15' 	=> 'ISO-8859-15',
									'iso8859-15' 	=> 'ISO-8859-15',
									'utf-8'			=> 'UTF-8',
									'cp866'			=> 'cp866',
									'ibm866'		=> 'cp866',
									'cp1251'		=> 'windows-1251',
									'windows-1251'	=> 'windows-1251',
									'win-1251'		=> 'windows-1251',
									'cp1252'		=> 'windows-1252',
									'windows-1252'	=> 'windows-1252',
									'koi8-r'		=> 'KOI8-R',
									'koi8-ru'		=> 'KOI8-R',
									'koi8r'			=> 'KOI8-R',
									'big5'			=> 'BIG5',
									'gb2312'		=> 'GB2312',
									'big5-hkscs'	=> 'BIG5-HKSCS',
									'shift_jis'		=> 'Shift_JIS',
									'sjis'			=> 'Shift_JIS',
									'euc-jp'		=> 'EUC-JP',
									'eucjp'			=> 'EUC-JP' );
									

	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		if ( ! defined( 'IPS_DOC_CHAR_SET' ) )
		{
			define( 'IPS_DOC_CHAR_SET', 'UTF-8' );
		}
		
		$this->xml_header = "<?xml version=\"1.0\" encoding=\"" . IPS_DOC_CHAR_SET . "\"?".'>';

		/* Convert incoming $_POST fields */
		array_walk_recursive( $_POST, array( $this, 'arrayWalkConvert' ) );
		
		//-----------------------------------------
		// Using this code allows characters that ARE supported
		// within the character set to be recoded properly, instead
		// of as HTML entities when submitted via AJAX.  The problem is,
		// any characters NOT supported in the charset are corrupted. :-\
		//-----------------------------------------
		//array_walk_recursive( $_POST, create_function( '&$value, $key', '$value = IPSText::convertCharsets( IPSText::convertUnicode($value, true), "utf-8", "' . IPS_DOC_CHAR_SET . '" );' ) );

		// We use $_REQUEST in a lot of places, so we might need to do this, but based on the below comments I'll leave this
		// commented out for now...
		//array_walk_recursive( $_REQUEST, create_function( '&$value, $key', '$value = IPSText::convertUnicode($value);' ) );
		
		/* Risky converting $_GET because it calls rawurldecode which could allow crafty users to hide html entities */
		//array_walk_recursive( $_GET , create_function( '&$value, $key', '$value = IPSText::convertUnicode($value);' ) );
		//array_walk_recursive( ipsRegistry::$request, create_function( '&$value, $key', '$value = IPSText::convertUnicode($value);' ) );
	}
	
	/**
	 * Callback to convert unicode and charset for AJAX requests
	 * 
	 * @param	mixed		Value
	 * @param	string		Key
	 * @return	@e void
	 */
 	public function arrayWalkConvert( &$value, $key )
 	{
 		if( is_string( $value ) )
 		{
 			$value = IPSText::convertCharsets( IPSText::convertUnicode( $value ), "utf-8", IPS_DOC_CHAR_SET );
 		}
 	}

 	/**
	 * Normalize the character set of incoming AJAX data and optionally parse the value through the parseCleanValue routine
	 *
	 * @param	string		Raw input string
	 * @param	boolean		Run through parse_incoming routine
	 * @see		IPSText::parseCleanValue
	 * @return	@e string
	 */
 	public function convertAndMakeSafe( $value, $parse_incoming=true )
 	{
 		$value = rawurldecode( $value );
 		
 		//-----------------------------------------
 		// \ char is passed as %5C so it won't be double
 		// escaped if magic quotes is on - do it manually
 		// @link	http://community.invisionpower.com/tracker/issue-24971-status-update-issue/
 		//-----------------------------------------
 		
 		if( IPS_MAGIC_QUOTES )
 		{
 			$value = str_replace( "\\", "\\\\", $value );
 		}
 		
   		$value = $this->convertUnicode( $value );
		$value = $this->convertHtmlEntities( $value );
		
		if( $parse_incoming )
		{
			$value = IPSText::parseCleanValue( $value );
		}
		
		return $value;
 	}
 	
 	/**
 	 * Remove hook comments and parse replacements
 	 * 
 	 * @param	string		Output data
 	 * @return	@e string
 	 */
 	public function cleanOutput( $string )
 	{
 		if ( ! IN_ACP )
		{
			$string = preg_replace( '#<!--hook\.([^\>]+?)-->#', '', $string );
		}
		
		$string = ipsRegistry::getClass('output')->replaceMacros( $string );
	
		return $string;
	}
		

	/**
	 * Print a generic error message
	 *
	 * @return	@e void
	 */
	public function returnGenericError()
	{
		@header( "Content-type: text/xml" );
		$this->printNocacheHeaders();
		
		$this->xml_output = $this->xml_header . "\r\n<errors>\r\n";
		$this->xml_output .= "<error><message>You must be logged in to access this feature</message></error>\r\n";
		$this->xml_output .= "</errors>";

		print $this->xml_output;
		exit();
	}
	
	/**
	 * Return a NULL XML result
	 *
	 * @param	mixed		Value to send
	 * @return	@e void
	 */
	public function returnNull( $val=0 )
	{
		@header( "Content-type: text/xml" );
		$this->printNocacheHeaders();
		
		$val = $this->parseAndCleanHooks( $val );
		
		print $this->xml_header . "\r\n<null>{$val}</null>";
		exit();
	}

	/**
	 * Return a string
	 *
	 * @param	string		String to output
	 * @return	@e void
	 */
	public function returnString( $string )
	{
		@header( "Content-type: text/plain;charset=" . IPS_DOC_CHAR_SET );
		$this->printNocacheHeaders();
		
		echo $this->parseAndCleanHooks( $string );
		exit();
	}

	/**
	 * Return a JSON error message
	 *
	 * @param	string		Error message
	 * @param	string		Content-type header to send
	 * @return	@e void
	 */
	public function returnJsonError( $message, $header="application/json" )
	{
		/* Just alias it... */
		$this->returnJsonArray( array( 'error' => $message ), false, $header );
	}

	/**
	 * Return a JSON object
	 *
	 * @param	array		Array of key => value fields
	 * @param	boolean		Clean the data (used when passing HTML)
	 * @param	string		Optional content-type header string (default application/json)
	 * @param	array		Optional array (0 => template group, 1 => bit name) to pass JSON into a custom skin method
	 * @return	@e void
	 */
	public function returnJsonArray( $json=array(), $cleanData=false, $header="application/json", $templateWrapper=array() )
	{
		@header( "Content-type: " . $header . ";charset=" . IPS_DOC_CHAR_SET );
		$this->printNocacheHeaders();
		
		/* Always return as UTF-8 */
		array_walk_recursive( $json, array( 'IPSText', 'arrayWalkCallbackConvert' ) );
		
		if ( $cleanData )
		{
			array_walk_recursive( $json, array( $this, 'cleanHtml' ) );
		}
		
		$result	= json_encode( $json );
		$result	= IPSText::convertCharsets($result, "UTF-8", IPS_DOC_CHAR_SET);
		
		/* Print via a wrapper? */
		if ( is_array( $templateWrapper ) AND count( $templateWrapper ) )
		{
			print ipsRegistry::getClass('output')->getTemplate( $templateWrapper[0] )->$templateWrapper[1]( $result );
		}
		else
		{
			print $result;
		}
			
		exit();
	}
	
	/**
	 * Return HTML content
	 *
	 * @param	string		HTML to output
	 * @param	boolean		Force returned output to UTF-8
	 * @return	@e void
	 */
	public function returnHtml( $string, $returnAsUtf8=false )
	{
		if ( $string )
		{
			$this->cleanHtml( $string );
		}
		
		/* Always return as UTF-8 */
		if ( $returnAsUtf8 )
		{
			$string = IPSText::convertCharsets( $string, IPS_DOC_CHAR_SET, "UTF-8" );
		}
		
		@header( "Content-type: text/html;charset=" . IPS_DOC_CHAR_SET );
		$this->printNocacheHeaders();

		print $string;
		exit();
	}
	
	/**
	 * Execute template hooks and remove hook comments
	 *
	 * @param	string	$string
	 * @return	@e string
	 */
	public function parseAndCleanHooks( $string )
	{
		$string = ipsRegistry::getClass('output')->templateHooks( $string );
		$string = preg_replace( '#<!--hook\.([^\>]+?)-->#', '', $string );
		
		return $string;
	}
	
	/**
	 * Print 200 OK and nocache headers
	 *
	 * @return	@e void
	 */
	public function printNocacheHeaders()
	{
		if ( isset( $_SERVER['SERVER_PROTOCOL'] ) AND strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) )
		{
			header( "HTTP/1.0 200 OK" );
		}
		else
		{
			header( "HTTP/1.1 200 OK" );
		}
		
		header( "Cache-Control: no-cache, must-revalidate, max-age=0" );
		header( "Expires: 0" );
		header( "Pragma: no-cache" );
	}
	
	/**
	 * Convert AJAX unicode to standard char codes
	 *
	 * @deprecated	This function no longer does anything
	 * @param	string		Input to convert
	 * @return	@e string
	 */
	public function convertUnicode( $t )
	{
		/* This is called at various stages through different ajax files but its no longer required */
		/* The ajax class cleans up (converts via IPSText::convertUnicode()) the post get and input vars in the constructor now so we don't need to manually use it */
		
		return $t;
	}
	
	/**
	 * Convert HTML entities into raw characters
	 *
	 * @param	string		Input to convert
	 * @return	@e string
	 */
	public function convertHtmlEntities($t)
	{
		//-----------------------------------------
		// Try and fix up HTML entities with missing ;
		//-----------------------------------------
		
		$t = preg_replace( '/&#(\d+?)([^\d;])/i', "&#\\1;\\2", $t );

		//-----------------------------------------
		// Continue...
		//-----------------------------------------

		if ( strtolower(IPS_DOC_CHAR_SET) != 'iso-8859-1' && strtolower(IPS_DOC_CHAR_SET) != 'utf-8' )
   		{
	   		if ( isset($this->decodeCharsets[ strtolower(IPS_DOC_CHAR_SET) ]) )
	   		{
		   		$IPS_DOC_CHAR_SET = $this->decodeCharsets[strtolower(IPS_DOC_CHAR_SET)];

		   		$t = html_entity_decode( $t, ENT_NOQUOTES, IPS_DOC_CHAR_SET );
	   		}
	   		else
	   		{
		   		// Take a crack at entities in other character sets
		   		
		   		$t = str_replace( "&amp;#", "&#", $t );
		   		
		   		// If mb functions available, we can knock out html entities for a few more char sets

				if( function_exists('mb_list_encodings') )
				{
					$valid_encodings = array();
					$valid_encodings = mb_list_encodings();
					
					if( count($valid_encodings) )
					{
						if( in_array( strtoupper(IPS_DOC_CHAR_SET), $valid_encodings ) )
						{
							$t = mb_convert_encoding( $t, strtoupper(IPS_DOC_CHAR_SET), 'HTML-ENTITIES' );
						}
					}
				}
	   		}
   		}

   		return $t;
	}
	
	/**
	 * Formats an HTML string for output.  Fixes some browser-specific bugs, parses replacements and executes hooks
	 *
	 * @param	string	$string
	 * @param	string	Array key (only necessary when executed from an array_walk callback)
	 * @return	@e string
	 */
	public function cleanHtml( &$string, $key='' )
	{ 
		// Fix IE bugs
		$string = str_ireplace( "&sect", 	"&amp;sect", 	$string );
		
		if ( strtolower( IPS_DOC_CHAR_SET ) == 'iso-8859-1' )
		{
			$string = str_replace( "ì", "&#8220;", $string );
			$string = str_replace( "î", "&#8221;", $string );
		}

		// Other stuff
		$string = ipsRegistry::getClass('output')->replaceMacros( $string );
		$string = $this->parseAndCleanHooks( $string );

		return $string;
	}
}