<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Cleanup and rebuild tools skin file
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
 
class cp_skin_rebuild
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
 * Splash screen of available tools
 *
 * @return	string		HTML
 */
public function toolsSplashScreen()
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['re_310to320']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='320photos' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['re_320photos']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_field'><strong class='title'>{$this->lang->words['re_320photos_info']}</strong></td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			{$this->lang->words['re_convertfromphotos']} <select name='from'><option value='avatars'>{$this->lang->words['re_cp_avatars']}</option><option value='photos'>{$this->lang->words['re_cp_photos']}</option></select> <input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<div class='section_title'>
	<h2>{$this->lang->words['re_230to300']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='300pms' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['re_300pms']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_field'><strong class='title'>{$this->lang->words['re_300pms_info']}</strong></td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />
	
<div class='section_title'>
	<h2>{$this->lang->words['re_20to21']}</h2>	
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='210tool_settings' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['re_dupe2']}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_field'><strong class='title'>{$this->lang->words['re_dupe2_info']}</strong></td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='210calevents' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_events']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_field'><strong class='field'>{$this->lang->words['re_events_info']}</strong></td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='210polls' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_polls']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_field'><strong class='title'>{$this->lang->words['re_polls_info']}</strong></td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<div class='section_title'>
	<h2>{$this->lang->words['re_1xto20']}</h2>	
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='tool_settings' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_dupe1']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_field'><strong class='title'>{$this->lang->words['re_dupe1_info']}</strong></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='tool_converge' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_converge']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_field'><strong class='title'>{$this->lang->words['re_converge_info']}</strong></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='tool_bansettings' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_ban']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_field'><strong class='title'>{$this->lang->words['re_ban_info']}</strong></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml//
return $IPBHTML;

}

/**
 * Rebuild content splash screen
 *
 * @param	array 		Form elements
 * @param	array 		Sections we can rebuild
 * @param	array 		Sections we have rebuilt
 * @return	string		HTML
 */
public function rebuildSplashScreen( $form, $rebuildSections, $rebuiltSections )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['re_title']}</h2>
</div>

HTML;

if ( $this->registry->class_permissions->checkPermission( 'tools_recount' ) )
{

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='docount' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_stats']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_s_total']}</strong></td>
				<td class='field_field'>{$form['posts']}</td>
            </tr>
			
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_s_members']}</strong></td>
				<td class='field_field'>{$form['members']}</td>
			</tr>
			
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_s_last']}</strong></td>
				<td class='field_field'>{$form['lastreg']}</td>
			</tr>
			
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_s_most']}</strong></td>
				<td class='field_field'>{$form['online']}</td>
			</tr>
		</table>		
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_stats']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

HTML;
}

if ( $this->registry->class_permissions->checkPermission( 'tools_resyncht' ) )
{
$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='doresynctopics' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_topics']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_topics']}</strong></td>
				<td class='field_field'>{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}<br /><span class='desctext'>{$this->lang->words['re_topics_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_topics']}' class='button primary' accesskey='s'>
		</div>	
	</div>
</form><br />

HTML;
}

if ( $this->registry->class_permissions->checkPermission( 'tools_resynch' ) )
{

$IPBHTML .= <<<HTML

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='doresyncforums' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_forums']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_forums']}</strong></td>
				<td class='field_field'>{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}<br /><span class='desctext'>{$this->lang->words['re_forums_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_forums']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />
HTML;

}

if( count($rebuildSections) and $this->registry->class_permissions->checkPermission( 'tools_rebuild' ) )
{
	$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='doposts' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_rebuild']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<div style='overflow:auto'>
				<tr>
				    <td class='field_title'><strong class='title'>{$this->lang->words['re_rebuildbutton']}</strong></td>
					<td class='field_field'><input type='radio' name='type' id='type_none' checked='checked' value='0' /> <label style='float:none;' for='type_none'>{$this->lang->words['remenu_none']}</label></td>
				</tr>
HTML;

				foreach( $rebuildSections as $section )
				{
					$description	= '';
					
					if( in_array( $section[0], $rebuiltSections ) )
					{
						$description	= "<div class='desctext' style='color:red; margin-left: 28px;'>{$this->lang->words['noneed_rebuild_again']}</div>";
					}
					
					$IPBHTML .= <<<HTML
					<tr>
					    <td class='field_title'>&nbsp;</td>
						<td class='field_field'><input type='radio' name='type' id='type_{$section[0]}' value='{$section[0]}' /> <label style='float:none;' for='type_{$section[0]}'>{$section[1]}</label>{$description}</td>
					</tr>
HTML;
				}
				
				$IPBHTML .= <<<HTML
				</tr>
				
				<tr>
				    <td class='field_title'><strong class='title'>{$this->lang->words['re_percycle']}</strong></td>
					<td class='field_field'>{$form['pergo']}</td>
				</tr>

			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_rebuildbutton']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />
HTML;
}

if ( $this->registry->class_permissions->checkPermission( 'tools_postcounts' ) )
{
$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='dopostnames' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_user']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_user']}</strong></td>
				<td class='field_field'>{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}<br /><span class='desctext'>{$this->lang->words['re_user_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_user']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='doseousernames' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_seouser']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_seouser']}</strong></td>
				<td class='field_field'>{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}<br /><span class='desctext'>{$this->lang->words['re_seouser_info']}</span></td>
			</tr>
		</table>	
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_seouser']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='domsgcounts' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_msgcount']}</h3>
	
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_msgcount']}
				<td class='field_field'>{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}<br /><span class='desctext'>{$this->lang->words['re_msgcount_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_msgcount']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='dopostcounts' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_count']}</h3>
	
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_count']}</strong></td>
				<td class='field_field'>{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}<br /><span class='desctext'>{$this->lang->words['re_count_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_count']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='doreputationcount' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['rep_count']}</h3>
	
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['rep_count']}</strong></td>
				<td class='field_field'>{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}<br /><span class='desctext'>{$this->lang->words['rep_count_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['rep_count']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

HTML;
}

if ( $this->registry->class_permissions->checkPermission( 'tools_thumbs' ) )
{
$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='dophotos' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_pphoto']}</h3>
	
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_pphoto']}</strong></td>
				<td class='field_field'>{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}<br /><span class='desctext'>{$this->lang->words['re_pphoto_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_pphoto']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='dothumbnails' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_thumb']}</h3>
	
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_thumb']}</strong></td>
				<td class='field_field'>{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}<br /><span class='desctext'>{$this->lang->words['re_thumb_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_thumb']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />
HTML;
}

if ( $this->registry->class_permissions->checkPermission( 'tools_attach' ) )
{
$IPBHTML .= <<<HTML

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='doattachdata' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_data']}</h3>
	
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_data']}</strong></td>
				<td class='field_field'>{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}<br /><span class='desctext'>{$this->lang->words['re_data_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_data']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

HTML;
}

if ( $this->registry->class_permissions->checkPermission( 'tools_orphaned' ) )
{
$IPBHTML .= <<<HTML

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='cleanattachments' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_orph']}</h3>
	
		<table class='ipsTable double_pad'>
			<td>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_orph']}</strong></td>
				<td class='field_field'>{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}<br /><span class='desctext'>{$this->lang->words['re_orph_info']}</span></td>
			</td>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_orph']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

HTML;
}

if ( $this->registry->class_permissions->checkPermission( 'tools_orphanp' ) )
{
$IPBHTML .= <<<HTML

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='cleanphotos' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_uphoto']}</h3>

		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['re_uphoto']}</strong></td>
				<td class='field_field'>{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}<br /><span class='desctext'>{$this->lang->words['re_uphoto_info']}</span></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_uphoto']}' class='button primary' accesskey='s'>
		</div>	
	</div>
</form>
HTML;

}

//--endhtml--//
return $IPBHTML;
}


}