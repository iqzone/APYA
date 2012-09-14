<?php

/*
+--------------------------------------------------------------------------
|   IP.Board v3.3.3
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
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
	 * @var		string
	 */
	protected $_output = '';
	
	/**
	 * fetchs output
	 * 
	 * @return	string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
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
		
		//-----------------------------------------
		// Remove dupe categories
		//-----------------------------------------
		
		$title_id_to_keep    = array();
		$title_id_to_delete  = array();
		$title_deleted_count = 0;
		$msg                 = '';
		
		$this->DB->build( array( 'select' => '*', 'from' => 'conf_settings_titles', 'order' => 'conf_title_id DESC' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			if ( $title_id_to_keep[ $r['conf_title_title'] ] )
			{
				$title_id_to_delete[ $r['conf_title_id'] ] = $r['conf_title_id'];
			}
			else
			{
				$title_id_to_keep[ $r['conf_title_title'] ] = $r['conf_title_id'];
			}
		}
		
		if ( count( $title_id_to_delete ) )
		{
			$this->DB->delete( 'conf_settings_titles', 'conf_title_id IN ('.implode( ',', $title_id_to_delete ).')' );
		}
		
		$title_deleted_count = intval( count($title_id_to_delete) );
		
		$this->registry->output->addMessage("$title_deleted_count duplicate settings deleted");
		
		return true;
	}
}