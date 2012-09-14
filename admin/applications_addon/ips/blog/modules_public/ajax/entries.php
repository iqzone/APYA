<?php
/**
 * @file		entries.php 	Ajax functions for Entries
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * @version		v2.5.2
 * $Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		public_blog_ajax_entries
 * @brief		Ajax functions for Entries
 */
class public_blog_ajax_entries extends ipsAjaxCommand
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs for the ajax handler]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'preview':
				$this->_entryPreview();
			break;
		}
	}
	
	/**
	 * Displays an entry preview
	 *
	 * @return	@e void
	 */
	protected function _entryPreview()
	{
		/* INIT */
		$entry = intval( $this->request['entryid'] );
		$topic = array();
		$posts = array();
		$query = '';
		
		/* Grab entry & blog data */
		$entry = $this->DB->buildAndFetch( array( 'select'	    => 'e.*',
												  'from'		=> array( 'blog_entries' => 'e' ),
												  'where'		=> 'e.entry_id=' . intval( $this->request['entryid'] ),
												  'add_join'	=> array( array( 'select' => 'm.member_id as entry_author_id, m.members_display_name as entry_author_name, m.member_group_id, m.mgroup_others, m.members_seo_name',
																				 'from'   => array( 'members' => 'm' ),
																				 'where'  => 'm.member_id=e.entry_author_id',
																				 'tye'    => 'left' ),
																	  	  array( 'select' => 'pp.*', 
																				 'from'   => array( 'profile_portal' => 'pp' ),
																				 'where'  => 'pp.pp_member_id=m.member_id',
																				 'tye'    => 'left' ) )
										  )		 );
		
		if ( !$entry['entry_id'] OR !$entry['blog_id'] )
		{
			return $this->returnString( 'no_entry' );
		}
		
		/* Set blog.. */
		$this->registry->getClass('blogFunctions')->setActiveBlog( $entry['blog_id'], true );
		$blog = $this->registry->getClass('blogFunctions')->getActiveBlog();
		
		if ( $this->registry->getClass('blogFunctions')->error or !$blog['blog_id'] OR !$blog['blog_name'] OR $blog['blog_type'] == 'external' )
		{
			return $this->returnString( 'no_entry' );
		}
		
		/* Guest permission check */
		if ( !$this->memberData['member_id'] && !$blog['blog_allowguests'] )
		{
			return $this->returnString( 'no_permission' );
		}
		
		/* Draft? */
		if ( $entry['entry_status'] == 'draft' && !$this->blog['allow_entry'] && !$this->memberData['g_is_supmod'] && !$this->memberData['_blogmod']['moderate_can_view_draft'] )
		{
            return $this->returnString( 'no_permission' );
		}
		
		/* Load parsing lib */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/parsing.php', 'blogParsing', 'blog' );
		$this->registry->setClass( 'blogParsing', new $classToLoad( $this->registry ) );
		
		/* Parse all ;o */
		$entry['member_id'] = $entry['entry_author_id'];
		$entry['members_display_name'] = $entry['entry_author_name'];
		
		$entry = IPSMember::buildDisplayData( $entry, array( 'reputation' => 0, 'warn' => 0 ) );
		$entry = $this->registry->blogParsing->parseEntry( $entry );
		$entry['entry'] = IPSText::truncate( IPSText::getTextClass('bbcode')->stripAllTags( strip_tags( $entry['entry'], '<br>' ) ), 500 );
		
		return $this->returnHtml( $this->registry->output->getTemplate('blog_list')->blogPreview( $blog, $entry ) );
	}
}