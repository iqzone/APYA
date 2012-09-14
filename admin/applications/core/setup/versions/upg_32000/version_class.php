<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Upgrade Class
 *
 * Class to add options and notices for IP.Board upgrade
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 * 
 * @author		Matt Mecham <matt@invisionpower.com>
 * @version		$Rev: 10721 $
 * @since		3.0
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @link		http://www.invisionpower.com
 * @package		IP.Board
 */ 

class version_class_core_32000
{
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Add pre-upgrade options: Form
	 * 
	 * @return	string	 HTML block
	 */
	public function preInstallOptionsForm()
	{
		$_wrapper	= "<ul>%s</ul>";
		$_html		= '';
		$posts		= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'posts' ) );
		
		/* Got more than 100K posts? */
		if( $posts['total'] > 100000 )
		{
$_html	.= <<<EOF
		<li>
			<input type='checkbox' name='manualPostsTableQuery' value='1' checked='checked' />
			Manually apply changes to the posts table?  Your site has more than 100,000 posts.  We <b>strongly recommend</b> that you enable this option and manually run the provided 
			SQL query to alter your posts table in order to prevent timeouts in the web-based upgrader.  If you uncheck this option, it is very possible the upgrader will timeout attempting to
			update your posts table.
		</li>
EOF;
		}

		if( $_html )
		{
			return sprintf( $_wrapper, $_html );
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Add pre-upgrade options: Save
	 *
	 * Data will be saved in saved data array as: appOptions[ app ][ versionLong ] = ( key => value );
	 * 
	 * @return	array	 Key / value pairs to save
	 */
	public function preInstallOptionsSave()
	{
		return array( 'manualPostsTableQuery'	=> intval( $_REQUEST['manualPostsTableQuery'] )
					);
	}
	
	/**
	 * Return any post-installation notices
	 * 
	 * @return	array	 Array of notices
	 */
	public function postInstallNotices()
	{
		return array();
	}
	
	
	/**
	 * Return any pre-installation notices
	 * 
	 * @return	array	 Array of notices
	 */
	public function preInstallNotices()
	{
		$notices = array();
		
		$notices[] = "All XML skins will be uninstalled during the upgrade.";
		
		return $notices;
		
	}
}
	
