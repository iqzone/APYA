<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Global Search
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */ 

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class classes_tags_search_sql
{
	
	
	/**
	 * Setup registry objects
	 *
	 * @access	public
	 * @return	@e void
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
	}
	
	/**
	 * Perform the search
	 * @param array $tags
	 * @param array $options
	 */
	public function run( array $tags, array $options )
	{
		$where  = array();
		$order  = ( ! empty( $options['sortKey'] ) )   ? $options['sortKey']   : 'tg.tag_id';
		$dir    = ( ! empty( $options['sortOrder'] ) ) ? $options['sortOrder'] : 'desc';
		$return = array();
		
		/* Format query */
		if ( isset( $options['meta_parent_id'] ) && ( is_numeric( $options['meta_parent_id'] ) || count( $options['meta_parent_id'] ) ) )
		{
			$where[] = ( is_array( $options['meta_parent_id'] ) && count( $options['meta_parent_id'] ) ) ? 'tg.tag_meta_parent_id IN (' . implode( ',', IPSLib::cleanIntArray( $options['meta_parent_id'] ) ) . ')' : 'tg.tag_meta_parent_id=' . intval( $options['meta_parent_id'] );
		}
		
		if ( isset( $options['meta_id'] ) && ( is_numeric( $options['meta_id'] ) || count( $options['meta_id'] ) ) )
		{
			$where[] = ( is_array( $options['meta_id'] ) && count( $options['meta_id'] ) ) ? 'tg.tag_meta_id IN (' . implode( ',', IPSLib::cleanIntArray( $options['meta_id'] ) ) . ')' : 'tg.tag_meta_id=' . intval( $options['meta_id'] );
		}
		
		if ( isset( $options['meta_app'] ) )
		{
			$where[] = ( is_array( $options['meta_app'] ) && count( $options['meta_app'] ) ) ? 'tg.tag_meta_app IN (\'' . implode( "','", $options['meta_app'] ) . '\')' : 'tg.tag_meta_app=\'' . $options['meta_app'] . '\'';
		}
		
		if ( isset( $options['meta_area'] ) )
		{
			$where[] = ( is_array( $options['meta_area'] ) && count( $options['meta_area'] ) ) ? 'tg.tag_meta_area IN (\'' . implode( "','", $options['meta_area'] ) . '\')' : 'tg.tag_meta_area=\'' . $options['meta_area'] . '\'';
		}
		
		if ( ! empty( $options['not_meta_id'] ) )
		{
			$where[] = ( is_array( $options['not_meta_id'] ) && count( $options['not_meta_id'] ) ) ? 'tg.tag_meta_id NOT IN (' . implode( ",", $options['not_meta_id'] ) . ')' : 'tg.tag_meta_id !=' . intval( $options['not_meta_id'] );
		} 
		
		if ( isset( $tags ) )
		{
			if ( isset( $options['match'] ) AND $options['match'] == 'loose' )
			{
				$_tags = ( is_array( $tags ) ) ? $tags : array( $tags );
				$_t    = array();
				
				foreach( $_tags as $text )
				{
					$_t[] = ' tg.tag_text LIKE \'%' . $this->DB->addSlashes( $text ) . '%\'';
				}
				
				if ( count( $_t ) )
				{
					$where[] = implode( " OR ", $_t );
				}
			}
			else
			{
				if ( is_array( $tags ) )
				{
					$_t   = $tags;
					$tags = array();
					
					foreach( $_t as $t )
					{
						$tags[] = $this->DB->addSlashes( $t );
					}
				}
				
				$where[] = ( is_array( $tags ) ) ? 'tg.tag_text IN (\'' . implode( "','", $tags ) . '\')' : 'tg.tag_text=\'' . $this->DB->addSlashes( $tags ) . '\'';
			}
		}
		
		$prefix = ips_DBRegistry::getPrefix();
		
		/* Did we add in perm check? */
		if ( ! empty( $options['isViewable'] ) )
		{		
			if ( $options['joins'] )
			{
				$select = array();
				$join   = '';
				
				foreach( $options['joins'] as $j )
				{
					foreach( $j['from'] as $name => $ref )
					{
						$select[] = $j['select'];
						$join    .= ' LEFT JOIN ' . $prefix . $name . ' ' . $ref;
						
						if ( $j['where'] )
						{
							$join .= ' ON (' . $j['where'] . ')';
						}
					}
				}
			}
		
			if ( count( $select ) )
			{
				$_select = ',' . implode( ',', $select );
			}
			
			$options['limit'] = ( $options['limit'] > 0 && $options['limit'] < 5000 ) ? $options['limit'] : 250;
			
			$this->DB->allow_sub_select = true;
			
			$this->DB->query( 'SELECT tg.* ' . $_select . ' FROM ' . $prefix . 'core_tags tg ' . $join . ' WHERE ' . implode( ' AND ', $where ) . ' AND tg.tag_aai_lookup IN ('
								. 'SELECT tag_perm_aai_lookup FROM  ' . $prefix . 'core_tags_perms WHERE ' . $this->DB->buildWherePermission( $this->member->perm_id_array, 'tag_perm_text' ) . ' AND tag_perm_visible=1 '
								. ') ORDER BY ' . $order . ' ' . $dir . ' LIMIT 0,' . $options['limit'] );
								
			$this->DB->execute();
		}
		else
		{
			if ( is_array( $options['joins'] ) )
			{
				$db = array( 'select'   => 'tg.*',
						     'from'	    => array( 'core_tags' =>  'tg' ),
						     'where'    => implode( ' AND ', $where ),
							 'add_join' => array( $options['joins'] ),
						     'order'    => $order . ' ' . $dir );
			}
			else
			{
				$db = array( 'select' => 'tg.*',
							 'from'	  => 'core_tags tg',
							 'where'  => implode( ' AND ', $where ),
							 'order'  => $order . ' ' . $dir );
			}
			
			if ( ! empty( $options['limit'] ) || ! empty( $options['offset'] ) )
			{
				$db['limit'] = array( intval( $options['offset'] ), intval( $options['limit'] ) );
			}
			
			/* Fetch */
			$this->DB->build( $db );
			$this->DB->execute();
		}
		
		/* Fetch data */
		while( $row = $this->DB->fetch() )
		{
			$return[ $row['tag_id'] ] = $row;
		}
		
		return $return;
	}

}
