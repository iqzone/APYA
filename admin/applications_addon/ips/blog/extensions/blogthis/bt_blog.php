<?php
/**
* Blog This Module for BLOG
*
* @package		IP.Blog
* @author		Mark Wade
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/
class bt_blog implements iBlogThis
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
		return $this->registry->getClass('blogFunctions')->setActiveBlog( $this->_incoming['id2'] );
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
		$entry = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'blog_entries', 'where' => 'entry_id=' . intval( $this->_incoming['id1'] ) ) );		
		if ( $entry['entry_id'] )
		{
			$return['title'] = $entry['entry_name'];
			$author = $entry['entry_author_name'];
			$date = $entry['entry_date'];
			$content = $entry['entry'];
			$url = $this->registry->output->buildSEOUrl( "app=blog&module=display&section=blog&blogid={$entry['blog_id']}&showentry={$entry['entry_id']}", 'public', $entry['entry_name_seo'], 'showentry' );
			
			IPSText::getTextClass('bbcode')->parsing_section		= 'blog_entry';
			IPSText::getTextClass('bbcode')->parsing_mgroup			= $this->memberData['member_group_id'];
			IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $this->memberData['mgroup_others'];
			
			$return['content']	= IPSText::raw2form( trim( "[quote name='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $author ) . "' date='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $this->registry->getClass('class_localization')->getDate( $date, 'LONG', 1 ) ) . "' timestamp='{$date}']<br />{$content}<br />" . "[/quote]" ) . "<br /><br /><br />{$this->lang->words['bt_source']} [url=\"{$url}\"]{$return['title']}[/url]<br />" );
			$return['title']	= $this->lang->words['bt_from'] . ' ' . $return['title'];
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
			return array( 1 => $url['showentry'], 2 => $url['blogid'] ); 
		}
		else
		{
			preg_match( $furlRegex, $url, $matches );
			return array( 1 => $matches[2], 2 => $matches[1] );
		}
	}
}