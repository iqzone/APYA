<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Profile Plugin Library
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

abstract class profile_plugin_parent
{
	/**
	 * Registry object
	 *
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @var		object
	 */	
	protected $DB;
	
	/**
	 * Settings object
	 *
	 * @var		object
	 */	
	protected $settings;
	
	/**
	 * Request object
	 *
	 * @var		object
	 */	
	protected $request;
	
	/**
	 * Language object
	 *
	 * @var		object
	 */	
	protected $lang;
	
	/**
	 * Member object
	 *
	 * @var		object
	 */	
	protected $member;
	protected $memberData;
	
	/**
	 * Cache object
	 *
	 * @var		object
	 */	
	protected $cache;
	protected $caches;
		
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Abstract :: return HTML block
	 *
	 * @param	array		Member information
	 * @return	string		HTML block
	 */
	abstract public function return_html_block( $member=array() );
}