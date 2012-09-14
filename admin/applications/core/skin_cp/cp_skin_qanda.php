<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Question and answer skin file
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
 
class cp_skin_qanda
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
 * Show the q&a form
 *
 * @param	string		Action
 * @param	int			ID
 * @param	array 		Form elements
 * @param	string		Button text
 * @return	string		HTML
 */
public function showForm( $do, $id, $form, $button )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['qa_help_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$button}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['qa_form_question']}</strong>
				</td>
				<td class='field_field'>
					{$form['question']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['qa_form_answers']}</strong>
				</td>
				<td class='field_field'>
					{$form['answers']}<br />
					<span class='desctext'>{$this->lang->words['qa_form_answers_extra']}</span>
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$button}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the overview page
 *
 * @param	array 		Rows
 * @return	string		HTML
 */
public function overview( $rows )
{
$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['qa_help_title']}</h2>
	<div class='section_info'>
		{$this->lang->words['qahelp_infos']}
	</div>
	<div class='ipsActionBar clearfix'>
		<ul>
HTML;

		if ( $this->registry->class_permissions->checkPermission( 'qa_manage' ) )
		{
			$IPBHTML .= <<<HTML
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=new' title='{$this->lang->words['qa_addlink']}'>
					<img src='{$this->settings['skin_acp_url']}/images/icons/help_add.png' alt='' />
					{$this->lang->words['qa_addlink']}
				</a>
			</li>
HTML;
		}
		
		$IPBHTML .= <<<HTML
		</ul>
	</div>
</div>
HTML;



$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['qa_current']}</h3>
	<table class='ipsTable'>
HTML;

if( count($rows) )
{		
$IPBHTML .= <<<HTML
		<tr>
			<th>{$this->lang->words['qa_form_question']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;
foreach( $rows as $r )
{
	$r['_answers'] = IPSText::truncate( str_replace("\r", ", ", $r['qa_answers']), 50 );

$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td>
				<span class='larger_text'>{$r['qa_question']}</span>
				<br /><span class='desctext'>{$r['_answers']}</span>
			</td>
			<td>
HTML;

		if ( $this->registry->class_permissions->checkPermission( 'qa_manage' ) )
		{
			$IPBHTML .= <<<HTML
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;id={$r['qa_id']}'>{$this->lang->words['qa_edit']}</a>
					</li>
					<li class='i_delete'>
						<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=remove&amp;id={$r['qa_id']}");'>{$this->lang->words['qa_delete']}</a>
					</li>
				</ul>
HTML;
		}
		
			$IPBHTML .= <<<HTML
			</td>
		</tr>
HTML;
}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td class='no_messages'>{$this->lang->words['qa_none']}.
HTML;
	if ( $this->registry->class_permissions->checkPermission( 'qa_manage' ) )
	{
		$IPBHTML .= <<<HTML
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=new' title='{$this->lang->words['qa_addlink']}' class='mini_button'>{$this->lang->words['qa_createonenow']}</a>
HTML;
	}
	
	$IPBHTML .= <<<HTML
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;


}

}