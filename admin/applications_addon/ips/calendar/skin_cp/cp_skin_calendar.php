<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Calendar skin file
 * Last Updated: $Date: 2011-09-01 08:16:37 -0400 (Thu, 01 Sep 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Calendar
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 9439 $
 */
 
class cp_skin_calendar
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
 * Form to add/edit a calendar
 *
 * @access	public
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		Calendar data
 * @return	string		HTML
 */
public function calendarForm($form, $title, $formcode, $button, $calendar) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['c_title']}</h2>
</div>

<form id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$formcode}&amp;cal_id={$calendar['cal_id']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	
	<div class='acp-box alternate_rows'>
		<h3>{$title}</h3>
		<div id='tabstrip_calendar' class='ipsTabBar'>
			<ul>
				<li id='tab_options'>{$this->lang->words['a_options']}</li>
				<li id='tab_perms'>{$this->lang->words['tab_permissions']}</li>
			</ul>
		</div>
		
		<div id='tabContent' class='ipsTabBar_content'>
		
			<div id='tab_options_content'>
				<table class='ipsTable double_pad'>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['c_block_title']}</strong>
						</td>
						<td class='field_field'>{$form['cal_title']}</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['c_block_mod']}</strong>
						</td>
						<td class='field_field'>{$form['cal_moderate']}<br /><span class='desctext'>{$this->lang->words['c_block_mod_info']}</span></td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['c_block_modc']}</strong>
						</td>
						<td class='field_field'>{$form['cal_moderatec']}<br /><span class='desctext'>{$this->lang->words['c_block_modc_info']}</span></td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['c_block_rsvp']}</strong>
						</td>
						<td class='field_field'>{$form['cal_rsvp']}<br /><span class='desctext'>{$this->lang->words['c_block_rsvp_info']}</span></td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['c_block_limit']}</strong>
						</td>
						<td class='field_field'>{$form['cal_event_limit']}<br /><span class='desctext'>{$this->lang->words['c_block_limit_info']}</span></td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['c_block_bday']}</strong>
						</td>
						<td class='field_field'>{$form['cal_bday_limit']}<br /><span class='desctext'>{$this->lang->words['c_block_bday_info']}</span></td>
					</tr>
					
					<tr>
						<th colspan='2'>{$this->lang->words['c_block_rss']}</th>
					</tr>
					
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['c_block_enable']}</strong>
						</td>
						<td class='field_field'>{$form['cal_rss_export']}<br /><span class='desctext'>{$this->lang->words['c_block_enabled_info']}</span></td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['c_block_forthcoming']}</strong>
						</td>
						<td class='field_field'>{$form['cal_rss_export_days']}</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['c_block_max']}</strong>
						</td>
						<td class='field_field'>{$form['cal_rss_export_max']}<br /><span class='desctext'>{$this->lang->words['c_block_max_info']}</span></td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['c_block_freq']}</strong>
						</td>
						<td class='field_field'>{$form['cal_rss_update']}<br /><span class='desctext'>{$this->lang->words['c_block_freq_info']}</span></td>
					</tr>
				</table>
		 	</div>
		 	<div id='tab_perms_content'>
	 			{$form['perm_matrix']}
	 		</div>
		</div>
		<div class='acp-actionbar'>
			<input type='submit' class='realbutton' value='{$button}' />
		</div>
	</div>
</form>

<script type='text/javascript'>
	jQ("#tabstrip_calendar").ipsTabBar({tabWrap: "#tabContent", defaultTab: 'tab_options'});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Calendar overview screen
 *
 * @access	public
 * @param	array 		Calendars
 * @return	string		HTML
 */
public function calendarOverviewScreen( $rows ) {

$IPBHTML = "";

//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['c_title']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=calendar_add' title='{$this->lang->words['c_addcal']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' />
					{$this->lang->words['c_addcal']}
				</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=calendar_rebuildcache' title='{$this->lang->words['c_recachecal']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='' />
					{$this->lang->words['c_recachecal']}
				</a>
			</li>
		</ul>
	</div>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['c_calendars']}</h3>
	<ul class='ipsDragParent alternate_rows' id='sortable_handle'>
HTML;

foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
		<li class='isDraggable' id='calendar_{$r['cal_id']}'>
			<table class='ipsTable double_pad'>
				<tr class='ipsControlRow'>
					<td class='col_drag'><div class='draghandle'>&nbsp;</div></td>
					<td>
						<strong class='title'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=calendar_edit&amp;cal_id={$r['cal_id']}'>{$r['cal_title']}</a></strong>
					</td>
					<td class='col_buttons' nowrap="true">
						<ul class='ipsControlStrip'>
							<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=calendar_edit&amp;cal_id={$r['cal_id']}'>{$this->lang->words['c_editcal']}</a></li>
							<li class='i_refresh'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=calendar_rss_cache&amp;cal_id={$r['cal_id']}'>{$this->lang->words['c_rebuildcal']}</a></li>
							<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=calendar_delete&amp;cal_id={$r['cal_id']}");'>{$this->lang->words['c_deletecal']}</a></li>
						</ul>
					</td>
				</tr>
			</table>
		</li>
HTML;
}

$IPBHTML .= <<<HTML
	</ul>
</div>
<script type='text/javascript'>

dropItLikeItsHot = function( draggableObject, mouseObject )
{
	var options = {
					method : 'post',
					parameters : Sortable.serialize( 'sortable_handle', { tag: 'li', name: 'calendars' } )
				};
 
	new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=calendar_move&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

	return false;
};

Sortable.create( 'sortable_handle', { revert: true, format: 'calendar_([0-9]+)', onUpdate: dropItLikeItsHot, handle: 'draghandle' } );
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Confirm feed deletion and how to handle events
 *
 * @access	public
 * @param	array 		Feed data
 * @return	string		HTML
 */
public function deleteConfirm( $feed ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['feed_del_confirm_t']}</h2>
</div>

<form id='adminform' action='{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;id={$feed['feed_id']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	<input type='hidden' name='confirm' value='1' />
	
	<div class='acp-box'>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['delete_events_too']}</strong>
				</td>
				<td class='field_field'>{$this->registry->output->formYesNo( 'delete_events' , 0 )}</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' class='realbutton' value='{$this->lang->words['do_del_button']}' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit an ical feed
 *
 * @access	public
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		Feed data
 * @return	string		HTML
 */
public function feedForm($form, $title, $formcode, $button, $feed) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['ical_manager']}</h2>
</div>


<form id='adminform' action='{$this->settings['base_url']}{$this->form_code}&amp;do={$formcode}&amp;id={$feed['feed_id']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$title}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['feed_formtitle']}</strong>
				</td>
				<td class='field_field'>{$form['feed_title']}</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['feed_formurl']}</strong>
				</td>
				<td class='field_field'>{$form['feed_url']}<br /><span class='desctext'>{$this->lang->words['feed_urlinfo']}</span></td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['feed_calid']}</strong>
				</td>
				<td class='field_field'>{$form['feed_calendar_id']}<br /><span class='desctext'>{$this->lang->words['feed_calinfo']}</span></td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['feed_formmem']}</strong>
				</td>
				<td class='field_field'>{$form['feed_member_id']}<br /><span class='desctext'>{$this->lang->words['feed_formmemi']}</span></td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['feed_formfreq']}</strong>
				</td>
				<td class='field_field'>{$form['feed_recache_freq']}<br /><span class='desctext'>{$this->lang->words['feed_freqinfo']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' class='realbutton' value='{$button}' />
		</div>
	</div>
</form>
<script type='text/javascript'>
document.observe("dom:loaded", function(){
	var autocomplete = new ipb.Autocomplete( $('feed_member_id'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * List of feeds
 *
 * @access	public
 * @param	array 		Feeds
 * @param	array 		Calendars
 * @return	string		HTML
 */
public function feedsList( $feeds, $calendars ) {

$IPBHTML = "";

//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['c_title']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' title='{$this->lang->words['feed_addlink']}'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' />
				{$this->lang->words['feed_addlink']}
			</a>
			</li>
		</ul>
	</div>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['feeds_for_import']}</h3>
	<table class="ipsTable">
HTML;

if( count($feeds) )
{
	foreach( $feeds as $r )
	{
	$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td>
				<span class='larger_text'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;id={$r['feed_id']}'>{$r['feed_title']}</a></span>
			</td>
			<td class='col_buttons' nowrap="true">
				<ul class='ipsControlStrip'>
					<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;id={$r['feed_id']}'>{$this->lang->words['feed_editlink']}</a></li>
					<li class='i_refresh'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=recache&amp;id={$r['feed_id']}'>{$this->lang->words['feed_recachelink']}</a></li>
					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;id={$r['feed_id']}");'>{$this->lang->words['feed_deletelink']}</a></li>
				</ul>
			</td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='2' class='no_messages'>
				{$this->lang->words['no_feeds_yet']} <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' class='mini_button'>{$this->lang->words['addfirstfeed']}</a>
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=import' method='post' enctype='multipart/form-data'>
<div class="acp-box">
	<h3>{$this->lang->words['feed_ics_import']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['upload_ics_feed']}</strong>
			</td>
			<td class='field_field'><input type='file' name='FILE_UPLOAD' /><br /><span class='desctext'>{$this->lang->words['upload_ics_info']}</span></td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['upload_ics_cal']}</strong>
			</td>
			<td class='field_field'>
				<select name='calendar_id' class='dropdown'>
HTML;

				foreach( $calendars as $calendar )
				{
					$IPBHTML .= "<option value='{$calendar['cal_id']}'>{$calendar['cal_title']}</option>";
				}

$IPBHTML .= <<<HTML
				</select>
				<br /><span class='desctext'>{$this->lang->words['upload_ics_calinfo']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['upload_ics_user']}</strong>
			</td>
			<td class='field_field'><input type='text' autocomplete='off' name='member_name' id='feed_member_name' class='textinput' size='30' value='{$this->memberData['members_display_name']}' /><br /><span class='desctext'>{$this->lang->words['upload_ics_useri']}</span></td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['feed_ics_upload']}' class="button primary" />
	</div>
</div>
</form>
<script type='text/javascript'>
document.observe("dom:loaded", function(){
	var autocomplete = new ipb.Autocomplete( $('feed_member_name'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}


}