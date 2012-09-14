<?php

class boardIndexFacebookActivity
{
	public $registry;
	
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
	}
	
	public function getOutput()
	{
		return $this->registry->getClass('class_forums')->hooks_facebookActivity();
	}	
}