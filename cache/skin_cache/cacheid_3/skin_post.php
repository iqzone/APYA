<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD 3         */
/* CACHE FILE: Skin set id: 3               */
/* CACHE FILE: Generated: Wed, 29 Aug 2012 14:15:03 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_post_3 extends skinMaster{

/**
* Construct
*/
function __construct( ipsRegistry $registry )
{
	parent::__construct( $registry );
	

$this->_funcHooks = array();
$this->_funcHooks['postFormTemplate'] = array('hazTag','edit_tags_check');


}

/* -- attachiFrame --*/
function attachiFrame($JSON, $id) {
$IPBHTML = "";
$IPBHTML .= "<!-- NoData -->";
return $IPBHTML;
}

/* -- errors --*/
function errors($data="") {
$IPBHTML = "";
$IPBHTML .= "<!-- NoData -->";
return $IPBHTML;
}

/* -- pollBox --*/
function pollBox($data) {
$IPBHTML = "";
$IPBHTML .= "<!-- NoData -->";
return $IPBHTML;
}

/* -- postFormTemplate --*/
function postFormTemplate($formData=array(), $form = array()) {
$IPBHTML = "";
if( IPSLib::locationHasHooks( 'skin_post', $this->_funcHooks['postFormTemplate'] ) )
{
$count_69e17a3b1d3b29eb408c3cc7d4c8fd60 = is_array($this->functionData['postFormTemplate']) ? count($this->functionData['postFormTemplate']) : 0;
$this->functionData['postFormTemplate'][$count_69e17a3b1d3b29eb408c3cc7d4c8fd60]['formData'] = $formData;
$this->functionData['postFormTemplate'][$count_69e17a3b1d3b29eb408c3cc7d4c8fd60]['form'] = $form;
}
$IPBHTML .= "<postingForm>
				" . (($formData['formType'] == 'new' OR ( $formData['formType'] == 'edit')) ? ("" . (($formData['tagBox']) ? ("
{$formData['tagBox']}
					") : ("")) . "") : ("")) . "
	<submitURL><![CDATA[{$this->settings['base_url']}]]></submitURL>
	<st>{$this->request['st']}</st>
	<app>forums</app>
	<module>post</module>
	<section>post</section>
	<do>{$form['doCode']}</do>
	<s>{$this->member->session_id}</s>
	<p>{$form['p']}</p>
	<t>{$form['t']}</t>
	<f>{$form['f']}</f>
	<parent_id>{$form['parent']}</parent_id>
	<attach_post_key>{$form['attach_post_key']}</attach_post_key>
	<auth_key>{$this->member->form_hash}</auth_key>
	<removeattachid>0</removeattachid>
	<return>{$this->request['return']}</return>
	<_from>{$this->request['_from']}</_from>
	{$formData['editor']}
</postingForm>";
return $IPBHTML;
}

/* -- preview --*/
function preview($data="") {
$IPBHTML = "";
$IPBHTML .= "<!-- NoData -->";
return $IPBHTML;
}

/* -- topicSummary --*/
function topicSummary($posts=array()) {
$IPBHTML = "";
$IPBHTML .= "<!-- NoData -->";
return $IPBHTML;
}

/* -- uploadForm --*/
function uploadForm($post_key="",$type="",$stats=array(),$id="",$forum_id=0) {
$IPBHTML = "";
$IPBHTML .= "<!-- NoData -->";
return $IPBHTML;
}


}


/*--------------------------------------------------*/
/* END OF FILE                                      */
/*--------------------------------------------------*/

?>