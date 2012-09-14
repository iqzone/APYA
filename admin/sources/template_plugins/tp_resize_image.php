<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Template Pluging: Resize images in templates proportionately
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

/**
* Main loader class
*/
class tp_resize_image extends output implements interfaceTemplatePlugins
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
	 * @author	Brandon Farber
	 * @param	string	The initial data from the tag
	 * @param	array	Array of options
	 * @return	string	Processed HTML
	 */
	public function runPlugin( $data, $options )
	{
		//-----------------------------------------
		// Process the tag and return the data
		//-----------------------------------------

		if( !$data )
		{
			return;	
		}

		if( substr( $data, 0, 1 ) == '$' )
		{
			$_phpCode	= '" . IPSLib::getTemplateDimensions(' . $data . ', \'' . $options['maxwidth'] . '\', \'' . $options['maxheight'] . '\')  . "';
		}
		else
		{
			$_phpCode	= '" . IPSLib::getTemplateDimensions("' . $data . '", \'' . $options['maxwidth'] . '\', \'' . $options['maxheight'] . '\')  . "';
		}

		//-----------------------------------------
		// Process the tag and return the data
		//-----------------------------------------

		return $_phpCode;
	}
	
	/**
	 * Return information about this modifier
	 *
	 * It MUST contain an array  of available options in 'options'. If there are no allowed options, then use an empty array.
	 * Failure to keep this up to date will most likely break your template tag.
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @return	array
	 */
	public function getPluginInfo()
	{
		//-----------------------------------------
		// Return the data, it's that simple...
		//-----------------------------------------
		
		return array( 'name'    => 'resize_image',
					  'author'  => 'Invision Power Services, Inc.',
					  'usage'   => '{parse resize_image="/path/to/image" maxwidth="100" maxheight="100"}',
					  'options' => array( 'maxwidth', 'maxheight' ) );
	}
}