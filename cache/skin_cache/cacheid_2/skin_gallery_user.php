<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD 3         */
/* CACHE FILE: Skin set id: 2               */
/* CACHE FILE: Generated: Wed, 29 Aug 2012 14:14:59 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_gallery_user_2 extends skinMaster{

/**
* Construct
*/
function __construct( ipsRegistry $registry )
{
	parent::__construct( $registry );
	

$this->_funcHooks = array();
$this->_funcHooks['galleryAdvancedSearchFilters'] = array('hasOptions');
$this->_funcHooks['galleryAlbumSearchResult'] = array('catapprove','filerate1','filerate2','filerate3','ralerate4','filerate5','hasDate','hasLittleWeeBabbies','isNotGlobal','hasDescription');
$this->_funcHooks['galleryCommentSearchResult'] = array('filerate1','filerate2','filerate3','ralerate4','filerate5','hasDate');
$this->_funcHooks['galleryImageSearchResult'] = array('hasDate','filerate1','filerate2','filerate3','ralerate4','filerate5','hasDate','hasDescription');
$this->_funcHooks['profileBlock'] = array('catapprove','filerate1','filerate2','filerate3','ralerate4','filerate5','hasDate','hasLittleWeeBabbies','isNotGlobal','hasDescription','file_rows','hasMore');
$this->_funcHooks['searchResultsAsGallery'] = array('results');


}

/* -- galleryAdvancedSearchFilters --*/
function galleryAdvancedSearchFilters($options) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_gallery_user', $this->_funcHooks['galleryAdvancedSearchFilters'] ) )
{
$count_a8792f49adea5bd638af2439fe95a3fb = is_array($this->functionData['galleryAdvancedSearchFilters']) ? count($this->functionData['galleryAdvancedSearchFilters']) : 0;
$this->functionData['galleryAdvancedSearchFilters'][$count_a8792f49adea5bd638af2439fe95a3fb]['options'] = $options;
}
$IPBHTML .= "<fieldset class='" .  IPSLib::next( $this->registry->templateStriping["search"] ) . "'>
	<span class='search_msg'>
		{$this->lang->words['s_gallery_desc']}
	</span>
	<ul>
		" . (($options) ? ("
			<li>
				<label for='forums_filter'>{$this->lang->words['find_in_category']}</label>
				<select name='search_app_filters[gallery][albumids][]' class='input input_select' size='6' multiple='multiple'>
					{$options}
				</select>
			</li>
		") : ("")) . "
		<li>
			<label for='forums_filter'>{$this->lang->words['find_in_albums']}</label>
			<input type='checkbox' name='search_app_filters[gallery][excludeAlbums]' value='1' class='input_check' />
		</li>
	</ul>
</fieldset>";
return $IPBHTML;
}

/* -- galleryAlbumSearchResult --*/
function galleryAlbumSearchResult($data, $resultAsTitle=false) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_gallery_user', $this->_funcHooks['galleryAlbumSearchResult'] ) )
{
$count_a7d305a2d1bf038235caf352e9086b09 = is_array($this->functionData['galleryAlbumSearchResult']) ? count($this->functionData['galleryAlbumSearchResult']) : 0;
$this->functionData['galleryAlbumSearchResult'][$count_a7d305a2d1bf038235caf352e9086b09]['data'] = $data;
$this->functionData['galleryAlbumSearchResult'][$count_a7d305a2d1bf038235caf352e9086b09]['resultAsTitle'] = $resultAsTitle;
}
$IPBHTML .= "<div class='row' id='album_id_{$data['album_id']}'>
	<div class='icon'>" . $this->registry->getClass('gallery')->inlineResize( $data['thumb'],'thumb_small','','' ) . "</div>
	<div class='rowContent'>
		<a class='title' href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;album={$data['album_id']}", "public",'' ), "{$data['album_name_seo']}", "viewalbum" ) . "'>" . IPSText::truncate( $data['album_name'], 200) . "</a>
	</div>
</div>
<!--
<div class='ipg_category_row clearfix " .  IPSLib::next( $this->registry->templateStriping["downloadsTable"] ) . "' id='album_id_{$data['album_id']}'>
	" . $this->registry->getClass('gallery')->inlineResize( $data['thumb'],'thumb_medium','','' ) . "
	<div class='file_info right'>
		" . (($permissions['canapp']) ? ("
			<input type='checkbox' class='input_check topic_mod right' id='file_{$data['album_id']}' />
		") : ("")) . "
		<span class='mini_rate'>
			" . (($data['album_rating_aggregate'] >= 1) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
			" . (($data['album_rating_aggregate'] >= 2) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
			" . (($data['album_rating_aggregate'] >= 3) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
			" . (($data['album_rating_aggregate'] >= 4) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
			" . (($data['album_rating_aggregate'] >= 5) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
		</span>
		{$data['_totalImages']} images ({$data['_totalComments']} comments)
		<br />
		<span class='date'>
			" . (($data['album_last_img_date']) ? ("Updated " . $this->registry->getClass('class_localization')->getDate($data['album_last_img_date'],"date", 0) . "") : ("&nbsp;")) . "
		</span>
	</div>
	
	" . (($data['_childrenCount']) ? ("
		<span class='topic_prefix'> 
			<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;browseAlbum={$data['album_id']}", "public",'' ), "{$data['album_name_seo']}", "browsealbum" ) . "\">" . sprintf( $this->lang->words['view_child_albums'], $data['_childrenCount']) . "</a>
		</span>
	") : ("")) . "
	<h3>
		<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;album={$data['album_id']}", "public",'' ), "{$data['album_name_seo']}", "viewalbum" ) . "'>" . IPSText::truncate( $data['album_name'], 200) . "</a>
	</h3>
	
	<br />
	" . ((!$this->registry->gallery->helper('albums')->isGlobal($data)) ? ("
		{$this->lang->words['by_ucfirst']} " . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($data) : '' ) . "
	") : ("
		<span class='topic_prefix light'>Global Album</span>
	")) . "
	
	" . (($data['album_description']) ? ("
		<div class='album_desc'>
			" . IPSText::truncate( strip_tags( IPSText::getTextClass('bbcode')->stripAllTags( $data['album_description'] ), '<br />' ), 100 ) . "
		</div>
	") : ("")) . "
</div>-->";
return $IPBHTML;
}

/* -- galleryCommentSearchResult --*/
function galleryCommentSearchResult($data, $resultAsTitle=false) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_gallery_user', $this->_funcHooks['galleryCommentSearchResult'] ) )
{
$count_d69201d50ef860c01b3d5c14855cfb55 = is_array($this->functionData['galleryCommentSearchResult']) ? count($this->functionData['galleryCommentSearchResult']) : 0;
$this->functionData['galleryCommentSearchResult'][$count_d69201d50ef860c01b3d5c14855cfb55]['data'] = $data;
$this->functionData['galleryCommentSearchResult'][$count_d69201d50ef860c01b3d5c14855cfb55]['resultAsTitle'] = $resultAsTitle;
}
$IPBHTML .= "<div class='ipg_category_row clearfix " .  IPSLib::next( $this->registry->templateStriping["downloadsTable"] ) . "' id='img_id_{$data['id']}'>
	" . $this->registry->getClass('gallery')->inlineResize( $data['thumb'],'thumb_medium','','' ) . "
	<div class='file_info right'>
		<span class='mini_rate'>
			" . (($data['rating'] >= 1) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
			" . (($data['rating'] >= 2) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
			" . (($data['rating'] >= 3) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
			" . (($data['rating'] >= 4) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
			" . (($data['rating'] >= 5) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
		</span>
		{$data['views']} views ({$data['comments']} comments)
		<br />
		<span class='date'>
			" . (($data['album_last_img_date']) ? ("Uploaded " . $this->registry->getClass('class_localization')->getDate($data['idate'],"date", 0) . "") : ("&nbsp;")) . "
		</span>											
	</div>
	<h3>
		<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;image={$data['id']}", "public",'' ), "{$data['caption_seo']}", "viewimage" ) . "'>" . IPSText::truncate( $data['caption'], 200) . "</a>
		<span style='font-size:0.7em'>in <a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;album={$data['album_id']}", "public",'' ), "{$data['album_name_seo']}", "viewalbum" ) . "'>" . IPSText::truncate( $data['album_name'], 200) . "</a></span>
	</h3>
	<div class='album_desc'>
		{$data['content']}
		<br />
		Comment by " . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($data) : '' ) . " on " . $this->registry->getClass('class_localization')->getDate($data['post_date'],"short", 0) . "
	</div>
</div>";
return $IPBHTML;
}

/* -- galleryImageSearchResult --*/
function galleryImageSearchResult($data, $resultAsTitle=false) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_gallery_user', $this->_funcHooks['galleryImageSearchResult'] ) )
{
$count_19811f7be2926d96049aa6315af215cb = is_array($this->functionData['galleryImageSearchResult']) ? count($this->functionData['galleryImageSearchResult']) : 0;
$this->functionData['galleryImageSearchResult'][$count_19811f7be2926d96049aa6315af215cb]['data'] = $data;
$this->functionData['galleryImageSearchResult'][$count_19811f7be2926d96049aa6315af215cb]['resultAsTitle'] = $resultAsTitle;
}
$IPBHTML .= "<div class='row touch-row' id='img_id_{$data['id']}'>
	<div class='icon'>" . $this->registry->getClass('gallery')->inlineResize( $data['thumb'],'thumb_small','','' ) . "</div>
	<div class='rowContent'>
		<a class='title' href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;image={$data['id']}", "public",'' ), "{$data['caption_seo']}", "viewimage" ) . "'>" . IPSText::truncate( $data['caption'], 200) . "</a>
		<br />
		<span class='subtext'>
			" . (($data['album_last_img_date']) ? ("Uploaded " . $this->registry->getClass('class_localization')->getDate($data['idate'],"date", 0) . "<br />") : ("")) . "
			{$data['views']} views, {$data['comments']} comments
		</span>
	</div>
</div>
<!--
<div class='ipg_category_row clearfix " .  IPSLib::next( $this->registry->templateStriping["downloadsTable"] ) . "' id='img_id_{$data['id']}'>
	" . $this->registry->getClass('gallery')->inlineResize( $data['thumb'],'thumb_medium','','' ) . "
	<div class='file_info right'>
		<span class='mini_rate'>
			" . (($data['rating'] >= 1) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
			" . (($data['rating'] >= 2) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
			" . (($data['rating'] >= 3) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
			" . (($data['rating'] >= 4) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
			" . (($data['rating'] >= 5) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
		</span>
		{$data['views']} views ({$data['comments']} comments)
		<br />
		<span class='date'>
			" . (($data['album_last_img_date']) ? ("Uploaded " . $this->registry->getClass('class_localization')->getDate($data['idate'],"date", 0) . "") : ("&nbsp;")) . "
		</span>
	</div>
	<h3>
		<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;image={$data['id']}", "public",'' ), "{$data['caption_seo']}", "viewimage" ) . "'>" . IPSText::truncate( $data['caption'], 200) . "</a>
		<span style='font-size:0.7em'>in <a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;album={$data['album_id']}", "public",'' ), "{$data['album_name_seo']}", "viewalbum" ) . "'>" . IPSText::truncate( $data['album_name'], 200) . "</a></span>
	</h3>
	<br />
	{$this->lang->words['by_ucfirst']} " . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($data) : '' ) . "
	" . (($data['description']) ? ("
		<div class='album_desc'>
			" . IPSText::truncate( strip_tags( IPSText::getTextClass('bbcode')->stripAllTags( $data['description'] ), '<br />' ), 100 ) . "
		</div>
	") : ("")) . "
</div>-->";
return $IPBHTML;
}

/* -- profileBlock --*/
function profileBlock($member, $albums=array(), $hasMore=false) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_gallery_user', $this->_funcHooks['profileBlock'] ) )
{
$count_8dfeffeb90f0783bc5caede72dcadcce = is_array($this->functionData['profileBlock']) ? count($this->functionData['profileBlock']) : 0;
$this->functionData['profileBlock'][$count_8dfeffeb90f0783bc5caede72dcadcce]['member'] = $member;
$this->functionData['profileBlock'][$count_8dfeffeb90f0783bc5caede72dcadcce]['albums'] = $albums;
$this->functionData['profileBlock'][$count_8dfeffeb90f0783bc5caede72dcadcce]['hasMore'] = $hasMore;
}

if ( ! isset( $this->registry->templateStriping['downloadsTable'] ) ) {
$this->registry->templateStriping['downloadsTable'] = array( FALSE, "row1","row2");
}
$IPBHTML .= "" . ( method_exists( $this->registry->getClass('output')->getTemplate('gallery_global'), 'galleryCss' ) ? $this->registry->getClass('output')->getTemplate('gallery_global')->galleryCss() : '' ) . "
<div class='tab_general'>
	<h3 class='bar'>{$this->lang->words['advsearch_albums']}</h3>
	<div class='gallery_row'>
		<div id='ipg_category'>
						".$this->__f__9adbe073d7ea66a2cb3af663a2d37e67($member,$albums,$hasMore)."		</div>
		" . (($hasMore) ? ("
			<div class='pad right'>
				<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;user={$member['member_id']}", "public",'' ), "{$member['members_seo_name']}", "useralbum" ) . "\">{$this->lang->words['view_all_albums']}</a>
			</div>
		") : ("")) . "
	</div>
</div>";
return $IPBHTML;
}


function __f__9adbe073d7ea66a2cb3af663a2d37e67($member, $albums=array(), $hasMore=false)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $albums as $id => $data )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
				<div class='ipg_category_row clearfix " .  IPSLib::next( $this->registry->templateStriping["downloadsTable"] ) . "' id='album_id_{$data['album_id']}' style='font-size:0.85em'>
					" . $this->registry->getClass('gallery')->inlineResize( $data['thumb'],'thumb_medium','','' ) . "
					<div class='file_info right'>
						" . (($permissions['canapp']) ? ("
							<input type='checkbox' class='input_check topic_mod right' id='file_{$data['album_id']}' />
						") : ("")) . "
						<span class='mini_rate'>
							" . (($data['album_rating_aggregate'] >= 1) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
							" . (($data['album_rating_aggregate'] >= 2) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
							" . (($data['album_rating_aggregate'] >= 3) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
							" . (($data['album_rating_aggregate'] >= 4) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
							" . (($data['album_rating_aggregate'] >= 5) ? ("<img src=\"{$this->settings['img_url']}/gallery/star.png\" alt='*' class='rate_img' />") : ("<img src=\"{$this->settings['img_url']}/gallery/star_off.png\" alt='*' class='rate_img' />")) . "
						</span>
						<br />
						{$data['_totalImages']} images ({$data['_totalComments']} comments)
						<br />
						<span class='date'>
							" . (($data['album_last_img_date']) ? ("Updated " . $this->registry->getClass('class_localization')->getDate($data['album_last_img_date'],"date", 0) . "") : ("&nbsp;")) . "
						</span>											
					</div>
					
					" . (($data['_childrenCount']) ? ("
						<span class='topic_prefix'> 
							<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;browseAlbum={$data['album_id']}", "public",'' ), "{$data['album_name_seo']}", "browsealbum" ) . "\">" . sprintf( $this->lang->words['view_child_albums'], $data['_childrenCount']) . "</a>
						</span>
					") : ("")) . "
					<h3>
						<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;album={$data['album_id']}", "public",'' ), "{$data['album_name_seo']}", "viewalbum" ) . "'>" . IPSText::truncate( $data['album_name'], 200) . "</a>
					</h3>
					
					<br />
					" . ((!$this->registry->gallery->helper('albums')->isGlobal($data)) ? ("
						{$this->lang->words['by_ucfirst']} " . ( method_exists( $this->registry->getClass('output')->getTemplate('global'), 'userHoverCard' ) ? $this->registry->getClass('output')->getTemplate('global')->userHoverCard($data) : '' ) . "
					") : ("
						<span class='topic_prefix light'>Global Album</span>
					")) . "
					
					" . (($data['album_description']) ? ("
						<div class='album_desc'>
							" . IPSText::truncate( strip_tags( IPSText::getTextClass('bbcode')->stripAllTags( $data['album_description'] ), '<br />' ), 100 ) . "
						</div>
					") : ("")) . "
				</div>	
			
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- searchResultsAsGallery --*/
function searchResultsAsGallery($results, $titlesOnly) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_gallery_user', $this->_funcHooks['searchResultsAsGallery'] ) )
{
$count_342290c30f4d3763926aeaedf0e36527 = is_array($this->functionData['searchResultsAsGallery']) ? count($this->functionData['searchResultsAsGallery']) : 0;
$this->functionData['searchResultsAsGallery'][$count_342290c30f4d3763926aeaedf0e36527]['results'] = $results;
$this->functionData['searchResultsAsGallery'][$count_342290c30f4d3763926aeaedf0e36527]['titlesOnly'] = $titlesOnly;
}

if ( ! isset( $this->registry->templateStriping['downloadsTable'] ) ) {
$this->registry->templateStriping['downloadsTable'] = array( FALSE, "row1","row2");
}
$IPBHTML .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"{$this->settings['public_dir']}style_css/{$this->registry->output->skin['_csscacheid']}/ipgallery.css\" />
<div class='ipsPad clearfix'>
	<div id='ipg_category'>
				".$this->__f__9e4360397d314cd223324140e7d03ede($results,$titlesOnly)."	</div>
</div>";
return $IPBHTML;
}


function __f__9e4360397d314cd223324140e7d03ede($results, $titlesOnly)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $results as $result )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
			{$result['html']}
		
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- unapprovedComments --*/
function unapprovedComments($comments) {
$IPBHTML = "";
$IPBHTML .= "<!--no data in this master skin-->";
return $IPBHTML;
}

/* -- unapprovedImages --*/
function unapprovedImages($images) {
$IPBHTML = "";
$IPBHTML .= "<!--no data in this master skin-->";
return $IPBHTML;
}

/* -- viewforumImages --*/
function viewforumImages($pagination, $images_html, $filter) {
$IPBHTML = "";

if ( ! isset( $this->registry->templateStriping['images'] ) ) {
$this->registry->templateStriping['images'] = array( FALSE, "row1","row2");
}
$IPBHTML .= "<div class='topic_controls'>
	{$pagination}
</div>
<h2 class='maintitle gallery_cat_title'>{$this->lang->words['images_to_forum']}</h2>
<div class='generic_bar'></div>

		<div class='gallery_row row2'>
		" . (($images_html) ? ("
			{$images_html}
		") : ("" . ( method_exists( $this->registry->getClass('output')->getTemplate('gallery_imagelisting'), 'basic_row' ) ? $this->registry->getClass('output')->getTemplate('gallery_imagelisting')->basic_row('category_no_images') : '' ) . "")) . "
	</div>
	<br class='clear' />

<div class='filter_bar rounded'>
	{$filter}
</div>
<div class='topic_controls'>
	{$pagination}
</div>";
return $IPBHTML;
}


}


/*--------------------------------------------------*/
/* END OF FILE                                      */
/*--------------------------------------------------*/

?>