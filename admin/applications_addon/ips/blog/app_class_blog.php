<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Last Updated: $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 4 $
 *
 */

class app_class_blog
{
	/**
	* Constructor
	*
	*/
	function __construct( ipsRegistry $registry )
	{
		/* INIT */
		$this->memberData =& $registry->member()->fetchMemberData();
		
		ipsRegistry::$settings['blog_max_cats']    = empty(ipsRegistry::$settings['blog_max_cats']) ? 50 : ipsRegistry::$settings['blog_max_cats'];
		ipsRegistry::$settings['blog_entry_short'] = empty(ipsRegistry::$settings['blog_entry_short']) ? 360 : ipsRegistry::$settings['blog_entry_short'];
		
		// Set up blogFunctions:
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/blogFunctions.php', 'blogFunctions', 'blog' );
		$registry->setClass( 'blogFunctions', new $classToLoad( $registry ) );
		
		// Set up contentBlocks:
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'blog' ) . '/sources/classes/contentblocks/blocks.php', 'contentBlocks', 'blog' );
		$registry->setClass( 'cblocks', new $classToLoad( $registry ) );
		
		/* Got blogs to recache? */
		if ( ! empty($this->memberData['blogs_recache']) || $this->memberData['has_blog'] == 'recache' )
		{
			$registry->blogFunctions->rebuildMyBlogsCache( $this->memberData );
		}
		
		/* ADMIN SETUP */
		if( IN_ACP )
		{
			$registry->DB()->loadCacheFile( IPSLib::getAppDir('blog') . '/sql/' . ips_DBRegistry::getDriverType() . '_blog_admin_queries.php', 'sql_blog_admin_queries' );
		}
		/* PUBLIC SETUP */
		else
		{
			$registry->DB()->loadCacheFile( IPSLib::getAppDir('blog') . '/sql/' . ips_DBRegistry::getDriverType() . '_blog_queries.php', 'sql_blog_queries' );
			
			/* Already been here? */
			if ( empty(ipsRegistry::$settings['_blog_app_class_init']) )
			{
				/* Build the permissions */
				$this->memberData = $registry->blogFunctions->buildPerms( $this->memberData );
				
				/* Load the language */
				$registry->class_localization->loadLanguageFile( array( 'public_blog' ), 'blog' );
								
				/* Load and set active blog */
				if ( ! empty( ipsRegistry::$request['blogid'] ) )
				{
					$registry->getClass('blogFunctions')->setActiveBlog( intval( ipsRegistry::$request['blogid'] ) );
				}
				
				/* Ensure member's has_blog status is correct */
				$this->memberData['has_blog'] = $registry->getClass('blogFunctions')->fetchMyBlogs( $this->memberData );
				
				ipsRegistry::$settings['_blog_app_class_init'] = true;
			}
		}	
	}
	
	/**
	* Checks to see if the blog system is online
	*
	* @param	object		$registry
	* @return	@e void
	*/
	public function afterOutputInit( ipsRegistry $registry )
	{
		ipsRegistry::$settings['blog_upload_url'] = ipsRegistry::$settings['blog_upload_url'] ? ipsRegistry::$settings['blog_upload_url'] : ipsRegistry::$settings['upload_url'];
		
		if( !IN_ACP )
		{
			//-----------------------------------------
			// Show error if blog is not online
			//-----------------------------------------

			if( ! ipsRegistry::$settings['blog_online'] )
			{
				$offlineAccess	= explode( ',', ipsRegistry::$settings['blog_offline_view'] );
				$myGroups		= array_diff( array_merge( array( $registry->member()->getProperty('member_group_id') ), explode( ',', IPSText::cleanPermString( $registry->member()->getProperty('mgroup_others') ) ) ), array('') );
				$accessAnyways	= false;

				foreach( $myGroups as $group )
				{
					if( in_array( $group, $offlineAccess ) )
					{
						$accessAnyways	= true;
					}
				}

				if( !$accessAnyways )
				{
					/* Setup a few things manually, since we're stopping execution early */
					$registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
					$registry->member()->finalizePublicMember();
					
					/* Show the offline message */
					$registry->getClass('output')->offlineMessage	= ipsRegistry::$settings['blog_offline_text'];
					$registry->getClass('output')->showBoardOffline();
				}
			}
			
			/* Are we authorized */
			if( !$this->memberData['g_blog_settings']['g_blog_allowview'] && ipsRegistry::$request['section'] != 'external' )
			{
				if ( ( empty($this->memberData['member_id']) && ipsRegistry::$settings['force_login'] ) || ( ipsRegistry::$settings['board_offline'] == 1 AND ! IPS_IS_SHELL && $this->memberData['g_access_offline'] != 1 ) )
				{
					return false;
				}
				else
				{
					/* Setup a few things manually, since we're stopping execution early */
					$registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
					$registry->member()->finalizePublicMember();
					
					$registry->output->showError( 'no_blogview_permission', 1062, null, null, 403 );
				}
			}
			
			if( ipsRegistry::$request['request_method'] == 'get' )
			{
				if( $_GET['section'] == 'entry' )
				{
					/* Get entry name */
					$entry = $registry->DB()->buildAndFetch( array( 'select' => 'entry_name, entry_name_seo', 'from' => 'blog_entries', 'where' => 'entry_id=' . intval( $_GET['showentry'] ) ) );
					$entry['entry_name_seo'] = $entry['entry_name_seo'] ? $entry['entry_name_seo'] : IPSText::makeSeoTitle( $entry['entry_name'] );

					/* Check permalink... */
					$registry->getClass('output')->checkPermalink( $entry['entry_name_seo'] );
				}
				elseif( $_GET['blogid'] && !isset($_GET['cat']) AND $_REQUEST['module'] != 'manage' )
				{
					$registry->getClass('output')->checkPermalink( $registry->getClass('blogFunctions')->blog['blog_seo_name'] );
				}
				elseif( $_GET['autocom'] == 'blog' or $_GET['automodule'] == 'blog' )
				{
					$registry->output->silentRedirect( ipsRegistry::$settings['base_url'] . "app=blog", 'false', true, 'app=blog' );
				}
			}
		}	
	}	
}
