<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Posting Library: New Blog Entry
 * Last Updated: $LastChangedDate: 2012-01-06 11:19:13 -0500 (Fri, 06 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 13 $
 */

class postFunctions extends blogPost
{
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
		
		/* Set the post key */
		$this->post_key = !empty( $this->request['post_key'] ) ? $this->request['post_key'] : md5(microtime());

		/* Check permissions */
		$this->checkForNewEntry();
		
		/* Navigation */
	    $this->nav[] = array( $this->lang->words['blog_post'], '' );
	    
	    /* Load the Blog this library */
		if ( ! $this->registry->isClassLoaded('blogThis') )
		{
			$array = array( 'id1' => $this->request['id1'],
							'id2' => $this->request['id2'] );
			
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/blogthis/bt.php', 'blogthis', 'blog' );
			$this->registry->setClass( 'blogThis', new $classToLoad( $this->registry, $this->request['btapp'], $array ) );
		}
	}
	
	/**
	 * MAIN PROCESS FUNCTION
	 *
	 * @return	@e void
	 */
	public function process()
	{
		/* Compile the poll */
		$this->poll_questions = $this->compilePoll();
		
		/* Parse the post, and check for any errors. */
		$this->entry = $this->compilePost();

		/* check to make sure we have a valid topic title */
		$this->request['EntryTitle'] = trim( str_ireplace( array('<br>','<br />'), '', $this->request['EntryTitle'] ) );
		
		/* More unicode.. */
		$temp = IPSText::stripslashes( $_POST['EntryTitle'] );
		$temp = preg_replace("/&#([0-9]+);/", "-", $temp );

		if( IPSText::mbstrlen( $temp ) > 150 )
		{
			$this->obj['post_errors'] = 'topic_title_long';
		}
		if( ( IPSText::mbstrlen( $temp ) < 2) or ( ! $this->request['EntryTitle'] ) )
		{
			$this->obj['post_errors'] = 'no_entry_title';
		}

		/* If we don't have any errors yet, parse the upload */
		if( ( $this->obj['post_errors'] != "" ) or ( $this->obj['preview_post'] != "" ) )
		{
			$this->showForm();
		}
		else
		{
			$this->addNewEntry();
		}
	}
	
	/**
	 * Add the entry to the database
	 *
	 * @return	@e void
	 */
	public function addNewEntry()
	{
		/* Data Hook Location */
		IPSLib::doDataHooks( $this->entry, 'blogAddEntry' );
		
		/* Upload Image */
		if ( !empty($_FILES['entry_image']['name']) && !empty($_FILES['entry_image']['size']) && $_FILES['entry_image']['name'] != 'none' ) 
		{
			require_once( IPS_KERNEL_PATH . 'classUpload.php' );/*noLibHook*/
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
		
		/* Add entry to DB */
		$this->DB->setDataType( array( 'entry', 'entry_short' ), 'string' );
		
		$this->DB->insert( 'blog_entries', $this->entry );
		$entry_id                = $this->DB->getInsertId();
		$this->entry['entry_id'] = $entry_id;
		
		/* Add tags to DB */
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
		classes_tags_bootstrap::run( 'blog', 'entries' )->add( $_POST['ipsTags'], array(
			'meta_id'			=> $entry_id,
			'meta_parent_id'	=> $this->blog_id,
			'member_id'			=> $this->memberData['member_id'],
			'meta_visible'		=> ( $this->entry['entry_status'] == 'published' ) ? 1 : 0, 
			) );
		
		/* Sort categories */
		$this->processCategories( $this->blog_id, $entry_id, $this->entry['entry_status'], false );
		
		/* Store blog this! maps */
		$this->setBlogThis( $entry_id, $this->request['btapp'], $this->request['id1'], $this->request['id2'] );
		
		/* Rebuild */
		$this->blogFunctions->rebuildBlog( $this->blog_id );
		
		if( $this->entry['entry_status'] == 'published' )
		{
			$this->DB->update( 'blog_rsscache', array( 'rsscache_refresh' => 1 ), "blog_id in(0,{$this->blog_id})" );
			
			if( $this->settings['blog_cblock_cache'] )
			{
				$this->DB->update( 'blog_cblock_cache', array( 'cbcache_refresh' => 1 ), "blog_id={$this->blog_id} AND cbcache_key in('minicalendar','lastentries')", true );
			}
			
			$this->blogFunctions->sendBlogLikeNotifications($this->blog, $this->entry);
		}

		/* Add the poll to the polls table */
		if( count( $this->poll_questions ) AND $this->can_add_poll )
		{
			$_pollData = array( 'entry_id'      => $entry_id,
								'start_date'    => time(),
								'choices'       => serialize( $this->poll_questions ),
								'starter_id'    => $this->memberData['member_id'],
								'votes'         => 0,
								'poll_question' => $this->request['poll_question'],
								);
			
			/* Data Hook Location */
			IPSLib::doDataHooks( $_pollData, 'blogAddEntryPoll' );
			
			$this->DB->insert( 'blog_polls', $_pollData );
		}
		
		/* Make attachments "permanent" */
		$number = $this->pfMakeAttachmentsPermanent( $this->post_key, $entry_id, 'blogentry' );

		if( $number )
		{
			$this->DB->update( 'blog_entries', array( 'entry_has_attach' => $number ), 'entry_id=' . $entry_id );
		}
		
		/* Tracking all new comments? If so like the entry */
		if ( $this->blog['blog_settings']['trackcomments']  )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like = classes_like::bootstrap( 'blog', 'entries' );
			$_like->add( $entry_id, $this->memberData['member_id'], array( 'like_notify_do' => 1, 'like_notify_freq' => 'immediate' ) );
		}
 
		// Send blog pings:
		$this->blogPings( $entry_id, $this->entry );
		
		/* Update the Blog stats */
		$this->blogFunctions->rebuildStats();
		
		/* Clear Autosaved Content */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();
		$editor->removeAutoSavedContent( array( 'member_id' => $this->memberData['member_id'], 'autoSaveKey' => "blog-new-{$this->entry['blog_id']}" ) );
		
		/* Boink */		
		$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=blog&module=display&section=blog&blogid={$this->entry['blog_id']}&showentry={$entry_id}", $this->entry['entry_name_seo'], false, 'showentry' );
	}
	
	/**
	 * SHOW FORM
	 *
	 * @return	@e void
	 */
	public function showForm()
	{
		/* Init vars */
		$_entryTitle = '';
		$_blogEntry  = '';
		$_topicData  = array();
		
		/* Blog this data? */
		if ( $this->settings['blog_allow_bthis'] && isset( $this->request['btapp'] ) )
		{
			$data = $this->registry->getClass('blogThis')->fetchData();
			
			if ( is_array( $data ) )
			{
				$_entryTitle = $data['title'];
				$_blogEntry  = $data['content'];
				$_topicData  = $data['topicData'];
			}
		}
		
		/* Check for existing values */
		$entry_title         = isset($_POST['EntryTitle'])			? $this->request['EntryTitle'] : $_entryTitle;
		$entry_gallery_album = isset($_POST['entry_gallery_album'])	? intval( $this->request['entry_gallery_album'] ) : 0;
		$blogEntry			 = isset($_POST['Post'])				? $_POST['Post'] : $_blogEntry;
						
		/* Build category select box */
		$this->lang->words['blog_cats_all'] = $this->lang->words['entry_cat_none'];

		/* Show Errors */
		if( !empty( $this->obj['post_errors'] ) )
		{
			$this->output .= "<br />".$this->registry->output->getTemplate('post')->errors( $this->lang->words[ $this->obj['post_errors'] ]);
		}
		
		/* Show Preview */
		if( $this->obj['preview_post'] && isset( $this->entry['entry'] ) )
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
		$poll_box = $this->_generatePollBox('add');
				
		/* Need to show blogs? */
		$blogsdd = ( $this->_needsBlogDropDown ) ? $this->fetchPostableBlogs() : array();
		
		/* Upload */
		$upload_field = ( $this->can_upload ) ? $this->htmlBuildUploads( $this->post_key, 'blogentry', 0 ) : '';
		
		/* Posted so have selected meows? */
		if ( isset( $_POST['catCheckBoxes'] ) AND count( $_POST['catCheckBoxes'] ) )
		{
			$selCats = array_keys( $_POST['catCheckBoxes'] );
		}
		
		/* Tags */
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
		$tagsClass	= classes_tags_bootstrap::run( 'blog', 'entries' );
		$tagBox		= '';
		
		if ( $tagsClass->can( 'add', array( 'meta_parent_id' => $this->request['blogid'] ) ) )
		{
			$tagBox = $tagsClass->render( 'entryBox', array( 'meta_parent_id' => $this->request['blogid'] ) );
		}
						
		require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
		$tagBox = classes_tags_bootstrap::run( 'blog', 'entries' )->render( 'entryBox', array( 'meta_parent_id' => $this->request['blogid'] ) );
				
		/* Build the form */
		$this->output .= $this->registry->output->getTemplate( 'blog_post' )->blogPostForm( array(  array( 'do'      , 'dopost' ), 
																									array( 'post_key', $this->post_key ),
																									array( 'blogid'  , $this->request['blogid'] ),
																									array( 'btapp'   , $this->request['btapp'] ),
																									array( 'id1'     , $this->request['id1'] ),
																									array( 'id2'     , $this->request['id2'] ),
																									array( 'id3'     , $this->request['id3'] ) ),
																							"{$this->lang->words['blog_top_txt_new']} {$this->blog['blog_name']}",
																							$this->lang->words['blog_submit_new'],
																							$this->htmlDateField(),
																							array( 'TITLE'       => $entry_title,
																								   'CURRENTCATS' => IPSText::simpleJsonEncode( $this->blogFunctions->fetchBlogCategories( $this->request['blogid'], $selCats ) ) ),
																							$this->htmlAlbumField( $entry_gallery_album ),
																							$this->modOptions(),
																							$this->htmlPostBody( $blogEntry, 'blog-new-' . $this->request['blogid'] ),
																							$this->htmlNameField(),
																							$poll_box,
																							$upload_field,
																							$this->htmlCheckBoxes(),
																							$this->blogFunctions->allowPublish( $this->blog ),
																							$blogsdd,
																							array( 'bt_topicData' => $_topicData ),
																							$tagBox
																						);
		
		/* Output */
		$this->registry->output->setTitle( $this->settings['board_name'] . ' -> ' . $this->lang->words['blog_post'] . ' ' .$this->blog['blog_name'] );
		
		foreach( $this->nav as $v )
		{
			$this->registry->output->addNavigation( $v[0], $v[1], $v[2], $v[3] );
		}
		$this->registry->output->addContent( $this->output );
		$this->blogFunctions->sendBlogOutput( $this->blog, $this->lang->words['blog_top_txt_new'].' '.$this->blog['blog_name'], FALSE );
	}
	
	/**
	 * perform: Check for new entry
	 *
	 * @return	@e void
	 */
	public function checkForNewEntry()
	{
		if( ! $this->blog['allow_entry'] )
		{
            $this->registry->output->showError( 'no_blog_mod_permission', 106168, null, null, 403 );
        }
        
        if( $this->blog['blog_type'] != 'local' )
		{
            $this->registry->output->showError( 'no_blog_mod_permission', 106169, null, null, 403 );
        }
	}
	
	/**
	 * Ping-e-dee-doo
	 *
	 * @param  integer  $entry_id
	 * @param  array    $entry
	 * @return	@e void
	 */
	public function blogPings( $entry_id, $entry )
	{
		if ( $this->settings['blog_allow_pingblogs'] && is_array( $this->blog['blog_settings']['pings'] ) && count( $this->blog['blog_settings']['pings'] ) > 0 )
		{
			$ping_added = 0;
			foreach( $this->blog['blog_settings']['pings'] as $service => $enabled )
			{
				if( $enabled )
				{
					$updateping['ping_active'] = ( $entry['entry_status']=='published' ? 1 : 0 );
					$updateping['ping_time'] = time();
					$updateping['blog_id'] = $this->blog['blog_id'];
					$updateping['entry_id'] = $entry_id;
					$updateping['ping_service'] = $service;
					$this->DB->insert( 'blog_updatepings', $updateping );
					$ping_added = $updateping['ping_active'] ? 1 : 0;
				}
			}
			
			if( $ping_added )
			{
				// activate task
				$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_taskmanager.php', 'class_taskmanager' );
				$task = new $classToLoad( $this->registry );

				$this_task = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'task_manager', 'where' => "task_key='blogpings'" ) );
				$newdate = $task->generateNextRun($this_task);
				$this->DB->update( 'task_manager', array( 'task_next_run' => $newdate, 'task_enabled' => 1 ), "task_id=".$this_task['task_id'] );
				$task->saveNextRunStamp();
			}
		}
	}
}