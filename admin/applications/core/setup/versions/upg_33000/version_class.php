<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Upgrade Class
 *
 * Class to add options and notices for IP.Board upgrade
 * Last Updated: $Date: 2011-05-25 19:58:10 +0100 (Wed, 25 May 2011) $
 * </pre>
 * 
 * @author		Matt Mecham <matt@invisionpower.com>
 * @version		$Rev: 8891 $
 * @since		3.0
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @link		http://www.invisionpower.com
 * @package		IP.Board
 */ 

class version_class_core_33000
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
		
		// Is 3.1?
		if ( IPSSetUp::is300plus() && ! IPSSetUp::is320plus() )
		{
			// Got more than 100K posts?
			if( $posts['total'] > 100000 && ! $this->DB->checkForField( 'post_field_int', 'posts' ) )
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
		}
		
		$_html	.= <<<EOF
		<li>
			<input type='checkbox' name='flagBanned' value='1' checked='checked' />
			<strong>Flag all members in the Banned member group as banned</strong><br />
			3.3.0 removes the need for a specific Banned Group and uses the built in 'flag' on a per-member basis. However, unless you choose to update all
			members in the current Banned Group as 'banned' they may not appear when searching for banned members in the ACP.
			<br />We recommend you keep this box ticked
		</li>
EOF;

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
		return array( 'flagBanned'	=> intval( $_REQUEST['flagBanned'] ), 'manualPostsTableQuery' => intval( $_REQUEST['manualPostsTableQuery'] ) );
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
		return array();
	}
}