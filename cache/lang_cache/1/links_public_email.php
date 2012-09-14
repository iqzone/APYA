<?php

/*******************************************************
NOTE: This is a cache file generated by IP.Board on Wed, 20 Jun 2012 21:29:32 +0000 by Julio César Barrera
Do not translate this file as you will lose your translations next time you edit via the ACP
Please translate via the ACP
*******************************************************/



$lang = array( 
'cat_daily_digest' => "<#NAME#>,

You are receiving this email because you have subscribed to a daily email digest for the links category <#CATNAME#>. There have been a total of <#TOTAL#> links submitted or commented on within the past 24 hours.

<#CONTENT#>

Unsubscribing:
--------------
You can unsubscribe at any time by logging into your control panel and clicking on the \"Manage Watched Categories\" link in the \"Links\" tab.
If you are not subscribed to any categories and wish to stop receiving notifications, uncheck the setting \"Send me any updates sent by the board administrator\" found in \"My Settings\" under \"General Settings\" in the \"Settings\" tab.
",
'cat_weekly_digest' => "<#NAME#>,

You are receiving this email because you have subscribed to a weekly email digest for the links category <#CATNAME#>. There have been a total of <#TOTAL#> links submitted or commented on within the past 7 days.

<#CONTENT#>

Unsubscribing:
--------------
You can unsubscribe at any time by logging into your control panel and clicking on the \"Manage Watched Categories\" link in the \"Links\" tab.
If you are not subscribed to any categories and wish to stop receiving notifications, uncheck the setting \"Send me any updates sent by the board administrator\" found in \"My Settings\" under \"General Settings\" in the \"Settings\" tab.
",
'digest_comment_line' => "Comment added on %s by %s:",
'digest_link_line' => "Link: %s (Submitted by %s -- Last updated %s)",
'link_approved_noti' => "<#NAME#>,

You are receiving this notification because you have opted to receive a notification for when a link you've submitted is either approved or rejected. Your link, <#TITLE#>, submitted on <#DATE#>, was approved just recently. You can view this link below,
<#BOARD_ADDRESS#>?app=links&showlink=<#LINKID#>

Thank you for submitting this link. If you would like to stop these notifications, uncheck the setting \"Notify me when my links are approved or rejected\" found in \"My Settings\" under the \"Links\" tab.
",
'link_daily_digest' => "<#NAME#>,

You are receiving this email because you have subscribed to a daily email digest for the link <#LINKNAME#>. There have been a total of <#TOTAL#> comments added to this link within the past 24 hours.

<#CONTENT#>


You can view these and more comments at the below information page for this link:
<#LINKURL#>

Unsubscribing:
--------------
You can unsubscribe at any time by logging into your control panel and clicking on the \"Manage Watched Links\" link in the \"Links\" tab.
If you are not subscribed to any categories and wish to stop receiving notifications, uncheck the setting \"Send me any updates sent by the board administrator\" found in \"My Settings\" under \"General Settings\" in the \"Settings\" tab.
",
'link_rej_noti' => "<#NAME#>,

You are receiving this notification because you have opted to receive a notification for when a link you've submitted is either approved or rejected. Your link, <#TITLE#>, submitted on <#DATE#>, was rejected just recently. <#REASON#>

If you would like to stop these notifications, uncheck the setting \"Notify me when my links are approved or rejected\" found in \"My Settings\" under the \"Links\" tab.
",
'link_rej_noti_reason' => "The following are the reason(s) given by the moderator who rejected your link,

----------------------------------------------------------------------
%s
----------------------------------------------------------------------
",
'link_weekly_digest' => "<#NAME#>,

You are receiving this email because you have subscribed to a weekly email digest for the link <#LINKNAME#>. There have been a total of <#TOTAL#> comments added to this link within the past 7 days.

<#CONTENT#>


You can view these and more comments at the below information page for this link:
<#LINKURL#>

Unsubscribing:
--------------
You can unsubscribe at any time by logging into your control panel and clicking on the \"Manage Watched Links\" link in the \"Links\" tab.
If you are not subscribed to any categories and wish to stop receiving notifications, uncheck the setting \"Send me any updates sent by the board administrator\" found in \"My Settings\" under \"General Settings\" in the \"Settings\" tab.
",
'subject__cat_daily_digest' => "[<#CATNAME#>] Daily Digest",
'subject__cat_weekly_digest' => "[<#CATNAME#>] Weekly Digest",
'subject__link_approved_noti' => "[<#TITLE#>] Link Approval Notification",
'subject__link_daily_digest' => "[<#LINKNAME#>] Daily Digest",
'subject__link_rej_noti' => "[<#TITLE#>] Link Rejection Notification",
'subject__link_weekly_digest' => "[<#LINKNAME#>] Weekly Digest",
'subject__subs_new_comment' => "[<#TITLE#>] New Comment Notification",
'subject__subs_new_link' => "[<#TITLE#>] New Link Notification",
'subject__subs_new_pending_link' => "[<#TITLE#>] New Link Awaiting Approval",
'subs_new_comment' => "<#NAME#>,

You are receiving this message because you have subscribed to the link <#TITLE#>. <#POSTER#> has just submitted a new comment to this link. You may find a copy of the comment below,

----------------------------------------------------------------------
<#COMMENT#>
----------------------------------------------------------------------

You can view this comment and other comments at the below information page for this link:
<#BOARD_ADDRESS#>?app=links&showlink=<#LINKID#>


Unsubscribing:
--------------

You can unsubscribe at any time by logging into your control panel and clicking on the \"Manage Watched Links\" link in the \"Links\" tab.
If you are not subscribed to any categories and wish to stop receiving notifications, uncheck the setting \"Send me any updates sent by the board administrator\" found in \"My Settings\" under \"General Settings\" in the \"Settings\" tab.
",
'subs_new_link' => "<#NAME#>,

You are receiving this message because you have subscribed to the category <#CATEGORY#>. <#POSTER#> has just submitted a new link entitled \"<#TITLE#>\" to this category. You may find a copy of the link's description below,

----------------------------------------------------------------------
<#DESCRIPTION#>
----------------------------------------------------------------------

More information about this link may be found here:
<#BOARD_ADDRESS#>?app=links&showlink=<#LINKID#>

Please note that if you wish to get email notifications of any comments to this link, you will have to click on the
\"Watch this Link\" button shown on the link information page above, or by visiting the below address:
<#BOARD_ADDRESS#>?app=core&module=usercp&tab=links&area=watch&do=watch&watch=link&lid=<#LINKID#>


Unsubscribing:
--------------

You can unsubscribe at any time by logging into your control panel and clicking on the \"Manage Watched Categories\" link in the \"Links\" tab.
If you are not subscribed to any categories and wish to stop receiving notifications, uncheck the setting \"Send me any updates sent by the board administrator\" found in \"My Settings\" under \"General Settings\" in the \"Settings\" tab.
",
'subs_new_pending_link' => "<#NAME#>,

You are receiving this message because you have subscribed to the category <#CATEGORY#>. <#POSTER#> has just submitted a new link entitled \"<#TITLE#>\" to this category and it is currently awaiting approval from a moderator. You may find a copy of the link's description below,

----------------------------------------------------------------------
<#DESCRIPTION#>
----------------------------------------------------------------------

More information about this link may be found at the below address. You may approve or delete the link from here as well.
<#BOARD_ADDRESS#>?app=links&showlink=<#LINKID#>


Unsubscribing:
--------------

You can unsubscribe at any time by logging into your control panel and clicking on the \"Manage Watched Categories\" link in the \"Links\" tab.
If you are not subscribed to any categories and wish to stop receiving notifications, uncheck the setting \"Send me any updates sent by the board administrator\" found in \"My Settings\" under \"General Settings\" in the \"Settings\" tab.
",
 ); 
