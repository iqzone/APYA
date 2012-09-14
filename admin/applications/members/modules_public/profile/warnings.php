<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Warnings Management
 * Last Updated: $Date: 2012-05-25 13:17:47 -0400 (Fri, 25 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $ (Original: Mark)
 * @copyright	© 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @since		9th November 2011
 * @version		$Revision: 10798 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_profile_warnings extends ipsCommand 
{
	/**
	 * Can the member warn other users?
	 * 
	 * @var		$canWarn
	 */
	public $canWarn = false;
	
	/**
	 * List of all the reasons for a warning
	 * 
	 * @var		$reasons
	 */
	public $reasons = array();
	
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
				
		/* Do we even have warnings enabled? */
		if ( !$this->settings['warn_on'] )
		{
			$this->registry->output->showError( 'no_permission', 10260, null, null, 403 );
		}
	
		/* Load the member */	
		$memberId = intval( $this->request['member'] );
		$this->_member = IPSMember::load( $memberId );
		if ( !$this->_member['member_id'] )
		{	
			$this->_member = $this->memberData;
		}
		
		/* Is this user protected? */
		if ( $this->settings['warn_protected'] )
		{
			if ( IPSMember::isInGroup( $this->_member, explode( ',', $this->settings['warn_protected'] ) ) )
			{
				$this->registry->output->showError( 'warn_protected_member', 10265, null, null, 403 );
			}
		}
		
		/* Can we view? */
		$pass			= FALSE;
		$this->canWarn	= FALSE;
		$modType		= NULL;
		
		if( $this->memberData['member_id'] )
		{
			if( $this->memberData['g_is_supmod'] )
			{
				$pass = TRUE;
				$this->canWarn = TRUE;
				$modType = 'warn_gmod_day';
			}
			elseif( $this->memberData['is_mod'] )
			{
				$other_mgroups	= array();
				$_other_mgroups	= IPSText::cleanPermString( $this->memberData['mgroup_others'] );
				
				if( $_other_mgroups )
				{
					$other_mgroups	= explode( ",", $_other_mgroups );
				}
				
				$other_mgroups[] = $this->memberData['member_group_id'];

				$this->DB->build( array( 
										'select' => '*',
										'from'   => 'moderators',
										'where'  => "(member_id='" . $this->memberData['member_id'] . "' OR (is_group=1 AND group_id IN(" . implode( ",", $other_mgroups ) . ")))" 
								)	);
											  
				$this->DB->execute();
				
				while ( $this->moderator = $this->DB->fetch() )
				{
					if ( $this->moderator['allow_warn'] )
					{
						$pass = TRUE;
						$this->canWarn = TRUE;
						$modType = 'warn_mod_day';
						break;
					}
				}
			}
			
			if( ! $pass && $this->memberData['member_id'] == $this->_member['member_id'] )
			{
				if ( $this->settings['warn_show_own'] || $this->canWarn || in_array( $this->request['do'], array( 'acknowledge', 'do_acknowledge' ) ) )
				{
					$pass = TRUE;
				}
			}
		}
		
		if ( !$pass )
		{
			$this->registry->output->showError( 'no_permission', 10262, null, null, 403 );
		}
		
		/* Are we limited per day? */
		if ( $this->canWarn and !$this->memberData['g_access_cp'] and $this->settings[ $modType ] != -1 )
		{
			$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as dracula', 'from' => 'members_warn_logs', 'where' => "wl_moderator={$this->memberData['member_id']} AND wl_member={$this->_member['member_id']} AND wl_date>" . ( time() - 86400 ) ) );
			if ( $count['dracula'] >= $this->settings[ $modType ] )
			{
				$this->canWarn = FALSE;
			}
		}
				
		/* Load reasons */
		$this->DB->build( array( 'select' => '*', 'from' => 'members_warn_reasons', 'order' => 'wr_order' ) );
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			$this->reasons[ $row['wr_id'] ] = $row;
		}
		
		/* Output init */
		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );
		$this->registry->output->addNavigation( "{$this->lang->words['page_title_pp']}: {$this->_member['members_display_name']}", "showuser={$this->_member['member_id']}", $this->_member['members_seo_name'], 'showuser' );
		$this->registry->output->addNavigation( $this->lang->words['warnings'], "app=members&module=profile&section=warnings&member={$this->_member['member_id']}" );
		
		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------
		
		switch ( $this->request['do'] )
		{
			case 'add':
				$this->form();
				break;
				
			case 'save':
				$this->save();
				break;
				
			case 'acknowledge':
				$this->acknowledge();
				break;
				
			case 'do_acknowledge':
				$this->doAcknowledge();
				break;
		
			default:
				$this->viewWarnings();
				break;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->sendOutput();
		
	}
	
	/**
	 * Action: View Warnings
	 */
	public function viewWarnings()
	{
		$warnings = array();
		
		/* Get the count */
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as dracula', 'from' => 'members_warn_logs', 'where' => "wl_member={$this->_member['member_id']}" ) );
		
		/* Sort out pagination */
		$st = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$pagination = $this->registry->output->generatePagination( array( 
			'totalItems'		=> $count['dracula'],
			'itemsPerPage'		=> 25,
			'currentStartValue'	=> $st,
			'baseUrl'			=> "app=members&amp;module=profile&amp;section=warnings&amp;member={$this->_member['member_id']}&amp;from_app={$this->request['from_app']}&amp;from_id1={$this->request['from_id1']}&amp;from_id2={$this->request['from_id2']}",
			'seoTemplate'		=> '',
			'seoTitle'			=> '',
			)	);
		
		/* Fetch 'em */
		$this->DB->build( array(
			'select'	=> '*',
			'from'		=> 'members_warn_logs',
			'where'		=> "wl_member={$this->_member['member_id']}",
			'order'		=> 'wl_date DESC'	
			) );
		$e = $this->DB->execute();
		while ( $row = $this->DB->fetch( $e ) )
		{
			$row['wl_moderator'] = IPSMember::load( $row['wl_moderator'] );
			$warnings[ $row['wl_id'] ] = $row;
		}
		
		/* Display */
		$this->registry->output->addContent( $this->registry->output->getTemplate('profile')->listWarnings( $this->_member, $warnings, $pagination, $this->reasons, $this->canWarn ) );
		$this->registry->output->setTitle( sprintf( $this->lang->words['warnings_member'], $this->_member['members_display_name'] ) );
	}
	
	/**
	 * Show Form: Warning
	 */
	public function form( $errors=array() )
	{
		//-----------------------------------------
		// Permission Check
		//-----------------------------------------
	
		if ( !$this->canWarn )
		{
			$this->registry->output->showError( 'no_permission', 10263, null, null, 403 );
		}
		
		//-----------------------------------------
		// Work out current punishments
		//-----------------------------------------

		$currentPunishments = array();
		foreach ( array( 'mq' => 'mod_posts', 'rpa' => 'restrict_post', 'suspend' => 'temp_ban' ) as $k => $mk )
		{
			if ( $this->_member[ $mk ] )
			{
				if ( $this->_member[ $mk ] == 1 )
				{
					$currentPunishments[ $k ] = sprintf( $this->lang->words['warnings_already_'.$k.'_perm'], $this->_member['members_display_name'] );
				}
				else
				{
					$_processed = IPSMember::processBanEntry( $this->_member[ $mk ] );
					$currentPunishments[ $k ] = sprintf( $this->lang->words['warnings_already_'.$k.'_time'], $this->_member['members_display_name'], $this->lang->getDate( $_processed['date_end'], 'SHORT' ) );
				}
			}
		}

		//-----------------------------------------
		// Editor
		//-----------------------------------------
		
		$editor = array();
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		
		$editor['member'] = new $classToLoad();
		if( $this->request['note_member'] )
		{
			$editor['member']->setContent( $editor['member']->process( $_POST['note_member'] ) );
		}
		$editor['member'] = $editor['member']->show( 'note_member', array( 'autoSaveKey' => "warn-member", 'type' => 'mini', 'minimize' => TRUE ) );
		
		$editor['mod'] = new $classToLoad();
		if( $this->request['note_mods'] )
		{
			$editor['mod']->setContent( $editor['mod']->process( $_POST['note_mods'] ) );
		}
		$editor['mod'] = $editor['mod']->show( 'note_mods', array( 'autoSaveKey' => "warn-mod", 'type' => 'mini', 'minimize' => TRUE ) );
		
		//-----------------------------------------
		// Display
		//-----------------------------------------
				
		$this->registry->output->addContent( $this->registry->output->getTemplate('profile')->addWarning( $this->_member, $this->reasons, $errors, $editor, $currentPunishments ) );
		$this->registry->output->setTitle( sprintf( $this->lang->words['warnings_member_add'], $this->_member['members_display_name'] ) );
	}
	
	/**
	 * Action: Issue Warning
	 */
	public function save()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
	
		$points = 0;
		$mq = 0;
		$mq_unit = 'd';
		$rpa = 0;
		$rpa_unit = 'd';
		$suspend = 0;
		$suspend_unit = 'd';
		$banGroup = 0;
		$removePoints = 0;
		$removePointsUnit = 'd';
	
		//-----------------------------------------
		// Validate
		//-----------------------------------------
		
		$errors = array();
		
		if ( $this->request['reason'] === '' )
		{
			/* No reason selected */
			$errors['reason'] = $this->lang->words['warnings_err_reason'];
		}
		else
		{
			$reason = intval( $this->request['reason'] );
			
			/* "Other" reason selected */
			if ( !$reason )
			{
				/* Check we're actually allowed to use it */
				if ( !$this->memberData['g_access_cp'] and !$this->settings['warnings_enable_other'] )
				{
					/* Nope */
					$errors['reason'] = $this->lang->words['warnings_err_reason'];
				}
				else
				{
					/* If we select "Other", we determine the number of points and when they expire */
					$points = floatval( $this->request['points'] );
					$removePoints = intval( $this->request['remove'] );
					$removePointsUnit = $this->request['remove_unit'] == 'h' ? 'h' : 'd';
				}
			}
			/* Defined reason selected */
			else
			{
				$reason = $this->reasons[ $reason ];
				
				/* Check it's valid */
				if ( !$reason['wr_id'] )
				{
					/* Nope */
					$errors['reason'] = $this->lang->words['warnings_err_reason'];
				}
				else
				{
					/* Can we override the number of points for this reason? */
					if ( $this->memberData['g_access_cp'] or $reason['wr_points_override'] )
					{
						// Yes, get value from input
						$points = floatval( $this->request['points'] );
					}
					else
					{
						// No, take whatever the reason has set
						$points = $reason['wr_points'];
					}
					
					/* Can we override when the points expire? */
					if ( $this->memberData['g_access_cp'] or $reason['wr_remove_override'] )
					{
						// Yes, get value from input
						$removePoints = intval( $this->request['remove'] );
						$removePointsUnit = $this->request['remove_unit'] == 'h' ? 'h' : 'd';
					}
					else
					{
						// No, take whatever the reason has set
						$removePoints = intval( $reason['wr_remove'] );
						$removePointsUnit = $reason['wr_remove_unit'];
					}
				}
				
				$reason = $reason['wr_id'];
			}
			
			/* Now let's get the action */
			$newPointLevel = floatval( $this->_member['warn_level'] + $points );
			$action = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_actions', 'where' => "wa_points<={$newPointLevel}", 'order' => 'wa_points DESC', 'limit' => 1 ) );
			
			if ( $action )
			{
				/* We have an action. Can we override it's punishment? */
				if ( $action['wa_override'] )
				{
					// Yes, get values from input
					$mq = $this->request['mq_perm'] ? -1 : intval( $this->request['mq'] );
					$mq_unit = $this->request['mq_unit'];
					$rpa = $this->request['rpa_perm'] ? -1 : intval( $this->request['rpa'] );
					$rpa_unit = $this->request['rpa_unit'];
					$suspend = $this->request['suspend_perm'] ? -1 : intval( $this->request['suspend'] );
					$suspend_unit = $this->request['suspend_unit'];
					$banGroup = $this->request['ban_group'] ? intval( $this->request['ban_group_id'] ) : 0;
				}
				else
				{
					// No, do whatever the action says
					$mq = intval( $action['wa_mq'] );
					$mq_unit = $action['wa_mq_unit'];
					$rpa = intval( $action['wa_rpa'] );
					$rpa_unit = $action['wa_rpa_unit'];
					$suspend = intval( $action['wa_suspend'] );
					$suspend_unit = $action['wa_suspend_unit'];
					$banGroup = intval( $action['wa_ban_group'] );
				}
			}
			else
			{
				/* We don't have an action - are we allowed to give a custom punishment? */
				if ( $this->memberData['g_access_cp'] or $this->settings['warning_custom_noaction'] )
				{
					// Yes, get values from input
					$mq = $this->request['mq_perm'] ? -1 : intval( $this->request['mq'] );
					$mq_unit = $this->request['mq_unit'];
					$rpa = $this->request['rpa_perm'] ? -1 : intval( $this->request['rpa'] );
					$rpa_unit = $this->request['rpa_unit'];
					$suspend = $this->request['suspend_perm'] ? -1 : intval( $this->request['suspend'] );
					$suspend_unit = $this->request['suspend_unit'];
					$banGroup = $this->request['ban_group'] ? intval( $this->request['ban_group_id'] ) : 0;
				}
				else
				{
					// We're not allowed to give a punishment so this is a verbal warning only.
					// The values we set earlier during init are fine
				}
			}
		}
		
		if ( !empty( $errors ) )
		{
			return $this->form( $errors );
		}
		
		//-----------------------------------------
		// Parse
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();
		
		$noteForMember = $editor->process( $_POST['note_member'] );
		$noteForMods   = $editor->process( $_POST['note_mods'] );
				
		//-----------------------------------------
		// Save Log
		//-----------------------------------------	
		
		/* If our points are going to expire, woprk out exactly when */
		$expireDate = 0;
		if ( $removePoints )
		{
			IPSTime::setTimestamp( time() );
			if ( $removePointsUnit == 'h' )
			{
				IPSTime::add_hours( $removePoints );
			}
			else
			{
				IPSTime::add_days( $removePoints );
			}
			$expireDate = IPSTime::getTimestamp();
		}
				
		/* Log */
		$warning = array(
			'wl_member'			=> $this->_member['member_id'],
			'wl_moderator'		=> $this->memberData['member_id'],
			'wl_date'			=> time(),
			'wl_reason'			=> $reason,
			'wl_points'			=> $points,
			'wl_note_member'	=> $noteForMember,
			'wl_note_mods'		=> $noteForMods,
			'wl_mq'				=> $mq,
			'wl_mq_unit'		=> $mq_unit,
			'wl_rpa'			=> $rpa,
			'wl_rpa_unit'		=> $rpa_unit,
			'wl_suspend'		=> $suspend,
			'wl_suspend_unit'	=> $suspend_unit,
			'wl_ban_group'		=> $banGroup,
			'wl_expire'			=> $removePoints,
			'wl_expire_unit'	=> $removePointsUnit,
			'wl_acknowledged'	=> ( $this->settings['warnings_acknowledge'] ? 0 : 1 ),
			'wl_content_app'	=> trim( $this->request['from_app'] ),
			'wl_content_id1'	=> $this->request['from_id1'],
			'wl_content_id2'	=> $this->request['from_id2'],
			'wl_expire_date'	=> $expireDate,
			);
		
		/* Data Hook Location */
		$warning['actionData']  = $action;
		$warning['reasonsData'] = $this->reasons;
		IPSLib::doDataHooks( $warning, 'memberWarningPre' );
		unset( $warning['actionData'], $warning['reasonsData'] );
		
		$this->DB->insert( 'members_warn_logs', $warning );
		$warning['wl_id'] = $this->DB->getInsertId();
		
		/* Data Hook Location */
		$warning['actionData']  = $action;
		$warning['reasonsData'] = $this->reasons;
		IPSLib::doDataHooks( $warning, 'memberWarningPost' );
		unset( $warning['actionData'], $warning['reasonsData'] );
		
		//-----------------------------------------
		// Actually do it
		//-----------------------------------------
		
		$update = array();
		
		/* Add Points */
		if ( $points )
		{
			$update['warn_level'] = $this->_member['warn_level'] + $points;
		}
		
		/* Set Punishments */
		if ( $mq )
		{
			$update['mod_posts'] = ( $mq == -1 ? 1 : IPSMember::processBanEntry( array( 'unit' => $mq_unit, 'timespan' => $mq ) ) );
		}
		if ( $rpa )
		{
			$update['restrict_post'] = ( $rpa == -1 ? 1 : IPSMember::processBanEntry( array( 'unit' => $rpa_unit, 'timespan' => $rpa ) ) );
		}
		if ( $suspend )
		{
			if ( $suspend == -1 )
			{
				$update['member_banned'] = 1;
			}
			else
			{
				$update['temp_ban'] = IPSMember::processBanEntry( array( 'unit' => $suspend_unit, 'timespan' => $suspend ) );
			}
		}
		
		if ( $banGroup > 0 )
		{
			$update['member_group_id'] = $banGroup;
		}
		
		if ( $this->settings['warnings_acknowledge'] )
		{
			$update['unacknowledged_warnings'] = 1;
		}
		
		/* Save */
		if ( !empty( $update ) )
		{
			IPSMember::save( $this->_member['member_id'], array( 'core' => $update ) );
		}
		
		//-----------------------------------------
		// Work out where this warning came from
		//-----------------------------------------
		
		if ( $warning['wl_content_app'] and IPSLib::appIsInstalled( $warning['wl_content_app'] ) )
		{
			$file = IPSLib::getAppDir( $warning['wl_content_app'] ) . '/extensions/warnings.php';
			
			if ( is_file( $file ) )
			{
				$classToLoad = IPSLib::loadLibrary( $file, 'warnings_' . $warning['wl_content_app'], $warning['wl_content_app'] );
				
				if ( class_exists( $classToLoad ) and method_exists( $classToLoad, 'getContentUrl' ) )
				{
					$object = new $classToLoad();
					$content = $object->getContentUrl( $warning );
				}
			}
		}
		
		//-----------------------------------------
		// Send notifications
		//-----------------------------------------
		
		/* Init */
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );
		
		/* Send to member being warned */
		if ( $this->settings['warnings_acknowledge'] OR $noteForMember )
		{
			try
			{
				$notifyLibrary->setMember( $this->_member );
				$notifyLibrary->setFrom( $this->memberData );
				$notifyLibrary->setNotificationKey( 'warning' );
				$notifyLibrary->setNotificationUrl( "app=members&module=profile&section=warnings&member={$this->_member['member_id']}" );
				$notifyLibrary->setNotificationTitle( sprintf( $this->lang->words['warnings_notify'], $this->registry->output->buildUrl( "app=members&module=profile&section=warnings&member={$this->_member['member_id']}" ) ) );
				$notifyLibrary->setNotificationText( sprintf(
					$this->lang->words['warnings_notify_text'],
					$this->_member['members_display_name'],
					$this->memberData['members_display_name'],
					$reason ? $this->reasons[ $reason ]['wr_name'] : $this->lang->words['warnings_reasons_other'],
					$noteForMember ? sprintf( $this->lang->words['warnings_notify_member_note'], $noteForMember ) : '',
					$this->settings['warn_show_own'] ? sprintf( $this->lang->words['warnings_notify_view_link'], $this->registry->output->buildUrl( "app=members&module=profile&section=warnings&member={$this->_member['member_id']}" ) ) : ''
					) );
				$notifyLibrary->sendNotification();
			}
			catch ( Exception $e ) {}
		}
		
		/* And all mods that can warn and are super_mods (split this up because of: @link http://community.invisionpower.com/tracker/issue-36960-bad-warn-query/ */
		$mods = array();
		$mids = array();
		$gids = array();
		$canWarnMids = array();
		$canWarnGids = array();
		
		$this->DB->build( array( 'select' => 'member_id, allow_warn',
								 'from'   => 'moderators',
								 'where'  => 'is_group=0' ) );
								 
		$this->DB->execute();
		while ( $row = $this->DB->fetch() )
		{
			$mids[ $row['member_id'] ] = $row['member_id'];
			
			if ( $row['allow_warn'] )
			{
				$canWarnMids[] = $row['member_id'];
			}
		}
		
		$this->DB->build( array( 'select' => 'group_id',
								 'from'   => 'moderators',
								 'where'  => 'is_group=1 AND allow_warn=1' ) );
								 
		$this->DB->execute();
		while ( $row = $this->DB->fetch() )
		{
			$gids[]        = $row['group_id'];
			$canWarnGids[] = $row['group_id'];
		}
		
		foreach( $this->caches['group_cache'] as $id => $row )
		{
			if ( $row['g_is_supmod'] )
			{
				$gids[] = $row['g_id'];
			}
		}
		
		/* Limit this because it could go a bit wrong innit */
		if ( count( $gids ) )
		{
			$this->DB->build( array( 'select' => 'member_id',
									 'from'   => 'members',
									 'where'  => 'member_group_id IN (' . implode( ',', $gids ) . ')',
									 'limit'  => array( 0, 750 ) ) );
									 
			$this->DB->execute();
			while ( $row = $this->DB->fetch() )
			{
				$mids[ $row['member_id'] ] = $row['member_id'];
			}
		}
	
		$_mods = IPSMember::load( $mids, 'all' );
		
		if ( count( $_mods ) )
		{
			foreach( $_mods as $id => $row )
			{
				if ( $row['member_id'] == $this->memberData['member_id'] )
				{
					continue;
				}
				
				if ( $row['g_is_supmod'] OR ( in_array( $row['member_id'], $canWarnMids ) ) OR ( in_array( $row['member_group_id'], $canWarnGids ) ) )
				{
					$mods[ $row['member_id'] ] = $row;
				}
			}
		}

		if ( count( $mods ) )
		{
			$notifyLibrary		= new $classToLoad( $this->registry );
			$notifyLibrary->setMultipleRecipients( $mods );
			$notifyLibrary->setFrom( $this->memberData );
			$notifyLibrary->setNotificationKey( 'warning_mods' );
			$notifyLibrary->setNotificationUrl( "app=members&module=profile&section=warnings&member={$this->_member['member_id']}" );
			$notifyLibrary->setNotificationTitle( sprintf(
				$this->lang->words['warnings_notify_mod'],
				$this->_member['members_display_name'],
				$this->registry->output->buildUrl( "app=members&module=profile&section=warnings&member={$this->_member['member_id']}" ),
				$this->memberData['members_display_name']
				) );
			$notifyLibrary->setNotificationText( sprintf(
				$this->lang->words['warnings_notify_text_mod'],
				$this->_member['members_display_name'],
				$this->memberData['members_display_name'],
				$this->registry->output->buildUrl( "app=members&module=profile&section=warnings&member={$this->_member['member_id']}" )
				) );
				
			try
			{
				$notifyLibrary->sendNotification();
			} catch ( Exception $e ) { }
		}
		
		//-----------------------------------------
		// Boink
		//-----------------------------------------
		
		if ( empty( $content['url'] ) )
		{
			$this->registry->getClass('output')->redirectScreen( $this->lang->words['warnings_done'] , $this->settings['base_url'] . 'app=members&amp;module=profile&amp;section=warnings&amp;member=' . $this->_member['member_id'] );
		}
		else
		{
			$this->registry->getClass('output')->redirectScreen( $this->lang->words['warnings_done'] , $content['url'] );
		}	
	}
	
	/**
	 * Show Form: Acknowledge
	 */
	public function acknowledge()
	{
		$id = intval( $this->request['id'] );
		$warning = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_logs', 'where' => "wl_id={$id}" ) );
		if ( !$warning['wl_id'] or $warning['wl_member'] != $this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'no_permission', 10264, null, null, 403 );
		}
		
		$warning['wl_moderator'] = IPSMember::load( $warning['wl_moderator'] );
		$warning['wl_reason'] = $this->reasons[ $warning['wl_reason'] ];
		
		$warning['content'] = NULL;
		
		if ( $warning['wl_content_app'] and IPSLib::appIsInstalled( $warning['wl_content_app'] ) )
		{
			$file = IPSLib::getAppDir( $warning['wl_content_app'] ) . '/extensions/warnings.php';
			
			if ( is_file( $file ) )
			{
				$classToLoad = IPSLib::loadLibrary( $file, 'warnings_' . $warning['wl_content_app'], $warning['wl_content_app'] );
				
				if ( class_exists( $classToLoad ) and method_exists( $classToLoad, 'getContentUrl' ) )
				{
					$object = new $classToLoad();
					$content = $object->getContentUrl( $warning );
					
					if ( !is_null( $content ) )
					{
						$warning['content'] = "<a href='{$content['url']}'>{$content['title']}</a>";
					}
				}
			}
		}
	
		$this->registry->output->addContent( $this->registry->output->getTemplate('profile')->acknowledgeWarning( $warning ) );
		$this->registry->output->setTitle( $this->lang->words['warnings_acknowledge'] );
	}
	
	/**
	 * Action: Acknowledge
	 */
	public function doAcknowledge()
	{
		$id = intval( $this->request['id'] );
		$warning = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_logs', 'where' => "wl_id={$id}" ) );
		if ( !$warning['wl_id'] or $warning['wl_member'] != $this->_member['member_id'] or $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', 10264, null, null, 403 );
		}
		
		$this->DB->update( 'members_warn_logs', array( 'wl_acknowledged' => 1 ), "wl_id={$warning['wl_id']}" );
		
		$count = array( 'count' => 0 );
		if ( $this->settings['warnings_acknowledge'] )
		{
			$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'members_warn_logs', 'where' => "wl_member={$this->_member['member_id']} AND wl_acknowledged=0" ) );
		}
		if ( !$count['count'] and $this->memberData['unacknowledged_warnings'] )
		{
			IPSMember::save( $this->_member['member_id'], array( 'core' => array( 'unacknowledged_warnings' => 0 ) ) );
		}
		
		if ( $this->request['ref'] )
		{
			$this->registry->output->silentRedirect( $this->request['ref'] );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['warnings_acknowledged'], $this->settings['board_url'] );
		}
	}
}