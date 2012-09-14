<?php
/**
* Blog This Module for DOWNLOADS
*
* @package		IP.Blog
* @author		Mark Wade
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/
class bt_downloads implements iBlogThis
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
		$file = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'downloads_files', 'where' => 'file_id=' . intval( $this->_incoming['id1'] ) ) );
		if ( !$file['file_id'] )
		{
			return false;
		}
		
		return ipsRegistry::getClass( 'permissions' )->check( 'show', $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'permission_index', 'where' => "app='downloads' AND perm_type='cat' AND perm_type_id=" . intval( $file['file_cat'] ) ) ) );
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
		$file = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'downloads_files', 'where' => 'file_id=' . intval( $this->_incoming['id1'] ) ) );		
		if ( $file['file_id'] )
		{
			$return['title'] = $file['file_name'];
			$author = IPSMember::load( $file['file_submitter'] );
			$author = $author['members_display_name'];
			$date = $file['file_submitted'];
			$content = $file['file_desc'];
			$url = $this->registry->output->buildSEOUrl( "app=downloads&showfile={$file['file_id']}", 'public', $file['file_name_furl'], 'idmshowfile' );
						
			IPSText::getTextClass('bbcode')->parsing_section		= 'idm_submit';
			IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
			IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];
			
			$return['title']	= $this->lang->words['bt_from'] . ' ' . $return['title'];
			$return['content']	= IPSText::raw2form( trim( "[quote name='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $author ) . "' date='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $this->registry->getClass('class_localization')->getDate( $date, 'LONG', 1 ) ) . "' timestamp='{$date}']<br />{$content}<br />" . "[/quote]" ) . "<br /><br /><br />{$this->lang->words['bt_source']} [url=\"{$url}\"]{$return['title']}[/url]<br />" );
			$return['topicData']= $this->topicData;
		}
		
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
			return array( 1 => $url['showfile'] );
		}
		else
		{
			preg_match( $furlRegex, $url, $matches );
			return array( 1 => $matches[1] );
		}
	}
}