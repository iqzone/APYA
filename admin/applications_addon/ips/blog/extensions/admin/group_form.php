<?php
/**
 * @file		group_form.php 	Blog group editing form
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * @version		v2.5.2
 * $Revision: 4 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @interface	admin_group_form__blog
 * @brief		Blog group editing form
 *
 */
class admin_group_form__blog implements admin_group_form
{	
	/**
	 * Tab name (leave it blank to use the default application title)
	 *
	 * @var		$tab_name
	 */
	public $tab_name = "";

	/**
	 * Returns HTML tabs content for the page
	 *
	 * @param	array		$group		Group data
	 * @param	integer		$tabsUsed	Number of tabs used so far (your ids should be this +1)
	 * @return	@e array Array of 'tabs' (HTML for the tabs), 'content' (HTML for the content), 'tabsUsed' (number of tabs you have used)
	 */
	public function getDisplayContent( $group=array(), $tabsUsed = 2 )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = ipsRegistry::getClass('output')->loadTemplate( 'cp_skin_blog_group_form', 'blog' );
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_blog' ), 'blog' );

		//-----------------------------------------
		// Show...
		//-----------------------------------------
		$group['g_blog_settings'] = unserialize( $group['g_blog_settings'] );
		
		return array( 'tabs' => $this->html->acp_blog_group_form_tabs( $group, ( $tabsUsed + 1 ) ), 'content' => $this->html->acp_blog_group_form_main( $group, ( $tabsUsed + 1 ) ), 'tabsUsed' => 1 );
	}
	
	/**
	 * Process the entries for saving and return
	 *
	 * @return	@e array Array of keys => values for saving
	 */
	public function getForSave()
	{
		$return = array('g_blog_do_html'			=> ipsRegistry::$request['g_blog_do_html'],
						'g_blog_do_commenthtml'		=> intval( ipsRegistry::$request['g_blog_do_commenthtml'] ),
						'g_blog_allowpoll'			=> ipsRegistry::$request['g_blog_allowpoll'],
						'g_blog_allowprivate'		=> ipsRegistry::$request['g_blog_allowprivate'],
						'g_blog_allowprivclub'		=> ipsRegistry::$request['g_blog_allowprivclub'],
						'g_blog_alloweditors'		=> ipsRegistry::$request['g_blog_alloweditors'],
						'g_blog_attach_max'			=> ipsRegistry::$request['g_blog_attach_max'],
						'g_blog_attach_per_entry'	=> ipsRegistry::$request['g_blog_attach_per_entry'],
						'g_blog_allowskinchoose'	=> ipsRegistry::$request['g_blog_allowskinchoose'],
						'g_blog_preventpublish'		=> ipsRegistry::$request['g_blog_preventpublish'],
						'g_blog_settings'			=> serialize( array('g_blog_allowview'    => ipsRegistry::$request['g_blog_allowview'],
																		'g_blog_allowcomment' => ipsRegistry::$request['g_blog_allowcomment'],
																		'g_blog_allowcreate'  => ipsRegistry::$request['g_blog_allowcreate'],
																		'g_blog_allowlocal'   => ipsRegistry::$request['g_blog_allowlocal'],
																		'g_blog_allowownmod'  => ipsRegistry::$request['g_blog_allowownmod'],
																		'g_blog_maxblogs'	  => ipsRegistry::$request['g_blog_maxblogs'],
																		'g_blog_allowdelete'  => ipsRegistry::$request['g_blog_allowdelete'],
																		'g_blog_rsspergo'	  => intval( ipsRegistry::$request['g_blog_rsspergo'] )
																 )		)
						);

		return $return;
	}
}