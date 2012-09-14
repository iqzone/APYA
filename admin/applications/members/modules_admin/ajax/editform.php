<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Member property updater (AJAX)
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_members_ajax_editform extends ipsAjaxCommand 
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$this->registry->class_localization->loadLanguageFile( array( 'admin_member' ), 'members' );
		
    	switch( $this->request['do'] )
    	{
			default:
			case 'show':
				$this->show();
			break;
			case 'remove_photo':
				$this->remove_photo();
			break;
    	}
	}

	/**
	 * Remove user's photo
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function remove_photo()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id		= intval( $this->request['member_id'] );
		
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id );
																	
		if ( ! $member['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['m_noid'] );
			exit();
		}
		
		//-----------------------------------------
		// Allowed to upload pics for administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_photo_admin', 'members', 'members' ) )
		{
			$this->returnJsonError( $this->lang->words['m_editadmin'] );
			exit();
		}

		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/photo.php', 'classes_member_photo' );
		$photos			= new $classToLoad( $this->registry );
		$photos->remove( $member_id );

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf($this->lang->words['m_imgremlog'], $member_id ) );

		$member = IPSMember::load( $member_id );
		$member	= IPSMember::buildDisplayData( $member, 0 );

		//-----------------------------------------
		// Return
		//-----------------------------------------

		$this->returnJsonArray( array( 
										'success'			=> 1, 
										'pp_main_photo'		=> $member['pp_main_photo'], 
										'pp_main_width'		=> $member['pp_main_width'], 
										'pp_main_height'	=> $member['pp_main_height']
							)	);
	}

	/**
	 * Show the form
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	protected function show()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$name		= trim( IPSText::alphanumericalClean( $this->request['name'] ) );
		$member_id	= intval( $this->request['member_id'] );
		$output		= '';
		
		//-----------------------------------------
		// Load language and skin
		//-----------------------------------------
		
		$html = $this->registry->output->loadTemplate('cp_skin_member_form');
		
		$this->lang->loadLanguageFile( array( 'admin_member' ) );
		
		//-----------------------------------------
		// Get member data
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id, 'extendedProfile,customFields' );
		
		//-----------------------------------------
		// Got a member?
		//-----------------------------------------
		
		if ( ! $member['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['m_noid'] );
		}
		
		//-----------------------------------------
		// Return the form
		//-----------------------------------------
		
		if ( method_exists( $html, $name ) )
		{
			$output = $html->$name( $member );
		}
		else
		{
			$save_to		= '';
			$div_id			= '';
			$form_field		= '';
			$text			= '';
			$description	= '';
			$method			= '';

			switch( $name )
			{
				case 'inline_ban_member':

					if( !$this->registry->getClass('class_permissions')->checkPermission( 'member_ban', 'members', 'members' ) )
					{
						$this->returnJsonError($this->lang->words['m_noban']);
					}
					
					if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_ban_admin', 'members', 'members' ) )
					{
						$this->returnJsonError($this->lang->words['m_noban']);
					}

					//-----------------------------------------
					// INIT
					//-----------------------------------------
					
					$ban_filters 	= array( 'email' => array(), 'name' => array(), 'ip' => array() );
					$email_banned	= false;
					$ip_banned		= array();
					$name_banned	= false;
					
					//-----------------------------------------
					// Grab existing ban filters
					//-----------------------------------------
					
					$this->DB->build( array( 'select' => '*', 'from' => 'banfilters' ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						$ban_filters[ $r['ban_type'] ][] = $r['ban_content'];
					}
					
					//-----------------------------------------
					// Check name and email address
					//-----------------------------------------
					
					if( in_array( $member['email'], $ban_filters['email'] ) )
					{
						$email_banned	= true;
					}
					
					if( in_array( $member['name'], $ban_filters['name'] ) )
					{
						$name_banned	= true;
					}

					//-----------------------------------------
					// Retrieve IP addresses
					//-----------------------------------------
					
					$ip_addresses	= IPSMember::findIPAddresses( $member['member_id'] );
					
					//-----------------------------------------
					// Start form fields
					//-----------------------------------------
					
					$form['member']			= ipsRegistry::getClass('output')->formCheckbox( "ban__member", $member['member_banned'] );
					$form['email']			= ipsRegistry::getClass('output')->formCheckbox( "ban__email", $email_banned );
					$form['name']			= ipsRegistry::getClass('output')->formCheckbox( "ban__name", $name_banned );
					
					$form['note']			= ipsRegistry::getClass('output')->formCheckbox( "ban__note", 0 );
					$form['note_field']		= ipsRegistry::getClass('output')->formTextarea( "ban__note_field" );
					$form['ips']			= array();
					
					//-----------------------------------------
					// What about IPs?
					//-----------------------------------------
					
					if( is_array($ip_addresses) AND count($ip_addresses) )
					{
						foreach( $ip_addresses as $ip_address => $count )
						{
							if( in_array( $ip_address, $ban_filters['ip'] ) )
							{
								$form['ips'][ $ip_address ] = ipsRegistry::getClass('output')->formCheckbox( "ban__ip_" . str_replace( '.', '_', $ip_address ), true );
							}
							else
							{
								$form['ips'][ $ip_address ] = ipsRegistry::getClass('output')->formCheckbox( "ban__ip_" . str_replace( '.', '_', $ip_address ), false );
							}
						}
					}
					
					$member_groups = array();
					
					foreach( ipsRegistry::cache()->getCache('group_cache') as $group )
					{
						if( $group['g_id'] == $member['member_group_id'] )
						{
							$member['_group_title'] = $group['g_title'];
						}

						$member_groups[] = array( $group['g_id'], $group['g_title'] );
					}
					
					$form['groups_confirm']	= ipsRegistry::getClass('output')->formCheckbox( "ban__group_change", 0 );
					$form['groups'] 		= ipsRegistry::getClass('output')->formDropdown( "ban__group", $member_groups, $member['member_group_id'] );
					
					$output = $html->inline_ban_member_form( $member, $form );
				break;
			}
			
			if( !$output AND $method AND method_exists( $html, $method ) )
			{
				$output = $html->$method( $member, $save_to, $div_id, $form_field, $text, $description );
			}
		}

		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
		$this->returnHtml( $output );
	}
}