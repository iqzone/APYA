<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * IP.Content custom bbcode
 * Last Updated: $Date: 2012-06-01 13:11:08 -0400 (Fri, 01 Jun 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10855 $ 
 */

if( !class_exists('bbcode_parent_class') )
{
	require_once( IPS_ROOT_PATH . 'sources/classes/bbcode/custom/defaults.php' );/*noLibHook*/
}

class bbcode_page extends bbcode_parent_class implements bbcodePlugin
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->currentBbcode	= 'page';
		
		parent::__construct( $registry );
	}

	/**
	 * Do the actual replacement
	 *
	 * @access	protected
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	string				BBCode content, ready for editing
	 */
	protected function _replaceText( $txt )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$tag			= '[' . $this->currentBbcode . ']';
		$_curPage		= $this->request['pg'] ? $this->request['pg'] : 1;
		$_ttlPages		= 1;
		$_ttlPages		+= substr_count( $txt, $tag );
		$_requestUri	= rtrim( $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : @getenv('REQUEST_URI'), '/' );
		$_reconstructed	= ( ( $_SERVER['HTTPS'] and $_SERVER['HTTPS'] != 'off' ) ? "https://" : "http://" ) . $_SERVER['HTTP_HOST'] . ( substr( $_requestUri, 0, 1 ) == '/' ? $_requestUri : '/' . $_requestUri );

		//-----------------------------------------
		// Get rid of existing pg= params
		//-----------------------------------------
		
		$_reconstructed	= preg_replace( "/pg=(\d+)/", '', $_reconstructed );
		
		$_reconstructed	= rtrim($_reconstructed, '?&' );
		
		$_reconstructed	= str_replace( '&', '&amp;', $_reconstructed );
		
		//-----------------------------------------
		// Add on the parameter separator
		//-----------------------------------------
		
		if( $this->settings['url_type'] == 'query_string' )
		{
			if( substr_count( $_reconstructed, '?' ) > 1 )
			{
				$_reconstructed	.= '&amp;';
			}
			else
			{
				$_reconstructed	.= '?';
			}
		}
		else
		{
			if( strpos( $_reconstructed, '?' ) !== false )
			{
				$_reconstructed	.= '&amp;';
			}
			else
			{
				$_reconstructed	.= '?';
			}
		}
		
		//-----------------------------------------
		// If no pages, no need to have links
		//-----------------------------------------
		
		if( $_ttlPages < 2 )
		{
			return $txt;
		}
		
		//-----------------------------------------
		// Do replacements
		//-----------------------------------------

		if( !$_curPage )
		{
			$txt	= substr( $txt, 0, strpos( $txt, $tag ) );
		}
		else
		{
			$bits	= explode( $tag, $txt );
			
			$txt	= $bits[ $_curPage - 1 ];
		}
		
		//-----------------------------------------
		// Clean up leading <br /> tags
		//-----------------------------------------

		$txt	= trim($txt);
		
		if( substr( $txt, 0, 15 ) == '~~~~~_____~~~~~' )
		{
			$txt	= substr( $txt, 15 );
		}

		//-----------------------------------------
		// And start generating output
		//-----------------------------------------
		
		$txt	.= $this->registry->output->getTemplate('ccs_global')->articlePages( $_ttlPages, $_curPage, $_reconstructed );

		return $txt;
	}
}