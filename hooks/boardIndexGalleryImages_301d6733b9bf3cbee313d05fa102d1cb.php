<?php

class boardIndexGalleryImages
{
	public $registry;
	
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
	}
	
	public function getOutput()
	{
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/hooks.php', 'app_gallery_classes_hooks', 'gallery' );
		$hook = new $classToLoad( $this->registry );
		
		return $hook->hookBoardIndexRecentImages();
	}
}