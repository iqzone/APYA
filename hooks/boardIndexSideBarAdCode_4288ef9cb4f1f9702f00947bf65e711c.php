<?php

class boardIndexSideBarAdCode
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	protected $settings;
	/**#@-*/
	
	public function __construct()
	{
		/* Make registry objects */
		$this->registry		=  ipsRegistry::instance();
		$this->settings 	=& $this->registry->fetchSettings();
	}
	
	public function getOutput()
	{
		if( $this->registry->getClass('IPSAdCode')->userCanViewAds() )
		{
			return $this->registry->getClass('IPSAdCode')->getAdCode('ad_code_board_sidebar');
		}
		else
		{
			return '';
		}
	}

}