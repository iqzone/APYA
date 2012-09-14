<?php
/**
 * @file		banfilters.php 	Provides helper methods to add ban filters
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2011-03-22 12:48:06 -0400 (Tue, 22 Mar 2011) $
 * @version		v3.3.3
 * $Revision: 8149 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		admin_members_members_banfilters
 * @brief		Provides helper methods to add ban filters
 */
class admin_members_members_banfilters extends ipsCommand
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load skin & setup some stuff */
		$this->html			= $this->registry->output->loadTemplate( 'cp_skin_banfilters' );
		$this->form_code	= $this->html->form_code	= 'module=members&amp;section=banfilters&amp;';
		$this->form_code_js	= $this->html->form_code_js	= 'module=members&section=banfilters&';
		
		/* Load lang */
		$this->registry->class_localization->loadLanguageFile( array( 'admin_member' ), 'members' );
				
		/* What to do... */
		switch( $this->request['do'] )
		{				
			case 'ban_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ban_manage' );
				$this->banAdd();
			break;
				
			case 'ban_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ban_remove' );
				$this->banDelete();
			break;
			
			default:
			case 'ban':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'ban_manage' );
				$this->banOverview();
			break;			
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();		
	}
	
	/**
	 * Displays the ban overview screen
	 *
	 * @return	@e void
	 */
	public function banOverview()
	{
		/* INI */
		$ban	= array();
		$ips	= array();
		$emails	= array();
		$names	= array();
		
		/* Get ban filters */
		$this->DB->build( array( 'select' => '*', 'from' => 'banfilters', 'order' => 'ban_date desc' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$ban[ $r['ban_type'] ][ $r['ban_id'] ] = $r;
		}
		
		/* IPs */
		if( isset( $ban['ip'] ) AND is_array( $ban['ip'] ) AND count( $ban['ip'] ) )
		{
			foreach( $ban['ip'] as $entry )
			{
				$entry['_date'] = $this->registry->class_localization->getDate( $entry['ban_date'], 'SHORT', 1 );
				$ips[] = $entry;
			}
		}
		
		/* Emails */
		if( isset( $ban['email'] ) AND is_array( $ban['email'] ) AND count( $ban['email'] ) )
		{
			foreach( $ban['email'] as $entry )
			{
				$entry['_date'] = $this->registry->class_localization->getDate( $entry['ban_date'], 'SHORT' );
				$emails[] = $entry;
			}
		}
		
		/* Banned Names */
		if( isset( $ban['name'] ) AND is_array( $ban['name'] ) AND count( $ban['name'] ) )
		{
			foreach( $ban['name'] as $entry )
			{
				$entry['_date'] = $this->registry->class_localization->getDate( $entry['ban_date'], 'SHORT' );
				$names[] = $entry;
			}
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->banOverview( $ips, $emails, $names );
	}
	
	/**
	 * Add a new ban filter
	 *
	 * @return	@e void
	 */
	public function banAdd()
	{
		$this->request['bantext'] = trim($this->request['bantext']);
		
		/* Error checking */
		if( ! $this->request['bantext'] )
		{
			$this->registry->output->global_message = $this->lang->words['ban_entersomething'];
			$this->banOverview();
			return;
		}
		
		/* Check for duplicate */
		$result = $this->DB->buildAndFetch( array( 'select' => '*', 
												   'from'   => 'banfilters',
												   'where'  => "ban_content='{$this->request['bantext']}' and ban_type='{$this->request['bantype']}'" 
										   )	  );
		
		if ( $result['ban_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['ban_dupe'];
			$this->banOverview();
			return;
		}
		
		/* Insert the new ban filter */
		$this->DB->insert( 'banfilters', array( 'ban_type'	  => trim($this->request['bantype']),
												'ban_content' => trim($this->request['bantext']),
												'ban_date'    => time(),
												'ban_reason'  => trim($this->request['banreason'])
												)
						  );
		
		/* Rebuild cacne and bounce */
		$this->rebuildBanCache();
		
		$this->registry->output->global_message = $this->lang->words['ban_added'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'].$this->form_code.'do=ban' );
	}
	
	/**
	 * Delete a ban filter
	 *
	 * @return	@e void
	 */
	public function banDelete()
	{
		/* INI */
		$ids = array();
		
		/* Loop through the request fields and find checked ban filters */
		foreach( $this->request as $key => $value )
		{
			if( preg_match( '/^banid_(\d+)$/', $key, $match ) )
			{
				if( $this->request[ $match[0] ] )
				{
					$ids[] = $match[1];
				}
			}
		}
		
		/* Clean the array */
		$ids = IPSLib::cleanIntArray( $ids );
		
		/* Delete any checked ban filters */
		if ( count( $ids ) )
		{
			$this->DB->delete( 'banfilters', 'ban_id IN(' . implode( ",", $ids ) . ')' );
		}
		
		/* Rebuild the cache */
		$this->rebuildBanCache();
		
		/* Bounce */
		$this->registry->output->global_message = $this->lang->words['ban_removed'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'].$this->form_code.'do=ban' );
	}
	
	/**
	 * Rebuilds the ban cache
	 *
	 * @return	@e void
	 */
	public function rebuildBanCache()
	{
		/* INI */		
		$cache = array();
		
		/* Get the ban filters */
		$this->DB->build( array( 'select' => 'ban_content', 'from' => 'banfilters', 'where' => "ban_type='ip'" ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$cache[] = $r['ban_content'];
		}

		/* Update the cache */
		$this->cache->setCache( 'banfilters', $cache, array( 'array' => 1 ) );
	}
}