<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Library for reported content
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class reportLibrary
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
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
	
	/**
	 * Group Ids
	 *
	 * @var		array
	 */	
	protected $member_group_ids;
	
	/**
	 * Array of plugin objects
	 *
	 * @var		array
	 */	
	public $plugins;
	
	/**
	 * Status for new reports
	 *
	 * @var		integer
	 */	
	public $report_is_new		= 0;
	
	/**
	 * Status for complete reports
	 *
	 * @var		integer
	 */	
	public $report_is_complete	= 0;
	
	/**
	 * Cache of status/flag images
	 *
	 * @var		array
	 */	
	public $flag_cache			= array();
	
	/**
	 * Cache of HTML status dropdown
	 *
	 * @var		string
	 */	
	public $flag_body			= '';

	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make object
		//-----------------------------------------
		
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   	= $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		$this->member_group_ids	= array( $this->memberData['member_group_id'] );
		$this->member_group_ids	= array_diff( array_merge( $this->member_group_ids, explode( ',', $this->memberData['mgroup_others'] ) ), array('') );
	}
	
	/**
	 * Recache report center plugins
	 *
	 * @return	@e void
	 */
	public function rebuildReportCache()
	{
		$_classes	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'rc_classes', 'where' => 'onoff=1' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			if( IPSLib::appIsInstalled( $r['app'] ) )
			{
				$_classes[ $r['my_class'] ]	= $r;
			}
		}
		
		$this->cache->setCache( 'report_plugins' , $_classes, array( 'array' => 1 ) );
	}

	/**
	 * Returns the correct status icon / flag to display for a report row
	 *
	 * @param	array    Report Row
	 * @return	string
	 */
	public function buildStatusIcon( $row )
	{
		$this->buildStatuses( true );
		
		//-----------------------------------------
		// Pick the right flag.. or else!
		//-----------------------------------------

		$row['img']		= str_replace( '<#IMG_DIR#>', $this->registry->output->skin['set_image_dir'], $this->flag_cache[ $row['status'] ][ $row['points'] ]['img'] );
		$row['width']	= $this->flag_cache[ $row['status'] ][ $row['points'] ]['width'];
		$row['height']	= $this->flag_cache[ $row['status'] ][ $row['points'] ]['height'];
		$row['is_png']	= $this->flag_cache[ $row['status'] ][ $row['points'] ]['is_png'];

		//-----------------------------------------
		// Image? PNG? Using 'Is-Evil' machine?
		//-----------------------------------------
		
		if( $row['img'] != '' )
		{
			return $this->registry->getClass('output')->getTemplate('reports')->statusIcon( $row['img'], $row['width'], $row['height'] );
		}
		else
		{
			return '&nbsp;';
		}
	}

	/**
	 * Rebuild the member cache array if it is outdated
	 *
	 * @return	integer		New 'total reports' count
	 */
	public function rebuildMemberCacheArray()
	{
		$this->DB->loadCacheFile( IPSLib::getAppDir('core') . '/sql/' . ips_DBRegistry::getDriverType() . '_report_queries.php', 'report_sql_queries' );

		$class_perm = $this->buildQueryPermissions();
		
		$total = $this->DB->buildAndFetch( array(
														'select'	=> 'COUNT(*) as reports',
														'from'		=> array( 'rc_reports_index' => 'rep' ),
														'where'		=> $class_perm . " AND stat.is_active=1",
														'add_join'	=> array(
																			array(
																				'from'	=> array( 'rc_classes' => 'rcl' ),
																				'where'	=> 'rcl.com_id=rep.rc_class'
																				),
																			array(
																				'from'	=> array( 'rc_status' => 'stat' ),
																				'where'	=> 'stat.status=rep.status'
																				),
																			)
												)		);
		
		$reports_by_plugin = array();
		
		$this->DB->build( array(
									'select'	=> 'rep.id, rep.title, rep.num_reports, rep.exdat1, rep.exdat2, rep.exdat3',
									'from'		=> array( 'rc_reports_index' => 'rep' ),
									'where'		=> $class_perm . " AND (stat.is_active=1 OR stat.is_new=1) AND rcl.onoff=1",
									'order'		=> 'stat.is_new ASC',
									'add_join'	=> array(
														array(
															'select'	=> 'stat.is_active, stat.is_new',
															'from'		=> array( 'rc_status' => 'stat' ),
															'where'		=> 'stat.status=rep.status'
															),
														array(
															'select'	=> 'rcl.com_id, rcl.com_id, rcl.my_class, rcl.extra_data, rcl.app',
															'from'		=> array( 'rc_classes' => 'rcl' ),
															'where'		=> 'rcl.com_id=rep.rc_class'
															),

														)
							)		);
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			if( $row['my_class'] != '' )
			{
				$reports_by_plugin[ $row['app'] ][ $row['my_class'] ][] = $row;
			}
		}
		
		$build_member_cache_array['report_temp'] = array();
		
		foreach( $reports_by_plugin as $app => $plugins )
		{
			if( IPSLib::appIsInstalled( $app ) )
			{
				foreach( $plugins as $plugin_name => $reports_array )
				{
					$this->loadPlugin( $plugin_name, $app );
					$this->plugins[ $plugin_name ]->updateReportsTimestamp( $reports_array, $build_member_cache_array );
				}
			}
		}
		
		$build_member_cache_array['report_last_updated']	= time();
		$build_member_cache_array['report_num']				= $total['reports'];
		
		if( count($build_member_cache_array) > 0 )
		{
			IPSMember::packMemberCache( $this->memberData['member_id'], $build_member_cache_array );
		}
	
		return $total['reports'];
	}
	
	/**
	 * Builds permissions for several sql queries for various functions
	 *
	 * @param	string	based on mods or users
	 * @return	string
	 */
	public function buildQueryPermissions( $check='mod' )
	{
		//-----------------------------------------
		// Are we checking user or mod permissions?
		//-----------------------------------------
		
		if( $check == 'mod' )
		{
			$col = 'mod_group_perm';
		}
		else
		{
			$col = 'group_can_report';
		}

		//-----------------------------------------
		// Get components we have access to...
		//-----------------------------------------
		
		foreach( $this->cache->getCache('report_plugins') as $_className => $row )
		{
			if( !IPSMember::isInGroup( $this->memberData, explode( ',', IPSText::cleanPermString( $row[ $col ] ) ) ) )
			{
				continue;
			}

			$spec_perm = '';

			$this->loadPlugin( $row['my_class'], $row['app'] );
			
			if( !$this->plugins[ $row['my_class'] ] )
			{
				continue;
			}
			
			if( $row['extra_data'] && $row['extra_data'] != 'N;' )
			{
				$this->plugins[ $row['my_class'] ]->_extra = unserialize( $row['extra_data'] );
			}
			else
			{
				$this->plugins[ $row['my_class'] ]->_extra = array();
			}

			if( $this->plugins[ $row['my_class'] ]->getReportPermissions( $check, $row, $this->member_group_ids, $spec_perm ) )
			{
				$cids[ $row['com_id'] ] = $spec_perm;
			}
		}

		return $this->DB->fetchLoadedClass('report_sql_queries')->join_com_permissions( array( 'NOTCACHE' => 1, 'COMS' => $cids ) );
	}
	
	/**
	 * Check permissions for "can report"
	 *
	 * @param	string	$className	Report center class name
	 * @return	@e bool
	 */
	public function canReport( $className )
	{
		if( !$this->caches['report_plugins'][ $className ]['com_id'] )
		{
			return false;
		}
		
		if( IPSMember::isInGroup( $this->memberData, explode( ',', IPSText::cleanPermString( $this->caches['report_plugins'][ $className ]['group_can_report'] ) ) ) )
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Loads plugins into $this->plugins object for later use
	 *
	 * @param	string	$plugin_name	plugin name (>name<.php)
	 * @param	string	$app			App name
	 * @return	@e void
	 */
	public function loadPlugin( $plugin_name, $app )
	{
		if( ! $this->plugins[ $plugin_name ] )
		{
			if( IPSLib::appIsInstalled( $app ) AND is_file( IPSLib::getAppDir( $app ) . '/extensions/reportPlugins/' . $plugin_name . '.php' ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( $app ) . '/extensions/reportPlugins/' . $plugin_name . '.php', $plugin_name . '_plugin', $app );
				
				$this->plugins[ $plugin_name ] = new $classToLoad( $this->registry );
			}
		}
	}
	
	/**
	 * Process a URL
	 *
	 * @param	string	URL
	 * @param	string	FURL Title
	 * @param	string	FURL Template
	 * @return	string	Full URL if existing URL was short
	 */
	public function processUrl( $url, $friendlyTitle='', $friendlyTemplate='' )
	{
		if( $url && ! preg_match( "/(http|https)\:\/\//", $url ) )
		{
			$returnUrl	= str_replace( '/index.php?', '', $url );
			$returnUrl	= $this->registry->output->buildSEOUrl( $returnUrl, 'public', $friendlyTitle, $friendlyTemplate );
			
			return $returnUrl;
		}
		else
		{
			return $url;
		}
	}
	
	/**
	 * Fixes the member's RSS Key if none set
	 *
	 * @return	@e void
	 */
	public function checkMemberRSSKey()
	{
		if( ! $this->memberData['_cache']['rc_rss_key'] )
		{
			$new_rss_key = md5( uniqid( microtime(), true ) );
			
			$this->DB->build( array( 'select' => '*', 'from' => 'rc_modpref', 'where' => "mem_id=" . $this->memberData['member_id'] ) );
			$this->DB->execute();
		
			if( $this->DB->getTotalRows() == 1 )
			{
				$this->DB->update( 'rc_modpref', array( 'rss_key' => $new_rss_key ), "mem_id=" . $this->memberData['member_id'] );
			}
			else
			{
				$this->DB->insert( 'rc_modpref', array( 'rss_key' => $new_rss_key, 'mem_id' => $this->memberData['member_id'], 'rss_cache' => '' ) );
			}
			
			$memberCache				= $this->memberData['_cache'];
			$memberCache['rc_rss_key']	= $new_rss_key;
			$this->member->setProperty( '_cache', $memberCache );

			IPSMember::packMemberCache( $this->memberData['member_id'], array( 'rc_rss_key' => $new_rss_key ) );
		}
	}
	
	/**
	 * Generates report form HTML
	 *
	 * @param	string	Title - What is being reported
	 * @param	string	URL - what the user can click on (title)
	 * @param	array	Extra data passed on to the form for processing
	 * @return	string
	 */
	public function showReportForm( $name, $url, $ex_data=array() )
	{
		$extra_input = '';

		if( is_array( $ex_data ) && count( $ex_data ) > 0 )
		{
			foreach( $ex_data as $bname => $value )
			{
				$extra_input .= "<input type='hidden' name='{$bname}' value='{$value}' />";
			}
		}
		
		return $this->registry->getClass('output')->getTemplate('reports')->basicReportForm( $name, $url, $extra_input );
	}
	
	/**
	 * Updates global 'cache time' which forces 'mod caches' to re-cache
	 *
	 * @return	@e void
	 */
	public function updateCacheTime()
	{
		$cache					= $this->cache->getCache('report_cache');
		$cache['last_updated']	= time();

		$this->cache->setCache( 'report_cache', $cache, array( 'array' => 1, 'donow' => 1 ) );
	}
	
	/**
	 * Builds the status information (and maybe drop down html)
	 *
	 * @param	boolean		Do we need the drop down?
	 * @return	string 		HTML body
	 */
	public function buildStatuses( $ignore_html = false )
	{
		if( !$this->flag_cache )
		{
			$statuses	= array();
			$stat_set	= array();
			$this->body	= '';
	
			$this->DB->build( array( 'select'	=> 'stat.status, stat.title, stat.is_new, stat.is_complete, stat.rorder',
									 'from'		=> array( 'rc_status' => 'stat' ),
									 #'order'	=> 'stat.rorder ASC, star.points ASC', # Temp table + filesort, replaced with PHP code
									 'add_join'	=> array( array( 'select'	=> 'star.img, star.width, star.height, star.points, star.is_png',
																 'from'		=> array( 'rc_status_sev' => 'star' ),
																 'where'	=> 'stat.status=star.status' ) )
							 )		);
			$this->DB->execute(); 
	
			while( $row = $this->DB->fetch() )
			{
				$statuses[ $row['rorder'] ][ $row['points'] ] = $row;
			}
			
			ksort( $statuses, SORT_NUMERIC );
			
			/* Now that we're in order loop our statuses */
			foreach( $statuses as $sid => $rows )
			{
				ksort( $rows, SORT_NUMERIC );
				
				foreach( $rows as $row )
				{
					if( empty($stat_set[ $row['status'] ]) )
					{
						$this->body .= "<option value='{$row['status']}'>{$row['title']}</option>";
		
						if( $row['is_new'] == 1 )
						{
							$this->report_is_new = $row['status'];
						}
						elseif( $row['is_complete'] == 1 )
						{
							$this->report_is_complete = $row['status'];
						}
					}
		
					$stat_set[ $row['status'] ] = 1;
					
					$this->flag_cache[ $row['status'] ][ $row['points'] ] = array( 'img'    => $row['img'],
																				   'width'  => $row['width'],
																				   'height' => $row['height'],
																				   'is_png' => $row['is_png'],
																				   'title'  => $row['title'],
																				  );
				}
			}
		}

		return $ignore_html ? '' : $this->body;
	}
}