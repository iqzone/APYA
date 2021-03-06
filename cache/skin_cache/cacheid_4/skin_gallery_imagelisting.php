<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD 3         */
/* CACHE FILE: Skin set id: 4               */
/* CACHE FILE: Generated: Wed, 29 Aug 2012 14:15:07 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_gallery_imagelisting_4 extends skinMaster{

/**
* Construct
*/
function __construct( ipsRegistry $registry )
{
	parent::__construct( $registry );
	

$this->_funcHooks = array();
$this->_funcHooks['review'] = array('cover','cover','isMedia','hasTags','clickGps','geo','images','isModerating','hasAlreadyCoverUpload','hasAlreadyCoverEdit','isSel0','canPrivate','isSel1','isSel2','hasAlbumType','isWatermarkSel0','isWatermarkSel1','canWatermark','hasImagesToReviewTitle','isReviewingUpload','hasImagesToReview');


}

/* -- review --*/
function review($images, $album, $type, $sessionKey) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_gallery_imagelisting', $this->_funcHooks['review'] ) )
{
$count_d761df797ebfe15d59ed45faf811fe57 = is_array($this->functionData['review']) ? count($this->functionData['review']) : 0;
$this->functionData['review'][$count_d761df797ebfe15d59ed45faf811fe57]['images'] = $images;
$this->functionData['review'][$count_d761df797ebfe15d59ed45faf811fe57]['album'] = $album;
$this->functionData['review'][$count_d761df797ebfe15d59ed45faf811fe57]['type'] = $type;
$this->functionData['review'][$count_d761df797ebfe15d59ed45faf811fe57]['sessionKey'] = $sessionKey;
}
$IPBHTML .= "" . $this->registry->getClass('output')->addJSModule("gallery", "0" ) . "
" . $this->registry->getClass('output')->addJSModule("gallery_albumchooser", "0" ) . "
<form method=\"post\" id='postingform' action=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;module=images&amp;section=review&amp;do=process&amp;sessionKey={$sessionKey}&amp;album_id={$album['album_id']}&amp;type={$type}", "public",'' ), "", "" ) . "\">
	<input type=\"hidden\" name=\"sessionKey\" value=\"{$sessionKey}\" />
	
	" . (($type == 'uploads') ? ("<h1 class='ipsType_pagetitle'>{$this->lang->words['upload_ucfirst']}</h1>
		<br />
		<div class='ipsSteps clearfix'>
			<ul>
				<li class=\"ipsSteps_done\">
					<strong class='ipsSteps_title'>" . sprintf( $this->lang->words['step'], '1' ) . "</strong>
					<span class='ipsSteps_desc'>{$this->lang->words['review_step_upload']}</span>
					<span class='ipsSteps_arrow'>&nbsp;</span>
				</li>
				<li class=\"ipsSteps_active\">
					<strong class='ipsSteps_title'>" . sprintf( $this->lang->words['step'], '2' ) . "</strong>
					<span class='ipsSteps_desc'>{$this->lang->words['review_step_publish']}</span>
					<span class='ipsSteps_arrow'>&nbsp;</span>
				</li>	
			</ul>
		</div>
		<br />
		
		" . (($album['album_g_approve_img'] AND ! $this->registry->gallery->helper('albums')->canModerate( $album )) ? ("
		<div class='message unspecific'>
		<h3>{$this->lang->words['image_approval_required']}</h3>
		<p>{$this->lang->words['image_approval_required_text']}</p>
		</div>
		<br />
		") : ("")) . "

		" . (($album['_hasCoverSet'] == 'elsewhere') ? ("
			{$album['cover']['tag']}
			<div class='ipsBox_withphoto'>
				<h2 class='ipsType_subtitle'>{$this->lang->words['review_sel_album']} {$album['album_name']}</h2>
				<br /><input type='radio' id='keep_cover' name='makeCover' value='0' checked=\"checked\"> <label for=\"keep_cover\">&nbsp;{$this->lang->words['review_cover_img']}</label>
			</div>
		") : ("
			<h2 class='ipsType_subtitle'>{$this->lang->words['review_sel_album']} {$album['album_name']}</h2>
		")) . "") : ("<h1 class='ipsType_pagetitle'>{$this->lang->words['review_title_' . $type ]}</h1>
		<br />
		
		<div class='ipsBox'>
			<div class='ipsBox_container ipsPad'>
				<div class='ipsLayout ipsLayout_withleft ipsLayout_smallleft ipsLayout_withright ipsLayout_bigright clearfix'>
					<div class='ipsLayout_left short'>
						<ul class='ipsForm ipsForm_vertical'>
							<li class='ipsField'>
								<label class='ipsField_title'>{$this->lang->words['current_cover']}</label>
								<p class='ipsField_content'>
									{$album['thumb']}
									
									<!-- SKINNOTE: restore this radio button once we add pagination to that area
									" . (($album['_hasCoverSet'] == 'elsewhere') ? ("
										<br /><span class='ipsPad_half'><input type='radio' id='keep_cover' name='makeCover' value='0' checked=\"checked\"> <label for=\"keep_cover\">&nbsp;{$this->lang->words['review_cover_img']}</label></span>
									") : ("")) . "
									-->
								</p>
							</li>
						</ul>
					</div>
					<div class='ipsLayout_content'>
						<ul class='ipsForm ipsForm_horizontal'>
							<li class='ipsField'>
								<label class='ipsField_title'>{$this->lang->words['album_name']}</label>
								<p class='ipsField_content'><input type='text' name='album_name' class='input_text' value=\"{$album['album_name']}\" size='34' /></p>
							</li>
							" . (($this->registry->gallery->helper('albums')->isGlobal( $album ) !== true) ? ("<li class='ipsField ipsField_select'>
									<label class='ipsField_title'>{$this->lang->words['album_type']}</label>
									<div class='ipsField_content'>
										<select name=\"album_is_public\">
											" . (($this->memberData['g_album_private']) ? ("<option value=\"0\" " . (($album['album_is_public'] == 0) ? ("selected='selected'") : ("")) . ">{$this->lang->words['private_album']}</option>") : ("")) . "
											<option value=\"1\" " . (($album['album_is_public'] == 1) ? ("selected='selected'") : ("")) . ">{$this->lang->words['public_album']}</option>
											<option value=\"2\" " . (($album['album_is_public'] == 2) ? ("selected='selected'") : ("")) . ">{$this->lang->words['friend_album']}</option>
										</select>
									</div>
								</li>") : ("")) . "
							<li class='ipsField ipsField_select'>
								<label class='ipsField_title'>{$this->lang->words['album_sorting']}</label>
								<div class='ipsField_content'>
									<select name=\"album_sort_options__key\">
										".$this->__f__58ec2602f3116ec9badc55288d15dd5e($images,$album,$type,$sessionKey)."									</select>
									<select name=\"album_sort_options__dir\">
										".$this->__f__26792805ce01ab8bd9e266cebdd2c823($images,$album,$type,$sessionKey)."									</select>
								</div>
							<li>
							<li class='ipsField ipsField_select'>
								<label class='ipsField_title'>{$this->lang->words['parent_album']}</label>
								<div class='ipsField_content'>
									<input type='hidden' id='albumParentId' name='album_parent_id' value='{$album['album_parent_id']}' /><div class='albumSelected' id='asDiv'>{$album['_parent']['parentText']}</div>
									<a href='#' class='ipsButton_secondary' data-album-selector-auto-update='{\"field\": \"albumParentId\", \"div\": \"asDiv\"}' data-album-selector='type=editAlbum&moderate=1&album_id={$album['album_id']}'>{$this->lang->words['as_select_album']}</a>
								</div>
							<li>
							" . (($album['_canWatermark']) ? ("<li class='ipsField ipsField_select'>
									<label class='ipsField_title'>{$this->lang->words['album_watermark_check']}</label>
									<div class='ipsField_content'>
										<select name='album_watermark'>
											<option value='0' " . (($album['album_watermark'] == 0) ? ("selected='selected'") : ("")) . ">{$this->lang->words['album_dont_watermark']}</option>
											<option value='1' " . (($album['album_watermark'] == 1) ? ("selected='selected'") : ("")) . ">{$this->lang->words['album_apply_watermark']}</option>
										</select>
									</div>
								<li>") : ("")) . "
							<li class='ipsField ipsField_checkbox'>
								<input type='checkbox' value='1' name='album_detail_default' id='album_detail_default' " . (($album['album_detail_default']) ? ("checked='checked'") : ("")) . " />
								<label for='album_detail_default'>
									<p class='class='ipsField_content'>
										&nbsp;{$this->lang->words['album_detail_default']}
									<p>
								</label>
							</li>
						</ul>
					</div>
					
					<div class='ipsLayout_right'>
						<ul class='ipsForm ipsForm_vertical'>
							<li class='ipsField'>
								<label class='ipsField_title'>{$this->lang->words['review_desc']}</label>
								<p class='ipsField_content'>
									<textarea name=\"album_description\" style=\"width:98%; height:100px\">{$album['album_description']}</textarea>
								</p>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<fieldset class='submit'>
			<input type='submit' value='{$this->lang->words['review_finish_' . $type ]}' class='input_submit' />
		</fieldset>
		<br />
		
		" . ((is_array($images) && count($images)) ? ("
			<h2 class='ipsType_subtitle'>{$this->lang->words['review_title_uploads']}</h2>
		") : ("")) . "")) . "
	
	" . ((is_array($images) && count($images)) ? ("
		<br />
		<div class='ipsBox'>
			".$this->__f__d25c3f123b5f5961f5f21b8e61fa394f($images,$album,$type,$sessionKey)."		</div>
		<fieldset class='submit'>
			<input type='submit' value='{$this->lang->words['review_finish_' . $type ]}' class='input_submit' />
		</fieldset>
	") : ("")) . "
</form>
<div id=\"templates-mediaupload\" style=\"display:none\">
	<h3>{$this->lang->words['review_upload_thumb']}</h3>
	<div class='ipsBox short'>
	 	<div id='mt_attachments_#{id}' style='width:400px;height:50px;'></div>
	 	<div id='mtErrorBox_#{id}' style='display:none' class='message error'>&nbsp;</div>
		<input type='button' id='mt_add_files_#{id}' class='input_submit' value='{$this->lang->words['media_save_thumb']}' tabindex='1' />
	</div>
</div>
<script type=\"text/javascript\">
//<![CDATA[
	document.observe(\"dom:loaded\", function(){
		ipb.gallery.setUpReviewPage();
	} );
//]]>
</script>";
return $IPBHTML;
}


function __f__58ec2602f3116ec9badc55288d15dd5e($images, $album, $type, $sessionKey)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( array('idate','views','comments','rating' ) as $field )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
											<option value=\"{$field}\" " . (($album['album_sort_options__key'] == $field) ? ("selected='selected'") : ("")) . ">{$this->lang->words['album_sort_'.$field]}</option>
										
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

function __f__26792805ce01ab8bd9e266cebdd2c823($images, $album, $type, $sessionKey)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( array('asc', 'desc' ) as $field )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
											<option value=\"{$field}\" " . (($album['album_sort_options__dir'] == $field) ? ("selected='selected'") : ("")) . ">{$this->lang->words['album_sort_'.$field]}</option>
										
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

function __f__d25c3f123b5f5961f5f21b8e61fa394f($images, $album, $type, $sessionKey)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $images as $id => $image )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
				<input type='hidden' class='_imgIds _x{$id}' name='imageIds[{$id}]' value=\"$id\">
				<div class='ipsBox_container ipsPad'>
					<div class='ipsLayout ipsLayout_withleft ipsLayout_smallleft'>
						<div class='ipsLayout_left'>
							{$image['thumb']}
							<p class='ipsPad desc'>
								" . (($image['_isMedia']) ? ("<input type=\"button\" class=\"ipsButton_secondary media_thumb_pop\" style='margin-left:5px' value=\"{$this->lang->words['media_add_thumb']}\" media-has-thumb=\"" . (($image['media_thumb']) ? ("true") : ("false")) . "\" media-id=\"{$image['id']}\" />
									<br /><br /><input type='radio' name='makeCover' value='{$image['id']}' " . (($image['_cover']) ? ("checked=\"checked\"") : ("")) . "> &nbsp;{$this->lang->words['review_cover_img']}
									<br /><input type='checkbox' name='delete[{$image['id']}]' value='1'>  &nbsp;{$this->lang->words['review_delete_movie']}") : ("<input type='radio' name='makeCover' value='{$image['id']}' " . (($image['_cover']) ? ("checked=\"checked\"") : ("")) . "> &nbsp;{$this->lang->words['review_cover_img']}
									<br /><input type='checkbox' name='delete[{$image['id']}]' value='1'>  &nbsp;{$this->lang->words['review_delete_img']}
									<br /><span class='rotate _r{$image['id']}'><img src=\"{$this->settings['img_url']}/gallery/rotate90.png\" alt='{$this->lang->words['review_rotate_alt']}' title='{$this->lang->words['review_rotate_alt']}' /> {$this->lang->words['review_rotate']}</span>")) . "
							</p>
						</div>
						<div class='ipsLayout_content'>
							<ul class='ipsForm ipsForm_vertical'>
								<li class='ipsField'>
									<label class='ipsField_title'>{$this->lang->words['title_ucfirst']}</label>
									<p class='ipsField_content'><input type='text' name='title[{$image['id']}]' class='input_text' style='width:95%;' value='{$image['_title']}' /></p>
								</li>
								" . (($image['_tagBox']) ? ("
									<li class='ipsField'>
										<label class='ipsField_title'>{$this->lang->words['review_tags']}</label>
										<div class='ipsField_content'>
										{$image['_tagBox']}
										</div>
									</li>
								") : ("")) . "
								<li class='ipsField'>
									<label class='ipsField_title'>{$this->lang->words['review_desc']}</label>
									<div class='ipsField_content'>
										{$image['editor']}
									</div>
								</li>
								" . (($image['image_gps_lat'] AND $image['image_loc_short']) ? ("<li class='ipsField ipsField_checkbox'>
										<input type='checkbox' id='loc_{$image['id']}' name='locationAllow[{$image['id']}]' " . (($image['image_gps_show']) ? ("checked='checked'") : ("")) . "value='1'>
										<label for='loc_{$image['id']}'>
											<p class='class='ipsField_content'>
												&nbsp;{$this->lang->words['review_show_loc']} <span class='desc'>({$image['image_loc_short']})</span>
											<p>
										</label>
									</li>") : ("")) . "
								<li class='ipsField'>
									<label class='ipsField_title'>{$this->lang->words['copyright_ucfirst']}</label>
									<p class='ipsField_content'><input type='text'name='copyright[{$image['id']}]'  class='input_text' style='width:95%;' value='{$image['copyright']}' /></p>
								</li>
							</ul>
						</div>
						<br class='clear' />
					</div>
				</div>
				<br />
			
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