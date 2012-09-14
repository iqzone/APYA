<?php

/**
 * @file		classCaptcha.php 	Provides methods to handle CAPTCHA abstraction - easily create, check and display CAPTHCA images
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		Friday 19th May 2006 17:33
 * $LastChangedDate: 2012-02-10 20:05:52 -0500 (Fri, 10 Feb 2012) $
 * @version		v3.3.3
 * $Revision: 10288 $
 */

/**
 *
 * @class		classCaptcha
 * @brief		Provides methods to handle CAPTCHA abstraction - easily create, check and display CAPTHCA images
 *
 */
class classCaptcha
{
	/**
	 * Registry Object
	 *
	 * @var		object
	 */
	public $registry;

	/**
	 * Settings array
	 *
	 * @var		array
	 */
	public $settings;
	
	/**
	 * Object that stored the plug in class
	 *
	 * @var		$_plugInClass
	 */
	protected $_plugInClass;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry Object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry =  $registry;
		$this->settings =& $this->registry->fetchSettings();
		
		$plugin = $this->settings['bot_antispam_type'];
		
		if ( ! is_file( IPS_KERNEL_PATH . 'classCaptchaPlugin/' . $plugin . '.php' ) )
		{
			$plugin = 'default';
		}
	
		require_once( IPS_KERNEL_PATH . 'classCaptchaPlugin/' . $plugin . '.php' );/*noLibHook*/
		$this->_plugInClass = new captchaPlugIn( $registry );
	}
	
	/**
	 * Magic __call method
	 *
	 * @param	string		$method		Method name
	 * @param	mixed		$arguments	Method arguments
	 * @return	@e mixed
	 */
	public function __call( $method, $arguments )
	{
		if ( method_exists( $this->_plugInClass, $method ) )
		{
			return $this->_plugInClass->$method( $arguments );
		}
		else
		{
			trigger_error( $method . " does not exist", E_USER_ERROR );
		}
	}
	
	/**
	 * Magic __get method
	 *
	 * @param	string		$name		Property name
	 * @return	@e mixed
	 */
	public function __get( $name )
	{
		if ( property_exists( $this->_plugInClass, $name ) )
		{
			return $this->_plugInClass->$name;
		}
		else
		{
			trigger_error( $name . " does not exist", E_USER_ERROR );
		}
	}
}