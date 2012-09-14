<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * ACP ranks skin file
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
 
class cp_skin_ranks
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
 * Ranks overview page
 *
 * @param	array 		Rows
 * @return	string		HTML
 */
public function titlesOverview( $rows ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['rnk_titles']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['rnk_titles']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='40%'>{$this->lang->words['rnk_title']}</th>
			<th width='20%'>{$this->lang->words['rnk_minposts']}</th>
			<th width='30%'>{$this->lang->words['rnk_pips']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
HTML;

if( count($rows) )
{
	foreach( $rows as $rank )
	{
		$rank['img'] = "";
		
		if( preg_match( "/[a-zA-Z]{1,}/", $rank['pips'] ) )
		{
			$rank['img'] = "<img src='" . $this->settings['public_dir'] . "style_extra/team_icons/{$rank['pips']}' />";
		}
		else
		{
			for ( $i = 1; $i <= $rank['pips']; $i++ )
			{
				$rank['img'] .= $rank['A_STAR'];
			}
		}

		$IPBHTML .= <<<HTML
		<tr class='ipsControlRow'>
			<td>
				<strong class='larger_text'>
					<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rank_edit&amp;id={$rank['id']}'>{$rank['title']}</a>
				</strong>
			</td>
			<td>{$rank['posts']}</td>
			<td>{$rank['img']}</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rank_edit&amp;id={$rank['id']}' title='{$this->lang->words['rnk_editlink']}'>{$this->lang->words['rnk_editlink']}</a>
					</li>
					<li class='i_delete'>
						<a href='#' onclick='return acp.confirmDelete( "{$this->settings['base_url']}{$this->form_code}&amp;do=rank_delete&amp;id={$rank['id']}" )' title='{$this->lang->words['rnk_deletelink']}'>{$this->lang->words['rnk_deletelink']}</a>
					</li>
				</ul>								
			</td>
		</tr>		
HTML;
	}
}


$IPBHTML .= <<<HTML
	</table>
</div>
<br />
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=do_add_rank' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['rnk_addarank']}</h3>
		
		<table class='ipsTable'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['rnk_ranktitle']}</strong></td>
				<td class='field_field'><input type='text' name='title' class='input_text' /></td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['rnk_minpostsneeded']}</strong></td>
				<td class='field_field'><input type='text' name='posts' class='input_text' /></td>
			</tr>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['rnk_numpips']}</strong></td>
				<td class='field_field'><input type='text' name='pips' class='input_text' /><br /><span class='desctext'>{$this->lang->words['rnk_numpips_info']}</span></td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['rnk_addrank']}' class='button primary'/>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Ranks form
 *
 * @param	array 		Rank data
 * @param	string		Action code
 * @param	string		Button text
 * @return	string		HTML
 */
public function titlesForm( $rank, $action, $button ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$button}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$action}' method='post'>
	<input type='hidden' name='id' value='{$rank['id']}' />
	
	<div class='acp-box'>
		<h3>{$button}</h3>
 		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rnk_ranktitle']}</strong>
				</td>
				<td class='field_field'>
					<input type='text' name='title' class='input_text' value='{$rank['title']}' />
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rnk_minpostsneeded']}</strong>
				</td>
				<td class='field_field'>
				<input type='text' name='posts' class='input_text' value='{$rank['posts']}' />
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['rnk_numpips']}</strong>
				</td>
				<td class='field_field'>
					<input type='text' name='pips' class='input_text' value='{$rank['pips']}' /><br />
					<span class='desctext'>{$this->lang->words['rnk_numpips_info']}</span>
				</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$button}' class='button primary' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


}