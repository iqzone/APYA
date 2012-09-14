<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Bookmarks
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_core_tools_sharelinks extends ipsCommand
{
	/**
	* Skin object
	*
	* @var		object			Skin templates
	*/
	protected $html;
	
	/**
	* Shortcut for url
	*
	* @var		string			URL shortcut
	*/
	protected $form_code;
	
	/**
	* Shortcut for url (javascript)
	*
	* @var		string			JS URL shortcut
	*/
	protected $form_code_js;
	
	/**
	* Main class entry point
	*
	* @param	object		ipsRegistry reference
	* @return	@e void		[Outputs to screen]
	*/	
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin Template */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_tools' );
		
		/* Load Language */
		$this->lang->loadLanguageFile( array( 'admin_tools' ) );
		
		/* URL Bits */
		$this->html->form_code    = $this->form_code    = 'module=tools&amp;section=sharelinks&amp;';
		$this->html->form_code_js = $this->form_code_js = 'module=tools&section=sharelinks&';
		
		switch( $this->request['do'] )
		{
			case 'reorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sharelinks_manage' );
				$this->shareLinksReorder();
			break;
			
			case 'delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sharelinks_manage' );
				$this->sharelinksDelete();
			break;
			
			case 'add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sharelinks_manage' );
				$this->sharelinksForm( 'add' );
			break;
			
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sharelinks_manage' );
				$this->sharelinksForm( 'edit' );
			break;
			
			case 'do_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sharelinks_manage' );
				$this->sharelinksSave( 'add' );
			break;
			
			case 'do_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sharelinks_manage' );
				$this->sharelinksSave( 'edit' );
			break;
		
			case 'index':
			default:
				$this->_sharelinksIndex();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Reorder
	 *
	 * @return	@e void
	 */
	protected function shareLinksReorder()
	{
		/* AJAX Class */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax		 = new $classToLoad();
		
		/* Check security key */
		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
		
		/* Save new positions */
		$position	= 1;
		
		if( is_array( $this->request['share'] ) AND count( $this->request['share'] ) )
		{
			foreach( $this->request['share'] as $this_id )
			{
				$this->DB->update( 'core_share_links', array( 'share_position' => $position ), 'share_id=' . intval( $this_id ) );
				$position++;
			}
		}
		
		/* Rebuild Cache */
		$this->rebuildCache();
		
		/* Done */
		$ajax->returnString( 'OK' );
		exit();
	}
	
	/**
	 * sharelinksDelete
	 *
	 * @return void
	 */	
	function sharelinksDelete()
	{
		/* ID */
		$id = intval( $this->request['share_id'] );
		
		/* Remove */
		$this->DB->delete( 'core_share_links', "share_id={$id}" );
		$this->rebuildCache();
		
		/* Redirect */
	 	$this->registry->output->global_message = $this->lang->words['sl_removed'];
	 	$this->registry->output->silentRedirectWithMessage( "{$this->settings['base_url']}{$this->form_code}" );
	}	
	
	/**
	 * sharelinksSave
	 *
	 * @param  string [$type='add']
	 * @return void
	 */
	function sharelinksSave( $type='add' )
	{
		/* INI */
		$errors = array();
		
		/* Check input */
		if( ! $this->request['share_title'] )
		{
			$errors[] = "<li>{$this->lang->words['sl_must_title']}";
		}

		if( ! $this->request['share_key'] )
		{
			$errors[] = "<li>{$this->lang->words['sl_must_key']}";
		}
		
		if( count( $errors ) )
		{
			$this->registry->output->html .= "<div class='warning'><h4>{$this->lang->words['sl_formerror']}</h4><ul>" . implode( '', $errors ) . "</ul></div><br />";
			$this->sharelinksForm( $type );
			return;
		}
	
	 	/* Data */
	 	$data = array( 	 			
	 					'share_title'		=> $this->request['share_title'],
	 					'share_key'			=> $this->request['share_key'],
	 					'share_enabled'		=> $this->request['share_enabled'],
	 					'share_canonical'	=> $this->request['share_canonical']
	 				);
	 	
	 	/* Check the type */
	 	if( $type == 'add' )
	 	{
	 		$max = $this->DB->buildAndFetch( array( 'select' => 'MAX(share_position) as position', 'from' => 'core_share_links' ) );
	 		
	 		$data['share_position']	= intval($max['position']) + 1;
	 		
	 		/* Insert the record */
	 		$this->DB->insert( 'core_share_links', $data );
	 		$this->rebuildCache();
	 		
	 		/* All done */
	 		$this->registry->output->global_message = sprintf( $this->lang->words['sl_added'], $data['share_title'] );
	 		$this->registry->output->silentRedirectWithMessage( "{$this->settings['base_url']}{$this->form_code}" );
	 	}
	 	else
	 	{
	 		/* ID */
	 		$id = intval( $this->request['id'] );
	 		
	 		/* Update */
	 		$this->DB->update( 'core_share_links', $data, "share_id={$id}" );
	 		$this->rebuildCache();
	 		
	 		/* Done and done */
	 		$this->registry->output->global_message = sprintf( $this->lang->words['sl_updated'], $data['share_title'] );
	 		$this->registry->output->silentRedirectWithMessage( "{$this->settings['base_url']}{$this->form_code}" );
	 	}
	 
	}
	
	/**
	 * Displays the add/edit bookmark form
	 *
	 * @param  string [$type='add']
	 * @return void
	 */
	public function sharelinksForm( $type='add' )
	{
		/* Check form type */
		if( $type == 'add' )
		{
			/* Strings */
			$title  = $this->lang->words['sl_form_new_title'];
			$button = $this->lang->words['sl_form_new_button'];
			$req    = 'do_add';
			
			/* Data */
			$data   = array(
								'share_title'		=> $this->request['share_title'],
								'share_key'			=> $this->request['share_key'],
								'share_enabled'		=> $this->request['share_enabled'],
								'share_canonical'	=> $this->request['share_canonical']
							);
			$id     = 0;
		}
		else
		{
			/* Strings */
			$title  = $this->lang->words['sl_form_edit_title'];
			$button = $this->lang->words['sl_form_edit_button'];
			$req    = 'do_edit';
			
			/* Data */
			$id     = intval( $this->request['share_id'] );
			$data   = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_share_links', 'where' => "share_id={$id}" ) );

			/* Set Defaults */
			$data['share_title']		= !empty( $this->request['share_title'] )		? $this->request['share_title']		: $data['share_title'];
			$data['share_key']			= !empty( $this->request['share_key'] )			? $this->request['share_key']		: $data['share_key'];
			$data['share_enabled']		= !empty( $this->request['share_enabled'] )		? $this->request['share_enabled']	: $data['share_enabled'];
			$data['share_canonical']	= !empty( $this->request['share_canonical'] )	? $this->request['share_canonical']	: $data['share_canonical'];
		}
		
		/* Form Elements */
		$form = array();
		
		$form['share_title']		= $this->registry->output->formInput( 'share_title'		, $data['share_title']	, 'share_title'	, 50 );
		$form['share_key']			= $this->registry->output->formInput( 'share_key'		, $data['share_key']	, 'share_key'	, 50 );
		$form['share_enabled']		= $this->registry->output->formYesNo( 'share_enabled'	, $data['share_enabled'] );
		$form['share_canonical']	= $this->registry->output->formYesNo( 'share_canonical'	, $data['share_canonical'] );

		$this->registry->output->html .= $this->html->sharelinksForm( $id, $req, $title, $button, $form );
	}
	
	/**
	 * List all the the current bookmark types
	 *
	 * @return void
	 */
	protected function _sharelinksIndex()
	{
		/* Init */
		$rows = array();
		
		/* Fetch */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'core_share_links',
								 'order'  => 'share_position ASC, share_id ASC' ) );
							
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$rows[] = $r;
		}
		
		/* End table and output */
        $this->registry->output->html .= $this->html->shareLinksIndex( $rows );
	}
	
	/**
	 * Recaches share links
	 *
	 * @return	@e void
	 */
	public function rebuildCache()
	{
		/* INIT */
		$cache = array();
		$path  = IPS_ROOT_PATH . 'sources/classes/share/plugins/';
		
		/* Fetch */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'core_share_links',
								 'order'  => 'share_position ASC, share_id ASC' ) );
							
		$this->DB->execute();
	
		while( $row = $this->DB->fetch() )
		{
			$_file = $path . IPSText::alphanumericalClean( $row['share_key'] ) . '.php';
			
			if ( is_file( $_file ) )
			{
				$classToLoad = IPSLib::loadLibrary( $_file, 'sl_' . $row['share_key'] );
				$test        = new $classToLoad( $this->registry );
				
				if ( method_exists( $test, 'customOutput' ) )
				{
					$row['customOutput'] = $test->customOutput();
					if ( !isset( $row['customOutput'][2] ) )
					{
						$row['customOutput'][2] = array();
					}
				}
			}
			
			$cache[ $row['share_key'] ] = $row;
		}
		
		$this->cache->setCache( 'sharelinks', $cache, array( 'array' => 1 ) );
	}
}