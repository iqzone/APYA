<?php
/*
+--------------------------------------------------------------------------
|   IP.Board vVERSION_NUMBER
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
		
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			default:
			case 'fixtitles':
				$this->fixSeoTitles();
			break;
		}
		
		/* Workact is set in the function, so if it has not been set, then we're done. The last function should unset it. */
		if ( $this->request['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Set the SEO titles
	 * 
	 * @param	int
	 * @return	@e void
	 */
	public function fixSeoTitles()
	{
		/* Init */
		$st		= intval($this->request['st']);
		$did	= 0;
		$each	= 200;
		
		/* If this is the first pass, do the cal_calendars table too */
		if( !$st )
		{
			$this->DB->build( array( 'select' => 'cal_id, cal_title', 'from' => 'cal_calendars' ) );
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				$this->DB->update( 'cal_calendars', array( 'cal_title_seo' => IPSText::makeSeoTitle( $r['cal_title'] ) ), 'cal_id=' . $r['cal_id'] );
			}
			
			$this->registry->output->addMessage( 'Calendar FURL titles rebuilt' );
		}

		/* Build event FURL titles */
		$this->DB->build( array( 'select' => 'event_id, event_title', 'from' => 'cal_events', 'order' => 'event_id ASC', 'limit' => array( $st, $each ) ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$did++;
			
			$this->DB->update( 'cal_events', array( 'event_title_seo' => IPSText::makeSeoTitle( $r['event_title'] ) ), 'event_id=' . $r['event_id'] );
		}

		/* Show message and redirect */
		if( $did > 0 )
		{
			$this->request['st']		= $st + $did;
			$this->request['workact']	= 'fixtitles';
			
			$this->registry->output->addMessage( "Up to {$this->request['st']} event FURL titles rebuilt so far..." );
		}
		else
		{
			$this->request['st']		= 0;
			$this->request['workact']	= '';
			
			$this->registry->output->addMessage( "All event FURL titles rebuilt..." );
		}

		/* Next Page */
		return;
	}
}