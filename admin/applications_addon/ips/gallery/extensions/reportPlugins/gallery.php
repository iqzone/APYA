<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Report Center :: Gallery plugin
 * Last Updated: $LastChangedDate: 2011-12-11 19:46:05 -0500 (Sun, 11 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: rtissier $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9981 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class gallery_plugin
{
	/**#@+
	 * Registry objects
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
	protected $cache;
	/**#@-*/
	
	/**
	 * Holds extra data for the plugin
	 *
	 * @access	private
	 * @var		array			Data specific to the plugin
	 */
	public $_extra;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry   = $registry;
		$this->DB	      = $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->member     = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  = $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		$this->lang		  = $this->registry->class_localization;
		
		/* Gallery Object */
		if ( !ipsRegistry::isClassLoaded('gallery') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
			$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
		}
		
		/* Load the language File */
		$registry->class_localization->loadLanguageFile( array( 'public_gallery', 'public_gallery_four' ), 'gallery' );
	}
	
	/**
	 * Display the form for extra data in the ACP
	 *
	 * @param	array 		Plugin data
	 * @param	object		HTML object
	 * @return	@e string		HTML to add to the form
	 */
	public function displayAdminForm( $plugin_data, &$html )
	{
		$return = '';
		
		$return .= $html->addRow(	$this->lang->words['r_supermod'],
									sprintf(  $this->lang->words['r_supermod_info'], $this->settings['_base_url'] ),
									$this->registry->output->formYesNo('report_supermod', (!isset( $plugin_data['report_supermod'] )) ? 1 : $plugin_data['report_supermod'] )
								);
							
		$return .= $html->addRow(	$this->lang->words['r_galmod'],
									"",
									$this->registry->output->formYesNo('report_bypass', (!isset( $plugin_data['report_bypass'] )) ? 1 : $plugin_data['report_bypass'] )
								);

		return $return;
	}
	
	/**
	 * Process the plugin's form fields for saving
	 *
	 * @param	array 		Plugin data for save
	 * @return	@e string		Error message
	 */
	public function processAdminForm( &$save_data_array )
	{
		$save_data_array['report_supermod']	= intval($this->request['report_supermod']);
		$save_data_array['report_bypass']	= intval($this->request['report_bypass']);
		
		return '';
	}
	
	/**
	 * Update timestamp for report
	 *
	 * @param	array 		New reports
	 * @param 	array 		New members cache
	 * @return	@e boolean
	 */
	public function updateReportsTimestamp( $new_reports, &$new_members_cache )
	{
		return true;
	}
	
	/**
	 * Get report permissions
	 *
	 * @param	string 		Type of perms to check
	 * @param 	array 		Permissions data
	 * @param 	array 		group ids
	 * @param 	string		Special permissions
	 * @return	@e boolean
	 */
	public function getReportPermissions( $check, $com_dat, $group_ids, &$to_return )
	{
		if( $this->_extra['report_bypass'] == 0 || ( $this->memberData['g_is_supmod'] == 1 && ( !isset($this->_extra['report_supermod']) || $this->_extra['report_supermod'] == 1 ) ) )
		{
			return true;
		}
		else
		{
			$this->DB->build( array(
										'select'	=> 'g.g_id',
										'from'		=> array( 'groups' => 'g' ),
										'where'		=> "(g.g_mod_albums=1 OR g.g_is_supmod=1) AND m.member_id=" . $this->memberData['member_id'],
										'add_join'	=> array(
															array(
																'select'	=> 'm.members_display_name, m.member_id, m.email',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> "m.member_group_id = g.g_id OR m.mgroup_others LIKE " . $this->DB->buildConcat( array( array( '%', 'string' ), array( 'g.g_id' ), array( '%', 'string' ) ) ) ,
																)
															)
								)		);
			$res = $this->DB->execute();

			if ( $this->DB->getTotalRows($res) > 0 )
            {
            	return true;
            }
            else
            {
            	return false;
            }
		}
	}
	
	/**
	 * Show the report form for this module
	 *
	 * @param	array		$com_dat		Report plugin data
	 * @return	@e string	HTML form information
	 */
	public function reportForm( $com_dat )
	{
		/* Init vars & data */
		$image   = array();
		$album   = array();
		$comment = array();
		$parents = array();
		
		if ( $this->request['ctyp'] == 'comment' )
		{
			$comment = $this->registry->gallery->helper('comments')->fetchById( $this->request['commentId'] );
			
			/* Not found? */
			if ( !$comment['pid'] )
			{
				$this->registry->output->showError( 'reports_no_commentid', 10163.1 );
			}
			
			$image   = $this->registry->gallery->helper('image')->fetchImage( $comment['img_id'] );
			
			$ex_form_data = array( 'imageId'   => $image['id'],
								   'commentId' => $comment['pid'],
								   'ctyp'      => 'comment',
								   'title'     => $image['caption'] . ' ' . $this->lang->words['report_gallery_comment_suffix'] . ' #' . $comment['pid']
								  );
		}
		else
		{
			$image   = $this->registry->gallery->helper('image')->fetchImage( $this->request['imageId'] );
			
			$ex_form_data = array( 'imageId' => $image['id'],
								   'ctyp'    => 'image',
								   'title'   => $image['caption'] . ' ' . $this->lang->words['report_gallery_image_suffix']
								  );
		}
		
		/* No image found? */
		if ( !$image['id'] )
		{
			$this->registry->output->showError( 'reports_no_imageid', 10163 );
		}
		
		/* Fetch album */
		$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $image['img_album_id'] );
		
		/* Check comment is approved and moderator status (if any) */
		if ( $comment['pid'] && !$comment['approved'] && !$this->registry->gallery->helper('albums')->canModerate( $album ) )
		{
			$this->registry->output->showError( 'reports_no_commentid', 10163.2 );
		}
		
		/* Setup navigation */
		$this->registry->output->addNavigation( IPSLib::getAppTitle('gallery'), 'app=gallery', 'false', 'app=gallery' );
		
		$parents = $this->registry->gallery->helper('albums')->fetchAlbumParents( $album['album_id'] );
		
		if ( count($parents) )
		{
			$parents = array_reverse( $parents, true );
			
			foreach( $parents as $id => $data )
			{
				$this->registry->output->addNavigation( $data['album_name'], 'app=gallery&amp;album=' . $data['album_id'], $data['album_name_seo'], 'viewalbum' );	
			}
		}
		
		$this->registry->output->addNavigation( $album['album_name'], 'app=gallery&amp;album=' . $album['album_id'], $album['album_name_seo'], 'viewalbum' );
		$this->registry->output->addNavigation( $image['caption'], 'app=gallery&amp;image=' . $image['id'], $image['caption_seo'], 'viewimage' );
		
		/* Setup report url */
		if ( $comment['pid'] )
		{
			$url = $this->registry->output->buildUrl( "app=core&module=global&section=comments&do=findComment&comment_id={$comment['pid']}&parentId={$image['id']}&fromApp=gallery-images", 'public' );
			$this->registry->output->addNavigation( $this->lang->words['reports_comment_title'] );
		}
		else
		{
			$url = $this->registry->output->buildSEOUrl( "app=gallery&amp;image={$image['id']}", 'public', $image['caption_seo'], 'viewimage' );
			$this->registry->output->addNavigation( $this->lang->words['reports_image_title'] );
		}
		
		/* Setup report title */
		$title = $image['caption'];
		
		if ( $comment['pid'] )
		{
			$title .= " ({$this->lang->words['comment_ucfirst']} #{$comment['pid']})";
		}
		
		$this->lang->words['report_basic_title'] = $comment['pid'] ? $this->lang->words['reports_comment_title'] : $this->lang->words['reports_image_title'];
		$this->lang->words['report_basic_enter'] .= '<br /><br />' . $this->registry->gallery->helper('image')->makeImageTag( $image, array( 'type' => 'thumb' ) );
		
		/* Title and output */
		$this->registry->getClass('output')->setTitle( "{$this->lang->words['reporting_title']} {$title}" );
		
		return $this->registry->getClass('reportLibrary')->showReportForm( $title, $url, $ex_form_data );
	}
	
	/**
	 * Get section and link
	 *
	 * @param	array		$report_row		Report data
	 * @return	@e array	Section/link
	 */
	public function giveSectionLinkTitle( $report_row )
	{
		/* Got a comment? O_ò */
		if ( $report_row['exdat3'] && is_numeric( $report_row['exdat3'] ) )
		{
			$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $report_row['exdat3'] );
			
			/* Album exists? */
			if ( $album['album_id'] )
			{
				return array( 'title'		=> IPSLib::getAppTitle('gallery') . ': '. $album['album_name'],
							  'url'			=> '/index.php?app=gallery&amp;album=' . $album['album_id'],
							  'seo_title'	=> $album['album_name_seo'],
							  'seo_template'=> 'viewalbum'
							 );
			}
		}
		
		/* Got here? Fallback on normal link then */
		return array( 'title'		=> $this->lang->words['report_section_title_site_gallery'],
					  'url'			=> '/index.php?app=gallery',
					  'seo_title'	=> 'false',
					  'seo_template'=> 'app=gallery'
					 );
	}
	
	/**
	 * Process a report and save the data appropriate
	 *
	 * @param	array		$com_dat		Report plugin data
	 * @return	@e array	Data from saving the report
	 */
	public function processReport( $com_dat )
	{
		/* Init vars & data */
		$image   = array();
		$album   = array();
		$comment = array( 'pid' => 0 );
		$return  = array();
		$repUrl  = '';
		
		$this->request['ctyp'] = trim($this->request['ctyp']);
		
		if ( $this->request['ctyp'] == 'comment' )
		{
			$comment = $this->registry->gallery->helper('comments')->fetchById( $this->request['commentId'] );
			
			/* Not found? */
			if ( !$comment['pid'] )
			{
				$this->registry->output->showError( 'reports_no_commentid', 10163.3 );
			}
			
			$image   = $this->registry->gallery->helper('image')->fetchImage( $comment['img_id'] );
			
			$repUrl  = "app=core&module=global&section=comments&do=findComment&comment_id={$comment['pid']}&parentId={$image['id']}&fromApp=gallery-images";
		}
		else
		{
			$image   = $this->registry->gallery->helper('image')->fetchImage( $this->request['imageId'] );
			
			$repUrl  = "app=gallery&amp;image={$image['id']}";
		}
		
		/* No image found? */
		if ( !$image['id'] )
		{
			$this->registry->output->showError( 'reports_no_imageid', 10165 );
		}
		
		/* Fetch album & init some other vars */
		$album = $this->registry->gallery->helper('albums')->fetchAlbumsById( $image['img_album_id'] );
		$uid   = md5(  'gallery_' . $this->request['ctyp'] . '_' . $image['id'] . '_' . $comment['pid'] . '_' . $com_dat['com_id'] );
		$status= array();
		
		/* Check comment is approved and moderator status (if any) */
		if ( $comment['pid'] && !$comment['approved'] && !$this->registry->gallery->helper('albums')->canModerate( $album ) )
		{
			$this->registry->output->showError( 'reports_no_commentid', 10163.4 );
		}
		
		/* Retrieve statuses */
		$this->DB->build( array( 'select' 	=> 'status, is_new, is_complete', 
								 'from'		=> 'rc_status', 
								 'where'	=> 'is_new=1 OR is_complete=1'
						 )		);
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			if( $row['is_new'] == 1 )
			{
				$status['new'] = $row['status'];
			}
			elseif( $row['is_complete'] == 1 )
			{
				$status['complete'] = $row['status'];
			}
		}
		
		/* Retrieve any current report */
		$_reportData = $this->DB->buildAndFetch( array( 'select' => 'id', 'from' => 'rc_reports_index', 'where' => "uid='{$uid}'" ) );
		
		if( $_reportData['id'] )
		{
			/* Update status, number of reports and time */
			$this->DB->update( 'rc_reports_index', 'num_reports=num_reports+1,date_updated='.IPS_UNIX_TIME_NOW.',status='.intval($status['new']), "id={$_reportData['id']}", false, true );
		}
		else
		{	
			$_reportData = array( 'uid'			=> $uid,
								  'title'		=> $this->request['title'],
								  'status'		=> $status['new'],
								  'url'			=> '/index.php?' . $repUrl,
								  'seoname'		=> $comment['pid'] ? '' : $image['caption_seo'],
								  'seotemplate'	=> $comment['pid'] ? '' : 'viewimage',
								  'rc_class'	=> $com_dat['com_id'],
								  'updated_by'	=> $this->memberData['member_id'],
								  'date_updated'=> IPS_UNIX_TIME_NOW,
								  'date_created'=> IPS_UNIX_TIME_NOW,
								  'img_preview'	=> '',
								  'exdat1'		=> $image['id'],
								  'exdat2'		=> $comment['pid'],
								  'exdat3'		=> $album['album_id'],
								  'num_reports'	=> 1, // We're adding it now so it's 1 for sure :P
								 );

			$this->DB->insert( 'rc_reports_index', $_reportData );
			
			$_reportData['id'] = $this->DB->getInsertId();
		}
		
		/* Insert new report */
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'reports';
		
		$build_report = array( 'rid'			=> $_reportData['id'],
							   'report'			=> IPSText::getTextClass('bbcode')->preDbParse( $this->request['message'] ),
							   'report_by'		=> $this->memberData['member_id'],
							   'date_reported'	=> IPS_UNIX_TIME_NOW
							  );
		
		$this->DB->insert( 'rc_reports', $build_report );
		
		/* Finally return based on type */
		$return = array( 'REDIRECT_URL'	=> $repUrl,
						 'REPORT_INDEX'	=> $_reportData['id'],
						 'SAVED_URL'	=> $_reportData['url'],
						 'REPORT'		=> $build_report['report'],
						 'SEOTITLE'		=> $comment['pid'] ? '' : $image['caption_seo'],
						 'TEMPLATE'		=> $comment['pid'] ? '' : 'viewimage'
						);
		
		return $return;
	}

	/**
	 * Accepts an array of data from rc_reports_index and returns an array formatted nearly identical to processReport()
	 *
	 * @param 	array 		Report data
	 * @return	array 		Formatted report data
	 */
	public function formatReportData( $report_data )
	{
		return array(
					'REDIRECT_URL'	=> $report_data['url'],
					'REPORT_INDEX'	=> $report_data['id'],
					'SAVED_URL'		=> str_replace( '&amp;', '&', $report_data['url'] ),
					'REPORT'		=> '',
					'SEOTITLE'		=> $report_data['seoname'],
					'TEMPLATE'		=> $report_data['viewimage'],
					);
	}
	
	/**
	 * Where to send user after report is submitted
	 *
	 * @param	array		$report_data		Report data
	 * @return	@e void
	 */
	public function reportRedirect( $report_data )
	{
		$this->registry->output->redirectScreen( $this->lang->words['report_sending'], $this->settings['base_url'] . $report_data['REDIRECT_URL'], $report_data['SEOTITLE'], $report_data['TEMPLATE'] );
	}
	
	/**
	 * Retrieve list of users to send notifications to
	 *
	 * @param	string 		Group ids
	 * @param	array 		Report data
	 * @return	@e array 		Array of users to PM/Email
	 */
	public function getNotificationList( $group_ids, $report_data )
	{
		$notify = array();
		
		$this->DB->build( array(
									'select'	=> 'noti.*',
									'from'		=> array( 'rc_modpref' => 'noti' ),
									'where'		=> 'mem.member_group_id IN(' . $group_ids . ')',
									'add_join'	=> array(
														array(
															'select'	=> 'mem.member_id, mem.members_display_name as name, mem.language, mem.members_disable_pm, mem.email, mem.member_group_id',
															'from'		=> array( 'members' => 'mem' ),
															'where'		=> 'mem.member_id=noti.mem_id',
															)
														)
							)		);
		$this->DB->execute();

		if( $this->DB->getTotalRows() > 0 )
		{
			while( $row = $this->DB->fetch() )
			{
				$notify[] = $row;
			}	
		}
		
		return $notify;
	}
}