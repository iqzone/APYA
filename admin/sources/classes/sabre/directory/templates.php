<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Sabre classes by Matt Mecham
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		Friday 18th March 2011
 * @version		$Revision: 10721 $
 */

class sabre_directory_templates extends Sabre_DAV_Directory
{
	protected $_skinSet = array();

	public function __construct( $skinSet )
	{
	
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Require some files for our sabre implementation */
	 	require_once( IPS_ROOT_PATH . 'sources/classes/sabre/root/skins.php' );/*noLibHook*/
	 	require_once( IPS_ROOT_PATH . 'sources/classes/sabre/directory/groups.php' );/*noLibHook*/
	 	require_once( IPS_ROOT_PATH . 'sources/classes/sabre/files/templates.php' );/*noLibHook*/
	    require_once( IPS_ROOT_PATH . 'sources/classes/sabre/lock/nolocks.php' );/*noLibHook*/
	    
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		
		$this->skinFunctions = new skinCaching( $this->registry );
		$this->_skinSet      = $skinSet;
	}

	public function getChildren()
	{
		$templates = $this->skinFunctions->fetchTemplates( $this->_skinSet['set_id'], 'groupNames');
		
		$output = array();
		
		/* Add in css */
		$output[] = new sabre_directory_groups( 'css', $this->_skinSet );
		
		foreach( $templates as $group => $data )
		{
			$output[] = new sabre_directory_groups( $group, $this->_skinSet );
		}

		return $output;
	}

	public function getChild( $title )
	{
		if ( strstr( $title, '.css' ) )
		{
			return new sabre_directory_groups( 'css', $this->_skinSet );
		}
		else
		{
			return new sabre_directory_groups( 'skin_' . $title, $this->_skinSet );
		}
	}

	public function getName()
	{
		return $this->_skinSet['set_id'] . '__' . IPSText::alphanumericalClean( $this->_skinSet['set_name'] );
	}
}