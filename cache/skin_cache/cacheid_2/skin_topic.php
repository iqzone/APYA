<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD 3         */
/* CACHE FILE: Skin set id: 2               */
/* CACHE FILE: Generated: Wed, 29 Aug 2012 14:15:00 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_topic_2 extends skinMaster{

/**
* Construct
*/
function __construct( ipsRegistry $registry )
{
	parent::__construct( $registry );
	

$this->_funcHooks = array();
$this->_funcHooks['pollDisplay'] = array('hasVoters','multiVote','showingResults','poll_choices','votedClass','noGuestVote','poll_questions','showPollResults','publicPollNotice','deleteVote','cast','viewVotersLink','alreadyDisplayVotes','displayVotes','youCreatedPoll','voteButtonVoted','voteButtonMid','voteButton');
$this->_funcHooks['post'] = array('postMember','userIgnoredLang','userIgnoredLangTwo','postEditByReason','postEditBy','userIgnored','canEdit','canDelete','deleted','sDeleted');
$this->_funcHooks['quickEditPost'] = array('editReasonQe','editByQe');
$this->_funcHooks['show_attachment_title'] = array('attachType','attach');
$this->_funcHooks['softDeletedPostBit'] = array('postMember','showReason','sdOptions','userIgnoredLang','userIgnoredLangTwo','postEditByReason','postEditBy','userIgnored','canEdit','canDelete');
$this->_funcHooks['topicViewTemplate'] = array('post_data','closedButtonLink','replyButtonLink','replyButton','closedButton','hasPosts','canShare','closedButtonLink','replyButtonLink','replyButton','closedButton','fastReply');


}

/* -- ajax__deletePost --*/
function ajax__deletePost() {
$IPBHTML = "";
$IPBHTML .= "<!--no data in this master skin-->";
return $IPBHTML;
}

/* -- ajax__doDeletePost --*/
function ajax__doDeletePost() {
$IPBHTML = "";
$IPBHTML .= "<!--no data in this master skin-->";
return $IPBHTML;
}

/* -- ajax__restoreTopicDialog --*/
function ajax__restoreTopicDialog() {
$IPBHTML = "";
$IPBHTML .= "<!--no data in this master skin-->";
return $IPBHTML;
}

/* -- ajaxSigCloseMenu --*/
function ajaxSigCloseMenu($post) {
$IPBHTML = "";
$IPBHTML .= "<!--no data in this master skin-->";
return $IPBHTML;
}

/* -- announcement_show --*/
function announcement_show($announce="",$author="") {
$IPBHTML = "";
$IPBHTML .= "<div class='master_list'>
	
	<h2>" . IPSText::truncate( $announce['announce_title'], 45 ) . "</h2>
	<div class='topic_reply'>
		<h2 class='secondary' style='padding-left: 10px;'><a class=\"url fn\" href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showuser={$author['member_id']}", "public",'' ), "{$author['members_seo_name']}", "showuser" ) . "'>{$author['members_display_name']}</a></h2>
		<div class='post'>
			{$announce['announce_post']}
		</div>
	</div>
</div>";
return $IPBHTML;
}

/* -- archiveStatusMessage --*/
function archiveStatusMessage($topic, $forum) {
$IPBHTML = "";
$IPBHTML .= "<!--no data in this master skin-->";
return $IPBHTML;
}

/* -- hookFacebookLike --*/
function hookFacebookLike() {
$IPBHTML = "";
$IPBHTML .= "<!--no data in this master skin-->";
return $IPBHTML;
}

/* -- likeSummary --*/
function likeSummary($data, $relId, $opts) {
$IPBHTML = "";
$IPBHTML .= "<!--no data in this master skin-->";
return $IPBHTML;
}

/* -- likeSummaryContents --*/
function likeSummaryContents($data, $relId, $opts=array()) {
$IPBHTML = "";
$IPBHTML .= "<!--no data in this master skin-->";
return $IPBHTML;
}

/* -- pollDisplay --*/
function pollDisplay($poll, $topicData, $forumData, $pollData, $showResults) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_topic', $this->_funcHooks['pollDisplay'] ) )
{
$count_eefe042cee44484a365307f1413aa11e = is_array($this->functionData['pollDisplay']) ? count($this->functionData['pollDisplay']) : 0;
$this->functionData['pollDisplay'][$count_eefe042cee44484a365307f1413aa11e]['poll'] = $poll;
$this->functionData['pollDisplay'][$count_eefe042cee44484a365307f1413aa11e]['topicData'] = $topicData;
$this->functionData['pollDisplay'][$count_eefe042cee44484a365307f1413aa11e]['forumData'] = $forumData;
$this->functionData['pollDisplay'][$count_eefe042cee44484a365307f1413aa11e]['pollData'] = $pollData;
$this->functionData['pollDisplay'][$count_eefe042cee44484a365307f1413aa11e]['showResults'] = $showResults;
}
$IPBHTML .= "<div class='general_box alt poll' id='poll_{$poll['pid']}'>
	<form action=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=forums&amp;module=extras&amp;section=vote&amp;t={$topicData['tid']}&amp;st={$this->request['st']}&amp;do=add&amp;secure_key={$this->member->form_hash}", "public",'' ), "", "" ) . "\" name='pollForm' method=\"post\">
		<h3>{$this->lang->words['poll']} {$poll['poll_question']}" . (($showResults) ? (" <span class='desc'>({$poll['_totalVotes']} {$this->lang->words['poll_vote_casted']})</span>") : ("")) . "</h3>
		" . (($this->settings['poll_allow_public'] AND $poll['poll_view_voters'] AND ! $showResults) ? ("
			<div class='message unspecified'>{$this->lang->words['poll_public_notice']}</div>
		") : ("")) . "
		".$this->__f__7fbeabf11b3dc943ee8f1bd0ef971cec($poll,$topicData,$forumData,$pollData,$showResults)."		" . (($topicData['state'] != 'closed') ? ("<fieldset class='submit'>
				<legend>{$this->lang->words['poll_vote']}</legend>
				" . (($this->memberData['member_id']) ? ("" . (($poll['_memberVoted']) ? ("" . (($this->settings['poll_allow_vdelete'] OR $this->memberData['g_is_supmod']) ? ("
							<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=forums&amp;module=extras&amp;section=vote&amp;t={$topicData['tid']}&amp;st={$this->request['st']}&amp;do=delete&amp;secure_key={$this->member->form_hash}", "public",'' ), "", "" ) . "' title='{$this->lang->words['poll_delete_vote']}' id='poll_deletevote' class='button secondary'>{$this->lang->words['poll_delete_vote']}</a>
						") : ("
							{$this->lang->words['poll_you_voted']}
						")) . "") : ("" . ((($poll['starter_id'] == $this->memberData['member_id']) and ($this->settings['allow_creator_vote'] != 1)) ? ("
							{$this->lang->words['poll_you_created']}
						") : ("<!-- VOTE Button -->
							" . (($this->request['mode'] != 'show') ? ("
								<input class='button secondary' type=\"submit\" name=\"submit\" value=\"{$this->lang->words['poll_add_vote']}\" title=\"{$this->lang->words['tt_poll_vote']}\" />
							") : ("")) . "
							<!-- SHOW Button -->
							" . (($this->settings['allow_result_view'] == 1) ? ("" . (($this->request['mode'] == 'show') ? ("" . ((! $poll_view_voters) ? ("
										<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showtopic={$topicData['tid']}&amp;st={$this->request['st']}", "public",'' ), "{$topicData['title_seo']}", "showtopic" ) . "' title='{$this->lang->words['tt_poll_svote']}' id='poll_nullvote' class='button secondary'>{$this->lang->words['pl_show_vote']}</a>
									") : ("")) . "") : ("
									<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showtopic={$topicData['tid']}&amp;mode=show&amp;st={$this->request['st']}", "public",'' ), "{$topicData['title_seo']}", "showtopic" ) . "' title='{$this->lang->words['tt_poll_show']}' id='poll_showresults' class='button secondary'>{$this->lang->words['pl_show_results']}</a>
								")) . "") : ("
								<input class='button secondary' type=\"submit\" name=\"nullvote\" value=\"{$this->lang->words['poll_null_vote']}\" title=\"{$this->lang->words['tt_poll_null']}\" />
							")) . "")) . "")) . "") : ("
					{$this->lang->words['poll_no_guests']}
				")) . "
			</fieldset>") : ("")) . "
	</form>
</div>";
return $IPBHTML;
}


function __f__b5d0fe88a6d77389c4e93b5fc865e93e($poll, $topicData, $forumData, $pollData, $showResults,$questionID='',$questionData='')
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $pollData[ $questionID ]['choices'] as $choiceID => $choiceData )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
							" . (($showResults) ? ("<li class='row'>
									<span class='answer'>" . ((is_array( $choiceData['voters'] ) AND in_array( $this->memberData['member_id'], array_keys( $choiceData['voters'] ) )) ? (" " . $this->registry->getClass('output')->getReplacement("your_vote") . " ") : ("")) . "{$choiceData['choice']}</span>
									<span class='votes'> ({$choiceData['votes']} {$this->lang->words['poll_votes']} [{$choiceData['percent']}%])</span>
									<p class='progress_bar topic_poll' title='{$this->lang->words['poll_percent_of_vote']} {$choiceData['percent']}%'>
										<span style='width: {$choiceData['percent']}%'><span>{$this->lang->words['poll_percent_of_vote']} {$choiceData['percent']}%</span></span>
									</p>
								</li>") : ("" . (($choiceData['type'] == 'multi') ? ("
									<li class='row'><input type='checkbox' id='choice_{$questionID}_{$choiceID}' name='choice_{$questionID}_{$choiceID}' value='1' class='input_check' /> <label for='choice_{$questionID}_{$choiceID}'>{$choiceData['choice']}</label></li>
								") : ("
									<li class='row'><input type='radio' name='choice[{$questionID}]' id='choice_{$questionID}_{$choiceID}' class='input_radio' value='{$choiceID}' /> <label for='choice_{$questionID}_{$choiceID}'>{$choiceData['choice']}</label></li>
								")) . "")) . "
						
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

function __f__7fbeabf11b3dc943ee8f1bd0ef971cec($poll, $topicData, $forumData, $pollData, $showResults)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $pollData as $questionID => $questionData )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
			<div class='" . (($showResults) ? (" voted") : ("")) . "'>
				<h2 class='secondary'>{$pollData[ $questionID ]['question']}</h2>
				" . ((! $this->settings['allow_result_view'] AND ! $this->memberData['member_id']) ? ("
					{$this->lang->words['poll_noview_guest']}
				") : ("
					<ol>
						".$this->__f__b5d0fe88a6d77389c4e93b5fc865e93e($poll,$topicData,$forumData,$pollData,$showResults,$questionID,$questionData)."					</ol>
				")) . "
			</div>
		
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- post --*/
function post($post, $displayData, $topic, $forum=array()) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_topic', $this->_funcHooks['post'] ) )
{
$count_e39596ccc430a22d154452e43e95ac7e = is_array($this->functionData['post']) ? count($this->functionData['post']) : 0;
$this->functionData['post'][$count_e39596ccc430a22d154452e43e95ac7e]['post'] = $post;
$this->functionData['post'][$count_e39596ccc430a22d154452e43e95ac7e]['displayData'] = $displayData;
$this->functionData['post'][$count_e39596ccc430a22d154452e43e95ac7e]['topic'] = $topic;
$this->functionData['post'][$count_e39596ccc430a22d154452e43e95ac7e]['forum'] = $forum;
}
$IPBHTML .= "<!--post:{$post['post']['pid']}-->
" . (($post['post']['_isDeleted'] AND $post['post']['_softDeleteSee']) ? ("
	" . ( method_exists( $this->registry->getClass('output')->getTemplate('topic'), 'softDeletedPostBit' ) ? $this->registry->getClass('output')->getTemplate('topic')->softDeletedPostBit($post, $displayData['sdData'], $topic) : '' ) . "
") : ("" . ((!$post['post']['_isDeleted']) ? ("<div class='topic_reply' id='entry{$post['post']['pid']}'>
			<h2 class='secondary'>
				" . ( method_exists( $this->registry->getClass('output')->getTemplate('global_other'), 'repButtons' ) ? $this->registry->getClass('output')->getTemplate('global_other')->repButtons($post['author'], array_merge( array( 'primaryId' => $post['post']['pid'], 'domLikeStripId' => 'like_post_' . $post['post']['pid'], 'domCountId' => 'rep_post_' . $post['post']['pid'], 'app' => 'forums', 'type' => 'pid', 'likeFormatted' => $post['post']['like']['formatted'] ), $post['post'] )) : '' ) . "
				" . (($post['author']['member_id']) ? ("
					<img src='{$post['author']['pp_thumb_photo']}' alt=\"" . sprintf($this->lang->words['users_photo'],$post['author']['members_display_name']) . "\" class='photo' />
				") : ("")) . "
				" . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($post['author']) : '' ) . "
				<span class='subtext'>" . $this->registry->getClass('class_localization')->getDate($post['post']['post_date'],"DATE", 0) . "</span>
			</h2>
			<div id=\"post-{$post['post']['pid']}\" class='post line_spacing'>
				" . ((($post['post']['_repignored'] == 1 || $post['post']['_ignored']) AND $this->request['view_ignored'] != $post['post']['pid']) ? ("<div class='post_ignore'>
						" . (($post['post']['_repignored'] == 1) ? ("{$this->lang->words['post_ignored_rep']}") : ("{$this->lang->words['post_ignored']}")) . " " . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($post['author']) : '' ) . ". <a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showtopic={$post['post']['topic_id']}&amp;st={$this->request['st']}&amp;view_ignored={$post['post']['pid']}", "public",'' ), "{$topic['title_seo']}", "showtopic" ) . "#entry{$post['post']['pid']}' title='{$this->lang->words['ignore_view_post']}' id='unhide_post_{$post['post']['pid']}'>{$this->lang->words['rep_view_anyway']}</a>
						" . (($this->settings['reputation_enabled'] AND $post['post']['_repignored'] == 1) ? ("<div><a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showtopic={$post['post']['topic_id']}&amp;st={$this->request['st']}&amp;rep_filter_set=*&amp;rep_filter=update", "public",'' ), "{$topic['title_seo']}", "showtopic" ) . "\">{$this->lang->words['post_ignore_reset_rep']}</a></div>") : ("")) . "
					</div>") : ("{$post['post']['post']}
					{$post['post']['attachmentHtml']}
					
					" . (($post['post']['edit_by']) ? ("<br />
						<strong>{$post['post']['edit_by']}</strong>
						" . (($post['post']['post_edit_reason'] != '') ? ("
							<br />
							<span class='subtext'>{$this->lang->words['reason_for_edit']}: {$post['post']['post_edit_reason']}</span>
						") : ("")) . "") : ("")) . "")) . "
			</div>
			<div id=\"post-{$post['post']['pid']}-controls\" class='post_controls'>
				<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "module=post&amp;section=post&amp;do=reply_post&amp;f={$this->request['f']}&amp;t={$this->request['t']}&amp;qpid={$post['post']['pid']}", "publicWithApp",'' ), "", "" ) . "\" title=\"{$this->lang->words['tt_reply_to_post']}\">{$this->lang->words['post_reply']}</a>
				" . (($post['post']['_can_edit'] === TRUE) ? ("
					<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "module=post&amp;section=post&amp;do=edit_post&amp;f={$forum['id']}&amp;t={$topic['tid']}&amp;p={$post['post']['pid']}&amp;st={$this->request['st']}", "publicWithApp",'' ), "", "" ) . "' title='{$this->lang->words['post_edit_title']}' class='edit_post' id='edit_post_{$post['post']['pid']}'>{$this->lang->words['post_edit']}</a>
				") : ("")) . "
				" . (($post['post']['_can_delete'] === TRUE) ? ("
					<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "module=moderate&amp;section=moderate&amp;do=04&amp;f={$forum['id']}&amp;t={$topic['tid']}&amp;p={$post['post']['pid']}&amp;st={$this->request['st']}&amp;auth_key={$this->member->form_hash}", "publicWithApp",'' ), "", "" ) . "' title='{$this->lang->words['post_delete_title']}' class='delete_post'>{$this->lang->words['post_delete']}</a>
				") : ("")) . "
			</div>
		</div>") : ("")) . "")) . "";
return $IPBHTML;
}

/* -- quickEditPost --*/
function quickEditPost($post) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_topic', $this->_funcHooks['quickEditPost'] ) )
{
$count_22267926ae1ebaca288eaed1fb9a3a06 = is_array($this->functionData['quickEditPost']) ? count($this->functionData['quickEditPost']) : 0;
$this->functionData['quickEditPost'][$count_22267926ae1ebaca288eaed1fb9a3a06]['post'] = $post;
}
$IPBHTML .= "{$post['post']}
    {$post['attachmentHtml']}
	<br />
	" . (($post['edit_by']) ? ("<p class='edit'>
			{$post['edit_by']}
			" . (($post['post_edit_reason'] != '') ? ("
				<br />
				<span class='reason'>{$this->lang->words['reason_for_edit']}: {$post['post_edit_reason']}</span>
			") : ("")) . "
		</p>") : ("")) . "";
return $IPBHTML;
}

/* -- show_attachment_title --*/
function show_attachment_title($title="",$data="",$type="") {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_topic', $this->_funcHooks['show_attachment_title'] ) )
{
$count_db641612e232daded400ce546983c04c = is_array($this->functionData['show_attachment_title']) ? count($this->functionData['show_attachment_title']) : 0;
$this->functionData['show_attachment_title'][$count_db641612e232daded400ce546983c04c]['title'] = $title;
$this->functionData['show_attachment_title'][$count_db641612e232daded400ce546983c04c]['data'] = $data;
$this->functionData['show_attachment_title'][$count_db641612e232daded400ce546983c04c]['type'] = $type;
}
$IPBHTML .= "<div id='attach_wrap' class='rounded clearfix'>
	<h4>$title</h4>
	<ul>
		".$this->__f__779556ad82babfcdb5026d8588d8f2c7($title,$data,$type)."	</ul>
</div>";
return $IPBHTML;
}


function __f__779556ad82babfcdb5026d8588d8f2c7($title="",$data="",$type="")
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $data as $file )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
			<li class='" . (($type == 'attach') ? ("clear") : ("")) . "'>
				{$file}
			</li>
		
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- Show_attachments --*/
function Show_attachments($data="") {
$IPBHTML = "";
$IPBHTML .= "<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=attach&amp;section=attach&amp;attach_id={$data['attach_id']}", "public",'' ), "", "" ) . "\" title=\"{$this->lang->words['attach_dl']}\"><img src=\"{$this->settings['public_dir']}{$data['mime_image']}\" alt=\"{$this->lang->words['attached_file']}\" /></a>
&nbsp;<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=attach&amp;section=attach&amp;attach_id={$data['attach_id']}", "public",'' ), "", "" ) . "\" title=\"{$this->lang->words['attach_dl']}\">{$data['attach_file']}</a> <span class='desc'><strong>({$data['file_size']})</strong></span>
<br /><span class=\"desc info\">{$this->lang->words['attach_hits']}: {$data['attach_hits']}</span>";
return $IPBHTML;
}

/* -- Show_attachments_img --*/
function Show_attachments_img($data="") {
$IPBHTML = "";
$IPBHTML .= "<a class='resized_img' rel='lightbox[{$data['attach_rel_id']}]' id='ipb-attach-url-{$data['_attach_id']}' href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=attach&amp;section=attach&amp;attach_rel_module={$data['type']}&amp;attach_id={$data['attach_id']}", "public",'' ), "", "" ) . "\" title=\"{$data['location']} - {$this->lang->words['attach_size']} {$data['file_size']}, {$this->lang->words['attach_ahits']} {$data['attach_hits']}\"><img src=\"{$this->settings['upload_url']}/{$data['o_location']}\" class='bbc_img linked-image' alt=\"{$this->lang->words['pic_attach']}: {$data['location']}\" /></a>";
return $IPBHTML;
}

/* -- Show_attachments_img_thumb --*/
function Show_attachments_img_thumb($data=array()) {
$IPBHTML = "";
$IPBHTML .= "<a class='resized_img' rel='lightbox[{$data['attach_rel_id']}]' id='ipb-attach-url-{$data['_attach_id']}' href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=attach&amp;section=attach&amp;attach_rel_module={$data['type']}&amp;attach_id={$data['attach_id']}", "public",'' ), "", "" ) . "\" title=\"{$data['location']} - {$this->lang->words['attach_size']} {$data['file_size']}, {$this->lang->words['attach_ahits']} {$data['attach_hits']}\"><img src=\"{$this->settings['upload_url']}/{$data['t_location']}\" id='ipb-attach-img-{$data['_attach_id']}' class='attach' alt=\"{$this->lang->words['pic_attach']}\" /></a>";
return $IPBHTML;
}

/* -- softDeletedPostBit --*/
function softDeletedPostBit($post, $sdData, $topic) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_topic', $this->_funcHooks['softDeletedPostBit'] ) )
{
$count_53d9e5d27f6db7118270fe6361e0fd3e = is_array($this->functionData['softDeletedPostBit']) ? count($this->functionData['softDeletedPostBit']) : 0;
$this->functionData['softDeletedPostBit'][$count_53d9e5d27f6db7118270fe6361e0fd3e]['post'] = $post;
$this->functionData['softDeletedPostBit'][$count_53d9e5d27f6db7118270fe6361e0fd3e]['sdData'] = $sdData;
$this->functionData['softDeletedPostBit'][$count_53d9e5d27f6db7118270fe6361e0fd3e]['topic'] = $topic;
}

$_sD = $sdData[ $post['post']['pid'] ];
	$_sM = $_sD;
$IPBHTML .= "	<div class='topic_reply moderated' id='entry{$post['post']['pid']}'>
		<h2 class='secondary'>
			" . (($post['author']['member_id']) ? ("
				<img src='{$post['author']['pp_thumb_photo']}' alt=\"" . sprintf($this->lang->words['users_photo'],$post['author']['members_display_name']) . "\" class='photo' />
			") : ("")) . "
			" . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($post['author']) : '' ) . "
			<span class='subtext'>" . $this->registry->getClass('class_localization')->getDate($post['post']['post_date'],"DATE", 0) . "</span>
		</h2>
		<div id='postsDelete-{$post['post']['pid']}'>
			<div class='padding'>
				<strong>{$this->lang->words['post_deleted_by']} <a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showuser={$_sD['sdl_obj_member_id']}", "public",'' ), "{$_sM['members_seo_name']}", "showuser" ) . "'>{$_sM['members_display_name']}</a> {$this->lang->words['on']} " . $this->registry->getClass('class_localization')->getDate($_sD['sdl_obj_date'],"long", 0) . ".</strong>
				" . (($post['post']['_softDeleteReason']) ? ("<p class='desc'>" . (($_sD['sdl_obj_reason']) ? ("{$_sD['sdl_obj_reason']}") : ("{$this->lang->words['no_reason_given']}")) . "</p>") : ("")) . "
				
				" . (($post['post']['_softDeleteContent'] OR $post['post']['_softDeleteRestore']) ? ("" . (($post['post']['_softDeleteContent']) ? ("
						<br />
						<span class='post_toggle sd_content' id='seeContent_{$post['post']['pid']}'>
							<a class='button secondary' href='#'><span>{$this->lang->words['togglepostcontent']}</span></a>
						</span>
					") : ("")) . "") : ("")) . "
			</div>
		</div>
		<div id=\"post-{$post['post']['pid']}\" class='post line_spacing' style='display: none'>
			" . ((($post['post']['_repignored'] == 1 || $post['post']['_ignored']) AND $this->request['view_ignored'] != $post['post']['pid']) ? ("<div class='post_ignore'>
					" . (($post['post']['_repignored'] == 1) ? ("{$this->lang->words['post_ignored_rep']}") : ("{$this->lang->words['post_ignored']}")) . " " . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($post['author']) : '' ) . ". <a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showtopic={$post['post']['topic_id']}&amp;st={$this->request['st']}&amp;view_ignored={$post['post']['pid']}", "public",'' ), "{$topic['title_seo']}", "showtopic" ) . "#entry{$post['post']['pid']}' title='{$this->lang->words['ignore_view_post']}' id='unhide_post_{$post['post']['pid']}'>{$this->lang->words['rep_view_anyway']}</a>
					" . (($this->settings['reputation_enabled'] AND $post['post']['_repignored'] == 1) ? ("<div><a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showtopic={$post['post']['topic_id']}&amp;st={$this->request['st']}&amp;rep_filter_set=*&amp;rep_filter=update", "public",'' ), "{$topic['title_seo']}", "showtopic" ) . "\">{$this->lang->words['post_ignore_reset_rep']}</a></div>") : ("")) . "
				</div>") : ("{$post['post']['post']}
				{$post['post']['attachmentHtml']}
				" . (($post['post']['edit_by']) ? ("<br />
					<strong>{$post['post']['edit_by']}</strong>
					" . (($post['post']['post_edit_reason'] != '') ? ("
						<br />
						<span class='subtext'>{$this->lang->words['reason_for_edit']}: {$post['post']['post_edit_reason']}</span>
					") : ("")) . "") : ("")) . "")) . "
		</div>
		<div id=\"post-{$post['post']['pid']}-controls\" class='post_controls'>
			<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "module=post&amp;section=post&amp;do=reply_post&amp;f={$this->request['f']}&amp;t={$this->request['t']}&amp;qpid={$post['post']['pid']}", "publicWithApp",'' ), "", "" ) . "\" title=\"{$this->lang->words['tt_reply_to_post']}\">" . $this->registry->getClass('output')->getReplacement("reply_post_icon") . " {$this->lang->words['post_reply']}</a>
			" . (($post['post']['_can_edit'] === TRUE) ? ("
				<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "module=post&amp;section=post&amp;do=edit_post&amp;f={$forum['id']}&amp;t={$topic['tid']}&amp;p={$post['post']['pid']}&amp;st={$this->request['st']}", "publicWithApp",'' ), "", "" ) . "' title='{$this->lang->words['post_edit_title']}' class='edit_post' id='edit_post_{$post['post']['pid']}'>" . $this->registry->getClass('output')->getReplacement("edit_post_icon") . " {$this->lang->words['post_edit']}</a>
			") : ("")) . "
			" . (($post['post']['_can_delete'] === TRUE) ? ("
				<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "module=moderate&amp;section=moderate&amp;do=04&amp;f={$forum['id']}&amp;t={$topic['tid']}&amp;p={$post['post']['pid']}&amp;st={$this->request['st']}&amp;auth_key={$this->member->form_hash}", "publicWithApp",'' ), "", "" ) . "' title='{$this->lang->words['post_delete_title']}' class='delete_post'>" . $this->registry->getClass('output')->getReplacement("delete_post_icon") . " {$this->lang->words['post_delete']}</a>
			") : ("")) . "
		</div>
	</div>";
return $IPBHTML;
}

/* -- topicPreview --*/
function topicPreview($topic, $posts) {
$IPBHTML = "";
$IPBHTML .= "<!--no data in this master skin-->";
return $IPBHTML;
}

/* -- topicViewTemplate --*/
function topicViewTemplate($forum, $topic, $post_data, $displayData) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_topic', $this->_funcHooks['topicViewTemplate'] ) )
{
$count_6cad7e2e3c2bbe3c35245c2fd3846963 = is_array($this->functionData['topicViewTemplate']) ? count($this->functionData['topicViewTemplate']) : 0;
$this->functionData['topicViewTemplate'][$count_6cad7e2e3c2bbe3c35245c2fd3846963]['forum'] = $forum;
$this->functionData['topicViewTemplate'][$count_6cad7e2e3c2bbe3c35245c2fd3846963]['topic'] = $topic;
$this->functionData['topicViewTemplate'][$count_6cad7e2e3c2bbe3c35245c2fd3846963]['post_data'] = $post_data;
$this->functionData['topicViewTemplate'][$count_6cad7e2e3c2bbe3c35245c2fd3846963]['displayData'] = $displayData;
}

$pluginEditorHook = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
	$editor = new $pluginEditorHook();
$IPBHTML .= "" . (($this->settings['_mobile_back_nav'] = 1) ? ("") : ("")) . "
<div class='master_list'>
	<h2>
		" . IPSText::truncate( $topic['title'], 45 ) . "
	</h2>
	<div class='controls'>
		<div class='buttons'>
			" . (($displayData['reply_button']['image'] == 'locked') ? ("" . (($displayData['reply_button']['url']) ? ("
					<a href='{$displayData['reply_button']['url']}' accesskey='r' class='button locked'>{$this->lang->words['mobile_top_locked_reply']}</a>
				") : ("
					<span class='button locked'>{$this->lang->words['mobile_top_locked']}</span>
				")) . "") : ("" . (($displayData['reply_button']['image']) ? ("" . (($displayData['reply_button']['url']) ? ("
						<a href='{$displayData['reply_button']['url']}' title='{$this->lang->words['mobile_topic_add_reply']}' accesskey='r' class='button'>{$this->lang->words['mobile_topic_add_reply']}</a>
					") : ("")) . "") : ("")) . "")) . "
		{$topic['SHOW_PAGES']}
		</div>
	</div>
	
	{$displayData['poll_data']['html']}
	
	" . ((is_array( $post_data ) AND count( $post_data )) ? ("
		".$this->__f__3236c2896b51b3d71d5109f7264206a5($forum,$topic,$post_data,$displayData)."	") : ("")) . "
	" . ((!$forum['disable_sharelinks'] AND $this->settings['sl_enable']) ? ("
		<div id='shareStrip'>
			<a class='button secondary' id='share_facebook_trigger' href='#'>{$this->lang->words['share']} <img src=\"{$this->settings['public_dir']}style_extra/sharelinks/facebook.png\" /></a>
			<a class='button secondary' id='share_twitter_trigger' href='#'>{$this->lang->words['share']} <img src=\"{$this->settings['public_dir']}style_extra/sharelinks/twitter.png\" /></a>
		</div>
	") : ("")) . "
	<div class='controls'>
		<div class='buttons'>
			" . (($displayData['reply_button']['image'] == 'locked') ? ("" . (($displayData['reply_button']['url']) ? ("
					<a href='{$displayData['reply_button']['url']}' accesskey='r' class='button locked'>{$this->lang->words['mobile_top_locked_reply']}</a>
				") : ("
					<span class='button locked'>{$this->lang->words['mobile_top_locked']}</span>
				")) . "") : ("" . (($displayData['reply_button']['image']) ? ("" . (($displayData['reply_button']['url']) ? ("
						<a href='{$displayData['reply_button']['url']}' title='{$this->lang->words['mobile_topic_add_reply']}' accesskey='r' class='button'>{$this->lang->words['mobile_topic_add_reply']}</a>
					") : ("")) . "") : ("")) . "")) . "
		{$topic['SHOW_PAGES']}
		</div>
	</div>
	" . (($displayData['fast_reply'] && $displayData['reply_button']['url']) ? ("<h2>{$this->lang->words['qr_title']}</h2>
		<div class='row'>
			<form action=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "", "public",'' ), "", "" ) . "\" method=\"post\">
				<input type=\"hidden\" name=\"app\" value=\"forums\" />
				<input type=\"hidden\" name=\"module\" value=\"post\" />
				<input type=\"hidden\" name=\"section\" value=\"post\" />
				<input type=\"hidden\" name=\"do\" value=\"reply_post_do\" />
				<input type=\"hidden\" name=\"f\" value=\"{$forum['id']}\" />
				<input type=\"hidden\" name=\"t\" value=\"{$topic['tid']}\" />
				<input type=\"hidden\" name=\"st\" value=\"{$this->request['st']}\" />
				<input type=\"hidden\" name=\"auth_key\" value=\"{$this->member->form_hash}\" />
				<input type=\"hidden\" name=\"fast_reply_used\" value=\"1\" />
				<input type=\"hidden\" name=\"enableemo\" value=\"yes\" />
				<input type=\"hidden\" name=\"enablesig\" value=\"yes\" />
				" . (($this->memberData['auto_track']) ? ("
					<input type=\"hidden\" name=\"enabletrack\" value=\"1\" />
				") : ("")) . "" . $editor->show('Post', array( 'type' => 'full', 'minimize' => 1, 'isTypingCallBack' => 'ipb.topic.isTypingCallBack', 'height' => 180, 'autoSaveKey' => 'reply-' . $topic[tid], 'warnInfo' => 'fastReply', 'modAll' => $topic['_fastReplyModAll'] ), "")  . "
				<div class=\"buttons\"><input type='submit' name=\"submit\" class='button page-button' value='{$this->lang->words['qr_post']}' tabindex='0' accesskey='s' id='submit_post' /></div>
			</form>
		</div>") : ("")) . "
</div>
<script type=\"text/javascript\">
	document.observe(\"dom:loaded\", function(){
				  	
		" . ((!$forum['disable_sharelinks'] AND $this->settings['sl_enable']) ? ("
			/* Facebook */
			if ( $('share_facebook_trigger') )
			{
				$('share_facebook_trigger').on('click', _fireFacebook );
			}
			
			/* Twitter */
			if ( $('share_twitter_trigger') )
			{
				$('share_twitter_trigger').on('click', _fireTwitter );
			}
		") : ("")) . "
		
		$$('.bbc_spoiler_show').find( function( elem ) {
			elem.addClassName( 'button page-button' ).setStyle( { 'font-size' : '12px', 'padding': '5px' } );
			elem.observe('click', _fireSpoiler );
		} ) } );
	
	function _fireTwitter(e)
	{
		Event.stop(e);
				
		_url   = '" . $this->registry->output->getCanonicalUrl() . "';
		_title = $$('meta[property~=\"og:title\"]').first().readAttribute('content');
		
		window.open('http://twitter.com/intent/tweet?url=' +  encodeURIComponent( _url ) + '&text=' + encodeURIComponent( _title ) );
	}
	
	function _fireFacebook(e)
	{
		Event.stop(e);
		
		_url   = '" . $this->registry->output->getCanonicalUrl() . "';
		_title = $$('meta[property~=\"og:title\"]').first().readAttribute('content');
		window.open('http://www.facebook.com/sharer.php?u=' + encodeURIComponent( _url ) + '&t=' + encodeURIComponent( _title ) );
	}
	
	function _fireSpoiler(e)
	{
		button = Event.element(e);
		
		Event.stop(e);
		var returnvalue = $(button).up().down('.bbc_spoiler_wrapper').down('.bbc_spoiler_content').toggle();
		
		if( returnvalue.visible() )
		{
			$(button).value = \"{$this->lang->words['cpt_hide']}\";
		}
		else
		{
			$(button).value = \"{$this->lang->words['macro__show']}\";
		}
	}
</script>";
return $IPBHTML;
}


function __f__3236c2896b51b3d71d5109f7264206a5($forum, $topic, $post_data, $displayData)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $post_data as $post )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
			" . ( method_exists( $this->registry->getClass('output')->getTemplate('topic'), 'post' ) ? $this->registry->getClass('output')->getTemplate('topic')->post($post, $displayData, $topic, $forum) : '' ) . "
		
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