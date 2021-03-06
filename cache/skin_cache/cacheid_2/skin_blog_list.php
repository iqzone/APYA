<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD 3         */
/* CACHE FILE: Skin set id: 2               */
/* CACHE FILE: Generated: Wed, 29 Aug 2012 14:14:59 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_blog_list_2 extends skinMaster{

/**
* Construct
*/
function __construct( ipsRegistry $registry )
{
	parent::__construct( $registry );
	

$this->_funcHooks = array();
$this->_funcHooks['blog_rating'] = array('ratingishigher','ratingimages');
$this->_funcHooks['blogFeaturedEntry'] = array('featured');
$this->_funcHooks['blogIndexPage'] = array('featuredloop','tablepinnedblogloop','tablenormalblogloop','featured','tablepinnedblogs','tablenormalblogs','noblogs','blogowner');
$this->_funcHooks['blogListRow'] = array('viewdraftcss','isdraft','isfeatured','isrssimport','viewprivate','canbanish','selected','ismod','entryFooterMod','hasQueuedComments','islocked');
$this->_funcHooks['blogTableRow'] = array('isGroupBlog','isDisabledBadge','blogIsPrivate','isntGroupBlog','isGroupBlog');
$this->_funcHooks['generateBlogIcon'] = array('gotoNewCommentsStart','isBlogOwnerUnread','isNotBlogOwnerUnread','isBlogOwner','isDisabled','gotoNewCommentsEnd');


}

/* -- blog_rating --*/
function blog_rating($number) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_list', $this->_funcHooks['blog_rating'] ) )
{
$count_76a8f9f9a1cdf4c8066b0e77c7c10a21 = is_array($this->functionData['blog_rating']) ? count($this->functionData['blog_rating']) : 0;
$this->functionData['blog_rating'][$count_76a8f9f9a1cdf4c8066b0e77c7c10a21]['number'] = $number;
}
$IPBHTML .= "".$this->__f__e0c3fd1390007aacef8ee5d09f1fe873($number)."";
return $IPBHTML;
}


function __f__e0c3fd1390007aacef8ee5d09f1fe873($number)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( array(1,2,3,4,5) as $int )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
	" . (($number >= $int) ? ("
		" . $this->registry->getClass('output')->getReplacement("mini_rate_on") . "
	") : ("
		" . $this->registry->getClass('output')->getReplacement("mini_rate_off") . "
	")) . "

";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- blogAjaxSidebar --*/
function blogAjaxSidebar($blogs) {
$IPBHTML = "";
$IPBHTML .= "<!--no data in this master skin-->";
return $IPBHTML;
}

/* -- blogAjaxSidebarREntries --*/
function blogAjaxSidebarREntries($entries) {
$IPBHTML = "";
$IPBHTML .= "<!--no data in this master skin-->";
return $IPBHTML;
}

/* -- blogFeaturedEntry --*/
function blogFeaturedEntry($featured=array()) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_list', $this->_funcHooks['blogFeaturedEntry'] ) )
{
$count_c358d7db6f6bceca79f2d391894ac1ec = is_array($this->functionData['blogFeaturedEntry']) ? count($this->functionData['blogFeaturedEntry']) : 0;
$this->functionData['blogFeaturedEntry'][$count_c358d7db6f6bceca79f2d391894ac1ec]['featured'] = $featured;
}
$IPBHTML .= "" . ((is_array($featured) AND count($featured)) ? ("
	<link rel=\"stylesheet\" type=\"text/css\" title='Main' media='screen' href='{$this->settings['public_dir']}style_css/{$this->registry->output->skin['_csscacheid']}/ipblog.css' />
	<div class='ipsBox'>
		<div class='ipsBox_container' id='entry_data'>
			" . ( method_exists( $this->registry->getClass('output')->getTemplate('blog_list'), 'blogListRow' ) ? $this->registry->getClass('output')->getTemplate('blog_list')->blogListRow($featured, TRUE) : '' ) . "
		</div>
	</div>
	<br class='clear' />
") : ("")) . "";
return $IPBHTML;
}

/* -- blogIndexPage --*/
function blogIndexPage($pages, $featured=array(), $blogs=array(), $extra='', $type='dash', $sorting='') {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_list', $this->_funcHooks['blogIndexPage'] ) )
{
$count_edb8b2ad01250af418b7845fc6baf116 = is_array($this->functionData['blogIndexPage']) ? count($this->functionData['blogIndexPage']) : 0;
$this->functionData['blogIndexPage'][$count_edb8b2ad01250af418b7845fc6baf116]['pages'] = $pages;
$this->functionData['blogIndexPage'][$count_edb8b2ad01250af418b7845fc6baf116]['featured'] = $featured;
$this->functionData['blogIndexPage'][$count_edb8b2ad01250af418b7845fc6baf116]['blogs'] = $blogs;
$this->functionData['blogIndexPage'][$count_edb8b2ad01250af418b7845fc6baf116]['extra'] = $extra;
$this->functionData['blogIndexPage'][$count_edb8b2ad01250af418b7845fc6baf116]['type'] = $type;
$this->functionData['blogIndexPage'][$count_edb8b2ad01250af418b7845fc6baf116]['sorting'] = $sorting;
}
$IPBHTML .= "<div class='master_list'>
	<h2>{$this->lang->words['blist_blogs']}</h2>
	
	" . ((!count($blogs) && !count($featured)) ? ("
		<div class='row no_messages'>{$this->lang->words['no_blogs_found']}</div>
	") : ("" . ((is_array($featured) AND count($featured)) ? ("
			<h3>{$this->lang->words['featured_entries']}</h3>
			".$this->__f__5674009af2fec1889d38ba8e74e1a892($pages,$featured,$blogs,$extra,$type,$sorting)."		") : ("")) . "
		" . ((is_array( $blogs['pinned'] ) && count( $blogs['pinned'] )) ? ("
			<h3>{$this->lang->words['bloglist_start_pinned']}</h3>
			".$this->__f__5de85844220dcc8ab71e9ac9ce27648c($pages,$featured,$blogs,$extra,$type,$sorting)."		") : ("")) . "
		" . ((is_array( $blogs['normal'] ) && count( $blogs['normal'] )) ? ("
			<h3>{$this->lang->words['other_blogs']}</h3>
			".$this->__f__fdae303fc1d9d7dab517cf51fd84386d($pages,$featured,$blogs,$extra,$type,$sorting)."		") : ("")) . "")) . "
	
	<div class='controls'>
		{$pages}
		<div class='buttons'>
			" . ((is_array($this->memberData['has_blog']) AND count($this->memberData['has_blog'])) ? ("
				<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=post&amp;section=post&amp;do=showform", "public",'' ), "", "" ) . "' class='button'>{$this->lang->words['add_entry']}</a>
			") : ("")) . "
		</div>
	</div>
</div>";
return $IPBHTML;
}


function __f__5674009af2fec1889d38ba8e74e1a892($pages, $featured=array(), $blogs=array(), $extra='', $type='dash', $sorting='')
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $featured as $fid => $entry )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
				" . ( method_exists( $this->registry->getClass('output')->getTemplate('blog_list'), 'blogTableRow' ) ? $this->registry->getClass('output')->getTemplate('blog_list')->blogTableRow($entry) : '' ) . "
			
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

function __f__5de85844220dcc8ab71e9ac9ce27648c($pages, $featured=array(), $blogs=array(), $extra='', $type='dash', $sorting='')
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $blogs['pinned'] as $pinned )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
				" . ( method_exists( $this->registry->getClass('output')->getTemplate('blog_list'), 'blogTableRow' ) ? $this->registry->getClass('output')->getTemplate('blog_list')->blogTableRow($pinned) : '' ) . "
			
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

function __f__fdae303fc1d9d7dab517cf51fd84386d($pages, $featured=array(), $blogs=array(), $extra='', $type='dash', $sorting='')
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $blogs['normal'] as $normal )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
				" . ( method_exists( $this->registry->getClass('output')->getTemplate('blog_list'), 'blogTableRow' ) ? $this->registry->getClass('output')->getTemplate('blog_list')->blogTableRow($normal) : '' ) . "
			
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- blogListRow --*/
function blogListRow($entry, $outSideBlog=false) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_list', $this->_funcHooks['blogListRow'] ) )
{
$count_9afc5153a9b812255f6b0bbec074a40a = is_array($this->functionData['blogListRow']) ? count($this->functionData['blogListRow']) : 0;
$this->functionData['blogListRow'][$count_9afc5153a9b812255f6b0bbec074a40a]['entry'] = $entry;
$this->functionData['blogListRow'][$count_9afc5153a9b812255f6b0bbec074a40a]['outSideBlog'] = $outSideBlog;
}
$IPBHTML .= "<div class='entry" . (($entry['entry_featured']) ? (" featured") : ("")) . "" . (($entry['entry_status'] != 'published') ? (" moderated") : ("")) . "" . (($entry['hide_private']) ? (" private") : ("")) . "' id='entry_{$entry['entry_id']}'>
	<div class='entry_header'>
		" . (($entry['entry_status'] != 'published') ? ("<span class='ipsBadge ipsBadge_red'>{$this->lang->words['entry_is_a_draft']}</span>") : ("")) . "
		" . (($entry['blog_pinned'] || $entry['entry_featured']) ? ("<span class='ipsBadge ipsBadge_green'>{$this->lang->words['entry_is_featured']}</span>") : ("")) . "
		
		<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showuser={$entry['member_id']}", "public",'' ), "{$entry['members_seo_name']}", "showuser" ) . "' class='ipsUserPhotoLink left ipsPad_half'>
			<img src='{$entry['pp_small_photo']}' alt='{$this->lang->words['photo']}' class='ipsUserPhoto ipsUserPhoto_medium' />
		</a>
		
		<h2 class='ipsType_pagetitle'><a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$entry['blog_id']}&amp;showentry={$entry['entry_id']}", "public",'' ), "{$entry['entry_name_seo']}", "showentry" ) . "\" title='{$this->lang->words['view_entry_alt']}'>{$entry['entry_name']}</a></h2>
		<div class='entry_date desc'>" . $this->registry->getClass('class_localization')->getDate($entry['_entry_date'],"short2", 1) . "</div>
		<br class='clear' />
	</div>
	<div class='entry_author desc ipsPad_half'>
		" . (($entry['entry_rss_import']) ? ("" . $this->registry->getClass('output')->getReplacement("blog_rss_import") . "&nbsp;") : ("")) . "
		{$this->lang->words['posted_by']} <strong>" . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($entry) : '' ) . "</strong>
		{$this->lang->words['in']} <a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$entry['blog_id']}", "public",'' ), "{$entry['blog_seo_name']}", "showblog" ) . "'>{$entry['blog_name']}</a>
	</div>
	<div class='entry_content ipsType_textblock ipsPad'>
		" . (($entry['hide_private']) ? ("
			{$this->lang->words['blog_private_entry']}
			<div class='desc right'><a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$entry['blog_id']}&amp;showentry={$entry['entry_id']}&amp;showprivate=1", "public",'' ), "{$entry['entry_name_seo']}", "showentry" ) . "\">{$this->lang->words['blog_show_privateentry']} {$this->lang->words['_rarr']}</a></div>
		") : ("
			{$entry['entry_short']}
		")) . "
		<br />
	</div>
	<div class='entry_footer general_box clear'>
		<h3 class='ipsType_small'>
			" . (($this->memberData['member_id'] && !$outSideBlog) ? ("<span class='right'>
					" . (($this->memberData['g_is_supmod']) ? ("
						<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$entry['blog_id']}&amp;showentry={$entry['entry_id']}&amp;banish=1&amp;return=home", "public",'' ), "{$entry['entry_name_seo']}", "showentry" ) . "\" title=\"{$this->lang->words['blist_banish']}\">" . $this->registry->getClass('output')->getReplacement("blog_banish") . "</a>
					") : ("")) . "
					" . (($this->_canModerateBlogs) ? ("&nbsp;&nbsp;<input type='checkbox' name='bmod_{$entry['blog_id']}' value='1' class='input_check' " . (($entry['bidon']) ? ("selected='selected'") : ("")) . " /> <label for='bmod_{$entry['blog_id']}' class='hide'>{$this->lang->words['select_for_mod']}</label>") : ("")) . "
				</span>") : ("")) . "
			" . (($entry['entry_queued_comments'] && $entry['_can_approve']) ? ("<span class='ipsBadge ipsBadge_orange' data-tooltip=\"" . sprintf( $this->lang->words['entry_queued_comments'], $entry['entry_queued_comments'] ) . "\">{$this->lang->words['f_queued_badge']}</span>") : ("")) . "
			" . (($entry['entry_locked']) ? ("" . $this->registry->getClass('output')->getReplacement("blog_locked") . "") : ("" . (($entry['entry_num_comments'] AND $entry['entry_last_comment_date'] > $entry['_lastRead']) ? ("" . $this->registry->getClass('output')->getReplacement("blog_comments_new") . "") : ("" . $this->registry->getClass('output')->getReplacement("blog_comments") . "")) . "")) . "
			<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$entry['blog_id']}&amp;showentry={$entry['entry_id']}", "public",'' ), "{$entry['entry_name_seo']}", "showentry" ) . "#commentsStart\">{$entry['entry_num_comments']} {$this->lang->words['entry_comments']}</a>
		</h3>
	</div>
</div>";
return $IPBHTML;
}

/* -- blogPreview --*/
function blogPreview($blog, $entry) {
$IPBHTML = "";
$IPBHTML .= "<!--no data in this master skin-->";
return $IPBHTML;
}

/* -- blogTableRow --*/
function blogTableRow($blog) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_list', $this->_funcHooks['blogTableRow'] ) )
{
$count_b91074c908b68c515b011f365c338b06 = is_array($this->functionData['blogTableRow']) ? count($this->functionData['blogTableRow']) : 0;
$this->functionData['blogTableRow'][$count_b91074c908b68c515b011f365c338b06]['blog'] = $blog;
}
$IPBHTML .= "<div class='row touch-row with_photo'>
	<div class='icon'>
		" . (($blog['_lastAuthor']) ? ("
			<img src='{$blog['_lastAuthor']['pp_mini_photo']}' width='{$blog['_lastAuthor']['pp_mini_width']}' height='{$blog['_lastAuthor']['pp_mini_height']}' class='photo' />
		") : ("
			<img src='{$blog['pp_mini_photo']}' width='{$blog['pp_mini_width']}' height='{$blog['pp_mini_height']}' class='photo' />
		")) . "
	</div>
	
	<div class='rowContent'>
		
		" . (($blog['blog_type'] == 'local') ? ("" . (($blog['entry_featured']) ? ("<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$blog['blog_id']}&amp;showentry={$blog['entry_id']}", "public",'' ), "{$blog['entry_name_seo']}", "showentry" ) . "\" title='{$this->lang->words['entry_view']}' class='title'>{$blog['_entry_name_short']}</a>
				<br />
				<span class='desc'>{$blog['entry_date_short']} &middot; " . $this->registry->getClass('class_localization')->formatNumber( $blog['entry_num_comments'] ) . " {$this->lang->words['comments_fn']}</span>
				<br />
	
				<div class='blog_excerpt'>
					" . IPSText::truncate( $blog['entry_short'], 100 ) . "
				</div>
				
				<span class='desc'>
					<strong>
						{$this->lang->words['blog_in']} <a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$blog['blog_id']}", "public",'' ), "{$blog['blog_seo_name']}", "showblog" ) . "\" title='{$this->lang->words['view_blog']}'>{$blog['blog_name']}</a> {$this->lang->words['by_small']} 
						" . (($blog['blog_groupblog']) ? ("
							{$blog['blog_groupblog_name']}
						") : ("
							" . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($blog) : '' ) . "
						")) . "
					</strong>
				</span>") : ("" . (($blog['blog_disabled']) ? ("
					<span class='ipsBadge ipsBadge_red'>{$this->lang->words['blog_disabled_prefix']}</span>
				") : ("")) . "
				
				" . (($blog['blog_private']) ? ("
					<span class='ipsBadge ipsBadge_red'>{$this->lang->words['cat_private']}</span>
				") : ("")) . "
				<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$blog['blog_id']}", "public",'' ), "{$blog['blog_seo_name']}", "showblog" ) . "\" title='{$this->lang->words['view_blog']}' class='title'>{$blog['blog_name']}</a>
				" . ((!$blog['blog_groupblog']) ? ("
					<span class='desc'>{$this->lang->words['by_small']} {$blog['members_display_name']}</span>
				") : ("")) . "
				<br />
				
				<div class='blog_excerpt'>
					" . $this->registry->getClass('class_localization')->formatNumber( $blog['blog_num_entries'] ) . " {$this->lang->words['entries_fn']} &middot;
					" . $this->registry->getClass('class_localization')->formatNumber( $blog['blog_num_comments'] ) . " {$this->lang->words['comments_fn']} &middot;
					" . $this->registry->getClass('class_localization')->formatNumber( $blog['blog_num_views'] ) . " {$this->lang->words['blog_num_views']}
				</div>
				
				" . (($blog['blog_last_entry']) ? ("
					<span class='desc'>
			 			{$this->lang->words['last_entry_on']}: <a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$blog['blog_id']}&amp;showentry={$blog['blog_last_entry']}", "public",'' ), "{$blog['entry_name_seo']}", "showentry" ) . "\" title='{$this->lang->words['entry_view']}'>{$blog['_entry_name_short']}</a> {$this->lang->words['on']} {$blog['entry_date_short']}
					</span>
				") : ("")) . "")) . "") : ("<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$blog['blog_id']}", "public",'' ), "{$blog['blog_seo_name']}", "showblog" ) . "\" title='{$this->lang->words['view_blog']}' class='title'>{$blog['blog_name']}</a> {$this->lang->words['by_small']} 
			" . (($blog['blog_groupblog']) ? ("
				<img src='{$this->settings['img_url']}/blog/blog_group_small.png' alt='' /> {$blog['blog_groupblog_name']}
			") : ("
				" . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($blog) : '' ) . "
			")) . "
			<br />
			<span class='desc'>{$this->lang->words['blist_redirect_hits']}: " . $this->registry->getClass('class_localization')->formatNumber( $blog['blog_num_exthits'] ) . "</span>")) . "
	</div>
</div>";
return $IPBHTML;
}

/* -- generateBlogIcon --*/
function generateBlogIcon($data) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_list', $this->_funcHooks['generateBlogIcon'] ) )
{
$count_49be087e3382e5befe1046d5e8d688c5 = is_array($this->functionData['generateBlogIcon']) ? count($this->functionData['generateBlogIcon']) : 0;
$this->functionData['generateBlogIcon'][$count_49be087e3382e5befe1046d5e8d688c5]['data'] = $data;
}

$isUnread = ($data['last_read'] < $data['blog_last_update']) ? true : false;
$IPBHTML .= "" . (($isUnread) ? ("
	<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$data['blog_id']}&amp;showentry={$data['blog_last_entry']}&amp;show=newcomment", "public",'' ), "{$data['entry_name_seo']}", "showentry" ) . "' title='{$this->lang->words['view_newest_comment']}'>
") : ("")) . "
" . (($blog['blog_disabled']) ? ("
	<span title=\"{$this->lang->words['blog_global_disabled_title']}\">" . $this->registry->getClass('output')->getReplacement("t_closed") . "</span>
") : ("" . (($this->registry->blogFunctions->ownsBlog( $data )) ? ("" . (($isUnread) ? ("
			" . $this->registry->getClass('output')->getReplacement("t_unread_dot") . "
		") : ("
			" . $this->registry->getClass('output')->getReplacement("t_read_dot") . "
		")) . "") : ("" . (($isUnread) ? ("
			" . $this->registry->getClass('output')->getReplacement("t_unread") . "
		") : ("
			" . $this->registry->getClass('output')->getReplacement("t_read") . "
		")) . "")) . "")) . "
" . (($isUnread) ? ("
	</a>
") : ("")) . "";
return $IPBHTML;
}


}


/*--------------------------------------------------*/
/* END OF FILE                                      */
/*--------------------------------------------------*/

?>