<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Forums application initialization
 * Last Updated: $LastChangedDate: 2012-05-25 12:09:04 -0400 (Fri, 25 May 2012) $
 * </pre>
 *
 * @author 		$Author: mmecham $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		14th May 2003
 * @version		$Rev: 10796 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class app_class_forums
{
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
	 * Constructor
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		
		if ( IN_ACP )
		{
			try
			{
				require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );/*noLibHook*/
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/admin_forum_functions.php", 'admin_forum_functions', 'forums' );
				
				$this->registry->setClass( 'class_forums', new $classToLoad( $registry ) );
				$this->registry->getClass('class_forums')->strip_invisible = 0;
			}
			catch( Exception $error )
			{
				IPS_exception_error( $error );
			}
		}
		else
		{
			try
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
				$this->registry->setClass( 'class_forums', new $classToLoad( $registry ) );
				$this->registry->getClass('class_forums')->strip_invisible = 1;
			}
			catch( Exception $error )
			{
				IPS_exception_error( $error );
			}
		}
		
		//---------------------------------------------------
		// Grab and cache the topic now as we need the 'f' attr for
		// the skins...
		//---------------------------------------------------
		
		if ( ! empty( $_GET['showtopic'] ) )
		{
			/* Load tagging stuff */
			if ( ! $this->registry->isClassLoaded('tags') )
			{
				require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
				$this->registry->setClass( 'tags', classes_tags_bootstrap::run( 'forums', 'topics' )  );
			}
		
			$this->request['t'] = intval( $_GET['showtopic']  );
			
			$this->DB->build( array( 'select'   => 't.*',
									 'from'     => array( 'topics' => 't' ),
									 'where'    => 't.tid=' . $this->request['t'],
									 'add_join' => array( $this->registry->tags->getCacheJoin( array( 'meta_id_field' => 't.tid' ) ) ) ) );
			$this->DB->execute();
			
			$topic = $this->DB->fetch();
			
			$this->registry->getClass('class_forums')->topic_cache = $topic;
	   
		    $this->request['f'] =  $topic['forum_id'];
			
			/* Update query location */
			$this->member->sessionClass()->addQueryKey( 'location_2_id', ipsRegistry::$request['f'] );
		}
		
		$this->registry->getClass('class_forums')->forumsInit();
		
		//-----------------------------------------
		// Set up moderators
		//-----------------------------------------
		
		$this->memberData = IPSMember::setUpModerator( $this->memberData );
		
		/* Other set up for this app */
		$this->settings['topic_title_max_len'] = ( $this->settings['topic_title_max_len'] > 2 ) ? $this->settings['topic_title_max_len'] : 2;
	}
	
	/**
	 * Do some set up after ipsRegistry::init()
	 * 
	 * @return	@e void
	 */
	public function afterOutputInit()
	{
		if ( !empty( $_GET['showtopic'] ) AND is_array( $this->registry->getClass('class_forums')->topic_cache ) )
		{
			$topic = $this->registry->getClass('class_forums')->topic_cache;
			$topic['title_seo'] = ( $topic['title_seo'] ) ? $topic['title_seo'] : IPSText::makeSeoTitle( $topic['title'] );
			
			/* Check TOPIC permalink... */
			$this->registry->getClass('output')->checkPermalink( $topic['title_seo'] );
			
			/* Add canonical tag */
			$this->registry->getClass('output')->addCanonicalTag( ( $this->request['st'] ) ? 'showtopic=' . $topic['tid'] . '&st=' . $this->request['st'] : 'showtopic=' . $topic['tid'], $topic['title_seo'], 'showtopic' );
			
			/* Store root doc URL */
			$this->registry->getClass('output')->storeRootDocUrl( $this->registry->getClass('output')->buildSEOUrl( 'showtopic=' . $topic['tid'], 'publicNoSession', $topic['title_seo'], 'showtopic' ) );
		}
		else if ( !empty( $_GET['showforum'] ) )
		{
			$_GET['showforum']					= intval($_GET['showforum']);
			ipsRegistry::$request['showforum']	= intval(ipsRegistry::$request['showforum']);
			
			$data				= $this->registry->getClass('class_forums')->forumsFetchData( $_GET['showforum'] );
			$data['name_seo']	= ( $data['name_seo'] ) ? $data['name_seo'] : IPSText::makeSeoTitle( $data['name'] );
			
			/* Check FORUM permalink... */
			$this->registry->getClass('output')->checkPermalink( $data['name_seo'] );
			
			/* Add canonical tag */
			if( $data['id'] )
			{
				$this->registry->getClass('output')->addCanonicalTag( ( $this->request['st'] ) ? 'showforum=' . $data['id'] . '&st=' . $this->request['st'] : 'showforum=' . $data['id'], $data['name_seo'], 'showforum' );
				
				/* Store root doc URL */
				$this->registry->getClass('output')->storeRootDocUrl( $this->registry->getClass('output')->buildSEOUrl( 'showforum=' . $data['id'], 'publicNoSession', $data['name_seo'], 'showforum' ) );
			}
		}
		else if ( !empty( $_GET['showannouncement'] ) )
		{
			$announce	= $this->caches['announcements'][ intval( $_GET['showannouncement'] ) ];
													
			if ( $announce['announce_id'] )
			{
				$_seoTitle	= $announce['announce_seo_title'] ? $announce['announce_seo_title'] : IPSText::makeSeoTitle( $announce['announce_title'] );
				
				$this->registry->getClass('output')->checkPermalink( $_seoTitle );
				
				/* Add canonical tag */
				if( $announce['announce_id'] )
				{
					$this->registry->getClass('output')->addCanonicalTag( 'showannouncement=' . $announce['announce_id'] . ( $_GET['f'] ? '&amp;f=' . intval($_GET['f']) : '&amp;f=0' ), $_seoTitle, 'showannouncement' );
					
					/* Store root doc URL */
					$this->registry->getClass('output')->storeRootDocUrl( $this->registry->getClass('output')->buildSEOUrl( 'showannouncement=' . $announce['announce_id'] . ( $_GET['f'] ? '&amp;f=' . intval($_GET['f']) : '&amp;f=0' ), 'publicNoSession', $_seoTitle, 'showannouncement' ) );
				}
			}
		}
	}
}