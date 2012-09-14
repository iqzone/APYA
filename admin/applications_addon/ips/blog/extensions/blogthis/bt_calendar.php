<?php
/**
* Blog This Module for CALENDAR
*
* @package		IP.Blog
* @author		Mark Wade
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/
class bt_calendar implements iBlogThis
{	
	/**
	 * CONSTRUCTOR
	 *
	 * @param  array   $blog      Array of data from the current blog
	 * @param  object  $registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $app='core', $incoming=array() )
	{
		if ( ! $this->registry )
		{
			/* Make registry objects */
			$this->registry		=  $registry;
			$this->DB			=  $this->registry->DB();
			$this->settings		=& $this->registry->fetchSettings();
			$this->request		=& $this->registry->fetchRequest();
			$this->lang			=  $this->registry->getClass('class_localization');
			$this->member		=  $this->registry->member();
			$this->memberData	=& $this->registry->member()->fetchMemberData();
			$this->cache		=  $this->registry->cache();
			$this->caches		=& $this->registry->cache()->fetchCaches();
		}
		
		$this->_incoming = $incoming;
	}
	
	/**
	 * check permission
	 *
	 * @return boolean
	 */
	public function checkPermission()
	{
		$this->event = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'cal_events', 'where' => 'event_id=' . intval( $this->_incoming['id1'] ) ) );
		if ( !$this->event['event_id'] or $this->event['event_private'] )
		{
			return false;
		}
		
		if ( $this->event['event_perms'] != '*' )
		{
			$permCheck = array_intersect_assoc( explode( ',', IPSText::cleanPermString( $this->event['event_perms'] ) ), $this->member->perm_id_array );
			if ( empty( $permCheck ) )
			{
				return false;
			}
		}
		
		return ipsRegistry::getClass( 'permissions' )->check( 'view', $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'permission_index', 'where' => "app='calendar' AND perm_type='calendar' AND perm_type_id=" . intval( $this->event['event_calendar_id'] ) ) ) );
	}
	
	/**
	 * Returns the data for the items
	 * Data should be post textarea ready
	 *
	 * @return	array( title / content )
	 */
	public function fetchData()
	{
		$return = array( 'title' => '', 'content' => '', 'topicData' => array() );
		
		/* Check permission first */
		if ( ! $this->checkPermission() )
		{
			return $return;
		}
		
		/* Get Data */
		$return['title'] = $this->event['event_title'];
		$author = IPSMember::load( $this->event['event_member_id'] );
		$author = $author['member_id'];
		$date = $this->event['event_saved'];
		$content = $this->event['event_content'];
		$url = $this->registry->output->buildSEOUrl( "app=calendar&module=calendar&section=view&do=showevent&event_id={$this->event['event_id']}", 'public', $this->event['event_title_seo'], 'cal_event' );
		
		IPSText::getTextClass('bbcode')->parsing_section		= 'calendar';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];
		
		$return['content']	= IPSText::raw2form( trim( "[quote name='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $author ) . "' date='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $this->registry->getClass('class_localization')->getDate( $date, 'LONG', 1 ) ) . "' timestamp='{$date}']<br />{$content}<br />" . "[/quote]" ) . "<br /><br /><br />{$this->lang->words['bt_source']} [url=\"{$url}\"]{$return['title']}[/url]<br />" );
		$return['title']	= $this->lang->words['bt_from'] . ' ' . $return['title'];
		
		return $return;
	}
	
	/**
	 * Get IDs
	 *
	 * @param	string	URL
	 * @return	array	IDs
	 */
	public function getIds( $url, $furlRegex=NULL )
	{	
		if ( is_array( $url ) )
		{
			return array( 1 => $url['event_id'] );
		}
		else
		{
			preg_match( $furlRegex, $url, $matches );
			return array( 1 => $matches[1] );
		}
	}
}