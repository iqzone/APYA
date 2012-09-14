<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Moderator actions
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
 * 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class classPost
{
	/**
	 * Attachments class
	 *
	 * @var		object
	 */
	public $class_attach;

	/**
	 * Moderators
	 *
	 * @var		array
	 */
    protected $moderator	= array();
   
	/**
	 * Topic data
	 *
	 * @var		array
	 */
    public $topic	= array();
    
	/**
	 * Open/close times
	 *
	 * @var		array
	 */
    public $times	= array( 'open' => NULL, 'close' => NULL );
    
	/**
	 * This request triggers two posts to merge
	 *
	 * @var		bool
	 */
    protected $_isMergingPosts	= false;
    
	/**#@+
	 * Various user permissions
	 *
	 * @var		mixed		integer|boolean
	 */
	public $can_add_poll					= 0;
	public $max_poll_questions				= 0;
	public $max_poll_choices_per_question	= 0;
	public $can_upload						= 0;
    public $can_edit_poll					= 0;
 	public $poll_total_votes				= 0;
 	public $can_set_close_time				= 0;
 	public $can_set_open_time				= 0;
 	/**#@-*/
 	
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * Internal post array when editing
	 *
	 * @var		array
	 */
	protected $_originalPost = array();
	
	/**
	 * Internal __call array
	 *
	 * @var		array
	 */
	protected $_internalData = array();
	
	/**
	 * Internal post error string
	 *
	 * @var		string
	 */
	public $_postErrors = '';
	
	/**
	 * Topic Title
	 *
	 * @var		string
	 */
	protected $_topicTitle	= '';
	
	/**
	 * Flag to indicate if we can edit the title
	 *
	 * @var		int
	 */
	protected $edit_title	= 0;

	/**#@+
	 * Poll data
	 *
	 * @var		array
	 */
	protected $poll_data		= array();
	protected $poll_answers		= array();
	/**#@-*/

	/**
	 * Allowed items to be saved in the get/set array
	 *
	 * @var		array
	 */
	protected $_allowedInternalData = array( 'Author',
										   'ForumID',
										   'TopicID',
										   'PostID',
										   'PostContent',
										   'PostContentPreFormatted',
										   'TopicState',
										   'TopicPinned',
										   'Published',
										   'PublishedRedirectSkip',
										   'Date',
										   'Settings',
										   'IsPreview',
										   'ModOptions',
										   'PreventFromArchiving',
										   'IsAjax' );
	
	/**
	 * Forum Data
	 *
	 * @var 	array
	 */
	protected $_forumData = array();
	
	/**
	 * Topic Data
	 *
	 * @var 	array
	 */
	protected $_topicData = array();
	
	/**
	 * Post Data
	 *
	 * @var 	array
	 */
	protected $_postData = array();
	
	/**
	 * Bypass all permission checks
	 *
	 * Use with care, this will allow the class to be used within an API
	 * 
	 * @var		boolean
	 */
	protected $_bypassPermChecks = false;
	
	/**
	 * Run the increment post count adding a reply 
	 *
	 * @var		boolean
	 */
	protected $_incrementPostCount = true;
	
	/**
	 * Construct
	 *
	 * @param	object	ipsRegistry Object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_post' ), 'forums' );

		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];
		
		/* Just to be sure */
		$this->setBypassPermissionCheck( false );
		
		/* Prevent some notices */
		$this->request['mod_options']	= isset($this->request['mod_options']) ? $this->request['mod_options'] : null;
		
		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor = new $classToLoad();
		
		/* Load tagging stuff */
		if ( ! $this->registry->isClassLoaded('tags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'tags', classes_tags_bootstrap::run( 'forums', 'topics' ) );
		}
		
		if ( ! $this->registry->isClassLoaded('topics') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
			$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}
	}
	
	/**
	 * Magic Call method
	 *
	 * @param	string	Method Name
	 * @param	mixed	Method arguments
	 * @return	mixed
	 * Exception codes:
	 */
	public function __call( $method, $arguments )
	{
		$firstBit = substr( $method, 0, 3 );
		$theRest  = substr( $method, 3 );
	
		if ( in_array( $theRest, $this->_allowedInternalData ) )
		{
			if ( $firstBit == 'set' )
			{
				if ( $theRest == 'Author' )
				{
					if ( is_array( $arguments[0] ) )
					{
						$this->_internalData[ $theRest ] = $arguments[0];
					}
					else
					{
						if( $arguments[0] )
						{
							/* Set up moderator stuff, too */
							$this->_internalData[ $theRest ] = IPSMember::setUpModerator( IPSMember::load( intval( $arguments[0] ), 'all' ) );

							/* And ignored users */
							$this->_internalData[ $theRest ]['ignored_users'] = array();
							
							$this->registry->DB()->build( array( 'select' => '*', 'from' => 'ignored_users', 'where' => "ignore_owner_id=" . intval( $arguments[0] ) ) );
							$this->registry->DB()->execute();
				
							while( $r = $this->registry->DB()->fetch() )
							{
								$this->_internalData[ $theRest ]['ignored_users'][] = $r['ignore_ignore_id'];
							}
						}
						else
						{
							$this->_internalData[ $theRest ] = IPSMember::setUpGuest();
						}
					}
					
					if( $this->_internalData['Author']['mgroup_others'] )
					{
						$_others	= explode( ',', IPSText::cleanPermString( $this->_internalData['Author']['mgroup_others'] ) );
						$_perms		= array();
						
						foreach( $_others as $_other )
						{
							$_perms[]	= $this->caches['group_cache'][ $_other ]['g_perm_id'];
						}
						
						if( count($_perms) )
						{
							$this->_internalData['Author']['g_perm_id']	= $this->_internalData['Author']['g_perm_id'] . ',' . implode( ',', $_perms );
						}
					}
				}
				else
				{
					$this->_internalData[ $theRest ] = $arguments[0];
					return TRUE;
				}
			}
			else
			{
				if ( ( $theRest == 'Author' OR $theRest == 'Settings' OR $theRest == 'ModOptions' ) AND isset( $arguments[0] ) )
				{
					return isset( $this->_internalData[ $theRest ][ $arguments[0] ] ) ? $this->_internalData[ $theRest ][ $arguments[0] ] : '';
				}
				else
				{
					return isset( $this->_internalData[ $theRest ] ) ? $this->_internalData[ $theRest ] : '';
				}
			}
		}
		else
		{
			switch( $method )
			{
				case 'setForumData':
					$this->_forumData = $arguments[0];
				break;
				case 'setPostData':
					$this->_postData = $arguments[0];
				break;
				case 'setTopicData':
					$this->_topicData = $arguments[0];
				break;
				case 'getForumData':
					if ( !empty($arguments[0]) )
					{
						return $this->_forumData[ $arguments[0] ];
					}
					else
					{
						return $this->_forumData;
					}
				break;
				case 'getPostData':
					if ( !empty($arguments[0]) )
					{
						return $this->_postData[ $arguments[0] ];
					}
					else
					{
						return $this->_postData;
					}
				break;
				case 'getTopicData':
					if ( !empty($arguments[0]) )
					{
						return $this->_topicData[ $arguments[0] ];
					}
					else
					{
						return $this->_topicData;
					}
				break;
				case 'getPostError':
					return isset($this->lang->words[ $this->_postErrors ]) ? $this->lang->words[ $this->_postErrors ]: $this->_postErrors;
				break;
			}
		}
	}
	
	/**
	 * Checks to see if a setting has been sent in (aka exists)
	 *
	 * @param	string	Key of setting to check
	 * @return	boolean boo lean - new diet idea 'drop fat by being scared'
	 */
	public function hasSetting( $key )
	{
		if ( in_array( $key, $this->_internalData['Settings'] ) )
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Set the forum ID and set forumData
	 * @param int $id
	 */
	public function setForumID( $id )
	{
		$this->_internalData['ForumID'] = intval( $id );
		
		$this->_forumData = $this->registry->class_forums->getForumById( $this->_internalData['ForumID'] );
	}
	
	/**
	 * Set the topic ID and set topicData
	 * @param int $id
	 */
	public function setTopicID( $id )
	{
		$this->_internalData['TopicID'] = intval( $id );
		
		if( empty( $this->_topicData['tid'] ) )
		{
			$this->_topicData = $this->registry->topics->getTopicById( $this->_internalData['TopicID'] );
		}
	}
	
	/**
	 * Set the bypass permission flag
	 *
	 * @param	boolean
	 * @return	@e void
	 */
	public function setBypassPermissionCheck( $bool=false )
	{
		$this->_bypassPermChecks = ( $bool === true ) ? true : false;
	}
	
	/**
	 * Set the increment post count flag
	 *
	 * @param	boolean
	 * @return	@e void
	 */
	public function setIncrementPostCountFlag( $bool=true )
	{
		$this->_incrementPostCount = ( $bool === true ) ? true : false;
	}
	
	/**
	 * Set a post error remotely
	 *
	 * @param	string		Error
	 * @return	@e void
	 */
	public function setPostError( $error )
	{
		$this->_postErrors	= $error;
	}

	/**
	 * Sets the topic title.
	 * You *must* pass a raw GET or POST value. ie, a value that has not been cleaned by parseCleanValue
	 * as there are unicode checks to perform. This function will test those and clean the topic title for you
	 *
	 * @param	string		Topic Title
	 */
	public function setTopicTitle( $topicTitle )
	{ 
		if ( $topicTitle )
		{
			$this->_topicTitle = $topicTitle;

			/* Clean */
			if( $this->settings['etfilter_shout'] )
			{
				if( function_exists('mb_convert_case') )
				{
					if( in_array( strtolower( $this->settings['gb_char_set'] ), array_map( 'strtolower', mb_list_encodings() ) ) )
					{
						$this->_topicTitle = mb_convert_case( $this->_topicTitle, MB_CASE_TITLE, $this->settings['gb_char_set'] );
					}
					else
					{
						$this->_topicTitle = ucwords( strtolower($this->_topicTitle) );
					}
				}
				else
				{
					$this->_topicTitle = ucwords( strtolower($this->_topicTitle) );
				}
			}
			
			/* Encode curly braces @see http://community.invisionpower.com/tracker/issue-33987-replacements-parsed-in-titles/ */
			$this->_topicTitle = str_replace( array( '{', '}' ), array( '&#123;', '&#125;' ), $this->_topicTitle );
			
			$this->_topicTitle = IPSText::parseCleanValue( $this->_topicTitle );
			$this->_topicTitle = $this->cleanTopicTitle( $this->_topicTitle );
			$this->_topicTitle = IPSText::getTextClass( 'bbcode' )->stripBadWords( $this->_topicTitle );
			
			if( $this->getIsPreview() !== TRUE )
			{
				/* Unicode test */
				if ( IPSText::mbstrlen( $topicTitle ) > $this->settings['topic_title_max_len'] )
				{
					$this->_postErrors = 'topic_title_long';
				}
			
				if ( (IPSText::mbstrlen( IPSText::stripslashes( $topicTitle ) ) < 2) or ( ! $this->_topicTitle )  )
				{
					$this->_postErrors = 'no_topic_title';
				}
			}
		}
	}

	/**
	 * Global checks and set up
	 * Functions pertaining to ALL posting methods
	 *
	 * @return	@e void
	 * Exception Codes:
	 * NO_USER_SET			No user has been set
	 * NO_POSTING_PPD		No posting perms 'cos of PPD
	 */
	public function globalSetUp()
	{
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( ! $this->getForumID() )
		{
			throw new Exception( 'NO_FORUM_ID' );
		}
		
		if ( ! is_array( $this->getAuthor() ) )
		{
			throw new Exception( 'NO_AUTHOR_SET' );
		}
		
		//-----------------------------------------
		// Forum checks
		//-----------------------------------------

        # No forum id?
        if ( ! $this->getForumData('id') )
        {
        	throw new Exception( 'NO_FORUM_ID' );
        }
        
		# Non postable sub forum
        if ( ! $this->getForumData('sub_can_post') )
        {
        	throw new Exception( 'NO_SUCH_FORUM' );
        }   
		
		/* Make sure we have someone set */
		if ( ( ! $this->getAuthor('member_group_id') ) OR ( ! $this->getAuthor('members_display_name') ) )
		{
			throw new Exception( "NO_USER_SET" );
		}
		
		/* Does this member have permanent post restriction ? */
		if ( $this->_bypassPermChecks !== TRUE && IPSMember::isOnModQueue( $this->getAuthor() ) === NULL )
		{
			throw new Exception( "warnings_restrict_post_perm" );
		}
		
		/* Auto check published */
		$pub = $this->getPublished();
		
		if ( is_string( $pub ) )
		{
			$this->setPublished( $this->_checkPostModeration( $pub ) );
		}
		
		//-----------------------------------------
		// Do we have the member group info for this member?
		//-----------------------------------------
	
		if ( ! $this->getAuthor('g_id') )
		{
			$group_cache = $this->registry->cache()->getCache('group_cache');
			
			$this->setAuthor( array_merge( $this->getAuthor(), $group_cache[ $this->getAuthor('member_group_id') ] ) );
		}
		
		//-----------------------------------------
		// Allowed to upload?
		//-----------------------------------------
		
		$perm_id	= $this->getAuthor('org_perm_id') ? $this->getAuthor('org_perm_id') : $this->getAuthor('g_perm_id');
		$perm_array = explode( ",", $perm_id );

		if ( $this->registry->permissions->check( 'upload', $this->getForumData(), $perm_array ) === TRUE )
        {
        	if ( $this->getAuthor('g_attach_max') != -1 )
        	{
        		$this->can_upload = 1;
			}
		}
		
		//-----------------------------------------
		// Allowed poll?
		//-----------------------------------------
	
		$_moderator = $this->getAuthor('forumsModeratorData');
		
		$this->can_add_poll                  = intval($this->getAuthor('g_post_polls'));
		$this->max_poll_choices_per_question = intval($this->settings['max_poll_choices']);
		$this->max_poll_questions            = intval($this->settings['max_poll_questions']);
		$this->can_edit_poll                 = ( $this->getAuthor('g_is_supmod') ) ? $this->getAuthor('g_is_supmod') : ( isset($_moderator[ $this->getForumData('id') ]['edit_post']) ? intval( $_moderator[ $this->getForumData('id') ]['edit_post'] ) : 0 );
		
		if ( ! $this->max_poll_questions )
		{
			$this->can_add_poll = 0;
		}
		
		if ( ! $this->getForumData('allow_poll') )
		{
			$this->can_add_poll = 0;
		}

		$this->settings['max_post_length'] = $this->settings['max_post_length'] ? $this->settings['max_post_length'] : 2140000 ;
	
		//-----------------------------------------
        // Are we a moderator?
        //-----------------------------------------
        
        if ( $this->getAuthor('member_id') != 0 and $this->getAuthor('g_is_supmod') == 0 )
        {
			/* Load Moderator Options */
			$this->moderator = $_moderator[ $this->getForumID() ];
        }
	
		//-----------------------------------------
		// Set open and close time
		//-----------------------------------------
		
		$this->can_set_open_time  = ( $this->getAuthor('g_is_supmod') ) ? $this->getAuthor('g_is_supmod') : ( isset($_moderator[ $this->getForumData('id') ]['mod_can_set_open_time']) ? intval( $_moderator[ $this->getForumData('id') ]['mod_can_set_open_time'] ) : 0 );
		$this->can_set_close_time = ( $this->getAuthor('g_is_supmod') ) ? $this->getAuthor('g_is_supmod') : ( isset($_moderator[ $this->getForumData('id') ]['mod_can_set_close_time']) ? intval( $_moderator[ $this->getForumData('id') ]['mod_can_set_close_time'] ) : 0 );
	
		//-----------------------------------------
		// OPEN...
		//-----------------------------------------
		
		$_POST['open_time_date']  = isset($_POST['open_time_date']) ? $_POST['open_time_date'] : NULL;
		$_POST['open_time_time']  = isset($_POST['open_time_time']) ? $_POST['open_time_time'] : NULL;
		$_POST['close_time_date'] = isset($_POST['close_time_date']) ? $_POST['close_time_date'] : NULL;
		$_POST['close_time_time'] = isset($_POST['close_time_time']) ? $_POST['close_time_time'] : NULL;
		
		if ( $this->can_set_open_time AND $_POST['open_time_date'] AND $_POST['open_time_time'] )
		{
			$date					= strtotime( $_POST['open_time_date'] );
			list( $hour , $minute ) = explode( ":", $_POST['open_time_time'] );
			
			if ( $date )
			{
				// Bug #20374
				$this->times['open'] = ( $date + ( $minute * 60 ) + ( $hour * 3600 ) ) - $this->registry->class_localization->getTimeOffset();
			}
		}
		
		//-----------------------------------------
		// CLOSE...
		//-----------------------------------------
		
		if ( $this->can_set_close_time AND $_POST['close_time_date'] AND $_POST['close_time_time'] )
		{
			$date					= strtotime( $_POST['close_time_date'] );
			list( $hour , $minute ) = explode( ":", $_POST['close_time_time'] );
			
			if ( $date )
			{
				// Bug #20374
				$this->times['close'] = ( $date + ( $minute * 60 ) + ( $hour * 3600 ) ) - $this->registry->class_localization->getTimeOffset();
			}
		}
		
		/* Check PPD. Always do this last in globalSetUp as we skip this error if showing edit box */
		if ( $this->_bypassPermChecks !== TRUE && $this->registry->getClass('class_forums')->checkGroupPostPerDay( $this->getAuthor() ) !== TRUE )
		{
			throw new Exception( 'NO_POSTING_PPD' );
		}

	}
	
	/**
	 * Alter the topic based on moderation options, etc
	 *
	 * @param	array 	Topic data from the DB
	 * @return	array 	Altered topic data
	 */
	protected function _modTopicOptions( $topic )
	{
		/* INIT */
		$topic['state'] = ( $topic['state'] == 'closed' ) ? 'closed' : 'open';
		
		if( $this->getIsPreview() !== TRUE )
		{
			if ( ( $this->request['mod_options'] != "") or ( $this->request['mod_options'] != 'nowt' ) )
			{			
				if ($this->request['mod_options'] == 'pin')
				{
					if ($this->getAuthor('g_is_supmod') == 1 or $this->moderator['pin_topic'] == 1)
					{
						$topic['pinned'] = 1;
						
						$this->addToModLog( $this->lang->words['modlogs_pinned'], $topic['title']);
					}
				}
				else if ($this->request['mod_options'] == 'unpin')
				{
					if ($this->getAuthor('g_is_supmod') == 1 or $this->moderator['unpin_topic'] == 1)
					{
						$topic['pinned'] = 0;
						
						$this->addToModLog( $this->lang->words['modlogs_unpinned'], $topic['title']);
					}
				}
				else if ($this->request['mod_options'] == 'close')
				{
					if ($this->getAuthor('g_is_supmod') == 1 or $this->moderator['close_topic'] == 1)
					{
						$topic['state'] = 'closed';
						
						$this->addToModLog( $this->lang->words['modlogs_closed'], $topic['title']);
					}
				}
				else if ($this->request['mod_options'] == 'open')
				{
					if ($this->getAuthor('g_is_supmod') == 1 or $this->moderator['open_topic'] == 1)
					{
						$topic['state'] = 'open';
						
						$this->addToModLog( $this->lang->words['modlogs_opened'], $topic['title']);
					}
				}
				else if ($this->request['mod_options'] == 'move')
				{
					if ($this->getAuthor('g_is_supmod') == 1 or $this->moderator['move_topic'] == 1)
					{
						$topic['_returnToMove'] = 1;
					}
				}
				else if ($this->request['mod_options'] == 'pinclose')
				{
					if ($this->getAuthor('g_is_supmod') == 1 or ( $this->moderator['pin_topic'] == 1 AND $this->moderator['close_topic'] == 1 ) )
					{
						$topic['pinned'] = 1;
						$topic['state']  = 'closed';
						
						$this->addToModLog( $this->lang->words['modlogs_pinclose'], $topic['title']);
					}
				}
				else if ($this->request['mod_options'] == 'pinopen')
				{
					if ($this->getAuthor('g_is_supmod') == 1 or ( $this->moderator['pin_topic'] == 1 AND $this->moderator['open_topic'] == 1 ) )
					{
						$topic['pinned'] = 1;
						$topic['state']  = 'open';
						
						$this->addToModLog( $this->lang->words['modlogs_pinopen'], $topic['title']);
					}
				}
				else if ($this->request['mod_options'] == 'unpinclose')
				{
					if ($this->getAuthor('g_is_supmod') == 1 or ( $this->moderator['unpin_topic'] == 1 AND $this->moderator['close_topic'] == 1 ) )
					{
						$topic['pinned'] = 0;
						$topic['state']  = 'closed';
						
						$this->addToModLog( $this->lang->words['modlogs_unpinclose'], $topic['title']);
					}
				}
				else if ($this->request['mod_options'] == 'unpinopen')
				{
					if ($this->getAuthor('g_is_supmod') == 1 or ( $this->moderator['unpin_topic'] == 1 AND $this->moderator['open_topic'] == 1 ) )
					{
						$topic['pinned'] = 0;
						$topic['state']  = 'open';
						
						$this->addToModLog( $this->lang->words['modlogs_unpinopen'], $topic['title']);
					}
				}
			}
			
			//-----------------------------------------
			// Check close times...
			//-----------------------------------------
			
			if ( $topic['state'] == 'open' AND ( $this->times['close'] AND $this->times['close'] <= IPS_UNIX_TIME_NOW ) )
			{
				$topic['state'] = 'closed';
			}
			else if ( $topic['state'] == 'closed' AND ( $this->times['open'] AND $this->times['open'] >= IPS_UNIX_TIME_NOW ) )
			{
				$topic['state'] = 'open';
			}
			
			if ( $topic['state'] == 'open' AND ( $this->times['open'] OR $this->times['close'] )
					AND ( $this->times['close'] <= IPS_UNIX_TIME_NOW OR ( $this->times['open'] > IPS_UNIX_TIME_NOW AND ! $this->times['close'] ) ) )
			{
				$topic['state'] = 'closed';
			}
			
			if ( $topic['state'] == 'open' AND ( $this->times['open'] AND $this->times['close'] )
					AND ( $this->times['close'] >= $this->times['open'] ) )
			{
				$topic['state'] = 'closed';
			}
			
			$topic['state'] = ( $topic['state'] == 'closed' ) ? 'closed' : 'open';
		}
		
		return $topic;
	}
	
	/**
	 * Post a reply
	 * Very simply posts a reply. Simple.
	 *
	 * Usage:
	 * $post->setTopicID(100);
	 * $post->setForumID(5);
	 * $post->setAuthor( $member );
	 * 
	 * $post->setPostContent( "Hello [b]there![/b]" );
	 * # Optional: No bbcode, etc parsing will take place
	 * # $post->setPostContentPreFormatted( "Hello [b]there![/b]" );
	 * $post->addReply();
	 *
	 * Exception Error Codes:
	 * NO_TOPIC_ID       : No topic ID set
	 * NO_FORUM_ID		: No forum ID set
	 * NO_AUTHOR_SET	    : No Author set
	 * NO_CONTENT        : No post content set
	 * NO_SUCH_TOPIC     : No such topic
	 * NO_SUCH_FORUM		: No such forum
	 * NO_REPLY_PERM     : Author cannot reply to this topic
	 * TOPIC_LOCKED		: The topic is locked
	 * NO_REPLY_POLL     : Cannot reply to this poll only topic
	 * TOPIC_LOCKED		: The topic is locked
	 * NO_POST_FORUM		: Unable to post in that forum
	 * FORUM_LOCKED		: Forum read only
	 *
	 * @return	mixed	Exception, boolean, or void
	 */
	public function addReply()
	{
		//-----------------------------------------
		// Global checks and functions
		//-----------------------------------------
		
		try
		{
			$this->globalSetUp();
		}
		catch( Exception $error )
		{
			$this->_postErrors	= $error->getMessage();
		}
		
		if ( ! $this->getPostContent() AND ! $this->getPostContentPreFormatted() AND ! $this->getIsPreview() )
		{
			$this->_postErrors	= 'NO_CONTENT';
		}

		//-----------------------------------------
		// Get topic
		//-----------------------------------------
		
		try
		{
			$topic = $this->replySetUp();
		}
		catch( Exception $error )
		{
			$this->_postErrors	= $error->getMessage();
		}
		
		//-----------------------------------------
		// Parse the post, and check for any errors.
		//-----------------------------------------
		
		$post = $this->compilePostData();
		
		$forumData = $this->getForumData();
		
		//-----------------------------------------
		// Do we have a valid post?
		// alt+255 = chr(160) = blank space
		//-----------------------------------------

		if ( strlen( trim( IPSText::removeControlCharacters( IPSText::br2nl( $post['post'] ) ) ) ) < 1 AND !$this->getIsPreview() )
		{
			$this->_postErrors	= 'post_too_short';
		}
		
		if ( IPSText::mbstrlen( $post['post'] ) > ( $this->settings['max_post_length'] * 1024 ) AND !$this->getIsPreview() )
		{
			$this->_postErrors	= 'post_too_long';
		}
		
		//-----------------------------------------
		// Compile the poll
		//-----------------------------------------
		
		$this->poll_questions = $this->compilePollData();
		
		if ( ($this->_postErrors != "") or ( $this->getIsPreview() === TRUE ) )
		{
			//-----------------------------------------
			// Show the form again
			//-----------------------------------------
			
			return FALSE;
		}
		
		//-----------------------------------------
		// Insert the post into the database to get the
		// last inserted value of the auto_increment field
		//-----------------------------------------
		
		$post['topic_id'] = $topic['tid'];
		
		//-----------------------------------------
		// Merge concurrent posts?
		//-----------------------------------------
		
		if ( $this->getAuthor('member_id') AND $this->settings['post_merge_conc'] )
		{
			//-----------------------------------------
			// Get check time
			//-----------------------------------------
			
			$time_check = IPS_UNIX_TIME_NOW - ( $this->settings['post_merge_conc'] * 60 );
			
			//-----------------------------------------
			// Last to post?
			//-----------------------------------------
			
			if ( ( $topic['last_post'] > $time_check ) AND ( $topic['last_poster_id'] == $this->getAuthor('member_id') ) )
			{
				//-----------------------------------------
				// Get the last post. 2 queries more efficient
				// than one... trust me
				//-----------------------------------------
				
				$last_pid = $this->DB->buildAndFetch( array( 'select' => 'MAX(pid) as maxpid',
																			  'from'   => 'posts',
																			  'where'  => 'topic_id='.$topic['tid'],
																			  'limit'  => array( 0, 1 ) ) );
				
				$last_post = $this->DB->buildAndFetch( array( 'select' => '*',
																			   'from'   => 'posts',
																			   'where'  => 'pid='.$last_pid['maxpid'] ) );
				
				//-----------------------------------------
				// Sure we're the last poster?
				//-----------------------------------------
				
				if ( $last_post['author_id'] == $this->getAuthor('member_id') )
				{
					$new_post  = $last_post['post'].'<br /><br />'.$post['post'];
					
					//-----------------------------------------
					// Make sure we don't have too many images
					//-----------------------------------------
					
					IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->getAuthor('member_group_id');
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->getAuthor('mgroup_others');
										
					$test_post = IPSText::getTextClass( 'bbcode' )->preEditParse( $new_post );
					$test_post = IPSText::getTextClass( 'bbcode' )->preDbParse( $test_post );
										
					if ( IPSText::getTextClass( 'bbcode' )->error )
					{
						$this->_postErrors = 'merge_'.IPSText::getTextClass( 'bbcode' )->error;
						$this->showReplyForm();
						return;
					}
					
					//-----------------------------------------
					// Update post row
					//-----------------------------------------
					
					$this->DB->setDataType( 'pid', 'int' );
					$this->DB->setDataType( 'post', 'string' );

					$_autoMergeData = array( 'post' => $new_post, 'post_date' => IPS_UNIX_TIME_NOW, 'pid' => $last_post['pid'] );
					
					/* Data Hook Location */
					IPSLib::doDataHooks( $_autoMergeData, 'postAutoMerge' );
					
					/* Terabyte didn't notice a bug in this location since fixed */
					unset( $_autoMergeData['pid'] );
				
					$this->DB->update( 'posts', $_autoMergeData, 'pid='.$last_post['pid'] );
										
					/* Add to cache */
					IPSContentCache::drop( 'post', $last_post['pid'] );
					// Commented out for bug #14252, replace is unreliable
					//IPSContentCache::update( $last_post['pid'], 'post', $this->formatPostForCache( $new_post ) );
					
					$post['pid']			= $last_post['pid'];
					$post['post_key']		= $last_post['post_key'];
					$post['post']			= $new_post;
					$this->_isMergingPosts	= 1;
					
					/* Make sure we reset the post key for attachments */
					$this->DB->update( 'attachments', array( 'attach_post_key' => $post['post_key'] ), "attach_rel_module='post' AND attach_post_key='" . $this->post_key . "'" );
				}
			}
		}

		//-----------------------------------------
		// No?
		//-----------------------------------------
		
		if ( ! $this->_isMergingPosts )
		{
			//-----------------------------------------
			// Add post to DB
			//-----------------------------------------
			
			$post['post_key']    = $this->post_key;

			//-----------------------------------------
			// Typecast
			//-----------------------------------------
			
			$this->DB->setDataType( 'pid', 'int' );
			$this->DB->setDataType( 'post', 'string' );

			/* Data Hook Location */
			IPSLib::doDataHooks( $post, 'postAddReply' );
			
			/* Finally insert.. */
			$this->DB->insert( 'posts', $post );
						
			$post['pid'] = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Require pre-approval of posts?
			//-----------------------------------------
			
			if ( $post['queued'] )
			{
				$this->DB->insert( 'mod_queued_items', array( 'type' => 'post', 'type_id' => $post['pid'] ) );/*noLibHook*/
			}
			
			/* Add to cache */
			IPSContentCache::update( $post['pid'], 'post', $this->formatPostForCache( $post['post'] ) );
		}
		
		//-----------------------------------------
		// If we are still here, lets update the
		// board/forum/topic stats
		//-----------------------------------------
		
		$this->updateForumAndStats( $topic, 'reply');
		
		//-----------------------------------------
		// Update view counts
		//-----------------------------------------
		
		$this->updateViewCounter( $topic['tid'] );
		
		//-----------------------------------------
		// Get the correct number of replies
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$topic['tid']} AND " . $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ) ) ) );
		$this->DB->execute();
		
		$posts = $this->DB->fetch();
		
		$pcount = intval( $posts['posts'] - 1 );
		
		//-----------------------------------------
		// Get the correct number of queued replies
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$topic['tid']} AND " . $this->registry->class_forums->fetchPostHiddenQuery( array( 'hidden' ) ) ) );
		$this->DB->execute();
		
		$qposts  = $this->DB->fetch();
		
		$qpcount = intval( $qposts['posts'] );
		
		//-----------------------------------------
		// Get the correct number of deleted replies
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'COUNT(*) as posts', 'from' => 'posts', 'where' => "topic_id={$topic['tid']} AND " . $this->registry->class_forums->fetchPostHiddenQuery( array( 'sdeleted' ) ) ) );
		$this->DB->execute();
		
		$qposts  = $this->DB->fetch();
		
		$dcount = intval( $qposts['posts'] );
		
		//-----------------------------------------
		// UPDATE TOPIC
		//-----------------------------------------
		
		$poster_name = $this->getAuthor('member_id') ? $this->getAuthor('members_display_name') : $this->request['UserName'];
		
		$update_array = array('posts'			    => $pcount,
							  'topic_queuedposts'   => $qpcount,
							  'topic_deleted_posts' => $dcount );
							 
		if ( $this->getPublished() )
		{					 
			$update_array['last_poster_id']   = $this->getAuthor('member_id');
			$update_array['last_poster_name'] = $poster_name;
			$update_array['seo_last_name']    = IPSText::makeSeoTitle( $poster_name );
			$update_array['last_post']        = IPS_UNIX_TIME_NOW;
			$update_array['pinned']           = $topic['pinned'];
			$update_array['state']            = $topic['state'];
			
			if ( count( $this->poll_questions ) AND $this->can_add_poll )
			{
				$update_array['poll_state'] = 1;
			}
		}
		
		/* Typecast */
		$this->DB->setDataType( array( 'title', 'starter_name', 'seo_last_name', 'last_poster_name' ), 'string' );

		/* Data Hook Location */
		IPSLib::doDataHooks( $update_array, 'postAddReplyTopicUpdate' );
													  
		$this->DB->update( 'topics', $update_array, "tid={$topic['tid']}"  );
		
		//-----------------------------------------
		// Add the poll to the polls table
		//-----------------------------------------
		
		if ( count( $this->poll_questions ) AND $this->can_add_poll )
		{
			$poll_only = 0;
			
			if( $this->settings['ipb_poll_only'] AND ipsRegistry::$request['poll_only'] == 1 )
			{
				$poll_only = 1;
			}
			
			$_pollData = array( 'tid'           => $topic['tid'],
								'forum_id'      => $this->getForumData('id'),
								'start_date'    => IPS_UNIX_TIME_NOW,
								'choices'       => serialize( $this->poll_questions ),
								'starter_id'    => $this->getAuthor('member_id'),
								'votes'         => 0,
								'poll_question' => ipsRegistry::$request['poll_question'],
								'poll_only'		=> $poll_only );
			
			/* Data Hook Location */
			IPSLib::doDataHooks( $_pollData, 'postAddReplyPoll' );

			$this->DB->insert( 'polls', $_pollData );
		}
		
		//-----------------------------------------
		// If we are a member, lets update thier last post
		// date and increment their post count.
		//-----------------------------------------
		
		if ( ! $this->_isMergingPosts )
		{
			$this->incrementUsersPostCount();
		}
		
		/* Upload Attachments */
		$this->uploadAttachments( $post['post_key'], $post['pid'] );
		
		//-----------------------------------------
		// Make attachments "permanent"
		//-----------------------------------------
		 
		$this->makeAttachmentsPermanent( $post['post_key'], $post['pid'], 'post', array( 'topic_id' => $topic['tid'] ) );
		
		//-----------------------------------------
		// Send out notifications
		//-----------------------------------------
		
		if ( ! $this->_isMergingPosts AND $this->getPublished() === FALSE )
		{
			$this->sendNewTopicForApprovalEmails( $topic['tid'], $topic['title'], $topic['starter_name'], $post['post'], $post['pid'], 'reply' );
		}
			
		//-----------------------------------------
		// Moderating?
		//-----------------------------------------
		
		if ( ! $this->_isMergingPosts AND $this->getPublished() === FALSE AND $this->getIsAjax() !== TRUE )
		{
			$page = floor( $topic['posts'] / $this->settings['display_max_posts'] );
			$page = $page * $this->settings['display_max_posts'];
			
			ipsRegistry::getClass('output')->redirectScreen( $this->lang->words['moderate_post'], $this->settings['base_url'] . "showtopic={$topic['tid']}&st=$page" );
		}
		else if( ! $this->_isMergingPosts AND $this->getPublished() === FALSE AND $this->getIsAjax() === TRUE )
		{
			//-----------------------------------------
			// Leave data for other apps
			//-----------------------------------------
			
			$this->setTopicData( $topic );
			$this->setPostData( $post );
			
			return TRUE;
		}
		
		/* remove saved content */
		if ( $this->memberData['member_id'] )
		{
			$this->editor->removeAutoSavedContent( array( 'member_id' => $this->memberData['member_id'], 'autoSaveKey' => 'reply-' . intval( $topic['tid'] ) ) );
		}
		
		/* add to recent post cache */
		if ( ! $this->_isMergingPosts && $this->memberData['member_id'] )
		{
			$this->registry->topics->addRecentPost( array( 'post_id'        => $post['pid'],
														   'post_topic_id'  => $topic['tid'],
														   'post_forum_id'  => $topic['forum_id'],
														   'post_author_id' => $this->memberData['member_id'],
														   'post_date'	    => IPS_UNIX_TIME_NOW ) );
		}
		
		//-----------------------------------------
		// Are we tracking topics we reply in 'auto_track'?
		//-----------------------------------------
		
		$this->addTopicToTracker($topic['tid'], 1);
		
		//-----------------------------------------
		// Check for subscribed topics
		// XXPass on the previous last post time of the topic
		// 12.26.2007 - we want to send email if the new post was
		// made after the member's last visit...which should be
		// last_activity minus session expiration
		// to see if we need to send emails out
		//-----------------------------------------
		
		$notificationsSentTo = $this->sendOutTrackedTopicEmails( $topic, $post['post'], $poster_name );

		//-----------------------------------------
		// Send notification of post quoted
		//-----------------------------------------
		
		$this->sendOutQuoteNotifications( $post, $notificationsSentTo );
		
		/* Mark as read */
		$this->registry->getClass('classItemMarking')->markRead( array( 'forumID' => $forumData['id'], 'itemID' => $topic['tid'], 'markDate' => IPS_UNIX_TIME_NOW, 'containerLastActivityDate' => $forumData['last_post'] ) );

		//-----------------------------------------
		// Leave data for other apps
		//-----------------------------------------
		
		$this->setTopicData( $topic );
		$this->setPostData( $post );
		
		return TRUE;
	}

	/**
	 * Performs set up for adding a reply
	 *
	 * @return	array    Topic data
	 *
	 * Exception Error Codes
	 * NO_SUCH_TOPIC		No topic could be found matching the topic ID and forum ID
	 * NO_REPLY_PERM		Viewer does not have permission to reply
	 * TOPIC_LOCKED		The topic is locked
	 * NO_REPLY_POLL		This is a poll only topic
	 * NO_TOPIC_ID		No topic ID (durrrrrrrrrrr)
	 */
	public function replySetUp()
	{
		//-----------------------------------------
		// Check for a topic ID
		//-----------------------------------------
	
		if ( ! $this->getTopicID() )
		{
			throw new Exception( 'NO_TOPIC_ID' );
		}
		
        /* Minimum Posts Check */        
		if ( $this->_bypassPermChecks !== TRUE && $this->getForumData('min_posts_post') && $this->getForumData('min_posts_post') > $this->getAuthor('posts') && !$this->getAuthor('g_is_supmod') )
		{
			$this->registry->output->showError( 'posting_not_enough_posts', 103140, null, null, 403 );
		}		
		
		//-----------------------------------------
		// Set up post key
		//-----------------------------------------
		
		$this->post_key = ( $this->request['attach_post_key'] AND $this->request['attach_post_key'] != "" ) ? $this->request['attach_post_key'] : md5( microtime() );
		
		//-----------------------------------------
		// Load and set topic
		//-----------------------------------------

		$topic = $this->getTopicData();
		
		if ( empty($topic['tid']) )
		{
			/* Fetch topic if topic ID exists */
			$topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $this->getTopicID() ) );
			
			if ( $topic['tid'] )
			{
				$this->setTopicData( $topic );
			}
			else
			{
				throw new Exception("NO_SUCH_TOPIC");
			}
		}
		
		//-----------------------------------------
		// Checks
		//-----------------------------------------

		if ( $this->_bypassPermChecks !== TRUE )
		{
			if( $topic['poll_state'] == 'closed' and $this->getAuthor('g_is_supadmin') != 1 )
			{
				throw new Exception( 'NO_REPLY_PERM' );
			}
			
			if( $topic['starter_id'] == $this->getAuthor('member_id') )
			{
				if( ! $this->getAuthor('g_reply_own_topics'))
				{
					throw new Exception( 'NO_REPLY_PERM' );
				}
			}
			else
			{
				if( ! $this->getAuthor('g_reply_other_topics') )
				{
					throw new Exception( 'NO_REPLY_PERM' );
				}
			}
			
			$perm_id	= $this->getAuthor('org_perm_id') ? $this->getAuthor('org_perm_id') : $this->getAuthor('g_perm_id');
			$perm_array = explode( ",", $perm_id );
			
			if ( $this->registry->permissions->check( 'reply', $this->getForumData(), $perm_array ) === FALSE )
			{
				throw new Exception( 'NO_REPLY_PERM' );
			}
			
			if( $topic['state'] != 'open')
			{
				if( $this->getAuthor('g_post_closed') != 1 )
				{
					throw new Exception( 'TOPIC_LOCKED' );
				}
			}
			
			if( !empty($topic['poll_only']) )
			{
				if( $this->getAuthor('g_post_closed') != 1 )
				{
					throw new Exception( 'NO_REPLY_POLL' );
				}
			}
		}
		
		//-----------------------------------------
		// POLL BOX ( Either topic starter or admin)
		// and without a current poll
		//-----------------------------------------
		
		if ( $this->can_add_poll )
		{
			$this->can_add_poll = 0;
			
			if ( ! $topic['poll_state'] )
			{
				if ( $this->getAuthor('member_id') AND $this->getPublished() )
				{
					if ( $this->getAuthor('g_is_supmod') == 1 )
					{
						$this->can_add_poll = 1;
					}
					else if ( $topic['starter_id'] == $this->getAuthor('member_id') )
					{
						if ( ($this->settings['startpoll_cutoff'] > 0) AND ( $topic['start_date'] + ($this->settings['startpoll_cutoff'] * 3600) > IPS_UNIX_TIME_NOW ) )
						{
							$this->can_add_poll = 1;
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Mod options...
		//-----------------------------------------
		
		$topic = $this->_modTopicOptions( $topic );
		
		return $topic;
	}
	
	/**
	 * Post a new topic
	 * Very simply posts a new topic. Simple.
	 *
	 * Usage:
	 * $post->setTopicID(100);
	 * $post->setForumID(5);
	 * $post->setAuthor( $member );
	 * 
	 * $post->setPostContent( "Hello [b]there![/b]" );
	 * # Optional: No bbcode, etc parsing will take place
	 * # $post->setPostContentPreFormatted( "Hello [b]there![/b]" );
	 * $post->setTopicTitle('Hi!');
	 * $post->addTopic();
	 *
	 * Exception Error Codes:
	 * NO_FORUM_ID		: No forum ID set
	 * NO_AUTHOR_SET	    : No Author set
	 * NO_CONTENT        : No post content set
	 * NO_SUCH_FORUM		: No such forum
	 * NO_REPLY_PERM     : Author cannot reply to this topic
	 * NO_POST_FORUM		: Unable to post in that forum
	 * FORUM_LOCKED		: Forum read only
	 *
	 * @return	mixed
	 */
	public function addTopic()
	{
		//-----------------------------------------
		// Global checks and functions
		//-----------------------------------------
		
		try
		{
			$this->globalSetUp();
		}
		catch( Exception $error )
		{
			$this->_postErrors = $error->getMessage();
		}
		
		if ( ! $this->getPostContent() AND ! $this->getPostContentPreFormatted() AND !$this->getIsPreview() )
		{
			$this->_postErrors = 'NO_CONTENT';
		}
		
		//-----------------------------------------
		// Get topic
		//-----------------------------------------
		
		try
		{
			$topic = $this->topicSetUp();
		}
		catch( Exception $error )
		{
			$this->_postErrors = $error->getMessage();
		}
		
		//-----------------------------------------
		// Parse the post, and check for any errors.
		//-----------------------------------------
				
		$post = $this->compilePostData();

		//-----------------------------------------
		// Do we have a valid post?
		//-----------------------------------------
		
		if( $this->getIsPreview() !== TRUE )
		{
			if ( strlen( trim( IPSText::removeControlCharacters( IPSText::br2nl( $post['post'] ) ) ) ) < 1 )
			{
				$this->_postErrors = 'post_too_short';
			}
			
			if ( IPSText::mbstrlen( $post['post'] ) > ( $this->settings['max_post_length'] * 1024 ) )
			{
				$this->_postErrors = 'post_too_long';
			}
			
			/* Got a topic title? */
			if ( ! $this->_topicTitle )
			{
				$this->_postErrors = 'no_topic_title';
			}
		}
				
		//-----------------------------------------
		// Compile the poll
		//-----------------------------------------
		
		$this->poll_questions = $this->compilePollData();
		
		if ( ($this->_postErrors != "") or ( $this->getIsPreview() === TRUE ) )
		{
			//-----------------------------------------
			// Show the form again
			//-----------------------------------------
			
			return FALSE;
		}
		
		//-----------------------------------------
		// Build the master array
		//-----------------------------------------

		$topic = array( 'title'                => $this->_topicTitle,
					    'title_seo'		       => IPSText::makeSeoTitle( $this->_topicTitle ),
					    'state'                => $topic['state'],
					    'posts'                => 0,
					    'starter_id'           => $this->getAuthor('member_id'),
					    'starter_name'         => $this->getAuthor('member_id') ?  $this->getAuthor('members_display_name') : $this->request['UserName'],
					    'seo_first_name'       => IPSText::makeSeoTitle( $this->getAuthor('member_id') ?  $this->getAuthor('members_display_name') : $this->request['UserName'] ),
					    'start_date'           => ( $this->getDate() ) ? $this->getDate() : IPS_UNIX_TIME_NOW,
					    'last_poster_id'       => $this->getAuthor('member_id'),
					    'last_poster_name'     => $this->getAuthor('member_id') ?  $this->getAuthor('members_display_name') : $this->request['UserName'],
					    'seo_last_name'        => IPSText::makeSeoTitle( $this->getAuthor('member_id') ?  $this->getAuthor('members_display_name') : $this->request['UserName'] ),
					    'last_post'            => ( $this->getDate() ) ? $this->getDate() : IPS_UNIX_TIME_NOW,
					    'author_mode'          => $this->getAuthor('member_id') ? 1 : 0,
					    'poll_state'           => ( count( $this->poll_questions ) AND $this->can_add_poll ) ? 1 : 0,
					    'last_vote'            => 0,
					    'views'                => 0,
					    'forum_id'             => $this->getForumData('id'),
					    'approved'             => ( $this->getPublished() === TRUE ) ? 1 : 0,
					    'topic_archive_status' => ( $this->getPreventFromArchiving() ) ? 3 : 0,
					    'pinned'               => intval( $topic['pinned'] ),
					    'topic_open_time'      => intval( $this->times['open'] ),
					    'topic_close_time'     => intval( $this->times['close'] ) );

		//-----------------------------------------
		// Check if we're ok with tags
		//-----------------------------------------
		
		$where		= array( 'meta_parent_id'	=> $this->getForumData('id'),
							  'member_id'		=> $this->getAuthor('member_id'),
							  'existing_tags'	=> explode( ',', IPSText::cleanPermString( $this->request['ipsTags'] ) ) );
									  
		if ( $this->registry->tags->can( 'add', $where ) AND $this->settings['tags_enabled'] AND ( !empty( $_POST['ipsTags'] ) OR $this->settings['tags_min'] ) )
		{
			$this->registry->tags->checkAdd( $_POST['ipsTags'], array(
																  'meta_parent_id' => $topic['forum_id'],
																  'member_id'	   => $this->memberData['member_id'],
																  'meta_visible'   => $topic['approved'] ) );

			if ( $this->registry->tags->getErrorMsg() )
			{
				$this->_postErrors = $this->registry->tags->getFormattedError();
				return FALSE;
			}
			
			$_storeTags	= true;
		}

		//-----------------------------------------
		// Insert the topic into the database to get the
		// last inserted value of the auto_increment field
		// follow suit with the post
		//-----------------------------------------
		
		$this->DB->setDataType( array( 'title', 'starter_name', 'seo_first_name', 'last_poster_name', 'seo_last_name' ), 'string' );

		/* Data Hook Location */
		IPSLib::doDataHooks( $topic, 'postAddTopic' );
		
		$this->DB->insert( 'topics', $topic );
		
		$post['topic_id']  = $this->DB->getInsertId();
		$topic['tid']      = $post['topic_id'];
		
		//-----------------------------------------
		// Update the post info with the upload array info
		//-----------------------------------------
		
		$post['post_key']  = $this->post_key;
		$post['new_topic'] = 1;
		
		//-----------------------------------------
		// Unqueue the post if we're starting a new topic
		//-----------------------------------------
		
		$post['queued'] = 0;
		
		/* Typecast */
		$this->DB->setDataType( 'post', 'string' );
		
		/* Data Hook Location */
		IPSLib::doDataHooks( $post, 'postFirstPost' );
		
		/* Add post to DB */
		$this->DB->insert( 'posts', $post );
	
		$post['pid'] = $this->DB->getInsertId();
		
		//-----------------------------------------
		// Require pre-approval of topics?
		//-----------------------------------------
		
		if( ! $topic['approved'] )
		{
			$this->DB->insert( 'mod_queued_items', array( 'type' => 'topic', 'type_id' => $topic['tid'] ) );/*noLibHook*/
		}
		
		/* Add to cache */
		IPSContentCache::update( $post['pid'], 'post', $this->formatPostForCache( $post['post'] ) );
		
		//-----------------------------------------
		// Update topic with firstpost ID
		//-----------------------------------------
		
		$this->DB->update( 'topics', array( 'topic_firstpost' => $post['pid'] ), 'tid=' . $topic['tid'] );

		//-----------------------------------------
		// Tagging
		//-----------------------------------------
		
		if ( $_storeTags )
		{
			$this->registry->tags->add( $_POST['ipsTags'], array( 'meta_id'		   => $topic['tid'],
																  'meta_parent_id' => $topic['forum_id'],
																  'member_id'	   => $this->memberData['member_id'],
																  'meta_visible'   => $topic['approved'] ) );
		}
		
		//-----------------------------------------
		// Add the poll to the polls table
		//-----------------------------------------
		
		if ( count( $this->poll_questions ) AND $this->can_add_poll )
		{
			$poll_only = 0;
			
			if ( $this->settings['ipb_poll_only'] AND $this->request['poll_only'] == 1 )
			{
				$poll_only = 1;
			}
			
			$_pollData = array( 'tid'				=> $topic['tid'],
								'forum_id'			=> $this->getForumData('id'),
								'start_date'		=> IPS_UNIX_TIME_NOW,
								'choices'			=> addslashes(serialize( $this->poll_questions )),
								'starter_id'		=> $this->getAuthor('member_id'),
								'votes'				=> 0,
								'poll_question'		=> IPSText::stripAttachTag( $this->request['poll_question'] ),
								'poll_only'			=> $poll_only,
								'poll_view_voters'	=> intval( $this->request['poll_view_voters'] ) );
			
			/* Data Hook Location */
			IPSLib::doDataHooks( $_pollData, 'postAddTopicPoll' );

			$this->DB->insert( 'polls', $_pollData );
		}
		
		/* remove saved content */
		if ( $this->memberData['member_id'] )
		{
			$this->editor->removeAutoSavedContent( array( 'member_id' => $this->memberData['member_id'], 'autoSaveKey' => 'new-' . intval( $this->getForumData('id') ) ) );
		}
		
		//-----------------------------------------
		// If we are still here, lets update the
		// board/forum stats
		//----------------------------------------- 
		
		$this->updateForumAndStats( $topic, 'new');
		
		/* Upload Attachments */
		$this->uploadAttachments( $this->post_key, $post['pid'] );		
		
		//-----------------------------------------
		// Make attachments "permanent"
		//-----------------------------------------
		
		$this->makeAttachmentsPermanent( $this->post_key, $post['pid'], 'post', array( 'topic_id' => $topic['tid'] ) );
		
		//-----------------------------------------
		// If we are a member, lets update thier last post
		// date and increment their post count.
		//-----------------------------------------
		
		$this->incrementUsersPostCount();
		
		//-----------------------------------------
		// Are we tracking new topics we start 'auto_track'?
		//-----------------------------------------
		
		$this->addTopicToTracker($topic['tid']);		
		
		//-----------------------------------------
		// Moderating?
		//-----------------------------------------
		
		if ( $this->getPublished() === FALSE )
		{
			/* Send email to mods about new unapproved topics */
			$this->sendNewTopicForApprovalEmails( $topic['tid'], $topic['title'], $topic['starter_name'], $post['post'], $post['pid'] );
			
			/* Do we want to skip the redirect? Useful in APIs */
			if ( $this->getPublishedRedirectSkip() !== TRUE )
			{
				ipsRegistry::getClass('output')->redirectScreen( $this->lang->words['moderate_topic'], $this->settings['base_url'] . "showforum=" . $this->getForumData('id') );
			}
		}
		else
		{
			/* add to recent post cache */
			if ( $this->memberData['member_id'] )
			{
				$this->registry->topics->addRecentPost( array( 'post_id'        => $post['pid'],
															   'post_topic_id'  => $topic['tid'],
															   'post_forum_id'  => $topic['forum_id'],
															   'post_author_id' => $this->memberData['member_id'],
															   'post_date'	    => IPS_UNIX_TIME_NOW ) );
			}
			
			//-----------------------------------------
			// Are we tracking this forum? If so generate some mailies - yay!
			//-----------------------------------------
			
			$this->sendOutTrackedForumEmails($this->getForumData(), $topic, $post['post'] );
			
			/* Send out social shares */
			$this->sendSocialShares( $topic );
		}
		
		//-----------------------------------------
		// Leave data for other apps
		//-----------------------------------------
		
		$this->setTopicData( $topic );
		$this->setPostData( $post );
		
		return TRUE;
	}

	/**
	 * Performs set up for adding a new topic
	 *
	 * @return	array 	Topic data (state, pinned, etc)
	 *
	 * Exception Error Codes
	 * NO_START_PERM		User does not have permission to start a topic
	 * NOT_ENOUGH_POSTS		User does not have enough posts to start a topic
	 */
	public function topicSetUp()
	{
		//-----------------------------------------
		// Set up post key
		//-----------------------------------------
		
		$this->post_key = ( $this->request['attach_post_key'] AND $this->request['attach_post_key'] != "" ) ? $this->request['attach_post_key'] : md5( microtime() );

		if ( $this->_bypassPermChecks !== TRUE && ! $this->getAuthor('g_post_new_topics') )
		{
			throw new Exception( 'NO_START_PERM' );
		}
		
		//if ( IPSMember::checkPermissions( 'start', $this->getForumID() ) == FALSE )
		$perm_id	= $this->getAuthor('org_perm_id') ? $this->getAuthor('org_perm_id') : $this->getAuthor('g_perm_id');
		$perm_array = explode( ",", $perm_id );
		
		if ( $this->_bypassPermChecks !== TRUE )
		{
			if ( $this->registry->permissions->check( 'start', $this->getForumData(), $perm_array ) === FALSE )
			{
				throw new Exception( 'NO_START_PERM' );
			}
			
	        /* Minimum Posts Check */
			if ( $this->getForumData('min_posts_post') && $this->getForumData('min_posts_post') > $this->getAuthor('posts') && !$this->getAuthor('g_is_supmod') )
			{
				throw new Exception( 'NOT_ENOUGH_POSTS' );
			}
		}
	
		//-----------------------------------------
		// Mod options...
		//-----------------------------------------
		
		$topic = $this->_modTopicOptions( array( 'title' => $this->_topicTitle )  );
		
		return $topic;
	}
	
	/**
	 * Post a reply
	 * Very simply posts a reply. Simple.
	 *
	 * Usage:
	 * $post->setForumID(1);
	 * $post->setTopicID(5);
	 * $post->setPostID(100);
	 * $post->setAuthor( $member );
	 * 
	 * $post->setPostContent( "Hello [b]there![/b]" );
	 * # Optional: No bbcode, etc parsing will take place
	 * # $post->setPostContentPreFormatted( "Hello <b>there!</b>" );
	 * $post->editPost();
	 *
	 * Exception Error Codes:
	 * NO_TOPIC_ID       : No topic ID set
	 * NO_FORUM_ID		: No forum ID set
	 * NO_AUTHOR_SET	    : No Author set
	 * NO_CONTENT        : No post content set
	 * CONTENT_TOO_LONG  : Post is too long
	 * NO_SUCH_TOPIC     : No such topic
	 * NO_SUCH_FORUM		: No such forum
	 * NO_REPLY_PERM     : Author cannot reply to this topic
	 * TOPIC_LOCKED		: The topic is locked
	 * NO_REPLY_POLL     : Cannot reply to this poll only topic
	 * TOPIC_LOCKED		: The topic is locked
	 * NO_REPLY_POLL		: This is a poll only topic
	 * NO_POST_FORUM		: Unable to post in that forum
	 * FORUM_LOCKED		: Forum read only
	 *
	 * @return	mixed
	 */
	public function editPost()
	{
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
				$this->_postErrors	= $error->getMessage();
			}
		}
		
		if ( ! $this->getPostContent() AND ! $this->getPostContentPreFormatted() )
		{
			$this->_postErrors	= 'NO_CONTENT';
		}
		
		//-----------------------------------------
		// Get topic
		//-----------------------------------------
		
		try
		{
			$topic = $this->editSetUp();
		}
		catch( Exception $error )
		{
			$this->_postErrors	= $error->getMessage();
		}
		
		//-----------------------------------------
		// Parse the post, and check for any errors.
		//-----------------------------------------

		$post = $this->compilePostData();
		
		//-----------------------------------------
		// Do we have a valid post?
		//-----------------------------------------
		
		if ( strlen( trim( IPSText::removeControlCharacters( IPSText::br2nl( $post['post'] ) ) ) ) < 1 )
		{
			$this->_postErrors	= 'NO_CONTENT';
		}
		
		if ( IPSText::mbstrlen( $post['post'] ) > ( $this->settings['max_post_length'] * 1024 ) )
		{
			$this->_postErrors	= 'CONTENT_TOO_LONG';
		}
		
		if ( $this->_postErrors != "" )
		{
			//-----------------------------------------
			// Show the form again
			//-----------------------------------------
			
			return FALSE;
		}
		
		//-----------------------------------------
		// Ajax specifics
		//-----------------------------------------
		
		if ( $this->getIsAjax() === TRUE )
		{
			# Prevent polls from being edited
			$this->can_add_poll = 0;

			# Prevent titles from being edited
			$this->edit_title   = 0;
			
			# Prevent open time from being edited
			$this->can_set_open_time  = 0;
			
			# Prevent close time from being edited
			$this->can_set_close_time = 0;
			
			# Set Settings
			$this->setSettings( array( 'enableSignature' => ( $this->_originalPost['use_sig'] ) ? 1 : 0,
									   'enableEmoticons' => ( $this->_originalPost['use_emo'] ) ? 1 : 0 ) );

			if ( ! $this->getAuthor('g_append_edit') )
			{
				$this->request['add_edit'] =  ( $this->_originalPost['append_edit'] OR ! $this->getAuthor('g_append_edit') ? 1 : 0 );
			}
		}

		//-----------------------------------------
		// Compile the poll
		//-----------------------------------------
		
		if ( $this->can_add_poll )
		{
			//-----------------------------------------
			// Load the poll from the DB
			//-----------------------------------------
			
			$this->poll_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'polls', 'where' => "tid=" . intval( $topic['tid'] ) ) );
	
    		$this->poll_answers = ( ! empty($this->poll_data['choices']) && IPSLib::isSerialized($this->poll_data['choices']) ) ? unserialize(stripslashes($this->poll_data['choices'])) : array();
		}
		
		//-----------------------------------------
		// Compile the poll
		//-----------------------------------------
		
		$this->poll_questions = $this->compilePollData();
		
		if ( ($this->_postErrors != "") or ( $this->getIsPreview() === TRUE ) )
		{
			//-----------------------------------------
			// Show the form again
			//-----------------------------------------
			
			return FALSE;
		}
		
		/* Got a topics table to update? */
		$updateTopicTable = array();
		
		//-----------------------------------------
		// Reset some data
		//-----------------------------------------
		
		$post['ip_address']  = $this->_originalPost['ip_address'];
		$post['topic_id']    = $this->_originalPost['topic_id'];
		$post['author_id']   = $this->_originalPost['author_id'];
		$post['post_date']   = $this->_originalPost['post_date'];
		$post['author_name'] = $this->_originalPost['author_name'];
		$post['queued']      = $this->_originalPost['queued'];
		$post['edit_time']   = ( $this->getDate() ) ? $this->getDate() : IPS_UNIX_TIME_NOW;
		$post['edit_name']   = $this->getAuthor('members_display_name');
		
		if ( $this->_originalPost['new_topic'] == 1 )
		{
			/* Tagging */
			if ( isset( $_POST['ipsTags'] ) )
			{
				$this->registry->tags->replace( $_POST['ipsTags'], array( 'meta_id'		   => $topic['tid'],
																		  'meta_parent_id' => $topic['forum_id'],
																		  'member_id'	   => $this->memberData['member_id'],
																		  'meta_visible'   => $topic['approved'] ) );
			}
			
			/* Like if not ajax edit */
			if ( ! IPS_IS_AJAX )
			{
				$this->addTopicToTracker( $topic['tid'] );
			}
			
			//-----------------------------------------
			// Update open and close times
			//-----------------------------------------
			
			if ( $this->can_set_open_time AND $this->times['open'] )
			{
				$updateTopicTable['topic_open_time'] = intval( $this->times['open'] );
				
				if( $topic['topic_open_time'] AND $this->times['open'] )
				{
					$updateTopicTable['state'] = 'closed';
					
					if( IPS_UNIX_TIME_NOW > $topic['topic_open_time'] )
					{
						if( IPS_UNIX_TIME_NOW < $topic['topic_close_time'] )
						{
							$updateTopicTable['state'] = 'open';
						}
					}
				}
				if ( ! $this->times['open'] AND $topic['topic_open_time'] )
				{
					if ( $topic['state'] == 'closed' )
					{
						$updateTopicTable['state'] = 'open';
					}
				}				
			}
			else if( $this->can_set_open_time AND $topic['topic_open_time'] )
			{
				$updateTopicTable['topic_open_time']	= 0;
			}

			if ( $this->can_set_close_time AND $this->times['close'] )
			{
				$updateTopicTable['topic_close_time'] = intval( $this->times['close'] );
				
				//-----------------------------------------
				// Was a close time, but not now?
				//-----------------------------------------
				
				if ( ! $this->times['close'] AND $topic['topic_close_time'] )
				{
					if ( $topic['state'] == 'closed' )
					{
						$updateTopicTable['state'] = 'open';
					}
				}
			}
			else if( $this->can_set_close_time AND $topic['topic_close_time'] )
			{
				$updateTopicTable['topic_close_time']	= 0;
			}

			if( $this->edit_title )
			{
				if( $this->getForumID() != $topic['forum_id'] )
				{
					$updateTopicTable['forum_id']	= $this->getForumID();
				}
			}
		}
		
		//-----------------------------------------
		// Update poll
		//-----------------------------------------
		
		if ( $this->can_add_poll )
		{
			if ( is_array( $this->poll_questions ) AND count( $this->poll_questions ) )
			{
				$poll_only = 0;
				
				if ( $this->settings['ipb_poll_only'] AND $this->request['poll_only'] == 1 )
				{
					$poll_only = 1;
				}
				
				$poll_view_voters = ( ! $this->poll_data['votes'] ) ? $this->request['poll_view_voters'] : $this->poll_data['poll_view_voters'];
				
				if( $topic['poll_state'] )
				{
					$_pollData = array( 
										'votes'				=> intval( $this->poll_total_votes ),
										'choices'			=> addslashes(serialize( $this->poll_questions )),
										'poll_question'		=> IPSText::stripAttachTag( $this->request['poll_question'] ),
										'poll_only'			=> $poll_only,
										'poll_view_voters'	=> intval( $poll_view_voters )
									);

					/* Data Hook Location */
					IPSLib::doDataHooks( $_pollData, 'editPostUpdatePoll' );
					
					$this->DB->update( 'polls', $_pollData, 'tid='.$topic['tid'] );
							
					if ( $this->poll_data['choices'] != serialize( $this->poll_questions ) OR $this->poll_data['votes'] != intval($this->poll_total_votes) )
					{
						$this->DB->insert( 'moderator_logs', array( 'forum_id'    => $this->getForumData('id'),
																	'topic_id'    => $topic['tid'],
																	'post_id'     => $this->_originalPost['pid'],
																	'member_id'   => $this->getAuthor('member_id'),
																	'member_name' => $this->getAuthor('members_display_name'),
																	'ip_address'  => $this->ip_address,
																	'http_referer'=> htmlspecialchars( my_getenv('HTTP_REFERER') ),
																	'ctime'       => IPS_UNIX_TIME_NOW,
																	'topic_title' => $topic['title'],
																	'action'      => $this->lang->words['edited_poll'],
																	'query_string'=> htmlspecialchars( my_getenv( 'QUERY_STRING' ) ),
										)	);
					}
				}
				else
				{
					$_pollData = array( 
										'tid'				=> $topic['tid'],
										'forum_id'			=> $this->getForumData('id'),
										'start_date'		=> IPS_UNIX_TIME_NOW,
										'choices'			=> addslashes(serialize( $this->poll_questions )),
										'starter_id'		=> $this->getAuthor('member_id'),
										'votes'				=> 0,
										'poll_question'		=> IPSText::stripAttachTag( $this->request['poll_question'] ),
										'poll_only'			=> $poll_only,
										'poll_view_voters'	=> intval( $poll_view_voters ) 
									);
					
					/* Data Hook Location */
					IPSLib::doDataHooks( $_pollData, 'editPostAddPoll' );
					
					$this->DB->insert( 'polls', $_pollData );
													
					$this->DB->insert( 'moderator_logs', array ( 'forum_id'    => $this->getForumData('id'),
																 'topic_id'    => $topic['tid'],
																 'post_id'     => $this->_originalPost['pid'],
																 'member_id'   => $this->getAuthor('member_id'),
																 'member_name' => $this->getAuthor('members_display_name'),
																 'ip_address'  => $this->ip_address,
																 'http_referer'=> htmlspecialchars( my_getenv('HTTP_REFERER') ),
																 'ctime'       => IPS_UNIX_TIME_NOW,
																 'topic_title' => $topic['title'],
																 'action'      => sprintf( $this->lang->words['added_poll'], $this->request['poll_question'] ),
																 'query_string'=> htmlspecialchars( my_getenv('QUERY_STRING') ) ) );
					
					/* Update topics table later */
					$updateTopicTable['poll_state'] = 1;
					$updateTopicTable['last_vote']  = 0;
				}
			}
			else
			{
				/* Remove the poll */
				$this->DB->delete( 'polls', 'tid=' . $topic['tid'] );
				$this->DB->delete( 'voters', 'tid=' . $topic['tid'] );
				
				/* Update topics table later */
				$updateTopicTable['poll_state'] = 0;
				$updateTopicTable['last_vote']  = 0;
			}
		}
		
		//-----------------------------------------
		// Update topic title?
		//-----------------------------------------
		
		if ( $this->edit_title == 1 )
		{
			//-----------------------------------------
			// Update topic title
			//-----------------------------------------
				
			if ( $this->_topicTitle != "" )
			{
				if ( $this->_topicTitle != $topic['title'] OR !$topic['title_seo'] )
				{
					$updateTopicTable['title']		= $this->_topicTitle;
					$updateTopicTable['title_seo']	= IPSText::makeSeoTitle( $this->_topicTitle );
					
					$_forumUpdate	= array();
					
					if ( $topic['tid'] == $this->getForumData('last_id') )
					{
						$_forumUpdate['last_title']		= $updateTopicTable['title'];
						$_forumUpdate['seo_last_title']	= $updateTopicTable['title_seo'];
					}
					
					if( $topic['tid'] == $this->getForumData('newest_id') )
					{
						$_forumUpdate['newest_title']		= $updateTopicTable['title'];
					}
					
					if( count($_forumUpdate) )
					{
						$this->DB->update( 'forums', $_forumUpdate, 'id=' . $this->getForumData('id') );
					}
					
					if ( ($this->moderator['edit_topic'] == 1) OR ( $this->getAuthor('g_is_supmod') == 1 ) )
					{
						$this->DB->insert( 'moderator_logs', array(
																	'forum_id'    => $this->getForumData('id'),
																	'topic_id'    => $topic['tid'],
																	'post_id'     => $this->_originalPost['pid'],
																	'member_id'   => $this->getAuthor('member_id'),
																	'member_name' => $this->getAuthor('members_display_name'),
																	'ip_address'  => $this->ip_address,
																	'http_referer'=> htmlspecialchars( my_getenv('HTTP_REFERER') ),
																	'ctime'       => IPS_UNIX_TIME_NOW,
																	'topic_title' => $topic['title'],
																	'action'      => sprintf( $this->lang->words['edited_topic_title'], $topic['title'], $this->_topicTitle ),
																	'query_string'=> htmlspecialchars( my_getenv('QUERY_STRING') ),
															)    );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Reason for edit?
		//-----------------------------------------
		
		if ( $this->_bypassPermChecks OR $this->moderator['edit_post'] OR $this->getAuthor('g_is_supmod') )
		{
			$post['post_edit_reason'] = trim( $this->request['post_edit_reason'] );
		}
		
		//-----------------------------------------
		// Update the database (ib_forum_post)
		//-----------------------------------------
		
		$post['append_edit'] = 1;
		
		if ( $this->_bypassPermChecks OR $this->getAuthor('g_append_edit') )
		{
			if ( $this->request['add_edit'] != 1 )
			{
				$post['append_edit'] = 0;
			}
		}
		
		/* Typecast */
		$this->DB->setDataType( 'post_edit_reason', 'string' );
		
		/* Data Hook Location */
		IPSLib::doDataHooks( $post, 'editPostData' );
		
		$this->DB->update( 'posts', $post, 'pid='.$this->_originalPost['pid'] );
		
		/* Got a topic to update? */
		$updateTopicTable['post_data']  = $post;
		$updateTopicTable['forum_data'] = $this->getForumData();
		IPSLib::doDataHooks( $updateTopicTable, 'editPostTopicData' );
		unset( $updateTopicTable['post_data'], $updateTopicTable['forum_data'] ); // Remove added data
		
		if ( count( $updateTopicTable ) )
		{
			$this->DB->update( 'topics', $updateTopicTable, 'tid=' . $topic['tid'] );
		}
		
		/* remove saved content */
		if ( $this->memberData['member_id'] )
		{
			$this->editor->removeAutoSavedContent( array( 'member_id' => $this->memberData['member_id'], 'autoSaveKey' => 'edit-' . intval( $this->_originalPost['pid'] ) ) );
		}
		
		/* Add to cache */
		IPSContentCache::update( $this->_originalPost['pid'], 'post', $this->formatPostForCache( $post['post'] ) );
		
		/* Upload Attachments */
		$this->uploadAttachments( $this->post_key, $this->_originalPost['pid'] );
		
		//-----------------------------------------
		// Make attachments "permanent"
		//-----------------------------------------
		
		$this->makeAttachmentsPermanent( $this->post_key, $this->_originalPost['pid'], 'post', array( 'topic_id' => $topic['tid'] ) );
		
		//-----------------------------------------
		// Make sure paperclip symbol is OK
		//-----------------------------------------
		
		$this->recountTopicAttachments($topic['tid']);
		
		//-----------------------------------------
		// Leave data for other apps
		//-----------------------------------------
		
		$this->setTopicData( $topic );
		$this->setPostData( array_merge( $this->_originalPost, $post ) );
		
		return TRUE;
	}

	/**
	 * Performs set up for editing a post
	 *
	 * @return	array    Topic data
	 *
	 * Exception Error Codes
	 * NO_SUCH_TOPIC		No topic could be found matching the topic ID and forum ID
	 * NO_SUCH_POST		Post could not be loaded
	 * NO_EDIT_PERM		Viewer does not have permission to edit
	 * TOPIC_LOCKED		The topic is locked
	 * NO_REPLY_POLL		This is a poll only topic
	 * NO_TOPIC_ID		No topic ID (durrrrrrrrrrr)
	 */
	public function editSetUp()
	{
		//-----------------------------------------
		// Check for a topic ID
		//-----------------------------------------
		
		if ( ! $this->getTopicID() )
		{
			throw new Exception( 'NO_TOPIC_ID' );
		}
		
		//-----------------------------------------
		// Load and set topic
		//-----------------------------------------
		
		$forum_id = intval( $this->getForumID() );
		
		$topic = $this->getTopicData();

		if ( ! $topic['tid'] )
		{
			throw new Exception("NO_SUCH_TOPIC");
		}
		
		if ( $forum_id != $topic['forum_id'] && ! $this->_bypassPermChecks )
		{
			throw new Exception("NO_SUCH_TOPIC");
		}

		//-----------------------------------------
		// Load the old post
		//-----------------------------------------
		
		$this->_originalPost = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'posts', 'where' => "pid=" . $this->getPostID() ) );

		if ( ! $this->_originalPost['pid'] )
		{
			throw new Exception( "NO_SUCH_POST" );
		}

		if ( $this->getIsAjax() === TRUE )
		{
			$this->setSettings( array( 'enableSignature'	=> intval($this->_originalPost['use_sig']),
									   'enableEmoticons'	=> intval($this->_originalPost['use_emo']),
									   'post_htmlstatus'	=> $this->getSettings('post_htmlstatus') !== '' ? $this->getSettings('post_htmlstatus') : intval($this->_originalPost['post_htmlstate']),
							) 		);
		}

		//-----------------------------------------
		// Same topic?
		//-----------------------------------------
		
		if ( $this->_originalPost['topic_id'] != $topic['tid'] )
		{
            ipsRegistry::getClass('output')->showError( 'posting_mismatch_topic', 20311 );
        }
		
		//-----------------------------------------
		// Generate post key (do we have one?)
		//-----------------------------------------
		
		if ( ! $this->_originalPost['post_key'] )
		{
			//-----------------------------------------
			// Generate one and save back to post and attachment
			// to ensure 1.3 < compatibility
			//-----------------------------------------
			
			$this->post_key = md5(microtime());
			
			$this->DB->update( 'posts'      , array( 'post_key' => $this->post_key ), 'pid='.$this->_originalPost['pid'] );
			$this->DB->update( 'attachments', array( 'attach_post_key' => $this->post_key ), "attach_rel_module='post' AND attach_rel_id=".$this->_originalPost['pid'] );
		}
		else
		{
			$this->post_key = $this->_originalPost['post_key'];
		}
		
		//-----------------------------------------
		// Lets do some tests to make sure that we are
		// allowed to edit this topic
		//-----------------------------------------
		
		$_canEdit = 0;
		
		if ( $this->getAuthor('g_is_supmod') )
		{
			$_canEdit = 1;
		}
		
		if ( !empty( $this->moderator['edit_post'] ) )
		{
			$_canEdit = 1;
		}
		
		if ( ($this->_originalPost['author_id'] == $this->getAuthor('member_id')) and ($this->getAuthor('g_edit_posts')) )
		{ 
			//-----------------------------------------
			// Have we set a time limit?
			//-----------------------------------------
			
			if ( $this->getAuthor('g_edit_cutoff') > 0 )
			{
				if ( $this->_originalPost['post_date'] > ( IPS_UNIX_TIME_NOW - ( intval($this->getAuthor('g_edit_cutoff')) * 60 ) ) )
				{
					$_canEdit = 1;
				}
			}
			else
			{
				$_canEdit = 1;
			}
		}
		
		//-----------------------------------------
		// Is the topic locked?
		//-----------------------------------------
		
		if ( ( $topic['state'] != 'open' ) and ( ! $this->memberData['g_is_supmod'] AND ! $this->moderator['edit_post'] ) )
		{
			if ( $this->memberData['g_post_closed'] != 1 )
			{
				$_canEdit = 0;
			}
		}
		
		if ( $_canEdit != 1 )
		{
			if ( $this->_bypassPermChecks !== TRUE )
			{
				throw new Exception( "NO_EDIT_PERMS" );
			}
		}
		
		//-----------------------------------------
		// If we're not a mod or admin
		//-----------------------------------------

		if ( ! $this->getAuthor('g_is_supmod') AND ! $this->moderator['edit_post'] )
		{
			$perm_id	= $this->getAuthor('org_perm_id') ? $this->getAuthor('org_perm_id') : $this->getAuthor('g_perm_id');
			$perm_array = explode( ",", $perm_id );
	
			if ( $this->registry->permissions->check( 'reply', $this->getForumData(), $perm_array ) !== TRUE )
			{
				$_ok = 0;
			
				//-----------------------------------------
				// Are we a member who started this topic
				// and are editing the topic's first post?
				//-----------------------------------------
			
				if ( $this->getAuthor('member_id') )
				{
					if ( $topic['topic_firstpost'] )
					{
						$_post = $this->DB->buildAndFetch( array( 'select' => 'pid, author_id, topic_id',
																  'from'   => 'posts',
																  'where'  => 'pid=' . intval( $topic['topic_firstpost'] ) ) );
																			
						if ( $_post['pid'] AND $_post['topic_id'] == $topic['tid'] AND $_post['author_id'] == $this->getAuthor('member_id') )
						{
							$_ok = 1;
						}
					}
				}
			
				if ( ! $_ok )
				{
					if ( $this->_bypassPermChecks !== TRUE )
					{
						throw new Exception( "NO_EDIT_PERMS" );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Do we have edit topic abilities?
		//-----------------------------------------
		
		# For edit, this means there is a poll and we have perm to edit
		$this->can_add_poll_mod = 0;
		
		if ( $this->_originalPost['new_topic'] == 1 )
		{
			if ( $this->_bypassPermChecks === true )
			{
				$this->edit_title       = 1;
				$this->can_add_poll_mod = 1;
			}
			if ( $this->getAuthor('g_is_supmod') == 1 )
			{
				$this->edit_title       = 1;
				$this->can_add_poll_mod = 1;
			}
			else if ( $this->moderator['edit_topic'] == 1 )
			{
				$this->edit_title       = 1;
				$this->can_add_poll_mod = 1;
			}
			else if ( $this->getAuthor('g_edit_topic') == 1 AND ($this->_originalPost['author_id'] == $this->getAuthor('member_id')) )
			{
				$this->edit_title = 1;
			}
		}
		else
		{
			$this->can_add_poll = 0;
		}
		
		return $topic;
	}
	
	/**
	 * Guest Captcha Check
	 *
	 * Not called automatically! You must check this in your own scripts!
	 *
	 * Exception Error Codes
	 * REG_CODE_ENTER	No reg code was entered
	 * CODE_ERROR		The code entered did not match the one stored in the DB
	 *
	 * @return	@e void
	 */
	public function checkGuestCaptcha()
	{
		//-----------------------------------------
		// Guest w/ CAPTCHA?
		//-----------------------------------------
		
		if ( ! $this->memberData['member_id'] AND $this->settings['guest_captcha'] AND $this->settings['bot_antispam_type'] != 'none' )
		{
			//-----------------------------------------
			// Security code stuff
			//-----------------------------------------
			
			if ( $this->request['fast_reply_used'] AND $this->request['fast_reply_used'] == 1 )
			{
				throw new Exception( "REG_CODE_ENTER" );
			}					

			if ( !$this->registry->getClass('class_captcha')->validate() )
			{
				throw new Exception( "CODE_ERROR" );
			}			
		}
	}
    
	/**
	 * Send out social shares
	 * 
	 * @param	array	TopicData
	 */
	public function sendSocialShares( array $topicData )
	{
		$forumData = $this->getForumData();
		$services  = array();
		
		/* Check for share strip */
		if ( $forumData['disable_sharelinks'] )
		{
			return;
		}
		
		/* Check permission */
		if ( ! $this->registry->getClass('class_forums')->checkEmailAccess( array_merge( $this->memberData, $forumData, $topicData ) ) )
		{
			return;
		}
		
		/* What are we sharing? */
		foreach( $this->request as $k => $v )
		{
			if ( stristr( $k, 'share_x_' ) and ! empty( $v ) )
			{
				$services[] = str_ireplace( 'share_x_', '', $k );
			}
		}
		
		if ( count( $services ) )
		{
			$data = array( 'title' => $topicData['title'],
						   'url'   => $this->registry->output->buildSEOUrl( 'showtopic=' . $topicData['tid'] . '&amp;view=getnewpost', 'publicNoSession', $topicData['title_seo'], 'showtopicunread' ) );
			
			IPSMember::sendSocialShares( $data, $services );
		}
	}
	
	/**
	 * Sends new topic waiting for approval email
	 *
	 * @param	integer	$tid
	 * @param	string	$title
	 * @param	integer	$author
	 * @param	string	[$type]	Set to 'new' for new topic or 'reply' for a reply to topic, 'new' is the default option
	 * @return	@e void
	 */
	public function sendNewTopicForApprovalEmails( $tid, $title, $author, $post, $pid=0, $type='new' )
	{
		$tmp = $this->DB->buildAndFetch( array( 'select' => 'notify_modq_emails', 'from' => 'forums', 'where' => "id=".$this->getForumData('id')) );
		
		if ( $tmp['notify_modq_emails'] == "" )
		{ 
			return;
		}
		
		if ( $type == 'new' )
		{
			IPSText::getTextClass( 'email' )->getTemplate("new_topic_queue_notify");
		}
		else
		{
			IPSText::getTextClass( 'email' )->getTemplate("new_post_queue_notify");
		}
		
		IPSText::getTextClass( 'email' )->buildMessage( array(
																'TOPIC'  => $title,
																'FORUM'  => $this->getForumData('name'),
																'POSTER' => $author,
																'POST'	 => $post,
																'DATE'   => $this->registry->getClass( 'class_localization')->getDate( IPS_UNIX_TIME_NOW, 'SHORT', 1 ),
																'LINK'   => $this->settings['base_url'] .'app=forums&module=forums&section=findpost&pid='.$pid,
															  ) );
		
		$email_message = IPSText::getTextClass( 'email' )->message;
		
		foreach( explode( ",", $tmp['notify_modq_emails'] ) as $email )
		{
			if ( $email )
			{
				IPSText::getTextClass( 'email' )->message = $email_message;
				IPSText::getTextClass( 'email' )->to      = trim($email);
				IPSText::getTextClass( 'email' )->sendMail();
			}
		}
	}
	
	/**
	 * Sends out topic subscription emails
	 *
	 * @param	array	$topic
	 * @param	string	$content
	 * @param	integer	$poster
	 * @return	array	List of member IDs we sent emails to
	 */
	public function sendOutTrackedTopicEmails( $topic, $content="" )
	{
		$sentToIds = array();
	
		if ( empty( $topic['tid'] ) )
		{
			return true;
		}
		
		if ( $this->getPublished() === false )
		{
			return true;
		}
		
		/* Load language */
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_email_content' ), 'core' );
		
		/* Set up */
		$_poster	 = IPSMember::load( $this->getAuthor('member_id') );
		$poster_name = $_poster['member_id'] ? '<a href="' . $this->registry->output->buildSEOUrl( 'showuser=' . $_poster['member_id'], 'publicNoSession', $_poster['members_seo_name'], 'showuser' ) . '">' . $_poster['members_display_name'] . '</a>' : $this->request['UserName'];
		$url         = $this->registry->output->buildSEOUrl( 'showtopic=' . $topic['tid'] . '&amp;view=getnewpost', 'publicNoSession', $topic['title_seo'], 'showtopicunread' );

		/* Fetch like class */
		try
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like = classes_like::bootstrap( 'forums','topics' );
			
			$_like->sendNotifications(
				$topic['tid'],
				array( 'immediate', 'offline' ),
				array(
					'notification_key'		=> 'followed_topics',
					'notification_url'		=> $url,
					'email_template'		=> 'subs_with_post',
					'email_only_subject'	=> array( 'key' => 'subject__subs_with_post.emailOnly', 'params' => array( $topic['title'] ) ), # This generic email subject allows clients to nest
					'build_message_array'	=> array(
						'NAME'		=> '-member:members_display_name-',
						'POSTER'	=> $poster_name,
						'TITLE'		=> $topic['title'],
						'URL'		=> $url,
						'POST'		=> $content
						),
					'ignore_data'			=> array( 'ignore_topics' => $_poster['member_id'] ? array( $_poster['member_id'] ) : array() ),
					'from'					=> $_poster, /* @link http://community.invisionpower.com/tracker/issue-37157-inline-notification-%26gt%3Bwrong-photo */
					),
				$sentToIds
				);
		}
		catch( Exception $e )
		{
			/* Something has gone wrong */
		}
				
		return $sentToIds;
	}
	
	/**
	 * Sends out forum subscription emails
	 *
	 * @param	array	$forum
	 * @param	array	$topic
	 * @param	string	$content
	 * @return	bool
	 */
	public function sendOutTrackedForumEmails( $forum, $topic, $content )
	{
		if ( empty( $topic['tid'] ) )
		{
			return true;
		}
		
		if ( $this->getPublished() === false )
		{
			return true;
		}
		
		/* Load language */
		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_email_content' ), 'core' );
		
		/* Set up */
		$_poster	 = IPSMember::load( $this->getAuthor('member_id') );
		$poster_name = $this->getAuthor('member_id') ? $this->getAuthor('members_display_name') : $this->request['UserName'];
		$url         = $this->registry->output->buildSEOUrl( 'showtopic=' . $topic['tid'] . '&amp;view=getnewpost', 'publicNoSession', $topic['title_seo'], 'showtopicunread' );

		/* Fetch like class */
		$sentToIds = array();
		try
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like = classes_like::bootstrap( 'forums','forums' );
			
			$_like->sendNotifications(
				$forum['id'],
				array( 'immediate', 'offline' ),
				array(
					'notification_key'		=> 'followed_forums',
					'notification_url'		=> $url,
					'email_template'		=> 'subs_new_topic',
					'build_message_array'	=> array(
						'NAME'		=> '-member:members_display_name-',
						'POSTER'	=> $poster_name,
						'FORUM'		=> $forum['name'],
						'TITLE'		=> $topic['title'],
						'URL'		=> $url,
						'POST'		=> $content
						),
					'ignore_data'			=> array( 'ignore_topics' => $_poster['member_id'] ? array( $_poster['member_id'] ) : array() )
					),
					$sentToIds
				);
		}
		catch( Exception $e )
		{
			/* Something has gone wrong */
		}
		
		/* Send out auto */
		$this->sendOutNewTopicNoticeToAutoSubGroups( $forum, $topic, $content, $sentToIds );
		
		return TRUE;
	}
	
	/**
	 * Sends out a notification to those in groups when new topic is created
	 * @param array $forum
	 * @param array $topic
	 * @param array $skipMemberIds
	 */
	public function sendOutNewTopicNoticeToAutoSubGroups( $forum, $topic, $content, $skipMemberIds=array() )
	{
		if ( $this->settings['autoforum_sub_groups'] )
		{
			$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
			$notifyLibrary		= new $classToLoad( $this->registry );
			
			$time_limit = time() - ( 30 * 60 );
			$mids[]     = $topic['starter_id'];
			
			/* Set up */
			$_poster	 = IPSMember::load( $this->getAuthor('member_id') );
			$poster_name = $this->getAuthor('member_id') ? $this->getAuthor('members_display_name') : $this->request['UserName'];
			$url         = $this->registry->output->buildSEOUrl( 'showtopic=' . $topic['tid'] . '&amp;view=getnewpost', 'publicNoSession', $topic['title_seo'], 'showtopicunread' );

			if ( is_array( $skipMemberIds ) )
			{
				$mids = array_unique( array_merge( $skipMemberIds, $mids ) );
			}
			
			$this->DB->build( array( 'select'   => 'm.members_display_name, m.member_group_id, m.email, m.member_id, m.language, m.last_activity, m.org_perm_id, m.mgroup_others, m.posts, m.members_cache',
									 'from'     => array( 'members' => 'm' ),
									 'where'    => "m.member_group_id IN (" . $this->settings['autoforum_sub_groups'] . ")
													   AND m.member_id NOT IN (" . implode( ',', $mids ) . ") 
													   AND m.allow_admin_mails=1
													   AND m.last_activity < " . $time_limit,
									 'add_join' => array( array( 'select' => 'g.g_perm_id',
															     'from'   => array( 'groups' => 'g' ),
																 'where'  => "m.member_group_id=g.g_id",
																 'type'   => 'left' ) )
																		
							)	);
		
			$this->DB->execute();
			
			while ( $r = $this->DB->fetch() )
			{
				$r['forum_id'] = $forum['id'];
				$gotem[ $r['member_id'] ] = $r;
			}
		}
		
		/* This is only here to make the code look nice */
		if ( count( $gotem ) )
		{			
			foreach( $gotem as $mid => $r )
			{
				if ( ! $this->registry->getClass('class_forums')->checkEmailAccess( $r ) )
				{
					continue;
				}
				
				if ( $mid == $this->memberData['member_id'] )
				{
					continue;
				}
				
				$r['language'] = $r['language'] ? $r['language'] : '';
				
				IPSText::getTextClass('email')->setPlainTextTemplate( IPSText::getTextClass('email')->getTemplate( 'subs_new_topic', $r['language'] ) );
		
				IPSText::getTextClass( 'email' )->buildMessage( array( 'TITLE'           => $topic['title'],
																	   'NAME'            => $r['members_display_name'],
																	   'URL'			 => $url,
																	   'POSTER'          => $poster_name,
																	   'FORUM'           => $forum['name'],
																	   'POST'            => $content ) );

				/* Send */
				$notifyLibrary->setMember( $r );
				$notifyLibrary->setFrom( $_poster );
				$notifyLibrary->setNotificationKey( 'followed_forums' );
				$notifyLibrary->setNotificationUrl( $url );
				$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
				$notifyLibrary->setNotificationTitle( IPSText::getTextClass('email')->subject );
				
				try
				{
					$notifyLibrary->sendNotification();
				}
				catch( Exception $e ){}
			}
		}
	}
	
	/**
	 * Send out quote notifications, yo.
	 *
	 * @param	array	$post			Post data
	 * @param	array	$membersToSkip	List of member IDs to not send notifications to
	 * @return	void
	 */
	public function sendOutQuoteNotifications( $post, $membersToSkip )
	{
		$seen = array();
	
		if ( stristr( $post['post'], '[quote' ) )
		{
			if ( preg_match_all( '#\[quote(?:[^\]]+?)post=(?:\'|"|&quot;|&\#39;)(\d+?)(?:\'|"|&quot;|&\#39;)#', $post['post'], $quotes) )
			{
				$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
				$notifyLibrary		= new $classToLoad( $this->registry );
				
				$posts = $this->registry->getClass('topics')->getPosts( array( 'postId' => array_unique( $quotes[1] ) ) );
				
				foreach( $posts AS $pid => $_quoted )
				{
					/* Try and make things a bit more readable */
					$_toMember	= $_quoted;
					$topic      = $this->registry->getClass('topics')->getTopicById( $post['topic_id'] );
						
					if ( $this->registry->getClass('topics')->canView( $topic, $_toMember ) )
					{
						if ( ( ! isset( $seen[ $_quoted['author_id'] ] ) ) && $_quoted['author_id'] && ( $_quoted['author_id'] != $this->getAuthor('member_id') ) and ( ! in_array( $_quoted['author_id'], $membersToSkip ) ) )
						{
							$seen[ $_quoted['author_id'] ] = true;
							
							$_toMember['language'] = $_toMember['language'] == "" ? IPSLib::getDefaultLanguage() : $_toMember['language'];
							
							IPSText::getTextClass('email')->getTemplate( "post_was_quoted", $_toMember['language'] );
																					
							IPSText::getTextClass('email')->buildMessage( array('MEMBER_NAME'	=> $this->getAuthor('members_display_name'),
																				'ORIGINAL_POST'	=> $this->registry->output->buildSEOUrl( "showtopic={$_quoted['tid']}&amp;view=findpost&amp;p={$_quoted['pid']}", "publicNoSession", $_quoted['title_seo'], 'showtopic' ),
																				'NEW_POST'		=> $this->registry->output->buildSEOUrl( "showtopic={$post['topic_id']}&amp;view=findpost&amp;p={$post['pid']}", "publicNoSession", $topic['title_seo'], 'showtopic' ),
																				'POST'			=> $post['post'] ) );
			
							IPSText::getTextClass('email')->subject	= sprintf(  IPSText::getTextClass('email')->subject, 
																				$this->registry->output->buildSEOUrl( 'showuser=' . $this->getAuthor('member_id'), 'publicNoSession', $this->getAuthor('members_seo_name'), 'showuser' ), 
																				$this->getAuthor('members_display_name'),
																				$this->registry->output->buildSEOUrl( "showtopic={$post['topic_id']}&amp;view=findpost&amp;p={$post['pid']}", "publicNoSession", $topic['title_seo'], 'showtopic' ),
																				$this->registry->output->buildSEOUrl( "showtopic={$_quoted['tid']}&amp;view=findpost&amp;p={$_quoted['pid']}", "publicNoSession", $_quoted['title_seo'], 'showtopic' ) );
			
							$notifyLibrary->setMember( $_toMember );
							$notifyLibrary->setFrom( $this->memberData );
							$notifyLibrary->setNotificationKey( 'post_quoted' );
							$notifyLibrary->setNotificationUrl( $this->registry->output->buildSEOUrl( "showtopic={$post['topic_id']}&amp;view=findpost&amp;p={$post['pid']}", "public", $topic['title_seo'], 'showtopic' ) );
							$notifyLibrary->setNotificationText( IPSText::getTextClass('email')->message );
							$notifyLibrary->setNotificationTitle( IPSText::getTextClass('email')->subject );
							
							try
							{
								$notifyLibrary->sendNotification();
							}
							catch( Exception $e ){}
						}
					}
				}
			}
		}
	}
    
	/**
	 * Compiles an array of poll questions
	 *
	 * @return	array
	 */
    protected function compilePollData()
    {
    	//-----------------------------------------
		// Check poll
		//-----------------------------------------

		$questions		= array();
		$choices_count	= 0;
		$is_mod			= $this->getAuthor('g_is_supmod') ? $this->getAuthor('g_is_supmod') : ( isset($this->moderator['edit_topic']) ? intval($this->moderator['edit_topic']) : 0);
				
		if ( $this->can_add_poll )
		{
			if ( isset($_POST['question']) AND is_array( $_POST['question'] ) and count( $_POST['question'] ) )
			{
				foreach( $_POST['question'] as $id => $q )
				{
					if ( ! $q OR ! $id )
					{
						continue;
					}
					
					$questions[ $id ]['question'] = IPSText::truncate( IPSText::getTextClass( 'bbcode' )->stripBadWords( IPSText::parseCleanValue( IPSText::stripAttachTag( $q ) ) ), 255 );
				}
			}
			
			if ( isset($_POST['multi']) AND is_array( $_POST['multi'] ) and count( $_POST['multi'] ) )
			{
				foreach( $_POST['multi'] as $id => $q )
				{
					if ( ! $q OR ! $id )
					{
						continue;
					}
					
					$questions[ $id ]['multi'] = intval($q);
				}
			}			
			
			//-----------------------------------------
			// Choices...
			//-----------------------------------------
			
			if ( isset($_POST['choice']) AND is_array( $_POST['choice'] ) and count( $_POST['choice'] ) )
			{
				foreach( $_POST['choice'] as $mainid => $choice )
				{
					if ( trim( $choice ) == '' )
					{
						continue;
					}

					list( $question_id, $choice_id ) = explode( "_", $mainid );
					
					$question_id = intval( $question_id );
					$choice_id   = intval( $choice_id );
					
					if ( ! $question_id OR ! isset($choice_id) )
					{
						continue;
					}
					
					if ( ! $questions[ $question_id ]['question'] )
					{
						continue;
					}
					
					$questions[ $question_id ]['choice'][ $choice_id ] = IPSText::truncate( IPSText::getTextClass( 'bbcode' )->stripBadWords( IPSText::parseCleanValue( IPSText::stripAttachTag( $choice ) ) ), 255 );
					
					if( ! $is_mod OR $this->request['poll_view_voters'] OR $this->poll_data['poll_view_voters'] )
					{
						$questions[ $question_id ]['votes'][ $choice_id ]  = intval($this->poll_answers[ $question_id ]['votes'][ $choice_id ]);
					}
					else
					{
						$_POST['votes'] = isset($_POST['votes']) ? $_POST['votes'] : 0;
						
						$questions[ $question_id ]['votes'][ $choice_id ]  = intval( $_POST['votes'][ $question_id.'_'.$choice_id ] );
					}
					
					$this->poll_total_votes += $questions[ $question_id ]['votes'][ $choice_id ];
				}
			}
			
			//-----------------------------------------
			// Make sure we have choices for each
			//-----------------------------------------
			
			foreach( $questions as $id => $data )
			{
				if ( ! is_array( $data['choice'] ) OR ! count( $data['choice'] ) OR count($data['choice']) < 2 )
				{
					unset( $questions[ $id ] );
				}
				else
				{
					$choices_count += intval( count( $data['choice'] ) );
				}
			}
			
			//-----------------------------------------
			// Error check...
			//-----------------------------------------
			
			if ( count( $questions ) > $this->max_poll_questions )
			{
				$this->_postErrors = 'poll_to_many';
			}
			
			if ( count( $choices_count ) > ( $this->max_poll_questions * $this->max_poll_choices_per_question ) )
			{
				$this->_postErrors = 'poll_to_many';
			}
		}

		return $questions;
    }

	/**
	 * Compiles all the incoming information into an array which is returned to hte accessor
	 *
	 * @return	array
	 */
	protected function compilePostData()
	{
		//-----------------------------------------
		// Sort out post content
		//-----------------------------------------
		
		if ( $this->getPostContentPreFormatted() )
		{
			$postContent = $this->getPostContentPreFormatted();
		}
		else
		{
			$postContent = $this->formatPost( $this->getPostContent() );
		}
		
		//-----------------------------------------
		// Need to format the post?
		//-----------------------------------------
		$bw   = array( 'bw_post_from_mobile' => intval( $this->member->isMobileApp ) );
		
		$post = array(
						'author_id'      => $this->getAuthor('member_id') ? $this->getAuthor('member_id') : 0,
						'use_sig'        => intval( $this->getSettings('enableSignature') ),
						'use_emo'        => intval( $this->getSettings('enableEmoticons') ),
						'ip_address'     => $this->member->ip_address,
						'post_date'      => ( $this->getDate() ) ? $this->getDate() : IPS_UNIX_TIME_NOW,
						'post'           => $postContent,
						'author_name'    => $this->getAuthor('member_id') ? $this->getAuthor('members_display_name') : ( empty($this->request['UserName']) ? $this->getAuthor('members_display_name') : $this->request['UserName'] ),
						'topic_id'       => 0,
						'queued'         => ( $this->getPublished() ) ? 0 : 1,
						'post_htmlstate' => intval( $this->getSettings('post_htmlstatus') ),
						'post_bwoptions' => IPSBWOptions::freeze( $bw, 'posts', 'forums' )
					 );

		//-----------------------------------------
		// If we had any errors, parse them back to this class
		// so we can track them later.
		//-----------------------------------------

		if( $postContent )
		{
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= $post['use_emo'];
			IPSText::getTextClass( 'bbcode' )->parse_html				= ( $this->getForumData('use_html') and $this->getAuthor('g_dohtml') and $post['post_htmlstate'] ) ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $post['post_htmlstate'] == 2 ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $this->getForumData('use_ibc') ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->getAuthor('member_group_id');
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->getAuthor('mgroup_others');
			
			$testParse	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $postContent );
			
			if ( IPSText::getTextClass( 'bbcode' )->error )
			{
				$this->_postErrors = IPSText::getTextClass( 'bbcode' )->error;
			}
			else if ( IPSText::getTextClass( 'bbcode' )->warning )
			{
				$this->_postErrors = IPSText::getTextClass( 'bbcode' )->warning;
			}
		}
		
		return $post;
	}
	
	/**
	 * Format Post: Converts BBCode, smilies, etc
	 *
	 * @param	string	Raw Post
	 * @return	string	Formatted Post
	 * @author	MattMecham
	 */
	public function formatPost( $postContent )
	{ 
		/* Set HTML Flag for the editor. Bug #19796 */
		$this->editor->setAllowHtml( ( intval($this->request['post_htmlstatus']) AND $this->getForumData('use_html') AND $this->getAuthor('g_dohtml') ) ? 1 : 0 );
		
		$postContent = $this->editor->process( $postContent );

		//-----------------------------------------
		// Parse post
		//-----------------------------------------

		if( $postContent )
		{
			IPSText::getTextClass( 'bbcode' )->parse_smilies    = $this->getSettings('enableEmoticons');
			IPSText::getTextClass( 'bbcode' )->parse_html    	= (intval($this->request['post_htmlstatus']) AND $this->getForumData('use_html') AND $this->getAuthor('g_dohtml')) ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br		= intval($this->request['post_htmlstatus']) == 2 ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode    	= $this->getForumData('use_ibc');
			IPSText::getTextClass( 'bbcode' )->parsing_section	= 'topics';
			
			$postContent = IPSText::getTextClass( 'bbcode' )->preDbParse( $postContent );
		}
		
		# Make this available elsewhere without reparsing, etc
		$this->setPostContentPreFormatted( $postContent );
		
		return $postContent;
	}
	
	/**
	 * Format Post for cache: Converts BBCode, smilies, etc
	 *
	 * @param	string	Raw Post
	 * @return	string	Formatted Post
	 * @author	MattMecham
	 */
	public function formatPostForCache( $postContent )
	{
		/* Set up parser */
		IPSText::getTextClass( 'bbcode' )->parse_smilies         = $this->getSettings('enableEmoticons');
		IPSText::getTextClass( 'bbcode' )->parse_html    	     = (intval($this->request['post_htmlstatus']) AND $this->getForumData('use_html') AND $this->getAuthor('g_dohtml')) ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br		     = intval($this->request['post_htmlstatus']) == 2 ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode    	     = $this->getForumData('use_ibc');
		IPSText::getTextClass( 'bbcode' )->parsing_section	     = 'topics';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup		 = $this->getAuthor('member_group_id');
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others = $this->getAuthor('mgroup_others');
		
		/* Did we already format this? */
		$tmp = $this->getPostContentPreFormatted();
		
		if ( $tmp )
		{
			$postContent = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $tmp );
		}
		else
		{
			$postContent = IPSText::getTextClass( 'bbcode' )->preDisplayParse( IPSText::getTextClass( 'bbcode' )->preDbParse( $postContent ) );
		}
		
		return $postContent;
	}
	
	/**
	 * Adds the action to the moderator logs
	 *
	 * @param	string	$title
	 * @param	string	$topic_title
	 * @return	@e void
	 */
	protected function addToModLog( $title='unknown', $topic_title )
	{
		$this->DB->insert( 'moderator_logs', array (
												'forum_id'    => $this->request['f'],
												'topic_id'    => $this->request['t'],
												'post_id'     => $this->request['p'],
												'member_id'   => $this->getAuthor('member_id'),
												'member_name' => $this->getAuthor('members_display_name'),
												'ip_address'  => $this->member->ip_address,
												'http_referer'=> htmlspecialchars( my_getenv('HTTP_REFERER') ),
												'ctime'       => IPS_UNIX_TIME_NOW,
												'topic_title' => $topic_title,
												'action'      => $title,
												'query_string'=> htmlspecialchars( my_getenv('QUERY_STRING') ),
										     ) );
	}
	
	/**
	 * Increments the users post count
	 *
	 * @param	int		Number of posts to increment by (default 1)
	 * @return	@e void
	 */
	public function incrementUsersPostCount( $inc=1 )
	{
		$author = $this->getAuthor();
		
		/* Is that really a member? */
		if ( $author['member_id'] )
		{
			/* Init vars */
			$update_sql = array();
			$today      = IPS_UNIX_TIME_NOW - 86400;
						
			/* Recount today's posts BOTH approved and unapproved */
			if ( $author['g_ppd_limit'] )
			{
				$count = $this->registry->class_forums->fetchMemberPpdCount( $author['member_id'], $today );
				$update_sql['members_day_posts'] = intval( $count['count'] ) . ',' . intval( $count['min'] );
			}
			
			/* Post must be published.. */
			if ( $this->getPublished() )
			{
				/* Handle last_post update */
				$update_sql['last_post'] = IPS_UNIX_TIME_NOW;
				$this->member->setProperty( 'last_post', IPS_UNIX_TIME_NOW );
				
				/* ..and forum allow posts increment before we go on */
				if ( $this->_incrementPostCount && $this->getForumData('inc_postcount') )
				{
					/* Increment the users post count */
					$author['posts'] += intval( $inc );
					$update_sql['posts'] = $author['posts'];
					
					/* Are we checking for auto promotion? */
					if ( $author['g_promotion'] != '-1&-1' )
					{
						/* Are we checking for post based auto incrementation? 0 is post based, 1 is date based, so...  */
						if ( ! $author['gbw_promote_unit_type'] )
						{
							list($gid, $gposts) = explode( '&', $author['g_promotion'] );
						
							if ( $gid > 0 and $gposts > 0 )
							{
								if ( $author['posts'] >= $gposts )
								{
									$update_sql['member_group_id'] = $gid;
								}
							}
						}
					}
				}
			}
			
			/* Update our author data */
			$this->setAuthor( array_merge( $author, $update_sql ) );
			
			/* Data Hook Location */
			$update_sql['author_data'] = $this->getAuthor();
			$update_sql['forum_data']  = $this->getForumData();
			IPSLib::doDataHooks( $update_sql, 'incrementUsersPostCount' );
			unset( $update_sql['author_data'], $update_sql['forum_data'] );
			
			if ( count( $update_sql ) )
			{
				IPSMember::save( $author['member_id'], array( 'core' => $update_sql ) );
			}
		}	
	}
	
	/**
	 * Update forum's last post information
	 *
	 * @param	array 	$topic
	 * @param	string	$type
	 * @return	@e void
	 */
	protected function updateForumAndStats( $topic, $type='new')
	{
		$moderated  = 0;
		$stat_cache = $this->registry->cache()->getCache('stats');
		$forum_data = $this->getForumData();
		
		//-----------------------------------------
		// Moderated?
		//-----------------------------------------
		
		$moderate = 0;
		
		if ( $this->getPublished() === false )
		{
			$moderate = 1;
		}

		//-----------------------------------------
		// Add to forum's last post?
		//-----------------------------------------
		
		if ( ! $moderate )
		{
			if( $topic['approved'] )
			{
				$dbs = array( 'last_title'       => $topic['title'],
							  'seo_last_title'   => IPSText::makeSeoTitle( $topic['title'] ),
							  'last_id'          => $topic['tid'],
							  'last_post'        => IPS_UNIX_TIME_NOW,
							  'last_poster_name' => $this->getAuthor('member_id') ?  $this->getAuthor('members_display_name') : $this->request['UserName'],
							  'seo_last_name'    => IPSText::makeSeoTitle( $this->getAuthor('member_id') ?  $this->getAuthor('members_display_name') : $this->request['UserName'] ),
							  'last_poster_id'   => $this->getAuthor('member_id'),
							  'last_x_topic_ids' => $this->registry->class_forums->lastXFreeze( $this->registry->class_forums->buildLastXTopicIds( $forum_data['id'], FALSE ) )
						   );
			
				if ( $type == 'new' )
				{
					$stat_cache['total_topics']++;
					
					$forum_data['topics'] = intval($forum_data['topics']);
					$dbs['topics']         = ++$forum_data['topics'];
					
					$dbs['newest_id']	   = $topic['tid'];
					$dbs['newest_title']   = $topic['title'];
				}
				else
				{
					$stat_cache['total_replies']++;
					
					$forum_data['posts'] = intval($forum_data['posts']);
					$dbs['posts']         = ++$forum_data['posts'];
				}
			}
		}
		else
		{
			if ( $type == 'new' )
			{
				$forum_data['queued_topics'] = intval($forum_data['queued_topics']);
				$dbs['queued_topics']         = ++$forum_data['queued_topics'];
			}
			else
			{
				$forum_data['queued_posts'] = intval($forum_data['queued_posts']);
				$dbs['queued_posts']         = ++$forum_data['queued_posts'];
			}
		}
		
		//-----------------------------------------
		// Merging posts?
		// Don't update counter
		//-----------------------------------------
		
		if ( $this->_isMergingPosts )
		{
			unset($dbs['posts']);
			unset($dbs['queued_posts']);
			
			$stat_cache['total_replies'] -= 1;
		}
		
		//-----------------------------------------
		// Update
		//-----------------------------------------
		
		if( is_array($dbs) AND count($dbs) )
		{
			$this->DB->setDataType( array( 'last_poster_name', 'seo_last_name', 'seo_last_title', 'last_title' ), 'string' );

			/* Data Hook Location */
			IPSLib::doDataHooks( $dbs, 'updateForumLastPostData' );
	
			$this->DB->update( 'forums', $dbs, "id=".intval($forum_data['id']) );
		}
		
		//-----------------------------------------
		// Update forum cache
		//-----------------------------------------
		
		//$this->registry->getClass('class_forums')->updateForumCache();
		
		$this->registry->cache()->setCache( 'stats', $stat_cache, array( 'array' => 1, 'donow' => 0 ) );
	}
	
	/**
	 * Convert temp uploads into permanent ones! YAY
	 *
	 * @param	string	$post_key
	 * @param	integer	$rel_id
	 * @param	string	$rel_module
	 * @param	arary 	$args
	 * @return	@e void
	 */
	protected function makeAttachmentsPermanent( $post_key="", $rel_id=0, $rel_module="", $args=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cnt = array( 'cnt' => 0 );
		
		//-----------------------------------------
		// Attachments: Re-affirm...
		//-----------------------------------------
		
		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad( $this->registry );
		
		$class_attach->type				= $rel_module;
		$class_attach->attach_post_key	= $post_key;
		$class_attach->attach_rel_id	= $rel_id;
		$class_attach->attach_parent_id	= $args['topic_id'];
		$class_attach->init();
		
		$return = $class_attach->postProcessUpload( $args );
		
		return intval( $return['count'] );
	}
	
	/**
	 * Upload any attachments that were not handled by the flash or JS uploaders
	 *
	 * @param	string	$post_key
	 * @param	integer	$rel_id
	 * @return	array
	 */
	public function uploadAttachments( $post_key, $rel_id )
	{
		/* Setup Attachment Handler */
		$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach = new $classToLoad( $this->registry );
		
		$class_attach->type            = 'post';
		$class_attach->attach_post_key = $post_key;
		$class_attach->attach_rel_id   = $rel_id;
		$class_attach->init();

		return $class_attach->processMultipleUploads();
	}	
	
	/**
	 * Recount how many attachments a topic has
	 *
	 * @param	integer	$tid
	 * @return	@e void
	 */
	public function recountTopicAttachments( $tid=0 )
	{
		if ( $tid == "" )
		{
			return;
		}
		
		/* INIT */
		$cnt = 0;
		
		$cnt = $this->DB->buildAndFetch( array( 'select' 	=> 'count(*) as cnt',
												'from'		=> array( 'attachments' => 'a' ),
												'where'	=> "a.attach_rel_module='post' AND p.topic_id={$tid}",
												'add_join'	=> array( array( 'from'  => array( 'posts' => 'p' ),
																			 'where' => "p.pid=a.attach_rel_id",
																			 'type'  => 'left' ) )
										)		);
		
		$this->DB->update( 'topics', array( 'topic_hasattach' => intval( $cnt['cnt'] ) ), 'tid='.$tid );
	}
	
	/**
	 * Check out the tracker whacker
	 *
	 * @param	integer	$tid
	 * @return	@e void
	 */
	protected function addTopicToTracker( $tid=0 )
	{
		if ( ! $tid )
		{
			return;
		}
		
		if ( $this->getAuthor('member_id') )
		{			
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like = classes_like::bootstrap( 'forums','topics' );

			if ( $this->hasSetting('enableTracker') && $this->getSettings('enableTracker') )
			{
				$_like->add( $tid, $this->getAuthor('member_id'), array( 'like_notify_do' => 1, 'like_notify_meta' => $tid, 'like_notify_freq' => $this->getAuthor('auto_track') ? $this->getAuthor('auto_track') : 'immediate' ) );
			}
			else
			{
				$_like->remove( $tid, $this->getAuthor('member_id') );
			}
		}
	}
	
	/**
	 * Updates the topic view counter when a reply is made.
	 *
	 * @param	int		Topic ID
	 * @return	@e void
	 */
	public function updateViewCounter( $tid )
	{
		/* Only do this if we have delayed topic views */
		if ( ! $this->settings['update_topic_views_immediately'] )
		{
			/* Grab count */
			$count = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as topicviews',
									 				  'from'	=> 'topic_views',
									 				  'where'	=> 'views_tid=' . intval( $tid ) ) );
			
			
			if ( $count['topicviews'] )
			{
				/* Update */
				$this->DB->update( 'topics', 'views=views+' . intval( $count['topicviews'] ), "tid=" . intval( $tid ), false, true );
				
				/* Delete */
				$this->DB->delete( 'topic_views', 'views_tid=' . intval( $tid ) );
			}
		}
	}
	
	/**
	 * Clean the topic title
	 *
	 * @param	string	Raw title
	 * @return	string	Cleaned title
	 */
	public function cleanTopicTitle( $title="" )
	{
		if( $this->settings['etfilter_punct'] )
		{
			$title	= preg_replace( '/\?{1,}/'      , "?"    , $title );		
			$title	= preg_replace( "/(&#33;){1,}/" , "&#33;", $title );
		}

		//-----------------------------------------
		// The DB column is 250 chars, so we need to do true mb_strcut, then fix broken HTML entities
		// This should be fine, as DB would do it regardless (cept we can fix the entities)
		//-----------------------------------------

		$title = preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?$/", '', IPSText::mbsubstr( $title, 0, 250 ) );
		
		$title = IPSText::stripAttachTag( $title );
		$title = str_replace( "<br />", "", $title  );
		$title = trim( $title );

		return $title;
	}
	
	/**
	 * Used when isPublished is set to auto
	 * @param string $type
	 * @return boolean
	 */
	protected function _checkPostModeration( $type )
	{
		$memberData = $this->getAuthor();
				
		/* Does this member have mod_posts enabled? */
		if ( IPSMember::isOnModQueue( $memberData ) )
		{
			return FALSE;
		}
		
		/* Group can bypass mod queue */
		if ( $memberData['g_avoid_q'] )
		{
			return TRUE;
		}
				
		/* Check to see if this forum has moderation enabled */
		switch( intval( $this->getForumData('preview_posts') ) )
		{
			default:
			case 0:
				return TRUE;
			break;
			case 1:
				return FALSE;
			break;
			case 2:
				return ( $type == 'new' ) ? FALSE : TRUE;
			break;
			case 3:
				return ( $type == 'reply' ) ? FALSE : TRUE;
			break;
		}
		
		/* Our post can be seen! */
		return TRUE;
	}
	/**
	 * Check Multi Quote
	 * Checks for quoted information
	 *
	 * @param	string	Any raw post
	 * @return	string	Formatted post
	 */
	protected function _checkMultiQuote( $postContent )
	{
		$raw_post = '';
		
		if ( ! $this->request['qpid'] )
		{
			$this->request['qpid'] = IPSCookie::get('mqtids');
			
			if ( $this->request['qpid'] == "," )
			{
				$this->request['qpid'] = "";
			}
		}
		else
		{
			//-----------------------------------------
			// Came from reply button
			//-----------------------------------------
			
			$this->request['parent_id'] = $this->request['qpid'];
		}

		$this->request['qpid'] = preg_replace( '/[^,\d]/', "", trim($this->request['qpid']) );

		if ( $this->request['qpid'] )
		{
			$this->quoted_pids = preg_split( '/,/', $this->request['qpid'], -1, PREG_SPLIT_NO_EMPTY );
			
			//-----------------------------------------
			// Get the posts from the DB and ensure we have
			// suitable read permissions to quote them
			//-----------------------------------------
			
			if( count( $this->quoted_pids ) )
			{
				$perm_id	= $this->getAuthor('org_perm_id') ? $this->getAuthor('org_perm_id') : $this->getAuthor('g_perm_id');
				$perm_array = explode( ",", $perm_id );

				$this->DB->build( array( 
										'select' 	=> 'p.*' ,
										'from'		=> array( 'posts' => 'p' ),
										'where'		=> "p.pid IN(" . implode( ',', $this->quoted_pids ) . ")",
										'add_join'	=> array( array( 'select'	=> 't.*',
																	 'from'		=> array( 'topics' => 't' ),
																	 'where'	=> 't.tid=p.topic_id',
																	 'type'		=> 'left' ),
															  array( 'select'   => 'member_id, members_display_name',
																	 'from'     => array( 'members' => 'm' ),
																	 'where'    => 'p.author_id=m.member_id',
																	 'type'     => 'left' ) ) ) );
															
				$this->DB->execute();
				
				while( $tp = $this->DB->fetch() )
				{
					$canSee = $this->registry->getClass('topics')->canView( $tp );
					
					/* Direct quote/reply access */
					if ( $this->request['qpid'] && ! $canSee )
					{
						$msg = str_replace( 'EX_', '', $this->registry->getClass('topics')->getErrorMessage() );
			
						$this->registry->output->showError( $msg, 10340, null, null, 404 );
					}
					
					if ( $canSee === true && $this->registry->permissions->check( 'read', $this->registry->class_forums->forum_by_id[ $tp['forum_id'] ], $perm_array ) === true )
					{
						$tmp_post          = $this->_afterPostCompile( $tp['post'], 'reply' );
						$tp['author_name'] = ( $tp['members_display_name'] ) ? $tp['members_display_name'] : $tp['author_name'];
						
						if ( $this->settings['strip_quotes'] )
						{
							$tmp_post = trim( $this->_recursiveKillQuotes( $tmp_post ) );
						}
						
						$tmp_post = IPSText::getTextClass( 'bbcode' )->stripSharedMedia( $tmp_post );

						if( $tmp_post )
						{
							$_post		= preg_replace( '/(<br\s*\/?>\s*)+$/', "<br />", "<br />{$tmp_post}<br />" );
							$raw_post	.= "[quote name='" . IPSText::getTextClass( 'bbcode' )->makeQuoteSafe($tp['author_name']) . "' timestamp='" . $tp['post_date'] . "' post='" . $tp['pid'] . "']{$_post}[/quote]<br />";
						}
					}
				}
				
				$raw_post = trim($raw_post) . "<br />";
			}
		}

		//-----------------------------------------
		// Make raw POST safe for the text area
		//-----------------------------------------

		$raw_post .= $postContent;
		
		return $raw_post;
	}
	
	
	/**
	 * Cheap and probably nasty way of killing quotes
	 *
	 * @return  string
	 */
	protected function _recursiveKillQuotes( $t )
	{
		return IPSText::getTextClass( 'bbcode' )->stripQuotes( $t );
	}
}