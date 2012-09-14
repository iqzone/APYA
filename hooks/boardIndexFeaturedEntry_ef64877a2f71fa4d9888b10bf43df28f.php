<?php
/**
 * @file		boardIndexFeaturedEntry.php 	Adds the blog featured entry on board index
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		17 May 2011
 * $LastChangedDate: 2011-05-18 04:49:10 -0400 (Wed, 18 May 2011) $
 * @version		v2.5.2
 * $Revision: 8812 $
 */

/**
 * @class		boardIndexFeaturedEntry
 * @brief		Adds the blog featured entry on board index
 */
class boardIndexFeaturedEntry
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$lang
	 * @var		$memberData
	 * @var		$cache
	 */
	public $registry;
	public $DB;
	public $lang;
	public $memberData;
	public $cache;
	
	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		$this->registry		= ipsRegistry::instance();
		$this->DB			= $this->registry->DB();
		$this->lang			= $this->registry->getClass('class_localization');
		$this->memberData	=&$this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
	}
	
	/**
	 * Get template hook output
	 *
	 * @return	@e string	HTML
	 */
	public function getOutput()
	{
		/* Init vars */
		$output = '';
		
		if ( IPSLib::appIsInstalled('blog') )
		{
			$this->memberData['g_blog_settings'] = ( is_array($this->memberData['g_blog_settings']) && count($this->memberData['g_blog_settings']) ) ? $this->memberData['g_blog_settings'] : ( IPSLib::isSerialized($this->memberData['g_blog_settings']) ? unserialize($this->memberData['g_blog_settings']) : array() );
			
			/* Can see? */
			if( $this->memberData['g_blog_settings']['g_blog_allowview'] )
			{
				$this->cache->getCache( array( 'emoticons', 'cblocks' ) );
				
				/* Setup Blog Environment */
				ipsRegistry::getAppClass('blog');
				
				/* Get data from DB */
				$featured = $this->DB->buildAndFetch( array(
														'select'	=> 'e.*',
														'from'		=> array( 'blog_entries' => 'e' ),
														'where'		=> "e.entry_featured=1 AND e.entry_status='published'",
														'add_join'	=> array(
																			array( 'select'	=> 'b.*',
																					'from'	=> array( 'blog_blogs' =>'b' ),
																					'where'	=> 'b.blog_id=e.blog_id',
																					'type'	=> 'left',
																				),
																			array( 'select'	=> 'm.*',
																					'from'	=> array( 'members' => 'm' ),
																					'where'	=> 'm.member_id=e.entry_author_id',
																					'type'	=> 'left'
																				),
																			array( 'select'	=> 'pp.*',
																					'from'	=> array( 'profile_portal' => 'pp' ),
																					'where'	=> 'pp.pp_member_id=m.member_id',
																					'type'	=> 'left',
																				)
																			)
												)		);
				
				/* Got a featured entry? */
				if ( $featured['entry_id'] && !$featured['blog_disabled'] )
				{
					/* Load parsing lib */
					if ( !ipsRegistry::isClassLoaded('blogParsing') )
					{
						$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('blog') . '/sources/parsing.php', 'blogParsing', 'blog' );
						$this->registry->setClass( 'blogParsing', new $classToLoad( $this->registry ) );
					}
					
					/* Parse all ;o */
					$featured['entry_author_id'] = $featured['member_id'];
					$featured['entry_author_name'] = $featured['members_display_name'];
					
					$featured = IPSMember::buildDisplayData( $featured, array( 'reputation' => 0, 'warn' => 0 ) );
					$featured = $this->registry->blogParsing->parseEntry( $featured );
					$featured['entry'] = IPSText::truncate( IPSText::getTextClass('bbcode')->stripAllTags( strip_tags( $featured['entry'], '<br>' ) ), 500 );
					
					$output = $this->registry->output->getTemplate('blog_list')->blogFeaturedEntry( $featured );
				}
			}
		}
		
		return $output;
	}
}