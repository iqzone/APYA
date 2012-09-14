<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Archive stuff (AJAX) - Matt Mecham
 * Last Updated: $Date: 2011-05-05 12:03:47 +0100 (Thu, 05 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @since		17 November 2011
 * @version		$Revision: 8644 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_forums_ajax_archive extends ipsAjaxCommand 
{
	/**
	* Main class entry point
	*
	* @param	object		ipsRegistry reference
	* @return	@e void		[Outputs to screen]
	*/
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_archive');
		$this->form_code = $this->html->form_code = 'module=archive&amp;section=archive&amp;';
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_archive' ) );
		
		/* Load up archive class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/writer.php', 'classes_archive_writer' );
		$this->archiveWriter = new $classToLoad();
		
		$this->archiveWriter->setApp('forums');
		
    	switch( $this->request['do'] )
    	{
			case 'updateCounter':
			default:
				$this->_updateCounter();
			break;
			case 'showRestoreDialog':
				$this->_showRestoreDialog();
			break;
			case 'showAddForumDialog':
				$this->_showAddForumDialog();
			break;
			case 'saveAddForumDialog':
				$this->_saveAddForumDialog();
			break;
			case 'showAddMemberDialog':
				$this->_showAddMemberDialog();
			break;
			case 'saveAddMemberDialog':
				$this->_saveAddMemberDialog();
			break;
			case 'deleteMember':
				$this->_deleteMember();
			break;
    	}
	}
	
	/**
	 * Deletes a member
	 */
	protected function _deleteMember()
	{
		$type    = trim( $this->request['type'] );
		$id      = intval( $this->request['id'] );
		$rules   = $this->archiveWriter->getRulesFromDb();
		$int     = ( $type == 'archive' ) ? 0 : 1;
		$return  = array();
		$members = array();
		
		/* Existing names */
		$current = ( IPSLib::isSerialized( $rules[ $type ]['member']['text'] ) ) ? unserialize( $rules[ $type ]['member']['text'] ) : array();
		
		foreach( $current as $_id )
		{
			if ( $id != $_id )
			{
				$return[] = $_id;
			}
		}
    	
    	$return = array_unique( $return );
    
    	/* Update DB */
		$this->DB->replace( 'core_archive_rules',   array( 'archive_key'   => md5( 'forums_member_' . $int ),
														   'archive_app'   => 'forums',
														   'archive_field' => 'member',
														   'archive_value' => '',
														   'archive_text'  => serialize( $return ),
														   'archive_unit'  => '',
														   'archive_skip'  => $int ), array( 'archive_key' ) );
		
		/* Update members */
		if ( count( $return ) )
		{
			$members = IPSMember::load( $return, 'all' );
			
			foreach( $members as $id => $data )
			{
				$members[ $id ] = IPSMember::buildProfilePhoto( $members[ $id ]);
				$members[ $id ]['photoTag'] = IPSMember::buildPhotoTag( $members[ $id ], 'inset' );
			}
		}
    	
    	$this->returnJsonArray( array( 'count' => count( $members ), 'data' => $members, 'ids' => serialize( array_keys( $members ) ) ) );
	}
	/**
	 * Saves the add forum dialog
	 */
	protected function _saveAddMemberDialog()
	{
		$type    = trim( $this->request['type'] );
		$rules   = $this->archiveWriter->getRulesFromDb();
		$int     = ( $type == 'archive' ) ? 0 : 1;
		$return  = array();
		
		/* INIT */
		$memberName = trim( $this->request['addName'] );
		
		/* Existing names */
		$current = ( IPSLib::isSerialized( $rules[ $type ]['member']['text'] ) ) ? unserialize( $rules[ $type ]['member']['text'] ) : array();
		
		/* Fetch from DB */
    	$member = $this->DB->buildAndFetch( array( 'select' => 'member_id',
    											   'from'   => 'members',
    											   'where'  => "members_display_name = '" . $this->DB->addSlashes( $memberName ) . "'" ) );
    	
    	if ( $member['member_id'] )
    	{
    		$current[] = $member['member_id'];
    	}
    	
    	$current = array_unique( $current );
    
    	/* Update DB */
		$this->DB->replace( 'core_archive_rules',   array( 'archive_key'   => md5( 'forums_member_' . $int ),
														   'archive_app'   => 'forums',
														   'archive_field' => 'member',
														   'archive_value' => '',
														   'archive_text'  => serialize( $current ),
														   'archive_unit'  => '',
														   'archive_skip'  => $int ), array( 'archive_key' ) );
		
		/* Update members */
		if ( count( $current ) )
		{
			$members = IPSMember::load( $current, 'all' );
			
			foreach( $members as $id => $data )
			{
				$members[ $id ] = IPSMember::buildProfilePhoto( $members[ $id ]);
				$members[ $id ]['photoTag'] = IPSMember::buildPhotoTag( $members[ $id ], 'inset' );
			}
		}
    	
    	$this->returnJsonArray( array( 'count' => count( $members ), 'data' => $members, 'ids' => serialize( array_keys( $members ) ) ) );
	}
	
	/**
	 * Show the add members dialog
	 */
	protected function _showAddMemberDialog()
	{
		$type    = trim( $this->request['type'] );
		$rules   = $this->archiveWriter->getRulesFromDb();
		
		//IPSText::jsonEncodeForTemplate( $rules )
		
		$this->returnHtml( $this->html->showAddMemberDialog( $type ) );
	}
	
	/**
	 * Saves the add forum dialog
	 */
	protected function _saveAddForumDialog()
	{
		$type    = trim( $this->request['type'] );
		$rules   = $this->archiveWriter->getRulesFromDb();
		$forums  = array();
		$int     = ( $type == 'archive' ) ? 0 : 1;
		$return  = '';
		
		/* Mix in data */
		if ( is_array( $_POST['forumIds'] ) )
		{
			$forums = IPSLib::cleanIntArray( $_POST['forumIds'] );
		}
		else
		{
			$forums = array();
		}
		
		/* Update DB */
		$this->DB->replace( 'core_archive_rules',   array( 'archive_key'   => md5( 'forums_forum_' . $int ),
														   'archive_app'   => 'forums',
														   'archive_field' => 'forum',
														   'archive_value' => '',
														   'archive_text'  => serialize( $forums ),
														   'archive_unit'  => '',
														   'archive_skip'  => $int ), array( 'archive_key' ) );
		
		/* Send back serialized data */
		$return['ids'] = serialize( $forums );
		
		/* Now return the data */
		foreach( $forums as $fid )
		{
			$return['data'][ $fid ] = array( 'data' => $this->registry->class_forums->getForumbyId( $fid ),
									         'nav'  => $this->html->buildForumNav( $this->registry->class_forums->forumsBreadcrumbNav( $fid, 'showforum=', true ) ) );
		}
		
		$this->returnJsonArray( $return );
	}
	
	/**
	 * Show the add forum dialog
	 */
	protected function _showAddForumDialog()
	{
		$type    = trim( $this->request['type'] );
		$rules   = $this->archiveWriter->getRulesFromDb();
		$current = array();
		
		if ( IPSLib::isSerialized( $rules[ $type ]['forum']['text'] ) )
		{
			$current = unserialize( $rules[ $type ]['forum']['text'] );
		}
		
		$multiSelect = $this->registry->class_forums->forumsForumJump( 1, 0, 1, $current, true );
		
		$this->returnHtml( $this->html->showAddForumDialog( $multiSelect, $type ) );
	}
	
	/**
	* Shows the restore prefs
	*
	* @return	@e void		[Outputs to screen]
	*/
	protected function _showRestoreDialog()
	{
		/* Load up archive class */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/archive/restore.php', 'classes_archive_restore' );
		$archiveRestore = new $classToLoad();
		
		$archiveRestore->setApp('forums');
		
		/* Show topics waiting unarchiving */
		$restoreData = $archiveRestore->getRestoreData();
		
		$this->returnHtml( $this->html->showRestorePrefs( $restoreData ) );
	}
	
	/**
	* Returns updated count data
	*
	* @return	@e void		[Outputs to screen]
	*/
	protected function _updateCounter()
	{
		/* Get fields to save */
		$archiveFields = $this->archiveWriter->getArchiveOnFields();
		$skipFields    = $this->archiveWriter->getArchiveSkipFields();
				
		/* To save */
		$rules = array();
		
		/* Loop through and get archive data */
		foreach( $archiveFields as $k )
		{
			if ( isset( $_POST[ 'archive_field_' . $k ] ) )
			{
				$rules['archive'][ $k ] = array( 'value' => $_POST[ 'archive_field_' . $k ],
												 'text'  => isset( $_POST[ 'archive_field_' . $k . '_text' ] ) ? $_POST[ 'archive_field_' . $k . '_text' ] : '',
												 'unit'  => isset( $_POST[ 'archive_field_' . $k . '_unit' ] ) ? $_POST[ 'archive_field_' . $k . '_unit' ] : '' );
			}
		}
		
		/* Loop through and get skup data */
		foreach( $skipFields as $k )
		{
			if ( isset( $_POST[ 'skip_field_' . $k ] ) )
			{
				$rules['skip'][ $k ]       = array( 'value' => $_POST[ 'skip_field_' . $k ],
												    'text'  => isset( $_POST[ 'skip_field_' . $k . '_text' ] ) ? $_POST[ 'skip_field_' . $k . '_text' ] : '',
													'unit'  => isset( $_POST[ 'skip_field_' . $k . '_unit' ] ) ? $_POST[ 'skip_field_' . $k . '_unit' ] : '' );
			}
		}
		
		$result = $this->archiveWriter->getArchivePossibleCount( $rules );
		
		if ( $result['count'] < 1 )
		{
			$result['textString'] = $this->lang->words['archive_no_query'];
		}
		else
		{
			$result['textString'] = sprintf( $this->lang->words['archive_x_query'], $result['percentage'], $this->lang->formatNumber( $result['count'] ), $this->lang->formatNumber( $result['total'] ) );
		}
		
		$this->returnJsonArray( $result );
	}
}