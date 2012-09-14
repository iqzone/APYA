<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Forum moderator management
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		Tuesday 17th August 2004
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forums_forums_moderator extends ipsCommand
{
	/**
	 * Forum functions library
	 *
	 * @var		object
	 */
	public $forumfunc;
	
	/**
	 * Skin methods library
	 *
	 * @var		object
	 */
	public $html;
	
	/**
	 * Main execution point
	 *
	 * @param 	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Admin forum functions */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/admin_forum_functions.php", 'admin_forum_functions', 'forums' );
		$this->forumfunc = new $classToLoad( $registry );
		
		/* Skin and Lang */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_forums' );
		$this->html->form_code    = 'module=forums&amp;section=moderator&amp;';
		$this->html->form_code_js = 'module=forums&amp;section=moderator&amp;';
		
		$this->lang->loadLanguageFile( array( 'admin_forums' ) );
				
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mods_add' );
				$this->moderatorAddPreform();
			break;
			
			case 'add_final':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mods_add' );
				$this->modForm('add');
			break;
				
			case 'doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mods_add' );
				$this->addMod();
			break;
			
			case 'add_forum_to_set':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mods_edit' );
				$this->addForumToSet();
			break;
				
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mods_edit' );
				$this->modForm('edit');
			break;
				
			case 'doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mods_edit' );
				$this->doEdit();
			break;
				
			case 'remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'mods_delete' );
				$this->delete();
			break;
				
			default:
				$this->registry->output->silentRedirect( $this->settings['_base_url'] . 'app=core&module=help&id=mod_mmod' );
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------

		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Delete a moderator
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function delete()
	{
		if ($this->request['mid'] == "")
		{
			$this->registry->output->showError( $this->lang->words['mod_valid'], 11319 );
		}
		
		$mod = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'moderators', 'where' => "mid=".intval($this->request['mid']) ) );
		
		if ( $mod['is_group'] )
		{
			$name = $this->lang->words['mod_group'].$mod['group_name'];
		}
		else
		{
			$getname = $this->DB->buildAndFetch( array( 'select' => 'members_display_name', 'from' => 'members', 'where' => 'member_id=' . $mod['member_id'] ) );
			
			$name = $getname['members_display_name'];
		}
		
		if( $this->request['fid'] == 'all' )
		{
			$this->DB->delete( 'moderators', "mid=".intval($this->request['mid']) );
			
			//-----------------------------------------
			// Delete *all* instances of this moderator
			//-----------------------------------------
			
			if( $mod['is_group'] )
			{
				$this->DB->delete( 'moderators', "is_group=1 AND group_id=". $mod['group_id'] );
			}
			else
			{
				$this->DB->delete( 'moderators', "is_group=0 AND member_id=". $mod['member_id'] );
			}
		}
		else
		{
			$forumIds	= explode( ',', IPSText::cleanPermString( $mod['forum_id'] ) );
			$newForums	= array();
			
			foreach( $forumIds as $aForumId )
			{
				if( $aForumId != $this->request['fid'] )
				{
					$newForums[] = $aForumId;
				}
			}

			if( !count($newForums) )
			{
				$this->DB->delete( 'moderators', "mid=" . $mod['mid'] );
			}
			else
			{
				$this->DB->update( 'moderators', array( 'forum_id' => ',' . implode( ',', $newForums ) . ',' ), 'mid=' . $mod['mid'] );
			}
		}

		
		$this->rebuildModeratorCache();
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf($this->lang->words['mod_removedlog'], $name) );
		
		$this->registry->output->global_message = $this->lang->words['mod_removed'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'].'&app=forums&module=forums&section=forums' );
	}	
	
	
	/**
	 * Edit a moderator
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function doEdit()
	{
		/* Set up our return forum */
		$return_id = intval( $this->request['return_id'] );
		$url_extra = $return_id ? "&f=".$return_id : "";
		
		if( $this->request['mid'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['mod_valid'], 11321 );
		}
		
		$forums	= array();
		
		foreach( $this->request['forums'] as $forum_id )
		{
			$forums[ $forum_id ] = $forum_id;
		}
		
		$mod = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'moderators', 'where' => "mid=" . intval( $this->request['mid'] ) ) );

		if( $mod['is_group'] )
		{
			foreach( $forums as $forum_id )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'moderators', 'where' => "forum_id LIKE '%,{$forum_id},%' and group_id=" . $mod['group_id'] . ' AND mid<>' . $mod['mid'] ) );
				$this->DB->execute();
				
				while( $f = $this->DB->fetch() )
				{
					$theseForums = explode( ',', IPSText::cleanPermString( $f['forum_id'] ) );
					
					foreach( $theseForums as $thisForumId )
					{
						unset($forums[ $thisForumId ]);
					}
				}
			}
		}
		else
		{
			foreach( $forums as $forum_id )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'moderators', 'where' => "forum_id LIKE '%,{$forum_id},%' and member_id=" . $mod['member_id'] . ' AND mid<>' . $mod['mid'] ) );
				$this->DB->execute();
				
				while( $f = $this->DB->fetch() )
				{
					$theseForums = explode( ',', IPSText::cleanPermString( $f['forum_id'] ) );
					
					foreach( $theseForums as $thisForumId )
					{
						unset($forums[ $thisForumId ]);
					}
				}
			}
		}

		//-----------------------------------------
		// Build Mr Hash
		//-----------------------------------------
		
		$this->DB->update( 'moderators', array( 
													'forum_id'               => ',' . implode( ',', $forums ) . ',',
													'edit_post'              => intval( $this->request['edit_post'] ),
													'edit_topic'             => intval( $this->request['edit_topic'] ),
													'delete_post'            => intval( $this->request['delete_post'] ),
													'delete_topic'           => intval( $this->request['delete_topic'] ),
													'view_ip'                => intval( $this->request['view_ip'] ),
													'open_topic'             => intval( $this->request['open_topic'] ),
													'close_topic'            => intval( $this->request['close_topic'] ),
													'mass_move'              => intval( $this->request['mass_move'] ),
													'mass_prune'             => intval( $this->request['mass_prune'] ),
													'move_topic'             => intval( $this->request['move_topic'] ),
													'pin_topic'              => intval( $this->request['pin_topic'] ),
													'unpin_topic'            => intval( $this->request['unpin_topic'] ),
													'post_q'                 => intval( $this->request['post_q'] ),
													'topic_q'                => intval( $this->request['topic_q'] ),
													'allow_warn'             => intval( $this->request['allow_warn'] ),
													'split_merge'            => intval( $this->request['split_merge'] ),
													'can_mm'                 => intval( $this->request['can_mm'] ),
													'mod_can_set_open_time'  => intval( $this->request['mod_can_set_open_time'] ),
													'mod_can_set_close_time' => intval( $this->request['mod_can_set_close_time'] ),
													'mod_bitoptions'         => IPSBWOptions::freeze( $this->request, 'moderators', 'forums' )
												), 'mid='.intval($this->request['mid']) );
		
		$this->rebuildModeratorCache();
		
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['mod_editedlog'], $mod['member_name'] ) );
		
		$this->registry->output->global_message = $this->lang->words['mod_edited'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'].'module=forums&section=forums'.$url_extra );
	}	
	
	/**
	 * Add a moderator
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function addMod()
	{
		/* Set up our return forum */
		$return_id = intval( $this->request['return_id'] );
		$url_extra = $return_id ? "&f=".$return_id : "";
		
		if( !is_array($this->request['forums']) OR !count($this->request['forums']) )
		{
			$this->registry->output->showError( $this->lang->words['mod_noforums'], 11320 );
		}
		
		//-----------------------------------------
		// Build Mr Hash
		//-----------------------------------------
		
		$mr_hash = array( 
							'edit_post'              => intval( $this->request['edit_post'] ),
							'edit_topic'             => intval( $this->request['edit_topic'] ),
							'delete_post'            => intval( $this->request['delete_post'] ),
							'delete_topic'           => intval( $this->request['delete_topic'] ),
							'view_ip'                => intval( $this->request['view_ip'] ),
							'open_topic'             => intval( $this->request['open_topic'] ),
							'close_topic'            => intval( $this->request['close_topic'] ),
							'mass_move'              => intval( $this->request['mass_move'] ),
							'mass_prune'             => intval( $this->request['mass_prune'] ),
							'move_topic'             => intval( $this->request['move_topic'] ),
							'pin_topic'              => intval( $this->request['pin_topic'] ),
							'unpin_topic'            => intval( $this->request['unpin_topic'] ),
							'post_q'                 => intval( $this->request['post_q'] ),
							'topic_q'                => intval( $this->request['topic_q'] ),
							'allow_warn'             => intval( $this->request['allow_warn'] ),
							'split_merge'            => intval( $this->request['split_merge'] ),
							'can_mm'	             => intval( $this->request['can_mm'] ),
							'mod_can_set_open_time'  => intval( $this->request['mod_can_set_open_time'] ),
							'mod_can_set_close_time' => intval( $this->request['mod_can_set_close_time'] ),
							'forum_id'				 => ',' . implode( ',', $this->request['forums'] ) . ',',
							'mod_bitoptions'         => IPSBWOptions::freeze( $this->request, 'moderators', 'forums' )
						);

		$forums	= array();
		
		foreach( $this->request['forums'] as $forum_id )
		{
			$forums[ $forum_id ] = $forum_id;
		}

		//-----------------------------------------
						
		if( $this->request['mod_type'] == 'group' )
		{
			if( $this->request['gid'] == "" )
			{
				$this->registry->output->showError( $this->lang->words['mod_gid'], 11322 );
			}
			
			$this->DB->build( array( 'select' => 'g_id, g_title', 'from' => 'groups', 'where' => "g_id=" . intval( $this->request['gid'] ) ) );
			$this->DB->execute();
		
			if ( ! $group = $this->DB->fetch() )
			{
				$this->registry->output->showError( $this->lang->words['mod_gid'], 11323 );
			}
			
			//-----------------------------------------
			// Already using this group on this forum?
			//-----------------------------------------
			
			foreach( $forums as $forum_id )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'moderators', 'where' => "forum_id LIKE '%,{$forum_id},%' and group_id=" . intval( $this->request['gid'] ) ) );
				$this->DB->execute();
				
				while( $f = $this->DB->fetch() )
				{
					$theseForums = explode( ',', IPSText::cleanPermString( $f['forum_id'] ) );
					
					foreach( $theseForums as $thisForumId )
					{
						unset($forums[ $thisForumId ]);
					}
				}
			}
			
			$mr_hash['member_name'] = '-1';
			$mr_hash['member_id']   = '-1';
			$mr_hash['group_id']    = $group['g_id'];
			$mr_hash['group_name']  = $group['g_title'];
			$mr_hash['is_group']    = 1;
			
			$ad_log = sprintf( $this->lang->words['mod_addedgroup'], $group['g_title'] );
			
		}
		else
		{
			if( $this->request['mem'] == "" )
			{
				$this->registry->output->showError( $this->lang->words['mod_nomember'], 11324 );
			}
			
			$this->DB->build( array( 'select' => 'member_id, members_display_name', 'from' => 'members', 'where' => "member_id=" . intval( $this->request['mem'] ) ) );
			$this->DB->execute();
		
			if ( ! $mem = $this->DB->fetch() )
			{
				$this->registry->output->showError( $this->lang->words['mod_memid'], 11325 );
			}
			
			//-----------------------------------------
			// Already using this member on this forum?
			//-----------------------------------------
			
			foreach( $forums as $forum_id )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'moderators', 'where' => "forum_id LIKE '%,{$forum_id},%' and member_id=" . intval( $this->request['mem'] ) ) );
				$this->DB->execute();
				
				while( $f = $this->DB->fetch() )
				{
					$theseForums = explode( ',', IPSText::cleanPermString( $f['forum_id'] ) );
					
					foreach( $theseForums as $thisForumId )
					{
						unset($forums[ $thisForumId ]);
					}
				}
			}

			$mr_hash['member_name'] = $mem['members_display_name'];
			$mr_hash['member_id']   = $mem['member_id'];
			$mr_hash['is_group']    = 0;
			
			$ad_log = sprintf( $this->lang->words['mod_addedmem'], $mem['members_display_name'] );
		}
		
		//-----------------------------------------
		// Check for legal forums
		//-----------------------------------------
		
		if( count( $forums ) == 0 )
		{
			$this->registry->output->showError( $this->lang->words['mod_nonewfor'], 11326 );
		}
		
		//-----------------------------------------
		// Loopy loopy
		//-----------------------------------------
		
		$mr_has['forum_id'] = ',' . implode( ',', $forums ) . ',';

		$this->DB->setDataType( 'member_name', 'string' );
		
		$this->DB->insert( 'moderators', $mr_hash );
		
		$this->registry->adminFunctions->saveAdminLog($ad_log);
		
		$this->rebuildModeratorCache();
		
		$this->registry->output->global_message = $this->lang->words['mod_added'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'].'&module=forums&section=forums'.$url_extra );
	}	
	
	/**
	 * Rebuild moderator cache
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function rebuildModeratorCache()
	{
		$new_cache = array();
		
		//-----------------------------------------
		// Get dem moderators
		//-----------------------------------------
		
		$this->DB->build( array( 'select'   => 'moderator.*',
												 'from'     => array( 'moderators' => 'moderator' ),
												 'add_join' => array( 0 => array( 'select' => 'm.members_display_name, m.members_seo_name',
																				  'from'   => array( 'members' => 'm' ),
																				  'where'  => "m.member_id=moderator.member_id",
																				  'type'   => 'left' ) ) ) );
		
		$this->DB->execute();
		
		while ( $i = $this->DB->fetch() )
		{
			$forums	= explode( ',', IPSText::cleanPermString( $i['forum_id'] ) );
			
			/* Unpack bitwise fields */
			$_tmp = IPSBWOptions::thaw( $i['mod_bitoptions'], 'moderators', 'forums' );

			if ( count( $_tmp ) )
			{
				foreach( $_tmp as $k => $v )
				{ 
					$i[ $k ] = $v;
				}
			}
			
			foreach( $forums as $forum_id )
			{
				$i['forum_id']	= $forum_id;
				$new_cache[]	= $i;
			}
		}
		
		$this->cache->setCache( 'moderators', $new_cache, array( 'array' => 1, 'donow' => 0 ) );
	}
	
	/**
	 * Show the add/edit form
	 *
	 * @param	string		[add|edit]
	 * @return	@e void		[Outputs to screen]
	 */
	public function modForm( $type='add' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$group = array();

		if ( $type == 'add' )
		{
			/* Form Data */
			$mod_type	= $this->request['group'] ? 'group' : 'name';
			$mod		= array();
			$names		= array();
			$forum_id	= explode( ',', $this->request['fid'] );

			//-----------------------------------------
			// Start proper
			//-----------------------------------------
			
			$button		= $this->lang->words['mod_addthis'];
			
			$form_code	= 'doadd';
			
			if ( $this->request['group'] )
			{
				$this->DB->build( array( 'select' => 'g_id, g_title', 'from' => 'groups', 'where' => "g_id=" . intval( $this->request['group'] ) ) );
				$this->DB->execute();
				
				if (! $group = $this->DB->fetch() )
				{
					$this->registry->output->showError( $this->lang->words['mod_nogroup'], 11327 );
				}
			}
			else
			{
				if ( ! $this->request['member_id'] )
				{
					$this->registry->output->showError( $this->lang->words['mod_memid'], 11328 );
				}
				else
				{
					$this->DB->build( array( 'select' => 'members_display_name, member_id', 'from' => 'members', 'where' => 'member_id=' . intval( $this->request['member_id'] ) ) );
					$this->DB->execute();
		
					if ( ! $mem = $this->DB->fetch() )
					{
						$this->registry->output->showError( $this->lang->words['mod_memid'], 11329 );
					}
					
					$member_id		= $mem['member_id'];
					$member_name	= $mem['members_display_name'];
				}
			}
			
			//-----------------------------------------
			// Are they already a moderator?
			//-----------------------------------------
			
			$existingForums = array();
			$existingForumsByRecord = array();
			$this->DB->build( array( 'select' => '*', 'from' => 'moderators', 'where' => ( isset( $member_id ) ? "member_id={$member_id}" : "group_id={$group['g_id']}" ) ) );
			$this->DB->execute();
			while ( $row = $this->DB->fetch() )
			{
				$exploded = array_filter( explode( ',', $row['forum_id'] ), create_function( '$v', 'return (bool) $v;' ) );
				
				$existingForums = array_merge( $existingForums, $exploded );
				
				foreach ( $exploded as $fid )
				{
					$existingForumsByRecord[ $fid ] = $row['mid'];
				}
			}
			
			$alreadyAMod = array_intersect( $forum_id, $existingForums );
			$notCurrentlyAMod = array_diff( $forum_id, $existingForums );
			
			if ( !empty( $alreadyAMod ) and empty( $notCurrentlyAMod ) )
			{
				// All of the forums we have selected we are already a moderator of
				// Are they all in the same record?
				$recordId = NULL;
				foreach ( $forum_id as $fid )
				{
					if ( $recordId === NULL or $recordId == $existingForumsByRecord[ $fid ] )
					{
						$recordId = $existingForumsByRecord[ $fid ];
					}
					else
					{
						$this->registry->output->showError( 'mod_all_selected_mod_already', 11332 );
						return;
					}
				}
					
				$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=forums&amp;section=moderator&amp;act=mod&amp;do=edit&mid={$recordId}&amp;return_id={$recordId}" );
			}
			elseif ( !empty( $alreadyAMod ) )
			{
				$_alreadyAMod = array();
				$_alreadyAModByRecord = array();
				foreach ( $alreadyAMod as $id )
				{
					$_alreadyAMod[ $id ] = $this->registry->class_forums->forum_by_id[ $id ]['name'];
					
					$_alreadyAModByRecord[ $existingForumsByRecord[ $id ] ][ $id ] = $this->registry->class_forums->forum_by_id[ $id ]['name'];
				}
				
				$_notCurrentlyAMod = array();
				foreach ( $notCurrentlyAMod as $id )
				{
					$_notCurrentlyAMod[ $id ] = $this->registry->class_forums->forum_by_id[ $id ]['name'];
				}

				$this->registry->output->html .= $this->html->moderatorDuplicateForm( $mod_type, ( $mod_type == 'name' ? IPSMember::load( $member_id ) : $group ), $_alreadyAMod, $_notCurrentlyAMod, $_alreadyAModByRecord );
				return;
			}
		}
		else
		{
			/* Check the moderator */
			if ($this->request['mid'] == "")
			{
				$this->registry->output->showError( $this->lang->words['mod_valid'], 11330 );
			}
			
			/* Form bits */
			$button		= $this->lang->words['mod_edithis'];		
			$form_code	= "doedit";
			
			/* Moderator Info */
			$this->DB->build( array( 'select' => '*', 'from' => 'moderators', 'where' => "mid=" . intval( $this->request['mid'] ) ) );
			$this->DB->execute();
		
			if ( ! $mod = $this->DB->fetch() )
			{
				$this->registry->output->showError( $this->lang->words['mod_mid'], 11331 );
			}
			
			/* BW Options */
			$_tmp = IPSBWOptions::thaw( $mod['mod_bitoptions'], 'moderators', 'forums' );

			if ( count( $_tmp ) )
			{
				foreach( $_tmp as $k => $v )
				{ 
					$mod[ $k ] = $v;
				}
			}
			
			/* Other */
			$forum_id		= explode( ',', IPSText::cleanPermString( $mod['forum_id'] ) );
			$member_id		= $mod['member_id'];
			$member_name	= $mod['member_name'];
			$mod_type		= $mod['is_group'] ? 'group' : 'name';
		}
			
		/* Form Fields */
		$mod['edit_post']              = $this->registry->output->formYesNo( 'edit_post'             , $mod['edit_post'] );
		$mod['edit_topic']             = $this->registry->output->formYesNo( 'edit_topic'            , $mod['edit_topic'] );
		$mod['delete_post']            = $this->registry->output->formYesNo( 'delete_post'           , $mod['delete_post'] );
		$mod['delete_topic']           = $this->registry->output->formYesNo( 'delete_topic'          , $mod['delete_topic'] );
		$mod['view_ip']                = $this->registry->output->formYesNo( 'view_ip'               , $mod['view_ip'] );
		$mod['open_topic']             = $this->registry->output->formYesNo( 'open_topic'            , $mod['open_topic'] );								     
		$mod['close_topic']            = $this->registry->output->formYesNo( 'close_topic'           , $mod['close_topic'] );
		$mod['move_topic']             = $this->registry->output->formYesNo( 'move_topic'            , $mod['move_topic'] );
		$mod['pin_topic']              = $this->registry->output->formYesNo( 'pin_topic'             , $mod['pin_topic'] );
		$mod['unpin_topic']            = $this->registry->output->formYesNo( 'unpin_topic'           , $mod['unpin_topic'] );
		$mod['split_merge']            = $this->registry->output->formYesNo( 'split_merge'           , $mod['split_merge'] );
		$mod['mod_can_set_open_time']  = $this->registry->output->formYesNo( 'mod_can_set_open_time' , $mod['mod_can_set_open_time'] );
		$mod['mod_can_set_close_time'] = $this->registry->output->formYesNo( 'mod_can_set_close_time', $mod['mod_can_set_close_time'] );
		$mod['mass_move']              = $this->registry->output->formYesNo( 'mass_move'             , $mod['mass_move'] );
		$mod['mass_prune']             = $this->registry->output->formYesNo( 'mass_prune'            , $mod['mass_prune'] );
		$mod['topic_q']                = $this->registry->output->formYesNo( 'topic_q'               , $mod['topic_q'] );
		$mod['post_q']                 = $this->registry->output->formYesNo( 'post_q'                , $mod['post_q'] );
		$mod['allow_warn']             = $this->registry->output->formYesNo( 'allow_warn'            , $mod['allow_warn'] );
		$mod['can_mm']                 = $this->registry->output->formYesNo( 'can_mm'                , $mod['can_mm'] );
		$mod['bw_flag_spammers']       = $this->registry->output->formYesNo( 'bw_flag_spammers'      , $mod['bw_flag_spammers'] );
		$mod['forums']				   = $this->registry->output->formMultiDropdown( 'forums[]'		 , $this->registry->getClass('class_forums')->adForumsForumList(1), $forum_id );
		
		$mod['bw_mod_soft_delete']				= $this->registry->output->formYesNo( "bw_mod_soft_delete", $mod['bw_mod_soft_delete'] );
		$mod['bw_mod_un_soft_delete']			= $this->registry->output->formYesNo( "bw_mod_un_soft_delete", $mod['bw_mod_un_soft_delete'] );
		$mod['bw_mod_soft_delete_see']			= $this->registry->output->formYesNo( "bw_mod_soft_delete_see", $mod['bw_mod_soft_delete_see'] );


		/* Output */
		$this->registry->output->extra_nav[]	= array( '', $this->lang->words['mod_' . $type ] );
		$this->registry->output->html	.= $this->html->moderatorPermissionForm( $mod, $form_code, $mod['mid'], $member_id, $mod_type, $group['g_id'], $group['g_name'], $button );
	}
	
	/**
	 * Refine member search, if necessary
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function moderatorAddPreform()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$type                 = $this->request['name'] ? 'name' : 'group';
		$this->request['fid'] = ltrim( $this->request['modforumids'], ',' );
		
		//-----------------------------------------
		// Are we adding a group as a mod?
		//-----------------------------------------
		
		if ( $type == 'group' )
		{
			$this->modForm();
			return;
		}
		
		//-----------------------------------------
		// Got forums?
		//-----------------------------------------
		
		if ( ! $this->request['fid'] )
		{
			$this->registry->output->global_message = $this->lang->words['mod_noforums'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=forums&section=forums' );
		}
		
		//-----------------------------------------
		// Else continue as normal.
		//-----------------------------------------
		
		if ( $this->request['name'] == "" )
		{
			$this->registry->output->global_message = $this->lang->words['mod_nomember'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=forums&section=forums' );
		}
		
		$this->DB->build( array( 'select' => 'member_id, members_display_name as name', 'from' => 'members', 'where' => "members_display_name LIKE '{$this->request['name']}%' OR members_display_name LIKE '{$this->request['name']}%'" ) );
		$this->DB->execute();
		
		if (! $this->DB->getTotalRows() )
		{
			$this->registry->output->global_message = $this->lang->words['mod_noresults'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=forums&section=forums' );
		}
		
		//-----------------------------------------
		// Show possible matches
		//-----------------------------------------
		
		$form_array = array();
		
		while ( $r = $this->DB->fetch() )
		{
			$form_array[] = array( $r['member_id'] , $r['name'] );
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->moderatorSelectForm( $this->request['fid'], $this->registry->output->formDropdown( "member_id", $form_array ) );
	}
	
	/**
	 * Add a forum to an existing moderator
	 */
	public function addForumToSet()
	{
		$mid = intval( $this->request['mid'] );
		
		$record = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'moderators', 'where' => "mid={$mid}" ) );
		if ( !$record['mid'] )
		{
			$this->registry->output->showError( $this->lang->words['mod_valid'], 11333 );
		}
				
		$existingForums = array_filter( explode( ',', $record['forum_id'] ), create_function( '$v', 'return (bool) $v;' ) );
		$forumsToAdd = array_filter( explode( ',', $this->request['forums'] ), create_function( '$v', 'return (bool) $v;' ) );
		
		foreach ( $forumsToAdd as $f )
		{
			if ( !in_array( $f, $existingForums ) )
			{
				$existingForums[] = $f;
			}
		}
				
		$this->DB->update( 'moderators', array( 'forum_id' => ',' . implode( ',', $existingForums ) . ',' ), "mid={$mid}" );
		
		
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['mod_editedlog'], $record['member_name'] ) );
		
		$this->rebuildModeratorCache();
		
		$this->registry->output->global_message = $this->lang->words['mod_added'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'].'&module=forums&section=forums' );
		
	}
}