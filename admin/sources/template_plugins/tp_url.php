<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Template Pluging: URL
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
class tp_url extends output implements interfaceTemplatePlugins
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
		// INIT
		//-----------------------------------------

		$return					= '';
		$base					= str_replace( '"', '\\"', $options['base'] );
		$seotitle				= isset( $options['seotitle'] ) ? str_replace( '"', '\\"', $options['seotitle'] ) : '';
		$template				= isset( $options['template'] ) ? str_replace( '"', '\\"', $options['template'] ) : '';
		$options['httpauth']	= isset( $options['httpauth'] ) ? $options['httpauth'] : '';
		
		//$data					= $this->_cleanData( $data );

		$return = '$this->registry->getClass(\'output\')->formatUrl( $this->registry->getClass(\'output\')->buildUrl( "' . $data . '", "' . $base . '",\'' . $options['httpauth'] . '\' ), "' . $seotitle . '", "' . $template . '" )';

		return '" . ' . $return . ' . "';
	}
	
	/**
	 * Clean the data passed to this plugin
	 *
	 * @access	protected
	 * @param	string		Query string params
	 * @return	string		Query string params (singular keys)
	 * @see	http://community.invisionpower.com/tracker/issue-20122-hard-coded-query-strings-in-members-list/
	 * @deprecated
	 */
	protected function _cleanData( $data )
	{
		$_return	= str_replace( '&amp;', '&', $data );
		$_pairs		= array();
		
		$_existing	= explode( '&', $_return );
		
		if( count( $_existing ) AND is_array( $_existing ) )
		{
			foreach( $_existing as $kvp )
			{
				list( $key, $value )	= explode( '=', $kvp );

				if( $key AND $value )
				{
					if( strpos( $key, '[' ) === false OR strpos( $key, '[]' ) !== false )
					{
						$_pairs[ $key ]	= $key . '=' . $value;
					}
					else
					{
						preg_match( "/^(.+?)\[(.+?)\]$/", $key, $matches );
						
						$_pairs[ $matches[1] . $matches[2] ]	= $key . '=' . $value;
					}
				}
				else
				{
					$_pairs[]	= $kvp;
				}
			}
			
			return implode( '&amp;', $_pairs );
		}
		else
		{
			return $data;
		}
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
		
		return array( 'name'    => 'url',
					  'author'  => 'IPS, Inc.',
					  'usage'   => '{parse url="this=that" base="public"}',
					  'options' => array( 'base', 'seotitle', 'template', 'httpauth' ) );
	}
}