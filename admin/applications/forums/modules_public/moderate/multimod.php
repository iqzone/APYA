<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Moderator actions
 * Last Updated: $Date: 2012-06-07 06:49:06 -0400 (Thu, 07 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10887 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_moderate_multimod extends ipsCommand
{
	/**
	 * Temporary stored output HTML
	 *
	 * @var		string
	 */
	public $output;
	
	/**
	 * Moderator function library
	 *
	 * @var		object
	 */
	protected $modLibrary;

	/**
	 * Moderator information
	 *
	 * @var		array		Array of moderator details
	 */
	protected $moderator		= array();

	/**
	 * Forum information
	 *
	 * @var		array		Array of forum details
	 */
	protected $forum			= array();

	/**
	 * Topic information
	 *
	 * @var		array		Array of topic details
	 */
	protected $topic			= array();
	
	/**
	 * Topic id
	 *
	 * @var		integer		Topic id
	 */
	protected $topic_id		= 0;

	/**
	 * Forum id
	 *
	 * @var		integer		Forum id
	 */
	protected $forum_id		= 0;

	/**
	 * Multimod id
	 *
	 * @var		integer		Multimod id
	 */
	protected $mm_id			= 0;

	/**
	 * Multimod data
	 *
	 * @var		array 		Multimod data
	 */
	protected $mm_data		= array();
	
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load modules...
		//-----------------------------------------
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_mod' ) );
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
		$this->modLibrary = new $classToLoad( $this->registry );

		//-----------------------------------------
		// Clean the incoming
		//-----------------------------------------
		
		$this->request[ 't'] =  intval($this->request['t'] );
		$this->mm_id	= intval($this->request['mm_id']);
		
		if ($this->request['t'] < 0 )
		{
			$this->registry->output->showError( 'multimod_no_topic', 103121, null, null, 404 );
		}
		
		//-----------------------------------------
		// Get the topic id / forum id
		//-----------------------------------------
		
		$this->topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => "tid=" . intval($this->request['t']) ) );
		$this->forum = $this->registry->class_forums->allForums[ $this->topic['forum_id'] ];
		
		//-----------------------------------------
		// Error out if we can not find the topic
		//-----------------------------------------
		
		if ( !$this->topic['tid'])
		{
			$this->registry->output->showError( 'multimod_no_topic', 103122, null, null, 404 );
		}
			
		//-----------------------------------------
		// Error out if we can not find the forum
		//-----------------------------------------
		
		if ( !$this->forum['id'] )
		{
			$this->registry->output->showError( 'multimod_no_topic', 103123, null, null, 404 );
		}

		//-----------------------------------------
		// Are we a moderator?
		//-----------------------------------------
		
		if ( $this->memberData['member_id'] && ! $this->memberData['g_is_supmod'] )
		{
			/**
			 * @link	http://community.invisionpower.com/tracker/issue-37736-multi-moderationsecondary-group-permissions/
			 */
			$this->moderator = empty($this->memberData['forumsModeratorData'][ $this->forum['id'] ]) ? array() : $this->memberData['forumsModeratorData'][ $this->forum['id'] ];
			
			/*$this->moderator = $this->DB->buildAndFetch( array( 'select' => '*',
																'from'   => 'moderators',
																'where'  => "forum_id LIKE '%,{$this->forum['id']},%' AND (member_id=" . $this->memberData['member_id'] . " OR (is_group=1 AND group_id='" . $this->memberData['member_group_id'] . "'))" 
															)		);*/
		}
		
		//-----------------------------------------
		// Init modfunc module
		//-----------------------------------------
		
		$this->modLibrary->init( $this->forum, $this->topic, $this->moderator );
		
		//-----------------------------------------
		// Do we have permission?
		//-----------------------------------------
		
		if ( $this->modLibrary->mmAuthorize() != TRUE )
		{
			$this->registry->output->showError( 'multimod_no_perms', 2038, null, null, 403 );
		}
		
		//-----------------------------------------
		// Get MM data
		//-----------------------------------------
		
		$this->mm_data = $this->caches['multimod'][ $this->mm_id ];
		
		if ( ! $this->mm_data['mm_id'] )
		{
			$this->registry->output->showError( 'multimod_not_found', 103124 );
		}
		
		//-----------------------------------------
		// Does this forum have this mm_id
		//-----------------------------------------
		
		if ( $this->modLibrary->mmCheckIdInForum( $this->forum['id'], $this->mm_data ) != TRUE )
		{
			$this->registry->output->showError( 'multimod_no_perms', 2039, null, null, 403 );
		}

		$this->modLibrary->stmInit();
		
		//-----------------------------------------
		// Open close?
		//-----------------------------------------
		
		if ( $this->mm_data['topic_state'] != 'leave' )
		{
			if ( $this->mm_data['topic_state'] == 'close' )
			{
				$this->modLibrary->stmAddClose();
			}
			else if ( $this->mm_data['topic_state'] == 'open' )
			{
				$this->modLibrary->stmAddOpen();
			}
		}
		
		//-----------------------------------------
		// pin no-pin?
		//-----------------------------------------
		
		if ( $this->mm_data['topic_pin'] != 'leave' )
		{
			if ( $this->mm_data['topic_pin'] == 'pin' )
			{
				$this->modLibrary->stmAddPin();
			}
			else if ( $this->mm_data['topic_pin'] == 'unpin' )
			{
				$this->modLibrary->stmAddUnpin();
			}
		}
		
		//-----------------------------------------
		// Approve / Unapprove
		//-----------------------------------------
		
		if ( $this->mm_data['topic_approve'] )
		{
			if ( $this->mm_data['topic_approve'] == 1 )
			{
				$this->modLibrary->stmAddApprove();
			}
			else if ( $this->mm_data['topic_approve'] == 2 )
			{
				$this->modLibrary->stmAddUnapprove();
			}
		}
		
		//-----------------------------------------
		// Topic title
		// Regexes clean title up
		//-----------------------------------------
		
		$title = $this->topic['title'];
		
		if ( $this->mm_data['topic_title_st'] )
		{
			$title = preg_replace( "/^" . preg_quote( $this->mm_data['topic_title_st'], '/' ) . "/", "", $title );
		}
		
		if ( $this->mm_data['topic_title_end'] )
		{
			$title = preg_replace( "/" . preg_quote( $this->mm_data['topic_title_end'], '/' ) . "$/", "", $title );
		}
		
		$this->modLibrary->stmAddTitle( IPSText::UNhtmlspecialchars( $this->mm_data['topic_title_st'] ) . $title . IPSText::UNhtmlspecialchars( $this->mm_data['topic_title_end'] ) );
		
		//-----------------------------------------
		// Update what we have so far...
		//-----------------------------------------
		
		$this->modLibrary->stmExec( $this->topic['tid'] );
		
		//-----------------------------------------
		// Add reply?
		//-----------------------------------------
		
		if ( $this->mm_data['topic_reply'] and $this->mm_data['topic_reply_content'] )
		{
			IPSText::getTextClass('bbcode')->parse_smilies			= 1;
			IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
			IPSText::getTextClass('bbcode')->parse_html				= 1;
			IPSText::getTextClass('bbcode')->parse_nl2br			= 1;
			IPSText::getTextClass('bbcode')->parsing_section		= 'topics';
		
			$this->modLibrary->topicAddReply( 
											$this->mm_data['topic_reply_content']
											, array( 0 => array( $this->topic['tid'], $this->forum['id'] ) )
											, $this->mm_data['topic_reply_postcount']
										   );
		}
		
		//-----------------------------------------
		// Move topic?
		//-----------------------------------------
		
		if ( $this->mm_data['topic_move'] )
		{
			//-----------------------------------------
			// Move to forum still exist?
			//-----------------------------------------

			$r = $this->registry->class_forums->allForums[ $this->mm_data['topic_move'] ];

			if( $r['id'] )
			{
				if ( $r['sub_can_post'] != 1 )
				{
					$this->DB->update( 'topic_mmod', array( 'topic_move' => 0 ), 'mm_id=' . $this->mm_id );
				}
				else
				{
					if ( $r['id'] != $this->forum['id'] )
					{
						$this->modLibrary->topicMove( $this->topic['tid'], $this->forum['id'], $r['id'], $this->mm_data['topic_move_link'] );
					
						$this->modLibrary->forumRecount( $r['id'] );
					}
				}
			}
			else
			{
				$this->DB->update( 'topic_mmod', array( 'topic_move' => 0 ), 'mm_id=' . $this->mm_id );
			}
		}
		
		//-----------------------------------------
		// Recount root forum
		//-----------------------------------------
		
		$this->modLibrary->forumRecount( $this->forum['id'] );
		
		$this->cache->rebuildCache( 'stats', 'global' );
		
		//-----------------------------------------
		// Add mod log
		//-----------------------------------------
		
		$this->modLibrary->addModerateLog( $this->forum['id'], $this->topic['tid'], "", $this->topic['title'], "Applied multi-mod: " . $this->mm_data['mm_title'] );
		
		//-----------------------------------------
		// Redirect back with nice fluffy message
		//-----------------------------------------
		
		$this->registry->output->redirectScreen( sprintf($this->lang->words['mm_applied'], $this->mm_data['mm_title'] ), $this->settings['base_url'] . "showforum=" . $this->forum['id'], $this->forum['name_seo'], 'showforum' );
				  
	}
}