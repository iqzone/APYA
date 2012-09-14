<?php
/**
* Installation Schematic File
* Generated on Thu, 30 Apr 2009 19:03:38 +0000 GMT
*/
$INDEX[] = "ALTER TABLE cal_events ADD FULLTEXT (event_content);";
$INDEX[] = "ALTER TABLE cal_events ADD FULLTEXT (event_title);";
?>