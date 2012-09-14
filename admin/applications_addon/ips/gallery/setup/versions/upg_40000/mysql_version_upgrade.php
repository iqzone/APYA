<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v4.2.1
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/


class SQLVC
{
	public static function albumsConvertAlbums()
	{
		$DB  = ipsRegistry::DB();
		$PRE = ipsRegistry::dbFunctions()->getPrefix();
		
		return "INSERT INTO `{$PRE}gallery_albums_main`
						(album_id, album_parent_id, album_owner_id, album_name, album_name_seo, album_description, album_is_public,
						 album_is_global, album_is_profile, album_count_imgs, album_count_comments, album_count_imgs_hidden,
						 album_count_comments_hidden, album_cover_img_id, album_last_img_id, album_last_img_date, album_sort_options,
						 album_allow_comments, album_cache, album_node_level, album_node_left, album_node_right, album_g_approve_img, album_g_approve_com,
						 album_g_bitwise, album_g_rules, album_g_container_only, album_g_perms_thumbs, album_g_perms_view,
						 album_g_perms_images, album_g_perms_comments, album_g_perms_moderate, album_child_tree, album_parent_tree, album_preset_tags, album_g_latest_imgs )
						
						( SELECT a.id, a.parent, a.member_id, a.name, a.name_seo, a.description,( CASE WHEN a.friend_only THEN 2 WHEN a.public_album THEN 1 ELSE 0 END ),
						0, a.profile_album, a.images, a.comments, a.mod_images,
						a.mod_comments, 0, 0, 0, '',
						1, CONCAT( 'cat-', a.category_id ), 0, 0, 0, 0, 0,
						0, '', 0, '*', '*',
						'*', '*', '', '', '', '', '' FROM `{$PRE}gallery_albums` a)";
	}
	
	public static function albumsConvertCats()
	{
		$DB  = ipsRegistry::DB();
		$PRE = ipsRegistry::dbFunctions()->getPrefix();
		
		return "INSERT INTO `{$PRE}gallery_albums_main`
						(album_parent_id, album_owner_id, album_name, album_name_seo, album_description, album_is_public,
						 album_is_global, album_is_profile, album_count_imgs, album_count_comments, album_count_imgs_hidden,
						 album_count_comments_hidden, album_cover_img_id, album_last_img_id, album_last_img_date, album_sort_options,
						 album_allow_comments, album_cache, album_node_level, album_node_left, album_node_right, album_g_approve_img, album_g_approve_com,
						 album_g_bitwise, album_g_rules, album_g_container_only, album_g_perms_thumbs, album_g_perms_view,
						 album_g_perms_images, album_g_perms_comments, album_g_perms_moderate, album_child_tree, album_parent_tree, album_preset_tags, album_g_latest_imgs )
						
						( SELECT 0, 0, c.name, c.name_seo, c.description, 1,
						1, 0, c.images, c.comments, c.mod_images,
						0, 0, 0, 0, '',
						c.allow_comments, CONCAT( 'catid-', c.id ), 0, 0, 0, c.mod_images, c.mod_comments,
						0, CONCAT( 'parent-cat-', c.parent ), c.category_only, p.perm_view, p.perm_2,
						p.perm_3, p.perm_4, p.perm_5, '', '', '', '' FROM `{$PRE}gallery_categories` c, `{$PRE}permission_index` p WHERE p.perm_type='cat' AND p.perm_type_id=c.id AND p.app='gallery' ORDER BY c.id ASC )";
	}
	
	public static function albumsTrimPerms()
	{
		$DB  = ipsRegistry::DB();
		$PRE = ipsRegistry::dbFunctions()->getPrefix();
		
		return "UPDATE {$PRE}gallery_albums_main SET
				album_g_perms_thumbs   = TRIM( BOTH ',' FROM  album_g_perms_thumbs ),
				album_g_perms_view     = TRIM( BOTH ',' FROM  album_g_perms_view ),
				album_g_perms_images   = TRIM( BOTH ',' FROM  album_g_perms_images ),
				album_g_perms_comments = TRIM( BOTH ',' FROM  album_g_perms_comments ),
				album_g_perms_moderate = TRIM( BOTH ',' FROM  album_g_perms_moderate );";
	}
}