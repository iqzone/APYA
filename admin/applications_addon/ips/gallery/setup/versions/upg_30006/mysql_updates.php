<?php

$SQL[] = <<<EOF
ALTER TABLE  gallery_images CHANGE  caption  caption VARCHAR( 255 ) NOT NULL;
EOF;

$SQL[] = <<<EOF
ALTER TABLE gallery_images CHANGE credit_info credit_info TEXT NULL;
EOF;
