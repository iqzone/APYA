<?php

$INSERT	= array();

class portal_blocks
{
	protected $registry;
	protected $DB;
	
	public function __construct()
	{
		$this->registry	= ipsRegistry::instance();
		$this->DB		= $this->registry->DB();
        
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
				
				$this->DB->insert( "portal_blocks", $_block );
			}
		}

	}    
}

$portalBlockInstall = new portal_blocks();
