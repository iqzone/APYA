<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Dashboard Notifications
 * Last Updated: $Date
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
* Main loader class
*/
class dashboardNotifications__members
{
	public function __construct()
	{
		$this->settings	= ipsRegistry::fetchSettings();
		$this->lang		= ipsRegistry::getClass( 'class_localization' );
		$this->DB		= ipsRegistry::DB();
	}
	
	public function get()
	{
		/* INIT */
		$entries = array();
		
		if ( $this->settings['reg_auth_type'] == 'admin_user' or $this->settings['reg_auth_type'] == 'admin' )
		{
			if( $this->settings['reg_auth_type'] == 'admin' )
			{
				$where = "v.new_reg=1";
			}
			else
			{
				$where = "v.new_reg=1 AND v.user_verified=1";
			}

			$count = $this->DB->buildAndFetch( array( 	'select'    => 'COUNT(*) as count',
														'from'		=> array( 'validating' => 'v' ),
														'where'		=> "v.lost_pass=0 AND m.member_group_id={$this->settings['auth_group']} AND {$where}",
														'add_join'	=> array(
																		array(  'from'		=> array( 'members' => 'm' ),
																				'where'		=> 'm.member_id=v.member_id',
																				'type'		=> 'left' ) ) )		);
			$count = $count['count'];
			
			if ( $count )
			{
				$entries[] = array( $this->lang->words['cp_validations'], sprintf( $this->lang->words['cp_validations_info'], $count ) . ' <a href="' . ipsRegistry::getClass('output')->buildUrl( 'app=members&amp;module=members&amp;section=members&amp;do=members_overview&amp;type=validating', 'admin' ) . '">' . $this->lang->words['cp_validations_review'] . '</a>' );
			}
		}
				
		return $entries;
	}
}
