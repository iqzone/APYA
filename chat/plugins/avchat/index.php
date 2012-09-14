<?php

/*

CometChat
Copyright (c) 2012 Inscripts

CometChat ('the Software') is a copyrighted work of authorship. Inscripts 
retains ownership of the Software and any copies of it, regardless of the 
form in which the copies may exist. This license is not a sale of the 
original Software or any copies.

By installing and using CometChat on your server, you agree to the following
terms and conditions. Such agreement is either on your own behalf or on behalf
of any corporate entity which employs you or which you represent
('Corporate Licensee'). In this Agreement, 'you' includes both the reader
and any Corporate Licensee and 'Inscripts' means Inscripts (I) Private Limited:

CometChat license grants you the right to run one instance (a single installation)
of the Software on one web server and one web site for each license purchased.
Each license may power one instance of the Software on one domain. For each 
installed instance of the Software, a separate license is required. 
The Software is licensed only to you. You may not rent, lease, sublicense, sell,
assign, pledge, transfer or otherwise dispose of the Software in any form, on
a temporary or permanent basis, without the prior written consent of Inscripts. 

The license is effective until terminated. You may terminate it
at any time by uninstalling the Software and destroying any copies in any form. 

The Software source code may be altered (at your risk) 

All Software copyright notices within the scripts must remain unchanged (and visible). 

The Software may not be used for anything that would represent or is associated
with an Intellectual Property violation, including, but not limited to, 
engaging in any activity that infringes or misappropriates the intellectual property
rights of others, including copyrights, trademarks, service marks, trade secrets, 
software piracy, and patents held by individuals, corporations, or other entities. 

If any of the terms of this Agreement are violated, Inscripts reserves the right 
to revoke the Software license at any time. 

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/

include dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR."plugins.php";
include dirname(__FILE__).DIRECTORY_SEPARATOR."config.php";
include dirname(__FILE__).DIRECTORY_SEPARATOR."lang/en.php";

if (file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR."lang/".$lang.".php")) {
	include dirname(__FILE__).DIRECTORY_SEPARATOR."lang/".$lang.".php";
}

if (!file_exists(dirname(__FILE__)."/themes/".$theme."/avchat".$rtl.".css")) {
	$theme = "default";
}

if ($p_<4) exit;

if($videoPluginType != '3') {

if ($_REQUEST['action'] == 'request') {
	$grp = sha1(time()+$userid+'from');

	sendMessageTo($_REQUEST['to'],$avchat_language[2]." <a href='javascript:void(0);' onclick=\"javascript:jqcc.ccavchat.accept('".$userid."','".$grp."');\">".$avchat_language[3]."</a> ".$avchat_language[4]);

	sendSelfMessage($_REQUEST['to'],$avchat_language[5]);

	if (!empty($_REQUEST['callback'])) {
		header('content-type: application/json; charset=utf-8');
		echo $_REQUEST['callback'].'()';
	}
}

if ($_REQUEST['action'] == 'accept') {
	sendMessageTo($_REQUEST['to'],$avchat_language[6]." <a href='javascript:void(0);' onclick=\"javascript:jqcc.ccavchat.accept_fid('".$userid."','".$_REQUEST['grp']."');\">".$avchat_language[7]."</a>");

	if (!empty($_REQUEST['callback'])) {
		header('content-type: application/json; charset=utf-8');
		echo $_REQUEST['callback'].'()';
	}
}

if ($_REQUEST['action'] == 'call') {

	$grp = $_REQUEST['grp'];

	if (!empty($_REQUEST['chatroommode'])) {
		if (empty($_REQUEST['join'])) {
			sendChatroomMessage($grp,$avchat_language[19]." <a href='javascript:void(0);' onclick=\"javascript:jqcc.ccavchat.join('".$_REQUEST['grp']."');\">".$avchat_language[20]."</a>");
		}
	}

	if($videoPluginType=='0')
	{
		$connectUrl = 'rtmfp://stratus.rtmfp.net';
		$developerKey = 'b72b713a18065673cdc1064e-0a89db06e6f8';

		$flashvariables = '{grp:"'.$grp.'",quality:"'.$quality.'",bandwidth:"0",connectUrl:"'.$connectUrl.'",DeveloperKey:"'.$developerKey.'",maxP:'.$maxP.'}';
		$file = '';
	}
	else if ($videoPluginType=='1')
	{
		ini_set('display_errors', 0);

		include_once (dirname(__FILE__)."/lccs.php"); 
		
		$room  = "avchat".$grp;
		$token = "";
		
		$roomURL = "{$accountURL}/{$room}";
		$displayName = "avchat".rand(0,9999);
		$username = $displayName;
		$role = 0;

		if (empty($lccsUsername) || empty($lccsPassword) || empty($accountURL) || empty($accountSharedSecret)) {
			echo "Please configure this plugin using administration centre before using."; exit;
		}
		
		try {
			$account = new RTCAccount($accountURL);
			$account->login($lccsUsername,$lccsPassword);
		} catch (Exception $ex) {
			echo 'Invalid LCCS username/password. <a href="javascript:location.reload();">Click here to try again</a>';
			exit;
		}

		try
		{
			$rooms = $account->getRoomInfo($room);
			if(strpos($rooms,'"error"')>0) {
				$account->createRoom($room);
			}

			$session = $account->getSession($room);
			$token = $session->getAuthenticationToken($accountSharedSecret, $displayName, $username, 100);
		} catch (Exception $ex) {
			echo '<script>setTimeout("location.reload()",2000)</script>Please be patient. Initializing.';
			echo '<!--'.$ex.'-->';
			exit;
		}

		$flashvariables = '{quality:"'.$quality.'", lccs_url: "'.$accountURL.'", room: "'.$room.'", token: "'.$token.'", mode:"3"}';
		$file = '_lccs';

	}
	else if($videoPluginType=='2')
	{
		ini_set('display_errors', 0);

		$flashvariables = '{grp:"'.$grp.'",connectUrl: "'.$connectUrl.'",name:"",quality: "'. $quality. '",bandwidth: "'.$bandwidth.'",fps:"'.$fps.'",mode: "'.$mode.'",maxP: "'.$maxP.'"}';

		$file = '_fms';
	}

	echo <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title>{$avchat_language[8]}</title> 
<style>
html, body, div, span, applet, object, iframe,
h1, h2, h3, h4, h5, h6, p, blockquote, pre,
a, abbr, acronym, address, big, cite, code,
del, dfn, em, font, img, ins, kbd, q, s, samp,
small, strike, strong, sub, sup, tt, var,
dl, dt, dd, ol, ul, li,
fieldset, form, label, legend,
table, caption, tbody, tfoot, thead, tr, th, td {
	margin: 0;
	padding: 0;
	border: 0;
	outline: 0;
	font-weight: inherit;
	font-style: inherit;
	font-size: 100%;
	font-family: inherit;
	vertical-align: baseline;
    text-align: center;
}

html {
  height: 100%;
  overflow: hidden; /* Hides scrollbar in IE */
}

body {
  height: 100%;
  margin: 0;
  padding: 0;
}

#flashcontent {
  height: 100%;
}


</style>
	<script type="text/javascript" src="swfobject.js"></script>
	<script type="text/javascript">
		var swfVersionStr = "10.1.0";
		var xiSwfUrlStr = "playerProductInstall.swf";
		var flashvars = {$flashvariables};
		var params = {};
		params.quality = "high";
		params.bgcolor = "#000000";
		params.allowscriptaccess = "sameDomain";
		params.allowfullscreen = "true";
		var attributes = {};
		attributes.id = "audiovideochat";
		attributes.name = "audiovideochat";
		attributes.align = "middle";
		swfobject.embedSWF(
			"audiovideochat{$file}.swf?v3", "flashContent", 
			"100%", "100%", 
			swfVersionStr, xiSwfUrlStr, 
			flashvars, params, attributes);
		swfobject.createCSS("#flashContent", "display:block;text-align:left;");
	</script>

</head>
<body>  


  <div id="flashContent">
        	<p>
	        	To view this page ensure that Adobe Flash Player version 
				10.1.0 or greater is installed. 
			</p>
			<script type="text/javascript"> 
				var pageHost = ((document.location.protocol == "https:") ? "https://" :	"http://"); 
				document.write("<a href='http://www.adobe.com/go/getflashplayer'><img src='" 
								+ pageHost + "www.adobe.com/images/shared/download_buttons/get_flash_player.gif' alt='Get Adobe Flash player' /></a>" ); 
			</script> 
        </div>	
</body>
</html>
EOD;
}

} else {

require_once dirname(__FILE__).'/sdk/API_Config.php';
require_once dirname(__FILE__).'/sdk/OpenTokSDK.php';

$apiKey = '348501';
$apiSecret = '1022308838584cb6eba1fd9548a64dc1f8439774';
$apiServer = 'https://api.opentok.com/hl';

if ($_GET['action'] == 'request') {
	$apiObj = new OpenTokSDK($apiKey, $apiSecret);
		
	$location = time();

	if (!empty($_SERVER['REMOTE_ADDR'])) {
		$location = $_SERVER['REMOTE_ADDR'];
	}

	$session = $apiObj->create_session($location, array("p2p.preference" => "enabled"));
	$sessionid = $session->getSessionId();

	sendMessageTo($_REQUEST['to'],$avchat_language[2]." <a href='javascript:void(0);' onclick=\"javascript:jqcc.ccavchat.accept('".$userid."','".$sessionid."');\">".$avchat_language[3]."</a> ".$avchat_language[4]);

	sendSelfMessage($_REQUEST['to'],$avchat_language[5]);

	
	if (!empty($_GET['callback'])) {
		header('content-type: application/json; charset=utf-8');
		echo $_GET['callback'].'()';
	}

}

if ($_GET['action'] == 'accept') {
	sendMessageTo($_REQUEST['to'],$avchat_language[6]." <a href='javascript:void(0);' onclick=\"javascript:jqcc.ccavchat.accept_fid('".$userid."','".$_REQUEST['grp']."');\">".$avchat_language[7]."</a>");

	if (!empty($_GET['callback'])) {
		header('content-type: application/json; charset=utf-8');
		echo $_GET['callback'].'()';
	}

}

if ($_GET['action'] == 'call') {
	$sessionid = $_GET['grp'];
	$apiObj = new OpenTokSDK($apiKey, $apiSecret);
	$token = $apiObj->generate_token();

	if (!empty($_GET['chatroommode'])) {
		if (empty($_GET['join'])) {
			sendChatroomMessage($sessionid,$avchat_language[19]." <a href='javascript:void(0);' onclick=\"javascript:jqcc.ccavchat.join('".$_GET['grp']."');\">".$avchat_language[20]."</a>");
		}

		$sql = ("select vidsession from cometchat_chatrooms where id = '".mysql_real_escape_string($sessionid)."'");
		$query = mysql_query($sql);
		$chatroom = mysql_fetch_array($query);

		if (empty($chatroom['vidsession'])) {
			$session = $apiObj->create_session(time());
			$newsessionid = $session->getSessionId();

			$sql = ("update cometchat_chatrooms set  vidsession = '".mysql_real_escape_string($newsessionid)."' where id = '".mysql_real_escape_string($sessionid)."'");
			$query = mysql_query($sql);

			$sessionid = $newsessionid;

		} else {
			$sessionid = $chatroom['vidsession'];
		}

	}


	$name = "";

    $sql = getUserDetails($userid);

	if ($guestsMode && $userid >= 10000000) {
		$sql = getGuestDetails($userid);
	}

	$result = mysql_query($sql);
	
	if($row = mysql_fetch_array($result)) {
		
		if (function_exists('processName')) {
			$row['username'] = processName($row['username']);
		}

		$name = $row['username'];
	}

	$name = urlencode($name);

	$baseUrl = BASE_URL;
	$embed = '';
	$embedcss = '';
	$resize = 'window.resizeTo(';
	$invitefunction = 'window.open';
	
	if (!empty($_GET['embed']) && $_GET['embed'] == 'web') {
		$embed = 'web';
		$resize = "parent.resizeCCPopup('audiovideochat',";
		$embedcss = 'embed';
		$invitefunction = 'parent.loadCCPopup';
	}

	if (!empty($_GET['embed']) && $_GET['embed'] == 'desktop') {
		$embed = 'desktop';
		$resize = "parentSandboxBridge.resizeCCPopup('audiovideochat',";
		$embedcss = 'embed';
		$invitefunction = 'parentSandboxBridge.loadCCPopup';
	}

	echo <<<EOD
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
		<title>{$avchat_language[8]}</title> 
		<link href="otchat.css" type="text/css" rel="stylesheet" >
		<script src="http://static.opentok.com/v0.91/js/TB.min.js" type="text/javascript" charset="utf-8"></script>
		<script type="text/javascript" charset="utf-8">
			var apiKey = {$apiKey};
			var sessionId = '{$sessionid}';
			var token = '{$token}';
			
			var session;
			var publisher;
			var subscribers = {};
			var totalStreams = 0;
 			
			if (TB.checkSystemRequirements() != TB.HAS_REQUIREMENTS) {
				alert('Sorry, but your computer configuration does not meet minimum requirements for video chat.');
			} else {
				session = TB.initSession(sessionId);
				session.addEventListener('sessionConnected', sessionConnectedHandler);
				session.addEventListener('sessionDisconnected', sessionDisconnectedHandler);
				session.addEventListener('connectionCreated', connectionCreatedHandler);
				session.addEventListener('connectionDestroyed', connectionDestroyedHandler);
				session.addEventListener('streamCreated', streamCreatedHandler);
				session.addEventListener('streamDestroyed', streamDestroyedHandler);
			}
 
			function connect() {
				session.connect(apiKey, token);
			}
			
			function disconnect() {
				unpublish();
				session.disconnect();
				hide('navigation');
				show('endcall');
				var div = document.getElementById('canvas');	div.parentNode.removeChild(div);
				{$resize}300,330);
			}
 
			function publish() {
				if (!publisher) {
					var parentDiv = document.getElementById("myCamera");
					var div = document.createElement('div');		
					div.setAttribute('id', 'opentok_publisher');
					parentDiv.appendChild(div);
					var params = {width: '{$vidWidth}', height: '{$vidHeight}', name: '{$name}'};
					publisher = session.publish('opentok_publisher', params); 	
					resizeWindow();
					show('unpublishLink');
					hide('publishLink');
				}
			}
 
			function unpublish() {

				if (publisher) {
					session.unpublish(publisher);
				}
				
				publisher = null;
				
				show('publishLink');
				hide('unpublishLink');
				resizeWindow();
			}

			function resizeWindow() {
				if (publisher) {
					width = (totalStreams+1)*({$vidWidth}+30);
					document.getElementById('canvas').style.width = (totalStreams+1)*{$vidWidth}+'px';
				} else {
					width = (totalStreams)*({$vidWidth}+30);
					document.getElementById('canvas').style.width = (totalStreams)*{$vidWidth}+'px';
				}

				if (width < {$vidWidth}+30) { width = {$vidWidth}+30; }
				if (width < 300) { width = 300; }

				{$resize}width,{$vidHeight}+165);

				var h = {$vidHeight};
				if( typeof( window.innerWidth ) == 'number' ) {
					h = window.innerHeight;
				} else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
					h = document.documentElement.clientHeight;
				} else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
					h = document.body.clientHeight;
				}

				if (document.getElementById('canvas') && document.getElementById('canvas').style.display != 'none') {
					if (h > {$vidHeight}){
						offset = (h-30-{$vidHeight})/2;
						document.getElementById('canvas').style.marginTop = offset+'px';
					} else {
						document.getElementById('canvas').style.marginTop = '0px';
					}
				}

			}
			
			function sessionConnectedHandler(event) {
				
				hide('loading');
				show('canvas');
		
				for (var i = 0; i < event.streams.length; i++) {

					if (event.streams[i].connection.connectionId != session.connection.connectionId) {
						totalStreams++;
					}
					addStream(event.streams[i]);
				}

				publish();

				resizeWindow();
				show('navigation');
				show('unpublishLink');
				show('disconnectLink');
				hide('publishLink');
			}
 
			function streamCreatedHandler(event) {

				for (var i = 0; i < event.streams.length; i++) {
					if (event.streams[i].connection.connectionId != session.connection.connectionId) {
						totalStreams++;
					}
					addStream(event.streams[i]);
				}
				resizeWindow();
			}
 
			function streamDestroyedHandler(event) {

				for (var i = 0; i < event.streams.length; i++) {
					if (event.streams[i].connection.connectionId != session.connection.connectionId) {
						totalStreams--;
					}
				}
				resizeWindow();
			}
 
			function sessionDisconnectedHandler(event) {
				publisher = null;
			}
 
			function connectionDestroyedHandler(event) {
			}
 
			function connectionCreatedHandler(event) {
			}
			
			function exceptionHandler(event) {
			}
			
			function addStream(stream) {
			
				if (stream.connection.connectionId == session.connection.connectionId) {
					return;
				}
				var div = document.createElement('div');	
				var divId = stream.streamId;	
				div.setAttribute('id', divId);	
				div.setAttribute('class', 'camera');
				document.getElementById('otherCamera').appendChild(div);
				var params = {width: '{$vidWidth}', height: '{$vidHeight}'};
				subscribers[stream.streamId] = session.subscribe(stream, divId, params);
			}
 
			function show(id) {
				document.getElementById(id).style.display = 'block';
			}
 
			function hide(id) {
				document.getElementById(id).style.display = 'none';
			}
			
			function inviteUser() {
				{$invitefunction}('{$baseUrl}plugins/avchat/invite.php?action=invite&roomid='+sessionId, 'invite',"status=0,toolbar=0,menubar=0,directories=0,resizable=0,location=0,status=0,scrollbars=1, width=400,height=200",400,200,'{$avchat_language[16]}'); 
			}
 
		</script>
	</head>
	<body>
		<div id="loading"><img src="res/init.png"></div>
		<div id="endcall"><img src="res/ended.png"></div>
		<div id="canvas">
			<div id="myCamera" class="publisherContainer"></div>
			<div id="otherCamera"></div>
			<div style="clear:both"></div>
		</div>
		<div id="navigation">
			<div id="navigation_elements">
				<a href="#" onclick="javascript:disconnect();" id="disconnectLink"><img src="res/hangup.png"></a>
				<a href="#" onclick="javascript:inviteUser()" id="inviteLink"><img src="res/invite.png"></a>
				<a href="#" onclick="javascript:publish()" id="publishLink"><img src="res/turnonvideo.png"></a>
				<a href="#" onclick="javascript:unpublish()" id="unpublishLink"><img src="res/turnoffvideo.png"></a>
				<div style="clear:both"></div>
			</div>
			<div style="clear:both"></div>
		</div>
	</body>
    <script>
		{$resize}300,330);
		connect();
		window.onload = function() { resizeWindow(); }
		window.onresize = function() { resizeWindow(); }
	</script>
</html>
EOD;
	
}
}