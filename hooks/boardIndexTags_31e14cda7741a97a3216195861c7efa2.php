<?php

class boardIndexTags
{
	public $registry;
	
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
	}
	
	public function getOutput()
	{
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('forums') . '/sources/classes/hooks/gateway.php', 'forums_hookGateway', 'forums' );
		$hook = new $classToLoad( $this->registry );
		
		return $hook->tags();
	}
}