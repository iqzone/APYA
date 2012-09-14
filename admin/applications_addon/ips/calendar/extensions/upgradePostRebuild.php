<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Rebuild post content plugin
 * Last Updated: $Date: 2011-05-05 07:03:47 -0400 (Thu, 05 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 8644 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class postRebuild_calendar
{
	/**
	 * New content parser
	 *
	 * @access	public
	 * @var		object
	 */
	public $parser;

	/**
	 * Old content parser
	 *
	 * @access	public
	 * @var		object
	 */
	public $oldparser;
	
	/**#@+
	 * Registry Object Shortcuts
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
	
	/**
	 * I'm a constructor, twisted constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang	    = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Grab the dropdown options
	 *
	 * @access	public
	 * @return	array 		Multidimensional array of contents we can rebuild
	 */
	public function getDropdown()
	{
		return array( array( 'cal', $this->lang->words['remenu_events'] ) );
	}
	
	/**
	 * Find out if there are any more
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	integer		Start point
	 * @return	integer
	 */
	public function getMax( $type, $dis )
	{
		switch( $type )
		{
			case 'cal':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'event_id', 'from' => 'cal_events', 'limit' => array($dis,1)  ) );
			break;
		}
		
		return intval( $tmp['event_id'] );
	}
	
	/**
	 * Execute the database query to return the results
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	integer		Start point
	 * @param	integer		End point
	 * @return	integer
	 */
	public function executeQuery( $type, $start, $end )
	{
		switch( $type )
		{
			case 'cal':
				$this->DB->build( array( 'select' 	=> 'e.*',
														 'from' 	=> array( 'cal_events' => 'e' ),
														 'order' 	=> 'e.event_id ASC',
														 'where'	=> 'e.event_id > ' . $start,
														 'limit' 	=> array($end),
														 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
														  									'select'	=> 'm.member_group_id, m.mgroup_others',
														  								  	'from'		=> array( 'members' => 'm' ),
														  								  	'where' 	=> "m.member_id=e.event_member_id"
														  						)	)
												) 		);
			break;
		}
	}
	
	/**
	 * Get preEditParse of the content
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	array 		Database record from while loop
	 * @return	string		Content preEditParse
	 */
	public function getRawPost( $type, $r )
	{
		$this->parser->parse_smilies	= $this->oldparser->parse_smilies	= $r['event_smilies'];
		$this->parser->parse_html		= $this->oldparser->parse_html		= 0;
		$this->parser->parse_bbcode		= $this->oldparser->parse_bbcode	= 1;
		$this->parser->parsing_section		= 'calendar';

		switch( $type )
		{
			case 'cal':
				$rawpost = $this->oldparser->preEditParse( $r['event_content'] );
			break;
		}

		return $rawpost;
	}
	
	/**
	 * Store the newly converted content
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	array 		Database record from while loop
	 * @param	string		Newly parsed post
	 * @return	string		Content preEditParse
	 */
	public function storeNewPost( $type, $r, $newpost )
	{
		$lastId	= 0;
		
		switch( $type )
		{
			case 'cal':
				$this->DB->update( 'cal_events', array( 'event_content' => $newpost ), 'event_id=' . $r['event_id'] );
				$lastId = $r['event_id'];
			break;
		}

		return $lastId;
	}
}