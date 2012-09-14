<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Forum permissions mappings
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Member Synchronization extensions
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage  Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $ 
 */
class forumsMemberSync
{
	/**
	 * Registry reference
	 *
	 * @var		object
	 */
	public $registry;
	
	/**
	 * CONSTRUCTOR
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
	}
	
	/**
	 * This method is run when a member is flagged as a spammer
	 *
	 * @param	array 	$member	Array of member data
	 * @return	@e void
	 */
	public function onSetAsSpammer( $member )
	{

	}
	
	/**
	 * This method is run when a member is un-flagged as a spammer
	 *
	 * @param	array 	$member	Array of member data
	 * @return	@e void
	 */
	public function onUnSetAsSpammer( $member )
	{

	}
	
	/**
	 * This method is run when a new account is created
	 *
	 * @param	array 	$member	Array of member data
	 * @return	@e void
	 */
	public function onCreateAccount( $member )
	{

	}
	
	/**
	 * This method is run when the register form is displayed to a user
	 *
	 * @return	@e void
	 */
	public function onRegisterForm()
	{

	}
	
	/**
	 * This method is run when a user successfully logs in
	 *
	 * @param	array 	$member	Array of member data
	 * @return	@e void
	 */
	public function onLogin( $member )
	{

	}
	
	/**
	 * This method is called after a member account has been removed
	 *
	 * @param	string	$ids	SQL IN() clause
	 * @return	@e void
	 */
	public function onDelete( $mids )
	{

	}
	
	/**
	 * This method is called after a member's account has been merged into another member's account
	 *
	 * @param	array	$member		Member account being kept
	 * @param	array	$member2	Member account being removed
	 * @return	@e void
	 */
	public function onMerge( $member, $member2 )
	{

	}
	
	/**
	 * This method is run after a users email address is successfully changed
	 *
	 * @param  integer  $id         Member ID
	 * @param  string   $new_email  New email address
	 * @param  string	$old_email	Old email address
	 * @return void
	 */
	public function onEmailChange( $id, $new_email, $old_email )
	{

	}
	
	/**
	 * This method is run after a users password is successfully changed
	 *
	 * @param	integer	$id						Member ID
	 * @param	string	$new_plain_text_pass	The new password
	 * @return	@e void
	 */
	public function onPassChange( $id, $new_plain_text_pass )
	{

	}
	
	/**
	 * This method is run after a users profile is successfully updated
	 * $member will contain EITHER 'member_id' OR 'email' depending on what data was passed to
	 * IPSMember::save().
	 *
	 * @param	array 	$member		Array of values that were changed
	 * @return	@e void
	 */
	public function onProfileUpdate( $member )
	{

	}
	
	/**
	 * This method is run after a users group is successfully changed
	 *
	 * @param	integer	$id			Member ID
	 * @param	integer	$new_group	New Group ID
	 * @param	integer	$old_group	Old Group ID
	 * @return	@e void
	 */
	public function onGroupChange( $id, $new_group, $old_group )
	{

	}
	
	/**
	 * This method is run after a users display name is successfully changed
	 *
	 * @param	integer	$id			Member ID
	 * @param	string	$new_name	New display name
	 * @return	@e void
	 */
	public function onNameChange( $id, $new_name )
	{

	}
	
	/**
	 * This method is run when a user logs out
	 *
	 * @param	array 	$member	Array of member data
	 * @return	@e void
	 */
	public function onLogOut( $member )
	{
		
	}
}