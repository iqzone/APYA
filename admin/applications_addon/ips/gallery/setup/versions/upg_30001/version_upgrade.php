<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > IPB UPGRADE MODULE:: IPB 2.0.2 -> IPB 2.0.3
|   > Script written by Matt Mecham
|   > Date started: 23rd April 2004
|   > "So what, pop is dead - it's no great loss.
	   So many facelifts, it's face flew off"
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @access	private
	 * @var		string
	 */
	private $_output = '';
	
	/**
	* fetchs output
	* 
	* @access	public
	* @return	@e string
	*/
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			default:
				$this->upgradeGallery();
			break;
		}
		
		return true;	
	}
	
	/**
	 * Main work of upgrading gallery
	 *
	 * @return void
	 */
	public function upgradeGallery()
	{
		//-----------------------------------------
		// Got our XML?
		//-----------------------------------------
		
		if ( is_file( IPS_ROOT_PATH . 'applications_addon/ips/gallery/xml/information.xml' ) )
		{
			//-----------------------------------------
			// Not already "installed"?
			//-----------------------------------------
			
			$check	= $this->DB->buildAndFetch( array( 'select' => 'app_directory', 'from' => 'core_applications', 'where' => "app_directory='gallery'" ) );
			
			if( !$check['app_directory'] )
			{
				//-----------------------------------------
				// Get position
				//-----------------------------------------
				
				$max	= $this->DB->buildAndFetch( array( 'select' => 'MAX(app_position) as max', 'from' => 'core_applications' ) );
				
				$_num	= $max['max'] + 1;
				
				//-----------------------------------------
				// Get XML data
				//-----------------------------------------
				
				$data	= IPSSetUp::fetchXmlAppInformation( 'gallery' );

				//-----------------------------------------
				// Get current versions
				//-----------------------------------------
				
				if ( $this->DB->checkForTable( 'gallery_upgrade_history' ) )
				{
					/* Fetch current version number */
					$version = $this->DB->buildAndFetch( array( 'select' => '*',
																'from'   => 'gallery_upgrade_history',
																'order'  => 'gallery_version_id DESC',
																'limit'  => array( 0, 1 ) ) );
																
					$data['_currentLong']	= $version['gallery_version_id'];
					$data['_currentHuman']	= $version['gallery_version_human'];
				}
				
				$_enabled   = ( $data['disabledatinstall'] ) ? 0 : 1;
	
				if ( $data['_currentLong'] )
				{
					//-----------------------------------------
					// Insert record
					//-----------------------------------------
					
					$this->DB->insert( 'core_applications', array(   'app_title'        => $data['name'],
																	 'app_public_title' => ( $data['public_name'] ) ? $data['public_name'] : '',	// Allow blank in case it's an admin-only app
																	 'app_description'  => $data['description'],
																	 'app_author'       => $data['author'],
																	 'app_version'      => $data['_currentHuman'],
																	 'app_long_version' => $data['_currentLong'],
																	 'app_directory'    => $data['key'],
																	 'app_added'        => time(),
																	 'app_position'     => $_num,
																	 'app_protected'    => 0,
																	 'app_location'     => IPSLib::extractAppLocationKey( $data['key'] ),
																	 'app_enabled'      => $_enabled ) );
				}
			}
		}
	}
}