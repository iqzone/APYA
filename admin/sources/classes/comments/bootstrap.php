<?php
/**
 * Class to manage comments
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10862 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class classes_comments_bootstrap
{
	static private $_app;
	
	/**
	 * Construct
	 * 
	 * @param	string	Comment class key (app-section)
	 * @param	mixed	Optional extra data to pass to init() method
	 */
	static public function controller( $app=null, $extraData=null )
	{
		if ( strstr( $app, '-' ) )
		{
			list( $app, $key ) = explode( '-', $app );
		}
				
		$_file = IPSLib::getAppDir( $app ) . '/extensions/comments/' . $key . '.php';
		$_class = 'comments_' . $app . '_' . $key;
		
		if ( is_file( $_file ) )
		{
			$classToLoad = IPSLib::loadLibrary( $_file, $_class, $app );
			
			if ( class_exists( $classToLoad ) )
			{
				self::$_app = new $classToLoad();
				self::$_app->init( $extraData );
			}
			else
			{
				throw new Exception( "No comment class available for $app" );
			}
		}
		else
		{
			throw new Exception( "No comment class available for $app" );
		}
		
		/* Load up language file */
		$registry = ipsRegistry::instance();
		$registry->getClass('class_localization')->loadLanguageFile( array( 'public_comments', 'public_editors' ), 'core' );
		
		return self::$_app;
	}
}

abstract class classes_comments_renderer
{
	/**#@+
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
	/**#@-*/	
	
	protected $_cache = array();
	/**
	 * App object
	 *
	 * @param	object
	 */
	protected $_app;
	
	/**
	 * Local settings object
	 *
	 * @param	array
	 */
	protected $_settings;

	/**
	 * CONSTRUCTOR
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make registry objects */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}

	/**
	 * Init method
	 *
	 * @param	mixed	Extra data (can be used by extending classes)
	 * @return	@e void
	 */
	public function init( $extraData=null )
	{
		$this->lang->loadLanguageFile( array( 'public_topic' ), 'forums' );

		/* Init some data */
		$this->_settings = $this->settings();
		$this->qpids = IPSCookie::get( 'comment_pids' );
		$this->request['selectedpids']     = IPSCookie::get( 'commentmodpids' );
		$this->request['selectedpidcount'] = intval( count( preg_split( "/,/", $this->request['commentmodpids'], -1, PREG_SPLIT_NO_EMPTY ) ) );
		IPSCookie::set('commentmodpids', '', 0);
	}
	
	/**
	 * Get report center library
	 *
	 * @return	@e object
	 */
	public function getReportLibrary()
	{
		static $reports = null;
		
		if( !$reports )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/reportLibrary.php', 'reportLibrary' );
			$reports		= new $classToLoad( $this->registry );
		}
		
		return $reports;
	}
	
	/**
	 * Fetch setting
	 *
	 * @param	string	Setting key
	 * @return	mixed	Setting value OR null
	 */
	public function fetchSetting( $k, $parent=array() )
	{
		$setting = ( isset( $this->_settings[ $k ] ) ) ? $this->_settings[ $k ] : null;
		
		if ( $setting !== null && is_array( $parent ) && count( $parent ) )
		{
			foreach( $parent as $_k => $_v )
			{
				$setting = str_replace( '#{' . $_k . '}', $_v, $setting );
			}
		}
		
		return $setting;
	}
	
	/**
	 * Fetch formatted comments with HTML wrap
	 *
	 * @param	mixed		INT, parent ID, array, image data
	 * @param	array		Filters
	 * @return	string
	 */
	public function fetchFormatted( $parent, $filters=array() )
	{
		/* Check parent */
		if ( is_numeric( $parent ) )
		{
			$parent = $this->fetchParent( $parent );
		}
		
		/* Set filters */
		$filters  = $this->_setFilters( $filters );
		$_remap   = $this->remapKeys();
		$parent   = $this->remapFromLocal( $parent, 'parent' );
		$preReply = '';
		
		$data     = array( 'counts' => array( 'commentTotal'  => 0,
											  'thisPageCount' => 0,
							   				  'perPage'	      => $filters['limit'],
										      'curStart'	  => $filters['offset'] ) );
		/* Fetch raw data */
		$comments = $this->fetch( $parent, $filters );
		
		list( $app, $_where ) = explode( '-', $this->whoAmI() );
		
		if ( $comments === false )
		{
			return false;
		}
				 
		/* Can comment */
		$parent['_canComment'] = ( $this->can( 'add', array( 'comment_parent_id' => $parent['parent_id'] ) ) === true ) ? true : false;
			
		/* Can moderate? */
		$parent['_canModerate'] = ( $this->can( 'moderate', array( 'comment_parent_id' => $parent['parent_id'] ) ) === true ) ? true : false;
			
		/* Got something? */
		if ( is_array( $comments ) AND count( $comments ) )
		{
			$total = $this->count( $parent );
			
			/* Add in addtional data */
			if ( $total )
			{
				$data['counts']['commentTotal'] = $total;
				$data['counts']['thisPageCount'] = count( $comments );
			}
				
			$pages = $this->registry->output->generatePagination( array(  'totalItems'			=> $total,
												 						  'itemsPerPage'		=> $filters['limit'],
												 						  'currentStartValue'	=> $filters['offset'],
												 						  'baseUrl'				=> sprintf( $this->fetchSetting('urls-showParent', $parent), $parent['parent_id'] ),
												 						  'anchor'				=> 'commentsStart',
												 						  'base'				=> 'public',
												 						  'seoTitle'			=> $parent['parent_seo_title'],
												 						  'seoTemplate'			=> $this->seoTemplate() ) );
			
		}
		
		
		$data['settings']		= $this->settings();
		$data['canModerate']	= ( $parent['_canModerate'] ) ? true : false;
		$data['fromApp']		= $this->whoAmI();
		$data['thisApp']		= $app;
		$data['repType']		= $_remap['comment_id'];
		$data['enableRep']		= $this->reputationEnabled();
		$data['autoSaveKey']	= 'comment-' . $this->table() . '-' . $parent['parent_id'];
		$data['baseUrl']		= "app=core&amp;module=global&amp;section=comments&amp;parentId={$parent['parent_id']}&amp;fromApp={$data['fromApp']}";
		$data['formUrl']		= $this->registry->output->buildSEOUrl( "" );
		$data['formApp']		= 'core';
		$data['formModule']		= 'global';
		$data['formSection']	= 'comments';

		$data['captcha']		= $this->getCaptcha();
		
		/* Got a reply? */
		if ( !empty( $this->request['_rcid'] ) )
		{
			$preReply = $this->fetchReply( $parent['parent_id'], $this->request['_rcid'], $this->memberData );
		}
		
		/* Give plugin a chance to adjust */
		$adjusted	= $this->preOutputAdjustment( 'commentsList', array( 'comments' => $comments, 'data' => $data, 'pages' => $pages, 'parent' => $parent, 'preReply' => $preReply ) );

		return $this->registry->output->getTemplate( $this->skin() )->commentsList( $adjusted['comments'], $adjusted['data'], $adjusted['pages'], $adjusted['parent'], $adjusted['preReply'] );
	}
	
	/**
	 * Returns recaptcha data if we are a guest
	 */
 	public function getCaptcha()
 	{
 		if ( ! $this->memberData['member_id'] AND $this->settings['guest_captcha'] AND $this->settings['bot_antispam_type'] != 'none' )
 		{
 			return $this->registry->getClass('class_captcha')->getTemplate();
 		}
 	}
	
	/**
	 * Fetch a total count of comments we can see
	 *
	 * @param	mixed	parent Id or parent array
	 * @return	int
	 */
	public function count( $parent )
	{
		/* Check parent */
		if ( is_numeric( $parent ) )
		{
			$parent = $this->fetchParent( $parent );
		}

		$_remap  = $this->remapKeys();
		$parent  = $this->remapFromLocal( $parent, 'parent' );
		
		/* Check it out */
		$q = ( $this->can( 'moderate', array( 'comment_parent_id' => $parent['parent_id'] ) ) ) ? '' : ' AND ' . $_remap['comment_approved'] . '=1';
		
		/* Fetch a total */
		$total	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total',
												   'from'   => $this->table(),
												   'where'  => $_remap['comment_parent_id'] . '=' . $parent['parent_id'] . $q ) );
		
		return $total['total'];
	}
	
	/**
	 * Fetch a single formatted comment. Useful for ajaxish functions.
	 *
	 * @param	mixed		INT, image ID, array, image data
	 * @param	int			Comment ID
	 * @return	string
	 */
	public function fetchFormattedSingle( $parent, $commentId )
	{
		/* Got comment id? */
		$filters['comment_id'] = intval( $commentId );
		
		/* Check parent */
		if ( is_numeric( $parent ) )
		{
			$parent = $this->fetchParent( $parent );
		}
		
		/* Set filters */
		$filters  = $this->_setFilters( $filters );
		$parent   = $this->remapFromLocal( $parent, 'parent' );
		$_remap   = $this->remapKeys();
		
		list( $app, $_where ) = explode( '-', $this->whoAmI() );
		
		/* Fetch raw data */
		$comments = $this->fetch( $parent, $filters );
		$comment  = array_pop( $comments );
		
		if ( $comment === null )
		{
			return false;
		}
			
		/* Can moderate? */
		$parent['_canModerate'] = $this->can( 'moderate', array( 'comment_parent_id' => $parent['parent_id'] ) ) === true ? true: false;
		
		$data['settings']	= $this->settings();
		$data['fromApp']	= $this->whoAmI();
		$data['thisApp']	= $app;
		$data['repType']	= $_remap['comment_id'];
		$data['enableRep']	= $this->reputationEnabled();
		
		/* Give plugin a chance to adjust */
		$adjusted	= $this->preOutputAdjustment( 'commentSingle', array( 'comment' => $comment, 'data' => $data, 'parent' => $parent ) );

		return $this->registry->output->getTemplate( $this->skin() )->comment( $adjusted['comment'], $adjusted['parent'], $adjusted['data'] );
	}
	
	/**
	 * Fetch a comment by ID (no perm checks)
	 *
	 * @param	 int 		Comment ID
	 */
	public function fetchById( $commentId )
	{
		$_remap = $this->remapKeys();
		
		if ( ! isset( $this->_cache[ $commentId ] ) )
		{
			$comment = $this->DB->buildAndFetch( array( 'select' => '*',
									 		 			'from'  => $this->table(),
									 					'where'	=> $_remap['comment_id'] . '=' . intval( $commentId ) ) );
			
			$comment = $this->remapFromLocal( (array) $comment );
			$this->_cache[ $comment['comment_id'] ] = $comment;
		}
		
		return $this->_cache[ $commentId ];
	}
	
	/**
	 * Fetch raw comments
	 *
	 * @param	mixed		parent ID
	 * @param	array		Filters
	 * @return	array
	 */
	public function fetch( $parent, $filters=array() )
	{
		/* Check parent */
		if ( is_numeric( $parent ) )
		{
			$parent = $this->fetchParent( $parent );
		}
		
		/* Set filters */
		$filters              = $this->_setFilters( $filters );
		$parent               = $this->remapFromLocal( $parent, 'parent' );
		$comments             = array();
		$q                    = array();
		$_remap               = $this->remapKeys();
		list( $app, $_where ) = explode( '-', $this->whoAmI() );

		/* unpack cache */
		$this->_unpackRepCache();
		
		/* Joins */
		$_post_joins = array( array( 'select'	=> 'm.*',
									 'where'	=> 'm.member_id=c.' . $_remap['comment_author_id'],
									 'from'		=> array( 'members' => 'm' ),
									 'type'		=> 'left', ),
							  array( 'select'	=> 'pc.*',
									 'where'	=> 'pc.member_id=m.member_id',
									 'from'		=> array( 'pfields_content' => 'pc' ),
									 'type'		=> 'left', ),
							  array( 'select'	=> 'pp.*',
									 'where'	=> 'pp.pp_member_id=m.member_id',
									 'from'		=> array( 'profile_portal' => 'pp' ),
									 'type'		=> 'left', ),
							 );
		
		/* Reputation system enabled? */
		if ( $this->reputationEnabled() && $this->settings['reputation_enabled'] )
		{
			/* Add the join to figure out if the user has already rated the post */
			$_post_joins[] = $this->registry->repCache->getUserHasRatedJoin( $_remap['comment_id'], 'c.' . $_remap['comment_id'], $app );
			
			/* Add the join to figure out the total ratings for each post */
			if( $this->settings['reputation_show_content'] )
			{
				$_post_joins[] = $this->registry->repCache->getTotalRatingJoin( $_remap['comment_id'], 'c.' . $_remap['comment_id'], $app );
			}
		}
		
		/* Start of query */
		$q[] = "c." . $_remap['comment_parent_id'] . "=" . $parent['parent_id'];
		
		/* Can moderate? */
		if ( $this->can( 'moderate', array( 'comment_parent_id' => $parent['parent_id'] ) ) !== true )
		{
			$q[] = 'c.' . $_remap['comment_approved'] . '=1';
		}
		
		/* Fetching a single comment? */
		if ( isset( $filters['comment_id'] ) )
		{
			if ( is_numeric( $filters['comment_id'] ) )
			{
				$q[] = 'c.' . $_remap['comment_id'] . '=' . intval( $filters['comment_id'] );
			}
			else if ( is_array( $filters['comment_id'] ) )
			{
				$q[] = 'c.' . $_remap['comment_id'] . ' IN(' . implode( ",", IPSLib::cleanIntArray( $filters['comment_id'] ) ) . ')';
			}
		}

		/* Set up parser */
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= $this->bbcodeSection();

		/* Allow plugin to add additional where clauses, or modify existing ones */
		$query	= array( 'select'	=> 'c.*',
						 'from'		=> array( $this->table() => 'c' ),
						 'where'	=> implode( ' AND ', $q ),
						 'order'    => 'c.' . $_remap[ $filters['sortKey'] ] . ' ' . $filters['sortOrder'],
						 'limit'    => array( $filters['offset'], $filters['limit'] ),
						 'add_join'	=> $_post_joins );
		
		$query	= $this->alterFetchQuery( $query, $_remap, $parent, $filters );

		/* Fetch the data */
		$this->DB->build( $query );
		
		$oq = $this->DB->execute();
		
		/* Go get 'em bawss */
		$hidden = array();
		while( $row = $this->DB->fetch( $oq ) )
		{
			/* Reset member ID */
			$row['member_id'] = $row[ $_remap['comment_author_id'] ];
			
			/* Set up parser */
			IPSText::getTextClass('bbcode')->parsing_mgroup		 	= $row['member_group_id'];
			IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $row['mgroup_others'];

			/* Remap */
			$row = $this->remapFromLocal( $row );

			/* cache in generic state */
			$this->_cache[ $row['comment_id'] ] = $row;
				
			/* Preserve original DB formatting */
			$row['_db_comment'] = $row['comment_text'];
			
			/* Parse for display */
			$row['comment_text'] = IPSText::getTextClass('bbcode')->preDisplayParse( $row['comment_text'] );
			
			/* Can moderate? */
			if ( $this->can( 'moderate', array( 'comment_parent_id' => $parent['parent_id'] ) ) )
			{
				if ( $this->request['selectedpids'] )
				{
					if ( strstr( ','.$this->request['selectedpids'].',', ','.$row['pid'].',' ) )
					{
						$row['_pidChecked'] = true;
					}
				}
			}
			
			/* Ignored users */
			foreach( $this->member->ignored_users as $_i )
			{
				if( $_i['ignore_topics'] AND $_i['ignore_ignore_id'] == $row['author_id'] )
				{
					if ( ! strstr( $this->settings['cannot_ignore_groups'], ','.$row['member_group_id'].',' ) )
					{
						$row['_ignored']	= true;
						break;
					}
				}
			}
			
			/* Reputation */
			if ( $this->settings['reputation_enabled'] )
			{
				$row['pp_reputation_points'] = $row['pp_reputation_points'] ? $row['pp_reputation_points'] : 0;
				$row['has_given_rep']        = $row['has_given_rep']	    ? $row['has_given_rep'] : 0;
				$row['rep_points']           = $row['rep_points'] 		    ? $row['rep_points'] : 0;
			}
			
			/* Edit button */
			$row['_canEdit'] = ( $this->can('edit', $row) === true ) ? 1 : 0;
			
			/* Hide/Unhide buttons */
			$row['_canHide'] = ( $this->can('hide', $row) === true ) ? 1 : 0;
			$row['_canUnhide'] = ( $this->can('unhide', $row) === true ) ? 1 : 0;
			$row['_canApprove'] = ( $this->can('visibility', $row) === true ) ? 1 : 0;
			
			/* Delete button */
			$row['_canDelete'] = ( $this->can('delete', $row) === true ) ? 1 : 0;
			
			/* Reply button */
			$row['_canReply'] = ( $this->can('add', $row) === true ) ? 1 : 0;
			
			/* Report link */
			if ( $this->fetchSetting('urls-report', $parent) )
			{
				$_parts	= substr_count( $this->fetchSetting('urls-report', $parent), '%s' );
				
				switch( $_parts )
				{
					case 1:
						$row['urls-report'] = sprintf( $this->fetchSetting('urls-report', $parent), $row['comment_id'] );
					break;
					
					case 2:
						$row['urls-report'] = sprintf( $this->fetchSetting('urls-report', $parent), $row['comment_id'], $parent['parent_id'] );
					break;
				}
			}
			
			/* Ensure parity */
			$row['member_id']				= $row['comment_author_id'];
			$row['members_display_name']	= $row['members_display_name'] ? $row['members_display_name'] : $this->lang->words['global_guestname'];
			
			/* Reputation */
			if ( $this->settings['reputation_enabled'] )
			{
				$row['like']	= $this->returnReputationFormatted( $app, $_remap, $row );
			}
			
			/* Hidden Information */
			if ( $row['comment_approved'] == -1 )
			{
				$hidden[] = $row['comment_id'];
			}
			
			/* Done */
			$comments[ $row['comment_id'] ] = array( 'author' => IPSMember::buildDisplayData( $row ), 'comment' => $row );
		}
		if ( !empty( $hidden ) )
		{
			foreach ( IPSDeleteLog::fetchEntries( $hidden, $this->whoAmI() ) as $k => $v )
			{
				$comments[ $k ]['sD'] = $v;
			}
		}

		return is_array($comments) ? $comments : array();
	}

	/**
	 * Return the reputation data formatted.  Abstracted so apps can override if needed.
	 *
	 * @param	string	Application
	 * @param	array 	Remap data
	 * @param	array 	Record data from database
	 * @return	@string Reputation formatted
	 */
	public function returnReputationFormatted( $app, $_remap, $row )
	{
		return $this->registry->repCache->getLikeFormatted( array( 'app' => $app, 'type' => $_remap['comment_id'], 'id' => $row['comment_id'], 'rep_like_cache' => $row['rep_like_cache'] ) );
	}
	
	/**
	 * Updates a comment
	 *
	 * @param	string	Action (delete/approve/unapprove)
	 * @param	int		ID of parent
	 * @param	int		Comment IDs
	 * @param	array	Member Data of current member
	 * @reutrn	html	Does stuff to things.
	 * EXCEPTIONS
	 * MISSING_DATA		Ids missing
	 * NO_PERMISSION	No permission
	 */
	public function moderate( $action, $parentId, $commentIds, $memberData )
	{
		/* Init */
		if ( is_numeric( $memberData ) )
		{
			$memberData = IPSMember::load( $memberData, 'all' );
		}
		
		/* Fetch and check image */
		$parent = $this->fetchParent( $parentId );
		$_remap = $this->remapKeys();
		
		/* Parent */
		$parent = $this->remapFromLocal( $parent, 'parent' );
		
		/* Fetch comment */
		$comments = $this->fetch( $parent, array( 'comment_id' => $commentIds ) );
		
		if ( ! count( $comments ) )
		{
			throw new Exception( 'MISSING_DATA' );
		}

		/* Permission test */
		$can = $this->can('moderate', array( 'comment_parent_id' => $parentId ) );
		
		if ( $can !== true )
		{
			throw new Exception( $can );
		}
		
		/* WHAT ARE YOU DOING HERE? */
		switch ( $action )
		{
			case 'delete':
				try
				{
					return $this->delete( $parentId, $commentIds, $memberData );
				}
				catch (Exception $e)
				{
					throw new Exception('NO_PERMISSION');
				}
			break;
			case 'approve':
				try
				{
					return $this->visibility( 'on', $parentId, $commentIds, $memberData );
				}
				catch (Exception $e)
				{
					throw new Exception('NO_PERMISSION');
				}
			break;
			case 'unapprove':
				try
				{
					return $this->visibility( 'off', $parentId, $commentIds, $memberData );
				}
				catch (Exception $e)
				{
					throw new Exception('NO_PERMISSION' . $e->getMessage() . ' line: ' . $e->getFile() . '.' . $e->getLine());
				}
			break;
			case 'hide':
				$r = $this->hide( $parentId, $commentIds, NULL, $memberData );
				if ( $r !== TRUE ) { throw new Exception( $r ); }
			break;
			case 'unhide':
				$r = $this->unhide( $parentId, $commentIds, $memberData );
				if ( $r !== TRUE ) { throw new Exception( $r ); }
			break;
		}
	}
	
	/**
	 * Hide a comment
	 *
	 * @param	int			Parent ID
	 * @param	int|array	Comment IDs
	 * @param	string		Reason
	 * @param	int|array	Member Data
	 * @return	bool|string	TRUE on sucess, error string on error
	 */
	public function hide( $parentId, $commentIds, $reason, $memberData )
	{
		/* Init */
		if ( is_numeric( $memberData ) )
		{
			$memberData = IPSMember::load( $memberData, 'all' );
		}
		
		/* Check */
		if ( !is_array( $commentIds ) )
		{
			$commentIds = array( $commentIds );
		}
		if ( ! $memberData['member_id'] OR ! $parentId )
		{
			return 'MISSING_DATA';
		}
		
		/* Permission Check */
		foreach ( $commentIds as $k )
		{
			$permCheck = $this->can( 'hide', array( 'comment_id' => $k, 'comment_parent_id' => $parentId ) );
			if ( $permCheck !== TRUE )
			{
				return $permCheck;
			}
		}
		
		/* Do it */
		$_remap   = $this->remapKeys();
		$array = array( 'comment_approved' => -1 );
		$update = $this->preVisibility( -1, $commentIds, $parentId, $array );
		$save = $this->remapToLocal( $update );
		$this->DB->update( $this->table(), $save, $this->DB->buildWherePermission( $commentIds, $_remap['comment_id'], FALSE ) );
		$this->postVisibility( -1, $commentIds, $parentId );
		
		/* Log */
		foreach ( $commentIds as $k )
		{
			IPSDeleteLog::addEntry( $k, $this->whoAmI(), $reason, $memberData );
		}
		
		return true;
	} 
	
	/**
	 * Unhide a comment
	 *
	 * @param	int			Parent ID
	 * @param	int|array	Comment IDs
	 * @param	int|array	Member Data
	 * @return	bool|string	TRUE on sucess, error string on error
	 */
	public function unhide( $parentId, $commentIds, $memberData )
	{
		/* Init */
		if ( is_numeric( $memberData ) )
		{
			$memberData = IPSMember::load( $memberData, 'all' );
		}
		
		/* Check */
		if ( !is_array( $commentIds ) )
		{
			$commentIds = array( $commentIds );
		}
		if ( ! $memberData['member_id'] OR ! $parentId )
		{
			return 'MISSING_DATA';
		}
		
		/* Permission Check */
		foreach ( $commentIds as $k )
		{
			$permCheck = $this->can( 'unhide', array( 'comment_id' => $k, 'comment_parent_id' => $parentId ) );
			if ( $permCheck !== TRUE )
			{
				return $permCheck;
			}
		}
		
		/* Do it */
		$_remap   = $this->remapKeys();
		$array = array( 'comment_approved' => 1 );
		$update = $this->preVisibility( 1, $commentIds, $parentId, $array );
		$save = $this->remapToLocal( $update );
		$this->DB->update( $this->table(), $save, $this->DB->buildWherePermission( $commentIds, $_remap['comment_id'], FALSE ) );
		$this->postVisibility( 1, $commentIds, $parentId );
		
		/* Log */
		IPSDeleteLog::removeEntries( $commentIds, $this->whoAmI() );
		
		
		return true;
	}

	
	/**
	 * Add a comment
	 *
	 * @param	Int		Parent ID
	 * @param	string	Post content
	 * @param	array	Member ID of author (current member assumed if empty)
	 * @return	exception or comment ID
	 *
	 * EXCEPTION CODES
	 * NO_PERMISSION		Do not have permission to add comment
	 */
	 public function add( $parentId, $post, $memberId=null )
	 {
		/* Load member */
		$memberData = IPSMember::load( ( ( $memberId === null ) ? $this->memberData['member_id'] : $memberId ), 'all' );
		
		if( !$memberData['member_id'] )
		{
			if( !$this->request['comment_name'] )
			{
				throw new Exception( 'NO_NAME' );
			}

			$memberData['members_display_name']	= $this->request['comment_name'];
		}
		
		/* Permission test */
		$can = $this->can('add', array( 'comment_parent_id' => $parentId ) );
		
		if ( $can !== true )
		{
			throw new Exception( $can );
		}
		
		/* Mod queue? */
		$modQ = IPSMember::isOnModQueue( $memberData );
		if ( $modQ === NULL )
		{
			throw new Exception( 'NO_PERMISSION' );
		}
				
		/* Format comment */		
		IPSText::getTextClass( 'bbcode' )->parsing_section = $this->bbcodeSection();

		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();
		
		$comment = $editor->process( $post );
		if ( !trim( $comment ) )
		{
			throw new Exception( 'NO_COMMENT' );
		}
		
		$comment = IPSText::getTextClass( 'bbcode' )->preDbParse( $comment );
						 
		/* Build insert data */
		$array  = array( 'comment_author_id'   => intval( $memberData['member_id'] ),
						 'comment_author_name' => $memberData['members_display_name'],
						 'comment_ip_address'  => $this->member->ip_address,
						 'comment_date'        => time(),
						 'comment_text'        => $comment,
						 'comment_approved'    => $modQ ? 0 : 1,
						 'comment_parent_id'   => $parentId );
						 
		/* Pre save */
		$insert = $this->preSave( 'add', $array, 0, $parentId );
		
		/* Insert and fetch DB */
		$save = $this->remapToLocal( $insert );
		
		if( count($save) )
		{
			$this->DB->insert( $this->table(), $save );
			
			$insert['comment_id'] = $this->DB->getInsertId();
		}
		
		/* Post save */
		$insert	= $this->postSave( 'add', $insert, $insert['comment_id'], $parentId );

		/* remove saved content */
		if ( $this->memberData['member_id'] )
		{
			$editor->removeAutoSavedContent( array( 'member_id' => $this->memberData['member_id'], 'autoSaveKey' => 'comment-' . $this->table() . '-' . $parentId ) );
		}

		/* Send notifications */
		$this->sendNotifications( $this->remapFromLocal( $insert ), $comment );
		
		return $insert;
	}
	
	/**
	 * Sends "like" notifications
	 *
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @param	string	Comment Text
	 * @return 	nowt lad
	 */
	public function sendNotifications( array $array, $comment )
	{	
		list( $_app, $_area ) = explode( '-', $this->whoAmI() );
		
		/* Send them if they are approved */
		if ( $array['comment_approved'] )
		{
			$this->lang->loadLanguageFile( array( 'public_comments' ), 'core' );
			
			/* Fetch meta data */
			$parent	= $this->remapFromLocal( $this->fetchParent( $array['comment_parent_id'] ), 'parent' );
			
			$_url 		= $this->fetchSetting( 'urls-showParent', $parent );
			$url  	    = $this->registry->output->buildSEOUrl( sprintf( $_url, $parent['parent_id'] ), 'public', $parent['parent_seo_title'], $this->seoTemplate() );
			$commentUrl = $this->registry->output->buildSEOUrl( 'app=core&amp;module=global&amp;section=comments&amp;do=findComment&amp;fromApp=' . $_app . '-' . $_area . '&amp;parentId=' . $parent['parent_id'] . '&amp;commentId=' . $array['comment_id'], 'public', true, 'findcomment' );
			
			/* Fetch like class */
			try
			{
				require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
				$_like = classes_like::bootstrap( $_app, $_area );
				
				$_like->sendNotifications( $parent['parent_id'], array( 'immediate', 'offline' ), array( 'notification_key'		=> 'new_comment',
																									     'notification_url'		=> $url,
																									     'email_template'		=> 'comment_notifications',
																									     'email_subject'		=> sprintf( $this->lang->words['comment_notice_subject'], $url, $parent['parent_title'] ),
																									     'build_message_array'	=> array( 'NAME'  		=> '-member:members_display_name-',
																																	      'AUTHOR'		=> $this->memberData['members_display_name'],
																																	      'TITLE' 		=> $parent['parent_title'],
																																	      'URL'			=> $url,
																																	      'COMMENTURL'  => $commentUrl,
																																	      'COMMENT'		=> $comment ) ) );
			}
			catch( Exception $e )
			{
				/* No like class for this comment class */
			}
		}
	}
	
	/**
	 * Updates a comment
	 *
	 * @param	int		ID of parent
	 * @param	int		Comment ID
	 * @param	array	Member Data of current member
	 * @reutrn	html	Content of edited comment ready for printing to screen
	 * EXCEPTIONS
	 * MISSING_DATA		Ids missing
	 * NO_PERMISSION	No permission
	 */
	public function edit( $parentId, $commentId, $post, $memberData )
	{
		/* Init */
		if ( is_numeric( $memberData ) )
		{
			$memberData = IPSMember::load( $memberData, 'all' );
		}
		
		/* Fetch and check image */
		$parent = $this->fetchParent( $parentId );
		$_remap = $this->remapKeys();
		
		/* Parent */
		$parent = $this->remapFromLocal( $parent, 'parent' );
		
		/* Fetch comment */
		$_c      = $this->fetch( $parent, array( 'comment_id' => $commentId ) );
		$comment = array_pop( $_c );

		/* Permission test */
		$can = $this->can('edit', array( 'comment_id' => $commentId, 'comment_parent_id' => $parentId ) );
		
		if ( $can !== true )
		{
			throw new Exception( $can );
		}

		/* Format post */
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parse_smilies		= 1;
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parsing_section    = $this->bbcodeSection();
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();
		
		$comment = $editor->process( $post );
		$comment = IPSText::getTextClass( 'bbcode' )->preDbParse( $comment );
							 
		/* Update array */
		$array  = array( 'comment_edit_date' => time(),
						 'comment_text'		 => $comment );
		/* Pre save */
		$update = $this->preSave( 'edit', $array, $commentId, $parentId );
		
		/* Insert and fetch DB */
		$save = $this->remapToLocal( $update );
		
		/* Update */
		if( count($save) )
		{
			$this->DB->update( $this->table(), $save, $_remap['comment_id'] . '=' . intval( $commentId ) );
		}
		
		/* Post save */
		$this->postSave( 'edit', $update, $commentId, $parentId );
		
		/* remove saved content */
		if ( $this->memberData['member_id'] )
		{
			$editor->removeAutoSavedContent( array( 'member_id' => $this->memberData['member_id'], 'autoSaveKey' => 'comment-' . $this->table() . '-c' . $commentId ) );
		}

		/* Format and return */
		IPSText::getTextClass('bbcode')->parse_html				= 0;
		IPSText::getTextClass('bbcode')->parse_nl2br			= 1;
		IPSText::getTextClass('bbcode')->parse_smilies			= 1;
		IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
		IPSText::getTextClass('bbcode')->parsing_section    	= $this->bbcodeSection();
		IPSText::getTextClass('bbcode')->parsing_mgroup		 	= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];
		
		return IPSText::getTextClass('bbcode')->preDisplayParse( $comment );
	}
	
	/**
	 * Deletes a comment
	 *
	 * @param	int		Image ID of parent
	 * @param	mixed	Comment ID or array of comment IDs
	 * @param	array	Member Data of current member
	 * @reutrn	html
	 * EXCEPTIONS
	 * MISSING_DATA		Ids missing
	 * NO_PERMISSION	No permission
	 */
	public function delete( $parentId, $commentId, $memberData )
	{
		/* Init */
		if ( is_numeric( $memberData ) )
		{
			$memberData = IPSMember::load( $memberData, 'all' );
		}
		
		/* Check */
		if ( ! $memberData['member_id'] OR ! $parentId OR ! $commentId )
		{
			throw new Exception('MISSING_DATA');
		}
		
		/* Fetch and check image */
		$parent = $this->fetchParent( $parentId );
		$_remap = $this->remapKeys();
		$cids   = array();
		
		/* Parent */
		$parent = $this->remapFromLocal( $parent, 'parent' );
		
		/* One or many? */
		if ( is_numeric( $commentId ) )
		{
			/* Fetch comment */
			$_c      = $this->fetch( $parent, array( 'comment_id' => $commentId ) );
			$comment = array_pop( $_c );
			
			/* Permission test */
			$can = $this->can('delete', array( 'comment_id' => $commentId, 'comment_parent_id' => $parentId ) );
			
			if ( $can !== true )
			{
				throw new Exception( $can );
			}
			
			$cids = array( $commentId );
		}
		else if ( is_array( $commentId ) )
		{
			/* Permission test */
			$can = $this->can('moderate', array( 'comment_parent_id' => $parentId ) );
			
			if ( $can !== true )
			{
				throw new Exception( $can );
			}
			
			/* Finalize comment Ids */
			$this->DB->build( array( 'select' => '*',
									 'from'   => $this->table(),
									 'where'  => $_remap['comment_id'] . ' IN (' . implode( ",", IPSLib::cleanIntArray( $commentId ) ) . ') AND ' . $_remap['comment_parent_id'] . '=' . $parent['parent_id'] ) );
									 
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$cids[] = $row[ $_remap['comment_id'] ];
			}
		}
		
		/* If we have anything.. */
		if ( count( $cids ) )
		{
			/* Pre delete */
			$this->preDelete( $cids, $parentId );
			
			/* Delete */
			$this->DB->delete( $this->table(), $_remap['comment_id'] . ' IN (' . implode( ",", $cids ) . ')' );
			
			/* We also need to delete rep - Bug #37686 */			
			list( $app, $_where ) = explode( '-', $this->whoAmI() );
			
			foreach( $cids as $cid )
			{
				$this->DB->delete( 'reputation_cache' , "app='" . $app . "' AND type='comment_id' AND type_id=" . $cid );
				$this->DB->delete( 'reputation_index' , "app='" . $app . "' AND type='comment_id' AND type_id=" . $cid );
				$this->DB->delete( 'reputation_totals', "rt_key=MD5('" . $app . ";comment_id;" . $cid . "') AND rt_type_id=" . $cid );
			}
					
			/* Post delete */
			$this->postDelete( $cids, $parentId );
		}
		
		/* Return count deleted */
		return count( $cids );
	}
	
	/**
	 * Toggles visbility a comment
	 *
	 * @param	string	on/off
	 * @param	int		Image ID of parent
	 * @param	mixed	Comment ID or array of comment IDs
	 * @param	array	Member Data of current member
	 * @reutrn	html
	 * EXCEPTIONS
	 * MISSING_DATA		Ids missing
	 * NO_PERMISSION	No permission
	 */
	public function visibility( $toggle, $parentId, $commentId, $memberData )
	{
		/* Init */
		if ( is_numeric( $memberData ) )
		{
			$memberData = IPSMember::load( $memberData, 'all' );
		}
		
		/* Check */
		if ( ! $memberData['member_id'] OR ! $parentId OR ! $commentId )
		{
			throw new Exception('MISSING_DATA');
		}
		
		/* Fetch and check image */
		$parent = $this->fetchParent( $parentId );
		$_remap = $this->remapKeys();
		$cids   = array();
		
		/* Parent */
		$parent = $this->remapFromLocal( $parent, 'parent' );
		
		/* One or many? */
		if ( is_numeric( $commentId ) )
		{
			/* Fetch comment */
			$_c      = $this->fetch( $parent, array( 'comment_id' => $commentId ) );
			$comment = array_pop( $_c );
			
			/* Permission test */
			$can = $this->can('visibility', array( 'comment_id' => $commentId, 'comment_parent_id' => $parentId ) );
			
			if ( $can !== true )
			{
				throw new Exception( $can );
			}
			
			$cids = array( $commentId );
		}
		else if ( is_array( $commentId ) )
		{
			/* Permission test */
			$can = $this->can('visibility', array( 'comment_parent_id' => $parentId ) );
			
			if ( $can !== true )
			{
				throw new Exception( $can );
			}
			
			/* Finalize comment Ids */
			$this->DB->build( array( 'select' => '*',
									 'from'   => $this->table(),
									 'where'  => $_remap['comment_id'] . ' IN (' . implode( ",", IPSLib::cleanIntArray( $commentId ) ) . ') AND ' . $_remap['comment_parent_id'] . '=' . $parent['parent_id'] ) );
									 
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$cids[] = $row[ $_remap['comment_id'] ];
			}
		}
		
		/* If we have anything.. */
		if ( count( $cids ) )
		{			
			/* Update array */
			$array = array( 'comment_approved' => ( $toggle == 'on' ? 1 : 0 ) );
			
			/* Pre save */
			$update = $this->preVisibility( $toggle, $cids, $parentId, $array );

			/* Insert and fetch DB */
			$save = $this->remapToLocal( $update );

			/* Update */
			$this->DB->update( $this->table(), $save, $_remap['comment_id'] . ' IN (' . implode( ",", $cids ) . ')' );
			
			/* Post delete */
			$this->postVisibility( $toggle, $cids, $parentId );
		}
		
		/* Return count deleted */
		return count( $cids );
	}
	
	/**
	 * Display a comment edit form suitable for ajax
	 *
	 * @param	int		Image ID of parent
	 * @param	int		Comment ID
	 * @param	array	Member Data of current member
	 * @reutrn	html
	 * EXCEPTIONS
	 * MISSING_DATA		Ids missing
	 * NO_PERMISSION	No permission
	 */
	public function fetchReply( $parentId, $commentId, $memberData )
	{
		/* Init */
		if ( is_numeric( $memberData ) )
		{
			$memberData = IPSMember::load( $memberData, 'all' );
		}
		
		/* Check */
		if ( ! $parentId OR ! $commentId )
		{
			throw new Exception('MISSING_DATA');
		}
		
		/* Fetch and check image */
		$parent = $this->fetchParent( $parentId );
		
		/* Parent */
		$parent = $this->remapFromLocal( $parent, 'parent' );
		
		/* Fetch comment */
		$_c      = $this->fetch( $parent, array( 'comment_id' => $commentId ) );
		$comment = array_pop( $_c );
		
		/* Permission test */
		$can = $this->can('view', array( 'comment_id' => $commentId, 'comment_parent_id' => $parentId ) );
		
		if ( $can !== true )
		{
			throw new Exception( $can );
		}
			
		if ( $this->settings['strip_quotes'] )
		{
			$comment['comment']['comment_text'] = IPSText::getTextClass( 'bbcode' )->stripQuotes( $comment['comment']['_db_comment'] );
		}
		
		$reply = "[quote name='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $comment['author']['members_display_name'] ) . "' timestamp='" . $comment['comment']['comment_date'] . "']<br />{$comment['comment']['comment_text']}<br />[/quote]<br /><br />";
		
		/* Throw, fetch and wag tail */
		return $reply;
	}

	
	/**
	 * Display a comment edit form suitable for ajax
	 *
	 * @param	int		Image ID of parent
	 * @param	int		Comment ID
	 * @param	array	Member Data of current member
	 * @param	string	Type: html or ajax
	 * @reutrn	html
	 * EXCEPTIONS
	 * MISSING_DATA		Ids missing
	 * NO_PERMISSION	No permission
	 */
	public function displayEditForm( $parentId, $commentId, $memberData, $type='html' )
	{
		/* Init */
		if ( is_numeric( $memberData ) )
		{
			$memberData = IPSMember::load( $memberData, 'all' );
		}
		
		/* Check */
		if ( ! $memberData['member_id'] OR ! $parentId OR ! $commentId )
		{
			throw new Exception('MISSING_DATA');
		}
		
		/* Fetch and check image */
		$parent = $this->fetchParent( $parentId );
		
		/* Parent */
		$parent = $this->remapFromLocal( $parent, 'parent' );
		
		/* Fetch comment */
		$_c      = $this->fetch( $parent, array( 'comment_id' => $commentId ) );
		$comment = array_pop( $_c );
		
		/* Permission test */
		$can = $this->can('edit', array( 'comment_id' => $commentId, 'comment_parent_id' => $parentId ) );
		
		if ( $can !== true )
		{
			throw new Exception( $can );
		}

		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parse_smilies		= 1;
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parsing_section    = $this->bbcodeSection();

		/* Ayjacks? */
		if ( $type == 'ajax' )
		{
			/* Throw, fetch and wag tail */
			return $this->registry->getClass('output')->getTemplate('editors')->ajaxEditBox( $comment['comment']['_db_comment'], $commentId, array(), array( 'showEditOptions' => false, 'checkBoxes' => false, 'skipFullButton' => true, 'autoSaveKey' => 'comment-' . $this->table() . '-c' . $commentId ) );
		}
		else
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
			$editor = new $classToLoad();
			
			$_editor = $editor->show( 'Post', array( 'type' => 'mini', 'autoSaveKey' => 'comment-' . $this->table() . '-c' . $commentId, 'warnInfo' => 'fastReply' ), $comment['comment']['_db_comment'] );

			$settings = array( 'fromApp'		=> $this->whoAmI(),
							   'thisApp'		=> preg_replace( '#^(.+?)(?:-.*|$)#', '\\1', $this->whoAmI() ),
							   'baseUrl'		=> "app=core&amp;module=global&amp;section=comments&amp;parentId={$parent['parent_id']}&amp;fromApp=" . $this->whoAmI(),
							   'formAction'		=> $this->settings['base_url'],
							   'formApp'		=> 'core',
							   'formModule'		=> 'global',
							   'formSection'	=> 'comments' );

			/* Give plugin a chance to adjust */
			$adjusted	= $this->preOutputAdjustment( 'form', array( 'comment' => $comment, 'parent' => $parent, '_editor' => $_editor, 'settings' => $settings, 'errors' => array(), 'do' => 'saveEdit' ) );
						
			return $this->registry->getClass('output')->getTemplate( $this->skin() )->form( $adjusted['comment'], $adjusted['parent'], $adjusted['_editor'], $adjusted['settings'], $adjusted['errors'], $adjusted['do'] );
		}
	}
	
	/**
	 * Display a comment edit form suitable for ajax
	 *
	 * @param	int		Image ID of parent
	 * @param	int		Comment ID
	 * @param	array	Member Data of current member
	 * @reutrn	html
	 * EXCEPTIONS
	 * MISSING_DATA		Ids missing
	 * NO_PERMISSION	No permission
	 */
	public function displayAjaxEditForm( $parentId, $commentId, $memberData )
	{
		try
		{
			return $this->displayEditForm( $parentId, $commentId, $memberData, 'ajax' );
		}
		catch( Exception $e )
		{
			throw new Exception( $e->getMessage() );
			
		}
	}
	
	/**
	 * Redirects to the latest comment
	 *
	 * @param	int			Comment ID
	 * @param	int			Parent ID
	 * @param	bool		Did we just add a comment that requires approval?
	 * @return	@e void		[Redirects]
	 */
	public function redirectToComment( $commentId, $parentId, $showApprovalMessage=false )
	{
		/* Init */
		$parentId = intval( $parentId );
		
		if( !$parentId )
		{
			$this->registry->output->showError( 'nocomment_found', 110102.111 );
		}
		
		$parent   = $this->fetchParent( $parentId );
		$_remap   = $this->remapKeys();
		
		/* Parent */
		$parent = $this->remapFromLocal( $parent, 'parent' );
		
		/* fetch */
		$q = ( $this->can( 'moderate', array( 'comment_parent_id' => $parent['parent_id'] ) ) === true ) ? '' : ' AND ' . $_remap['comment_approved'] . '=1';
		
		/* Fetch comment */
		if ( $commentId == 'last' )
		{
			$comment = $this->DB->buildAndFetch( array(  'select' => '*',
														 'from'   => $this->table(),
														 'where'  => $_remap['comment_parent_id'] . '='. $parentId . $q,
														 'order'  => $_remap['comment_id'] . ' DESC',
														 'limit'  => array( 0, 1 ) ) );
		}
		else
		{
			$comment = $this->DB->buildAndFetch( array(  'select' => '*',
														 'from'   => $this->table(),
														 'where'  => $_remap['comment_id'] . '='. intval( $commentId ) . $q,
														 'limit'  => array( 0, 1 ) ) );
		}
		
		/* got a comment? */
		if ( ! is_array( $comment ) )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . sprintf( $this->fetchSetting('urls-showParent', $parent), $parentId ) );
		}
		
		/* Permission test */
		$can = $this->can('view', array( 'comment_id' => $comment['comment_id'], 'comment_parent_id' => $parentId ) );
		
		if ( $can !== true )
		{
			throw new Exception( $can );
		}
		
		/* Fetch total */
		$total	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total',
												   'from'   => $this->table(),
												   'where'  => $_remap['comment_parent_id'] . '=' . $parentId . $q . ' AND ' . $_remap['comment_id'] . '<=' . intval( $comment[ $_remap['comment_id'] ] ) ) );
												   
		if ( $total['total'] > $this->perPage() )
		{
			if ( $total['total'] % $this->perPage() == 0 )
			{
				$pages = $total['total'] / $this->perPage();
			}
			else
			{
				$number = ( $total['total'] / $this->perPage() );
				$pages = ceil( $number);
			}
			
			$st = ($pages - 1) * $this->perPage(); 
			
			if( $showApprovalMessage )
			{
				$this->registry->output->redirectScreen( $this->lang->words['comment_approval_required'], $this->settings['base_url'] . sprintf( $this->fetchSetting('urls-showParent', $parent), $parentId ) . "&st=" . $st . '#comment_' . $comment[ $_remap['comment_id'] ], $parent['parent_seo_title'], $this->seoTemplate() );
			}
			else
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] . sprintf( $this->fetchSetting('urls-showParent', $parent), $parentId ) . "&st=" . $st . '#comment_' . $comment[ $_remap['comment_id'] ], $parent['parent_seo_title'], false, $this->seoTemplate() );
			}
		}
		else
		{
			if( $showApprovalMessage )
			{
				$this->registry->output->redirectScreen( $this->lang->words['comment_approval_required'], $this->settings['base_url'] . sprintf( $this->fetchSetting('urls-showParent', $parent), $parentId ) . "&st=0#comment_" . $comment[ $_remap['comment_id'] ], $parent['parent_seo_title'], $this->seoTemplate() );
			}
			else
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] . sprintf( $this->fetchSetting('urls-showParent', $parent), $parentId ) . "&st=0#comment_" . $comment[ $_remap['comment_id'] ], $parent['parent_seo_title'], false, $this->seoTemplate() );
			}
		}
	}
	
	/**
	 * Remap for local
	 * Accepts a standard array of data
	 *
	 * @param	array 	Array of GENERIC formatted data
	 * @return	@e void
	 */
	public function remapToLocal( array $array, $type='comment' )
	{
		/* Return */
		$return = array();
		$_remap = $this->remapKeys( $type );
		
		/* Please; continue */
		foreach( $array as $k => $v )
		{
			if ( isset( $_remap[ $k ] ) )
			{
				$return[ $_remap[ $k ] ] = $v;
			}
			else
			{
				$return[ $k ] = $v;
			}
		}
		
		return $return;
	}
	
	/**
	 * Remap for generic
	 * Accepts a standard array of data
	 *
	 * @param	array 	Array of LOCAL data
	 * @return	@e void
	 */
	public function remapFromLocal( array $array, $type='comment' )
	{
		/* Return */
		$return  = array();
		$_remap  = array_flip( $this->remapKeys( $type ) );
		
		/* Please; continue */
		foreach( $array as $k => $v )
		{
			if ( isset( $_remap[ $k ] ) )
			{
				$return[ $_remap[ $k ] ] = $v;
			}
			else
			{
				$return[ $k ] = $v;
			}
		}
		
		return $return;
	}
	
	/**
	 * Parent SEO template
	 *
	 * @return	string
	 */
	public function seoTemplate()
	{
		return '';
	}

	/**
	 * Section parsing for BBCode routines
	 *
	 * @return	string
	 */
	public function bbcodeSection()
	{
		return 'global_comments';
	}

	/**
	 * Enable reputation?
	 *
	 * @return	string
	 */
	public function reputationEnabled()
	{
		return true;
	}
	
	/**
	 * Number of items per page
	 *
	 * @return	int
	 */
	public function perPage()
	{
		return 10;
	}
	
	/**
	 * Skin class
	 *
	 * @return	string
	 */
	public function skin()
	{
		return 'global_comments';
	}

	/**
	 * Adjust parameters
	 *
	 * @param	string	Skin template being called
	 * @param	array	Array of parameters to be passed to skin template
	 * @return	array	Skin parameters to be passed to template (array keys MUST be preserved)
	 */
	public function preOutputAdjustment( $template, $params )
	{
		return $params;
	}

	/**
	 * Adjust where clause parameters for fetch query
	 *
	 * @param	array	Array of where clause parameters
	 * @param	array 	Remapped columns used in the query
	 * @param	array 	Parent data
	 * @param	array 	Filters to use in the query
	 * @return	array	Array of where clause parameters
	 */
	public function alterFetchQuery( $q, $remap, $parent, $filters )
	{
		return $q;
	}
	
	/**
	 * Pre save
	 * Accepts an array of GENERIC data and allows manipulation before it's added to DB
	 *
	 * @param	string		$type			Type of save (edit/add)
	 * @param	array		$array			Array of GENERIC data (comment_xxxx)
	 * @param	integer		$commentId		Comment ID (only for edits)
	 * @param	integer		$parentId		Parent content ID
	 * @return	@e array	Array of GENERIC data
	 */
	public function preSave( $type, array $array )
	{
		return $array;
	}
	
	/**
	 * Post save
	 * Accepts an array of GENERIC data and allows manipulation after it's added to DB
	 *
	 * @param	string	Type of action (edit/add)
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @return 	nowt lad
	 */
	public function postSave( $type, array $array )
	{
		return $array;
	}
	
	/**
	 * Pre Visibility
	 * Pre-DB-save function before visibility is toggled
	 *
	 * @param	string	on/off
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 * @param	array	Array of db fields => data to be updated
	 */
	public function preVisibility( $toggle, $commentIds, $parentId, array $update )
	{
		return $update;
	}
	
	/**
	 * Post Visibility
	 * Pre-DB-save function before visibility is toggled
	 *
	 * @param	string	on/off
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 */
	public function postVisibility( $toggle, $commentIds, $parentId )
	{
	}
	
	/**
	 * Pre delete. Can do stuff and that
	 *
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 * @return 	nowt lad
	 */
	public function preDelete( $commentIds, $parentId )
	{
	}
	
	/**
	 * Post delete. Can do stuff and that
	 *
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 * @return 	nowt lad
	 */
	public function postDelete( $commentIds, $parentId )
	{
	}
	
	/**
	 * Perform a permission check
	 *
	 * @param	string	Type of check (add/edit/delete/editall/deleteall/approve all)
	 * @param	array 	Array of GENERIC data
	 * @return	true or string to be used in exception
	 */
	public function can( $type, array $array )
	{
		/* Default, so return least permissive */
		return 'NO_PERMISSION';	
	}
	/**
	 * Unpack the repuation cache, yo.
	 *
	 * @return	@e void
	 */
	protected function _unpackRepCache()
	{
		if ( $this->settings['reputation_enabled'] )
		{
			/* Load the class */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
			$this->registry->setClass( 'repCache', new $classToLoad() );
			
			/* Update the filter? */
			if( isset( $this->request['rep_filter'] ) && $this->request['rep_filter'] == 'update' )
			{
				$_mem_cache = IPSMember::unpackMemberCache( $this->memberData['members_cache'] );
				
				if( $this->request['rep_filter_set'] == '*' )
				{
					$_mem_cache['rep_filter'] = '*';
				}
				else
				{
					$_mem_cache['rep_filter'] = intval( $this->request['rep_filter_set'] );
				}
				
				IPSMember::packMemberCache( $this->memberData['member_id'], $_mem_cache );
				
				$this->memberData['_members_cache'] = $_mem_cache;
			}
			else
			{
				$this->memberData['_members_cache'] = IPSMember::unpackMemberCache( $this->memberData['members_cache'] );
			}
			
			$this->memberData['_members_cache']['rep_filter'] = isset( $this->memberData['_members_cache']['rep_filter'] ) ? $this->memberData['_members_cache']['rep_filter'] : '*';
		}
	}
	
	/**
	 * Set filters
	 * Takes user input and cleans it up a bit
	 *
	 * @param	array		Incoming filters
	 * @return	array
	 */
	protected function _setFilters( $filters )
	{
		/* Do some set up */
		switch( $filters['sortKey'] )
		{
			case 'post_date':
			case 'date':
			case 'time':
				$filters['sortKey']  = 'comment_date';
			break;
			case 'author':
			case 'author_id':
				$filters['sortKey']  = 'comment_author_id';
			break;
			
			default:
				if ( !$filters['sortKey'] )
				{
					$filters['sortKey'] = 'comment_date';
				}
			break;
		}
		
		switch( $filters['sortOrder'] )
		{
			case 'desc':
			case 'descending':
			case 'z-a':
				$filters['sortOrder'] = 'desc';
			break;
			default:
			case 'asc':
			case 'ascending':
			case 'a-z':
				$filters['sortOrder'] = 'asc';
			break;
		}
		
		/* Others */
		$filters['offset']     = intval( $filters['offset'] );
		$filters['limit']      = intval( $filters['limit'] );
		$filters['unixCutOff'] = intval( $filters['unixCutOff'] );
				
		/* Make sure we have a limit */
		if ( ! $filters['limit'] )
		{
			$filters['limit'] = $this->perPage();
		}
		
		/* So we don't have to do this twice */
		$filters['_cleaned']   = true;
		
		return $filters;
	}
}