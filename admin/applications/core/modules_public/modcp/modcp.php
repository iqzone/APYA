<?php
/**
 * @file		modcp.php 	Moderator control panel
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		2/14/2011
 * $LastChangedDate: 2012-05-21 16:37:50 -0400 (Mon, 21 May 2012) $
 * @version		v3.3.3
 * $Revision: 10777 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		public_core_modcp_modcp
 * @brief		Moderator control panel
 * 
 */
class public_core_modcp_modcp extends ipsCommand
{	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load basic things
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_modcp' ) );
		
		$this->registry->output->setTitle( $this->lang->words['modcp_page_title'] );

		//-----------------------------------------
		// Which road are we going to take?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'index':
				$this->_indexPage();
			break;
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->getClass('output')->addNavigation( $this->lang->words['modcp_navbar'], "app=core&amp;module=modcp" );
		$this->registry->getClass('output')->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Show the mod CP portal
	 *
	 * @return	@e void
	 */
	protected function _indexPage()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		ipsRegistry::getAppClass('forums');
		$this->request['tab']     = empty($this->request['tab'])	 ? 'index' : trim($this->request['tab']);
		$this->request['fromapp'] = empty($this->request['fromapp']) ? 'index' : trim($this->request['fromapp']);
		
		$_plugins	= array();
		$_tabs		= array();
		$_activeNav = array( 'primary' => 'index', 'secondary' => 'index' );
		$_output	= '';
		$moderator	= $this->registry->class_forums->getModerator();
		$tab		= $this->request['tab'];
		$app		= $this->request['fromapp'];
		
		/**
		 * Loop through all apps and get plugins
		 * 
		 * @note	When updating this code below remember to update also the core in public_core_reports_reports
		 */
		foreach( IPSLib::getEnabledApplications() as $appDir => $appData )
		{
			if( is_dir( IPSLib::getAppDir( $appDir ) . '/extensions/modcp' ) )
			{
				try
				{
					foreach( new DirectoryIterator( IPSLib::getAppDir( $appDir ) . '/extensions/modcp' ) as $file )
					{
						if( ! $file->isDot() && $file->isFile() )
						{
							if( preg_match( '/^plugin_(.+?)\.php$/', $file->getFileName(), $matches ) )
							{ 
								//-----------------------------------------
								// We load each plugin so it can determine
								// if it should show based on permissions
								//-----------------------------------------
								
								$classToLoad = IPSLib::loadLibrary( $file->getPathName(), 'plugin_' . $appDir . '_' . $matches[1], $appDir );

								if( class_exists($classToLoad) )
								{
									$_plugins[ $appDir ][ $matches[1] ] = new $classToLoad( $this->registry );

									if( $_plugins[ $appDir ][ $matches[1] ]->canView( $moderator ) )
									{ 
										//-----------------------------------------
										// Hacky solution - we want forum plugins to
										// come first as they're the most used
										//-----------------------------------------
										
										if( $appDir == 'forums' AND !empty($_tabs[ $_plugins[ $appDir ][ $matches[1] ]->getPrimaryTab() ]) )
										{
											array_unshift( $_tabs[ $_plugins[ $appDir ][ $matches[1] ]->getPrimaryTab() ], array( $_plugins[ $appDir ][ $matches[1] ]->getSecondaryTab(), $appDir, $matches[1] ) );
										}
										else
										{
											$_tabs[ $_plugins[ $appDir ][ $matches[1] ]->getPrimaryTab() ][] = array( $_plugins[ $appDir ][ $matches[1] ]->getSecondaryTab(), $appDir, $matches[1] );
										}
										
										/* Sort active tab */
										if ( $appDir == $app && $tab == $matches[1] )
										{
											$_activeNav = array( 'primary' => $_plugins[ $appDir ][ $matches[1] ]->getPrimaryTab(), 'secondary' => $_plugins[ $appDir ][ $matches[1] ]->getSecondaryTab() );
										}
									}
								}
							}
						}
					}
				} catch ( Exception $e ) {}
			}
		}
		
		// Move trash can to the bottom - if available
		if ( isset($_tabs['deleted_content']) )
		{
			$trashCan = $_tabs['deleted_content'];
			unset( $_tabs['deleted_content'] );
			$_tabs['deleted_content'] = $trashCan;
		}
		
		//-----------------------------------------
		// If we can't view any tabs, show an error
		//-----------------------------------------
		
		if( !count($_tabs) )
		{
			$this->registry->output->showError( $this->lang->words['modcp_no_access'], 10194.12, false, null, 403 );
		}
		
		//-----------------------------------------
		// Pass the necessary template variables into the plugin
		//-----------------------------------------

		$this->registry->output->getTemplate('modcp')->templateVars['tabs'] = $_tabs;
		$this->registry->output->getTemplate('modcp')->templateVars['activeNav'] = $_activeNav;

		//-----------------------------------------
		// Get appropriate content to show
		//-----------------------------------------

		if( $tab AND $app AND isset($_plugins[ $app ][ $tab ]) )
		{
			$_output = $_plugins[ $app ][ $tab ]->executePlugin( $moderator );
		}
		else
		{
			switch ( $this->request['do'] )
			{
				case 'editmember':
					$_output = $this->_editMember();
					break;
					
				case 'doeditmember':
					$this->_doEditMember();
					break;
					
				case 'setAsSpammer':
					$this->_setAsSpammer();
					break;
					
				default:
					$_output = $this->registry->output->getTemplate('modcp')->memberLookup();
					break;
			}
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->output .= $this->registry->output->getTemplate('modcp')->portalPage( $_output, $_tabs, $_activeNav );
	}
	
	/**
	 * Load all necessary properties
	 *
	 * @return	@e void
	 */
	public function loadData()
	{

		//-----------------------------------------
		// Get forum libraries
		//-----------------------------------------
		
		ipsRegistry::getAppClass( 'forums' );

		//-----------------------------------------
		// Make sure we're a moderator...
		//-----------------------------------------
		
		$pass = 0;

		if( $this->memberData['member_id'] )
		{
			if( $this->memberData['g_is_supmod'] == 1 )
			{
				$pass				        = 1;
			}
			else if( $this->memberData['is_mod'] )
			{
				$other_mgroups	= array();
				$_other_mgroups	= IPSText::cleanPermString( $this->memberData['mgroup_others'] );
				
				if( $_other_mgroups )
				{
					$other_mgroups	= explode( ",", $_other_mgroups );
				}
				
				$other_mgroups[] = $this->memberData['member_group_id'];

				$this->DB->build( array( 
										'select' => '*',
										'from'   => 'moderators',
										'where'  => "(member_id='" . $this->memberData['member_id'] . "' OR (is_group=1 AND group_id IN(" . implode( ",", $other_mgroups ) . ")))" 
								)	);
											  
				$this->DB->execute();
				
				while ( $this->moderator = $this->DB->fetch() )
				{
					if ( $this->moderator['allow_warn'] )
					{
						$pass				        = 1;
					}
				}
			}			
		}
			
		if ( !$pass )
		{
			$this->registry->output->showError( 'warn_no_access', 2025, null, null, 403 );
		}

		//-----------------------------------------
		// Ensure we have a valid member
		//-----------------------------------------
		
		$mid	= intval($this->request['mid']);
		
		if ( $mid < 1 )
		{
			$this->registry->output->showError( 'warn_no_user', 10249, null, null, 404 );
		}
		
		$this->warn_member	= IPSMember::load( $mid, 'all' );

		if ( ! $this->warn_member['member_id'] )
		{
			$this->registry->output->showError( 'warn_no_user', 10250, null, null, 404 );
		}
		
		//-----------------------------------------
		// Get editor
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor	= new $classToLoad();
	}
	
	/**
	 * Get the moderator library and return it
	 *
	 * @return	@e object
	 */
	protected function getModLibrary()
	{
		static $modLibrary	= null;
		
		if( !$modLibrary )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
			$modLibrary		= new $classToLoad( $this->registry );
			$modLibrary->init( array() );
		}
		
		return $modLibrary;
	}
	
	/**
	 * Form to edit a member
	 *
	 * @return	@e void
	 * @todo 	[Future] Determine what items should be editable and allow moderators to edit them
	 */
	protected function _editMember()
	{
		$this->loadData();
	
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		if ( ! $this->memberData['g_is_supmod'] )
		{
			return $this->registry->getClass('output')->getTemplate('modcp')->modcpMessage();
		}

		if ( ! $this->memberData['g_access_cp'] AND $this->warn_member['g_access_cp'] )
		{
			return $this->registry->getClass('output')->getTemplate('modcp')->modcpMessage( $this->lang->words['mod_cannot_edit_admin'] );
		}
		
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$editable	= array();
		
		//-----------------------------------------
		// Show about me and signature editors
		//-----------------------------------------
		
		$this->editor->setAllowBbcode( true );
		$this->editor->setAllowSmilies( false );
		$this->editor->setAllowHtml( $this->caches['group_cache'][ $this->warn_member['member_group_id'] ]['g_dohtml'] );
		$this->editor->setContent( $this->warn_member['signature'], 'signatures' );
		$editable['signature']	= $this->editor->show( 'Post', array( 'noSmilies' => true, 'height' => 100 ) );

		$this->editor->setAllowBbcode( true );
		$this->editor->setAllowSmilies( true );
		$this->editor->setAllowHtml( $this->caches['group_cache'][ $this->warn_member['member_group_id'] ]['g_dohtml'] );
		$this->editor->setContent( $this->warn_member['pp_about_me'], 'aboutme' );
		$editable['aboutme']	= $this->editor->show( 'aboutme', array( 'height' => 100 ) );

		//-----------------------------------------
		// Other fields
		//-----------------------------------------
		
		$editable['member_id']				= $this->warn_member['member_id'];
		$editable['members_display_name']	= $this->warn_member['members_display_name'];
		$editable['title']					= $this->warn_member['title'];

		//-----------------------------------------
		// Profile fields
		//-----------------------------------------

		$classToLoad			= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php', 'customProfileFields' );
		$fields					= new $classToLoad();
		$fields->member_data	= $this->warn_member;
		$fields->initData( 'edit' );
		$fields->parseToEdit();
		
		$editable['_parsedMember']	= IPSMember::buildDisplayData( $this->warn_member );

		//-----------------------------------------
		// Return the HTML
		//-----------------------------------------
		
		return $this->registry->getClass('output')->getTemplate('modcp')->editUserForm( $editable, $fields );
	}
	
	/**
	 * Save the member updates
	 *
	 * @return	@e void
	 * @todo 	[Future] Determine what items should be editable and allow moderators to edit them
	 */
	protected function _doEditMember()
	{
		$this->loadData();
		
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		if ( ! $this->memberData['g_is_supmod'] )
		{
			$this->registry->output->showError( 'mod_only_supermods', 10370, true, null, 403 );
		}

		if ( ! $this->memberData['g_access_cp'] AND $this->warn_member['g_access_cp'] )
		{
			$this->registry->output->showError( 'mod_admin_edit', 3032, true, null, 403 );
		}

		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', 3032.1, null, null, 403 );
		}
		
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$editable	= array();

		//-----------------------------------------
		// Signature and about me
		//-----------------------------------------
		
		$signature	= $this->editor->process( $_POST['Post'] );
		$aboutme	= $this->editor->process( $_POST['aboutme'] );

		//-----------------------------------------
		// Parse signature
		//-----------------------------------------
		
		IPSText::getTextClass('bbcode')->parse_smilies			= 0;
		IPSText::getTextClass('bbcode')->parse_html				= $this->caches['group_cache'][ $this->warn_member['member_group_id'] ]['g_dohtml'];
		IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
		IPSText::getTextClass('bbcode')->parsing_section		= 'signatures';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->warn_member['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->warn_member['mgroup_others'];

		$signature		= IPSText::getTextClass('bbcode')->preDbParse( $signature );
		$signatureCache	= IPSText::getTextClass('bbcode')->preDisplayParse( $signature );
		
		//-----------------------------------------
		// Parse about me
		//-----------------------------------------
		
		IPSText::getTextClass('bbcode')->parse_smilies			= 1;
		IPSText::getTextClass('bbcode')->parse_html				= $this->caches['group_cache'][ $this->warn_member['member_group_id'] ]['g_dohtml'];
		IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
		IPSText::getTextClass('bbcode')->parsing_section		= 'aboutme';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->warn_member['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->warn_member['mgroup_others'];

		$aboutme	= IPSText::getTextClass('bbcode')->preDbParse( $aboutme );	
		
		//-----------------------------------------
		// Add to array to save
		//-----------------------------------------
		
		$save['extendedProfile']	= array( 'signature' => $signature, 'pp_about_me' => $aboutme );
		$save['members']			= array( 'title' => $this->request['title'] );

		if ( $this->request['photo'] == 1 )
		{
			$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/photo.php', 'classes_member_photo' );
			$photos			= new $classToLoad( $this->registry );
			$photos->remove( $this->warn_member['member_id'] );
		}
		
		//-----------------------------------------
		// Profile fields
		//-----------------------------------------

		$classToLoad			= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php', 'customProfileFields' );
		$fields					= new $classToLoad();
		$fields->member_data	= $this->warn_member;
		$fields->initData( 'edit' );
		$fields->parseToSave( $_POST );
		
		if ( count( $fields->out_fields ) )
		{
			$save['customFields']	= $fields->out_fields;
		}
		
		//-----------------------------------------
		// Bitwise
		//-----------------------------------------

		$bw = IPSBWOptions::thaw( $this->warn_member['members_bitoptions'], 'members' );
		$bw['bw_no_status_update'] = ( $this->request['status_updates'] ) ? 0 : 1;
		$save['core']['members_bitoptions'] = IPSBWOptions::freeze( $bw, 'members' );

		//-----------------------------------------
		// Write it to the DB.
		//-----------------------------------------
		
		IPSMember::save( $this->warn_member['member_id'], $save );
		
		//-----------------------------------------
		// Update signature content cache
		//-----------------------------------------
		
		IPSContentCache::update( $this->warn_member['member_id'], 'sig', $signatureCache );

		//-----------------------------------------
		// Add a mod log entry and redirect
		//-----------------------------------------
		
		$this->getModLibrary()->addModerateLog( 0, 0, 0, 0, $this->lang->words['acp_edited_profile'] . " " . $this->warn_member['members_display_name'] );

		$this->_redirect( $this->lang->words['acp_edited_profile'] . " " . $this->warn_member['members_display_name'] );
	}
	
	/**
	 * Flag a user account as a spammer
	 *
	 * @return	@e void
	 */
	protected function _setAsSpammer()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$member_id	= intval( $this->request['member_id'] );
		$toSave		= array( 'core' => array( 'bw_is_spammer' => 1 ) );
		$topicId	= intval($this->request['t']);
		$topic		= array();
		
		if( $topicId )
		{
			$topic	= $this->DB->buildAndFetch( array( 'select' => 'tid, title_seo, forum_id', 'from' => 'topics', 'where' => 'tid=' . $topicId ) );
		}
		
		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member	= IPSMember::load( $member_id );
		
		if ( ! $member['member_id'] )
		{
			$this->registry->output->showError( 'moderate_no_permission', 10311900, true, null, 404 );
		}
		
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		if( !$this->memberData['g_is_supmod'] AND !$this->memberData['forumsModeratorData'][ $topic['forum_id'] ]['bw_flag_spammers'] )
		{
			$this->registry->output->showError( 'moderate_no_permission', 103119, true, null, 403 );
		}

		if ( IPSMember::isInGroup( $member, explode( ',', $this->settings['warn_protected'] ) ) )
		{
			$this->registry->output->showError( 'moderate_no_permission', 10311901, true, null, 403 );
		}
		
		//-----------------------------------------
		// Do it
		//-----------------------------------------

		IPSMember::flagMemberAsSpammer( $member, $this->memberData );
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		if( $topicId )
		{
			$this->registry->output->redirectScreen( $this->lang->words['flag_spam_done'], $this->settings['base_url'] . "showtopic=" . $topic['tid'] . "&amp;st=" . intval($this->request['st']), $topic['title_seo'], 'showtopic' );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['flag_spam_done'], $this->settings['base_url'] . "showuser=" . $member['member_id'], $member['members_seo_name'], 'showuser' );
		}
	}
	
	/**
	 * Redirect back to where we came from
	 *
	 * @param	string	$message	Redirect message
	 * @return	@e void
	 */
	protected function _redirect( $message )
	{
		if( $this->request['pf'] )
		{
			$this->registry->output->redirectScreen( $message, $this->settings['base_url'] . "showuser=" . $this->warn_member['member_id'], $this->warn_member['members_seo_name'], 'showuser' );
		}
		else if( $this->request['t'] )
		{
			$topic	= $this->DB->buildAndFetch( array( 'select' => 'tid, title_seo', 'from' => 'topics', 'where' => 'tid=' . intval($this->request['t']) ) );

			$this->registry->output->redirectScreen( $message, $this->settings['base_url'] . "showtopic=" . $topic['tid'] . '&amp;st=' . $this->request['_st'], $topic['title_seo'], 'showtopic' );
		}
		else
		{
			$this->registry->output->redirectScreen( $message, $this->settings['base_url'] . "app=core&amp;module=modcp&amp;do=editmember&amp;mid={$this->warn_member['member_id']}" );
		}
	}
}