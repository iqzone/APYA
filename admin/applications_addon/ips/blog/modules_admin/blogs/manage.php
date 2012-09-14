<?php
/**
 * @file		manage.php 	Blog management methods
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
 * @class		admin_blog_blogs_manage
 * @brief		Blog management methods
 */
class admin_blog_blogs_manage extends ipsCommand
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
		/* Load Skin Template */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_blog', 'blog' );
		
		/* Load Language */
		$this->lang->loadLanguageFile( array( 'public_blog', 'admin_blog' ), 'blog' );
		
		/* URL Bits */
		$this->html->form_code    = $this->form_code    = 'module=blogs&amp;section=manage&amp;';
		$this->html->form_code_js = $this->form_code_js = 'module=blogs&section=manage&';
				
		switch( $this->request['do'] )
		{
			case 'searchblog':
				$this->searchBlog();
			break;
			
			case 'deleteGroupBlog':
			case 'do_deleteblog':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_delete' );
				$this->doDeleteBlog();
			break;
			
			case 'editblog':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_manage' );
				$this->editBlog();
			break;
			
			case 'doeditblog':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_manage' );
				$this->doEditBlog();
			break;
			
			case 'addGroupBlog':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_manage' );
				$this->groupBlogForm('add');
			break;
			
			case 'doAddGroup':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_manage' );
				$this->groupBlogSave('add');
			break;
			
			case 'editGroupBlog':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_manage' );
				$this->groupBlogForm('edit');
			break;
			
			case 'doEditGroup':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'blogs_manage' );
				$this->groupBlogSave('edit');
			break;
			
			case 'addmodone':
				$this->addModOne();
			break;
			
			case 'addmodtwo':
				$this->addModTwo();
			break;
			
			case 'addmodfinal':
				$this->modForm( 'add' );
			break;
			
			case 'doaddmod':
				$this->doAddMod();
			break;
			
			case 'editmod':
				$this->modForm( 'edit' );
			break;
			
			case 'doeditmod':
				$this->doEditMod();
			break;
			
			case 'dodelmod':
				$this->doDelMod();
			break;
			
			default:
				$this->blogsIndex();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Blogs Index Screen
	 *
	 * @return	@e void
	 * @todo	[Future] Add a list of all blogs here, maybe
	 */
	public function blogsIndex()
	{
		/* Init vars */
		$moderators = array();
		
		
		if ( ! count( $this->caches['blog_gblogs'] ) )
		{
			$this->recacheGroupBlogs();
		}
		
		$blogs = ( count( $this->caches['blog_gblogs'] ) ) ? $this->registry->getClass('blogFunctions')->loadBlog( array_keys( $this->caches['blog_gblogs'] ) ) : array();
		
		/* Query Moderators */
		$this->DB->build( array( 
								'select'   => "md.*",
								'from'	   => array( 'blog_moderators' => 'md' ),
								'add_join' => array( array( 'select' => 'm.name, m.members_display_name',
															'from'	 => array( 'members' => 'm' ),
															'where'	 => "md.moderate_mg_id=m.member_id and md.moderate_type='member'",
															'type'	 => 'left' ),
													 array( 'select' => 'g.g_title',
															'from'	 => array( 'groups' => 'g' ),
															'where'	 => "md.moderate_mg_id=g.g_id and md.moderate_type='group'",
															'type'	 => 'left' ) ) ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$moderators[] = $row;
		}

		$this->registry->output->html .= $this->html->blogManager( $blogs, $moderators );
	}
	
	/**
	 * Save a group blog
	 *
	 * @param	string		$type		Type (add/edit)
	 * @return	@e void
	 */
    public function groupBlogSave( $type='add' )
    {
    	/* Check ID */
		$blog_id             = intval( $this->request['blogid'] );
		$blog_name           = trim( $this->request['blog_name'] );
		$blog_desc           = trim( $this->request['blog_desc'] );
		$blog_groupblog_name = trim( $this->request['blog_groupblog_name'] );
		$blog_group_ids	     = IPSLib::cleanIntArray( $this->request['blog_group_ids'] );
		$memberGroupsRecache = array();
		
		/* Checks */
		if ( ! $blog_name OR ! $blog_groupblog_name OR ! count( $blog_group_ids ) )
		{
			$this->registry->output->global_error = $this->lang->words['blog_complete_fully'];
			return $this->groupBlogForm( $type );
		}
		
		if ( $type == 'edit' )
		{
			if ( empty($blog_id) )
			{
	            $this->registry->output->global_error = $this->lang->words['bm_wronguse'];
	            return $this->groupBlogForm( $type );
			}
			
			/* Get the blog */
			$blog   = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_blogs', 'where' => "blog_id={$blog_id}" ) );
			
			if ( empty($blog['blog_id']) )
			{
				$this->registry->output->global_error = $this->lang->words['bm_wronguse'];
	            return $this->groupBlogForm( $type );
	        }
	        
	        $oldGroupIds = IPSText::cleanPermString( $blog['blog_groupblog_ids'] );
	        
	        $memberGroupsRecache = array_diff( explode( ',', $oldGroupIds ), $blog_group_ids );
	        $memberGroupsRecache = array_merge( $memberGroupsRecache, array_diff( $blog_group_ids, explode( ',', $oldGroupIds ) ) );
	        
	        /* Update */
	        $this->DB->update( 'blog_blogs', array( 'blog_name'           => $blog_name,
	        										'blog_desc'           => $blog_desc,
	        										'blog_groupblog_ids'  => ',' . implode( ',', $blog_group_ids ) . ',',
	        										'blog_groupblog_name' => $blog_groupblog_name ), 'blog_id=' . $blog_id );
		}
		else
		{
			$memberGroupsRecache = $blog_group_ids;
			
			/* Create */
			$blog = array( 'blog_name'           => $blog_name,
						   'blog_desc'           => $blog_desc,
						   'blog_type'           => 'local',
						   'blog_groupblog_ids'  => ',' . implode( ',', $blog_group_ids ) . ',',
						   'blog_groupblog_name' => $blog_groupblog_name );
						   
			/* Create the blog, then! */
			try
			{
				$blog = $this->registry->blogFunctions->createBlog( $blog );
			}
			catch( Exception $error )
			{
				$this->registry->output->global_error = isset($this->lang->words[ $error->getMessage() ]) ? $this->lang->words[ $error->getMessage() ] : $error->getMessage();
				return $this->groupBlogForm( $type );
			}
		}
		
		/* Update Cache */
		$this->recacheGroupBlogs();
		
		/* reset membahs */
		$this->registry->getClass('blogFunctions')->flagMyBlogsRecache( $memberGroupsRecache, 'group', ( $type == 'edit' ? false : true ) );
		
		/* Redirect */
		$this->registry->output->global_message = $this->lang->words['bm_groupblogsave'];
		$this->registry->output->silentRedirectWithMessage( "{$this->settings['base_url']}{$this->html->form_code}do=index" );
	}
	
	/**
	 * Edit a Blog
	 *
	 * @param	string		$type		Type (add/edit)
	 * @return	@e void
	 */
    public function groupBlogForm( $type='add' )
    {
    	/* Check ID */
		$blog_id = intval( $this->request['blogid'] );
		
		if ( $type == 'edit' )
		{
			if ( ! $blog_id )
			{
	            $this->registry->output->showError( $this->lang->words['bm_wronguse'], 1164 );
			}
			
			/* Get the blog */
			$blog   = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_blogs', 'where' => "blog_id={$blog_id}" ) );
			$code   = 'doEditGroup';
			$button = $this->lang->words['blog_edit_group_blog'];
		}
		else
		{
			$blog   = array();
			$code   = 'doAddGroup';
			$button = $this->lang->words['blog_add_group_blog'];
		}
				
		/* Output */
		$this->registry->output->html .= $this->html->blogGroupForm( $code, $button, $blog );
	}

	/**
	 * Recache group blogs cache
	 *
	 * @return	@e void
	 */
	 public function recacheGroupBlogs()
	 {
	 	$blogs = array();
	 	
	 	$this->DB->build( array( 'select'   => "b.blog_name, b.blog_seo_name, b.blog_id, b.member_id as blog_owner_id, b.blog_groupblog_name, blog_groupblog_ids",
								 'from'	    => array( 'blog_blogs' => 'b' ),
								 'where'	=> 'b.blog_disabled=0 AND b.blog_groupblog_ids != \'\'',
								 'add_join' => array( array( 'select' => 'm.member_id, m.member_group_id, m.mgroup_others',
								 							 'from'   => array( 'members' => 'm' ),
								 							 'where'  => 'b.member_id=m.member_id',
								 							 'type'   => 'left' ) ) ) );
																
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$row['_groupIds'] 		  = explode( ',', IPSText::cleanPermString( $row['blog_groupblog_ids'] ) );
			$blogs[ $row['blog_id'] ] = $row;
		}
		
		$this->cache->setCache( 'blog_gblogs', $blogs, array( 'array' => 1 ) );	
	 }
	 
	/**
	 * Search a Blog for edit
	 *
	 * @return	@e void
	 */
	public function searchBlog()
    {
    	/* Search by blog name */
		if( $this->request['blogname'] != '' )
		{
			$this->request['blogname'] = trim( $this->request['blogname'] );
			$searchsql                 = "b.blog_name like '%{$this->request['blogname']}%'";
		}
		/* Search by user name */
		elseif( $this->request['membername'] != '' )
		{
			$this->request['membername'] = trim( $this->request['membername'] );
			$searchsql                   = "m.name like '%{$this->request['membername']}%'";
		}
		/* Search by display name */
		elseif( $this->request['memberdisplayname'] != '' )
		{
			$this->request['memberdisplayname'] = trim( $this->request['memberdisplayname'] );
			$searchsql                          = "m.members_display_name like '%{$this->request['memberdisplayname']}%'";
		}
		/* Search by member ID */
		elseif( $this->request['member_id'] != '' )
		{
			$this->request['member_id'] = intval( $this->request['member_id'] );
			$searchsql                  = "b.blog_member_id=" . $this->request['member_id'] . " AND b.blog_groupblog=0";
		}
		/* Error */
		else
		{
            $this->registry->output->showError( $this->lang->words['bm_wronguse'], 1160 );
		}
		
		/* Count results, for pagination */
		$count = $this->DB->buildAndFetch( array( 
													'select'   => "count(*) as blog_count",
													'from'     => array( 'blog_blogs' => 'b' ),
													'add_join' => array( 
																		array( 
																				'from'  => array( 'members' => 'm' ),
																				'where' => "b.member_id=m.member_id",
																				'type'  => 'left'
																			)
																		),
													'where'	   => "{$searchsql}",
										)	);

		if( $count['blog_count'] < 1 )
		{
            $this->registry->output->showError( $this->lang->words['bm_noblogs'], 1161 );
		}

		/* Pagination Links */
		$pages = $this->registry->output->generatePagination( array( 
																	'totalItems'        => $count['blog_count'],
																	'itemsPerPage'      => 25,
																	'currentStartValue' => $this->request['st'],
																	'baseUrl'           => "{$this->ad_blog->base_url}req=searchblog&membername={$this->request['membername']}&blogname={$this->request['blogname']}",
											 				)	);

		/* Run the search */
		$this->DB->build( array( 
									'select'   => 'b.*',
									'from'     => array( 'blog_blogs' => 'b' ),
									'add_join' => array(
														array( 
																'select' => 'li.blog_num_entries',
																'from'   => array( 'blog_lastinfo' => 'li' ),
																'where'  => "b.blog_id=li.blog_id",
																'type'   => 'left'
															),
														array( 
																'select' => 'm.members_display_name, m.ip_address, m.email, m.member_group_id',
																'from'   => array( 'members' => 'm' ),
																'where'  => "b.member_id=m.member_id",
																'type'   => 'left'
															),
														array( 
																'select' => 'pp.*',
																'from'   => array( 'profile_portal' => 'pp' ),
																'where'  => "m.member_id=pp.pp_member_id",
																'type'   => 'left'
															)
														),
									'where'	   => "{$searchsql}",
									'limit'	   => array( ( $this->request['st'] ? $this->request['st'] : 0 ), 25 )
						     )	);
		$this->DB->execute();
		
		/* Loop through and build output rows */
		$output_rows = array();
		
		while( $r = $this->DB->fetch() )
		{
			$output_rows[] = $r;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->blogSearchResults( $output_rows, $count['blog_count'], $pages );
	}

	/**
	 * Delete a blog from the database
	 *
	 * @return	@e void
	 */
    public function doDeleteBlog()
    {
    	/* Check ID */
		$blog_id = intval( $this->request['blogid'] );
		
		if( ! $blog_id )
		{
            $this->registry->output->showError( $this->lang->words['bm_wronguse'], 1162 );
		}
		
		/* Get the blog */
		$blog = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_blogs', 'where' => "blog_id={$blog_id}" ) );
		
		if( ! $blog['blog_id'] )
		{
            $this->registry->output->showError( sprintf( $this->lang->words['bm_idnotexist'], $blog_id), 1163 );
		}
		
		/* Delete it */
		$this->registry->getClass('blogFunctions')->removeBlog( $blog_id );
				
		/* Done */
        $this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['bm_deletebloglog'], $blog_id) );
        
        $this->registry->output->global_message = $this->lang->words['bm_deletedblog'];
		$this->registry->output->silentRedirectWithMessage( "{$this->settings['base_url']}{$this->html->form_code}do=index" );
    }

	/**
	 * Edit a Blog
	 *
	 * @param	array		$errors		Errors data
	 * @return	@e void
	 */
    public function editBlog( $errors=array() )
    {
    	/* Check ID */
		$blog_id = intval( $this->request['blogid'] );
		
		if( ! $blog_id )
		{
            $this->registry->output->showError( $this->lang->words['bm_wronguse'], 1164 );
		}
		
		/* Get the blog */
		$blog = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_blogs', 'where' => "blog_id={$blog_id}" ) );

		/* Cleanup input data */
		foreach( array( 'blog_name', 'blog_desc', 'blog_type', 'blog_exturl' ) as $bit )
		{
			if( isset( $this->request[ $bit ] ) )
			{
				$blog[ $bit ] = trim( $this->request[ $bit ] );
			}
		}
		
		/* Form Elements */
		$form['blog_name']		= $this->registry->output->formInput( 'blog_name', $blog['blog_name'] );
		$form['blog_desc']		= $this->registry->output->formInput( 'blog_desc', $blog['blog_desc'] );
		$form['blog_type']		= $this->registry->output->formDropdown( 'blog_type', array( array( 'local', $this->lang->words['bucp_btype_public'] ), array( 'external', $this->lang->words['bucp_btype_external'] ) ), $blog['blog_type'] );
		$form['blog_exturl']	= $this->registry->output->formInput( 'blog_exturl', $blog['blog_exturl'] );
		
		/* Output */
		$this->registry->output->html .= $this->html->blogEditForm( $blog_id, $form, $errors, $blog );
	}

	/**
	 * Save changes to Blog
	 *
	 * @return	@e void
	 */
    public function doEditBlog()
    {
   		/* Blog library */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
		$blogfunc    = new $classToLoad( $this->registry );
		
		/* Errors */
		$errors = array();
		
		/* Check ID */
		$blog_id = intval( $this->request['blogid'] );
				
		if ( empty($blog_id) )
		{
            $this->registry->output->showError( $this->lang->words['bm_wronguse'], 1165.1 );
		}

		$blog = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_blogs', 'where' => "blog_id={$blog_id}" ) );
		
		if ( empty($blog['blog_id']) )
		{
            $this->registry->output->showError( $this->lang->words['bm_wronguse'], 1165.2 );
		}		
		
		if ( empty($this->request['blog_name']) )
		{
			$errors[] = $this->lang->words['bl_mustenterbname'];
		}
		
		/* Found errors? */
		if ( count( $errors ) )
		{
			$this->editBlog( $errors );
			return;
		}
		
		$update = array('blog_name'		=> $this->request['blog_name'],
						'blog_seo_name'	=> IPSText::makeSeoTitle( $this->request['blog_name'] ),
						'blog_desc'		=> $this->request['blog_desc'],
						'blog_type'		=> $this->request['blog_type'],
						'blog_exturl'	=> $this->request['blog_exturl'],
						);
		
		/* Build Settings */
		$blog['blog_settings'] = unserialize( $blog['blog_settings'] );
		$update['blog_settings'] = serialize( $blog['blog_settings'] );

		/* Let's update the BLOG record */
		$this->DB->update( 'blog_blogs', $update, "blog_id={$blog['blog_id']}" );
		
		/* All done */
 		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['blog_edited_admin_log'], $blog['blog_name'], $blog['blog_id'] ) );
 		
		$this->registry->output->global_message	= sprintf( $this->lang->words['bm_editedblog'], $blog['blog_name'] );
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Add a moderator (step one)
	 *
	 * @return	@e void
	 */
	public function addModOne()
	{
		/* Moderator: Member */
		if ($this->request['mod_type'] == 'member')
		{
			$this->registry->output->html .= $this->html->moderatorMemberAddOne();
		}
		/* Moderator: groups */
		else
		{
			$mem_group = array();

			$this->DB->build( array( 'select' => 'g_id, g_title', 'from' => 'groups', 'order' => "g_title" ) );
			$this->DB->execute();

			while ( $r = $this->DB->fetch() )
			{
				$mem_group[] = array( $r['g_id'] , $r['g_title'] );
			}

			$this->registry->output->html .= $this->html->moderatorGroupAddOne( $this->registry->output->formDropdown( "mod_group", $mem_group ) );
		}
	}

	/**
	 * Add a moderator (step two)
	 *
	 * @return	@e void
	 */
	public function addModTwo()
	{
		/* Are we adding a group as a mod? If so, bounce straight to the mod perms form */
		if( $this->request['mod_type'] == 'group' )
		{
			$this->modForm( 'add' );
			return;
		}

		/* Else continue as normal. */
		if( $this->request['USER_NAME'] == '' )
		{
			$this->registry->output->showError( $this->lang->words['gm_nomember'], 11652 );
		}
		
		/* Find matching members */
		$this->DB->build( array( 'select' => 'member_id, name, members_display_name', 'from' => 'members', 'where' => "name LIKE '".$this->request['USER_NAME']."%' OR members_display_name LIKE '".$this->request['USER_NAME']."%'" ) );
		$this->DB->execute();

		if( ! $this->DB->getTotalRows() )
		{
			$this->registry->output->showError( $this->lang->words['gm_nomemberlocate'], 11653 );
		}
		
		/* Build a list of members */
		$form_array = array();

		while ( $r = $this->DB->fetch() )
		{
			$form_array[] = array( $r['member_id'] , $r['members_display_name'] );
		}

		/* Output */
		$this->registry->output->html .= $this->html->moderatorChooseMember( $this->registry->output->formDropdown( "MEMBER_ID", $form_array ) );
	}

	/**
	 * Moderator form
	 *
	 * @param	string		$type		Form type (add|edit)
	 * @return	@e void
	 */
	public function modForm( $type='add' )
	{
		/* INI */
		$group = array();
		$mod   = array();
		
		/* Add Moderator */
		if( $type == 'add' )
		{
			/* Form Bits */
			$button	   = "Add this moderator";
			$form_code = 'doaddmod';
			
			/* Group */
			if( $this->request['mod_type'] == 'group' )
			{
				/* Query the group */
				$this->DB->build( array( 'select' => 'g_id, g_title', 'from' => 'groups', 'where' => "g_id='" . $this->request['mod_group'] . "'" ) );
				$this->DB->execute();
				
				/* Check the group */
				if( ! $group = $this->DB->fetch() )
				{
					$this->registry->output->showError( $this->lang->words['gm_nogrouplocate'], 11654 );
				}

				/* ID */
				$mg_id = $group['g_id'];
			}
			/* Member */
			else
			{
				/* Check fo ran ID */
				if( $this->request['MEMBER_ID'] == "" )
				{
					$this->registry->output->showError( $this->lang->words['gm_noidresolve'], 11655 );
				}
				else
				{
					/* Query the member */
					$this->DB->build( array( 'select' => 'name, member_id, members_display_name', 'from' => 'members', 'where' => "member_id=".intval($this->request['MEMBER_ID']) ) );
					$this->DB->execute();
					
					/* Chceck the member */
					if( ! $mem = $this->DB->fetch() )
					{
						$this->registry->output->showError( $this->lang->words['gm_noidresolve'], 11656 );
					}
				}

				/* ID */
				$mg_id = $mem['member_id'];
			}

		}
		/* Edit Moderator */
		else
		{
			/* Check the id */
			if( $this->request['modid'] == "" )
			{
				$this->registry->output->showError( $this->lang->words['gm_validmod'], 11657 );
			}
			
			/* Form Bits */
			$button	   = "Edit this moderator";
			$form_code = "doeditmod";

			/* Query the moderator info */
			$this->DB->build( array( 'select' => '*', 'from' => 'blog_moderators', 'where' => "moderate_id=" . intval( $this->request['modid'] ) ) );
			$this->DB->execute();
			
			/* Check the info */
			if( ! $mod = $this->DB->fetch() )
			{
				$this->registry->output->showError( $this->lang->words['gm_nomodlocate'], 11658 );
			}
			
			/* ID */
			$mg_id = $mod['moderate_gm_id'];
		}

		/* Form Elements */
		$form = array();
		
		$form['moderate_can_edit_comments'] = $this->registry->output->formYesNo( 'moderate_can_edit_comments', $mod['moderate_can_edit_comments'] );
		$form['moderate_can_edit_entries']	= $this->registry->output->formYesNo( 'moderate_can_edit_entries' , $mod['moderate_can_edit_entries'] );
		$form['moderate_can_del_comments']	= $this->registry->output->formYesNo( 'moderate_can_del_comments' , $mod['moderate_can_del_comments'] );
		$form['moderate_can_del_entries']	= $this->registry->output->formYesNo( 'moderate_can_del_entries'  , $mod['moderate_can_del_entries'] );
		$form['moderate_can_publish']		= $this->registry->output->formYesNo( 'moderate_can_publish'	  , $mod['moderate_can_publish'] );
		$form['moderate_can_lock']			= $this->registry->output->formYesNo( 'moderate_can_lock'		  , $mod['moderate_can_lock'] );
		$form['moderate_can_approve']		= $this->registry->output->formYesNo( 'moderate_can_approve'	  , $mod['moderate_can_approve'] );
		$form['moderate_can_del_trackback'] = $this->registry->output->formYesNo( 'moderate_can_del_trackback', $mod['moderate_can_del_trackback'] );
		$form['moderate_can_view_draft']	= $this->registry->output->formYesNo( 'moderate_can_view_draft'	  , $mod['moderate_can_view_draft'] );
		$form['moderate_can_view_private']	= $this->registry->output->formYesNo( 'moderate_can_view_private' , $mod['moderate_can_view_private'] );
		$form['moderate_can_warn']			= $this->registry->output->formYesNo( 'moderate_can_warn'		  , $mod['moderate_can_warn'] );
		$form['moderator_can_feature']		= $this->registry->output->formYesNo( 'moderator_can_feature'	  , $mod['moderator_can_feature'] );
		$form['moderate_can_pin']			= $this->registry->output->formYesNo( 'moderate_can_pin'		  , $mod['moderate_can_pin'] );
		$form['moderate_can_disable']		= $this->registry->output->formYesNo( 'moderate_can_disable'	  , $mod['moderate_can_disable'] );
		$form['moderate_can_editcblocks']	= $this->registry->output->formYesNo( 'moderate_can_editcblocks'  , $mod['moderate_can_editcblocks'] );

		/* Output */
		$this->registry->output->html .= $this->html->moderatorForm( $mod['moderate_id'], $mg_id, $form_code, $button, $form );
	}

	/**
	 * Do Add Moderator
	 *
	 * @return	@e void
	 */
	public function doAddMod()
	{
		/* Build Mr. Hash */
		$mr_hash = array(
							'moderate_type'					=> $this->request['mod_type'],
							'moderate_mg_id'				=> $this->request['mgid'],
							'moderate_can_edit_comments'	=> $this->request['moderate_can_edit_comments'],
							'moderate_can_edit_entries'		=> $this->request['moderate_can_edit_entries'],
							'moderate_can_del_comments'		=> $this->request['moderate_can_del_comments'],
							'moderate_can_del_entries'		=> $this->request['moderate_can_del_entries'],
							'moderate_can_publish'			=> $this->request['moderate_can_publish'],
							'moderate_can_lock'				=> $this->request['moderate_can_lock'],
							'moderate_can_approve'			=> $this->request['moderate_can_approve'],
							'moderate_can_editcblocks'		=> $this->request['moderate_can_editcblocks'],
							'moderate_can_del_trackback'	=> $this->request['moderate_can_del_trackback'],
							'moderate_can_view_draft'		=> $this->request['moderate_can_view_draft'],
							'moderate_can_view_private'		=> $this->request['moderate_can_view_private'],
							'moderate_can_warn'				=> $this->request['moderate_can_warn'],
							'moderate_can_pin'				=> $this->request['moderate_can_pin'],
							'moderate_can_disable'			=> $this->request['moderate_can_disable'],
							'moderator_can_feature'			=> $this->request['moderator_can_feature'],
						);

		/* Check for the id */
		if( $this->request['mgid'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['gm_noidresolve'], 11659 );
		}

		/* Already using this group on this forum? */
		$this->DB->build( array( 'select' => '*', 'from' => 'blog_moderators', 'where' => "moderate_type='{$this->request['mod_type']}' and moderate_mg_id=".intval($this->request['mgid']) ) );
		$this->DB->execute();

		if( $this->DB->getTotalRows() )
		{
			$this->registry->output->showError( $this->lang->words['gm_alreadymod'], 11660 );
		}

		/* Group Moderator */
		if( $this->request['mod_type'] == 'group' )
		{
			/* Get the group */
			$this->DB->build( array( 'select' => 'g_id, g_title', 'from' => 'groups', 'where' => "g_id=" . intval( $this->request['mgid'] ) ) );
			$this->DB->execute();
			
			/* Check the group */
			if( ! $group = $this->DB->fetch() )
			{
				$this->registry->output->showError( $this->lang->words['gm_nogrouplocate'], 11661 );
			}

			$ad_log = sprintf( $this->lang->words['gm_addedgroup'], $group['g_title'] );
		}
		/* Member Moderator */
		else
		{
			/* Get the member */
			$this->DB->build( array( 'select' => 'member_id, name, members_display_name', 'from' => 'members', 'where' => "member_id=" . intval( $this->request['mgid'] ) ) );
			$this->DB->execute();

			if( ! $mem = $this->DB->fetch() )
			{
				$this->registry->output->showError( $this->lang->words['gm_nomemberlocate'], 11662 );
			}

			$ad_log = sprintf( $this->lang->words['gm_addedmember'], $mem['namembers_display_nameme'] );
		}

		/* Store Mr. Hash */
		$this->DB->insert( 'blog_moderators', $mr_hash );
		
		/* Log, Cache, and Bounce */
		$this->registry->adminFunctions->saveAdminLog($ad_log);
		$this->rebuildModeratorCache();
		
		$this->registry->output->global_message = $this->lang->words['gm_modadded'];
		$this->registry->output->silentRedirectWithMessage( "{$this->settings['base_url']}{$this->html->form_code}do=index" );
	}

	/**
	 * Do Edit Mod
	 *
	 * @return	@e void
	 */
	public function doEditMod()
	{
		/* Build Mr Hash */
		$mr_hash = array(
							'moderate_can_edit_comments'	=> $this->request['moderate_can_edit_comments'],
							'moderate_can_edit_entries'		=> $this->request['moderate_can_edit_entries'],
							'moderate_can_del_comments'		=> $this->request['moderate_can_del_comments'],
							'moderate_can_del_entries'		=> $this->request['moderate_can_del_entries'],
							'moderate_can_publish'			=> $this->request['moderate_can_publish'],
							'moderate_can_lock'				=> $this->request['moderate_can_lock'],
							'moderate_can_approve'			=> $this->request['moderate_can_approve'],
							'moderate_can_editcblocks'		=> $this->request['moderate_can_editcblocks'],
							'moderate_can_del_trackback'	=> $this->request['moderate_can_del_trackback'],
							'moderate_can_view_draft'		=> $this->request['moderate_can_view_draft'],
							'moderate_can_view_private'		=> $this->request['moderate_can_view_private'],
							'moderate_can_warn'				=> $this->request['moderate_can_warn'],
							'moderate_can_pin'				=> $this->request['moderate_can_pin'],
							'moderate_can_disable'			=> $this->request['moderate_can_disable'],
							'moderator_can_feature'			=> $this->request['moderator_can_feature'],
						);

		/* Do we have all data? */
		if( $this->request['modid'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['gm_nomodid'], 11663 );
		}

		/* Already using this group on this forum? */
		$this->DB->build( array( 'select' => '*', 'from' => 'blog_moderators', 'where' => "moderate_id=".intval($this->request['modid']) ) );
		$this->DB->execute();

		if( !$this->DB->getTotalRows() )
		{
			$this->registry->output->showError( $this->lang->words['gm_novalidmodin'], 11664 );
		}

		/* Store mr hash */
		$this->DB->update( 'blog_moderators', $mr_hash, "moderate_id=".intval($this->request['modid']) );

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['gm_modeditedlog'], $this->request['modid'] ) );

		$this->rebuildModeratorCache();
		
		$this->registry->output->global_message = $this->lang->words['gm_modedit'];
		$this->registry->output->silentRedirectWithMessage( "{$this->settings['base_url']}{$this->html->form_code}do=index" );
	}

	/**
	 * Delete moderator
	 *
	 * @return	@e void
	 */
	public function doDelMod()
	{
		/* Do we have all data? */
		if( $this->request['modid'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['gm_nomodid'], 11665 );
		}

		/* Already using this group on this forum? */
		$this->DB->build( array( 'select' => '*', 'from' => 'blog_moderators', 'where' => "moderate_id=" . intval( $this->request['modid'] ) ) );
		$this->DB->execute();

		if( ! $this->DB->getTotalRows() )
		{
			$this->registry->output->showError( $this->lang->words['gm_novalidmodin'], 11666 );
		}

		/* Delete the moderator */
		$this->DB->delete( 'blog_moderators', 'moderate_id=' . intval( $this->request['modid'] ) );

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['gm_moddellog'], $this->request['modid'] ) );

		$this->rebuildModeratorCache();
		
		$this->registry->output->global_message = $this->lang->words['gm_moddel'];
		$this->registry->output->silentRedirectWithMessage( "{$this->settings['base_url']}{$this->html->form_code}do=index" );
	}

	/**
	 * Rebuild the moderators cache
	 *
	 * @return	@e void
	 */
	public function rebuildModeratorCache()
	{
		$cache = array();

		$this->DB->build( array( 'select' => "*", 'from' => 'blog_moderators' ) );
		$this->DB->execute();

		while ( $i = $this->DB->fetch() )
		{
			$cache[ $i['moderate_id'] ] = $i;
		}

		$this->cache->setCache( 'blogmods', $cache, array( 'array' => 1 ) );
	}

	/**
	 * Rebuild group cache
	 *
	 * @return	@e void
	 */
	public function rebuildGroupCache()
	{
		$cache = array();

		$this->DB->build( array( 'select' => "*", 'from' => 'groups' ) );
		$this->DB->execute();

		while ( $i = $this->DB->fetch() )
		{
			$cache[ $i['g_id'] ] = $i;
		}

		$this->cache->setCache( 'group_cache', $cache, array( 'array' => 1 ) );
	}
}