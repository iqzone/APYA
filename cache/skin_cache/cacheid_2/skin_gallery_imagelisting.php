<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD 3         */
/* CACHE FILE: Skin set id: 2               */
/* CACHE FILE: Generated: Wed, 29 Aug 2012 14:14:59 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_gallery_imagelisting_2 extends skinMaster{

/**
* Construct
*/
function __construct( ipsRegistry $registry )
{
	parent::__construct( $registry );
	

$this->_funcHooks = array();
$this->_funcHooks['review'] = array('cover','cover','isMedia','geo','images','wizzard','isSel0','isSel1','isSel2','isPubicWithAnEl','isEditing');


}

/* -- review --*/
function review($images, $album, $type, $sessionKey) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_gallery_imagelisting', $this->_funcHooks['review'] ) )
{
$count_3a2c908ad5071f2c6e93160b5e40d527 = is_array($this->functionData['review']) ? count($this->functionData['review']) : 0;
$this->functionData['review'][$count_3a2c908ad5071f2c6e93160b5e40d527]['images'] = $images;
$this->functionData['review'][$count_3a2c908ad5071f2c6e93160b5e40d527]['album'] = $album;
$this->functionData['review'][$count_3a2c908ad5071f2c6e93160b5e40d527]['type'] = $type;
$this->functionData['review'][$count_3a2c908ad5071f2c6e93160b5e40d527]['sessionKey'] = $sessionKey;
}
$IPBHTML .= "" . (($type == 'uploads') ? ("
	<ul id=\"wizard_progress\" class=\"row2\">
	<li>{$this->lang->words['review_step_upload']}</li>
	<li class=\"active\">{$this->lang->words['review_step_publish']}</li>
</ul>
") : ("")) . "
" . $this->registry->getClass('output')->addJSModule("editor", "0" ) . "
<script type=\"text/javascript\">
" . ( method_exists( $this->registry->getClass('output')->getTemplate('editors'), 'editorJS' ) ? $this->registry->getClass('output')->getTemplate('editors')->editorJS() : '' ) . "
</script>
<form method=\"post\" id='postingform' action=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&module=images&section=review&do=process&sessionKey={$sessionKey}&album_id={$album['album_id']}&type={$type}", "public",'' ), "", "" ) . "\">
<input type=\"hidden\" name=\"sessionKey\" value=\"{$sessionKey}\" />
<h2 class=\"maintitle\">{$this->lang->words['review_title_' . $type ]}</h2>
<div id='albumBoxWrap'>
	<div id='albumWrap'>
		<input type='submit' value=\"{$this->lang->words['review_finish_' . $type ]}\" class='galleryNextButton' />
		<div id='albumWrap_x'>
			{$album['thumb']}
			" . (($album['_hasCoverSet'] == 'elsewhere') ? ("
				<span><input type='radio' id='keep_cover' name='makeCover' value='0' checked=\"checked\"> <label for=\"keep_cover\">&nbsp;{$this->lang->words['review_cover_img']}</label></span>
			") : ("")) . "
			" . (($type == 'uploads') ? ("
				<h4>Selected Album: {$album['album_name']}</h4>
				<p class='desc'>" . sprintf( $this->lang->words['review_img_comm'], $album['album_count_imgs'], $album['album_count_comments'] ) . "</p>
			") : ("<h4>
					<input type=\"text\" name=\"album_name\" style=\"width:250px\" class='input_text' value=\"{$album['album_name']}\" />
					" . (($this->registry->gallery->helper('albums')->isGlobal( $album ) !== true) ? ("<select name=\"album_is_public\">
							<option value=\"0\" " . (($album['album_is_public'] == 0) ? ("selected='selected'") : ("")) . ">{$this->lang->words['private_album']}</option>
							<option value=\"1\" " . (($album['album_is_public'] == 1) ? ("selected='selected'") : ("")) . ">{$this->lang->words['public_album']}</option>
							<option value=\"2\" " . (($album['album_is_public'] == 2) ? ("selected='selected'") : ("")) . ">{$this->lang->words['friend_album']}</option>
						</select>") : ("")) . "
					<br />
					<select style='margin-left:-2px; margin-top:8px' name='album_parent_id'>" . $this->registry->gallery->helper('albums')->getOptionTags( 0, array( 'skip' => $album['album_id'], 'isUploadable' => true, 'selected' => $album['album_parent_id'], 'skipChildrenOfSelected' => true ) ) . "</select>				
				</h4>
				<p class='desc' style='margin-top:3px'>
					{$this->lang->words['review_desc']}<br />
					<textarea name=\"album_description\" style=\"width:254px; height:50px\">{$album['album_description']}</textarea>
				</p>")) . "
		</div>
	</div>
</div>
<div class=\"ipsPad row2 altrow\">
	".$this->__f__af6a5b99b943bd0cf0f820f37fb835aa($images,$album,$type,$sessionKey)."	<div style='height:37px'>
		<input type='submit' value=\"{$this->lang->words['review_finish_' . $type ]}\" class='galleryNextButton' />
	</div>
</div>
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


function __f__af6a5b99b943bd0cf0f820f37fb835aa($images, $album, $type, $sessionKey)
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $images as $id => $image )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
		<input type='hidden' class='_imgIds _x{$id}' name='imageIds[{$id}]' value=\"$id\">
		<div class=\"block_inner clearfix pad review_row\">
			<div class='left' id='image_thumb_wrap_{$id}'>
				{$image['thumb']}
				<p class='desc'>
					" . (($image['_isMedia']) ? ("<input type=\"button\" class=\"input_submit media_thumb_pop\" style='margin-left:5px' value=\"{$this->lang->words['media_add_thumb']}\" media-has-thumb=\"" . (($image['media_thumb']) ? ("true") : ("false")) . "\" media-id=\"{$image['id']}\" />
						<br /><br /><input type='radio' name='makeCover' value='{$image['id']}' " . (($image['_cover'] && $album['_hasCoverSet'] == 'inline') ? ("checked=\"checked\"") : ("")) . "> &nbsp;Cover Image
						<br /><input type='checkbox' name='delete[{$image['id']}]' value='1'>  &nbsp;Delete Movie") : ("<input type='radio' name='makeCover' value='{$image['id']}' " . (($image['_cover'] && $album['_hasCoverSet'] == 'inline') ? ("checked=\"checked\"") : ("")) . "> &nbsp;Cover Image
						<br /><input type='checkbox' name='delete[{$image['id']}]' value='1'>  &nbsp;Delete Image
						<br /><span class='rotate _r{$image['id']}'><img src=\"{$this->settings['img_url']}/gallery/rotate90.png\" alt='Rotate 90 degrees' title='Rotate 90 Degrees' /> Rotate Image</span>")) . "
				</p>
			</div>
			<div class='block_right review_row_right'>
				<table class='ipb_table'>
					<tr>
						<td style='width: 15%; text-align: right'>
							{$this->lang->words['title_ucfirst']}:
						</td>
						<td style='width: 85%; text-align: left; padding:14px'>
							<input type='text' name='title[{$image['id']}]' class='input_text' style='width:95%;' value='{$image['_title']}' />
						</td>
					</tr>
					<tr>
						<td style='width: 15%; text-align: right; vertical-align:top;'>
							Description:
						</td>
						<td style='width: 85%'>
							" . ( method_exists( $this->registry->getClass('output')->getTemplate('editors'), 'editorShell' ) ? $this->registry->getClass('output')->getTemplate('editors')->editorShell('fast-reply-' . $image['id'], 'description_' . $image['id'], $image['_description'], 1, 1) : '' ) . "
							" . (($image['image_gps_lat'] AND $image['image_loc_short']) ? ("
								<div style='padding-top:6px; padding-left:8px;'><input type='checkbox' id='loc_{$image['id']}' name='locationAllow[{$image['id']}]' value='1'> <label for=\"loc_{$image['id']}\">Display image map and location <span class='desc'>({$image['image_loc_short']})</span></label></div>
							") : ("")) . "
						</td>
					</tr>
					<tr>
						<td style='width: 15%; text-align: right' class='last'>
							{$this->lang->words['copyright_ucfirst']}:
						</td>
						<td style='width: 85%; text-align: left; padding:14px' class='last'>
							<input type='text'name='copyright[{$image['id']}]'  class='input_text' style='width:95%;' value='{$image['_copyright']}' />
						</td>
					</tr>
				</table>
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