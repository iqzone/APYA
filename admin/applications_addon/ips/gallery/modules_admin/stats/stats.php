<?php
/**
 * @file		stats.php 	Gallery statistics methods
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * $LastChangedDate: 2011-12-09 15:24:24 -0500 (Fri, 09 Dec 2011) $
 * @version		v4.2.1
 * $Revision: 9978 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		admin_gallery_stats_stats
 * @brief		Gallery statistics methods
 */
class admin_gallery_stats_stats extends ipsCommand 
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin Template */
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_gallery' );
		
		/* Load Language */
		$this->lang->loadLanguageFile( array( 'admin_gallery' ) );
		
		/* URL Bits */
		$this->html->form_code    = $this->form_code    = 'module=stats&amp;section=stats&amp;';
		$this->html->form_code_js = $this->form_code_js = 'module=stats&section=stats&';
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'get_chart':
				$this->_getChart();
			break;
			
			case 'domemsrch':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'galstats_members' );
				
				if( empty($this->request['viewuser']) )
				{
					$this->doMemberSearch();
				}
				else
				{
					$this->viewMemberReport( $this->request['viewuser'] );
				}
			break;
			
			case 'dogroupsrch':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'galstats_groups' );
				
				if( empty($this->request['viewgroup']) )
				{
					$this->doGroupSearch();
				}
				else
				{
					$this->viewGroupReport( $this->request['viewgroup'] );
				}
			break;
			
			case 'dofilesrch':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'galstats_files' );
				
				if( empty($this->request['viewfile']) )
				{
					$this->doFileSearch();
				}
				else
				{
					$this->viewFileReport( $this->request['viewfile'] );
				}
			break;
			
			case 'domemact':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'galstats_files' );
				$this->doMemberAction();
			break;

			case 'dofileact':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'galstats_memberalter' );
				$this->doFileAction();
			break;

			default:
				$this->indexScreen();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Main stats display screen
	 *
	 * @return	@e void
	 */
	public function indexScreen()
	{
		//---------------------------------------
		// Overall Stats
		//---------------------------------------
		
		$stats = $this->DB->buildAndFetch( array( 'select' => 'SUM( file_size ) as total_size, COUNT( file_size ) as total_uploads', 'from' => 'gallery_images' ) );
		
		$overall = array();
		
		$overall['total_diskspace'] = IPSLib::sizeFormat( intval( $stats['total_size'] ) );
		$overall['total_uploads']   = intval( $stats['total_uploads'] );

		if( $this->settings['gallery_detailed_bandwidth'] )
		{
			$time_cutoff = time() - ( $this->settings['gallery_bandwidth_period'] * 3600 );
			
			$more_stats = $this->DB->buildAndFetch( array( 'select' => 'SUM( bsize ) as total_transfer, COUNT( bsize ) as total_viewed', 'from' => 'gallery_bandwidth', 'where' => 'bdate > '.$time_cutoff ) );
			$stats = array_merge( $stats, $more_stats );

			unset( $more_stats, $time_cutoff );
			
			$overall['total_transfer'] = IPSLib::sizeFormat( intval( $stats['total_transfer'] ) );
			$overall['total_views']    = intval( $stats['total_viewed'] );
		}
		
		$stats['total_transfer'] = $stats['total_transfer'] ? intval($stats['total_transfer']) : 1;
		
		/* Group Stats */
		$this->DB->build( array('select'   => 'g.g_title, g.g_id',
								'from'     => array( 'groups' => 'g' ),
								'group'    => 'm.member_group_id',
								'order'    => 'diskspace DESC',
								'add_join' => array( array( 'from'   => array( 'members' => 'm' ),
															'where'  => 'm.member_group_id=g.g_id',
															'type'   => 'inner' ),
													 array( 'select' => 'SUM( i.file_size ) as diskspace, COUNT( i.file_size ) as uploads',
															'from'   => array( 'gallery_images' => 'i' ),
															'where'  => 'i.member_id=m.member_id',
															'type'   => 'inner' ) )
							)	);
		$this->DB->execute();
		
		/* Loop through and build output array */
		$groups_disk = array();

		while( $i = $this->DB->fetch() )
		{
		 	$i['dp_percent'] = round( $i['diskspace'] 	/ $stats['total_size']		, 2 ) * 100;
		 	$i['up_percent'] = round( $i['uploads'] 	/ $stats['total_uploads']	, 2 ) * 100;
			$i['diskspace']  = IPSLib::sizeFormat( $i['diskspace'] );
			
			$groups_disk[] = $i;
		}

		//---------------------------------------
		// Diskspace By Member
		//---------------------------------------
		
		$this->DB->build( array('select'   => 'm.members_display_name, m.member_id AS mid',
								'from'     => array( 'members' => 'm' ),
								'group'    => 'm.member_id, m.members_display_name',
								'order'    => 'diskspace DESC',
								'limit'    => array( 0, 10 ),
								'add_join' => array( array( 'select' => 'SUM( i.file_size ) as diskspace, COUNT( i.file_size ) as uploads',
															'from'   => array( 'gallery_images' => 'i' ),
															'where'  => 'i.member_id=m.member_id',
															'type'   => 'inner' ) )
							)	);
		$this->DB->execute();
		
		/* Loop through and build output array */
		$users_disk = array();

		while( $i = $this->DB->fetch() )
		{
		 	$i['dp_percent'] = round( $i['diskspace'] 	/ $stats['total_size']		, 2 ) * 100;
		 	$i['up_percent'] = round( $i['uploads'] 	/ $stats['total_uploads']	, 2 ) * 100;
			$i['diskspace']  = IPSLib::sizeFormat( $i['diskspace'] );
			
			$users_disk[] = $i;
		}

		//---------------------------------------
		// Bandwidth Stats
		//---------------------------------------

		if( $this->settings['gallery_detailed_bandwidth'] )
		{
			//---------------------------------------
			// Bandwidth By Group
			//---------------------------------------

			$this->DB->build( array('select'   => 'g.g_title, g.g_id',
									'from'     => array( 'groups' => 'g' ),
									'group'    => 'g.g_id',
									'order'    => 'transfer DESC',
									'add_join' => array( array( 'from'   => array( 'members' => 'm' ),
																'where'  => 'm.member_group_id=g.g_id',
																'type'   => 'inner' ),
														 array( 'select' => 'SUM( b.bsize ) as transfer, COUNT( b.bsize ) as total',
																'from'   => array( 'gallery_bandwidth' => 'b' ),
																'where'  => 'b.member_id=m.member_id',
																'type'   => 'inner' ) )
							 )		);
			$this->DB->execute();
			
			/* Loop through and build output array */
			$groups_bandwidth = array();

			while( $i = $this->DB->fetch() )
			{
				$i['dp_percent'] = $stats['total_transfer']	? round( $i['transfer']	/ $stats['total_transfer']	, 2 ) * 100 : 0;
				$i['up_percent'] = $stats['total_viewed'] 	? round( $i['total'] 	/ $stats['total_viewed']	, 2 ) * 100 : 0;
				$i['transfer']   = IPSLib::sizeFormat( $i['transfer'] );
				
				$groups_bandwidth[] = $i;
			}

			//---------------------------------------
			// Bandwidth By Member
			//---------------------------------------

			$this->DB->build( array('select'   => 'm.members_display_name, m.member_id',
									'from'     => array( 'members' => 'm' ),
									'group'    => 'm.member_id, m.members_display_name',
									'order'    => 'transfer DESC',
									'limit'    => array( 0, 5 ),
									'add_join' => array( array( 'select' => 'SUM( b.bsize ) as transfer, COUNT( b.bsize ) as total',
																'from'   => array( 'gallery_bandwidth' => 'b' ),
																'where'  => 'b.member_id=m.member_id',
																'type'   => 'inner' ) ) 
							 )		);
			$this->DB->execute();
			
			/* Loop through and build output array */
			$users_bandwidth = array();

		 	while( $i = $this->DB->fetch() )
		 	{
			 	$dp_percent = round( $i['transfer'] / $stats['total_transfer'], 2 ) * 100;
			 	
			 	if( $stats['total_viewed'] )
			 	{
			 		$up_percent = round( $i['total'] / $stats['total_viewed'], 2 ) * 100;
		 		}
		 		else
		 		{
			 		$up_percent = 0;
		 		}
				
			 	$i['dp_percent'] = $dp_percent;
			 	$i['up_percent'] = $up_percent;
				$i['transfer']   = IPSLib::sizeFormat( $i['transfer'] );
				
				$users_bandwidth[] = $i;
		 	}

			//---------------------------------------
			// Bandwidth By File
			//---------------------------------------

			$this->DB->build( array('select'   => 'i.file_name, i.id',
									'from'     => array( 'gallery_images' => 'i' ),
									'group'    => 'b.file_name',
									'order'    => 'transfer DESC',
									'limit'    => array( 0, 5 ),
									'add_join' => array( array( 'select' => 'SUM( b.bsize ) as transfer, COUNT( b.bsize ) as total, b.file_name AS m_file_name',
																'from'   => array( 'gallery_bandwidth' => 'b' ),
																'where'  => 'b.file_name=i.masked_file_name',
																'type'   => 'inner' ) )
							 )		);
			$this->DB->execute();  
			
			/* Loop through and build output array */
			$files_bandwidth = array();

		 	while( $i = $this->DB->fetch() )
		 	{
			 	$dp_percent = round( $i['transfer']	/ $stats['total_transfer']	, 2 ) * 100;
			 	
			 	if( $stats['total_viewed'] )
			 	{
			 		$up_percent = round( $i['total'] 	/ $stats['total_viewed']	, 2 ) * 100;
		 		}
		 		else
		 		{
			 		$up_percent = 0;
		 		}

			 	if( substr( $i['m_file_name'], 0, 3 ) == 'tn_' )
			 	{
					$i['file_name'] = 'tn_'.$i['file_name'];
			 	}
			 
			 	$i['dp_percent'] = $dp_percent;
			 	$i['up_percent'] = $up_percent;
				$i['transfer']   = IPSLib::sizeFormat( $i['transfer'] );
				
				$files_bandwidth[] = $i;
		 	}
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->statsOverview( $overall, $groups_disk, $users_disk, $groups_bandwidth, $users_bandwidth, $files_bandwidth );
	}
	
	/**
	 * Perform a Member Search
	 *
	 * @return	@e void
	 */
	public function doMemberSearch()
	{
		/* Make sure we have a search term */
		if( ! $this->request['search_term'] )
		{
		 	$this->registry->output->showError( $this->lang->words['stats_no_search_term'], 11737 );
		}
		
		$search_term = $this->DB->addSlashes( strtolower($this->request['search_term']) );
		
		/* Do the member search */
		$this->DB->build( array('select' => 'member_id, members_display_name', 
								'from'   => 'members',
								'order'  => 'members_display_name ASC',
								'where'  => "members_l_username LIKE '%" . $search_term . "%' OR members_l_display_name LIKE '%" . $search_term . "%'" 
						 )		);
		$this->DB->execute();

		$result_cnt = $this->DB->getTotalRows();

		/* How many results.. */
		if( $result_cnt == 1 )
		{
		 	$i = $this->DB->fetch();
		 	$this->viewMemberReport( $i['member_id'] ); 
		}
		else
		{
			/* Build the rows array */
			$rows = array();

			while( $i = $this->DB->fetch() )
			{
				$rows[] = array( 'url'  => "{$this->settings['base_url']}{$this->form_code}do=domemsrch&amp;viewuser={$i['member_id']}", 'name' => $i['members_display_name'] );
			}
			
			/* Output */
			$this->registry->output->html .= $this->html->statSearchResults( sprintf( $this->lang->words['stats_mem_results_cnt'], $result_cnt ), $rows );
		}
	}

	/**
	 * View Results From a Member Search
	 *
	 * @param	integer		$mid		Member ID
	 * @return	@e void
	 */
	public function viewMemberReport( $mid )
	{
		/* Get Member */
		$member = $this->DB->buildAndFetch( array( 'select' => 'member_id, members_display_name, gallery_perms',
												   'from'   => 'members',
												   'where'  => 'member_id=' . intval( $mid ) 
										   )	  );

		if( ! $member['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['stats_no_member_found'], 11738 );
		}
		
		/* Overall Stats */
		$stats = $this->DB->buildAndFetch( array( 'select' => 'SUM( file_size ) as total_size, AVG( file_size ) as total_avg_size, COUNT( file_size ) as total_uploads', 'from' => 'gallery_images' ) );

		$stats = array_merge( $stats, $this->DB->buildAndFetch( array( 'select' => 'SUM( bsize ) as total_transfer, COUNT( bsize ) as total_viewed', 'from' => 'gallery_bandwidth' ) ) );

		$stats = array_merge( $stats, $this->DB->buildAndFetch( array( 'select' => 'SUM( file_size ) as user_size, AVG( file_size ) as user_avg_size, COUNT( file_size ) as user_uploads', 'from' => 'gallery_images', 'where'  => 'member_id=' . $mid ) ) );

		$stats = array_merge( $stats, $this->DB->buildAndFetch( array( 'select' => 'SUM( bsize ) as user_transfer, COUNT( bsize ) as user_viewed', 'from' => 'gallery_bandwidth', 'where' => 'member_id=' . $mid ) ) );
		
		/* Format some stats */
		$stats['dp_percent']     = $stats['total_size'] 	? ( round( $stats['user_size'] 		/ $stats['total_size']		, 2 ) * 100 ).'%' : '0%';
		$stats['up_percent']     = $stats['total_uploads'] 	? ( round( $stats['user_uploads'] 	/ $stats['total_uploads']	, 2 ) * 100 ).'%' : '0%';
		$stats['user_size']      = IPSLib::sizeFormat( $stats['user_size'] );
		$stats['total_avg_size'] = IPSLib::sizeFormat( $stats['total_avg_size'] );
		$stats['user_avg_size']  = IPSLib::sizeFormat( $stats['user_avg_size'] );

		/* Detailed Bandwidth Logs? */
		$stats['bw'] = array();
		
		if( $this->settings['gallery_detailed_bandwidth'] )
		{
			/* Section Title */
			$stats['bw']['title'] = sprintf( $this->lang->words['stats_mem_result_bw_tbl'], $this->settings['gallery_bandwidth_period'] );

			$stats['bw']['tr_percent'] = $stats['total_transfer'] 	? ( round( $stats['user_transfer'] 	/ $stats['total_transfer']	, 2 ) * 100 ).'%' : '0%';
			$stats['bw']['vi_percent'] = $stats['total_viewed']		? ( round( $stats['user_viewed'] 	/ $stats['total_viewed']	, 2 ) * 100 ).'%' : '0%';
			$stats['user_transfer']    = IPSLib::sizeFormat( $stats['user_transfer'] );

		 	$stats['bw']['list_title'] = sprintf( $this->lang->words['stats_top_views_bandwidth'], $this->settings['gallery_bandwidth_period'] );
			
		 	/* Query bandwidth logs */
			$this->DB->build( array('select'   => 'i.file_name, i.id',
									'from'     => array( 'gallery_images' => 'i' ),
									'where'    => "b.member_id={$mid}",
									'group'    => 'b.file_name',
									'order'    => 'transfer DESC',
									'limit'    => array( 0, 5 ),
									'add_join' => array( array( 'select' => "SUM( b.bsize ) as transfer, COUNT( b.bsize ) as total, b.file_name AS m_file_name",
																'from'   => array( 'gallery_bandwidth' => 'b' ),
																'where'  => 'b.file_name=i.masked_file_name',
																'type'   => 'inner' ) )
							 )		);
			$this->DB->execute();
			
			/* Build output rows */
			$stats['bw']['rows'] = array();
			
		 	while( $i = $this->DB->fetch() )
		 	{
			 	$i['dp_percent'] = $stats['user_transfer'] 	? round( $i['transfer']	/ $stats['user_transfer']	, 2 ) * 100 : 0;
			 	$i['up_percent'] = $stats['user_viewed']	? round( $i['total'] 	/ $stats['user_viewed']		, 2 ) * 100	: 0;

			 	if( substr( $i['m_file_name'], 0, 3 ) == 'tn_' )
			 	{
					$i['file_name'] = 'tn_'.$i['file_name'];
			 	}
			 	
			 	$i['transfer'] = IPSLib::sizeFormat( $i['transfer'] );

			 	$rows[] = $i;
		 	}
	 	}
		
	 	/* Count Stuff */
		$comments = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) AS comments', 'from' => 'gallery_comments', 'where' => 'author_id=' . $mid ) );
		$comments = intval($comments['comments']);

		$rate = $this->DB->buildAndFetch( array('select' => 'COUNT(rate) AS total_rates, AVG(rate) AS avg_rate', 
												'from'   => 'gallery_ratings', 
												'where'  => 'member_id=' . $mid 
										 )		);
										
		$rate['avg_rate'] = round( $rate['avg_rate'], 2 );

		$perms = explode( ":", $member['gallery_perms'] );
		
		$stats['remove_gallery']   = $this->registry->output->formYesNo( 'remove_gallery', ( $perms[0] == 1 ) ? 0 : 1 );
		$stats['remove_uploading'] = $this->registry->output->formYesNo( 'remove_uploading', ( $perms[1] == 1 ) ? 0 : 1 );
		
		/* Page Information */
		$title = sprintf( $this->lang->words['stats_mem_result_page_title'], $member['members_display_name'] );
		
		/* Output */
		$this->registry->output->html .= $this->html->memberFileReport( $mid, $stats, $comments, $rate, $title );
	}
		
	/**
	 * Take Action Against Member
	 *
	 * @return	@e void
	 */
	public function doMemberAction()
	{
		$this->request['mid'] = intval($this->request['mid']);
		
		$view    = ( $this->request['remove_gallery'] == 1 ) ? 0 : 1;
		$upload  = ( $this->request['remove_uploading'] == 1 ) ? 0 : 1;
		
		$perms = $view . ':' . $upload;
		
		$this->DB->update( 'members', array( 'gallery_perms' => $perms ), 'member_id=' . $this->request['mid'] );

		$this->registry->adminFunctions->saveAdminLog( sprintf( $this->lang->words['stats_mem_action_log'], $this->request['mid'], $perms ) );
		
		/* Redirect */
		$this->registry->output->global_message = $this->lang->words['stats_mem_action_msg'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'].$this->form_code.'do=domemsrch&amp;viewuser='.$this->request['mid'] );
	}

	/**
	 * Perform Group Search
	 *
	 * @return	@e void
	 */
	public function doGroupSearch()
	{
		/* Check search term */
		if( ! $this->request['search_term'] )
		{
		 	$this->registry->output->showError( $this->lang->words['stats_no_search_term'], 11739 );
		}
		
		/* Search */
		$this->DB->build( array('select' => 'g_id, g_title',
								'from'   => 'groups',
								'where'  => $this->DB->buildLower('g_title') . " LIKE '%" . $this->DB->addSlashes( strtolower($this->request['search_term']) ) . "%'" 
						 )		);
		$this->DB->execute();
		
		/* Total results */
		$result_cnt = $this->DB->getTotalRows();
		 		
		if( $result_cnt == 1 )
		{
		 	$i = $this->DB->fetch();
		 	$this->viewGroupReport( $i['g_id'] );
		}
		else
		{
			/* Build the rows */
			$rows = array();

			while( $i = $this->DB->fetch() )
			{
				$rows[] = array( 'url' => "{$this->settings['base_url']}{$this->form_code}do=dogroupsrch&amp;viewgroup={$i['g_id']}", 'name' => $i['g_title'] );
			}
			
			/* Output */
			$this->registry->output->html .= $this->html->statSearchResults( sprintf( $this->lang->words['stats_mem_results_cnt'], $result_cnt ), $rows );
		}
	}

	/**
	 * View Results From Group Search
	 *
	 * @param	integer		$gid		Group ID
	 * @return	@e void
	 */
	public function viewGroupReport( &$gid )
	{
		/* Image Stats */
		$stats = $this->DB->buildAndFetch( array( 'select' => 'SUM( file_size ) as total_size, AVG( file_size ) as total_avg_size, COUNT( file_size ) as total_uploads', 'from' => 'gallery_images' ) );
		
		/* Bandwidth Stats */
		$stats = array_merge( $stats, $this->DB->buildAndFetch( array( 'select' => 'SUM( bsize ) as total_transfer, COUNT( bsize ) as total_viewed', 'from' => 'gallery_bandwidth' ) ) );
							
		/* Query Diskspace Stats */
		$this->DB->build( array('select'   => 'g.g_title, g.g_id, SUM( i.file_size ) as group_size, AVG( file_size ) as group_avg_size',
								'from'     => array( 'groups' => 'g' ),
								'where'    => "m.member_group_id={$gid}",
								'group'    => 'm.member_group_id',
								'add_join' => array( array( 'from'  => array( 'members' => 'm' ),
															'where' => 'm.member_group_id=g.g_id',
															'type'  => 'inner' ),
													 array( 'select' => 'COUNT( i.file_size ) as group_uploads',
															'from'   => array( 'gallery_images' => 'i' ),
															'where'  => 'i.member_id=m.member_id',
															'type'   => 'inner' ) )
						 )		);
		$this->DB->execute();

		if( $this->DB->getTotalRows() )
		{
			$row = $this->DB->fetch();
			
			$stats = array_merge( $stats, ( is_array($row) AND count($row) ) ? $row : array() );
		}
		
		/* Get Bandwidth Stats */
		$this->DB->build( array('select'   => 'g.g_title, g.g_id',
								'from'     => array( 'groups' => 'g' ),
								'where'    => "m.member_group_id={$gid}",
								'group'    => 'm.member_group_id',
								'add_join' => array( array( 'from'  => array( 'members' => 'm' ),
															'where' => 'm.member_group_id=g.g_id',
															'type'  => 'inner' ),
													 array( 'select' => "SUM( b.bsize ) as group_transfer, COUNT( b.bsize ) as group_viewed",
															'from'   => array( 'gallery_bandwidth' => 'b' ),
															'where'  => 'b.member_id=m.member_id',
															'type'   => 'inner' ) )
						 )		);
		$this->DB->execute();
		
		if( $this->DB->getTotalRows() )
		{
			$stats = array_merge( $stats, $this->DB->fetch() );
		}
		
		/* Bandwidth Stuff */		
		$bw = array();
		
		if( $this->settings['gallery_detailed_bandwidth'] )
		{
			/* Title */
			$bw['title'] = sprintf( $this->lang->words['stats_mem_result_bw_tbl'], $this->settings['gallery_bandwidth_period'] );
			
			/* Format */
		 	$bw['tr_percent']        = $stats['total_transfer']		? ( round( $stats['group_transfer'] / $stats['total_transfer']	, 2 ) * 100 ) . '%' : '0%';
		 	$bw['vi_percent']        = $stats['total_viewed']		? ( round( $stats['group_viewed'] 	/ $stats['total_viewed']	, 2 ) * 100 ) . '%' : '0%';
			$stats['group_transfer'] = IPSLib::sizeFormat( intval( $stats['group_transfer'] ) );
		 	$stats['group_viewed']   = intval( $stats['group_viewed'] );
		}
		
		/* Comment Stats */  	
		$tmp = $this->DB->buildAndFetch( array( 'select'   => 'COUNT(*) as total_comments',
												'from'     => array( 'groups' => 'g' ),
												'where'    => "m.member_group_id={$gid}",
												'group'    => 'm.member_group_id',
												'add_join' => array( array( 'from'  => array( 'members' => 'm' ),
																			'where' => 'm.member_group_id=g.g_id',
																			'type'  => 'left' ),
																	 array( 'from'  => array( 'gallery_comments' => 'c' ),
																			'where' => 'c.author_id=m.member_id',
																			'type'  => 'left' ) )
										 )		);
		
		$comments = intval($tmp['total_comments']);

		/* Rating Stats */
		$this->DB->build( array('select'   => 'COUNT(rate) AS total_rates, AVG(rate) AS avg_rate',
								'from'     => array( 'groups' => 'g' ),
								'where'    => "m.member_group_id={$gid}",
								'group'    => 'm.member_group_id',
								'add_join' => array( array( 'from'  => array( 'members' => 'm' ),
															'where' => 'm.member_group_id=g.g_id',
															'type'  => 'left' ),
													 array( 'from'  => array( 'gallery_ratings' => 'r' ),
															'where' => 'r.member_id=m.member_id',
															'type'  => 'left' ) ) 
						)		);
		$this->DB->execute();
		
		$rate = $this->DB->fetch();
		
		/* Format Stats */
		$rate['total_rates']     = intval( $rate['total_rates'] );
		$rate['avg_rate']        = round( $rate['avg_rate'], 2 );
		$stats['group_size']     = IPSLib::sizeFormat( intval( $stats['group_size'] ) );
		$stats['dp_percent']     = $stats['total_size'] 	? ( round( $stats['group_size'] 	/ $stats['total_size']		, 2 ) * 100 ) . '%' : '0%';
		$stats['up_percent']     = $stats['total_uploads'] 	? ( round( $stats['group_uploads'] 	/ $stats['total_uploads']	, 2 ) * 100 ) . '%' : '0%';
		$stats['group_uploads']  = intval( $stats['group_uploads'] );
		$stats['total_avg_size'] = IPSLib::sizeFormat( intval( $stats['total_avg_size'] ) );
		$stats['group_avg_size'] = IPSLib::sizeFormat( intval( $stats['group_avg_size'] ) );
		
		/* Page Information */
		$title = sprintf( $this->lang->words['stats_group_result_title'], $this->caches['group_cache'][ $gid ]['g_title'] );
		
		/* Output */
		$this->registry->output->html .= $this->html->groupFileReport( $stats, $bw, $comments, $rate, $title );
	}

	/**
	 * Perform File Search
	 *
	 * @return	@e void
	 */
	public function doFileSearch()
	{
		/* Check search term */
		if( ! $this->request['search_term'] )
		{
		 	$this->registry->output->showError( $this->lang->words['stats_no_search_term'], 11740 );
		}
		
		$search_term = $this->DB->addSlashes( strtolower($this->request['search_term']) );
		
		/* Search */
		$this->DB->build( array('select' => '*',
								'from'   => 'gallery_images',
								'where'  => $this->DB->buildLower('caption') . " LIKE '%{$search_term}%' OR " . $this->DB->buildLower('file_name') . " LIKE '%{$search_term}%'" 
						 )		);
		$outer = $this->DB->execute();
		
		/* Total Results */
		$result_cnt = $this->DB->getTotalRows();
		
		if( $result_cnt == 1 )
		{
		 	$i = $this->DB->fetch( $outer );
		 	$this->viewFileReport( $i['id'] );
		}
		else
		{
			/* INI */
			$rows = array();
			
			/* Image Set */
			$this->image_dir = $this->registry->output->skin['set_image_dir'];
			
			while( $i = $this->DB->fetch( $outer ) )
			{
				$rows[] = array( 'url' => "{$this->settings['base_url']}{$this->form_code}do=dofilesrch&amp;viewfile={$i['id']}", 'name' => "<br /><strong>{$i['caption']}</strong><br /><em>{$i['file_name']}</em>", 'thumb' => $this->registry->gallery->helper('image')->makeImageLink( $i, array( 'type' => 'thumb' ) ) );
			}
			
			$this->registry->output->html = str_replace( "<#IMG_DIR#>", $this->image_dir, $this->registry->output->html );
			
			/* Output */
			$this->registry->output->html .= $this->html->statSearchResults( sprintf( $this->lang->words['stats_mem_results_cnt'], $result_cnt ), $rows, 'stats_file_report_title' );
		}
	}
	
	/**
	 *  View Results of a File Search
	 *
	 * @param	integer		File ID
	 * @return	@e void
	 */
	public function viewFileReport( $fid )
	{
		$fid = intval( $fid );
		
		$file = $this->DB->buildAndFetch( array(
												'select'	=> 'i.*',
												'from'		=> array( 'gallery_images' => 'i' ),
												'where'		=> 'i.id=' . $fid,
												'add_join'	=> array(
																		array(	'select'	=> 'm.member_id as mid, m.members_display_name',
																				'from'		=> array( 'members' => 'm' ),
																				'where'		=> 'm.member_id=i.member_id',
																				'type'		=> 'left',
																			),
																		array(	'select'	=> 'a.album_name as aname',
																				'from'		=> array( 'gallery_albums_main' => 'a' ),
																				'where'		=> 'a.album_id=i.img_album_id',
																				'type'		=> 'left',
																			),
																		)
										)	);

		/* Format File Stuff */
		$file['approved']  = $file['approved'] ? $this->lang->words['gbl_yes'] : $this->lang->words['gbl_no'];
		$file['file_size'] = IPSLib::sizeFormat( $file['file_size'] );
		$file['thumbnail'] = $file['thumbnail'] ? $this->lang->words['gbl_yes'] : $this->lang->words['gbl_no'];
		$file['idate']     = $this->registry->class_localization->getDate( $file['idate'], 'LONG' );

		if( $file['album_id'] )
		{
		 	$file['container']	= $file['aname'];
		 	$file['local_name'] = $this->lang->words['stats_file_album'];
		}
		else
		{
		 	$file['container']	= '<i>' . $this->lang->words['stats_file_unk'] . '</i>';
		 	$file['local_name'] = $this->lang->words['stats_file_unk'];
		}
		
		/* Rating Stats */
		$rate = $this->DB->buildAndFetch( array( 'select'	=> 'AVG( rate ) AS avg_rate, SUM( rate ) AS total_rate',
												'from'		=> 'gallery_ratings',
												'where'		=> "id={$file['id']}" 
										)		);
										
		$rate['total_rate'] = intval( $rate['total_rate'] );
		$rate['avg_rate']   = $rate['avg_rate'] ? round( $rate['avg_rate'], 2 ) : 0;

		/* Detailed bandwidth stats */
		$bw_stats = array();
		
		if( $this->settings['gallery_detailed_bandwidth'] )
		{
			$bandwidth = $this->DB->buildAndFetch( array( 'select' => 'COUNT( * ) AS views, SUM( bsize ) AS transfer',
														  'from'   => 'gallery_bandwidth',
														  'where'  => "file_name='{$file['masked_file_name']}'"
												  )		);
			
			$bw_stats = array( 'title'    => sprintf( $this->lang->words['stats_mem_result_bw_tbl'], $this->settings['gallery_bandwidth_period'] ),
							   'views'    => intval( $bandwidth['views'] ),
							   'transfer' => IPSLib::sizeFormat( intval($bandwidth['transfer']) )
							  );
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->statFileReport( $this->registry->gallery->helper('image')->makeImageTag( $file, array( 'type' => 'thumb' ) ), $file, $rate, $bw_stats );		
	}

	/**
	 * Take Action Against a File
	 *
	 * @return	@e void
	 */
	public function doFileAction()
	{
		$this->request['fid'] = intval($this->request['fid']);
		
		if( $this->request['new_owner'] )
		{
			$member = $this->DB->buildAndFetch( array( 
														'select' => 'member_id', 
														'from' 	 => 'members',
														'where'  => "members_l_display_name='".$this->DB->addSlashes( strtolower($this->request['new_owner']) )."'"
											)	);

			if( ! $member['member_id'] )
			{
				$this->registry->output->showError( $this->lang->words['stats_no_member_found'], 11741 );
			}
			
			$this->DB->update( 'gallery_images', array( 'member_id' => $member['member_id']), 'id=' . $this->request['fid'] );
		}
		
		if( $this->request['clear_rating'] )
		{
		 	$this->DB->delete( 'gallery_ratings', 'id=' . $this->request['fid'] );
		}

		if( $this->request['clear_bandwidth'] )
		{
		 	$i = $this->DB->buildAndFetch( array( 'select' => 'masked_file_name',
												  'from'   => 'gallery_images',
												  'where'  => 'id=' . $this->request['fid'] 
										)	);

		 	$this->DB->delete( 'gallery_bandwidth', "file_name='{$i['masked_file_name']}'" );
		}

		/* Redirect */
		$this->registry->output->global_message = $this->lang->words['stats_actions_taken'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'].$this->form_code.'do=dofilesrch&amp;viewfile='.$this->request['fid'] );
	}
	
	/**
	 *  Load a chart
	 *
	 * @return	@e void
	 */
	private function _getChart()
	{	
		$chart_data	= array();
		$labels		= array();

		$this->DB->build( array( 
								'select' 	=> '*', 
								'from'		=> 'gallery_bandwidth', 
								'where' 	=> 'bdate > ' . ( time() - ( $this->settings['gallery_bandwidth_period'] * 3600 ) ),
								'order' 	=> 'bdate ASC'
						)	);
		$this->DB->execute();
		
		while( $i = $this->DB->fetch() )
		{
		 	$t_data = strftime( "%A", $i['bdate'] );
		 
		 	$chart_data[$t_data]	+= round( ( $i['bsize'] / 1024 ), 2 );
		 	$labels[$t_data]		= $t_data . ' (' . $chart_data[$t_data] . ' kb)';
		}
		
		//-----------------------------------------
		// If no images, don't show chart
		//-----------------------------------------
		
		if( !count($labels) )
		{
			header( "Content-Type: image/gif" );
			print base64_decode( "R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" );
			exit;
		}

		require_once( IPS_KERNEL_PATH . '/classGraph.php' );/*noLibHook*/
		$graph = new classGraph();
		$graph->options['title'] = sprintf( $this->lang->words['stats_chart_bw_usage'], $this->settings['gallery_bandwidth_period'] );
		$graph->options['width'] = 650;
		$graph->options['height'] = 400;
		$graph->options['style3D'] = 1;
		
		$graph->addLabels( $labels );
		$graph->addSeries( 'test', $chart_data );

		$graph->options['charttype'] = 'Pie';
		$graph->display();
		exit;
	}
}