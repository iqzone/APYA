<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Posting display formatting methods
 * Last Updated: $Date: 2012-06-06 15:26:34 -0400 (Wed, 06 Jun 2012) $
 * </pre>
 * File Created By: Matt Mecham
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10878 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class classPostForms extends classPost
{	
	/**
	 * Constructor
	 *
	 * @param	object	ipsRegistry reference
	 * @return void
	 */
	public function __construct( ipsRegistry $registry )
	{
		parent::__construct( $registry );
		
		$this->lang->words['the_max_length'] = $this->settings['max_post_length'] * 1024;
	}
	
	/**
	 * Magic __call method
	 *
	 * @param	object	ipsRegistry reference
	 * @return void
	 */
	public function __call( $method, $arguments )
	{
		return parent::__call( $method, $arguments );
	}
	
	/**
	 * Displays the ajax edit box
	 *
	 * @return	string		HTML
	 */
	public function displayAjaxEditForm()
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$extraData  = array( 'isHtml' => 0 );
		$errors     = '';
		
		$this->setIsAjax( TRUE );
		
		//-----------------------------------------
		// Global checks and functions
		//-----------------------------------------
	
		try
		{
			$this->globalSetUp();
		}
		catch( Exception $error )
		{
			$e = $error->getMessage();
			
			if ( $e != 'NO_POSTING_PPD' )
			{
				throw new Exception( $e );
			}
		}
		
		//-----------------------------------------
		// Form specific...
		//-----------------------------------------
		
		try
		{
			$topic = $this->editSetUp();
		}
		catch( Exception $error )
		{
			throw new Exception( $error->getMessage() );
		}

		//-----------------------------------------
		// Appending a reason for the edit?
		//-----------------------------------------

		$extraData['showAppendEdit'] = 0;
		
		if ( $this->getAuthor('g_append_edit') )
		{
			$extraData['showEditOptions'] = 1;
			$extraData['showAppendEdit'] = 1;
			
			if ( $this->_originalPost['append_edit'] )
			{
				$extraData['checked'] = 'checked';
			}
			else
			{
				$extraData['checked'] = '';
			}
		}
		
		if ( $this->moderator['edit_post'] OR $this->getAuthor('g_is_supmod') )
		{
			$extraData['showEditOptions'] = 1;
			
			$extraData['showReason'] = 1;
		}
		
		/* Reset reason for edit */
		$extraData['reasonForEdit']	= $this->request['post_edit_reason'] ? $this->request['post_edit_reason'] : $this->_originalPost['post_edit_reason'];
		$extraData['append_edit']	= $this->request['append_edit'] ? $this->request['append_edit'] : $this->_originalPost['append_edit'];
		
		$extraData['checkBoxes'] = $this->_generateCheckBoxes( 'edit', isset( $topic['tid'] ) ? $topic['tid'] : 0, $this->getForumData('id') );

		//-----------------------------------------
		// Parse the post, and check for any errors.
		//-----------------------------------------
		
		$post        = $this->compilePostData();
		$postContent = $this->getPostContentPreFormatted() ? $this->getPostContentPreFormatted() : $this->getPostContent();
		$postContent = $this->_afterPostCompile( $postContent, 'edit' );
		
		
		//-----------------------------------------
		// Do we have any posting errors?
		//-----------------------------------------
		
		if ( $this->_postErrors )
		{
			$errors = isset($this->lang->words[ $this->_postErrors ]) ? $this->lang->words[ $this->_postErrors ] : $this->_postErrors;
		}
		
		//-----------------------------------------
		// Do we need to tell browser to load the JS?
		//-----------------------------------------
		
		$extraData['_loadJs']	= false;
		$extraData['smilies']	= null;
		
		if (  
		       ( $this->registry->permissions->check( 'reply', $this->getForumData() ) != TRUE )
		   or ( $this->getTopicData('state') == 'closed' AND !$this->memberData['g_post_closed'] )
		   //or ( $this->getTopicData('_ppd_ok') === FALSE )
		   or ( $this->getTopicData('poll_only') ) )
		{
			$extraData['_loadJs']	= true;
			$extraData['smilies']	= $this->editor->fetchEmoticons();
		}
		
		$extraData['htmlStatus'] = $this->_originalPost['post_htmlstate'];
		
		/* Set HTML status */
		if ( $this->_canHtml( $this->getForumData('id') ) && $this->_originalPost['post_htmlstate'] )
		{
			$extraData['isHtml'] = true;
		}
		
		$html = $this->registry->getClass('output')->getTemplate('editors')->ajaxEditBox( $postContent, $this->getPostID(), $errors, $extraData );
		
		return $html;
	}
	
	/**
	 * Show the reply form
	 *
	 * @param	string	Type of form (new/reply/add)
	 * @param 	array	Array of extra data
	 * @return 	void 	[Passes data to classOutput]
	 */
	protected function _displayForm( $formType, $extraData=array() )
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$output      = '';
		$titleText   = '';
		$buttonText  = '';
		$doCode      = '';
		$topText     = '';
		$checkFunc   = '';
		$autoSaveKey = '';
		
		//-----------------------------------------
		// Work out function type
		//-----------------------------------------
		
		switch( $formType )
		{
			default:
			case 'reply':
				$checkFunc  = 'replySetUp';
			break;
			case 'new':
				$checkFunc  = 'topicSetUp';
			break;
			case 'edit': 
				$checkFunc  = 'editSetUp';
			break;
		}
		
		//-----------------------------------------
		// Global checks and functions
		//-----------------------------------------
	
		try
		{
			$this->globalSetUp();
		}
		catch( Exception $error )
		{
			$e = $error->getMessage();
			
			if ( $formType == 'edit' AND $e == 'NO_POSTING_PPD' )
			{
			
			}
			else
			{
				throw new Exception( $e );
			}
		}
		
		//-----------------------------------------
		// Form specific...
		//-----------------------------------------
		
		try
		{
			$topic = $this->$checkFunc();
		}
		catch( Exception $error )
		{
			throw new Exception( $error->getMessage() );
		}

		//-----------------------------------------
		// Work out elements
		//-----------------------------------------

		switch( $formType )
		{
			default:
			case 'reply':
				$doCode      = 'reply_post_do';
				$titleText   = $this->lang->words['top_txt_reply'] . ' ' . $topic['title'];
				$buttonText  = $this->lang->words['submit_reply'];
				$topText     = $this->lang->words['replying_in'] . ' ' . $topic['title'];
				$autoSaveKey = 'reply-' . intval( $this->request['t'] );
			break;
			case 'new':
				$doCode      = 'new_post_do';
				$titleText   = $this->lang->words['top_txt_new'] . $this->getForumData('name');
				$buttonText  = $this->lang->words['submit_new'];
				$topText     = $this->lang->words['posting_new_topic'];
				$autoSaveKey = 'new-' . intval( $this->request['f'] );
				$tagBox      = '';
				
				$where       = array( 'meta_parent_id'	=> intval( $this->request['f'] ),
									  'member_id'		=> $this->memberData['member_id'],
									  'existing_tags'	=> explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) ) );
				
				if ( $this->registry->tags->can( 'add', $where ) )
				{
					$tagBox = $this->registry->tags->render('entryBox', $where);
				}
			break;
			case 'edit': 
				$doCode      = 'edit_post_do';
				$titleText   = $this->lang->words['top_txt_edit'] . ' ' . $topic['title'];
				$buttonText  = $this->lang->words['submit_edit'];
				$topText     = $this->lang->words['editing_post'] . ' ' . $topic['title'];
				$autoSaveKey = 'edit-' . intval( $this->request['p'] );
				
				$where       = array( 'meta_id'		   => $topic['tid'],
									  'meta_parent_id' => intval( $this->request['f'] ),
									  'member_id'	   => $this->memberData['member_id'] );

				if( $_REQUEST['ipsTags'] )
				{
					$where['existing_tags']	= explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) );
				}

				if ( $this->registry->tags->can( 'edit', $where ) && ( $this->request['p'] == $topic['topic_firstpost'] ) )
				{
					$tagBox = $this->registry->tags->render('entryBox', $where);
				}

				/* Are we following? */
				require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
				$_like	= classes_like::bootstrap( 'forums','topics' );
				$_likes	= $_like->isLiked( $this->getTopicData('tid'), $this->getAuthor('member_id') );

				//-----------------------------------------
				// Appending a reason for the edit?
				//-----------------------------------------
			
				if ( $this->getAuthor('g_append_edit') )
				{
					$extraData['showEditOptions'] = 1;
		
					if ( !empty( $this->_originalPost['append_edit'] ) )
					{
						$extraData['checked'] = "checked";
					}	
				}
				
				if ( !empty( $this->moderator['edit_post'] ) OR $this->getAuthor('g_is_supmod') )
				{
					$extraData['showReason'] = 1;
				}
		
				/* Reset reason for edit */
				$extraData['reasonForEdit'] = $this->request['post_edit_reason'] ? $this->request['post_edit_reason'] : $this->_originalPost['post_edit_reason'];

				/* Reset check boxes and such */
				$this->setSettings( array( 'enableSignature' => $this->_originalPost['use_sig'],
										   'enableEmoticons' => $this->_originalPost['use_emo'],
										   'post_htmlstatus' => $this->_originalPost['post_htmlstate'],
										   'enableTracker'   => ( intval($this->request['enabletrack']) != 0 ? 1 : ( $_likes ? 1 : 0 ) ) ) );
			break;
		}
		
		//-----------------------------------------
		// Parse the post, and check for any errors.
		//-----------------------------------------

		$post        = $this->compilePostData();
		$postContent = $this->getPostContentPreFormatted() ? $this->getPostContentPreFormatted() : $this->getPostContent();

		//-----------------------------------------
		// Hmmmmm....
		//-----------------------------------------
		
		$postContent = $this->_afterPostCompile( $postContent, $formType );

		//-----------------------------------------
		// Compile the poll
		//-----------------------------------------

		$this->poll_questions = $this->compilePollData();

		//-----------------------------------------
		// Are we quoting posts?
		//-----------------------------------------
		
		$postContent = $this->_checkMultiQuote( $postContent );
		
		/* Set HTML status */
		if ( $this->_canHtml( $this->getForumData('id') ) && $this->_originalPost['post_htmlstate'] )
		{
			$this->editor->setIsHtml( true );
		}

		/* Set content in editor */
		$this->editor->setContent( $postContent );
		
		//-----------------------------------------
		// Do we have any posting errors?
		//-----------------------------------------
		
		if ( $this->_postErrors )
		{
			$output .= $this->registry->getClass('output')->getTemplate('post')->errors( isset($this->lang->words[ $this->_postErrors ]) ? $this->lang->words[ $this->_postErrors ] : $this->_postErrors );
		}
		
		if ( $this->getIsPreview() )
		{
			$output .= $this->registry->getClass('output')->getTemplate('post')->preview( $this->_generatePostPreview( $this->getPostContentPreFormatted() ? $this->getPostContentPreFormatted() : $this->getPostContent(), $this->post_key ) );
		}

		/* Defaults */
		if( ! isset( $extraData['checked'] ) )
		{
			$extraData['checked'] = '';
		}
		
		//-----------------------------------------
		// Gather status messages
		//-----------------------------------------
		
		/* status from mod posts */
		$this->registry->getClass('class_forums')->checkGroupPostPerDay( $this->getAuthor(), TRUE );
		
		if ( $formType != 'edit' AND $this->registry->getClass('class_forums')->ppdStatusMessage )
		{
			$_statusMsg[] = $this->registry->getClass('class_forums')->ppdStatusMessage;
		}
		
		$modAll = FALSE;
		switch( intval( $this->getForumData('preview_posts') ) )
		{
			case 1:
				$modAll = TRUE;
			break;
			case 2:
				if ( $formType == 'new' )
				{
					$modAll = TRUE;
				}
			break;
			case 3:
				if ( $formType == 'reply' )
				{
					$modAll = TRUE;
				}
			break;
		}
				
		//-----------------------------------------
		// Load attachments so we get some stats
		//-----------------------------------------
		
		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad( $this->registry );
		$class_attach->type				= 'post';
		$class_attach->attach_post_key	= $this->post_key;
		$class_attach->init();
		$class_attach->getUploadFormSettings();
			
		//-----------------------------------------
		// START TABLE
		//-----------------------------------------

		$output .= $this->registry->getClass('output')->getTemplate('post')->postFormTemplate( array( 'title'            => $titleText,
																									  'captchaHTML'      => $this->_generateGuestCaptchaHTML(),
																									  'checkBoxes'       => $this->_generateCheckBoxes( $formType, isset( $topic['tid'] ) ? $topic['tid'] : 0, $this->getForumData('id') ),
																									  'editor'           => $this->editor->show( 'Post', array( 'autoSaveKey' => $autoSaveKey, 'height' => 350, 'warnInfo' => 'full', 'modAll' => $modAll ) ),
																									  'buttonText'       => $buttonText,
																									  'uploadForm'       => ( $this->can_upload ) ? $this->registry->getClass('output')->getTemplate('post')->uploadForm( $this->post_key, 'post', $class_attach->attach_stats, $this->getPostID(), $this->getForumData('id') ) : "",
																									  'topicSummary'     => $this->_generateTopicSummary( $topic['tid'] ),
																									  'formType'         => $formType,
																									  'extraData'        => $extraData,
																									  'modOptionsData'   => $this->_generateModOptions( $topic, $formType ),
																									  'pollBoxHTML'      => $this->_generatePollBox( $formType ),
																									  'canEditTitle'     => $this->edit_title,
																									  'topicTitle'       => $this->_topicTitle ? $this->_topicTitle : $topic['title'],
																									  'seoTopic'		 => $topic['title_seo'],
																									  'seoForum'		 => $this->getForumData('name_seo'),
																									  'statusMsg'        => $_statusMsg,
																									  'tagBox'			 => $tagBox,
																									  'socialShareOff'   => ( $formType != 'new' ) ? 1 : $this->getForumData('disable_sharelinks')
																								), 
																								array( 	'doCode' 			=> $doCode,
																									 	'p'					=> $this->getPostID(),
																										't'					=> $topic['tid'],
																										'f'					=> $this->getForumData('id'),
																										'parent'			=> ( ipsRegistry::$request['parent_id'] ? intval(ipsRegistry::$request['parent_id']) : 0 ),
																										'attach_post_key'	=> $this->post_key,
																									) );
			
		//-----------------------------------------
		// Reset multi-quote cookie
		//-----------------------------------------
		
		IPSCookie::set('mqtids', ',', 0);
		
		//-----------------------------------------
		// Send for output
		//-----------------------------------------
		
		$this->registry->getClass('output')->setTitle( $topText . ' - ' . $this->settings['board_name']);
		$this->registry->getClass('output')->addContent( $output );
		
		$this->nav = $this->registry->getClass('class_forums')->forumsBreadcrumbNav( $this->getForumData('id') );

    	if ( !empty($topic['tid']) )
    	{
    		$this->nav[] = array( $topic['title'], "showtopic={$topic['tid']}", $topic['title_seo'], 'showtopic' );
    	}

		if ( is_array( $this->nav ) AND count( $this->nav ) )
		{
			foreach( $this->nav as $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}
		
        $this->registry->getClass('output')->sendOutput();
	}

	
	/**
	 * Show the edit form
	 *
	 * @return	string	HTML to show
	 */
	public function showEditForm()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$extraData   = array();
		
		/* At this point, $this->moderator isn't set up because global set up hasn't yet been run */
		if ( $this->getAuthor('member_id') != 0 and $this->getAuthor('g_is_supmod') == 0 )
        {
			$_moderator      = $this->getAuthor('forumsModeratorData');
			$this->moderator = $_moderator[ $this->getForumID() ];
        }
		
		$this->_displayForm( 'edit', $extraData );
	}
	
	/**
	 * Show the reply form
	 *
	 * @return	string	HTML to show
	 */
	public function showReplyForm()
	{
		$this->_displayForm( 'reply' );
	}
	
	/**
	 * Show the topic form
	 *
	 * @return	string	HTML to show
	 */
	public function showTopicForm()
	{
		$this->_displayForm( 'new' );
	}
	
	/**
	 * After post compilation has taken place, we can manipulate it further
	 *
	 * @param	string	Post content
	 * @param	string	Form type (new/edit/reply)
	 * @author	MattMecham
	 */
	protected function _afterPostCompile( $postContent, $formType )
	{
		$postContent = $postContent ? $postContent : $this->_originalPost['post'];
		
		if ( $formType == 'edit' )
		{
			//-----------------------------------------
			// Unconvert the saved post if required
			//-----------------------------------------

			if ( isset($_POST['Post']) )
			{
				$postContent = IPSText::stripslashes( $_POST['Post'] );
			}
		}

		return $postContent;
	}
	
	/**
	 * Topic Summary
	 *
	 * @param	int		Topic ID
	 * @return	string	HTML elements
	 * @author 	MattMecham
	 */
	protected function _generateTopicSummary( $topicID )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cached_members = array();
		$attach_pids	= array();
		$posts          = array();
		
		//-----------------------------------------
		// CHECK
		//-----------------------------------------
		
		if ( ! $topicID )
		{
			return;
		}
		
		if ( $this->settings['disable_summary'] )
		{
			return;
		}
		
		//-----------------------------------------
		// Set ignored users
		//-----------------------------------------
		
		$ignored_users = array();
		
		foreach( $this->member->ignored_users as $_i )
		{
			if( $_i['ignore_topics'] )
			{
				$ignored_users[] = $_i['ignore_ignore_id'];
			}
		}
		
		//-----------------------------------------
		// Get the posts
		// This section will probably change at some point
		//-----------------------------------------

		$_queued	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ), 'p.' );
		
		$this->DB->build( array( 'select'   => 'p.*',
								 'from'     => array( 'posts' => 'p' ),
								 'where'    => 'p.topic_id=' . $topicID . ' AND ' . $_queued,
								 'order'    => 'p.pid DESC',
								 'limit'    => array( 0, 10 ),
								 'add_join' => array(
								   						array(	'select'	=> 'm.members_display_name, m.member_group_id, m.mgroup_others, m.member_id, m.members_seo_name',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=p.author_id',
																'type'		=> 'left' 
															),
								   						array(
																'from'		=> array( 'topics' => 't' ),
																'where'		=> 't.tid=p.topic_id',
																'type'		=> 'left' 
															),
								   						array(	'select'	=> 'f.use_ibc',
																'from'		=> array( 'forums' => 'f' ),
																'where'		=> 'f.id=t.forum_id',
																'type'		=> 'left' 
															),
														) 
						)		);
							 
		$post_query = $this->DB->execute();
		
		while ( $row = $this->DB->fetch($post_query) )
		{
		    $row['author'] = $row['members_display_name'] ? $row['members_display_name'] : $row['author_name'];
			
			$row['date']   = $this->registry->getClass('class_localization')->getDate( $row['post_date'], 'LONG' );
			
			//-----------------------------------------
			// Parse the post
			//-----------------------------------------
	
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= $row['use_emo'];
			IPSText::getTextClass( 'bbcode' )->parse_html				= $row['post_htmlstate'];
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $row['post_htmlstate'] == 2 ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $row['use_ibc'];
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];

			$row['post'] = IPSText::getTextClass('bbcode')->preDisplayParse( $row['post'] );
				
			//-----------------------------------------
			// Are we giving this bloke a good ignoring?
			//-----------------------------------------

			if ( count($ignored_users) )
			{
				if ( in_array( $row['author_id'], $ignored_users ) and $this->request['qpid'] != $row['pid'] )
				{
					if ( ! strstr( $this->settings['cannot_ignore_groups'], ','.$row['member_group_id'].',' ) )
					{
						$row['_ignored'] = 1;
					}
				}
			}
			
			$posts[ $row['pid'] ] = $row;
			
			$attach_pids[] = $row['pid'];
		}
		
		$content = $this->registry->getClass('output')->getTemplate('post')->topicSummary( $posts );
		
		//-----------------------------------------
		// Got any attachments?
		//-----------------------------------------
		
		if ( count( $attach_pids ) )
		{
			//-----------------------------------------
			// Get topiclib
			//-----------------------------------------
			
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------

				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach                  =  new $classToLoad( $this->registry );
				$this->class_attach->attach_post_key =  '';
				
				$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_topic' ), 'forums' );
			}

			$this->class_attach->attach_post_key = '';
			$this->class_attach->type            = 'post';
			$this->class_attach->init();
		
			$content = $this->class_attach->renderAttachments( $content, $attach_pids );
		}	
		
		// We need 0 and html values to avoid string offest errors
		return is_array($content) ? $content : array( 0 => array( 'html' => $content ) );
	}
	
	/**
	 * Generates checkboxes
	 *
	 * @param	string	Type of form
	 * @param	int		Topic ID
	 * @param	int		Forum ID
	 * @return	string	HTML of Checkboxes
	 */
	protected function _generateCheckBoxes($type="", $topicID="", $forumID="") 
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
	
		$options_array   = array( 0 => '', 1 => '', 2 => '' );
		$group_cache     = $this->registry->cache()->getCache('group_cache');
		$return          = array(
								  'sig'  => 'checked="checked"',
						  		  'emo'  => 'checked="checked"',
						          'html' => null,
						  		  'tra'  => ( ( $this->getAuthor('auto_track') and !$topicID ) OR $this->getSettings('enableTracker') ) ? 'checked="checked"' : ''
						        );
		
		if ( ! $this->getSettings('enableSignature') )
		{
			$return['sig'] = "";
		}
		
		if ( ! $this->getSettings('enableEmoticons') )
		{
			$return['emo'] = "";
		}
		
		if ( $this->_canHtml( $forumID ) )
		{
			$return['html'] = ( $this->getSettings('post_htmlstatus') ) ? ' checked="checked"' : '';
		}
		
		if ( $type == 'reply' )
		{
			if ( $topicID and $this->getAuthor('member_id') )
			{
				//-----------------------------------------
				// Like class
				//-----------------------------------------
				
				require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
				$_like	= classes_like::bootstrap( 'forums', 'topics' );
				$_track	= $_like->isLiked( $topicID, $this->getAuthor('member_id') );

				if ( $_track )
				{
					$return['tra'] = '-tracking-';
				}
			}
		}

		return $return;
	}
	
	/**
	 * Check to see if user can HTML
	 * @param int $forumId
	 */
	protected function _canHtml( $forumId )
	{
		return ( $this->registry->class_forums->forum_by_id[ $forumId ]['use_html'] and $this->getAuthor('g_dohtml') );
	}
	
	/**
	 * Generates mod options dropdown
	 *
	 * @param	array 	Topic data
	 * @param	string	Type of form (new/edit/reply)
	 * @return	string	HTML of dropdown box
	 */
	protected function _generateModOptions( $topic, $type='new' )
	{
		/* INIT */
		$can_close = 0;
		$can_pin   = 0;
		$can_unpin = 0;
		$can_open  = 0;
		$can_move  = 0;
		$html      = "";
		$mytimes   = array();
			
		//-----------------------------------------
		// Mod options
		//-----------------------------------------
		
		if ( $type != 'edit' )
		{
			if ( $this->getAuthor('g_is_supmod') )
			{
				$can_close = 1;
				$can_open  = 1;
				$can_pin   = 1;
				$can_unpin = 1;
				$can_move  = 1;
			}
			else if ( $this->getAuthor('member_id') != 0 )
			{
				if ( $this->moderator['mid'] != "" )
				{
					if ($this->moderator['close_topic'])
					{
						$can_close = 1;
					}
					if ($this->moderator['open_topic'])
					{
						$can_open  = 1;
					}
					if ($this->moderator['pin_topic'])
					{
						$can_pin   = 1;
					}
					if ($this->moderator['unpin_topic'])
					{
						$can_unpin = 1;
					}
					if ($this->moderator['move_topic'])
					{
						$can_move  = 1;
					}
				}
			}
			else
			{
				// Guest
				return "";
			}
			
			if ( !($can_pin == 0 and $can_close == 0 and $can_move == 0) )
			{
				$selected = ($this->getModOptions() == 'nowt') ? " selected='selected'" : '';
				
				$html = "<select id='forminput' name='mod_options' class='forminput'>\n<option value='nowt'{$selected}>".$this->lang->words['mod_nowt']."</option>\n";
			}
			
			if ($can_pin AND !$topic['pinned'] )
			{
				$selected = ($this->getModOptions() == 'pin') ? " selected='selected'" : '';

				$html .= "<option value='pin'{$selected}>".$this->lang->words['mod_pin']."</option>";
			}
			else if ($can_unpin AND $topic['pinned'] )
			{
				$selected = ($this->getModOptions() == 'unpin') ? " selected='selected'" : '';
				
				$html .= "<option value='unpin'{$selected}>".$this->lang->words['mod_unpin']."</option>";	
			}
			
			if ( $can_close AND ($topic['state'] == 'open' OR !$topic['state']) )
			{
				$selected = ($this->getModOptions() == 'close') ? " selected='selected'" : '';
				
				$html .= "<option value='close'{$selected}>".$this->lang->words['mod_close']."</option>";
			}
			else if ( $can_open AND $topic['state'] == 'closed' )
			{
				$selected = ($this->getModOptions() == 'open') ? " selected='selected'" : '';
				
				$html .= "<option value='open'{$selected}>".$this->lang->words['mod_open']."</option>";
			}
			
			if ( $can_close and $can_pin and $topic['state'] == 'open' AND !$topic['pinned'] )
			{
				$selected = ($this->getModOptions() == 'pinclose') ? " selected='selected'" : '';
				
				$html .= "<option value='pinclose'{$selected}>".$this->lang->words['mod_pinclose']."</option>";
			}
			else if( $can_open and $can_pin and $topic['state'] == 'closed' AND !$topic['pinned'] )
			{
				$selected = ($this->getModOptions() == 'pinopen') ? " selected='selected'" : '';
				
				$html .= "<option value='pinopen'{$selected}>".$this->lang->words['mod_pinopen']."</option>";
			}
			else if ( $can_close and $can_unpin and $topic['state'] == 'open' AND $topic['pinned'] )
			{
				$selected = ($this->getModOptions() == 'unpinclose') ? " selected='selected'" : '';
				
				$html .= "<option value='unpinclose'{$selected}>".$this->lang->words['mod_unpinclose']."</option>";
			}
			else if( $can_open and $can_unpin and $topic['state'] == 'closed' AND $topic['pinned'] )
			{
				$selected = ($this->getModOptions() == 'unpinopen') ? " selected='selected'" : '';
				
				$html .= "<option value='unpinopen'{$selected}>".$this->lang->words['mod_unpinopen']."</option>";
			}
			
			if ($can_move and $type != 'new' )
			{
				$selected = ($this->getModOptions() == 'move') ? " selected='selected'" : '';
				
				$html .= "<option value='move'{$selected}>".$this->lang->words['mod_move']."</option>";
			}
		}
		
		//-----------------------------------------
		// If we're replying, kill off time boxes
		//-----------------------------------------

		if ( $type == 'reply' )
		{
			$this->can_set_open_time  = 0;
			$this->can_set_close_time = 0;
		}
		else
		{
			//-----------------------------------------
			// Check dates...
			//-----------------------------------------
			
			$mytimes['open_time']  = isset($_POST['open_time_time'])  ? $_POST['open_time_time']  : '';
			$mytimes['open_date']  = isset($_POST['open_time_date'])  ? $_POST['open_time_date']  : '';
			$mytimes['close_time'] = isset($_POST['close_time_time']) ? $_POST['close_time_time'] : '';
			$mytimes['close_date'] = isset($_POST['close_time_date']) ? $_POST['close_time_date'] : '';
			
			if( $this->_originalPost['new_topic'] )
			{
				if ( empty($mytimes['open_date']) )
				{
					if ( !empty($topic['topic_open_time']) )
					{
						# Bug 23258 - add on TZ as we take this off when saving time
						$date                 = IPSTime::unixstamp_to_human( $topic['topic_open_time'] + $this->registry->class_localization->getTimeOffset() );
						$mytimes['open_date'] = sprintf("%02d/%02d/%04d", $date['month'], $date['day'], $date['year'] );
						$mytimes['open_time'] = sprintf("%02d:%02d"     , $date['hour'] , $date['minute'] );
					}
				}
				
				if ( empty($mytimes['close_date']) )
				{
					if ( !empty($topic['topic_close_time']) )
					{
						# Bug 23258 - add on TZ as we take this off when saving time
						$date                  = IPSTime::unixstamp_to_human( $topic['topic_close_time'] + $this->registry->class_localization->getTimeOffset() );
						$mytimes['close_date'] = sprintf("%02d/%02d/%04d", $date['month'], $date['day'], $date['year'] );
						$mytimes['close_time'] = sprintf("%02d:%02d"     , $date['hour'] , $date['minute'] );
					}
				}
			}
			else
			{
				if ( $type != 'new' )
				{
					$this->can_set_open_time  = 0;
					$this->can_set_close_time = 0;
				}
			}
		}
		
		return array( 'dropDownOptions' => $html,
					  'canSetOpenTime'  => $this->can_set_open_time,
					  'canSetCloseTime' => $this->can_set_close_time,
					  'myTimes'         => $mytimes );
	}

	/**
	 * Generates the captcha if required
	 *
	 * @return	string	Captcha IMG id
	 */
	protected function _generateGuestCaptchaHTML()
	{
		if ( ! $this->getAuthor('member_id') AND $this->settings['guest_captcha'] AND $this->settings['bot_antispam_type'] != 'none' )
		{
			$captchaHTML = $this->registry->getClass('class_captcha')->getTemplate();

			return $captchaHTML;
		}
	}
	
	/**
	 * Generates the poll box
	 *
	 * @param	string	Form type (new/edit/reply)
	 * @return	string	HTML
	 * @author	MattMecham
	 */
	protected function _generatePollBox( $formType )
	{
		if ( $this->can_add_poll )
		{
			//-----------------------------------------
			// Did someone hit preview / do we have
			// post info?
			//-----------------------------------------
			
			$poll_questions   = array();
			$poll_question	  = "";
			$poll_view_voters = 0;
			$poll_choices     = array();
			$show_open        = 0;
			$is_mod           = 0;
			$poll_votes		  = array();
			$poll_only	  	  = 0;
			$poll_multi		  = array();
			
			if( $this->settings['ipb_poll_only'] AND ipsRegistry::$request['poll_only'] AND ipsRegistry::$request['poll_only'] == 1 )
			{
				$poll_only = 1;
			}			
			
			if ( isset($_POST['question']) AND is_array( $_POST['question'] ) and count( $_POST['question'] ) )
			{
				foreach( $_POST['question'] as $id => $question )
				{
					$poll_questions[$id] = IPSText::parseCleanValue( $question );
				}
				
				$poll_question = ipsRegistry::$request['poll_question'];
				$show_open     = 1;
			}
			
			if ( isset($_POST['multi']) AND is_array( $_POST['multi'] ) and count( $_POST['multi'] ) )
			{
				foreach( $_POST['multi'] as $id => $checked )
				{
					$poll_multi[ $id ] = $checked;
				}
			}			
			
			if ( isset($_POST['choice']) AND is_array( $_POST['choice'] ) and count( $_POST['choice'] ) )
			{
				foreach( $_POST['choice'] as $id => $choice )
				{
					$poll_choices[ $id ] = IPSText::parseCleanValue( $choice );
				}
			}
			
			if ( $formType == 'edit' )
			{
				if ( isset( $_POST['votes'] ) && is_array( $_POST['votes'] ) and count( $_POST['votes'] ) )
				{
					foreach( $_POST['votes'] as $id => $vote )
					{
						$poll_votes[ $id ] = $vote;
					}
				}
			}			
			
			if ( $formType == 'edit' AND ( ! isset($_POST['question']) OR ! is_array( $_POST['question'] ) OR ! count( $_POST['question'] ) ) )
			{
				//-----------------------------------------
				// Load the poll from the DB
				//-----------------------------------------
				
				$this->poll_data    = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'polls', 'where' => "tid=".$this->getTopicID() ) );

				$this->poll_answers = unserialize(stripslashes($this->poll_data['choices']));
				
				if( !is_array($this->poll_answers) OR !count($this->poll_answers) )
				{
					$this->poll_answers = unserialize( preg_replace( '!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", stripslashes( $this->poll_data['choices'] ) ) );
				}
				if ( !is_array($this->poll_answers) OR !count($this->poll_answers) )
				{
					$this->poll_answers = array();
				}

        		//-----------------------------------------
        		// Lezz go
        		//-----------------------------------------
        		
        		foreach( $this->poll_answers as $question_id => $data )
        		{
        			if( !$data['question'] OR !is_array($data['choice']) )
        			{
        				continue;
        			}
        			
        			$poll_questions[ $question_id ] = $data['question'];
        			$poll_multi[ $question_id ]     = isset($data['multi']) ? intval($data['multi']) : 0;
        			
        			foreach( $data['choice'] as $choice_id => $text )
					{
						$poll_choices[ $question_id . '_' . $choice_id ] = stripslashes( $text );
						$poll_votes[ $question_id . '_' . $choice_id ]   = intval($data['votes'][ $choice_id ]);
					}
				}
				
				$poll_only = 0;
				
				if ( $this->settings['ipb_poll_only'] AND $this->poll_data['poll_only'] == 1 )
				{
					$poll_only = 1;
				}				
				
				$poll_view_voters = $this->poll_data['poll_view_voters'];
				$poll_question   = $this->poll_data['poll_question'];
				$show_open       = $this->poll_data['choices'] ? 1 : 0;
				$is_mod          = $this->can_add_poll_mod;
			}
			else
			{
				$poll_view_voters = $this->request['poll_view_voters'];
			}
			
			return $this->registry->getClass('output')->getTemplate('post')->pollBox( array(
																							'max_poll_questions'	=> $this->max_poll_questions, 
																							'max_poll_choices'		=> $this->max_poll_choices_per_question, 
																							'poll_questions'		=> IPSText::simpleJsonEncode( $poll_questions ), 
																							'poll_choices'			=> IPSText::simpleJsonEncode( $poll_choices ), 
																							'poll_votes'			=> IPSText::simpleJsonEncode( $poll_votes ), 
																							'show_open'				=> $show_open, 
																							'poll_question'			=> $poll_question, 
																							'is_mod'				=> $is_mod, 
																							'poll_multi'			=> json_encode( $poll_multi ), 
																							'poll_only'				=> $poll_only, 
																							'poll_view_voters'		=> $poll_view_voters, 
																							'poll_total_votes'		=> intval( $this->poll_data['votes'] ),
																							'poll_data'				=> $this->poll_data,
																							'poll_answers'			=> $this->poll_answers 
																					)		);
		}
		
		return '';
	}
	
	/**
	 * Show a preview of the post
	 *
	 * @param	string	Post Content
	 * @param	string	MD5 post key for attachments
	 * @return	string	HTML
	 * @author 	Matt Mecham
	 */
    protected function _generatePostPreview( $postContent="", $post_key='' )
    {
		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ) );
	
    	IPSText::getTextClass('bbcode')->parse_html				= (intval($this->request['post_htmlstatus']) AND $this->getForumData('use_html') AND $this->getAuthor('g_dohtml')) ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_nl2br			= $this->request['post_htmlstatus'] == 2 ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_smilies			= intval($this->getSettings('enableEmoticons'));
		IPSText::getTextClass('bbcode')->parse_bbcode  			= $this->getForumData('use_ibc');
		IPSText::getTextClass('bbcode')->parsing_section		= 'topics';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];
		
		# Make sure we have the pre-display look
		$postContent = IPSText::getTextClass('bbcode')->preDisplayParse( $postContent );
		
		if ( ! is_object( $this->class_attach ) )
		{
			//-----------------------------------------
			// Grab render attach class
			//-----------------------------------------

			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
			$this->class_attach = new $classToLoad( $this->registry );
		}
			
		//-----------------------------------------
		// Continue...
		//-----------------------------------------
		
		$this->class_attach->type  = 'post';
		$this->class_attach->attach_post_key = $post_key;
		$this->class_attach->init();
		
		$attachData = $this->class_attach->renderAttachments( array( 0 => $postContent ) );			
		
		return $attachData[0]['html'] . $attachData[0]['attachmentHtml'];
    }
}