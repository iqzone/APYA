<?php
/**
 * @file		plugin_entries.php 	Shared media plugin: blog entries
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		3/9/2011
 * $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * @version		v2.5.2
 * $Revision: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_blog_entries
 * @brief		Provide ability to share blog entries via editor
 */
class plugin_blog_entries
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
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
		
		$this->lang->loadLanguageFile( array( 'public_blog' ), 'blog' );
	}
	
	/**
	 * Return the tab title
	 *
	 * @return	@e string
	 */
	public function getTab()
	{
		if( $this->memberData['member_id'] )
		{
			return $this->lang->words['sharedmedia_blog'];
		}
	}
	
	/**
	 * Return the HTML to display the tab
	 *
	 * @return	@e string
	 */
	public function showTab( $string )
	{
		//-----------------------------------------
		// Are we a member?
		//-----------------------------------------
		
		if( !$this->memberData['member_id'] )
		{
			return '';
		}

		//-----------------------------------------
		// How many approved events do we have?
		//-----------------------------------------
		
		$st		= intval($this->request['st']);
		$each	= 30;
		$where	= '';
		$rows	= array();
		
		if( $string )
		{
			$where	= " AND ( entry_name LIKE '%{$string}%' OR entry LIKE '%{$string}%' )";
		}

		$count	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'blog_entries', 'where' => "entry_status='published' AND entry_author_id={$this->memberData['member_id']}" . $where ) );
		
		$pages	= $this->registry->output->generatePagination( array(	'totalItems'		=> $count['total'],
																		'itemsPerPage'		=> $each,
																		'currentStartValue'	=> $st,
																		'method'			=> 'nextPrevious',
																		'noDropdown'		=> true,
																		'baseUrl'			=> "app=core&amp;module=ajax&amp;section=media&amp;do=loadtab&amp;tabapp=blog&amp;tabplugin=entries&amp;search=" . urlencode($string) )	);
		
		if ( $count['total'] )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'blog_entries',
									 'where'  => "entry_status='published' AND entry_author_id={$this->memberData['member_id']}" . $where,
									 'order'  => 'entry_last_update DESC',
									 'limit'  => array( $st, $each )
							 )		);
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				$rows[]	= array(
								'image'		=> $this->settings['img_url'] . '/sharedmedia/entries.png',
								'width'		=> 0,
								'height'	=> 0,
								'title'		=> IPSText::truncate( $r['entry_name'], 25 ),
								'desc'		=> IPSText::truncate( strip_tags( IPSText::stripAttachTag( IPSText::getTextClass('bbcode')->stripAllTags( $r['entry'] ) ), '<br>' ), 100 ),
								'insert'	=> "blog:entries:" . $r['entry_id'],
								);
			}
		}

		return $this->registry->output->getTemplate('editors')->mediaGenericWrapper( $rows, $pages, 'blog', 'entries' );
	}

	/**
	 * Return the HTML output to display
	 *
	 * @param	int		$entryId		Entry ID to show
	 * @return	@e string
	 */
	public function getOutput( $entryId=0 )
	{
		$entryId	= intval($entryId);
		
		if( !$entryId )
		{
			return '';
		}

		$entry	= $this->DB->buildAndFetch( array(
												'select'	=> 'e.*',
												'from'		=> array( 'blog_entries' => 'e' ),
												'where'		=> "e.entry_status='published' AND e.entry_id=" . $entryId,
												'add_join'	=> array(
																	array(
																		'select'	=> 'b.*',
																		'from'		=> array( 'blog_blogs' => 'b' ),
																		'where'		=> 'b.blog_id=e.blog_id',
																		'type'		=> 'left',
																		)
																	)
										)		);

		return $this->registry->output->getTemplate('blog_portal')->bbCodeEntry( $entry );
	}
	
	/**
	 * Verify current user has permission to post this
	 *
	 * @param	int		$entryId	Entry ID to show
	 * @return	@e bool
	 */
	public function checkPostPermission( $entryId )
	{
		$entryId	= intval($entryId);
		
		if( !$entryId )
		{
			return '';
		}
		
		if( $this->memberData['g_is_supmod'] OR $this->memberData['is_mod'] )
		{
			return '';
		}
		
		$entry	= $this->DB->buildAndFetch( array(
												'select'	=> 'e.*',
												'from'		=> array( 'blog_entries' => 'e' ),
												'where'		=> "e.entry_status='published' AND e.entry_id=" . $entryId,
												'add_join'	=> array(
																	array(
																		'select'	=> 'b.*',
																		'from'		=> array( 'blog_blogs' => 'b' ),
																		'where'		=> 'b.blog_id=e.blog_id',
																		'type'		=> 'left',
																		)
																	)
										)		);
		
		if( $this->memberData['member_id'] AND $entry['entry_author_id'] == $this->memberData['member_id'] )
		{
			return '';
		}
		
		return 'no_permission_shared';
	}
}