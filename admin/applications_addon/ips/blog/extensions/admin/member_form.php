<?php
/**
 * @file		member_form.php 	IP.Blog member form extension
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
 * @class		admin_member_form__blog
 * @brief		IP.Blog member form extension
 */
class admin_member_form__blog implements admin_member_form
{	
	/**
	 * Tab name, if left blank the application title is used
	 *
	 * @var		$tab_name
	 */
	public $tab_name = "";

	
	/**
	 * Returns sidebar links for this tab
	 * You may return an empty array or FALSE to not have
	 * any links show in the sidebar for this block.
	 *
	 * The links must have 'section=xxxxx&module=xxxxx[&do=xxxxxx]'. The rest of the URL
	 * is added automatically.
	 *
	 * The image must be a full URL or blank to use a default image.
	 *
	 * Use the format:
	 * $array[] = array( 'img' => '', 'url' => '', 'title' => '' );
	 *
	 * @param	array		$member		Member data
	 * @return	@e array	Array of links
	 */
	public function getSidebarLinks( $member=array() )
	{
		$array = array();
				
		return $array;
	}
	
	/**
	 * Returns content for the page.
	 *
	 * @param	array		$member			Member data
	 * @param	integer		$tabsUsed		Tabs used
	 * @return	@e array	Array of tabs, content
	 */
	public function getDisplayContent( $member=array(), $tabsUsed=5 )
	{
		$this->registry = ipsRegistry::instance();
		$this->DB       = $this->registry->DB();
		
		/* Load language and skin */
		$this->html = $this->registry->getClass('output')->loadTemplate('cp_skin_member_form_blog', 'blog');

		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_blog' ), 'blog' );
		
		/* Grab member data */
		$member = IPSMember::load( $member['member_id'], 'extendedProfile' );
		
		/* Load the Blog functions library */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
		$this->registry->setClass( 'blogFunctions', new $classToLoad( $this->registry ) );
	
		/* Load up content blocks lib */ 
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
		$this->registry->setClass( 'cblocks', new $classToLoad( $this->registry ) );

		/* Ensure member's has_blog status is correct */
		$member['has_blog'] = $this->registry->getClass('blogFunctions')->fetchMyBlogs( $member );
		
		/* Build permissions */
		$member = $this->registry->blogFunctions->buildPerms( $member );
		
		/* Load blogs */
		$blogids = array();
		$blogs   = array();
	
		if ( count( $member['has_blog'] ) )
		{
			foreach( $member['has_blog'] as $blogid => $blogdata )
			{
				if ( $member['member_id'] == $blogdata['blog_owner_id'] AND ( ! $blogdata['blog_groupblog'] ) )
				{
					$blogids[] = $blogid;
				}
			}
		}
		
		if ( count( $blogids ) )
		{
			$blogs = $this->registry->blogFunctions->loadBlog( $blogids, $member );
		}
		
		return array( 'tabs' => $this->html->acp_member_form_tabs( $member, ( $tabsUsed + 1 ) ), 'content' => $this->html->acp_member_form_main( $member, $blogs, ( $tabsUsed + 1) ), 'tabsUsed' => 1 );
	}
	
	/**
	 * Process the entries for saving and return
	 *
	 * @return	@e array	Multi-dimensional array (core, extendedProfile) for saving
	 */
	public function getForSave()
	{
		return array( 'core' => array(), 'extendedProfile' => array() );
	}
}