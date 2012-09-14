<?php

$SQL[] = "ALTER TABLE gallery_categories CHANGE perms_thumbs perms_thumbs TEXT NULL ,
CHANGE perms_view perms_view TEXT NULL ,
CHANGE perms_images perms_images TEXT NULL ,
CHANGE perms_comments perms_comments TEXT NULL ,
CHANGE perms_moderate perms_moderate TEXT NULL ;";

$SQL[] = "UPDATE gallery_form_fields SET deleteable='0' WHERE id=4 OR id=5";
