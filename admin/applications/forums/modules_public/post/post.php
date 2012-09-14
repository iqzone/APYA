<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Posting
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 * File Created By: Matt Mecham
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage  Forums 
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_post_post extends ipsCommand
{
	/**
	 * Post Class
	 *
	 * @var		object	Post Class
	 */
	protected $_postClass;
	
	/**
	 * Post Form Class
	 *
	 * @var		object	Post Class
	 */
	protected $_postFormClass;
	
	/**
	 * Controller run function
	 * 
	 * @param	object	Registry
	 * @return	@e void
	 */
    public function doExecute( ipsRegistry $registry )
    {
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$doCodes = array(
							'new_post'       => array( '0'  , 'new'     ),
							'new_post_do'    => array( '1'  , 'new'     ),
							'reply_post'     => array( '0'  , 'reply'   ),
							'reply_post_do'  => array( '1'  , 'reply'   ),
							'edit_post'      => array( '0'  , 'edit'    ),
							'edit_post_do'   => array( '1'  , 'edit'    )
						);
						
		$do = $this->request['do'];

		//-----------------------------------------
        // Make sure our input doCode element is legal.
        //-----------------------------------------
        
        if ( ! isset( $doCodes[ $do ] ) )
        {
        	$this->registry->getClass('output')->showError( 'posting_bad_action', 103125 );
        }
		
		//-----------------------------------------
        // Check the input
        //-----------------------------------------
        
        $this->request[ 't' ] = intval($this->request['t']);
        $this->request[ 'p' ] = intval($this->request['p']);
        $this->request[ 'f' ] = intval($this->request['f']);
        $this->request[ 'st'] = intval($this->request['st']);
       
		//-----------------------------------------
		// Grab the post class
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php' );/*noLibHook*/
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php', 'classPostForms', 'forums' );
		$this->_postClass = new $classToLoad( $registry );

		//-----------------------------------------
		// Set up some stuff
		//-----------------------------------------
		
		# IDs
		$this->_postClass->setTopicID( $this->request['t'] );
		$this->_postClass->setPostID( $this->request['p'] );
		$this->_postClass->setForumID( $this->request['f'] );
		
		# Topic Title use _POST as it is cleaned in the function.
		# We wrap this because the title may not be set when showing a form and would
		# throw a length error
		if ( $_POST['TopicTitle'] )
		{
			$this->_postClass->setTopicTitle( $_POST['TopicTitle'] );
		}
		
		# Is Preview Mode
		$this->_postClass->setIsPreview( ( $this->request['preview'] ) ? TRUE : FALSE );

		# Forum Data
		$this->_postClass->setForumData( $this->registry->getClass('class_forums')->forum_by_id[ $this->request['f'] ] );
		
		# Topic Data
		$this->_postClass->setTopicData( $this->DB->buildAndFetch( array( 
																			'select'   => 't.*, p.poll_only', 
																			'from'     => array( 'topics' => 't' ), 
																			'where'    => "t.forum_id={$this->_postClass->getForumID()} AND t.tid={$this->_postClass->getTopicID()}",
																			'add_join' => array(
																								array( 
																										'type'	=> 'left',
																										'from'	=> array( 'polls' => 'p' ),
																										'where'	=> 'p.tid=t.tid'
																									)
																								)
									) 							)	 );
		
		
		# Published
		$this->_postClass->setPublished( $doCodes[ $do ][1] );

		# Post Content
		$this->_postClass->setPostContent( isset( $_POST['Post'] ) ? $_POST['Post'] : '' );
		
		# Set Author
		$this->_postClass->setAuthor( $this->memberData['member_id'] );
	
		# Mod Options
		$this->_postClass->setModOptions( $this->request['mod_options'] );
	
		# Set Settings
		if ( ! $doCodes[ $do ][0] )
		{
			if ( $this->_postClass->getIsPreview() !== TRUE )
			{
				/* Showing form */
				$this->request['enablesig'] = ( isset( $this->request['enablesig'] ) ) ? $this->request['enablesig'] : 'yes';
				$this->request['enableemo'] = ( isset( $this->request['enableemo'] ) ) ? $this->request['enableemo'] : 'yes';
			}
		}
		
		$this->_postClass->setSettings( array( 'enableSignature' => ( $this->request['enablesig']  == 'yes' ) ? 1 : 0,
											   'enableEmoticons' => ( $this->request['enableemo']  == 'yes' ) ? 1 : 0,
											   'post_htmlstatus' => intval( $this->request['post_htmlstatus'] ),
											   'enableTracker'   => intval( $this->request['enabletrack'] ) ) );
											
		//-----------------------------------------
        // Checks...
        //-----------------------------------------
       
        $this->registry->getClass('class_forums')->forumsCheckAccess( $this->_postClass->getForumData('id'), 1, 'forum', $this->_postClass->getTopicData() );
		
        //-----------------------------------------
        // Flood check.
        //-----------------------------------------
        
        if ( $this->memberData['member_id'] )
        {
        	if (  ! in_array( $do, array( 'edit_post', 'edit_post_do', 'poll_add', 'poll_add_do' ) ) )
        	{
				if ( $this->settings['flood_control'] > 0 )
				{
					if ( $this->memberData['g_avoid_flood'] != 1 )
					{
						if ( ( time() - $this->memberData['last_post'] < $this->settings['flood_control'] ) OR time() < $this->memberData['last_post'] )
						{
							$this->registry->getClass('output')->showError( array( 'flood_control', $this->settings['flood_control'] - ( time() - $this->memberData['last_post'] ) ), 103128, null, null, 403 );
						}
					}
				}
			}
        }
        else if ( $this->member->is_not_human == 1 )
        {
        	$this->registry->getClass('output')->showError( 'posting_restricted', 103129, null, null, 403 );
        }
        
        //-----------------------------------------
        // Show form or process?
        //-----------------------------------------
        
        if ( $doCodes[ $do ][0] )
        {
        	//-----------------------------------------
        	// Make sure we have a valid auth key
        	//-----------------------------------------
        	
        	if ( $this->request['auth_key'] != $this->member->form_hash )
			{
				$this->registry->getClass('output')->showError( 'posting_bad_auth_key', 20310, null, null, 403 );
			}
			
			//-----------------------------------------
			// Guest captcha?
			//-----------------------------------------
			
			try
			{
				$this->_postClass->checkGuestCaptcha();
			}
			catch( Exception $error )
			{
				$this->_postClass->setPostError( $error->getMessage() );
				$this->showForm( $doCodes[ $do ][1] );
			}
        	
        	//-----------------------------------------
        	// Make sure we have a "Guest" Name..
        	//-----------------------------------------
        	
        	$this->_fixGuestName();
        	$this->checkDoublePost();
        	
        	$this->saveForm( $doCodes[ $do ][1] );
        }
        else
        {
        	$this->showForm( $doCodes[ $do ][1] );
        }
	}
	
	/**
	 * Save the form
	 *
	 * @param	string	Type of form to show
	 * @return	@e void
	 */
	public function saveForm( $type )
	{
		switch( $type )
		{
			case 'reply':
				try
				{
					if ( $this->_postClass->addReply() === FALSE )
					{
						$this->lang->loadLanguageFile( array('public_error'), 'core' );
						
						$this->showForm( $type );
					}
					
					$topic = $this->_postClass->getTopicData();
					$post  = $this->_postClass->getPostData();
							
					# Redirect
					if( $topic['_returnToMove'] )
					{
						ipsRegistry::getClass('output')->silentRedirect( "{$this->settings['base_url']}t={$topic['tid']}&amp;f={$topic['forum_id']}&amp;auth_key={$this->memberData['form_hash']}&amp;app=forums&amp;module=moderate&amp;section=moderate&amp;do=02" );
					}
					else if ( $this->settings['post_order_sort'] == 'desc' )
					{
						ipsRegistry::getClass('output')->silentRedirect( $this->settings['base_url']."showtopic=" . $this->_postClass->getTopicID() . "&#entry" . $this->_postClass->getPostID(), $topic['title_seo'] );
					}
					else
					{
						$permissions['softDelete']    = $this->registry->getClass('class_forums')->canSoftDeletePosts( $topic['forum_id'], $topic );
						$permissions['softDeleteSee'] = $this->registry->getClass('class_forums')->canSeeSoftDeletedPosts( $topic['forum_id'] );

						$posts = $topic['posts'] + 1;

						if( $this->moderator['post_q'] OR $this->memberData['g_is_supmod'] )
						{
							$posts += $topic['topic_queuedposts'];
						}
						
						if ( $permissions['softDelete'] AND $permissions['softDeleteSee'] )
						{
							$posts += $topic['topic_deleted_posts'];
						}

						if( ( $posts % $this->settings['display_max_posts'] ) == 0 )
						{
							$page = ( ($posts) / $this->settings['display_max_posts'] );
						}
						else
						{
							$page = ( ($posts) / $this->settings['display_max_posts'] );
							$page = ceil($page) - 1;
						}

						$page = $page * $this->settings['display_max_posts'];

						ipsRegistry::getClass('output')->silentRedirect( $this->settings['base_url']."showtopic={$topic['tid']}&st=$page&gopid={$post['pid']}&#entry{$post['pid']}", $topic['title_seo'] );
					}
				}
				catch( Exception $error )
				{
					if( $this->_postClass->getIsPreview() )
					{
						$this->showForm( $type );
					}

					$this->registry->getClass('output')->showError( $error->getMessage(), 103130, null, null, 403 );
				}
			break;
			case 'new':
				try
				{
					if ( $this->_postClass->addTopic() === FALSE )
					{
						$this->lang->loadLanguageFile( array('public_error'), 'core' );
						
						$this->showForm( $type );
					}
					
					$topic = $this->_postClass->getTopicData();
					$post  = $this->_postClass->getPostData();
					
					# Redirect
					$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."showtopic={$topic['tid']}", $topic['title_seo'] );
				}
				catch( Exception $error )
				{ 
					$language	= $this->lang->words[ $error->getMessage() ] ? $this->lang->words[ $error->getMessage() ] : $error->getMessage();
					$this->registry->getClass('output')->showError( $language, 103131, null, null, 403 );
				}
			break;
			case 'edit':
				try
				{
					if ( $this->_postClass->editPost() === FALSE )
					{
						$this->lang->loadLanguageFile( array('public_error'), 'core' );
						
						$this->showForm( $type );
					}
					
					$topic = $this->_postClass->getTopicData();
					$post  = $this->_postClass->getPostData();
					
					if( $this->request['return'] )
					{
						$_bits	= explode( ':', $this->request['return'] );
						
						if( count($_bits) AND $_bits[0] == 'modcp' )
						{
							$this->registry->output->redirectScreen( $this->lang->words['post_edited'], $this->settings['base_url'] . "app=core&amp;module=modcp&amp;fromapp=forums&amp;tab=" . $_bits[1] . 'posts' );
						}
					}
				
					# Redirect
					ipsRegistry::getClass('output')->redirectScreen( $this->lang->words['post_edited'], $this->settings['base_url'] . "showtopic={$topic['tid']}&st=" . $this->request['st'] . "#entry{$post['pid']}", $topic['title_seo'] );
					
				}
				catch( Exception $error )
				{
					$this->registry->getClass('output')->showError( $error->getMessage(), 103132, null, null, 403 );
				}
			break;
		}
	}
	
	/**
	 * Show the Form
	 *
	 * @param	string	Type of form to show
	 * @param	string	Error message
	 * @return	null	[ Returns HTML to the output engine for immediate printing ]
	 */
	public function showForm( $type )
	{
		switch( $type )
		{
			case 'reply':
				try
				{
					$this->_postClass->showReplyForm();
				}
				catch( Exception $error )
				{
					if ( $error->getMessage() == 'NO_POSTING_PPD' )
					{
						$_l = $this->_fetchPpdError();
						
						$this->registry->output->showError( $_l, 103153, null, null, 403 );
					}
					else
					{
						$this->registry->getClass('output')->showError( $this->lang->words[ $error->getMessage() ] ? $this->lang->words[ $error->getMessage() ] : $error->getMessage(), 103133, null, null, 403 );
					}
				}
			break;
			case 'new':
				try
				{
					$this->_postClass->showTopicForm();
				}
				catch( Exception $error )
				{
					if ( $error->getMessage() == 'NOT_ENOUGH_POSTS' )
					{
						$this->registry->output->showError( 'posting_not_enough_posts', 103140 );
					}
					else if ( $error->getMessage() == 'NO_POSTING_PPD' )
					{
						$_l = $this->_fetchPpdError();
						
						$this->registry->output->showError( $_l, 103153 );
					}
					else
					{
						$this->registry->getClass('output')->showError( $this->lang->words[ $error->getMessage() ] ? $this->lang->words[ $error->getMessage() ] : $error->getMessage(), 103134, null, null, 403 );
					}
				}
			break;
			case 'edit':
				try
				{
					$this->_postClass->showEditForm();
				}
				catch( Exception $error )
				{
					if ( $error->getMessage() == 'NO_POSTING_PPD' )
					{
						$_l = $this->_fetchPpdError();
						
						$this->registry->output->showError( $_l, 103153 );
					}
					else
					{
						$this->registry->getClass('output')->showError( $this->lang->words[ $error->getMessage() ] ? $this->lang->words[ $error->getMessage() ] : $error->getMessage(), 103135, null, null, 403 );
					}
				}
			break;
		}
	}
	
	/**
	 * Check for double post
	 *
	 * @return void
	 */
	public function checkDoublePost()
	{
		if ( ! $this->_postClass->getIsPreview() )
		{
			if ( time() - $this->memberData['last_post'] <= 4 )
			{
				if ( $this->request['do'] == 'new_post_do' )
				{
					/* Redirect to the newest topic in the forum */
					$forum = $this->_postClass->getForumData();

					$topic	= $this->DB->buildAndFetch( array( 
															'select' => 'tid',
															'from'   => 'topics',
															'where'  => "forum_id='" . $forum['id'] . "' AND " . $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), '' ),
															'order'  => 'last_post DESC',
															'limit'  => array( 0, 1 )
									)	);
					
					$this->registry->getClass('output')->silentRedirect( "{$this->settings['base_url']}showtopic={$topic['tid']}" );
					exit();
				}
				else
				{
					/* Verify if last post was made to this same topic
						@link	http://community.invisionpower.com/tracker/issue-25712-replying-to-two-topics-at-same-time-causing-only-one-post-to-actually-make-it-into-the-database */
					$_lastPost	= $this->DB->buildAndFetch( array( 'select' => 'topic_id', 'from' => 'posts', 'where' => 'author_id=' . $this->memberData['member_id'], 'order' => 'post_date DESC', 'limit' => array( 0, 1 ) ) );

					/* We made a reply within the last 4 seconds to this topic.. */
					if( $_lastPost['topic_id'] == $this->request['t'] )
					{
						$this->registry->getClass('output')->silentRedirect( "{$this->settings['base_url']}showtopic={$this->request['t']}&amp;view=getlastpost" );
						exit();
					}
				}
			}
		}
	}
	
	/**
	 * Check for guest's name being in use
	 *
	 * @return	@e void
	 */
	protected function _fixGuestName()
	{
		if ( ! $this->memberData['member_id'] )
		{
			$this->request['UserName'] = trim( $this->request['UserName'] );
			$this->request['UserName'] = str_replace( '<br />', '', $this->request['UserName'] );
			
			$this->request['UserName'] = $this->request['UserName'] ? $this->request['UserName'] : $this->lang->words['global_guestname'] ;
			$this->request['UserName'] = IPSText::mbstrlen( $this->request['UserName'] ) > $this->settings['max_user_name_length'] ? $this->lang->words['global_guestname'] : $this->request['UserName'];
			
		}
	}
	
	/**
	 * Fetch post per day error
	 *
	 * @return	string
	 */
	protected function _fetchPpdError()
	{
		$_g = $this->caches['group_cache'][ $this->memberData['member_group_id'] ];
		$_l = sprintf( $this->lang->words['NO_POSTING_PPD'], $_g['g_ppd_limit'] );
		
 		if ( $_g['g_ppd_unit'] )
		{
			if ( $_g['gbw_ppd_unit_type'] )
			{
				$_l .= "<br />" . sprintf( $this->lang->words['NO_PPD_DAYS'], $this->lang->getDate( ( $this->memberData['joined'] + ( 86400 * $_g['g_ppd_unit'] ) ) , 'LONG' ) );
			}
			else
			{
				$_l .= "<br />" . sprintf( $this->lang->words['NO_PPD_POSTS'], $_g['g_ppd_unit'] - $this->memberData['posts'] );
			}
		}
		
		return $_l;
	}
}