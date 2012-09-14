<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Mobile app methods
 * Last Updated: $LastChangedDate: 2012-05-21 07:52:30 -0400 (Mon, 21 May 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		14th May 2003
 * @version		$Rev: 10770 $
 */

/**
* @author	Matt Mecham
* @since	Wednesday 9th May 2012
* @package	IP.Board
*/
class ipsMobileApp
{
	/* Never convert these fields */
	static public $noConvertFields = array( 'ips_username', 'ips_password' );
	
	/**
	 * Constructor
	 *
	 * @access	private
	 * @return	@e void
	 */
	public function __construct()
	{
		$this->convertIncoming();
	}

	/**
	 * Initialize ipsRegistry and this class
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function convertIncoming()
	{
		# GET first
		$input = $this->_recurse( $_GET, array() );

		# Then overwrite with POST
		ipsRegistry::$request = $this->_recurse( $_POST, $input );
		
		/* Reset $_GET and $_POST */
		array_walk_recursive( $_POST, create_function( '&$value, $key', '$value = ipsMobileApp::convert($key, $value);' ) );
		array_walk_recursive( $_GET , create_function( '&$value, $key', '$value = ipsMobileApp::convert($key, $value);' ) );
	}
	
	/**
	 * Recursively parse stuff
	 */
	private function _recurse( &$data, $input=array(), $iteration=0 )
	{
		if ( $iteration >= 20 )
		{
			return $input;
		}
		
		if ( is_array( $data ) )
		{
			foreach( $data as $k => $v )
			{
				if ( is_array( $v ) )
				{
					$input[ $k ] = $this->_recurse( $data[ $k ], array(), $iteration + 1 );
				}
				else
				{
					$v = self::convert( $k, $v );
	
					$input[ $k ] = IPSText::parseCleanValue( $v, true );
				}
			}
		}
		
		return $input;
	}
	
	/**
	 * Parses the content
	 */
	public static function convert( $k, $v )
	{
		return ( is_string( $v ) && ( ! in_array( $k,  self::$noConvertFields ) ) ) ? IPSText::convertCharsets( $v,  'UTF-8', IPS_DOC_CHAR_SET ) : $v;
	}

}