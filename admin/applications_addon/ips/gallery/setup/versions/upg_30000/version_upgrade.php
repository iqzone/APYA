<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * IP.Gallery 3.x upgrader
 * Last Updated: $Date: 2011-05-18 12:10:05 -0400 (Wed, 18 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @since		1st April 2004
 * @version		$Revision: 8829 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @access	private
	 * @var		string
	 */
	private $_output = '';
	
	/**
	 * fetchs output
	 * 
	 * @access	public
	 * @return	@e string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			default:
				$this->upgradeCategories();
			break;
		}
		
		return true;	
	}
	
	/**
	 * Main work of upgrading IP.Gallery
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function upgradeCategories()
	{
		/* Convert category perms */
		$this->DB->build( array( 'select' => '*', 'from' => 'gallery_categories' ) );
								
		$o = $this->DB->execute();
					
		while( $row = $this->DB->fetch( $o ) )
		{
			$_view		= ( $row['perms_view'] )		? ',' . implode( ',', explode( ',', $row['perms_view'] ) ) . ',' : '';
			$_images	= ( $row['perms_images'] )		? ',' . implode( ',', explode( ',', $row['perms_images'] ) ) . ',' : '';
			$_thumbs	= ( $row['perms_thumbs'] )		? ',' . implode( ',', explode( ',', $row['perms_thumbs'] ) ) . ',' : '';
			$_comment	= ( $row['perms_comments'] )	? ',' . implode( ',', explode( ',', $row['perms_comments'] ) ) . ',' : '';
			$_mod		= ( $row['perms_moderate'] )	? ',' . implode( ',', explode( ',', $row['perms_moderate'] ) ) . ',' : '';
			
			$this->DB->insert( 'permission_index', array( 'app'				=> 'gallery',
														  'perm_type'		=> 'cat',
														  'perm_type_id'	=> $row['id'],
														  'perm_view'		=> str_replace( ',*,', '*', $_thumbs ),
														  'perm_2'			=> str_replace( ',*,', '*', $_view ),
														  'perm_3'			=> str_replace( ',*,', '*', $_images ),
														  'perm_4'			=> str_replace( ',*,', '*', $_comment ),
														  'perm_5'			=> str_replace( ',*,', '*', $_mod ),
														  'perm_6'			=> '',
														  'perm_7'			=> '' ) );
		}
	}
}