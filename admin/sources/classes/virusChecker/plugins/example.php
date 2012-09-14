<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Virus scanner: Plugin example.
 * The plugin system can be used to extend the virus scanner functionality by checking for arbitrary 
 * things to score the virus rating against.  See the two links below for more ideas/suggestions.
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		Tue. 17th August 2004
 * @version		$Rev: 10721 $
 * 
 * @link 		http://forums.invisionpower.com/index.php?autocom=tracker&showissue=8452
 * @link		http://forums.invisionpower.com/index.php?autocom=tracker&showissue=8453
 */
class virusScannerPlugin_example
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
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
	
	/**
	 * Constructor
	 * 
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->DB       = $registry->DB();
		$this->settings = $registry->settings();
		$this->member   = $registry->member();
		$this->memberData =& $registry->member()->fetchMemberData();
		$this->cache    = $registry->cache();
		$this->caches   =& $registry->cache()->fetchCaches();
		$this->request  = $registry->request();
	}
	
	/**
	 * Run scorer
	 * 
	 * @access	public
	 * @param	string		This is the full path to the file currently being scanned
	 * @return	integer		Number of points to add to the score.
	 */
	public function run( $filepath )
	{
		return 0;
	}
}