<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Forum management
 * Last Updated: $Date: 2012-05-17 08:01:44 -0400 (Thu, 17 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		Tuesday 17th August 2004
 * @version		$Revision: 10764 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forums_forums_forums extends ipsCommand
{
	/**
 	 * Skin HTML object
 	 *
 	 * @var		object
 	 */
	protected $html;

	/**
	 * Main entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		Outputs to screen
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load skin & lang
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_forums' );
		$this->html->form_code    = 'module=forums&amp;section=forums&amp;';
		$this->html->form_code_js = 'module=forums&amp;section=forums&amp;';
		
		$this->lang->loadLanguageFile( array( 'admin_forums' ) );
		
		//-----------------------------------------
		// LOAD HTML
		//-----------------------------------------
		
		$this->registry->getClass('class_forums')->html =& $this->html;
		
		/* Load tagging stuff */
		if ( ! $this->registry->isClassLoaded('tags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'tags', classes_tags_bootstrap::run( 'forums', 'topics' ) );
		}
		
		/* Init */
		if ( ! $this->registry->isClassLoaded('topics') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
			$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}
		
		//-----------------------------------------
		// To do...
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'forum_add':
			case 'new':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_add' );
				$this->forumForm( 'new' );
				break;
			case 'donew':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_add' );
				$this->forumSave( 'new' );
				break;
			//------------------- ----------------------
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_edit' );
				$this->forumForm( 'edit' );
				break;
			case 'doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_edit' );
				$this->forumSave( 'edit' );
				break;
			//-----------------------------------------
			case 'pedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_permissions' );
				$this->permEditForm();
				break;
			case 'pdoedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_permissions' );
				$this->permDoEdit();
				break;
			//-----------------------------------------
			case 'doreorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_reorder' );
				$this->doReorder();
				break;
			//-----------------------------------------
			case 'delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_delete' );
				$this->deleteForm();
				break;
			case 'dodelete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_delete' );
				$this->doDelete();
				break;
			//-----------------------------------------
			case 'recount':
				$this->recount();
				break;
			//-----------------------------------------
			case 'empty':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_empty' );
				$this->emptyForum();
				break;
			case 'doempty':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_empty' );
				$this->doEmpty();
				break;
			//-----------------------------------------
			case 'frules':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_rules' );
				$this->showRules();
				break;
			case 'dorules':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_rules' );
				$this->doRules();
				break;
			//-----------------------------------------
			case 'skinedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_skins' );
				$this->skinEdit();
				break;
			case 'doskinedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'forums_skins' );
				$this->doSkinEdit();
				break;
			//-----------------------------------------
			case 'forums_overview':
			default:
				$this->request['do'] = 'forums_overview';
				$this->showForums();
				break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Edit skins assigned to forums
	 *
	 * @return	@e void		Outputs to screen
	 */	
	public function skinEdit()
	{
		/* INI */
		$this->request['f'] = intval( $this->request['f'] );
		
		if( $this->request['f'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1131 );
		}
		
		/* Forum Data */
		$forum = $this->registry->getClass('class_forums')->forum_by_id[ $this->request['f'] ];
		
		/* Check the forum */
		if ( ! $forum['id'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1132 );
		}
		
		if ( ! $forum['skin_id'] )
		{
			$forum['skin_id'] = -1;
		}
		
		/* Skins */
		$skin_list	= array_merge( array( array( -1, $this->lang->words['for_noneall'] ) ), $this->registry->output->generateSkinDropdown() );

		/* Form Data */
		$forum['fsid']              = $this->registry->output->formDropdown( 'fsid', $skin_list, $forum['skin_id'] );
		$forum['apply_to_children'] = $this->registry->output->formYesNo( 'apply_to_children' );
		
		/* Output */
		$this->registry->output->extra_nav[]	= array( '', $this->lang->words['modify_skin_head'] );
		$this->registry->output->html .= $this->html->forumSkinOptions( $this->request['f'], $forum );
	}
	
	/**
	 * Save the skin assigned to the forum
	 *
	 * @return	@e void		Outputs to screen
	 */	
	public function doSkinEdit()
	{
		/* INI */
		$this->request['f'] = intval( $this->request['f'] );
		
		/* Check the forum */
		if ($this->request['f'] == "")
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1133 );
		}
		
		/* Forum Data */
		$forum = $this->registry->getClass('class_forums')->forum_by_id[ $this->request['f'] ];
		
		/* Update the forum */
		$this->DB->update( 'forums', array( 'skin_id' => $this->request['fsid'] ), 'id='.$this->request['f'] );
		
		/* Apply to children */
		if( $this->request['apply_to_children'] )
		{
			$ids = $this->registry->getClass('class_forums')->forumsGetChildren( $this->request['f'] );
			
			if ( count( $ids ) )
			{
				$this->DB->update( 'forums', array( 'skin_id' => $this->request['fsid'] ), 'id IN ('.implode(",",$ids).')' );
			}
		}
		
		$this->registry->output->global_message = $this->lang->words['for_skinup'];

		$this->registry->getClass('class_forums')->forumsInit();
		
		/* Bounce */		
		$this->request['f'] = $this->registry->getClass('class_forums')->forum_by_id[ $this->request['f'] ]['parent_id'];
		$this->showForums();
	}
	
	/**
	 * Show the form to edit rules
	 *
	 * @return	@e void		Outputs to screen
	 */	
	public function showRules()
	{
		/* INI */
		$this->request['f'] = intval( $this->request['f'] );
		
		if( ! $this->request['f'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1134 );
		}
		
		$this->DB->build( array( 'select' => 'id, name, show_rules, rules_title, rules_text, rules_raw_html', 'from' => 'forums', 'where' => "id=".$this->request['f'] ) );
		$this->DB->execute();
		
		//-----------------------------------------
		// Make sure we have a legal forum
		//-----------------------------------------
		
		if ( ! $this->DB->getTotalRows() )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1135 );
		}
		
		$forum = $this->DB->fetch();
		
		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$_editor = new $classToLoad();
		
		$_editor->setAllowHtml( 1 );
		$_editor->setAllowSmilies( 1 );
		$_editor->setContent( $forum['rules_text'], 'rules' );

		$forum['_editor']	= $_editor->show( 'body', array( 'height' => 350, 'isHtml' => 1 ) );

        /* Form Fields */
        $forum['_show_rules'] = $this->registry->output->formDropdown( "show_rules", array( 
																							array( '0' , $this->lang->words['for_rulesdont'] ),
																							array( '1' , $this->lang->words['for_ruleslink'] ),
																							array( '2' , $this->lang->words['for_rulesfull'] )
																							), $forum['show_rules'] );
																								
		$forum['_title'] = $this->registry->output->formInput( "title", IPSText::stripslashes( str_replace( "'", '&#039;', $forum['rules_title'] ) ) );

		$forum['rules_raw_html'] = $this->registry->output->formCheckbox( 'rules_raw_html', $forum['rules_raw_html'] );

		/* Output */
		$this->registry->output->extra_nav[]	= array( '', $this->lang->words['frm_rulessetup'] );
		$this->registry->output->html .= $this->html->forumRulesForm( $forum['id'], $forum );
	}
	
	/**
	 * Save the forum rules
	 *
	 * @return	@e void		Outputs to screen
	 */	
	public function doRules()
	{
		/* INI */
		$this->request['f'] = intval( $this->request['f'] );
				
		if( $this->request['f'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1136 );
		}
		
		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$_editor = new $classToLoad();
	
		/* About me editor */
		$_editor->setAllowHtml( 1 );
		$_editor->setAllowSmilies( 0 );
		
		$_POST['body'] 		= $_editor->process( $_POST['body'] );
		
		IPSText::getTextClass('bbcode')->parsing_section	= 'rules';
		$_POST['body']		= IPSText::getTextClass('bbcode')->preDbParse( $_POST['body'] );

		$rules = array( 
						'rules_title'		=> IPSText::stripslashes( $_POST['title'] ),
						'rules_text'		=> IPSText::stripslashes( $_POST['body'] ),
						'show_rules'		=> $this->request['show_rules'],
						//'rules_raw_html'	=> intval($this->request['rules_raw_html']),
					  );
					
		$this->DB->update( 'forums', $rules, 'id='.$this->request['f'] );

		$this->registry->output->global_message = $this->lang->words['for_rulesup'];
		
		//-----------------------------------------
		// Bounce back to parent...
		//-----------------------------------------
		
		$this->request['f'] = $this->registry->getClass('class_forums')->forum_by_id[ $this->request['f'] ]['parent_id'];
		$this->showForums();
	}
	
	/**
	 * Recount the forum
	 *
	 * @param	integer		[optional] Forum id
	 * @return	@e void		Outputs to screen
	 */	
	public function recount($f_override="")
	{
		//-----------------------------------------
		// Remap
		//-----------------------------------------
		
		if( $f_override )
		{
			ipsRegistry::$request['f'] = $f_override;
		}
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
		$modfunc		= new $classToLoad( $this->registry );
		
		$modfunc->forumRecount($this->request['f']);

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['for_recountedlog'], $this->registry->getClass('class_forums')->forum_by_id[$this->request['f']]['name'] ) );
		
		$this->registry->output->global_message = $this->lang->words['for_resynched'];
		
		//-----------------------------------------
		// Bounce back to parent...
		//-----------------------------------------
		
		ipsRegistry::$request['f'] = $this->registry->getClass('class_forums')->forum_by_id[ $this->request['f'] ]['parent_id'] ;
		$this->showForums();
	}
	
	/**
	 * Show the form to empty a forum
	 *
	 * @return	@e void		Outputs to screen
	 */	
	public function emptyForum()
	{
		/* INI */
		$this->request['f'] = intval( $this->request['f'] );
		$form_array         = array();
		
		if( !$this->request['f'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1137 );
		}
		
		$forum	= $this->DB->buildAndFetch( array( 'select' => 'id, name', 'from' => 'forums', 'where' => "id=" . $this->request['f'] ) );
		
		//-----------------------------------------
		// Make sure we have a legal forum
		//-----------------------------------------
		
		if ( ! $forum['id'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 1138 );
		}
		
		/* Now lets check and see if we've any archived topics and that */
		$count = $this->registry->topics->getTopics( array( 'forumId'      => array( $forum['id'] ),
															'archiveState' => array( 'archived', 'working', 'restore' ),
															'getCountOnly' => true ) );
		
		if ( $count )
		{
			$this->registry->output->showError( $this->lang->words['contains_archived_topics_so_there_haha'], '1138.1' );
		}
		
		//-----------------------------------------
		
		$this->registry->output->extra_nav[]	= array( '', $this->lang->words['frm_emptytitle'] );
		$this->registry->output->html .= $this->html->forumEmptyForum( $forum );		
	}
	
	/**
	 * Empty a forum
	 *
	 * @return	@e void		Outputs to screen
	 */	
	public function doEmpty()
	{
		/* INI */
		$this->request['f']	= intval( $this->request['f'] );
		$soFar				= intval($this->request['sofar']);
		$thisCycle			= 0;
				
		//-----------------------------------------
		// Get module
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
		$modfunc		= new $classToLoad( $this->registry );
		
		if( !$this->request['f'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid_source'], 1139 );
		}
		
		//-----------------------------------------
		// Check to make sure its a valid forum.
		//-----------------------------------------
		
		$forum	= $this->DB->buildAndFetch( array( 'select' => 'id, name, posts, topics', 'from' => 'forums', 'where' => "id=" . $this->request['f'] ) );
		
		if( !$forum['id'] )
		{
			$this->registry->output->showError( $this->lang->words['for_nodetails'], 11310 );
		}
		
		$this->DB->build( array( 'select' => 'tid', 'from' => 'topics', 'where' => "forum_id=" . $this->request['f'], 'limit' => array( 0, 200 ) ) );
		$outer = $this->DB->execute();
		
		//-----------------------------------------
		// What to do..
		//-----------------------------------------
		
		while( $t = $this->DB->fetch($outer) )
		{
			$modfunc->topicDeleteFromDB( $t['tid'] );
			$thisCycle++;
		}
		
		//-----------------------------------------
		// Rebuild stats and cache if we're done
		//-----------------------------------------
		
		if( !$thisCycle )
		{
			$modfunc->forumRecount( $this->request['f'] );
			
			$this->cache->rebuildCache( 'stats', 'global' );

			$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['for_emptiedlog'], $forum['name'] ) );
		
			$this->request['f'] = $this->registry->getClass('class_forums')->forum_by_id[ $this->request['f'] ]['parent_id'];

			$this->registry->output->global_message   = $this->lang->words['for_emptied'];
			$this->showForums();
		}
		else
		{
			$soFar	= $soFar + $thisCycle;
			$this->registry->output->html           .= $this->registry->output->global_template->temporaryRedirect( $this->settings['base_url'] . $this->html->form_code . "&do=doempty&f={$this->request['f']}&sofar={$soFar}", sprintf( $this->lang->words['emptyforum_sofarcycle'], $soFar ) );
		}
	}
	
	/**
	 * Show the form to delete a form
	 *
	 * @return	@e void		Outputs to screen
	 */	
	public function deleteForm()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$this->request['f'] = intval( $this->request['f'] );		
		$form_array = array();
		if ( ! $this->request['f'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid_delete'], 11311 );
		}
		
		$this->DB->build( array( 'select' => 'id, name, parent_id', 'from' => 'forums', 'order' => 'position' ) );
		$this->DB->execute();
		
		if( $this->DB->getTotalRows() < 2 )
		{
			$this->registry->output->showError( $this->lang->words['for_lastforum'], 11312 );
		}
		
		/* Now lets check and see if we've any archived topics and that */
		$count = $this->registry->topics->getTopics( array( 'forumId'      => array( $this->request['f'] ),
															'archiveState' => array( 'archived', 'working', 'restore' ),
															'getCountOnly' => true ) );
		
		if ( $count )
		{
			$this->registry->output->showError( $this->lang->words['contains_archived_topics_so_there_haha'], '11312.1' );
		}
		
		while( $r = $this->DB->fetch() )
		{
			if( $r['id'] == $this->request['f'] )
			{
				$name 	= $r['name'];
				$is_cat	= $r['parent_id'] > 0 ? 0 : 1;
				continue;
			}
		}
		
		//-----------------------------------------
		// Where would you like to move topics?
		//-----------------------------------------
		
		$posts = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'topics', 'where' => 'forum_id='.$this->request['f'] ) );
		if( $posts['count'] )
		{
			$move = $this->registry->output->formDropdown( "MOVE_ID", array_merge( array( array( 0, $this->lang->words['forum_delete_select'] ) ), $this->registry->getClass('class_forums')->adForumsForumList( 1, TRUE, array( $this->request['f'] ) ) ) );
		}
		
		//-----------------------------------------
		// Where would you like to move subforums?
		//-----------------------------------------
		
		$subforums = array();
		$this->DB->build( array( 'select' => 'id', 'from' => 'forums', 'where' => "parent_id={$this->request['f']}" ) );
		$this->DB->execute();
		while ( $row = $this->DB->fetch() )
		{
			$subforums[] = $row['id'];
		}
		
		if ( !empty( $subforums ) )
		{		
			$subforums[] = $this->request['f'];
			$subs = $this->registry->output->formDropdown( "new_parent_id", array_merge( array( array( 0, $this->lang->words['forum_delete_select'] ) ), $this->registry->getClass('class_forums')->adForumsForumList( FALSE, FALSE, $subforums ) ) );
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------

		/* Output */
		$this->registry->output->extra_nav[]	= array( '', $this->lang->words['frm_deletebutton'] );
		$this->registry->output->html .= $this->html->forumDeleteForm( $this->request['f'], $name, $move, $is_cat, $subs );
	}
	
	/**
	 * Delete a forum
	 *
	 * @return	@e void		Outputs to screen
	 */	
	public function doDelete()
	{
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------
		
		$this->registry->adminFunctions->checkSecurityKey();
		
		//-----------------------------------------
		// Continue
		//-----------------------------------------
		
		$this->request['f']             = intval( $this->request['f'] );
		$this->request['new_parent_id'] = intval( $this->request['new_parent_id'] );
		
		$forum	= $this->registry->class_forums->forum_by_id[ $this->request['f'] ];
		
		if( ! $forum['id'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid_source'], 11313 );
		}
		
		if( ! $this->request['new_parent_id'] )
		{
			$this->request['new_parent_id'] = -1;
		}
		else
		{
			if( $this->request['new_parent_id'] == $this->request['f'] )
			{
				$this->registry->output->global_message = $this->lang->words['for_child_no_parent'];
				$this->deleteForm();
				return;
			}
		}
		
		//-----------------------------------------
		// Would deleting this category orphan the only
		// remaining forums?
		//-----------------------------------------
		
		if( $forum['parent_id'] == -1 )
		{
			$otherParent	= 0;
			
			foreach( $this->registry->class_forums->forum_by_id as $id => $data )
			{
				if( $data['parent_id'] == -1 )
				{
					$otherParent	= $id;
					break;
				}
			}
			
			if( !$otherParent )
			{
				$this->registry->output->showError( $this->lang->words['nodelete_last_cat'], 11364 );
			}
		}
		
		//-----------------------------------------
		// Get library
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
		$modfunc = new $classToLoad( $this->registry );

		/* Load tagging stuff */
		if ( ! $this->registry->isClassLoaded('tags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'tags', classes_tags_bootstrap::run( 'forums', 'topics' ) );
		}
		
		//-----------------------------------------
		// Move stuff
		//-----------------------------------------
				
		if ( isset( $this->request['MOVE_ID'] ) )
		{
			$this->request['MOVE_ID'] = intval( $this->request['MOVE_ID'] );

			if ( ! $this->request['MOVE_ID'] )
			{
				$this->registry->output->global_error = $this->lang->words['forum_delete_none_selected'];
				$this->deleteForm();
				return;
			}
			
			if ( $this->request['MOVE_ID'] == $this->request['f'] )
			{
				$this->registry->output->global_error = $this->lang->words['for_wherewhatwhy'];
				$this->deleteForm();
				return;
			}
			
			//-----------------------------------------
			// Move topics...
			//-----------------------------------------
			
			$this->DB->update( 'topics', array( 'forum_id' => $this->request['MOVE_ID'] ), 'forum_id='.$this->request['f'] );
			
			//-----------------------------------------
			// Move polls...
			//-----------------------------------------
			
			$this->DB->update( 'polls', array( 'forum_id' => $this->request['MOVE_ID'] ), 'forum_id='.$this->request['f'] );
			
			//-----------------------------------------
			// Move voters...
			//-----------------------------------------
			
			$this->DB->update( 'voters', array( 'forum_id' => $this->request['MOVE_ID'] ), 'forum_id='.$this->request['f'] );
			
			/* Move tags */
			$this->registry->tags->moveTagsByParentId( $this->request['f'], $this->request['MOVE_ID'] );
		
			$modfunc->forumRecount( $this->request['MOVE_ID'] );
		}
		
		//-----------------------------------------
		// Delete the forum
		//-----------------------------------------
		
		$this->DB->delete( 'forums', "id=".$this->request['f'] );
		$this->DB->delete( 'permission_index', "app='forums' AND perm_type='forum' AND perm_type_id=".$this->request['f'] );
		
		//-----------------------------------------
		// Remove moderators from this forum
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'moderators', 'where' => "forum_id LIKE '%,{$this->request['f']},%'" ) );
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$forums		= explode( ',', IPSText::cleanPermString( $r['forum_id'] ) );
			$newForums	= array();
			
			foreach( $forums as $aForumId )
			{
				if( $aForumId != $this->request['f'] )
				{
					$newForums[] = $aForumId;
				}
			}
			
			if( !count($newForums) )
			{
				$this->DB->delete( 'moderators', "mid=" . $r['mid'] );
			}
			else
			{
				$this->DB->update( 'moderators', array( 'forum_id' => ',' . implode( ',', $newForums ) . ',' ), 'mid=' . $r['mid'] );
			}
		}
		
		//-----------------------------------------
		// Delete forum subscriptions
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'forums', 'forums' );
		$_like->remove( $this->request['f'] );
		
		//-----------------------------------------
		// Update children
		//-----------------------------------------
		
		$this->DB->update( 'forums', array( 'parent_id' => $this->request['new_parent_id'] ), "parent_id=" . $this->request['f'] );

		//-----------------------------------------
		// Rebuild moderator cache
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir( 'forums' ) . '/modules_admin/forums/moderator.php', 'admin_forums_forums_moderator' );
		$moderator   = new $classToLoad();
		$moderator->makeRegistryShortcuts( $this->registry );
		$moderator->rebuildModeratorCache();
		
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['for_removedlog'], $forum['name'] ) );

		$this->registry->output->global_message	= $this->lang->words['for_removed'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}

	/**
	 * Show the form to edit a forum
	 *
	 * @param	string		[new|edit]
	 * @param	boolean		Whether to change forum to category/back
	 * @return	@e void		Outputs to screen
	 */	
	public function forumForm( $type='edit', $changetype=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$addnew_type = ( isset( $this->request['type'] ) ) ? $this->request['type'] : 'forum';
		
		$form        = array();
		$forum       = array();
		$forum_id    = $this->request['f']           ? intval( $this->request['f'] ) : 0;
		$parentid    = intval( $this->request['p'] ) ? intval( $this->request['p'] ) : -1;
		$cat_id      = $this->request['c']           ? intval( $this->request['c'] ) : 0;
		$f_name      = $this->request['name']        ? $this->request['name']        : '';
		$subcanpost  = $cat_id == 1                  ? 0                             : 1;
		$perm_matrix = "";
		$dd_state    = array( 0 => array( 1, $this->lang->words['for_active'] ), 1 => array( 0, $this->lang->words['for_readonly'] ) );
		$dd_moderate = array(
							 0 => array( 0, $this->lang->words['for_no'] ),
							 1 => array( 1, $this->lang->words['for_modall'] ),
							 2 => array( 2, $this->lang->words['for_modtop'] ),
							 3 => array( 3, $this->lang->words['for_modrep'] ),
							);
		$dd_prune    = array( 
							 0 => array( 1, $this->lang->words['for_today'] ),
							 1 => array( 5, $this->lang->words['for_last5']  ),
							 2 => array( 7, $this->lang->words['for_last7']  ),
							 3 => array( 10, $this->lang->words['for_last10'] ),
							 4 => array( 15, $this->lang->words['for_last15'] ),
							 5 => array( 20, $this->lang->words['for_last20'] ),
							 6 => array( 25, $this->lang->words['for_last25'] ),
							 7 => array( 30, $this->lang->words['for_last30'] ),
							 8 => array( 60, $this->lang->words['for_last60'] ),
							 9 => array( 90, $this->lang->words['for_last90'] ),
							 10=> array( 100, $this->lang->words['for_showall']     ),
							);
		
		$dd_order    = array( 
							 0 => array( 'last_post', $this->lang->words['for_s_last'] ),
							 1 => array( 'title'    , $this->lang->words['for_s_topic'] ),
							 2 => array( 'starter_name', $this->lang->words['for_s_name'] ),
							 3 => array( 'posts'    , $this->lang->words['for_s_post'] ),
							 4 => array( 'views'    , $this->lang->words['for_s_view'] ),
							 5 => array( 'start_date', $this->lang->words['for_s_date'] ),
							 6 => array( 'last_poster_name'   , $this->lang->words['for_s_poster'] )
							);

		$dd_by       = array( 
							 0 => array( 'Z-A', $this->lang->words['for_desc'] ),
							 1 => array( 'A-Z', $this->lang->words['for_asc']  )
							);
							
		$dd_filter	 = array(
							 0 => array( 'all', 	$this->lang->words['for_all'] ),
							 1 => array( 'open', 	$this->lang->words['for_open'] ),
							 2 => array( 'hot',		$this->lang->words['for_hot'] ),
							 3 => array( 'poll',	$this->lang->words['for_poll'] ),
							 4 => array( 'locked',	$this->lang->words['for_locked'] ),
							 5 => array( 'moved',	$this->lang->words['for_moved'] ),
							 6 => array( 'istarted', $this->lang->words['for_istarted'] ),
							 7 => array( 'ireplied', $this->lang->words['for_ireplied'] ),
							);

		//-----------------------------------------
		// EDIT
		//-----------------------------------------
		
		if ( $type == 'edit' or $this->request['duplicate'] )
		{
			//-----------------------------------------
			// Check
			//-----------------------------------------
			
			if ( ! $forum_id )
			{
				$this->registry->output->showError( $this->lang->words['for_noforumselected'], 11314 );
			}
			
			//-----------------------------------------
			// Do not show forum in forum list
			//-----------------------------------------
			
			$this->registry->getClass('class_forums')->exclude_from_list = $forum_id;
			
			//-----------------------------------------
			// Get this forum
			//-----------------------------------------
			
			$forum = $this->registry->class_forums->getForumById( $forum_id );
			
			//-----------------------------------------
			// Check
			//-----------------------------------------
			
			if( !$forum['id'] )
			{
				$this->registry->output->showError( $this->lang->words['for_noid'], 11315 );
			}
			
			//-----------------------------------------
			// Set up code buttons
			//-----------------------------------------
			
			$addnew_type	= $forum['parent_id'] == -1 ? 'category' : 'forum';
			
			if( $changetype )
			{
				$addnew_type = $addnew_type == 'category' ? 'forum' : 'category';
			}
			
			if( $addnew_type == 'category' )
			{
				$title  		= sprintf( $this->lang->words['for_editcat'], $forum['name'] );
				$button 		= $this->lang->words['for_editcat_button'];
				$code   		= "doedit";
			}
			else
			{
				$title  		= sprintf( $this->lang->words['for_editfor'], $forum['name'] );
				$button 		= $this->lang->words['for_editfor_button'];
				$code   		= "doedit";
			}
			
			//-----------------------------------------
			// Duplicating?
			//-----------------------------------------
			
			if ( $this->request['duplicate'] )
			{
				$forum['id'] = 0;
				$this->request['f'] = 0;
				$code = 'donew';
			}
		}
		
		//-----------------------------------------
		// NEW
		//-----------------------------------------
		
		else
		{
			# Ensure there is an ID
			$this->request['f'] = 0;
			
			if( $changetype )
			{
				$addnew_type = $addnew_type == 'category' ? 'forum' : 'category';
			}

			if( $addnew_type == 'category' )
			{
				$forum = array(
								'sub_can_post'				=> $subcanpost,
								'name'						=> $f_name ? $f_name : $this->lang->words['for_newcat'],
								'parent_id'					=> $parentid,
								'use_ibc'					=> 1,
								'allow_poll'				=> 1,
								'prune'						=> 100,
								'topicfilter'				=> 'all',
								'sort_key'					=> 'last_post',
								'sort_order'				=> 'Z-A',
								'inc_postcount'				=> 1,
								'description'				=> '',
								'redirect_url'				=> '',
								'password'					=> '',
								'password_override'			=> '',
								'redirect_on'				=> 0,
								'redirect_hits'				=> 0,
								'permission_showtopic'		=> '',
								'permission_custom_error'	=> '',
								'use_html'					=> 0,
								'allow_pollbump'			=> 0,
								'forum_allow_rating'		=> 0,
								'preview_posts'				=> 0,
								'notify_modq_emails'		=> 0,
								'can_view_others'			=> 1,
								
							  );
							  
				$title	= $this->lang->words['for_addcat'];
				$button	= $this->lang->words['for_addcat'];
				$code	= "donew";
			}
			else
			{
				$forum = array(
								'sub_can_post'				=> $subcanpost,
								'name'						=> $f_name ? $f_name : $this->lang->words['for_newfor'],
								'parent_id'					=> $parentid,
								'use_ibc'					=> 1,
								'allow_poll'				=> 1,
								'prune'						=> 100,
								'topicfilter'				=> 'all',
								'sort_key'					=> 'last_post',
								'sort_order'				=> 'Z-A',
								'inc_postcount'				=> 1,
								'description'				=> '',
								'redirect_url'				=> '',
								'password'					=> '',
								'password_override'			=> '',
								'redirect_on'				=> 0,
								'redirect_hits'				=> 0,
								'permission_showtopic'		=> '',
								'permission_custom_error'	=> '',
								'use_html'					=> 0,
								'allow_pollbump'			=> 0,
								'forum_allow_rating'		=> 0,
								'preview_posts'				=> 0,
								'notify_modq_emails'		=> 0,
								'min_posts'					=> 0,
								'hide_last_info'			=> 0,
								'can_view_others'			=> 1,
							  );
							  
				$title       = $this->lang->words['for_addfor'];
				$button      = $this->lang->words['for_addfor'];
				$code        = "donew";
			}
		}

		//-----------------------------------------
		// Build forumlist
		//-----------------------------------------
		
		$forumlist = $this->registry->getClass('class_forums')->adForumsForumList();
		
		//-----------------------------------------
		// Build group list
		//-----------------------------------------		
		
		$mem_group = array();
		
		foreach( $this->caches['group_cache'] as $g_id => $group )
		{
			$mem_group[] = array( $g_id , $group['g_title'] );
		}		
			
		//-----------------------------------------
		// Generate form items
		//-----------------------------------------
		
		# Main settings
		$form['name']         = $this->registry->output->formInput(   'name'        , IPSText::parseCleanValue( !empty( $_POST['name'] ) ? $_POST['name'] : $forum['name'] ) );
		$form['description']  = $this->registry->output->formTextarea("description" , IPSText::br2nl( !empty( $_POST['description']) ? $_POST['description'] : $forum['description'] ) );
		$form['parent_id']    = $this->registry->output->formDropdown("parent_id"   , $forumlist, !empty($_POST['parent_id'] ) ? $_POST['parent_id']    : $forum['parent_id'] );
		$form['sub_can_post'] = $this->registry->output->formYesNo(  'sub_can_post', !empty($_POST['sub_can_post']) ? $_POST['sub_can_post'] : ( $forum['sub_can_post'] == 1 ? 0 : 1 ) );
		
		# Redirect options
		$form['redirect_url']  = $this->registry->output->formInput( 'redirect_url' , !empty($_POST['redirect_url']) ? $_POST['redirect_url']  : $forum['redirect_url']  );
		$form['redirect_on']   = $this->registry->output->formYesNo('redirect_on'  , !empty($_POST['redirect_on']) ? $_POST['redirect_on']   : $forum['redirect_on']   );
		$form['redirect_hits'] = $this->registry->output->formInput( 'redirect_hits', !empty($_POST['redirect_hits']) ? $_POST['redirect_hits'] : $forum['redirect_hits'] );
		
		# Permission settings
		$form['permission_showtopic']    = $this->registry->output->formYesNo(  'permission_showtopic'   , !empty($_POST['permission_showtopic']) ? $_POST['permission_showtopic'] : $forum['permission_showtopic'] );
		$form['permission_custom_error'] = $this->registry->output->formTextarea("permission_custom_error", IPSText::br2nl( !empty($_POST['permission_custom_error']) ? $_POST['permission_custom_error'] : $forum['permission_custom_error'] ) );
		
		# Forum settings
		$form['use_html']           = $this->registry->output->formYesNo('use_html'          , !empty($_POST['use_html']) ? $_POST['use_html']            : $forum['use_html'] );
		$form['use_ibc']            = $this->registry->output->formYesNo('use_ibc'           , !empty($_POST['use_ibc']) ? $_POST['use_ibc']             : $forum['use_ibc']  );
		$form['allow_poll']         = $this->registry->output->formYesNo('allow_poll'        , !empty($_POST['allow_poll']) ? $_POST['allow_poll']          : $forum['allow_poll']  );
		$form['allow_pollbump']     = $this->registry->output->formYesNo('allow_pollbump'    , !empty($_POST['allow_pollbump']) ? $_POST['allow_pollbump']      : $forum['allow_pollbump']  );
		$form['inc_postcount']      = $this->registry->output->formYesNo('inc_postcount'     , !empty($_POST['inc_postcount']) ? $_POST['inc_postcount']       : $forum['inc_postcount']  );
		$form['forum_allow_rating'] = $this->registry->output->formYesNo('forum_allow_rating', !empty($_POST['forum_allow_rating']) ? $_POST['forum_allow_rating']  : $forum['forum_allow_rating']  );
		$form['min_posts_post']		= $this->registry->output->formInput('min_posts_post'     , !empty($_POST['min_posts_post']) ? $_POST['min_posts_post']      : $forum['min_posts_post']  );
		$form['min_posts_view']		= $this->registry->output->formInput('min_posts_view'     , !empty($_POST['min_posts_view']) ? $_POST['min_posts_view']      : $forum['min_posts_view']  );
		$form['can_view_others']	= $this->registry->output->formYesNo('can_view_others'   , !empty($_POST['can_view_others']) ? $_POST['can_view_others']     : $forum['can_view_others']  );
		$form['hide_last_info']		= $this->registry->output->formYesNo('hide_last_info'   , !empty($_POST['hide_last_info']) ? $_POST['hide_last_info']     : $forum['hide_last_info']  );
		$form['disable_sharelinks'] = $this->registry->output->formYesNo('disable_sharelinks'   , !empty($_POST['disable_sharelinks']) ? $_POST['disable_sharelinks']     : $forum['disable_sharelinks']  );

		# Mod settings
		$form['preview_posts']      = $this->registry->output->formDropdown(		"preview_posts"    		, $dd_moderate, !empty($_POST['preview_posts']) ? $_POST['preview_posts'] 	: $forum['preview_posts'] );
		$form['notify_modq_emails'] = $this->registry->output->formInput(  		'notify_modq_emails'	, !empty($_POST['notify_modq_emails']) ? $_POST['notify_modq_emails'] 	: $forum['notify_modq_emails'] );
		$form['password']           = $this->registry->output->formInput(  		'password'          	, !empty($_POST['password']) ? $_POST['password']           	: $forum['password'] );
		$form['password_override']  = $this->registry->output->formMultiDropdown(  	'password_override[]'	, $mem_group, !empty($_POST['password_override']) ? $_POST['password_override'] : explode( ",", $forum['password_override'] ) );
		
		# Sorting settings
		$form['prune']      		= $this->registry->output->formDropdown("prune"     , $dd_prune, !empty($_POST['prune']) ? $_POST['prune']		: $forum['prune'] );
		$form['sort_key']   		= $this->registry->output->formDropdown("sort_key"  , $dd_order, !empty($_POST['sort_key']) ? $_POST['sort_key']	: $forum['sort_key'] );
		$form['sort_order'] 		= $this->registry->output->formDropdown("sort_order", $dd_by   , !empty($_POST['sort_order']) ? $_POST['sort_order'] 	: $forum['sort_order'] );
		$form['topicfilter'] 		= $this->registry->output->formDropdown("topicfilter", $dd_filter, !empty($_POST['topicfilter']) ? $_POST['topicfilter'] : $forum['topicfilter'] );
		
		$form['bw_disable_tagging']  = $this->registry->output->formYesNo("bw_disable_tagging", !empty($_POST['bw_disable_tagging']) ? $_POST['bw_disable_tagging'] : $forum['bw_disable_tagging'] );
		$form['bw_disable_prefixes'] = $this->registry->output->formYesNo("bw_disable_prefixes", !empty($_POST['bw_disable_prefixes']) ? $_POST['bw_disable_prefixes'] : $forum['bw_disable_prefixes'] );
		$form['tag_predefined']      = $this->registry->output->formTextarea("tag_predefined", IPSText::br2nl( !empty($_POST['tag_predefined']) ? $_POST['tag_predefined'] : $forum['tag_predefined'] ) );
		
		
		# Trim the form for categories...
		$form['addnew_type']			= $addnew_type;
		$this->request['type']          = $addnew_type;
		$form['addnew_type_upper']		= ucwords($addnew_type);

		//-----------------------------------------
		// Show permission matrix
		//-----------------------------------------
		
		if ( $type != 'edit' OR $addnew_type == 'category' )
		{
			/* Permission Class */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
		   	$permissions = new $classToLoad( ipsRegistry::instance() );
			
			if( $addnew_type == 'category' )
			{
				$perm_matrix = $permissions->adminPermMatrix( 'forum', $forum, 'forums', 'view' );
			}
			else
			{
		   		$perm_matrix = $permissions->adminPermMatrix( 'forum', $forum );
			}
		}
		
		/* Application Tabs */
		$form['tabStrip']	= '';
		$form['tabContent']	= '';
		
		$tabsUsed = 2;
		$firstTab = empty($this->request['_initTab']) ? false : trim($this->request['_initTab']);
		
		IPSLib::loadInterface( 'admin/forum_form.php' );
		
		foreach( IPSLib::getEnabledApplications() as $app_dir => $app_data )
		{
			if ( is_file( IPSLib::getAppDir( $app_dir ) . '/extensions/admin/forum_form.php' ) )
			{
				$_class = IPSLib::loadLibrary( IPSLib::getAppDir( $app_dir ) . '/extensions/admin/forum_form.php', 'admin_forum_form__'.$app_dir, $app_dir );
				
				if ( class_exists( $_class ) )
				{
					$_object = new $_class( $this->registry );
	
					$data = $_object->getDisplayContent( $forum, $tabsUsed );
					$form['tabContent']	.= $data['content'];
					$form['tabStrip']	.= $data['tabs'];
					
					$tabsUsed	= $data['tabsUsed'] ? ( $tabsUsed + $data['tabsUsed'] ) : ( $tabsUsed + 1 );
					
					if ( $this->request['_initTab'] == $app_dir )
					{
						$firstTab = $tabsUsed;
					}
				}
			}
		}
		
		//-----------------------------------------
		// Show form...
		//-----------------------------------------
		
		$this->registry->output->extra_nav[]	= array( '', $title );
		$this->registry->output->html .= $this->html->forumForm( $form, $button, $code, $title, $forum, $perm_matrix, $firstTab );
	}
	
	/**
	 * Save the forum
	 *
	 * @param	string		$type		[new|edit]
	 * @return	@e void
	 */	
	public function forumSave( $type='new' )
	{
		/* If this is not a redirect forum anymore empty the redirect url - #35126 */
		if ( $this->request['forum_type'] != 'redirect' )
		{
			$this->request['redirect_url'] = '';
		}
		
		//-----------------------------------------
		// Converting the type?
		//-----------------------------------------

		if( $this->request['convert'] )
		{
			$this->forumForm( $type, 1 );
			return;
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['name']		= trim( $this->request['name'] );
		$this->request['f']			= intval( $this->request['f'] );
		$this->request['parent_id']	= !empty($this->request['parent_id']) ? intval($this->request['parent_id']) : -1;
		$forum_cat_lang				= intval( $this->request['parent_id'] ) == -1 ? $this->lang->words['for_iscat_y'] : $this->lang->words['for_iscat_n'];
		
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------
		
		$this->registry->adminFunctions->checkSecurityKey();
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if( $this->request['name'] == "" )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['for_entertitle'], strtolower( $forum_cat_lang ) );
			$this->forumForm( $type );
			return;
		}
		
		//-----------------------------------------
		// Are we trying to do something stupid
		// like running with scissors or moving
		// the parent of a forum into itself
		// spot?
		//-----------------------------------------
		
		if( $this->request['parent_id'] != $this->registry->getClass('class_forums')->forum_by_id[ $this->request['f'] ]['parent_id'] )
		{
			$ids   = $this->registry->getClass('class_forums')->forumsGetChildren( $this->request['f'] );
			$ids[] = $this->request['f'];
			
			if ( in_array( $this->request['parent_id'], $ids ) )
			{
				$this->registry->output->global_error = $this->lang->words['for_whymovethere'];
				$this->forumForm( $type );
				return;
			}
		}
		
		//if( $this->request['parent_id'] < 1 )
		//{
		//	$this->request['sub_can_post'] = 1;
		//}
				
		//-----------------------------------------
		// Save array
		//-----------------------------------------

		$save = array(	'name'						=> IPSText::getTextClass('bbcode')->xssHtmlClean( nl2br( IPSText::stripslashes( $_POST['name'] ) ) ),
						'name_seo'					=> IPSText::makeSeoTitle( $this->request['name'] ),
						'description'				=> IPSText::getTextClass('bbcode')->xssHtmlClean( nl2br( IPSText::stripslashes( $_POST['description'] ) ) ),
						'use_ibc'					=> isset( $this->request['use_ibc'] ) ? intval($this->request['use_ibc']) : 1,
						'use_html'					=> intval($this->request['use_html']),
						'password'					=> $this->request['password'],
						'password_override'			=> is_array($this->request['password_override']) ? implode( ",", $this->request['password_override'] ) : '',
						'sort_key'					=> $this->request['sort_key'],
						'sort_order'				=> $this->request['sort_order'],
						'prune'						=> intval($this->request['prune']),
						'topicfilter'				=> $this->request['topicfilter'],
						'preview_posts'				=> intval($this->request['preview_posts']),
						'allow_poll'				=> intval($this->request['allow_poll']),
						'allow_pollbump'			=> intval($this->request['allow_pollbump']),
						'forum_allow_rating'		=> intval($this->request['forum_allow_rating']),
						'inc_postcount'				=> intval($this->request['inc_postcount']),
						'parent_id'					=> intval($this->request['parent_id']),
						'sub_can_post'				=> intval($this->request['sub_can_post']),
						'redirect_on'				=> intval($this->request['redirect_on']),
						'redirect_hits'				=> intval($this->request['redirect_hits']),
						'redirect_url'				=> $this->request['redirect_url'],
						'notify_modq_emails'		=> $this->request['notify_modq_emails'],
						'permission_showtopic'		=> $this->request['parent_id'] == -1 ? 1 : intval($this->request['permission_showtopic']),
						'min_posts_post'			=> intval( $this->request['min_posts_post'] ),
						'min_posts_view'			=> intval( $this->request['min_posts_view'] ),
						'can_view_others'			=> intval( $this->request['can_view_others'] ),
						'hide_last_info'			=> intval( $this->request['hide_last_info'] ),
						'disable_sharelinks'		=> intval( $this->request['disable_sharelinks'] ),
						'tag_predefined'			=> $this->request['tag_predefined'],
					    'forums_bitoptions'			=> IPSBWOPtions::freeze( $this->request, 'forums', 'forums' ),
						'permission_custom_error'	=> nl2br( IPSText::stripslashes($_POST['permission_custom_error']) ) );

		/* Save data from application tabs */
		IPSLib::loadInterface( 'admin/forum_form.php' );
		
		$_forumPlugins = array();
		
		foreach( IPSLib::getEnabledApplications() as $app_dir => $app_data )
		{
			if ( is_file( IPSLib::getAppDir( $app_dir  ) . '/extensions/admin/forum_form.php' ) )
			{
				$_class  = IPSLib::loadLibrary( IPSLib::getAppDir( $app_dir ) . '/extensions/admin/forum_form.php', 'admin_forum_form__'.$app_dir, $app_dir );
				$_forumPlugins[ $_class ] = new $_class( $this->registry );
				
				$remote  = $_forumPlugins[ $_class ]->getForSave();
				
				$save    = array_merge( $save, $remote );
			}
		}

		//-----------------------------------------
		// ADD
		//-----------------------------------------
		
		if ( $type == 'new' )
		{
			 $this->DB->build( array( 'select' => 'MAX(id) as top_forum', 'from' => 'forums' ) );
			 $this->DB->execute();
			 
			 $row = $this->DB->fetch();
			 
			 if ( $row['top_forum'] < 1 )
			 {
			 	$row['top_forum'] = 0;
			 }
			 
			 $row['top_forum']++;

			/* Forum Information */
			//$save['id']               = $row['top_forum'];
			$save['position']         = $row['top_forum'];
			$save['topics']           = 0;
			$save['posts']            = 0;
			$save['last_post']        = 0;
			$save['last_poster_id']   = 0;
			$save['last_poster_name'] = "";
			
			/* Insert the record */
			$this->DB->insert( 'forums', $save );
			$forum_id = $this->DB->getInsertId();
			
			/* Permissions */
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
			$permissions = new $classToLoad( ipsRegistry::instance() );
			$permissions->savePermMatrix( $this->request['perms'], $forum_id, 'forum' );
			
			if( !$save['can_view_others'] )
			{
				$this->DB->update( 'permission_index', array( 'owner_only' => 1 ), "app='forums' AND perm_type='forum' AND perm_type_id={$forum_id}" );
			}
			
			/* Done */
			$this->registry->output->global_message = $forum_cat_lang . $this->lang->words['for__created'];			
			$this->registry->adminFunctions->saveAdminLog( $forum_cat_lang . " '" . $this->request['name'] . "'" . strtolower ( $this->lang->words['for__created'] ) );
		}
		else
		{
			$forumData = $this->registry->class_forums->getForumById( $this->request['f'] );
			
			if ( $this->request['parent_id'] == -1 )
			{
				$save['can_view_others'] = 1;
				
				/* Permissions */
				//$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
				//$permissions = new $classToLoad( ipsRegistry::instance() );
				//$permissions->savePermMatrix( $this->request['perms'], $this->request['f'], 'forum' );
				
				if( ! $save['can_view_others'] )
				{
					$this->DB->update( 'permission_index', array( 'owner_only' => 1 ), "app='forums' AND perm_type='forum' AND perm_type_id={$this->request['f']}" );
				}
				else
				{
					$this->DB->update( 'permission_index', array( 'owner_only' => 0 ), "app='forums' AND perm_type='forum' AND perm_type_id={$this->request['f']}" );
				}
			}

			$this->DB->update( 'forums', $save, "id=" . $this->request['f'] );
			$forum_id = $this->request['f'];
			
			/* Tags */
			$this->registry->getClass('class_forums')->forumsInit();
			$this->registry->tags->updatePermssionsByParentId( $this->request['f'] );
			
			/* Did we enable/disable tagging? @todo find a better way to do this. Perhaps another field in tags? */
			if ( isset( $this->request['bw_disable_tagging'] ) )
			{
				if ( $forumData['bw_disable_tagging'] != $this->request['bw_disable_tagging'] )
				{
					$toggle = ( $this->request['bw_disable_tagging'] ) ? 0 : 1;
					
					$this->registry->tags->updateVisibilityByParentId( $this->request['f'], $toggle );
					
					if ( $this->request['bw_disable_tagging'] == 0 )
					{
						/* We just restored all tags, so lets return hidden topics back to 0 */
						$this->DB->build( array( 'select' => 'tid',
												 'from'   => 'topics',
												 'where'  => 'forum_id=' . $this->request['f'] . ' AND ' . $this->registry->class_forums->fetchTopicHiddenQuery( array( 'sdeleted', 'hidden', 'pdelete', 'oktoremove' ) ),
												 'order'  => 'last_post DESC',
												 'limit'  => array( 0, 500 ) ) );
						$this->DB->execute();
						
						$topics = array();
						while( $row = $this->DB->fetch() )
						{
							$topics[] = $row['tid'];
						}
						
						if ( count( $topics ) )
						{
							$this->registry->tags->updateVisibilityByMetaId( $topics, 0 );
						}
					}
				}
			}
			
			$this->registry->output->global_message = $forum_cat_lang.$this->lang->words['for__edited'];
			
			$this->registry->adminFunctions->saveAdminLog( $forum_cat_lang." '" . $this->request['name'] . "' " . strtolower ( $this->lang->words['for__edited'] ) );
		}
		
		$this->request['f'] = '';
		if( $save['parent_id'] > 0 )
		{
			$this->request['f'] = $save['parent_id'];
		}
		
		//-----------------------------------------
		// Post save callbacks
		//-----------------------------------------
		
		if( count($_forumPlugins) )
		{
			foreach( $_forumPlugins as $_className => $_object )
			{
				if( method_exists( $_object, 'postSave' ) )
				{
					$_object->postSave( $forum_id );
				}
			}
		}
		
		$this->registry->getClass('class_forums')->forumsInit();
		
		$this->showForums();
	}
	
	/**
	 * Show the form to edit permissions
	 *
	 * @return	@e void		Outputs to screen
	 */	
	public function permEditForm()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['f'] = intval( $this->request['f'] );
		
		//-----------------------------------------
		// check..
		//-----------------------------------------
		
		if ( ! $this->request['f'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 11316 );
		}
		
		//-----------------------------------------
		// Get this forum details
		//-----------------------------------------
		
		$forum = $this->registry->getClass('class_forums')->forum_by_id[ $this->request['f'] ];
		
		if( $this->request['copyForumPerms'] )
		{
			$forumToCopy	= $this->registry->getClass('class_forums')->forum_by_id[ $this->request['copyForumPerms'] ];
			
			foreach( array( 'perm_view', 'perm_2', 'perm_3', 'perm_4', 'perm_5', 'perm_6' ) as $field )
			{
				$forum[ $field ] = $forumToCopy[ $field ];
			}
		}

		//-----------------------------------------
		// Next id...
		//-----------------------------------------
		
		$relative = $this->getNextId( $this->request['f'] );
		
		//-----------------------------------------
		// check...
		//-----------------------------------------
		
		if ( ! $forum['id'] )
		{
			$this->registry->output->showError( $this->lang->words['for_noid'], 11317 );
		}
		
		//-----------------------------------------
		// HTML
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
		$permissions = new $classToLoad( ipsRegistry::instance() );

		if( $forum['parent_id'] != 'root' )
		{
			$perm_matrix = $permissions->adminPermMatrix( 'forum', $forum );
		}
		else
		{
			$perm_matrix = $permissions->adminPermMatrix( 'forum', $forum, 'forums', 'view' );			
		}
		
		$forumCopyDropdown = $this->registry->output->formDropdown( 'copyForumPerms', $this->registry->getClass('class_forums')->adForumsForumList( TRUE, FALSE, array( $forum['id'] ) ) );

		$this->registry->output->html .= $this->html->forumPermissionForm( $forum, $relative, $perm_matrix, $forum, $forumCopyDropdown );
	}
	
	/**
	 * Get the id of the next forum
	 *
	 * @param	integer		Last forum id
	 * @return	@e void		Outputs to screen
	 */	
	public function getNextId($fid)
	{
		$nextid = 0;
		$ids    = array();
		$index  = 0;
		$count  = 0;
		
		foreach( $this->registry->getClass('class_forums')->forum_cache['root'] as $forum_data )
		{
			$ids[ $count ] = $forum_data['id'];
			
			if ( $forum_data['id'] == $fid )
			{
				$index = $count;
			}
			
			$count++;
			
			if ( isset($this->registry->getClass('class_forums')->forum_cache[ $forum_data['id'] ]) AND is_array( $this->registry->getClass('class_forums')->forum_cache[ $forum_data['id'] ] ) )
			{
				foreach( $this->registry->getClass('class_forums')->forum_cache[ $forum_data['id'] ] as $forum_data )
				{
					$children = $this->registry->getClass('class_forums')->forumsGetChildren( $forum_data['id'] );
					
					$ids[ $count ] = $forum_data['id'];
			
					if ( $forum_data['id'] == $fid )
					{
						$index = $count;
					}
					
					$count++;
					
					if ( is_array($children) and count($children) )
					{
						foreach( $children as $kid )
						{
							$ids[ $count ] = $kid;
			
							if ( $kid == $fid )
							{
								$index = $count;
							}
							
							$count++;
						}
					}
				}
			}
		}
	
		return array( 'next' => $ids[ $index + 1 ], 'previous' => $ids[ $index - 1 ] );
	}

	/**
	 * Save the permissions
	 *
	 * @return	@e void		Outputs to screen
	 */	
	public function permDoEdit()
	{
		/* INI */
		$perms = array();
		$this->request['f'] = intval( $this->request['f'] );		
		
		/* Security Check */
		$this->registry->adminFunctions->checkSecurityKey();
		
		/* Save the permissions */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
		$permissions = new $classToLoad( ipsRegistry::instance() );
		$permissions->savePermMatrix( $this->request['perms'], $this->request['f'], 'forum' );
		
		/* Log */
		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['for_permeditedin'], $this->request['name'] ) );

		/* Previous Forum */
		if ( $this->request['doprevious'] AND $this->request['doprevious'] and $this->request['previd'] > 0 )
		{
			$this->registry->output->global_message = $this->lang->words['for_permedited'];
			
			$this->request['f'] = $this->request['previd'];
			
			$this->registry->output->silentRedirect( "{$this->settings['base_url']}{$this->html->form_code}do=pedit&f=" . $this->request['f'] );
		}
		/* Next Forum */
		else if ( $this->request['donext'] AND $this->request['donext'] and $this->request['nextid'] > 0 )
		{
			$this->registry->output->global_message = $this->lang->words['for_permedited'];
			
			$this->request['f'] = $this->request['nextid'];
			
			$this->registry->output->silentRedirect( "{$this->settings['base_url']}{$this->html->form_code}do=pedit&f=" . $this->request['f'] );
		}
		/* Reload */
		else if ( $this->request['reload'] AND $this->request['reload'] )
		{
			$this->registry->output->silentRedirect( "{$this->settings['base_url']}{$this->html->form_code}do=pedit&f=" . $this->request['f'] );
		}
		/* Done */
		else
		{
			$this->registry->output->global_message	= $this->lang->words['for_permedited2'];
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
		}
	}
	
	/**
	 * Reorder the child forums
	 *
	 * @return	@e void		Outputs to screen
	 */	
	public function doReorder()
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
 		
 		if( is_array($this->request['forums']) AND count($this->request['forums']) )
 		{
 			foreach( $this->request['forums'] as $this_id )
 			{
 				$this->DB->update( 'forums', array( 'position' => $position ), 'id=' . $this_id );
 				
 				$position++;
 			}
 		}

 		$ajax->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * List the forums
	 *
	 * @return	@e void		Outputs to screen
	 */	
	public function showForums()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['f'] = intval( $this->request['f'] );

		//-----------------------------------------
		// Grab the moderators
		//-----------------------------------------
		
		$this->registry->getClass('class_forums')->moderators	= array();
		$this->registry->getClass('class_forums')->type			= 'manage';
		
		$this->DB->build( array( 
								'select'	=> 'm.*', 
								'from'		=> array( 'moderators' => 'm' ),
								'add_join'	=> array(
													array( 
															'select' => 'mm.members_display_name',
															'from'	 => array( 'members' => 'mm' ),
															'where'	 => 'mm.member_id=m.member_id AND m.is_group=0',
															'type'	 => 'left'
														)
													)
								) 		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$this->registry->getClass('class_forums')->moderators[] = $r;
		}
		
		//-----------------------------------------
		// Print screen
		//-----------------------------------------

		$this->registry->output->html .= $this->html->renderForumHeader();
		
		$this->registry->getClass('class_forums')->forumsListForums();

		//-----------------------------------------
		// Add footer
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->renderForumFooter();
	}	
}