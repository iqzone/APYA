<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Custom bbcode plugin interfaces
 * Last Updated: $Date: 2012-05-24 11:33:36 -0400 (Thu, 24 May 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10790 $ 
 */

interface bbcodePlugin
{
	/**
	 * Method that is run before the content is stored in the database
	 * You are responsible for ensuring you mark the replaced text appropriately so that you
	 *	are able to unparse it, if you wish to have bbcode parsed on save
	 *
	 * @access	public
	 * @param	string		$txt	BBCode text from submission to be stored in database
	 * @return	string				Formatted content, ready for display
	 */
	public function preDbParse( $txt );
	
	/**
	 * Method that is run before the content is displayed to the user
	 * This is the safest method of parsing, as the original submitted text is left in tact.
	 *	No markers are necessary if you use parse on display.
	 *
	 * @access	public
	 * @param	string		$txt	BBCode/parsed text from database to be displayed
	 * @return	string				Formatted content, ready for display
	 */
	public function preDisplayParse( $txt );
	
	/**
	 * Method that is run before the content is placed into an editor for editing
	 * If you use "parse on display" you may simply return $txt
	 *
	 * @access	public
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	string				BBCode content, ready for editing
	 */
	public function preEditParse( $txt );
}

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * BBCode parser: default custom bbcodes: img, quote, list, size, member, media, url, snapback
 * Last Updated: $Date: 2012-05-24 11:33:36 -0400 (Thu, 24 May 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10790 $ 
 *
 */

class bbcode_parent_class
{
	/**
	 * Current position in the text document
	 *
	 * @access	protected
	 * @var		integer
	 */	
	protected $cur_pos					= 0;
	
	/**
	 * Stored position of ending quote tag
	 *
	 * @access	protected
	 * @var		integer
	 */	
	protected $end_pos					= 0;

	/**
	 * Error message
	 *
	 * @access	public
	 * @var		string
	 */	
	public $error						= '';
	
	/**
	 * Warning message
	 *
	 * @access	public
	 * @var		string
	 */	
	public $warning						= '';
	
	/**
	 * This bbcode's data
	 *
	 * @access	protected
	 * @var		integer
	 */	
	protected $_bbcode					= array();

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
	 * Settings object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $settings;
	
	/**
	 * Request object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $request;
	
	/**
	 * Language object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $lang;
	
	/**
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $member;
	protected $memberData;
	
	/**
	 * Cache object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $cache;
	protected $caches;
	
	/**
	 * Core bbcode class
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $_parentBBcode;
	
	/**
	 * Current bbcode
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $currentBbcode;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @param	object		Parent bbcode class
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $_parent=null )
	{
		/* Make object */
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			= $this->registry->getClass('class_localization');
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		
		$this->_parentBBcode = $_parent;
		
		/* Retrieve bbcode data */
		$bbcodeCache	= $this->cache->getCache('bbcode');
		$this->_bbcode	= $bbcodeCache[ $this->currentBbcode ];
	}
	
	/**
	 * Method that is run before the content is stored in the database
	 * You are responsible for ensuring you mark the replaced text appropriately so that you
	 *	are able to unparse it, if you wish to have bbcode parsed on save
	 *
	 * @access	public
	 * @param	string		$txt	BBCode text from submission to be stored in database
	 * @return	string				Formatted content, ready for display
	 */
	public function preDbParse( $txt )
	{
		return $txt;
	}
	
	/**
	 * Method that is run before the content is displayed to the user
	 * This is the safest method of parsing, as the original submitted text is left in tact.
	 *	No markers are necessary if you use parse on display.
	 *
	 * @access	public
	 * @param	string		$txt	BBCode/parsed text from database to be displayed
	 * @return	string				Formatted content, ready for display
	 */
	public function preDisplayParse( $txt )
	{
		$this->cur_pos		= 0;
		$this->end_pos		= 0;
		$this->error		= '';
		
		return $this->_replaceText( $txt );
	}

	/**
	 * Method that is run before the content is placed into an editor for editing
	 * If you use "parse on display" you may simply return $txt
	 *
	 * @access	public
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	string				BBCode content, ready for editing
	 */
	public function preEditParse( $txt )
	{
		$this->error		= '';
		
		return $txt;
	}
	
	/**
	 * Retrieves the tags used for this bbcode, including aliases
	 *
	 * @access	public
	 * @return	array				Array of tags to check
	 */
	protected function _retrieveTags()
	{
		$_tags = array( $this->_bbcode['bbcode_tag'] );

		//-----------------------------------------
		// We'll also need to check for any aliases
		//-----------------------------------------
		
		if( $this->_bbcode['bbcode_aliases'] )
		{
			$aliases = explode( ',', trim($this->_bbcode['bbcode_aliases']) );
			
			if( is_array($aliases) AND count($aliases) )
			{
				foreach( $aliases as $alias )
				{
					$_tags[]	= trim($alias);
				}
			}
		}

		return $_tags;
	}
}

//----------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------

class bbcode_sharedmedia extends bbcode_parent_class implements bbcodePlugin
{
	/**
	 * Store plugins we've instantiated
	 *
	 * @var		array
	 */
	protected $plugins		= array();

	/**
	 * Constructor
	 *
	 * @param	object		Registry object
	 * @param	object		Parent bbcode class
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $_parent=null )
	{
		$this->currentBbcode	= 'sharedmedia';

		parent::__construct( $registry, $_parent );
	}

	/**
	 * We use preDbParse to verify you have permission to share the items you are sharing
	 *
	 * @access	public
	 * @param	string		$txt	BBCode text from submission to be stored in database
	 * @return	string				Formatted content, ready for display
	 */
	public function preDbParse( $txt )
	{
		$_ignored	= preg_replace_callback( '#(\[sharedmedia=(.+?):(.+?):(.+?)\])#is' , array( $this, '_checkPostingPermissions' ), $txt );

		return $txt;
	}
	
	/**
	 * We use this to check for permissions
	 *
	 * @param	array 		preg_replace_callback Matches
	 * @return	@e string
	 */
	protected function _checkPostingPermissions( $matches )
	{
		$txt	= trim( $matches[1] );
		$app	= trim( $matches[2] );
		$plugin	= trim( $matches[3] );
		
		if( !$txt OR !$app OR !$plugin )
		{
			return '';
		}
		
		if( !isset($this->plugins[ $app ][ $plugin ]) )
		{
			if( is_file( IPSLib::getAppDir( $app ) . '/extensions/sharedmedia/plugin_' . $plugin . '.php' ) )
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( $app ) . '/extensions/sharedmedia/plugin_' . $plugin . '.php', 'plugin_' . $app . '_' . $plugin, $app );

				$this->plugins[ $app ][ $plugin ]		= new $classToLoad( $this->registry );
				
			}
		}
		
		if( isset($this->plugins[ $app ][ $plugin ]) )
		{
			if( $error = $this->plugins[ $app ][ $plugin ]->checkPostPermission( $matches[4] ) )
			{
				$this->error	= $error;
			}
		}

		//-----------------------------------------
		// Return the original output (we aren't replacing, just verifying permissions)
		//-----------------------------------------
		
		return $txt;
	}
	
	/**
	 * Do the actual replacement
	 *
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	@e string
	 */
	protected function _replaceText( $txt )
	{
		$txt	= preg_replace_callback( '#(\[sharedmedia=(.+?):(.+?):(.+?)\])#is' , array( $this, '_parseMedia' ), $txt );

		return $txt;
	}

	/**
	 * Callback for shared media preg_replace call
	 *
	 * @param	array 		preg_replace_callback Matches
	 * @return	@e string
	 */
	protected function _parseMedia( $matches )
	{
		$txt	= trim( $matches[1] );
		$app	= trim( $matches[2] );
		$plugin	= trim( $matches[3] );
		$_orig	= $txt;
		
		if( !$txt OR !$app OR !$plugin )
		{
			return '';
		}
		
		if( !isset($this->plugins[ $app ][ $plugin ]) )
		{
			if( is_file( IPSLib::getAppDir( $app ) . '/extensions/sharedmedia/plugin_' . $plugin . '.php' ) )
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( $app ) . '/extensions/sharedmedia/plugin_' . $plugin . '.php', 'plugin_' . $app . '_' . $plugin, $app );

				$this->plugins[ $app ][ $plugin ]		= new $classToLoad( $this->registry );
				
			}
		}
		
		if( isset($this->plugins[ $app ][ $plugin ]) )
		{
			$txt			= $this->plugins[ $app ][ $plugin ]->getOutput( $matches[4] );
		}

		//-----------------------------------------
		// Return the replaced output
		//-----------------------------------------
		
		return $txt;
	}
}

//----------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------

class bbcode_img extends bbcode_parent_class implements bbcodePlugin
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @param	object		Parent bbcode class
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $_parent=null )
	{
		$this->currentBbcode	= 'img';
		
		parent::__construct( $registry, $_parent );
	}

	/**
	 * Do the actual replacement
	 *
	 * @access	protected
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	string				BBCode content, ready for editing
	 */
	protected function _replaceText( $txt )
	{
		$_tags = $this->_retrieveTags();

		foreach( $_tags as $_tag )
		{
			//-----------------------------------------
			// Start building open/close tag
			//-----------------------------------------
			
			$open_tag	= '[' . $_tag . ']';
			$close_tag	= '[/' . $_tag . ']';

			//-----------------------------------------
			// Infinite loop catcher
			//-----------------------------------------
			
			$_iteration	= 0;
			
			//-----------------------------------------
			// Doz I can haz opin tag? Loopy loo
			//-----------------------------------------
			
			while( ( $this->cur_pos = stripos( $txt, $open_tag, $this->cur_pos ) ) !== false )
			{
				//-----------------------------------------
				// Stop infinite loops
				//-----------------------------------------
				
				if( $_iteration > $this->settings['max_bbcodes_per_post'] )
				{
					break;
				}
				
				$_iteration++;
						
				//-----------------------------------------
				// Grab the new position to jump to
				//-----------------------------------------
				
				$new_pos = strpos( $txt, ']', $this->cur_pos ) ? strpos( $txt, ']', $this->cur_pos ) : $this->cur_pos + 1;

				//-----------------------------------------
				// No closing tag
				//-----------------------------------------
				
				if( stripos( $txt, $close_tag, $new_pos ) === false )
				{
					break;
				}
						
				//-----------------------------------------
				// Grab the content
				//-----------------------------------------
				
				$_content	= substr( $txt, ($this->cur_pos + strlen($open_tag)), (stripos( $txt, $close_tag, $this->cur_pos ) - ($this->cur_pos + strlen($open_tag))) );
								
				//-----------------------------------------
				// If this is a single tag, that's it
				//-----------------------------------------
				
				if( $_content )
				{
					/* Trying to sneak in another URL to auto parse? */
					preg_match_all( '#http(s)?://#i', $_content, $_match );
					
					if ( count( $_match[0] ) > 1 )
					{
						/* Make safe */
						$txt = preg_replace( "#(http|https|news|ftp)://#i", "\\1&#58;//", $txt );
						
						return $txt;
					}
				
					$txt		= substr_replace( $txt, $this->_buildOutput( $_content ), $this->cur_pos, (stripos( $txt, $close_tag, $this->cur_pos ) + strlen($close_tag) - $this->cur_pos) );
				}
				else
				{
					$txt		= substr_replace( $txt, '', $this->cur_pos, (stripos( $txt, $close_tag, $this->cur_pos ) + strlen($close_tag) - $this->cur_pos) );
				}
				
				//-----------------------------------------
				// And reset current position to end of open tag
				//-----------------------------------------
				
				$this->cur_pos = stripos( $txt, $open_tag ) ? stripos( $txt, $open_tag ) : $this->cur_pos + 1; //$new_pos;
				
				if( $this->cur_pos > strlen($txt) )
				{
					//-----------------------------------------
					// Need to reset for next "tag"
					//-----------------------------------------
					
					$this->cur_pos	= 0;
					break;
				}
			}
		}
		
		return $txt;
	}
	
	/**
	 * Build the actual output to show
	 *
	 * @access	protected
	 * @param	array		$content	Image URL to link to
	 * @return	string					Content to replace bbcode with
	 */
	protected function _buildOutput( $content )
	{
		$content	= trim($content);
		
		//-----------------------------------------
		// Too many images?
		//-----------------------------------------
		
		$existing	= $this->cache->getCache( '_tmp_bbcode_images', false );
		$existing	= intval($existing) + 1;
		
		if ( $this->settings['max_images'] AND $this->caches['_tmp_section'] != 'signatures' )
		{
			if ($existing > $this->settings['max_images'])
			{
				$this->error = 'too_many_img';
				return $content;
			}
		}
		
		$this->cache->updateCacheWithoutSaving( '_tmp_bbcode_images', $existing );
		
		//-----------------------------------------
		// Some security checking
		//-----------------------------------------
		
		if ( IPSText::xssCheckUrl( $content ) !== TRUE )
		{
			return $content;
		}
		
		foreach( $this->cache->getCache('bbcode') as $bbcode )
		{
			$_tags = $this->_retrieveTags();
			
			foreach( $_tags as $tag )
			{
				if ( stripos( $content, '[' . $tag ) !== false )
				{
					return $content;
				}
			}
		}
		
		//-----------------------------------------
		// Allowed type?
		//-----------------------------------------
		
		if ( $this->settings['img_ext'] )
		{
			$path	= @parse_url( html_entity_decode( $content ), PHP_URL_PATH );
			$pieces	= explode( '.', $path );
			$ext	= array_pop( $pieces );
			$ext	= strtolower( $ext );

			if( !in_array( $ext, explode( ',', str_replace( '.', '', strtolower($this->settings['img_ext']) ) ) ) )
			{
				$this->error = 'invalid_ext';
				return $content;
			}
		}
		
		//-----------------------------------------
		// URL filtering?
		//-----------------------------------------
				
		if ( $this->settings['ipb_use_url_filter'] )
		{
			$list_type = $this->settings['ipb_url_filter_option'] == "black" ? "blacklist" : "whitelist";
			
			if( $this->settings['ipb_url_' . $list_type ] )
			{
				$list_values 	= array();
				$list_values 	= explode( "\n", str_replace( "\r", "", $this->settings['ipb_url_' . $list_type ] ) );
				
				if( $list_type == 'whitelist' )
				{
					$list_values[]	= "http://{$_SERVER['HTTP_HOST']}/*";
				}
				
				if ( count( $list_values ) )
				{
					$good_url = 0;
					
					foreach( $list_values as $my_url )
					{
						if( !trim($my_url) )
						{
							continue;
						}

						$my_url = preg_quote( $my_url, '/' );
						$my_url = str_replace( '\*', "(.*?)", $my_url );
						
						if ( $list_type == "blacklist" )
						{
							if( preg_match( '/' . $my_url . '/i', $content ) )
							{
								$this->error = 'domain_not_allowed';
								return $content;
							}
						}
						else
						{
							if ( preg_match( '/' . $my_url . '/i', $content ) )
							{
								$good_url = 1;
							}
						}
					}
					
					if ( ! $good_url AND $list_type == "whitelist" )
					{
						$this->error = 'domain_not_allowed';
						return $content;
					}						
				}
			}
		}
					
		return "<span rel='lightbox'><img src='" . IPSText::xssMakeJavascriptSafe( $content ) . "' alt='{$this->lang->words['bbcode_img_alt']}' class='bbc_img' /></span>";
	}
}

//----------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------

class bbcode_quote extends bbcode_parent_class implements bbcodePlugin
{
	/**#@+
	 * Quote tracking
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $quote_open	= 0;
	protected $quote_closed	= 0;
	protected $quote_error	= 0;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @param	object		Parent bbcode class
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $_parent=null )
	{
		$this->currentBbcode	= 'quote';

		parent::__construct( $registry, $_parent );
	}
	
	/**
	 * Do the actual replacement
	 *
	 * @access	protected
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	string				BBCode content, ready for editing
	 */
	protected function _replaceText( $txt )
	{
		/* Clean up some dickery */
		$txt = preg_replace( '#\[quote([^\]]+?)?\]\](\s+?)?\[/url\](\s+?)?\[/quote\]#si', '', $txt );
		
		$orig	= $txt;
		$txt	= preg_replace_callback( '#(\[quote([^\]]+?)?\].*\[/quote\])#is' , array( $this, '_parseQuote' ), $txt );

		if( $this->error )
		{
			return $orig;
		}

		if( stripos( $txt, '[quote' ) !== false OR stripos( $txt, '[/quote]' ) !== false )
		{
			$this->error = 'quote_mismatch';
			return $orig;
		}

		return $txt;
	}
	
	/**
	 * Build the actual output to show
	 *
	 * @access	protected
	 * @param	array 		$options	[Optional] Quote options
	 * @return	string					Content to replace bbcode with
	 */
	protected function _buildOutput( $options=array() )
	{
		//-----------------------------------------
		// Build output and return it
		//-----------------------------------------
		
		if ( $options['collapse'] )
		{
			if ( $options['collapse'] == '1' )
			{
				$options['collapse'] = $this->lang->words['bbc_quote'];
			}
		
			$eqid = rand( 1, 1000000 );
			$output = <<<OUTPUT
<p class='citation' onclick="if(\$('emailquote-{$eqid}').style.display=='none'){\$('emailquote-{$eqid}').style.display='inline';$('quotehelp-{$eqid}').style.display='inline';}else{\$('emailquote-{$eqid}').style.display='none';}">
OUTPUT;
		}
		else
		{
			$output	= "<p class='citation'>";
		}
		
		$snapback	= '';
				
		if( $options['post'] )
		{
			$snapback = "<a class='snapback' rel='citation' href='{$this->settings['board_url']}/index.php?app=forums&amp;module=forums&amp;section=findpost&amp;pid={$options['post']}'>" . 
						$this->registry->output->getReplacement( 'snapback' ) . "</a>";
		}

		if( $options['name'] OR $options['date'] OR $options['timestamp'] )
		{
			// sort timestamp
			if ( $options['timestamp'] AND strlen( $options['timestamp'] ) == 10 AND ( intval($options['timestamp']) == $options['timestamp'] ) )
			{
				if ( $this->settings['cc_on'] )
				{
					/* Add for parsing */
					$options['date'] = '<!--{timestamp:' . $options['timestamp'] . ':long}-->';
				}
				else
				{
					$options['date'] = $this->registry->getClass('class_localization')->getDate( $options['timestamp'], 'LONG' );
				}
			}

			if( $options['name'] AND $options['date'] )
			{
				$output .=  $snapback . sprintf( $this->lang->words['bbc_full_cite'], $options['name'], $options['date'] ) ;
			}
			else if( $options['name'] )
			{
				$output .=  $snapback . sprintf( $this->lang->words['bbc_name_cite'], $options['name'] ) ;
			}
			else if( $options['date'] )
			{
				$output .= $snapback . sprintf( $this->lang->words['bbc_date_cite'], $options['date'] ) ;
			}
		}
		elseif ( $options['collapse'] )
		{
			$output .= $snapback . $options['collapse'] . ' ' . $this->lang->words['bbc_quote_collapsed'];
		}
		else
		{
			$output .= $snapback . $this->lang->words['bbc_quote'];
		}
		
		$output .= "</p>";
		$output .= '<div class="blockquote">';
		$output .= $options['collapse'] ? "<div class='quote' id='emailquote-{$eqid}' style='display:none'>" : "<div class='quote'>";
		
		if ( substr_count( $output, '<p' ) != substr_count( $output, '</p' ) )
		{
			return '';
		}
		
		return $output;
	}

	/**
	 * Callback for quote preg_replace call
	 *
	 * @access	protected
	 * @param	array 		preg_replace_callback Matches
	 * @return	string		Output
	 */
	protected function _parseQuote( $matches )
	{
		$txt	= trim( $matches[1] );
		$_orig	= $txt;

		if ( ! $txt )
		{
			return '';
		}
		
		$this->quote_open   = 0;
		$this->quote_closed = 0;
		$this->quote_error  = 0;

		//-----------------------------------------
		// Make sure we don't have too many embedded
		//-----------------------------------------
		
		if ( $this->settings['max_quotes_per_post'] )
		{
			if ( substr_count( strtolower($txt), '[quote' ) > $this->settings['max_quotes_per_post'] )
			{
				$this->error = 'too_many_quotes';
				return $txt;
			}
		}

		//-----------------------------------------
		// Fix char 173
		//-----------------------------------------
		
		// This breaks Chinese and probably other MB languages
		// http://community.invisionpower.com/tracker/issue-26289-multibyte-issue-with-quotes
		//$txt	= str_replace( chr(173).']', '&#93;', $txt );
	
		//-----------------------------------------
		// Trim the quote content
		//-----------------------------------------
		
		$txt	= preg_replace_callback( '#\[quote([^\]]+?)?\](.+?)\[/quote\]#si', array( $this, '_trimQuote' ), $txt );
		
		//-----------------------------------------
		// Clean usernames with brackets and quotes
		//-----------------------------------------

		$txt	= preg_replace_callback( '#(name=(?:&\#39;|&quot;|\'|\"))(.+?)(&\#39;|&quot;|\'|\")#si', array( $this, '_makeQuoteSafe' ), $txt );
		$txt	= preg_replace_callback( '#\[quote([?:^\]]+?)?name=(&\#39;|&quot;|\'|\")(.+?)\]#si', array( $this, '_makeNameSafe' ), $txt );

		//-----------------------------------------
		// Replace out end tag
		//-----------------------------------------

		$txt	= str_ireplace( "[/quote]", "</div></div>", $txt, $this->quote_closed );

		//-----------------------------------------
		// Replace the quote tag
		//-----------------------------------------

		$txt	= preg_replace_callback( '#\[quote([^\]]+?)?\]#i', array( $this, '_replaceQuoteTag' ), $txt );
		
		//-----------------------------------------
		// Newlines
		//-----------------------------------------
		
		//$txt	= str_replace( "\n", "<br />", $txt );
		
		$txt	= str_replace( array( "<div class='quote'><br />", "<div class='quote'>~~~~~_____~~~~~" ), "<div class='quote'>", $txt );
		
		//-----------------------------------------
		// Swap name replacement (_makeNameSafe) back
		//-----------------------------------------
		
		$txt	= str_replace( '&#0039;', "&#39;", $txt );

		//-----------------------------------------
		// Turn attachments into links
		// Prevents em from breaking on other pages
		//-----------------------------------------
		
		preg_match_all( "#<div class='quote'>(.+?)</div></div>#ims", $txt, $_outerMatches );

		foreach( $_outerMatches[1] as $_outerMatch )
		{
			preg_match_all( '#\[attachment=(.+?):(.+?)\]#', $_outerMatch, $_matches );
	
			if( is_array( $_matches[1] ) && count( $_matches[1] ) )
			{
				foreach( $_matches[1] as $idx => $attach_id )
				{
					$txt = str_replace( "[attachment={$attach_id}:{$_matches[2][$idx]}]", $this->registry->getClass('output')->getReplacement('post_attach_link') . " <a href='{$this->settings['board_url']}/index.php?app=core&amp;module=attach&amp;section=attach&amp;attach_rel_module=post&amp;attach_id={$attach_id}' target='_blank'>{$_matches[2][$idx]}</a>", $txt );
				}
			}
		}

		//-----------------------------------------
		// If open and close tags match, we're good.
		// Otherwise, return an error.
		//-----------------------------------------

		if ( ( $this->quote_open == $this->quote_closed ) and ( $this->quote_error == 0 ) )
		{
			return $txt;
		}
		else
		{
			$this->error = 'quote_mismatch';

			return $_orig;
		}
	}
	
	/**
	 * Callback for triming quote
	 *
	 * @access	protected
	 * @param	array 		preg_replace_callback Matches
	 * @return	string		Output
	 */
	protected function _trimQuote( $matches )
	{
		$txt	= $matches[2];
		$extra	= IPSText::stripUrls( $matches[1] );
		
		if( $txt == "" )
		{
			return "[quote][/quote]";
		}
		else
		{
			$txt = trim( $txt );
			
			return "[quote{$extra}]{$txt}[/quote]";
		}
	}
	
	/**
	 * Make the quoted content safe for regex parsing
	 *
	 * @access	protected
	 * @param	array 		preg_replace_callback Matches
	 * @return	string		Output
	 */
	protected function _makeQuoteSafe( $matches )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$begin	= $matches[1];
		$end	= $matches[3];
		$txt	= $matches[2];
		
		//-----------------------------------------
		// Sort name
		//-----------------------------------------
		
		$txt = str_replace( "+", "&#043;" , $txt );
		$txt = str_replace( "-", "&#045;" , $txt );
		$txt = str_replace( ":", "&#58;"  , $txt );
		$txt = str_replace( "[", "&#91;"  , $txt );
		$txt = str_replace( "]", "&#93;"  , $txt );
		$txt = str_replace( ")", "&#41;"  , $txt );
		$txt = str_replace( "(", "&#40;"  , $txt );
		$txt = str_replace( "'", "&#039;" , $txt );

		return $begin . IPSText::getTextClass('bbcode')->xssHtmlClean( IPSText::getTextClass('bbcode')->stripAllTags( IPSText::stripUrls( $txt ) ) ) . $end;
	}
	
	/**
	 * Make the name used for the quote safe for regex parsing
	 *
	 * @access	protected
	 * @param	array 		preg_replace_callback Matches
	 * @return	string		Output
	 * @note	This looks complicated (and is somewhat) but it's done this way to try to quote correctly users with ' in their names
	 * @link 	http://community.invisionpower.com/tracker/issue-18588-bbcode-mistake-when-quoting-users-with-a-in-their-name
	 */
	protected function _makeNameSafe( $matches )
	{
		$quote	= $matches[1];
		$name	= $matches[2];
		$next	= '';

		if( strpos( $name, 'post=' ) !== false )
		{
			$next	= substr( $name, strpos( $name, 'post=' ) );
			$name	= substr( $name, 0, strpos( $name, 'post=' ) -1 );
		}

		if( strpos( $name, 'date=' ) !== false )
		{
			$next	= $next . ' ' . substr( $name, strpos( $name, 'date=' ) );
			$name	= substr( $name, 0, strpos( $name, 'date=' ) -1 );
		}

		if( strpos( $name, 'timestamp=' ) !== false )
		{
			$next	= $next . ' ' . substr( $name, strpos( $name, 'timestamp=' ) );
			$name	= substr( $name, 0, strpos( $name, 'timestamp=' ) -1 );
		}
		
		$name	= substr( $name, 0, -(strlen($quote)) );

		# Squeeze past the parser...
		$name  = str_replace( array( '&#39;', "'" ), "&#0039;", $name );
		
		$_last	= $next ? ' ' . $next : '';
		
		return 'name=' . $quote . IPSText::getTextClass('bbcode')->xssHtmlClean( IPSText::getTextClass('bbcode')->stripAllTags( IPSText::stripUrls( $name ) ) ) . $quote . $_last . ']'; 
	}

	/**
	 * Replace the quote tag
	 *
	 * @access	protected
	 * @param	array 		preg_replace_callback Matches
	 * @return	string		Output
	 */
	protected function _replaceQuoteTag( $matches )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$extra		= str_replace( '&apos;', "'", $matches[1] );
		$post_id	= 0;
		$date		= '';
		$timestamp	= 0;
		$name		= '';

		//-----------------------------------------
		// Inc..
		//-----------------------------------------

		$this->quote_open++;

		//-----------------------------------------
		// Post?
		//-----------------------------------------
		
		preg_match( '#post=([\"\']|&quot;|&\#039;|&\#39;)?(\d+)(\\1)?#', $extra, $match );
		
		if ( isset($match[2]) AND intval( $match[2] ) )
		{
			$post_id = intval( $match[2] );
		}
	
		//-----------------------------------------
		// Name?
		//-----------------------------------------
		
		preg_match( '#name=([\"\']|&quot;|&\#039;|&\#39;)(.*?)(\\1)\s?(date|post)?#is', $extra, $match );

		if ( !empty($match[2]) )
		{
			$name = $match[2] ? $this->_makeQuoteSafe( array( 2 => $match[2] ) ) : '-';
		}

		//-----------------------------------------
		// Date?
		//-----------------------------------------
		
		preg_match( '#date=([\"\']|&quot;|&\#039;|&\#39;)(.*?)(\\1)#', $extra, $match );
		
		if ( !empty($match[2]) )
		{
			$date = $this->_makeQuoteSafe( array( 2 => $match[2] ) );
		}
		
		//-----------------------------------------
		// Timestamp?
		//-----------------------------------------
		
		preg_match( '#timestamp=([\"\']|&quot;|&\#039;|&\#39;)(.*?)(\\1)#', $extra, $match );
		
		if ( !empty($match[2]) )
		{
			$timestamp	= intval( $match[2] );
		}
		
		//-----------------------------------------
		// Collapse?
		//-----------------------------------------
		
		preg_match( '#collapse=([\"\']|&quot;|&\#039;|&\#39;)(.*?)(\\1)#', $extra, $match );
		
		if ( !empty($match[2]) )
		{
			$collapse	= $match[2];
		}

		return $this->_buildOutput( array( 'name' => $name, 'date' => $date, 'post' => $post_id, 'timestamp' => $timestamp, 'collapse' => $collapse ) );
	}
}

//----------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------

class bbcode_list extends bbcode_parent_class implements bbcodePlugin
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @param	object		Parent bbcode class
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $_parent=null )
	{
		$this->currentBbcode	= 'list';
		
		parent::__construct( $registry, $_parent );
	}
	
	//public function preDisplayParse($txt){parent::preDisplayParse($txt);exit;}
	
	/**
	 * Do the actual replacement
	 *
	 * @access	protected
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	string				BBCode content, ready for editing
	 */
	protected function _replaceText( $txt )
	{
		$_tags = $this->_retrieveTags();

		$txt = preg_replace( '#\[/list\](\s+?)?<br />#is', "[/list]", $txt );
		
		foreach( $_tags as $_tag )
		{
			while( preg_match( "#\n?\[{$_tag}(.*?)\](.+?)\[/{$_tag}\]\n?#is" , $txt, $matches ) )
			{
				$txt = preg_replace_callback( "#(\n){0,1}\[{$_tag}(.*?)\](.+?)\[/{$_tag}\](\n){0,1}#is", array( &$this, '_buildOutput' ), $txt );
			}

			/*while( preg_match( "#\n?\[{$_tag}=(a|A|i|I|1)\](.+?)\[/{$_tag}\]\n?#is" , $txt ) )
			{
				$txt = preg_replace_callback( "#(\n){0,1}\[{$_tag}=(a|A|i|I|1)\](.+?)\[/{$_tag}\](\n){0,1}#is", array( &$this, '_buildOutput' ), $txt );
			}*/
		}

		return $txt;
	}
	
	/**
	 * Build the actual output to show
	 *
	 * @access	protected
	 * @param	string		$matches	Array of regex matches
	 * @return	string					Content to replace bbcode with
	 */
	protected function _buildOutput( $matches=array() )
	{
		//-----------------------------------------
		// Make sure we have at least one list item
		//-----------------------------------------
		
		//if( $matches[2] AND strpos( $matches[2], '[*]' ) === false )
		//{
		//	return preg_replace( "#\[/?list\]?#i", "", $matches[0] . $matches[2] . $matches[3] );
		//}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		if( $matches[2] )
		{
			$matches[2] = str_replace( array( '"', "'", '&quot;', '&#039;', '&#39;', '=' ), '', $matches[2] );
		}

		$types = array( 'a', 'A', 'i', 'I', '1' );

		if ( in_array( $matches[2], $types ) )
		{
			$fnl	= $matches[1];
			$type	= $matches[2];
			$txt 	= $matches[3];
			$lnl	= $matches[4];
		}
		else
		{
			$fnl	= $matches[1];
			$type	= '';
			$txt	= $matches[3];
			$lnl	= $matches[4];
		}
		
		if ( !$txt )
		{
			return;
		}
		
		//-----------------------------------------
		// No br tags, br tags bad
		//-----------------------------------------

		$txt	= str_replace( "\n", "", $txt );
		$txt	= str_replace( array( '<br />', '<br>'), "\n", $txt );
		$txt	= str_replace( "[/list]\n[*]", "[/list][*]", $txt );

		$return	= '';

		if ( ! $type )
		{
			$return	= $fnl . "<ul class='bbc'>" . $this->_listItem($txt) . "</ul>" . $lnl;
		}
		else
		{
			$_cssClass	= "decimal";
			
			switch( $type )
			{
				case 'a':
					$_cssClass	= "lower-alpha";
				break;

				case 'A':
					$_cssClass	= "upper-alpha";
				break;

				case 'i':
					$_cssClass	= "lower-roman";
				break;

				case 'I':
					$_cssClass	= "upper-roman";
				break;
			}
			
		//	if( !$this->caches['_tmp_bbcode_isForRte'] )
		//	{
				$return	= $fnl . "<ul class='bbcol {$_cssClass}'>" . $this->_listItem($txt) . "</ul>" . $lnl;
		//	}
		//	else
		//	{
				//$return	= $fnl . "<ol class='bbcol {$_cssClass}'>" . $this->_listItem($txt) . "</ol>" . $lnl;
			//}
		}
		
		/* Need to remove any extra closing <li> tags. bug #20327 */
		$return = preg_replace( '/<ul class=\'(.+?)\'>~~~~~_____~~~~~<\/li>/', "<ul class='\\1'>~~~~~_____~~~~~", $return );
		
		return $return;
	}
	
	/**
	 * Build a list item
	 *
	 * @access	protected
	 * @param	string		$txt	Text
	 * @return	string		List item
	 */
	protected function _listItem( $txt )
	{
		$txt = preg_replace( '#\[\*\]#'		, "</li><li>"	, trim($txt) );
		$txt = preg_replace( "#^</?li>#"	, ""			, $txt );
		
		/* Bug @link http://community.invisionpower.com/tracker/issue-37342-lists-getting-double-spaced/ */
		$txt = preg_replace( '#(&nbsp;){1,}</li>#', '</li>', $txt );
		$txt = preg_replace( '#(<br(?:[^>])>){1,}</li>#', '</li>', $txt );
		
		return str_replace( "\n</li>", "</li>", nl2br($txt) . "</li>" );
	}
}

//----------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------

class bbcode_indent extends bbcode_parent_class implements bbcodePlugin
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @param	object		Parent bbcode class
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $_parent=null )
	{
		$this->currentBbcode	= 'indent';
		
		parent::__construct( $registry, $_parent );
	}

	/**
	 * Do the actual replacement
	 *
	 * @access	protected
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	string				BBCode content, ready for editing
	 */
	protected function _replaceText( $txt )
	{
		$_tags = $this->_retrieveTags();
		
		/* Convert old indent tags over */
		if ( stristr( $txt, '[indent]' ) )
		{
			$txt = str_ireplace( '[indent]', '[indent=1]', $txt );
		}
		
		foreach( $_tags as $_tag )
		{
			//-----------------------------------------
			// Infinite loop catcher
			//-----------------------------------------
			
			$_iteration	= 0;
			
			//-----------------------------------------
			// Start building open tag
			//-----------------------------------------
			
			$open_tag = '[' . $_tag . '=';
	
			//-----------------------------------------
			// Doz I can haz opin tag? Loopy loo
			//-----------------------------------------
			
			while( ( $this->cur_pos = stripos( $txt, $open_tag, $this->cur_pos ) ) !== false )
			{
				//-----------------------------------------
				// Stop infinite loops
				//-----------------------------------------
				
				if( $_iteration > $this->settings['max_bbcodes_per_post'] )
				{
					break;
				}
				
				$_iteration++;

				//-----------------------------------------
				// Grab the new position to jump to
				//-----------------------------------------
				
				$new_pos = strpos( $txt, ']', $this->cur_pos ) ? strpos( $txt, ']', $this->cur_pos ) : $this->cur_pos + 1;

				//-----------------------------------------
				// Extract the option (like surgery)
				//-----------------------------------------
	
				$_content	= '';
				$_option	= substr( $txt, $this->cur_pos + strlen($open_tag), (strpos( $txt, ']', $this->cur_pos ) - ($this->cur_pos + strlen($open_tag))) );
	
				$close_tag	= '[/' . $_tag . ']';
				
				//-----------------------------------------
				// Protect against XSS
				//-----------------------------------------
				
				$_option = intval($_option);
				$_option = ( $_option ) ? $_option : 1;
			
				//-----------------------------------------
				// No closing tag
				//-----------------------------------------
				
				if ( $_option !== false AND stripos( $txt, $close_tag, $new_pos ) !== false )
				{
					$_content	= substr( $txt, ($this->cur_pos + strlen($open_tag) + strlen($_option) + 1), (stripos( $txt, $close_tag, $this->cur_pos ) - ($this->cur_pos + strlen($open_tag) + strlen($_option) + 1)) );
		
					//-----------------------------------------
					// If this is a single tag, that's it
					//-----------------------------------------

					if( preg_match( '/\S/', $_content ) ) /* Make sure we don't miss this, if there's only a 0 for the content. Bug #21610 */
					{
						$txt = substr_replace( $txt, $this->_buildOutput( $_option, $_content ), $this->cur_pos, (stripos( $txt, $close_tag, $this->cur_pos ) + strlen($close_tag) - $this->cur_pos) );
					}
					else
					{
						$txt = substr_replace( $txt, '', $this->cur_pos, (stripos( $txt, $close_tag, $this->cur_pos ) + strlen($close_tag) - $this->cur_pos) );
					}
				}
				
				//-----------------------------------------
				// And reset current position to end of open tag
				//-----------------------------------------

				$this->cur_pos = stripos( $txt, $open_tag ) ? stripos( $txt, $open_tag ) : $this->cur_pos + 1; //$new_pos;

				if( $this->cur_pos > strlen($txt) )
				{
					//-----------------------------------------
					// Need to reset for next "tag"
					//-----------------------------------------
					
					$this->cur_pos	= 0;
					break;
				}
			}
		}

		return $txt;
	}
	
	/**
	 * Build the actual output to show
	 *
	 * @access	protected
	 * @param	integer		$option		Font size
	 * @param	string		$content	Text
	 * @return	string					Content to replace bbcode with
	 */
	protected function _buildOutput( $option, $content )
	{
		//-----------------------------------------
		// Strip the optional quote delimiters
		//-----------------------------------------

		$option			= intval( $option );
		$size 			= $option * 40;
		$content        = trim( $content );
		
		return "<p class='bbc_indent' style='margin-left: " . $size . "px;'>{$content}</p>";
	}
}
//----------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------

class bbcode_size extends bbcode_parent_class implements bbcodePlugin
{
	/**
	 * Mapped font sizes
	 *
	 * @access	protected
	 * @var		array
	 */	
	protected $font_sizes     = array( 1 => 8,
									   2 => 10,
									   3 => 12,
									   4 => 14,
									   5 => 18,
									   6 => 24,
									   7 => 36,
									   8 => 48 );

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @param	object		Parent bbcode class
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $_parent=null )
	{
		$this->currentBbcode	= 'size';
		
		parent::__construct( $registry, $_parent );
	}

	/**
	 * Do the actual replacement
	 *
	 * @access	protected
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	string				BBCode content, ready for editing
	 */
	protected function _replaceText( $txt )
	{
		$_tags = $this->_retrieveTags();

		foreach( $_tags as $_tag )
		{
			//-----------------------------------------
			// Infinite loop catcher
			//-----------------------------------------
			
			$_iteration	= 0;
			
			//-----------------------------------------
			// Start building open tag
			//-----------------------------------------
			
			$open_tag = '[' . $_tag . '=';
	
			//-----------------------------------------
			// Doz I can haz opin tag? Loopy loo
			//-----------------------------------------
			
			while( ( $this->cur_pos = stripos( $txt, $open_tag, $this->cur_pos ) ) !== false )
			{
				//-----------------------------------------
				// Stop infinite loops
				//-----------------------------------------
				
				if( $_iteration > $this->settings['max_bbcodes_per_post'] )
				{
					break;
				}
				
				$_iteration++;

				//-----------------------------------------
				// Grab the new position to jump to
				//-----------------------------------------
				
				$new_pos = strpos( $txt, ']', $this->cur_pos ) ? strpos( $txt, ']', $this->cur_pos ) : $this->cur_pos + 1;

				//-----------------------------------------
				// Extract the option (like surgery)
				//-----------------------------------------
	
				$_content	= '';
				$_option	= substr( $txt, $this->cur_pos + strlen($open_tag), (strpos( $txt, ']', $this->cur_pos ) - ($this->cur_pos + strlen($open_tag))) );
	
				$close_tag	= '[/' . $_tag . ']';
				
				//-----------------------------------------
				// Protect against XSS
				//-----------------------------------------
				
				$_option	= IPSText::getTextClass('bbcode')->xssHtmlClean($_option);
				
				/* Make sure it's clean */
				$test = str_replace( array( '"', "'", '&quot;', '&#39;' ), "", $_option );
				$test1 = IPSText::alphanumericalClean( $test, '.+ ' );
				
				if ( $test1 != $test )
				{
					$_option = false;
				}
				
				//-----------------------------------------
				// No closing tag
				//-----------------------------------------
				
				if ( $_option !== false AND stripos( $txt, $close_tag, $new_pos ) !== false )
				{
					$_content	= substr( $txt, ($this->cur_pos + strlen($open_tag) + strlen($_option) + 1), (stripos( $txt, $close_tag, $this->cur_pos ) - ($this->cur_pos + strlen($open_tag) + strlen($_option) + 1)) );
		
					//-----------------------------------------
					// If this is a single tag, that's it
					//-----------------------------------------

					if( preg_match( '/\S/', $_content ) ) /* Make sure we don't miss this, if there's only a 0 for the content. Bug #21610 */
					{
						$txt		= substr_replace( $txt, $this->_buildOutput( $_option, $_content ), $this->cur_pos, (stripos( $txt, $close_tag, $this->cur_pos ) + strlen($close_tag) - $this->cur_pos) );
					}
					else
					{
						$txt		= substr_replace( $txt, '', $this->cur_pos, (stripos( $txt, $close_tag, $this->cur_pos ) + strlen($close_tag) - $this->cur_pos) );
					}
				}
				
				//-----------------------------------------
				// And reset current position to end of open tag
				//-----------------------------------------

				$this->cur_pos = stripos( $txt, $open_tag ) ? stripos( $txt, $open_tag ) : $this->cur_pos + 1; //$new_pos;

				if( $this->cur_pos > strlen($txt) )
				{
					//-----------------------------------------
					// Need to reset for next "tag"
					//-----------------------------------------
					
					$this->cur_pos	= 0;
					break;
				}
			}
		}

		return $txt;
	}
	
	/**
	 * Build the actual output to show
	 *
	 * @access	protected
	 * @param	integer		$option		Font size
	 * @param	string		$content	Text
	 * @return	string					Content to replace bbcode with
	 */
	protected function _buildOutput( $option, $content )
	{
		//-----------------------------------------
		// Strip the optional quote delimiters
		//-----------------------------------------

		$option			= trim( $option, '"' . "'" );
		$option			= str_replace( '&quot;', '', $option );
		$option			= str_replace( '&#39;', '', $option );
		
		$size 			= $this->font_sizes[ $option ];
		
		return "<span style='font-size: " . $size . "px;'>{$content}</span>";
	}
}

//----------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------

class bbcode_snapback extends bbcode_parent_class implements bbcodePlugin
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @param	object		Parent bbcode class
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $_parent=null )
	{
		$this->currentBbcode	= 'snapback';
		
		parent::__construct( $registry, $_parent );
	}

	/**
	 * Do the actual replacement
	 *
	 * @access	protected
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	string				BBCode content, ready for editing
	 */
	protected function _replaceText( $txt )
	{
		$_tags = $this->_retrieveTags();

		foreach( $_tags as $_tag )
		{
			//-----------------------------------------
			// Infinite loop catcher
			//-----------------------------------------
			
			$_iteration	= 0;
			
			//-----------------------------------------
			// Start building open tag
			//-----------------------------------------
			
			$open_tag = '[' . $_tag . ']';
	
			//-----------------------------------------
			// Doz I can haz opin tag? Loopy loo
			//-----------------------------------------
			
			while( ( $this->cur_pos = stripos( $txt, $open_tag, $this->cur_pos ) ) !== false )
			{
				//-----------------------------------------
				// Stop infinite loops
				//-----------------------------------------
				
				if( $_iteration > $this->settings['max_bbcodes_per_post'] )
				{
					break;
				}
				
				$_iteration++;
						
				//-----------------------------------------
				// Grab the new position to jump to
				//-----------------------------------------
				
				$new_pos = strpos( $txt, ']', $this->cur_pos ) ? strpos( $txt, ']', $this->cur_pos ) : $this->cur_pos + 1;
				
				//-----------------------------------------
				// Grab content
				//-----------------------------------------

				$close_tag	= '[/' . $_tag . ']';
				$_content	= substr( $txt, ($this->cur_pos + strlen($open_tag) ), (stripos( $txt, $close_tag, $this->cur_pos ) - ($this->cur_pos + strlen($open_tag))) );

				//-----------------------------------------
				// If this is a single tag, that's it
				//-----------------------------------------
				
				if( $_content )
				{
					$txt		= substr_replace( $txt, $this->_buildOutput( $_content ), $this->cur_pos, (stripos( $txt, $close_tag, $this->cur_pos ) + strlen($close_tag) - $this->cur_pos) );
				}
				else
				{
					$txt		= substr_replace( $txt, '', $this->cur_pos, (stripos( $txt, $close_tag, $this->cur_pos ) + strlen($close_tag) - $this->cur_pos) );
				}
				
				//-----------------------------------------
				// And reset current position to end of open tag
				//-----------------------------------------
				
				$this->cur_pos = stripos( $txt, $open_tag ) ? stripos( $txt, $open_tag ) : $this->cur_pos + 1; //$new_pos;
				
				if( $this->cur_pos > strlen($txt) )
				{
					//-----------------------------------------
					// Need to reset for next "tag"
					//-----------------------------------------
					
					$this->cur_pos	= 0;
					break;
				}
			}
		}

		return $txt;
	}
	
	/**
	 * Build the actual output to show
	 *
	 * @access	protected
	 * @param	string		$content	Snapback ID
	 * @return	string					Content to replace bbcode with
	 */
	protected function _buildOutput( $content )
	{
		//-----------------------------------------
		// Prevent XSS in URL
		//-----------------------------------------

		$content		= intval($content);

		if( !$content )
		{
			return '';
		}

		return "<a href='{$this->settings['board_url']}/index.php?app=forums&amp;module=forums&amp;section=findpost&amp;pid={$content}' class='bbc_url'>" . $this->registry->output->getReplacement( 'snapback' ) . "</a>";
	}
}

//----------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------

class bbcode_member extends bbcode_parent_class implements bbcodePlugin
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @param	object		Parent bbcode class
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $_parent=null )
	{
		$this->currentBbcode	= 'member';
		
		parent::__construct( $registry, $_parent );
	}

	/**
	 * Do the actual replacement
	 *
	 * @access	protected
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	string				BBCode content, ready for editing
	 */
	protected function _replaceText( $txt )
	{
		$_tags = $this->_retrieveTags();

		foreach( $_tags as $_tag )
		{
			//-----------------------------------------
			// Infinite loop catcher
			//-----------------------------------------
			
			$_iteration	= 0;
			
			//-----------------------------------------
			// Start building open tag
			//-----------------------------------------
			
			$open_tag = '[' . $_tag . '=';
	
			//-----------------------------------------
			// Doz I can haz opin tag? Loopy loo
			//-----------------------------------------
			
			while( ( $this->cur_pos = stripos( $txt, $open_tag, $this->cur_pos ) ) !== false )
			{
				//-----------------------------------------
				// Stop infinite loops
				//-----------------------------------------
				
				if( $_iteration > $this->settings['max_bbcodes_per_post'] )
				{
					break;
				}
				
				$_iteration++;
						
				//-----------------------------------------
				// Grab the new position to jump to
				//-----------------------------------------
				
				$new_pos = strpos( $txt, ']', $this->cur_pos ) ? strpos( $txt, ']', $this->cur_pos ) : $this->cur_pos + 1;
				
				//-----------------------------------------
				// Extract the option (like surgery)
				//-----------------------------------------
	
				$_content	= '';
				$_option	= substr( $txt, $this->cur_pos + strlen($open_tag), (strpos( $txt, ']', $this->cur_pos ) - ($this->cur_pos + strlen($open_tag))) );
				$_length	= strlen( $_option );

				/* @link http://community.invisionpower.com/tracker/issue-24470-bbcodemember-not-working-when-in-the-name/ */
				
				if( substr( $_option, 0, 1 ) == "'" OR substr( $_option, 0, 1 ) == '"' )
				{
					$_option	= substr( $_option, 1 );
				}
				
				if( substr( $_option, 0, 5 ) == "&#39;" )
				{
					$_option	= substr( $_option, 5 );
				}
				
				if( substr( $_option, 0, 6 ) == "&quot;" )
				{
					$_option	= substr( $_option, 6 );
				}
				
				if( substr( $_option, -1 ) == "'" OR substr( $_option, -1 ) == '"' )
				{
					$_option	= substr( $_option, 0, -1 );
				}
				
				if( substr( $_option, -5, 5 ) == "&#39;" )
				{
					$_option	= substr( $_option, 0, -5 );
				}
				
				if( substr( $_option, -6, 6 ) == "&quot;" )
				{
					$_option	= substr( $_option, 0, -6 );
				}

				$existing	= $this->cache->getCache( '_tmp_bbcode_members', false );
				$existing	= is_array($existing) ? $existing : array();
	
				if ( ! isset($existing[ $_option ]) )
				{
					$existing[ $_option ] = IPSMember::load( $_option, 'core', 'displayname' );
					$this->cache->updateCacheWithoutSaving( '_tmp_bbcode_members', $existing );
				}
				
				if( $existing[ $_option ]['members_display_name'] )
				{	
					$_content = $this->_buildOutput( $existing[ $_option ] );
				}
	
				//-----------------------------------------
				// If this is a single tag, that's it
				//-----------------------------------------
				
				if( $_content )
				{
					$txt	= substr_replace( $txt, $_content, $this->cur_pos, (strlen($open_tag) + $_length + 1) );
				}
				else
				{
					$txt	= substr_replace( $txt, '', $this->cur_pos, (strlen($open_tag) + $_length + 1) );
				}
				
				//-----------------------------------------
				// And reset current position to end of open tag
				//-----------------------------------------
				
				$this->cur_pos = stripos( $txt, $open_tag ) ? stripos( $txt, $open_tag ) : $this->cur_pos + 1; //$new_pos;
				
				if( $this->cur_pos > strlen($txt) )
				{
					//-----------------------------------------
					// Need to reset for next "tag"
					//-----------------------------------------
					
					$this->cur_pos	= 0;
					break;
				}
			}
		}

		return $txt;
	}
	
	/**
	 * Build the actual output to show
	 *
	 * @access	protected
	 * @param	array		$member	Member id and display name
	 * @return	string				Content to replace bbcode with
	 */
	protected function _buildOutput( $member )
	{
		$member['_hoverClass'] = 'bbc_member';
		$member['_hoverTitle'] = $this->lang->words['bbc_member_bbcode'];
		
		/* Be sure we can see profile for this (cache sucks) */
		$_temp = $this->memberData['g_mem_info'];
		$this->memberData['g_mem_info'] = 1;
		
		$_output = $this->registry->output->getTemplate('global')->userHoverCard( $member );
		
		$this->memberData['g_mem_info'] = $_temp;
		
		/* Return output */
		return $_output;
	}
}

//----------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------

class bbcode_media extends bbcode_parent_class implements bbcodePlugin
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @param	object		Parent bbcode class
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $_parent=null )
	{
		$this->currentBbcode	= 'media';
		
		parent::__construct( $registry, $_parent );
	}

	/**
	 * Do the actual replacement
	 *
	 * @access	protected
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	string				BBCode content, ready for editing
	 */
	protected function _replaceText( $txt )
	{
		$_tags = $this->_retrieveTags();

		foreach( $_tags as $_tag )
		{
			//-----------------------------------------
			// Infinite loop catcher
			//-----------------------------------------
			
			$_iteration	= 0;
			
			//-----------------------------------------
			// Start building open/close tag
			//-----------------------------------------

			$open_tag	= '[' . $_tag;
			$close_tag	= '[/' . $_tag . ']';

			//-----------------------------------------
			// Doz I can haz opin tag? Loopy loo
			//-----------------------------------------
			
			while( ( $this->cur_pos = stripos( $txt, $open_tag, $this->cur_pos ) ) !== false )
			{
				//-----------------------------------------
				// Stop infinite loops
				//-----------------------------------------
				
				if( $_iteration > $this->settings['max_bbcodes_per_post'] )
				{
					break;
				}
				
				$_iteration++;
						
				$open_length = strlen($open_tag);
				
				//-----------------------------------------
				// Extract the option (like surgery)
				//-----------------------------------------

				$_option	= '';
				
				if( $this->_bbcode['bbcode_useoption'] )
				{
					//-----------------------------------------
					// Is option optional?
					//-----------------------------------------
				
					if( $this->_bbcode['bbcode_optional_option'] )
					{
						//-----------------------------------------
						// Does we haz it?
						//-----------------------------------------
						
						if( substr( $txt, $this->cur_pos + strlen($open_tag), 1 ) == '=' )
						{
							$open_length	+= 1;
							$_option		= substr( $txt, $this->cur_pos + $open_length, (strpos( $txt, ']', $this->cur_pos ) - ($this->cur_pos + $open_length)) );
						}
						
						//-----------------------------------------
						// If not, [u] != [url] (for example)
						//-----------------------------------------
				
						else if( (strpos( $txt, ']', $this->cur_pos ) - ( $this->cur_pos + $open_length )) !== 0 )
						{
							$this->cur_pos = strpos( $txt, ']', $this->cur_pos );
							continue;
						}
					}
					
					//-----------------------------------------
					// No?  Then just grab it
					//-----------------------------------------
					
					else
					{
						$open_length	+= 1;
						$_option		= substr( $txt, $this->cur_pos + $open_length, (strpos( $txt, ']', $this->cur_pos ) - ($this->cur_pos + $open_length)) );
					}
				}
				
				//-----------------------------------------
				// [img] != [i] (for example)
				//-----------------------------------------
				
				else if( (strpos( $txt, ']', $this->cur_pos ) - ( $this->cur_pos + $open_length )) !== 0 )
				{
					$this->cur_pos = strpos( $txt, ']', $this->cur_pos ) ? strpos( $txt, ']', $this->cur_pos ) : $this->cur_pos + 1;
					continue;
				}
				
				//-----------------------------------------
				// Grab the new position to jump to
				//-----------------------------------------
				
				$new_pos = strpos( $txt, ']', $this->cur_pos ) ? strpos( $txt, ']', $this->cur_pos ) : $this->cur_pos + 1;
				
				//-----------------------------------------
				// No closing tag
				//-----------------------------------------
				
				if( stripos( $txt, $close_tag, $new_pos ) === false )
				{
					break;
				}
				
				//-----------------------------------------
				// Grab the content
				//-----------------------------------------
				
				$_content	= substr( $txt, ($this->cur_pos + $open_length + strlen($_option) + 1), (stripos( $txt, $close_tag, $this->cur_pos ) - ($this->cur_pos + $open_length + strlen($_option) + 1)) );
				
				if ( strpos( $_content, "<a " ) !== false )
				{
					$_content = preg_replace( '/\<a href=[\'\"](.+?)[\"\'].*\>(.+?)\<\/a\>/i', "\\1", $_content );
				}
				
				/* Make sure we've not embedded [media] */
				if ( stristr( $_content, '[media]' ) )
				{
					return $txt;
				}
				
				//-----------------------------------------
				// If this is a single tag, that's it
				//-----------------------------------------
				
				if( $_content )
				{
					$txt = substr_replace( $txt, $this->_buildOutput( $_content, $_option ), $this->cur_pos, (stripos( $txt, $close_tag, $this->cur_pos ) + strlen($close_tag) - $this->cur_pos) );
				}
				else
				{
					$txt = substr_replace( $txt, '', $this->cur_pos, (stripos( $txt, $close_tag, $this->cur_pos ) + strlen($close_tag) - $this->cur_pos) );
				}
				
				//-----------------------------------------
				// And reset current position to end of open tag
				//-----------------------------------------
				
				$this->cur_pos = stripos( $txt, $open_tag ) ? stripos( $txt, $open_tag ) : $this->cur_pos + 1; //$new_pos;

				if( $this->cur_pos > strlen($txt) )
				{
					//-----------------------------------------
					// Need to reset for next "tag"
					//-----------------------------------------
					
					$this->cur_pos	= 0;
					break;
				}
			}
		}

		return $txt;
	}
	
	/**
	 * Build the actual output to show
	 *
	 * @access	protected
	 * @param	array		$content	Image URL to link to
	 * @param	string		$option		[Optional] Dimension options (width,height)
	 * @return	string					Content to replace bbcode with
	 */
	protected function _buildOutput( $content, $option='' )
	{
		//-----------------------------------------
		// Too many media files?
		//-----------------------------------------

		$existing	= $this->cache->getCache( '_tmp_bbcode_media', false );
		$existing	= intval($existing) + 1;

		if ( $this->settings['max_media_files'] )
		{
			if ( $existing > $this->settings['max_media_files'] )
			{
				$this->error = 'too_many_media';
				return $content;
			}
		}
		
		$this->cache->updateCacheWithoutSaving( '_tmp_bbcode_media', $existing );
		
		//-----------------------------------------
		// XSS check
		//-----------------------------------------
		
		if ( ! IPSText::xssCheckUrl( $content ) )
		{
			return $content;
		}

		//-----------------------------------------
		// Loop through media tags and extract
		//-----------------------------------------
		
		$media		= $this->cache->getCache( 'mediatag' );
		$original	= $content;

       	if( is_array($media) AND count($media) )
		{
			foreach( $media as $type => $r )
			{
				if( preg_match( "#^" . $r['match'] . "$#is", $content ) )
				{
					$content = preg_replace( "#^" . $r['match'] . "$#is", $r['replace'], $content );
					
					if( $option )
					{
						list( $width, $height )	= explode( ',', str_replace( array( '"', "'", '&#39;', '&quot;' ), '', $option ) );
						
						if( $width AND $height )
						{
							if ( $width > $this->settings['max_w_flash'] )
							{
								$this->error = 'flash_too_big';
								return $original;
							}
							
							if ( $height > $this->settings['max_h_flash'] )
							{
								$this->error = 'flash_too_big';
								return $original;
							}

							$content = str_replace( '{width}', "width='{$width}'", $content );
							$content = str_replace( '{height}', "height='{$height}'", $content );
						}
					}
					else
					{
						$content = str_replace( '{width}', "", $content );
						$content = str_replace( '{height}', "", $content );
					}
					
					$content = str_replace( '{base_url}', $this->settings['board_url'] . '/index.php?', $content );
					$content = str_replace( '{board_url}', $this->settings['board_url'], $content );
					$content = str_replace( '{image_url}', $this->settings['img_url'], $content );
					
					preg_match( '/\{text\.(.+?)\}/i', $content, $matches );
					
					if( is_array($matches) AND count($matches) )
					{
						$content = str_replace( $matches[0], $this->lang->words[ $matches[1] ], $content );
					}
				}
			}
		}

		return $content;
	}
}

//----------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------

class bbcode_url extends bbcode_parent_class implements bbcodePlugin
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @param	object		Parent bbcode class
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $_parent=null )
	{
		$this->currentBbcode	= 'url';
		
		parent::__construct( $registry, $_parent );
	}

	/**
	 * Do the actual replacement
	 *
	 * @access	protected
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	string				BBCode content, ready for editing
	 */
	protected function _replaceText( $txt )
	{
		$_tags = $this->_retrieveTags();

		foreach( $_tags as $_tag )
		{
			//-----------------------------------------
			// Infinite loop catcher
			//-----------------------------------------
			
			$_iteration	= 0;
			
			//-----------------------------------------
			// Start building open/close tag
			//-----------------------------------------

			$open_tag	= '[' . $_tag;
			$close_tag	= '[/' . $_tag . ']';

			//-----------------------------------------
			// Doz I can haz opin tag? Loopy loo
			//-----------------------------------------

			while( ( $this->cur_pos = stripos( $txt, $open_tag, $this->cur_pos ) ) !== false )
			{
				//-----------------------------------------
				// Stop infinite loops
				//-----------------------------------------
				
				if( $_iteration > $this->settings['max_bbcodes_per_post'] )
				{
					break;
				}
				
				$_iteration++;
						
				$open_length = strlen($open_tag);
				
				//-----------------------------------------
				// Extract the option (like surgery)
				//-----------------------------------------

				$_option	= '';
				
				if( $this->_bbcode['bbcode_useoption'] )
				{
					//-----------------------------------------
					// Is option optional?
					//-----------------------------------------
				
					if( $this->_bbcode['bbcode_optional_option'] )
					{
						//-----------------------------------------
						// Does we haz it?
						//-----------------------------------------
				
						if( substr( $txt, $this->cur_pos + strlen($open_tag), 1 ) == '=' )
						{
							$open_length	+= 1;
							
							//-----------------------------------------
							// This is here to try to capture urls with
							// [ and ] in them, only works if enclosed in quotes
							//-----------------------------------------
							
							$cur_content	= '';

							if( substr( $txt, $this->cur_pos + $open_length, 6 ) == '&quot;' )
							{
								/* Skip the bbocde if there is more than 2 quotes. Bug #21161 */
								if( strlen( substr( $txt, $this->cur_pos, stripos( $txt, ']', $this->cur_pos ) - $this->cur_pos ) ) < 1 OR ( substr_count( $txt, '&quot;', $this->cur_pos, strlen( substr( $txt, $this->cur_pos, stripos( $txt, ']', $this->cur_pos ) - $this->cur_pos ) ) ) ) > 2 )
								{
									$this->cur_pos = strpos( $txt, ']', $this->cur_pos ) ? strpos( $txt, ']', $this->cur_pos ) : $this->cur_pos + 1;
									continue;
								}

								$end_pos		= stripos( $txt, '&quot;', $this->cur_pos + $open_length + 1 ) ? stripos( $txt, '&quot;', $this->cur_pos + $open_length + 1 ) : strpos( $txt, ']', $this->cur_pos + $open_length + 1 );
								$cur_content	= substr( $txt, $this->cur_pos + $open_length + 6, ($end_pos - ($this->cur_pos + $open_length + 6 ) ) );
								$new_content	= str_replace( '[', '%5B', str_replace( ']', '%5D', $cur_content ) );
								$txt			= substr_replace( $txt, $new_content, $this->cur_pos + $open_length + 6, ($end_pos - ($this->cur_pos + $open_length + 6 ) ) );
							}
							else if( substr( $txt, $this->cur_pos + $open_length, 5 ) == "&#39;" )
							{
								/* Skip the bbocde if there is mroe than 2 quotes. Bug #21161 */
								if( strlen( substr( $txt, $this->cur_pos, stripos( $txt, ']', $this->cur_pos ) - $this->cur_pos ) ) < 1 OR ( substr_count( $txt, '&#39;', $this->cur_pos, strlen( substr( $txt, $this->cur_pos, stripos( $txt, ']', $this->cur_pos ) - $this->cur_pos ) ) ) ) > 2 )
								{
									$this->cur_pos = strpos( $txt, ']', $this->cur_pos ) ? strpos( $txt, ']', $this->cur_pos ) : $this->cur_pos + 1;
									continue;
								}
							
								$end_pos		= stripos( $txt, '&#39;', $this->cur_pos + $open_length + 1 ) ? stripos( $txt, '&#39;', $this->cur_pos + $open_length + 1 ) : strpos( $txt, ']', $this->cur_pos + $open_length + 1 );
								$cur_content	= substr( $txt, $this->cur_pos + $open_length + 5, ($end_pos - ($this->cur_pos + $open_length + 5 )) );
								$new_content	= str_replace( '[', '%5B', str_replace( ']', '%5D', $cur_content ) );
								$txt			= substr_replace( $txt, $new_content, $this->cur_pos + $open_length + 5, ($end_pos - ($this->cur_pos + $open_length + 5 ) ) );
							}
							
							//-----------------------------------------
							// Need this because HTML on converts the 
							// entities back to quote/apos
							//-----------------------------------------
							
							else if( substr( $txt, $this->cur_pos + $open_length, 1 ) == '"' )
							{
								/* Skip the bbocde if there is mroe than 2 quotes. Bug #21161 */
								if( strlen( substr( $txt, $this->cur_pos, stripos( $txt, ']', $this->cur_pos ) - $this->cur_pos ) ) < 1 OR ( substr_count( $txt, '"', $this->cur_pos, strlen( substr( $txt, $this->cur_pos, stripos( $txt, ']', $this->cur_pos ) - $this->cur_pos ) ) ) ) > 2 )
								{
									$this->cur_pos = strpos( $txt, ']', $this->cur_pos ) ? strpos( $txt, ']', $this->cur_pos ) : $this->cur_pos + 1;
									continue;
								}
								
								$end_pos		= stripos( $txt, '"', $this->cur_pos + $open_length + 1 ) ? stripos( $txt, '"', $this->cur_pos + $open_length + 1 ) : strpos( $txt, ']', $this->cur_pos + $open_length + 1 );
								$cur_content	= substr( $txt, $this->cur_pos + $open_length + 1, ($end_pos - ($this->cur_pos + $open_length + 1 ) ) );
								$new_content	= str_replace( '[', '%5B', str_replace( ']', '%5D', $cur_content ) );
								$txt			= substr_replace( $txt, $new_content, $this->cur_pos + $open_length + 1, ($end_pos - ($this->cur_pos + $open_length + 1 ) ) );
							}
							else if( substr( $txt, $this->cur_pos + $open_length, 1 ) == "'" )
							{ 
								/* Skip the bbocde if there is mroe than 2 quotes. Bug #21161 */
								if( strlen( substr( $txt, $this->cur_pos, stripos( $txt, ']', $this->cur_pos ) - $this->cur_pos ) ) < 1 OR ( substr_count( $txt, "'", $this->cur_pos, strlen( substr( $txt, $this->cur_pos, stripos( $txt, ']', $this->cur_pos ) - $this->cur_pos ) ) ) ) > 2 )
								{
									$this->cur_pos = strpos( $txt, ']', $this->cur_pos ) ? strpos( $txt, ']', $this->cur_pos ) : $this->cur_pos + 1;
									continue;
								}
								
								$end_pos		= stripos( $txt, "'", $this->cur_pos + $open_length + 1 ) ? stripos( $txt, "'", $this->cur_pos + $open_length + 1 ) : strpos( $txt, ']', $this->cur_pos + $open_length + 1 );
								$cur_content	= substr( $txt, $this->cur_pos + $open_length + 1, ($end_pos - ($this->cur_pos + $open_length + 1 )) );
								$new_content	= str_replace( '[', '%5B', str_replace( ']', '%5D', $cur_content ) );
								$txt			= substr_replace( $txt, $new_content, $this->cur_pos + $open_length + 1, ($end_pos - ($this->cur_pos + $open_length + 1 ) ) );
							}

							$_option		= substr( $txt, $this->cur_pos + $open_length, (strpos( $txt, ']', $this->cur_pos ) - ($this->cur_pos + $open_length)) );
						}
						
						//-----------------------------------------
						// If not, [u] != [url] (for example)
						//-----------------------------------------
				
						else if( (strpos( $txt, ']', $this->cur_pos ) - ( $this->cur_pos + $open_length )) !== 0 )
						{
							$this->cur_pos = strpos( $txt, ']', $this->cur_pos ) ? strpos( $txt, ']', $this->cur_pos ) : $this->cur_pos + 1;
							continue;
						}
					}
					
					//-----------------------------------------
					// No?  Then just grab it
					//-----------------------------------------
					
					else
					{
						$open_length	+= 1;
						$_option		= substr( $txt, $this->cur_pos + $open_length, (strpos( $txt, ']', $this->cur_pos ) - ($this->cur_pos + $open_length)) );
					}
				}
				
				//-----------------------------------------
				// [img] != [i] (for example)
				//-----------------------------------------
				
				else if( (strpos( $txt, ']', $this->cur_pos ) - ( $this->cur_pos + $open_length )) !== 0 )
				{
					$this->cur_pos = strpos( $txt, ']', $this->cur_pos ) ? strpos( $txt, ']', $this->cur_pos ) : $this->cur_pos + 1;
					continue;
				}

				//-----------------------------------------
				// Grab the new position to jump to
				//-----------------------------------------
				
				$new_pos = strpos( $txt, ']', $this->cur_pos ) ? strpos( $txt, ']', $this->cur_pos ) : $this->cur_pos + 1;
			
				//-----------------------------------------
				// No closing tag
				//-----------------------------------------
				
				if( stripos( $txt, $close_tag, $new_pos ) === false )
				{
					break;
				}
						
				//-----------------------------------------
				// Grab the content
				//-----------------------------------------

				$_content	= substr( $txt, ($this->cur_pos + $open_length + strlen($_option) + 1), ( ( stripos( $txt, $close_tag, $this->cur_pos + $open_length + strlen($_option) + 1 ) ) - ($this->cur_pos + $open_length + strlen($_option) + 1)) );
				
				//-----------------------------------------
				// If this is a single tag, that's it
				// @link: http://forums.invisionpower.com/index.php?autocom=tracker&showissue=11909 
				//-----------------------------------------

				if( $_content OR $_content === '0' )
				{
					if( $this->_buildOutput( $_content, $_option ? $_option : $_content ) )
					{
						$txt		= substr_replace( $txt, $this->_buildOutput( $_content, $_option ? $_option : $_content ), $this->cur_pos, (stripos( $txt, $close_tag, $this->cur_pos ) + strlen($close_tag) - $this->cur_pos) );
					}
				}
				else
				{
					$txt		= substr_replace( $txt, '', $this->cur_pos, (stripos( $txt, $close_tag, $this->cur_pos ) + strlen($close_tag) - $this->cur_pos) );
				}
				
				//-----------------------------------------
				// And reset current position to end of open tag
				//-----------------------------------------
				
				$this->cur_pos = stripos( $txt, $open_tag ) ? stripos( $txt, $open_tag ) : $this->cur_pos + 1; //$new_pos;

				if( $this->cur_pos > strlen($txt) )
				{
					//-----------------------------------------
					// Need to reset for next "tag"
					//-----------------------------------------
					
					$this->cur_pos	= 0;
					break;
				}
			}
		}

		return $txt;
	}
	
	/**
	 * Build the actual output to show
	 *
	 * @access	protected
	 * @param	array		$content	Display text
	 * @param	string		$option		URL to link to
	 * @return	string					Content to replace bbcode with
	 */
	protected function _buildOutput( $content, $option )
	{
		// This is problematic if url contains a ' or "
		// $option = str_replace( array( '"', "'", '&#39;', '&quot;' ), '', $option );

		//-----------------------------------------
		// Remove " and ' from beginning + end
		//-----------------------------------------
		
		if( substr( $option, 0, 5 ) == '&#39;' )
		{
			$option = substr( $option, 5 );
		}
		else if( substr( $option, 0, 6 ) == '&quot;' )
		{
			$option = substr( $option, 6 );
		}
		else if( substr( $option, 0, 1 ) == "'" )
		{
			$option = substr( $option, 1 );
		} 
		else if( substr( $option, 0, 1 ) == '"' )
		{
			$option = substr( $option, 1 );
		}
		
		if( substr( $option, -5 ) == '&#39;' )
		{
			$option = substr( $option, 0, -5 );
		}
		else if( substr( $option, -6 ) == '&quot;' )
		{
			$option = substr( $option, 0, -6 );
		}
		else if( substr( $option, -1 ) == "'" )
		{
			$option = substr( $option, 0, -1 );
		} 
		else if( substr( $option, -1 ) == '"' )
		{
			$option = substr( $option, 0, -1 );
		}

		//-----------------------------------------
		// Some security checking
		//-----------------------------------------
		
		if ( IPSText::xssCheckUrl( $option ) !== TRUE )
		{
			return $content;
		}
		
		/* Check for mangled or embedded URLs */
		if ( stristr( $option, '[attachment' )  OR stristr( $option, '[quote' )  OR stristr( $option, '[url' )  OR stristr( $option, '[/url' ) OR stristr( $content, '[url' )  OR stristr( $content, '[/url' ) )
		{
			return $content;
		}

		//-----------------------------------------
		// Fix quotes in urls
		//-----------------------------------------

		$option	= str_replace( array( '&#39;', "'" ), '%27', $option );
		$option	= str_replace( array( '&quot;', '"' ), '%22', $option );

		foreach( $this->cache->getCache('bbcode') as $bbcode )
		{
			$_tags = $this->_retrieveTags();
			
			foreach( $_tags as $tag )
			{
				if( strpos( $option, '[' . $tag ) !== false )
				{
					return $content;
				}
			}
		}

		//-----------------------------------------
		// URL filtering?
		//-----------------------------------------
		
		if ( $this->settings['ipb_use_url_filter'] )
		{
			$list_type = $this->settings['ipb_url_filter_option'] == "black" ? "blacklist" : "whitelist";

			if( $this->settings['ipb_url_' . $list_type ] )
			{
				$list_values = array();
				$list_values = explode( "\n", str_replace( "\r", "", $this->settings['ipb_url_' . $list_type ] ) );
				
				if ( $list_type == "whitelist" )
				{
					$list_values[]	= "http://{$_SERVER['HTTP_HOST']}/*";
				}

				if ( count( $list_values ) )
				{
					$good_url = 0;
					
					foreach( $list_values as $my_url )
					{
						if( !trim($my_url) )
						{
							continue;
						}

						$my_url = preg_quote( $my_url, '/' );
						$my_url = str_replace( '\*', "(.*?)", $my_url );

						if ( $list_type == "blacklist" )
						{
							if( preg_match( '/' . $my_url . '/i', $option ) )
							{
								$this->warning = 'domain_not_allowed';
								return $content;
							}
						}
						else
						{
							if ( preg_match( '/' . $my_url . '/i', $option ) )
							{
								$good_url = 1;
							}
						}
					}

					if ( ! $good_url AND $list_type == "whitelist" )
					{
						$this->warning = 'domain_not_allowed';
						return $content;
					}				
				}
			}
		}
		
		//-----------------------------------------
		// Let's remove any nested links..
		//-----------------------------------------
		
		$content = preg_replace( '/<a href=\'(.+?)\'(.*?)>(.+?)<\/a>/is', "\\3", $content );
		
		//-----------------------------------------
		// Need to "truncate" the "content" to ~35
		// EDIT: but only if it's the same as content
		//-----------------------------------------
		
		/* Changes here @link http://community.invisionpower.com/tracker/issue-36082-long-links-on-mobile-extend-width/ */
		if( ( empty( $this->settings['__noTruncateUrl'] ) ) AND IPSText::mbstrlen($content) > 38 AND ( substr( $content, 0, 7) == 'http://' OR substr( $content, 0, 8 ) == 'https://' ) )
		{
			$content = htmlspecialchars( IPSText::mbsubstr( html_entity_decode( urldecode( $content ) ), 0, 20 ) ) . '...' . htmlspecialchars( IPSText::mbsubstr( html_entity_decode( urldecode( $content ) ), -15 ) );
		}
		
		//-----------------------------------------
		// Adding rel='nofollow'?
		//-----------------------------------------
		
		$rels	= array();
		$rel	= '';
		$_title = '';
		
		/* Fetch actual host for better matching */
		$data = @parse_url( $option );
		
		if ( $this->settings['posts_add_nofollow'] )
		{
			if ( ! stristr( $data['host'], $_SERVER['HTTP_HOST'] ) )
			{
				$rels[]	= "nofollow";
			}
		}
		
		if ( $this->settings['links_external'] )
		{ 
			if ( ! stristr( $data['host'], $_SERVER['HTTP_HOST'] ) )
			{ 
				/* Look a little closer */
				$rels[]	= "external";
				$_title = $this->lang->words['bbc_external_link'];
			}
		}
	
		if ( count($rels) )
		{
			$rel = " rel='" . implode( ' ', $rels ) . "'";
		}

		return "<a href='{$option}' class='bbc_url' title='{$_title}'{$rel}>{$content}</a>";
	}
}