<?php
/**
 * Blog ThisInterfaces
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @author		MattMecham
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $ 
 */

interface iBlogThis
{
	/**
	 * CONSTRUCTOR
	 *
	 * @param  object  $registry
	 * @param  string
	 * @param  array	array of items (id1, id2, etc);
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $app='core', $incoming=array() );
		
	/**
	 * check permission
	 *
	 * @return boolean
	 */
	public function checkPermission();
	
	/**
	 * Returns the data for the items
	 * Data should be post textarea ready
	 *
	 * @return	array( title / content )
	 */
	public function fetchData();
}

class blogthis
{
	/**
	* Registry Object Shortcuts
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
	
	/**
	 * @var	string
	 */
	protected $_app = '';
	
	/**
	 * @var	object
	 */
	protected $_class = '';
	
	/**
	 * CONSTRUCTOR
	 *
	 * @param  blog_show $class
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $app='core', $incoming=array() )
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
		
		/* Load the Blog functions library */
		if ( ! $this->registry->isClassLoaded('blogFunctions') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
			$this->registry->setClass( 'blogFunctions', new $classToLoad($this->registry) );
		}
		
		if ( ! is_object( $this->_class ) )
		{ 
			/* Do we have a plug in? */
			$_f = IPSLib::getAppDir( $app ) . '/extensions/blogthis/bt_' . $app . '.php';
			
			if ( ! is_file( $_f ) )
			{
				$_f = IPSLib::getAppDir('blog') . '/extensions/blogthis/bt_' . $app . '.php';
			}
			
			if ( is_file( $_f ) )
			{
				$classToLoad  = IPSLib::loadLibrary( $_f, 'bt_' . $app, 'blog' );
				$this->_class = new $classToLoad( $registry, $app, $incoming );
			}
			else
			{
				$classToLoad  = IPSLib::loadLibrary( '', 'blogthis_base', 'blog' );
				$this->_class = new $classToLoad( $registry, $app, $incoming );
			}
		}
	}
	
	/**
	 * Trap calls for modules
	 *
	 * @access	public
	 * @param	string
	 * @param	mixed		void, or an array of arguments
	 * @return	mixed		string, or an error
	 */
	public function __call( $funcName, $args )
	{
		if ( is_object( $this->_class ) )
		{
			return $this->_class->$funcName( $args );
		}
	}
}

class blogthis_base
{
	/**
	 * CONSTRUCTOR
	 *
	 * @param  array   $blog      Array of data from the current blog
	 * @param  object  $registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $app='core', $incoming=array() )
	{
		if ( ! $this->registry )
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
		}
	}
	
	/**
	 * Returns the data for the items
	 * Data should be post textarea ready
	 *
	 * @return	array( title / content )
	 */
	public function fetchData()
	{
		$title = IPSText::parseCleanValue( base64_decode($_REQUEST['title']) );
		$url   = IPSText::parseCleanValue( base64_decode($_REQUEST['url']) );
	
		$return = array( 'title' => '', 'content' => '', 'topicData' => array() );
		$return['title']	= $title;
		$return['content']	= "<br /><br /><br />{$this->lang->words['bt_source']} [url=\"{$url}\"]{$title}[/url]<br />";
		
		return $return;
	}
}