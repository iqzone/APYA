<?php
	
		include dirname(__FILE__).DIRECTORY_SEPARATOR."lang/en.php";

		if (file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR."lang/".$lang.".php")) {
			include dirname(__FILE__).DIRECTORY_SEPARATOR."lang/".$lang.".php";
		} 

		foreach ($screenshare_language as $i => $l) {
			$screenshare_language[$i] = str_replace("'", "\'", $l);
		}
?>

/*
 * CometChat
 * Copyright (c) 2012 Inscripts - support@cometchat.com | http://www.cometchat.com | http://www.inscripts.com
*/

(function($){   
  
	$.ccscreenshare = (function () {

		var title = '<?php echo $screenshare_language[0];?>';
		var lastcall = 0;

        return {

			getTitle: function() {
				return title;	
			},

			init: function (id) {
				var currenttime = new Date();
				currenttime = parseInt(currenttime.getTime()/1000);
				if (currenttime-lastcall > 10) {
					baseUrl = getBaseUrl();

					var random = currenttime;

					lastcall = currenttime;

					var w = window.open (baseUrl+'plugins/screenshare/index.php?action=screenshare&type=1&chatroommode=1&roomid='+id+'&id='+random, 'screenshare',"status=0,toolbar=0,menubar=0,directories=0,resizable=1,location=0,status=0,scrollbars=0, width=400,height=200");
					w.focus();

				} else {
					alert('<?php echo $screenshare_language[1];?>');
				}
			},

			accept: function (id,random) {
				baseUrl = getBaseUrl();
				loadCCPopup(baseUrl+'plugins/screenshare/index.php?action=screenshare&type=0&id='+random, 'screenshare',"status=0,toolbar=0,menubar=0,directories=0,resizable=1,location=0,status=0,scrollbars=0, width=800,height=600",640,480,'<?php echo $screenshare_language[7];?>',1);
			}
        };
    })();
 
})(jqcc);