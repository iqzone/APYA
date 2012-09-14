<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Setup skin file
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
 
class cp_skin_setup
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
 * Redirect screen for app setup
 *
 * @param	array 		Output pieces
 * @param	array 		Error pieces
 * @param	string		Next URL to redirect to
 * @return	string		HTML
 */
public function setup_redirectScreen( $output, $errors, $next_url ) {

$IPBHTML = "";
//--starthtml--//

$text = ( strtolower($this->request['type']) == 'install' ) ? $this->lang->words['type__installation'] : $this->lang->words['type__upgradet'];

$this->lang->words['type__text']	= sprintf( $this->lang->words['type__text'], $text );

$IPBHTML .= <<<EOF
<div class='information-box'>
 <h2>{$this->lang->words['ipboard_setup_title']}</h2>
 <p style='font-size:12px'>
 	<br />
 	<strong>{$this->lang->words['ipboard_welcome_setup']}</strong>
	<br />
	{$this->lang->words['type__text']}
	<br />
	<br />
EOF;
if ( is_array( $errors ) AND count( $errors ) )
{
	$IPBHTML .= "<strong>{$this->lang->words['error__display']}</strong><br /><br />";
	
	foreach( $errors as $msg )
	{
		$IPBHTML .= "<span style='color:red'>&middot; " . $msg . "</span><br />";
	}
	
$IPBHTML .= <<<EOF
	<div class='input-ok-content' style='margin-left:60px;padding:15px;width:300px'>
		<strong><a href='{$next_url}'>{$this->lang->words['error__continue']}</a></strong>
	</div>
EOF;
}
else
{
	if ( is_array( $output ) AND count( $output ) )
	{
		$IPBHTML .= "<strong>{$this->lang->words['progress_report']}</strong><br /><br />";

		foreach( $output as $msg )
		{
			$IPBHTML .= "<span style='color:green'>&middot; " . $msg . "</span><br />";
		}
	}

$IPBHTML .= <<<EOF
	<script type='text/javascript'>

	setTimeout("redirect()",1500);

	function redirect()
	{
		var url_bit     = "{$next_url}";
		acp.redirect( url_bit.replace( new RegExp( "&amp;", "g" ) , '&' ), true );
	}

	//>
	</script>
	<div class='input-ok-content' style='margin-left:60px;padding:15px;width:300px'>
		<strong><a href='$next_url'>{$this->lang->words['redir__continue']}</a></strong>
	</div>
EOF;
	

}


$IPBHTML .= <<<EOF
 </p>
</div>


EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the finished screen for app setup
 *
 * @param	array 		Data
 * @param	string		Type of setup
 * @return	string		HTML
 */
public function setup_completed_screen( $data, $type ) {

$IPBHTML = "";
//--starthtml--//

$text	= ( $type == 'install' ) ? $this->lang->words['type__installation'] : $this->lang->words['type__upgradet'];
$lang 	= sprintf( $this->lang->words['type__text_finished'], $text );

$IPBHTML .= <<<EOF
<div class='information-box'>
 <h2>{$data['title']} {$text}</h2>
 <p style='font-size:12px'>
 	<br />
 	<strong>{$this->lang->words['ipboard_welcome_setup']}</strong>
	<br />
	{$lang}
	<br /><br />
	<a href='{$this->settings['base_url']}app=core&amp;module=applications'><b>{$this->lang->words['return_to_overview']}</b></a>
 </p>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the splash start screen for app setup
 *
 * @param	array 		Data
 * @param	array 		Errors
 * @param	string		Setup type
 * @return	string		HTML
 */
public function setup_splash_screen( $data, $errors, $type ) {

$IPBHTML = "";
//--starthtml--//
$has_errors	= 0;
$text		= ( $type == 'install' ) ? $this->lang->words['install_latest'] : $this->lang->words['upgrade_latest'];
$prefix		= ( $type == 'install' ) ? $this->lang->words['type__install'] : $this->lang->words['type__upgrade'];

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['ipboard_setup_title']}</h2>
</div>

EOF;

if ( is_array( $errors ) AND count( $errors ) )
{
$has_errors = 1;
$IPBHTML .= <<<EOF
	<div class='warning'>
		<h4>{$this->lang->words['please_correct_errors']}</h4>
EOF;
	
	foreach( $errors as $msg )
	{
		$IPBHTML .= $msg . "<br />";
	}

$IPBHTML .= "</div><br />";
}

$IPBHTML .= <<<EOF
<form action="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=sql&amp;app_directory={$data['app_directory']}&amp;type={$type}&amp;version={$data['next_version']}" method='post'>
	<div class='acp-box'>
		<h3>{$prefix} {$data['title']} {$this->lang->words['applications_title']}</h3>
		<table class='ipsTable'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['current_version']}</strong>
				</td>
				<td class='field_field'>
					{$data['current_version']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$prefix} {$this->lang->words['version_suffix']}</strong>
				</td>
				<td class='field_field'>
					{$data['latest_version']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['author_suffix']}</strong>
				</td>
				<td class='field_field'>
					{$data['author']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['duplicate_tables']}</strong>
				</td>
				<td class='field_field'>
					<select name='dupe_tables' id='dupeTables'>
						<option value='skip'>{$this->lang->words['dup__skip']}</option>
						<option value='drop'>{$this->lang->words['dup__drop']}</option>
					</select>
				</td>
			</tr>
		</table>
EOF;
		if( !$has_errors )
		{
			$IPBHTML .= <<<EOF
				<div class='acp-actionbar'>
					<input type='submit' value='{$this->lang->words['continue_button']}' class='button primary' />
				</div>
EOF;
		}
		
		$IPBHTML .= <<<EOF
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}


}