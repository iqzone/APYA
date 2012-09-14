<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Custom field management
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

class admin_members_cfields_customfields extends ipsCommand
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
		// Load skin & lang
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_profilefields' );		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_member' ) );
		
		$this->form_code    = $this->html->form_code    = '&module=cfields&amp;section=customfields&amp;';
		$this->js_form_code = $this->html->form_code_js = '&module=cfields&section=customfields&';
		
		//-----------------------------------------
		// switch-a-magoo
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefields_global' );
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefields_add' );
				$this->mainForm( 'add' );
			break;
				
			case 'doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefields_global' );
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefields_add' );
				$this->mainSave( 'add' );
			break;
				
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefields_global' );
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefields_edit' );
				$this->mainForm( 'edit' );
			break;
				
			case 'doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefields_global' );
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefields_edit' );
				$this->mainSave( 'edit' );
			break;
				
			case 'delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefields_global' );
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefields_delete' );
				$this->deleteForm();
			break;
				
			case 'dodelete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefields_global' );
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefields_delete' );	
				$this->doDelete();
			break;

			case 'group_form_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefieldgroups_global' );
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefieldgroups_add' );
				$this->groupForm( 'add' );
			break;
			
			case 'group_form_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefieldgroups_global' );
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefieldgroups_edit' );
				$this->groupForm( 'edit' );
			break;
			
			case 'do_add_group':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefieldgroups_global' );
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefieldgroups_add' );
				$this->groupSave( 'add' );
			break;
			
			case 'do_edit_group':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefieldgroups_global' );
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefieldgroups_edit' );
				$this->groupSave( 'edit' );
			break;
			
			case 'group_form_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefieldgroups_global' );
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefieldgroups_delete' );
				$this->groupDelete();
			break;
			
			case 'reorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefields_global' );
				$this->reorder();
			break;
						
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'profilefields_global' );
				$this->mainScreen();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();		
	}
	
	/**
	 * Remove a profile field group
	 *
	 * @return	@e void
	 */
	public function groupDelete()
	{
		/* ID */
		$id = intval( $this->request['id'] );
		
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['cf_gid'], 11211 );
		}
		
		/* Do the delete */
		$this->DB->delete( 'pfields_groups', "pf_group_id={$id}" );
		
		/* Done */		
		$this->registry->output->global_message = $this->lang->words['cf_gremoved'];
		$this->rebuildCache();
		$this->mainScreen();
	}
	
	/**
	 * Handles the form for adding/editing profile field groups
	 *
	 * @param	string	$mode	Either add or edit	 
	 * @return	@e void
	 */
	public function groupSave( $mode='add' )
	{
		/* Error Checking */
		if( ! $this->request['pf_group_name'] || ! $this->request['pf_group_key'] )
		{
			$this->registry->output->showError( $this->lang->words['cf_completeform'], 11212 );
		}
		
		/* DB Array */
		$db_array = array(
							'pf_group_name' => $this->request['pf_group_name'],
							'pf_group_key'  => str_replace( ' ', '_', $this->request['pf_group_key'] ),
						);
										
		/* Create the field */
		if( $mode == 'add' )
		{
			/* Make sure the key is unique */
			$chk = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'pfields_groups', 'where' => "pf_group_key='{$db_array['pf_group_key']}'" ) );
			
			if( $chk )
			{
				$this->registry->output->showError( $this->lang->words['cf_gkey'], 11213 );
			}
			
			/* Insert the group */
			$this->DB->insert( 'pfields_groups', $db_array );
			
			/* Done */
			$this->registry->output->global_message = $this->lang->words['cf_gadded'];
		}
		/* Edit the field */
		else
		{
			/* ID */
			$id = intval( $this->request['id'] );
			
			if( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['cf_gid'], 11214 );
			}
			
			/* Make sure the key is unique */
			$chk = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'pfields_groups', 'where' => "pf_group_key='{$db_array['pf_group_key']}' AND pf_group_id <> {$id}" ) );
			
			if( $chk )
			{
				$this->registry->output->showError( $this->lang->words['cf_gkey'], 11215 );
			}			
			
			/* Update the group */
			$this->DB->update( 'pfields_groups', $db_array, "pf_group_id={$id}" );	
						
			/* Done */
			$this->registry->output->global_message = $this->lang->words['cf_gmodified'];		
		}
		
		/* Done */
		$this->rebuildCache();
		$this->mainScreen();
	}
	
	/**
	 * Builds the form for adding/editing profile field groups
	 *
	 * @param	string	$mode	Either add or edit
	 * @return	@e void
	 */
	public function groupForm( $mode='add' )
	{
		/* Add Form */
		if( $mode == 'add' )
		{
			/* ID */
			$id = 0;
			
			/* Data */
			$data = array();
			
			/* Text Bits */
			$title = $this->lang->words['cf_gcreate'];
			$do    = 'do_add_group';
		}
		/* Edit Form */
		else
		{
			/* ID */
			$id = intval( $this->request['id'] );
			
			if( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['cf_gid'], 11216 );
			}
			
			/* Data */
			$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'pfields_groups', 'where' => "pf_group_id={$id}" ) );
			
			/* Text Bits */
			$title = $this->lang->words['cf_gedit'];
			$do    = 'do_edit_group';
		}
		
		/* Default Values */
		$data = array(
						'pf_group_name' => $this->request['pf_group_name'] ? $this->request['pf_group_name'] : $data['pf_group_name'],
						'pf_group_key'  => $this->request['pf_group_key']  ? $this->request['pf_group_key']  : $data['pf_group_key'],
					);
		
		/* Output */
		$this->registry->output->html .= $this->html->groupForm( $id, $data, $title, $do );		
	}

	/**
	 * Rebuilds the custom profile field cache
	 *
	 * @return	@e void
	 */
	public function rebuildCache()
	{
		/* INIT */
		$profile_fields = array();
		
		/* Query the fields */
		$this->DB->build( array( 
										'select'   => 'p.*',
										'from'     => array( 'pfields_data' => 'p' ),
										'order'    => 'p.pf_group_id, p.pf_position',
										'add_join' => array(
															array(
																	'select' => 'g.*',
																	'from'   => array( 'pfields_groups' => 'g' ),
																	'where'  => 'p.pf_group_id=g.pf_group_id',
																	'type'   => 'left',
																)
											) 
							)	 );						 
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['pf_group_key'] = $r['pf_group_key'] ? $r['pf_group_key'] : '_other';
			$profile_fields[ $r['pf_id'] ] = $r;
		}
		
		/* Update the cache */		
		$this->cache->setCache( 'profilefields', $profile_fields, array( 'array' => 1 ) );		
	}
	
	/**
	 * Confirm field deletion form
	 *
	 * @return	@e void
	 */
	public function deleteForm()
	{
		/* INI */
		$id = intval( $this->request['id'] );
		
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['cf_gid'], 11217 );
		}
		
		/* Query the field */		
		$this->DB->build( array( 'select' => 'pf_title', 'from' => 'pfields_data', 'where' => "pf_id={$id}" ) );
		$this->DB->execute();
		
		if ( ! $field = $this->DB->fetch() )
		{
			$this->registry->output->showError( $this->lang->words['cf_norow'], 11218 );
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->customProfileFieldDelete( $id, $field['pf_title'] );
	}

	/**
	 * Removes a field from the database
	 *
	 * @return	@e void
	 */
	public function doDelete()
	{
		/* INI */
		$id = intval( $this->request['id'] );
		
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['cf_gid'], 11219 );
		}
		
		/* Query the field */		
		$this->DB->build( array( 'select' => '*', 'from' => 'pfields_data', 'where' => "pf_id={$id}" ) );
		$this->DB->execute();
		
		if ( ! $row = $this->DB->fetch() )
		{
			$this->registry->output->showError( $this->lang->words['cf_norow'], 11220 );
		}
		
		/* Remove the filed */
		$this->DB->dropField( 'pfields_content', "field_{$row['pf_id']}" );		
		$this->DB->delete( 'pfields_data', "pf_id={$id}" );
		
		/* Rebuild the cache and redirect */
		$this->rebuildCache();
		
		$this->registry->output->global_message	= $this->lang->words['cf_removed'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	
	/**
	 * Saves a custom field form
	 *
	 * @param	string		Type (add|edit)
	 * @return	@e void
	 */
	public function mainSave( $type='edit' )
	{
		/* ID */
		$id = intval( $this->request['id'] );
		
		/* Custom Fields Class */
		$classToLoad   = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classCustomFields.php', 'classCustomFields' );
		$cfields_class = new $classToLoad( array() );
		
		if( ! $this->request['pf_title'] )
		{
			$this->registry->output->showError( $this->lang->words['cf_entertitle'], 11221 );
		}
		
		if( ! $this->request['pf_key'] )
		{
			$this->registry->output->showError( $this->lang->words['cf_enterkey'], 11221.5 );
		}
		else
		{
			if( $type == 'edit' )
			{
				$_exist	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'pfields_data', 'where' => "pf_key='{$this->request['pf_key']}' AND pf_id <> {$id}" ) );
			}
			else
			{
				$_exist	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'pfields_data', 'where' => "pf_key='{$this->request['pf_key']}'" ) );
			}
			
			if( $_exist['pf_id'] )
			{
				$this->registry->output->showError( $this->lang->words['cf_duplicatekey'], 11221.6 );
			}
		}
		
		//-----------------------------------------
		// check-da-motcha
		//-----------------------------------------
		
		if( $type == 'edit' )
		{
			if( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['cf_norow'], 11222 );
			}
		}
		
		$content = "";
		
		if( $_POST['pf_content'] != "" )
		{
			$content = $cfields_class->formatContentForSave( $_POST['pf_content'] );
		}
		
		$db_string = array( 'pf_title'			=> $this->request['pf_title'],
						    'pf_desc'			=> $this->request['pf_desc'],
						    'pf_content'		=> IPSText::stripslashes( $content ),
						    'pf_type'			=> $this->request['pf_type'],
						    'pf_not_null'		=> intval( $this->request['pf_not_null'] ),
						    'pf_member_hide'	=> intval( $this->request['pf_member_hide'] ),
						    'pf_max_input'		=> intval( $this->request['pf_max_input'] ),
						    'pf_member_edit'	=> intval( $this->request['pf_member_edit'] ),
						    'pf_position'		=> intval( $this->request['pf_position'] ),
						    'pf_show_on_reg'	=> intval( $this->request['pf_show_on_reg'] ),
						    'pf_input_format'	=> $this->request['pf_input_format'],
						    'pf_admin_only'		=> intval( $this->request['pf_admin_only'] ),
						    'pf_topic_format'	=> IPSText::stripslashes( $_POST['pf_topic_format'] ),
						    'pf_group_id'		=> intval( $this->request['pf_group_id'] ),
						    'pf_icon'			=> trim($this->request['pf_icon']),
						    'pf_key'			=> trim($this->request['pf_key']),
							'pf_search_type'	=> trim($this->request['pf_search_type']),
							'pf_filtering'		=> intval($this->request['pf_filtering']),
						  );
		
						  
		if ($type == 'edit')
		{
			$this->DB->update( 'pfields_data', $db_string, 'pf_id=' . $id );			
			
			$this->registry->output->global_message = $this->lang->words['cf_edited'];			
		}
		else
		{
			$this->DB->insert( 'pfields_data', $db_string );
			$new_id = $this->DB->getInsertId();
			$this->DB->addField( 'pfields_content', "field_$new_id", 'text' );
			$this->DB->optimize( 'pfields_content' );
						
			$this->registry->output->global_message = $this->lang->words['cf_added'];
		}
		
		$this->rebuildCache();
		$this->mainScreen();
	}
	
	/**
	 * Shows a custom field form
	 *
	 * @param	string		Type (add|edit)
	 * @return	@e void
	 */
	public function mainForm( $type='edit' )
	{
		/* INI */
		$id = intval( $this->request['id'] );
		
		/* Custom Fields Class */		
		$classToLoad   = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classCustomFields.php', 'classCustomFields' );
		$cfields_class = new $classToLoad( array() );
		
		/* Type of form */
		if( $type == 'edit' )
		{
			/* Check for id */
			if ( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['cf_gid'], 11223 );
			}
			
			/* Query data */
			$this->DB->build( array( 'select' => '*', 'from' => 'pfields_data', 'where' => "pf_id=" . $id ) );
			$this->DB->execute();		
			$fields = $this->DB->fetch();			
			
			/* Form Setup */
			$form_code = 'doedit';
			$button    = $this->lang->words['cf_editbutton'];
			$title 	= $this->lang->words['cf_pagetitle_edit']; 
		}
		else
		{
			/* Form Setup */
			$form_code = 'doadd';
			$button    = $this->lang->words['cf_addbutton'];
			
			/* Data */
			$fields = array( 'pf_topic_format' => '<span class="ft">{title}:</span><span class="fc">{content}</span>' );
			$title 	= $this->lang->words['cf_pagetitle_add'];
		}

		/* Format Content */					     
		$fields['pf_content'] = $cfields_class->formatContentForEdit($fields['pf_content'] );
		
		/* Form Fields */
		$fields['pf_show_on_reg']	= $this->registry->output->formYesNo( 'pf_show_on_reg', $fields['pf_show_on_reg'] );
		$fields['pf_not_null']		= $this->registry->output->formYesNo( 'pf_not_null', $fields['pf_not_null'] );
		$fields['pf_member_edit']	= $this->registry->output->formYesNo( 'pf_member_edit', $fields['pf_member_edit'] );
		$fields['pf_member_hide']	= $this->registry->output->formYesNo( 'pf_member_hide', $fields['pf_member_hide'] );
		$fields['pf_admin_only']	= $this->registry->output->formYesNo( 'pf_admin_only', $fields['pf_admin_only'] );
		$fields['pf_type']			= $this->registry->output->formDropdown( 'pf_type', $cfields_class->getFieldTypes(), $fields['pf_type'] );
		$fields['pf_icon']			= $this->registry->output->formInput( 'pf_icon', $fields['pf_icon'] );
		$fields['pf_key']			= $this->registry->output->formInput( 'pf_key', $fields['pf_key'] );
		$fields['pf_search_type']	= $this->registry->output->formDropdown( 'pf_search_type', $cfields_class->getFieldSearchTypes(), $fields['pf_search_type'] );
		$fields['pf_filtering']		= $this->registry->output->formYesNo( 'pf_filtering', $fields['pf_filtering'] );
		
		/* Grab the groups */
		$this->DB->build( array( 'select' => '*', 'from' => 'pfields_groups' ) );
		$this->DB->execute();
		
		$_groups = array();
		while( $r = $this->DB->fetch() )
		{
			$_groups[] = array( $r['pf_group_id'], $r['pf_group_name'] );
		}
		
		$fields['pf_group_id'] = $this->registry->output->formDropdown( 'pf_group_id', $_groups, $fields['pf_group_id'] );
		
		/* Output */
		$this->registry->output->html .= $this->html->customProfileFieldForm( $id, $form_code, $button, $fields, $title );
	}

	/**
	 * Lists the current custom profile fields
	 *
	 * @return	@e void
	 */
	public function mainScreen()
	{
		/* Get the fields */
		$this->DB->build( array( 
										'select'   => 'p.*',
										'from'     => array( 'pfields_data' => 'p' ),
										'order'    => 'p.pf_position',
										'add_join' => array(
															array(
																	'select' => 'g.*',
																	'from'   => array( 'pfields_groups' => 'g' ),
																	'where'  => 'p.pf_group_id=g.pf_group_id',
																	'type'   => 'left'
																)															
											)
							)	);
		$this->DB->execute();
		
		/* Loop through and build list */
		$fields = array();
		
		if ( $this->DB->getTotalRows() )
		{
			while ( $r = $this->DB->fetch() )
			{
				/* Hidden */
				$r['_hide'] = 		( $r['pf_member_hide'] ) ? 'tick.png' : 'cross.png';
				$r['_req'] = 		( $r['pf_not_null'] ) ? 'tick.png' : 'cross.png';
				$r['_regi'] = 		( $r['pf_show_on_reg'] ) ? 'tick.png' : 'cross.png';
				$r['_admin'] = 		( $r['pf_admin_only'] ) ? 'tick.png' : 'cross.png';

				/* Add to fields */
				$key = $r['pf_group_name'] ? $r['pf_group_name'] : $this->lang->words['nogroup_other'];
				
				$fields[$key][] = $r;
											 
			}
		}
		
		/* Make sure we have all of the groups
			@link	http://community.invisionpower.com/tracker/issue-33386-custom-fields-groups-dont-appear-after-created-while-is-empty */
		$this->DB->build( array( 'select' => '*', 'from' => 'pfields_groups' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$key			= $r['pf_group_name'] ? $r['pf_group_name'] : $this->lang->words['nogroup_other'];
			$fields[ $key ]	= count($fields[ $key ]) ? $fields[ $key ] : $r['pf_group_id'];
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->customProfileFieldsList( $fields );
	}
	
	/**
	 * Reorder fields
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function reorder()
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
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['fields']) AND count($this->request['fields']) )
 		{
 			foreach( $this->request['fields'] as $this_id )
 			{
 				$this->DB->update( 'pfields_data', array( 'pf_position' => $position ), 'pf_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
 		$this->rebuildCache();

 		$ajax->returnString( 'OK' );
 		exit();
	}
}