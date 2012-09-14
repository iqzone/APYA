<?php
/**
 * Invision Power Services
 * IP.Blog - Unapproved Comments Extension
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 *
 * @author 		$Author: ips_terabyte $ (Orginal: Mark)
 * @copyright	© 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		5th October 2011
 * @version		$Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class plugin_blog_blogcomments
{
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------

		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			= $this->registry->class_localization;
		
		ipsRegistry::getAppClass( 'blog' );
	}
	
	/**
	 * Determine if we can view tab
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e bool
	 */
	public function canView( $permissions )
	{
		return (bool) $this->memberData['g_is_supmod'];
	}
	
	/**
	 * Returns the primary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getPrimaryTab()
	{
		return 'unapproved_content';
	}
	
	/**
	 * Returns the secondary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getSecondaryTab()
	{
		return 'blogcomments';
	}
	
	/**
	 * Execute plugin
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e string
	 */
	public function executePlugin( $permissions )
	{
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		if( !$this->canView( $permissions ) )
		{
			return '';
		}
		
		/* Init vars */
		$st		 = intval($this->request['st']);
		$each	 = 25;
		$results = array();
		
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'blog_comments', 'where' => 'comment_approved=0' ) );
		
		//-----------------------------------------
		// Page links
		//-----------------------------------------
		$pages	= $this->registry->output->generatePagination( array(	'totalItems'		=> $count['total'],
																		'itemsPerPage'		=> $each,
																		'currentStartValue'	=> $st,
																		'baseUrl'			=> "app=core&amp;module=modcp&amp;fromapp=blog&amp;tab=blogcomments",
															)		);
		
		/* Got comments pending approval? */
		if ( $count['total'] )
		{
			$this->DB->build( array( 'select'	=> 'c.*',
									 'from'		=> array( 'blog_comments' => 'c' ),
									 'where'	=> "c.comment_approved=0",
									 'limit'	=> array( $st, $each ),
									 'add_join'	=> array( array( 'select' => 'm.*',
																 'from'   => array( 'members' => 'm' ),
																 'where'  => 'm.member_id=c.member_id',
																 'type'   => 'left' ),
														  array( 'select' => 'pp.*',
																 'from'   => array( 'profile_portal' => 'pp' ),
																 'where'  => 'm.member_id=pp.pp_member_id',
																 'type'   => 'left' ),
														  array( 'select' => 'e.*',
																 'from'   => array( 'blog_entries' => 'e' ),
																 'where'  => 'e.entry_id=c.entry_id',
																 'type'   => 'left' ) )
							 )		);
			$e = $this->DB->execute();
			
			while( $row = $this->DB->fetch( $e ) )
			{
				IPSText::getTextClass('bbcode')->parse_html				= 0;
				IPSText::getTextClass('bbcode')->parse_nl2br			= 1;
				IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
				IPSText::getTextClass('bbcode')->parsing_section		= 'global_comments';
				IPSText::getTextClass('bbcode')->parsing_mgroup			= $row['member_group_id'];
				IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $row['mgroup_others'];
				
				$row['comment_text'] = IPSText::getTextClass('bbcode')->preDisplayParse( $row['comment_text'] );
			
				$results[] = IPSMember::buildDisplayData( $row );
			}
		}
						
		return $this->registry->getClass('output')->getTemplate('blog_portal')->unapprovedComments( $results, $pages );
	}
}