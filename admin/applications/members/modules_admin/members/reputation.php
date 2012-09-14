<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Reputation System
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_members_members_reputation extends ipsCommand
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
		/* Load Skin & Lang */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_reputation' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_member' ) );
		
		/* URL Bits */
		$this->form_code	= $this->html->form_code	= 'module=members&amp;section=reputation';
		$this->form_code_js	= $this->html->form_code_js	= 'module=members&section=reputation';

		/* What to do */
		switch( $this->request['do'] )
		{
			case 'add_level_form':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reps_manage' );
				$this->levelForm( 'add' );
			break;
			
			case 'do_add_level':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reps_manage' );
				$this->doLevelForm( 'add' );
			break;
			
			case 'edit_level_form':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reps_manage' );
				$this->levelForm( 'edit' );
			break;
			
			case 'do_edit_level':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reps_manage' );
				$this->doLevelForm( 'edit' );
			break;
			
			case 'delete_level':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reps_delete' );
				$this->deleteLevel();
			break;
			
			default:
			case 'overview':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'reps_manage' );
				$this->reputationOverview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();		
	}
	
	/**
	 * Rebuilds the reputation level cache
	 *
	 * @return	@e void
	 */
	public function rebuildReputationLevelCache()
	{
		/* Cache */
		$cache = array();
		
		/* Query the levels */
		$this->DB->build( array( 'select' => '*', 'from' => 'reputation_levels', 'order' => 'level_points DESC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$cache[] = $r;
		}
		
		/* Update the cache */
		$this->cache->setCache( 'reputation_levels', $cache, array( 'array' => 1 ) );
	}
	
	/**
	 * Removes the selected reputation level
	 *
	 * @return	@e void
	 */
	public function deleteLevel()
	{
		/* ID */
		$id = intval( $this->request['id'] );
		
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['invalid_id'], 11244 );
		}
		
		/* Delete */
		$this->DB->delete( 'reputation_levels', "level_id={$id}" );
		$this->rebuildReputationLevelCache();
		
		/* Redirect */
		$this->registry->output->global_message	= $this->lang->words['rep_level_removed'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Handles the add/edit reputation level form
	 *
	 * @param	string	$mode	Either add or edit
	 * @return	@e void
	 */	
	public function doLevelForm( $mode='add' )
	{
		/* Error Checking */
		$errors = array();
		
		if( ! $this->request['level_title'] && ! $this->request['level_image'] )
		{
			$errors[] = $this->lang->words['rep_no_title_img'];
		}
		
		if( count( $errors ) )
		{
			$this->levelForm( $mode, $errors );
			return;
		}
		
		/* Build the data array */
		$data = array(
						'level_title'  => $this->request['level_title'],
						'level_image'  => $this->request['level_image'],
						'level_points' => intval( $this->request['level_points'] )
					);
					
		/* Add the level */
		if( $mode == 'add' )
		{
			/* Insert */
			$this->DB->insert( 'reputation_levels', $data );
			$this->rebuildReputationLevelCache();
			
			/* Redirect */
			$this->registry->output->global_message	= $this->lang->words['rep_level_added'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
		}
		else
		{
			/* ID Check */
			$id = intval( $this->request['id'] );
			
			if( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['invalid_id'], 11245 );
			}
			
			/* Update */
			$this->DB->update( 'reputation_levels', $data, "level_id={$id}" );
			$this->rebuildReputationLevelCache();
			
			/* Redirect */
			$this->registry->output->global_message	= $this->lang->words['rep_level_edited'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
		}		
	}
	
	/**
	 * Form for adding/exiting reputation levels
	 *
	 * @param	string	$mode	Either add or edit
	 * @param	array	$errors	Array of error messages to display
	 * @return	@e void
	 */
	public function levelForm( $mode='add', $errors=array() )
	{
		/* Add Level Form */
		if( $mode == 'add' )
		{
			/* ID */
			$id = 0;
			
			/* Data */
			$data = array();
						
			/* Text Bits */
			$title = $this->lang->words['rep_form_add_title'];
			$do    = 'do_add_level';
		}
		/* Edit Level Form */
		else
		{
			/* ID */
			$id = intval( $this->request['id'] );
			
			if( ! $id )
			{
				$this->registry->output->showError( $this->lang->words['invalid_id'], 11246 );
			}
			
			/* Data */
			$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'reputation_levels', 'where' => 'level_id=' . $id ) );
			
			/* Text Bits */
			$title = $this->lang->words['rep_form_edit_title'];
			$do    = 'do_edit_level';
		}

		/* Default Values */
	 	$data['level_title']  = isset( $this->request['level_title'] )  ? $this->request['level_title']  : $data['level_title'];
	 	$data['level_image']  = isset( $this->request['level_image'] )  ? $this->request['level_image']  : $data['level_image'];
	 	$data['level_points'] = isset( $this->request['level_points'] ) ? $this->request['level_points'] : $data['level_points'];

		/* Form Elements */
		$form = array();
		
		$form['level_title']  = $this->registry->output->formInput( 'level_title' , $data['level_title'] );
		$form['level_image']  = $this->registry->output->formInput( 'level_image' , $data['level_image'] );
		$form['level_points'] = $this->registry->output->formInput( 'level_points', $data['level_points'] );
		
		/* Output */
		$this->registry->output->html .= $this->html->reputationForm( $id, $do, $title, $form, $errors );
	}
	
	/**
	 * Reputation overview
	 *
	 * @return	@e void
	 */
	public function reputationOverview()
	{
		/* INIT */
		$levels = array();
		
		/* Query Levels */
		$this->DB->build( array( 'select' => '*', 'from' => 'reputation_levels', 'order' => 'level_points ASC' ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$r['level_image'] = $r['level_image'] ? "<img src='{$this->settings['public_dir']}style_extra/reputation_icons/{$r['level_image']}'>" : '';
			
			$levels[] = $r;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->reputationOverview( $levels );
	}
}