<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.0
 * ACP members skin file
 * Last Updated: $Date: 2012-03-05 16:36:15 -0500 (Mon, 05 Mar 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		20th February 2002
 * @version		$Rev: 10391 $
 *
 */
class cp_skin_status {

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
    public function __construct(ipsRegistry $registry) {
        $this->registry = $registry;
        $this->DB = $this->registry->DB();
        $this->settings = & $this->registry->fetchSettings();
        $this->request = & $this->registry->fetchRequest();
        $this->member = $this->registry->member();
        $this->memberData = & $this->registry->member()->fetchMemberData();
        $this->cache = $this->registry->cache();
        $this->caches = & $this->registry->cache()->fetchCaches();
        $this->lang = $this->registry->class_localization;
    }

    /**
     * Display attachment search form
     *
     * @param	array 	Form fields
     * @return	string	HTML
     */
    public function statusCreateForm($form) {
        $IPBHTML = "";
//--starthtml--//

        $IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>Portal</h2>        
</div>

<div class='acp-box'>
	<h3>Portal/Status</h3>        

	<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='addConf' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['tablename']}</strong>
			</td>
			<td class='field_field'>
				{$form['table']}
			</td>
		</tr>

		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['action_taken']}</strong>
			</td>
			<td class='field_field'>
				{$form['action_id']}				
			</td>
		</tr>
                              
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['user']}</strong>
			</td>
			<td class='field_field'>
				{$form['user_id']}
			</td>
		</tr>
                                
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['text']}</strong>
			</td>
			<td class='field_field'>
				{$form['text_id']}
			</td>
		</tr>

		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['date']}</strong>
			</td>
			<td class='field_field'>
				{$form['created_at']}
			</td>
		</tr>

		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['newfields']}</strong>
			</td>
			<td class='field_field'>
				{$form['valueBoolean']}
			</td>
		</tr>
		
	</table>
	
	<div class='acp-actionbar'><input type='submit' value='{$this->lang->words['acceptbutton']}' class='realbutton' accesskey='s' /></div>
            
	</form>
</div>

<script type='text/javascript'>
document.observe("dom:loaded", function(){
	var search = new ipb.Autocomplete( $('authorname'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});
</script>
HTML;

//--endhtml--//
        return $IPBHTML;
    }

}