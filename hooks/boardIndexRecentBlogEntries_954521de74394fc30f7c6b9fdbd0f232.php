<?php
/**
 * @file		boardIndexRecentBlogEntries.php 	Adds a sidebar block with the most recent blog entries
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		17 May 2011
 * $LastChangedDate: 2011-05-18 04:49:10 -0400 (Wed, 18 May 2011) $
 * @version		v2.5.2
 * $Revision: 8812 $
 */

/**
 * @class		boardIndexRecentBlogEntries
 * @brief		Adds a sidebar block with the most recent blog entries
 */
class boardIndexRecentBlogEntries
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 */
	public $registry;
	
	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
	}
	
	public function getOutput()
	{
		/* Init vars */
		$output = '';
		
		if ( IPSLib::appIsInstalled('blog') )
		{
			/* Require our gateway */
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/hooks/gateway.php', 'blog_hookGateway', 'blog' );
			$hookGateway = new $classToLoad( $this->registry );
			
			$output = $hookGateway->recentEntries();
		}
		
		return $output;
	}	
}