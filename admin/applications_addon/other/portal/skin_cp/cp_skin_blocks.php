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

class cp_skin_blocks extends output
{

function __destruct()
{
}

public function blockForm( $form, $formFields, $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['custom_blocks']}</h2>
</div>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$form['url']}&amp;id={$data['block_id']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />

<div class='acp-box'>
	<h3>{$form['title']}</h3>
		<table class='ipsTable double_pad'>
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['block_title']}</strong></td>
                <td class='field_field'>{$formFields['title']}</td>
            </tr>
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['block_position']}</strong></td>
                <td class='field_field'>{$formFields['align']}</td>
            </tr>
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['block_template']}</strong></td>
                <td class='field_field'>{$formFields['template']}<br /><span class='desctext'>{$this->lang->words['block_template_desc']}</span></td>
            </tr>                        
            <tr>
                <td class='field_title'><strong class='title'>{$this->lang->words['block_code']}</strong></td>
                <td class='field_field'>{$formFields['block_code']}<br /><span class='desctext'>{$this->lang->words['block_code_desc']}</span></td>
            </tr>             
        </table>                   
	
	<div class='acp-actionbar'>
			<input type='submit' value=' {$form['button']} ' class='button primary' />
	</div>
	</div>	
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

public function blocksOverview( $leftBlocks, $mainBlocks, $rightBlocks ) {

$IPBHTML = "";

//--starthtml--//
$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.portal.js'></script>

<div class='section_title'>
	<h2>{$this->lang->words['custom_blocks']}</h2>
        <div class='ipsActionBar clearfix'>
            <ul>
                <li class='ipsActionButton'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=block_add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['add_block']}</a></li>
             </ul>
        </div>    
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['custom_blocks']}</h3>
    
	<div id='tabstrip_customBlocks' class='ipsTabBar with_left with_right'>
		<span class='tab_left'>&laquo;</span>
		<span class='tab_right'>&raquo;</span>
		<ul>
			<li id='tab_left'>{$this->lang->words['left_blocks']}</li>
			<li id='tab_main'>{$this->lang->words['main_blocks']}</li>
			<li id='tab_right'>{$this->lang->words['right_blocks']}</li>            
		</ul>
	</div>    
    
   	<div id='tabstrip_customBlocks_content' class='ipsTabBar_content'>

HTML;

if( is_array($leftBlocks) AND count($leftBlocks) )
{ 
$IPBHTML .= <<<HTML
<div id='tab_left_content'>  
    <table class='ipsTable' id='leftblock_list'>
HTML;

foreach( $leftBlocks as $r )
{
	
$IPBHTML .= <<<HTML
		<tr id='blocks_{$r['block_id']}' class='ipsControlRow isDraggable'>
			<td class='col_drag'>
				<div class='draghandle'>&nbsp;</div>
			</td>        
			<td><strong>{$r['title']}</strong></td>
            <td>
                <ul class='ipsControlStrip'>           
                    <li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=block_edit&amp;id={$r['block_id']}' title='{$this->lang->words['edit_block']}'>{$this->lang->words['edit_block']}</a></li>
                	<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=block_delete&amp;id={$r['block_id']}");' title='{$this->lang->words['delete_block']}'>{$this->lang->words['delete_block']}</a></li>
			        <li class='ipsControlStrip_more ipbmenu' id='menu_{$r['block_id']}'>
						<a href='#'>{$this->lang->words['more']}</a>
					</li>
                </ul> 
			    <ul class='acp-menu' id='menu_{$r['block_id']}_menucontent' style='display: none'>
					<li class='icon view'><a href='#' id='PM__view{$r['block_id']}'>{$this->lang->words['view_block']}</a></li>
				</ul>                                
            </td>
		</tr>
		<script type='text/javascript'>
			$('PM__view{$r['block_id']}').observe('click', acp.portal.viewBlock.bindAsEventListener( this, "app=portal&amp;module=ajax&amp;section=view&amp;do=view_block&amp;id={$r['block_id']}" ) );
		</script>         
HTML;
}

$IPBHTML .= <<<HTML
    </table>
</div>

<script type='text/javascript'>
	jQ("#leftblock_list").ipsSortable('table', { 
		url: "{$this->settings['base_url']}{$this->form_code_js}&do=block_move&id={$r['block_id']}&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>
HTML;
}
else
{
	
$IPBHTML .= <<<HTML
<div id='tab_left_content'>  
    <table class='ipsTable'>
		<tr>
			<td class='no_messages'>
                {$this->lang->words['no_custom_blocks']} <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=block_add' class='mini_button'>{$this->lang->words['add_block']}</a>				
			</td>
		</tr>
    </table>
</div>        
HTML;
}

if( is_array($mainBlocks) AND count($mainBlocks) )
{ 
$IPBHTML .= <<<HTML
<div id='tab_main_content'>  
    <table class='ipsTable' id='mainblock_list'>
HTML;

foreach( $mainBlocks as $r )
{
	
$IPBHTML .= <<<HTML
		<tr id='blocks_{$r['block_id']}' class='ipsControlRow isDraggable'>
			<td class='col_drag'>
				<div class='draghandle'>&nbsp;</div>
			</td>        
			<td><strong>{$r['title']}</strong></td>
            <td>
                <ul class='ipsControlStrip'>           
                    <li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=block_edit&amp;id={$r['block_id']}' title='{$this->lang->words['edit_block']}'>{$this->lang->words['edit_block']}</a></li>
                	<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=block_delete&amp;id={$r['block_id']}");' title='{$this->lang->words['delete_block']}'>{$this->lang->words['delete_block']}</a></li>
			        <li class='ipsControlStrip_more ipbmenu' id='menu_{$r['block_id']}'>
						<a href='#'>{$this->lang->words['more']}</a>
					</li>
                </ul> 
			    <ul class='acp-menu' id='menu_{$r['block_id']}_menucontent' style='display: none'>
					<li class='icon view'><a href='#' id='PM__view{$r['block_id']}'>{$this->lang->words['view_block']}</a></li>
				</ul>                                
            </td>
		</tr>
		<script type='text/javascript'>
			$('PM__view{$r['block_id']}').observe('click', acp.portal.viewBlock.bindAsEventListener( this, "app=portal&amp;module=ajax&amp;section=view&amp;do=view_block&amp;id={$r['block_id']}" ) );
		</script>
HTML;
}

$IPBHTML .= <<<HTML
    </table>
</div>

<script type='text/javascript'>
	jQ("#mainblock_list").ipsSortable('table', { 
		url: "{$this->settings['base_url']}{$this->form_code_js}&do=block_move&id={$r['block_id']}&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>
HTML;

}
else
{
	
$IPBHTML .= <<<HTML
<div id='tab_main_content'>  
    <table class='ipsTable'>
		<tr>
			<td class='no_messages'>
                {$this->lang->words['no_custom_blocks']} <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=block_add' class='mini_button'>{$this->lang->words['add_block']}</a>				
			</td>
		</tr>
    </table>
</div>        
HTML;
}

if( is_array($rightBlocks) AND count($rightBlocks) )
{ 
$IPBHTML .= <<<HTML
<div id='tab_right_content'>  
    <table class='ipsTable' id='rightblock_list'>
HTML;

foreach( $rightBlocks as $r )
{
	
$IPBHTML .= <<<HTML
		<tr id='blocks_{$r['block_id']}' class='ipsControlRow isDraggable'>
			<td class='col_drag'>
				<div class='draghandle'>&nbsp;</div>
			</td>        
			<td><strong>{$r['title']}</strong></td>
            <td>
                <ul class='ipsControlStrip'>           
                    <li class='i_edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=block_edit&amp;id={$r['block_id']}' title='{$this->lang->words['edit_block']}'>{$this->lang->words['edit_block']}</a></li>
                	<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=block_delete&amp;id={$r['block_id']}");' title='{$this->lang->words['delete_block']}'>{$this->lang->words['delete_block']}</a></li>
			        <li class='ipsControlStrip_more ipbmenu' id='menu_{$r['block_id']}'>
						<a href='#'>{$this->lang->words['more']}</a>
					</li>
                </ul> 
			    <ul class='acp-menu' id='menu_{$r['block_id']}_menucontent' style='display: none'>
					<li class='icon view'><a href='#' id='PM__view{$r['block_id']}'>{$this->lang->words['view_block']}</a></li>
				</ul>                                
            </td>
		</tr>
		<script type='text/javascript'>
			$('PM__view{$r['block_id']}').observe('click', acp.portal.viewBlock.bindAsEventListener( this, "app=portal&amp;module=ajax&amp;section=view&amp;do=view_block&amp;id={$r['block_id']}" ) );
		</script>
HTML;
}

$IPBHTML .= <<<HTML
    </table>
</div>

<script type='text/javascript'>
	jQ("#rightblock_list").ipsSortable('table', { 
		url: "{$this->settings['base_url']}{$this->form_code_js}&do=block_move&id={$r['block_id']}&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' )
	});
</script>
HTML;
}
else
{
	
$IPBHTML .= <<<HTML
<div id='tab_right_content'>  
    <table class='ipsTable'>
		<tr>
			<td class='no_messages'>
                {$this->lang->words['no_custom_blocks']} <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=block_add' class='mini_button'>{$this->lang->words['add_block']}</a>				
			</td>
		</tr>
    </table>
</div>        
HTML;
}

$IPBHTML .= <<<HTML
       
		<script type='text/javascript'>
			jQ("#tabstrip_customBlocks").ipsTabBar({ tabWrap: "#tabstrip_customBlocks_content" });
		</script>            
    </div>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

public function viewBlock( $block ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$block['title']}</h3>
	<table class='ipsTable'>
		<td>
			{$block['block_code']}
		</td>
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

}
?>