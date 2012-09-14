<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Archive: Restore
 * By Matt Mecham
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		17th February 2010
 * @version		$Revision: 8644 $
 */

class classes_archive_restore_sql extends classes_archive_restore
{
	
	public function __construct()
	{
		/* Make registry objects */
		$this->registry		=  ipsRegistry::instance();
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		
		/* Do we have a remote DB? */
		if ( $this->settings['archive_remote_sql_database'] && $this->settings['archive_remote_sql_user'] )
		{
			if ( ! is_object( $this->registry->dbFunctions()->getDB('remoteArchive') ) )
			{	
				
				$this->registry->dbFunctions()->setDB( 'mysql', 'remoteArchive', array(  'sql_database'	        => $this->settings['archive_remote_sql_database'],
																						 'sql_user'		        => $this->settings['archive_remote_sql_user'],
																						 'sql_pass'		        => $this->settings['archive_remote_sql_pass'],
																						 'sql_host'		        => $this->settings['archive_remote_sql_host'],
																						 'sql_charset'	        => $this->settings['archive_remote_sql_charset'],
																						 'sql_tbl_prefix'       => $this->settings['sql_tbl_prefix'],
																						 'catchConnectionError' => true ) );
				
				
				$this->remoteDB = $this->registry->dbFunctions()->getDB('remoteArchive');
				
				/* Check for connection issue */
				if ( $this->remoteDB->error )
				{
					$this->connectError = $this->remoteDB->error;
					$this->remoteDB     = null;
					
					$this->registry->dbFunctions()->unsetDB('remoteArchive');
				}
			}
			else
			{
				$this->remoteDB = $this->registry->dbFunctions()->getDB('remoteArchive');
				
				/* Check for connection issue */
				if ( $this->remoteDB->error )
				{
					$this->connectError = $this->remoteDB->error;
					$this->remoteDB     = null;
					
					$this->registry->dbFunctions()->unsetDB('remoteArchive');
				}
			}
		}
		else
		{
			$this->remoteDB = $this->DB;
		}
	}
	
	/**
	 * Get max PID in a TID
	 */
	public function getMaxPidInTid( $tid )
	{
		$max = $this->remoteDB->buildAndFetch( array( 'select' => 'MAX(archive_id) as i', 'from' => 'forums_archive_posts', 'where' => 'archive_topic_id=' . intval( $tid ) ) );
		
		return $max['i'];
	}
	
	/**
	 * Fetch by TIDS
	 */
	public function getPidsInTids( array $tids, $limit=50 )
	{
		$pids = array();
		
		/* Select remaining Pids */
		$this->remoteDB->build( array( 'select' => 'archive_id',
									   'from'   => 'forums_archive_posts',
									   'where'  => 'archive_topic_id IN(' . implode( ',', $tids ) . ') AND archive_restored=0',
									   'order'  => 'archive_topic_id ASC, archive_id ASC',
									   'limit'  => array( 0, $limit ) ) );
		
		$o = $this->remoteDB->execute();
		
		while( $post = $this->remoteDB->fetch( $o ) )
		{
			$pids[ $post['archive_id'] ] = $post['archive_id'];
		}
			
		return $pids;
	}
	
	/**
	 * Fetch between TIDS and date
	 *
	 */
	public function getPidsBetweenTidsAndDate( $min, $max, $date=0, $limit=50 )
	{
		$this->remoteDB->build( array( 'select' => 'archive_id',
									   'from'   => 'forums_archive_posts',
								 	   'where'  => 'archive_content_date > ' . $date . ' AND archive_topic_id BETWEEN ' . intval( $min ) . ' AND ' . intval( $max ),
								 	   'order'  => 'archive_topic_id ASC, archive_id ASC',
								 	   'limit'  => array( 0, $limit ) ) );
		
		$o = $this->remoteDB->execute();
		
		while( $post = $this->remoteDB->fetch( $o ) )
		{
			$pids[ $post['archive_id'] ] = $post['archive_id'];
		}
			
		return $pids;
	}
	
	/**
	 * Remove posts by TIDs
	 * @param	array TIDS
	 */
	public function removePostsByTids( array $tids )
	{
		if ( count( $tids ) )
		{
			$this->remoteDB->delete( 'forums_archive_posts', 'archive_topic_id IN(' . implode( ',', $tids ) . ')' );
		}
	}
	
	/**
	 * Remove posts by PIDs
	 * @param	array PIDs
	 */
	public function setAsRestoredByPids( array $pids )
	{
		if ( count( $pids ) )
		{
			$this->DB->update( 'forums_archive_posts', array( 'archive_restored' => 1 ), 'archive_id IN (' . implode( ',', $pids ) .')' );
		}
	}
	
	/**
	 * Write single entry to DB
	 * @param	array	INTS
	 */
	public function set( $data=array() )
	{
		if ( ! count( $data ) )
		{
			return null;
		}
		
		$this->DB->replace( 'posts', $this->archiveToNativeFields( $data ), array( 'pid' ) );
	}
	
}
