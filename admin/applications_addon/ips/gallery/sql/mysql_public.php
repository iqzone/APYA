<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v4.2.1
 * Last Updated: $LastChangedDate: 2011-10-11 09:22:35 -0400 (Tue, 11 Oct 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		27th January 2004
 * @version		$Rev: 9594 $
 *
 */



class public_gallery_sql_queries extends db_driver_mysql
{
     protected $db  = "";
     protected $tbl = "";

    /* Construct */
    public function __construct( &$obj )
    {
    	$reg          = ipsRegistry::instance();
    	$this->member = $reg->member();
    	$this->DB     = $reg->DB();
    	$this->tbl	  = ips_DBRegistry::getPrefix();
    }

    /*========================================================================*/

	/**
     * Fetches a single random image (few holes)
     * @param array $album
     * @return string
     */
	public function gallery_fetch_feature_image( $where )
    {
    	/* All we want to do is ORDER BY RAND() but that is hugely inefficient on MySQL */
    	$where  = ( count( $where ) ) ? ' ' . implode( ' AND ', $where )  : ' 1=1 ';
    	$where2 = str_replace( 'i.', 'x.', $where );
    	$where3 = str_replace( 'i.', 'y.', $where );
  		$prefix = $this->tbl;
  		
  		$this->DB->allow_sub_select = true;
		
		$this->DB->query("SELECT MIN(id) AS minid, MAX(id) as maxid FROM {$prefix}gallery_images y FORCE INDEX (rnd_lookup) WHERE {$where3}" );
		$counts = $this->DB->fetch();
		
		$rand   = mt_rand( $counts['minid'], $counts['maxid'] );
		
  		$query = "SELECT i.*, a.*, m.members_display_name, m.members_seo_name, m.member_id
					  FROM {$prefix}gallery_images AS i
					  	 LEFT JOIN {$prefix}gallery_albums_main a ON (i.img_album_id=a.album_id)
						 LEFT JOIN {$prefix}members m ON (i.member_id=m.member_id)
				 WHERE {$where} AND i.id >= " . $rand . "
				 ORDER BY i.id ASC
				 LIMIT 1;";
    	
    	return $query;
	}
	
	/**
     * Fetches a single random image for focused albums (members, etc)
     * @param array $album
     * @return string
     */
	public function gallery_fetch_feature_image_php_rand( $where )
    {
    	/* All we want to do is ORDER BY RAND() but that is hugely inefficient on MySQL */
    	$where  = ( count( $where ) ) ? ' ' . implode( ' AND ', $where )  : ' 1=1 ';
  		$prefix = $this->tbl;
  		$array  = array();
  		$c      = 0;
  		
  		$this->DB->allow_sub_select = true;
		
		$this->DB->query("SELECT id FROM {$prefix}gallery_images i FORCE INDEX (rnd_lookup) WHERE {$where} ORDER BY i.id DESC LIMIT 0, 500" );
		
		while( $row = $this->DB->fetch() )
		{
			$array[ $c ] = $row['id'];
			$c++;
		}
		
		if ( ! count( $array ) )
		{
			return false;
		}
		
		/* Select random image */
		$rand = mt_rand( 0, $c - 1 );
	
  		$query = "SELECT i.*, a.*, m.members_display_name, m.members_seo_name, m.member_id
					  FROM {$prefix}gallery_images AS i
					  	 LEFT JOIN {$prefix}gallery_albums_main a ON (i.img_album_id=a.album_id)
						 LEFT JOIN {$prefix}members m ON (i.member_id=m.member_id)
				 WHERE {$where} AND i.id = " . intval( $array[ $rand ] );
    	
    	return $query;
	}
							 					 
    /**
     * Updates all image permissions
     * @param array $album
     * @return string
     */
    public function gallery_update_all_image_permission( $album )
    {
    	$where = '';
    	
    	if ( isset( $album['album_node_left'] ) AND isset( $album['album_node_right'] ) )
    	{
    		$where = '(a.album_node_left >= ' . intval( $album['album_node_left'] ) . ' AND a.album_node_right <= ' . intval( $album['album_node_right'] ) . ')';
    	}
    	else
    	{
    		$where = "1=1";
    	}
    
    	$query = "UPDATE " . $this->tbl . "gallery_images i, " . $this->tbl . "gallery_albums_main a
    				SET i.image_parent_permission=a.album_g_perms_view,
    					i.image_privacy=CASE WHEN a.album_is_global=1 THEN 3 ELSE a.album_is_public END
    				WHERE i.img_album_id=a.album_id AND " . $where;
    	
    	return $query;
	}
	
	/**
	 * 
	 * Update public owner albums quickly
	 * @param array $album
	 */
	public function gallery_update_user_albums( $album )
    {
    	$where = '';
    	$tmp   = str_replace( array( '.', ':' ), '', $this->member->ip_address );
    	
    	if ( isset( $album['album_node_left'] ) AND isset( $album['album_node_right'] ) )
    	{
    		$where = '(g.album_node_left >= ' . intval( $album['album_node_left'] ) . ' AND g.album_node_right <= ' . intval( $album['album_node_right'] ) . ')';
    	}
    	else
    	{
    		$where = "1=1";
    	}
    	
    	/* Got a process running? */
    	$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) AS cnt', 'from' => 'gallery_albums_temp' ) );
    	
    	if ( $count['cnt'] > 0 )
    	{
    		return false;
    	}
    	
    	/* Need to essentially UPDATE FROM with ORDER but MySQL cannot do this. */
    	$this->DB->allow_sub_select = true;
    	$this->DB->query( "INSERT INTO  " . $this->tbl . "gallery_albums_temp (album_id, album_g_perms_view) SELECT g.album_id, g.album_g_perms_view FROM " . $this->tbl . "gallery_albums_main g WHERE g.album_parent_id > 0 AND g.album_is_global=0 AND g.album_is_public=1 AND " . $where );
    	
    	$this->DB->allow_sub_select = true;
    	$this->DB->query( "UPDATE  " . $this->tbl . "gallery_albums_temp xxx
    						SET xxx.album_g_perms_view=
								(SELECT aaa.album_g_perms_view 
										FROM " . $this->tbl . "gallery_albums_main aaa, " . $this->tbl . "gallery_albums_main bbb
										WHERE bbb.album_id=xxx.album_id AND bbb.album_node_left BETWEEN aaa.album_node_left AND aaa.album_node_right AND aaa.album_is_global=1
										ORDER BY aaa.album_node_left ASC LIMIT 1);");
    	
    	$this->DB->allow_sub_select = true;
    	$this->DB->query( "UPDATE " . $this->tbl . "gallery_albums_main g, " . $this->tbl . "gallery_albums_temp xxx SET g.album_g_perms_view=xxx.album_g_perms_view WHERE g.album_id=xxx.album_id" );
    	
    	$query = "DELETE FROM " . $this->tbl . "gallery_albums_temp";
    	
    	return $query;
	}


} // end class


/*
// PUBLIC non GLOBAL albums
UPDATE gallery_albums_main aaa, gallery_albums_main bbb
	SET aaa.album_g_perms_view=bbb.album_g_perms_view WHERE aaa.album_id=bbb.album_parent_id
	WHERE aaa.album_parent_id > 0 AND aaa.album_is_global=0 AND aaa.album_is_public=1
	WHERE aaa.album_node_left >= 0 AND aaa.album_node_right <= 100
	ORDER BY aaa.album_node_left


UPDATE gallery_albums_main a LEFT JOIN gallery_albums_main b USING(album_id)
SET a.album_g_perms_view=b.album_g_perms_view
WHERE a.album_parent_id > 0 AND a.album_is_global=0 AND a.album_is_public=1
ORDER BY a.album_node_left



UPDATE gallery_albums_main SET album_g_perms_view=(SELECT album_g_perms_view FROM gallery_albums_main b WHERE album_id=b.album_parent_id)
WHERE album_parent_id > 0 AND album_is_global=0 AND album_is_public=1
ORDER BY album_node_left*/
