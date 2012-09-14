<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Performance mode skin file
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
 
class cp_skin_performance
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
 * Show the results from toggling the mode
 *
 * @param	bool		Currently on or off
 * @param	array 		Actions that were taken
 * @return	string		HTML
 */
public function toggleResults( $current, $actions=array() )
{
$IPBHTML = "";
//--starthtml--//

$text	= $current ? "<div style='font-weight: bold; color: red; font-size: 22px;'>" . $this->lang->words['perf_on'] . "</div>" : "<div style='font-weight: bold; color: green; font-size: 22px;'>" . $this->lang->words['perf_off'] . "</div>";

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['perf_help_title']}</h2>
</div>

<div class='section_info'>
	{$this->lang->words['perf_help_information']}
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['perf_current']}</h3>
	
	<div class='acp-row_off' style='padding: 10px;'>
		{$text}
	</div>
HTML;

if( count($actions) )
{
	$IPBHTML .= "<ul style='list-style: disc; margin-left: 40px'>";
	foreach( $actions as $action )
	{
		$IPBHTML .= <<<HTML
				<li style='padding: 3px 5px'>
				 	{$action}
				</li>
HTML;
	}
	$IPBHTML .= "</ul>";
}

$IPBHTML .= <<<HTML
	<div class='acp-actionbar' style='padding-top: 12px'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toggle' class='button'>{$this->lang->words['perf_toggle']}</a></div>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the overview page
 *
 * @param	string		Content
 * @return	string		HTML
 */
public function overview( $current )
{
$IPBHTML = "";
//--starthtml--//

$text	= $current ? "<div style='font-weight: bold; color: red; font-size: 22px;'>" . $this->lang->words['perf_on'] . "</div>" : "<div style='font-weight: bold; color: green; font-size: 22px;'>" . $this->lang->words['perf_off'] . "</div>";


$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['perf_help_title']}</h2>
</div>

<div class='section_info'>
	{$this->lang->words['perf_help_information']}
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['perf_current']}</h3>
	
	<div class='acp-row_off' style='padding: 10px;'>
		{$text}
	</div>
	
	<div class='acp-actionbar' style='padding-top: 12px'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toggle' class='button'>{$this->lang->words['perf_toggle']}</a></div>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;


}

}