<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Profile Plugin Library
 * Last Updated: $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class profile_blog extends profile_plugin_parent
{
	/**
	 * return HTML block
	 *
	 * @access	public
	 * @param	array		Member information
	 * @return	string		HTML block
	 */
    public function return_html_block( $member=array() ) 
    {
		/* Get blog API */
		require_once( IPS_ROOT_PATH . 'api/api_core.php' );/*noLibHook*/
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'api/blog/api_blog.php', 'apiBlog' );
		
		$blog_api = new $classToLoad();
		
		/* Get parsing class */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/parsing.php', 'blogParsing', 'blog' );
		$this->registry->setClass( 'blogParsing', new $classToLoad( ipsRegistry::instance(), null ) );

		/* Language */
		$this->lang->loadLanguageFile( array( 'public_portal', 'public_blog' ), 'blog' );

		$content  = '';
		$rows     = array();
		$entries  = $blog_api->lastXEntries( 'member', $member['member_id'], 5 );

        if( is_array( $entries) && count( $entries ) )
        {
			$attachments = 0;
			$entry_ids = array();
			
			foreach( $entries as $row )
			{
				$row = $this->registry->getClass('blogParsing')->parseEntry( $row, 1, array( 'entryParse' => 1, 'noPositionInc' => 1 ), $row );
				
				$rows[ $row['entry_id'] ] = $row;
			}
			
			$content = $this->registry->output->getTemplate('blog_portal')->profileBlock( $rows );
		}

		/* Return some output */
		return $content ? $content : $this->registry->getClass('output')->getTemplate('profile')->tabNoContent( 'noblogentries' );
	}
}