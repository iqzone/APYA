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
 
class sabre_root_skins extends Sabre_DAV_Directory
{

	public function __construct()
	{
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
	 	/* Require some files for our sabre implementation */
	 	require_once( IPS_ROOT_PATH . 'sources/classes/sabre/root/skins.php' );/*noLibHook*/
	 	require_once( IPS_ROOT_PATH . 'sources/classes/sabre/directory/templates.php' );/*noLibHook*/
	 	require_once( IPS_ROOT_PATH . 'sources/classes/sabre/files/templates.php' );/*noLibHook*/
	    require_once( IPS_ROOT_PATH . 'sources/classes/sabre/lock/nolocks.php' );/*noLibHook*/
	    
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		
		$this->skinFunctions = new skinCaching( $this->registry );
	}
	
	public function getChildren()
	{
		/* Show all skins */
		$this->DB->build( array( 'select' => '*',
							     'from'   => 'skin_collections',
							     'order'  => 'set_id ASC' ) );
							     
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$output[] = new sabre_directory_templates( $row );
		}

		return $output;
	}


	public function getChild( $directoryName )
	{
		/* Fetch ID */
		$skinId = preg_replace( '#^([0-9]+?)__.*$#', '\1', $directoryName );

		if ( is_numeric( $skinId ) )
		{
			$skinSet = $this->skinFunctions->fetchSkinData( intval( $skinId ) );
			
			return new sabre_directory_templates( $skinSet );
		}
	}

	public function getName()
	{
		return 'PublicTemplates';
	}
}