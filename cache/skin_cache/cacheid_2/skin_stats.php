<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD 3         */
/* CACHE FILE: Skin set id: 2               */
/* CACHE FILE: Generated: Wed, 29 Aug 2012 14:15:00 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_stats_2 extends skinMaster{

/**
* Construct
*/
function __construct( ipsRegistry $registry )
{
	parent::__construct( $registry );
	

$this->_funcHooks = array();
$this->_funcHooks['group_strip'] = array('forums','specificForums','isonline','isFriend','isFriendable','canPm','hasBlog','hasGallery','members','hasLeaders');
$this->_funcHooks['top_posters'] = array('tpIsFriend','tpIsFrindable','tpPm','tpBlog','tpGallery','topposters','hasTopPosters');
$this->_funcHooks['whoPosted'] = array('posterHasMid','whoposted','hasPosters');


}

/* -- group_strip --*/
function group_strip($group="", $members=array()) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_stats', $this->_funcHooks['group_strip'] ) )
{
$count_682a488f95041f1dc599db2584d71a5f = is_array($this->functionData['group_strip']) ? count($this->functionData['group_strip']) : 0;
$this->functionData['group_strip'][$count_682a488f95041f1dc599db2584d71a5f]['group'] = $group;
$this->functionData['group_strip'][$count_682a488f95041f1dc599db2584d71a5f]['members'] = $members;
}

if ( ! isset( $this->registry->templateStriping['staff'] ) ) {
$this->registry->templateStriping['staff'] = array( FALSE, "row1","row2");
}
$IPBHTML .= "<!-- This is looped over once for admins, once for supmods, and then once for forum mods -->
<table class='ipb_table'>
	<caption class='maintitle'>{$group}</caption>
	<tr class='header'>
		<th scope='col' style='width: 3%'>&nbsp;</th>
		<th scope='col' style='width: 25%'>{$this->lang->words['leader_name']}</th>
		<th scope='col' style='width: 35%' class='short'>{$this->lang->words['leader_forums']}</th>
		<th scope='col' style='width: 25%'>{$this->lang->words['leader_location']}</th>
		<th scope='col' style='width: 12%'>&nbsp;</th>
	</tr>
	" . ((count($members) AND is_array($members)) ? ("
				".$this->__f__966a0897ec0fe3627c0eeeb5da3c3190($group,$members)."	") : ("")) . "
</table>
<br />";
return $IPBHTML;
}


function __f__5577c7907c90cbb4502c3922df7c795d($group="", $members=array(),$info='')
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $info['forums'] as $id => $name )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
									<option value=\"{$id}\">{$name}</option>
								
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

function __f__966a0897ec0fe3627c0eeeb5da3c3190($group="", $members=array())
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $members as $info )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
			<tr class='" .  IPSLib::next( $this->registry->templateStriping["staff"] ) . "'>
				<td>
					<img src='{$info['pp_mini_photo']}' alt='photo' class='photo' width='{$info['pp_mini_width']}' height='{$info['pp_mini_height']}' />
				</td>
				<td>
					" . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($info) : '' ) . "
				</td>
				<td class='altrow short'>
					" . ((is_array($info['forums'])) ? ("
						<form method=\"post\" action=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "", "public",'' ), "", "" ) . "\" id=\"jmenu{$info['member_id']}\" class='jump'>
							<select name=\"showforum\">
								<option value=\"-1\">" . sprintf($this->lang->words['no_forums'],count($info['forums'])) . "</option>
								<option value=\"-1\">---------------------------</option>
								".$this->__f__5577c7907c90cbb4502c3922df7c795d($group,$members,$info)."							</select>
							<input type='submit' class='input_submit alt' value='{$this->lang->words['go_button']}' />
						</form>
					") : ("
						{$info['forums']}
					")) . "
				</td>
				<td>
					" . (($info['_online']) ? ("
						{$info['online_extra']}
					") : ("")) . "
				</td>
				<td class='short altrow'>
					<ul class='user_controls clear'>
						" . (($this->memberData['member_id'] AND $this->memberData['member_id'] != $info['member_id'] && $this->settings['friends_enabled'] AND $this->memberData['g_can_add_friends']) ? ("" . ((IPSMember::checkFriendStatus( $info['member_id'] )) ? ("
								<li class='mini_friend_toggle is_friend' id='friend_xxx_{$info['member_id']}'><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=members&amp;module=profile&amp;section=friends&amp;do=remove&amp;member_id={$info['member_id']}&amp;secure_key={$this->member->form_hash}", "public",'' ), "", "" ) . "' title='{$this->lang->words['remove_friend']}'>" . $this->registry->getClass('output')->getReplacement("remove_friend") . "</a></li>
							") : ("
								<li class='mini_friend_toggle is_not_friend' id='friend_xxx_{$info['member_id']}'><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=members&amp;module=profile&amp;section=friends&amp;do=add&amp;member_id={$info['member_id']}&amp;secure_key={$this->member->form_hash}", "public",'' ), "", "" ) . "' title='{$this->lang->words['add_friend']}'>" . $this->registry->getClass('output')->getReplacement("add_friend") . "</a></li>								
							")) . "") : ("")) . "
						" . (($this->memberData['g_use_pm'] AND $this->memberData['member_id'] != $info['member_id'] AND $this->memberData['members_disable_pm'] == 0 AND IPSLib::moduleIsEnabled( 'messaging', 'members' )) ? ("
							<li class='pm_button' id='pm_xxx_{$info['member_id']}'><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=members&amp;module=messaging&amp;section=send&amp;do=form&amp;fromMemberID={$info['member_id']}", "public",'' ), "", "" ) . "' title='{$this->lang->words['pm_member']}'>" . $this->registry->getClass('output')->getReplacement("send_msg") . "</a></li>
						") : ("")) . "
						" . (($info['has_blog'] AND IPSLib::appIsInstalled( 'blog' )) ? ("
							<li><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;mid={$info['member_id']}", "public",'' ), "", "" ) . "' title='{$this->lang->words['view_blog']}'>" . $this->registry->getClass('output')->getReplacement("blog_link") . "</a></li>
						") : ("")) . "
						" . (($info['has_gallery'] AND IPSLib::appIsInstalled( 'gallery' )) ? ("
							<li><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;module=user&amp;section=user&amp;user={$info['member_id']}", "public",'' ), "", "" ) . "' title='{$this->lang->words['view_gallery']}'>" . $this->registry->getClass('output')->getReplacement("gallery_link") . "</a></li>
						") : ("")) . "
					</ul>
				</td>
			</tr>
		
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- top_posters --*/
function top_posters($rows) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_stats', $this->_funcHooks['top_posters'] ) )
{
$count_63becc93f04352af75117a91dd50e1c3 = is_array($this->functionData['top_posters']) ? count($this->functionData['top_posters']) : 0;
$this->functionData['top_posters'][$count_63becc93f04352af75117a91dd50e1c3]['rows'] = $rows;
}

if ( ! isset( $this->registry->templateStriping['top_posters'] ) ) {
$this->registry->templateStriping['top_posters'] = array( FALSE, "row1","row2");
}
$IPBHTML .= "<table class='ipb_table'>
	<caption class='maintitle'>{$this->lang->words['todays_posters']}</caption>
	<tr class='header'>
		<th scope='col' style='width: 3%'>&nbsp;</th>
		<th scope='col'>{$this->lang->words['member']}</th>
		<th scope='col'>{$this->lang->words['member_joined']}</th>
		<th scope='col' class='short'>{$this->lang->words['member_posts']}</th>
		<th scope='col' class='short'>{$this->lang->words['member_today']}</th>
		<th scope='col' class='short'>{$this->lang->words['member_percent']}</th>
		<th scope='col' class='short'>&nbsp;</th>
	</tr>
	" . ((!is_array($rows) OR !count($rows)) ? ("
		<tr>
			<td colspan='7' class='no_messages'>{$this->lang->words['no_info']}</td>
		</tr>
	") : ("
				".$this->__f__7be9bb1e8c8ca80971f98c61b606fe62($rows)."	")) . "
</table>";
return $IPBHTML;
}


function __f__7be9bb1e8c8ca80971f98c61b606fe62($rows)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $rows as $info )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
			<tr class='" .  IPSLib::next( $this->registry->templateStriping["top_posters"] ) . "'>
				<td>
					<img src='{$info['pp_mini_photo']}' alt=\"" . sprintf($this->lang->words['users_photo'],$info['members_display_name']) . "\" class='photo' width='{$info['pp_mini_width']}' height='{$info['pp_mini_height']}' />
				</td>
				<td>
					" . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($info) : '' ) . "
				</td>
				<td class='altrow'>
					" . $this->registry->getClass('class_localization')->getDate($info['joined'],"joined", 0) . "
				</td>
				<td class='short'>
					" . $this->registry->getClass('class_localization')->formatNumber( $info['posts'] ) . "
				</td>
				<td class='altrow short'>
					" . $this->registry->getClass('class_localization')->formatNumber( $info['tpost'] ) . "
				</td>
				<td class='short'>
					{$info['today_pct']}%
				</td>
				<td class='altrow short'>
					<ul class='user_controls clear'>
						" . (($this->memberData['member_id'] AND $this->memberData['member_id'] != $info['member_id'] && $this->settings['friends_enabled'] AND $this->memberData['g_can_add_friends']) ? ("" . ((IPSMember::checkFriendStatus( $info['member_id'] )) ? ("
								<li class='mini_friend_toggle is_friend' id='friend_xxx_{$info['member_id']}'><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=members&amp;module=profile&amp;section=friends&amp;do=remove&amp;member_id={$info['member_id']}&amp;secure_key={$this->member->form_hash}", "public",'' ), "", "" ) . "' title='{$this->lang->words['remove_friend']}'>" . $this->registry->getClass('output')->getReplacement("remove_friend") . "</a></li>
							") : ("
								<li class='mini_friend_toggle is_not_friend' id='friend_xxx_{$info['member_id']}'><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=members&amp;module=profile&amp;section=friends&amp;do=add&amp;member_id={$info['member_id']}&amp;secure_key={$this->member->form_hash}", "public",'' ), "", "" ) . "' title='{$this->lang->words['add_friend']}'>" . $this->registry->getClass('output')->getReplacement("add_friend") . "</a></li>								
							")) . "") : ("")) . "
						" . (($this->memberData['g_use_pm'] AND $this->memberData['member_id'] != $info['member_id'] AND $this->memberData['members_disable_pm'] == 0 AND IPSLib::moduleIsEnabled( 'messaging', 'members' )) ? ("
							<li class='pm_button' id='pm_xxx_{$info['member_id']}'><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=members&amp;module=messaging&amp;section=send&amp;do=form&amp;fromMemberID={$info['member_id']}", "public",'' ), "", "" ) . "' title='{$this->lang->words['pm_member']}'>" . $this->registry->getClass('output')->getReplacement("send_msg") . "</a></li>
						") : ("")) . "
						" . (($info['has_blog'] AND IPSLib::appIsInstalled( 'blog' )) ? ("
							<li><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;mid={$info['member_id']}", "public",'' ), "", "" ) . "' title='{$this->lang->words['view_blog']}'>" . $this->registry->getClass('output')->getReplacement("blog_link") . "</a></li>
						") : ("")) . "
						" . (($info['has_gallery'] AND IPSLib::appIsInstalled( 'gallery' )) ? ("
							<li><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;module=user&amp;section=user&amp;user={$info['member_id']}", "public",'' ), "", "" ) . "' title='{$this->lang->words['view_gallery']}'>" . $this->registry->getClass('output')->getReplacement("gallery_link") . "</a></li>
						") : ("")) . "
					</ul>
				</td>
			</tr>
		
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- whoPosted --*/
function whoPosted($tid=0, $title="", $rows=array()) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_stats', $this->_funcHooks['whoPosted'] ) )
{
$count_a07c356d62b2ef0771dae0815e001b74 = is_array($this->functionData['whoPosted']) ? count($this->functionData['whoPosted']) : 0;
$this->functionData['whoPosted'][$count_a07c356d62b2ef0771dae0815e001b74]['tid'] = $tid;
$this->functionData['whoPosted'][$count_a07c356d62b2ef0771dae0815e001b74]['title'] = $title;
$this->functionData['whoPosted'][$count_a07c356d62b2ef0771dae0815e001b74]['rows'] = $rows;
}

if ( ! isset( $this->registry->templateStriping['whoposted'] ) ) {
$this->registry->templateStriping['whoposted'] = array( FALSE, "row1","row2");
}
$IPBHTML .= "" . (($this->request['module']=='ajax') ? ("
	<h3>{$this->lang->words['who_farted']} {$title}</h3>
") : ("
	<h3 class='maintitle'>{$this->lang->words['who_farted']} {$title}</h3>
")) . "
<table class='ipb_table'>
	<tr class='header'>
		<th>{$this->lang->words['whoposted_name']}</th>
		<th>{$this->lang->words['whoposted_posts']}</th>
	</tr>
	" . ((count($rows) AND is_array($rows)) ? ("
				".$this->__f__26f9b55ad7e5f8e7790438cf8377a396($tid,$title,$rows)."	") : ("")) . "
	</table>";
return $IPBHTML;
}


function __f__26f9b55ad7e5f8e7790438cf8377a396($tid=0, $title="", $rows=array())
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $rows as $row )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
			<tr class='" .  IPSLib::next( $this->registry->templateStriping["whoposted"] ) . "'>
				<td class=\"altrow\">
					" . (($row['author_id']) ? ("
						<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showuser={$row['author_id']}", "public",'' ), "{$row['members_seo_name']}", "showuser" ) . "\" title=\"{$this->lang->words['goto_profile']}\">{$row['author_name']}</a>
					") : ("
						{$row['author_name']}
					")) . "
				</td>
				<td>{$row['pcount']}</td>
			</tr>
		
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}


}


/*--------------------------------------------------*/
/* END OF FILE                                      */
/*--------------------------------------------------*/

?>