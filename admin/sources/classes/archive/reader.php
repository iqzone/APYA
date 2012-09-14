<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Archive: Reader
 * By Matt Mecham
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		18th November 2011
 * @version		$Revision: 8644 $
 */

class classes_archive_reader
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
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
	
	/* Switch fields */
	protected $fields = array();
	/**
	 * Set current app
	 */
	private $_app = '';
	
	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		/* Make registry objects */
		$this->registry		=  ipsRegistry::instance();
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			=  $this->registry->getClass('class_localization');
		$this->member		=  $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		=  $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		
		/* Check for class_forums */
		if ( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
			$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
			$this->registry->class_forums->forumsInit();
		}
		
		/* Load topic class */
		if ( ! $this->registry->isClassLoaded('topics') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
			$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}
		
		/* Language class */
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		/* Fetch engine class */
		$this->settings['archive_engine'] = ( $this->settings['archive_engine'] ) ? $this->settings['archive_engine'] : 'sql';
		
		/* Load up archive class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/reader/' . $this->settings['archive_engine'] . '.php', 'classes_archive_reader_' . $this->settings['archive_engine'] );
		$this->engine = new $classToLoad();
		
		$this->fields = $this->registry->topics->getPostTableFields();
	}

	/**
	 * Set the current application
	 * @param string $app
	 */
	public function setApp( $app )
	{
		$this->_app = $app;
	}
	
	/**
	 * Get the application
	 * @return string
	 */
	public function getApp()
	{
		return $this->_app;
	}
	
	/**
	 * Fetch a topic's post count
	 * @param	int		Topic ID
	 * @param	array	Masks [visible, hidden, sdelete]
	 * @return  int
	 */
	public function getPostCount( $tid, $masks )
	{
		$masks = ( is_array( $masks ) ) ? $masks : array( $masks );
	
		return $this->engine->getPostCount( $tid, $masks );
	}
	
	/**
	 * getPosts
	 * Fetches posts based on different critera
	 * @param	array	Filters (see below for specifics)
	 * @return	array
	 *
	 * FILTERS:
	 * topicId			Get posts matching the (array) topic ids, (int) topic ID
	 * forumId			Get posts matching the (array) topic ids, (int) forum ID
	 * postId			Get posts matching the (array) post ids, (int) post id
	 * memberData		Set memberData (this->memberData is used otherwise)
	 * onlyViewable		Set whether this member can view them or not. (default is true ) NOTE: Will not check to see if parent topic is viewable!
	 * onlyVisible 		Set whether to skip unapproved posts where permission allows (default is true)
	 * postType			array of 'sdelete', 'visible', 'hidden', 'pdeleted' (if you specify these, permission checks are NOT performed)
	 * sortField		Sort key (date, pid, etc)
	 * sortOrder		asc/desc
	 * pidIsGreater		Where PID is greater than x
	 * dateIsGreater	Where DATE is greater than UNIX
	 * skipForumCheck	Skips the forum ID IN list check to ensure you have access to view (good for when using perms elsewhere)
	 * parse			Parses post content
	 * limit, offset	Limit the amount of results in the returned query
	 * getCount			fetch count without limit
	 *
	 */
	public function getPosts( $filters )
	{
		return $this->engine->getPosts( $filters );
	}
	
	/**
	 * Get fields
	 * @return array
	 */
	public function getFields()
	{
		return $this->registry->topics->getPostTableFields();
	}
	
	/**
	 * Fetches topic data
	 * @param array $data
	 */
	public function get( $data )
	{
		/* Clean up a bit */
		$data['offset'] = intval( $data['offset'] );
		$data['limit']  = intval( $data['limit'] );
		
		return $this->engine->getData( $data );
	}
	
	/**
	 * Take an archive row and returned native friendly array
	 * @param array $post
	 * @return array
	 */
	public function archiveToNativeFields( $post )
	{
		return $this->registry->topics->archivePostToNativeFields( $post );
	}
	
	
}
