<?php
/*
+--------------------------------------------------------------------------
|   Portal 1.1.0
|   =============================================
|   by Michael John
|   Copyright 2011-2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
|   Based on IP.Board Portal by Invision Power Services
|   Website - http://www.invisionpower.com/
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	protected $_output = '';
	
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	public function doExecute( ipsRegistry $registry ) 
	{
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
			case 'addblocks':
				$this->addBlocks();
				break;
		}

		return true;
	}

	public function addBlocks()
	{
		$_total		= 0;

        # Load block file and import.		
		$block_file = file_get_contents( IPS_ROOT_PATH . 'applications_addon/other/portal/xml/custom_blocks.xml' );
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $block_file );
		
		foreach( $xml->fetchElements('block') as $block )
		{
			$_block	= $xml->fetchElementsFromRecord( $block );

			if( $_block['title'] )
			{
				if( !$_block['name'] )
                {
                    $_block['name'] = IPSText::makeSeoTitle( $_block['title'] );    
                }
				
                $_total++;
                
				$this->DB->insert( "portal_blocks", $_block );
			}
		}

		/* Message */
		$this->registry->output->addMessage("{$_total} new custom blocks added....");
		
		/* Next Page */
		$this->request['workact'] = '';
	}
}