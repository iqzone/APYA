<?php
/**
 * @file		warnings.php 	Warning Log Details Popup AJAX
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mark $ (Original: Mark)
 * @since		9 Nov 2011
 * $LastChangedDate: 2012-04-23 12:09:51 -0400 (Mon, 23 Apr 2012) $
 * @version		v3.3.3
 * $Revision: 10623 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		public_members_ajax_warnings
 * @brief		Warning Log Details Popup AJAX
 */
class public_members_ajax_warnings extends ipsAjaxCommand 
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );
		
		switch ( $this->request['do'] )
		{
			case 'form':
				$this->getActionsForForm();
				break;
				
			case 'explain_points':
				$this->explainPoints();
				break;
		
			default:
				$this->showLog();
				break;
		}
	}
	
	/**
	 * Action: Get actions for warning form
	 */
	public function getActionsForForm()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
	
		$id = intval( $this->request['id'] );
		
		$manuallySetPoints = TRUE;
		$setPoints = '';
		$allowCustomPunishment = $this->settings['warning_custom_noaction'];
		$setPunishment = $this->lang->words['warnings_verbal_only'];
		$mq = 0;
		$mq_unit = 'd';
		$rpa = 0;
		$rpa_unit = 'd';
		$suspend = 0;
		$suspend_unit = 'd';
		$banGroup = FALSE;
		$allowCustomRemovePoints = TRUE;
		$removePoints = '';
		$removePointsUnit = 'd';
		
		//-----------------------------------------
		// Fetch reason
		//-----------------------------------------
				
		if ( $id != 0 )
		{
			$reason = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_reasons', 'where' => "wr_id={$id}" ) );
			
			$manuallySetPoints			= $reason['wr_points_override'];
			$setPoints					= $this->request['points'] ? floatval( $this->request['points'] ) : $reason['wr_points'];
			
			$removePoints				= $reason['wr_remove'];
			$removePointsUnit			= $reason['wr_remove_unit'];
			$allowCustomRemovePoints	= $reason['wr_remove_override'];
		}
		elseif ( $this->request['points'] !== '' )
		{
			$setPoints = $this->request['points'];
		}
		
		//-----------------------------------------
		// Load Member
		//-----------------------------------------
		
		$member = IPSMember::load( intval( $this->request['member'] ) );
		if ( !$member['member_id'] )
		{
			$this->returnJsonError("NO_MEMBER");
		}
		
		//-----------------------------------------
		// Are they already being punished?
		//-----------------------------------------
		
		$currentPunishments = array();
		foreach ( array( 'mq' => 'mod_posts', 'rpa' => 'restrict_post', 'suspend' => 'temp_ban' ) as $k => $mk )
		{
			if ( $member[ $mk ] )
			{
				if ( $member[ $mk ] == 1 )
				{
					$currentPunishments[ $k ] = sprintf( $this->lang->words['warnings_already_'.$k.'_perm'], $member['members_display_name'] );
				}
				else
				{
					$_processed = IPSMember::processBanEntry( $member[ $mk ] );
					$currentPunishments[ $k ] = sprintf( $this->lang->words['warnings_already_'.$k.'_time'], $member['members_display_name'], $this->lang->getDate( $_processed['date_end'], 'SHORT' ) );
				}
			}
		}
		
		//-----------------------------------------
		// Okay, so do we have an action here?
		//-----------------------------------------
		
		$newPointLevel = floatval( $member['warn_level'] + $setPoints );			
		$action = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_actions', 'where' => "wa_points<={$newPointLevel}", 'order' => 'wa_points DESC', 'limit' => 1 ) );
		if ( $action )
		{
			$setPunishment = array();
			foreach ( array( 'mq', 'rpa', 'suspend' ) as $k )
			{
				if ( $action[ 'wa_' . $k ] )
				{
					if ( $action[ 'wa_' . $k ] == -1 )
					{
						$text = sprintf( $this->lang->words[ 'warnings_' . $k ], $this->lang->words['warnings_permanently'] );
					}
					else
					{
						$text = sprintf( $this->lang->words[ 'warnings_' . $k ], sprintf( $this->lang->words['warnings_for'], $action[ 'wa_' . $k ], $this->lang->words[ 'warnings_time_' . $action[ 'wa_' . $k . '_unit' ] ] ) );
					}
					
					if ( $currentPunishments[ $k ] )
					{
						$text .= " <span class='error'>{$currentPunishments[ $k ]}{$this->lang->words['warnings_already_autochange']}</span>";
					}
					
					$setPunishment[] = $text;
				}
			}				
			$setPunishment = empty( $setPunishment ) ? $this->lang->words['warnings_verbal_only'] : implode( '<br />', $setPunishment );
			
			$allowCustomPunishment = $action['wa_override'];
			
			$mq = $action['wa_mq'];
			$mq_unit = $action['wa_mq_unit'];
			$rpa = $action['wa_rpa'];
			$rpa_unit = $action['wa_rpa_unit'];
			$suspend = $action['wa_suspend'];
			$suspend_unit = $action['wa_suspend_unit'];
			$banGroup = $action['wa_ban_group'];
		}
		
		$this->returnJsonArray( array(
			'id'						=> $id,
			'manuallySetPoints'			=> $this->memberData['g_access_cp'] ? TRUE : $manuallySetPoints,
			'setPoints'					=> $setPoints,
			'allowCustomPunishment'		=> $this->memberData['g_access_cp'] ? TRUE : $allowCustomPunishment,
			'setPunishment'				=> $setPunishment,
			'allowCustomRemovePoints'	=> $this->memberData['g_access_cp'] ? TRUE : $allowCustomRemovePoints,
			'removePoints'				=> $removePoints,
			'removePointsUnit'			=> $removePointsUnit,
			'mq'						=> $mq,
			'mq_unit'					=> $mq_unit,
			'rpa'						=> $rpa,
			'rpa_unit'					=> $rpa_unit,
			'suspend'					=> $suspend,
			'suspend_unit'				=> $suspend_unit,
			'banGroup'					=> $banGroup,
			) );
	}
	
	/**
	 * Action: Explain Points
	 * 
	 * @return	@e void
	 */
	public function explainPoints()
	{
		/* Init vars */
		$reasons = array();
		$actions = array();
		
		/* Load data from DB */
		$this->DB->build( array( 'select' => '*', 'from' => 'members_warn_reasons', 'order' => 'wr_order' ) );
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			$reasons[ $row['wr_id'] ] = $row;
		}
		
		$this->DB->build( array( 'select' => '*', 'from' => 'members_warn_actions', 'order' => 'wa_points ASC' ) );
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			$actions[ $row['wa_id'] ] = $row;
		}
	
		$this->returnHtml( $this->registry->output->getTemplate('profile')->explainPoints( $reasons, $actions ) );
	}
	
	/**
	 * Action: Show Log Popup
	 * 
	 * @return	@e void
	 */
	public function showLog()
	{	
		/* Load it */
		$id = intval( $this->request['id'] );
		$warning = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_logs', 'where' => "wl_id={$id}" ) );
		
		if ( !$warning['wl_id'] )
		{
			$this->returnJsonError("NO_LOG");
		}
		
		/* Can we view it? */
		$canSeeModNote = FALSE;
		
		if ( $this->memberData['g_is_supmod'] or $this->memberData['is_mod'] )
		{
			$canSeeModNote = TRUE;
		}
		elseif ( ! $this->settings['warn_show_own'] OR $this->memberData['member_id'] != $warning['wl_member'] )
		{
			$this->returnJsonError("NO_PERMISSION");
		}
		
		//-----------------------------------------
		// Parse
		//-----------------------------------------
		
		/* Reason */
		if ( $warning['wl_reason'] )
		{
			$reason = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_reasons', 'where' => 'wr_id=' . intval( $warning['wl_reason'] ) ) );
			$warning['wl_reason'] = $reason['wr_name'];
		}
		if ( !$warning['wl_reason'] )
		{
			$warning['wl_reason'] = $this->lang->words['warnings_reasons_other'];
		}
		
		/* Moderator */
		$warning['wl_moderator'] = IPSMember::load( $warning['wl_moderator'] );
		
		/* Content */
		$warning['content'] = "<em>{$this->lang->words['warnings_content_unknown']}</em>";
		
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
		
		/* Notes */
		IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
		IPSText::getTextClass('bbcode')->parse_smilies			= 1;
		IPSText::getTextClass('bbcode')->parse_html				= 0;
		IPSText::getTextClass('bbcode')->parse_nl2br			= 1;
		IPSText::getTextClass('bbcode')->parsing_section		= 'warn';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $warning['wl_moderator']['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $warning['wl_moderator']['mgroup_others'];
		
		$warning['wl_note_member'] = IPSText::getTextClass('bbcode')->preDisplayParse( $warning['wl_note_member'] );
		$warning['wl_note_mods']   = IPSText::getTextClass('bbcode')->preDisplayParse( $warning['wl_note_mods'] );
		
		//-----------------------------------------
		// Display
		//-----------------------------------------
						
		$this->returnHtml( $this->registry->output->getTemplate('global')->warnDetails( $warning, $canSeeModNote ) );
	}
}