<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Navigation Builder
 * Owner: Matt Mecham
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		17th March 2011
 * @version		$Revision: 10721 $
 */

	 
class classes_navigation_build
{
	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $DB;
	protected $app;
	protected $extension;
	
	/**
	 * Method constructor
	 *
	 * @access	public
	 * @param	string		Application
	 * @return	@e void
	 * 
	 */
	public function __construct( $app='core' )
	{
		/* Make object */
		$this->registry   = ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Set local data */
		$this->setApp( $app );
		
		/* Load application file */
		$this->_loadExtension();
	}
	
	/**
	 * @return the $extension
	 */
	public function getExtension()
	{
		return $this->extension;
	}

	/**
	 * @param field_type $extension
	 */
	public function setExtension( $extension )
	{
		$this->extension = $extension;
	}

	/**
	 * @return the $app
	 */
	public function getApp()
	{
		return $this->app;
	}

	/**
	 * @param field_type $app
	 */
	public function setApp( $app )
	{
		$this->app = $app;
	}
	
	/**
	 * Loads the navigationd data ...
	 */
	public function loadApplicationTabs()
	{
		/* ipsRegistry::$applications only contains apps with a public title #15785 */
		$appCache = ipsRegistry::cache()->getCache('app_cache');
		$tabs     = array();
		
		/* Loop through applications */
		foreach( $appCache as $app_dir => $app )
		{
			/* Only if app enabled... */
			if ( $app['app_enabled'] )
			{
				/* Setup */
				$_file  = IPSLib::getAppDir( $app['app_directory'] ) . '/extensions/navigation/generate.php';
				$_class	= 'navigation_' . $app['app_directory'];
				
				/* Current app? */
				if ( $app['app_directory'] == $this->getApp() )
				{
					$tabs[ $app['app_directory'] ] = $this->getExtension()->getTabName();
					continue;
				}
				
				/* Check for the file */
				if ( is_file( $_file ) )
				{
					/* Get the file */
					$__class = IPSLib::loadLibrary( $_file, $_class, $app['app_directory'] );
					
					/* Check for the class */
					if ( class_exists( $__class ) )
					{
						/* Create an object */
						$_obj = new $__class();
						
						/* Check for the module */
						$tabs[ $app['app_directory'] ] = $_obj->getTabName();
					}
				}
			}
		}
		
		return $tabs;
	}
	
	/**
	 * Loads the navigationd data ...
	 */
	public function loadNavigationData()
	{
		return $this->getExtension()->getNavigationData();
	}
	
	/**
	 * Loads the current extension
	 */
	protected function _loadExtension()
	{
		/* Pointless comment! */
		$_file	= IPSLib::getAppDir( $this->getApp() ) . '/extensions/navigation/generate.php';
		$_class	= 'navigation_' . $this->getApp();
		
		/* Otherwise create object and cache */
		if ( is_file( $_file ) )
		{
			$classToLoad = IPSLib::loadLibrary( $_file, $_class, $this->getApp() );
			
			if ( class_exists( $classToLoad ) )
			{
				$this->setExtension( new $classToLoad() );
			}
			else
			{
				$this->setApp('core');
				$this->_loadExtension();
			}
		}
		else
		{
			$this->setApp('core');
			$this->_loadExtension();
		}
	}
	
}