<?php
/**
 *
 * @class	version_upgrade
 * @brief	3.2.0 Alpha 1 Upgrade Logic
 *
 */
class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @var		string
	 */
	private $_output = '';
	
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
			case 'xmlskin':
			default:
				$this->_restoreXmlSkin();
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
	 * Execute selected method
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function _restoreXmlSkin() 
	{
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
		$xml    = new classXML( IPSSetUp::charSet );
			
		/* Skin Set Data */
		$xml->load( IPS_ROOT_PATH . 'setup/xml/skins/setsData.xml' );

		foreach( $xml->fetchElements( 'set' ) as $xmlelement )
		{
			$data = $xml->fetchElementsFromRecord( $xmlelement );
			
			if ( $data['set_key'] == 'xmlskin' )
			{
				$data['set_order'] = intval( $data['set_order'] );
				
				unset( $data['set_id'] );
				$this->DB->delete( 'skin_collections', "set_key='xmlskin'" );
				$this->DB->insert( 'skin_collections', $data );
			}
		}
		
		$this->registry->output->addMessage( "XML Skin restored" );
		
		$this->request['workact'] = '';
	}
	
	
}