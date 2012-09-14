<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Template Pluging: Resize images in templates proportionately
 * Last Updated: $Date: 2011-10-31 13:03:49 -0400 (Mon, 31 Oct 2011) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9714 $
 */

/**
* Main loader class
*/
class tp_gallery_resize extends output implements interfaceTemplatePlugins
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

		if ( ! $data )
		{
			return;	
		}
		
		$return = '$this->registry->getClass(\'gallery\')->inlineResize( ' . $data . ",'" . trim( $options['width'] ) . "','" . trim( $options['height'] ) . "','" . trim( $options['class'] ) . '\' )';

		return '" . ' . $return . ' . "';
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
		
		return array( 'name'    => 'gallery_resize',
					  'author'  => 'Invision Power Services, Inc.',
					  'usage'   => '{parse gallery_resize="{$image_tag_html}" width="100" height="100"}',
					  'options' => array( 'width', 'height', 'class' ) );
	}
}