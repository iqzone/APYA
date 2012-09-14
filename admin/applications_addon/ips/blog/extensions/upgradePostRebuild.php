<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Rebuild post content plugin
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
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class postRebuild_blog
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
		
		$this->lang->loadLanguageFile( array( 'admin_blog' ), 'blog' );
		
		ipsRegistry::getAppClass('blog');
	}
	
	/**
	 * Grab the dropdown options
	 *
	 * @access	public
	 * @return	array 		Multidimensional array of contents we can rebuild
	 */
	public function getDropdown()
	{
		$return		= array( array( 'blog_entries', $this->lang->words['rebuild__entry'] ) );
		$return[]	= array( 'blog_comments', $this->lang->words['rebuild__comment'] );
		$return[]	= array( 'blog_cblocks', $this->lang->words['rebuild__cblocks'] );
	    return $return;
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
			case 'blog_entries':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'entry_id as nextid', 'from' => 'blog_entries', 'where' => 'entry_id > ' . $dis, 'limit' => array(1)  ) );
			break;
			
			case 'blog_comments':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'comment_id as nextid', 'from' => 'blog_comments', 'where' => 'comment_id > ' . $dis, 'limit' => array(1)  ) );
			break;

			case 'blog_cblocks':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'cbcus_id as nextid', 'from' => 'blog_custom_cblocks', 'where' => 'cbcus_id > ' . $dis, 'limit' => array(1)  ) );
			break;
		}
		
		return intval( $tmp['nextid'] );
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
			case 'blog_entries':
				$this->DB->build( array( 
										'select'	=> 'e.*',
										'from'		=> array( 'blog_entries' => 'e' ),
										'order' 	=> 'e.entry_id ASC',
										'where'		=> 'e.entry_id > ' . $start,
										'limit' 	=> array( $end ),
										'add_join'	=> array(
																array( 
																		'type'		=> 'left',
																		'select'	=> 'm.member_group_id, m.mgroup_others',
																		'from'		=> array( 'members' => 'm' ),
																		'where' 	=> "m.member_id=e.entry_author_id"
																	)
															)
								)	);
			break;
			
			case 'blog_comments':
				$this->DB->build( array( 
										'select' 	=> 'c.*',
										'from' 		=> array( 'blog_comments' => 'c' ),
										'order' 	=> 'c.comment_id ASC',
										'where'		=> 'c.comment_id > ' . $start,
										'limit' 	=> array( $end ),
										'add_join'	=> array(
																array( 
																		'type'		=> 'left',
																		'select'	=> 'm.member_group_id, m.mgroup_others',
																		'from'		=> array( 'members' => 'm' ),
																		'where' 	=> "m.member_id=c.member_id"
																	),
															)
								)	);
			break;

			case 'blog_cblocks':
				$this->DB->build( array( 
										'select' 	=> 'c.*',
										'from' 		=> array( 'blog_custom_cblocks' => 'c' ),
										'order' 	=> 'c.cbcus_id ASC',
										'where'		=> 'c.cbcus_id > ' . $start,
										'limit' 	=> array( $end ),
										'add_join'	=> array(
																array( 
																		'type'		=> 'left',
																		'from'		=> array( 'blog_cblocks' => 'b' ),
																		'where' 	=> "b.cblock_type='custom' AND b.cblock_ref_id=c.cbcus_id"
																	),
																array( 
																		'type'		=> 'left',
																		'select'	=> 'm.member_group_id, m.mgroup_others',
																		'from'		=> array( 'members' => 'm' ),
																		'where' 	=> "m.member_id=b.member_id"
														  			),
															)
								)	);
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
		$group	= $this->cache->getCache('group_cache');

		$this->parser->parse_smilies	= $this->oldparser->parse_smilies	= 1;
		$this->parser->parse_bbcode		= $this->oldparser->parse_bbcode	= 1;

		switch( $type )
		{
			case 'blog_entries':
				$this->parser->parse_html		= $this->oldparser->parse_html		= $r['entry_html_state'] ? 1 : 0;
				$this->parser->parse_nl2br		= $this->oldparser->parse_nl2br		= $r['entry_html_state'] == 2 ? 1 : 0;
				$this->parser->parse_nl2br		= $this->oldparser->parse_nl2br		= $r['entry_use_emo'] ? 1 : 0;
				$this->parser->parsing_section	= 'blog_entry';

				$rawpost = $this->oldparser->preEditParse( $r['entry'] );
			break;
			
			case 'blog_comments':
				$this->parser->parse_html		= $this->oldparser->parse_html		= $r['comment_html_state'] ? 1 : 0;
				$this->parser->parse_nl2br		= $this->oldparser->parse_nl2br		= $r['comment_html_state'] == 2 ? 1 : 0;
				$this->parser->parse_nl2br		= $this->oldparser->parse_nl2br		= $r['comment_use_emo'] ? 1 : 0;
				$this->parser->parsing_section	= 'global_comments';

				$rawpost = $this->oldparser->preEditParse( $r['comment_text'] );
			break;
			
			case 'blog_cblocks':
				$this->parser->parse_html		= $this->oldparser->parse_html		= $r['cbcus_html_state'] ? 1 : 0;
				$this->parser->parse_nl2br		= $this->oldparser->parse_nl2br		= $r['cbcus_html_state'] == 2 ? 1 : 0;
				
				$this->parser->parsing_section	= 'blog_cblock';

				$rawpost = $this->oldparser->preEditParse( $r['cbcus'] );
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
			case 'blog_entries':
				$this->DB->update( 'blog_entries', array( 'entry' => $newpost ), 'entry_id='.$r['entry_id'] );
				$lastId = $r['entry_id'];
			break;
			
			case 'blog_comments':
				$this->DB->update( 'blog_comments', array( 'comment_text' => $newpost ), 'comment_id='.$r['comment_id'] );
				$lastId = $r['comment_id'];
			break;

			case 'blog_cblocks':
				$this->DB->update( 'blog_custom_cblocks', array( 'cbcus' => $newpost ), 'cbcus_id='.$r['cbcus_id'] );
				$lastId = $r['cbcus_id'];
			break;
		}

		return $lastId;
	}
}