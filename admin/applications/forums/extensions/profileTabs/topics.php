<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Profile Plugin Library
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class profile_topics extends profile_plugin_parent
{
	/**
	 * Attachment object
	 *
	 * @var		object
	 */	
	protected $attach;
	
	/**
	 * Return HTML block
	 *
	 * @param	array		Member information
	 * @return	string		HTML block
	 */
	public function return_html_block( $member=array() ) 
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content		= '';
		$last_x			= 5;
		$forum_ids		= array();
		$date_cut		= '';
		//-----------------------------------------
		// Got a member?
		//-----------------------------------------
		
		if ( ! is_array( $member ) OR ! count( $member ) )
		{
			return $this->registry->getClass('output')->getTemplate('profile')->tabNoContent( 'err_no_posts_to_show' );
		}
		
		//-----------------------------------------
		// Some words
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );

		//-----------------------------------------
		// Can view other member's topics?
		//-----------------------------------------
		
		if( !$this->memberData['g_other_topics'] AND $this->memberData['member_id'] != $member['member_id'] )
		{
			return $this->registry->getClass('output')->getTemplate('profile')->tabNoContent( 'err_no_topics_to_show' );
		}
		
		/* Get list of good forum IDs */
		$forumIdsOk = $this->registry->class_forums->fetchSearchableForumIds();
		
		//-----------------------------------------
		// Get last X posts
		//-----------------------------------------
		
		if( is_array($forumIdsOk) AND count($forumIdsOk) )
		{
			/* Init vars */
			$pids				= array();
			$parseAttachments	= false;
			
			/* Set up joins */
			$_post_joins = array( array(
										'select' => 'p.*',
										'from'   => array( 'posts' => 'p' ),
										'where'  => 't.topic_firstpost=p.pid',
										'type'   => 'left' 
									),
								 array(
										'select'	=> 'm.member_group_id, m.mgroup_others',
										'from'		=> array( 'members' => 'm' ),
										'where'		=> 'm.member_id=p.author_id',
										'type'		=> 'left' 
									) );
			
			/* Cache? */
			if ( IPSContentCache::isEnabled() )
			{
				if ( IPSContentCache::fetchSettingValue('post') )
				{
					$_post_joins[] = IPSContentCache::join( 'post', 'p.pid' );
				}
			}
			
			if ( $this->settings['search_ucontent_days'] )
			{
				$date_cut = ( $this->memberData['last_post'] ? $this->memberData['last_post'] : time() ) - 86400 * intval( $this->settings['search_ucontent_days'] );
				$date_cut = ' AND t.start_date > ' . $date_cut;
			}
			
			$_approved	= $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'visible' ), 't.' );
			
			$this->DB->build( array( 'select'	=> 't.*',
									 'from'		=> array( 'topics' => 't' ),
									 'where'	=> "t.starter_id={$member['member_id']} AND {$_approved} AND t.forum_id IN (" . implode( ",", $forumIdsOk ) . ") " . $date_cut,
									'order'		=> 't.start_date DESC',
									'limit'		=> array( 0, $last_x ),
									'add_join'	=> $_post_joins ) );
			$o = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $o ) )
			{
				//-----------------------------------------
				// Ghost topics... BOO
				//-----------------------------------------
				
				if( !$row['pid'] )
				{
					continue;
				}
				
				$pids[ $row['pid'] ]	= $row['pid'];
				
				if( $row['topic_hasattach'] )
				{
					$parseAttachments	= true;
				}
				
				if ( ! $row['cache_content'] )
				{
					IPSText::getTextClass( 'bbcode' )->parse_smilies			= $row['use_emo'];
					IPSText::getTextClass( 'bbcode' )->parse_html				= ( $row['use_html'] and $this->caches['group_cache'][ $row['member_group_id'] ]['g_dohtml'] and $row['post_htmlstate'] ) ? 1 : 0;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $row['post_htmlstate'] == 2 ? 1 : 0;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];
					
					$row['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['post'] );
					
					IPSContentCache::update( $row['pid'], 'post', $row['post'] );
				}
				else
				{
					$row['post'] = $row['cache_content'];
				}

				$row['_post_date']  = ipsRegistry::getClass('class_localization')->getDate( $row['post_date'], 'SHORT' );
				$row['_raw_date']   = $row['post_date'];
				$row['_date_array'] = IPSTime::date_getgmdate( $row['post_date'] + ipsRegistry::getClass( 'class_localization')->getTimeOffset() );
				
				$row['post'] .= "\n<!--IBF.ATTACHMENT_". $row['pid']. "-->";
				
				$url	= $this->registry->output->buildSEOUrl( "showtopic={$row['topic_id']}&amp;view=findpost&amp;p={$row['pid']}", 'public', $row['title_seo'], 'showtopic' );
				
				$content .= $this->registry->getClass('output')->getTemplate('profile')->tabSingleColumn( $row, $this->lang->words['profile_read_topic'], $url, $row['title'] );
			}
			
			//-----------------------------------------
			// Attachments (but only if necessary)
			//-----------------------------------------
			
			if ( $parseAttachments AND !is_object( $this->class_attach ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach        = new $classToLoad( $this->registry );
	
				$this->class_attach->type  = 'post';
				$this->class_attach->init();
				
				if ( IPSMember::checkPermissions('download') === false )
				{
					$this->settings['show_img_upload'] = 0;
				}
				
				$content = $this->class_attach->renderAttachments( $content, $pids );
				$content = $content[0]['html'];
			}
	
			//-----------------------------------------
			// Macros...
			//-----------------------------------------
			
			$content = $this->registry->output->replaceMacros( $content );
		}
		
		//-----------------------------------------
		// Return content..
		//-----------------------------------------
		
		return $content ? $this->registry->getClass('output')->getTemplate('profile')->tabTopics( $content ) : $this->registry->getClass('output')->getTemplate('profile')->tabNoContent( 'err_no_topics_to_show' );
	}
}