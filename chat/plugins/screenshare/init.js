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
					baseUrl = $.cometchat.getBaseUrl();
					baseData = $.cometchat.getBaseData();

					var random = currenttime;
					$.getJSON(baseUrl+'plugins/screenshare/index.php?action=request&callback=?', {to: id, id: random, basedata: baseData});
					lastcall = currenttime;

					baseUrl = $.cometchat.getBaseUrl();

					var w = window.open (baseUrl+'plugins/screenshare/index.php?action=screenshare&type=1&id='+random+'&basedata='+baseData, 'screenshare',"status=0,toolbar=0,menubar=0,directories=0,resizable=1,location=0,status=0,scrollbars=0, width=400,height=200");
					w.focus();

					/* Uncomment to use popup instead */
					
					//	loadCCPopup(baseUrl+'plugins/screenshare/index.php?action=screenshare&type=1&id='+random+'&basedata='+baseData, 'screenshare',"status=0,toolbar=0,menubar=0,directories=0,resizable=1,location=0,status=0,scrollbars=0, width=400,height=200",400,200,'<?php echo $screenshare_language[0];?>');
					

				} else {
					alert('<?php echo $screenshare_language[1];?>');
				}
			},

			accept: function (id,random) {
				baseUrl = $.cometchat.getBaseUrl();
				baseData = $.cometchat.getBaseData();

				$.getJSON(baseUrl+'plugins/screenshare/index.php?action=accept&callback=?', {to: id, basedata: baseData});
				loadCCPopup(baseUrl+'plugins/screenshare/index.php?action=screenshare&type=0&id='+random+'&basedata='+baseData, 'screenshare',"status=0,toolbar=0,menubar=0,directories=0,resizable=1,location=0,status=0,scrollbars=0, width=800,height=600",640,480,'<?php echo $screenshare_language[7];?>',1); 
			}
        };
    })();
 
})(jqcc);