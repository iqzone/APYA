<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Hooks export skin file
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 10721 $
 */
 
class cp_skin_hooks_export
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member   	= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang 		= $this->registry->class_localization;
	}

/**
 * Inline css dhtml box
 *
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function inline_css( $hook, $form=array() )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__css'] = {};
	acp.hooks.fields['MF__css']['fields'] = $A(['css']);
	acp.hooks.fields['MF__css']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=css&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__css']['callback'] = function( t, json ){
		$('MF__css').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__css'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>{$this->lang->words['addcss']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['selectoneomorecss']}</strong>
			</td>
			<td class='field_field'>
				{$form['css']}
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['hookssavebutton']}' class='realbutton' id='MF__css_save' />
	</div>
</div>

EOF;

return $IPBHTML;
}

/**
 * Inline replacements dhtml box
 *
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function inline_replacements( $hook, $form=array() )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__replacements'] = {};
	acp.hooks.fields['MF__replacements']['fields'] = $A(['replacements']);
	acp.hooks.fields['MF__replacements']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=replacements&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__replacements']['callback'] = function( t, json ){
		$('MF__replacements').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__replacements'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>{$this->lang->words['addreplacements']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['selectoneomorereplacements']}</strong>
			</td>
			<td class='field_field'>
				{$form['replacements']}
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['hookssavebutton']}' class='realbutton' id='MF__replacements_save' />
	</div>
</div>

EOF;

return $IPBHTML;
}


/**
 * Inline settings dhtml box
 *
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function inline_settings( $hook, $form=array() )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__settings'] = {};
	acp.hooks.fields['MF__settings']['fields'] = $A(['setting_groups', 'settings']);
	acp.hooks.fields['MF__settings']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=settings&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__settings']['callback'] = function( t, json ){
		$('MF__settings').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__settings'), { pulses: 3 } );
	}
</script>
<style type='text/css'>
	#hook_settings_table select {
		width: 300px;
		height: 100px;
		font-size: 10px;
	}
</style>

<div class='acp-box'>
	<h3>{$this->lang->words['addsettings']}</h3>
	<table class='ipsTable double_pad' id='hook_settings_table'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['exportonesettings']}</strong>
			</td>
			<td class='field_field'>
				{$form['groups']}
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['indivsettingsex']}</strong>
			</td>
			<td class='field_field'>
				{$form['settings']}
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['hookssavebutton']}' class='realbutton' id='MF__settings_save' />
	</div>
</div>

EOF;

return $IPBHTML;
}

/**
 * Inline modules dhtml box
 *
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function inline_modules( $hook, $form=array() )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__modules'] = {};
	acp.hooks.fields['MF__modules']['fields'] = $A(['modules']);
	acp.hooks.fields['MF__modules']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=modules&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__modules']['callback'] = function( t, json ){
		$('MF__modules').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__modules'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>{$this->lang->words['addmodules']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['exportmodules']}</strong>
			</td>
			<td class='field_field'>
				{$form['modules']}
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['hookssavebutton']}' class='realbutton' id='MF__modules_save' />
	</div>
</div>

EOF;

return $IPBHTML;
}

/**
 * Inline custom script dhtml box
 *
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function inline_custom( $hook, $form=array() )
{
$IPBHTML = "";

$pathcustomscript = sprintf( $this->lang->words['pathcustomscript'], rtrim( str_replace( DOC_IPS_ROOT_PATH, '', IPS_HOOKS_PATH ), '/' ) );

$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__custom'] = {};
	acp.hooks.fields['MF__custom']['fields'] = $A(['custom']);
	acp.hooks.fields['MF__custom']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=custom&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__custom']['callback'] = function( t, json ){
		$('MF__custom').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__custom'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>{$this->lang->words['addcustomscript']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$pathcustomscript}</strong>
			</td>
			<td class='field_field'>
				install_ {$form['custom']}
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['hookssavebutton']}' class='realbutton' id='MF__custom_save' />
	</div>
</div>
EOF;

return $IPBHTML;
}

/**
 * Inline help files dhtml box
 *
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function inline_help( $hook, $form=array() )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__help'] = {};
	acp.hooks.fields['MF__help']['fields'] = $A(['help']);
	acp.hooks.fields['MF__help']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=help&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__help']['callback'] = function( t, json ){
		$('MF__help').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__help'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>{$this->lang->words['addhelpfiles']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['selectoneormorehelp']}</strong>
			</td>
			<td class='field_field'>
				{$form['help']}
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['hookssavebutton']}' class='realbutton' id='MF__help_save' />
	</div>
</div>
EOF;

return $IPBHTML;
}

/**
 * Inline tasks dhtml box
 *
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function inline_tasks( $hook, $form=array() )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__tasks'] = {};
	acp.hooks.fields['MF__tasks']['fields'] = $A(['tasks']);
	acp.hooks.fields['MF__tasks']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=tasks&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__tasks']['callback'] = function( t, json ){
		$('MF__tasks').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__tasks'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>{$this->lang->words['addtasks']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['oneormtasks']}</strong>
			</td>
			<td class='field_field'>
				{$form['tasks']}
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['hookssavebutton']}' class='realbutton' id='MF__tasks_save' />
	</div>
</div>

EOF;

return $IPBHTML;
}

/**
 * Inline language bits dhtml box
 *
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @param	int			Current index
 * @return	string		HTML
 */
public function inline_languages( $hook, $form=array(), $i=0 )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__language'] = {};
	acp.hooks.fields['MF__language']['fields'] = $A([]);
	acp.hooks.fields['MF__language']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=language&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__language']['callback'] = function( t, json ){
		$('MF__language').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__language'), { pulses: 3 } );
	}
	
	ipb.templates['lang_row'] = new Template("<li><table class='ipsTable'><tr><td style='width: 30%; vertical-align: top;'>#{control}<br /><span class='desctext langdesc' id='container_desc_#{containerid}' style='display: none'>{$this->lang->words['nowselectstrings']} {$this->lang->words['_rarr']}</span></td><td style='vertical-align: top;'><div id='container_#{containerid}' style='display: none'></div></td></tr></table></li>");
	
	acp.hooks.languageMax = $i;
</script>
<style type='text/css'>
	.langdesc {
		font-size: 13px;
		padding: 5px;
	}
</style>

<div class='acp-box'>
	<h3>{$this->lang->words['addlanguage']}</h3>
	<div style='max-height: 350px; overflow: auto'>
	<ul class='acp-form alternate_rows sep_rows' id='language_wrap'>
EOF;
	if( $i )
	{
		for( $k=1; $k<$i; $k++ )
		{
			$IPBHTML .= <<<EOF
			<li>
				<table class='ipsTable'>
					<tr>
						<td style='width: 30%; vertical-align: top;'>
							{$form['language_file_' . $k ]}<br />
							<span class='desctext langdesc' id='container_desc_{$k}'>{$this->lang->words['nowselectstrings']} {$this->lang->words['_rarr']}</span>
						</td>
						<td style='vertical-align: top;'>
							<div id='container_{$k}'>
								{$form['language_strings_' . $k ]}
								<script type='text/javascript'>
									acp.hooks.fields['MF__language']['fields'].push("language_{$k}");
									acp.hooks.fields['MF__language']['fields'].push("strings_{$k}");
								</script>
							</div>
						</td>
					</tr>
				</table>
			</li>
EOF;
		}
	}
	
	$IPBHTML .= <<<EOF
		<li>
			<table class='ipsTable'>
				<tr>
					<td style='width: 30%; vertical-align: top;'>
						{$form['language_file_' . $i ]}<br />
						<span class='desctext langdesc' id='container_desc_{$i}' style='display: none'>{$this->lang->words['nowselectstrings']} {$this->lang->words['_rarr']}</span>
					</td>
					<td style='vertical-align: top;'>
						<div id='container_{$i}' style='display: none'>
							{$form['language_strings_' . $i ]}
							<script type='text/javascript'>
								acp.hooks.fields['MF__language']['fields'].push("language_{$i}");
								acp.hooks.fields['MF__language']['fields'].push("strings_{$i}");
							</script>
						</div>
					</td>
				</tr>
			</table>
		</li>
	</ul>
	</div>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['addanotherfile']}' class='realbutton' id='addLanguage' /> <input type='submit' value='{$this->lang->words['hookssavebutton']}' class='realbutton' id='MF__language_save' />
		<script type='text/javascript'>
			$('addLanguage').observe('click', acp.hooks.addAnotherLanguage);
		</script>
	</div>
</div>
EOF;

return $IPBHTML;
}

/**
 * Inline skin bits dhtml box
 *
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @param	int			Current index
 * @return	string		HTML
 */
public function inline_skins( $hook, $form=array(), $i=0 )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__templates'] = {};
	acp.hooks.fields['MF__templates']['fields'] = $A([]);
	acp.hooks.fields['MF__templates']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=skins&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__templates']['callback'] = function( t, json ){
		$('MF__templates').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__templates'), { pulses: 3 } );
	}
	
	ipb.templates['skin_row'] = new Template("<li><table class='ipsTable'><tr><td style='width: 35%; vertical-align: top;'>#{control}<br /><span class='desctext skindesc' id='s_container_desc_#{containerid}' style='display: none'>{$this->lang->words['nowselectbits']} {$this->lang->words['_rarr']}</span></td><td style='vertical-align: top;'><div id='s_container_#{containerid}' style='display: none'></div></td></tr></table></li>");
	
	acp.hooks.skinMax = $i;
</script>
<style type='text/css'>
	.skindesc {
		font-size: 13px;
		padding: 5px;
	}
</style>

<div class='acp-box'>
	<h3>{$this->lang->words['addtemplates']}</h3>
	<div style='max-height: 350px; overflow: auto'>
	<ul class='acp-form alternate_rows sep_rows' id='skin_wrap'>
EOF;

if( $i )
{
	for( $k=1; $k<$i; $k++ )
	{
		$IPBHTML .= <<<EOF
		<li>
			<table class='ipsTable'>
				<tr>
					<td style='width: 35%; vertical-align: top;'>
						{$form['skin_file_' . $k ]}<br />
						<span class='desctext skindesc' id='s_container_desc_{$k}'>{$this->lang->words['nowselectbits']} {$this->lang->words['_rarr']}</span>
					</td>
					<td style='vertical-align: top;'>
						<div id='s_container_{$k}'>
							{$form['skin_method_' . $k ]}
							<script type='text/javascript'>
								acp.hooks.fields['MF__templates']['fields'].push("templates_{$k}");
								acp.hooks.fields['MF__templates']['fields'].push("skin_{$k}");
							</script>
						</div>
					</td>
				</tr>
			</table>
		</li>
		
EOF;
	}
}
	$IPBHTML .= <<<EOF
		<li>
			<table class='ipsTable'>
				<tr>
					<td style='width: 35%; vertical-align: top;'>
						{$form['skin_file_' . $i ]}<br />
						<span class='desctext skindesc' id='s_container_desc_{$i}' style='display: none'>{$this->lang->words['nowselectbits']} {$this->lang->words['_rarr']}</span>
					</td>
					<td style='vertical-align: top;'>
						<div id='s_container_{$i}' style='display: none'>
							{$form['language_strings_' . $i ]}
							<script type='text/javascript'>
								acp.hooks.fields['MF__templates']['fields'].push("templates_{$i}");
								acp.hooks.fields['MF__templates']['fields'].push("skin_{$i}");
							</script>
						</div>
					</td>
				</tr>
			</table>
		</li>
	</ul>
	</div>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['addanothertemplate']}' class='realbutton' id='addTemplates' /> <input type='submit' value='{$this->lang->words['hookssavebutton']}' class='realbutton' id='MF__templates_save' />
		<script type='text/javascript'>
			$('addTemplates').observe('click', acp.hooks.addAnotherTemplate);
		</script>
	</div>
</div>
EOF;

return $IPBHTML;
}

/**
 * Inline database changes dhtml box
 *
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @param	int			Current index
 * @return	string		HTML
 */
public function inline_database( $hook, $form=array(), $i=0 )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__database'] = {};
	acp.hooks.fields['MF__database']['fields'] = $A([]);
	acp.hooks.fields['MF__database']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=database&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__database']['callback'] = function( t, json ){
		$('MF__database').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__database'), { pulses: 3 } );
	}
	
	ipb.templates['db_row'] = new Template("<li><table class='ipsTable'><tr><td style='width: 35%; vertical-align: top;'><select name='type_#{id}' onchange='acp.hooks.generateFields(#{id});' id='type_#{id}' class='dropdown'><option value='0'>{$this->lang->words['selectone']}</option><option value='create'>{$this->lang->words['hook_db_create']}</option><option value='alter'>{$this->lang->words['hook_db_alter']}</option><option value='update'>{$this->lang->words['hook_db_update']}</option><option value='insert'>{$this->lang->words['hook_db_insert']}</option></select><br /><span class='desctext dbdesc' id='d_container_desc_#{id}' style='display: none'>{$this->lang->words['nowselectstrings']} {$this->lang->words['_rarr']}</span></td><td style='vertical-align: top;'><div id='d_container_#{id}' style='display: none' class='dbcontainer'></div></td></tr></table></li>");
	
	ipb.templates['db_create'] = new Template("{$this->lang->words['desc_newtable']}<br /><input name='name_#{id}' id='name_#{id}' type='input' /><br /><br />{$this->lang->words['desc_fieldnames']}<br /><textarea name='fields_#{id}' id='fields_#{id}'></textarea><br /><br />{$this->lang->words['desc_tabletype']}<br /><input name='tabletype_#{id}' id='tabletype_#{id}' type='input' />");
	
	ipb.templates['db_alter'] = new Template("{$this->lang->words['desc_altertype']}<br /><select name='altertype_#{id}' id='altertype_#{id}'><option value='add'>{$this->lang->words['hook_db_addnew']}</option><option value='change'>{$this->lang->words['hook_db_change']}</option><option value='remove'>{$this->lang->words['hook_db_drop']}</option></select><br /><br />{$this->lang->words['desc_existtable']}<br /><input name='table_#{id}' id='table_#{id}' type='text' /><br /><br />{$this->lang->words['desc_field']}<br /><input name='field_#{id}' id='field_#{id}' type='text'><br /><br />{$this->lang->words['desc_changefield']}<br /><input name='newfield_#{id}' id='newfield_#{id}' type='text'><br /><br />{$this->lang->words['desc_definition']}<br /><input name='fieldtype_#{id}' id='fieldtype_#{id}' type='text'><br /><br />{$this->lang->words['desc_defaultvalue']}<br /><input name='default_#{id}' id='default_#{id}' type='text' />");
	
	ipb.templates['db_update'] = new Template("{$this->lang->words['desc_existtable']}<br /><input name='table_#{id}' id='table_#{id}' type='text'><br /><br />{$this->lang->words['desc_field']}<br /><input name='field_#{id}' id='field_#{id}' type='text'><br /><br />{$this->lang->words['desc_newvalue']}<br /><input name='newvalue_#{id}' id='newvalue_#{id}' type='text'><br /><br />{$this->lang->words['desc_oldvalue']}<br /><input name='oldvalue_#{id}' id='oldvalue_#{id}' type='text'><br /><br />{$this->lang->words['desc_where']}<br /><input name='where_#{id}' id='where_#{id}' type='text'>");
	
	ipb.templates['db_insert'] = new Template("{$this->lang->words['desc_existtable']}<br /><input name='table_#{id}' id='table_#{id}' type='text' /><br /><br />{$this->lang->words['desc_data']}<br /><textarea name='updates_#{id}' id='updates_#{id}'></textarea><br /><br />{$this->lang->words['desc_revert']}<br /><input name='fordelete_#{id}' id='fordelete_#{id}' type='text' />");
	
	acp.hooks.dbMax = $i;
</script>

<style type='text/css'>
	.dbdesc {
		font-size: 13px;
		padding: 5px;
	}
	
	.dbcontainer input,
	.dbcontainer select,
	.dbcontainer textarea {
		margin: 4px 0px 4px 10px;
	}
</style>


<div class='acp-box'>
	<h3>{$this->lang->words['adddbchanges']}</h3>
	<div style='max-height: 350px; overflow: auto'>
	<ul class='acp-form alternate_rows sep_rows' id='database_wrap'>
EOF;

if( $i )
{
	for( $k=1; $k<$i; $k++ )
	{	
		$IPBHTML .= <<<EOF
		<li>
			<table class='ipsTable'>
				<tr>
					<td style='width: 35%; vertical-align: top;'>
						{$form['type_' . $k ]}<br />
						<span class='desctext dbdesc' id='d_container_desc_{$k}'>{$this->lang->words['nomodifyset']} {$this->lang->words['_rarr']}</span>
					</td>
					<td style='vertical-align: top;'>
						<div id='d_container_{$k}' class='dbcontainer'>
EOF;
						if( $form['field_1_' . $k ] )
						{
							$IPBHTML .= <<<EOF
							{$form['description_1_' . $k ]}<br />
							{$form['field_1_' . $k ]}<br /><br />
EOF;
						}
						
						if( $form['field_2_' . $k ] )
						{
							$IPBHTML .= <<<EOF
							{$form['description_2_' . $k ]}<br />
							{$form['field_2_' . $k ]}<br /><br />
EOF;
						}
						
						if( $form['field_3_' . $k ] )
						{
							$IPBHTML .= <<<EOF
							{$form['description_3_' . $k ]}<br />
							{$form['field_3_' . $k ]}<br /><br />
EOF;
						}
						
						if( $form['field_4_' . $k ] )
						{
							$IPBHTML .= <<<EOF
							{$form['description_4_' . $k ]}<br />
							{$form['field_4_' . $k ]}<br /><br />
EOF;
						}
						
						if( $form['field_5_' . $k ] )
						{
							$IPBHTML .= <<<EOF
							{$form['description_5_' . $k ]}<br />
							{$form['field_5_' . $k ]}<br /><br />
EOF;
						}
						
						if( $form['field_6_' . $k ] )
						{
							$IPBHTML .= <<<EOF
							{$form['description_6_' . $k ]}<br />
							{$form['field_6_' . $k ]}<br /><br />
EOF;
						}
						
						$IPBHTML .= <<<EOF
							<script type='text/javascript'>
								 acp.hooks.fields['MF__database']['fields'].push( "type_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "name_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "fields_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "tabletype_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "altertype_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "table_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "field_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "newfield_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "fieldtype_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "default_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "where_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "newvalue_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "oldvalue_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "updates_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "fordelete_{$k}" );
								
								acp.hooks.dbMax = {$k};
							</script>
						</div>
					</td>
				</tr>
			</table>
		</li>
EOF;
	}
}
$IPBHTML .= <<<EOF
		<li>
			<table class='ipsTable'>
				<tr>
					<td style='width: 35%; vertical-align: top;'>
						{$form['type_' . $i ]}<br />
						<span class='desctext dbdesc' id='d_container_desc_{$i}' style='display: none'>{$this->lang->words['nomodifyset']} {$this->lang->words['_rarr']}</span>
					</td>
					<td style='vertical-align: top;'>
						<div id='d_container_{$i}' style='display: none' class='dbcontainer'>
							{$form['language_strings_' . $i ]}
							<script type='text/javascript'>
								acp.hooks.fields['MF__database']['fields'].push("type_{$i}");
								acp.hooks.fields['MF__database']['fields'].push( "name_{$k}" );
								acp.hooks.fields['MF__database']['fields'].push( "fields_{$k}" );
								acp.hooks.fields['MF__database']['fields'].push( "tabletype_{$k}" );
								acp.hooks.fields['MF__database']['fields'].push( "altertype_{$k}" );
								acp.hooks.fields['MF__database']['fields'].push( "table_{$k}" );
								acp.hooks.fields['MF__database']['fields'].push( "field_{$k}" );
								acp.hooks.fields['MF__database']['fields'].push( "newfield_{$k}" );
								acp.hooks.fields['MF__database']['fields'].push( "fieldtype_{$k}" );
								acp.hooks.fields['MF__database']['fields'].push( "default_{$k}" );
								acp.hooks.fields['MF__database']['fields'].push( "where_{$k}" );
								acp.hooks.fields['MF__database']['fields'].push( "newvalue_{$k}" );
								acp.hooks.fields['MF__database']['fields'].push( "oldvalue_{$k}" );
								acp.hooks.fields['MF__database']['fields'].push( "updates_{$k}" );
								acp.hooks.fields['MF__database']['fields'].push( "fordelete_{$k}" );
							</script>
						</div>
					</td>
				</tr>
			</table>
		</li>
	</ul>
	</div>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['addanotherchange']}' class='realbutton' id='addDB' /> <input type='submit' value='{$this->lang->words['hookssavebutton']}' class='realbutton' id='MF__database_save' />
		<script type='text/javascript'>
			$('addDB').observe('click', acp.hooks.addAnotherDB);
		</script>
	</div>
</div>


EOF;

return $IPBHTML;
}

}