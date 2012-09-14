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
		
		if ( ! $this->remoteDB->checkForField( 'archive_forum_id', 'forums_archive_posts' ) ) 
		{
			$PRE = trim(ipsRegistry::dbFunctions()->getPrefix());
			
			$this->remoteDB->query('ALTER TABLE ' . $PRE . 'forums_archive_posts ADD archive_forum_id INT(10) NOT NULL DEFAULT 0;');
			
			$txt = "Adding archive_forum_id field to your database - Done<br />";
		}
		
		$html = <<<EOF
		<strong>Archive Update for IP.Board 3.3.1</strong>
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
		$id         = intval( $this->request['id'] );
		$lastId     = 0;
		$done  		= intval( $this->request['done'] );
		$cycleDone  = 0;
		$content    = array();
		
		
		/* skipping? */
		$total = $this->remoteDB->buildAndFetch( array( 'select' => 'count(*) as count',
										          		'from'   => 'forums_archive_posts' ) );
		
		/* Fetch batch */
		$this->remoteDB->build( array( 'select' => '*',
									   'from'   => 'forums_archive_posts',
									   'where'  => 'archive_topic_id > ' . $id,
									   'limit'  => array( 0, self::TOPICS_PER_GO ),
									   'order'  => 'archive_topic_id ASC' )  );
	
								
		$o = $this->remoteDB->execute();
		
		while( $row = $this->remoteDB->fetch( $o ) )
		{
			$cycleDone++;
			$lastId = $row['archive_topic_id'];
			
			$content[] = "Updating topics " . ( $done + $cycleDone ) . " (archive_topic_id: " . $row['archive_topic_id'] . ") of " . $total['count'];
			
			/* Fetch the forum ID */
			$topic = $this->DB->buildAndFetch( array( 'select' => 'forum_id',
											 		  'from'   => 'topics',
													  'where'  => 'tid=' . intval( $row['archive_topic_id'] ) ) );
													  
			if ( $topic['forum_id'] )
			{
				$this->remoteDB->update( 'forums_archive_posts', array( 'archive_forum_id' => $topic['forum_id'] ), 'archive_topic_id=' . $row['archive_topic_id'] );
			}
		}
		
		/* More? */
		if ( $cycleDone )
		{
			/* Reset */
			$done += $cycleDone;
			
			$this->show( $content, "do=process&id=" . $lastId . "&done=" . $done );
		}
		else
		{
			$this->show( "Process complete" );
			return;
		}
	}
}

?>