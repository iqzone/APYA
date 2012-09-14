<?php
/**
 * Report center comments class
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class comments_core_reports extends classes_comments_renderer
{
	/**
	 * Internal remap array
	 *
	 * @param	array
	 */
	protected $_remap = array(	'comment_id'			=> 'id',
								'comment_author_id'		=> 'comment_by',
								'comment_author_name'	=> 'author_name',
								'comment_text'			=> 'comment',
								'comment_ip_address'	=> 'ip_address',
								'comment_edit_date'		=> 'edit_date',
								'comment_date'			=> 'comment_date',
								'comment_approved'		=> 'approved',
								'comment_parent_id'		=> 'rid' );
					 
	/**
	 * Internal parent remap array
	 *
	 * @param	array
	 */
	protected $_parentRemap = array(	'parent_id'			=> 'id',
										'parent_owner_id'	=> 'updated_by',
										'parent_parent_id'	=> 'uid',
										'parent_title'		=> 'title',
										'parent_seo_title'	=> 'seoname',
										'parent_date'		=> 'date_created' );
	
	/**
	 * Who am I?
	 *
	 * @return	string
	 */
	public function whoAmI()
	{
		return 'core-reports';
	}
	
	/**
	 * Comment table
	 *
	 * @return	string
	 */
	public function table()
	{
		return 'rc_comments';
	}

	/**
	 * Enable reputation?
	 *
	 * @return	string
	 */
	public function reputationEnabled()
	{
		return false;
	}
	
	/**
	 * Fetch parent
	 *
	 * @return	array
	 */
	public function fetchParent( $id )
	{
		static $cachedReports	= array();
		
		if( !isset($cachedReports[ $id ]) )
		{
			$cachedReports[ $id ]	= $this->DB->buildAndFetch( array(
																	'select'	=> 'i.*',
																	'from'		=> array( 'rc_reports_index' => 'i' ),
																	'where'		=> "i.id=" . intval($id),
																	'add_join'	=> array(
																						array(
																							'select'	=> 'c.*',
																							'from'		=> array( 'rc_classes' => 'c' ),
																							'where'		=> 'c.com_id=i.rc_class',
																							'type'		=> 'left',
																							)
																						)
																)		);
		}
		
		return $cachedReports[ $id ];
	}
	
	/**
	 * Fetch settings
	 *
	 * @return	array
	 */
	public function settings()
	{
		return array( 'urls-showParent' => "app=core&amp;module=reports&amp;do=show_report&amp;rid=%s",
					  'urls-report'		=> '' );
	}
	
	/**
	 * Number of items per page
	 *
	 * @return	int
	 */
	public function perPage()
	{
		return 100;
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
	public function preSave( $type, array $array, $commentId=0, $parentId=0 )
	{
		/**
		 * Always approve comments in report center
		 * 
		 * @link	http://community.invisionpower.com/tracker/issue-36632-report-comments-cant-be-approved/
		 */
		$array['comment_approved'] = 1;
		
		return $array;
	}
	
	/**
	 * Post save
	 * Accepts an array of GENERIC data and allows manipulation after it's added to DB
	 *
	 * @param	string	Type of action (edit/add)
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @param	int		Comment id (if available)
	 * @param	int		Parent id
	 * @return 	array	Array of GENERIC data
	 */
	public function postSave( $type, array $array, $commentId=0, $parentId=0 )
	{
		$_cnt	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'rc_comments', 'where' => 'rid=' . $parentId ) );
		$this->DB->update( 'rc_reports_index', array( 'num_comments' => intval($_cnt['total']), 'date_updated' => IPS_UNIX_TIME_NOW, 'updated_by' => $array['comment_author_id'] ), "id=" . $parentId );
		
		//-----------------------------------------
		// Notify other moderators
		//-----------------------------------------
		
		$report			= $this->fetchParent( $parentId );

		$this->registry->class_localization->loadLanguageFile( array( 'public_reports' ) );

		$this->DB->loadCacheFile( IPSLib::getAppDir('core') . '/sql/' . ips_DBRegistry::getDriverType() . '_report_queries.php', 'report_sql_queries' );
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('core') .'/sources/classes/reportLibrary.php', 'reportLibrary' );
		$this->registry->setClass( 'reportLibrary', new $classToLoad( $this->registry ) );
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/reportNotifications.php', 'reportNotifications' );
		$notify			= new $classToLoad( $this->registry );
		
		$this->registry->getClass('reportLibrary')->loadPlugin( $report['my_class'], $report['app'] );
		
		if( is_object($this->registry->getClass('reportLibrary')->plugins[ $report['my_class'] ]) )
		{
			if( $report['extra_data'] && $report['extra_data'] != 'N;' )
			{
				$this->registry->getClass('reportLibrary')->plugins[ $report['my_class'] ]->_extra = unserialize( $report['extra_data'] );
			}
			else
			{
				$this->registry->getClass('reportLibrary')->plugins[ $report['my_class'] ]->_extra = array();
			}
			
			$report_data	= $this->registry->getClass('reportLibrary')->plugins[ $report['my_class'] ]->formatReportData( $report );

			$notify->initNotify( $this->registry->getClass('reportLibrary')->plugins[ $report['my_class'] ]->getNotificationList( IPSText::cleanPermString( $report['mod_group_perm'] ), $report_data ), $report_data );
			$notify->sendReplyNotifications( $array['comment_text'] );
		}
			
		return $array;
	}
	
	/**
	 * Post delete. Can do stuff and that
	 *
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 * @return 	void
	 */
	public function postDelete( $commentIds, $parentId )
	{
		/* Determine most recent comment or report... */
		$_comment	= $this->DB->buildAndFetch( array( 'select' => 'comment_by, comment_date', 'from' => 'rc_comments', 'where' => 'approved=1 AND rid=' . $parentId, 'order' => 'comment_date DESC', 'limit' => array( 1 ) ) );
		$_report	= $this->DB->buildAndFetch( array( 'select' => 'report_by, date_reported', 'from' => 'rc_reports', 'where' => 'rid=' . $parentId, 'order' => 'date_reported DESC', 'limit' => array( 1 ) ) );
		
		$_date_updated	= 0;
		$_updated_by	= 0;
		
		if( $_comment['comment_date'] )
		{
			$_date_updated	= $_comment['comment_date'];
			$_updated_by	= $_comment['comment_by'];
		}
		
		if( $_report['date_reported'] > $_date_updated )
		{
			$_date_updated	= $_report['date_reported'];
			$_updated_by	= $_report['report_by'];
		}
		
		$_cnt	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'rc_comments', 'where' => 'rid=' . $parentId ) );
		$this->DB->update( 'rc_reports_index', array( 'num_comments' => intval($_cnt['total']), 'date_updated' => $_date_updated, 'updated_by' => $_updated_by ), "id=" . $parentId );
	}

	/**
	 * Fetch a total count of comments we can see
	 *
	 * @param	mixed	parent Id or parent array
	 * @return	int
	 */
	public function count( $parent )
	{
		/* Get parent */
		if ( is_numeric( $parent ) )
		{
			$parent	= $this->fetchParent( $parent );
		}

		return intval($parent['num_comments']);
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
		/* Init */
		$comment = array();
		
		/* Got data? */
		if ( empty( $array['comment_parent_id'] ) )
		{
			trigger_error( "No parent ID passed to " . __FILE__, E_USER_WARNING );
		}
		
		/* Get the file */
		$event	= $this->fetchParent( $array['comment_parent_id'] );

		/* Fetch comment */
		if ( $array['comment_id'] )
		{ 
			$comment	= $this->fetchById( $array['comment_id'] );
		}

		/* Check permissions */
		switch( $type )
		{
			case 'view':
				return true;
			break;
			
			case 'edit':
				if( $this->memberData['g_is_supmod'] OR $this->memberData['member_id'] != $comment['comment_mid'] )
				{
					return true;
				}

				return 'NO_PERMISSION';
			break;
			
			case 'add':
				return true;
			break;
			
			case 'delete':
				return $this->memberData['g_is_supmod'] ? true : 'NO_PERMISSION';
			break;
			
			case 'visibility':
			case 'moderate':
				return 'NO_PERMISSION';
			break;
		}
	}

	/**
	 * Returns remap keys (generic => local)
	 *
	 * @return	array
	 */
	public function remapKeys( $type='comment' )
	{
		return ( $type == 'comment' ) ? $this->_remap : $this->_parentRemap;
	}
}