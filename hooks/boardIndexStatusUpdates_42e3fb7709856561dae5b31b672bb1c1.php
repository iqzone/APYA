<?php

class boardIndexStatusUpdates
{
	protected $hookGateway;
	
	public function __construct()
	{
		$registry = ipsRegistry::instance();
		
		$classToLoad       = IPSLib::loadLibrary( IPSLib::getAppDir( 'members' ) . '/sources/classes/hooks/gateway.php', 'members_hookGateway', 'members' );
		$this->hookGateway = new $classToLoad( $registry );
	}
	
	public function getOutput()
	{
		return $this->hookGateway->statusUpdates();
	}	
}