<?php
/**
 * @file		reports.php 	Management for reported content
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @author		Based on original "Report Center" by Luke Scott
 * @since		-
 * $LastChangedDate: 2012-04-24 06:56:18 -0400 (Tue, 24 Apr 2012) $
 * @version		v3.3.3
 * $Revision: 10627 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		admin_core_report_reports
 * @brief		Management for reported content
 */
class admin_core_report_reports extends ipsCommand 
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
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_reports');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=report&amp;section=reports';
		$this->form_code_js	= $this->html->form_code_js	= 'module=report&section=reports';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_posts' ) );

		//-----------------------------------------
		// How would you like your eggs cooked?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'plugin_toggle':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'plugins_manage' );
				$this->_togglePlugin();
			break;
			case 'create_plugin':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'plugins_manage' );
				$this->_createPlugin();
			break;
			case 'edit_plugin':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'plugins_manage' );
				$this->_editPlugin();
			break;
			case 'change_plugin':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'plugins_manage' );
				$this->_changePlugin();
			break;
			case 'remove_plugin':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'plugins_remove' );
				$this->_removePlugin();
			break;
			
			default:
			case 'plugin':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'plugins_manage' );
				$this->_showPluginIndex();
			break;
			
			case 'remove_image':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_remove' );
				$this->_removeImage();
			break;
			case 'add_image':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_addImage();
			break;
			case 'edit_image':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_editImage();
			break;
			case 'status':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_showStatusIndex();
			break;
			case 'set_status':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_setStatus();
			break;
			case 'remove_status':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_removeStatus();
			break;
			case 'create_status':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_createStatus();
			break;
			case 'edit_status':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_editStatus();
			break;
			case 'reorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'sands_manage' );
				$this->_moveStatus();
			break;
		}

		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Displays the overview page with statistics, graphs, etc...
	 *
	 * @return	@e void
	 */
	public function _mainScreen()
	{
		/* Get some data */
		$_tmp = $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'rc_reports' ) );
		$num_reports = intval($_tmp['total']);
		
		$_tmp = $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'rc_comments' ) );
		$num_comments = intval($_tmp['total']);
		
		$this->DB->build( array( 'select' => 'onoff', 'from' => 'rc_classes' ) );
		$this->DB->execute();
		
		$plug_on	= 0;
		$plug_total	= 0;
		
		while( $plugs = $this->DB->fetch() )
		{
			if( $plugs['onoff'] == 1 )
			{
				$plug_on++;
			}

			$plug_total++;
		}

		$this->registry->output->html .= $this->html->overview_main_template( array(
																					'reports_total'		=> $num_reports,
																					'comments_total'	=> $num_comments,
																					'active_plugins'	=> $plug_on,
																					'total_plugins'		=> $plug_total,
																			)		);
	}
	

	/**
	 * This function is used when you disable/enable a report plugin
	 *
	 * @return	@e void
	 */
	public function _togglePlugin()
	{
		$plugin_id = intval($this->request['plugin_id']);
		
		//--------------------------------------------
		// Checks...
		//--------------------------------------------
		
		if ( ! $plugin_id )
		{
			$this->registry->output->global_error = $this->lang->words['r_noid'];
			$this->_showPluginIndex();
			return;
		}

		//--------------------------------------------
		// Get from database
		//--------------------------------------------
		
		$component		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_classes', 'where' => 'com_id=' . $plugin_id ) );
		
		$com_enabled	= $component['onoff'] ? 0 : 1;
		
		$this->DB->update( 'rc_classes', array( 'onoff' => $com_enabled ), 'com_id=' . $plugin_id );
		
		$this->registry->output->global_message = $this->lang->words['r_toggle'];
		$this->_showPluginIndex();
	}

	/**
	 * Basics for creating a plugin (with only file name, title, and description)
	 *
	 * @return	@e void
	 */
	public function _createPlugin()
	{
		$this->registry->output->extra_nav[] = array( "{$this->settings['base_url']}{$this->form_code}&amp;do=plugin" , $this->lang->words['r_plugmanager'] );
		
		if( $_POST['finish'] == 1 )
		{
			//-----------------------------------------
			// Make sure stuff is right
			//-----------------------------------------
			
			if( ! $_POST['plugi_title'] || ! $_POST['plugi_desc'] || ! $_POST['plugi_file'] )
			{
				$this->registry->output->global_error = $this->lang->words['r_missingfield'];
			}
			elseif( preg_match("/[^a-z0-9\-_]/i", $this->request['plugi_file'] ) )
			{
				$this->registry->output->global_error = $this->lang->words['r_incchar'];
			}
			elseif( ! is_file( IPSLib::getAppDir( $this->request['appPlugin'] ) . '/extensions/reportPlugins/' . $this->request['plugi_file'] . '.php' ) )
			{
				$this->registry->output->global_error = $this->lang->words['r_404file'];
			}
			
			//-----------------------------------------
			// If no errors, create the plugin
			//-----------------------------------------
		
			if( ! $this->registry->output->global_error )
			{	
				$build_plugin = array(
									'onoff'			=> 1,
									'class_title'	=> $this->request['plugi_title'],
									'class_desc'	=> IPSText::stripslashes( $_POST['plugi_desc'] ),
									'author'		=> $this->request['plugi_author'],
									'author_url'	=> $this->request['plugi_author_url'],
									'my_class'		=> $this->request['plugi_file'],
									'pversion'		=> 'v' . strval($this->request['plugi_version']),
									'lockd'			=> 0,
									'app'			=> $this->request['appPlugin']
				);
				
				$this->DB->insert( 'rc_classes', $build_plugin );

				$plugin_id = $this->DB->getInsertId();
				
				//-----------------------------------------
				// Redirect to edit the plugin for more
				//-----------------------------------------
				
				$this->registry->output->global_message	= $this->lang->words['r_plugincreate'];
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . "&amp;do=edit_plugin&amp;com_id=" . $plugin_id );
			}
		}

		//-----------------------------------------
		// Show the form
		//-----------------------------------------

		$this->registry->output->html .= $this->html->pluginForm();
	}

	/**
	 * Here we are editing a plugin's settings
	 *
	 * @return	@e void
	 */
	public function _editPlugin()
	{
		$this->registry->output->extra_nav[] = array( "{$this->settings['base_url']}{$this->form_code}&do=plugin" , $this->lang->words['r_plugmanager'] );
		
		$plug_id = intval( $this->request['com_id'] );
		
		//-----------------------------------------
		// Make sure the plug ID is not zero...
		//-----------------------------------------
		
		if( $plug_id < 1 )
		{
			$this->registry->output->global_error = $this->lang->words['r_noid'];
			$this->_showPluginIndex();
			return;
		}
		
		//-----------------------------------------
		// Pull up the plugin information...
		//-----------------------------------------
	
		$plug_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_classes', 'where' => "com_id='{$plug_id}'" ) );

		//-----------------------------------------
		// Does this plugin even exist...?
		//-----------------------------------------
		
		if( !$plug_data['com_id'] )
		{
			$this->registry->output->global_error = $this->lang->words['r_plugnoexist'];
			$this->_showPluginIndex();
			return;
		}
		
		//-----------------------------------------
		// Load the plugin and it's information
		//-----------------------------------------

		if( $plug_data['my_class'] == '' )
		{
			$plug_data['my_class'] = 'default';
		}
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( $plug_data['app'] ) . '/extensions/reportPlugins/' . $plug_data['my_class'] . '.php', $plug_data['my_class'] . '_plugin', $plug_data['app'] );
		$plugin = new $classToLoad( $this->registry );
		
		//-----------------------------------------
		// Load the plugin's extra data settings
		//-----------------------------------------
		
		if( $plug_data['extra_data'] && $plug_data['extra_data'] != 'N;' )
		{
			$plugin->_extra = unserialize( $plug_data['extra_data'] );
		}
		else
		{
			$plugin->_extra = array();
		}
		
		if( $_POST['finish'] == 1 )
		{
			//-----------------------------------------
			// Form was sent, so let's process stuff
			//-----------------------------------------

			$plugin_error = $plugin->processAdminForm( $plugin->_extra );
			
			if( $plugin_error )
			{
				$this->registry->output->global_error = $plugin_error;
			}
			
			if( is_array( $_POST['plugi_can_report'] ) )
			{
				$p_can_report = ',' . implode( ',', $_POST['plugi_can_report'] ) . ',';
			}
			
			if( is_array( $_POST['plugi_gperm'] ) )
			{
				$p_can_gperm = ',' . implode( ',', $_POST['plugi_gperm'] ) . ',';
			}
			
			$build_plugin = array(
								'onoff'				=> $this->request['plugi_onoff'],
								'group_can_report'	=> $p_can_report,
								'mod_group_perm'	=> $p_can_gperm,
								'extra_data'		=> serialize( $plugin->_extra ),
								'app'				=> $this->request['appPlugin']
								);
			
			if( $plug_data['lockd'] == 0 || IN_DEV == 1 )
			{
				$build_plugin['lockd'] = intval($this->request['plugi_lockd']);
			}
			
			if( ! $this->registry->output->global_error )
			{
				$this->DB->update( 'rc_classes', $build_plugin, "com_id={$plug_id}" );
				
				$this->cache->rebuildCache( 'report_plugins', 'global' );
				
				$this->registry->output->global_message	= $this->lang->words['r_plugupdated'];
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . "&amp;do=plugin" );
			}
			else
			{
				$plug_data = $build_plugin;
			}
		}
		
		//-----------------------------------------
		// Break up group perms into an array
		//-----------------------------------------
						   
		if( $plug_data['group_can_report'] )
		{
			$sel_can_report = explode( ',' , $plug_data['group_can_report'] );
		}
		
		if( $plug_data['mod_group_perm'] )
		{
			$sel_group_perm = explode( ',' , $plug_data['mod_group_perm'] );
		}

		//-----------------------------------------
		// Display special plugin settings here...
		//-----------------------------------------
		
		$extraForm = $plugin->displayAdminForm( $plugin->_extra, $this->html );

		$this->registry->output->html .= $this->html->finishPluginForm( $plug_data, $sel_can_report, $sel_group_perm, $extraForm );
	}
	
	/**
	 * Lock/unlock a plugin
	 *
	 * @return	@e void
	 */
	public function _changePlugin()
	{
		$this->registry->output->extra_nav[]		= array( "{$this->settings['base_url']}{$this->form_code}&do=plugin" , $this->lang->words['r_plugmanager'] );
		
		$plug_id = intval( $this->request['com_id'] );
		
		//-----------------------------------------
		// Make sure plugin ID is > than zero...
		//-----------------------------------------

		if( $plug_id < 1 )
		{
			$this->registry->output->global_error = $this->lang->words['r_noid'];
			$this->_showPluginIndex();
			return;
		}
		
		//-----------------------------------------
		// Load basic, very basic, information...
		//-----------------------------------------
		
		$plug_data = $this->DB->buildAndFetch( array( 'select' => 'com_id, my_class, class_title, class_desc, author, author_url, pversion', 'from' => 'rc_classes', 'where' => "com_id='{$plug_id}'" ) );
		
		//-----------------------------------------
		// Does our plugin even exist...?
		//-----------------------------------------
		
		if( !$plug_data['com_id'] )
		{
			$this->registry->output->global_error = $this->lang->words['r_plugnoexist'];
			$this->_showPluginIndex();
			return;
		}
		
		//-----------------------------------------
		// Can we even change this plugin?
		//-----------------------------------------

		if( $plug_data['lockd'] > 0 && !IN_DEV )
		{
			$this->registry->output->global_error = $this->lang->words['r_pluglocked'];
			$this->_showPluginIndex();
			return;
		}
		
		//-----------------------------------------
		// Let's start loading stuff...!
		//-----------------------------------------

		if( $plug_data['my_class'] == '' )
		{
			$plug_data['my_class'] = 'default';
		}
		
		if( $_POST['finish'] == 1 )
		{
			//-----------------------------------------
			// The form got sent, so lets go!
			//-----------------------------------------
			
			if( ! $_POST['plugi_title'] || ! $_POST['plugi_desc'] || ! $_POST['plugi_file'] )
			{
				$this->registry->output->global_error = $this->lang->words['r_missingfield'];
			}
			elseif( preg_match( "/[^a-z0-9_\-]/i", $_POST['plugi_file'] ) )
			{
				$this->registry->output->global_error = $this->lang->words['r_incchar'];
			}
			
			$build_plugin = array(
								'class_title'	=> $this->request['plugi_title'],
								'class_desc'	=> IPSText::stripslashes( $_POST['plugi_desc'] ),
								'author'		=> $this->request['plugi_author'],
								'author_url'	=> $this->request['plugi_author_url'],
								'my_class'		=> $this->request['plugi_file'],
								'pversion'		=> 'v'  . strval($this->request['plugi_version']),
								'lockd'			=> intval($this->request['plugi_lockd']) 
								);
			
			//-----------------------------------------
			// If file was changed blank out extra...
			//-----------------------------------------
			
			if( $plug_data['my_class'] != $build_plugin['my_class'] )
			{
				$build_plugin['extra_data'] = '';
				$do_edit = true;
			}

			if( ! $this->registry->output->global_error )
			{						
				$this->DB->update( 'rc_classes', $build_plugin, "com_id={$plug_id}" );
				
				if( $do_edit == true )
				{
					//-----------------------------------------
					// Plugin was changed, need settings now
					//-----------------------------------------
					
					$this->registry->output->global_message	= $this->lang->words['r_plugupdated'];
					$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . "&amp;do=edit_plugin&amp;com_id=" . $plug_id );
				}
				else
				{
					//-----------------------------------------
					// File was not changed, no need to edit..
					//-----------------------------------------
					
					$this->registry->output->global_message	= $this->lang->words['r_plugupdated'];
					$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . "&amp;do=plugin" );
				}
			}
			else
			{
				$plug_data = $build_plugin;
			}
		}
		
		//-----------------------------------------
		// Basic info for when I hit "Save"...
		//-----------------------------------------

		$this->registry->output->html .= $this->html->pluginForm( $plug_data );
	}

	/**
	 * This is when we want to remove a plugin, possibly cuz it's crap
	 *
	 * @return	@e void
	 */
	public function _removePlugin()
	{
		$com_id = intval( $this->request['com_id'] );
		
		//-----------------------------------------
		// Make sure we don't delete nothing...
		//-----------------------------------------
		
		if( $com_id < 1 )
		{
			$this->registry->output->global_error = $this->lang->words['r_deleteair'];
			$this->_showPluginIndex();
			return;
		}
		
		$row = $this->DB->buildAndFetch( array( 'select' => 'com_id, lockd', 'from' => 'rc_classes', 'where' => "com_id={$com_id}" ) );

		//-----------------------------------------
		// Make sure plugin exists first!
		// Again... NOTHING!
		//-----------------------------------------
		
		if( !$row['com_id'] )
		{
			$this->registry->output->global_error = $this->lang->words['r_plugin404'];
			$this->_showPluginIndex();
			return;
		}

		//-----------------------------------------
		// Make sure it isn't locked... Snicker..
		//-----------------------------------------
		
		if( $row['lockd'] == 1 && !IN_DEV )
		{
			$this->registry->output->global_error = $this->lang->words['r_dellocked'];
			$this->_showPluginIndex();
			return;
		}
		
		$this->DB->delete( 'rc_classes', "com_id={$com_id}" );
		
		$this->registry->output->global_message	= $this->lang->words['r_deleteplug'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . "&amp;do=plugin" );
	}

	/**
	 * This is where you view all the plugins in a list with options
	 *
	 * @return	@e void
	 */
	public function _showPluginIndex()
	{
		$this->registry->output->extra_nav[]		= array( "{$this->settings['base_url']}{$this->form_code}&do=plugin" , $this->lang->words['r_plugmanager'] );
		
		$this->DB->build( array( 'select' => '*', 'from' => 'rc_classes' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['_enabled_img'] = $row['onoff'] == 1 ? 'tick.png' : 'cross.png';

			$plugin_rows .= $this->html->report_plugin_row( $row );
		}
		
		$this->registry->output->html .= $this->html->report_plugin_overview( $plugin_rows );
	}
	
	/**
	 * This is when you delete a status/severity image
	 *
	 * @return	@e void
	 */
	public function _removeImage()
	{
		$img_id = intval( $this->request['id'] );
		
		if( $img_id < 1 )
		{
			$this->registry->output->global_error = $this->lang->words['r_noid'];
			$this->_showStatusIndex();
			return;
		}
		
		$this->DB->delete( 'rc_status_sev', "id={$img_id}" );
		
		$this->registry->output->global_message	= $this->lang->words['r_imgdel'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . "&amp;do=status" );
	}

	/**
	 * This is when I want to add a severity/status image
	 *
	 * @return	@e void
	 */
	public function _addImage()
	{
		$stat_id = intval( $this->request['id'] );
		
		//-----------------------------------------
		// Make sure that status isn't zero...
		//-----------------------------------------
		
		if( $stat_id < 1 )
		{
			$this->registry->output->global_error = $this->lang->words['r_noid'];
			$this->_showStatusIndex();
			return;
		}
		
		if( $_POST['finish'] == 1 )
		{
			//-----------------------------------------
			// Time to add that status img to DB...
			//-----------------------------------------
						
			$image_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_status_sev', 'where' => "status='{$stat_id}' AND points={$this->request['img_points']}" ) );
		
			if( $image_data['id'] )
			{
				$this->registry->output->setMessage( $this->lang->words['r_changepoints'], 1 );
			}
			else
			{
				$build_image = array(
									'status'	=> $stat_id,
									'img'		=> trim(IPSText::safeslashes($_POST['img_filename'])),
									'width'		=> $this->request['img_width'],
									'height'	=> $this->request['img_height'],
									'points'	=> $this->request['img_points'],
									'is_png'	=> ( strtolower(strrchr( $this->request['img_filename'], '.' )) == '.png' ? 1 : 0 )
				);
				
				$this->DB->insert( 'rc_status_sev', $build_image );
				
				$this->registry->output->global_message	= $this->lang->words['r_imgsaved'];
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . "&amp;do=status" );
			}
		}
		
		//-----------------------------------------
		// Load basic status data...
		//-----------------------------------------

		$status = $this->DB->buildAndFetch( array( 'select' => 'title', 'from' => 'rc_status', 'where' => "status='{$stat_id}'" ) );

		//-----------------------------------------
		// Does the status exist in the db?
		//-----------------------------------------
		
		if( !$status['title'] )
		{
			$this->registry->output->global_error = $this->lang->words['r_404status'];
			$this->_showStatusIndex();
			return;
		}

		//-----------------------------------------
		// Build the form so we can do something
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->imageForm( 'add_image', $status, $image_data );
	}

	/**
	 * This is for setting, in status index, which is New, Complete, and Active
	 *
	 * @return	@e void
	 */
	public function _setStatus()
	{
		$stat_id = intval( $this->request['id'] );
		
		if( $stat_id < 1 )
		{
			$this->registry->output->global_error = $this->lang->words['r_noid'];
			$this->_showStatusIndex();
			return;
		}
		
		//-----------------------------------------
		// Load the status that needs a change
		//-----------------------------------------
		
		$row = $this->DB->buildAndFetch( array( 'select' => 'is_new, is_complete, is_active', 'from' => 'rc_status', 'where' => "status='{$stat_id}'" ) );
		
		if( $this->request['status'] == 'new' || $this->request['status'] == 'complete' )
		{
			//-----------------------------------------
			// We want to make it new or complete
			//-----------------------------------------
			
			if( $this->request['status'] == 'new' )
			{
				if( $row['is_complete'] == 1 )
				{
					$this->registry->output->global_error = $this->lang->words['r_newcomplete'];
					$this->_showStatusIndex();
					return;
				}

				$build_status			= array( 'is_new' => 1 );
				$build_other_statuses	= array( 'is_new' => 0 );
			}
			else
			{
				if( $row['is_new'] == 1 )
				{
					$this->registry->output->global_error = $this->lang->words['r_completenew'];
					$this->_showStatusIndex();
					return;
				}

				$build_status			= array( 'is_complete' => 1 );
				$build_other_statuses	= array( 'is_complete' => 0 );
			}

			$this->DB->update( 'rc_status', $build_other_statuses, "status<>{$stat_id}" );
			$this->DB->update( 'rc_status', $build_status, "status={$stat_id}" );
		}
		elseif( $this->request['status'] == 'active' )
		{
			//-----------------------------------------
			// We can have as many active as we want
			//-----------------------------------------
			
			$this->DB->update( 'rc_status', array( 'is_active' => intval( !$row['is_active'] ) ), "status={$stat_id}" );
		}
		else
		{
			//-----------------------------------------
			// What the heck can this be?
			//-----------------------------------------
			
			$this->registry->output->global_message = $this->lang->words['r_invpar'];	
		}

		$this->_showStatusIndex();
	}

	/**
	 * This is the magic behind removing a status and the images behind it
	 *
	 * @return	@e void
	 */
	public function _removeStatus()
	{
		$stat_id = intval( $this->request['id'] );
		
		if( $stat_id < 1 )
		{
			$this->registry->output->global_error = $this->lang->words['r_deleteair'];
			$this->_showStatusIndex();
			return;
		}
		
		$row = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_status', 'where' => "status='{$stat_id}'" ) );

		if( !$row['status'] )
		{
			$this->registry->output->global_error = $this->lang->words['r_noid'];
			$this->_showStatusIndex();
			return;
		}

		//-----------------------------------------
		// Make sure we arn't removing something
		// that we are going to need.
		//-----------------------------------------
		
		if( $row['is_new'] == 1 )
		{
			$this->registry->output->global_error = $this->lang->words['r_onenew'];
			$this->_showStatusIndex();
			return;
		}
		elseif( $row['is_complete'] == 1 )
		{
			$this->registry->output->global_error = $this->lang->words['r_onecomplete'];
			$this->_showStatusIndex();
			return;
		}
		
		//-----------------------------------------
		// Remove the status and its images
		//-----------------------------------------
		
		$this->DB->delete( 'rc_status', "status='{$stat_id}'" );
		$this->DB->delete( 'rc_status_sev', "status='{$stat_id}'" );
		
		$this->registry->output->global_message	= $this->lang->words['r_statdel'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . "&amp;do=status" );
	}

	/**
	 * Here is where you create a status (before the added images)
	 *
	 * @return	@e void
	 */
	public function _createStatus()
	{
		if( $_POST['finish'] == 1 )
		{
			//-----------------------------------------
			// The form has been sent, lets go!
			//-----------------------------------------
			
			if( preg_match("/[^0-9]/", $_POST['stat_ppr'] ) )
			{
				$this->registry->output->global_error = $this->lang->words['r_ppr'];
			}
			elseif( preg_match("/[^0-9\.]/", $_POST['stat_pph'] ) )
			{
				$this->registry->output->global_error = $this->lang->words['r_mtp'];
			}
			else if( !$this->request['stat_title'] )
			{
				$this->registry->output->global_error = $this->lang->words['r_nostattitle'];
			}
			else
			{
				$stat_check = $this->DB->buildAndFetch( array( 'select' => 'MAX(rorder) as rorder', 'from' => 'rc_status' ) );

				$build_status = array(
									'title'				=> $this->request['stat_title'],
									'points_per_report'	=> $this->request['stat_ppr'],
									'minutes_to_apoint'	=> $this->request['stat_pph'],
									'rorder'			=> $stat_check['rorder'] + 1,
									);
				
				$this->DB->insert( 'rc_status', $build_status );

				$this->registry->output->global_message	= $this->lang->words['r_statcreated'];
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . "&amp;do=status" );
			}
		}
			 
		$this->registry->output->html .= $this->html->statusForm();
	}

	/**
	 * This is when I want to edit a status
	 *
	 * @return	@e void
	 */
	public function _editStatus()
	{
		$stat_id = intval( $this->request['id'] );
		
		//-----------------------------------------
		// Make sure the status ID is not zero
		//-----------------------------------------
		
		if( $stat_id < 1 )
		{
			$this->registry->output->global_error = $this->lang->words['r_noid'];
			$this->_showStatusIndex();
			return;
		}
		
		if( $_POST['finish'] == 1 )
		{
			//-----------------------------------------
			// We just sen't the form, check stuff
			//-----------------------------------------
			
			if( preg_match("/[^0-9]/", $_POST['stat_ppr'] ) )
			{
				$this->registry->output->global_error = $this->lang->words['r_ppr'];
			}
			elseif( preg_match("/[^0-9\.]/", $_POST['stat_pph'] ) )
			{
				$this->registry->output->global_error = $this->lang->words['r_mtp'];
			}
			else
			{
				$build_status = array(
									'title'				=> $this->request['stat_title'],
									'points_per_report'	=> $this->request['stat_ppr'],
									'minutes_to_apoint'	=> $this->request['stat_pph'],
				);
					
				$this->DB->update( 'rc_status', $build_status, "status='{$stat_id}'" );

				$this->registry->output->global_message	= $this->lang->words['r_statsaved'];
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . "&amp;do=status" );
			}
		}
		
		$stat_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_status', 'where' => "status='{$stat_id}'" ) );

		//-----------------------------------------
		// Make sure the status actually exists
		//-----------------------------------------
		
		if( !$stat_data['status'] )
		{
			$this->registry->output->global_error = $this->lang->words['r_noid'];
			$this->_showStatusIndex();
			return;
		}

		$this->registry->output->html .= $this->html->statusForm( 'edit_status', $stat_data );
	}

	/**
	 * This is where you edit an image under a status
	 *
	 * @return	@e void
	 */
	public function _editImage()
	{
		$img_id = intval( $this->request['id'] );
		
		//-----------------------------------------
		// Make sure the ID is not zero...
		//-----------------------------------------
		
		if( $img_id < 1 )
		{
			$this->registry->output->global_error = $this->lang->words['r_noid'];
			$this->_showStatusIndex();
			return;
		}
		
		if( $_POST['finish'] == 1 )
		{
			//-----------------------------------------
			// Now it's time to send the form...
			//-----------------------------------------
			
			$this->DB->build( array( 'select' => '*', 'from' => 'rc_status_sev', 'where' => "status='{$this->request['img_status']}' AND points={$this->request['img_points']} AND id!={$img_id}", 'limit' => 1 ) );
			$this->DB->execute();
		
			if( $this->DB->getTotalRows() != 0 )
			{
				$this->registry->output->setMessage( $this->lang->words['r_changepoints'], 1 );
			}
			else
			{
				$build_image = array(
									'img'		=> trim(IPSText::safeslashes($_POST['img_filename'])),
									'width'		=> $this->request['img_width'],
									'height'	=> $this->request['img_height'],
									'points'	=> $this->request['img_points'],
									'is_png'	=> ( strtolower(strrchr( $this->request['img_filename'], '.' )) == '.png' ? 1 : 0 )
				);
				
				$this->DB->update( 'rc_status_sev', $build_image, "id='{$img_id}'" );	
				
				$this->registry->output->global_message	= $this->lang->words['r_imgsaved'];
				$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . "&amp;do=status" );
			}
		}
		
		$image_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_status_sev', 'where' => "id='{$img_id}'" ) );
		
		//-----------------------------------------
		// Make sure the image actually exists...
		//-----------------------------------------
		
		if( !$image_data['id'] )
		{
			$this->registry->output->global_error = $this->lang->words['r_noid'];
			$this->_showStatusIndex();
			return;
		}
		
		//-----------------------------------------
		// Load basic data so we can edit it
		//-----------------------------------------

		$status = $this->DB->buildAndFetch( array( 'select' => 'title', 'from' => 'rc_status', 'where' => "status='{$image_data['status']}'" ) );
		
		$this->registry->output->html .= $this->html->imageForm( 'edit_image', $status, $image_data );
	}

	/**
	 * When we want to move the status up or down (not sideways...)
	 *
	 * @return	@e void
	 */
	public function _moveStatus()
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
 		
 		if( is_array($this->request['status']) AND count($this->request['status']) )
 		{
 			foreach( $this->request['status'] as $this_id )
 			{
 				$this->DB->update( 'rc_status', array( 'rorder' => $position ), 'status=' . $this_id );
 				
 				$position++;
 			}
 		}

 		$ajax->returnString( 'OK' );
 		exit();
	}

	/**
	 * Shows the status/severity index with all the special stuff
	 *
	 * @return	@e void
	 */
	public function _showStatusIndex()
	{
		/* Init vars */
		$statusRows  = array();
		$imagesCache = array();

		//-----------------------------------------
		// Get the default board skin...
		//-----------------------------------------
		
		$skin = $this->DB->buildAndFetch( array( 'select' => 'set_image_dir', 'from' => 'skin_collections', 'where' => "set_is_default=1" ) );
		
		//-----------------------------------------
		// Get the status images...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'rc_status_sev', 'order' => 'status ASC, points ASC' ) );
		$this->DB->execute();
		
		while( $srow = $this->DB->fetch() )
		{
			$imagesCache[ $srow['status'] ] .= str_replace( '<#IMG_DIR#>', $skin['set_image_dir'], $this->html->report_status_image( $srow ) );
		}
		
		//-----------------------------------------
		// Load and process the stateses...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'rc_status', 'order' => 'rorder ASC' ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			//-----------------------------------------
			// Show the New/Complete/Active boxes...
			//-----------------------------------------
			
			$row['_is_new']			= $row['is_new'] == 1 ? 'on' : 'off';
			$row['_is_complete']	= $row['is_complete'] == 1 ? 'on' : 'off';
			$row['_is_active']		= $row['is_active'] == 1 ?  'on' : 'off';
			
			//-----------------------------------------
			// Finish row with image cache...
			//-----------------------------------------
			
			$row['status_images'] = $imagesCache[ $row['status'] ];
			
			$statusRows[] = $row;
		}
		
		/* Output */
		$this->registry->output->extra_nav[] = array( "{$this->settings['base_url']}{$this->form_code}&amp;do=status" , $this->lang->words['r_statsever'] );
		$this->registry->output->html .= $this->html->report_status_overview( $statusRows );
	}
}