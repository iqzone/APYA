<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Tagging: Cloud class - is it bird? is it a cloud? No it's a.. nope, it's a cloud. Sorry for that.
 * Matt Mecham
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2011 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		24 Feb 2011
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class classes_tags_cloud
{
	/**#@+
	 * Registry objects
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

	/**#@+
	 * Internally stored configuration strings
	 *
	 * @var	mixed
	 */
	protected $app  = '';
	protected $area = '';
	protected $errorMsg = '';
	protected $relId    = 0;
	protected $parentId = 0;
	protected $skinGroup    = 'global_other';
	protected $skinTemplate = 'tagCloud';
	/**#@-*/

	/**
	 * Array of loaded plugin classes
	 *
	 * @var	array
	 */
	protected $plugins	= array();
	
	/**
	 * Retrieve the error message
	 *
	 * @return	@e string
	 */
	public function getErrorMsg()
	{
		return $this->errorMsg;
	}

	/**
	 * Set the error message
	 *
	 * @param	string	$errorMsg	Error message
	 * @return	@e void
	 */
	public function setErrorMsg( $errorMsg )
	{
		$this->errorMsg = $errorMsg;
	}

	/**
	 * Return the app
	 *
	 * @return	@e string
	 */
	public function getApp()
	{
		return $this->app;
	}

	/**
	 * Return the area
	 *
	 * @return	@e string
	 */
	public function getArea()
	{
		return $this->area;
	}

	/**
	 * Set the app
	 *
	 * @param	string	$app	App key
	 * @return	@e void
	 */
	public function setApp( $app )
	{
		$this->app = $app;
	}

	/**
	 * Set the area
	 *
	 * @param	string	$area	Area
	 * @return	@e void
	 */
	public function setArea( $area )
	{
		$this->area = $area;
	}
	
	
	/**
	 * Return the relational id
	 *
	 * @return	@e integer
	 */
	public function getRelId()
	{
		return $this->relId;
	}

	/**
	 * Set the relational ID
	 *
	 * @param	integer	$relId	Rel ID
	 */
	public function setRelId( $relId )
	{
		$this->relId = intval( $relId );
	}

	/**
	 * Return the parent ID
	 *
	 * @return	@e integer
	 */
	public function getParentId()
	{
		return $this->parentId;
	}

	/**
	 * Set the parent ID
	 *
	 * @param	integer	$parentId	Parent ID
	 */
	public function setParentId( $parentId )
	{
		$this->parentId = intval( $parentId );
	}

	/**
	 * Return the skin group to use
	 *
	 * @return	@e string
	 */
	public function getSkinGroup()
	{
		return $this->skinGroup;
	}

	/**
	 * Set the skin group to use
	 *
	 * @param	string	$skinGroup	Skin group
	 * @return	@e void
	 */
	public function setSkinGroup( $skinGroup )
	{
		$this->skinGroup = $skinGroup;
	}

	/**
	 * Get the skin template to use
	 *
	 * @return	@e string
	 */
	public function getSkinTemplate()
	{
		return $this->skinTemplate;
	}

	/**
	 * Set the skin template to use
	 *
	 * @param	string	$skinTemplate	Skin template
	 * @return	@e void
	 */
	public function setSkinTemplate( $skinTemplate )
	{
		$this->skinTemplate = $skinTemplate;
	}

	/**
	 * Is tagging enabled?
	 * 
	 * @return 	boolean
	 */
	protected function _isEnabled()
	{
		return ( $this->settings['tags_enabled'] ) ? true : false;	
	}
	
	/**
	 * Setup registry classes
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
		
		/* Clean member perm array */
		foreach( $this->member->perm_id_array as $k => $v )
		{
			if ( empty( $v ) )
			{
				unset( $this->member->perm_id_array[ $k ] );
			}
		}
		
		if ( ! $this->getApp() )
		{
			/* We set the app in the URL now so don't need to limit cloud by app */
			//$this->setApp( IPS_DEFAULT_APP );
		}
	}
	
	/**
	 * Render the cloud and return the HTML
	 *
	 * @param	array	$data	returned from getCloudData
	 * @return	@e string
	 */
	public function render( array $data )
	{
		$group = $this->getSkinGroup();
		$templ = $this->getSkinTemplate();
			
		/* Some basic formatt-ing */
		foreach( $data['tags'] as $id => $val )
		{
			$app = ( $val['app'] ) ? $val['app'] : IPS_DEFAULT_APP;
			
			$data['tags'][ $id ]['className']  = $this->_weightToClassName( $val['weight'] );
			$data['tags'][ $id ]['tagWithUrl'] = $this->registry->output->getTemplate('global_other')->tagEntry( $val['tag'], true, $app, $val['section'] );
		}
		
		return $this->registry->output->getTemplate( $group )->$templ( $data );
	}
	
	/**
	 * Determine the CSS class name to use based on the weight
	 *
	 * @param	float	$weight	Weight
	 * @return	@e string
	 */
	protected function _weightToClassName( $weight )
	{
		$className = 'ipsTagWeight_1';
		
		if ( $weight < 0.9 )
		{
			$className = 'ipsTagWeight_2';
		}
		
		if ( $weight < 0.8 )
		{
			$className = 'ipsTagWeight_3';
		}
		
		if ( $weight < 0.7 )
		{
			$className = 'ipsTagWeight_4';
		}
	
		if ( $weight < 0.6 )
		{
			$className = 'ipsTagWeight_5';
		}
	
		if ( $weight < 0.5 )
		{
			$className = 'ipsTagWeight_6';
		}
	
		if ( $weight < 0.4 )
		{
			$className = 'ipsTagWeight_7';
		}
	
		if ( $weight < 0.3 )
		{
			$className = 'ipsTagWeight_8';
		}
		
		return $className;
	}
	
	/**
	 * getCloudData
	 * 
	 * Filters:
	 * limit		Limit the number of tags returned
	 * visible		The number of visible tags
	 * noCache		Bypass any caching and always fetch fresh
	 * 
	 * @param	array	Filters (limit, visible)
	 * @return	@e array
	 */
	public function getCloudData( $filters=array() )
	{
		/* INIT */
		$where = array();
		$data  = array();
		$raw   = array();
		$nums  = array( 'min' => 0, 'max' => 0, 'count' => 0 );
		$final = array( 'tags' => array(), 'nums' => array() );
		
		/* Clean up filters */
		$filters['limit']   = intval( $filters['limit'] );
		$filters['offset']  = intval( $filters['offset'] );
		
		$filters['visible'] = ( ! isset( $filters['visible'] ) ) ? 1 : intval( $filters['visible'] );
		
		if ( $this->getApp() && $this->getArea() && $this->getRelId() )
		{
			$where[] = "t.tag_aai_lookup='" . $this->_getKey( array( 'meta_id' => $this->getRelId() ) ) . "'";
		}
		else if ( $this->getApp() && $this->getArea() && $this->getParentId() )
		{
			$where[] = "t.tag_aap_lookup='" . $this->_getKey( array( 'meta_parent_id' => $this->getParentId() ) ) . "'";
		}
		
		if ( $this->getApp() )
		{
			$where[] = "t.tag_meta_app='" . $this->DB->addSlashes( $this->getApp() ) . "'";
		}
		
		if ( $this->getArea() )
		{
			$where[] = "t.tag_meta_area='" . $this->DB->addSlashes( $this->getArea() ) . "'";
		}

		/* Test against cache class */
		if ( empty( $filters['noCache'] ) )
		{
			$cacheKey = 'tagCloud-' . md5( implode( '&', $where ) );
			$cached   = $this->registry->getClass('cacheSimple')->get( $cacheKey );
			
			if ( $cached !== null && is_array( $cached['data'] ) )
			{
				$cached['data']['cached'] = $cached['time'];
				
				return $cached['data'];
			}
		}
				
		/* Still here? Fetch from the database */
		$this->DB->allow_sub_select = true;
		$this->DB->loadCacheFile( IPSLib::getAppDir('core') . '/sql/' . ips_DBRegistry::getDriverType() . '_tag_queries.php', 'public_tag_sql_queries' );
		$this->DB->buildFromCache( 'getCloudData', array( 'where' => $where, 'limit' => array( $filters['offset'], $filters['limit'] ) ), 'public_tag_sql_queries' );		
		$o = $this->DB->execute();
		
		while( $tag = $this->DB->fetch() )
		{
			$raw[ $tag['times'] . '.' . md5( $tag['tag_text'] ) ] = $tag;
			
			if ( empty( $nums['min'] ) OR $nums['min'] > $tag['times'] )
			{
				$nums['min'] = $tag['times'];
			}
			
			if ( $nums['max'] < $tag['times'] )
			{
				$nums['max'] = $tag['times'];
			}
			
			$nums['count'] += $tag['times'];
		}
		
		/* Sort it */
		krsort( $raw );
		
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/

		/* Now give out some useful data */
		foreach( $raw as $key => $data )
		{
			/* Get plugin class to work out 'section' */
			if( !isset($this->plugins[ $data['tag_meta_area'] ]) )
			{
				$this->plugins[ $data['tag_meta_area'] ]	= classes_tags_bootstrap::run( $data['tag_meta_app'], $data['tag_meta_area'] );
			}

			/* Section */
			$section	= $this->plugins[ $data['tag_meta_area'] ]->getSearchSection();

			/* Work out a percentage */
			$percent = sprintf( '%.2F', $data['times'] / $nums['max'] * 100 );
			
			$final['tags'][] = array( 'tag'     => $data['tag_text'],
							  		  'count'   => $data['times'],
							  		  'app'		=> $data['tag_meta_app'],
							  		  'section'	=> $section,
							  	  	  'percent' => $percent,
									  'weight'  => sprintf( '%.2F', $percent / 100 ) );
		}
		
		$final['nums'] = $nums;
		
		/* Cache */
		$this->registry->getClass('cacheSimple')->set( $cacheKey, $final );
		
		return $final;
	}
	
	/**
	 * Build a key
	 *
	 * @param	array	$where	Params
	 * @return	@e string
	 */
	private function _getKey( $where )
	{		
		if ( isset( $where['meta_id'] ) )
		{
			return md5( $this->getApp() . ';' . $this->getArea() . ';' . intval( $where['meta_id'] ) );
		}
		else if ( isset( $where['meta_parent_id'] ) )
		{
			return md5( $this->getApp() . ':' . $this->getArea() . ':' . intval( $where['meta_parent_id'] ) );
		}
	}	
}