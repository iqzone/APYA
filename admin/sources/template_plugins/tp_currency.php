<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Template Pluging: Currency
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

/**
* Main loader class
*/
class tp_currency extends output implements interfaceTemplatePlugins
{
	/**
	 * Prevent our main destructor being called by this class
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function __destruct()
	{
	}
	
	/**
	 * Run the plug-in
	 *
	 * @access	public
	 * @param	string	The initial data from the tag
	 * @param	array	Array of options
	 * @return	string	Processed HTML
	 */
	public function runPlugin( $data, $options )
	{
		$return = '$this->registry->getClass(\'class_localization\')->formatMoney( ' . $data . ', FALSE )';
		
		return '" . ' . $return . ' . "';
	}
	
	/**
	 * Return information about this modifier.
	 *
	 * It MUST contain an array  of available options in 'options'. If there are no allowed options, then use an empty array.
	 * Failure to keep this up to date will most likely break your template tag.
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPluginInfo()
	{
		return array( 'name'    => 'currency',
					  'author'  => 'IPS, Inc.',
					  'usage'   => '{parse currency="5.00"}',
					  'options' => array( 'format', 'relative' ) );
	}
}