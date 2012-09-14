<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Setup skin file
 * Last Updated: $Date: 2012-06-05 16:14:25 -0400 (Tue, 05 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: AndyMillne $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 10867 $
 */
 
class skin_setup extends output
{
	/**
	 * Show no button
	 *
	 */
	 private $_showNoButtons = FALSE;
/**
 * Prevent our main destructor being called by this class
 *
 * @access	public
 * @return	@e void
 */
public function __destruct()
{
}

/**
 * Show install complete page
 *
 * @access	public
 * @param	array
 * @return	string		HTML
 */
public function upgrade_complete( $options ) {

$IPBHTML = "";
//--starthtml--//

$_productName    = $this->registry->fetchGlobalConfigValue('name');

$IPBHTML .= <<<EOF
<div class='message unspecified'>
EOF;
	foreach( $options as $app => $_bleh )
	{
		foreach( $options[ $app ] as $num => $data )
		{
			if ( ! $data['out'] )
			{
				continue;
			}
			
			if ( $data['app']['key'] == 'core' )
			{
				$data['app']['name'] = 'IP.Board';
			}
			
			$IPBHTML .= <<<EOF
				<strong style='font-weight:bold; font-size:14px'>Messages</strong>
				<p>{$data['out']}</p>
EOF;

		}
	}

$IPBHTML .= <<<EOF
<p>Congratulations, <a href='../../index.php'>your upgrade is complete!</a></p>
</div>
<br />
<span class='done_text'>Upgrade complete!</span>
EOF;

$IPBHTML .= <<<EOF
    <ul id='links'>
    	<li><img src='{$this->registry->output->imageUrl}/link.gif' align='absmiddle' /> <a href='{$this->settings['_admin_link']}'>Admin Control Panel</a></li>
        <li><img src='{$this->registry->output->imageUrl}/link.gif' align='absmiddle' /> <a href='http://external.ipslink.com/ipboard30/landing/?p=clientarea'>Client Area</a></li>
        <li><img src='{$this->registry->output->imageUrl}/link.gif' align='absmiddle' /> <a href='http://external.ipslink.com/ipboard30/landing/?p=docs-ipb'>Documentation</a></li>
        <li><img src='{$this->registry->output->imageUrl}/link.gif' align='absmiddle' /> <a href='http://external.ipslink.com/ipboard30/landing/?p=forums'>IPS Company Forum</a></li>
    </ul>
EOF;

return $IPBHTML;
}

/**
 * Show the page to manually run log query, with option to prune and run instead
 *
 * @access	public
 * @return	string		HTML
 */
public function upgrade_manual_queries_logs( $queries, $id=1, $TABLE='' ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<h3>Please run these queries before continuing</h3>
<div class='message unspecified'>
	You can <a href='index.php?app=upgrade&amp;section=upgrade&amp;s={$this->request['s']}&amp;do=appclass&amp;workact=logs{$id}&amp;pruneAndRun=1'>click here</a> to prune the log table '{$TABLE}' and let the upgrader apply the changes
	<br />
	<b>OR</b> 
	<br />
	Run this query manually:
	<textarea style="width:100%; height: 300px">
EOF;

if ( $queries )
{
	$IPBHTML .= "\n" . $queries;
}

$IPBHTML .= <<<EOF
	</textarea>
</div>
EOF;

return $IPBHTML;
}

/**
 * Show the install start page
 *
 * @access	public
 * @return	string		HTML
 */
public function upgrade_manual_queries( $queries, $sourceFile='' ) {

$IPBHTML = "";
//--starthtml--//

$or = '';

$IPBHTML .= <<<EOF
<h3>Please run these queries before continuing</h3>
<div class='message unspecified'>
EOF;
	if ( $sourceFile )
	{
		$or = '<u>OR</u> ';
		
		$IPBHTML .= <<<EOF
		<strong>Run this source file</strong>
		<input type='text' size='100' style='width:98%' value='source {$sourceFile};' />
		<br />
EOF;
	}
$IPBHTML .= <<<EOF
	<strong>{$or}Individual Queries</strong>
	<textarea style="width:100%; height: 300px">
EOF;

if ( $queries )
{
	$IPBHTML .= "\n" . $queries;
}

$IPBHTML .= <<<EOF
	</textarea>
</div>
EOF;

return $IPBHTML;
}


/**
 * Show the install start page
 *
 * @access	public
 * @return	string		HTML
 */
public function upgrade_ready( $name, $current, $latest) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
The upgrader is now ready to start the upgrade of <strong>$name</strong>
<br />Current Version: v{$current}
<br />Latest Version: v{$latest}
<br />
<div class='message unspecified'>
	<strong>Upgrade Options</strong>
	<ul>
		<li>
			<input type='checkbox' name='man' value='1' />
			Show me manual upgrade steps for SQL queries to prevent PHP page timeouts. <b>WARNING:</b> If you select this option, you will be shown SQL queries that you must run at your mysql command line.  If you are not comfortable doing this, please submit a ticket and our technicians will assist you, or contact your webhost for assistance.
		</li>
		<li>
			<input type='checkbox' name='helpfile' value='1' checked="checked" />
			Update my help files if changes are found
		</li>
	</ul>
</div>
<br />

<div style='float: right'>
	<input type='submit' class='nav_button' value='Start Upgrade...'>
</div>
EOF;

return $IPBHTML;
}

/**
 * Show the upgrade app options
 *
 * @access	public
 * @return	string		HTML
 */
public function upgrade_appsOptions( $options ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
You have the following options:
<div class='message unspecified'>
EOF;
	foreach( $options as $app => $_bleh )
	{
		foreach( $options[ $app ] as $num => $data )
		{
			if ( $data['app']['key'] == 'core' )
			{
				$data['app']['name'] = 'IP.Board';
			}
			
			$IPBHTML .= <<<EOF
				<strong style='font-weight:bold; font-size:15px'>{$data['app']['name']} {$data['long']}</strong>
				{$data['out']}<br />
EOF;
		}
	}

$IPBHTML .= <<<EOF
</div>
EOF;

return $IPBHTML;
}

/**
 * Show the DB override page
 *
 * @access	public
 * @return	string		HTML
 */
public function upgrade_previousSession( $session=array() ) {

$IPBHTML = "";
//--starthtml--//

$url = IPSSetUp::getSavedData('install_url');

$date = gmdate( 'r', $session['session_start_time'] );

$IPBHTML .= <<<EOF
	<div class='message error'>
		<h2>Unfinished Upgrade Detected</h2>
		<p>
			An unfinished upgrade from <em>{$date} GMT</em> has been detected.
			<br />The upgrade was on section '{$session['session_section']} - {$session['_session_get']['do']}' upgrading apps '{$session['_sd']['install_apps']}', currently on app '{$session['_sd']['appdir']}'
			<br />
			<br />
			You can continue from this point by clicking <a href='index.php?app=upgrade&amp;s={$this->request['s']}&section=apps&do=rcontinue'>here</a> or you may click the NEXT button below to start a new upgrade session.
		</p>

	</div>
EOF;

return $IPBHTML;
}

/**
 * Show the upgrader applications page
 *
 * @access	public
 * @param	array 		Applications
 * @return	string		HTML
 */
public function upgrade_apps( $apps, $notices ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='message' style='margin-top: 4px;'>
	Please select the applications you wish to upgrade.
</div>
EOF;
	foreach( array( 'core', 'ips', 'other' ) as $type )
	{
		switch( $type )
		{
			case 'core':
				$title = "Default Applications";
			break;
			case 'ips':
				$title = "IPS Applications";
			break;
			case 'other':
				$title = "Third Party Applications";
			break;
		}
		
		if ( count( $apps[ $type ] ) )
		{
			$IPBHTML .= <<<EOF
			<fieldset>
                <legend>{$title}</legend>
EOF;
		
		
			foreach( $apps[ $type ] as $key => $data )
			{
				if ( $type == 'core' )
				{
					if ( $key == 'core' )
					{
						$data['name'] = 'IP.Board';
					}
					else
					{
						continue;
					}
				}
				
				$_upav    = ( $data['_vnumbers']['current'][0] >= $data['_vnumbers']['latest'][0] ) ? 0 : 1;
				$upgrade  = ( ! $_upav ) ? "Up To Date" : "Upgrade to {$data['_vnumbers']['latest'][1]}";
				$_checked = ( $_upav and $data['_vnumbers']['current'][0] ) ? ' checked="checked"' : '';
				$_style   = ( ! $data['_vnumbers']['current'][0] OR ( ! $_upav ) ) ? 'display:none' : '';
				
				/* Not installed? */
				if ( ! $data['_vnumbers']['current'][0] )
				{
					$upgrade = "Cannot upgrade. Not installed";
					$data['_vnumbers']['current'][1] = '';
				}

//-----------------------------------------
// Yes, I know this wouldn't work for "core"
// apps, but we can just use the global folder
// for them so it's irrelevant
//-----------------------------------------

$img = is_file( IPSLib::getAppDir( $key ) . '/skin_cp/appIcon.png' ) ? $this->settings['base_url'] . '/' . CP_DIRECTORY . '/applications_addon/' . $type . '/' . $key . '/skin_cp/appIcon.png' : "../skin_cp/images/applications/{$key}.png";

$IPBHTML .=  <<<EOF
					<table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
					<tr>
						<td width='7%' valign='top' style='padding:4px'>
							<input type='checkbox' name='apps[{$key}]' value='1' {$_checked} style="{$_style}" />
						</td>
						<td width='1%' valign='top' style='padding:4px'>
							<img src='{$img}' />
						</td>
       		 	        <td width='50%' class='content'>
                    		<strong style='font-size:12px'>{$data['name']}</strong> <span style='color:gray'>{$data['_vnumbers']['current'][1]}</span>
                    	</td>
						<td width='49%' style='padding:4px'>
							$upgrade
						</td>
                	</tr>
					</table>
EOF;
				if ( count( $notices[ $key ] ) )
				{
					$IPBHTML .= "<div class='warning'><ul>";
					foreach ( $notices[ $key ] as $n )
					{
						$IPBHTML .= "<li>{$n}</li>";
					}
					$IPBHTML .= "</ul></div>";
				}
			
			}
		
		
		$IPBHTML .=  <<<EOF
		    </fieldset>
EOF;
		}
	}

	return $IPBHTML;
}

/**
 * Show the upgrade overview page
 *
 * @access	public
 * @param	bool		Files ok
 * @param	bool		Extensions ok
 * @param	array 		Extensions
 * @return	string		HTML
 */
public function upgrade_overview( $filesOK, $extensionsOK, $extensions=array()) {

$minPHP = IPSSetUp::minPhpVersion;
$minSQL = IPSSetUp::minDb_mysql;

$prefPHP = IPSSetUp::prefPhpVersion;
$prefSQL = IPSSetUp::prefDb_mysql;

/* Memory warning */
$_memLimit	= null;
$_recLimit	= 128;

if( @ini_get('memory_limit') )
{
	$_memLimit	= @ini_get('memory_limit');
}

$_filesOK      = ( $filesOK === NULL )       ? "<span style='color:gray'>Not yet checked</span>" : ( ( $filesOK === FALSE ) ? "<span style='color:red'>Failed</span>" : "<span style='color:green'>Passed</span>" );
$_extensionsOK = ( $extensionsOK === FALSE ) ? "<span style='color:red'>Failed</span>" : "<span style='color:green'>Passed</span>";

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='message unspecified'>
	<strong>System Requirements</strong>
	<br />
	<strong>PHP:</strong> v{$minPHP} or better<br />
	<strong>SQL:</strong> MySQL v{$minSQL} ({$prefSQL} or better preferred)
	<br />
	<br />
EOF;

if( $_memLimit )
{
	$_intLimit	= $_memLimit;
	$_intRec	= $_recLimit * 1024 * 1024;
	
	preg_match( '#^(\d+)(\w+)$#', strtolower($_intLimit), $match );
	
	if( $match[2] == 'g' )
	{
		$_intLimit = intval( $_intLimit ) * 1024 * 1024 * 1024;
	}
	else if ( $match[2] == 'm' )
	{
		$_intLimit = intval( $_intLimit ) * 1024 * 1024;
	}
	else if ( $match[2] == 'k' )
	{
		$_intLimit = intval( $_intLimit ) * 1024;
	}
	else
	{
		$_intLimit = intval( $_intLimit );
	}
	
	if( $_intLimit >= $_intRec )
	{
		$IPBHTML .= <<<EOF
		<strong>Memory Limit:</strong> {$_recLimit}M or better recommended<br />
		<span style='color:green;'>Your memory limit: {$_memLimit}</span>
EOF;
	}
	else
	{
		$IPBHTML .= <<<EOF
		<strong>Memory Limit:</strong> {$_recLimit}M or better <em>recommended</em><br />
		<span style='color:orange; font-weight: bold;'>Your memory limit: {$_memLimit}.<br />You can still proceed but we recommend you contact your host and request the memory limit be raised to {$_recLimit}M to prevent possible issues.</span>
EOF;
	}
}
else
{
	$IPBHTML .= <<<EOF
	<strong>Memory Limit:</strong> {$_recLimit}M or better recommended<br />
	<span style='color:orange;'>Warning: Could not determine memory limit.</span>
EOF;
}


//-----------------------------------------
// Suhosin
//-----------------------------------------

if( extension_loaded( 'suhosin' ) )
{
	$_postMaxVars	= @ini_get('suhosin.post.max_vars');
	$_reqMaxVars	= @ini_get('suhosin.request.max_vars');
	$_postMaxLen	= @ini_get('suhosin.post.max_value_length');
	$_reqMaxLen		= @ini_get('suhosin.request.max_value_length');
	$_reqMaxVar		= @ini_get('suhosin.request.max_varname_length');
	
	$_indPMV		= $_postMaxVars < 4096 ? "orange; font-weight: bold" : "green";
	$_indRMV		= $_reqMaxVars < 4096 ? "orange; font-weight: bold" : "green";
	$_indPML		= $_postMaxLen < 1000000 ? "orange; font-weight: bold" : "green";
	$_indRML		= $_reqMaxLen < 1000000 ? "orange; font-weight: bold" : "green";
	$_indRMVL		= $_reqMaxVar < 350 ? "orange; font-weight: bold" : "green";
	
	$IPBHTML .= <<<EOF
	<br />
	<br />
	<strong>Suhosin:</strong><br />
	<span style='color:orange;'>Some settings if set too low can cause problems.</span><br />
	
	<strong>suhosin.post.max_vars:</strong> 4096 or better recommended<br />
	<span style='color:{$_indPMV};'>Your value: {$_postMaxVars}.<br />Can prevent some forms (especially in the ACP) from saving properly.</span><br />
	
	<strong>suhosin.request.max_vars:</strong> 4096 or better recommended<br />
	<span style='color:{$_indRMV};'>Your value: {$_reqMaxVars}.<br />Can prevent some forms (especially in the ACP) from saving properly.</span><br />
	
	<strong>suhosin.post.max_value_length:</strong> 1000000 or better recommended<br />
	<span style='color:{$_indPML};'>Your value: {$_postMaxLen}.<br />Can prevent very large posts or other form submissions from saving properly.</span><br />
	
	<strong>suhosin.request.max_value_length:</strong> 1000000 or better recommended<br />
	<span style='color:{$_indRML};'>Your value: {$_reqMaxLen}.<br />Can prevent very large posts or other form submissions from saving properly.</span><br />
	
	<strong>suhosin.request.max_varname_length:</strong> 350 or better recommended<br />
	<span style='color:{$_indRMVL};'>Your value: {$_reqMaxVar}.<br />Can prevent long friendly URLs from loading correctly.</span><br />
EOF;
}

$IPBHTML .= <<<EOF
	<br />
	<br />
	<strong>Pre-Install Check: Files</strong>
	<br />
	<em>Required Files:</em> {$_filesOK}
	<br />
	<br />
	<strong>Pre-Install Check: PHP Extensions</strong>
	<br />
	<em>PHP Extensions Overview:</em> {$_extensionsOK}
EOF;
	
foreach( $extensions as $xt )
{
	if ( $xt['_ok'] !== TRUE )
	{
		if ( $xt['_ok'] !== 1 )
		{
			$IPBHTML .= "<br />{$xt['prettyname']} ({$xt['extensionname']}): <span style='color:red; font-weight: bold;'>FAILED</span> (<a href='{$xt['helpurl']}' target='_blank'>Click for more info</a>)";
		}
		else
		{
			$IPBHTML .= "<br />{$xt['prettyname']} ({$xt['extensionname']}) <span style='font-style: italic;'>Recommended</span>: <span style='color:orange'>WARNING</span> (<a href='{$xt['helpurl']}' target='_blank'>Click for more info</a>)";
		}
	}
	else
	{
		$IPBHTML .= "<br />{$xt['prettyname']} ({$xt['extensionname']}): <span style='color:green'>Passed</span>";
	}
}

$IPBHTML .= <<<EOF
</div>
EOF;

return $IPBHTML;
}

/**
 * Log in page
 *
 * @access	public
 * @return	string		HTML
 */
public function upgrade_login_200plus( $loginType ) {

$IPBHTML = "";
//--starthtml--//

$label = ( $loginType == 'username' ) ? 'User Name' : 'Email Address';

$IPBHTML .= <<<EOF
	<input type='hidden' name='do' value='login' />
	<div class='ipsType_sectiontitle'>Welcome to the upgrade system.</div>
	<p class='ipsType_pagedesc'>This wizard will guide you through the upgrade process.</p>
	<br />
	  <fieldset>
      <legend>Log In</legend>
      <table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
          <tr>
              <td width='30%' class='title'>{$label}:</td>
              <td width='70%' class='content'><input type='text' class='input_text'  name='username' value=''></td>
          </tr>

      	<tr>
              <td width='30%' class='title'>Password</td>
              <td width='70%' class='content'><input type='password'class='input_text'  name='password' value=''></td>
          </tr>
      </table>
  </fieldset>
EOF;

return $IPBHTML;
}

/**
 * Log in page
 *
 * @access	public
 * @return	string		HTML
 */
public function upgrade_login_300plus( $additional_data, $replace_form, $loginType='username' ) {

$IPBHTML = "";
//--starthtml--//

switch( $loginType )
{
	case 'either':
		$loginString = "Username or email";
		break;
	case 'email':
		$loginString = "Email";
		break;
	default:
	case 'username':
		$loginString = "Username";
		break;
}

if( $replace_form )
{
	$IPBHTML .= $additional_data[0];
}
else
{
	$IPBHTML .= <<<EOF
	<input type='hidden' name='do' value='login' />
EOF;

	if ( $this->request['_acpRedirect'] )
	{
		$IPBHTML .= <<<EOF
	<div class='message error'>
		A new version of an application has been detected but the upgrader has not ran yet.<br />
		You <strong>must</strong> run the upgrader before you can access the Admin CP.
	</div>
EOF;
	}
	else
	{
		$IPBHTML .= <<<EOF
	<div class='ipsType_sectiontitle'>Welcome to the upgrade system.</div>
	<p class='ipsType_pagedesc'>This wizard will guide you through the upgrade process.</p>
EOF;
	}
	
	$IPBHTML .= <<<EOF
	<br />
	  <fieldset>
      <legend>Log In</legend>
		<div id='login_controls'>
			<label for='username'>{$loginString}</label>
			<input type='text' size='20' id='username' class='input_text' name='username' value=''>

			<label for='password'>Password</label>
			<input type='password' size='20' id='password' class='input_text'  name='password' value=''>
EOF;

		if( count($additional_data) > 0 )
		{
			foreach( $additional_data as $form_html )
			{
				$IPBHTML .= $form_html;
			}
		}
		
$IPBHTML .= <<<EOF
      </div>
  </fieldset>
EOF;
}

return $IPBHTML;
}

/**
 * Show error page
 *
 * @access	public
 * @param	string		Error message
 * @return	string		HTML
 */
public function page_error($msg) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<div class='message error'>
		{$msg}
	</div>
EOF;

return $IPBHTML;
}

/**
 * Show locked page
 *
 * @access	public
 * @return	string		HTML
 */
public function page_locked() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<div class='message error'>
		INSTALLER LOCKED<br />Please delete the file "cache/installer_lock.php" to continue.
	</div>
EOF;

return $IPBHTML;
}

/**
 * Show install complete page
 *
 * @access	public
 * @param	bool		Installer was locked successfully
 * @return	string		HTML
 */
public function page_installComplete( $installLocked ) {

$IPBHTML = "";
//--starthtml--//

$_productName    = $this->registry->fetchGlobalConfigValue('name');

if ( ! $installLocked )
{
	$extra = "<div class='message error'>
				INSTALLER NOT LOCKED<br />Please disable or remove 'admin/install/index.php' immediately!
			  </div>";
}

$IPBHTML .= <<<EOF
	<br />

    <span class='done_text'>Installation complete!</span><Br /><Br />
    Congratulations, your <a href='../../index.php'>{$_productName}</a> is now installed and ready to use! Below are some 
    links you may find useful.<br /><br /><br />
    {$extra}
    <ul id='links'>
    	<li><img src='{$this->registry->output->imageUrl}/link.gif' align='absmiddle' /> <a href='{$this->settings['_admin_link']}'>Admin Control Panel</a></li>
        <li><img src='{$this->registry->output->imageUrl}/link.gif' align='absmiddle' /> <a href='http://external.ipslink.com/ipboard30/landing/?p=clientarea'>Client Area</a></li>
        <li><img src='{$this->registry->output->imageUrl}/link.gif' align='absmiddle' /> <a href='http://external.ipslink.com/ipboard30/landing/?p=docs-ipb'>Documentation</a></li>
        <li><img src='{$this->registry->output->imageUrl}/link.gif' align='absmiddle' /> <a href='http://external.ipslink.com/ipboard30/landing/?p=forums'>IPS Company Forum</a></li>
    </ul>
EOF;

return $IPBHTML;
}

/**
 * Show the install start page
 *
 * @access	public
 * @return	string		HTML
 */
public function page_install() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	The installer is now ready to complete the installation of IP.Board. Click <strong>Start</strong> to 
	begin the automatic process!<br /><br />


	      <div style='float: right'>
           <input type='submit' class='nav_button' value='Start installation...'>
       </div>
EOF;

return $IPBHTML;
}

/**
 * Show the admin info page
 *
 * @access	public
 * @return	string		HTML
 */
public function page_admin() {

$IPBHTML = "";
//--starthtml--//

$username	= htmlspecialchars($_REQUEST['username']);
$email		= htmlspecialchars($_REQUEST['email']);

$IPBHTML .= <<<EOF
	<div class='message'>
		Please complete the form carefully.<br />The details you enter here will be used to log into the board and ACP.
	</div>
	<br />
	<fieldset>
	    <legend>Your administrative account</legend>
            <table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
                <tr>
                    <td width='30%' class='title'>Username:</td>

                    <td width='70%' class='content'><input type='text' class='sql_form' name='username' value='{$username}'></td>
                </tr>
                <tr>
                    <td class='title'>Password:</td>
                    <td class='content'><input type='password' class='sql_form' name='password'></td>
                </tr>
                <tr>
                    <td class='title'>Confirm Password:</td>

                    <td class='content'><input type='password' class='sql_form' name='confirm_password'></td>
                </tr>
                <tr>
                    <td class='title'>E-mail Address:</td>
                    <td class='content'><input type='text' class='sql_form' name='email' value='{$email}'></td>
                </tr>
            </table>
        </fieldset>
EOF;

return $IPBHTML;
}

/**
 * Show the DB override page
 *
 * @access	public
 * @return	string		HTML
 */
public function page_dbOverride() {

$IPBHTML = "";
//--starthtml--//

$url = IPSSetUp::getSavedData('install_url');

$IPBHTML .= <<<EOF
	<div class='message'>
		 The database (<em>{$this->request['db_name']}</em>) you are attempting to install into has existing tables using the same prefix (<em>{$this->request['db_pre']}</em>).
		<br />You can either select to overwrite or choose a new database or table prefix.
		<br /><span style='font-weight:bold'>Or</span> did you mean to <a class='color:gray' href='{$url}/admin/upgrade/index.php'>upgrade</a>
	</div>
	<br />
	<fieldset>
		<legend>Database Override</legend>
		<table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
			<tr>
               <td width='70%' class='title'>Overwrite current database with new installation</td>
               <td width='30%' class='content'><input type='checkbox' class='sql_form' value='1' name='overwrite' ></td>
           </tr>
		</table>
	</fieldset>
	<br />
	<fieldset>
		<legend>Or Modify Your Database Details</legend>
		<table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
			<tr>
	               <td width='30%' class='title'>SQL Host:</td>
	               <td width='70%' class='content'>
	               	<input type='text' class='sql_form' value='{$this->request['db_host']}' name='db_host'>
	               </td>
	           </tr>
			<tr>
	           <td class='title'>Database Name:</td>
               <td class='content'>
               	<input type='text' class='sql_form' name='db_name' value='{$this->request['db_name']}'>
               </td>
           </tr>
           <tr>
               <td class='title'>SQL Username:</td>
               <td class='content'>
               	<input type='text' class='sql_form' name='db_user' value='{$this->request['db_user']}'>
               </td>
           </tr>
           <tr>
               <td class='title'>SQL Password:</td>
               <td class='content'>
               	<input type='password' class='sql_form' name='db_pass' value='{$_REQUEST['db_pass']}'>
               </td>
           </tr>
           <tr>
               <td class='title'>SQL Table Prefix:</td>
               <td class='content'>
               	<input type='text' class='sql_form' name='db_pre' value='{$this->request['db_pre']}'>
               </td>
           </tr>
        <!--{EXTRA.SQL}-->
		</table>
	</fieldset>
EOF;

return $IPBHTML;
}


/**
 * Collect DB info
 *
 * @access	public
 * @return	string		HTML
 */
public function page_db() {

$IPBHTML = "";
//--starthtml--//

/* 'lil hack here */
if ( is_file( DOC_IPS_ROOT_PATH . "conf_global.php" ) )
{
	$INFO = array();
	require( DOC_IPS_ROOT_PATH . 'conf_global.php' );/*noLibHook*/

	if ( is_array( $INFO ) && count($INFO) )
	{
		$this->request['db_host'] = ( $this->request['db_host'] ) ? ( $this->request['db_host'] == 'localhost' ? ( $INFO['sql_host'] ? $INFO['sql_host'] : 'localhost' ) : $this->request['db_host'] ) : 'localhost';
		$this->request['db_name'] = ( $this->request['db_name'] ) ? $this->request['db_name'] : $INFO['sql_database'];  
	}
}

$IPBHTML .= <<<EOF
	<div class='message'>
		     Ask your webhost if you are unsure about any of these settings. You must create the database before installing.
		  </div>
		<br />
		   <fieldset>
		       <legend>Database details</legend>
		       <table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
		           <tr>
		               <td width='30%' class='title'>SQL Host:</td>
		               <td width='70%' class='content'>
		               	<input type='text' class='sql_form' value='{$this->request['db_host']}' name='db_host'>
		               </td>
		           </tr>
		           <tr>
		               <td class='title'>Database Name:</td>
		               <td class='content'>
		               	<input type='text' class='sql_form' name='db_name' value='{$this->request['db_name']}'>
		               </td>
		           </tr>
		           <tr>
		               <td class='title'>SQL Username:</td>
		               <td class='content'>
		               	<input type='text' class='sql_form' name='db_user' value='{$this->request['db_user']}'>
		               </td>
		           </tr>
		           <tr>
		               <td class='title'>SQL Password:</td>
		               <td class='content'>
		               	<input type='password' class='sql_form' name='db_pass' value='{$this->request['db_pass']}'>
		               </td>
		           </tr>
		           <tr>
		               <td class='title'>SQL Table Prefix:</td>
		               <td class='content'>
		               	<input type='text' class='sql_form' name='db_pre' value='{$this->request['db_pre']}'>
		               </td>
		           </tr>
		<!--{EXTRA.SQL}-->
		       </table>
		   </fieldset>
EOF;

return $IPBHTML;
}


/**
 * Check the database to use
 *
 * @access	public
 * @param	array 		Available DB drivers
 * @return	string		HTML
 */
public function page_check_db( $drivers ) {

	$_drivers = '';

	foreach ($drivers as $k => $v)
	{
		$selected  = ($v == "Mysql") ? " selected='selected'" : "";
		$_drivers .= "<option value='".$v."'".$selected.">".strtoupper($v)."</option>\n";
	}


$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<div class='message'>
            Please select which database engine you wish to use.
        </div>
        <br />
        <fieldset>
            <legend>Database Engine</legend>
            <table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
			<tr>
                    <td width='30%' class='title'>SQL Driver:</td>
                    <td width='70%' class='content'>
                    	<select name='sql_driver' class='sql_form'>{$_drivers}</select>
                    </td>
                </tr>
            </table>
        </fieldset>
EOF;

return $IPBHTML;
}

/**
 * Show the EULA
 *
 * @access	public
 * @return	string		HTML
 */
public function page_eula() {

$_eula = nl2br( $this->registry->fetchGlobalConfigValue('license') );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<script language='javascript'>
	check_eula = function()
	{
		if( document.getElementById( 'eula' ).checked == true )
		{
			return true;
		}
		else
		{
			alert( 'You must agree to the license before continuing' );
			return false;
		}
	}
	document.getElementById( 'install-form' ).onsubmit = check_eula;
	</script>

	Please read and agree to the End User License Agreement before continuing.<br /><br />


	<div class='eula'>
	    {$_eula}
    </div>
    <br />
    
    <input type='checkbox' name='eula' id='eula'> <strong><label for='eula'>I agree to the license agreement</label></strong>

EOF;

return $IPBHTML;
}

/**
 * Ask for license key
 *
 * @access	public
 * @return	string		HTML
 */
public function page_license( $error ) {

$IPBHTML = "";
//--starthtml--//

if ( $error )
{
$IPBHTML .= <<<EOF
	<input type='hidden' name='ignoreError' value='1' />
	 <div class='message error'>{$error}</div>
EOF;
}

$IPBHTML .= <<<EOF
	
	<br />
	<fieldset>
     <legend>License Key</legend>
		<table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
	      <tr>
	          <td class='title'><b>License Key</b></td>
	          <td width='70%' class='content'><input type='text' class='sql_form' name='lkey' value='{$this->request['lkey']}'></td>
	      </tr>
	      <tr>
	          <td colspan='2'><span style='color: gray'>Entering your license key <span style='font-weight:bold'>is optional</span> but doing so entitles you to <a href='http://external.ipslink.com/ipboard30/landing/?p=license' target='_blank'>additional features and benefits</a>.</span></td>
	      </tr>
	  	</table>
	 </fieldset>
	 <br />
		
	<div class='message unspecific note'>
		<a href='http://external.ipslink.com/ipboard30/landing/?p=lkey' target='_blank'>How to locate your license key</a>
	</div>

EOF;

return $IPBHTML;
}

/**
 * Show the address info page
 *
 * @access	public
 * @param	string		Directory
 * @param	string		URL
 * @return	string		HTML
 */
public function page_address( $dir, $url ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<fieldset>
     <legend>Address details</legend>

      <table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
          <tr>
              <td width='30%' class='title'>Install Directory:</td>
              <td width='70%' class='content'><input type='text' class='sql_form' name='install_dir' value='{$dir}'></td>
          </tr>

      	<tr>
              <td width='30%' class='title'>Install Address:</td>
              <td width='70%' class='content'><input type='text' class='sql_form' name='install_url' value='{$url}'></td>
          </tr>
      </table>
  </fieldset>

EOF;

return $IPBHTML;
}

/**
 * Show the applications page
 *
 * @access	public
 * @param	array 		Applications
 * @return	string		HTML
 */
public function page_apps( $apps ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='message' style='margin-top: 4px;'>
	Please select the applications you wish to install.<br />The following applications have been detected:
</div>
EOF;
	foreach( array( 'core', 'ips', 'other' ) as $type )
	{
		switch( $type )
		{
			case 'core':
				$title = "Default Applications";
			break;
			case 'ips':
				$title = "IPS Applications";
			break;
			case 'other':
				$title = "Third Party Applications";
			break;
		}
		
		if ( count( $apps[ $type ] ) )
		{
			$IPBHTML .= <<<EOF
			<fieldset>
                <legend>{$title}</legend>
EOF;
		
		
			foreach( $apps[ $type ] as $key => $data )
			{
				if ( isset( $this->request['apps'] ) )
				{
					$_checked = isset( $this->request['apps'][ $key ] ) ? ' checked="checked" ' : '';
				}
				else
				{
					$_checked = ( $type == 'core' OR $type == 'ips' ) ? ' checked="checked" ' : '';
				}
				$_style   = ( $type == 'core' ) ? 'display:none' : '';

//-----------------------------------------
// Yes, I know this wouldn't work for "core"
// apps, but we can just use the global folder
// for them so it's irrelevant
//-----------------------------------------

$img = is_file( IPSLib::getAppDir( $key ) . '/skin_cp/appIcon.png' ) ? '../applications_addon/' . $type . '/' . $key . '/skin_cp/appIcon.png' : "../skin_cp/images/applications/{$key}.png";

$IPBHTML .=  <<<EOF
					<table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
					<tr>
       		 	        <td width='5%' class='title'>
							<input type='checkbox' name='apps[{$key}]' value='1' {$_checked} style="{$_style}" />
						</td>
						<td width='1%' valign='top' style='padding:4px'>
							<img src='{$img}' />
						</td>
       		 	        <td width='70%' class='content'>
                    		<strong>{$data['name']}</strong> <span style='color:gray'><em>By: {$data['author']}</em></span><div style='color:#777'>{$data['description']}</div>
                    	</td>
                	</tr>
					</table>
EOF;
			}
		
		
		$IPBHTML .=  <<<EOF
		    </fieldset>
EOF;
		}
	}

	return $IPBHTML;
}
	
/**
 * Show the requirements page
 *
 * @access	public
 * @param	bool		Files ok
 * @param	bool		Extensions ok
 * @param	array 		Extensions
 * @return	string		HTML
 */
public function page_requirements( $filesOK, $extensionsOK, $extensions=array(), $text='installation' ) {

$minPHP = IPSSetUp::minPhpVersion;
$minSQL = IPSSetUp::minDb_mysql;

$prefPHP = IPSSetUp::prefPhpVersion;
$prefSQL = IPSSetUp::prefDb_mysql;

/* Memory warning */
$_memLimit	= null;
$_recLimit	= 128;

if( @ini_get('memory_limit') )
{
	$_memLimit	= @ini_get('memory_limit');
}
		
$_filesOK      = ( $filesOK === NULL )       ? "<span style='color:gray'>Not yet checked</span>" : ( ( $filesOK === FALSE ) ? "<span style='color:red'>Failed</span>" : "<span style='color:green'>Passed</span>" );
$_extensionsOK = ( $extensionsOK === FALSE ) ? "<span style='color:red'>Failed</span>" : ( $extensionsOK === TRUE ? "<span style='color:green'>Passed</span>" : "<span style='color:orange;'>Warnings</span>" );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div>
    <div>
        Welcome to the installer. This wizard will guide you through the {$text} process.
    </div>
EOF;

if( $text == 'upgrade' )
{
	$IPBHTML .= <<<EOF
	<div class='message unspecific note'>
		If you are not comfortable performing the upgrade yourself, please stop now and submit a <a href='http://invisionpower.com/clients'>technical support ticket</a> so that our technicians can perform the upgrade for you.
		<br /><br />
		You should be certain that you have a complete database backup before proceeding.  By continuing, you are certifying that you have saved a database backup.
	</div>
	<br />
EOF;
}
	
$IPBHTML .= <<<EOF
    <div class='message unspecific note'>
    	If you need help using this installer, please see our <a href='http://external.ipslink.com/ipboard30/landing/?p=installation-guide' target='_blank'><b>installation guide</b></a>.
    </div>
</div>
<br />
<div class='message unspecified'>
	<strong>System Requirements</strong>
	<br />
	<strong>PHP:</strong> v{$minPHP} or better<br />
	<strong>SQL:</strong> MySQL v{$minSQL} ({$prefSQL} or better preferred)
	<br />
	<br />
EOF;

if( $_memLimit )
{
	$_intLimit	= $_memLimit;
	$_intRec	= $_recLimit * 1024 * 1024;
	
	preg_match( '#^(\d+)(\w+)$#', strtolower($_intLimit), $match );
	
	if( $match[2] == 'g' )
	{
		$_intLimit = intval( $_intLimit ) * 1024 * 1024 * 1024;
	}
	else if ( $match[2] == 'm' )
	{
		$_intLimit = intval( $_intLimit ) * 1024 * 1024;
	}
	else if ( $match[2] == 'k' )
	{
		$_intLimit = intval( $_intLimit ) * 1024;
	}
	else
	{
		$_intLimit = intval( $_intLimit );
	}
	
	if( $_intLimit >= $_intRec )
	{
		$IPBHTML .= <<<EOF
		<strong>Memory Limit:</strong> {$_recLimit}M or better recommended<br />
		<span style='color:green;'>Your memory limit: {$_memLimit}</span>
EOF;
	}
	else
	{
		$IPBHTML .= <<<EOF
		<strong>Memory Limit:</strong> {$_recLimit}M or better recommended<br />
		<span style='color:orange; font-weight: bold;'>Your memory limit: {$_memLimit}.<br />You can still proceed but we recommend you contact your host and request the memory limit be raised to {$_recLimit}M to prevent possible issues.</span>
EOF;
	}
}
else
{
	$IPBHTML .= <<<EOF
	<strong>Memory Limit:</strong> {$_recLimit}M or better recommended<br />
	<span style='color:orange;'>Warning: Could not determine memory limit.</span>
EOF;
}


//-----------------------------------------
// Suhosin
//-----------------------------------------

if( extension_loaded( 'suhosin' ) )
{
	$_postMaxVars	= @ini_get('suhosin.post.max_vars');
	$_reqMaxVars	= @ini_get('suhosin.request.max_vars');
	$_postMaxLen	= @ini_get('suhosin.post.max_value_length');
	$_reqMaxLen		= @ini_get('suhosin.request.max_value_length');
	$_reqMaxVar		= @ini_get('suhosin.request.max_varname_length');
	
	$_indPMV		= $_postMaxVars < 4096 ? "orange; font-weight: bold" : "green";
	$_indRMV		= $_reqMaxVars < 4096 ? "orange; font-weight: bold" : "green";
	$_indPML		= $_postMaxLen < 1000000 ? "orange; font-weight: bold" : "green";
	$_indRML		= $_reqMaxLen < 1000000 ? "orange; font-weight: bold" : "green";
	$_indRMVL		= $_reqMaxVar < 350 ? "orange; font-weight: bold" : "green";
	
	$IPBHTML .= <<<EOF
	<br />
	<br />
	<strong>Suhosin:</strong><br />
	<span style='color:orange;'>Some settings if set too low can cause problems.</span><br />
	
	<strong>suhosin.post.max_vars:</strong> 4096 or better recommended<br />
	<span style='color:{$_indPMV};'>Your value: {$_postMaxVars}.<br />Can prevent some forms (especially in the ACP) from saving properly.</span><br />
	
	<strong>suhosin.request.max_vars:</strong> 4096 or better recommended<br />
	<span style='color:{$_indRMV};'>Your value: {$_reqMaxVars}.<br />Can prevent some forms (especially in the ACP) from saving properly.</span><br />
	
	<strong>suhosin.post.max_value_length:</strong> 1000000 or better recommended<br />
	<span style='color:{$_indPML};'>Your value: {$_postMaxLen}.<br />Can prevent very large posts or other form submissions from saving properly.</span><br />
	
	<strong>suhosin.request.max_value_length:</strong> 1000000 or better recommended<br />
	<span style='color:{$_indRML};'>Your value: {$_reqMaxLen}.<br />Can prevent very large posts or other form submissions from saving properly.</span><br />
	
	<strong>suhosin.request.max_varname_length:</strong> 350 or better recommended<br />
	<span style='color:{$_indRMVL};'>Your value: {$_reqMaxVar}.<br />Can prevent long friendly URLs from loading correctly.</span><br />
EOF;
}

$IPBHTML .= <<<EOF
	<br />
	<br />
	<strong>Pre-Install Check: Files</strong>
	<br />
	<em>Required Files:</em> {$_filesOK}
	<br />
	<br />
	<strong>Pre-Install Check: PHP Extensions</strong>
	<br />
	<em>PHP Extensions Overview:</em> {$_extensionsOK}
EOF;
	
foreach( $extensions as $xt )
{
	if ( $xt['_ok'] !== TRUE )
	{
		if ( $xt['_ok'] !== 1 )
		{
			$IPBHTML .= "<br />{$xt['prettyname']} ({$xt['extensionname']}): <span style='color:red; font-weight: bold;'>FAILED</span> (<a href='{$xt['helpurl']}' target='_blank'>Click for more info</a>)";
		}
		else
		{
			$IPBHTML .= "<br />{$xt['prettyname']} ({$xt['extensionname']}) <span style='font-style: italic;'>Recommended</span>: <span style='color:orange'>FAILED</span> (<a href='{$xt['helpurl']}' target='_blank'>Click for more info</a>)";
		}
	}
	else
	{
		$IPBHTML .= "<br />{$xt['prettyname']} ({$xt['extensionname']}): <span style='color:green'>Passed</span>";
	}
}

$IPBHTML .= <<<EOF
</div>
EOF;

return $IPBHTML;
}

/**
 * Global template/wrapper
 *
 * @access	public
 * @param	string		Title
 * @param	string		Page content
 * @param	array 		Data
 * @param	array 		Errors
 * @param	array 		Warnings
 * @param	array 		Install step info
 * @return	string		HTML
 */
public function globalTemplate( $title, $content, $data=array(), $errors=array(), $warnings=array(), $messages=array(), $installStep=array(), $version, $appData ) {

$IPBHTML = "";
//--starthtml--//

$_cssPath        = '../setup/public';
$_productVersion = $this->registry->fetchGlobalConfigValue('version');
$_productName    = $this->registry->fetchGlobalConfigValue('name');
$app			 = ( IPS_IS_UPGRADER ) ? 'upgrade' : 'install';
$extraUrl		 = ( IPS_IS_UPGRADER ) ? '&s=' . $this->request['s'] : '';
$extraUrl		.= ( IPS_IS_UPGRADER AND $this->request['workact'] ) ? '&workact=' . $this->request['workact'] : '';
$extraUrl		.= ( IPS_IS_UPGRADER AND isset( $this->request['st'] ) ) ? '&st=' . $this->request['st'] : '';
$extraInfo       = ( IPS_IS_UPGRADER AND $version ) ? 'This Module: ' . $version . '<br />(' . $appData['name'] . ')' : '';

$IPBHTML .= <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title>IPS SetUp: {$title}</title>
		<style type='text/css' media='all'>
			@import url('{$_cssPath}/install.css');
		</style>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />	
	</head>
	<body>
		<form id='install-form' action='index.php?app={$app}{$extraUrl}&section={$this->registry->output->nextAction}' method='post'>
		<input type='hidden' name='_sd' value='{$data['savedData']}'>
		
		<div id='ipbwrapper'>
			<div id='branding'>
				<div class='main_width'>
					<div class='logo'><img src='{$this->registry->output->imageUrl}/logo.png' /></div>
				</div>	
			</div>
			<div id='primary_nav' class='clearfix'>
				<div class='main_width'>
					<ul class='ipsList_inline' id='community_app_menu'>
						<li class='active'><a href='#'>{$this->registry->output->sequenceData[$this->registry->output->currentPage]}</a></li>
					
EOF;
if ( ! IPS_IS_UPGRADER )
{
	$IPBHTML .= <<<EOF
						<li><a href='http://external.ipslink.com/ipboard30/landing/?p=installation-guide' target='_blank'><b>Installation Guide</b></a></li>
EOF;
}

$IPBHTML .= <<<EOF
					</ul>
				</div>
			</div>
			<div id='content'>
		 	    <div class='ipsLayout ipsLayout_withleft ipsLayout_largeleft clearfix'>
		 	       <div class='ipsLayout_left clearfix'>
		 	       		<div class='ipsBox'>
		 	       			<div class='ipsBox_container'>
								<ul id='progress'>

EOF;

foreach( $data['progress'] as $p )
{
	$extra = '';
	
	if ( $installStep[0] > 0 )
	{
		 $extra = ( $p[0] == 'step_doing' ) ? "<p>Step {$installStep[0]}/{$installStep[1]}</p>" : '';
	}
	
	if ( $extraInfo )
	{
		 $extra .= ( $p[0] == 'step_doing' ) ? "<p>{$extraInfo}</p>" : '';
	}
	
	$IPBHTML .= <<<EOF
	<li class='{$p[0]}'>{$p[1]}{$extra}</li>
EOF;
}

$IPBHTML .= <<<EOF
    		 	    			</ul>
    		 	    		</div>
    		 	    	</div>
    		 	 	</div>
    		 	 	<div class='ipsLayout_content clearfix'>
EOF;

	if ( count( $messages ) )
	{
		$IPBHTML .= <<<EOF
		<br />
		    <div class='message' style='overflow:auto;max-height:180px'>
EOF;

		foreach( $messages as $msg )
		{
			$IPBHTML .= "<p>{$msg}</p>\n";	
		}
		
 		$IPBHTML.= <<<EOF
		    </div><br />
EOF;
	}

	if ( count( $errors ) OR count( $warnings ) )
	{
		$IPBHTML .= <<<EOF
		<br />
		    <div class='message error' style='overflow:auto;max-height:180px'>
EOF;

		foreach( $errors as $msg )
		{
			$IPBHTML .= "<p>Error: {$msg}</p>\n";	
		}
		
		foreach( $warnings as $msg )
		{
			$IPBHTML .= "<p>Warning: {$msg}</p>\n";	
		}
		
		
 		$IPBHTML.= <<<EOF
		    </div><br />
EOF;
	}
								$IPBHTML .= <<<EOF
    		 	        <div>
    		 	        	<h3 class='maintitle'>{$_productName} {$_productVersion}</h3>
    		 	            <div class='ipsBox'>
    		 	        		<div id='contentContainer' class='ipsBox_container ipsPad'>
        		 	            {$content}
    		 	            </div>
		 	            </div>
		 	            <div style='padding-top: 17px; padding-right: 15px; padding-left: 15px'>
		 	                <div style='float: right'>
EOF;

if ( $data['hideButton'] !== TRUE AND $this->_showNoButtons !== TRUE )
{
	if ( $this->registry->output->nextAction == 'disabled' OR count( $errors ) )
	{
		$IPBHTML .= <<<EOF
		 	                    <input type='submit' class='nav_button' value='Install can not continue...' disabled='disabled' />
EOF;
	}
	else 
	{
		if( ! $this->registry->output->nextAction )
		{
			$back = my_getenv('HTTP_REFERER');
	
			$IPBHTML .= <<<EOF
	<input type='button' class='nav_button' value='< Back' onclick="window.location='{$back}';return false;" />
EOF;
		}
		$IPBHTML .= <<<EOF
		 	                    <input type='submit' class='nav_button' value='Next >' />
EOF;
	}
}

$date = date("Y");

$IPBHTML .= <<<EOF
						</div>
					</div> <!-- buttons -->
				<br />
				<br />
				<div class='copyright'>
		 	    	&copy; 
EOF;
$IPBHTML .= date("Y");
$IPBHTML .= <<<EOF
 Invision Power Services, Inc.
				</div>
			</div><!-- ipsLayout_content -->
		</div><!-- ipsLayout-->

	</div><!-- content -->
</div><!-- wrapper -->
EOF;
/* Bit of a kludge */

if ( is_array( $errors ) AND count( $errors ) )
{
	$IPBHTML .= <<<EOF
		<script type='text/javascript'>
		//<![CDATA[

		function form_redirect()
		{
			return false;
		}
		//]]>
		</script>
EOF;
}

$IPBHTML .= <<<EOF
		</form>
	
	</body>
</html>
EOF;

return $IPBHTML;
}

/**
 * AJAX page refresh template
 *
 * @access	public
 * @param	string		Output
 * @return	string		HTML
 */
public function page_refresh( $output ) {

$this->_showNoButtons = TRUE;

$output = ( is_array( $output ) AND count( $output ) ) ? $output : array( 0 => 'Proceeding..' );
$errors = array_merge( $this->registry->output->fetchWarnings(), $this->registry->output->fetchErrors() );

$HTML = <<<EOF
<script type='text/javascript'>
//<![CDATA[
setTimeout("form_redirect()",2000);

function form_redirect()
{
	document.getElementById( 'install-form' ).submit();
}
//]]>
</script>

EOF;

if ( empty( $errors ) )
{
	$HTML .= <<<EOF
	<br />
	<div class='message'>Please wait...</div>
	<br />
	<br />
	<br />
	<div style='text-align: center'>
	<img src='{$this->registry->output->imageUrl}/wait.gif' />
	<br /><br /><br />
	<ul id='auto_progress'>
EOF;
	foreach( $output as $l )
	{
		$HTML .= <<<EOF
		<li>{$l}</li>
EOF;
	}
	$HTML .= <<<EOF
	</ul>
</div>
EOF;
}
else
{
	$HTML .= <<<EOF
	<div style='float: right'>
		<input type='submit' class='nav_button' value='Continue Anyway &rarr;' />
	</div>
EOF;
}

return $HTML;
}

}