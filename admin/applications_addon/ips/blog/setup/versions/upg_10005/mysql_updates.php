<?php

$SQL[] = "ALTER TABLE blog_comments change comment comment_text text NULL";

$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('SORT_ASC', '<img src=\'style_images/<#IMG_DIR#>/sort_asc.gif\' border=\'0\' alt=\'asc\' />', 1, 1)";
$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('SORT_DESC', '<img src=\'style_images/<#IMG_DIR#>/sort_desc.gif\' border=\'0\' alt=\'desc\' />', 1, 1)";
$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('P_OPENEOPT', '<img src=\'style_images/<#IMG_DIR#>/p_entryopts.gif\' border=\'0\' alt=\'Entry Options\' />', 1, 1)";
$SQL[] = "INSERT INTO skin_macro (macro_value, macro_replace, macro_can_remove, macro_set) VALUES ('P_CLOSEEOPT', '<img src=\'style_images/<#IMG_DIR#>/p_optsclose.gif\' border=\'0\' alt=\'Close\' />', 1, 1)";
	
