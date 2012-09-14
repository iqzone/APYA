<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD 3         */
/* CACHE FILE: Skin set id: 4               */
/* CACHE FILE: Generated: Wed, 29 Aug 2012 14:15:07 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_gallery_global_4 extends skinMaster{

/**
* Construct
*/
function __construct( ipsRegistry $registry )
{
	parent::__construct( $registry );
	

$this->_funcHooks = array();
$this->_funcHooks['bbCodeImage'] = array('ismediathumb');
$this->_funcHooks['hookRecentGalleryImages'] = array('gallery_images_hook');
$this->_funcHooks['likeSummaryContents'] = array('likeOnlyMembers');


}

/* -- bbCodeAlbum --*/
function bbCodeAlbum($album) {
$IPBHTML = "";
$IPBHTML .= "<div class='_sharedMediaBbcode'>
	<div class='bbcode_mediaWrap clearfix'>
		<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;album={$album['album_id']}", "public",'' ), "{$album['album_name_seo']}", "viewalbum" ) . "\"><img src='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;module=images&amp;section=img_ctrl&amp;img={$album['album_cover_img_id']}&amp;tn=1", "public",'' ), "", "" ) . "' class='sharedmedia_image' /></a>
		<div class='details'>
			<h5><a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;album={$album['album_id']}", "public",'' ), "{$album['album_name_seo']}", "viewalbum" ) . "\">{$this->lang->words['album_ucfirst']}: {$album['album_name']}</a></h5>
			<div>{$album['album_count_imgs']} {$this->lang->words['images_lower']}</div>
			<div>{$album['album_count_comments']} {$this->lang->words['comments_lower']}</div>
		</div>
	</div>
</div>
<br />";
return $IPBHTML;
}

/* -- bbCodeImage --*/
function bbCodeImage($image="", $album="") {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_gallery_global', $this->_funcHooks['bbCodeImage'] ) )
{
$count_e462d7328362fa32a2f190af94c5373a = is_array($this->functionData['bbCodeImage']) ? count($this->functionData['bbCodeImage']) : 0;
$this->functionData['bbCodeImage'][$count_e462d7328362fa32a2f190af94c5373a]['image'] = $image;
$this->functionData['bbCodeImage'][$count_e462d7328362fa32a2f190af94c5373a]['album'] = $album;
}
$IPBHTML .= "<div class='_sharedMediaBbcode'>
	<div class='bbcode_mediaWrap clearfix'>
		" . (($image['media'] == 1) ? ("
            <a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;image={$image['id']}", "public",'' ), "{$image['caption_seo']}", "viewimage" ) . "\"><img src='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;module=images&amp;section=img_ctrl&amp;img={$image['id']}&amp;file=media", "public",'' ), "", "" ) . "' class='sharedmedia_image' /></a>
        ") : ("
            <a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;image={$image['id']}", "public",'' ), "{$image['caption_seo']}", "viewimage" ) . "\"><img src='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;module=images&amp;section=img_ctrl&amp;img={$image['id']}&amp;tn=1", "public",'' ), "", "" ) . "' class='sharedmedia_image' /></a>
        ")) . "		<div class='details'>
			<h5><a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;image={$image['id']}", "public",'' ), "{$image['caption_seo']}", "viewimage" ) . "\">{$image['caption']}</a></h5>
			<div><a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;album={$image['album_id']}", "public",'' ), "{$image['album_name_seo']}", "viewalbum" ) . "\">{$this->lang->words['album_ucfirst']}: {$image['album_name']}</a></div>
			<div>{$this->lang->words['uploaded_ucfirst']} " . $this->registry->getClass('class_localization')->getDate($image['idate'],"tiny", 0) . "</div>
		</div>
	</div>
</div>
<br />";
return $IPBHTML;
}

/* -- galleryCss --*/
function galleryCss() {
$IPBHTML = "";
$IPBHTML .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"{$this->settings['public_dir']}style_css/{$this->registry->output->skin['_csscacheid']}/ipgallery.css\" />";
return $IPBHTML;
}

/* -- general_warning --*/
function general_warning($data='') {
$IPBHTML = "";
$IPBHTML .= "<!-- Show inline warning -->
<div class='message unspecific'>
	<strong>{$data['title']}</strong><br />
	{$data['body']}
</div>";
return $IPBHTML;
}

/* -- globals --*/
function globals($data='') {
$IPBHTML = "";

if ( ! isset( $this->registry->templateStriping['imagelisting'] ) ) {
$this->registry->templateStriping['imagelisting'] = array( FALSE, "row1","row2");
}
$IPBHTML .= "" . $this->registry->getClass('output')->addJSModule("gallery", "0" ) . "
" . $this->registry->getClass('output')->addJSModule("rating", "0" ) . "" . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'include_lightbox' ) ? $this->registry->getClass('output')->getTemplate('global')->include_lightbox() : '' ) . "
" . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'include_highlighter' ) ? $this->registry->getClass('output')->getTemplate('global')->include_highlighter(1) : '' ) . "";
return $IPBHTML;
}

/* -- hookRecentGalleryImages --*/
function hookRecentGalleryImages($rows) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_gallery_global', $this->_funcHooks['hookRecentGalleryImages'] ) )
{
$count_fd382d0f31fccf333b55e1a3622c3655 = is_array($this->functionData['hookRecentGalleryImages']) ? count($this->functionData['hookRecentGalleryImages']) : 0;
$this->functionData['hookRecentGalleryImages'][$count_fd382d0f31fccf333b55e1a3622c3655]['rows'] = $rows;
}
$IPBHTML .= "<style type=\"text/css\">
#appGallLatestHook
{
	overflow:auto;
	height: 117px;
}
	#appGallLatestHook a {
		display: block;
	}
	
	#appGallLatestHook ul li img
	{
		max-width: 100px;
		max-height: 100px;
	}
		
		#appGallLatestHook ul li:last-child { margin-right: 10px; }
</style>
<div id='category_gallrecent' class='category_block block_wrap'>
	<h3 class='maintitle'>
		<a class='toggle right' href='#' title=\"{$this->lang->words['toggle_ucfirst']}\">{$this->lang->words['toggle_ucfirst']}</a> <a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery", "public",'' ), "false", "app=gallery" ) . "\">{$this->lang->words['recent_gallery_images']}</a>
	</h3>
	<div id='appGallLatestHook' class='ipsBox table_wrap'>
		<ul class='ipsList_inline ipsList_nowrap'>
		".$this->__f__f3a0133177cbdce1d9f14b8a51b987ae($rows)."		</ul>
	</div>
</div>
<br />";
return $IPBHTML;
}


function __f__f3a0133177cbdce1d9f14b8a51b987ae($rows)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $rows as $r )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
			<li>" . $this->registry->getClass('gallery')->inlineResize( $r['thumb'],'thumb_large','','' ) . "</li>
		
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- likeSummary --*/
function likeSummary($data, $relId, $opts) {
$IPBHTML = "";
$IPBHTML .= "" . $this->registry->getClass('output')->addJSModule("like", "0" ) . "
<div class='__like right' data-app=\"{$data['app']}\" data-area=\"{$data['area']}\" data-relid=\"{$relId}\" data-isfave=\"{$data['iLike']}\">
	" . ( method_exists( $this->registry->getClass('output')->getTemplate('gallery_global'), 'likeSummaryContents' ) ? $this->registry->getClass('output')->getTemplate('gallery_global')->likeSummaryContents($data, $relId, $opts) : '' ) . "
</div>
<script type=\"text/javascript\">
	var FAVE_TEMPLATE = new Template( \"<h3>" . sprintf( $this->lang->words['unset_fave_title'], $this->lang->words['like_ucfirst_un' . $data['vernacular'] ]) . "</h3><div class='ipsPad'><span class='desc'>" . sprintf( $this->lang->words['unset_fave_words'], $this->lang->words['like_un' . $data['vernacular'] ]) . "</span><br /><p class='ipsForm_center'><input type='button' value='" . sprintf( $this->lang->words['unset_button'], $this->lang->words['like_ucfirst_un' . $data['vernacular'] ]) . "' class='input_submit _funset' /></p></div>\");
</script>";
return $IPBHTML;
}

/* -- likeSummaryContents --*/
function likeSummaryContents($data, $relId, $opts=array()) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_gallery_global', $this->_funcHooks['likeSummaryContents'] ) )
{
$count_70d34ee4e7626ebda26efb6b93d2cc3e = is_array($this->functionData['likeSummaryContents']) ? count($this->functionData['likeSummaryContents']) : 0;
$this->functionData['likeSummaryContents'][$count_70d34ee4e7626ebda26efb6b93d2cc3e]['data'] = $data;
$this->functionData['likeSummaryContents'][$count_70d34ee4e7626ebda26efb6b93d2cc3e]['relId'] = $relId;
$this->functionData['likeSummaryContents'][$count_70d34ee4e7626ebda26efb6b93d2cc3e]['opts'] = $opts;
}
$IPBHTML .= "<span class='ipsButton_extra right " . (($data['totalCount']) ? ("_fmore clickable") : ("")) . "' title='" . sprintf( $this->lang->words['like_totalcount_' . $data['vernacular'] ], $data['totalCount'] ) . "' data-tooltip=\"" . sprintf( $this->lang->words['like_totalcount_' . $data['vernacular'] ], $data['totalCount'] ) . "\"><img src='{$this->settings['img_url']}/icon_users.png' /> <strong>{$data['totalCount']}</strong></span>
" . (($this->memberData['member_id']) ? ("<a href='#' title=\"" . (($data['iLike']) ? ("" . sprintf( $this->lang->words['fave_tt_on'], $this->lang->words['like_ucfirst_un' . $data['vernacular'] ]) . "") : ("" . sprintf( $this->lang->words['fave_tt_off'], $this->lang->words['like_ucfirst_' . $data['vernacular'] ]) . "")) . "\" class='ftoggle ipsButton_secondary'>" . (($data['iLike']) ? ("" . sprintf( $this->lang->words['unset_fave_button'], $this->lang->words['like_ucfirst_un' . $data['vernacular'] ]) . "") : ("" . sprintf( $this->lang->words['set_fave_button'], $this->lang->words['like_ucfirst_' . $data['vernacular'] ]) . "")) . "</a>") : ("")) . "";
return $IPBHTML;
}

/* -- profileWrapper --*/
function profileWrapper($member,$data='') {
$IPBHTML = "";
$IPBHTML .= "" . ( method_exists( $this->registry->getClass('output')->getTemplate('gallery_global'), 'galleryCss' ) ? $this->registry->getClass('output')->getTemplate('gallery_global')->galleryCss() : '' ) . "
<div class='tab_general'>
	<h3 class=\"bar\"><img src='{$this->settings['img_url']}/picture.png' alt='' /> <a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=search&amp;do=user_activity&amp;search_app=gallery&amp;mid={$member['member_id']}&amp;search_app=gallery", "public",'' ), "", "" ) . "'>{$this->lang->words['view_all_images']}</a></h3>
	<div class='gallery_row'>
		{$data}
	</div>
</div>";
return $IPBHTML;
}


}


/*--------------------------------------------------*/
/* END OF FILE                                      */
/*--------------------------------------------------*/

?>