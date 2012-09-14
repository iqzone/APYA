<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Stats skin functions
 * Last Updated: $LastChangedDate: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		14th May 2003
 * @version		$Rev: 10721 $
 */
 
class cp_skin_stats
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
 * Show spam stats
 *
 * @return	string	HTML
 */
public function spamServiceStats( $title, $rows, $total ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['stats_spam']}</h2>
</div>

<table class='information-box' style='width:20%; background-image: none;' align='right'>
	<tr>
		<td><strong>{$this->lang->words['spam_spam']}</strong></td>
		<td><img src='{$this->settings['skin_acp_url']}/images/bar_left_red.gif' width='4' height='11' align='middle' alt=''><img src='{$this->settings['skin_acp_url']}/images/bar_red.gif' width='32' height='11' align='middle' alt=''><img src='{$this->settings['skin_acp_url']}/images/bar_right_red.gif' width='4' height='11' align='middle' alt=''><br /></td>
	</tr>
	<tr>
		<td><strong>{$this->lang->words['spam_ok']}</strong></td>
		<td><img src='{$this->settings['skin_acp_url']}/images/bar_left.gif' width='4' height='11' align='middle' alt=''><img src='{$this->settings['skin_acp_url']}/images/bar.gif' width='32' height='11' align='middle' alt=''><img src='{$this->settings['skin_acp_url']}/images/bar_right.gif' width='4' height='11' align='middle' alt=''></td>
	</tr>
</table>
<br class='clear'/>
<br class='clear'/>

<div class='acp-box'>
	<h3>{$title}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th width='20%'>{$this->lang->words['stats_date']}</th>
			<th width='70%'>{$this->lang->words['stats_results']}</th>
			<th width='10%'>{$this->lang->words['stats_count']}</th>
		</tr>
HTML;

foreach( $rows as $dateVal => $stats )
{
$IPBHTML .= <<<HTML
		<tr>
			<td width='20%'>{$dateVal}</td>
			<td width='70%'>
				<img src='{$this->settings['skin_acp_url']}/images/bar_left_red.gif' width='4' height='11' align='middle' alt=''><img src='{$this->settings['skin_acp_url']}/images/bar_red.gif' width='{$stats['spam']['_width']}' height='11' align='middle' alt=''><img src='{$this->settings['skin_acp_url']}/images/bar_right_red.gif' width='4' height='11' align='middle' alt=''><br />
				<img src='{$this->settings['skin_acp_url']}/images/bar_left.gif' width='4' height='11' align='middle' alt=''><img src='{$this->settings['skin_acp_url']}/images/bar.gif' width='{$stats['notspam']['_width']}' height='11' align='middle' alt=''><img src='{$this->settings['skin_acp_url']}/images/bar_right.gif' width='4' height='11' align='middle' alt=''>
			</td>
			<td width='10%'><center>{$this->registry->class_localization->formatNumber( $stats['spam']['count'] )}<br />{$this->registry->class_localization->formatNumber( $stats['notspam']['count'] )}</center></td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
		<tr>
			<td width='20%'>&nbsp;</td>
			<td width='70%' align='right'>{$this->lang->words['stats_total']}</td>
			<td width='10%'><center><b>{$this->registry->class_localization->formatNumber( $total )}</b></center></td>
		</tr>
	</table>
</div>
<br />
HTML;

if( count( $rows ) <= 30 AND $total > 0 )
{
$IPBHTML .= <<<HTML
<div class='center'>
	<img src='{$this->settings['base_url']}{$this->form_code}&amp;do=spamGraph&amp;to_month={$this->request['to_month']}&amp;to_day={$this->request['to_day']}&amp;to_year={$this->request['to_year']}h&amp;from_month={$this->request['from_month']}&amp;from_day={$this->request['from_day']}&amp;from_year={$this->request['from_year']}&amp;timescale={$this->request['timescale']}&amp;sortby={$this->request['sortby']}'>
</div>
HTML;
}
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the stats results
 *
 * @param	string	Title
 * @param	array 	Stats rows
 * @param	integer	Total
 * @return	string	HTML
 */
public function statResultsScreen( $title, $rows, $total ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['stats_title_results']}</h2>
</div>

<div class='acp-box'>
	<h3>{$title}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th width='20%'>{$this->lang->words['stats_date']}</th>
			<th width='70%'>{$this->lang->words['stats_results']}</th>
			<th width='10%'>{$this->lang->words['stats_count']}</th>
		</tr>
HTML;

foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
		<tr>
			<td width='20%'>{$r['_name']}</td>
			<td width='70%'>
				<img src='{$this->settings['skin_acp_url']}/images/bar_left.gif' width='4' height='11' align='middle' alt=''><img src='{$this->settings['skin_acp_url']}/images/bar.gif' width='{$r['_width']}' height='11' align='middle' alt=''><img src='{$this->settings['skin_acp_url']}/images/bar_right.gif' width='4' height='11' align='middle' alt=''>				
			</td>
			<td width='10%'><center>{$this->registry->class_localization->formatNumber( $r['result_count'] )}</center></td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
		<tr>
			<td width='20%'>&nbsp;</td>
			<td width='70%' align='right'>{$this->lang->words['stats_total']}</td>
			<td width='10%'><center><b>{$this->registry->class_localization->formatNumber( $total )}</b></center></td>
		</tr>
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the stats main screen
 *
 * @param	string	Type
 * @param	string	Title
 * @param	array 	Form fields
 * @return	string	HTML
 */
public function statMainScreeen( $type, $title, $form ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['stats_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$type}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$title}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['stats_datefrom']}</strong></td>
				<td class='field_field'>{$form['from_month']}&nbsp;&nbsp;{$form['from_day']}&nbsp;&nbsp;{$form['from_year']}</td>
			</tr>
			
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['stats_dateto']}</strong></td>
				<td class='field_field'>{$form['to_month']}&nbsp;&nbsp;{$form['to_day']}&nbsp;&nbsp;{$form['to_year']}</td>
			</tr>
HTML;

//-----------------------------------------
// Time scale is irrelevant to topic views
//-----------------------------------------

if( $type != 'statsShowTopicViews' )
{
	$IPBHTML .= <<<HTML
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['stats_timescale']}</strong></td>
				<td class='field_field'>{$form['timescale']}</td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
			
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['stats_sorting']}</strong></td>
				<td class='field_field'>{$form['sortby']}</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['stats_show']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

}