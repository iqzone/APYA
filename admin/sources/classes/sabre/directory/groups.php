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

class sabre_directory_groups extends Sabre_DAV_Directory
{
	protected $_skinSet = array();
	protected $_group   = '';
	
	public function __construct( $group, $skinSet )
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
		$this->_skinSet      = $skinSet;
		$this->_group		 = $group;
	}

	public function getChildren()
	{
		$output = array();
		
		/* Group data or CSS */
		if ( $this->_group != 'css' )
		{
			$templates = $this->skinFunctions->fetchTemplates( $this->_skinSet['set_id'], 'groupTemplatesNoContent', $this->_group );
			
			/* templates */
			foreach( $templates as $title => $data )
			{
				$output[] = new sabre_file_templates( $this->_skinSet, $data, $title, $this->_group );
			}
		}
		else
		{
			$css = $this->skinFunctions->fetchCSS( $this->_skinSet['set_id'] );
			
			/* templates */
			foreach( $css as $title => $data )
			{
				$output[] = new sabre_file_templates( $this->_skinSet, $data, $title, 'css' );
			}
		}

		return $output;
	}

	public function getChild( $title )
	{
		if ( ! $title || $title == '.' || strtolower( $title == '.ds_store' ) || $title == 'Thumbs.db' || $title == 'desktop.ini' )
		{
			return false;
		}
		
		$title = preg_replace( '#^(.*)\.(css|html)$#', '\1', $title );
		
		/* Group data or CSS */
		if ( $this->_group != 'css' )
		{
			/* Fetch template */
			$_templates = $this->skinFunctions->fetchTemplates( $this->_skinSet['set_id'], 'groupTemplates', $this->_group );
			$template   = $_templates[ strtolower( $title ) ];
			
			if ( $template )
			{
				return new sabre_file_templates( $this->_skinSet, $template, $title, $this->_group );
			}
			else
			{
				return new sabre_file_templates( $this->_skinSet, null, $title, $this->_group );
			}
		}
		else
		{
			$css = $this->skinFunctions->fetchCSS( $this->_skinSet['set_id'] );
			
			return new sabre_file_templates( $this->_skinSet, $css[ strtolower($title) ], $title, 'css' );
		}
	}

	public function getName()
	{
		if ( strstr( $this->_group, 'skin_' ) )
		{
			return preg_replace( '#^skin_(.*)$#', '\1', IPSText::alphanumericalClean( $this->_group ) );
		}
		else if ( $this->_group == 'css' )
		{
			return '0.css';
		}
	}
}