<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Template Pluging: Date
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
class tp_date extends output implements interfaceTemplatePlugins
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
	 * @author	Matt Mecham
	 * @param	string	The initial data from the tag
	 * @param	array	Array of options
	 * @return	string	Processed HTML
	 */
	public function runPlugin( $data, $options )
	{
		//-----------------------------------------
		// Process the tag and return the data
		//-----------------------------------------
				
		$return = '';
		$timestamp    = $data ? $data : "'{custom:now}'";

		# Fix up string style dates
		/*if ( ! preg_match( "#^[0-9]{10}$#", $timestamp ) AND ( substr( $timestamp, 0, 1 ) != '$' ) )
		{
			$_time = strtotime( $timestamp );

			if ( $_time === FALSE OR $_time == -1 )
			{
				$timestamp = 0;
			}
			else
			{
				$timestamp = $_time;
			}
		}*/

		$_relative = ( isset( $options['relative'] ) && $options['relative'] == 'false' ) ? 1 : 0;
		$_format   = empty( $options['format'] ) ? 'LONG' : $options['format'];

		$return = '$this->registry->getClass(\'class_localization\')->getDate(' . $timestamp . ',"' .  $_format . '", ' . $_relative . ')';
		
		return '" . ' . $return . ' . "';
	}
	
	/**
	 * Return information about this modifier.
	 *
	 * It MUST contain an array  of available options in 'options'. If there are no allowed options, then use an empty array.
	 * Failure to keep this up to date will most likely break your template tag.
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return	array
	 */
	public function getPluginInfo()
	{
		//-----------------------------------------
		// Return the data, it's that simple...
		//-----------------------------------------
		
		return array( 'name'    => 'date',
					  'author'  => 'IPS, Inc.',
					  'usage'   => '{parse date="now" format="long" relative="false"}',
					  'options' => array( 'format', 'relative' ) );
	}
}