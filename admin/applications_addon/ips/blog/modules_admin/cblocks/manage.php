<?php
/**
 * @file		manage.php 	Provides methods to manage the content blocks
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		27th January 2004
 * $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * @version		v2.5.2
 * $Revision: 4 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		admin_blog_cblocks_manage
 * @brief		Provides methods to manage the content blocks
 */
class admin_blog_cblocks_manage extends ipsCommand
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$this->_init();
		
		/* Mapping array to preserver legacy content blocks */
		$this->legacy_blocks = array(
										'get_admin_block'        => 'admin_block',
 										'get_my_categories'      => 'categories',
 										'get_my_tags'            => 'tags',
 										'get_my_search'          => 'search',
 										'get_mini_calendar'      => 'calendar',
 										'get_last_entries'       => 'last_entries',
 										'get_last_comments'      => 'last_comments',
 										'get_albums'             => 'albums',
 										'get_my_picture'         => 'mypicture',
 										'get_random_album_image' => 'rand_album_image',
 										'get_active_users'       => 'active_users',
									);	
										
		/* Load Skin Template */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_blog' );
		
		/* Load Language */
		$this->lang->loadLanguageFile( array( 'admin_blog' ) );
			
		/* URL Bits */
		$this->html->form_code    = $this->form_code    = 'module=cblocks&amp;section=manage&amp;';
		$this->html->form_code_js = $this->form_code_js = 'module=cblocks&section=manage&';

		switch( $this->request['do'] )
		{
			case 'reorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_cmanage' );
				$this->reorder();
			break;			
			
			case 'docblocks':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_cmanage' );
				$this->doCBlocks();
			break;
			
			case 'addcblock':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_cmanage' );
				$this->addCBlock();
			break;
			
			case 'editcblock':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_cmanage' );
				$this->editCBlock();
			break;
			
			case 'doaddcblock':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_cmanage' );
				$this->doAddCBlock();
			break;
			
			case 'doeditcblock':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_cmanage' );
				$this->doEditCBlock();
			break;
			
			case 'dodelcblock':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_cdelete' );
				$this->doDeleteCBlock();
			break;
			
			case 'installcblock':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_cmanage' );
				$this->doInstallCBlock();
			break;
			
			case 'uninstallcblock':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_cmanage' );
				$this->doUninstallCBlock();
			break;
			
			case 'cblocks':
			default:
				$this->viewCBlockIndex();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Setup some basic classes (blogFunctions, contentBlocks)
	 * 
	 * @return	@e void
	 */
	protected function _init()
	{
		// Set up blogFunctions:
		if ( ! $this->registry->isClassLoaded( 'blogFunctions' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
			$this->registry->setClass('blogFunctions', new $classToLoad($this->registry));
		}
		
		// Set up contentBlocks:
		if ( ! $this->registry->isClassLoaded( 'cblocks' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
			$this->registry->setClass('cblocks', new $classToLoad($this->registry));
		}
	}
	
	/**
	 * Uninstall content block
	 *
	 * @return	@e void
	 */
	public function doUninstallCBlock()
	{
		/* INI */
		$cblock_to_remove = $this->request['block'];
		
		/* Get block */
		$block = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_default_cblocks' ) );
		
		/* Delete cblock entry */
		$this->DB->delete( 'blog_default_cblocks', "cbdef_function='{$cblock_to_remove}'" );		
		
		/* Delete user blocks */
		$this->DB->delete( 'blog_cblocks', "cblock_ref_id={$block['cbdef_id']}" );
		
		$this->reCacheCBlocks();
		
		/* Done */
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['cm_uninstalled'], $cblock_to_remove ) );

		$this->registry->output->global_message	= $this->lang->words['cm_cbuninstalled'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Install content block
	 *
	 * @return	@e void
	 */
	public function doInstallCBlock()
	{
		/* INIT */
		$CB_PLUGIN_CONFIG	= array();
		$new_cblock			= $this->request['block'];
		
		/* Get Config */
		if( is_file( IPSLib::getAppDir('blog') . '/extensions/contentBlocks/cb_config_' . $new_cblock . '.php' ) )
		{
			require_once( IPSLib::getAppDir('blog') . '/extensions/contentBlocks/cb_config_' . $new_cblock . '.php' );/*noLibHook*/
		}
		else
		{
			$this->registry->output->showError( $this->lang->words['cm_noconfigdata'], 1166 );
		}
		
		/* Position */
		$row = $this->DB->buildAndFetch( array( 'select' => 'max(cbdef_order) as cbdef_order', 'from' => 'blog_default_cblocks' ) );
		$order = $row['cbdef_order'] + 1;
		
		/* Insert block */
		$this->DB->insert( 'blog_default_cblocks', array( 'cbdef_name' => $CB_PLUGIN_CONFIG['name'], 'cbdef_function' => $new_cblock, 'cbdef_order' => $order ) );
		$this->reCacheCBlocks();
		
		/* Done */
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['cm_installed'], $new_cblock ) );

		$this->registry->output->global_message	= $this->lang->words['cm_cbinstalled'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}

	/**
	 * Content Blocks index page
	 *
	 * @return	@e void
	 */
	public function viewCBlockIndex()
	{
		/* Query custom blocks */
		$this->DB->build( array( 'select'	=> '*', 'from' => 'blog_default_cblocks', 'order' => 'cbdef_order' ) );
		$this->DB->execute();
        
		/* Count Blocks */
		$count_order = $this->DB->getTotalRows();
		
		/* Loop through and build output array */
		$cblocks_output_rows = array();
		$installed_plugins   = array();
		
		while ( $cblock = $this->DB->fetch() )
		{
			/* Enabled Check Box */
			if ( $cblock['cbdef_enabled'] )
			{
				$cblock['_enabled'] = "<input type='checkbox' name='ENABLE_{$cblock['cbdef_id']}' checked value='1'>";
			}
			else
			{
				$cblock['_enabled'] = "<input type='checkbox' name='ENABLE_{$cblock['cbdef_id']}' value='1'>";
			}
			
			/* Default Check Box */
			if ( $cblock['cbdef_default'] )
			{
				$cblock['_default'] = "<input type='checkbox' name='DEFAULT_{$cblock['cbdef_id']}' checked value='1'>";
			}
			else
			{
				$cblock['_default'] = "<input type='checkbox' name='DEFAULT_{$cblock['cbdef_id']}' value='1'>";
			}
			
			/* Locked Check Box */
			if ( $cblock['cbdef_locked'] )
			{
				$cblock['_locked'] = "<input type='checkbox' name='LOCK_{$cblock['cbdef_id']}' checked value='1'>";
			}
			else
			{
				$cblock['_locked'] = "<input type='checkbox' name='LOCK_{$cblock['cbdef_id']}' value='1'>";
			}
			
			/* Order Buttons */
			$cblock['_order'] = '';
			
			if ( $cblock['cbdef_order'] > 1 )
			{
				$cblock['_order'] .= "<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=overview&op=up&id={$cblock['cbdef_id']}' class='fauxbutton'>&#8657;</a>";
			}
			
			if ( $cblock['cbdef_order'] < $count_order )
			{
				$cblock['_order'] .= "<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=overview&op=down&id={$cblock['cbdef_id']}' class='fauxbutton'>&#8659;</a>";
			}
			
			/* Add to the installed list */						
			$installed_plugins[] = isset( $this->legacy_blocks[$cblock['cbdef_function']] ) ? $this->legacy_blocks[$cblock['cbdef_function']] : $cblock['cbdef_function'];
			
			/* Add to output array */
			$cblocks_output_rows[] = $cblock;
		}
		
		// TODO: this is needed to not make the old links plugin re-appear after an upgrade (#34571)
		// We should probably remove it at some point for a new major version or if the files are moved somewhere else
		$installed_plugins[] = 'links';
		
		/* Loop through the plugin directory */
		$new_plugin_list = array();
		
		try
		{
			foreach( new DirectoryIterator( IPSLib::getAppDir('blog') . '/extensions/contentBlocks' ) as $f )
			{
				/* Skip if it's not a php file */
				if( ! preg_match( '#(\.php)$#', $f->getFileName() ) )
				{
					continue;
				}
				
				/* Check the filename */
				preg_match( '#cb_(.*?)_(.*?)(\.php)$#', $f->getFileName(), $match );
				
				/* Installed already? */
				if( ! in_array( $match[2], $installed_plugins ) )
				{
					/* Config? */
					if( $match[1] == 'config' )
					{
						$CB_PLUGIN_CONFIG = array();
						require_once( IPSLib::getAppDir('blog') . '/extensions/contentBlocks/' . $match[0] );/*noLibHook*/
						$new_plugin_list[$match[2]]['config'] = $CB_PLUGIN_CONFIG;
					}
				}			
			}
		} catch ( Exception $e ) {}
						
		/* Found new plugins? */
		$uninstalled_cblocks_output_rows = array();
		
		if( count( $new_plugin_list ) )
		{	        	
			/* Loop through the plugins */
			foreach( $new_plugin_list as $k => $data )
			{
				/* Add the block */
				$data['block'] = $k;
				
				/* Add to output array */
				$uninstalled_cblocks_output_rows[] = $data;     		
			}
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->cBlockList( $cblocks_output_rows, $uninstalled_cblocks_output_rows );
	}

	/**
	 * Show content block add form
	 *
	 * @return	@e void
	 */
	public function addCBlock()
    {
        /* Show the form */
		$this->showCBlockForm( 'doaddcblock', $this->lang->words['cm_addcb'], '', '' );
    }

	/**
	 * Show content block edit form
	 *
	 * @return	@e void
	 */
	public function editCBlock()
    {
		/* Check the ID */
		$cbdef_id = intval( $this->request['id'] );
		
		if( ! $cbdef_id )
		{
			$this->registry->output->showError( $this->lang->words['cm_invalidcb'], 1167 );
		}

		$cblock = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_default_cblocks', 'where' => "cbdef_id={$cbdef_id}" ) );

		if( ! $cblock['cbdef_id'] && $cblock['cbdef_function'] != 'get_admin_block' )
		{
			$this->registry->output->showError( $this->lang->words['cm_invalidcb'], 1168 );
        }

		$cache   = $this->cache->getCache('blog_admin_blocks');
 		$content = $cache[ 'cblock_'.$cbdef_id ];

		$this->showCBlockForm( 'doeditcblock', $this->lang->words['cm_editcb'], $cblock['cbdef_name'], IPSText::getTextClass( 'bbcode' )->preEditParse( $content ), $cbdef_id );
	}

	/**
	 * Show the content block add/edit form
	 *
	 * @param	string		$formcmd		Form title
	 * @param	string		$formheader		Form button
	 * @param	string		$blockname		Content block name
	 * @param	string		$blockcontent	Content block data
	 * @param	integer		$cbdef_id		Content block ID
	 * @return	@e void
	 */
	public function showCBlockForm( $formcmd, $formheader, $blockname, $blockcontent, $cbdef_id='' )
    {
		/* Form Elements */
		$form = array();
		
		$form['cblock_name']    = $this->registry->output->formInput( 'cblock_name', $blockname, '', 60 );
		$form['cblock_content'] = $this->registry->output->formTextarea( 'cblock_content', $blockcontent, 120, 15 );

		/* Output */
		$this->registry->output->html .= $this->html->cBlockForm( $cbdef_id, $formcmd, $formheader, $form );
	}

	/**
	 * Save content block
	 *
	 * @return	@e void
	 */
	public function doAddCBlock()
    {
    	/* Figure out the order position */
		$row   = $this->DB->buildAndFetch ( array( 'select' => 'max(cbdef_order) as cbdef_order', 'from' => 'blog_default_cblocks' ) );
		$order = $row['cbdef_order'] + 1;
		
		/* Format the content */
		$content = IPSText::stripslashes( $_POST['cblock_content'] );
		$content = str_replace( ">", "&gt;", $content );
		$content = str_replace( "<", "&lt;", $content );

		IPSText::getTextClass('bbcode')->parse_html    = 1;
		IPSText::getTextClass('bbcode')->parse_nl2br   = 0;
		IPSText::getTextClass('bbcode')->parse_smilies = 1;
		IPSText::getTextClass('bbcode')->parse_bbcode  = 1;
		$content = IPSText::getTextClass('bbcode')->preDbParse( $content );
		
		/* Build Insert Array */
		$insert = array( 'cbdef_name'		=> $this->request['cblock_name'],
						 'cbdef_function'	=> 'get_admin_block',
						 'cbdef_default'	=> 0,
						 'cbdef_order'		=> $order,
						 'cbdef_locked'		=> 0,
						 'cbdef_enabled'	=> 0
                         );
		
        /* Insert block and get id */
        $this->DB->insert( 'blog_default_cblocks', $insert );
		$cbdef_id = $this->DB->getInsertId();
		
		/* Update the cache */
		$cache = $this->cache->getCache('blog_admin_blocks');

		$cache[ 'cblock_' . $cbdef_id ] = $content;

		$this->cache->setCache( 'blog_admin_blocks', $cache, array( 'array' => 1 ) );

		$this->reCacheCBlocks();

		$this->registry->adminFunctions->saveAdminLog( $this->lang->words['cm_addedcb'] );

		$this->registry->output->global_message	= $this->lang->words['cm_addedcb'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}

	/**
	 * Edit content block
	 *
	 * @return	@e void
	 */
	public function doEditCBlock()
    {
    	/* Check ID */
		$cbdef_id = intval( $this->request['cbdef_id'] );
		
		if( ! $cbdef_id )
		{
			$this->registry->output->showError( $this->lang->words['cm_invalidcb'], 1169 );
        }
		
		/* Get the block */
		$cblock = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_default_cblocks', 'where' => "cbdef_id={$cbdef_id}" ) );

		if( ! $cblock['cbdef_id'] && $cblock['cbdef_function'] != 'get_admin_block' )
		{
			$this->registry->output->showError( $this->lang->words['cm_invalidcb'], 11610 );
		}
		
		/* Change the name */
		$this->DB->update( 'blog_default_cblocks', array( 'cbdef_name' => $this->request['cblock_name'] ), "cbdef_id={$cbdef_id}" );
		
        /* Format the content */
		$content = IPSText::stripslashes( $_POST['cblock_content'] );
		$content = str_replace( ">", "&gt;", $content );
		$content = str_replace( "<", "&lt;", $content );

		IPSText::getTextClass('bbcode')->parse_html    = 0;
		IPSText::getTextClass('bbcode')->parse_nl2br   = 0;
		IPSText::getTextClass('bbcode')->parse_smilies = 1;
		IPSText::getTextClass('bbcode')->parse_bbcode  = 1;
		$content = IPSText::getTextClass('bbcode')->preDbParse( $content );
		
		/* Update the cache */
		$cache = $this->cache->getCache('blog_admin_blocks');

		$cache[ 'cblock_' . $cbdef_id ] = $content;

		$this->cache->setCache( 'blog_admin_blocks', $cache, array( 'array' => 1 ) );

		$this->registry->adminFunctions->saveAdminLog( $this->lang->words['cm_editedcb'] );

		$this->registry->output->global_message	= $this->lang->words['cm_editedcb'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}

	/**
	 * Delete content block
	 *
	 * @return	@e void
	 */
	public function doDeleteCBlock()
	{
		/* Check ID */
		$cbdef_id = intval( $this->request['id'] );
		
		if( ! $cbdef_id )
		{
			$this->registry->output->showError( $this->lang->words['cm_invalidcb'], 11611 );
		}
		
		/* Check the block */
		$cblock = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_default_cblocks', 'where' => "cbdef_id={$cbdef_id}" ) );

		if( ! $cblock['cbdef_id'] && $cblock['cbdef_function'] != 'get_admin_block' )
		{
			$this->registry->output->showError( $this->lang->words['cm_invalidcb'], 11612 );
        }
		
        /* Delete the block */
		$this->DB->delete( 'blog_cblocks'        , "cblock_ref_id={$cbdef_id}" );
		$this->DB->delete( 'blog_default_cblocks', "cbdef_id={$cbdef_id}" );

		/* Update the cache */
		$cache = $this->cache->getCache('blog_admin_blocks');

		unset( $cache[ 'cblock_' . $cbdef_id ] );

		$this->cache->setCache( 'blog_admin_blocks', $cache, array( 'array' => 1 ) );

		$this->reCacheCBlocks();

		$this->registry->adminFunctions->saveAdminLog( $this->lang->words['cm_deletedcb'] );

		$this->registry->output->global_message	= $this->lang->words['cm_deletedcb'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
    }

	/**
	 * Save content block settings
	 *
	 * @return	@e void
	 */
	public function doCBlocks()
	{
		$this->DB->build( array( 'select' => 'cbdef_id', 'from' => 'blog_default_cblocks', 'order' => 'cbdef_id' ) );
		$qid = $this->DB->execute();

		while( $data = $this->DB->fetch($qid) )
		{
			$save_array['cbdef_enabled'] = $this->request[ 'ENABLE_' . $data['cbdef_id'] ] == 1  ? 1 : 0;
			$save_array['cbdef_default'] = $this->request[ 'DEFAULT_' . $data['cbdef_id'] ] == 1 ? 1 : 0;
			$save_array['cbdef_locked']  = $this->request[ 'LOCK_' . $data['cbdef_id'] ] == 1    ? 1 : 0;

			$this->DB->update( 'blog_default_cblocks', $save_array, "cbdef_id={$data['cbdef_id']}" );
 		}

		$this->reCacheCBlocks();

		$this->registry->adminFunctions->saveAdminLog( $this->lang->words['cm_editedcbsettings'] );

		$this->registry->output->global_message	= $this->lang->words['cm_cbsettingsmod'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}

	/**
	 * Reorder cblocks
	 *
	 * @return	@e void
	 */
	public function reorder()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax		 = new $classToLoad();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['cm_keyinvalid'] );
			exit();
		}
 		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['cblocks']) AND count($this->request['cblocks']) )
 		{
 			foreach( $this->request['cblocks'] as $this_id )
 			{
 				$this->DB->update( 'blog_default_cblocks', array( 'cbdef_order' => $position ), 'cbdef_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
 		$this->reCacheCBlocks();

 		$ajax->returnString( 'OK' );
 		exit();
	}

	/**
	 * Recache content blocks
	 *
	 * @return	@e void
	 */
    public function reCacheCBlocks()
    {	
		$this->_init();
		
		//-------------------------------------------------
		// First, a hackish attempt to sort cblock ordering
		//-------------------------------------------------
		
		$order = 1;
		
		$this->DB->build( array( 'select' => 'cblock_order, cblock_id', 'from' => 'blog_cblocks', 'order' => 'cblock_order ASC' ) );
		$o = $this->DB->execute();
		
		while( $k = $this->DB->fetch( $o ) )
		{
			$this->DB->update( 'blog_cblocks', array( 'cblock_order' => $order ), 'cblock_id=' . $k['cblock_id'] );
			$order++;
		}
		
		$order = 1;
		    	
		$this->DB->build( array( 'select' => 'cbdef_order, cbdef_id', 'from' => 'blog_default_cblocks', 'order' => 'cbdef_order ASC' ) );
		$o = $this->DB->execute();
		
		while( $k = $this->DB->fetch($o) )
		{
			$this->DB->update( 'blog_default_cblocks', array( 'cbdef_order' => $order ), 'cbdef_id=' . $k['cbdef_id'] );
			
			$order++;
		}
		
		//-------------------------------------------------
		// Now the cache..
		//-------------------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from'=> 'blog_default_cblocks', 'where' => "cbdef_enabled=1", 'order' => 'cbdef_order' ) );
		$this->DB->execute();
		
		$cache = array();
		while( $defblock = $this->DB->fetch() )
		{
			$cache[$defblock['cbdef_id']] = array( 
													'cbdef_name'		=> $defblock['cbdef_name'],
													'cbdef_function'	=> $defblock['cbdef_function'],
													'cbdef_default'		=> $defblock['cbdef_default'],
													'cbdef_order'		=> $defblock['cbdef_order'],
													'cbdef_locked'		=> $defblock['cbdef_locked']
												);
		}
		
		$this->cache->setCache( 'cblocks', $cache, array( 'array' => 1 ) );
		
		/* Drop local caches */
		$this->registry->getClass('cblocks')->dropCache();
	}
}