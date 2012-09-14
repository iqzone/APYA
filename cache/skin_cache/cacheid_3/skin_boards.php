<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD 3         */
/* CACHE FILE: Skin set id: 3               */
/* CACHE FILE: Generated: Wed, 29 Aug 2012 14:15:03 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_boards_3 extends skinMaster{

/**
* Construct
*/
function __construct( ipsRegistry $registry )
{
	parent::__construct( $registry );
	

$this->_funcHooks = array();
$this->_funcHooks['boardIndexTemplate'] = array('subforums','hasUnread','forums','cat_has_forums','categories','cats_forums');


}

/* -- boardIndexTemplate --*/
function boardIndexTemplate($lastvisit="", $stats=array(), $cat_data=array(), $show_side_blocks=true, $side_blocks=array()) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_boards', $this->_funcHooks['boardIndexTemplate'] ) )
{
$count_f9a71fe6305139451df19a15cc8e7d90 = is_array($this->functionData['boardIndexTemplate']) ? count($this->functionData['boardIndexTemplate']) : 0;
$this->functionData['boardIndexTemplate'][$count_f9a71fe6305139451df19a15cc8e7d90]['lastvisit'] = $lastvisit;
$this->functionData['boardIndexTemplate'][$count_f9a71fe6305139451df19a15cc8e7d90]['stats'] = $stats;
$this->functionData['boardIndexTemplate'][$count_f9a71fe6305139451df19a15cc8e7d90]['cat_data'] = $cat_data;
$this->functionData['boardIndexTemplate'][$count_f9a71fe6305139451df19a15cc8e7d90]['show_side_blocks'] = $show_side_blocks;
$this->functionData['boardIndexTemplate'][$count_f9a71fe6305139451df19a15cc8e7d90]['side_blocks'] = $side_blocks;
}
$IPBHTML .= "<template>boardIndex</template>
<subtext>authorName|date</subtext><!--authorName|date|postTitle-->
" . ((is_array( $cat_data ) AND count( $cat_data )) ? ("
		<categories>
		".$this->__f__96f227f8f775d44cfe2a36ccb8ed3c05($lastvisit,$stats,$cat_data,$show_side_blocks,$side_blocks)."		</categories>
	") : ("")) . "
		<statistics>
			<posts>{$stats['info']['total_posts']}</posts>
			<members>{$stats['info']['mem_count']}</members>
			<user>
				<id>{$stats['info']['last_mem_id']}</id>
				<name><![CDATA[{$stats['info']['last_mem_name']}]]></name>
				<url><![CDATA[{$stats['info']['last_mem_link']}]]></url>
			</user>
			<onlinerecord>{$stats['info']['most_online']}</onlinerecord>
		</statistics>";
return $IPBHTML;
}


function __f__f5dcc9dfadb1bffab67b9ccde8eba43b($lastvisit="", $stats=array(), $cat_data=array(), $show_side_blocks=true, $side_blocks=array(),$_data='',$forum_id='',$forum_data='')
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $forum_data['subforums'] as $__id => $__data )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
							<subforum>
								<id>{$__data[0]}</id>
								<name><![CDATA[{$__data[1]}]]></name>
								<url><![CDATA[" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showforum={$__data[0]}", "public",'' ), "{$__data[2]}", "showforum" ) . "]]></url>
							</subforum>
							
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

function __f__9793458dbba073fbcf9fb699cf53b02c($lastvisit="", $stats=array(), $cat_data=array(), $show_side_blocks=true, $side_blocks=array(),$_data='')
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $_data['forum_data'] as $forum_id => $forum_data )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
					<forum>
						<id>{$forum_data['id']}</id>
						<name><![CDATA[{$forum_data['name']}]]></name>
						
						<url><![CDATA[" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showforum={$forum_data['id']}", "public",'' ), "{$forum_data['name_seo']}", "showforum" ) . "]]></url>
						<description><![CDATA[{$forum_data['description']}]]></description>
							" . (($forum_data['redirect_on']) ? ("							
						<redirect>1</redirect>
						<redirectHits>{$forum_data['redirect_hits']}</redirectHits>
						<redirect_url><![CDATA[{$forum_data['redirect_url']}]]></redirect_url>
							") : ("" . (($forum_data['_has_unread']) ? ("
							<isRead>0</isRead>
						") : ("
							<isRead>1</isRead>
						")) . "
						<redirect>0</redirect>
						<type>{$forum_data['status']}</type>
						<topics>{$forum_data['topics']}</topics>
						<replies>{$forum_data['posts']}</replies>
						<lastpost>
								" . (($forum_data['hide_last_info']) ? ("
							<name>{$this->lang->words['f_protected']}</name>
								") : ("<date>" . $this->registry->getClass('class_localization')->getDate($forum_data['last_post'],"DATE", 0) . "</date>
							<name><![CDATA[{$forum_data['last_title']}]]></name>
							<id>{$forum_data['last_id']}</id>
							<url><![CDATA[" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showtopic={$forum_data['last_id']}&amp;view=getnewpost", "public",'' ), "{$forum_data['seo_last_title']}", "showtopic" ) . "]]></url>
							<user>
								" . (($forum_data['last_poster_id']) ? ("						
								<id>{$forum_data['last_poster_id']}</id>
								<name><![CDATA[{$forum_data['last_poster_name']}]]></name>
								<url><![CDATA[" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showuser={$forum_data['last_poster_id']}", "public",'' ), "{$forum_data['seo_last_name']}", "showuser" ) . "]]></url>
									") : ("
								<id>0</id>
								<name><![CDATA[{$this->settings['guest_name_pre']}{$forum_data['last_poster_name']}{$this->settings['guest_name_suf']}]]></name>
								<url></url>
								")) . "
							</user>")) . "
						</lastpost>")) . "					
										" . (($forum_data['show_subforums'] AND count( $forum_data['subforums'] ) AND $forum_data['show_subforums']) ? ("
						<subforums>
											".$this->__f__f5dcc9dfadb1bffab67b9ccde8eba43b($lastvisit,$stats,$cat_data,$show_side_blocks,$side_blocks,$_data,$forum_id,$forum_data)."						</subforums>
						") : ("")) . "
					</forum>
						
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

function __f__96f227f8f775d44cfe2a36ccb8ed3c05($lastvisit="", $stats=array(), $cat_data=array(), $show_side_blocks=true, $side_blocks=array())
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $cat_data as $_data )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
			" . ((is_array( $_data['forum_data'] ) AND count( $_data['forum_data'] )) ? ("
			<category>
				<id>{$_data['cat_data']['id']}</id>
				<name><![CDATA[{$_data['cat_data']['name']}]]></name>
				<url><![CDATA[" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showforum={$_data['cat_data']['id']}", "public",'' ), "{$_data['cat_data']['name_seo']}", "showforum" ) . "]]></url>
				<forums>
						".$this->__f__9793458dbba073fbcf9fb699cf53b02c($lastvisit,$stats,$cat_data,$show_side_blocks,$side_blocks,$_data)."				</forums>
			</category>
			") : ("")) . "
		
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- hookBoardIndexShareLinks --*/
function hookBoardIndexShareLinks($data) {
$IPBHTML = "";
$IPBHTML .= "<!-- NoData -->";
return $IPBHTML;
}

/* -- hookBoardIndexStatusUpdates --*/
function hookBoardIndexStatusUpdates($updates=array()) {
$IPBHTML = "";
$IPBHTML .= "<!-- NoData -->";
return $IPBHTML;
}

/* -- hookFacebookActivity --*/
function hookFacebookActivity() {
$IPBHTML = "";
$IPBHTML .= "<!-- NoData -->";
return $IPBHTML;
}

/* -- hookRecentTopics --*/
function hookRecentTopics($topics) {
$IPBHTML = "";
$IPBHTML .= "<!-- NoData -->";
return $IPBHTML;
}

/* -- hookTagCloud --*/
function hookTagCloud($tags) {
$IPBHTML = "";
$IPBHTML .= "<!-- NoData -->";
return $IPBHTML;
}

/* -- statusReplies --*/
function statusReplies($replies=array()) {
$IPBHTML = "";
$IPBHTML .= "<!-- NoData -->";
return $IPBHTML;
}

/* -- statusUpdates --*/
function statusUpdates($updates=array(), $smallSpace=0, $latestOnly=0) {
$IPBHTML = "";
$IPBHTML .= "<!-- NoData -->";
return $IPBHTML;
}


}


/*--------------------------------------------------*/
/* END OF FILE                                      */
/*--------------------------------------------------*/

?>