<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Archive: Writer
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

class classes_archive_writer_sql extends classes_archive_writer
{
	protected $remoteDB     = false;
	protected $connectError = false;
	
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
		
		/* Connect */
		$this->_connect();
	}
	
	/**
	 * Connect to DB
	 */
	protected function _connect( $forceNew=false )
	{
		/* Do we have a remote DB? */
		if ( $this->settings['archive_remote_sql_database'] && $this->settings['archive_remote_sql_user'] )
		{
			if ( $forceNew OR ! is_object( $this->registry->dbFunctions()->getDB('remoteArchive') ) )
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
	 * Flush and reconnect
	 */
	public function flush()
	{
		$this->registry->dbFunctions()->unsetDB('remoteArchive');
		$this->_connect( TRUE );
	}
	
	/**
	 * Test to ensure this works
	 */
	public function test()
	{
		if ( $this->connectError )
		{
			return $this->connectError;
		}
		
		if ( ! $this->remoteDB )
		{
			return 'NO_CONNECTION';
		}
		
		if ( ! $this->remoteDB->checkForTable( 'forums_archive_posts' ) )
		{
			return 'NO_TABLE';
		}
		
		return true;
	}
	
	/**
	 * Create the table
	 */
	public function createTable()
	{
		if ( ! $this->remoteDB->checkForTable( 'forums_archive_posts' ) )
		{
			/* Fetch correct file */
			$TABLE  = array();
			$prefix = ( is_object( $this->registry->dbFunctions()->getDB('remoteArchive') ) ) ? $this->registry->dbFunctions()->getPrefix('remoteArchive') : $this->registry->dbFunctions()->getPrefix();
			
			include( IPSLib::getAppDir('forums') . '/setup/versions/install/sql/forums_mysql_tables.php' );/*noLibHook*/
			
			foreach( $TABLE as $t )
			{
				if ( stristr( $t, ' forums_archive_posts' ) )
				{
					$this->remoteDB->query( str_replace( ' forums_archive_posts', ' ' . $prefix . 'forums_archive_posts', $t ) );
				}
			}
		
		}
	}
	
	/**
	 * Update posts
	 *
	 * @param	$what		Array of fields with vals to update
	 * @param	$where		String of what to update
	 */
	public function update( array $what, $where )
	{
		$this->remoteDB->update( 'forums_archive_posts', $what, $where );
	}
	
	/**
	 * Get counts
	 *
	 */
	public function getDoneSoFarByTid( $tid )
	{
		return $this->remoteDB->buildAndFetch( array( 'select' => 'COUNT(*) as count, MAX(archive_id) as max',
													  'from'   => 'forums_archive_posts',
													  'where'  => 'archive_topic_id=' . $tid ) );
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
		
		if ( ! $this->remoteDB )
		{
			return null;
		}
		
		$this->remoteDB->replace( 'forums_archive_posts', $this->nativeToArchiveFields( $data ), array( 'archive_id' ) );
	}
	
}
