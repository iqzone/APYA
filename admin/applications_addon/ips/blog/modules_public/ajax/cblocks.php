<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog index listing
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_blog_ajax_cblocks extends ipsAjaxCommand
{
	/**
	* Stored temporary output
	*
	* @access	protected
	* @var 		string 				Page output
	*/
	protected $output				= "";
	
	/**
	* Current blog
	*
	* @access	protected
	* @var 		array
	*/
	protected $blog 				= array();

	/**
	* Stored error messages
	*
	* @access	protected
	* @var 		array
	*/
	protected $error 				= array();

	/**
	* Last read markers
	*
	* @access	protected
	* @var 		array
	*/
	protected $last_read			= array();

	/**
	* Stored entries that you've read
	*
	* @access	protected
	* @var 		array
	*/
	protected $entries_read			= array();

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// No guests
		//-----------------------------------------

		if ( !$this->memberData['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['no_guests'] );
		}
		
		//-----------------------------------------
		// Get teh blog
		//-----------------------------------------
		
		$blog_id	   = intval( $this->request['blogid'] );
       	$this->blog    = $this->registry->getClass('blogFunctions')->getActiveBlog();
		$this->blog_id = intval( $this->blog['blog_id'] );

		//-----------------------------------------
		// Get the Blog url
		//-----------------------------------------

		$this->settings[ 'blog_url'] =  $this->registry->getClass('blogFunctions')->getBlogUrl( $blog_id );

		//-----------------------------------------
		// Are we authorized?
		//-----------------------------------------

		/*if( ! $this->memberData['g_blog_allowlocal'] )
		{
			$this->returnJsonError( $this->lang->words['no_blog_create_permission'] );
		}*/

		if ( $this->memberData['member_id'] != $this->blog['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['incorrect_use'] );
		}

		//--------------------------------------------
		// What do you want to do Today?
		//--------------------------------------------

		switch( $this->request['do'] )
		{
			case 'showcblockconfig':
				$this->_showCblockConfig();
			break;
			
			case 'savecblockconfig':
				$this->_saveCblockConfig();
			break;
			
			case 'doenablecblock':
				$this->_enableCblock();
			break;
			
			case 'doaddcblock':
				$this->_addCblock();
			break;
			
			case 'savecblockpos':
				$this->_savePosition();
			break;
			
			case 'doremovecblock':
				$this->_removeCblock();
			break;
			
			case 'dodelcblock':
				$this->_deleteCblock();
			break;
		}
	}
	
	/**
	* Save the cblock configuration
	*
	* @access	protected
	* @return	@e void
	*/	
	protected function _showCblockConfig()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id				= intval( $this->request['cblock_id'] );
		$return_html	= '';
		
		//-----------------------------------------
		// Get cblock
		//-----------------------------------------
		
		$cblock	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_cblocks', 'where' => "cblock_id={$id}" ) );

		//-----------------------------------------
		// Is it ours
		//-----------------------------------------
		
		if( $this->memberData['member_id'] != $cblock['member_id'] )
		{
			$this->returnString( 'error' );
		}
		
		//-----------------------------------------
		// Grab teh lib
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
		$cblock_lib	 = new $classToLoad( $this->registry );
		
		$cb_plugin	= $cblock_lib->getPlugin( $id );
		
		//-----------------------------------------
		// What's with all the security?
		//-----------------------------------------
		
		if( $this->memberData['member_id'] != $cblock_lib->cblock['member_id'] )
		{
			$this->returnString( 'error' );
		}
		
		//-----------------------------------------
		// I trust that we can edit inline (angel)
		//-----------------------------------------
		
		$this->settings['blog_inline_edit'] =  1 ;	

		//-----------------------------------------
		// Return edit form
		//-----------------------------------------

		$this->returnString( $cblock_lib->wrapPluginOutput( $cb_plugin, $cb_plugin->getConfigForm( $cblock_lib->cblock ) ) );
	}
	
	/**
	* Save the cblock configuration
	*
	* @access	protected
	* @return	@e void
	*/	
	protected function _saveCblockConfig()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id			= intval( $this->request['cblock_id'] );
		$config		= $this->request['cblock_config'];
		
		foreach( $config as $k => $v )
		{
			$config[$k] = IPSText::getTextClass('bbcode')->xssHtmlClean( $v );
		}

		//-----------------------------------------
		// Get cblock
		//-----------------------------------------
		
		$cblock	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_cblocks', 'where' => "cblock_id={$id}" ) );
		
		//-----------------------------------------
		// Is it ours
		//-----------------------------------------

		if( $this->memberData['member_id'] != $cblock['member_id'] )
		{
			$this->returnString( 'error' );
		}
	
		//-----------------------------------------
		// Grab teh lib
		//-----------------------------------------

		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
		$cblock_lib	 = new $classToLoad( $this->registry );
		$cb_plugin	= $cblock_lib->getPlugin( $id );

		//-----------------------------------------
		// What's with all the security?
		//-----------------------------------------
		
		if( $this->memberData['member_id'] != $cblock_lib->cblock['member_id'] )
		{
			$this->returnString( 'error' );
		}
		
		//-----------------------------------------
		// I trust that we can edit inline (angel)
		//-----------------------------------------
		
		$this->settings[ 'blog_inline_edit'] =  1 ;
		
		//-----------------------------------------
		// Update the config
		//-----------------------------------------

		$cb_plugin->saveConfig( $config );
		$cblock_lib->cblock['cblock_config'] = serialize( $config );
		$this->DB->update( 'blog_cblocks', array( 'cblock_config' => $cblock_lib->cblock['cblock_config'] ), "cblock_id={$id}" );		
		
		/* Update cache */
		$cblock_lib->dropCache( $this->blog['blog_id'] );
		
		//-----------------------------------------
		// Return HTML or tell page to refresh
		//-----------------------------------------

		if( $cb_plugin->js_block )
		{
			$this->returnString( 'refresh' );	
		}
		else
		{
			$this->returnString( $cblock_lib->wrapPluginOutput( $cb_plugin, $cb_plugin->getBlock( $cblock_lib->cblock ) ) );
		}
	}

	/**
	* Save the new cblock position
	*
	* @access	protected
	* @param	boolean		Whether or not to output (vs returning)
	* @return	mixed
	*/	
	protected function _savePosition( $output=true )
    {
		$cb_id		= intval( $this->request['oldid'] );
		$new_id		= isset( $this->request['newid'] ) ? intval( $this->request['newid'] ) : 0;
		$pos		= $this->request['pos'] == 'l' ? "left" : "right";
		$new_order	= 0;
		
		IPSDebug::fireBug( 'info', array( 'Updating block position...' ) );

        $this->DB->build( array( 'select' => 'cblock_id, cblock_order', 'from' => 'blog_cblocks', 'where' => "member_id={$this->memberData['member_id']} and cblock_id<>" . $cb_id, 'order' => 'cblock_order ASC' ) );
        $qid = $this->DB->execute();

		while ( $cblock = $this->DB->fetch( $qid ) )
		{
			$new_order++;

			if ( $cblock['cblock_id'] == $new_id )
			{
				$this->DB->update( 'blog_cblocks', array ( 'cblock_order' => $new_order, 'cblock_position' => $pos ), "member_id = {$this->memberData['member_id']} and cblock_id={$cb_id}" );
				$new_order++;
			}

			if ( $new_order != $cblock['cblock_order'] )
			{
				$this->DB->update( 'blog_cblocks', array ( 'cblock_order' => $new_order ), "member_id = {$this->memberData['member_id']} and cblock_id={$cblock['cblock_id']}" );
			}
		}

		if ( !$new_id )
		{
			$this->DB->update( 'blog_cblocks', array ( 'cblock_order' => $new_order + 1, 'cblock_position' => $pos ), "member_id = {$this->memberData['member_id']} and cblock_id={$cb_id}" );
		}
		
		IPSDebug::fireBug( 'info', array( 'Done.' ) );
		
		/* Update cache */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
		$cblock_lib	 = new $classToLoad( $this->registry );
		$cblock_lib->recacheAllBlocks( $this->blog['blog_id'] );
		
		if ( $output )
		{
			$this->returnString( 'ok' );
		}
	}

	/**
	* Remove a custom block
	*
	* @access	protected
	* @return	@e void
	*/	
	protected function _removeCblock()
	{
		/* INIT */
		$cb_id = intval( $this->request['cbid'] );
		
		/* Check Permission */
		if( ! $this->settings['blog_allow_cblockchange'] )
		{
			$this->returnString( "error" );
		}

		/* Load the Content Block lib */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
		$cblock_lib	 = new $classToLoad( $this->registry );
		$cblock_name = $cblock_lib->getCblockName( $cb_id );
		
		/* Remove the block */
		if( $cblock_name )
		{
			$this->DB->update( 'blog_cblocks', array( 'cblock_show' => 0 ), "blog_id = {$this->blog['blog_id']} and cblock_id={$cb_id}" );
			
			/* Update cache */
			$cblock_lib->recacheAllBlocks( $this->blog['blog_id'] );
			
			$this->returnJsonArray( $cblock_name );
		}
		else
		{
			$this->returnString();
		}
	}

	/**
	* Delete a custom block
	*
	* @access	protected
	* @return	@e void
	*/	
	protected function _deleteCblock()
	{
    	if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->returnString();
		}

		$cb_id = intval( $this->request['cbid'] );
		
		//-----------------------------------------
		// Get the content block details
		//-----------------------------------------

		$cblock = $this->DB->buildAndFetch( array( 
													'select'	=> "bc.*",
													'from'		=> array('blog_cblocks' => 'bc'),
													'add_join'	=> array(
																		array( 
																				'select' => 'bcc.*',
																				'from'	 => array( 'blog_custom_cblocks' => 'bcc' ),
																				'where'	 => "bc.cblock_ref_id=bcc.cbcus_id and bc.cblock_type='custom'",
																				'type'	 => 'inner'
																			)
																		),
													'where'		=> "bc.cblock_id = {$cb_id}"
										)	);

		if ( $cblock['cblock_type'] == 'custom' )
		{
			$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php', 'class_attach' );
			$class_attach = new $classToLoad( $this->registry );
			$class_attach->type = 'blogcblock';
			$class_attach->init();

			$class_attach->bulkRemoveAttachment( array( $cblock['cbcus_id'] ) );

			$this->DB->delete( 'blog_custom_cblocks', "cbcus_id = {$cblock['cbcus_id']}" );
			$this->DB->delete( 'blog_cblocks', "cblock_id = {$cblock['cblock_id']}" );
			
			/* Update cache */
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
			$cblock_lib	 = new $classToLoad( $this->registry );
			$cblock_lib->recacheAllBlocks( $this->blog['blog_id'] );
			
			$this->returnString( $cblock['cbcus_name'] );
		}
		else
		{
			$this->returnString( "error" );
		}
	}

	/**
	* Enable a custom block
	*
	* @access	protected
	* @return	@e void
	*/	
	protected function _enableCblock()
	{
		//-----------------------------------------
		// Enable the cblock
		//-----------------------------------------
		
		$cb_id = intval( $this->request['cbid'] );

		$this->DB->update( 'blog_cblocks', array( 'cblock_show' => 1 ), "blog_id = {$this->blog['blog_id']} and cblock_id={$cb_id}" );

		//-----------------------------------------
		// Set position
		//-----------------------------------------
		
		$this->request[ 'oldid'] =  $cb_id ;
		$this->request[ 'pos'] =  'r' ;
		$this->_savePosition( 0 );

		/* Update cache */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
		$cblock_lib	 = new $classToLoad( $this->registry, $this->blog );
		$cblock_lib->recacheAllBlocks( $this->blog['blog_id'] );
		
		$this->_displayCblock( $cb_id );
	}

	/**
	* Add a custom block
	*
	* @access	protected
	* @return	@e void
	*/	
	protected function _addCblock()
	{
		//-----------------------------------------
		// Add the cblock
		//-----------------------------------------
		
		$cb_id = intval( $this->request['cbid'] );
		
	
		/* Default cBlocks Record */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'blog_default_cblocks',
								 'where'  => 'cbdef_enabled=1 and cbdef_id IN(' . $cb_id . ')' ) );
								 
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			if ( $row['cbdef_id'] )
			{
				$this->DB->insert( 'blog_cblocks', array( 'blog_id'       => $this->blog['blog_id'],
														  'member_id'     => $this->memberData['member_id'],
														  'cblock_order'  => 999,
														  'cblock_show'   => 0,
														  'cblock_type'   => 'default',
														  'cblock_ref_id' => $cb_id ) );
														  
				$this->request['cbid'] = $this->DB->getInsertId();
			}
		}
		
		//-----------------------------------------
		// And then enable..
		//-----------------------------------------
		
		$this->_enableCblock();
	}

	/**
	* Display Content Block (return Block HTML)
	*
	* @access	public
	* @param	integer		$cb_id
	* @return	@e void
	*/	
    public function _displayCblock( $cb_id )
    {
		$this->settings[ 'blog_inline_edit'] =  1 ;

		//-----------------------------------------
		// Load the Content Block lib
		//-----------------------------------------
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
		$cblock_lib	 = new $classToLoad( $this->registry );
		$html		 = $cblock_lib->get_cblock_html( $cb_id );

		if ( !$html )
		{
			$this->returnJsonError( 'no_html' );
		}
		
		$content = array( 'cb_id' => $cb_id, 'cb_html' => $html );
		
		$this->returnJsonArray( $content );
	}
}