<?php
/**
 * @file		plugin_attachments.php 	Shared media plugin: attachments
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: rtissier $
 * @since		3/8/2011
 * $LastChangedDate: 2011-03-16 19:10:59 -0400 (Wed, 16 Mar 2011) $
 * @version		v3.3.3
 * $Revision: 8108 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_core_attachments
 * @brief		Provide ability to share attachments via editor
 */
class plugin_core_attachments
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
	 * Attachments class
	 *
	 * @var		object
	 */
	protected $attach;

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
			return $this->lang->words['sharedmedia_attachments'];
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
		// How many attachments do we have?
		//-----------------------------------------
		
		$mimes	= $this->cache->getCache('attachtypes');
		$st		= intval($this->request['st']);
		$each	= 30;
		$where	= '';
		
		if( $string )
		{
			$where	= " AND attach_file LIKE '%{$string}%'";
		}

		$count	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'attachments', 'where' => "attach_member_id={$this->memberData['member_id']}" . $where ) );
		$rows	= array();
		
		$pages	= $this->registry->output->generatePagination( array(	'totalItems'		=> $count['total'],
																		'itemsPerPage'		=> $each,
																		'currentStartValue'	=> $st,
																		'seoTitle'			=> '',
																		'method'			=> 'nextPrevious',
																		'noDropdown'		=> true,
																		'ajaxLoad'			=> 'mymedia_content',
																		'baseUrl'			=> "app=core&amp;module=ajax&amp;section=media&amp;do=loadtab&amp;tabapp=core&amp;tabplugin=attachments&amp;search=" . urlencode($string) )	);

		$this->DB->build( array( 'select' => '*', 'from' => 'attachments', 'where' => "attach_member_id={$this->memberData['member_id']}" . $where, 'order' => 'attach_date DESC', 'limit' => array( $st, $each ) ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			if( $r['attach_thumb_location'] )
			{
				$image	= $this->settings['upload_url'] . '/' . $r['attach_thumb_location'];

				$dims	= IPSLib::scaleImage( array( 
														'cur_width'		=> $r['attach_thumb_width'],
														'cur_height'	=> $r['attach_thumb_height'],
														'max_width'		=> 80,
														'max_height'	=> 80,
												)		);
				$width	= $dims['img_width'];
				$height	= $dims['img_height'];
			}
			else
			{
				$image	= $this->settings['public_dir'] . $mimes[ $r['attach_ext'] ]['atype_img'];
				$width	= 0;
				$height	= 0;
			}

			$rows[]	= array(
							'image'		=> $image,
							'width'		=> $width,
							'height'	=> $height,
							'title'		=> IPSText::truncate( $r['attach_file'], 25 ),
							'desc'		=> '',
							'insert'	=> "core:attachments:" . $r['attach_id'],
							);
		}

		return $this->registry->output->getTemplate('editors')->mediaGenericWrapper( $rows, $pages, 'core', 'attachments' );
	}

	/**
	 * Return the HTML output to display
	 *
	 * @param	int		$attachId	Attachment ID to show
	 * @return	@e string
	 */
	public function getOutput( $attachId=0 )
	{
		$attachId	= intval($attachId);
		
		if( !$attachId )
		{
			return '';
		}
		
		if( !is_object($this->attach) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
			$this->attach	=  new $classToLoad( $this->registry );
		}
		
		$attachment	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'attachments', 'where' => 'attach_id=' . $attachId ) );
		
		$this->attach->type	= $attachment['attach_rel_module'];
		$this->attach->init();
		
		$output	= $this->attach->renderSingleAttachment( $attachment );
		
		return $output;
	}
	
	/**
	 * Verify current user has permission to post this
	 *
	 * @param	int		$attachId	Attachment ID to show
	 * @return	@e bool
	 */
	public function checkPostPermission( $attachId )
	{
		$attachId	= intval($attachId);
		
		if( !$attachId )
		{
			return '';
		}
		
		if( $this->memberData['g_is_supmod'] OR $this->memberData['is_mod'] )
		{
			return '';
		}
		
		$attachment	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'attachments', 'where' => 'attach_id=' . $attachId ) );
		
		if( $this->memberData['member_id'] AND $attachment['attach_member_id'] == $this->memberData['member_id'] )
		{
			return '';
		}
		
		return 'no_permission_shared';
	}
}