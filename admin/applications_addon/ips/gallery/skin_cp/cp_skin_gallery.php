<?php
/**
 * @file		cp_skin_gallery.php 	IP.Gallery admin templates
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * $LastChangedDate: 2011-12-09 15:24:24 -0500 (Fri, 09 Dec 2011) $
 * @version		v4.2.1
 * $Revision: 9978 $
 */

/**
 *
 * @class		cp_skin_gallery
 * @brief		IP.Gallery admin templates
 */
class cp_skin_gallery
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
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member   	= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang 		= $this->registry->class_localization;
	}


/**
 * Stats overview
 *
 * @param	array		$overall			Overall data
 * @param	array		$groups_disk		Groups disk usage
 * @param	array		$users_disk			Users disk usage
 * @param 	array		$groups_bandwidth	Groups bandwidth usage
 * @param	array		$users_bandwidth	Users bandwidth usage
 * @param	array		$files_bandwidth	Files bandwidth usage
 * @return	@e string	HTML
 */
public function statsOverview( $overall, $groups_disk, $users_disk, $groups_bandwidth, $users_bandwidth, $files_bandwidth ) {
$IPBHTML = "";
//--starthtml--//
$pasthours = sprintf( $this->lang->words['stats_td_transfer'], $this->settings['gallery_bandwidth_period'] );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['stats_page_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['stats_overview_tbl_header']}</h3>
	<table class='ipsTable'>
		<tr>
			<td width='25%'><strong class='title'>{$this->lang->words['gal_totaldisk']}</strong></td>
			<td width='25%'>{$overall['total_diskspace']}</td>
			<td width='25%'><strong class='title'>{$this->lang->words['gal_totalupload']}</strong></td>
			<td width='25%'>{$overall['total_uploads']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$pasthours}</strong></td>
			<td>{$overall['total_transfer']}</td>
			<td><strong class='title'>{$this->lang->words['gal_totalviews']}</strong></td>
			<td>{$overall['total_views']}</td>
		</tr>
	</table>
</div>

<center><img alt='{$this->lang->words['gal_statchart']}' src='{$this->settings['base_url']}&amp;module=stats&amp;section=stats&amp;do=get_chart' /></center>
<br />

<div class='section_title'>
	<h2>{$this->lang->words['gal_diskspaceusage']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['gal_groupoverview']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['gal_group']}</th>
			<th width='15%'>{$this->lang->words['gal_diskspaceusage']}</th>
			<th width='20%'>{$this->lang->words['gal_percentusage']}</th>
			<th width='15%'>{$this->lang->words['gal_uploadedfiles']}</th>
			<th width='20%'>{$this->lang->words['gal_percentoffiles']}</th>
		</tr>
HTML;

if( count( $groups_disk ) )
{
	foreach( $groups_disk as $r )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong><a href='{$this->settings['board_url']}/index.php?app=members&amp;module=list&amp;section=view&amp;filter={$r['g_id']}' target='_blank'>{$r['g_title']}</a></strong> <a href='{$this->settings['base_url']}{$this->form_code}do=dogroupsrch&amp;viewgroup={$r['g_id']}' title='{$this->lang->words['gal_viewgreport']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['gal_viewgreport']}' /></a></td>
			<td>{$r['diskspace']}</td>
			<td>{$r['dp_percent']}%</td>
			<td>{$r['uploads']}</td>
			<td>{$r['up_percent']}%</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['gal_top5disk']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['gal_member']}</th>
			<th width='15%'>{$this->lang->words['gal_diskspaceusage']}</th>
			<th width='20%'>{$this->lang->words['gal_percentusage']}</th>
			<th width='15%'>{$this->lang->words['gal_uploadedfiles']}</th>
			<th width='20%'>{$this->lang->words['gal_percentoffiles']}</th>
		</tr>
HTML;

if( count( $users_disk ) )
{
	foreach( $users_disk as $r )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong><a href='{$this->settings['board_url']}/index.php?showuser={$r['mid']}' target='_blank'>{$r['members_display_name']}</a></strong> <a href='{$this->settings['base_url']}{$this->form_code}do=domemsrch&amp;viewuser={$r['mid']}' title='{$this->lang->words['gal_viewmreport']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['gal_viewmreport']}' /></a></td>
			<td>{$r['diskspace']}</td>
			<td>{$r['dp_percent']}%</td>
			<td>{$r['uploads']}</td>
			<td>{$r['up_percent']}%</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />


<div class='section_title'>
	<h2>{$this->lang->words['stats_h_bandwidth']}</h2>
</div>
<div class='acp-box'>
	<h3>{$this->lang->words['stats_group_tbl_header']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['gal_group']}</th>
			<th width='15%'>{$this->lang->words['gal_transfer']}</th>
			<th width='20%'>{$this->lang->words['gal_percentoftransfer']}</th>
			<th width='15%'>{$this->lang->words['gal_imageloads']}</th>
			<th width='20%'>{$this->lang->words['gal_percentofloads']}</th>
		</tr>
HTML;

if( count( $groups_bandwidth ) )
{
	foreach( $groups_bandwidth as $r )
	{
$IPBHTML .= <<<HTML
		<tr>
			<td><strong><a href='{$this->settings['board_url']}/index.php?app=members&amp;module=list&amp;section=view&amp;filter={$r['g_id']}' target='_blank'>{$r['g_title']}</a></strong> <a href='{$this->settings['base_url']}{$this->form_code}do=dogroupsrch&amp;viewgroup={$r['g_id']}' title='{$this->lang->words['gal_viewgreport']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['gal_viewgreport']}' /></a></td>
			<td>{$r['transfer']}</td>
			<td>{$r['dp_percent']}%</td>
			<td>{$r['total']}</td>
			<td>{$r['up_percent']}%</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['gal_top5bw']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['gal_member']}</th>
			<th width='15%'>{$this->lang->words['gal_transfer']}</th>
			<th width='20%'>{$this->lang->words['gal_percentoftransfer']}</th>
			<th width='15%'>{$this->lang->words['gal_imageloads']}</th>
			<th width='20%'>{$this->lang->words['gal_percentofloads']}</th>
		</tr>
HTML;

if( count( $users_bandwidth ) )
{
	foreach( $users_bandwidth as $r )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong><a href='{$this->settings['board_url']}/index.php?showuser={$r['member_id']}' target='_blank'>{$r['members_display_name']}</a></strong> <a href='{$this->settings['base_url']}{$this->form_code}do=domemsrch&amp;viewuser={$r['member_id']}' title='{$this->lang->words['gal_viewmreport']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['gal_viewmreport']}' /></a></td>
			<td>{$r['transfer']}</td>
			<td>{$r['dp_percent']}%</td>
			<td>{$r['total']}</td>
			<td>{$r['up_percent']}%</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['gal_top5files']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['gal_file']}</th>
			<th width='15%'>{$this->lang->words['gal_transfer']}</th>
			<th width='20%'>{$this->lang->words['gal_percentoftransfer']}</th>
			<th width='15%'>{$this->lang->words['gal_imageloads']}</th>
			<th width='20%'>{$this->lang->words['gal_percentofloads']}</th>
		</tr>
HTML;

if( count( $files_bandwidth ) )
{
	foreach( $files_bandwidth as $r )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong><a href='{$this->settings['board_url']}/index.php?app=gallery&amp;image={$r['id']}' target='_blank'>{$r['file_name']}</a></strong> <a href='{$this->settings['base_url']}{$this->form_code}do=dofilesrch&amp;viewfile={$r['id']}' title='{$this->lang->words['gal_viewfreport']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png'alt='{$this->lang->words['gal_viewfreport']}' /></a></td>
			<td>{$r['transfer']}</td>
			<td>{$r['dp_percent']}%</td>
			<td>{$r['total']}</td>
			<td>{$r['up_percent']}%</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Stat search result
 *
 * @param	string		$title		Page title
 * @param	array		$rows		Results data
 * @return	@e string	HTML
 */
public function statSearchResults( $title, $rows ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['stats_results_page_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$title}</h3>
	
	<table class='ipsTable'>
HTML;

if( count( $rows ) )
{	
	foreach( $rows as $r )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$r['thumb']}<a href='{$r['url']}'>{$r['name']}</a></td>
		</tr>
HTML;
	}
}	
else
{
$IPBHTML .= <<<HTML
		<tr>
			<td class='no_messages'>{$this->lang->words['stats_mem_results_none']}</td>
		</tr>
HTML;
}		
$IPBHTML .= <<<HTML
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Group file report
 *
 * @param	array		$stats		Stats data
 * @param	array		$bw			Bandwidth data
 * @param	integer		$comments	Comments count
 * @param	array		$rate		Ratings data
 * @param	string		$title		Page title
 * @return	@e string	HTML
 */
public function groupFileReport( $stats, $bw, $comments, $rate, $title ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['stats_overview_tbl_header']}</h3>
	<table class='ipsTable'>
		<tr>
			<th colspan='4'>{$this->lang->words['stats_mem_disk_over']}</th>
		</tr>
		<tr>
			<td width='25%'><strong>{$this->lang->words['stats_td_diskspace']}</strong></td>
			<td width='25%'>{$stats['group_size']}</td>
			<td width='25%'><strong>{$this->lang->words['stats_td_disk_percent']}</strong></td>
			<td width='25%'>{$stats['dp_percent']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_uploads']}</strong></td>
			<td>{$stats['group_uploads']}</td>
			<td><strong>{$this->lang->words['stats_td_ups_percent']}</strong></td>
			<td>{$stats['up_percent']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_average_all']}</strong></td>
			<td>{$stats['total_avg_size']}</td>
			<td><strong>{$this->lang->words['stats_td_average']}</strong></td>
			<td>{$stats['group_avg_size']}</td>
		</tr>
HTML;

if( count( $bw ) )
{
	$IPBHTML .= <<<HTML
		<tr>
			<th colspan='4'>{$bw['title']}</th>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_transfer_nop']}</strong></td>
			<td>{$stats['group_transfer']}</td>
			<td><strong>{$this->lang->words['stats_bandwidth_tpercent']}</strong></td>
			<td>{$bw['tr_percent']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_views']}</strong></td>
			<td>{$stats['group_viewed']}</td>
			<td><strong>{$this->lang->words['stats_td_views_percent']}</strong></td>
			<td>{$bw['vi_percent']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['stats_other_tbl_header']}</h3>
	<table class='ipsTable'>
		<tr>
			<td width='16%'><strong>{$this->lang->words['stats_td_comments']}</strong></td>
			<td width='16%'>{$comments}</td>
			<td width='16%'><strong>{$this->lang->words['stats_td_ttlg_rating']}</strong></td>
			<td width='16%'>{$rate['total_rates']}</td>
			<td width='16%'><strong>{$this->lang->words['stats_td_avgg_rating']}</strong></td>
			<td width='16%'>{$rate['avg_rate']}</td>
		</tr>
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Member file report
 *
 * @param	integer		$mid		Member ID
 * @param	array		$stats		Stats data
 * @param	integer		$comments	Comments count
 * @param	array		$rate		Ratings data
 * @param	string		$title		Page title
 * @return	@e string	HTML
 */
public function memberFileReport( $mid, $stats, $comments, $rate, $title ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['stats_overview_tbl_header']}</h3>

	<table class='ipsTable'>
		<tr>
			<th colspan='4'>{$this->lang->words['stats_mem_disk_over']}</th>
		</tr>
		<tr>
			<td width='25%'><strong>{$this->lang->words['stats_td_diskspace']}</strong></td>
			<td width='25%'>{$stats['user_size']}</td>
			<td width='25%'><strong>{$this->lang->words['stats_td_disk_percent']}</strong></td>
			<td width='25%'>{$stats['dp_percent']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_uploads']}</strong></td>
			<td>{$stats['user_uploads']}</td>
			<td><strong>{$this->lang->words['stats_td_ups_percent']}</strong></td>
			<td>{$stats['up_percent']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_average_all']}</strong></td>
			<td>{$stats['total_avg_size']}</td>
			<td><strong>{$this->lang->words['stats_td_average']}</strong></td>
			<td>{$stats['user_avg_size']}</td>
		</tr>
HTML;

if( count( $stats['bw'] ) )
{
$IPBHTML .= <<<HTML
		<tr>
			<th colspan='4'>{$stats['bw']['title']}</th>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_transfer_nop']}</strong></td>
			<td>{$stats['user_transfer']}</td>
			<td><strong>{$this->lang->words['stats_bandwidth_tpercent']}</strong></td>
			<td>{$stats['bw']['tr_percent']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_td_views']}</strong></td>
			<td>{$stats['user_viewed']}</td>
			<td><strong>{$this->lang->words['stats_td_views_percent']}</strong></td>
			<td>{$stats['bw']['vi_percent']}</td>
		</tr>
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$stats['bw']['list_title']}</h3>

	<table class='ipsTable'>
		<tr>
			<th>{$this->lang->words['stats_bandwidth_file']}</th>
			<th>{$this->lang->words['stats_bandwidth_transfer']}</th>
			<th>{$this->lang->words['stats_bandwidth_user_trans']}</th>
			<th>{$this->lang->words['stats_bandwidth_loads']}</th>
			<th>{$this->lang->words['stats_bandwidth_user_views']}s</th>
		</tr>
HTML;

foreach( $stats['bw']['rows'] as $r )
{
$IPBHTML .= <<<HTML
		<tr>
			<td>
				<strong>{$r['file_name']}</strong> <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=dofilesrch&amp;viewfile={$r['id']}' title='{$this->lang->words['stats_view_file_alt']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['stats_view_file_alt']}' /></a>
			</td>
			<td>{$r['transfer']}%</td>
			<td>{$r['dp_percent']}</td>
			<td>{$r['total']}</td>
			<td>{$r['up_percent']}%</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />
HTML;
}

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['stats_other_tbl_header']}</h3>

	<table class='ipsTable'>
		<tr>
			<td width='16%'><strong>{$this->lang->words['stats_td_comments']}</strong></td>
			<td width='16%'>{$comments}</td>
			<td width='16%'><strong>{$this->lang->words['stats_td_ttl_rating']}</strong></td>
			<td width='16%'>{$rate['total_rates']}</td>
			<td width='16%'><strong>{$this->lang->words['stats_td_avg_rating']}</strong></td>
			<td width='16%'>{$rate['avg_rate']}</td>
		</tr>
	</table>
</div><br />

<div class='acp-box'>
	<h3>{$this->lang->words['stats_take_mem_action']}</h3>
	<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
		<input type='hidden' name='do' value='domemact' />
		<input type='hidden' name='mid' value='{$mid}' />
		<input type='hidden' name='_admin_auth_key' value='{$this->member->form_hash}' />

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['stats_mem_dis_up']}</strong></td>
				<td class='field_field'>{$stats['remove_uploading']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['stats_mem_dis_gal']}</strong></td>
				<td class='field_field'>{$stats['remove_gallery']}</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['stats_mem_action_submit']}' class='primary button' accesskey='s'>
		</div>
	</form>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * File report
 *
 * @param	array		$img		Image
 * @param	array		$file		File data
 * @param	array		$rate		Ratings data
 * @param	array		$bw			Bandwidth data
 * @return	@e string	HTML
 */
public function statFileReport( $img, $file, $rate, $bw ) {
$IPBHTML = "";
//--starthtml--//

$title	= sprintf( $this->lang->words['stats_file_result_title'], $file['file_name'] );

$form = array( 'new_owner'		 => $this->registry->output->formSimpleInput( 'new_owner', $file['members_display_name'], 40 ),
			   'clear_bandwidth' => $this->registry->output->formYesNo( 'clear_bandwidth' ),
			   'clear_rating'	 => $this->registry->output->formYesNo( 'clear_rating' )
			  );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<center>{$img}</center>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['stats_file_overview']}</h3>
	<table class='ipsTable'>
		<tr>
			<th colspan='4'>{$this->lang->words['stats_file_gen_overview']}</th>
		</tr>
		<tr>
			<td width='25%'><strong>{$this->lang->words['stats_file_uploadedby']}</strong></td>
			<td width='25%'>{$file['members_display_name']} <a href='{$this->settings['base_url']}{$this->form_code}do=domemsrch&amp;viewuser={$file['mid']}' title='{$this->lang->words['stats_view_mem_alt']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['stats_view_mem_alt']}' /></a></td>
			<td width='25%'><strong>{$this->lang->words['stats_file_approved']}</strong></td>
			<td width='25%'>{$file['approved']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_file_size']}</strong></td>
			<td>{$file['file_size']}</td>
			<td><strong>{$this->lang->words['stats_file_type']}</strong></td>
			<td>{$file['file_type']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_file_maskname']}</strong></td>
			<td>{$file['masked_file_name']}</td>
			<td><strong>{$this->lang->words['stats_file_thumb']}</strong></td>
			<td>{$file['thumbnail']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_file_date']}</strong></td>
			<td>{$file['idate']}</td>
			<td><strong>{$file['local_name']}</strong></td>
			<td>{$file['container']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_file_comments']}</strong></td>
			<td>{$file['comments']}</td>
			<td><strong>{$this->lang->words['stats_file_views']}</strong></td>
			<td>{$file['views']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_file_rates']}</strong></td>
			<td>{$rate['total_rate']}</td>
			<td><strong>{$this->lang->words['stats_file_avg_rates']}</strong></td>
			<td>{$rate['avg_rate']}</td>
		</tr>
HTML;

if( count( $bw ) )
{
$IPBHTML .= <<<HTML
		<tr>
			<th colspan='4'>{$bw['title']}</th>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['stats_bandwidth_loads']}</strong></td>
			<td>{$bw['views']}</td>
			<td><strong>{$this->lang->words['stats_bandwidth_transfer']}</strong></td>
			<td>{$bw['transfer']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<form name='DOIT' id='DOIT' action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='dofileact' />
	<input type='hidden' name='fid' value='{$file['id']}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['gal_takeactiononfile']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['gal_changeowner']}</strong></td>
				<td class='field_field'>{$form['new_owner']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['gal_clearbwlogs']}</strong></td>
				<td class='field_field'>{$form['clear_bandwidth']}</td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['gal_clearratinglogs']}</strong></td>
				<td class='field_field'>{$form['clear_rating']}</td>
			</tr>
		</table>
		<div class="acp-actionbar">
			<input value="{$this->lang->words['gal_takeaction']}" class="button primary" type="submit" />
		</div>
	</div>
</form>
<script type="text/javascript" defer="defer">
document.observe("dom:loaded", function(){
	var search = new ipb.Autocomplete( $('new_owner'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Gallery overview
 *
 * @param	array		$warnings		Warnings
 * @param	array		$versions		Upgrade records
 * @param	array		$stats			Statistics data
 * @return	@e string	HTML
 */
public function galleryOverview( $warnings=array(), $versions=array(), $stats=array() ) {
$IPBHTML = "";
//--starthtml--//

if( count( $warnings ) )
{
$IPBHTML .= <<<HTML
<div class='warning'>
	<span style='font-size:20px;font-weight:bold'>{$this->lang->words['gal_possibleerrors']}</span>
	<br /><br />
	<table width='100%' style='border:1px solid black;'>
		<tr>
			<th width='25%' style='border:1px solid black;padding:5px;'><strong>{$this->lang->words['gal_problem']}</strong></th>
			<th width='25%' style='border:1px solid black;padding:5px;'><strong>{$this->lang->words['gal_affectedsetting']}</strong></th>
			<th width='50%' style='border:1px solid black;padding:5px;'><strong>{$this->lang->words['gal_possiblefixes']}</strong></th>
		</tr>
HTML;

	foreach( $warnings as $r )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td style='border:1px solid black;padding:5px;'>{$r[0]}</td>
			<td style='border:1px solid black;padding:5px;'><a href='{$r[1]}'>{$r[2]}</a></td>
			<td style='border:1px solid black;padding:5px;'>{$r[3]}</td>
		</tr>
HTML;
	}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />
HTML;
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['overview_page_title']}</h2>
	
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}do=update_stats'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='' /> {$this->lang->words['overview_rebuild_stats']}</a>
			</li>
		</ul>
	</div>
</div>

<table width='100%'>
	<tr>
		<td width='39%' valign='top'>
			<div class='acp-box'>
				<h3>{$this->lang->words['gal_quickstats']}</h3>
				<table class='ipsTable'>
					<tr>
						<td width='60%'><strong class='title'>{$this->lang->words['gal_totalimages']}</strong></td>
						<td width='40%'>{$stats['images']}</td>
					</tr>
					<tr>
						<td><strong class='title'>{$this->lang->words['gal_totaldiskspace']}</strong></td>
						<td>{$stats['diskspace']}</td>
					</tr>
					<tr>
						<td><strong class='title'>{$this->lang->words['gal_totalcomments']}</strong></td>
						<td>{$stats['comments']}</td>
					</tr>
					<tr>
						<td><strong class='title'>{$this->lang->words['gal_totalalbums']}</strong></td>
						<td>{$stats['albums']}</td>
					</tr>
				</table>			
			</div>
		</td>
		<td width='1%'>&nbsp;</td>
		<td width='60%' valign='top'>
			<div class='acp-box'>
				<h3>{$this->lang->words['overview_quick_searches']}</h3>
				<table class='ipsTable'>
					<tr>
						<td><strong class='title'>{$this->lang->words['overview_member_search']}</strong></td>
						<td class='field_field'>
							<form action='{$this->settings['base_url']}' method='post'>
								<input type='hidden' name='module' value='stats' />
								<input type='hidden' name='do' value='domemsrch' />
								<input type='text' name='search_term' id='membersearch' value='' size='40' class='input_text' />
								&nbsp;&nbsp;<input type='submit' value='{$this->lang->words['overview_member_search_submit']}' accesskey='s' class='button primary' />
							</form>
						</td>
					</tr>
					<tr>
						<td><strong class='title'>{$this->lang->words['overview_group_search']}</strong></td>
						<td class='field_field'>
							<form action='{$this->settings['base_url']}' method='post'>
								<input type='hidden' name='module' value='stats' />
								<input type='hidden' name='do' value='dogroupsrch' />
								<input type='text' name='search_term' value='' size='40' class='input_text' />
								&nbsp;&nbsp;<input type='submit' value='{$this->lang->words['overview_group_search_submit']}' class='button primary' accesskey='s' />
							</form>
						</td>
					</tr>
					<tr>
						<td><strong class='title'>{$this->lang->words['overview_file_search']}</strong></td>
						<td class='field_field'>
							<form action='{$this->settings['base_url']}' method='post'>
								<input type='hidden' name='module' value='stats' />
								<input type='hidden' name='do' value='dofilesrch' />
								<input type='text' name='search_term' value='' size='40' class='input_text' />
								&nbsp;&nbsp;<input type='submit' value='{$this->lang->words['overview_file_search_submit']}' class='button primary' accesskey='s' />
							</form>
						</td>
					</tr>
				</table>
			</div>
			<script type='text/javascript'>
				Event.observe( window, "load", function(){
					var search = new ipb.Autocomplete( $('membersearch'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
				});
			</script>
		</td>
	</tr>
</table>
<br />

<table width='100%'>
	<tr>
		<td width='39%' valign='top'>
			<div class='acp-box'>
				<h3>{$this->lang->words['gal_upgradehistory']}</h3>
				<table class='ipsTable'>
HTML;
	
	foreach( $versions as $r )
	{
					$IPBHTML .= <<<HTML
					<tr>
						<td>{$r['upgrade_version_human']} ({$r['upgrade_version_id']})</td>
						<td>{$r['upgrade_date']}</td>
					</tr>
HTML;
	}
	
	$IPBHTML .= <<<HTML
				</table>
			</div>
		</td>
		<td width='1%'>&nbsp;</td>
		<td width='60%' valign='top'>
			<div class='acp-box'>
				<h3>{$this->lang->words['gal_groupoverview']}</h3>
				<table class='ipsTable'>
HTML;
	
	foreach( $this->cache->getCache('group_cache') as $r )
	{
		$r['g_title']         = IPSMember::makeNameFormatted( $r['g_title'], $r['g_id'] );
		$r['_noSetUp']        = ( ! $r['g_create_albums'] OR ! $r['g_gallery_use'] ) ? 1 : 0;
		$r['_setUp']		  = ( $r['_noSetUp'] ) ? "<div class='desctext'>{$this->lang->words['overview_g_nosetup']}</div>" : '';
		
		$IPBHTML .= <<<HTML
					<tr class='ipsControlRow'>
						<td>{$r['g_title']}{$r['_setUp']}</td>
						<td class='col_buttons'>
							<ul class='ipsControlStrip'>
								<li class='i_edit'><a href='{$this->settings['base_url']}app=members&amp;module=groups&amp;section=groups&amp;do=edit&amp;id={$r['g_id']}&amp;_initTab=gallery'>{$this->lang->words['gal_group_edit']}</a></li>
							</ul>
						</td>
					</tr>
HTML;
	}
	
	$IPBHTML .= <<<HTML
				</table>
			</div>
		</td>
	</tr>
</table>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Delete album popup
 *
 * @param	array		$data		Popup data
 * @return	@e string	HTML
 */
public function acpDeleteAlbumDialogue( $data=array(), $hasKids=false ) {
$IPBHTML = "";
//--starthtml--//

if ( $hasKids )
{
	$IPBHTML .= <<<HTML
<h3>{$this->lang->words['delete_album']}</h3>
<div class='pad center'>
	{$this->lang->words['mod_alb_del_not_able_children']}
</div>
HTML;
	
	return $IPBHTML;
}

$IPBHTML .= <<<HTML
<form action="{$this->settings['base_url']}app=gallery&amp;module=albums&amp;section=manage&amp;do=deleteAlbum&amp;albumId={$data['album_id']}&amp;secure_key={$this->member->form_hash}" method="post" id="albumDeleteForm_{$data['album_id']}">
	<input type='hidden' name='auth_key' value='{$this->member->form_hash}' />
	{$data['hiddens']}
	{$data['errors']}
	
	<h3>{$this->lang->words['delete_album']}</h3>
	<div class='pad center'>
	 {$this->lang->words['mod_alb_del_title']}
HTML;

if ( $data['options'] !== false )
{
	$IPBHTML .= <<<HTML
	<div style="width:auto; display:inline-block; margin: 0 auto; text-align: left;" class='pad'>
		<input type="radio" name="doDelete" value="0" checked="checked" /> {$this->lang->words['mod_alb_del_move']}
		<select name='move_to_album_id' id='move_to_album_id' class='input_select'>
			{$data['options']}
		</select>
		<br />
		<input type="radio" name="doDelete" value="1" /> {$this->lang->words['mod_alb_del_desc']}
	</div>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	<input type="hidden" name="doDelete" value="1" />
HTML;
}

$IPBHTML .= <<<HTML
	 <input type='submit' class="button primary" value="{$this->lang->words['mod_alb_del_go']}" />
	</div>
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}


/**
 * Albums page wrapper
 *
 * @param	array		$albums		Albums data
 * @param	string		$parentId	Parent album ID
 * @return	@e string	HTML
 */
public function albums( $globalAlbums, $parentId=0, $memberAlbums ) {

$IPBHTML = "";
//--starthtml--//

$_public = PUBLIC_DIRECTORY;

$IPBHTML .= <<<HTML
<script type='text/javascript' id='progressbarScript' src='{$this->settings['public_dir']}js/3rd_party/progressbar/progressbar.js'></script>
<script type='text/javascript' id='progressbarScript' src='{$this->settings['public_dir']}js/ips.gallery_albumchooser.js'></script>
<link rel='stylesheet' type='text/css' media='screen' href='{$this->settings['skin_app_url']}/gallery.css' />
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.gallery.js'></script>
<script type="text/javascript">
ACPGallery.section = 'albums';
</script>
<style type="text/css">
.child_album
{
	margin-top: 3px;
}

.child_album span
{
	display: inline-block;
	padding-top:4px;
}

.child_album a
{
	text-decoration: none;
}

.child_album a:hover
{
	text-decoration: underline;
}

#album_search_box {
	background: #fff url( {$this->settings['skin_acp_url']}/images/search_box_icon.png ) no-repeat 2px 3px;
	border: 1px inset #142A42;
	font-size: 11px;
	width: 190px;
	margin-top: -2px;
	padding: 3px 22px;
	color: #C7C7C7;
}

.galattach.cover_img___xx___ {
	width: 75px;
	height: 75px;
	background: url( ./../{$_public}/style_images/master/gallery/75x75.png ) no-repeat 0px 0px;
	padding: 13px;
}

#albumThumbs {
	padding: 8px 0px 4px 0px;
	height: 24px;
}
.inlineimage img {
	float: left;
	width: 24px;
	height: 24px;
}
</style>
<div class='section_title'>
	<h2>{$this->lang->words['albums_page_title']}</h2>
	
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='#' class='_acp_gallery_addalbum'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['albums_add_button']}</a>
			</li>
			<li class='ipsActionButton'>
				<a href='#' class='ipbmenu' id='albumTools'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' /> {$this->lang->words['tools_button']} <img src='{$this->settings['skin_acp_url']}/images/useropts_arrow.png' /></a>
				<ul class='ipbmenu_content' id='albumTools_menucontent' style='display: none'>
					<li><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> <a href='#' album-id="all" progress="thumbs">{$this->lang->words['albums_tool_images']}</a></li>
					<li><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> <a href='#' album-id="all" progress="resetpermissions">{$this->lang->words['albums_tool_perms']}</a></li>
					<li><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> <a href='#' album-id="all" progress="resyncalbums">{$this->lang->words['albums_tool_resync']}</a></li>
					<li><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> <a href='#' album-id="all" progress="rebuildnodes">{$this->lang->words['albums_tool_nodes']}</a></li>
					<li><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> <a href='#' album-id="all" progress="rebuildtreecaches">{$this->lang->words['acp_tool_rebuild_trees']}</a></li>
					<li><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> <a href='#' album-id="all" progress="rebuildstats">{$this->lang->words['acp_tool_restats']}</a></li>
				</ul>
			</li>
		</ul>
	</div>
</div>
HTML;

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['acp_manage_albums']}</h3>
	<div id='tabstrip_group' class='ipsTabBar'>
	<ul>
		<li id='tab_GALLERY_1'>{$this->lang->words['acp_global_albums']}</li>
		<li id='tab_GALLERY_2'>{$this->lang->words['acp_member_albums']}</li>
	</ul>
	</div>
	<div id='tabstrip_group_content' class='ipsTabBar_content'>
	<div id='tab_GALLERY_1_content'>
	<div class='header'>
		{$this->lang->words['global_albums_filters']}
		<select id='searchMatch_global'>
			<option value='is'>{$this->lang->words['filters_is']}</option>
			<option value='contains'>{$this->lang->words['filters_contains']}</option>
		</select>
		<input type='text' size='20' class='input_text' id='searchText_global' />
		<select id='searchSort_global'>
			<option value='date' id='searchSort_global_date'>{$this->lang->words['filters_sort_upload']}</option>
			<option value='name' id='searchSort_global_name'>{$this->lang->words['filters_sort_name']}</option>
			<option value='images' id='searchSort_global_images'>{$this->lang->words['filters_sort_images']}</option>
			<option value='comments' id='searchSort_global_comments'>{$this->lang->words['filters_sort_comments']}</option>
		</select>
		<select id='searchDir_global'>
			<option value='desc' id='searchDir_global_desc'>{$this->lang->words['filters_desc']}</option>
			<option value='asc' id='searchDir_global_asc'>{$this->lang->words['filters_asc']}</option>
		</select>
		<input type='button' id='searchGo_global' value='{$this->lang->words['filters_update']}' class='mini_button' />
	</div>
	<div id='galleryGlobalAlbumsHere'>
		{$globalAlbums}
	</div>
	</div>
	<div id='tab_GALLERY_2_content'>
		<div class='header'>
			{$this->lang->words['filters_show_all_where']}
			<select id='searchType'>
				<option value='member' id='searchType_member'>{$this->lang->words['filters_owners_name']}</option>
				<option value='album' id='searchType_album'>{$this->lang->words['filters_albums_name']}</option>
				<option value='parent' id='searchType_parent'>{$this->lang->words['filters_albums_parent']}</option>
			</select>
			<select id='searchMatch'>
				<option value='is'>{$this->lang->words['filters_is']}</option>
				<option value='contains'>{$this->lang->words['filters_contains']}</option>
			</select>
			<input type='text' size='20' class='input_text' id='searchText' />
			<select id='searchSort'>
				<option value='date' id='searchSort_date'>{$this->lang->words['filters_sort_upload']}</option>
				<option value='name' id='searchSort_name'>{$this->lang->words['filters_sort_name']}</option>
				<option value='images' id='searchSort_images'>{$this->lang->words['filters_sort_images']}</option>
				<option value='comments' id='searchSort_comments'>{$this->lang->words['filters_sort_comments']}</option>
			</select>
			<select id='searchDir'>
				<option value='desc' id='searchDir_desc'>{$this->lang->words['filters_desc']}</option>
				<option value='asc' id='searchDir_asc'>{$this->lang->words['filters_asc']}</option>
			</select>
			<input type='button' id='searchGo' value='{$this->lang->words['filters_update']}' class='mini_button' />
		</div>
		<div id='galleryAlbumsHere'>
			{$memberAlbums}
		</div>
	</div>
</div>
</div>

<script type='text/javascript'>
	jQ("#tabstrip_group").ipsTabBar({tabWrap: "#tabstrip_group_content", defaultTab: "tab_GALLERY_1" });

	var DraggingId = 0;

	startDrag = function( e )
	{
		elem = Event.findElement(e).up('tr');
		
		DraggingId = elem.id.replace( /albums_/, '' );
	};
	
	function processGalleryGetDragId( url )
	{
		return url.replace( /\#\{albumId\}/, DraggingId );
	}
	
	$$('.draghandle').each( function(elem) { elem.observe('mousedown', startDrag ); } );
	
	jQ("#albumsDragList").ipsSortable('table', { 
		url: "{$this->settings['base_url']}&module=ajax&section=albums&do=reorder&albumId=#{albumId}&md5check={$this->member->form_hash}".replace( /&amp;/g, '&' ),
		serializeOptions: { key: 'albums[]' },
		callBackUrlProcess: processGalleryGetDragId
	} );
	
	ACPGallery.templates['autocompleteAlbumWrap'] = new Template("<ul id='#{id}' class='ipbmenu_content' style='width: 230px; max-height:400px;overflow:auto'></ul>");
	ACPGallery.templates['autocompleteAlbumItem'] = new Template("<li id='#{id}' onclick='ACPGallery.albumSearchClick(event)'><div style='height:34px; padding:2px;'><div style='float:left;margin-right:5px;'>#{img}</div><div>#{itemvalue}<br /><span class='desctext'>#{img_w} {$this->lang->words['gal_images']}</span></div></div></li>");
</script>

<!-- NEW ALBUM POPUP -->
<div id='acp_gallery_addDialogue' style='display:none'>
	<div>
		<h3>{$this->lang->words['albums_add_title']}</h3>
		<table class='ipsTable'>
			<tr>
				<td><img src='{$this->settings['skin_app_url']}/images/gallery-global-album.png' alt='' /></td>
				<td>
					<strong>{$this->lang->words['albums_global_album_title']}</strong>
					<p class='desctext'>{$this->lang->words['albums_global_add_desc']}</p>
					<br />
					<p><a href="{$this->settings['base_url']}{$this->form_code}do=add&amp;albumType=global" class="button primary">{$this->lang->words['albums_global_album_title_add']}</a></p>
				</td>
			</tr>
			<tr>
				<td><img src='{$this->settings['skin_app_url']}/images/gallery-user-album.png' alt='' /></td>
				<td>
					<strong>{$this->lang->words['albums_member_album_title']}</strong>
					<p class='desctext'>{$this->lang->words['albums_member_add_desc']}</p>
					<br />
					<p><a href="{$this->settings['base_url']}{$this->form_code}do=add&amp;albumType=member" class="button primary">{$this->lang->words['albums_member_album_title_add']}</a></p>
				</td>
			</tr>
		</table>
	</div>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Albums page wrapper
 *
 * @param	array		$albums		Albums data
 * @return	@e string	HTML
 */
public function ajaxAlbums( $albums ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<table class='ipsTable' id='albumsDragList'>
HTML;

if ( !empty( $albums ) )
{
	foreach( $albums as $albumId => $album )
	{	
		$isGlobal =  $this->registry->getClass('gallery')->helper('albums')->isGlobal( $album );
		
		$cover   = $this->registry->getClass('gallery')->inlineResize( $album['thumb'], 30, 30 );
		$hasKids = ( count( $album['_children'] ) ) ? true : false;
		$image   = '';
		
		if ( $this->registry->getClass('gallery')->helper('albums')->isPrivate( $album ) )
		{
			$image = '<img src=\'' . $this->settings['skin_app_url'] . '/images/lock.png\' style="vertical-align: text-top" title=\'' . $this->lang->words['acp_private_album'] . '\' />&nbsp;';
		}
		else if ($this->registry->getClass('gallery')->helper('albums')->isFriends( $album ) )
		{
			$image = '<img src=\'' . $this->settings['skin_app_url'] . '/images/users.png\' style="vertical-align: text-top" title=\'' . $this->lang->words['acp_friends_album'] . '\' />&nbsp;';
		}
		
	if ( $isGlobal )
	{
		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow isDraggable' id='albums_{$albumId}'>
		<td width='1%' style='col_drag'>
			<div class='draghandle'>&nbsp;</div>
		</td>
HTML;
	}
	else
	{
		$IPBHTML .= <<<HTML
		<tr id='albums_{$albumId}'>
HTML;
		
	}
$IPBHTML .= <<<HTML
		<td width='1%'><div class='ipsUserPhoto'>{$cover}</div></td>
		<td width='45%'>
HTML;
		if ( $album['_parents'] )
		{
			foreach( $album['_parents'] as $id => $data )
			{
				$IPBHTML .= <<<HTML
			<a class='desctext' href='{$this->settings['base_url']}{$this->form_code}do=edit&amp;albumId={$id}' title='{$this->lang->words['edit']}'>{$data['album_name']}</a> <span class='desctext'>&rarr;</span>
HTML;
			}
		}
		
		if ( $hasKids )
		{
			$IPBHTML .= <<<HTML
			{$image}<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=overview&amp;parentId={$albumId}'><strong>{$album['album_name']}</strong></a>
			<div class='pad'>
				<ul class='ipsList_inline'>
HTML;
			foreach( $album['_children'] as $id => $data )
			{
				$cover = $this->registry->getClass('gallery')->inlineResize( $data['thumb'], 14, 14 );
				
				$IPBHTML .= <<<HTML
					 <li>{$cover} <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=overview&amp;parentId={$albumId}'>{$data['album_name']}</a></li>
HTML;
			}
			
			$IPBHTML .= <<<HTML
				</ul>
			</div>
HTML;
		}
		else
		{
			$IPBHTML .= <<<HTML
			{$image}<strong>{$album['album_name']}</strong>
HTML;
		}
			
		/* Global album (container) only? */
		$album['album_count_imgs'] = $this->registry->gallery->helper('albums')->isContainerOnly($album) ? '&nbsp;' : $album['album_count_imgs'];
		
		$lastUpload = $this->lang->getDate( $album['album_last_img_date'], 'short' );
		
		if ( ! $isGlobal )
		{
			$IPBHTML .= <<<HTML
			<div class='desctext'>{$this->lang->words['album_owned_by']} <a href='#' class='searchByMember' data-album-owners-name='{$album['owners_members_display_name']}' title='{$this->lang->words['acp_albums_find_all_by_members']}'>{$album['owners_members_display_name']}</a></div>
HTML;
		}
		else
		{
			if ( is_array( $album['album_child_tree'] ) )
			{
				$count = count( $album['album_child_tree'] ) - count( $album['_children'] );
			}
			
			if ( $this->registry->gallery->helper('albums')->getMembersAlbumId() == $album['album_id'] )
			{
				$count = $this->caches['gallery_stats']['total_public_member_albums'];
				
				$this->lang->words['acp_contains_n_albums'] = $this->lang->words['acp_contains_n_public_albums'];
			}
			
			if ( $count )
			{
				$langBit = sprintf( $this->lang->words['acp_contains_n_albums'], $count );
				
				$IPBHTML .= <<<HTML
					<div class='desctext'><a href='#' class='searchByParent' data-album-id='{$album['album_id']}' title='{$this->lang->words['acp_albums_find_memberalbums_by_parents']}'>{$langBit}</a></div>
HTML;
			}
		}
		
		$IPBHTML .= <<<HTML
		</td>
		<td width='30%' class='desctext'>
HTML;
		if ( $album['album_count_imgs'] > 0 )
		{
			$IPBHTML .= <<<HTML
			<strong>{$album['album_count_imgs']} {$this->lang->words['images_lower']}<br />{$album['album_count_comments']} {$this->lang->words['comments_lower']}</strong><br />{$this->lang->words['last_upload']} {$lastUpload}
HTML;
		}
		
		$IPBHTML .= <<<HTML
		</td>
		<td width='10%' class='ipsControlRow'>
			<ul class='ipsControlStrip'>
				<li class='i_edit'><a class='edit' href='{$this->settings['base_url']}{$this->form_code}do=edit&amp;albumId={$albumId}' title='{$this->lang->words['edit']}'>{$this->lang->words['edit']}</a></li>
				<li class='ipsControlStrip_more ipbmenu' id='menu_album_{$albumId}'><a href='#'>&nbsp;</a></li>
			</ul>
			<ul class='acp-menu' id='menu_album_{$albumId}_menucontent' style='display: none'>
				<li class='icon delete _albumDeleteDialogueTrigger' album-id='{$albumId}'><a href='#'>{$this->lang->words['delete']}...</a></li>
				<li class='icon delete'><a onclick="if ( !confirm('{$this->lang->words['albums_delete_confirm']}' ) ) { return false; }" href="{$this->settings['base_url']}{$this->form_code}do=emptyAlbum&amp;albumId={$album['album_id']}">{$this->lang->words['albums_link_empty']}</a></li>
				<li class='icon refresh ajaxWithDialogueTrigger' ajaxUrl="app=gallery&amp;module=ajax&amp;section=albums&amp;do=recount&amp;albumId={$albumId}"><a href='#'>{$this->lang->words['albums_link_resynch']}</a></li>
				<li class='icon manage'><a href='#' album-id="{$albumId}" progress="thumbs">{$this->lang->words['albums_link_rebuild']}</a></li>
			</ul>
		</td>
	</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
	</tr>
		<td class='no_messages' colspan='4'>
			{$this->lang->words['no_albums']}
		</td>
	</tr>
HTML;
}

$IPBHTML .= <<<HTML
</table>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Album popup dialogue
 *
 * @param	array		$album		Album data
 * @param	array		$last10		Last 10 album images
 * @return	@e string	HTML
 */
public function albumPopup( $album, $last10=array() ) {

$IPBHTML = "";
//--starthtml--//

$desc = sprintf( $this->lang->words['review_img_comm'], $album['album_count_imgs'], $album['album_count_comments'] );

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$album['album_name']}</h3>
	<div class='left pad'>{$album['thumb']}</div>
	<div class='pad'>
		<strong>{$album['album_name']}</strong>
		<br /><span class='desctext'>{$desc}</span>
		<div id='albumThumbs'>
HTML;
	
	if ( is_array($last10) && count($last10) )
	{
		foreach( $last10 as $thumb )
		{
			
			$IPBHTML .= "<div class='inlineimage'>{$thumb['thumb']}</div>";
		}
	}
	
	$IPBHTML .= <<<HTML
		</div>
	</div>
	<p class='clear center pad'>
		<a href="{$album['selfSeoUrl']}" class="button primary">{$this->lang->words['popup_public_view']}</a> &nbsp;
		<a href="{$this->settings['base_url']}{$this->form_code}module=albums&amp;section=manage&amp;do=overview&amp;parentId={$album['album_parent_id']}" class="button primary">{$this->lang->words['popup_find_in_acp']}</a> &nbsp;
		<a href="{$this->settings['base_url']}{$this->form_code}module=albums&amp;section=manage&amp;do=edit&amp;albumId={$album['album_id']}" class="button primary">{$this->lang->words['edit']}</a> &nbsp;
		<a href="#" album-id='{$album['album_id']}' class="button redbutton _albumDeleteDialogueTrigger">{$this->lang->words['delete']}</a>
	</p>
	<br class='clear' />
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Add/edit album form
 *
 * @param	array		$album		Album data
 * @param	array		$form		Form elements
 * @param	string		$type		Action type
 * @param	string		$albumType	Album type (global|member)
 * @return	@e string	HTML
 */
public function albumForm( $album, $form, $type, $albumType ) 
{
$IPBHTML = "";
//--starthtml--//

if( $type == 'edit' )
{
	$title	= sprintf( $this->lang->words['cats_edit_page_title'], $album['album_name'] );
	$form['button']   = $this->lang->words['albums_link_edit'];
	$form['formcode'] = 'doedit';
	$aSCode           = 'editAlbum';
}
else
{
	$title	= $this->lang->words['cats_add_page_title'];
	$form['button']   = $this->lang->words['albums_add_button'];
	$form['formcode'] = 'doadd';
	$aSCode           = ( $albumType == 'global' ) ? 'createGlobalAlbum' : 'createMemberAlbum';
	
	if ( $albumType == 'member' )
	{
		$album['album_parent_id'] = $this->registry->gallery->helper('albums')->getMembersAlbumId();
	}
}

$memberAlbumOnly     = !$album['album_is_global'] ? "" : " style='display:none' ";
$globalAlbumOnly     = $album['album_is_global']  ? "" : " style='display:none' ";
$parent              = array( 'album_parent_id' => 0, 'album_name' => 'Root' );
$hideIfMembersAlbum  = ( $album['album_id'] == $this->registry->gallery->helper('albums')->getMembersAlbumId() ) ? " style='display:none' " : '';

$watermarkWarning = ( empty($this->settings['gallery_watermark_path']) || !is_file($this->settings['gallery_watermark_path']) ) ? "<br /><br /><div class='information-box'><strong>{$this->lang->words['warning_no_watermark']}</strong></div>" : "";

if ( $album['album_parent_id'] )
{
	$parent = array( 'album_id' => $album['album_parent_id'], 'parentText' => $this->registry->gallery->helper('albums')->getParentsAsTextString( $album['album_parent_id'] ) );
}
else
{
	$parent = array( 'album_id' => 0, 'parentText' => $this->lang->words['as_root'] );
}

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.gallery.js'></script>
<script type='text/javascript' id='progressbarScript' src='{$this->settings['public_dir']}js/ips.gallery_albumchooser.js'></script>
<link rel='stylesheet' type='text/css' media='screen' href='{$this->settings['skin_app_url']}/gallery.css' />
<script type="text/javascript">
	ACPGallery.section = 'form';
</script>
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<style type="text/css">
#tabpane-2 h3 { display: none }
</style>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$form['formcode']}&amp;albumType={$albumType}' id='adminform' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	<input type='hidden' name='albumId' value='{$album['album_id']}' />
	{$form['_custom_hidden_fields']}
	
	<div class='acp-box'>
		<h3>{$title}</h3>
		
		<div id='tabstrip_albumform' class='ipsTabBar with_left with_right'>
			<span class='tab_left'>&laquo;</span>
			<span class='tab_right'>&raquo;</span>
			<ul>
				<li id='tab_AlbumSettings'>{$this->lang->words['cats_form_tbl_settings']}</li>
				<li id='tab_AlbumPerms'{$globalAlbumOnly}>{$this->lang->words['cats_form_tbl_permission']}</li>
				<li id='tab_AlbumRules'{$globalAlbumOnly}>{$this->lang->words['album_manage_rules_title']}</li>
			</ul>
		</div>
		
		<div id='tabstrip_albumform_content' class='ipsTabBar_content'>
			
			<!-- SETTINGS -->
			<div id='tab_AlbumSettings_content'>
				<table class="ipsTable double_pad">
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_name']}</strong></td>
						<td class='field_field'>{$form['album_name']}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_description']}</strong></td>
						<td class='field_field'>{$form['album_description']}</td>
					</tr>
					<tr{$hideIfMembersAlbum}>
						<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_parent']}</strong></td>
						<td class='field_field'>
							<input type='hidden' id='albumParentId' name='album_parent_id' value='{$album['album_parent_id']}' /><div class='albumSelected' id='asDiv'>{$parent['parentText']}</div>
							<a href='#' class='button' data-album-selector-auto-update='{"field": "albumParentId", "div": "asDiv"}' data-album-selector='type={$aSCode}&album_id={$album['album_id']}'>{$this->lang->words['select_album']}</a>					
						</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['album_sort_options__key']}</strong></td>
						<td class='field_field'>{$form['album_sort_options__key']} {$form['album_sort_options__dir']}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['form_watermark_title']}</strong></td>
						<td class='field_field'>{$form['album_watermark']}<br /><span class='desctext'{$globalAlbumOnly}>{$this->lang->words['form_watermark_desc_global']}</span><span class='desctext'{$memberAlbumOnly}>{$this->lang->words['form_watermark_desc_member']}</span>{$watermarkWarning}</td>
					</tr>
					<tr{$memberAlbumOnly}>
						<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_album_owner']}</strong></td>
						<td class='field_field'>{$form['album_owner_id__name']}<br /><span class='desctext'>{$this->lang->words['cats_form_album_owner_desc']}</span></td>
					</tr>
					<tr{$memberAlbumOnly}>
						<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_is_public']}</strong></td>
						<td class='field_field'>{$form['album_is_public']}</td>
					</tr>
					<tr{$globalAlbumOnly}>
						<td class='field_title'><strong class='title'>{$this->lang->words['album_mode']}</strong></td>
						<td class='field_field'>{$form['album_g_container_only']}<br /><span class='desctext'>{$this->lang->words['album_mode_desc']}</span></td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_comments']}</strong></td>
						<td class='field_field'>{$form['album_allow_comments']}</td>
					</tr>
					<tr{$globalAlbumOnly}>
						<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_appcomments']}</strong></td>
						<td class='field_field'>{$form['album_g_approve_com']}</td>
					</tr>
					<tr{$globalAlbumOnly}>
						<td class='field_title'><strong class='title'>{$this->lang->words['cats_form_appimages']}</strong></td>
						<td class='field_field'>{$form['album_g_approve_img']}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['album_show_detail_as_default']}</strong></td>
						<td class='field_field'>{$form['album_detail_default']}<br /><span class='desctext'>{$this->lang->words['album_show_detail_as_default_desc']}</span></td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['album_can_tag']}</strong></td>
						<td class='field_field'>{$form['album_can_tag']}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['album_preset_tags']}</strong></td>
						<td class='field_field'>{$form['album_preset_tags']}<br /><span class='desctext'>{$this->lang->words['album_preset_tags_desc']}</span></td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['album_manage_show_after_forum']}</strong></td>
						<td class='field_field'>{$form['album_after_forum_id']}</td>
					</tr>
				</table>
			</div>
			
			<!-- PERMISSIONS -->
			<div id='tab_AlbumPerms_content'{$globalAlbumOnly}>
HTML;
	if ( $album['album_is_global'] )
	{
		$IPBHTML .= <<<HTML
			{$form['neo_morpheus_trinity']}
HTML;
	}
	$IPBHTML .= <<<HTML
			</div>
			
			<!-- RULES -->
			<div id='tab_AlbumRules_content'{$globalAlbumOnly}>
	 			<table class='ipsTable double_pad'>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['album_manage_rules_t_title']}</strong></td>
						<td class='field_field'>{$form['album_g_rules__title']}</td>
					</tr>
					<tr>
						<td class='field_title'><strong class='title'>{$this->lang->words['album_manage_rules_t_text']}</strong></td>
						<td class='field_field'>{$form['album_g_rules__desc']}</td>
					</tr>
				</table>
			</div>
			
			<script type='text/javascript'>
				jQ("#tabstrip_albumform").ipsTabBar({ tabWrap: "#tabstrip_albumform_content" });
			</script>
		</div>
		
		<div class='acp-actionbar'>
			<input type='submit' name='submit' value='{$form['button']}' class='button primary' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

}