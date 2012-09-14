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
|   > $Id: galleryRebuild4_php.php 10039 2011-12-20 19:49:28Z mmecham $
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
else
{
	require_once( '../initdata.php' );/*noLibHook*/
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
	
	function __construct( ipsRegistry $registry )
	{
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		$this->memberData = array();
		
		switch( $this->request['do'] )
		{
			case 'save':
				$this->save();
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
				<title>Group Edit BitWise</title>
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
		/* Unpack bitwise fields */
		$_tmp	= IPSBWOptions::thaw( 0, 'groups', 'global' );
		$groups = $this->caches['group_cache'];
		
		foreach( $_tmp as $k => $v )
		{
			$radio .= "<tr>
						<td><strong>{$k}</strong></td>
						<td><input type='radio' name='bitwise[$k]' value='leave' checked='checked' /> <em>Leave as per group</em></td>
						<td><input type='radio' name='bitwise[$k]' value='1' /> <span style='color:green'>ON</span></td>
						<td><input type='radio' name='bitwise[$k]' value='0' /> <span style='color:red'>OFF</span></td>
					</tr>";
					
		}
		
		foreach( $groups as $id => $data )
		{
			$groupHTML .= "<div><input type='checkbox' name='groups[$id]' value='1' /> " . $data['g_title'] . "</div>\n";
		}
		
		$html = <<<EOF
		<form action="?do=save" method="POST">
		Bitwise Values
		<table>{$radio}</table>
		<br />
		Apply to groups:
		{$groupHTML}
		<br />
		<input type="submit" value="Save" />
		</form>
EOF;
	
		$this->show( $html );
	}
	
	/**
	 * Convert images
	 */
	function save()
	{
		$gids    = $_POST['groups'];
		$bitwise = $_POST['bitwise'];
		$groups  = $this->caches['group_cache'];

		foreach( $gids as $id => $val )
		{
			$thisGroup = $groups[ $id ];
			
			foreach( $bitwise as $k => $v )
			{
				if ( $v != 'leave' )
				{
					$thisGroup[ $k ] = intval( $v );
				}
			}
			
			$g_bitoptions = IPSBWOPtions::freeze( $thisGroup, 'groups', 'global' );
			
			$this->DB->update( 'groups', array( 'g_bitoptions' => $g_bitoptions ), 'g_id=' . $id );
		}
		
		$this->rebuildGroupCache();
		
		$this->show('Done');
	}
	
		/**
	 * Rebuilds the group cache
	 *
	 * @return	@e void
	 */
	public function rebuildGroupCache()
	{
		$cache	= array();
			
		$this->DB->build( array( 'select'	=> '*',
								 'from'	    => 'groups',
								 'order'	=> 'g_title ASC' ) );
		$this->DB->execute();
		
		while ( $i = $this->DB->fetch() )
		{
			$cache[ $i['g_id'] ] = IPSMember::unpackGroup( $i );
		}
		
		$this->cache->setCache( 'group_cache', $cache, array( 'array' => 1 ) );
	}
}


?>