<?php

/*
+---------------------------------------------------------------------------
|   IP.Board v3.3.3
|   ========================================
|   by Matthew Mecham
|   (c) 2008 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   Invision Power Board IS NOT FREE SOFTWARE!
+---------------------------------------------------------------------------
|   http://www.invisionpower.com/
|   > $Id: 10039 2011-12-20 19:49:28Z mmecham $
|   > $Revision: 10039 $
|   > $Date: 2011-12-20 14:49:28 -0500 (Tue, 20 Dec 2011) $
+---------------------------------------------------------------------------
*/
@set_time_limit( 3600 );

/**
* Main public executable wrapper.
*
* Set-up and load module to run
*
* @package	IP.Board
* @author   Matt Mecham
* @version	3.0
*/

if ( is_file( './initdata.php' ) )
{
	require_once( './initdata.php' );/*noLibHook*/
}
elseif ( is_file( '../initdata.php' ) )
{
	require_once( '../initdata.php' );/*noLibHook*/
}
else
{
	require_once( 'initdata.php' );/*noLibHook*/
}

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );/*noLibHook*/

$reg = ipsRegistry::instance();
$reg->init();

$moo = new moo( $reg );

class moo
{
	private $processed = 0;
	private $parser;
	private $oldparser;
	private $start     = 0;
	private $end       = 0;
	
	const TOPICS_PER_GO = 100;
	
	function __construct( ipsRegistry $registry )
	{
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		$this->memberData = array();

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
		
		switch( $this->request['do'] )
		{
			case 'process':
				$this->process();
			break;
			default:
				$this->splash();
			break;
		}
	}
	
	function show( $content, $url='' )
	{
		if ( $url )
		{
			$firstBit = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
			$refresh = "<meta http-equiv='refresh' content='0; url={$firstBit}?{$url}'>";
		}
		
		if ( is_array( $content ) )
		{
			$content = implode( "<br />", $content );
		}
		
		$html = <<<EOF
		<html>
			<head>
				<title>Archive Update</title>
				$refresh
			</head>
			<body>
				$content
			</body>
		</html>			
EOF;

		print $html; exit();
	}
	
	/**
	 * SPLASH
	 */
	function splash()
	{
		$txt = '';
		
		$html = <<<EOF
		<strong>Archive Update for IP.Board 3.3.3</strong>
		<br />{$txt}
		<a href="?do=process">Continue</a>
EOF;
	
		$this->show( $html );
	}
	
	/**
	 * Process
	 */
	function process()
	{
		$data   = $this->getRestoreData();
		$topics = array();
		
		$this->DB->build( array( 'select' => 'tid',
								 'from'   => 'topics',
								 'where'  => 'topic_archive_status=4' ) );
								 
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$topics[ $row['tid'] ] = $row['tid'];
		}
		
		/* get maxes */
		if ( count( $topics ) )
		{
			$this->DB->build( array( 'select' => 'topic_id, max(pid) as max',
									 'from'   => 'posts',
									 'where'  => 'topic_id IN( ' . implode( ',', $topics ) . ')',
									 'group'  => 'topic_id' ) );
									 
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$topics[ $row['topic_id'] ] = $row['max'];
			}
		}
		
		$data['restore_manual_tids'] = $topics;
		
		$this->setRestoreData( $data );
		
		$this->show( "Process complete" );
	}
	
	/**
	 * Get restore data
	 */
	public function getRestoreData()
	{
		$data = $this->DB->buildAndFetch( array( 'select' => '*',
												 'from'   => 'core_archive_restore' ) );
		
		if ( IPSLib::isSerialized( $data['restore_manual_tids'] ) )
		{
			$data['restore_manual_tids'] = unserialize( $data['restore_manual_tids'] );
		}
		else
		{
			$data['restore_manual_tids'] = array();
		}
		
		return $data;
	}
	
	/**
	 * Set restore data
	 * @param	array
	 */
	public function setRestoreData( $data )
	{
		if ( is_array( $data['restore_manual_tids'] ) )
		{
			$data['restore_manual_tids'] = serialize( $data['restore_manual_tids'] );
		}
		
		$data['restore_min_tid'] = intval( $data['restore_min_tid'] );
		$data['restore_max_tid'] = intval( $data['restore_max_tid'] );
		
		$this->DB->delete( 'core_archive_restore');
		$this->DB->insert( 'core_archive_restore', $data );
	}
}

?>