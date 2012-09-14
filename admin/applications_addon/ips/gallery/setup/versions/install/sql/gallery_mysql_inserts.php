<?php

$INSERT[] = <<<EOF
INSERT INTO rc_classes (onoff, class_title, class_desc, author, author_url, pversion, my_class, group_can_report, mod_group_perm, extra_data, lockd, app) VALUES(1, 'Gallery Plugin', 'This is the plugin for making reports for the <a href=''http://www.invisionpower.com/products/gallery/'' target=''_blank''>IP.Gallery</a>.', 'Invision Power Services, Inc', 'http://www.invisionpower.com', 'v1.0', 'gallery', ',1,2,3,4,6,', ',4,6,', 'a:2:{s:15:"report_supermod";s:1:"1";s:13:"report_bypass";s:1:"1";}', 0, 'gallery');
EOF;

$INSERT[] = <<<EOF
INSERT INTO gallery_albums_main (album_id, album_parent_id, album_owner_id, album_name, album_name_seo, album_description, album_is_public, album_is_global, album_is_profile, album_count_imgs, album_count_comments, album_count_imgs_hidden, album_count_comments_hidden, album_cover_img_id, album_last_img_id, album_last_img_date, album_sort_options, album_allow_comments, album_allow_rating, album_cache, album_g_approve_img, album_g_approve_com, album_g_bitwise, album_g_rules, album_g_container_only, album_g_perms_thumbs, album_g_perms_view, album_g_perms_images, album_g_perms_comments, album_g_perms_moderate, album_g_latest_imgs, album_node_level, album_node_left, album_node_right, album_rating_aggregate, album_rating_count, album_rating_total, album_after_forum_id, album_detail_default, album_watermark, album_position, album_can_tag, album_preset_tags, album_child_tree, album_parent_tree)
VALUES
	(1, 0, 0, 'Member&#39;s Album', 'members-album', 'A collection of our member&#39;s albums', 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 'a:2:{s:3:\"key\";s:5:\"idate\";s:3:\"dir\";s:3:\"ASC\";}', 1, 0, '', 0, 0, 0, 'a:2:{s:5:\"title\";s:0:\"\";s:4:\"text\";s:0:\"\";}', 0, '', ',4,2,3,1,', ',4,3,', ',4,3,', ',4,', 'a:0:{}', 0, 78, 89, 0, 0, 0, 0, 0, 0, 0, 1, '', 'a:0:{}', 'a:0:{}');
EOF;
