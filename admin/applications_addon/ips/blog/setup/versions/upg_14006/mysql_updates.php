<?php

$SQL[] = "UPDATE blog_mediatag SET mediatag_match='http://(|www.)youtube.com/watch?v={2}', mediatag_replace='<object width=\"425\" height=\"355\"><param name=\"movie\" value=\"http://youtube.com/v/\$2\"></param><param name=\"wmode\" value=\"transparent\"></param><embed src=\"http://youtube.com/v/\$2\" type=\"application/x-shockwave-flash\" wmode=\"transparent\" width=\"425\" height=\"355\"></embed></object>' WHERE mediatag_name='YouTube';";
