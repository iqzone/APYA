<?php
/**
* Blog This Module for NEXUS
*
* @package		IP.Blog
* @author		Mark Wade
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/
class bt_nexus implements iBlogThis
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
		
		require_once( IPSLib::getAppDir('nexus') . '/sources/packageCore.php' );/*noLibHook*/
	}
	
	/**
	 * check permission
	 *
	 * @return boolean
	 */
	public function checkPermission()
	{
		$id = intval( $this->_incoming['id1'] );
		try
		{
			$package = package::load( $id );
		}
		catch ( Exception $e )
		{
			return false;
		}
		if ( !$package->data['p_store'] or ( $package->data['p_member_groups'] != '*' and !IPSMember::isInGroup( $this->memberData, explode( ',', $package->data['p_member_groups'] ) ) ) )
		{
			return false;
		}
		
		return true;
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
		try
		{
			$package = package::load( $this->_incoming['id1'] );
		}
		catch ( Exception $e )
		{
			return $return;
		}
		
		$return['title'] = $package->data['p_name'];
		$content = $package->data['p_desc'];
		$url = $this->registry->output->buildSEOUrl( "app=nexus&module=payments&section=store&do=item&id={$package->data['p_id']}", 'public', $package->data['p_seo_name'], 'storeitem' );
		
		IPSText::getTextClass('bbcode')->parsing_section		= 'blog_entry';
		IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];
		
		$return['title']	= $this->lang->words['bt_from'] . ' ' . $return['title'];
		$return['content']	= IPSText::raw2form( trim( "[quote]<br />{$content}<br />" . "[/quote]" ) . "<br /><br /><br />{$this->lang->words['bt_source']} [url=\"{$url}\"]{$return['title']}[/url]<br />" );
		$return['topicData']= $this->topicData;
		
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
			return array( 1 => $url['id'] );
		}
		else
		{
			preg_match( $furlRegex, $url, $matches );
			return array( 1 => $matches[1] );
		}
	}
}