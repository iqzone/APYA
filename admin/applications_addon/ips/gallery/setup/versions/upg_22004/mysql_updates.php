<?php

# Oracle don't like "size" so we'll switch to bsize
$SQL[] = "ALTER TABLE gallery_bandwidth CHANGE size bsize int(10) unsigned not null default '0';";
