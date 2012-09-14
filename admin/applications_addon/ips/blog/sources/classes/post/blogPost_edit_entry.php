<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Posting Library: Edit Blog Entry
 * Last Updated: $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 4 $
 */

class postFunctions extends blogPost
{
	/**
	 * Array of entry data
	 *
	 * @var array
	 */	
	public $org_entry 		= array();
	
	/**
	 * Array of entry data
	 *
	 * @var array
	 */	
	public $entry     		= array();
	
	/**
	 * Unique MD5 hash for this post
	 *
	 * @var string
	 */
	public $post_key = "";	
	
	/**
	 * Array of poll data
	 *
	 * @var array
	 */
	public $poll_questions	= array();
	
	/**
	 * CONSTRUCTOR
	 *
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Parent contructor */
		parent::__construct( $registry );
		
		/* Check ID */
		$eid = intval( $this->request['eid'] );
				
		if( ! $eid )
		{
			$this->registry->output->showError( 'missing_files', 106163, null, null, 404 );
		}
		
		/* Get the original entry */
		$this->org_entry = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_entries', 'where' => "entry_id={$eid}" ) );
		
		if( !$this->org_entry['entry_id'] )
		{
			$this->registry->output->showError( 'missing_files', 106164, null, null, 404 );
		}

		if( $this->org_entry['blog_id'] != $this->blog['blog_id'] )
		{
			$this->registry->output->showError( 'missing_files', 106165, null, null, 404 );
		}		
		
		/* Set the post key */
		$this->post_key = $this->org_entry['entry_post_key'];

		/* Check permissions */
		$this->checkForEditEntry();
		
		/* Navigation */
		$this->nav[] = array( $this->org_entry['entry_name'], "app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->blog['blog_id']}&amp;showentry={$this->org_entry['entry_id']}", $this->org_entry['entry_name_seo'], 'showentry' );
	    $this->nav[] = array( $this->lang->words['blog_editentry'], '' );
	}
	
	/**
	 * MAIN PROCESS FUNCTION
	 *
	 * @return	@e void
	 */
	public function process()
	{
		/* Parse the post, and check for any errors. */
		$this->entry = $this->compilePost();
		
		/* Compile the poll */
		$this->poll_questions = $this->compilePoll();

		/* check to make sure we have a valid topic title */
		$this->request['EntryTitle'] = str_replace( "<br>", "", $this->request['EntryTitle'] );

		$this->request['EntryTitle'] = trim($this->request['EntryTitle']);

		if( ( IPSText::mbstrlen( $this->request['EntryTitle'] ) < 2 ) or ( ! $this->request['EntryTitle'] ) )
		{
			$this->obj['post_errors'] = 'no_topic_title';
		}
		
		/* More unicode.. */
		$temp = IPSText::stripslashes( $_POST['EntryTitle'] );
		$temp = preg_replace("/&#([0-9]+);/", "-", $temp );

		if( IPSText::mbstrlen( $temp ) > 150 )
		{
			$this->obj['post_errors'] = 'topic_title_long';
		}
		
		if( ( $this->obj['post_errors'] != "" ) or ( $this->obj['preview_post'] != "" ) )
		{
			$this->showForm();
		}
		else
		{
			$this->completeEdit();
		}
	}
	
	/**
	 * Complete Edit
	 *
	 * @return	@e void
	 */
	public function completeEdit()
	{
		/* Time */
		$time = $this->registry->class_localization->getDate( time(), 'LONG' );

		/* Reset some data */
		$this->entry['entry_author_id']		= $this->org_entry['entry_author_id'];
		$this->entry['entry_author_name']	= $this->org_entry['entry_author_name'];		
		$this->entry['entry_last_update']	= $this->org_entry['entry_last_update'];
		$this->entry['entry_edit_time']		= time();
		$this->entry['entry_edit_name'] 	= $this->memberData['members_display_name'];
		$this->entry['entry_poll_state']	= $this->org_entry['entry_poll_state'];
		
		/* Image */
		if ( $this->request['image_change'] )
		{
			$this->entry['entry_image'] = '';
			
			if ( $_FILES['entry_image']['name'] )
			{
				require_once IPS_KERNEL_PATH . 'classUpload.php';/*noLibHook*/
				$upload = new classUpload();
				$upload->upload_form_field = 'entry_image';
				$upload->allowed_file_ext = $upload->image_ext;
				$upload->out_file_dir = $this->settings['upload_dir'];
				$upload->out_file_name = 'blog-'. str_replace( array( '.', ' ' ), '', microtime() );
				$uploadSuccess = $upload->process();
				if ( $uploadSuccess === FALSE )
				{
					switch( $upload->error_no )
					{
						case 1:
							// No upload
							$this->obj['post_errors'] = 'upload_failed';
							break;
						case 2:
							// Invalid file ext
							$this->obj['post_errors'] = 'invalid_mime_type';
							break;
						case 3:
							// Too big...
							$this->obj['post_errors'] = 'upload_too_big';
							break;
						case 4:
							// Cannot move uploaded file
							$this->obj['post_errors'] = 'upload_failed';
							break;
						case 5:
							// Possible XSS attack (image isn't an image)
							$this->obj['post_errors'] = 'upload_failed';
							break;
					}
	
					return $this->showForm();
				}
				$file = $upload->parsed_file_name;
				
				/* Generate thumbnail */
				require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
				require_once( IPS_KERNEL_PATH . 'classImageGd.php' );/*noLibHook*/
				$image = new classImageGd();
				$image->init( array( 'image_path' => $this->settings['upload_dir'], 'image_file' => $file ) );
				$dims = IPSLib::scaleImage( array( 'cur_width' => $image->cur_dimensions['width'], 'cur_height' => $image->cur_dimensions['height'], 'max_width' => 100, 'max_height' => 100 ) );
				$thumb_data = $image->resizeImage( $dims['img_width'], $dims['img_height'] );
				$image->writeImage( $this->settings['upload_dir'] . '/thumb_' . $upload->parsed_file_name );
				
				$this->entry['entry_image'] = $file;
			}
		}
		
		/* Poll */
		if( $this->can_add_poll )
		{
			if( $this->org_entry['entry_poll_state'] )
			{
				if( is_array( $this->poll_questions ) AND count( $this->poll_questions ) )
				{
					if( ! ( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderate_can_edit_entry'] ) )
					{
						$row = $this->DB->buildAndFetch( array ('select' => 'choices', 'from' => 'blog_polls', 'where' => 'entry_id='.$this->org_entry['entry_id'] ) );
						$oldchoices = unserialize( $row['choices'] );
						foreach( $this->poll_questions as $id => $question )
						{
							$this->poll_questions[$id]['votes'] = $oldchoices[$id]['votes'];
						}
					}
					
					$_pollData = array( 'votes'         => intval( $this->poll_total_votes ),
										'choices'       => serialize( $this->poll_questions ),
										'poll_question' => $this->request['poll_question']
										);
					
					/* Data Hook Location */
					IPSLib::doDataHooks( $_pollData, 'blogEditEntryUpdatePoll' );
					
					$this->DB->update( 'blog_polls', $_pollData, 'entry_id='.$this->org_entry['entry_id'] );
				}
				else
				{
					/* Remove the poll */
					$this->DB->delete( 'blog_polls', 'entry_id='.$this->org_entry['entry_id'] );
					$this->DB->delete( 'blog_voters', 'entry_id='.$this->org_entry['entry_id'] );
					$this->entry['entry_poll_state']	= 0;
					$this->entry['entry_last_vote']		= 0;
				}
			}
			else
			{
				if( is_array( $this->poll_questions ) AND count( $this->poll_questions ) )
				{
					$this->entry['entry_poll_state'] = 1;
					
					$_pollData = array( 'entry_id'      => $this->org_entry['entry_id'],
										'start_date'    => time(),
										'choices'       => serialize( $this->poll_questions ),
										'starter_id'    => $this->memberData['member_id'],
										'votes'         => 0,
										'poll_question' => $this->request['poll_question'],
										);
					
					/* Data Hook Location */
					IPSLib::doDataHooks( $_pollData, 'blogEditEntryAddPoll' );
					
					$this->DB->insert( 'blog_polls', $_pollData );
				}
			}
		}

		/* Add tags to DB */
		if ( isset($_POST['ipsTags']) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			
			$tagreturn = classes_tags_bootstrap::run( 'blog', 'entries' )->replace( $_POST['ipsTags'], array(
				'meta_id'			=> $this->org_entry['entry_id'],
				'meta_parent_id'	=> $this->blog_id,
				'member_id'			=> $this->memberData['member_id'],
				'meta_visible'		=> ( $this->entry['entry_status'] == 'published' ) ? 1 : 0, 
				) );
		}
		
		/* Sort categories */
		$this->processCategories( $this->blog_id, $this->org_entry['entry_id'], $this->entry['entry_status'], false );
		
		/* Make attachments "permanent" */
		$number = $this->pfMakeAttachmentsPermanent( $this->post_key, $this->org_entry['entry_id'], 'blogentry' );

		if( $number )
		{
			$this->entry['entry_has_attach'] = $number;
		}
		
		/* Data Hook Location */
		IPSLib::doDataHooks( $this->entry, 'blogEditEntryData' );
		
		$this->DB->setDataType( array( 'entry', 'entry_short' ), 'string' );
		
		/* Update entry in DB */
		$this->DB->update( 'blog_entries', $this->entry, 'entry_id='.$this->org_entry['entry_id'] );

		/* Rebuild the Blog */
		$this->blogFunctions->rebuildBlog( $this->entry['blog_id'] );
		
		if( $this->org_entry['blog_id'] != $this->entry['blog_id'] )
		{
			$this->blogFunctions->rebuildBlog( $this->org_entry['blog_id'] );
		}
		
		/* Did we publish it? */
		if( $this->org_entry['entry_status'] == 'draft' && $this->entry['entry_status'] == 'published' )
		{
			/* Update the RSS Cache */
			$this->DB->update( 'blog_rsscache', array( 'rsscache_refresh' => 1 ), "blog_id in(0,{$this->blog_id})" );
			
			/* Content block cache */
			if( $this->settings['blog_cblock_cache'] ) 
			{
				$this->DB->update( 'blog_cblock_cache', array( 'cbcache_refresh' => 1 ), "blog_id={$this->blog_id} AND cbcache_key in('minicalendar','lastentries','lastcomments')", true );
			}

			/* Send out tracker mails */
			$this->blogFunctions->sendBlogLikeNotifications($this->blog, array_merge( $this->entry, array( 'entry_id' => $this->org_entry['entry_id'] ) ) );
			
			// Active the tracker pings
			$pings = $this->DB->buildAndFetch( array( 'select' => 'count(*) as to_do', 'from' => 'blog_updatepings', 'where' => "blog_id = {$this->entry['blog_id']} AND entry_id = {$this->org_entry['entry_id']}" ) );
			if ( $pings['to_do'] )
			{
				$this->DB->update( 'blog_updatepings', array( 'ping_active' => 1 ), "blog_id = {$this->entry['blog_id']} AND entry_id = {$this->org_entry['entry_id']}" );
	
				// activate task
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_taskmanager.php', 'class_taskmanager' );
				$task = new $classToLoad( $this->registry );
	
				$this_task = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_key='blogpings'" ) );
				$newdate = $task->generateNextRun($this_task);
				$this->DB->update( 'task_manager', array( 'task_next_run' => $newdate, 'task_enabled' => 1 ), "task_id=".$this_task['task_id'] );
				$task->saveNextRunStamp();
			}
		}
		
		/* Rebuild all stats */
		$this->blogFunctions->rebuildStats();

		/* Clear Autosaved Content */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();
		$editor->removeAutoSavedContent( array( 'member_id' => $this->memberData['member_id'], 'autoSaveKey' => "blog-edit-{$this->org_entry['entry_id']}" ) );
		
		/* Boink */		
		$this->registry->output->redirectScreen( $this->lang->words['entry_edited'], $this->settings['base_url'] . "app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->entry['blog_id']}&amp;showentry={$this->org_entry['entry_id']}", IPSText::makeSeoTitle( $this->org_entry['entry_name'] ), 'showentry' );
	}

	/**
	 * SHOW FORM
	 *
	 * @return	@e void
	 */
	public function showForm()
	{
		/* INIT */
		$raw_post = isset($_POST['Post']) ? $_POST['Post'] : $this->org_entry['entry'];

		/* Setup bbcode parser */
		IPSText::getTextClass('bbcode')->parse_html		= ( intval($this->org_entry['entry_htmlstatus']) AND $this->memberData['g_blog_do_html'] ) ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_nl2br	= $this->org_entry['entry_html_status'] == 2 ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_smilies	= $this->org_entry['entry_use_emo'] ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_bbcode	= 1;
		IPSText::getTextClass('bbcode')->parsing_section= 'blog_entry';
		
		/* Build Tags */
		$this->lang->words['blog_cats_all'] = $this->lang->words['entry_cat_none'];
		
		/* Default Data */
		$entry_title         = isset( $_POST['EntryTitle'] )          ? $this->request['EntryTitle']                    : $this->org_entry['entry_name'];
		$entry_gallery_album = isset( $_POST['entry_gallery_album'] ) ? intval( $this->request['entry_gallery_album'] ) : $this->org_entry['entry_gallery_album'];
		$entry_status        = isset( $_POST['mod_options'] )         ? $this->request['mod_options']                   : $this->org_entry['entry_status'];
		
		/* Errors */
		if( !empty( $this->obj['post_errors'] ) )
		{
			$this->output .= "<br />".$this->registry->output->getTemplate( 'post' )->errors( $this->lang->words[ $this->obj['post_errors'] ]);
		}
		
		/* Preview Post */
		if( $this->obj['preview_post'] )
		{
			$preview = $this->entry['entry'];
		
			/* Parse Attachments */
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/attach/class_attach.php', 'class_attach' );
			$this->class_attach       = new $classToLoad( $this->registry );
			$this->class_attach->type = 'blogentry';
			$this->class_attach->init();
	    
			$_output = $this->class_attach->renderAttachments( $preview, array( 0 ), 'blog_show' );
			$preview = $_output[0]['html'];
			
			if( $_output[0]['attachmentHtml'] )
			{
				$this->output = str_replace( '<!--IBF.ATTACHMENT_' . 0 . '-->', $_output[ 0 ]['attachmentHtml'], $preview );
			}
			if ( stristr( $preview, '[attach' ) )
			{
				$preview = preg_replace_callback( "#\[attachment=(.+?):(.+?)\]#", array( $this->registry->output->getTemplate('blog_global'), 'short_attach_tag' ), $preview );
			}

			$this->output .= "<br />".$this->registry->output->getTemplate('post')->preview( $this->showPostPreview( $preview ) );
		}
		
		/* Polls */
		$poll_box = $this->_generatePollBox('edit');
		
		/* HTML Status */
		$this->request['post_htmlstatus']	= isset($this->request['post_htmlstatus']) ? $this->request['post_htmlstatus'] : $this->org_entry['entry_html_state'];
		$this->request['enableemo']			= isset($this->request['enableemo']) ? $this->request['enableemo'] : $this->org_entry['entry_use_emo'];
		
		/* Upload */
		$upload_field = $this->can_upload ? $this->htmlBuildUploads( $this->post_key, 'blogentry', $this->org_entry['entry_id'] ) : '';
		
		/* Tags */
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
		$tagsClass	= classes_tags_bootstrap::run( 'blog', 'entries' );
		$tagBox		= '';
		
		if ( $tagsClass->can( 'edit', array( 'meta_id' => $this->org_entry['entry_id'] ) ) )
		{
			$tagBox = $tagsClass->render( 'entryBox', array( 'meta_id' => $this->org_entry['entry_id'] ) );
		}
		
		/* Build the form */
		$this->output .= $this->registry->output->getTemplate( 'blog_post' )->blogPostForm( array(  array( 'do'      , 'doeditentry' ), 
																									array( 'eid'     , $this->org_entry['entry_id'] ), 
																									array( 'st'      , $this->request['st'] ), 
																									array( 'post_key', $this->post_key ) ,
																									array( 'blogid'  , $this->request['blogid'] )
																								  ),
																							"{$this->lang->words['blog_top_txt_edit']} {$this->org_entry['entry_name']}",
																							$this->lang->words['blog_submit_edit'],
																							$this->htmlDateField( $this->org_entry['entry_date'] ),
																							array( 'TITLE'       => $entry_title,
																								   'CURRENTCATS' => IPSText::simpleJsonEncode( $this->blogFunctions->fetchBlogCategories( $this->request['blogid'], explode( ',', IPSText::cleanPermString( $this->org_entry['entry_category'] ) ) ) ) ),
																							$this->htmlAlbumField( $entry_gallery_album ),
																							$this->modOptions( $entry_status ),
																							$this->htmlPostBody( $raw_post, 'blog-edit-' . $this->org_entry['entry_id'] ),
																							$this->htmlNameField(),
																							$poll_box,
																							$upload_field,
																							$this->htmlCheckBoxes(),
																							$this->blogFunctions->allowPublish( $this->blog ),
																							'',
																							array(),
																							$tagBox,
																							$this->org_entry['entry_image']
																						);		

		/* Output */
		$this->registry->output->setTitle( $this->settings['board_name'] . ' -> ' . $this->lang->words['blog_editentry'] . ' ' .$this->blog['blog_name'] );
		
		foreach( $this->nav as $v )
		{
			$this->registry->output->addNavigation( $v[0], $v[1], $v[2], $v[3] );
		}
		$this->registry->output->addContent( $this->output );
		$this->blogFunctions->sendBlogOutput( $this->blog, $this->lang->words['blog_top_txt_edit'].' '.$this->org_entry['entry_name'], FALSE );
	}
	
	/**
	 * perform: Check for edit entry
	 *
	 * @param  array  $entry
	 * @return	@e void
	 */
	public function checkForEditEntry( $entry=array() )
	{
		if( ! $this->blogFunctions->allowEditEntry( $this->blog ) )
		{
			$this->registry->output->showError( 'no_blog_mod_permission', 106166, null, null, 403 );
		}
	}
}