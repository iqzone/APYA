<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.2
 * Report Center :: Blog plugin
 * Last Updated: $LastChangedDate: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @version		$Rev: 4 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class blog_plugin
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
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * Holds extra data for the plugin
	 *
	 * @access	private
	 * @var		array			Data specific to the plugin
	 */
	public $_extra;
	
	/**
	 * Blog dat
	 *
	 * @access	private
	 * @var		array			Data about the blog
	 */
	public $blog;
	
	/**
	 * Entry data
	 *
	 * @access	private
	 * @var		array			Data about the entry
	 */
	public $entry;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make object
		//-----------------------------------------
		
		$this->registry   = $registry;
		$this->DB	      = $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->member     = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  = $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		$this->lang		  = $this->registry->class_localization;
		
		/* Load the language */
		$registry->class_localization->loadLanguageFile( array( 'public_blog' ), 'blog' );
	}
	
	/**
	 * Display the form for extra data in the ACP
	 *
	 * @access	public
	 * @param	array 		Plugin data
	 * @param	object		HTML object
	 * @return	string		HTML to add to the form
	 */
	public function displayAdminForm( $plugin_data, &$html )
	{
		return $html->addRow(	$this->lang->words['r_supermod'],
								sprintf(  $this->lang->words['r_supermod_info'], $this->settings['_base_url'] ),
								$this->registry->output->formYesNo('report_supermod', (!isset( $plugin_data['report_supermod'] )) ? 1 : $plugin_data['report_supermod'] )
							);
	}
	
	/**
	 * Process the plugin's form fields for saving
	 *
	 * @access	public
	 * @param	array 		Plugin data for save
	 * @return	string		Error message
	 */
	public function processAdminForm( &$save_data_array )
	{
		$save_data_array['report_supermod'] = intval($this->request['report_supermod']);
		
		return '';
	}
	
	/**
	 * Update timestamp for report
	 *
	 * @access	public
	 * @param	array 		New reports
	 * @param 	array 		New members cache
	 * @return	boolean
	 */
	public function updateReportsTimestamp( $new_reports, &$new_members_cache )
	{
		return true;
	}
	
	/**
	 * Get report permissions
	 *
	 * @access	public
	 * @param	string 		Type of perms to check
	 * @param 	array 		Permissions data
	 * @param 	array 		group ids
	 * @param 	string		Special permissions
	 * @return	boolean
	 */
	public function getReportPermissions( $check, $com_dat, $group_ids, &$to_return )
	{
		if ( ! IPSLib::appIsInstalled('blog') )
		{
			return false;
		}
		
		if( $this->memberData['g_is_supmod'] == 1 && ( ! isset($this->_extra['report_supermod']) || $this->_extra['report_supermod'] == 1 ) )
		{
			return true;
		}
		else
		{
			$this->DB->build( array(
										'select'	=> 'md.moderate_type, md.moderate_mg_id',
										'from'		=> array('blog_moderators' => 'md'),
										'add_join'	=> array( 
							            					array(
																'select' => 'm.member_id, m.name, m.email, m.member_group_id',
																'from'   => array( 'members' => 'm' ),
																'where'  => "(md.moderate_type='member' AND md.moderate_mg_id={$this->memberData['member_id']}) OR (md.moderate_type='group' AND md.moderate_mg_id IN(" . implode( ',', $group_ids ) . "))",
																'type'   => 'inner'
																)
															),
									)		);
			$this->DB->execute();

			if ( $this->DB->getTotalRows() > 0 )
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
	 * @access	public
	 * @param 	array 		Application data
	 * @return	string		HTML form information
	 */
	public function reportForm( $com_dat )
	{
		$ex_form_data = array(
							'blog_id'		=> intval($this->request['blog_id']),
							'comment_id'	=> intval($this->request['comment_id']),
							'entry_id'		=> intval($this->request['entry_id']),
							'st'			=> intval($this->request['st'])
							);
		
		if( $ex_form_data['entry_id'] < 1 || $ex_form_data['blog_id'] < 1 )
		{
			$this->registry->output->showError( 'reports_blog_entry_id', 10152 );
		}
		
		$this->_loadBlog( $ex_form_data['blog_id'] );
		$this->_checkAccess( $ex_form_data['entry_id'] );
		
		$this->settings['blog_url'] = $this->registry->getClass('blogFunctions')->getBlogUrl( $this->blog['blog_id'] );
		
		$this->registry->output->setTitle( $this->lang->words['report_title'] );
		$this->registry->output->addNavigation( $this->lang->words['blog_title'], 'app=blog', 'false', 'app=blog' );
		$this->registry->output->addNavigation( $this->blog['blog_name'], $this->settings['blog_url'], $this->blog['blog_seo_name'], 'showblog', 'none' );
		$this->registry->output->addNavigation( $this->entry['entry_name'], $this->settings['blog_url'] . "&amp;showentry={$this->entry['entry_id']}", $this->entry['entry_name_seo'], 'showentry', 'none' );
		
		
		if ( $this->request['comment_id'] )
		{
			$url = $this->settings['base_url'] . "app=blog&blogid={$this->entry['blog_id']}&showentry={$ex_form_data['entry_id']}&show=comment&cid={$ex_form_data['comment_id']}";
			$this->registry->output->addNavigation( $this->lang->words['report_title'], '' );
		}
		else
		{
			$url = $this->settings['base_url'] . "app=blog&blogid={$this->entry['blog_id']}&showentry={$ex_form_data['entry_id']}";
			$this->registry->output->addNavigation( $this->lang->words['report_entry'], '' );
		}
		
		if ( $this->request['comment_id'] )
		{
			$this->lang->words['report_title'] .= " (" . $this->lang->words['bg_comment'] . " #" . $this->request['comment_id'] . ')';
		}

		$this->lang->words['report_basic_title']		= ( $this->request['comment_id'] ) ? $this->lang->words['report_title'] : $this->lang->words['report_entry'];
		$this->lang->words['report_basic_url_title']	= $this->lang->words['report_topic'];
		$this->lang->words['report_basic_enter']		= $this->lang->words['report_message'];
		
		//-----------------------------------------
		// Instead of dull output, lets make it
		// blogerishes!
		//-----------------------------------------

		return $this->registry->getClass('reportLibrary')->showReportForm( $this->entry['entry_name'], $url, $ex_form_data );
	}

	/**
	 * Get section and link
	 *
	 * @access	public
	 * @param 	array 		Report data
	 * @return	array 		Section/link
	 */
	public function giveSectionLinkTitle( $report_row )
	{
		$cache 	= $this->cache->getCache('report_cache');

		return array(
					'title'	=> $cache['blog_titles'][ $report_row['exdat1'] ],
					'url'	=> "/index.php?app=blog&blogid=" . $report_row['exdat1'],
					);
	}
	
	/**
	 * Process a report and save the data appropriate
	 *
	 * @access	public
	 * @param 	array 		Report data
	 * @return	array 		Data from saving the report
	 */
	public function processReport( $com_dat )
	{
		$blog_id	= intval( $this->request['blog_id'] );
		$entry_id	= intval( $this->request['entry_id'] );
		$comment_id	= intval( $this->request['comment_id'] );
		
		if( $entry_id < 1 || $blog_id < 1 )
		{
			$this->registry->output->showError( 'reports_blog_entry_id', 10153 );
		}
		
		$this->_loadBlog( $blog_id );
		$this->_checkAccess( $entry_id );
		
		if ( $comment_id )
		{
			$url = "app=blog&blogid={$blog_id}&showentry={$entry_id}&show=comment&cid={$comment_id}";
		}
		else
		{
			$url = "app=blog&blogid={$blog_id}&showentry={$entry_id}";
		}
		
		$return_data	= array();
		$a_url			= str_replace("&", "&amp;", $url);
		$uid			= md5( $url . '_' . $com_dat['com_id'] );
		
		$status = array();
		
		$this->DB->build( array( 'select' 	=> 'status, is_new, is_complete', 
								 'from'		=> 'rc_status', 
								 'where'	=> "is_new=1 OR is_complete=1" ) );
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
		
		if ( $comment_id )
		{
			$data = $this->DB->buildAndFetch( array(
													'select'	=> 'c.*',
													'from'		=> array( 'blog_comments' => 'c' ),
													'where'		=> 'c.comment_id=' . $comment_id,
													'add_join'	=> array(
																		array(
																			'select'	=> 'e.*',
																			'from'		=> array( 'blog_entries' => 'e' ),
																			'where'		=> 'e.entry_id=c.entry_id',
																			'type'		=> 'left',
																			),
																		array(
																			'select'	=> 'mem.member_id, mem.members_display_name',
																			'from'		=> array( 'members' => 'mem' ),
																			'where'		=> 'mem.member_id=c.member_id',
																			'type'		=> 'left',
																			),
																		)
											)		);
	
			IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
			IPSText::getTextClass('bbcode')->parse_html			= 0;
			IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
			IPSText::getTextClass('bbcode')->parse_nl2br		= 0;
			IPSText::getTextClass('bbcode')->parsing_section	= 'global';
			
			$message	= "[quote name='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $data['members_display_name'] ) . "']";
			$message    .= "\n[url=" . $this->settings['base_url'] . $url . "]" . $this->lang->words['bg_comment'] . " #{$comment_id}[/url]\n";
			$message	.= IPSText::getTextClass('bbcode')->preEditParse( $data['comment_text'] );
			$message	.= "[/quote]\n\n";
			
			$this->entry['entry_name'] .= ' (' . $this->lang->words['bg_comment'] . ' ' . $comment_id . ')';
		}
		else
		{
			$data = $this->DB->buildAndFetch( array('select'	=> 'e.*',
													'from'		=> array( 'blog_entries' => 'e' ),
													'where'		=> 'e.entry_id=' . $entry_id,
													'add_join'	=> array( array( 'select'	=> 'mem.member_id, mem.members_display_name',
																			     'from'		=> array( 'members' => 'mem' ),
																			     'where'	=> 'mem.member_id=e.entry_author_id',
																			     'type'		=> 'left' ) ) ) );
	
			IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
			IPSText::getTextClass('bbcode')->parse_html			= 0;
			IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
			IPSText::getTextClass('bbcode')->parse_nl2br		= 0;
			IPSText::getTextClass('bbcode')->parsing_section	= 'global';
			
			$message	= "[quote name='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $data['members_display_name'] ) . "']";
			$message	.= IPSText::getTextClass('bbcode')->preEditParse( $data['entry_short'] );
			$message    .= "\n[url=" . $this->settings['base_url'] . $url . "]" . $this->lang->words['read_more_go_on'] . "[/url]\n";
			$message	.= "[/quote]\n\n";
		}
		
		$message	.= $this->request['message'];
		
		$this->DB->build( array( 'select' => 'id', 'from' => 'rc_reports_index', 'where' => "uid='{$uid}'" ) );
		$this->DB->execute();
		
		if( $this->DB->getTotalRows() == 0 )
		{	
			$built_report_main = array(
										'uid'			=> $uid,
										'title'			=> $this->entry['entry_name'],
										'status'		=> $status['new'],
										'url'			=> '/index.php?' . $a_url,
										'rc_class'		=> $com_dat['com_id'],
										'updated_by'	=> $this->memberData['member_id'],
										'date_updated'	=> time(),
										'date_created'	=> time(),
										'exdat1'		=> $blog_id,
										'exdat2'		=> $entry_id,
										'exdat3'		=> $comment_id
									);
			$this->DB->insert( 'rc_reports_index', $built_report_main );
			$rid = $this->DB->getInsertId();
		}
		else
		{
			$the_report = $this->DB->fetch();
			$rid = $the_report['id'];
			$this->DB->update( 'rc_reports_index', array( 'date_updated' => time(), 'status' => $status['new'] ), "id='{$rid}'" );
		}
		
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'reports';

		$build_report = array(
							'rid'			=> $rid,
							'report'		=> IPSText::getTextClass('bbcode')->preDbParse( $message ),
							'report_by'		=> $this->memberData['member_id'],
							'date_reported'	=> time(),
						);
		
		$this->DB->insert( 'rc_reports', $build_report );
		
		$reports = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'rc_reports', 'where' => "rid='{$rid}'" ) );
		
		$this->DB->update( 'rc_reports_index', array( 'num_reports' => $reports['total'] ), "id='{$rid}'" );
		
		$cache 	= $this->cache->getCache('report_cache');
		
		if( $cache['blog_titles'][ $this->blog['blog_id'] ] != $this->blog['blog_name'] )
		{
			$cache['blog_titles'][ $this->blog['blog_id'] ]	= $this->blog['blog_name'];

			$this->cache->setCache( 'report_cache', $cache, array( 'array' => 1, 'donow' => 1 ) );
		}
		
		$return_data['REDIRECT_URL']	= $a_url;
		$return_data['REPORT_INDEX']	= $rid;
		$return_data['SAVED_URL']		= '/index.php?' . $url;
		$return_data['REPORT']			= $build_report['report'];
		
		return $return_data;
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
					);
	}
	
	/**
	 * Where to send user after report is submitted
	 *
	 * @access	public
	 * @param 	array 		Report data
	 * @return	@e void
	 */
	public function reportRedirect( $report_data )
	{
		$this->registry->output->redirectScreen( $this->lang->words['report_sending'],  $this->settings['base_url'] . $report_data['REDIRECT_URL'] );
	}
	
	/**
	 * Retrieve list of users to send notifications to
	 *
	 * @access	public
	 * @param 	string 		Group ids
	 * @param 	array 		Report data
	 * @return	array 		Array of users to PM/Email
	 */
	public function getNotificationList( $group_ids, $report_data )
	{
		$notify = array();
		
		$this->DB->build( array(
								'select'	=> 'mem.member_id, mem.members_display_name as name, mem.language, mem.members_disable_pm, mem.email, mem.member_group_id',
								'from'		=> array( 'members' => 'mem' ),
								'where'		=> 'mem.member_group_id IN(' . $group_ids . ')',
								'add_join'	=> array(
													array(
														'select'	=> 'noti.*',
														'from'		=> array( 'rc_modpref' => 'noti' ),
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
	
	/**
	 * Load up blog
	 *
	 * @param 	integer 	$blog_id	Blog id
	 * @return	@e void
	 */
	public function _loadBlog( $blog_id )
	{
		ipsRegistry::getAppClass('blog');
		
		$this->blog = $this->registry->getClass('blogFunctions')->loadBlog( $blog_id );
		
		$this->settings['blog_url'] =  $this->registry->getClass('blogFunctions')->getBlogUrl( $this->blog['blog_id'] );
	}
	
	/**
	 * Check access
	 *
	 * @param 	integer		$eid	Entry id
	 * @return	@e void
	 */
	public function _checkAccess($eid)
    {
		if ( ! $this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'reports_must_be_member' );
		}
		
		if ( ! $this->blog['blog_name'] )
		{
			$this->registry->output->showError( 'blog_not_enabled', 10154 );
		}
		
		$this->entry = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_entries', 'where' => "entry_id=" . $eid ) );
		
		if ( !$this->entry['entry_id'] )
		{
			$this->registry->output->showError( 'reports_no_entry', 10155 );
		}

		//-----------------------------------------
		// Are we allowed to see draft entries?
		//-----------------------------------------

		if ( $this->blog['allow_entry'] )
		{
			$show_draft	= true;
		}
		elseif ( $this->memberData['g_is_supmod'] or $this->memberData['_blogmod']['moderate_can_view_draft'] )
		{
			$show_draft	= true;
		}
		else
		{
			$show_draft	= false;
		}

		if ( $this->entry['entry_status'] == 'draft' and !$show_draft )
		{
			$this->registry->output->showError( 'reports_cannot_view_entry', 10156, true, null, 403 );
		}
	}
}