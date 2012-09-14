<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Converge Allowed Methods
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	IP.Converge
 * @link		http://www.invisionpower.com
 * @since		2.1.0
 * @version		$Revision: 10721 $
 *
 */

												
$_CONVERGE_ALLOWED_METHODS = array();

/**
* CONVERGE LOG IN
* Passes info to complete local log in
*/
$_CONVERGE_ALLOWED_METHODS['requestData'] = array(
												   'in'  => array(
																	'auth_key'          => 'string',
																	'product_id'        => 'integer',
																	'email_address'     => 'string',
																	'getdata_key'       => 'string',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );
												
/**
* CONVERGE LOG IN
* Passes info to complete local log in
*/
$_CONVERGE_ALLOWED_METHODS['convergeLogIn'] = array(
												   'in'  => array(
																	'auth_key'          => 'string',
																	'product_id'        => 'integer',
																	'email_address'     => 'string',
																	'md5_once_password' => 'string',
																	'ip_address'		=> 'string',
																	'unix_join_date'    => 'integer',
																	'timezone'			=> 'integer',
																	'dst_autocorrect'   => 'integer',
																	'extra_data'        => 'string',
																	'username'			=> 'string',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );
/**
* CONVERGE LOG OUT
* Passes info to complete local log out
*/
$_CONVERGE_ALLOWED_METHODS['convergeLogOut'] = array(
													   'in'  => array(
																		'auth_key'      => 'string',
																		'product_id'    => 'integer',
																		'email_address' => 'string',
																     ),
													   'out' => array(
																		'response' => 'xmlrpc'
																	 )
													 );
													
/**
* Disable converge from the system
*/
$_CONVERGE_ALLOWED_METHODS['convergeDisable'] = array(
												   'in'  => array(
																	'auth_key'          => 'string',
																	'product_id'        => 'integer',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );

/**
* ON Member Delete
* Delete the member
*/
$_CONVERGE_ALLOWED_METHODS['onMemberDelete'] = array(
												   'in'  => array(
																	'auth_key'                 => 'string',
																	'product_id'               => 'integer',
																	'multiple_email_addresses' => 'string',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );
																																				
/**
* ON Password change
* Give the local app a chance to perform a new member log in key request
*/
$_CONVERGE_ALLOWED_METHODS['onPasswordChange'] = array(
												   'in'  => array(
																	'auth_key'          => 'string',
																	'product_id'        => 'integer',
																	'email_address'     => 'string',
																	'md5_once_password' => 'string',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );
												
/**
* ON EMAIL CHANGE
* Update the local app with the new email address
*/
$_CONVERGE_ALLOWED_METHODS['onEmailChange'] = array(
												   'in'  => array(
																	'auth_key'          => 'string',
																	'product_id'        => 'integer',
																	'old_email_address' => 'string',
																	'new_email_address' => 'string',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );

/**
* ON USERNAME CHANGE
* Update the local app with the new email address
*/
$_CONVERGE_ALLOWED_METHODS['onUsernameChange'] = array(
												   'in'  => array(
																	'auth_key'          => 'string',
																	'product_id'        => 'integer',
																	'old_username'      => 'string',
																	'new_username'      => 'string',
																	'auth'				=> 'string',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );
															
/**
* Get a  batch of members to import
* 
*/
$_CONVERGE_ALLOWED_METHODS['importMembers'] = array(
													   'in'  => array(
																		'auth_key'   => 'string',
																		'product_id' => 'integer',
																		'limit_a'    => 'integer',
																		'limit_b'    => 'integer',
																     ),
													   'out' => array(
																		'response' => 'xmlrpc'
																	 )
													 );
													
/**
* Get number of members and last ID
* 
*/
$_CONVERGE_ALLOWED_METHODS['getMembersInfo'] = array(
													   'in'  => array(
																		'auth_key'   => 'string',
																		'product_id' => 'integer',
																     ),
													   'out' => array(
																		'response' => 'xmlrpc'
																	 )
													 );

/**
* Get additional data
*
*/
$_CONVERGE_ALLOWED_METHODS['requestAdditionalData'] = array(
														'in'  => array(
																	'auth_key'		=> 'string',
																	'product_id'	=> 'integer',
																	'getdata_key'	=> 'string',
																	'data'			=> 'string',
																	),
														'out' => array(
																	'response'		=> 'xmlrpc'
																		)
														);