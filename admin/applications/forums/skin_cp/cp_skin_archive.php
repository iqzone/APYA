<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Stats skin functions
 * Last Updated: $LastChangedDate: 2011-05-05 12:03:47 +0100 (Thu, 05 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		14th May 2003
 * @version		$Rev: 8644 $
 */
 
class cp_skin_archive
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
 * Overview screen
 */
public function showRestorePrefs( $restoreData ) {
$IPBHTML = "";

$lang = sprintf( $this->lang->words['restore_prefs_desc'], "<input type='text' name='restoreDays' value='{$this->settings['archive_restore_days']}' class='input_text' size='3' />" );

//--starthtml--//
$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=saveRestorePrefs' id='archiveForm' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['restore_prefs_title']}</h3>
	<div class='pad center'>
		{$lang}
	</div>
	<div class='acp-actionbar'>
		<input type='submit' class='button' value='{$this->lang->words['arch_update']}' />
	</div>
</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Add members pop-up
 */
public function showAddMemberDialog($type) {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['mm_title']}</h3>
	<div class='pad center'>
		<input type='text' id='addName' name='addName' value='' autocomplete='off' style='width:210px;' class='textinput' />
		<br />
		<span class='desctext'>{$this->lang->words['mm_desc_start_typing']}</span>
	</div>
	<div class='acp-actionbar'>
		<input type='submit' class='button' data-clicklaunch="saveAddMemberDialog" data-scope="ACPArchive" data-type="{$type}" value='{$this->lang->words['arch_update']}' />
	</div>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Add forum pop-up
 */
public function showAddForumDialog( $options, $type ) {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['sf_title']}</h3>
	<div class='pad center'>
		<select name='forumIds[]' id='forumIds' class='input_text' size='15' multiple='multiple' style='width:95%'>{$options}</select>
	</div>
	<div class='acp-actionbar'>
		<input type='submit' class='button' data-clicklaunch="saveAddForumDialog" data-scope="ACPArchive" data-type="{$type}" value='{$this->lang->words['arch_update']}' />
	</div>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Overview screen
 */
public function archiveDashboard( $restoreTopics, $restoreCount, $archTopics, $archCount, $possibleCounts, $connexTest ) {
$IPBHTML = "";
//--starthtml--//

$title  = ( $this->settings['archive_on'] ) ? $this->lang->words['archive_title_on'] : $this->lang->words['archive_title_off'];
$button = ( $this->settings['archive_on'] ) ? $this->lang->words['archive_disable'] : $this->lang->words['archive_enable'];
$class  = ( $this->settings['archive_on'] ) ? 'red' : 'green';

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.archive.js'></script>
<div class='section_title'>
	<h2>{$title}
		<div class='ipsActionButton right {$class}'>
			<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toggleArchiving'>
				<img src='{$this->settings['skin_acp_url']}/images/icons/archives.png' alt='' />
				{$button}
			</a>
		</div>
	</h2>
</div>
HTML;

if ( $archCount && $possibleCounts['count'] )
{
	$percent     = round( ( $archCount / $possibleCounts['total'] ) * 100, 2 );
	$restoreText = sprintf( $this->lang->words['topics_to_be_unarchived'], $restoreCount );
	
	$IPBHTML .= <<<HTML
	<div class='acp-box'>
		<div class='header' style='width: {$percent}%; height: 15px !important'>
			&nbsp;
		</div>
	</div>
		<div style='margin-top:-28px; padding-left: 10px;'>
			<strong>{$percent}% {$this->lang->words['archived_single']} ({$archCount}/{$possibleCounts['total']})</strong>
		</div>
	<br /><br />
HTML;
}

if ( $connexTest !== true )
{
	if ( isset( $this->lang->words['connex_error_' . $connexTest ] ) )
	{
		$connexTest = $this->lang->words['connex_error_' . $connexTest ];
	}
	
	$IPBHTML .= <<<HTML
	<div class='warning'>
 <h4>{$this->lang->words['connex_error_title']}</h4>
 {$connexTest}
</div>
<br />
HTML;
}

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$restoreText}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='70%'>{$this->lang->words['tbl_title']}</th>
			<th width='30%'>{$this->lang->words['tbl_last_post']}</th>
		</tr>
HTML;
	
		foreach( $restoreTopics as $id => $data )
		{
			$navHtml       = '';
			$data['title'] = IPSText::truncate( $data['title'], 50 );
			$lastPost      = $this->lang->getDate( $data['topic_last_post'], 'SHORT' );
			
			if ( $data['nav'] )
			{
				$navHtml = $this->buildForumNav( $data['nav'] );
			}
			
			$IPBHTML .= <<<HTML
		<tr>
			<td>{$navHtml}<a href="{$this->settings['public_url']}showtopic={$data['tid']}">{$data['title']}</a></td>
			<td>{$lastPost}</td>
		</tr>
HTML;
		}
		
		$descLang = sprintf( $this->lang->words['includes_manually_flagged'], $this->settings['archive_restore_days'] );
		$IPBHTML .= <<<HTML
		<tr>
			<td colspan='2' class='acp-actionbar desctext'>
				{$descLang} <a href='#' data-clicklaunch="showRestorePrefs" data-scope="ACPArchive" class='mini_button right'>{$this->lang->words['set_unarchive_prefs']}</a>
			</td>
		</tr>
	</table>
</div>
HTML;

	if ( count( $archTopics ) )
	{
		$archTitle = sprintf( $this->lang->words['topics_archived'], count($archTopics), $archCount );
		if ( $archCount > 10 )
		{
			$archTitle .= ' - ' . $this->lang->words['topics_archived_last'];
		}
		
		$IPBHTML .= <<<HTML
<br />
<div class='acp-box'>
	<h3>{$archTitle}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='70%'>{$this->lang->words['tbl_title']}</th>
			<th width='30%'>{$this->lang->words['tbl_last_post']}</th>
		</tr>
HTML;
	
		foreach( $archTopics as $id => $data )
		{
			$navHtml       = '';
			$data['title'] = IPSText::truncate( $data['title'], 50 );
			$lastPost      = $this->lang->getDate( $data['topic_last_post'], 'SHORT' );
			
			if ( $data['nav'] )
			{
				$navHtml = $this->buildForumNav( $data['nav'] );
			}
			
			$IPBHTML .= <<<HTML
		<tr>
			<td>{$navHtml}<a href="{$this->settings['public_url']}showtopic={$data['tid']}">{$data['title']}</a></td>
			<td>{$lastPost}</td>
		</tr>
HTML;
		}
	
		$IPBHTML .= <<<HTML
	</table>
</div>
HTML;
	}

//--endhtml--//
return $IPBHTML;
}

/**
 * Overview screen
 */
public function archiveRules( $json='{}', $textString='' ) {
$IPBHTML = "";
//--starthtml--//

$doArchivePane   = $this->archivePane( 'archive' );
$skipArchivePane = $this->archivePane( 'skip' );

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.archive.js'></script>
<script tyle='text/javascript'>
	ipb.ACPArchive.inSection = 'rules';
	ipb.ACPArchive.ruleData  = $json;
</script>
<div class='section_title'>
	<h2>{$this->lang->words['archive_rules_title']}</h2>
</div>
<div class='information-box' id='archiveCount'>
	{$textString}
</div>
<br />
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=saveRules' id='archiveForm' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['rules_title']}</h3>
	<div id='tabstrip_archive' class='ipsTabBar with_left with_right'>
	    <ul>
			<li id='tab_archive'>{$this->lang->words['rules_tab_archive']}</li>
			<li id='tab_skip'>{$this->lang->words['rules_tab_skip']}</li>
	   </ul>
	</div>
	<div id='tabstrip_archive_content' class='ipsTabBar_content'>
		<div id='tab_archive_content' class='pad row2'>
			<div class='pad row1'>
				{$doArchivePane}
			</div>
		</div>
		<div id='tab_skip_content' class='pad _red'>
			<div class='pad row1'>
				{$skipArchivePane}
			</div>
		</div>
	</div>
	<div class='acp-actionbar'>
		<input type='submit' name='submit' value='{$this->lang->words['rules_save']}' class='button primary' />
	</div>
</div>
<form>
<script type='text/javascript'>
	jQ("#tabstrip_archive").ipsTabBar({tabWrap: "#tabstrip_archive_content", defaultTab: "tab_archive" });
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Overview screen
 */
public function archivePane( $type='archive' ) {
$IPBHTML = "";
//--starthtml--//

if ( $type == 'archive' )
{
	$IPBHTML .= <<<HTML
<div class='acp-box pad row1 rule'>
	{$this->lang->words['rules_topic_is']}
	<select name='{$type}_field_state' id='{$type}_field_state'>
		<option value='-' id='{$type}_field_state_any'>{$this->lang->words['rules_state_any']}</option>
		<option value='open' id='{$type}_field_state_open'>{$this->lang->words['rules_state_open']}</option>
		<option value='closed' id='{$type}_field_state_closed'>{$this->lang->words['rules_state_closed']}</option>
	</select>
</div>
<br />
<div class='acp-box pad row1 rule'>
	{$this->lang->words['rules_topic_is']}
	<select name='{$type}_field_pinned' id='{$type}_field_pinned'>
		<option value='-' id='{$type}_field_pinned_any'>{$this->lang->words['rules_pinned_any']}</option>
		<option value='1' id='{$type}_field_pinned_1'>{$this->lang->words['rules_pinned_pin']}</option>
		<option value='0' id='{$type}_field_pinned_0'>{$this->lang->words['rules_pinned_not']}</option>
	</select>
</div>
<br />
<div class='acp-box pad row1 rule'>
	{$this->lang->words['rules_topic_is']}
	<select name='{$type}_field_approved' id='{$type}_field_approved'>
		<option value='-' id='{$type}_field_approved_any'>{$this->lang->words['rules_approved_any']}</option>
		<option value='1' id='{$type}_field_approved_1'>{$this->lang->words['rules_approved_visible']}</option>
		<option value='0' id='{$type}_field_approved_0'>{$this->lang->words['rules_approved_hidden']}</option>
	</select>
</div>
<br />
<div class='acp-box pad row1 rule'>
	{$this->lang->words['rules_topic_has']}
	<select name='{$type}_field_poll' id='{$type}_field_poll'>
		<option value='-' id='{$type}_field_poll_any'>{$this->lang->words['rules_poll_any']}</option>
		<option value='1' id='{$type}_field_poll_1'>{$this->lang->words['rules_poll_has']}</option>
		<option value='0' id='{$type}_field_poll_0'>{$this->lang->words['rules_poll_not']}</option>
	</select>
</div>
<br />
HTML;
}

$IPBHTML .= <<<HTML
<div class='acp-box pad row1 rule'>
	{$this->lang->words['rules_topic_has']}
	<select name='{$type}_field_post' id='{$type}_field_post'>
		<option value='<' id='{$type}_field_post_less'>{$this->lang->words['rules_less_than']}</option>
		<option value='>' id='{$type}_field_post_more'>{$this->lang->words['rules_more_than']}</option>
	</select>
	<input type='text' class='input_text' name='{$type}_field_post_text' id='{$type}_field_post_text' size='5'>
	{$this->lang->words['rules_posts']}
</div>
<br />
<div class='acp-box pad row1 rule'>
	{$this->lang->words['rules_topic_has']}
	<select name='{$type}_field_view' id='{$type}_field_view'>
		<option value='<' id='{$type}_field_view_less'>{$this->lang->words['rules_less_than']}</option>
		<option value='>' id='{$type}_field_view_more'>{$this->lang->words['rules_more_than']}</option>
	</select>
	<input type='text' class='input_text' name='{$type}_field_view_text' id='{$type}_field_view_text' size='5'>
	{$this->lang->words['rules_views']}
</div>
<br />
<div class='acp-box pad row1 rule'>
	{$this->lang->words['rules_topic_rated']}
	<select name='{$type}_field_rating' id='{$type}_field_rating'>
		<option value='>' id='{$type}_field_rating_more'>{$this->lang->words['rules_more_than']}</option>
		<option value='<' id='{$type}_field_rating_less'>{$this->lang->words['rules_less_than']}</option>
	</select>
	<input type='text' class='input_text' name='{$type}_field_rating_text' id='{$type}_field_rating_text' size='2'> {$this->lang->words['rules_oo_five']}
</div>
HTML;

if ( $type == 'archive' )
{
	$IPBHTML .= <<<HTML
<br />
<div class='acp-box pad row1 rule'>
	{$this->lang->words['rules_topic']}
	<select name='{$type}_field_forum' id='{$type}_field_forum'>
		<option value='+' id='{$type}_field_forum_less'>{$this->lang->words['rules_is_in_forum']}</option>
		<option value='-' id='{$type}_field_forum_more'>{$this->lang->words['rules_is_not_in_forum']}</option>
	</select>
	<input type='button' id='{$type}_addForum' value='{$this->lang->words['rules_forum_button']}' data-clicklaunch="launchAddForumDialog" data-scope="ACPArchive" data-type="{$type}" class='mini_button' />
	<div id='{$type}_forumsGoHere' class='pad' style='display:none'></div>
	<input type='hidden' name='{$type}_field_forum_text' id='{$type}_field_forum_text' />
</div>
<br />
<div class='acp-box pad row1 rule'>
	{$this->lang->words['rules_topic_starter']}
	<select name='{$type}_field_member' id='{$type}_field_member'>
		<option value='+' id='{$type}_field_member_less'>{$this->lang->words['rules_includes']}</option>
		<option value='-' id='{$type}_field_member_more'>{$this->lang->words['rules_does_not_include']}</option>
	</select>
	<input type='button' id='{$type}_addMember' value='{$this->lang->words['rules_members_button']}' data-clicklaunch="launchAddMemberDialog" data-scope="ACPArchive" data-type="{$type}" class='mini_button' />
	<div id='{$type}_membersGoHere' class='pad' style='display:none'></div>
	<input type='hidden' name='{$type}_field_member_text' id='{$type}_field_member_text' />
</div>

HTML;
}
$IPBHTML .= <<<HTML
<br />
<div class='acp-box pad row1 rule'>
	{$this->lang->words['rules_topic_last_post']}
	<select name='{$type}_field_lastpost' id='{$type}_field_lastpost'>
		<option value='<' id='{$type}_field_lastpost_less'>{$this->lang->words['rules_older_than']}</option>
		<option value='>' id='{$type}_field_lastpost_more'>{$this->lang->words['rules_newer_than']}</option>
	</select>
	<input type='text' class='input_text' name='{$type}_field_lastpost_text' id='{$type}_field_lastpost_text' size='5'>
	<select name='{$type}_field_lastpost_unit' id='{$type}_field_lastpost_unit'>
		<option value='d' id='{$type}_field_lastpost_unit_d'>{$this->lang->words['rules_days']}</option>
		<option value='m' id='{$type}_field_lastpost_unit_m'>{$this->lang->words['rules_months']}</option>
		<option value='y' id='{$type}_field_lastpost_unit_y'>{$this->lang->words['rules_years']}</option>
	</select>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Build nav
 */
function buildForumNav( $data )
{
	if ( ! is_array( $data ) )
	{
		return '';
	}
	
	foreach( $data as $nav )
	{
		$navHtml .= '<a href="' . $this->settings['public_url'] . $nav[1] . '" class="desctext">' . $nav[0] . '</a> <span class="desctext">&rarr;</span>&nbsp;';
	}
	
	return $navHtml;
}

}