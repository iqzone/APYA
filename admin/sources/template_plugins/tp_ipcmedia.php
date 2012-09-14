<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Template Plugin: Insert IP.C media file
 * Last Updated: $Date$$
 * </pre>
 *
 * @author 		$Author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev$
 */

/**
* Main loader class
*/
class tp_ipcmedia extends output implements interfaceTemplatePlugins
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
		//-----------------------------------------
		// Process the tag and return the data
		//-----------------------------------------

		if( !$data )
		{
			return;	
		}

		//-----------------------------------------
		// Check the media_path file
		//-----------------------------------------

		if( !is_file( DOC_IPS_ROOT_PATH . '/media_path.php' ) )
		{
			return;
		}
		
		require_once( DOC_IPS_ROOT_PATH . '/media_path.php' );/*noLibHook*/
		
		if( !defined('CCS_MEDIA') OR !CCS_MEDIA_URL OR !CCS_MEDIA OR !is_dir(CCS_MEDIA) )
		{
			return;
		}

		$_phpCode = CCS_MEDIA_URL . '/' . ltrim( $data, '/' );

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
		
		return array( 'name'    => 'ipcmedia',
					  'author'  => 'Invision Power Services, Inc.',
					  'usage'   => '{parse ipcmedia="/path/to/media.jpg"}',
					  'options' => array() );
	}
}