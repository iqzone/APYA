<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Global Search
 * Last Updated: $Date: 2012-05-18 13:26:29 -0400 (Fri, 18 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10768 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class classes_tags_search_sphinx
{
	
	
	/**
	 * Sphinx client object
	 *
	 * @access	public
	 * @var		object
	 */
	public $sphinxClient;
	
	/**
	 * Setup registry objects
	 *
	 * @access	public
	 * @param	object	ipsRegistry $registry
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make object */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Do we have the sphinxes? */
		if( ! is_file( 'sphinxapi.php' ) )
		{
			$this->registry->output->showError( 'sphinx_api_missing', 10182 );
		}

		/* Load Sphinx */
		require_once( 'sphinxapi.php' );/*noLibHook*/
		$this->sphinxClient = new SphinxClient();

		$this->sphinxClient->SetServer( $this->settings['search_sphinx_server'], intval( $this->settings['search_sphinx_port'] ) );
		$this->sphinxClient->SetMatchMode( SPH_MATCH_EXTENDED );
		$this->sphinxClient->SetLimits( 0, 1000 );
	}
	
	/**
	 * Perform the search
	 * @param array $tags
	 * @param array $options
	 */
	public function run( array $tags, array $options )
	{
		$order     = ( ! empty( $options['sortKey'] ) )   ? $options['sortKey']   : 'search_id';
		$dir       = ( ! empty( $options['sortOrder'] ) ) ? $options['sortOrder'] : 'desc';
		$return    = array();
		$query 	   = '';
		$searchIds = array();
		
		/* Format query */
		if ( ! empty( $options['meta_parent_id'] ) )
		{
			$this->sphinxClient->SetFilter( 'tag_meta_parent_id', ( is_array( $options['meta_parent_id'] ) ? IPSLib::cleanIntArray( $options['meta_parent_id'] ) : $options['meta_parent_id'] ) );
		}
		
		if ( ! empty( $options['meta_id'] ) )
		{
			$this->sphinxClient->SetFilter( 'tag_meta_id', ( is_array( $options['meta_id'] ) ? IPSLib::cleanIntArray( $options['meta_id'] ) : $options['meta_id'] ) );
		}
		
		if ( isset( $options['meta_app'] ) )
		{
			$query .= ' @tag_meta_app ' . ( ( is_array( $options['meta_app'] ) ) ? implode( "|", $options['meta_app'] ) : $options['meta_app'] ) . '';
		}
		
		if ( isset( $options['meta_area'] ) )
		{
			if( is_array( $options['meta_area'] ) )
			{
				$_areas	= array();

				foreach( $options['meta_area'] as $v )
				{
					$_areas[]	= str_replace( '-', '_', $v );
				}

				$options['meta_area']	= $_areas;
			}

			$query .= ' @tag_meta_area ' . ( ( is_array( $options['meta_area'] ) ) ? implode( "|", $options['meta_area'] ) : str_replace( '-', '_', $options['meta_area'] ) ) . '';
		}
		
		if ( ! empty( $options['not_meta_id'] ) )
		{
			$this->sphinxClient->SetFilter( 'tag_meta_id', ( is_array( $options['not_meta_id'] ) ? IPSLib::cleanIntArray( $options['not_meta_id'] ) : array( $options['not_meta_id'] ) ), true );
		}
		
		if ( isset( $tags ) )
		{
			if ( isset( $options['match'] ) AND $options['match'] == 'loose' )
			{
				$query .= ' @tag_text (' . ( ( is_array( $tags ) ) ? implode( " | ", $tags ) : $tags ) . ')';
			}
			else
			{
				$query .= ' @tag_text ^' . ( ( is_array( $tags ) ) ? implode( "$ | ^", $tags ) : $tags ) . '$';
			}
		}
		
		/* Did we add in perm check? */
		if ( ! empty( $options['isViewable'] ) )
		{
			$query .= ' @tag_perm_text ,"' . implode( '," | ",', $this->member->perm_id_array ) . ',"';
			
			$this->sphinxClient->SetFilter( 'tag_perm_visible', array( 1 ) );
		}
		
		/* Sort */
		if ( $dir == 'asc' )
		{
			$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_ASC, str_replace( 'tg.', '', $order ) );
		}
		else
		{
			$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_DESC, str_replace( 'tg.', '', $order ) );
		}
		
		/* Limit Results */
		if ( ! empty( $options['limit'] ) || ! empty( $options['offset'] ) )
		{
			$this->sphinxClient->SetLimits( intval( $options['offset'] ), intval( $options['limit'] ) );
		}

		/* run it */
		$result = $this->sphinxClient->Query( $query, $this->settings['sphinx_prefix'] . 'core_tags_search_main,' . $this->settings['sphinx_prefix'] . 'core_tags_search_delta' );

		$this->logSphinxWarnings();
		
		/* Check matches and fetch data */
		if ( is_array( $result['matches'] ) && count( $result['matches'] ) )
		{
			foreach( $result['matches'] as $res )
			{
				$searchIds[] = $res['attrs']['search_id'];
			}
		}
		
		if ( count( $searchIds ) )
		{
			/* Fetch */
			if ( count( $options['joins'] ) )
			{
				$this->DB->build( array( 'select'   => 'tg.*',
										 'from'	    => array( 'core_tags' => 'tg' ),
										 'where'    => 'tg.tag_id IN(' . implode( ",", $searchIds ) . ')',
										 'add_join' => $options['joins'],
										 'order'    => str_replace( 'search_id', 'tag_id', $order ) . ' ' . $dir ) );
			}
			else
			{
				$this->DB->build( array( 'select'   => '*',
										 'from'	    => 'core_tags',
										 'where'    => 'tag_id IN(' . implode( ",", $searchIds ) . ')',
										 'add_join' => $options['joins'],
										 'order'    => str_replace( 'search_id', 'tag_id', $order ) . ' ' . $dir ) );
			}
			
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$return[ $row['tag_id'] ] = $row;
			}
		}
		
		return $return;
	}
	
	/**
	 * Checks and logs any errors
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function logSphinxWarnings()
	{	
		$error   = $this->sphinxClient->GetLastError();
		$warning = $this->sphinxClient->GetLastWarning();
		
		if ( $error )
		{
			IPSDebug::addLogMessage( "Sphinx Error: $error", 'sphinx_error_' . date('m_d_y'), $error, TRUE );
		}
		
		if ( $warning )
		{
			IPSDebug::addLogMessage( "Sphinx Warning: $warning", 'sphinx_warning_' . date('m_d_y'), $warning, TRUE );
		}
	}
}