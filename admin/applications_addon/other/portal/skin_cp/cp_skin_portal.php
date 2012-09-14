<?php
/*
+--------------------------------------------------------------------------
|   Portal 1.1.0
|   =============================================
|   by Michael John
|   Copyright 2011-2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
|   Based on IP.Board Portal by Invision Power Services
|   Website - http://www.invisionpower.com/
+--------------------------------------------------------------------------
*/
 
class cp_skin_portal extends output 
{

/**
 * Prevent our main destructor being called by this class
 *
 * @access	public
 * @return	void
 */
public function __destruct()
{
}

/**
 * Portal tag details
 *
 * @access	public
 * @param	string		Page title
 * @param	array 		Available tags
 * @return	string		HTML
 */
public function portal_pop_overview( $title, $tags ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='section_title'>
    <h2>{$this->lang->words['main_title']}</h2>
        <div class='ipsActionBar clearfix'>
            <ul>
                <li class='ipsActionButton'><a href='{$this->settings['base_url']}'><img src='{$this->settings['skin_acp_url']}/images/icons/plugin.png' alt='' /> {$this->lang->words['portal_main_title']}</a></li>
                <li class='ipsActionButton'><a href='{$this->settings['base_url']}&amp;app=core&amp;module=settings&amp;section=settings&amp;do=setting_view&amp;conf_title_keyword=portal'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> {$this->lang->words['portal_settings']}</a></li>
             </ul>
        </div>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['portal_pop_tags']} {$title}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['portal_pop_name']}</th>
			<th width='70%'>{$this->lang->words['portal_pop_desc']}</th>
		</tr>
EOF;

if ( is_array( $tags ) AND count( $tags ) )
{
	foreach( $tags as $tag => $tag_data )
	{
		$IPBHTML .= <<<EOF
		<tr>
			<td>&lt;!--::<strong>{$tag}</strong>::--&gt;</td>
			<td><div class='desctext'>{$tag_data[1]}</td>
		</tr>
EOF;
	}
}
		
$IPBHTML .= <<<EOF
	</table>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Portal overview
 *
 * @access	public
 * @param	array 		Available portal objects
 * @return	string		HTML
 */
public function portal_overview( $objects ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='section_title'>
    <h2>{$this->lang->words['main_title']}</h2>
        <div class='ipsActionBar clearfix'>
            <ul>
                <li class='ipsActionButton'><a href='{$this->settings['base_url']}'><img src='{$this->settings['skin_acp_url']}/images/icons/plugin.png' alt='' /> {$this->lang->words['portal_main_title']}</a></li>
                <li class='ipsActionButton'><a href='{$this->settings['base_url']}&amp;app=core&amp;module=settings&amp;section=settings&amp;do=setting_view&amp;conf_title_keyword=portal'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' alt='' /> {$this->lang->words['portal_settings']}</a></li>
             </ul>
        </div>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['portal_main_title']}</h3>
	
	<table class='ipsTable'>
		<tr>
			<th>{$this->lang->words['portal_main_key']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
EOF;

if ( is_array( $objects ) AND count( $objects ) )
{
	foreach( $objects as $key => $data )
	{
		$IPBHTML .= <<<EOF
		<tr class='ipsControlRow'>
			<td><strong>{$data['pc_title']}</strong><div class='desctext'>{$data['pc_desc']} - {$data['pc_settings_keyword']}</div></td>
            <td>
                <ul class='ipsControlStrip'>                
EOF;
//startif
if ( $data['pc_settings_keyword'] )
{
$IPBHTML .= <<<EOF
					<li class='i_cog'><a href='{$this->settings['base_url']}&amp;app=core&amp;module=settings&amp;section=settings&amp;do=setting_view&amp;conf_title_keyword={$data['pc_settings_keyword']}' title='{$this->lang->words['plugin_settings']}'>{$this->lang->words['plugin_settings']}</a></li>
EOF;
}//endif
$IPBHTML .= <<<EOF
           
                    <li class='i_view'><a href='{$this->settings['base_url']}&{$this->form_code}&amp;do=portal_viewtags&amp;pc_key={$data['pc_key']}' title='{$this->lang->words['view_template_tag']}'>{$this->lang->words['view_template_tag']}</a></li>
                </ul>
            </td>            
		</tr>
EOF;
	}
}
		
$IPBHTML .= <<<EOF
	</table>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}


}