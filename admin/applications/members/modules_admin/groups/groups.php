<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Group management
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_members_groups_groups extends ipsCommand
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
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_groups');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=groups&amp;section=groups';
		$this->form_code_js	= $this->html->form_code_js	= 'module=groups&section=groups';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_groups' ) );

		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'groups_add' );
				$this->_saveGroup('add');
			break;
				
			case 'add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'groups_add' );
				$this->_groupForm('add');
			break;
				
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'groups_edit' );
				$this->_groupForm('edit');
			break;
			
			case 'doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'groups_edit' );
				$this->_saveGroup('edit');
			break;
			
			case 'delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'groups_delete' );
				$this->_deleteForm();
			break;
			
			case 'dodelete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'groups_delete' );
				$this->_doDelete();
			break;

			case 'master_xml_export':
				$this->masterXMLExport();
			break;
			
			case 'groups_overview':
			default:
				$this->_mainScreen();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * List the groups
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _mainScreen()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$g_array = array();
		$content = "";
		$form    = array();
		$rows    = array();

		//-----------------------------------------
		// Get groups
		//-----------------------------------------
		
		$this->DB->build( array( 'select'	=> 'g_id, g_access_cp, g_is_supmod, g_title, prefix, suffix',
								 'from'		=> 'groups',
								 'order'    => 'g_title' ) );
		$o = $this->DB->execute();
		
		while ( $row = $this->DB->fetch( $o ) )
		{
			$_extra = ( $this->request['showSecondary'] ) ? ' OR mgroup_others LIKE \'%,' . intval($row['g_id']) . ',%\'' : '';
			
			/* As much as I hate nested queries, this is actually quicker especially when you add in the LIKE check.*/
			$count = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as count',
										 			  'from'	=> 'members',
										 			  'where'   => 'member_group_id=' . intval($row['g_id']) . $_extra ) );
			$row['count'] = $count['count'];
			$rows[ $row['g_id'] ] = $row;
		}
		
		foreach( $rows as $row )
		{
			//-----------------------------------------
			// Set up basics
			//-----------------------------------------
			
			$row['_can_delete']		= ( $row['g_id'] > 4 ) ? 1 : 0;
			$row['_can_acp']		= ( $row['g_access_cp'] == 1 ) ? 1 : 0;
			$row['_can_supmod']		= ( $row['g_is_supmod'] == 1 ) ? 1 : 0;

			//-----------------------------------------
			// Add
			//-----------------------------------------
			
			$content .= $this->html->groupsOverviewRow( $row );
			
			//-----------------------------------------
			// Add to array
			//-----------------------------------------
									     
			$g_array[] = array( $row['g_id'], $row['g_title'] );
		}

		
		//-----------------------------------------
		// And output
		//-----------------------------------------

		$this->registry->output->html .= $this->html->groupsOverviewWrapper( $content, $g_array );
	}

	/**
	 * Exports the groups to a master XML file for distribution
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function masterXMLExport()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$entry = array();
		$skip  = IPSLib::fetchNonDefaultGroupFields();
		
		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/

		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		$xml->newXMLDocument();
		$xml->addElement( 'export' );
		$xml->addElement( 'group', 'export' );

		//-----------------------------------------
		// Grab our default 6 groups
		//-----------------------------------------
	
		$this->DB->build( array( 'select' => '*',
								'from'   => 'groups',
								'order'  => 'g_id ASC',
								'limit'  => array( 0, 6 )
						) 		);
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$content		= array();
			$r['g_icon']	= '';
			
			if( is_array($skip) AND count($skip) )
			{
				foreach( $skip as $_kk )
				{
					unset( $r[ $_kk ] );
				}
			}
			
			$xml->addElementAsRecord( 'group', 'row', $r );
		}

		//-----------------------------------------
		// Print to browser
		//-----------------------------------------
		
		$this->registry->output->showDownload( $xml->fetchDocument(), 'groups.xml', '', 0 );
	}
	

	/**
	 * Form to delete a group
	 *
	 * @return	@e void		[Outputs to screen]
	 * @todo	Change the "group_id < 6" check for 3.4 with "< 5" as we're going to remove the banned group most likely
	 */
	protected function _deleteForm()
	{
		$group_id	= intval($this->request['id']);
		$group		= $this->caches['group_cache'][ $group_id ];

		if ( !$group_id )
		{
			$this->registry->output->showError( $this->lang->words['g_whichgroup'], 1120 );
		}
		
		if ( $group_id < 6 )
		{
			$this->registry->output->showError( $this->lang->words['g_preset'], 1121 );
		}
		
		if( $group['g_access_cp'] )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'groups_delete_admin' );
		}

		//-----------------------------------------
		// How many users will we be moving?
		//-----------------------------------------
		
		$black_adder = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as users', 'from' => 'members', 'where' => "member_group_id=" . $group_id ) );
		$extra_group = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as users', 'from' => 'members', 'where' => "mgroup_others LIKE '%,". $group_id .",%'" ) );

		$black_adder['users']	= $black_adder['users'] > 0 ? $black_adder['users'] : 0;
		$extra_group['users']	= $extra_group['users'] > 1 ? $extra_group['users'] : 0;

		$this->registry->output->html .= $this->html->groupDelete( $group, $black_adder['users'], $extra_group['users'] );
	}
	
	/**
	 * Delete the group
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _doDelete()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['id']	= intval($this->request['id'] );
		$this->request['to_id']	= intval($this->request['to_id'] );
		
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------

		ipsRegistry::getClass('adminFunctions')->checkSecurityKey( $this->request['secure_key'] );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $this->request['id'] )
		{
			$this->registry->output->showError( $this->lang->words['g_whichgroup'], 1122 );
		}
		
		if ( $this->request['id'] < 6 )
		{
			$this->registry->output->showError( $this->lang->words['g_preset'], 1124 );
		}
		
		if ( ! $this->request['to_id'] )
		{
			$this->registry->output->showError( $this->lang->words['g_mecries'], 1123 );
		}

		//-----------------------------------------
		// Check to make sure that the relevant groups exist.
		//-----------------------------------------
		
		$original		= $this->caches['group_cache'][ $this->request['id'] ];
		$move_to		= $this->caches['group_cache'][ $this->request['to_id'] ];
		
		if( !count($original) )
		{
			$this->registry->output->showError( $this->lang->words['g_whichgroup'], 1125 );
		}
		
		if( !count($move_to) )
		{
			$this->registry->output->showError( $this->lang->words['g_mecries'], 1126 );
		}
		
		//-----------------------------------------
		// Check restrictions.
		//-----------------------------------------
				
		if( $original['g_access_cp'] )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'groups_delete_admin' );
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_move_admin1', 'members', 'members' );
		}
		else if( $move_to['g_access_cp'] )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_move_admin2', 'members', 'members' );
		}

		//-----------------------------------------
		// Move and delete
		//-----------------------------------------
		
		$this->DB->update( 'members', array( 'member_group_id' => $this->request['to_id'] ), 'member_group_id=' . $this->request['id'] );
		$this->DB->delete( 'groups', "g_id=" . $this->request['id'] );
		$this->DB->delete( 'admin_permission_rows', "row_id_type='group' AND row_id=" . $this->request['id'] );
		
		//-----------------------------------------
		// Can't promote to non-existent group
		//-----------------------------------------
		
		foreach( $this->cache->getCache('group_cache') as $row )
		{
			$promotion = explode( '&', $row['g_promotion'] );
			
			if( $promotion[0] == $this->request['id'] )
			{
				$this->DB->update( 'groups', array( 'g_promotion' => '-1&-1' ), 'g_id=' . $row['g_id'] );
			}
		}
		
		//-----------------------------------------
		// Remove from moderators table
		//-----------------------------------------
		
		$this->DB->delete( 'moderators', "is_group=1 AND group_id=" . $this->request['id'] );

		//-----------------------------------------
		// Remove as a secondary group
		//-----------------------------------------
		
		$this->DB->build( array( 
										'select'	=> 'member_group_id, mgroup_others, member_id', 
										'from'		=> 'members', 
										'where'		=> "mgroup_others LIKE '%," . $this->request['id'] . ",%'" 
								) 		);
		$exg = $this->DB->execute();
		
		while( $others = $this->DB->fetch($exg) )
		{
			$extra		= array();
			$extra		= explode( ",", IPSText::cleanPermString( $others['mgroup_others'] ) );
			$to_insert	= array();

			if( count( $extra ) )
			{
				foreach( $extra as $mgroup_other )
				{
					if( $mgroup_other != $this->request['id'] )
					{
						if( $mgroup_other != "" )
						{
							$to_insert[] = $mgroup_other;
						}
					}
				}

				if( count( $to_insert ) )
				{
					$new_others = ',' . implode( ',', $to_insert ) . ',';
				}
				else
				{
					$new_others = "";
				}

				$this->DB->update( 'members', array( 'mgroup_others' => $new_others ), 'member_id=' . $others['member_id'] );
			}
		}

		//-----------------------------------------
		// Rebuild caches
		//-----------------------------------------
		
		$this->rebuildGroupCache();
		$this->cache->rebuildCache( 'moderators', 'forums' );
	
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['g_removedlog'], $original['g_title'] ) );
		
		$this->registry->output->global_message = $this->lang->words['g_removed'];
		$this->_mainScreen();
	}
	
	
	/**
	 * Save the group [add/edit]
	 *
	 * @param 	string		'add' or 'edit'
	 * @return	@e void		[Outputs to screen]
	 */
	protected function _saveGroup( $type='edit' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$group_id	= intval($this->request['id']);
		$oldGroup	= $this->caches['group_cache'][ $group_id ];
		
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------

		ipsRegistry::getClass('adminFunctions')->checkSecurityKey( $this->request['secure_key'] );
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $this->request['g_title'] )
		{
			$this->registry->output->showError( $this->lang->words['g_title_error'], 1127 );
		}
		
		if( intval($this->request['g_max_mass_pm']) > 500 )
		{
			$this->registry->output->showError( $this->lang->words['g_mass_pm_too_large'], 1127 );
		}
		
		#MSSQL needs a check on this
		if ( IPSText::mbstrlen( $this->request['g_title'] ) > 32 )
		{
			$this->registry->output->showError( $this->lang->words['g_title_error'], 1127 );
		}
		
		if ( $type == 'edit' )
		{
			if ( !$group_id )
			{
				$this->registry->output->showError( $this->lang->words['g_whichgroup'], 1128 );
			}
			
			//-----------------------------------------
			// Check restrictions.
			//-----------------------------------------
					
			if( $this->caches['group_cache'][ $group_id ]['g_access_cp'] )
			{
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'groups_edit_admin' );
			}
		}

		//-----------------------------------------
		// Check restrictions.
		//-----------------------------------------
				
		if( ( $type == 'add' OR !$this->caches['group_cache'][ $group_id ]['g_access_cp'] ) AND $this->request['g_access_cp'] )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'groups_add_admin' );
		}
		
		//-----------------------------------------
		// Sort out the perm mask id things
		//-----------------------------------------
		$new_perm_set_id = 0;
		
		if( $this->request['g_new_perm_set'] )
		{	
			$this->DB->insert( 'forum_perms', array( 'perm_name' => $this->request['g_new_perm_set'] ) );
			$new_perm_set_id = $this->DB->getInsertId();
		}
		else
		{
			if ( !is_array( $this->request['permid'] ) )
			{
				$this->registry->output->showError( $this->lang->words['g_oneperm'], 1129 );
			}
		}
		
		$this->lang->loadLanguageFile( array( 'admin_permissions' ), 'members' );
		
		//-----------------------------------------
		// Some other generic fields
		//-----------------------------------------
		
		$promotion_a	= '-1'; // id
		$promotion_b	= '-1'; // posts
		
		if ( $this->request['g_promotion_id'] AND $this->request['g_promotion_id'] > 0 )
		{
			$promotion_a = $this->request['g_promotion_id'];
			$promotion_b = $this->request['g_promotion_posts'];
		}
		
		if ( $this->request['g_attach_per_post'] and $this->request['g_attach_max'] > 0 )
		{
			if ( $this->request['g_attach_per_post'] > $this->request['g_attach_max'] )
			{
				$this->registry->output->global_message = $this->lang->words['g_pergreater'];
				$this->_groupForm('edit');
				return;
			}
		}
		
		$this->request['p_max']		= str_replace( ":", "", $this->request['p_max'] );
		$this->request['p_width']	= str_replace( ":", "", $this->request['p_width'] );
		$this->request['p_height']	= str_replace( ":", "", $this->request['p_height'] );
		
		$sig_limits		= array(
								$this->request['use_signatures'],
								$this->request['max_images'],
								$this->request['max_dims_x'],
								$this->request['max_dims_y'],
								$this->request['max_urls'],
								$this->request['max_lines'],
								);
		
		//-----------------------------------------
		// Set the db array
		//-----------------------------------------
				
		$db_string = array( 'g_view_board'			=> intval($this->request['g_view_board']),
							'g_mem_info'			=> intval($this->request['g_mem_info']),
							'g_can_add_friends'		=> intval($this->request['g_can_add_friends']),
							'g_use_search'			=> intval($this->request['g_use_search']),
							'g_edit_profile'		=> intval($this->request['g_edit_profile']),
							'g_use_pm'				=> intval($this->request['g_use_pm']),
							'g_pm_perday'			=> intval($this->request['g_pm_perday']),
							'g_is_supmod'			=> intval($this->request['g_is_supmod']),
							'g_access_cp'			=> intval($this->request['g_access_cp']),
							'g_title'				=> trim($this->request['g_title']),
							'g_access_offline'		=> intval($this->request['g_access_offline']),
							'g_dname_changes'		=> intval($this->request['g_dname_changes']),
							'g_dname_date'			=> intval($this->request['g_dname_date']),
							'prefix'				=> trim( IPSText::safeslashes( $_POST['prefix'] ) ),
							'suffix'				=> trim( IPSText::safeslashes( $_POST['suffix'] ) ),
							'g_hide_from_list'		=> intval($this->request['g_hide_from_list']),
							'g_perm_id'				=> $new_perm_set_id ? $new_perm_set_id : implode( ",", $this->request['permid'] ),
							'g_icon'				=> trim( IPSText::safeslashes( $_POST['g_icon'] ) ),
							'g_attach_max'			=> ( $this->request['g_attach_max'] == '' ) ? 0 : $this->request['g_attach_max'],
							'g_max_messages'		=> intval($this->request['g_max_messages']),
							'g_max_mass_pm'			=> intval($this->request['g_max_mass_pm']),
							'g_pm_flood_mins'		=> intval($this->request['g_pm_flood_mins']),
							'g_search_flood'		=> intval($this->request['g_search_flood']),
							'g_promotion'			=> $promotion_a . '&' . $promotion_b,
							'g_photo_max_vars'		=> $this->request['p_max'].':'.$this->request['p_width'].':'.$this->request['p_height'],
							'g_dohtml'				=> intval($this->request['g_dohtml']),
							'g_bypass_badwords'		=> intval($this->request['g_bypass_badwords']),
							'g_can_msg_attach'		=> intval($this->request['g_can_msg_attach']),
							'g_attach_per_post'		=> intval($this->request['g_attach_per_post']),
							'g_rep_max_positive'	=> intval( $this->request['g_rep_max_positive'] ),
							'g_rep_max_negative'	=> intval( $this->request['g_rep_max_negative'] ),
							'g_signature_limits'	=> implode( ':', $sig_limits ),
							'g_hide_online_list'	=> intval( $this->request['g_hide_online_list'] ),
							'g_displayname_unit'	=> intval( $this->request['g_displayname_unit'] ),
							'g_sig_unit'			=> intval( $this->request['g_sig_unit'] ),
							'g_max_notifications'	=> intval( $this->request['g_max_notifications'] ),
							'g_max_bgimg_upload'    => intval( $this->request['g_max_bgimg_upload'] ),
							'g_bitoptions'			=> IPSBWOPtions::freeze( $this->request, 'groups', 'global' ), # Saves all BW options for all apps
						  );

		$this->DB->setDataType( 'g_title', 'string' );
    	
		//-----------------------------------------
		// Ok? Load interface and child classes
		//-----------------------------------------

		IPSLib::loadInterface( 'admin/group_form.php' );
		
		$_groupPlugins	= array();
		
		foreach( IPSLib::getEnabledApplications() as $app_dir => $app_data )
		{
			if ( is_file( IPSLib::getAppDir( $app_dir ) . '/extensions/admin/group_form.php' ) )
			{
				$_class  = IPSLib::loadLibrary( IPSLib::getAppDir( $app_dir ) . '/extensions/admin/group_form.php', 'admin_group_form__'.$app_dir, $app_dir );
				$_groupPlugins[ $_class ] = new $_class( $this->registry );
				
				$remote  = $_groupPlugins[ $_class ]->getForSave();

				$db_string = array_merge( $db_string, $remote );
			}
		}

		//-----------------------------------------
		// Editing...do an update
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			$this->DB->update( 'groups', $db_string, 'g_id=' . $group_id );
			
			//-----------------------------------------
			// Update moderator table too
			//-----------------------------------------
			
			$this->DB->update( 'moderators', array( 'group_name' => $db_string['g_title'] ), 'group_id=' . $group_id );
			
			$this->cache->rebuildCache( 'moderators', 'forums' );
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['g_editedlog'], $db_string['g_title'] ) );

			$this->registry->output->global_message = $this->lang->words['g_edited'];
		}
		else
		{
			$this->DB->insert( 'groups', $db_string );
			
			$group_id	= $this->DB->getInsertId();
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['g_addedlog'], $db_string['g_title'] ) );

			$this->registry->output->global_message = $this->lang->words['g_added'];
		}
		
		$this->rebuildGroupCache();
		
		//-----------------------------------------
		// Post save callbacks
		//-----------------------------------------
		
		if( count($_groupPlugins) )
		{
			foreach( $_groupPlugins as $_className => $_object )
			{
				if( method_exists( $_object, 'postSave' ) )
				{
					$_object->postSave( $group_id );
				}
			}
		}
		
		if( $new_perm_set_id )
		{
			$from = '';
			
			if( ( $type == 'edit' AND $db_string['g_access_cp'] AND !$oldGroup['g_access_cp'] ) OR ( $type == 'add' AND $db_string['g_access_cp'] ) )
			{
				//-----------------------------------------
				// Do they already have restrictions?
				//-----------------------------------------
				
				$test = $this->DB->buildAndFetch( array( 'select' => 'row_id', 'from' => 'admin_permission_rows', 'where' => "row_id_type='group' AND row_id=" . $group_id ) );
				
				if( !$test['row_id'] )
				{
					$from	= '&amp;_from=group-' . $group_id;
				}
			}
			
			$this->registry->output->global_message	= $this->lang->words['per_saved'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . 'module=groups&amp;section=permissions&amp;do=edit_set_form' . $from . '&amp;id=' . $new_perm_set_id );
		}
		else
		{
			if( ( $type == 'edit' AND $db_string['g_access_cp'] AND !$oldGroup['g_access_cp'] ) OR ( $type == 'add' AND $db_string['g_access_cp'] ) )
			{
				//-----------------------------------------
				// Do they already have restrictions?
				//-----------------------------------------
				
				$test = $this->DB->buildAndFetch( array( 'select' => 'row_id', 'from' => 'admin_permission_rows', 'where' => "row_id_type='group' AND row_id=" . $group_id ) );
				
				if( !$test['row_id'] )
				{
					$this->registry->output->html .= $this->html->groupAdminConfirm( $group_id );
				}
			}

			$this->_mainScreen();
		}
	}
	
	/**
	 * Rebuilds the group cache
	 *
	 * @return	@e void
	 */
	public function rebuildGroupCache()
	{
		$cache	= array();
			
		$this->DB->build( array( 'select'	=> '*',
								 'from'	    => 'groups',
								 'order'	=> 'g_title ASC' ) );
		$this->DB->execute();
		
		while ( $i = $this->DB->fetch() )
		{
			$cache[ $i['g_id'] ] = IPSMember::unpackGroup( $i, false, true );
		}
		
		$this->cache->setCache( 'group_cache', $cache, array( 'array' => 1 ) );
	}

	/**
	 * Show the add/edit group form
	 *
	 * @param 	string		'add' or 'edit'
	 * @return	@e void		[Outputs to screen]
	 * @todo	_initTab doesn't seem to work anymore with the new tabs, need to update each app to have a unique name/id
	 */
	protected function _groupForm( $type='edit' )
	{
		//-----------------------------------------
		// Grab group data and start us off
		//-----------------------------------------
		
		if ($type == 'edit')
		{
			if ($this->request['id'] == "")
			{
				$this->registry->output->showError( $this->lang->words['g_whichgroup'], 11210 );
			}
			
			$group = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'groups', 'where' => "g_id=" . intval($this->request['id']) ) );
			$group = IPSMember::unpackGroup( $group, FALSE, TRUE );
		
			//-----------------------------------------
			// Check restrictions.
			//-----------------------------------------
					
			if( $group['g_access_cp'] )
			{
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'groups_edit_admin' );
			}
		}
		else
		{
			$group				= array();
			
			if( $this->request['id'] )
			{
				$group = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'groups', 'where' => "g_id=" . intval($this->request['id']) ) );
				$group = IPSMember::unpackGroup( $group, FALSE, TRUE );
			}

			$group['g_title']	= 'New Group';
		}

		//-----------------------------------------
		// Grab permission masks
		//-----------------------------------------
		
		$perm_masks = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'forum_perms', 'order' => 'perm_name ASC' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$perm_masks[] = array( $r['perm_id'], $r['perm_name'] );
		}
		
		//-----------------------------------------
		// Ok? Load interface and child classes
		//-----------------------------------------
		
		$blocks	= array( 'tabs' => array(), 'area' => array() );

		IPSLib::loadInterface( 'admin/group_form.php' );
		
		$tabsUsed = 5;
		$firstTab = empty($this->request['_initTab']) ? false : trim($this->request['_initTab']);
		
		foreach( IPSLib::getEnabledApplications() as $app_dir => $app_data )
		{
			if ( is_file( IPSLib::getAppDir( $app_dir ) . '/extensions/admin/group_form.php' ) )
			{
				$_class = IPSLib::loadLibrary( IPSLib::getAppDir( $app_dir ) . '/extensions/admin/group_form.php', 'admin_group_form__'.$app_dir, $app_dir );
				
				if ( class_exists( $_class ) )
				{
					$_object = new $_class( $this->registry );
					
					$data = $_object->getDisplayContent( $group, $tabsUsed );
					$blocks['area'][ $app_dir ] = $data['content'];
					$blocks['tabs'][ $app_dir ] = $data['tabs'];
					
					$tabsUsed = $data['tabsUsed'] ? ( $tabsUsed + $data['tabsUsed'] ) : ( $tabsUsed + 1 );
					
					if ( !empty($this->request['_initTab']) && $this->request['_initTab'] == $app_dir )
					{
						$firstTab = $tabsUsed;
					}
				}
			}
		}

		//-----------------------------------------
		// And output to form
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->groupsForm( $type, $group, $perm_masks, $blocks, $firstTab );
	}
}