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
		
		
		/* Now make add a key */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'pfields_data' ) );
		
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			/* Attempt conversion of dd / dt microformats */
			if ( stristr( $row['pf_topic_format'], '<dt>' ) OR stristr( $row['pf_topic_format'], '<dd>' ) )
			{
				$row['pf_topic_format'] = str_replace( '<dt>', "<span class='ft'>", $row['pf_topic_format'] );
				$row['pf_topic_format'] = str_replace( '<dd>', "<span class='fc'>", $row['pf_topic_format'] );
				$row['pf_topic_format'] = str_replace( array( '</dt>', '</dd>' ), "</span>", $row['pf_topic_format'] );
				
				$this->DB->update( 'pfields_data', array( 'pf_topic_format' => $row['pf_topic_format'] ), 'pf_id=' . $row['pf_id'] );
			}
		}
		
		$this->registry->output->addMessage( "Updated custom profile fields");
		
		return true;
	}
}
	
?>