<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Reputation configuration for application
 * Last Updated: $Date: 2012-06-04 08:10:35 -0400 (Mon, 04 Jun 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10861 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class reputation_forums
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		/* Init attachments */
		$classname = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$this->class_attach = new $classname( ipsRegistry::instance() );

		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_topic' ), 'forums' );

		$this->class_attach->type = 'post';
		$this->class_attach->init();
		$this->class_attach->getUploadFormSettings();

		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
		ipsRegistry::setClass( 'repCache', new $classToLoad() );
	}
	
	/**
	 * Database Query to get results
	 *
	 * @param	string	'given' if we want to return items as user has liked
	 *					'received' if we want to fetch items a user has posted that others have liked
	 					'most' if we want to fetch 'most' repped items
	 * @param	array	Member this applies to
	 *
	 * @return	array	Parameters to pass to ipsRegistry::DB()->build
	 *					Must return at least the data from reputation_index
	 *					limit and order will be applied automatically
	 */
	public function fetch( $type=NULL, $member=NULL )
	{
		$allowedForumIDs = ipsRegistry::getClass('class_forums')->fetchSearchableForumIds();
		
		$userGiving = 'r';
		$extraWhere = '';
		$extraJoin = array();
		
		if ( $type == 'most' )
		{
			return array( 'type'   => 'pid',
						  
						  /* Used in first query to fetch type_ids */
						  'inner'  => array( 'select'   => 'p.pid',
						  					'from'     => array( 'posts' => 'p' ),
						  					//'where'    => '1=1',
						  					'where'    => 'p.queued=' . ipsRegistry::getClass('class_forums')->fetchPostHiddenFlag( 'visible' ),
						  					'add_join' => array( array( 'select' => '',
						  												'from'   => array( 'topics' => 't' ),
						  												'where'  => 't.tid=p.topic_id AND t.approved=' . ipsRegistry::getClass('class_forums')->fetchTopicHiddenFlag( 'visible' ) . ' AND ' . ( empty( $allowedForumIDs ) ? '1=0' : ipsRegistry::DB()->buildWherePermission( $allowedForumIDs, 't.forum_id', FALSE ) ),
																		'type'   => 'inner' ) ) # join must be INNER to prevent loading ALL the post IDs without a matching topic row
											),
						 /* Used in second query to fetch actual data */
						 'joins'   => array( array( 'select'    => 'rc.*',
						 							'from'		=> array( 'reputation_cache' => 'rc' ),
												    'where'		=> "rc.app='forums' AND rc.type='pid' AND rc.type_id=r.type_id" ),
											 array( 'select'    => 'p.*',
											 		'from'		=> array( 'posts' => 'p' ),
												    'where'		=> 'r.type_id=p.pid' ),
											 array( 'select'    => 't.*, t.title as topic_title',
											 		'from'		=> array( 'topics' => 't' ),
												    'where'		=> 'p.topic_id=t.tid' ),
											 array( 'select'    => 'f.*',
											 		'from'		=> array( 'forums' => 'f' ),
												    'where'		=> 'f.id=t.forum_id' ) )
											);
		}
		else
		{
			if ( $type !== NULL )
			{
				$extraWhere = ( ( $type == 'given' ) ? "r.member_id={$member['member_id']}" : "p.author_id={$member['member_id']}" ) . ' AND ';
			}
			else
			{
				$userGiving = 'r2';
				$extraJoin = array( array(
						'from'		=> array( 'reputation_index' => 'r2' ),
						'where'		=> "r2.app=r.app AND r2.type=r.type AND r2.type_id=r.type_id AND r2.member_id=" . ipsRegistry::member()->getProperty('member_id')
					) );
			}
			
			return array(
				'select'	=> "r.*, rc.*, {$userGiving}.member_id as repUserGiving, p.*, t.*, t.title as topic_title", // we have to do aliases on some of them due to duplicate column names
				'from'		=> array( 'reputation_index' => 'r'),
				'add_join'	=> array_merge( $extraJoin, array(
					array(
						'from'		=> array( 'reputation_cache' => 'rc' ),
						'where'		=> "rc.app=r.app AND rc.type=r.type AND rc.type_id=r.type_id"
						),
					array(
						'from'		=> array( 'posts' => 'p' ),
						'where'		=> 'r.type_id=p.pid'
						),
					array(
						'from'		=> array( 'topics' => 't' ),
						'where'		=> 'p.topic_id=t.tid'
						)
					) ),
				'where'		=>	"r.app='forums' AND r.type='pid' AND " . // belongs to this app
								$extraWhere . // is for this member
								" t.tid<>0 AND " . // is valid
								"t.approved=" . ipsRegistry::getClass('class_forums')->fetchTopicHiddenFlag( 'visible' ) . " AND " . // topic is visible
								"p.queued=" . ipsRegistry::getClass('class_forums')->fetchPostHiddenFlag( 'visible' ) . " AND " . // post is visible
								( empty( $allowedForumIDs ) ? '1=0' : ipsRegistry::DB()->buildWherePermission( $allowedForumIDs, 't.forum_id', FALSE ) ), // we have permission to view
				);
		}
	}
	
	/**
	 * Process Results
	 *
	 * @param	array	Row from database using query specified in fetch()
	 * @return	array	Same data with any additional processing necessary
	 */
	public function process( $row )
	{
		/* Build poster's display data */
		$member = $row['author_id'] ? IPSMember::load( $row['author_id'], 'profile_portal,pfields_content,sessions,groups', 'id' ) : IPSMember::setUpGuest();
		$row    = array_merge( $row, IPSMember::buildDisplayData( $member, array( 'reputation' => 0, 'warn' => 0 ) ) );
		
		/* Get forum data (damn HTML >.<) */
		$forumData = ipsRegistry::getClass('class_forums')->getForumById( $row['forum_id'] );
		
		/* Parse BBCode */
		IPSText::getTextClass('bbcode')->parse_smilies			= $row['use_emo'];
		IPSText::getTextClass('bbcode')->parse_html				= ( $forumData['use_html'] and $member['g_dohtml'] and $row['post_htmlstate'] ) ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_nl2br			= $row['post_htmlstate'] == 2 ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
		IPSText::getTextClass('bbcode')->parsing_section		= 'topics';
		IPSText::getTextClass('bbcode')->parsing_mgroup  		= $member['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $member['mgroup_others'];
		$row['post'] = IPSText::getTextClass('bbcode')->preDisplayParse( $row['post'] );
		
		/* Parse attachments */
		$messageHTML = array( $row['pid'] => $row['post'] );
		$attachHTML = $this->class_attach->renderAttachments( $messageHTML, array( $row['pid'] ) );
		
		if( is_array( $attachHTML ) AND count( $attachHTML ) )
		{		
			/* Get rid of any lingering attachment tags */
			if ( stristr( $attachHTML[ $row['pid'] ]['html'], "[attachment=" ) )
			{
				$attachHTML[ $row['pid'] ]['html'] = IPSText::stripAttachTag( $attachHTML[ $row['pid'] ]['html'] );
			}
			$row['post'] = $attachHTML[ $row['pid'] ]['html'] . $attachHTML[ $row['pid'] ]['attachmentHtml'];
		}
		
		/* Get rep buttons */
		if ( $row['repUserGiving'] == ipsRegistry::member()->getProperty('member_id') )
		{
			$row['has_given_rep'] = $row['rep_rating'];
		}
		
		$row['rep_points'] = ipsRegistry::getClass('repCache')->getRepPoints( array( 'app' => 'forums', 'type' => 'pid', 'type_id' => $row['pid'], 'rep_points' => $row['rep_points'] ) );
		$row['repButtons'] = ipsRegistry::getClass('repCache')->getLikeFormatted( array( 'app' => 'forums', 'type' => 'pid', 'id' => $row['pid'], 'rep_like_cache' => $row['rep_like_cache'] ) );
		
		/* Return */
		return $row;
	}
	
	/**
	 * Display Results
	 *
	 * @param	array	Results after being processed
	 * @param	string	HTML
	 */
	public function display( $results )
	{
		return ipsRegistry::getClass('output')->getTemplate( 'profile' )->tabReputation_posts( $results );
	}
}




$rep_author_config = array( 
						'pid' => array( 'column' => 'author_id', 'table'  => 'posts' )
					);

$rep_log_joins = array(
						array(
								'select' => 'p.*, p.author_id as repAuthorId, p.post as repContentToParse, p.pid as repPostId',
								'from'   => array( 'posts' => 'p' ),
								'where'  => 'r.type="pid" AND r.type_id=p.pid and r.app="forums"',
								'type'   => 'left'
							),
						array(
								'select' => 't.*, t.title as repContentTitle, t.tid as repContentID',
								'from'   => array( 'topics' => 't' ),
								'where'  => 'p.topic_id=t.tid',
								'type'   => 'left'
							),
					);

$rep_log_where = "p.author_id=%s";
$rep_log_link = 'findpost=%d';