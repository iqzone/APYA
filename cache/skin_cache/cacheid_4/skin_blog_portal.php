<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD 3         */
/* CACHE FILE: Skin set id: 4               */
/* CACHE FILE: Generated: Wed, 29 Aug 2012 14:15:07 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_blog_portal_4 extends skinMaster{

/**
* Construct
*/
function __construct( ipsRegistry $registry )
{
	parent::__construct( $registry );
	

$this->_funcHooks = array();
$this->_funcHooks['blogFollowedWrapper'] = array('followedResults','hasResults');
$this->_funcHooks['commentSearchResult'] = array('isUnread','isDraft','isEntryAuthor','newdata','submitterIsMemberStart','submitterIsMemberEnd','catuserid');
$this->_funcHooks['entrySearchResult'] = array('filerate','ratingLoop','isUnread','isDraft','isEntryAuthor','newdata','blogRatingEnabled','isFollowedStuff','oneCommentLang','submitterIsMemberStart','submitterIsMemberEnd','catuserid','isFollowedStuff');
$this->_funcHooks['followedContentBlogs'] = array('filerate','ratingLoop','isUnread','isEntryAuthor','newdata','blogRatingEnabled','hasDrafts','isFollowedStuff','submitterIsMemberStart','submitterIsMemberEnd','hasEntries','isFollowedStuff','followedResults');
$this->_funcHooks['moderatorPanel'] = array('hasPaginationTop','hasResults','hasPaginationBottom');
$this->_funcHooks['recentEntries'] = array('newcomment','recentLoop','anyrecents');
$this->_funcHooks['unapprovedComments'] = array('postMid','postMember','post_data','hasPaginationTop','hasPosts','hasPaginationBottom');


}

/* -- bbCodeEntry --*/
function bbCodeEntry($entry) {
$IPBHTML = "";
$IPBHTML .= "<div class='_sharedMediaBbcode'>
	<div class='bbcode_mediaWrap clearfix'>
		<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;blogid={$entry['blog_id']}&amp;showentry={$entry['entry_id']}", "public",'' ), "{$entry['entry_name_seo']}", "showentry" ) . "\"><img src='{$this->settings['img_url']}/sharedmedia/entries.png' alt='{$this->lang->words['entry_sharedimg']}' class='sharedmedia_image' /></a>
		<div class='details'>
			<h5><a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;blogid={$entry['blog_id']}&amp;showentry={$entry['entry_id']}", "public",'' ), "{$entry['entry_name_seo']}", "showentry" ) . "\">" . IPSText::truncate( $entry['entry_name'], 60 ) . "</a></h5>
			<div><a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$entry['blog_id']}", "public",'' ), "{$entry['blog_seo_name']}", "showblog" ) . "\">{$this->lang->words['sharedmedia_blogprefix']} {$entry['blog_name']}</a></div>
			<div>{$this->lang->words['sharedmedia_elu']} " . $this->registry->getClass('class_localization')->getDate($entry['entry_last_update'],"short", 0) . "</div>
		</div>
	</div>
</div>
<br />";
return $IPBHTML;
}

/* -- blogFollowedWrapper --*/
function blogFollowedWrapper($rows) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_portal', $this->_funcHooks['blogFollowedWrapper'] ) )
{
$count_3db96114efe7545b012d04c8740a949d = is_array($this->functionData['blogFollowedWrapper']) ? count($this->functionData['blogFollowedWrapper']) : 0;
$this->functionData['blogFollowedWrapper'][$count_3db96114efe7545b012d04c8740a949d]['rows'] = $rows;
}
$IPBHTML .= "" . ((is_array($rows) && count($rows)) ? ("
	<table class='ipb_table topic_list' id='forum_table'>
		".$this->__f__a3396926375a7da49a6edf62f91f6f92($rows)."	</table>
") : ("")) . "";
return $IPBHTML;
}


function __f__a3396926375a7da49a6edf62f91f6f92($rows)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $rows as $row )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
			{$row['html']}
		
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- commentSearchResult --*/
function commentSearchResult($r, $resultAsTitle=false) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_portal', $this->_funcHooks['commentSearchResult'] ) )
{
$count_f6bc98ce7d4b22c22568b395041c3d35 = is_array($this->functionData['commentSearchResult']) ? count($this->functionData['commentSearchResult']) : 0;
$this->functionData['commentSearchResult'][$count_f6bc98ce7d4b22c22568b395041c3d35]['r'] = $r;
$this->functionData['commentSearchResult'][$count_f6bc98ce7d4b22c22568b395041c3d35]['resultAsTitle'] = $resultAsTitle;
}
$IPBHTML .= "<tr class='" . (($r['newpost']) ? (" unread") : ("")) . "" . (($r['entry_status'] != 'published') ? (" moderated") : ("")) . "'>
	<td class='col_f_icon short altrow'>
		" . (($r['newpost']) ? ("<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$r['blog_id']}&amp;showentry={$r['entry_id']}&amp;show=newcomment", "public",'' ), "{$r['entry_name_seo']}", "showentry" ) . "\" title='{$this->lang->words['view_newest_comment']}'>
				" . (($this->memberData['member_id'] && $this->memberData['member_id'] == $r['member_id']) ? ("
					" . $this->registry->getClass('output')->getReplacement("t_unread_dot") . "
				") : ("
					" . $this->registry->getClass('output')->getReplacement("t_unread") . "
				")) . "
			</a>") : ("")) . "
	</td>
	<td>
		<h4><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=global&amp;section=comments&amp;do=findComment&amp;comment_id={$r['comment_id']}&amp;parentId={$r['entry_id']}&amp;fromApp=blog-entries", "public",'' ), "", "" ) . "'>{$r['content_title']}</a></h4>
		<span class='desc blend_links'>{$this->lang->words['in']} <a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$r['blog_id']}", "public",'' ), "{$r['blog_seo_name']}", "showblog" ) . "'>{$r['blog_name']}</a></span>
		<div>{$r['content']}</div>
	</td>
	<td class='col_f_post'>
		" . (($r['member_id']) ? ("<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showuser={$r['member_id']}", "public",'' ), "{$r['members_seo_name']}", "showuser" ) . "' class='ipsUserPhotoLink'>") : ("")) . "
			<img src='{$r['pp_small_photo']}' class='ipsUserPhoto ipsUserPhoto_mini left' />
		" . (($r['member_id']) ? ("</a>") : ("")) . "
		<ul class='last_post ipsType_small'>
			<li>" . (($r['member_id']) ? ("" . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($r) : '' ) . "") : ("{$this->lang->words['global_guestname']}")) . "</li>
			<li>" . $this->registry->getClass('class_localization')->getDate($r['comment_date'],"LONG", 0) . "</li>
		</ul>
	</td>
</tr>";
return $IPBHTML;
}

/* -- entrySearchResult --*/
function entrySearchResult($r, $resultAsTitle=false) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_portal', $this->_funcHooks['entrySearchResult'] ) )
{
$count_94a42a89117ca7920fd33e8d68e9f47c = is_array($this->functionData['entrySearchResult']) ? count($this->functionData['entrySearchResult']) : 0;
$this->functionData['entrySearchResult'][$count_94a42a89117ca7920fd33e8d68e9f47c]['r'] = $r;
$this->functionData['entrySearchResult'][$count_94a42a89117ca7920fd33e8d68e9f47c]['resultAsTitle'] = $resultAsTitle;
}
$IPBHTML .= "<tr class='" . (($r['newpost']) ? (" unread") : ("")) . "" . (($r['entry_status'] != 'published') ? (" moderated") : ("")) . "'>
	<td class='col_f_icon short altrow'>
		" . (($r['newpost']) ? ("<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$r['blog_id']}&amp;showentry={$r['entry_id']}&amp;show=newcomment", "public",'' ), "{$r['entry_name_seo']}", "showentry" ) . "\" title='{$this->lang->words['view_newest_comment']}'>
				" . (($this->memberData['member_id'] && $this->memberData['member_id'] == $r['member_id']) ? ("
					" . $this->registry->getClass('output')->getReplacement("t_unread_dot") . "
				") : ("
					" . $this->registry->getClass('output')->getReplacement("t_unread") . "
				")) . "
			</a>") : ("")) . "
	</td>
	<td>
		" . (($this->settings['blog_enable_rating']) ? ("
			<div class='right'>
				".$this->__f__0576a6130e0d9a2105112114359055dd($r,$resultAsTitle)."			</div>
		") : ("")) . "
		<h4><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;blogid={$r['blog_id']}&amp;showentry={$r['type_id_2']}", "public",'' ), "{$r['entry_name_seo']}", "showentry" ) . "'>{$r['content_title']}</a></h4>
		<div class='desc blend_links toggle_notify_off'>
			{$this->lang->words['in']} <a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$r['blog_id']}", "public",'' ), "{$r['blog_seo_name']}", "showblog" ) . "'>{$r['blog_name']}</a>
			" . ((!empty( $r['tags']['tags'] )) ? ("
				<img src='{$this->settings['img_url']}/icon_tag.png' /> {$r['tags']['formatted']['truncatedWithLinks']}
			") : ("")) . "
		</div>
		" . ((count($r['_followData'])) ? ("
			" . ( method_exists( $this->registry->getClass('output')->getTemplate('search'), 'followData' ) ? $this->registry->getClass('output')->getTemplate('search')->followData($r['_followData']) : '' ) . "
		") : ("")) . "
	</td>
	<td class='col_f_views'>
		<ul>
			<li>" . $this->registry->getClass('class_localization')->formatNumber( $r['entry_num_comments'] ) . " " . ((intval($r['entry_num_comments']) == 1) ? ("{$this->lang->words['comment_singular_lower']}") : ("{$this->lang->words['comments_fn']}")) . "</li>
			<li class='views desc'>" . $this->registry->getClass('class_localization')->formatNumber( $r['entry_views'] ) . " {$this->lang->words['blog_num_views_prefix']}</li>
		</ul>
	</td>
	<td class='col_f_post'>
		" . (($r['member_id']) ? ("<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showuser={$r['member_id']}", "public",'' ), "{$r['members_seo_name']}", "showuser" ) . "' class='ipsUserPhotoLink'>") : ("")) . "
			<img src='{$r['pp_small_photo']}' class='ipsUserPhoto ipsUserPhoto_mini left' />
		" . (($r['member_id']) ? ("</a>") : ("")) . "
		<ul class='last_post ipsType_small'>
			<li>" . (($r['member_id']) ? ("" . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($r) : '' ) . "") : ("{$this->lang->words['global_guestname']}")) . "</li>
			<li>" . $this->registry->getClass('class_localization')->getDate($r['entry_date'],"LONG", 0) . "</li>
		</ul>
	</td>
	" . ((count($r['_followData'])) ? ("
		<td class='col_f_mod'>
			<input class='input_check checkall toggle_notify_on' type=\"checkbox\" name=\"likes[]\" value=\"{$r['_followData']['like_app']}-{$r['_followData']['like_area']}-{$r['_followData']['like_rel_id']}\" />
		</td>
	") : ("")) . "
</tr>";
return $IPBHTML;
}


function __f__0576a6130e0d9a2105112114359055dd($r, $resultAsTitle=false)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( array(1,2,3,4,5) as $_rating )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
					" . (($r['entry_rating_total'] >= $_rating) ? ("" . $this->registry->getClass('output')->getReplacement("rate_on") . "") : ("" . $this->registry->getClass('output')->getReplacement("rate_off") . "")) . "
				
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- followedContentBlogs --*/
function followedContentBlogs($rows) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_portal', $this->_funcHooks['followedContentBlogs'] ) )
{
$count_26f434eef7c0ba16baf0f191a793113c = is_array($this->functionData['followedContentBlogs']) ? count($this->functionData['followedContentBlogs']) : 0;
$this->functionData['followedContentBlogs'][$count_26f434eef7c0ba16baf0f191a793113c]['rows'] = $rows;
}
$IPBHTML .= "<table class='ipb_table topic_list' id='forum_table'>
	".$this->__f__c22fcca73f716cc30576e61bb4cc24c8($rows)."</table>";
return $IPBHTML;
}


function __f__d2d0bf8e76afff3bacffc3c28c7314fc($rows,$data='')
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( array(1,2,3,4,5) as $_rating )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
							" . (($data['blog_rating_total'] >= $_rating) ? ("" . $this->registry->getClass('output')->getReplacement("rate_on") . "") : ("" . $this->registry->getClass('output')->getReplacement("rate_off") . "")) . "
						
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

function __f__c22fcca73f716cc30576e61bb4cc24c8($rows)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $rows as $data )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
		<tr" . (($data['newpost']) ? (" class='unread'") : ("")) . ">
			<td class='col_f_icon short altrow'>
				" . (($data['newpost']) ? ("<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$data['blog_id']}", "public",'' ), "{$data['blog_seo_name']}", "showblog" ) . "\">
						" . (($this->memberData['member_id'] && $this->memberData['member_id'] == $data['member_id']) ? ("
							" . $this->registry->getClass('output')->getReplacement("t_unread_dot") . "
						") : ("
							" . $this->registry->getClass('output')->getReplacement("t_unread") . "
						")) . "
					</a>") : ("")) . "		
			</td>
			<td valign='top'>
				" . (($this->settings['blog_enable_rating']) ? ("
					<div class='right'>
						".$this->__f__d2d0bf8e76afff3bacffc3c28c7314fc($rows,$data)."					</div>
				") : ("")) . "
				" . (($data['_hasDrafts']) ? ("
					<span class='ipsBadge ipsBadge_red reset_cursor' data-tooltip=\"" . sprintf( ( $data['blog_num_drafts'] == 1 ? $this->lang->words['badge_x_drafts_tooltip_s'] : $this->lang->words['badge_x_drafts_tooltip_p']), $data['blog_num_drafts'] ) . "\">" . sprintf( $this->lang->words['badge_x_drafts'], $data['blog_num_drafts'] ) . "</span>
				") : ("")) . "
				<h4><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$data['blog_id']}", "public",'' ), "{$data['blog_seo_name']}", "showblog" ) . "'>{$data['blog_name']}</a></h4>
				<div class='desc lighter toggle_notify_off'>{$data['blog_desc']}</div>
				" . ((count($data['_followData'])) ? ("
					" . ( method_exists( $this->registry->getClass('output')->getTemplate('search'), 'followData' ) ? $this->registry->getClass('output')->getTemplate('search')->followData($data['_followData']) : '' ) . "
				") : ("")) . "
			</td>
			<td class='col_f_views ipsType_small'>
				<strong>" . $this->registry->getClass('class_localization')->formatNumber( $data['blog_num_entries'] ) . "</strong> {$this->lang->words['entries_fn']}<br />
				<strong>" . $this->registry->getClass('class_localization')->formatNumber( $data['blog_num_comments'] ) . "</strong> {$this->lang->words['blog_num_comments']}<br />
				<strong>" . $this->registry->getClass('class_localization')->formatNumber( $data['blog_num_views'] ) . "</strong> {$this->lang->words['blog_num_views_prefix']}
			</td>
			<td class='col_f_post'>
				" . (($data['member']['member_id']) ? ("<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showuser={$data['member']['member_id']}", "public",'' ), "{$data['member']['members_seo_name']}", "showuser" ) . "' class='ipsUserPhotoLink'>") : ("")) . "
					<img src='{$data['member']['pp_small_photo']}' class='ipsUserPhoto ipsUserPhoto_mini left' />
				" . (($data['member']['member_id']) ? ("</a>") : ("")) . "
				<ul class='last_post ipsType_small'>
					" . (($data['blog_last_entry']) ? ("
						<li>
							<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$data['blog_id']}&amp;showentry={$data['blog_last_entry']}", "public",'' ), "{$data['entry_name_seo']}", "showentry" ) . "\" title='{$this->lang->words['entry_view']}'>
								" . IPSText::truncate( $blog['blog_last_entryname'], 28 ) . "
							</a>
						</li>
						<li>{$this->lang->words['by_ucfirst']} " . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($data['member']) : '' ) . "</li>
						<li class='desc lighter'>" . $this->registry->getClass('class_localization')->getDate($data['blog_last_update'],"DATE", 0) . "</li>
					") : ("
						<li class='desc lighter'><em>{$this->lang->words['profile_noblogentries']}</em></li>
					")) . "
				</ul>
			</td>
			" . ((count($data['_followData'])) ? ("
				<td class='col_f_mod'>
					<input class='input_check checkall toggle_notify_on' type=\"checkbox\" name=\"likes[]\" value=\"{$data['_followData']['like_app']}-{$data['_followData']['like_area']}-{$data['_followData']['like_rel_id']}\" />
				</td>
			") : ("")) . "
		</tr>
	
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- moderatorPanel --*/
function moderatorPanel($results, $pages='') {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_portal', $this->_funcHooks['moderatorPanel'] ) )
{
$count_619efdad7436c15a9a611fcf1113cb90 = is_array($this->functionData['moderatorPanel']) ? count($this->functionData['moderatorPanel']) : 0;
$this->functionData['moderatorPanel'][$count_619efdad7436c15a9a611fcf1113cb90]['results'] = $results;
$this->functionData['moderatorPanel'][$count_619efdad7436c15a9a611fcf1113cb90]['pages'] = $pages;
}
$IPBHTML .= "" . (($pages) ? ("
	<div>{$pages}</div>
") : ("")) . "
<div class='category_block block_wrap'>
	" . ( method_exists( $this->registry->getClass('output')->getTemplate('modcp'), 'subTabLoop' ) ? $this->registry->getClass('output')->getTemplate('modcp')->subTabLoop() : '' ) . "
	
	<div class=\"ipsBox_container\">
		<table id=\"forum_table\" class=\"ipb_table topic_list\">
			" . ((count($results)) ? ("
				".$this->__f__21179ac88b44d5b64e43ab5d3f9ffe1b($results,$pages)."			") : ("
				<tr>
					<td class='no_messages'>{$this->lang->words['no_draft_entries']}</td>
				</tr>
			")) . "
		</table>
	</div>
</div>
" . (($pages) ? ("
	<div>{$pages}</div>
") : ("")) . "";
return $IPBHTML;
}


function __f__21179ac88b44d5b64e43ab5d3f9ffe1b($results, $pages='')
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $results as $result )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
					" . ( method_exists( $this->registry->getClass('output')->getTemplate('blog_portal'), 'entrySearchResult' ) ? $this->registry->getClass('output')->getTemplate('blog_portal')->entrySearchResult($result) : '' ) . "
				
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- profileBlock --*/
function profileBlock($entries) {
$IPBHTML = "";
$IPBHTML .= "<h3 class='maintitle'>{$this->lang->words['portal_blog_entries']}</h3>
<div class='ipsBox'>
	<div class='ipsBox_container'>
		".$this->__f__b251aa2ab904d29d5e051d4a05ea4fef($entries)."	</div>
</div>";
return $IPBHTML;
}


function __f__b251aa2ab904d29d5e051d4a05ea4fef($entries)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $entries as $entry )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
			<div class='post_block no_sidebar'>
				<div class='post_wrap'>
					<h3 class='row2'>
						<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;blogid={$entry['blog_id']}", "public",'' ), "{$entry['blog_seo_name']}", "showblog" ) . "\">{$entry['blog_name']}</a> &gt;
						<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$entry['blog_id']}&amp;showentry={$entry['entry_id']}", "public",'' ), "{$entry['entry_name_seo']}", "showentry" ) . "\">" . IPSText::truncate( $entry['entry_name'], 90 ) . "</a>
					</h3>
					<div class='post_body'>
						<p class='posted_info'>{$this->lang->words['posted']} {$entry['entry_date_short']}</p>
						<div class='post'>
							{$entry['entry_short']}
							" . (($entry['_hasMore']) ? ("
								<br />
								<div class='right'>
									<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$entry['blog_id']}&amp;showentry={$entry['entry_id']}", "public",'' ), "{$entry['entry_name_seo']}", "showentry" ) . "\">
										<span class='ipsButton_secondary'>{$this->lang->words['read_more_go_on']} {$this->lang->words['_rarr']}</span>
									</a>
								</div>
								<br class='clear' />
							") : ("")) . "
						</div>
					</div>
				</div>
				<br />
			</div>
		
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- recentEntries --*/
function recentEntries($entries=array()) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_portal', $this->_funcHooks['recentEntries'] ) )
{
$count_b10599a5abb14f0681be4ab0f62e4872 = is_array($this->functionData['recentEntries']) ? count($this->functionData['recentEntries']) : 0;
$this->functionData['recentEntries'][$count_b10599a5abb14f0681be4ab0f62e4872]['entries'] = $entries;
}
$IPBHTML .= "<div class='ipsSideBlock clearfix'>
	<h3>" . ((isset($this->lang->words['recently_added_entries'])) ? ("{$this->lang->words['recently_added_entries']}") : ("{$this->lang->words['latest_entries_title']}")) . "</h3>
	<ul class='ipsList_withminiphoto'>
		" . ((is_array( $entries ) && count( $entries )) ? ("
			".$this->__f__50a043fc2e21190fca4d17597bb4f515($entries)."		") : ("
			<li class='row2'><div class='ipsPad'>{$this->lang->words['no_entry_matches']}</div></li>
		")) . "
	</ul>
</div>";
return $IPBHTML;
}


function __f__50a043fc2e21190fca4d17597bb4f515($entries=array())
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $entries as $eid => $entry )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
				<li class='clearfix'>
					<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showuser={$entry['member_id']}", "public",'' ), "{$entry['members_seo_name']}", "showuser" ) . "' class='ipsUserPhotoLink left'>
						<img src='{$entry['pp_small_photo']}' class='ipsUserPhoto ipsUserPhoto_mini' />
					</a>
					<div class='list_content'>
						" . (($entry['newpost']) ? ("
							<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$entry['blog_id']}&amp;showentry={$entry['entry_id']}&amp;show=newcomment", "public",'' ), "{$entry['entry_name_seo']}", "showentry" ) . "\">" . $this->registry->getClass('output')->getReplacement("f_newpost") . "</a>&nbsp;
						") : ("")) . "
						<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$entry['blog_id']}&amp;showentry={$entry['entry_id']}", "public",'' ), "{$entry['entry_name_seo']}", "showentry" ) . "\">{$entry['entry_name']}</a>
						<p class='desc ipsType_smaller'>
							<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$entry['blog_id']}", "public",'' ), "{$entry['blog_seo_name']}", "showblog" ) . "'>{$entry['blog_name']}</a> <span class='desc lighter ipsType_smaller'>" . $this->registry->getClass('class_localization')->getDate($entry['entry_date'],"manual{%d %b}", 0) . "</span>
						</p>
					</div>
				</li>
			
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- unapprovedComments --*/
function unapprovedComments($comments, $pages='') {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_portal', $this->_funcHooks['unapprovedComments'] ) )
{
$count_0c97a1ace2f956e3f797c3955dde6d70 = is_array($this->functionData['unapprovedComments']) ? count($this->functionData['unapprovedComments']) : 0;
$this->functionData['unapprovedComments'][$count_0c97a1ace2f956e3f797c3955dde6d70]['comments'] = $comments;
$this->functionData['unapprovedComments'][$count_0c97a1ace2f956e3f797c3955dde6d70]['pages'] = $pages;
}
$IPBHTML .= "" . (($pages) ? ("
	<div>{$pages}</div>
") : ("")) . "
" . ( method_exists( $this->registry->getClass('output')->getTemplate('modcp'), 'subTabLoop' ) ? $this->registry->getClass('output')->getTemplate('modcp')->subTabLoop() : '' ) . "
<div class='clearfix'>
	" . ((is_array( $comments ) AND count( $comments )) ? ("
		<div id='ips_Posts2'>
			".$this->__f__d0254255d6dd0e5a6e71bd4b8d644fe1($comments,$pages)."		</div>
	") : ("
		<div class='no_messages'>
			{$this->lang->words['no_unapproved_posts']}
		</div>
	")) . "
</div>
" . (($pages) ? ("
	<div>{$pages}</div>
") : ("")) . "";
return $IPBHTML;
}


function __f__d0254255d6dd0e5a6e71bd4b8d644fe1($comments, $pages='')
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $comments as $comment )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
				<div class='post_block hentry clear no_sidebar'>
					<div class='post_wrap'>
						" . (($comment['member_id']) ? ("
						<h3 class='row2'>
						") : ("
						<h3 class='guest row2'>
						")) . "
							<img src='{$comment['pp_small_photo']}' class='ipsUserPhoto ipsUserPhoto_tiny' />&nbsp;
							" . (($comment['member_id']) ? ("
								<span class=\"author vcard\">" . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($comment) : '' ) . "</span>
							") : ("
								{$comment['members_display_name']}
							")) . "
						</h3>
						
						<div class='post_body'>
							<ul class='ipsList_inline modcp_post_controls'>
								<li class='post_edit'>
									<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&module=global&section=comments&do=showEdit&comment_id={$comment['comment_id']}&parentId={$comment['entry_id']}&fromApp=blog-entries&modcp=blogcomments", "public",'' ), "", "" ) . "' title='{$this->lang->words['post_edit_title']}' class='ipsButton_secondary ipsType_smaller' id='edit_post_{$post['post']['pid']}'>{$this->lang->words['post_edit']}</a>
								</li>
								<li>
									<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=global&amp;section=comments&amp;do=approve&amp;comment_id={$comment['comment_id']}&amp;parentId={$comment['entry_id']}&amp;fromApp=blog-entries&amp;modcp=blogcomments&amp;auth_key={$this->member->form_hash}", "public",'' ), "", "" ) . "' title='{$this->lang->words['post_toggle_visible']}' class='ipsButton_secondary ipsType_smaller'>{$this->lang->words['post_approve']}</span></a>
								</li>
								<li class='post_del'>
									<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=global&amp;section=comments&amp;do=delete&amp;comment_id={$comment['comment_id']}&amp;parentId={$comment['entry_id']}&amp;fromApp=blog-entries&amp;modcp=blogcomments&amp;auth_key={$this->member->form_hash}", "public",'' ), "", "" ) . "' title='{$this->lang->words['post_delete_title']}' class='delete_post ipsButton_secondary important ipsType_smaller'>{$this->lang->words['post_delete']}</a>
								</li>
								<li class='desc'>
									<strong>{$this->lang->words['posted']}</strong> <span class='desc lighter'>" . $this->registry->getClass('class_localization')->getDate($comment['comment_date'],"short", 0) . "</span>
								</li>
								<li class='desc'>
									<strong>{$this->lang->words['in_blog_entry']}</strong> <a class='desc lighter' href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$comment['blog_id']}&amp;showentry={$comment['entry_id']}", "public",'' ), "{$comment['entry_name_seo']}", "" ) . "'>" . IPSText::truncate( $comment['entry_name'], 35 ) . "</a>
								</li>
							</ul>
							<div class='post entry-content'>
								{$comment['comment_text']}
							</div>
							<br />
						</div>
					</div>
				</div>
			
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