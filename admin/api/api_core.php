<?php
/**
 * @file		api_core.php 	Core functions for the API classes
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2011-03-18 17:34:15 -0400 (Fri, 18 Mar 2011) $
 * @version		v3.3.3
 * $Revision: 8129 $
 */

/**
 *
 * @class		apiCore
 * @brief		Core functions for the API classes
 */
class apiCore
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
	 * Array of the API errors
	 *
	 * @var		$api_error
	 */
	public $api_error = array();

	/**
	 * API Path to IPB root (where initdata.php/index.php is)
	 *
	 * @var 	$path_to_ipb
	 */
	public $path_to_ipb = '';

	/**
	 * Loads the registry class
	 *
	 * @return @e void
	 */
	public function init()
	{
		/* Path not set? */
		if( !$this->path_to_ipb )
		{
			/* Constant available? */
			if( defined('DOC_IPS_ROOT_PATH') )
			{
				$this->path_to_ipb	= DOC_IPS_ROOT_PATH;
			}
			else
			{
				/* Fallback.. */
				$this->path_to_ipb = dirname(__FILE__) . '/../../';
			}
		}
		
		/* Load the registry */
		require_once( $this->path_to_ipb . 'initdata.php' );/*noLibHook*/
		require_once( $this->path_to_ipb . CP_DIRECTORY . '/sources/base/ipsRegistry.php' );/*noLibHook*/
		
		$this->registry = ipsRegistry::instance();
		$this->registry->init();
		
		/* Make registry shortcuts */
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* INIT Child? */
		if( method_exists( $this, 'childInit' ) )
		{
			$this->childInit();
		}
	}
}