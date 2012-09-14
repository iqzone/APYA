<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP member forms skin file
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10721 $
 *
 */
 
class cp_skin_member_form
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
 * Ban member dhtml window
 *
 * @param	array 		Member data
 * @param	array 		Form data
 * @return	string		HTML
 */
public function inline_ban_member_form( $member, $form )
{
$IPBHTML = "";
																	
$IPBHTML .= <<<EOF

<form action='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=ban_member&amp;member_id={$member['member_id']}' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['sm_banmanage']}</h3>
		<div class='fixed_inner'>
			<table class='ipsTable double_pad'>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['mf_banperm']}</strong></td>
					<td class='field_field'>{$form['member']}</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['mf_movefrom']} '{$member['_group_title']}' {$this->lang->words['mf_to']}</strong></td>
					<td class='field_field'>
						{$form['groups_confirm']}
						{$form['groups']}
					</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['mf_banemail']} '{$member['email']}'</strong></td>
					<td class='field_field'>{$form['email']}</td>
				</tr>
				<tr>
					<td class='field_title'><strong class='title'>{$this->lang->words['mf_banname']} '{$member['name']}'</strong></td>
					<td class='field_field'>{$form['name']}</td>
				</tr>
EOF;
				if( $form['ips'] && count( $form['ips'] ) )
				{
					$IPBHTML .= <<<EOF
						<tr><th colspan='2'>{$this->lang->words['ipaddresses']}</tr>
					</table>
					<div style='max-height: 150px; overflow: auto;'>
						<table class='ipsTable double_pad'>
EOF;
				
					foreach( $form['ips'] as $ip => $form_field )
					{
						$IPBHTML .= <<<EOF
						<tr>
							<td class='field_title'><strong class='title'>{$this->lang->words['mf_banip']} '{$ip}'</strong></td>
							<td class='field_field'>{$form_field}</td>
						</tr>
EOF;
					}
				
					$IPBHTML .= <<<EOF
						</table>
					</div>
					<table class='ipsTable double_pad'>
EOF;
				}
			
				$IPBHTML .= <<<EOF
				<tr>
					<td colspan='2'><a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=banmember&amp;member_id={$member['member_id']}'>{$this->lang->words['mf_clickhere']}</a> {$this->lang->words['mf_tosuspend']}<br />
					{$this->lang->words['mf_justor']} <a href='#' onclick="new Effect.Fade( $('inlineFormWrap'), {duration: 0.3} ); acp.members.goToTab( 'tab_MEMBERS_7' ); return false;">{$this->lang->words['mf_clickhere']}</a> {$this->lang->words['mf_topostrestrict']}</td>
				</tr>
			</table>
		</div>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['mf_alterban']}' class='button primary' />
		</div>
	</div>
</form>
	
EOF;

return $IPBHTML;
}

/**
 * Edit email dhtml window
 *
 * @param	array 		Member data
 * @return	string		HTML
 */
public function inline_email( $member )
{
$IPBHTML = "";
																	
$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}&amp;module=members&amp;section=editform&amp;do=save_email&amp;member_id={$member['member_id']}&amp;secure_key={$this->member->form_hash}' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['mem_ajfo_email']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mem_ajfo_email1']}</strong></td>
			<td class='field_field'><input type='text' size='30' id='email' name='email' value="{$member['email']}" class='input_text' /></td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['mf_save']}' class='button primary' />
	</div>
</div>
</form>
EOF;

return $IPBHTML;
}


/**
 * Upload photo dhtml window
 *
 * @param	array 		Member data
 * @return	string		HTML
 */
public function inline_form_new_photo( $member )
{
$IPBHTML = "";
																	
$IPBHTML .= <<<EOF

<form action='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=new_photo&amp;member_id={$member['member_id']}' method='post' enctype='multipart/form-data'>
<div class='acp-box'>
	<h3>{$this->lang->words['mem_ajfo_photo']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mf_newphoto']}</strong></td>
			<td class='field_field'><input type='file' size='30' id='upload_photo' name='upload_photo' /></td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['mf_save']}' class='button primary' />
	</div>
</div>
</form>
EOF;

return $IPBHTML;
}


/**
 * Edit password dhtml window
 *
 * @param	array 		Member data
 * @return	string		HTML
 */
public function inline_password( $member )
{
$IPBHTML = "";
																	
$_form_new_salt       = ipsRegistry::getClass('output')->formYesNo( "new_salt", 1 );
$_form_new_pepper     = ipsRegistry::getClass('output')->formYesNo( "new_key" , 1 );

$IPBHTML .= <<<EOF

<form action='{$this->settings['base_url']}&amp;module=members&amp;section=editform&amp;do=save_password&amp;member_id={$member['member_id']}&amp;secure_key={$this->member->form_hash}' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['mem_ajfo_password']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mem_ajfo_password1']}</strong></td>
			<td class='field_field'><input type='password' size='30' id='password' name='password' class='input_text' /></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mem_ajfo_password2']}</strong></td>
			<td class='field_field'><input type='password' size='30' id='password2' name='password2' class='input_text' /></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mem_afjo_new_key']}</strong></td>
			<td class='field_field'>{$_form_new_pepper}<br /><span class='desctext'>{$this->lang->words['mem_afjo_new_key_desc']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mem_afjo_new_salt']}</strong></td>
			<td class='field_field'>{$_form_new_salt}</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['mf_save']}' class='button primary' id='MF__password_save' />
	</div>
</div>
</form>

EOF;

return $IPBHTML;
}

/**
 * Change name dhtml window
 *
 * @param	array 		Member data
 * @return	string		HTML
 */
public function inline_form_name( $member )
{
$IPBHTML = "";

$_form_send_email     = ipsRegistry::getClass('output')->formYesNo( "send_email", 1 );
$_form_email_contents = ipsRegistry::getClass('output')->formTextarea( "email_contents", $this->lang->words['mem_afjo_email_contents'] );

$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}&amp;module=members&amp;section=editform&amp;do=save_name&amp;member_id={$member['member_id']}&amp;secure_key={$this->member->form_hash}' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['mem_edit_login_name']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mem_ajfo_name']}</strong></td>
			<td class='field_field'><input type='text' size='30' id='name' name='name' value='{$member['name']}' class='input_text' /></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mem_afjo_send_email']}</strong></td>
			<td class='field_field'>{$_form_send_email}<br /><br />{$_form_email_contents}<br /><span class='desctext'>{$this->lang->words['mem_afjo_send_email_desc']}</span></td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['mf_save']}' class='button primary' id='MF__name_save' />
	</div>
</div>
</form>
EOF;

return $IPBHTML;
}


/**
 * Change display name dhtml window
 *
 * @param	array 		Member data
 * @return	string		HTML
 */
public function inline_form_display_name( $member )
{
$IPBHTML = "";

$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}&amp;module=members&amp;section=editform&amp;do=save_display_name&amp;member_id={$member['member_id']}&amp;secure_key={$this->member->form_hash}' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['mem_edit_display_name']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mem_display_name']}</strong></td>
			<td class='field_field'><input type='text' size='30' id='display_name' name='display_name' value='{$member['members_display_name']}' class='input_text' /></td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['mf_save']}' class='button primary' id='MF__member_display_name_save' />
	</div>
</div>
</form>

EOF;

return $IPBHTML;
}


}