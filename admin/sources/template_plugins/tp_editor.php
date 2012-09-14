<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Template Pluging: CCS block parsing
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
class tp_editor extends output implements interfaceTemplatePlugins
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
		
		if( ! $data )
		{
			return;	
		}
		
		$_params	= ( $options['options'] ) ? $options['options'] : array();
		$_content   = ( $options['content'] ) ? str_replace( '\'', '\\\'', $options['content'] ) : '';

		$_phpCode	= "<php>\n";
		$_phpCode	.= "\t" . '$pluginEditorHook = IPSLib::loadLibrary( IPS_ROOT_PATH . \'sources/classes/editor/composite.php\', \'classes_editor_composite\' );' . "\n";
		$_phpCode	.= "\t" . '$editor = new $pluginEditorHook();' . "\n</php>";
		$_phpCode	.= '" . $editor->show(\'' . $data . '\', ' . $_params . ', "' . $_content . '")  . "';

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
		
		return array( 'name'    => 'editor',
					  'author'  => 'Invision Power Services, Inc.',
					  'usage'   => '{parse editor="FormFieldName" content="" options="array()"}',
					  'options' => array('options', 'content') );
	}
}