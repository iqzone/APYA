<?php

$SQL[] = "UPDATE custom_bbcode SET bbcode_replace = '<a href=\'index.php?autocom=blog&amp;showentry={option}\'>{content}</a>' WHERE bbcode_tag='entry'";
