<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Upgrade Class
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 * 
 * @version		$Rev: 4 $
 * @since		3.0
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @link		http://www.invisionpower.com
 * @package		IP.Board
 */ 

class version_class_blog_20000
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry = $registry;
	}
	
	/**
	 * Add pre-upgrade options: Form
	 * 
	 * @access	public
	 * @return	string	 HTML block
	 */
	public function preInstallOptionsForm()
	{	
	}
	
	/**
	 * Add pre-upgrade options: Save
	 *
	 * Data will be saved in saved data array as: appOptions[ app ][ versionLong ] = ( key => value );
	 * 
	 * @access	public
	 * @return	array	 Key / value pairs to save
	 */
	public function preInstallOptionsSave()
	{
	}
	
	/**
	 * Return any post-installation notices
	 * 
	 * @access	public
	 * @return	array	 Array of notices
	 */
	public function postInstallNotices()
	{
		$notices[] = "You will need to run the 'Resynchronize Blogs' tool, found in the 'Tools -> Rebuild' section of the IP.Blog ACP area, in order to complete the upgrade.";
		
		return $notices;
	}
}