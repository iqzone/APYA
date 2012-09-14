<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Fetches meta data for notifications
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

class members_class_notifications
{
	/**
	 * Construct
	 *
	 */
	public function __construct()
	{
		/* Make object */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php', 'messengerFunctions', 'members' );
		$this->messengerFunctions = new $classToLoad( $this->registry );
	}
	
	/**
	 * Fetches data by meta data
	 * Must return: authorId, content, title, date
	 * @param array $metaData
	 */
	public function getLinkedDataByMetaData( array $metaData )
	{
		/* Fetch topic */
		$topic = $this->messengerFunctions->fetchTopicDataWithMessage( $metaData['meta_id'] );
		
		/* Get participants */
		$parts = $this->messengerFunctions->fetchTopicParticipants( $metaData['meta_id'] );
		
		/* New or reply? */
		if ( ! empty( $parts[ $this->memberData['member_id'] ]['map_read_time'] ) && ! empty( $parts[ $this->memberData['member_id'] ]['map_has_unread'] ) )
		{
			/* Is reply */
			$msg = $this->messengerFunctions->fetchMessageData( $metaData['meta_id'], $topic['mt_last_msg_id'] );
			
			$topic['msg_author_id'] = $msg['msg_author_id'];
			$topic['msg_post']      = $msg['msg_post'];
			$topic['msg_date']      = $msg['msg_date'];
		}
		
		/* Is this a new topic or reply? */
		return array( 'authorId' => $topic['msg_author_id'],
					  'content'  => $this->_formatMessageForDisplay( $topic['msg_post'], $this->memberData ),
					  'title'    => $topic['mt_title'],
					  'date' 	 => $topic['msg_date'],
					  'type'	 => $this->lang->words['gbl_notify_pm'] );
	}
	
	/**
	 * Function to format the actual message (applies BBcode, etc)
	 *
	 * @param	string		Raw text
	 * @param	array 		PM data
	 * @return	string		Processed text
	 */
	private function _formatMessageForDisplay( $msgContent, $data=array() )
	{
		IPSText::resetTextClass('bbcode');
		
		$this->settings['max_emos'] = 0;

 		IPSText::getTextClass('bbcode')->parse_smilies				= 1;
 		IPSText::getTextClass('bbcode')->parse_nl2br				= 1;
 		IPSText::getTextClass('bbcode')->parse_html					= 0;
 		IPSText::getTextClass('bbcode')->parse_bbcode				= 1;
 		IPSText::getTextClass('bbcode')->parsing_section			= 'pms';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $data['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $data['mgroup_others'];
 		
 		$msgContent = IPSText::getTextClass('bbcode')->preDisplayParse( $msgContent );
 	
		return $msgContent;
	}
	
}
