<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Forum RSS Import
 * Last Updated: $Date: 2012-05-17 07:44:48 -0400 (Thu, 17 May 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10763 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_forums_rss_import extends ipsCommand
{
	/**
	 * Classes yet loaded?
	 *
	 * @var		bool
	 */	
	protected $classes_loaded	= false;
	
	/**
	 * Items imported so far
	 *
	 * @var		integer
	 */	
	protected $import_count	= 0;
	
	/**
	 * Validation message(s)
	 *
	 * @var		array
	 */	
	protected $validate_msg	= array();
	
	/**
	 * Validation error(s)
	 *
	 * @var		array
	 */	
	protected $validate_errors	= array();
	
	/**#@+
	 * URL bits
	 *
	 * @var		string
	 */		
	public $form_code		= '';
	public $form_code_js	= '';
	/**#@-*/

	/**
	 * Mod Library, for recounting stats and deleting topics
	 *
	 * @var		object
	 */		
	protected $func_mod;	
	
	/**
	 * RSS Parser Class
	 *
	 * @var		object
	 */		
	protected $class_rss;
	
	/**
	 * Skin object
	 *
	 * @var		object
	 */	
	protected $html;	
	
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load HTML and Lang */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_rss' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_rss' ) );
			
		/* Load Mod Class */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
		$this->func_mod = new $classToLoad( $registry );
		
		/* URLs */
		$this->form_code	= $this->html->form_code	= 'module=rss&amp;section=import';
		$this->form_code_js	= $this->html->form_code_js	= 'module=rss&section=import';		
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'rssimport_overview':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportOverview();
			break;
				
			case 'rssimport_validate':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportValidate( 1 );
			break;				
			
			case 'rssimport_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportForm( 'add' );
			break;
				
			case 'rssimport_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportForm( 'edit' );
			break;
				
			case 'rssimport_add_save':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportSave( 'add' );
			break;
				
			case 'rssimport_edit_save':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportSave( 'edit' );
			break;
				
			case 'rssimport_recache':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportRebuildCache(0);
			break;
				
			case 'rssimport_remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_remove' );
				$this->rssImportRemoveDialogue();
			break;
				
			case 'rssimport_remove_complete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_remove' );
				$this->rssimportRemoveComplete( 1 );
			break;
				
			case 'rssimport_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_delete' );
				$this->rssImportDelete();
			break;
				
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'import_manage' );
				$this->rssImportOverview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();		
	}

	/**
	 * Delete an RSS Import Stream
	 *
	 * @return	@e void
	 */
	public function rssImportDelete()
	{
		/* INIT */
		$rss_import_id = intval( $this->request['rss_import_id'] );

		/* Load RSS Stream */
		$rssstream = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rss_import', 'where' => "rss_import_id={$rss_import_id}" ) );
		
		if ( ! $rssstream['rss_import_id'] )
		{
			$this->registry->output->global_error = $this->lang->words['im_noload'];
			$this->rssImportOverview();
			return;
		}
		
		/* Delete the stream */
		$this->DB->delete( 'rss_import', 'rss_import_id=' . $rss_import_id );
		
		$this->registry->output->global_message = $this->lang->words['im_removed'];
		$this->rssImportOverview();
	}	
	
	/**
	 * Removes imported articles
	 *
	 * @param	bool	$return			Whether to return or not
	 * @param	integer	$rss_import_id	RSS import id to remove
	 * @return	mixed
	 */
	public function rssimportRemoveComplete( $return=0, $rss_import_id=0 )
	{
		/* INIT */
		$rss_import_id = $rss_import_id ? $rss_import_id : intval( $this->request['rss_import_id'] );
		$remove_count  = intval( $this->request['remove_count'] ) ? intval( $this->request['remove_count'] ) : 500;
		$remove_tids   = array();
		
		/* Query the RSS Streams */
		$rssstream = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rss_import', 'where' => "rss_import_id={$rss_import_id}" ) );
		
		if ( ! $rssstream['rss_import_id'] )
		{
			$this->registry->output->global_error = $this->lang->words['im_noload'];
			$this->rssImportOverview();
			return;
		}
		
		/* Get tids */
		$this->DB->build( array( 
								'select'	=> 'rss_imported_tid',
								'from'		=> 'rss_imported',
								'where'		=> 'rss_imported_impid=' . $rss_import_id,
								'order'		=> 'rss_imported_tid DESC',
								'limit'		=> array( 0, $remove_count ) 
						)	 );												 
		$this->DB->execute();
		
		while( $tee = $this->DB->fetch() )
		{
			$remove_tids[ $tee['rss_imported_tid'] ] = $tee['rss_imported_tid'];
		}
		
		/* Check */
		if ( ! count( $remove_tids ) )
		{
			if ( $return )
			{
				$this->registry->output->global_error = $this->lang->words['im_findtopics'];
				$this->rssImportOverview();
				return;
			}
			else
			{
				return;
			}
		}
		
		/* Delete the topics */
		$this->func_mod->forum['id'] = $rssstream['rss_import_forum_id'];
		$this->func_mod->topicDelete( $remove_tids );
		
		/* Remove from the imported list */
		$this->DB->delete( 'rss_imported', 'rss_imported_tid IN(' . implode( ',', $remove_tids ) . ')' );
		
		$this->registry->output->global_message = intval( count( $remove_tids ) ) . $this->lang->words['im_topicsremoved'];
		$this->rssImportOverview();
	}	
	
	/**
	 * Splash screen for removing imported articles
	 *
	 * @return	@e void
	 */
	public function rssImportRemoveDialogue()
	{
		/* Check ID */
		$rss_import_id = intval( $this->request['rss_import_id'] );
		
		if ( ! $rss_import_id )
		{
			$this->registry->output->global_error = $this->lang->words['im_noid'];
			$this->rssImportOverview();
			return;
		}
		
		/* Load RSS Stream */
		$rssstream = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rss_import', 'where' => "rss_import_id={$rss_import_id}" ) );
		
		if( ! $rssstream['rss_import_id'] )
		{
			$this->registry->output->global_error = $this->lang->words['im_noload'];
			$this->rssImportOverview();
			return;
		}
		
		/* Count the number of imported topics */
		$article_count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as cnt', 'from' => 'rss_imported', 'where' => 'rss_imported_impid='.$rss_import_id ) );
		
		if ( $article_count['cnt'] < 1 )
		{
			$this->registry->output->global_error = sprintf( $this->lang->words['im_noarticles'], $rssstream['rss_import_title'] );
			$this->rssImportOverview();
			return;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->rssImportRemoveArticlesForm( $rssstream, intval( $article_count['cnt'] ) );
	}	
	
	/**
	 * Saves the add/edit RSS Import form
	 *
	 * @param	string	$type	Either add or edit
	 * @return	@e void
	 */
	public function rssImportSave($type='add')
	{
		/* Validate the feed? */
		if( $this->request['rssimport_validate'] AND $this->request['rssimport_validate'] )
		{
			$this->rssImportValidate();
			
			if( count($this->validate_msg) )
			{
				$this->registry->output->setMessage( sprintf( $this->lang->words['im_valresults'], IPSText::stripslashes( trim( $this->request['rss_import_url'] ) ), implode( "<br />&nbsp;&middot;", $this->validate_msg ) ), 1 );
			}
			
			if( count($this->validate_errors) )
			{
				$this->registry->output->global_error = sprintf( $this->lang->words['im_valerrors'], IPSText::stripslashes( trim( $this->request['rss_import_url'] ) ), implode( "<br />&nbsp;&middot;", $this->validate_errors ) );
			}
			
			
			$this->rssImportForm( $type );
			return;
		}
				
		/* Get Form Data */
		$rss_import_id         = intval( $this->request['rss_import_id'] );
		$rss_import_title      = trim( $this->request['rss_import_title'] );
		$rss_import_url        = IPSText::stripslashes( trim( $this->request['rss_import_url'] ) );
		$rss_import_mid        = trim( $this->request['rss_import_mid'] );
		$rss_import_showlink   = IPSText::stripslashes( trim( $this->request['rss_import_showlink'] ) );
		$rss_import_enabled    = intval( $this->request['rss_import_enabled'] );
		$rss_import_forum_id   = intval( $this->request['rss_import_forum_id'] );
		$rss_import_pergo      = intval( $this->request['rss_import_pergo'] );
		$rss_import_time       = intval( $this->request['rss_import_time'] );
		$rss_import_topic_open = intval( $this->request['rss_import_topic_open'] );
		$rss_import_topic_hide = intval( $this->request['rss_import_topic_hide'] );
		$rss_import_topic_pre  = $this->request['rss_import_topic_pre'];
		$rss_import_allow_html = intval( $this->request['rss_import_allow_html'] );
		$rss_import_auth	   = intval( $this->request['rss_import_auth'] );
		$rss_import_auth_user  = trim( $this->request['rss_import_auth_user'] ) ? trim( $this->request['rss_import_auth_user'] ) : '';
		$rss_import_auth_pass  = trim( $this->request['rss_import_auth_pass'] ) ? trim( $this->request['rss_import_auth_pass'] ) : '';

		$rss_error             = array();
		
		/* Error checking */
		if ( $type == 'edit' )
		{
			if ( ! $rss_import_id )
			{
				$this->registry->output->global_error = $this->lang->words['im_noid'];
				$this->rssImportOverview();
				return;
			}
		}
		
		if ( ! $rss_import_title OR ! $rss_import_url OR ! $rss_import_pergo OR ! $rss_import_forum_id OR ! $rss_import_mid )
		{
			$this->registry->output->global_error = $this->lang->words['im_completeform'];
			$this->rssImportForm( $type );
			return;
		}
		
		//-----------------------------------------
		// Only validate feed if it's enabled
		// @link	http://community.invisionpower.com/tracker/issue-26647-unable-to-disable-rss-import-feed
		//-----------------------------------------
		
		if ( $rss_import_enabled )
		{
			/* Load the RSS Class */
			$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
			$this->class_rss				= new $classToLoad();
			$this->class_rss->rss_max_show	= $rss_import_pergo;
			$this->class_rss->doc_type		= strtoupper(IPS_DOC_CHAR_SET);

			/* Set this import's authentication */				
			$this->class_rss->auth_req  = $rss_import_auth;
			$this->class_rss->auth_user = $rss_import_auth_user;
			$this->class_rss->auth_pass = $rss_import_auth_pass;
					
			/* Test URL */
			$this->class_rss->parseFeedFromUrl( $rss_import_url );
			
			/* Found an error? */
			if ( is_array( $this->class_rss->errors ) and count( $this->class_rss->errors ) )
			{
				$rss_error = array_merge( $rss_error,  $this->class_rss->errors );
			}
			
			/* Found some data? */
			if ( ! is_array( $this->class_rss->rss_channels ) or ! count( $this->class_rss->rss_channels ) )
			{
				$rss_error[] = sprintf( $this->lang->words['im_noopen'], $rss_import_url );
			}
			
			if ( is_array( $rss_error ) AND count( $rss_error ) )
			{
				$this->registry->output->global_error = implode( "<br />", $rss_error );
				$this->rssImportForm( $type );
				return;
			}
		}

		/* Member data */
		$member = $this->DB->buildAndFetch( array( 'select' => 'member_id, name', 'from' => 'members', 'where' => "members_l_display_name='" . strtolower( $rss_import_mid ) . "'" ) );
		
		if ( empty( $member['member_id'] ) )
		{
			$this->registry->output->global_error = sprintf( $this->lang->words['im_nomember'], $rss_import_mid );
			$this->rssImportForm( $type );
			return;
		}
		else
		{
			$rss_import_mid = $member['member_id'];
		}

		/* Check to make sure forum ID is valid */
		$this->registry->class_forums->forumsInit();
		
		if ( empty( $this->registry->class_forums->forum_by_id[ $rss_import_forum_id ] ) )
		{
			$this->registry->output->global_error = $this->lang->words['im_noforum'];
			$this->rssImportForm( $type );
			return;
		}
		
		if ( $this->registry->class_forums->forum_by_id[ $rss_import_forum_id ]['sub_can_post'] != 1 OR $this->registry->class_forums->forum_by_id[ $rss_import_forum_id ]['redirect_on'] == 1 )
		{
			$this->registry->output->global_error = $this->lang->words['im_noforumperm'];
			$this->rssImportForm( $type );
			return;
		}
		
		/* Build the db array */
		$array = array( 
						'rss_import_title'      => $rss_import_title,
						'rss_import_url'        => $rss_import_url,
						'rss_import_mid'        => $rss_import_mid,
						'rss_import_showlink'   => $rss_import_showlink,
						'rss_import_enabled'    => $rss_import_enabled,
						'rss_import_forum_id'   => $rss_import_forum_id,
						'rss_import_pergo'      => $rss_import_pergo,
						'rss_import_time'       => $rss_import_time < 30 ? 30 : $rss_import_time,
						'rss_import_topic_open' => $rss_import_topic_open,
						'rss_import_topic_hide' => $rss_import_topic_hide,
						'rss_import_topic_pre'  => $rss_import_topic_pre,
						'rss_import_allow_html'	=> $rss_import_allow_html,
						'rss_import_auth'		=> $rss_import_auth,
						'rss_import_auth_user'  => $rss_import_auth_user,
						'rss_import_auth_pass'  => $rss_import_auth_pass,
					 );
		
		/* Add to database */	 
		if ( $type == 'add' )
		{
			$this->DB->insert( 'rss_import', $array );
			$this->registry->output->global_message = $this->lang->words['im_created'];
			$rss_import_id = $this->DB->getInsertId();
		}
		/* Update the database */
		else
		{
			$this->DB->update( 'rss_import', $array, 'rss_import_id='.$rss_import_id );
			$this->registry->output->global_message = $this->lang->words['im_edited'];
		}
		
		/* Build the cache */
		if( $rss_import_enabled )
		{
			$this->rssImportRebuildCache( $rss_import_id, 0 );
		}
		
		/* Bounce */
		$this->rssImportOverview();
	}	
	
	/**
	 * Form for adding/editing RSS Imports
	 *
	 * @param	string	$type	Either add or edit
	 * @return	@e void
	 */
	public function rssImportForm( $type='add' )
	{
		/* INIT */
		$rss_import_id = $this->request['rss_import_id'] ? intval( $this->request['rss_import_id'] ) : 0;
		
		/* Build form drop downs */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/admin_forum_functions.php", 'admin_forum_functions', 'forums' );
		$aff = new $classToLoad( $this->registry );
		$aff->forumsInit();
		$forum_dropdown = $aff->adForumsForumList( 1 );
		
		/* Add new import */
		if ( $type == 'add' )
		{
			/* Form Bits */
			$formcode = 'rssimport_add_save';
			$title    = $this->lang->words['im_createnew'];
			$button   = $this->lang->words['im_createnew'];
			
			/* Form Data */
			$rssstream = array( 'rss_import_topic_open' => 1, 
							    'rss_import_enabled' 	=> 1, 
							    'rss_import_showlink' 	=> $this->lang->words['im_full'],
							    'rss_import_title'		=> '',
							    'rss_import_url'		=> '',
							    'rss_import_forum_id'	=> 0,
							    'rss_import_mid'		=> '',
							    'rss_import_pergo'		=> 10,
							    'rss_import_time'		=> '200',
							    'rss_import_topic_hide'	=> 0,
							    'rss_import_topic_pre'	=> '',
							    'rss_import_allow_html'	=> 0,
							    'rss_import_auth'		=> NULL,
							    'rss_import_auth_user'	=> NULL,
							    'rss_import_auth_pass'	=> NULL,
							    'rss_import_id'			=> 0 );
		}
		/* Edit Form */
		else
		{
			/* Form Data */
			$rssstream = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rss_import', 'where' => 'rss_import_id='.$rss_import_id ) );
			
			/* Make sure it's valid */
			if ( ! $rssstream['rss_import_id'] )
			{
				$this->registry->output->global_error = $this->lang->words['im_noid'];
				$this->rssImportOverview();
				return;
			}
			
			/* Get the member name */
			$member = $this->DB->buildAndFetch( array( 'select' => 'member_id, members_display_name', 'from' => 'members', 'where' => "member_id=" . intval( $rssstream['rss_import_mid'] ) ) );
			
			if ( $member['member_id'] )
			{
				$rssstream['rss_import_mid'] = $member['members_display_name'];
			}
			
			/* Form Bits */
			$formcode = 'rssimport_edit_save';
			$title    = $this->lang->words['im_edit'] . $rssstream['rss_import_title'];
			$button   = $this->lang->words['im_save'];
		}
		
		/* Form Elements */
		$form = array();
		
		$form['rss_import_title']      = $this->registry->output->formInput(        'rss_import_title'       , !empty($this->request['rss_import_title']) ? stripslashes($this->request['rss_import_title'])      : $rssstream['rss_import_title'] );
		$form['rss_import_enabled']    = $this->registry->output->formYesNo(       'rss_import_enabled'     , !empty($this->request['rss_import_enabled']) ? $this->request['rss_import_enabled']    : $rssstream['rss_import_enabled'] );
		$form['rss_import_url']        = $this->registry->output->formInput(        'rss_import_url'         , !empty($this->request['rss_import_url']) ? $this->request['rss_import_url']        : $rssstream['rss_import_url'] );
		$form['rss_import_forum_id']   = $this->registry->output->formDropdown(     'rss_import_forum_id'    , $forum_dropdown, !empty($this->request['rss_import_forum_id']) ? $this->request['rss_import_forum_id'] : $rssstream['rss_import_forum_id'] );
		$form['rss_import_mid']        = $this->registry->output->formInput(        'rss_import_mid'         , !empty($this->request['rss_import_mid']) ? $this->request['rss_import_mid']        : $rssstream['rss_import_mid'] );
		$form['rss_import_pergo']      = $this->registry->output->formSimpleInput( 'rss_import_pergo'       , !empty($this->request['rss_import_pergo']) ? $this->request['rss_import_pergo']      : $rssstream['rss_import_pergo'], 5 );
		$form['rss_import_time']       = $this->registry->output->formSimpleInput( 'rss_import_time'        , !empty($this->request['rss_import_time']) ? $this->request['rss_import_time']       : $rssstream['rss_import_time'], 5 );
		$form['rss_import_showlink']   = $this->registry->output->formInput(        'rss_import_showlink'    , !empty($this->request['rss_import_showlink']) ? htmlspecialchars($this->request['rss_import_showlink'])   : htmlspecialchars($rssstream['rss_import_showlink']) );
		$form['rss_import_topic_open'] = $this->registry->output->formYesNo(       'rss_import_topic_open'  , !empty($this->request['rss_import_topic_open']) ? $this->request['rss_import_topic_open'] : $rssstream['rss_import_topic_open'] );
		$form['rss_import_topic_hide'] = $this->registry->output->formYesNo(       'rss_import_topic_hide'  , !empty($this->request['rss_import_topic_hide']) ? $this->request['rss_import_topic_hide'] : $rssstream['rss_import_topic_hide'] );
		$form['rss_import_topic_pre']  = $this->registry->output->formInput(        'rss_import_topic_pre'   , !empty($this->request['rss_import_topic_pre']) ? $this->request['rss_import_topic_pre']  : $rssstream['rss_import_topic_pre'] );
		$form['rss_import_allow_html'] = $this->registry->output->formYesNo(       'rss_import_allow_html'  , !empty($this->request['rss_import_allow_html']) ? $this->request['rss_import_allow_html'] : $rssstream['rss_import_allow_html'] );
		$form['rss_import_auth']	   = $this->registry->output->formCheckbox(	 'rss_import_auth'		  ,
																						!empty($this->request['rss_import_auth']) ? $this->request['rss_import_auth'] : $rssstream['rss_import_auth'],
																						'1',
																						"rss_import_auth",
																						'onclick="ACPRss.showAuthBoxes()"'
																				);

		$form['rss_import_auth_user'] = $this->registry->output->formInput( 'rss_import_auth_user', !empty( $this->request['rss_import_auth_user'] ) ? $this->request['rss_import_auth_user'] : $rssstream['rss_import_auth_user'] );
		$form['rss_import_auth_pass'] = $this->registry->output->formInput( 'rss_import_auth_pass', !empty( $this->request['rss_import_auth_pass'] ) ? $this->request['rss_import_auth_pass'] : $rssstream['rss_import_auth_pass'] );																				
		
		/* Output */
		$this->registry->output->html           .= $this->html->rssImportForm( $form, $title, $formcode, $button, $rssstream );
	}	
	
	/**
	 * Builds a list of current RSS Imports
	 *
	 * @return	@e void
	 */
	public function rssImportOverview()
	{
		/* INIT */
		$rows    = array();		
		$st		 = intval( $this->request['st'] ) > 0 ? intval( $this->request['st'] ) : 0;
		
		/* Count the number of feeds we ahve */
		$num = $this->DB->buildAndFetch( array( 'select' => 'count(*) as row_count', 'from' => 'rss_import' ) );
		
		/* Generate Pagination */
		$page_links = $this->registry->output->generatePagination( array( 
																			'totalItems'         => $num['row_count'],
																			'itemsPerPage'       => 25,
																			'currentStartValue'  => $st,
																			'baseUrl'            => "{$this->settings['base_url']}{$this->form_code}",
																)	 );

		/* Query the current feeds */
		$this->DB->build( array( 'select' => '*', 'from' => 'rss_import', 'order' => 'rss_import_id ASC', 'limit' => array( $st, 25 ) ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['_enabled_img'] = $r['rss_import_enabled'] ? 'aff_tick.png' : 'aff_cross.png';
			
			$rows[] = $r;
		}
		
		/* Output */
		$this->registry->output->html            .= $this->html->rssImportOverview( $rows, $page_links );
	}	

	
	/**
	 * Rebuild the RSS Stream cache
	 *
	 * @param	mixed	$rss_import_id	ID of the stream to import
	 * @param	bool	$return			Set to true to return true/false
	 * @param	bool	$id_is_array	Set to true if the first paramter is an array of ids
	 * @return	mixed
	 */
	public function rssImportRebuildCache( $rss_import_id, $return=true, $id_is_array=false )
	{
		/* INIT */
		$errors             = array();
		$affected_forum_ids = array();
		$rss_error         	= array();
		$rss_import_ids		= array();
		$items_imported     = 0;
		
		/* Check the ID */
		if ( ! $rss_import_id )
		{
			$rss_import_id = $this->request['rss_import_id'] == 'all' ? 'all' : intval( $this->request['rss_import_id'] );
		}

		/* No ID Found */
		if ( ! $rss_import_id )
		{
			$this->registry->output->global_error = $this->lang->words['im_noid'];
			$this->rssImportOverview();
			return;
		}
		
		/* Create an array of ids */
		if( $id_is_array == 1 )
		{
			$rss_import_ids = explode( ",", $rss_import_id );
		}
		
		/* Load the classes we need */
		if ( ! $this->classes_loaded )
		{
			/* Get the RSS Class */
			if ( ! is_object( $this->class_rss ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
				$this->class_rss               = new $classToLoad();
				$this->class_rss->rss_max_show = 100;
			}

			/* Get the post class */
			require_once(IPSLib::getAppDir('forums') .'/sources/classes/post/classPost.php' );/*noLibHook*/
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php', 'classPostForms', 'forums' );
			
			$this->post = new $classToLoad( $this->registry );

			/* Load the mod libarry */
			if ( ! $this->func_mod )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
				$this->func_mod = new $classToLoad( $this->registry );
			}
			
			$this->classes_loaded = 1;
		}
		
		/* INIT Forums */
		if ( ! is_array( $this->registry->class_forums->forum_by_id ) OR !count( $this->registry->class_forums->forum_by_id ) )
		{
			$this->registry->class_forums->forumsInit();
		}
		
		/* Sort out which IDs to load.. */
		if ( $rss_import_id == 'all' )
		{
			$where = 'rss_import_enabled=1'; // Update only enabled ones!
		}
		elseif( $id_is_array == 1 )
		{
			$where = 'rss_import_id IN (' . implode(',', $rss_import_ids) . ')';
		}
		else
		{
			$where = 'rss_import_id=' . $rss_import_id;
		}
		
		/* Query the RSS imports */
		$this->DB->build( array( 'select' => '*', 'from' => 'rss_import', 'where' => $where ) );
		$outer = $this->DB->execute();
		
		/* Loop through and build cache */
		while( $row = $this->DB->fetch( $outer ) )
		{
			/* Skip non-existent forums - bad stuff happens */
			if ( empty($this->registry->class_forums->forum_by_id[ $row['rss_import_forum_id'] ]) )
			{
				continue;
			}
			
			/* Allowing badwords? */
			IPSText::getTextClass('bbcode')->bypass_badwords = $row['rss_import_allow_html'];
			
			/* Set this import's doctype */
			$this->class_rss->doc_type 		= strtoupper(IPS_DOC_CHAR_SET);
			
			/* Set this import's authentication */
			$this->class_rss->auth_req 	= $row['rss_import_auth'];
			$this->class_rss->auth_user = $row['rss_import_auth_user'];
			$this->class_rss->auth_pass = $row['rss_import_auth_pass'];

			/* Clear RSS object's error cache first */
			$this->class_rss->errors 	= array();
			$this->class_rss->rss_items = array();
			
			/* Reset the rss count as this is a new feed */
			$this->class_rss->rss_count 	= 0;
			$this->class_rss->rss_max_show 	= $row['rss_import_pergo'];
			
			/* Parse RSS */
			$this->class_rss->parseFeedFromUrl( $row['rss_import_url'] );
			
			/* Check for errors */
			if ( is_array( $this->class_rss->errors ) and count( $this->class_rss->errors ) )
			{
				$rss_error = array_merge( $rss_error,  $this->class_rss->errors );
				continue;
			}
			
			if ( ! is_array( $this->class_rss->rss_channels ) or ! count( $this->class_rss->rss_channels ) )
			{
				$rss_error[] = sprintf( $this->lang->words['im_noopen'], $row['rss_import_url'] );
				continue;
			}
			
			/* Update last check time */
			$this->DB->update( 'rss_import', array( 'rss_import_last_import' => IPS_UNIX_TIME_NOW ), 'rss_import_id='.$row['rss_import_id'] );
			
			/* Apparently so: Parse feeds and check for already imported GUIDs */
			$final_items = array();
			$items       = array();
			$check_guids = array();
			$final_guids = array();
			$count       = 0;
			
			if ( ! is_array( $this->class_rss->rss_items ) or ! count( $this->class_rss->rss_items ) )
			{
				$rss_error[] = $row['rss_import_url'] . $this->lang->words['im_noimport'];
				continue;
			}
				
			/* Loop through the channels */
			foreach ( $this->class_rss->rss_channels as $channel_id => $channel_data )
			{
				if ( is_array( $this->class_rss->rss_items[ $channel_id ] ) and count ($this->class_rss->rss_items[ $channel_id ] ) )
				{			
					/* Loop through the items in this channel */
					foreach( $this->class_rss->rss_items[ $channel_id ] as $item_data )
					{
						/* Item Data */
						$item_data['content']  = $item_data['content']   ? $item_data['content']  : $item_data['description'];
						$item_data['guid']     = md5( $row['rss_import_id'] . ( $item_data['guid'] ? $item_data['guid']     : preg_replace( '#\s|\r|\n#is', "", $item_data['title'].$item_data['link'].$item_data['description'] ) ) );
						$item_data['unixdate'] = intval($item_data['unixdate'])  ? intval($item_data['unixdate']) : IPS_UNIX_TIME_NOW;

						/*  If feed charset doesn't match original, we converted to utf-8 and need to convert back now */
						if ( $this->class_rss->doc_type != $this->class_rss->orig_doc_type )
						{
							$item_data['title']   = IPSText::convertCharsets( $item_data['title']  , "UTF-8", IPS_DOC_CHAR_SET );
							$item_data['content'] = IPSText::convertCharsets( $item_data['content'], "UTF-8", IPS_DOC_CHAR_SET );
						}
						
						/* Error check */
						if ( ! $item_data['title'] OR ! $item_data['content'] )
						{
						 	$rss_error[] = sprintf( $this->lang->words['im_notitle'], $item_data['title'] );
							continue;
						}
						
						/* Dates */
						if ( $item_data['unixdate'] < 1 )
						{
							$item_data['unixdate'] = IPS_UNIX_TIME_NOW;
						}
						else if ( $item_data['unixdate'] > IPS_UNIX_TIME_NOW )
						{
							$item_data['unixdate'] = IPS_UNIX_TIME_NOW;
						}
						
						/* Add to array */
						$items[ $item_data['guid'] ] = $item_data;
						$check_guids[]               = $item_data['guid'];
					}
				}
			}
			
			/* Check GUIDs */
			if ( ! count( $check_guids ) )
			{
				$rss_error[] = $this->lang->words['im_noitems'];
				continue;
			}
			
			$this->DB->build( array( 'select' => '*', 'from' => 'rss_imported', 'where' => "rss_imported_guid IN ('".implode( "','", $check_guids )."')" ) );
			$this->DB->execute();
			
			while ( $guid = $this->DB->fetch() )
			{
				$final_guids[ $guid['rss_imported_guid'] ] = $guid['rss_imported_guid'];
			}
			
			/* Compare GUIDs */
			$item_count = 0;
			
			foreach( $items as $guid => $data )
			{
				if ( in_array( $guid, $final_guids ) )
				{
					continue;
				}
				else
				{
					$item_count++;
					
					/* Make sure each item has a unique date */
					$final_items[ $data['unixdate'].$item_count ] = $data;
				}
			}

			/* Sort Array */
			krsort( $final_items );
			
			/* Pick off last X */
			$count           = 1;
			$tmp_final_items = $final_items;
			$final_items     = array();
			
			foreach( $tmp_final_items as $date => $data )
			{
				$final_items[ $date ] = $data;
				
				if ( $count >= $row['rss_import_pergo'] )
				{
					break;
				}
					
				$count++;
			}

			/* Anything left? */
			if ( ! count( $final_items ) )
			{
				continue;
			}
			
			/* Figure out MID */
			$member = $this->DB->buildAndFetch( array( 'select' => 'member_id, name, members_display_name, ip_address', 'from' => 'members', 'where' => "member_id={$row['rss_import_mid']}" ) );
			
			if ( ! $member['member_id'] )
			{
				continue;
			}
			
			/* Set member in post class */
			$this->post->setAuthor( $member['member_id'] );
			$this->post->setForumData( $this->registry->getClass('class_forums')->forum_by_id[ $row['rss_import_forum_id'] ] );
			$this->post->setBypassPermissionCheck( true );
			$this->post->setForumID( $row['rss_import_forum_id'] );
			
			/* Make 'dem posts */
			$affected_forum_ids[] = $row['rss_import_forum_id'];
			
			/* Get editor */
			$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
			$editor			= new $classToLoad();
			
			/* Force RTE */
			$editor->setRteEnabled( true );
				
			foreach( $final_items as $topic_item )
			{
				/* Fix &amp; */
				$topic_item['title'] = str_replace( '&amp;', '&', $topic_item['title'] );
				$topic_item['title'] = str_replace( array( "\r", "\n" ), ' ', $topic_item['title'] );
				$topic_item['title'] = str_replace( array( "<br />", "<br>" ), ' ', $topic_item['title'] );
				$topic_item['title'] = trim( $topic_item['title'] );
				$topic_item['title'] = strip_tags( $topic_item['title'] );
				$topic_item['title'] = IPSText::parseCleanValue( $topic_item['title'] );
				
				/* Fix up &amp;reg; */
				$topic_item['title'] = str_replace( '&amp;reg;', '&reg;', $topic_item['title'] );
				
				if ( $row['rss_import_topic_pre'] )
				{
					$topic_item['title'] = str_replace( '&nbsp;', ' ', str_replace( '&amp;nbsp;', '&nbsp;', $row['rss_import_topic_pre'] ) ) .' '. $topic_item['title'];
				}
				
				$this->post->setTopicTitle( IPSText::mbsubstr( $topic_item['title'], 0, 250 ) );
				$this->post->setDate( $topic_item['unixdate'] );
				$this->post->setPublished( ( $row['rss_import_topic_hide'] ) ? false : true );
				$this->post->setPublishedRedirectSkip( true );
			
				/* Sort post content: Convert HTML to BBCode */
				IPSText::getTextClass('bbcode')->parse_smilies		= 1;
				IPSText::getTextClass('bbcode')->parse_html			= intval($row['rss_import_allow_html']);
				IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
				IPSText::getTextClass('bbcode')->parsing_section	= 'topics';

				/* Clean up.. */
				$topic_item['content'] = preg_replace( "#<br />(\r)?\n#is", "<br />", $topic_item['content'] );
				
				if ( ! $row['rss_import_allow_html'] )
				{
					$topic_item['content']	= stripslashes($topic_item['content']);

					$post_content = $editor->process( $topic_item['content'] );
				}
				else
				{
					$post_content = stripslashes($topic_item['content']);
				}
				
				/* Add in Show link... */
				if ( $row['rss_import_showlink'] AND $topic_item['link'] )
				{
					$the_link = str_replace( '{url}', trim($topic_item['link']), $row['rss_import_showlink'] );

					if ( $row['rss_import_allow_html'] )
					{
						$_raw	= IPSText::getTextClass('bbcode')->preEditParse( stripslashes($the_link) );
						
						$the_link = "<br /><br />" . IPSText::getTextClass('bbcode')->preDbParse( $editor->process( $_raw ) );
					}
					else
					{
						$the_link = "<br /><br />" . $the_link;
					}
					
					$post_content .= $the_link;
				}
				
				/* Make sure HTML mode is enabled correctly */
				$this->request['post_htmlstatus'] = 1;
				$tmpForum  = $this->post->getForumData();
				$tmpAuthor = $this->post->getAuthor();
				
				$this->post->setForumData( array_merge( $tmpForum, array( 'use_html' => 1 ) ) );
				$this->post->setAuthor( array_merge( $tmpAuthor, array( 'g_dohtml' => 1 ) ) );
				
				$this->post->setPostContentPreFormatted( $post_content );
				
				/* Insert */
				try
				{
					$this->post->addTopic();
				}
				catch ( Exception $e ) {}
				
				/* Reset */
				$this->request['post_htmlstatus'] = 0;
				$this->post->setForumData( $tmpForum );
				$this->post->setAuthor( $tmpAuthor );
				
								
				/* Insert GUID match */
				$this->DB->insert( 'rss_imported', array( 'rss_imported_impid' => $row['rss_import_id'],
														  'rss_imported_guid'  => $topic_item['guid'],
														  'rss_imported_tid'   => $this->post->getTopicData('tid') ) );
				
				$this->import_count++;
			}
		}
		
		/* Recount Stats */		
		if ( count( $affected_forum_ids ) )
		{
			foreach( $affected_forum_ids as $fid )
			{
				$this->func_mod->forumRecount( $fid );
			}
			
			$this->cache->rebuildCache( 'stats', 'global' );
		}
		
		/* Return */
		if ( $return )
		{
			$this->registry->output->global_message = $this->lang->words['im_recached'];
			
			if ( count( $rss_error ) )
			{
				$this->registry->output->global_message .= "<br />".implode( "<br />", $rss_error );
			}
			
			$this->rssImportOverview();
			return;
		}
		else
		{
			return TRUE;
		}
	}
	
	/**
	 * Validate an RSS Feed
	 *
	 * @param	bool	$standalone	If set to true, data will be queried from the db based on rss_id, otherwise data will be gathered from form fields
	 * @return void
	 */
	public function rssImportValidate( $standalone=false )
	{
		/* INI */
		$return = 0;
		
		if( ! $standalone )
		{
			/* Get data from the form */
			$rss_import_id         = intval( $this->request['rss_import_id'] );
			$rss_import_title      = trim( $this->request['rss_import_title'] );
			$rss_import_url        = IPSText::stripslashes( trim( $this->request['rss_import_url'] ) );
			$rss_import_mid        = trim( $this->request['rss_import_mid'] );
			$rss_import_showlink   = IPSText::stripslashes( trim( $this->request['rss_import_showlink'] ) );
			$rss_import_enabled    = intval( $this->request['rss_import_enabled'] );
			$rss_import_forum_id   = intval( $this->request['rss_import_forum_id'] );
			$rss_import_pergo      = intval( $this->request['rss_import_pergo'] );
			$rss_import_time       = intval( $this->request['rss_import_time'] );
			$rss_import_topic_open = intval( $this->request['rss_import_topic_open'] );
			$rss_import_topic_hide = intval( $this->request['rss_import_topic_hide'] );
			$rss_import_topic_pre  = $this->request['rss_import_topic_pre'];
			$rss_import_allow_html = intval( $this->request['rss_import_allow_html'] );
			$rss_import_auth	   = intval( $this->request['rss_import_auth'] );
			$rss_import_auth_user  = trim( $this->request['rss_import_auth_user'] ) ? trim( $this->request['rss_import_auth_user'] ) : '';
			$rss_import_auth_pass  = trim( $this->request['rss_import_auth_pass'] ) ? trim( $this->request['rss_import_auth_pass'] ) : '';
			
			$return				   = 1;
		}
		else
		{
			/* Get the RSS ID */
			$rss_input_id = $this->request['rss_id'] ? intval($this->request['rss_id']) : 0;
			
			/* Found an id */
			if( $rss_input_id > 0 )
			{
				/* Query the data from the db */
				$rss_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rss_import', 'where' => 'rss_import_id=' . $rss_input_id ) );
				
				/* Format Data */
				if( ! $rss_data['rss_import_url'] )
				{
					$rss_import_url 		= "";
					$rss_import_auth 		= "";
					$rss_import_auth_user 	= "";
					$rss_import_auth_pass 	= "";
				}
				else
				{
					$standalone = 0;
					
					$rss_import_id         = intval( $rss_data['rss_import_id'] );
					$rss_import_url        = $rss_data['rss_import_url'];
					
					$member = $this->DB->buildAndFetch( array( 'select' => 'members_display_name', 'from' => 'members', 'where' => 'member_id=' . $rss_data['rss_import_mid'] ) );
					
					$rss_import_mid		   = $member['members_display_name'];
					
					$rss_import_forum_id   = intval( $rss_data['rss_import_forum_id'] );
					$rss_import_auth	   = intval( $rss_data['rss_import_auth'] );
					$rss_import_auth_user  = trim( $rss_data['rss_import_auth_user'] );
					$rss_import_auth_pass  = trim( $rss_data['rss_import_auth_pass'] );
				}
			}
			/* Try from URL */
			else
			{
				$rss_import_url 		= IPSText::stripslashes( trim( $this->request['rss_url'] ) );
				$rss_import_auth		= "";
				$rss_import_auth_user 	= "";
				$rss_import_auth_pass 	= "";				
			}
		}
		
		/* Check for URL */
		if( ! $rss_import_url )
		{
			$this->validate_errors[] = $this->lang->words['im_nourl'];
		}
		else
		{
			/* INIT */
			if ( ! $this->classes_loaded )
			{
				/* Load RSS Class */
				if ( ! is_object( $this->class_rss ) )
				{
					$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
					$this->class_rss               =  new $classToLoad();
					$this->class_rss->rss_max_show =  100;
				}
				
				$this->classes_loaded = 1;
			}
			
			/* Set this imports doc type */
			$this->class_rss->doc_type 		= strtoupper(IPS_DOC_CHAR_SET);
			
			/* Set this import's authentication */		
			$this->class_rss->auth_req  = $rss_import_auth;				
			$this->class_rss->auth_user = $rss_import_auth_user;
			$this->class_rss->auth_pass = $rss_import_auth_pass;
			$this->class_rss->userAgent = $_SERVER['HTTP_USER_AGENT'];
			
			/* Clear RSS object's error cache first */
			$this->class_rss->errors 	= array();
			$this->class_rss->rss_items = array();

			/* Reset the rss count as this is a new feed */
			$this->class_rss->rss_count =  0;
			
			/* Parse RSS */
			$this->class_rss->parseFeedFromUrl( $rss_import_url );
			
			/* Validate Data - HTTP Status Code/Text */
			if( $this->class_rss->classFileManagement->http_status_code != "200" )
			{
				if( $this->class_rss->classFileManagement->http_status_code )
				{
					$this->validate_errors[] ="{$this->lang->words['im_http']} {$this->class_rss->classFileManagement->http_status_code} ({$this->class_rss->classFileManagement->http_status_text})";
				}
			}
			else
			{
				$this->validate_msg[] = "{$this->lang->words['im_http']} {$this->class_rss->classFileManagement->http_status_code} ({$this->class_rss->classFileManagement->http_status_text})";
			}
			
			/* Display any errors found */
			if ( is_array( $this->class_rss->errors ) and count( $this->class_rss->errors ) )
			{
				foreach( $this->class_rss->errors as $error )
				{
					$this->validate_errors[] = $error;
				}
			}
			else
			{
				/* Channels */
				if ( ! is_array( $this->class_rss->rss_channels ) or ! count( $this->class_rss->rss_channels ) )
				{
					$this->validate_errors[] = $this->lang->words['im_nochannels'];
				}
				else
				{
					$this->validate_msg[] = sprintf( $this->lang->words['im_channelcount'], count($this->class_rss->rss_channels) );
					
					/* Any Items */
					if ( ! is_array( $this->class_rss->rss_items ) or ! count( $this->class_rss->rss_items ) )
					{
						$this->validate_errors[] = $this->lang->words['im_nocontent'];
					}
					else
					{
						foreach ( $this->class_rss->rss_channels as $channel_id => $channel_data )
						{
							if ( is_array( $this->class_rss->rss_items[ $channel_id ] ) and count ($this->class_rss->rss_items[ $channel_id ] ) )
							{
								$this->validate_msg[] = sprintf ( $this->lang->words['im_topiccount'], count($this->class_rss->rss_items[ $channel_id ]) );
																
								foreach( $this->class_rss->rss_items[ $channel_id ] as $item_data )
								{
									if( !$item_data['unixdate'] )
									{
										$this->validate_errors[] = $this->lang->words['im_nodate'];
									}
									
									if ( $item_data['unixdate'] < 1 )
									{
										$this->validate_errors[] = $this->lang->words['im_invdate'];
									}
									else if ( $item_data['unixdate'] > IPS_UNIX_TIME_NOW )
									{
										$this->validate_errors[] = $this->lang->words['im_invdate'];
									}	
									
									$item_data['content']  = $item_data['content']   ? $item_data['content']  : $item_data['description'];
									
									if ( ! $item_data['title'] OR ! $item_data['content'] )
									{
										$this->validate_errors[] = $this->lang->words['im_nodesc'];
									}
									
									break 2;
								}
							}
						}
					}
				}
			}
			
			if( !$standalone )
			{
				if( $rss_import_mid )
				{
					$member = $this->DB->buildAndFetch( array( 'select' => 'member_id, name', 'from' => 'members', 'where' => "members_l_display_name='{$rss_import_mid}'" ) );
					
					if ( ! $member['member_id'] )
					{
						$this->validate_errors[] = sprintf( $this->lang->words['im_nomember']. $rss_import_mid );
					}
				}
				else
				{
					$this->validate_errors[] = $this->lang->words['im_memval'];
				}					
			}
			
			/* Init forums if not already done so */
			if ( ! is_array( $this->registry->class_forums->forum_by_id ) OR !count( $this->registry->class_forums->forum_by_id ) )
			{
				$this->registry->class_forums->forums_init();
			}			
			
			if( !$standalone AND $rss_import_forum_id )
			{
				if ( ! $this->registry->class_forums->forum_by_id[ $rss_import_forum_id ] )
				{
					$this->validate_errors[] = $this->lang->words['im_noforum'];
				}
				else
				{
					if ( $this->registry->class_forums->forum_by_id[ $rss_import_forum_id ]['sub_can_post'] != 1 OR $this->registry->class_forums->forum_by_id[ $rss_import_forum_id ]['redirect_on'] == 1 )
					{
						$this->validate_errors[] = $this->lang->words['im_redforum'];
					}
				}
			}
			
			/* Display */
			if ( ! $return )
			{
				if( count( $this->validate_msg ) )
				{
					$this->registry->output->setMessage( sprintf( $this->lang->words['im_valresults'], IPSText::stripslashes( trim( $rss_import_url ) ), implode( "<br />&nbsp;&middot;", $this->validate_msg ) ), 1 );
				}
				
				if( count( $this->validate_errors ) )
				{
					$this->registry->output->global_error = sprintf( $this->lang->words['im_valerrors'], IPSText::stripslashes( trim( $rss_import_url ) ), implode( "<br />&nbsp;&middot;", $this->validate_errors ) );
				}
				
				$this->rssImportOverview();
				return;
			}
			else
			{
				return TRUE;
			}
		}	
	}
}