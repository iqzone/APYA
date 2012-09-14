<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * IP.Blog XML responses
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class xmlResponse
{
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
	* Constructor
	*
	* @access	public
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
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	* Return an error message
	*
	* @access	public
	* @param	string		Error to return
	* @return	@e void
	*/	
	public function returnError( $message='' )
	{
		@header('Content-Type: text/xml');
		@header('Pragma: public');
		
		echo <<<XML
<?xml version="1.0" encoding="iso-8859-1"?>
<response>
	<error>1</error>
	<message>{$message}</message>
</response>
XML;

		exit();
	}
	
	/**
	* Return an success flag
	*
	* @access	public
	* @return	@e void
	*/	
	public function returnSuccess()
	{
		@header('Content-Type: text/xml');
		@header('Pragma: public');
		
		echo <<<XML
<?xml version="1.0" encoding="iso-8859-1"?>
<response>
	<error>0</error>
</response>
XML;
		exit();
	}
	
	/**
	* Returns the trackback list
	*
	* @access	public
	* @param	array 		Items to include
	* @param	array 		Current entry
	* @param	string		Truncated/parsed description
	* @return	@e void
	*/	
	public function sendTrackbackList( $items, $entry, $entry_desc )
	{
		@header('Content-Type: text/xml');
		@header('Pragma: public');
		
		echo <<<XML
<?xml version="1.0" encoding="iso-8859-1"?>
<response>
	<error>0</error>
	<rss version="0.91">
		<channel>
			<title>TrackBack for {$entry['entry_name']}</title>
			<link>{$this->settings['base_url']}app=blog&amp;blogid={$entry['blog_id']}&amp;showentry={$entry['entry_id']}</link>
			<description>{$entry_desc}</description>
			<language>en-us</language>
XML;

foreach( $items as $trackback )
{
	echo <<<XML
			<item>
				<title>{$trackback['trackback_title']}</title>
				<link>{$trackback['trackback_url']}</link>
				<description>{$trackback['trackback_excerpt']}</description>
			</item>
XML;
}

echo <<<XML
		</channel>
	</rss>
</response>
XML;

		exit();
	}
}