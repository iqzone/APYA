<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Template plugin: Set and retrieve template variables
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 10721 $
 */

/**
* Main loader class
*/
class tp_variable extends output implements interfaceTemplatePlugins
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
	 *
	 * @author	Matt Mecham
	 * @param	string	The initial data from the tag
	 * @param	array	Array of options
	 * @return	string	Processed HTML
	 */
	public function runPlugin( $data, $options )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$phpCode = '';
		
		//-----------------------------------------
		// Just want the tag?
		//-----------------------------------------
		
		if ( ! count( $options ) )
		{
			return '" . $this->templateVars["'.$data.'"] . "';
		}
		
		if ( isset($options['default']) )
		{
			$phpCode .= "\n" . '$this->templateVars[\'' . $data . '\'] = "' . str_replace( '"', '\\"', $options['default'] ) . '";';
			$phpCode .= "\n" . '$this->__default__templateVars[\'' . $data . '\'] = "' . str_replace( '"', '\\"', $options['default'] ) . '";';
		}
		
		if ( !empty($options['oncondition'])AND isset( $options['value'] ) )
		{
			$phpCode .= "\nif ( {$options['oncondition']} )\n{\n\t".'$this->templateVars[\'' . $data . '\'] = "' . str_replace( '"', '\\"', $options['value'] ) . '";' . "\n}";
			$phpCode .= "\nelse {"  . '$this->templateVars[\'' . $data . '\'] = $this->__default__templateVars[\'' . $data . '\']; }';
		}
		
		//-----------------------------------------
		// Process the tag and return the data
		//-----------------------------------------

		return ( $phpCode ) ? "<php>" . $phpCode . "</php>" : '';
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
		return array( 'name'    => 'variable',
					  'author'  => 'IPS, Inc.',
					  'usage'   => '{parse variable="testKey" default="foo" oncondition="$data == \'new\'" value="bar"}',
					  'options' => array( 'default', 'oncondition', 'value' ) );
	}
}