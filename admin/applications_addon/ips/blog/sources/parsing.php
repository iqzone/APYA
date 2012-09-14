<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog data parsing
 * Last Updated: $Date: 2012-01-06 11:19:13 -0500 (Fri, 06 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 13 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class blogParsing
{
	/**
	* Blog data
	*
	* @access	protected
	* @var 		array
	*/
	protected $blog					= array();
	
	/**
	* Whether or not to update read markers
	*
	* @access	protected
	* @var 		boolean
	*/
	protected $update_read			= false;

	/**
	* Last time read counts were recounted
	*
	* @access	protected
	* @var 		integer
	*/
	protected $last_read_recount	= 0;

	/**
	* Array of last read timestamps
	*
	* @access	public
	* @var 		array
	*/
	public $last_read				= array();

	/**
	* Array of entries that have been read
	*
	* @access	public
	* @var 		array
	*/
	public $entries_read			= array();
	
	/**
	* Registry object
	*
	* @access	protected
	* @var		object
	*/	
	protected $registry;
	
	/**
	* Database object
	*
	* @access	protected
	* @var		object
	*/	
	protected $DB;
	
	/**
	* Settings object
	*
	* @access	protected
	* @var		object
	*/	
	protected $settings;
	
	/**
	* Request object
	*
	* @access	protected
	* @var		object
	*/	
	protected $request;
	
	/**
	* Language object
	*
	* @access	protected
	* @var		object
	*/	
	protected $lang;
	
	/**
	* Member object
	*
	* @access	protected
	* @var		object
	*/	
	protected $member;
	protected $memberData;
	
	/**
	* Cache object
	*
	* @access	protected
	* @var		object
	*/	
	protected $cache;
	protected $caches;
	
	/**
	 * Keeps an internal count of entry position
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_entryCount = -1;
	
	/**
	 * Future dates?
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_futureUpdated = false;
	
	/**
	* Constructor
	*
	* @access	public
	* @param	object		ipsRegistry reference
	* @param	array 		Blog data
	* @return	@e void
	*/	
	public function __construct( ipsRegistry $registry, $blog=array() )
	{
        /* Make registry objects */
		$this->registry		=  $registry;
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();

		if ( ! $this->registry->isClassLoaded('blogFunctions') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
			$this->registry->setClass('blogFunctions', new $classToLoad($this->registry));
		}

		$this->blogFunctions = $this->registry->getClass('blogFunctions');
		
		$this->blog          = $blog['blog_id'] ? $blog : $this->blogFunctions->getActiveBlog();

		//-----------------------------------------
		// Load the template
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
	}
	
	/**
	* Parse an entry
	*
	* @access	public
	* @param	array 		Entry data
	* @param	boolean		Do not update as read
	* @param	array		array of noParse flags: tags, cats, entryParse, noPositionInc
	* @param	array 		Array of blog data
	* @return	array 		Entry data parsed
	*/	
	public function parseEntry( $entry, $no_readupdate=false, $noParse=array(), $blog=null )
	{
		/* Set Blog */
		$blog = ( ! $blog ) ? $this->blog : ( ( is_array( $blog ) ) ? $blog : $this->blog );
		
		/* Sometimes the settings are not unserialized at this point.. */ 
		$blog['blog_settings'] = IPSLib::isSerialized($blog['blog_settings']) ? unserialize( $blog['blog_settings'] ) : $blog['blog_settings'];
		
		//-----------------------------------------
		// Update marker?
		//-----------------------------------------
		
		$entry['_lastRead'] = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'blogID' => $blog['blog_id'], 'itemID' => $entry['entry_id'] ), 'blog' );

		if ( ! $no_readupdate )
		{
			$this->registry->getClass('classItemMarking')->markRead( array( 'blogID' => $blog['blog_id'], 'itemID' => $entry['entry_id'] ), 'blog' );
		}
		
		$entry['newpost'] = ( $entry['entry_last_update'] > $entry['_lastRead'] ) ? true : false;

		//-----------------------------------------
		// Queued comments?
		//-----------------------------------------
	
		if ( $this->blogFunctions->allowApprove( $blog ) && $entry['entry_queued_comments'] )
		{
			$entry['queued_comment'] = $entry['entry_queued_comments'];
		}
		else
		{
			$entry['queued_comment'] = "";
		}
		
		/* Future date? */
		if ( ! $this->_futureUpdated AND ! $noParse['futureApprove'] AND ( $entry['entry_future_date'] AND $entry['entry_date'] <= time() ) )
		{
			$this->_futureUpdated = true;
		
			/* May as well do all blogs */
			$this->DB->update( 'blog_entries', array( 'entry_future_date' => 0, 'entry_date' => time(), 'entry_status' => 'published' ), 'entry_future_date=1 AND entry_date <=' . time() );
		}

		//-----------------------------------------
		// Some basic formatting
		//-----------------------------------------
		
		$entry['entry_num_comments']	= $this->registry->getClass('class_localization')->formatNumber($entry['entry_num_comments']);
		$entry['entry_trackbacks']		= $this->registry->getClass('class_localization')->formatNumber($entry['entry_trackbacks']);
		
		$entry['entry_day']				= gmstrftime( $this->settings['clock_joined'], $entry['entry_date'] + $this->registry->getClass('class_localization')->getTimeOffset() );
		$entry['entry_month']			= $this->lang->words[ 'M_' . gmdate( "n", $entry['entry_date'] + $this->registry->getClass('class_localization')->getTimeOffset() ) ] . " " . gmdate( "Y", $entry['entry_date'] + $this->registry->getClass('class_localization')->getTimeOffset() );
		$entry['entry_monthday']		= gmstrftime( "%d", $entry['entry_date'] + $this->registry->getClass('class_localization')->getTimeOffset() );
		$entry['_entry_date']			= $entry['entry_date'];
		$entry['entry_date']			= $this->registry->getClass('class_localization')->getDate( $entry['entry_date'] , 'LONG' );
		$entry['entry_date_short']		= $this->registry->getClass('class_localization')->getDate( $entry['_entry_date'], 'SHORT2', 1 );

		/* Publish/Draft Link */
		$entry['publish'] = "";

		if( $entry['entry_status'] == 'draft' && $this->blogFunctions->allowPublish( $blog ) )
		{
			$entry['publish'] = 'publish';
		}

		if( $entry['entry_status'] == 'published' && $this->blogFunctions->allowPublish( $blog ) )
		{
			$entry['publish'] = 'draft';
		}
		
		//-----------------------------------------
		// Feature entry link
		//-----------------------------------------
		
		$entry['allow_feature']	= 0;
		
		if( ( $this->memberData['_blogmod']['moderator_can_feature'] OR $this->memberData['g_is_supmod']) AND ( $blog['blog_view_level'] == 'public' ) AND ( ! $blog['blog_disabled'] AND $entry['entry_status'] == 'published' ) )
		{
			$entry['allow_feature'] = 1;
		}

		/* Lock/Unlock Link */
		$entry['lock']= "";

		if( ! $entry['entry_locked'] && $this->blogFunctions->allowLocking( $blog ) )
		{
			$entry['lock'] = 'lock';
		}
		if( $entry['entry_locked'] && $this->blogFunctions->allowLocking( $blog ) )
		{
			$entry['lock'] = 'unlock';
		}
		
		//-----------------------------------------
		// Trackbacks
		//-----------------------------------------
		
		$entry['send_trackback'] = false;

		if( $this->settings['blog_allow_trackbackping'] && $this->blogFunctions->ownsBlog( $blog, $this->memberData ) )
		{
			$entry['send_trackback'] = true;
		}
		
		$entry['allow_trackback']		= $this->settings['blog_allow_trackback'] && $blog['blog_settings']['allowtrackback'] ? 1 : 0;
		
		/* Excerpt - MUST come before $entry['entry'] is BBCode parsed, yo */
		$entry['entry_short'] = $this->blogFunctions->getEntryExcerpt( $entry );
	
		$entry['_hasMore']    = ( IPSText::mbstrlen( $entry['entry_short'] ) < IPSText::mbstrlen( $entry['entry'] ) ) ? 1 : 0;
		
		
		//-----------------------------------------
		// Parse the entry...
		//-----------------------------------------
		
		if ( empty( $noParse['entryParse'] ) )
		{
			IPSText::getTextClass('bbcode')->parse_html					= $entry['entry_html_state'] ? 1 : 0;
			IPSText::getTextClass('bbcode')->parse_nl2br				= $entry['entry_html_state'] == 2 ? 1 : 0;
			IPSText::getTextClass('bbcode')->parse_smilies				= $entry['entry_use_emo'] ? 1: 0;
			IPSText::getTextClass('bbcode')->parsing_section			= 'blog_entry';
			IPSText::getTextClass('bbcode')->parsing_mgroup				= $entry['member_group_id'];
			IPSText::getTextClass('bbcode')->parsing_mgroup_others		= $entry['mgroup_others'];

			$entry['entry']	= IPSText::getTextClass('bbcode')->preDisplayParse( $entry['entry'] );
		}

		/* Mod Options */
		$entry['edit_button'] = false;
		$entry['del_button']  = false;

		if( $this->blogFunctions->allowEditEntry( $blog ) )
		{
			$entry['edit_button'] = true;
		}

		if( $this->blogFunctions->allowDelEntry( $blog ) )
		{
			$entry['del_button'] = true;
		}

		if ( trim($blog['blog_settings']['eopt_mode']) == 'autohide' && ($entry['edit_button'] or $entry['del_button'] or $entry['send_trackback'] or $entry['lock'] or $entry['publish']) )
		{
			$entry['eopt_autohide'] = 1;
		}
		else
		{
			$entry['eopt_autohide'] = 0;
		}

		//-----------------------------------------
		// Tags
		//-----------------------------------------
		
		/* Categories */
		if ( empty( $noParse['cats'] ) )
		{
			/* Categories */
			$catIds               = explode( ',', IPSText::cleanPermString( $entry['entry_category'] ) );
			$entry['_categories'] = array();
			
			if ( is_array( $catIds ) AND count( $catIds ) AND count( $blog['_categories'] ) )
			{
				foreach( $catIds as $c )
				{
					if ( $blog['_categories'][ $c ] )
					{
						$entry['_categories'][ $c ] = $blog['_categories'][ $c ];
					}
				}
			}
		}
		
		//-----------------------------------------
		// Hide private entries
		//-----------------------------------------
		
		if( $blog['blog_view_level'] == 'private' && $this->memberData['member_id'] != $blog['member_id'] &&
			 !in_array($blog['blog_id'], $this->memberData['g_blog_authed_blogs']) && !$this->request['showprivate'] )
		{
			$entry['hide_private']		= 1;
		}
		else
		{
			$entry['hide_private']		= 0;
		}
		
		/* SEO Name */
		if ( $entry['entry_name'] AND  ! $entry['entry_name_seo'] )
		{
			$entry['entry_name_seo'] = IPSText::makeSeoTitle( $entry['entry_name'] );
			
			$this->DB->update( 'blog_entries', array( 'entry_name_seo' => $entry['entry_name_seo'] ), 'entry_id=' . intval( $entry['entry_id'] ) );
		}

		$entry['_blog_seo_name'] = $blog['blog_seo_name'] ? $blog['blog_seo_name'] : IPSText::makeSeoTitle( $blog['blog_name'] );
		
		/* Position */
		if ( empty( $noParse['noPositionInc'] ) )
		{
			$entry['_position']   = ++$this->_entryCount;
		}
		
		/* Rating */
		$entry['_rate_int'] = $entry['entry_rating_count'] ? round( $entry['entry_rating_total'] / $entry['entry_rating_count'], 0 ) : 0;
		
		if ( isset( $entry['blog_rating_count'] ) )
		{
			$entry['_blog_rate_int'] = $entry['blog_rating_count'] ? round( $entry['blog_rating_total'] / $entry['blog_rating_count'], 0 ) : 0;
		}
		
		/* Short */
		$entry['_entry_name_short'] = IPSText::truncate( $entry['entry_name'], 40 );
		
		return $entry;
	}

	/**
	 * Generate the Poll output
	 *
	 * @access	public
	 * @param   array	Array of entry data
	 * @return	string
	 */
	public function parsePoll( $entry )
	{
		$showResults = 0;
		$pollData    = array();
		
		//-----------------------------------------
		// Get the poll information...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'blog_polls', 'where' => 'entry_id=' . $entry['entry_id'] ) );							 
		$this->DB->execute();
		
		$poll = $this->DB->fetch();
		
		//-----------------------------------------
		// check we have a poll
		//-----------------------------------------
		
		if ( ! $poll['entry_id'] )
		{
			return;
		}
		
		//-----------------------------------------
		// Do we have a poll question?
		//-----------------------------------------
		
		if ( ! $poll['poll_question'] )
		{
			$poll['poll_question'] = $entry['entry_name'];
		}
		
		//-----------------------------------------
		// Additional Poll Vars
		//-----------------------------------------
		
		$poll['_totalVotes']  = 0;
		$poll['_memberVoted'] = 0;
		$memberChoices        = array();
		
		//-----------------------------------------
		// Have we voted in this poll?
		//-----------------------------------------
		
		$this->DB->build( array( 'select'   => 'v.*',
								 'from'     => array( 'blog_voters' => 'v' ),
								 'where'    => 'v.entry_id=' . $entry['entry_id'],
								 'add_join' => array( array( 'select' => 'm.*',
															 'from'   => array( 'members' => 'm' ),
															 'where'  => 'm.member_id=v.member_id',
															 'type'   => 'left' ) ) ) );
		$this->DB->execute();
		
		while( $voter = $this->DB->fetch() )
		{
			$poll['_totalVotes']++;
			
			if ( $voter['member_id'] == $this->memberData['member_id'] )
			{
				$poll['_memberVoted'] = 1;
			}
			
			/* Member choices */
			if ( $poll['poll_view_voters'] AND $voter['member_choices'] AND $this->settings['poll_allow_public'] )
			{
				$_choices = unserialize( $voter['member_choices'] );
				
				if ( is_array( $_choices ) AND count( $_choices ) )
				{
					$memberData = array( 'member_id'            => $voter['member_id'],
										 'members_seo_name'     => $voter['members_seo_name'],
										 'members_display_name' => $voter['members_display_name'],
										 'members_colored_name' => IPSMember::makeNameFormatted( $voter['members_display_name'], $voter['member_group_id'] ),
										 '_last'                => 0 );
					
					foreach( $_choices as $_questionID => $data )
					{
						foreach( $data as $_choice )
						{
							$memberChoices[ $_questionID ][ $_choice ][ $voter['member_id'] ] = $memberData;
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Already Voted
		//-----------------------------------------
		
		if ( $poll['_memberVoted'] )
		{
			$showResults = 1;
		}
	
		//-----------------------------------------
		// Created poll and can't vote in it
		//-----------------------------------------
		
		if ( ($poll['starter_id'] == $this->memberData['member_id']) and ($this->settings['allow_creator_vote'] != 1) )
		{
			$showResults = 1;
		}
		
		//-----------------------------------------
		// Guest, but can view results without voting
		//-----------------------------------------
		
		if ( ! $this->memberData['member_id'] AND $this->settings['allow_result_view'] )
		{
			$showResults = 1;
		}
		
		//-----------------------------------------
		// is the topic locked?
		//-----------------------------------------
		
		if( $entry['entry_locked'] )
		{
			$showResults = 1;
		}
		
		//-----------------------------------------
		// Can we see the poll before voting?
		//-----------------------------------------
		
		if ( $this->settings['allow_result_view'] == 1 AND $this->request['mode'] == 'show' )
		{
			$showResults = 1;
		}
		
		//-----------------------------------------
		// Stop the parser killing images
		// 'cos there are too many
		//-----------------------------------------
		
		$tmp_max_images			      = $this->settings['max_images'];
		$this->settings['max_images'] = 0;
		
		//-----------------------------------------
		// Parse it
		//-----------------------------------------
		
		$poll_answers 	 = unserialize(stripslashes($poll['choices']));
		
		reset($poll_answers);
		
		foreach ( $poll_answers as $id => $data )
		{
			//-----------------------------------------
			// Get the question
			//-----------------------------------------
			
			$pollData[ $id ]['question'] = $data['question'];
			
			$tv_poll = 0;
			
			# Get total votes for this question
			foreach( $poll_answers[ $id ]['votes'] as $number)
			{
				$tv_poll += intval( $number );
			}
				
			//-----------------------------------------
			// Get the choices for this question
			//-----------------------------------------
			
			foreach( $data['choice'] as $choice_id => $text )
			{
				$choiceData = array();
				$choice     = $text;
				$voters     = array();
				
				# Get total votes for this question -> choice
				$votes   = intval($data['votes'][ $choice_id ]);
				
				if ( strlen($choice) < 1 )
				{
					continue;
				}
			
				$choice = IPSText::getTextClass( 'bbcode' )->parsePollTags($choice);
				
				if ( $showResults )
				{
					$percent = $votes == 0 ? 0 : $votes / $tv_poll * 100;
					$percent = ceil( sprintf( '%.2f' , $percent ) );
					$width   = $percent > 0 ? intval($percent * 2) : 0;
				
					/* Voters */
					if ( $poll['poll_view_voters'] AND $memberChoices[ $id ][ $choice_id ] )
					{
						$voters = $memberChoices[ $id ][ $choice_id ];
						$_tmp   = $voters;
					
						$lastDude = array_pop( $_tmp );
					
						$voters[ $lastDude['member_id'] ]['_last'] = 1;
					}
					
					$pollData[ $id ]['choices'][ $choice_id ] = array( 'votes'   => $votes,
													  				   'choice'  => $choice,
																	   'percent' => $percent,
																	   'width'   => $width,
																	   'voters'  => $voters );
				}
				else
				{
					$pollData[ $id ]['choices'][ $choice_id ] =  array( 'type'   => !empty($data['multi']) ? 'multi' : 'single',
													   					'votes'  => $votes,
																		'choice' => $choice );
				}
			}
		}

		$html = $this->registry->output->getTemplate('blog_show')->pollDisplay( $poll, $entry, $pollData, $showResults );
		
		$this->settings['max_images'] = $tmp_max_images;
		
		return $html;
	}	

	/**
	* Parse a comment
	*
	* @access	public
	* @param	array 		Comment data
	* @param	integer		Last update
	* @return	array 		Comment row parsed
	*/
	public function parseComment( $row, $last_update )
	{
		$poster = array();
	
		if ( $last_update !== false )
		{
			$row['_lastRead'] = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'blogID' => $this->blog['blog_id'], 'itemID' => $row['entry_id'] ) );
		}
		
		//-----------------------------------------
		// Parse member
		//-----------------------------------------

		if( $row['member_id'] )
		{
			$poster = IPSMember::buildDisplayData( $row );
		}
		else
		{
			$poster = IPSMember::buildDisplayData( IPSMember::setUpGuest( $row['member_name'] ) );
		}

		//-----------------------------------------
		// buttons
		//-----------------------------------------

		$row['edit_button']		= false;
		$row['delete_button']	= false;
		$row['approve_button']	= false;
		$row['reply_button']	= false;

		if( $this->blogFunctions->allowEditComment( $this->blog, $row ) )
		{
			if( ! $row['entry_locked'] OR $this->blogFunctions->allowReplyClosed( $this->blog ) )
			{
				$row['edit_button']	= true;
			}
		}
		

		if ( $this->blogFunctions->allowDelComment( $this->blog ) )
		{
			$row['delete_button']	= true;
		}

		if ( !$row['comment_approved'] && $this->blogFunctions->allowApprove( $this->blog ) )
		{
			$row['approve_button']	= true;
		}

		if ( $this->memberData['g_blog_allowcomment'] && !$this->blog['blog_settings']['disable_comments'] )
		{
			$row['reply_button']	= true;
		}

		//-----------------------------------------
		// Alas, more formatting
		//-----------------------------------------
		
		$row['comment_date']	= $this->registry->getClass('class_localization')->getDate( $row['comment_date'], 'LONG' );

		/* Can we report? */
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('core') . '/sources/classes/reportLibrary.php', 'reportLibrary' );
		$reports		= new $classToLoad( $this->registry );

		$row['report_button'] = $reports->canReport( 'blog' ) && $this->memberData['member_id'];
		
		//-----------------------------------------
		// Siggie stuff
		//-----------------------------------------

		$row['signature']		= "";

		if ($poster['signature'] and $this->memberData['view_sigs'])
		{
			if ( $row['comment_use_sig'] == 1 )
			{
				IPSText::getTextClass('bbcode')->parse_html				= $this->caches['group_cache'][ $poster['member_group_id'] ]['g_dohtml'];
				IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
				IPSText::getTextClass('bbcode')->parse_nl2br			= 1;
				IPSText::getTextClass('bbcode')->parse_smilies			= 0;
				IPSText::getTextClass('bbcode')->parsing_section		= 'signature';
				IPSText::getTextClass('bbcode')->parsing_mgroup			= $poster['member_group_id'];
				IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $poster['mgroup_others'];

				$row['signature'] = $this->registry->getClass('output')->getTemplate('global')->signature_separator( IPSText::getTextClass('bbcode')->preDisplayParse($poster['signature']), $poster['member_id'], IPSMember::isIgnorable( $poster['member_group_id'], $poster['mgroup_others'] ) );
			}
		}

		//-----------------------------------------
		// Parse the comment
		//-----------------------------------------
		
		IPSText::getTextClass('bbcode')->parse_html				= $row['comment_html_state'] ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br			= $row['comment_html_state'] == 2 ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_smilies			= $row['comment_use_emo'] ? 1: 0;
		IPSText::getTextClass('bbcode')->parsing_section		= 'global_comments';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $row['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $row['mgroup_others'];
		
		$row['comment_text'] = IPSText::getTextClass('bbcode')->preDisplayParse( $row['comment_text'] );

		//-----------------------------------------
		// Post number
		//-----------------------------------------

		$this->comment_count++;

		$row['comment_count']	= intval($this->request['st']) + $this->comment_count;
		
		//-----------------------------------------
		// Ignoring the user?
		//-----------------------------------------

		foreach( $this->member->ignored_users as $_i )
		{
			if( $_i['ignore_topics'] AND $_i['ignore_ignore_id'] == $row['author_id'] )
			{
				if ( ! strstr( $this->settings['cannot_ignore_groups'], ','.$row['member_group_id'].',' ) )
				{
					$row['_ignored']	= true;
					break;
				}
			}
		}
		
		/* Reputation */
		if( $this->settings['reputation_enabled'] )
		{
			$row['pp_reputation_points'] = $row['pp_reputation_points'] ? $row['pp_reputation_points'] : 0;
			$row['has_given_rep']        = $row['has_given_rep'] ? $row['has_given_rep'] : 0;
			$row['rep_points']           = $row['rep_points'] ? $row['rep_points'] : 0;
		}
		
		/* Ignore based on rep */
		$this->memberData['_members_cache']['rep_filter'] = isset( $this->memberData['_members_cache']['rep_filter'] ) ? $this->memberData['_members_cache']['rep_filter'] : '*';

		if( $this->settings['reputation_enabled'] )
		{
			if( ! ( $this->settings['reputation_protected_groups'] && 
				    in_array( $this->memberData['member_group_id'], explode( ',', $this->settings['reputation_protected_groups'] ) ) 
				   ) &&
			 	$this->memberData['_members_cache']['rep_filter'] !== '*' 
			)
			{
				if( $this->settings['reputation_show_content'] && $row['rep_points'] < $this->memberData['_members_cache']['rep_filter'] )
				{
					$row['_repignored'] = 1;
				}
			}
		}
			
		return array( 'comment' => $row, 'poster' => $poster );
	}
	
	/**
	* Fetch a gallery album
	*
	* @access	public
	* @param	integer 	Album id
	* @return	string		Gallery album HTML to append to entry
	* @todo 	[Future] This really needs to be better abstracted to Gallery
	*/	
	public function fetchGalleryAlbum( $album_id )
	{
		if( $album_id > 0 && IPSLib::appIsInstalled('gallery') )
		{
			/* Init vars */
			$image_rows = array();
			$imagelimit	= $this->settings['blog_albumentry_limit'] ? $this->settings['blog_albumentry_limit'] : 20;
			
			/* Get main library */
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
			
			$this->albums = $this->registry->gallery->helper('albums');
			$this->images = $this->registry->gallery->helper('image');
			
			$this->DB->build( array( 
											'select'	=> '*',
											'from'	    => 'gallery_images',
											'where'  	=> "member_id = {$this->blog['member_id']} AND img_album_id={$album_id}",
											'order'	    => 'id DESC',
											'limit'  	=> array( 0, $imagelimit ) 
									)	);
			$qid = $this->DB->execute();
			
			while( $image = $this->DB->fetch( $qid ) )
			{				
				$image_rows[] = $this->images->makeImageLink( $image, array( 'type' => 'thumb' ) );
			}
			
			return $this->registry->getClass('output')->getTemplate('blog_show')->entryAlbum( $image_rows, $album_id );
		}

		return '';
	}
}