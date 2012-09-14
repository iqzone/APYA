<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD 3         */
/* CACHE FILE: Skin set id: 4               */
/* CACHE FILE: Generated: Wed, 29 Aug 2012 14:15:07 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_blog_post_4 extends skinMaster{

/**
* Construct
*/
function __construct( ipsRegistry $registry )
{
	parent::__construct( $registry );
	

$this->_funcHooks = array();
$this->_funcHooks['blogPostForm'] = array('needsddselected','needsddloop','noHardcodeBlogid','entryhiddensloop','hasCategories','arewebt','hasTags','needsdd','hasAlbums','canCreateAlbums','galleryIsInstalled','upload_form_check','buttonsAreTooLong','enterpoll','entryhtml','entryhiddens','cancelToEntry','cancelToPost');
$this->_funcHooks['cBlockForm'] = array('cblockhiddensloop','hasHtmlOptions','upload_form_check','cblockhidden');


}

/* -- blogPostForm --*/
function blogPostForm($form_hiddens=array(), $form_title="", $button="", $date=array(), $title=array(), $albums="", $mod_options="", $editor="", $captcha="", $poll_box="", $upload_field='', $html_status=array(), $canPublish=0, $blogsdd='', $_extraData=array(), $tagBox='', $image='') {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_post', $this->_funcHooks['blogPostForm'] ) )
{
$count_bbe867371069663e6654e1f967e66663 = is_array($this->functionData['blogPostForm']) ? count($this->functionData['blogPostForm']) : 0;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['form_hiddens'] = $form_hiddens;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['form_title'] = $form_title;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['button'] = $button;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['date'] = $date;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['title'] = $title;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['albums'] = $albums;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['mod_options'] = $mod_options;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['editor'] = $editor;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['captcha'] = $captcha;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['poll_box'] = $poll_box;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['upload_field'] = $upload_field;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['html_status'] = $html_status;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['canPublish'] = $canPublish;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['blogsdd'] = $blogsdd;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['_extraData'] = $_extraData;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['tagBox'] = $tagBox;
$this->functionData['blogPostForm'][$count_bbe867371069663e6654e1f967e66663]['image'] = $image;
}
$IPBHTML .= "" . $this->registry->getClass('output')->addJSModule("blog", "0" ) . "
<script type='text/javascript'>
	ipb.blog.maxCats = " . intval($this->settings['blog_max_cats']) . ";
	ipb.blog.canPostDraft = 1;
	ipb.blog.canPublish = " . intval($canPublish) . ";
	ipb.blog.defStatus  = \"{$this->blog['blog_settings']['defaultstatus']}\";
	ipb.blog.defStatusGlobal = \"{$this->settings['blog_entry_defaultstatus']}\";
	ipb.templates['cat_entry'] = new Template(\"<li><input type='checkbox' value='1' name='catCheckBoxes[#{cid}]' id='cat_#{cid}'>&nbsp; #{cat}<input type='hidden' name='catNames[#{cid}]' value='#{cat}'></li>\");
	ipb.blog.currentCats = \$H(
		" . (($title['CURRENTCATS'] != '[]') ? ("{$title['CURRENTCATS']}") : ("")) . "
	);
</script>
<br />
<!-- Blog this warning? -->
" . (($this->settings['blog_allow_bthis'] AND $this->request['btapp'] AND $this->request['id1']) ? ("
	<div class='message'>
		<h3>{$this->lang->words['blog_post_bt_title']}</h3>
		<p>{$this->lang->words['blog_post_bt']}</p>
	</div>
	<br />
") : ("")) . "
<form id='postingform' action='{$this->settings['base_url']}' method='post' name='postingform' enctype='multipart/form-data'>
	<div class='ipsBox ipsForm_vertical ipsLayout ipsLayout_withright ipsPostForm clearfix'>
	    
		<!-- Left Column -->
		<div class='ipsBox_container ipsLayout_content'>
			<ul class='ipsForm ipsForm_vertical ipsPad'>
				<li class='ipsField ipsField_primary'>
					<label for='entry_title' class='ipsField_title'>{$this->lang->words['entry_title']}</label>
					<p class='ipsField_content'>
						<input id='entry_title' class='input_text' type=\"text\" size=\"36\" maxlength=\"150\" name=\"EntryTitle\" value=\"{$title['TITLE']}\" tabindex=\"1\" />
					</p>
				</li>
				" . (($tagBox) ? ("
					<li class='ipsField tag_field'>
						<label for='blogTags' class='ipsField_title'>{$this->lang->words['entry_tags']}</label>
						<p class='ipsField_content'>
							{$tagBox}
						</p>
					</li>
				") : ("")) . "
				<li class='ipsField'>
					<label for='entry_title' class='ipsField_title'>{$this->lang->words['entry_image']}</label>
					<p class='ipsField_content'>
						" . (($image) ? ("
							<div id='image_preview' style='text-align: center; width: 100px'>
								<img src='{$this->settings['upload_url']}/thumb_{$image}' /><br />
								<a class='clickable' onclick='ipb.blog.changeImage()'>{$this->lang->words['entry_image_change']}</a>
							</div>
							<input type='hidden' id='image_change' name='image_change' value='0' />
						") : ("
							<input type='hidden' id='image_change' name='image_change' value='1' />
						")) . "
						<input type='file' name='entry_image' id='image_field' " . (($image) ? ("style='display:none'") : ("")) . " />
					</p>
				</li>
				" . ((is_array($blogsdd) AND count($blogsdd)) ? ("
					<li class='ipsField ipsField_select'>
						<label for='blog_chooser' class='ipsField_title'>{$this->lang->words['bpost_addtoblog']}</label>
						<p class='ipsField_content'>
							<select name='blogid' id='blog_chooser'>
								".$this->__f__c01b69731b004f4b1c5507d5793cb3f9($form_hiddens,$form_title,$button,$date,$title,$albums,$mod_options,$editor,$captcha,$poll_box,$upload_field,$html_status,$canPublish,$blogsdd,$_extraData,$tagBox,$image)."							</select>
						</p>
					</li>
				") : ("")) . "
				" . ((!empty($albums) && ( !empty($albums['dropdown']) || $albums['canCreate'] )) ? ("<li class='ipsField ipsField_select'>
						<label for='entry_gallery_album' class='ipsField_title'>{$this->lang->words['entry_albums']}</label>
						<p class='ipsField_content'>
							" . ((!empty($albums['dropdown'])) ? ("{$albums['dropdown']}") : ("{$this->lang->words['entry_no_albums_yet']}")) . "" . (($albums['canCreate']) ? ("&nbsp;&nbsp;&nbsp;<a class='ipsType_smaller' href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=gallery&amp;module=post&amp;section=image", "public",'' ), "", "" ) . "\" target='_blank'>{$this->lang->words['entry_add_album']}</a>") : ("")) . "
						</p>
					</li>") : ("")) . "
				<li class='ipsField ipsField_editor'>
					{$editor}
				</li>
			</ul>
			" . (($upload_field) ? ("
				<fieldset class='attachments'>
					{$upload_field}
				</fieldset>
			") : ("")) . "
		</div>
		<!--cambio de columna-->
		
		<div class='ipsBox_container ipsLayout_right ipsPostForm_sidebar'>
			<!--div class='ipsPostForm_sidebar_block'>
				<h3 class='bar'>{$this->lang->words['bpost_publish']}</h3>
				<ul class='ipsPad ipsForm ipsForm_vertical ipsType_small short' id='bf_timeOpts'>
					<li class='ipsField ipsField_select'>
						<select name='entry_day' id='date'>{$date['days']}</select> 
						<select name='entry_month'>{$date['months']}</select>
					</li>
					<li class='ipsField ipsField_select'>
						<input type='text' name='entry_time' class='inputtext' size='5' value='{$date['hour']}:{$date['minute']}' /> (HH:MM) <select name='entry_year'>{$date['years']}</select>
					</li>
					<li class='ipsField' id='bf_timeCancel' style='display:none;'>
						<a href='javascript:void(0);' data-clicklaunch=\"pfTimeToggle\" data-scope=\"blog\"><img src='{style_image_url}/delete.png' alt='{$this->lang->words['cancel']}' /> {$this->lang->words['cancel']}</a>
					</li>
				</ul>
				<ul class='ipsPad ipsForm ipsForm_vertical ipsType_small'>
					<li class='ipsField short ipsPad' id='bf_timeToggle' style='display:none;'><img src='{style_image_url}/time.png' alt='' /> <a href='javascript:void(0);' data-clicklaunch=\"pfTimeToggle\" data-scope=\"blog\">{$this->lang->words['bpost_timeopt']}</a></li>
					<li class='ipsField' id='bf_modWrapper'>
						<label for=''><strong>{$this->lang->words['blog_save_entry_as']}</strong></label>
						{$mod_options}</select>
					</li>
					<li class='ipsField short'>
						<span class='input_submit alt clickable' id='bf_draft'>{$this->lang->words['bpost_draft']}</span>" . ((strlen($this->lang->words['bpost_draft'].$this->lang->words['bpost_publish']) < 30) ? ("&nbsp;&nbsp;&nbsp;&nbsp;") : ("<br /><br />")) . "<span class='input_submit important clickable' id='bf_publish'>{$this->lang->words['bpost_publish']}</span>
					</li>
				</ul>
			</div-->
	    	" . (($poll_box) ? ("
				<!--div class='ipsPostForm_sidebar_block'>
					<h3 class='bar'>{$this->lang->words['entry_poll_h3']}</h3>
					<fieldset id='poll_fieldset' class='ipsPad' style='display: none'>
						{$poll_box}
					</fieldset>
					<script type='text/javascript'>
						$('poll_fieldset').show();
					</script>
				</div-->
			") : ("")) . "
			<div class='ipsPostForm_sidebar_block'>
				<h3 class='barTitle'>{$this->lang->words['entry_categories']}</h3>
				<ul id='formCats' class='ipsPad ipsForm ipsForm_vertical ipsType_small'></ul>
				<ul class='ipsPad'>
					<li class='short' id='categoryAddToggle'><a href='javascript:void(0);' class='ipsType_smaller' onclick=\"Event.stop(event); $('formCatAdd').toggle();\">{$this->lang->words['form_add_cat']}</a></li>
				</ul>
				<ul class='ipsPad ipsForm ipsForm_vertical ipsType_small' id='formCatAdd' style='display:none'>
					<li class='ipsField short'>
						<input class='input_text' type='text' id='formCatAddInput' value='' maxlength='32' />&nbsp;&nbsp;<span class='clickable' data-clicklaunch=\"formAddCat\" data-scope=\"blog\">" . $this->registry->getClass('output')->getReplacement("add_poll_choice") . "</span>
					</li>
				</ul>
				<script type='text/javascript'>
					ipb.blog.formInitCats();
				</script>
			</div>
			" . ((is_array( $html_status ) && count( $html_status )) ? ("
				<div class='ipsPostForm_sidebar_block'>
					<h3 class='bar'>{$this->lang->words['post_options']}</h3>
					<ul class='ipsPad ipsForm ipsForm_vertical ipsType_small'>
						<li class='ipsField'>
							<select name=\"post_htmlstatus\" class=\"input_select\" id='post_htmlstatus'>
								<option value=\"0\"{$html_status[0]}>{$this->lang->words['bp_html_off']}</option>
								<option value=\"1\"{$html_status[1]}>{$this->lang->words['bp_html1']}</option>
								<option value=\"2\"{$html_status[2]}>{$this->lang->words['bp_html2']}</option>
							</select>
						</li>
					</ul>
				</div>
			") : ("")) . "
		</div>
		
	</div>	
	<fieldset class='submit clear'>
		<input type='hidden' name='s' value='{$this->member->session_id}' />
		<input type='hidden' name='auth_key' value='{$this->member->form_hash}' />
		<input type='hidden' name='removeattachid' value='0' />
		<input type='hidden' name='app' value='blog' />
		<input type='hidden' name='module' value='post' />
		<input type='hidden' name='section' value='post' />
		<input type=\"hidden\" name=\"enableemo\" id='enableemo' value=\"yes\" />
		
		" . ((is_array( $form_hiddens ) && count( $form_hiddens )) ? ("
			".$this->__f__1ee34be51a35f4a49e0b9234e143fbb5($form_hiddens,$form_title,$button,$date,$title,$albums,$mod_options,$editor,$captcha,$poll_box,$upload_field,$html_status,$canPublish,$blogsdd,$_extraData,$tagBox,$image)."		") : ("")) . "
		
		<input type=\"submit\" name=\"dosubmit\" value=\"{$button}\" tabindex=\"0\" id='bfs_submit' class=\"input_submit\" accesskey=\"s\"  />&nbsp;
		<input type=\"submit\" name=\"preview\" value=\"{$this->lang->words['button_preview']}\" tabindex=\"0\" class=\"input_submit alt\" />
		{$this->lang->words['or']} <a href='" . ((!empty($_extraData['bt_topicData']) && !empty($this->request['id2'])) ? ("" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showtopic={$_extraData['bt_topicData']['tid']}&amp;view=findpost&amp;p={$this->request['id2']}", "public",'' ), "{$_extraData['bt_topicData']['title_seo']}", "showtopic" ) . "") : ("" . (($this->request['eid']) ? ("" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->request['blogid']}&amp;showentry={$this->request['eid']}", "public",'' ), "", "" ) . "") : ("" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$this->request['blogid']}", "public",'' ), "", "" ) . "")) . "")) . "' title='{$this->lang->words['cancel']}' class='cancel'>{$this->lang->words['cancel']}</a>
	</fieldset>
</form>
<script type='text/javascript'>
	ipb.blog.initPostForm();
</script>";
return $IPBHTML;
}


function __f__c01b69731b004f4b1c5507d5793cb3f9($form_hiddens=array(), $form_title="", $button="", $date=array(), $title=array(), $albums="", $mod_options="", $editor="", $captcha="", $poll_box="", $upload_field='', $html_status=array(), $canPublish=0, $blogsdd='', $_extraData=array(), $tagBox='', $image='')
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $blogsdd as $bid => $bdata )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
									<option value=\"{$bdata['blog_id']}\" " . (($bid == $this->request['blogid']) ? ("selected='selected'") : ("")) . ">{$bdata['blog_name']}</option>
								
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

function __f__1ee34be51a35f4a49e0b9234e143fbb5($form_hiddens=array(), $form_title="", $button="", $date=array(), $title=array(), $albums="", $mod_options="", $editor="", $captcha="", $poll_box="", $upload_field='', $html_status=array(), $canPublish=0, $blogsdd='', $_extraData=array(), $tagBox='', $image='')
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $form_hiddens as $hidden )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
				" . (($hidden[0] != 'blogid' OR !is_array($blogsdd) OR !count($blogsdd)) ? ("
					<input type='hidden' name='{$hidden[0]}' value='{$hidden[1]}' />
				") : ("")) . "
			
";
	}
	$_ips___x_retval .= '';
	unset( $__iteratorCount );
	return $_ips___x_retval;
}

/* -- cBlockForm --*/
function cBlockForm($form_hiddens=array(), $form_title='', $button='', $cbtitle='', $editor='', $html_status=array(), $upload_field='', $blogData=array()) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_blog_post', $this->_funcHooks['cBlockForm'] ) )
{
$count_9855d811ef6e3de145fda16429f169de = is_array($this->functionData['cBlockForm']) ? count($this->functionData['cBlockForm']) : 0;
$this->functionData['cBlockForm'][$count_9855d811ef6e3de145fda16429f169de]['form_hiddens'] = $form_hiddens;
$this->functionData['cBlockForm'][$count_9855d811ef6e3de145fda16429f169de]['form_title'] = $form_title;
$this->functionData['cBlockForm'][$count_9855d811ef6e3de145fda16429f169de]['button'] = $button;
$this->functionData['cBlockForm'][$count_9855d811ef6e3de145fda16429f169de]['cbtitle'] = $cbtitle;
$this->functionData['cBlockForm'][$count_9855d811ef6e3de145fda16429f169de]['editor'] = $editor;
$this->functionData['cBlockForm'][$count_9855d811ef6e3de145fda16429f169de]['html_status'] = $html_status;
$this->functionData['cBlockForm'][$count_9855d811ef6e3de145fda16429f169de]['upload_field'] = $upload_field;
$this->functionData['cBlockForm'][$count_9855d811ef6e3de145fda16429f169de]['blogData'] = $blogData;
}
$IPBHTML .= "<br />
<form id='postingform' action='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=post&amp;section=post", "public",'' ), "", "" ) . "' method='post' enctype='multipart/form-data'>
	<div class='ipsBox ipsForm_horizontal ipsPostForm clearfix'>
		<div class='ipsBox_container'>
			<ul class='ipsForm ipsForm_vertical ipsPad'>
				<li class='ipsField'>
					<label for='entry_title' class='ipsField_title'>{$this->lang->words['blog_cblock_name']}</label>
					<p class='ipsField_content'>
						<input type='text' size='50' maxlength='50' name='CBlockTitle' value='{$cbtitle}' tabindex='0' class='input_text' />
					</p>
				</li>
				" . ((is_array($html_status) && count($html_status)) ? ("
					<li class='ipsField ipsField_select'>
						<label for='post_htmlstatus' class='ipsField_title'>{$this->lang->words['html_posting']}</label>
						<p class='ipsField_content'>
							<select name='post_htmlstatus' class='input_select' id='post_htmlstatus'>
								<option value=\"0\"{$html_status[0]}>{$this->lang->words['bp_html_off']}</option>
								<option value=\"1\"{$html_status[1]}>{$this->lang->words['bp_html1']}</option>
								<option value=\"2\"{$html_status[2]}>{$this->lang->words['bp_html2']}</option>
							</select>
						</p>
					</li>
				") : ("")) . "
				<li class='ipsField ipsField_editor'>
					{$editor}
				</li>
			</ul>
			" . (($upload_field) ? ("
				<fieldset class='attachments'>
					{$upload_field}
				</fieldset>
			") : ("")) . "
		</div>
	</div>
	<fieldset class='submit clear'>
		<input type='hidden' name='s' value='{$this->member->session_id}' />
		<input type='hidden' name='auth_key' value='{$this->member->form_hash}' />
		<input type='hidden' name='removeattachid' value='0' />
		" . ((is_array( $form_hiddens ) && count( $form_hiddens )) ? ("
			".$this->__f__dce7cafb1f533a3e9e6e043e7024e92e($form_hiddens,$form_title,$button,$cbtitle,$editor,$html_status,$upload_field,$blogData)."		") : ("")) . "
		
		<input type=\"submit\" name=\"dosubmit\" value=\"{$button}\" tabindex=\"0\" class=\"input_submit\" accesskey=\"s\" />
		<input type=\"submit\" name=\"preview\" value=\"{$this->lang->words['preview_block']}\" tabindex=\"0\" class=\"input_submit alt\" />
		{$this->lang->words['or']} <a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=blog&amp;module=display&amp;section=blog&amp;blogid={$blogData['blog_id']}", "public",'' ), "{$blogData['blog_seo_name']}", "showblog" ) . "' title='{$this->lang->words['cancel']}' class='cancel'>{$this->lang->words['cancel']}</a>		
	</fieldset>
</form>";
return $IPBHTML;
}


function __f__dce7cafb1f533a3e9e6e043e7024e92e($form_hiddens=array(), $form_title='', $button='', $cbtitle='', $editor='', $html_status=array(), $upload_field='', $blogData=array())
{
	$_ips___x_retval = '';
	$__iteratorCount = 0;
	foreach( $form_hiddens as $v )
	{
		
		$__iteratorCount++;
		$_ips___x_retval .= "
				<input type='hidden' name='{$v[0]}' value='{$v[1]}' />
			
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