<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * BBCode parsing gateway.
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		9th March 2005 11:03
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class parseBbcode
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	
	/**
	 * Allowed to update the caches if not present
	 *
	 * @var		boolean
	 */	
	public $allow_update_caches		= true;
	
	/**
	 * Parse emoticons?
	 *
	 * @var		boolean
	 */	
	public $parse_smilies			= true;

	/**
	 * Parse HTML?
	 * HIGHLY NOT RECOMMENDED IN MOST CASES
	 *
	 * @var		boolean
	 */	
	public $parse_html				= false;
	public $skipXssCheck			= false;
	
	/**
	 * Parse bbcode?
	 *
	 * @var		boolean
	 */	
	public $parse_bbcode			= true;

	/**
	 * Strip quotes?
	 * Strips quotes from the resulting text
	 *
	 * @var		boolean
	 */	
	public $strip_quotes			= false;

	/**
	 * Auto convert newlines to html line breaks
	 *
	 * @var		boolean
	 */	
	public $parse_nl2br				= true;

	/**
	 * Bypass badwords?
	 *
	 * @var		boolean
	 */	
	public $bypass_badwords			= false;

	/**
	 * Section keyword for parsing area
	 *
	 * @var		string
	 */	
	public $parsing_section			= 'post';

	/**
	 * Group id of poster
	 *
	 * @var		int
	 */	
	public $parsing_mgroup			= 0;
	
	/**
	 * Value of mgroup_others for poster
	 *
	 * @var		string
	 */	
	public $parsing_mgroup_others	= '';
	
	/**
	 * Error code stored
	 *
	 * @var		string
	 */	
	public $error					= '';
	
	/**
	 * Warning code stored
	 *
	 * @var		string
	 */	
	public $warning					= '';
	
	/**
	 * BBCode library object
	 *
	 * @var		object
	 */	
	protected $bbclass;
	
	/**
	 * Already loaded the classes?
	 *
	 * @var		boolean
	 */	
	protected $classes_loaded		= false;
	
	/**
	 * Constructor
	 *
	 * @param	object		Registry object
	 * @param	string		Parsing method to use
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $method='normal' )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang	    = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		$this->pre_edit_parse_method	= $method;
		
		/* Initialize our bbcode class */
		$this->_loadClasses();
		
		/* And some default properties */
		$this->bypass_badwords	= $this->memberData ? intval( $this->memberData['g_bypass_badwords'] ) : 0;
		$this->strip_quotes		= $this->settings['strip_quotes'];
	}
	
	/**
	 * Load the required bbcode classes and initialize the object
	 *
	 * @return	@e void
	 */
	protected function _loadClasses()
	{
		$_NOW = IPSDebug::getMemoryDebugFlag();
		
		if ( ! $this->classes_loaded )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/bbcode/core.php', 'class_bbcode_core' );

			if( $this->pre_edit_parse_method == 'legacy' )
			{
				$classToLoad 	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/bbcode/legacy.php', 'class_bbcode_legacy' );
			}
			
			$this->bbclass			= new $classToLoad( $this->registry );
			$this->classes_loaded	= true;
			$this->error			=& $this->bbclass->error;
			$this->warning			=& $this->bbclass->warning;
		}
		
		IPSDebug::setMemoryDebugFlag( "BBCode classes loaded", $_NOW );
	}
	
	/**
	 * Pass off our settings to our bbcode handler
	 *
	 * @return	@e void
	 */
	protected function _passSettings()
	{
		/* Clean out secondary groups just in case of ,, */
		$this->parsing_mgroup_others = IPSText::cleanPermString( $this->parsing_mgroup_others );
		
		//-----------------------------------------
		// Pass the settings
		//-----------------------------------------

		$this->cache->updateCacheWithoutSaving( '_tmp_section', $this->parsing_section );

		$this->bbclass->bypass_badwords			= $this->bypass_badwords;
		$this->bbclass->parse_smilies			= $this->parse_smilies;
		$this->bbclass->parse_html				= $this->parse_html;
		$this->bbclass->parse_bbcode			= $this->parse_bbcode;
		$this->bbclass->strip_quotes			= $this->strip_quotes;
		$this->bbclass->parse_nl2br				= $this->parse_nl2br;
		$this->bbclass->parsing_section			= $this->parsing_section;
		$this->bbclass->parsing_mgroup			= $this->parsing_mgroup;
		$this->bbclass->parsing_mgroup_others	= $this->parsing_mgroup_others;
		$this->bbclass->skipXssCheck			= $this->skipXssCheck;
		
		$this->bbclass->initOurBbcodes();
	}
			
	/**
	 * Parses the bbcode to be stored into the database.
	 * If all bbcodes are parse on display, this method does nothing really
	 *
	 * @param 	string			Raw input text to parse
	 * @return	string			Parsed text ready to be stored in database
	 */
	public function preDbParse( $text )
	{
		$this->_passSettings();

		//-----------------------------------------
		// Pass off to the main handler
		//-----------------------------------------
		
		return $this->bbclass->preDbParse( trim($text) );
	}
	
	/**
	 * Parses the bbcode to be shown in the STD editor.
	 * If all bbcodes are parse on display, this method does nothing really
	 *
	 * @param 	string			Raw input text to parse
	 * @return	string			Parsed text ready to be stored in database
	 */
	public function preEditParse( $text )
	{
		$this->_passSettings();

		//-----------------------------------------
		// Parse
		//-----------------------------------------

		return $this->bbclass->preEditParse( $text );
	}
	
	/**
	 * Parses the bbcode to be shown in the browser.  Expects preDbParse has already been done before the save.
	 * If all bbcodes are parse on save, this method does nothing really
	 *
	 * @param 	string			Raw input text to parse
	 * @return	string			Parsed text ready to be displayed
	 */
	public function preDisplayParse( $text )
	{
		$_NOW = IPSDebug::getMemoryDebugFlag();
		
		$this->_passSettings();

		//-----------------------------------------
		// Parse
		//-----------------------------------------
				
		$text	= $this->bbclass->preDisplayParse( $text );
		
		IPSDebug::setMemoryDebugFlag( "PreDisplayParse completed", $_NOW );

		return $text;
	}
	
	/**
	 * Parses the bbcode to be shown in the polls.
	 * Parses img and url, if enabled
	 *
	 * @param 	string			Raw input text to parse
	 * @return	string			Parsed text ready to be displayed
	 */
	public function parsePollTags( $text )
	{
		$text	= $this->bbclass->parseBbcode( $text, 'display', array( 'img', 'url' ) );
		$text	= $this->bbclass->finishNonParsed( $text, 'display' );
		
		return $text;
	}

	/**
	 * Parse only specified bbcodes
	 *
	 * @param 	string			Raw input text to parse
	 * @param	string			db or display - what mode to parse in
	 * @param	mixed			BBcode tag to parse, or array of bbcode tags to parse
	 * @return	string			Parsed text ready to be displayed
	 */
	public function parseSingleBbcodes( $text, $method='display', $codes=null )
	{
		$text	= $this->bbclass->parseBbcode( $text, $method, $codes );
		$text	= $this->bbclass->finishNonParsed( $text, $method );
		
		return $text;
	}

	/**
	 * Converts the STD contents to RTE compatible output
	 * Used when switching editors or taking the bbcode post and putting into the RTE
	 *
	 * @param 	string			BBCode text
	 * @return	string			RTE-compatible text
	 */
	public function convertStdToRte( $t )
	{
		//-----------------------------------------
		// Ensure no slashy slashy
		//-----------------------------------------
		
		$t	= str_replace( '"','&quot;', $t );
		$t	= str_replace( "'",'&apos;', $t );
		
		//-----------------------------------------
		// Convert <>
		//-----------------------------------------

		if( $this->parse_nl2br )
		{
			$t	= str_replace( "<br />", "\n", $t );
		}
		
		$t	= str_replace( '<', '&lt;', $t );
		$t	= str_replace( '>', '&gt;', $t );
		
		//-----------------------------------------
		// RTE expects <br /> not \n
		//-----------------------------------------
		
		$t = str_replace( "\n", "<br />", str_replace( "\r\n", "\n", $t ) );
		
		//-----------------------------------------
		// Okay, convert ready for RTE
		//-----------------------------------------

		$t	= $this->preDbParse( $t );
		$t	= $this->convertForRTE( $t );
		
		return $t;
	}
	
	/**
	 * Converts "IP.Board HTML" to regular (RTE) HTML
	 *
	 * @param 	string			Parsed text
	 * @return	string			RTE-compatible text
	 */
	public function convertForRTE( $t )
	{
		$this->_passSettings();

		return $this->bbclass->convertForRTE( $t );
	}
	
	/**
	 * Strip all HTML and bbcode tags
	 *
	 * @param 	string			BBCode + HTML text
	 * @param	boolean			Run through pre_edit_parse
	 * @return	string			Raw text with no tags
	 */
	public function stripAllTags( $t, $pre_edit_parse=true )
	{
		$this->_passSettings();
		
		return $this->bbclass->stripAllTags( $t, $pre_edit_parse );
	}
	
	/**
	 * Strip quotes
	 *
	 * @param 	string			Raw posted text
	 * @param	string			Tag to strip (defaults to 'quote')
	 * @return	string			Raw text with no quotes
	 */
	public function stripQuotes( $t, $tag='quote' )
	{
		$this->_passSettings();
		
		if ( is_array( $tag ) )
		{
			foreach ( $tag as $_tag )
			{
				$t = $this->bbclass->stripBbcode( $_tag, $t );
			}
			
			return $t;
		}
		else
		{
			return $this->bbclass->stripBbcode( $tag, $t );
		}
	}
	
	/**
	 * Strip shared media
	 *
	 * @param 	string			Raw posted text
	 * @return	string			Raw text with no shared media
	 */
	public function stripSharedMedia( $t )
	{
		$t	= preg_replace( '#\[sharedmedia=([^\]]+?)\]#is', " ", $t );
		
		return $t;
	}
	
	/**
	 * Strip emoticons
	 *
	 * @param 	string			Raw posted text
	 * @return	string			Raw text with no emoticons
	 */
	public function stripEmoticons( $t )
	{
		$this->_passSettings();
		
		return $this->bbclass->stripEmoticons( $t );
	}
	
	/**
	 * Unconvert emoticons
	 *
	 * @param 	string			Raw posted text
	 * @return	string			Raw text with text emoticons
	 */
	public function unconvertSmilies( $t )
	{
		return IPSText::unconvertSmilies( $t );
	}
	
	/**
	 * Strip badwords
	 *
	 * @param 	string			Raw posted text
	 * @return	string			Raw text with no badwords
	 */
	public function stripBadWords( $t )
	{
		$this->_passSettings();
		
		return $this->bbclass->badWords( $t );
	}
	
	/**
	 * Check against blacklisted URLs
	 *
	 * @param 	string			Raw posted text
	 * @return	bool			False if blacklisted url present, otherwise true
	 */
	public function checkBlacklistUrls( $t )
	{
		$this->_passSettings();
		
		return $this->bbclass->checkBlacklistUrls( $t );
	}

	/**
	 * Make data in quotes "safe"
	 *
	 * @param 	string			Raw posted text
	 * @return	string			Raw text safe for use in quote tag
	 */
	public function makeQuoteSafe( $t )
	{
		$this->_passSettings();
		
		return $this->bbclass->makeQuoteSafe( $t );
	}
	
	/**
	 * Clean content from XSS (best shot at least)
	 *
	 * @param 	string			Raw posted text
	 * @param	boolean			Attempt to fix script tag
	 * @return	string			Cleaned text
	 */
	public function xssHtmlClean( $t, $fixScript=true )
	{
		$this->_passSettings();
		
		return $this->bbclass->checkXss( $t, $fixScript );
	}
}