<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 04-abr-2012 -006  $
 * </pre>
 * @filename            status.php
 * @author      $Author: juliobarreraa@gmail.com $
 * @package     PRI
 * @subpackage          
 * @link        http://www.codebit.org
 * @since       04-abr-2012
 * @timestamp           14:35:48
 * @version     $Rev:  $
 *
 */
/**
 * Description of status
 *
 * @author juliobarreraa@gmail.com
 */
IPSLib::loadLibrary(IPSLib::getAppDir('members') . '/modules_public/ajax/status.php', 'public_members_ajax_status');

class public_portal_ajax_status extends public_members_ajax_status {

    public $registry;
    private $notify = array();

    public function doExecute(ipsRegistry $registry) {
        $this->registry = $registry;
        /* Gallery Object */
        $classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . '/applications_addon/ips/gallery/sources/classes/gallery.php', 'ipsGallery' );
        $this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
        return parent::doExecute($registry);
    }

    /**
     * Add a new status
     *
     * @return  @e void
     */
    protected function _new() {
        /* INIT */
        $smallSpace = intval($this->request['smallSpace']);
        $su_Twitter = intval($this->request['su_Twitter']);
        $su_Facebook = intval($this->request['su_Facebook']);
        $skin_group = $this->getSkinGroup();
        $forMemberId = intval($this->request['forMemberId']);
        $mentions = str_replace(array('&quot;'), '"', $this->request['mentions']);
        $jsonMentions = json_decode($mentions);

        /* Got content? */
        if (!trim($this->convertAndMakeSafe(str_replace(array('&nbsp;', '&#160;'), '', $_POST['content'])))) {
            $this->returnJsonError($this->lang->words['no_status_sent']);
        }
        $string = $_POST['content'];
        foreach ($jsonMentions as $mention) {
            $pattern = '/(' . utf8_decode($mention->{'value'}) . ')/';
            $member = $this->DB->buildAndFetch(array(
                'select' => 'm.member_id, m.members_seo_name, m.members_display_name',
                'from' => array('members' => 'm'),
                'where' => 'member_id=' . (int) $mention->{'id'},
                    )
            );

            $member['members_seo_name'] = IPSMember::fetchSeoName($member);
            $string = preg_replace($pattern, utf8_decode("@{$member['member_id']}-@{$member['members_display_name']}@"), $string);
        }
        

        $_POST['content'] = $string;
        
		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor = new $classToLoad();
        $_POST['content'] = $this->formatPost($_POST['content']);
        
        
    	/* INIT Image */
		$sessionKey = trim( $this->request['sessionKey'] );
		
		
		/*Create album*/
		/* Cannot create? */
	    $album = $this->DB->buildAndFetch(array('select' => 'album_id', 'from' => 'gallery_albums_main', 'where' => "album_owner_id=".$this->memberData['member_id'] . " and album_name =  'Fotos del muro de {$this->memberData['members_l_username']}'"));
	    if(!(int)$album['album_id']) {
    		if ( !$this->registry->gallery->helper('albums')->canCreate() )
    		{
    			$this->returnJsonError( $this->lang->words['album_cannot_create_limit'] );
    		}
    		
    		/* Fix up names, damn charsets */
        	$name = IPSText::convertUnicode( $this->convertAndMakeSafe( 'Fotos del muro de ' . $this->memberData['members_l_username'], 0 ), true );
    		$name = IPSText::convertCharsets( $name, 'utf-8', IPS_DOC_CHAR_SET );
    		
        	$desc = IPSText::convertUnicode( $this->convertAndMakeSafe( '', 0 ), true );
    		$desc = IPSText::convertCharsets( $desc, 'utf-8', IPS_DOC_CHAR_SET );
    		
    		/* Init data */
    		$album = array( 'album_name'			=> $name,
    						'album_description'		=> $desc,
    						'album_detail_default'	=> intval( 0 ),
    						'album_sort_options'	=> serialize( array( 'key' => 'idate', 'dir' => 'asc' ) ),
    						'album_is_public'		=> intval( 2 ),
    						'album_parent_id'		=> intval( 1 ),
    						'album_owner_id'		=> intval( $this->memberData['member_id'] ),
    						'album_watermark'		=> intval( '' )
    						);
    		
    		/* Save it for the judge */
    		try 
    		{
    			$album = $this->registry->gallery->helper('moderate')->createAlbum( $album );
    		
    		}
    		catch ( Exception $e )
    		{
    			$msg = $e->getMessage();
    			
    			if ( $msg == 'MEMBER_ALBUM_NO_PARENT' )
    			{
    				$msg = $this->lang->words['parent_zero_not_global'];
    			}
    			
    			$this->returnJsonError( $msg );
    		}
    	}
		/*End create album*/
		$album_id   = intval( $album['album_id'] );
		$images     = array();
		
		
		/* Fetch items */
		if ( $sessionKey )
		{
			$images = $this->registry->gallery->helper('upload')->fetchSessionUploadsAsImages( $sessionKey );
		}
		
		
		if( $sessionKey && count( $images ) )
		{
			$type   = 'uploads';
			$Db_images = $this->registry->gallery->helper('upload')->fetchSessionUploadsAsImages( $sessionKey );
			$type      = 'uploads';
			/* Go freaking loopy */
			foreach( $images as $key => $data )
			{
				$_caption = $this->_parseContent($_POST['content']);
									
				/* Parse data */
				$images[ $key ]['description']    = '';
				$images[ $key ]['caption']    = utf8_encode($_caption);
				$images[ $key ]['caption_seo']    = IPSText::makeSeoTitle( $_caption );
				$images[ $key ]['img_album_id']    = $album_id;
			}
			/* Now save them back */
			if ( count( $images ) )
			{
				$this->registry->gallery->helper('image')->save( $images );
			}
		
			/* Finish up image uploading? */
			$this->registry->gallery->helper('upload')->finish( $sessionKey );
	        //Load library status load
	        $classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('portal') . '/sources/timeline.php', 'timelineClass', 'portal');
	
	
	        $this->timeline = new $classToLoad();
//	        $pages = $this->timeline->getPager();
	
	        $row = $this->timeline->status(); //Get Status ajax
	        $statusAjax = utf8_decode($this->registry->output->getTemplate('portal')->statusIndividual($row, false));
	        $this->returnJsonArray(array('status' => 'success', 'html' => $this->cleanOutput(base64_encode($statusAjax))));
			
		}else{

	        /* Set Author */
	        $this->registry->getClass('memberStatus')->setAuthor($this->memberData);
	
	        /* Set Content */
	        $this->registry->getClass('memberStatus')->setContent(trim($this->convertAndMakeSafe($_POST['content'])));
	
	        /* Can we create? */
	        if (!$this->registry->getClass('memberStatus')->canCreate()) {
	            $this->returnJsonError($this->lang->words['status_off']);
	        }
	
	        /* Update or comment? */
	        if ($forMemberId && $forMemberId != $this->memberData['member_id']) {
	            $owner = IPSMember::load($forMemberId);
	
	            if (!$owner['pp_setting_count_comments']) {
	                $this->returnJsonError($this->lang->words['status_off']);
	            }
	
	            /* Set owner */
	            $this->registry->getClass('memberStatus')->setStatusOwner($owner);
	        } else {
	            /* Set post outs */
	            $this->registry->getClass('memberStatus')->setExternalUpdates(array('twitter' => $su_Twitter, 'facebook' => $su_Facebook));
	        }
	
	        /* Update */
	        $newStatus = $this->registry->getClass('memberStatus')->create();
	
	        $insertId = $this->DB->buildAndFetch(array(
	            'select' => 'pl.id',
	            'from' => array('portal_logbook' => 'pl'),
	            'order' => 'id DESC',
	            'limit' => '1',
	                ));
	
	
	        $this->notify['id'] = (int) $insertId['id'];
	        foreach ($jsonMentions as $mention) {
	            IPSDebug::fireBug('info', array('[Portal] User Mention: ' . $member_id));
	            $member_id = (int) $mention->{'id'};
	            $memberData = IPSMember::load($member_id);
	            $this->_notification($memberData);
	        }
	        /* Now grab the reply and return it */
	        $new = $this->registry->getClass('output')->getTemplate('portal')->statusUpdates($this->registry->getClass('memberStatus')->fetch($this->memberData['member_id'], array('relatedTo' => $forMemberId, 'isApproved' => true, 'sort_dir' => 'desc', 'limit' => 1)), $smallSpace);
	        //Load library status load
	        $classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('portal') . '/sources/timeline.php', 'timelineClass', 'portal');
	
	
	        $this->timeline = new $classToLoad();
//	        $pages = $this->timeline->getPager();
	
	        $row = $this->timeline->status($this->notify['id']); //Get Status ajax
	        $statusAjax = utf8_decode($this->registry->output->getTemplate('portal')->statusIndividual($row, false));
	        $this->returnJsonArray(array('status' => 'success', 'html' => $this->cleanOutput(base64_encode($statusAjax))));
	     }
    }

    /**
     * Add a reply statussesses
     *
     * @return  @e void
     */
    protected function _showAll()
    {
        /* INIT */
        $status_id = intval( $this->request['status_id'] );
        $st        = intval( $this->request['st'] );

        $skin_group = $this->getSkinGroup();
        
        /* Quick check? */
        if ( ! $status_id )
        {
            $this->returnJsonError( $this->lang->words['status_off'] );
        }
        
        /* Set Author */
        $this->registry->getClass('memberStatus')->setAuthor( $this->memberData );
        
        /* Set Data */
        $this->registry->getClass('memberStatus')->setStatusData( $status_id );
        
        /* And the number of replies */
        $statusData = $this->registry->getClass('memberStatus')->getStatusData();
        /* Fetch */
        $this->returnJsonArray( array( 'status' => 'success', 'status_replies' => $statusData['status_replies'] + 1, 'html' => $this->cleanOutput( $this->registry->getClass('output')->getTemplate( 'portal' )->statusReplies( $this->fetchAllReplies($status_id) ) ) ) );
    }

    function _reply() {
        /* INIT */
        $status_id = intval($this->request['status_id']);
        if(!$_POST['content']) {
	        $_POST['content'] = $_POST['comment-'.$status_id];
	        unset($_POST['comment-'.$status_id]);
        }
        $comment = $this->convertAndMakeSafe(utf8_encode($_POST['content']));
        $id = intval($this->request['id']);

        $skin_group = $this->getSkinGroup();
        $mentions = str_replace(array('&quot;'), '"', $this->request['mentions']);
        $jsonMentions = json_decode($mentions);
        
        
        foreach ($jsonMentions as $mention) {
            $pattern = '/(' . $mention->{'value'} . ')/';
            $member = $this->DB->buildAndFetch(array(
                'select' => 'm.member_id, m.members_seo_name, m.members_display_name',
                'from' => array('members' => 'm'),
                'where' => 'member_id=' . (int) $mention->{'id'},
                    )
            );

            $member['members_seo_name'] = IPSMember::fetchSeoName($member);
            $comment = preg_replace($pattern, "@{$member['member_id']}-@{$member['members_display_name']}@", $comment);
        }
        
        $comment = $this->_parseContent($comment);
        
        $comment = utf8_decode($comment);
        
        
        
        /* Quick check? */
        if (!$status_id OR !$comment) {
            $this->returnJsonError($this->lang->words['status_no_reply']);
        }

        $this->DB->insert('log_reply', array(
            'log_reply_id' => $status_id,
            'log_member_id' => $this->memberData['member_id'],
            'log_date' => time(),
            'log_content' => $comment,
        ));
        
        $insertId = $this->DB->buildAndFetch(array(
            'select' => 'lr.log_id',
            'from' => array('log_reply' => 'lr'),
            'order' => 'log_id DESC',
            'limit' => '1',
                ));
        
        $this->notify['id'] = (int) $insertId['log_id'];
        foreach ($jsonMentions as $mention) {
            IPSDebug::fireBug('info', array('[Portal] User Mention: ' . $member_id));
            $member_id = (int) $mention->{'id'};
            $this->_notification($memberData);
        }


        /* Now grab the reply and return it */
        $reply = $this->registry->getClass('output')->getTemplate('portal')->statusReplies($this->fetchAllReplies($status_id, array('sort_dir' => 'desc', 'limit' => 1)));
        /* And the number of replies */
        $statusData = $this->registry->getClass('memberStatus')->getStatusData();

        $this->returnJsonArray(array('status' => 'success', 'html' => $this->cleanOutput($reply), 'status_replies' => $statusData['status_replies'] + 1));
    }

    /**
     * Delete a status
     *
     * @return  @e void
     */
    protected function _deleteStatus() {
        /* INIT */
        $status_id = intval($this->request['status_id']);

        /* Quick check? */
        if (!$status_id) {
            $this->returnJsonError($this->lang->words['status_off']);
        }

        $this->DB->delete( 'portal_logbook', 'id='.$status_id );



        /* Success? */
        $this->returnJsonArray(array('status' => 'success'));
    }

    private function _notification($memberData) {
        /* Load the notification library */
        $classToLoad = IPSLib::loadLibrary(IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications');
        $notifyLibrary = new $classToLoad($this->registry);
        
        $url = $this->registry->output->buildSEOUrl('app=portal&module=portal&section=status&id=' . $this->notify['id']);
        /* We need to build the notification message - the best way to do this is to build the email to send.  Here, we build the
          email message for the language string 'my_email_template' */
        IPSText::getTextClass('email')->setPlainTextTemplate(html_entity_decode(sprintf('<a href="%s">%s</a> te menciono en una <a href="%s">publicación</a>', $this->registry->output->buildSEOUrl('showuser=' . $memberData['member_id'], 'publicNoSession', $this->memberData['members_seo_name'], 'showuser'), $this->memberData['members_display_name'], $url)));
        IPSText::getTextClass('email')->buildMessage(array('NAME' => $memberData['members_display_name'], 'MESSAGE' => sprintf('<a href="%s">%s</a> te menciono en una <a href="%s">publicación</a>', $this->registry->output->buildSEOUrl('showuser=' . $memberData['member_id'], 'publicNoSession', $this->memberData['members_seo_name'], 'showuser'), $this->memberData['members_display_name'], $url)));
		IPSText::getTextClass('email')->subject = sprintf('<a href="%s">%s</a> te menciono en una <a href="%s">publicación</a>', $this->registry->output->buildSEOUrl('showuser=' . $memberData['member_id'], 'publicNoSession', $this->memberData['members_seo_name'], 'showuser'), $this->memberData['members_display_name'], $url);
		
		IPSText::getTextClass('email')->to = $memberData['email'];
        
        IPSText::getTextClass('email')->sendMail();
        /* Set the to member - the one we loaded previously */
        $notifyLibrary->setMember($memberData);
        /* Set the from member - typically the viewing user */
        $notifyLibrary->setFrom($this->memberData);
        /* Set the notification key - this must be a key defined in our notifications.php extension file */
        $notifyLibrary->setNotificationKey('new_likes');
        /* Set the notification URL - this should typically be a direct link to the content the user is being notified about */
        $notifyLibrary->setNotificationUrl($this->registry->output->buildSEOUrl('app=portal&amp;module=portal&section=status&amp;id=' . $this->notify['id']));
        /* Set the notification text - this will be the plain text version of the email we previously built */
        $notifyLibrary->setNotificationText(IPSText::getTextClass('email')->getPlainTextContent());
        /* Set the notifiation title - while we can use the email subject via IPSText::getTextClass('email')->subject, it is preferred to use
          a language string that has all of the relevant pieces of data appropriate linked */
        $title = sprintf('<a href="%s">%s</a> te menciono en una <a href="%s">publicación</a>', $this->registry->output->buildSEOUrl('showuser=' . $memberData['member_id'], 'publicNoSession', $this->memberData['members_seo_name'], 'showuser'), $this->memberData['members_display_name'], $url);
        $notifyLibrary->setNotificationTitle($title);
        /* You can OPTIONALLY set a subject to use explicitly for emails.  This can help facilitate built-in 'threading' capabilities of email clients (e.g. by ensuring
          that every email for the same content has the same title, while still allowing inline notifications to have unique descriptive titles) */
        $notifyLibrary->setEmailSubject('New content added');
        /* Send the notification */
        try {
            $notifyLibrary->sendNotification();
        } catch (Exception $e) {
            
        }
    }

    /**
     * Fetch all replies to a status
     * Default filters are sorted on reply_date ASC
     *
     * @param   mixed   [Array of member data OR member ID INT for member updating their status - will use ->getAuthor() if null]   
     * @param   array   Array of sort/filter data ( member_id [int], latest_only [0,1], offset [int], limit [int], unix_cutoff [int], sort_dir [asc,desc], sort_field [string] )
     */
    public function fetchAllReplies( $status=null, $filters=array() )
    {
        /* Load the class */
        if (!$this->registry->isClassLoaded('repCache')) {
            $classToLoad = IPSLib::loadLibrary(IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache');
            $this->registry->setClass('repCache', new $classToLoad());
        }
        //$status = ( $status === null ) ? $this->_internalData['StatusData'] : ( ( is_array( $status ) ) ? $status : $this->_loadStatus( $status ) );
        $where    = array();
        $replies = array();
        
        $sort_dir   = ( $filters['sort_dir'] == 'desc' ) ? 'desc' : 'asc';
        $sort_field = ( isset( $filters['sort_field'] ) ) ? $filters['sort_field'] : 'log_date';
        $offset     = ( isset( $filters['offset'] ) ) ? intval( $filters['offset'] ) : 0;
        $limit      = ( isset( $filters['limit'] ) ) ? intval( $filters['limit'] ) : 100;
        /* Grab them */
        $this->DB->build( array( 'select'   => 's.*',
                                 'from'     => array( 'log_reply' => 's' ),
                                 'where'    => 's.log_reply_id=' . intval($status),
                                 'order'    => 's.' . $sort_field . ' ' . $sort_dir,
                                 'limit'    => array( $offset, $limit ),
                                 'add_join' => array(array(  'select'   => 'm.*',
                                                             'from'     => array( 'members' => 'm' ),
                                                             'where'    => 'm.member_id=s.log_member_id',
                                                             'type'     => 'left' ),
                                                     array(  'select'   => 'pp.*',
                                                             'from'     => array( 'profile_portal' => 'pp' ),
                                                             'where'    => 'pp.pp_member_id=m.member_id',
                                                             'type'     => 'left' ) ) ) );
                                                             
        $o = $this->DB->execute();
        
        while( $row = $this->DB->fetch( $o ) )
        {
            /* Format some data */
            $row['reply_date_formatted'] = $this->registry->getClass('class_localization')->getDate( $row['log_date'], 'SHORT' );
            $row['_canDelete']           = $this->canDeleteReply( $this->getAuthor(), $row, $status );
            
            /* Format member */
            $row = IPSMember::buildDisplayData( $row, array( 'reputation' => 0, 'warn' => 0 ) );
            $row['reply_date_formatted'] = $row['log_date'];

	        $joins = array(array('select' => 'm.*', 'from' => array('members' => 'm'), 'where' => 'm.member_id=lr.log_member_id', 'type' => 'left'));
	        $joins[] = $this->registry->getClass('repCache')->getTotalRatingJoin('log_id', $row['log_id'], 'portal');
	        $joins[] = $this->registry->getClass('repCache')->getUserHasRatedJoin('log_id', $row['log_id'], 'portal');
			$data = $this->DB->buildAndFetch(array('select' => 'lr.*',
					'from' => array('log_reply' => 'lr'),
					'where' => "lr.log_id={$row['log_id']}",
					'add_join' => $joins
			));
	        
	        if($this->settings['reputation_enabled'] && $this->registry->repCache->isLikeMode()){
	            $row['like'] = $this->registry->repCache->getLikeFormatted(array('app' => 'portal', 'type' => 'log_id', 'log_id' => $data['log_id'], 'rep_like_cache' => $data['rep_like_cache']));
	        }
	        
            
            $replies[ $row['log_id'] ] = $row;
        }
        
        /* Phew */
        return $replies;
    }
    
	/**
	 * Auto parse some stuff
	 *
	 * Eventually could abstract it out but for now, this will do. Mkay.
	 */
	private function _parseContent( $content, $creator='' )
	{
		/* Auto parse tags */
		if ( $this->settings['su_parse_url'] )
		{
			$content = preg_replace_callback( '#(^|\s|\(|>|\](?<!\[url\]))((?:http|https|news|ftp)://\w+[^\),\s\<\[]+)#is', array( $this, '_autoParseUrls' ), $content );
		}
		
                if ( $this->settings['tc_parse_names'] )
                {
                		//var_dump(html_entity_decode(utf8_encode($content)));exit;
                        $content = preg_replace_callback('#(^|\s)@([0-9]+)-@([a-z_A-Z0-9 áéíóúÁÉÍÓÚñÑ\.]+)@#', array( $this, '_autoParseNamesInternal' ), $content );
                }
		
		return $content;
	}
	
	/**
	 * Callback to auto-parse @names
	 * 
	 * @param	array		Matches from the regular expression
	 * @return	string		Converted text
	 */
	private function _autoParseNamesInternal( $matches )
	{
            return $matches[1].'<a href="' . $this->registry->output->buildSEOUrl( 'showuser=' . $matches[2], 'publicNoSession', IPSText::makeSeoTitle($matches[3]), 'showuser' ) . '" class="su_links">' . $matches[3] . '</a>';
	}
	
	/**
	 * Callback to auto-parse urls
	 * I totally stole this from Brandon's code but do NOT tell him.
	 * 
	 * @param	array		Matches from the regular expression
	 * @return	string		Converted text
	 */
	private function _autoParseUrls( $matches )
	{
		/* We use this function in other areas also */
		$matches[3] = ( $matches[3] ) ? $matches[3] : $matches[2];
		
		//-----------------------------------------
		// Adding rel='nofollow'?
		//-----------------------------------------
		
		$rels	= array();
		$rel	= '';

		if( $this->settings['posts_add_nofollow'] )
		{
			$rels[]	= "nofollow";
		}
		
		if( $this->settings['links_external'] )
		{
			$rels[]	= "external";
		}
		
		if( count($rels) )
		{
			$rel = " rel='" . implode( ' ', $rels ) . "'";
		}
		
		return $matches[1] . '<a href="' . $matches[2] . '"' . $rel . ' class="su_links">' . $matches[3] . '</a>';
	}
	
	private function _uploadIframe() {
        //$this->_upload = $this->registry->gallery->helper('upload');
//        $this->ajax->returnHtml( $this->registry->output->getTemplate( 'gallery_post' )->attachiFrameUpload( 'upload_file' ) );
	}

	/**
	 * Format Post: Converts BBCode, smilies, etc
	 *
	 * @param	string	Raw Post
	 * @return	string	Formatted Post
	 * @author	MattMecham
	 */
	public function formatPost( $postContent )
	{ 
		/* Set HTML Flag for the editor. Bug #19796 */
		$this->editor->setAllowHtml( ( intval($this->request['post_htmlstatus']) AND $this->getForumData('use_html') AND $this->getAuthor('g_dohtml') ) ? 1 : 0 );
		
		$postContent = $this->editor->process( $postContent );

		//-----------------------------------------
		// Parse post
		//-----------------------------------------

		if( $postContent )
		{
			IPSText::getTextClass( 'bbcode' )->parse_smilies    = $this->getSettings('enableEmoticons');
			IPSText::getTextClass( 'bbcode' )->parse_html    	= (intval($this->request['post_htmlstatus']) AND $this->getForumData('use_html') AND $this->getAuthor('g_dohtml')) ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br		= intval($this->request['post_htmlstatus']) == 2 ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode    	= $this->getForumData('use_ibc');
			IPSText::getTextClass( 'bbcode' )->parsing_section	= 'topics';
			
			$postContent = IPSText::getTextClass( 'bbcode' )->preDbParse( $postContent );
		}
		
		# Make this available elsewhere without reparsing, etc
		$this->setPostContentPreFormatted( $postContent );
		
		return $postContent;
	}


}

?>
