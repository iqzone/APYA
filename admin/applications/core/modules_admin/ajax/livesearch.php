<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Live Search
 * Last Updated: $Date: 2012-05-24 06:38:15 -0400 (Thu, 24 May 2012) $
 * </pre>
 *
 * @author 		$Author: mark $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10789 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_ajax_livesearch extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		$this->registry->class_localization->loadLanguageFile( array( 'admin_ajax' ) );
		
		/* What to do */
		switch( $this->request['do'] )
		{
			default:
			case 'search':
				$this->doSearchRequest();
			break;
		}	
	}

	/**
	 * Handles the live search
	 *
	 * @return	@e void
	 */
	public function doSearchRequest()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$search_term	= IPSText::convertCharsets( $this->request['search_term'], 'utf-8', IPS_DOC_CHAR_SET );
		$results		= array();
		$results		= array( 'members' => array(), 'groups' => array(), 'groupLangs' => false, 'settings' => array(), 'forums' => array(), 'location' => array() );
		
		if ( IPSLib::appIsInstalled('nexus') )
		{
			$results['nexus'] = array();
		}
		
		//-----------------------------------------
		// Search
		//-----------------------------------------
		
		$results = $this->_getSettings( $search_term, $results );
		$results = $this->_getFromXML( $search_term, $results );
		$results = $this->_getMembers( $search_term, $results );
		$results = $this->_checkGroups( $search_term, $results );
		$results = $this->_checkForums( $search_term, $results );
		
		if ( IPSLib::appIsInstalled('nexus') )
		{
			$results = $this->_checkNexus( $search_term, $results );
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
				
		$this->returnJsonArray( $results );
	}
	
	/**
	 * Searches for matching members
	 *
	 * @param	string		Search term
	 * @param	array 		Existing search results
	 * @return	array 		New search results
	 */
	protected function _getMembers( $term, $results )
	{
		if ( !$this->registry->getClass('class_permissions')->checkPermission( 'member_edit', 'members', 'members' ) )
		{
			$results['members'] = array();
			return $results;
		}
		
		$term	= strtolower($term);
		
		$this->DB->build( array('select'	=> 'm.*, pp.*, g.g_title',
								'from'		=> array('members' => 'm'),
								'where'		=> "members_l_username LIKE '%{$term}%' OR members_l_display_name LIKE '%{$term}%' OR " . $this->DB->buildLower('email') . " LIKE '%{$term}%'",
								'limit'     => array(0, 250 ),
								'add_join'	=> array( array( 'from'  => array( 'groups' => 'g' ),
															 'where' => 'g.g_id=m.member_group_id',
															 'type'  => 'left' ),
													  array( 'from'  => array( 'profile_portal' => 'pp' ),
															 'where' => 'pp.pp_member_id=m.member_id',
															 'type'  => 'left' ) )
						 )		);
		
		$this->DB->execute();
		
		while( $member = $this->DB->fetch() )
		{
			$_matched	= '';
			$member     = $r = IPSMember::buildProfilePhoto( $member );
			$r			= array();
			
			if( $member['members_l_display_name'] AND strpos( $term, $member['members_l_display_name'] ) !== false )
			{
				$r['_matched']	= 'members_display_name';
			}
			else if( $member['members_l_username'] AND strpos( $term, $member['members_l_username'] ) !== false )
			{
				$r['_matched']	= 'name';
			}
			else
			{
				$r['_matched']	= 'email';
			}
			
			$r['name']  = $member['members_display_name'];
			$r['extra'] = $member['g_title'];
			$r['img']   = $member['pp_mini_photo'];
			$r['url']   = $this->settings['_base_url']."&amp;app=members&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id=".$member['member_id'];
			
			/* Trim out what we don't need */
			
			$results['members'][] = $r;
		}
	
		return $results;
	}
	
	/**
	 * Check if search term is found in groups language file or in the group_cache.g_title
	 *
	 * @param	string		Search term
	 * @param	array 		Existing search results
	 * @return	array 		New search results
	 */
	protected function _checkGroups( $term, $results )
	{
		if ( !$this->registry->getClass('class_permissions')->checkPermission( 'groups_edit', 'members', 'groups' ) )
		{
			$results['groups'] = array();
			return $results;
		}
	
		$term = strtolower($term);
		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_groups' ), 'members' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_forums' ), 'forums' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_gallery' ), 'gallery' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_blog' ), 'blog' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_downloads' ), 'downloads' );
		
		foreach( $this->lang->words as $k => $v )
		{
			if( strpos( $k, 'gf_' ) !== false AND strpos( $v, $term ) !== false )
			{
				IPSDebug::fireBug( 'info', array( 'Group key found: ' . $k . ': ' . $v ) );
				
				$results['groupLangs'] = true;
				break;
			}
		}
		
		/* Now check group names */
		$groups = $this->cache->getCache('group_cache');
		
		if ( is_array( $groups ) AND count( $groups ) )
		{
			foreach( $groups as $id => $data )
			{
				$_term = preg_quote( $term, '#' );
				
				if ( preg_match( "#" . $_term . "#i", $data['g_title'] ) )
				{
					$results['groups'][] = array(
												'name'	=> IPSMember::makeNameFormatted( $data['g_title'], $data['g_id'] ),
												'url'	=> $this->settings['_base_url'] . "&amp;app=members&amp;module=groups&amp;section=groups&amp;do=edit&amp;id=" . $data['g_id'],
												); 
				}
			}
		}
	
		return $results;
	}
	
	/**
	 * Check if search term is found in groups language file
	 *
	 * @param	string		Search term
	 * @param	array 		Existing search results
	 * @return	array 		New search results
	 */
	protected function _checkForums( $term, $results )
	{
		if ( !$this->registry->getClass('class_permissions')->checkPermission( 'forums_edit', 'forums', 'forums' ) )
		{
			$results['forums'] = array();
			return $results;
		}
		
		$term				= strtolower($term);
		$results['forums']	= false;
		
		/* Fetch forums lib */
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
		$forums = new $classToLoad( $this->registry );
		$forums->strip_invisible = 1;
		$forums->forumsInit();
	
		/* Now check forum titles names */
		foreach( $forums->forum_by_id as $id => $data )
		{
			$_term = preg_quote( $term, '#' );
			
			if ( preg_match( "#" . $_term . "#i", $data['name'] ) )
			{
				$data['extra'] = ( ( $data['posts'] == null ) ? 0 : $data['posts'] ) . " posts";
				$data['url'] = $this->settings['_base_url']."&amp;app=forums&amp;module=forums&amp;section=forums&amp;do=edit&amp;f=".$data['id'];
				$results['forums'][] = $data; 
			}
		}
		
		return $results;
	}
	
	/**
	 * Searches the settings table
	 *
	 * @param	string		Search term
	 * @param	array 		Existing search results
	 * @return	array 		New search results
	 */
	protected function _getSettings( $term, $results )
	{
		if ( !$this->registry->getClass('class_permissions')->checkPermission( 'settings_manage', 'core', 'settings' ) )
		{
			$results['settings'] = array();
			return $results;
		}
		
		$term	= strtolower($term);
		
		if( !IN_DEV )
		{
			$this->DB->build( array( 'select'	=> 'c.conf_group, c.conf_title, c.conf_description, c.conf_keywords',
									 'from'		=> array( 'core_sys_conf_settings' => 'c' ),
									 'where'	=> 't.conf_title_noshow=0 AND (' . $this->DB->buildLower('c.conf_title') . " LIKE '%{$term}%' OR ". $this->DB->buildLower('c.conf_description') . " LIKE '%{$term}%' OR " . $this->DB->buildLower('c.conf_keywords') . " LIKE '%{$term}%')",
									 'add_join'	=> array( array( 'from'  => array( 'core_sys_settings_titles' => 't' ),
																 'where' => 't.conf_title_id=c.conf_group',
																 'type'  => 'left' ) )
							 )		);
		}
		else
		{
			$this->DB->build( array( 'select'	=> 'conf_group, conf_title, conf_description, conf_keywords',
									 'from'		=> 'core_sys_conf_settings',
									 'where'	=> $this->DB->buildLower('conf_title') . " LIKE '%{$term}%' OR ". $this->DB->buildLower('conf_description') . " LIKE '%{$term}%' OR " . $this->DB->buildLower('conf_keywords') . " LIKE '%{$term}%'",
							 )		);
		}
		
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['name'] = $r['conf_title'];
			$r['url'] = $this->settings['_base_url']."&amp;app=core&amp;module=settings&amp;section=settings&amp;do=setting_view&amp;conf_group=" . $r['conf_group'] . "&amp;search=" . $this->request['search_term'];
			
			$results['settings'][] = $r;
		}
	
		return $results;
	}
	
	/**
	 * Searches Nexus
	 *
	 * @param	string		Search term
	 * @param	array 		Existing search results
	 * @return	array 		New search results
	 */
	protected function _checkNexus( $term, $results )
	{
		if ( !$this->registry->getClass('class_permissions')->checkForAppAccess('nexus') )
		{
			$results['nexus'] = array();
			return $results;
		}
		
		$textTerm		= $this->DB->addSlashes( strtolower( $term ) );
		$intTerm		= intval( $term );
		$encodedTerm	= urlencode( $encodedTerm );
		
		$tempResults = array();
		
		require_once( IPSLib::getAppDir('nexus') . '/sources/customer.php' );/*noLibHook*/
		
		//-----------------------------------------
		// Is it a number?
		//-----------------------------------------
				
		if ( (string) $term == (string) $intTerm )
		{
			/* Invoice? */
			if ( $this->registry->getClass('class_permissions')->checkPermission( 'invoices_manage', 'nexus', 'payments' ) )
			{
				$invoice = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'nexus_invoices', 'where' => "i_id={$intTerm}" ) );
				if ( $invoice['i_id'] )
				{
					$tempResults['invoice'] = array( 'name' => $invoice['i_title'], 'url' => $this->settings['_base_url']."&amp;app=nexus&amp;module=payments&amp;section=invoices&amp;do=view_invoice&amp;id={$invoice['i_id']}", 'img' => $this->settings['base_acp_url'] . '/applications_addon/ips/nexus/skin_cp/images/customers/invoice.png' );
				}
			}
			
			/* Purchase? */
			if ( $this->registry->getClass('class_permissions')->checkPermission( 'purchases_view', 'nexus', 'payments' ) )
			{
				$purchase = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'nexus_purchases', 'where' => "ps_id={$intTerm}" ) );
				if ( $purchase['ps_id'] )
				{
					$tempResults['purchase'] = array( 'name' => $purchase['ps_name'], 'url' => $this->settings['_base_url']."&amp;app=nexus&amp;module=payments&amp;section=purchases&amp;id={$purchase['ps_id']}", 'img' => $this->settings['base_acp_url'] . '/applications_addon/ips/nexus/skin_cp/images/customers/purchase.png' );
				}
			}
			
			/* Support */
			if ( $this->registry->getClass('class_permissions')->checkPermission( 'sr_view', 'nexus', 'tickets' ) )
			{
				$support = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'nexus_support_requests', 'where' => "r_id={$intTerm}" ) );
				if ( $support['r_id'] )
				{
					$tempResults['support'] = array( 'name' => $support['r_title'], 'url' => $this->settings['_base_url']."&amp;app=nexus&amp;module=tickets&amp;section=view&amp;id={$support['r_id']}", 'img' => $this->settings['base_acp_url'] . '/applications_addon/ips/nexus/skin_cp/images/customers/support.png' );
				}
			}
			
			/* Customer */
			if ( $this->registry->getClass('class_permissions')->checkForModuleAccess( 'nexus', 'customers' ) )
			{
				$customer = $this->DB->buildAndFetch( array( 'select' => 'member_id, members_display_name', 'from' => 'members', 'where' => "member_id={$intTerm}" ) );
				if ( $customer['member_id'] )
				{
					if ( !preg_replace( '/\s/', '', $customer['members_display_name'] ) )
					{
						$customer['members_display_name'] = '?????';
					}
				
					$tempResults['member'] = array( 'name' => $customer['members_display_name'], 'url' => $this->settings['_base_url']."&amp;app=nexus&amp;module=customers&amp;section=view&amp;id={$customer['member_id']}", 'img' => $this->settings['base_acp_url'] . '/applications_addon/ips/nexus/skin_cp/images/customers/info.png' );
				}
			}
			
			/* Transaction? */
			if ( $this->registry->getClass('class_permissions')->checkPermission( 'transactions_manage', 'nexus', 'payments' ) )
			{
				$transaction = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'nexus_transactions', 'where' => "t_id={$intTerm}" ) );
				if ( $transaction['t_id'] )
				{
					$tempResults['transactions'] = array( 'name' => $transaction['t_id'], 'url' => $this->settings['_base_url']."&amp;app=nexus&amp;module=payments&amp;section=transactions&amp;do=view&amp;id={$transaction['i_id']}", 'img' => $this->settings['base_acp_url'] . '/applications_addon/ips/nexus/skin_cp/images/customers/transaction.png' );
				}
			}

		}
		
		//-----------------------------------------
		// Nope - text
		//-----------------------------------------
		
		else
		{
			/* License Key */
			if ( $this->registry->getClass('class_permissions')->checkPermission( 'purchases_view', 'nexus', 'payments' ) )
			{
				$lkey = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'nexus_licensekeys', 'where' => $this->DB->buildLower('lkey_key') ."='{$textTerm}'" ) );
				if ( $lkey['lkey_key'] )
				{
					$tempResults['lkey'] = array( 'name' => $lkey['lkey_key'], 'url' => $this->settings['_base_url']."&amp;app=nexus&amp;module=payments&amp;section=purchases&amp;id={$lkey['lkey_purchase']}", 'img' => $this->settings['base_acp_url'] . '/applications_addon/ips/nexus/skin_cp/images/customers/lkey.png' );
				}
			}
			
			/* Customers */
			if ( $this->registry->getClass('class_permissions')->checkForModuleAccess( 'nexus', 'customers' ) )
			{
				$customer = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'members', 'where' => $this->DB->buildLower('email') ." LIKE '%{$textTerm}%'" ) );
				if ( $customer['count'] )
				{
					if ( $customer['count'] > 50 )
					{
						$tempResults['member'] = array( 'name' => sprintf( $this->lang->words['livesearch_more'], $customer['count'] ), 'url' => "{$this->settings['base_url']}app=nexus&amp;module=customers&amp;section=search&amp;searchpag=1&amp;option=email&amp;criteria=contains&amp;value={$encodedTerm}", 'img' => $this->settings['base_acp_url'] . '/applications_addon/ips/nexus/skin_cp/images/customers/info.png' );
	
					}
					else
					{
						$this->DB->build( array( 'select' => 'member_id, email', 'from' => 'members', 'where' => $this->DB->buildLower('email') ." LIKE '%{$textTerm}%'" ) );
						$this->DB->execute();
						while ( $row = $this->DB->fetch() )
						{
							$tempResults['member'][] = array( 'name' => $row['email'], 'url' => $this->settings['_base_url']."&amp;app=nexus&amp;module=customers&amp;section=view&amp;id={$row['member_id']}", 'img' => $this->settings['base_acp_url'] . '/applications_addon/ips/nexus/skin_cp/images/customers/info.png' );
						}
					}
				}
	
				$customer = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'members', 'where' => "members_l_display_name LIKE '%{$textTerm}%'" ) );
				if ( $customer['count'] )
				{
					if ( $customer['count'] > 50 )
					{
						$tempResults['member'] = array( 'name' => sprintf( $this->lang->words['livesearch_more'], $customer['count'] ), 'url' => "{$this->settings['base_url']}app=nexus&amp;module=customers&amp;section=search&amp;searchpag=1&amp;option=email&amp;criteria=contains&amp;value={$encodedTerm}", 'img' => $this->settings['base_acp_url'] . '/applications_addon/ips/nexus/skin_cp/images/customers/info.png' );
	
					}
					else
					{
						$this->DB->build( array( 'select' => 'member_id, members_display_name', 'from' => 'members', 'where' => "members_l_display_name LIKE '%{$textTerm}%'" ) );
						$this->DB->execute();
						while ( $row = $this->DB->fetch() )
						{
							$tempResults['member'][] = array( 'name' => $row['members_display_name'], 'url' => $this->settings['_base_url']."&amp;app=nexus&amp;module=customers&amp;section=view&amp;id={$row['member_id']}", 'img' => $this->settings['base_acp_url'] . '/applications_addon/ips/nexus/skin_cp/images/customers/info.png' );
						}
					}
				}
			}
			
			/* Hosting Accounts */
			if ( $this->registry->getClass('class_permissions')->checkPermission( 'purchases_view', 'nexus', 'payments' ) )
			{
				if ( strpos( $textTerm, '.' ) === FALSE )
				{
					$hosting = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'nexus_hosting_accounts', 'where' => $this->DB->buildLower('account_username') . " LIKE '%{$textTerm}%'" ) );
					if ( $hosting['count'] )
					{
						if ( $hosting['count'] > 50 )
						{
							$tempResults['hosting'] = array( 'name' => sprintf( $this->lang->words['livesearch_more'], $customer['count'] ), 'url' => "&amp;app=nexus&amp;module=search&amp;section=search&amp;nexus_search={$encodedTerm}&amp;nexus_searchby=username", 'img' => $this->settings['base_acp_url'] . '/applications_addon/ips/nexus/skin_cp/images/nexus_icons/hosting.png' );
		
						}
						else
						{
							$this->DB->build( array( 'select' => 'ps_id, account_username', 'from' => 'nexus_hosting_accounts', 'where' => $this->DB->buildLower('account_username') . " LIKE '%{$textTerm}%'" ) );
							$this->DB->execute();
							while ( $row = $this->DB->fetch() )
							{
								$tempResults['hosting'][] = array( 'name' => $row['account_username'], 'url' => $this->settings['_base_url']."&amp;app=nexus&amp;module=payments&amp;section=purchases&amp;id={$row['ps_id']}", 'img' => $this->settings['base_acp_url'] . '/applications_addon/ips/nexus/skin_cp/images/nexus_icons/hosting.png' );
							}
						}
					}
				}
				if ( empty( $tempResults['hosting'] ) )
				{
					$hosting = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'nexus_hosting_accounts', 'where' => $this->DB->buildLower('account_domain') . " LIKE '%{$textTerm}%'" ) );
					if ( $hosting['count'] )
					{
						if ( $hosting['count'] > 50 )
						{
							$tempResults['hosting'] = array( 'name' => sprintf( $this->lang->words['livesearch_more'], $customer['count'] ), 'url' => "&amp;app=nexus&amp;module=search&amp;section=search&amp;nexus_search={$encodedTerm}&amp;nexus_searchby=domain", 'img' => $this->settings['base_acp_url'] . '/applications_addon/ips/nexus/skin_cp/images/customers/info.png' );
		
						}
						else
						{
							$this->DB->build( array( 'select' => 'ps_id, account_domain', 'from' => 'nexus_hosting_accounts', 'where' => $this->DB->buildLower('account_domain') . " LIKE '%{$textTerm}%'" ) );
							$this->DB->execute();
							while ( $row = $this->DB->fetch() )
							{
								$tempResults['hosting'][] = array( 'name' => $row['account_domain'], 'url' => $this->settings['_base_url']."&amp;app=nexus&amp;module=payments&amp;section=purchases&amp;id={$row['ps_id']}", 'img' => $this->settings['base_acp_url'] . '/applications_addon/ips/nexus/skin_cp/images/nexus_icons/hosting.png' );
							}
						}
					}
				}
			}
		}
				
		//-----------------------------------------
		// Return results
		//-----------------------------------------
		
		
		foreach ( array( 'invoice', 'purchase', 'transactions', 'lkey', 'support', 'member', 'hosting' ) as $k )
		{
			if ( isset( $tempResults[ $k ] ) )
			{
				if ( isset( $tempResults[ $k ][0] ) )
				{
					foreach ( $tempResults[ $k ] as $l )
					{
						$results['nexus'][] = $l;
					}
				}
				else
				{
					$results['nexus'][] = $tempResults[ $k ];
				}
			}
		}
				
		return $results;
	}
	
	/**
	 * Searches the XML Files
	 *
	 * @param	string		Search term
	 * @param	array 		Existing search results
	 * @return	array 		New search results
	 */
	protected function _getFromXML( $term, $results )
	{
		foreach( $this->cache->getCache('app_menu_cache') as $app => $cache )
		{
			if( IPSLib::appIsInstalled( $app ) and $this->registry->getClass('class_permissions')->checkForAppAccess( $app ) )
			{
				foreach( $cache as $entry )
				{
					if( count($entry['items']) )
					{
						foreach( $entry['items'] as $item )
						{
							if ( $this->registry->getClass('class_permissions')->checkForModuleAccess( $app, $item['module'] ) and ( !$item['rolekey'] or $this->registry->getClass('class_permissions')->checkPermission( $item['rolekey'], $app, $item['module'] ) ) )
							{
								if( $item['section'] )
								{
									$item['url']	= "section={$item['section']}&amp;" . $item['url'];
								}
		
								if( isset($item['keywords']) AND stripos( $item['keywords'], $term ) !== false )
								{
									$item['name'] = $item['title'];
									$item['url'] = $this->settings['_base_url']."&amp;app={$app}&amp;module={$item['module']}&amp;{$item['url']}";
									$results['location'][] = $item;
								}
								else if( stripos( $item['title'], $term ) !== false )
								{
									$item['name'] = $item['title'];
									$item['url'] = $this->settings['_base_url']."&amp;app={$app}&amp;module={$item['module']}&amp;{$item['url']}";
									$results['location'][] = $item;
								}
							}
						}
					}
				}
			}
		}
		
		return $results;
	}
}