<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Global Search
 * Last Updated: $Date: 2012-05-25 12:09:04 -0400 (Fri, 25 May 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10796 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_search_search extends ipsCommand
{
	/**
	 * Generated output
	 *
	 * @var		string
	 */		
	protected $output			= '';
	
	/**
	 * Page Title
	 *
	 * @var		string
	 */		
	protected $title			= '';
	
	/**
	 * Object to handle searches
	 *
	 * @var		string
	 */	
	protected $search_plugin	= '';
	
	/**
	 * Topics array
	 *
	 * @var		array
	 */
	protected	$_topicArray	= array();
	protected $_removedTerms  = array();
	
	/**
	 * Search controller
	 *
	 * @var		obj
	 */		
	protected $searchController;
	protected $_session;

	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_search' ), 'core' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_forums', 'public_topic' ), 'forums' );
		
		/* Reset engine type */
		$this->settings['search_method'] = ( $this->settings['search_method'] == 'traditional' ) ? 'sql' : $this->settings['search_method'];
		
		/* Force SQL for view new content? */
		if ( ! empty( $this->settings['force_sql_vnc'] ) && $this->request['do'] == 'viewNewContent' )
		{
			$this->settings['search_method'] = 'sql';
		}
		
		/* Special consideration for contextual search */
		if ( isset( $this->request['search_app'] ) AND strstr( $this->request['search_app'], ':' ) )
		{
			list( $app, $type, $id ) = explode( ':', $this->request['search_app'] );
			
			$this->request['search_app'] = $app;
			$this->request['cType']      = $type;
			$this->request['cId']		 = $id;
		}
		else
		{
			/* Force forums as default search */
			$this->request['search_in']      = ( $this->request['search_in'] AND IPSLib::appIsSearchable( $this->request['search_in'], 'search' ) ) ? $this->request['search_in'] : 'forums';
			$this->request['search_app']     = $this->request['search_app'] ? $this->request['search_app'] : $this->request['search_in'];
		}
		
		/* Check Access */
		$this->_canSearch();		
		
		/* Start session - needs to be called before the controller is initiated */
		$this->_startSession();
		
		/* Load the controller */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH. 'sources/classes/search/controller.php', 'IPSSearch' );
		
		/* Sanitzie */
		if ( ! is_string( $this->request['search_app'] ) )
		{
			$this->request['search_app'] = 'forums';
		}
		
		try
		{
			$this->searchController = new $classToLoad( $registry, $this->settings['search_method'], $this->request['search_app'] );
		}
		catch( Exception $error )
		{
			$msg = $error->getMessage();

			/* Start session */
			$this->_endSession();
		
			switch( $msg )
			{
				case 'NO_SUCH_ENGINE':
				case 'NO_SUCH_APP':
				case 'NO_SUCH_APP_ENGINE':
					$this->registry->output->showError( sprintf( $this->lang->words['no_search_app'], ipsRegistry::$applications[ $this->request['search_app'] ]['app_title'] ), 10145.1 );
				break;
			}
		}
		
		/* Log type */
		IPSDebug::addMessage( "Search type: " . $this->settings['search_method'] );
		
		/* Set up some defaults */
		IPSSearchRegistry::set('opt.noPostPreview', false );
		IPSSearchRegistry::set('in.start', intval( $this->request['st'] ) );
		IPSSearchRegistry::set('opt.search_per_page', intval( $this->settings['search_per_page'] ) ? intval( $this->settings['search_per_page'] ) : 25 );
		
		$this->settings['search_ucontent_days']	= ( $this->settings['search_ucontent_days'] ) ? $this->settings['search_ucontent_days'] : 365;
		
		/* Contextuals */
		if ( isset( $this->request['cType'] ) )
		{
			IPSSearchRegistry::set('contextual.type', $this->request['cType'] );
			IPSSearchRegistry::set('contextual.id'  , $this->request['cId'] );
		}
			
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'user_activity':
				$this->viewUserContent();
			break;
		
			case 'new_posts':
			case 'viewNewContent':
			case 'active':
				$this->viewNewContent();
			break;
			
			case 'search':
			case 'quick_search':
				$this->searchResults();
			break;
			
			case 'followed':
				$this->viewFollowedContent();
			break;
			
			case 'manageFollowed':
				$this->updateFollowedContent();
			break;
			
			default:
			case 'search_form':	
				$this->searchAdvancedForm();
			break;
		}
		
		/* Start session */
		$this->_endSession();
		
		/* If we have any HTML to print, do so... */
		if ( $this->request['do'] == 'search' && ! empty( $this->request['search_tags'] ) )
		{
			$this->registry->output->setTitle( IPSText::urldecode_furlSafe( $this->request['search_tags'] ) . ' - ' . $this->lang->words['st_tags'] . ' - ' . IPSLib::getAppTitle( $this->request['search_app'] ) . ' - ' . ipsRegistry::$settings['board_name'] );
			
			/* Add canonical tag */
			$extra = ( $this->request['st'] ) ? '&amp;st=' . $this->request['st'] : '';
			$this->registry->output->addCanonicalTag( 'app=core&amp;module=search&amp;do=search&amp;search_tags=' . IPSText::urlencode_furlSafe( $this->request['search_tags'] ) . '&amp;search_app=' . $this->request['search_app']. $extra, $this->request['search_tags'], 'tags' );
		}
		else
		{
			$this->registry->output->setTitle( $this->title . ' - ' . ipsRegistry::$settings['board_name'] );
		}
		
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Moderate your list of liked content
	 *
	 * @author	bfarber
	 * @return	string	HTML to display
	 */
	public function updateFollowedContent()
	{
		IPSSearchRegistry::set( 'in.search_app', $this->request['search_app'] );
		
		//-----------------------------------------
		// Get the likes we selected
		//-----------------------------------------
		
		$_likes	= array();
		
		if( is_array($this->request['likes']) AND count($this->request['likes']) )
		{
			foreach( $this->request['likes'] as $_like )
			{
				$_thisLike	= explode( '-', $_like );
				$_likes[]	= array(
									'app'	=> $_thisLike[0],
									'area'	=> $_thisLike[1],
									'id'	=> $_thisLike[2],
									);
			}
		}
		
		//-----------------------------------------
		// Got any?
		//-----------------------------------------
		
		if( !count($_likes) OR !is_array($_likes) )
		{
			return $this->viewFollowedContent( $this->lang->words['no_likes_for_del'] );
		}
		
		//-----------------------------------------
		// Get like helper class
		//-----------------------------------------
		
		$bootstraps		= array();
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		
		//-----------------------------------------
		// Loop over and moderate
		//-----------------------------------------
		
		foreach( $_likes as $_like )
		{
			$_bootstrap		= classes_like::bootstrap( $_like['app'], $_like['area'] );
			$_likeKey		= classes_like_registry::getKey( $_like['id'], $this->memberData['member_id'] );
			$_frequencies	= $_bootstrap->allowedFrequencies();

			//-----------------------------------------
			// What action to take?
			//-----------------------------------------
			
			switch( $this->request['modaction'] )
			{
				case 'delete':
					$_bootstrap->remove( $_like['id'], $this->memberData['member_id'] );
				break;

				case 'change-donotify':
					$this->DB->update( 'core_like', array( 'like_notify_do' => 1, 'like_notify_freq' => 'immediate' ), "like_id='" . addslashes($_likeKey) . "'" );
				break;

				case 'change-donotnotify':
					$this->DB->update( 'core_like', array( 'like_notify_do' => 0 ), "like_id='" . addslashes($_likeKey) . "'" );
				break;

				case 'change-immediate':
					if( in_array( 'immediate', $_frequencies ) )
					{
						$this->DB->update( 'core_like', array( 'like_notify_do' => 1, 'like_notify_freq' => 'immediate' ), "like_id='" . addslashes($_likeKey) . "'" );
					}
				break;

				case 'change-offline':
					if( in_array( 'offline', $_frequencies ) )
					{
						$this->DB->update( 'core_like', array( 'like_notify_do' => 1, 'like_notify_freq' => 'offline' ), "like_id='" . addslashes($_likeKey) . "'" );
					}
				break;
				
				case 'change-daily':
					if( in_array( 'daily', $_frequencies ) )
					{
						$this->DB->update( 'core_like', array( 'like_notify_do' => 1, 'like_notify_freq' => 'daily' ), "like_id='" . addslashes($_likeKey) . "'" );
					}
				break;
				
				case 'change-weekly':
					if( in_array( 'weekly', $_frequencies ) )
					{
						$this->DB->update( 'core_like', array( 'like_notify_do' => 1, 'like_notify_freq' => 'weekly' ), "like_id='" . addslashes($_likeKey) . "'" );
					}
				break;

				case 'change-anon':
					$this->DB->update( 'core_like', array( 'like_is_anon' => 1 ), "like_id='" . addslashes($_likeKey) . "'" );
				break;

				case 'change-noanon':
					$this->DB->update( 'core_like', array( 'like_is_anon' => 0 ), "like_id='" . addslashes($_likeKey) . "'" );
				break;
			}
		}

		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."app=core&amp;module=search&amp;do=followed&amp;search_app={$this->request['search_app']}&amp;contentType={$this->request['contentType']}&amp;confirm=1" );
	}

	/**
	 * View content you are following
	 *
	 * @author	Brandon Farber
	 * @param	string	$error	Error message
	 * @return	@e void
	 */
	public function viewFollowedContent( $error='' )
	{
		IPSSearchRegistry::set( 'in.search_app', $this->request['search_app'] );
		IPSSearchRegistry::set( 'opt.searchType', 'titles' );
		IPSSearchRegistry::set( 'opt.noPostPreview', true );
		
		$results	= array();
		$formatted	= array();
		$count		= 0;

		//-----------------------------------------
		// Determine content type
		//-----------------------------------------
		
		$contentTypes	= IPSSearchRegistry::get('config.followContentTypes');

		//-----------------------------------------
		// Verify likes are available
		//-----------------------------------------
		
		if( count( IPSLib::getEnabledApplications('like') ) AND count( $contentTypes ) )
		{
			//-----------------------------------------
			// What content type?
			//-----------------------------------------
			
			$_type	= '';
			
			if( $this->request['contentType'] AND in_array( $this->request['contentType'], $contentTypes ) )
			{
				$_type	= $this->request['contentType'];
			}
			else
			{
				$_type	= $contentTypes[0];
			}
			
			$this->request['contentType']	= $_type;
			
			IPSSearchRegistry::set( 'in.followContentType', $this->request['contentType'] );

			/* Fetch like class */
			try
			{
				require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
				$this->_like = classes_like::bootstrap( IPSSearchRegistry::get('in.search_app'), $_type );
			}
			catch ( Exception $e)
			{
				/* Fail safe... */
				$this->registry->output->addNavigation( $this->lang->words['followed_ct_title'], '' );
				$this->title	= $this->lang->words['followed_ct_title'];
				$this->output	.= $this->registry->output->getTemplate( 'search' )->followedContentView( array(), '', 0, $error, $contentTypes );
			}
								
			/* Do we have some custom join and sort data? */
			if ( method_exists( $this->_like, 'getSearchJoinAndSortBy' ) )
			{
				$custom = $this->_like->getSearchJoinAndSortBy();
				
				$countQ = array( 'select' => 'COUNT(*) as er',
											  		   'from'   => array( 'core_like' => 'l' ),
											  		   'where'  => 'like_member_id=' . $this->memberData['member_id'] . " AND like_visible=1 AND like_app='" . IPSSearchRegistry::get('in.search_app') . "' AND like_area='" . $_type . "'" );
				if ( $custom['from'] )
				{
					$countQ['add_join'][] = array( 'from' => $custom['from'], 'where' => $custom['where'] );
				}
				if ( $custom['order'] )
				{
					$countQ['order'] = $custom['order'] . ' DESC';
				}
				if ( $custom['extraWhere'] )
				{
					$countQ['where'] .= ' AND ' . $custom['extraWhere'];
				}
				$count	= $this->DB->buildAndFetch( $countQ );
				$count	= $count['er'];
												
				$q = array( 'select'   => 'l.*',
										 'from'     => array('core_like' => 'l'),
										 'where'    => 'l.like_member_id=' . $this->memberData['member_id'] . " AND l.like_visible=1 AND l.like_app='" . IPSSearchRegistry::get('in.search_app') . "' AND l.like_area='" . $_type . "'",
										 'limit'    => array( IPSSearchRegistry::get('in.start'), IPSSearchRegistry::get('opt.search_per_page') ),
										 'order'  => 'like_added DESC'
										  );
				
				if ( $custom['from'] )
				{
					$q['add_join'][] = array( 'from' => $custom['from'], 'where' => $custom['where'] );
				}
				if ( $custom['order'] )
				{
					$q['order'] = $custom['order'] . ' DESC';
				}
				if ( $custom['extraWhere'] )
				{
					$q['where'] .= ' AND ' . $custom['extraWhere'];
				}
				
				$this->DB->build( $q );
			}
			else
			{
				$count	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as er',
											  		   'from'   => 'core_like',
											  		   'where'  => 'like_member_id=' . $this->memberData['member_id'] . " AND like_visible=1 AND like_app='" . IPSSearchRegistry::get('in.search_app') . "' AND like_area='" . $_type . "'" ) );
				$count	= $count['er'];
			
				$this->DB->build( array( 'select' => '*',
										 'from'   => 'core_like',
										 'where'  => 'like_member_id=' . $this->memberData['member_id'] . " AND like_visible=1 AND like_app='" . IPSSearchRegistry::get('in.search_app') . "' AND like_area='" . $_type . "'",
										 'limit'  => array( IPSSearchRegistry::get('in.start'), IPSSearchRegistry::get('opt.search_per_page') ),
										 'order'  => 'like_added DESC' ) );
			}
			
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				$results[]	= $r['like_rel_id'];

				$formatted[ $r['like_id'] ]	= $r;
			}
			
			/* Process */
			$results	= $this->searchController->fetchFollowedContentOutput( $results, $formatted );
				
			$pages = $this->registry->getClass('output')->generatePagination( array(  'totalItems'			=> $count,
															   					 	  'itemsPerPage'		=> IPSSearchRegistry::get('opt.search_per_page'),
																					  'currentStartValue'	=> IPSSearchRegistry::get('in.start'),
																					  'baseUrl'				=> "app=core&amp;module=search&amp;do=followed&amp;search_app=" . IPSSearchRegistry::get('in.search_app')  . '&amp;sid=' . $this->request['_sid'] . "&amp;contentType=" . $this->request['contentType'] ) );
		}
		else
		{
			$count		= 0;
			$results	= array();
		}

		/* Output */
		$this->registry->output->addNavigation( $this->lang->words['followed_ct_title'], '' );
		$this->title	= $this->lang->words['followed_ct_title'];
		$this->output	.= $this->registry->output->getTemplate( 'search' )->followedContentView( $results, $pages, $count, $error, $contentTypes );
	}
	
	/**
	 * Builds the advanced search form
	 *
	 * @param	string	Message
	 * @return	@e void
	 */
	public function searchAdvancedForm( $msg='', $removed_search_terms=array() )
	{
		/* Set up data */
		IPSSearchRegistry::set('view.search_form', true );
		
		/* Get any application specific filters */
		$appHtml   = $this->searchController->getHtml();
		$isBoolean = $this->searchController->isBoolean();
		$tagSupport = $this->searchController->getTagSupport();
		
		/* Output */
		$this->title   = $this->lang->words['search_form'];
		$this->registry->output->addNavigation( $this->lang->words['search_form'], '' );
		$this->output .= $this->registry->output->getTemplate( 'search' )->searchAdvancedForm( $appHtml, $msg, $this->request['search_app'], $removed_search_terms, $isBoolean, $tagSupport );
	}
	
	/**
	 * Processes a search request
	 *
	 * @return	@e void
	 */
	public function searchResults()
	{
		/* Search Term */
		$_st          = $this->searchController->formatSearchTerm( trim( $this->request['search_term'] ) );
		$search_term  = $_st['search_term'];
		$removedTerms = $_st['removed'];
		
		/* Set up some defaults */
		$this->settings['max_search_word'] = $this->settings['max_search_word'] ? $this->settings['max_search_word'] : 300;
		
		/* Did we come in off a post request? */
		if ( $this->request['request_method'] == 'post' )
		{
			/* Set a no-expires header */
			$this->registry->getClass('output')->setCacheExpirationSeconds( 30 * 60 );
		}
		
		/* App specific */
		if ( isset( $this->request['search_sort_by_' . $this->request['search_app'] ] ) )
		{
			$this->request['search_sort_by']    = ( $_POST[ 'search_sort_by_' . $this->request['search_app'] ] ) ? $_POST[ 'search_sort_by_' . $this->request['search_app'] ] : $this->request['search_sort_by_' . $this->request['search_app'] ];
			$this->request['search_sort_order'] = ( $_POST[ 'search_sort_order_' . $this->request['search_app'] ] ) ? $_POST[ 'search_sort_order_' . $this->request['search_app'] ] : $this->request['search_sort_order_' . $this->request['search_app'] ];
		}
		
		/* Populate the registry */
		IPSSearchRegistry::set('in.search_app'		 , $this->request['search_app'] );
		IPSSearchRegistry::set('in.raw_search_term'  , trim( $this->request['search_term'] ) );
		IPSSearchRegistry::set('in.clean_search_term', $search_term );
		IPSSearchRegistry::set('in.raw_search_tags'  , str_replace( '&amp;', '&', trim( IPSText::parseCleanValue( IPSText::urldecode_furlSafe( $_REQUEST['search_tags'] ) ) ) ) );
		IPSSearchRegistry::set('in.search_higlight'  , str_replace( '.', '', $this->request['search_term'] ) );
		IPSSearchRegistry::set('in.search_date_end'  , ( $this->request['search_date_start'] && $this->request['search_date_end'] )  ? $this->request['search_date_end'] : 'now' );
		IPSSearchRegistry::set('in.search_date_start', ( $this->request['search_date_start']  )  ? $this->request['search_date_start'] : '' );
		IPSSearchRegistry::set('in.search_author'    , !empty( $this->request['search_author'] ) ? $this->request['search_author'] : '' );
		
		/* Set sort filters */
		$this->_setSortFilters();
		
		/* These can be overridden in the actual engine scripts */
	//	IPSSearchRegistry::set('set.hardLimit'        , 0 );
		IPSSearchRegistry::set('set.resultsCutToLimit', false );
		IPSSearchRegistry::set('set.resultsAsForum'   , false );
		
		/* Are we option to show titles only / search in titles only */
		IPSSearchRegistry::set('opt.searchType', ( !empty( $this->request['search_content'] ) AND in_array( $this->request['search_content'], array( 'both', 'titles', 'content' ) ) ) ? $this->request['search_content'] : 'both' );
		
		/* Time check */
		if ( IPSSearchRegistry::get('in.search_date_start') AND strtotime( IPSSearchRegistry::get('in.search_date_start') ) > time() )
		{
			IPSSearchRegistry::set('in.search_date_start', 'now' );
		}
		
		if ( IPSSearchRegistry::get('in.search_date_end') AND strtotime( IPSSearchRegistry::get('in.search_date_end') ) > time() )
		{
			IPSSearchRegistry::set('in.search_date_end', 'now' );
		}
		
		/* Do some date checking */
		if( IPSSearchRegistry::get('in.search_date_end') AND IPSSearchRegistry::get('in.search_date_start') AND strtotime( IPSSearchRegistry::get('in.search_date_start') ) > strtotime( IPSSearchRegistry::get('in.search_date_end') ) )
		{
			$this->searchAdvancedForm( $this->lang->words['search_invalid_date_range'] );
			return;	
		}
		
		/**
		 * Lower limit
		 */
		if ( $this->settings['min_search_word'] && ! IPSSearchRegistry::get('in.search_author') && ! IPSSearchRegistry::get('in.raw_search_tags') )
		{
			if ( $this->settings['search_method'] == 'sphinx' && substr_count( $search_term, '"' ) == 2 )
			{
				$_ok = true;
			}
			else
			{
				$_words	= explode( ' ', preg_replace( "#\"(.*?)\"#", '', $search_term ) );
				$_ok	= $search_term ? true : false;
	
				foreach( $_words as $_word )
				{
					$_word	= preg_replace( '#^\+(.+?)$#', "\\1", $_word );
	
					if ( ! $_word OR $_word == '|' )
					{
						continue;
					}
	
					if( strlen( $_word ) < $this->settings['min_search_word'] )
					{
						$_ok	= false;
						break;
					}
				}
			}
			
			if( ! $_ok )
			{
				$this->searchAdvancedForm( sprintf( $this->lang->words['search_term_short'], $this->settings['min_search_word'] ), $removedTerms );
				return;
			}
		}	
		
		/**
		 * Ok this is an upper limit.
		 * If you needed to change this, you could do so via conf_global.php by adding:
		 * $INFO['max_search_word'] = #####;
		 */
		if ( $this->settings['max_search_word'] && strlen( IPSSearchRegistry::get('in.raw_search_term') ) > $this->settings['max_search_word'] )
		{
			$this->searchAdvancedForm( sprintf( $this->lang->words['search_term_long'], $this->settings['max_search_word'] ) );
			return;
		}
		
		/* Search Flood Check */
		if( $this->memberData['g_search_flood'] )
		{
			/* Check for a cookie */
			$last_search = IPSCookie::get( 'sfc' );
			$last_term	= str_replace( "&quot;", '"', IPSCookie::get( 'sfct' ) );
			$last_term	= str_replace( "&amp;", '&',  $last_term );			
			
			/* If we have a last search time, check it */
			if( $last_search && $last_term )
			{
				if( ( time() - $last_search ) <= $this->memberData['g_search_flood'] && $last_term != IPSSearchRegistry::get('in.raw_search_term') )
				{
					$this->searchAdvancedForm( sprintf( $this->lang->words['xml_flood'], $this->memberData['g_search_flood'] - ( time() - $last_search ) ) );
					return;					
				}
				else
				{
					/* Reset the cookie */
					IPSCookie::set( 'sfc', time() );
					IPSCookie::set( 'sfct', urlencode( IPSSearchRegistry::get('in.raw_search_term') ) );
				}
			}
			/* Set the cookie */
			else
			{
				IPSCookie::set( 'sfc', time() );
				IPSCookie::set( 'sfct', urlencode( IPSSearchRegistry::get('in.raw_search_term') ) );
			}
		}
		
		/* Clean search term for results view */
		$_search_term = trim( preg_replace( '#(^|\s)(\+|\-|\||\~)#', " ", $search_term ) );
		
		/* Got tag search only but app doesn't support tags */
		if ( IPSSearchRegistry::get('in.raw_search_tags') && ! IPSSearchRegistry::get( 'config.can_searchTags' ) && ! IPSSearchRegistry::get('in.raw_search_term') )
		{
			$count   = 0;
			$results = array();
		}
		else if ( IPSLib::appIsSearchable( IPSSearchRegistry::get('in.search_app'), 'search' ) )
		{
			/* Perform the search */
			$this->searchController->search();
			
			/* Get count */
			$count = $this->searchController->getResultCount();
			
			/* Get results which will be array of IDs */
			$results = $this->searchController->getResultSet();
			
			/* Get templates to use */
			$template = $this->searchController->fetchTemplates();
						
			/* Fetch sort details */
			$sortDropDown = $this->searchController->fetchSortDropDown();

			/* Set default sort option */
			$_a	= IPSSearchRegistry::get('in.search_app');
			$_k	= IPSSearchRegistry::get( $_a . '.searchInKey') ? IPSSearchRegistry::get( $_a . '.searchInKey') : '';

			if( $_k AND !$this->request['search_app_filters'][ $_a ][ $_k ]['sortKey'] AND is_array($sortDropDown) AND count($sortDropDown) )
			{
				$this->request['search_app_filters'][ $_a ][ $_k ]['sortKey']	= key( $sortDropDown );
			}
			else if( !$_k AND !$this->request['search_app_filters'][ $_a ]['sortKey'] AND is_array($sortDropDown) AND count($sortDropDown) )
			{
				$this->request['search_app_filters'][ $_a ]['sortKey']	= key( $sortDropDown );
			}
			
			/* Fetch sort details */
			$sortIn       = $this->searchController->fetchSortIn();
			
			/* Build pagination */
			$links = $this->registry->output->generatePagination( array( 'totalItems'		=> $count,
																		 'itemsPerPage'		=> IPSSearchRegistry::get('opt.search_per_page'),
																		 'currentStartValue'=> IPSSearchRegistry::get('in.start'),
																		 'baseUrl'			=> $this->_buildURLString() . '&amp;search_app=' . IPSSearchRegistry::get('in.search_app') . '' )	);
	
			/* Showing */
			$showing = array( 'start' => IPSSearchRegistry::get('in.start') + 1, 'end' => ( IPSSearchRegistry::get('in.start') + IPSSearchRegistry::get('opt.search_per_page') ) > $count ? $count : IPSSearchRegistry::get('in.start') + IPSSearchRegistry::get('opt.search_per_page') );
						
			/* Parse result set */
			$results = $this->registry->output->getTemplate( $template['group'] )->$template['template']( $results, ( IPSSearchRegistry::get('opt.searchType') == 'titles' || IPSSearchRegistry::get('opt.noPostPreview') ) ? 1 : 0 );
			
			/* Check for sortIn */
			if( count( $sortIn ) && !$this->request['search_app_filters'][ $this->request['search_app'] ]['searchInKey'] )
			{
				$this->request['search_app_filters'][ $this->request['search_app'] ]['searchInKey'] = $sortIn[0][0];
			}
		}
		else
		{
			$count   = 0;
			$results = array();
		}
		
		/* Output */
		$this->title   = $this->lang->words['search_results'];
		$this->output .= $this->registry->output->getTemplate( 'search' )->searchResultsWrapper( $results, $sortDropDown, $sortIn, $links, $count, $showing, $_search_term, $this->_buildURLString(), $this->request['search_app'], $removedTerms, IPSSearchRegistry::get('set.hardLimit'), IPSSearchRegistry::get('set.resultsCutToLimit'), IPSSearchRegistry::get('in.raw_search_tags') );
	}
	
	/**
	 * Starts session
	 * Loads / creates a session based on activity
	 *
	 * @return
	 */
	protected function _startSession()
	{
		$session_id  = IPSText::md5Clean( $this->request['sid'] );
		$requestType = ( $this->request['request_method'] == 'post' ) ? 'post' : 'get';
		
		if ( $session_id )
		{
			/* We check on member id 'cos we can. Obviously guests will have a member ID of zero, but meh */
			$this->_session = $this->DB->buildAndFetch( array( 'select' => '*',
															   'from'   => 'search_sessions',
															   'where'  => 'session_id=\'' . $session_id . '\' AND session_member_id=' . $this->memberData['member_id'] ) );
		}
		
		/* Deflate */
		if ( $this->_session['session_id'] )
		{
			if ( $this->_session['session_data'] )
			{
				$this->_session['_session_data'] = unserialize( $this->_session['session_data'] );
				
				if ( isset( $this->_session['_session_data']['search_app_filters'] ) )
				{
					$this->request['search_app_filters'] = is_array( $this->request['search_app_filters'] ) ? array_merge( $this->_session['_session_data']['search_app_filters'], $this->request['search_app_filters'] ) : $this->_session['_session_data']['search_app_filters'];
				}
			}
			
			IPSDebug::addMessage( "Loaded search session: <pre>" . var_export( $this->_session['_session_data'], true ) . "</pre>" );
		}
		else
		{
			/* Create a session */
			$this->_session = array( 'session_id'        => md5( uniqid( microtime(), true ) ),
									 'session_created'   => time(),
									 'session_updated'   => time(),
									 'session_member_id' => $this->memberData['member_id'],
									 'session_data'      => serialize( array( 'search_app_filters' => $this->request['search_app_filters'] ) ) );
									 
			$this->DB->insert( 'search_sessions', $this->_session );
			
			$this->_session['_session_data']['search_app_filters'] = $this->request['search_app_filters'];
			
			IPSDebug::addMessage( "Created search session: <pre>" . var_export( $this->_session['_session_data'], true ) . "</pre>" );
		}
		
		/* Do we have POST infos? */
		if ( isset( $_POST['search_app_filters'] ) )
		{
			$this->_session['_session_data']['search_app_filters'] = ( is_array( $this->_session['_session_data']['search_app_filters'] ) ) ? IPSLib::arrayMergeRecursive( $this->_session['_session_data']['search_app_filters'], $_POST['search_app_filters'] ) : $_POST['search_app_filters'];
			$this->request['search_app_filters']                   = $this->_session['_session_data']['search_app_filters'];
			
			IPSDebug::addMessage( "Updated filters: <pre>" . var_export( $_POST['search_app_filters'], true ) . "</pre>" );
		}
		
		/* Globalize the session ID */
		$this->request['_sid'] = $this->_session['session_id'];
	}
	
	/**
	 * End the session
	 *
	 */
	protected function _endSession()
	{
		if ( $this->_session['session_id'] )
		{
			$sd = array( 'session_updated'   => time(),
						 'session_data'      => serialize( $this->_session['_session_data'] ) );
						 
			$this->DB->update( 'search_sessions', $sd, 'session_id=\'' . $this->_session['session_id'] . '\'' );
		}
		
		/* Delete old sessions */
		$this->DB->delete( 'search_sessions', 'session_updated < ' . ( time() - 86400 ) );
	}
	
	/**
	 * Set the search order and key
	 *
	 * @return	@e void
	 */
	protected function _setSortFilters()
	{
		$app = $this->request['search_app'];
		$key = 'date';
		$dir = 'desc';
		$dun = false;
		
		/* multi search in options? */
		if ( isset( $this->request['search_app_filters'][ $app ]['searchInKey'] ) )
		{
			$_k = $this->request['search_app_filters'][ $app ]['searchInKey'];
			
			if ( isset( $this->request['search_app_filters'][ $app ][ $_k ]['sortKey'] ) )
			{
				$dun = true;
				$key = $this->request['search_app_filters'][ $app ][ $_k ]['sortKey'];
				$dir = $this->request['search_app_filters'][ $app ][ $_k ]['sortDir'];
			}
		}
		
		/* Normal options - although sometimes used even with multiple types */
		if ( ! $dun AND isset( $this->request['search_app_filters'][$app]['sortKey'] ) )
		{
			$key = $this->request['search_app_filters'][$app]['sortKey'];
			$dir = $this->request['search_app_filters'][$app]['sortDir'];
		}
		/* Global */
		else
		{
			if ( isset( $this->request['search_sort_by'] ) )
			{
				$key = $this->request['search_sort_by'];
				$dir = $this->request['search_sort_order'];
			}
		}
		
		/* Numeric? */
		if ( is_numeric( $dir ) )
		{
			$dir = ( $dir == 0 ) ? 'desc' : 'asc';
		}
		else
		{
			$dir = 'desc';
		}
		
		IPSSearchRegistry::set('in.search_sort_by'   , trim( $key ) );
		IPSSearchRegistry::set('in.search_sort_order', ( $dir != 'desc' ) ? 'asc' : 'desc' );
	}
	
	/**
	 * Displays latest user content
	 *
	 * @return	@e void
	 */
	public function viewUserContent()
	{
		/* INIT */
		$id 	    = $this->request['mid'] ? intval( trim( $this->request['mid'] ) ) : $this->memberData['member_id'];
		
		/* Save query if we are viewing our own content */
		if( $this->memberData['member_id'] AND $id == $this->memberData['member_id'] )
		{
			$member	= $this->memberData;
		}
		else
		{
			$member	    = IPSMember::load( $id, 'core' );
		}
		
		$beginStamp = 0;
		
		if ( ! $member['member_id'] )
		{
			$this->registry->output->showError( 'search_invalid_id', 10147, null, null, 403 );
		}
		
		$this->request['userMode']	= !empty($this->request['userMode']) ? $this->request['userMode'] : 'all';
		
		IPSSearchRegistry::set('in.search_app', $this->request['search_app'] );
		IPSSearchRegistry::set('in.userMode'  , $this->request['userMode'] );

		/* Set sort filters */
		$this->_setSortFilters();
		
		/* Can we do this? */
		if ( IPSLib::appIsSearchable( IPSSearchRegistry::get('in.search_app'), 'usercontent' ) )
		{
			/* Perform the search */
			$this->searchController->viewUserContent( $member );
			
			/* Get count */
			$count = $this->searchController->getResultCount();
			
			/* Get results which will be array of IDs */
			$results = $this->searchController->getResultSet();
			
			/* Get templates to use */
			$template = $this->searchController->fetchTemplates();
			
			/* Fetch sort details */
			$sortDropDown = $this->searchController->fetchSortDropDown();

			/* Set default sort option */
			$_a	= IPSSearchRegistry::get('in.search_app');
			$_k	= IPSSearchRegistry::get( $_a . '.searchInKey') ? IPSSearchRegistry::get( $_a . '.searchInKey') : '';

			if( $_k AND !$this->request['search_app_filters'][ $_a ][ $_k ]['sortKey'] AND is_array($sortDropDown) )
			{
				$this->request['search_app_filters'][ $_a ][ $_k ]['sortKey']	= key( $sortDropDown );
			}
			else if( !$_k AND !$this->request['search_app_filters'][ $_a ]['sortKey'] AND is_array($sortDropDown) )
			{
				$this->request['search_app_filters'][ $_a ]['sortKey']	= key( $sortDropDown );
			}
			
			/* Fetch sort details */
			$sortIn       = $this->searchController->fetchSortIn();
			
			/* Reset for template */
			$this->_resetRequestParameters();
			
			/* Parse result set */
			$results = $this->registry->output->getTemplate( $template['group'] )->$template['template']( $results, ( IPSSearchRegistry::get('opt.searchType') == 'titles' || IPSSearchRegistry::get('opt.noPostPreview') ) ? 1 : 0 );
			
			/* Build pagination */
			$links = $this->registry->output->generatePagination( array( 'totalItems'		=> $count,
																		'itemsPerPage'		=> IPSSearchRegistry::get('opt.search_per_page'),
																		'currentStartValue'	=> IPSSearchRegistry::get('in.start'),
																		'baseUrl'			=> 'app=core&amp;module=search&amp;do=user_activity&amp;mid=' . $id . '&amp;search_app=' . IPSSearchRegistry::get('in.search_app') . '&amp;userMode=' . IPSSearchRegistry::get('in.userMode')  . '&amp;sid=' . $this->request['_sid'] . $this->_returnSearchAppFilters() ) );
		}
		else
		{
			$count   = 0;
			$results = array();
		}
		
		$this->title   = sprintf( $this->lang->words['s_participation_title'], $member['members_display_name'] );
		$this->registry->output->addNavigation( $this->title, '' );
		$this->output .= $this->registry->output->getTemplate( 'search' )->userPostsView( $results, $links, $count, $member, IPSSearchRegistry::get('set.hardLimit'), IPSSearchRegistry::get('set.resultsCutToLimit'), $beginStamp, $sortIn, $sortDropDown );
	}
	
	/**
	 * View new posts since your last visit
	 *
	 * @return	@e void
	 */
	public function viewNewContent()
	{	
		IPSSearchRegistry::set('in.search_app', $this->request['search_app'] );
		
		/* Fetch member cache to see if we have a value set */
		$vncPrefs = IPSMember::getFromMemberCache( $this->memberData, 'vncPrefs' );
	
		/* Guests */
		if ( ! $this->memberData['member_id'] AND ( ! $this->request['period'] OR $this->request['period'] == 'unread' ) )
		{
			$this->request['period'] = 'today';
		}
			
		/* In period */
		if ( $vncPrefs === null OR ! isset( $vncPrefs[ IPSSearchRegistry::get('in.search_app') ]['view'] ) OR ( ! empty( $this->request['period'] ) AND isset( $this->request['change'] ) ) )
		{
			$vncPrefs[ IPSSearchRegistry::get('in.search_app') ]['view'] = ( ! empty( $this->request['period'] ) ) ? $this->request['period'] : $this->settings['default_vnc_method'];
		}
		
		/* Follow filter enabled */
		if ( $vncPrefs === null OR ! isset( $vncPrefs[ IPSSearchRegistry::get('in.search_app') ]['view'] ) OR isset( $this->request['followedItemsOnly'] ) )
		{
			$vncPrefs[ IPSSearchRegistry::get('in.search_app') ]['vncFollowFilter'] = ( ! empty( $this->request['followedItemsOnly'] ) ) ? 1 : 0;
		}
		
		/* Filtering VNC by forum? */
		IPSSearchRegistry::set('forums.vncForumFilters', $vncPrefs['forums']['vnc_forum_filter'] );

		/* Set period up */
		IPSSearchRegistry::set('in.period'           , $vncPrefs[ IPSSearchRegistry::get('in.search_app') ]['view'] );
		IPSSearchRegistry::set('in.vncFollowFilterOn', $vncPrefs[ IPSSearchRegistry::get('in.search_app') ]['vncFollowFilter'] );
		
		$this->request['userMode']	= !empty($this->request['userMode']) ? $this->request['userMode'] : '';
		IPSSearchRegistry::set('in.userMode'  , $this->request['userMode'] );
		
		/* Update member cache */
		if ( isset( $this->request['period'] ) AND isset( $this->request['change'] ) )
		{
			IPSMember::setToMemberCache( $this->memberData, array( 'vncPrefs' => $vncPrefs ) );
		}
		
		IPSDebug::addMessage( var_export( $vncPrefs, true ) );
		IPSDebug::addMessage( 'Using: ' . IPSSearchRegistry::get('in.period') );
		
		/* Can we do this? */
		if ( IPSLib::appIsSearchable( IPSSearchRegistry::get('in.search_app'), 'vnc' ) || IPSLib::appIsSearchable( IPSSearchRegistry::get('in.search_app'), 'active' ) )
		{
			/* Can't do a specific unread search, so */
			if ( IPSSearchRegistry::get('in.period') == 'unread' && ! IPSLib::appIsSearchable( IPSSearchRegistry::get('in.search_app'), 'vncWithUnreadContent' ) )
			{
				IPSSearchRegistry::set( 'in.period', 'lastvisit' );
			}
			
			/* Perform the search */
			$this->searchController->viewNewContent();
			
			/* Get count */
			$count = $this->searchController->getResultCount();
			
			/* Get results which will be array of IDs */
			$results = $this->searchController->getResultSet();
			
			/* Get templates to use */
			$template = $this->searchController->fetchTemplates();
			
			/* Fetch sort details */
			$sortDropDown = $this->searchController->fetchSortDropDown();
			
			/* Fetch sort details */
			$sortIn       = $this->searchController->fetchSortIn();
			
			/* Reset for template */
			$this->_resetRequestParameters();
			
			if( IPSSearchRegistry::get('in.start') > 0 AND !count($results) )
			{
				$new_url	= 'app=core&amp;module=search&amp;do=viewNewContent&amp;period=' . IPSSearchRegistry::get('in.period') . '&amp;userMode=' . IPSSearchRegistry::get('in.userMode') . '&amp;search_app=' . IPSSearchRegistry::get('in.search_app')  . '&amp;sid=' . $this->request['_sid'];
				$new_url	.= '&amp;st=' . ( IPSSearchRegistry::get('in.start') - IPSSearchRegistry::get('opt.search_per_page') ) . '&amp;search_app_filters[' . IPSSearchRegistry::get('in.search_app') . '][searchInKey]=' . $this->request['search_app_filters'][ IPSSearchRegistry::get('in.search_app') ]['searchInKey'];
				
				$this->registry->output->silentRedirect( $this->settings['base_url'] . $new_url );
			}
			
			/* Parse result set */
			$results = $this->registry->output->getTemplate( $template['group'] )->$template['template']( $results, ( IPSSearchRegistry::get('opt.searchType') == 'titles' || IPSSearchRegistry::get('opt.noPostPreview') ) ? 1 : 0 );
			
			/* Build pagination */
			$links = $this->registry->output->generatePagination( array( 'totalItems'		 => $count,
																		 'itemsPerPage'		 => IPSSearchRegistry::get('opt.search_per_page'),
																		 'currentStartValue' => IPSSearchRegistry::get('in.start'),
																		 //'method'			 => 'nextPrevious',
																		 'baseUrl'			 => 'app=core&amp;module=search&amp;do=viewNewContent&amp;period=' . IPSSearchRegistry::get('in.period') . '&amp;userMode=' . IPSSearchRegistry::get('in.userMode') . '&amp;search_app=' . IPSSearchRegistry::get('in.search_app')  . '&amp;sid=' . $this->request['_sid'] . $this->_returnSearchAppFilters() ) );
	
			/* Showing */
			$showing = array( 'start' => IPSSearchRegistry::get('in.start') + 1, 'end' => ( IPSSearchRegistry::get('in.start') + IPSSearchRegistry::get('opt.search_per_page') ) > $count ? $count : IPSSearchRegistry::get('in.start') + IPSSearchRegistry::get('opt.search_per_page') );
		}
		else
		{
			$count   = 0;
			$results = array();
		}
		
		/* Add Debug message */
		IPSDebug::addMessage( "View New Content Matches: " . $count );
		
		/* Check for sortIn */
		if( count( $sortIn ) && !$this->request['search_app_filters'][ $this->request['search_app'] ]['searchInKey'] )
		{
			$this->request['search_app_filters'][ $this->request['search_app'] ]['searchInKey'] = $sortIn[0][0];
		}
		
		/* Output */
		$this->title   = $this->lang->words['new_posts_title'];
		$this->registry->output->addNavigation( $this->lang->words['new_posts_title'], '' );
		$this->output .= $this->registry->output->getTemplate( 'search' )->newContentView( $results, $links, $count, $sortDropDown, $sortIn, IPSSearchRegistry::get('set.resultCutToDate') );
	}
	

	/**
	 * Returns a url string that will maintain search results via links
	 *
	 * @return	string
	 */
	protected function _buildURLString()
	{
		/* INI */
		$url_string  = 'app=core&amp;module=search&amp;do=search&amp;andor_type=' . $this->request['andor_type'];
		$url_string .= '&amp;sid=' . $this->request['_sid'];
		
		/* Add author name */
		if( !empty( $this->request['search_author'] ) )
		{
			$url_string .= "&amp;search_author=" . urlencode($this->request['search_author']);
		}

		/* Search Range */
		if( !empty( $this->request['search_date_start'] ) )
		{
			$url_string .= "&amp;search_date_start={$this->request['search_date_start']}";
		}
		
		if( !empty( $this->request['search_date_end'] ) )
		{
			$url_string .= "&amp;search_date_end={$this->request['search_date_end']}";
		}

		if( !empty( $this->request['search_app_filters'][ $this->request['search_app'] ]['sortKey'] ) )
		{
			$url_string .= "&amp;search_app_filters[{$this->request['search_app']}][sortKey]=" . $this->request['search_app_filters'][ $this->request['search_app'] ]['sortKey'];
		}

		if( !empty( $this->request['search_app_filters'][ $this->request['search_app'] ]['sortDir'] ) )
		{
			$url_string .= "&amp;search_app_filters[{$this->request['search_app']}][sortDir]=" . $this->request['search_app_filters'][ $this->request['search_app'] ]['sortDir'];
		}
		
		/* Contextual Type */
		if ( IPSSearchRegistry::get('contextual.type') )
		{
			$url_string .= "&amp;cType=" . IPSSearchRegistry::get('contextual.type') . "&amp;cId=" . IPSSearchRegistry::get('contextual.id');
		}
		
		/* Content Only */
		if( !empty( $this->request['search_content'] ) )
		{
			$url_string .= "&amp;search_content=" . $this->request['search_content'];
		}
		
		if ( IPSSearchRegistry::get('in.raw_search_tags') )
		{
			$url_string .= "&amp;search_tags=" . urlencode( IPSSearchRegistry::get('in.raw_search_tags') );
		}
		
		/* Types */
		if( isset( $this->request['type'] ) && isset( $this->request['type_id'] ) )
		{
			$url_string .= "&amp;type={$this->request['type']}&amp;type_id={$this->request['type_id']}";
		}
		
		if( isset( $this->request['type_2'] ) && isset( $this->request['type_id_2'] ) )
		{
			$url_string .= "&amp;type_2={$this->request['type_2']}&amp;type_id_2={$this->request['type_id_2']}";
		}
		
		$url_string .= $this->_returnSearchAppFilters();
		
		/* Fix up the search term a bit */
		$_search_term = str_replace( '&amp;', '&', $this->request['search_term'] );
		$_search_term = str_replace( '&quot;', '"', $_search_term );
		$_search_term = str_replace( '&gt;', '>', $_search_term );
		$_search_term = str_replace( '&lt;', '<', $_search_term );
		$_search_term = str_replace( '&#036;', '$', $_search_term );

		$url_string .= '&amp;search_term=' . urlencode( $_search_term );

		return $url_string;		
	}

	/**
	 * Return search app filters
	 *
	 * @return	@e string
	 */
	protected function _returnSearchAppFilters()
	{
		$url_string	= '';

		if( isset($this->request['search_app_filters']) AND $this->request['search_app_filters'] )
		{
			foreach( $this->request['search_app_filters'] as $app => $filters )
			{
				if( $app == $this->request['search_app'] AND count($filters) )
				{
					foreach( $filters as $_filterKey => $_filterValue )
					{
						if( is_array($_filterValue) )
						{
							foreach( $_filterValue as $_filterValueKey => $_filterValueValue )
							{
								$url_string .= "&amp;search_app_filters[{$app}][{$_filterKey}][{$_filterValueKey}]={$_filterValueValue}";
							}
						}
						else
						{
							$url_string .= "&amp;search_app_filters[{$app}][{$_filterKey}]={$_filterValue}";
						}
					}
				}
			}
		}

		return $url_string;
	}
	
	/**
	 * Checks to see if the logged in user is allowed to use the search system
	 *
	 * @return	@e void
	 */
	protected function _canSearch()
	{
		/* Check the search setting */
		if( ! $this->settings['allow_search'] )
		{
			if( $this->xml_out )
			{
				@header( "Content-type: text/html;charset={$this->settings['gb_char_set']}" );
				print $this->lang->words['search_off'];
				exit();
			}
			else
			{
				$this->registry->output->showError( 'search_off', 10145 );
			}
		}
		
		/* Check the member authorization */
		if( ! isset( $this->memberData['g_use_search'] ) || ! $this->memberData['g_use_search'] )
		{
			/* Is it a bot and we're looking for tags? */
			if ( $this->member->is_not_human && ( $this->request['do'] == 'search' && ! empty( $this->request['search_tags'] ) ) )
			{
				return true;
			}
			else
			{
				if( $this->xml_out )
				{
					@header( "Content-type: text/html;charset={$this->settings['gb_char_set']}" );
					print $this->lang->words['no_xml_permission'];
					exit();
				}
				else
				{
					$this->registry->output->showError( 'no_permission_to_search', 10146, null, null, 403 );
				}
			}
		}		
	}
	
	/**
	 * Resets params for template
	 */
	protected function _resetRequestParameters()
	{
		$this->request['period']			= IPSSearchRegistry::get('in.period');
		$this->request['real_period']		= IPSSearchRegistry::get('in.real_period');
		$this->request['search_app']		= IPSSearchRegistry::get('in.search_app');
		$this->request['vncFollowFilterOn']	= IPSSearchRegistry::get('in.vncFollowFilterOn');
		$this->request['followedItemsOnly']	= IPSSearchRegistry::get('in.vncFollowFilterOn');
	}
	 
}