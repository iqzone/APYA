<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Global Search
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */ 

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_format
{
	/**
	 * Search templates to use
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $templates = array();
	
	/**
	 * Setup registry objects
	 *
	 * @access	public
	 * @param	object	ipsRegistry $registry
	 * @return	@e void
	 */
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
		
		/* Set up default wrapper */
		$this->templates = array( 'group' => 'search', 'template' => 'searchResults' );
		
		// Need to setup this manually here, won't work in the template..
		$this->registry->templateStriping['searchResults'] = array( FALSE, "row1", "row2" );
	}
	
	/**
	 * Fetch templates to use
	 *
	 * @access	public
	 * @return	array
	 */
	public function fetchTemplates()
	{
		return $this->templates;
	}
	
	/**
	 * Wrapper for processResults() when called from 'Content I follow' view
	 *
	 * @param	array 	$ids			Ids
	 * @param	array	$followData		Retrieve the follow meta data
	 * @return array
	 */
	public function processFollowedResults( $ids, $followData=array() )
	{
		/* Per app class handles this */
		throw new Exception("NO_FORMAT_AVAILABLE");
	}
	
	/**
	 * Formats / grabs extra data for results
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @param	array 	$ids			Ids
	 * @return array
	 */
	public function processResults( $ids )
	{
		/* Per app class handles this */
		throw new Exception("NO_FORMAT_AVAILABLE");
	}
	
	/**
	 * Reassigns fields in a generic way for results output
	 * DEFAULT METHOD: Just return data. This should be overriden in the apps
	 *
	 * @param  array  $r
	 * @return array
	 */
	public function genericizeResults( $r )
	{
		return $r;
	}
	
	/**
	 * Parse common search results
	 *
	 * @access	private
	 * @param	array 	$r				Search result
	 * @return	array 	$html			Blocks of HTML
	 */
	public function parseAndFetchHtmlBlocks( $rows )
	{
		/* Forum stuff */
		$sub		 = false;
		$isVnc		 = false;
		$search_term = IPSSearchRegistry::get('in.clean_search_term');
		$results	 = array();
		
		/* loop and process */
		if( is_array($rows) AND count($rows) )
		{
			foreach( $rows as $id => $r )
			{
				/* If basic search, strip the content - process only if we have content though */
				if ( $r['content'] )
				{
					IPSText::getTextClass('bbcode')->parse_bbcode			= 0;
					IPSText::getTextClass('bbcode')->strip_quotes			= 1;
					IPSText::getTextClass('bbcode')->parsing_section		= 'topics';
					IPSText::getTextClass('bbcode')->parsing_mgroup			= $r['member_group_id'];
					IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $r['mgroup_others'];
			
					$r['content'] = strip_tags( IPSText::getTextClass( 'bbcode' )->stripAllTags( $r['content'] ) );
					$r['content'] = str_replace( array( '&lt;br&gt;', '&lt;br /&gt;' ), '', $r['content'] );
					$r['content'] = trim( str_replace( array( "\n\n\n", "\n\n" ), "\n", str_replace( "\r", '', $r['content'] ) ) );
			
					/* Highlight */
					$r['content'] = IPSText::searchHighlight( $this->searchTruncate( $r['content'], $search_term ), $search_term );
				}
				
				/* Format title */
				$r['content_title'] = IPSText::searchHighlight( $r['content_title'], $search_term );
				
				/* Format content */
				list( $html, $sub ) = $this->formatContent( $r );
				
				$results[ $id ] = array( 'html' => $html, 'app' => $r['app'], 'type' => $r['type'], 'sub' => $sub, '_followData' => !empty($r['_followData']) ? $r['_followData'] : array() );
			}
		}
				
		return $results;
	}
	
	/**
	 * Formats the forum search result for display
	 *
	 * @access	public
	 * @param	array   $search_row		Array of data
	 * @return	mixed	Formatted content, ready for display, or array containing a $sub section flag, and content
	 */
	public function formatContent( $data )
	{
		return array( $this->registry->output->getTemplate('search')->searchRowGenericFormat( $data ), 0 );
	}
	
	/**
	 * Function to trim the search result display around the the hit
	 *
	 * @access	private
	 * @param	string	$haystack	Full search result
	 * @param	string	$needle		The search term
	 * @return	string
	 */
	public function searchTruncate( $haystack, $needle )
	{
		/* Base on words */
		$haystack = explode( " ", $haystack );

		if( count( $haystack ) > 21 )
		{
			$_term_at = $this->searchInArray( $needle, $haystack );

			if( $_term_at - 11 > 0 )
			{
				$begin = array_splice( $haystack, 0, $_term_at - 11 );
				
				/* The term position will have changed now */
				$_term_at = $this->searchInArray( $needle, $haystack );
			}

			if( $_term_at + 11 < count( $haystack ) )
			{
				$end   = array_splice( $haystack, $_term_at + 11, count( $haystack ) );
			}
		}
		else
		{
			$begin = array();
			$end   = array();
		}

		$haystack = implode( " ", $haystack );
		
		if( is_array( $begin ) && count( $begin ) )
		{
			$haystack = '...' . $haystack;
		}
		
		if( is_array( $end ) && count( $end ) )
		{
			$haystack = $haystack . '...';
		}
		
		return $haystack;
	}
	
	/**
	 * Search array (array_search only finds exact instances)
	 *
	 * @access	protected
	 * @param	string		"Needle"
	 * @param	array 		Array of entries to search
	 * @return	mixed		Key of array, or false on failure
	 */
	public function searchInArray( $needle, $haystack )
	{
		if( !is_array( $haystack ) OR !count($haystack) OR ! $needle )
		{
			return false;
		}
		
		foreach( $haystack as $k => $v )
		{
			if( $v AND strpos( $v, $needle ) !== false )
			{
				return $k;
			}
		}
		
		return false;
	}
	
}
