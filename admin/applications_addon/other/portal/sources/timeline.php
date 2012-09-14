<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 09-may-2012 -006  $
 * </pre>
 * @filename            timeline.php
 * @author      $Author: juliobarreraa@gmail.com $
 * @package     PRI
 * @subpackage          
 * @link        http://www.codebit.org
 * @since       09-may-2012
 * @timestamp           16:26:49
 * @version     $Rev:  $
 *
 */

/**
 * Description of timeline
 *
 * @author juliobarreraa@gmail.com
 */
class timelineClass {

    //Protected
    protected $registry;
    protected $memberData;
    protected $member;
    protected $DB;
    //Public
    public $lang;
    //Private
    private $perpage;
    private $st;
    private $totalItems;
    private $higher;

    public function __construct($st = 0, $higher = false) {
        $this->registry = ipsRegistry::instance();
        $this->settings = & $this->registry->fetchSettings(); //Get settings timeline_max_status
        $this->memberData = & $this->registry->member()->fetchMemberData(); //This member data 
        $this->lang = $this->registry->getClass('class_localization'); //Load language
        $this->DB = $this->registry->DB();
        //Obsolete
        $this->member = $this->registry->member();


        //Init
        $this->st = (int) $st;
        $this->perpage = intval($this->settings['max_status_per_page']);
        $this->totalItems = intval($this->settings['timeline_max_status']);
        $this->higher = $higher;
        /* Load the class */
        if (!$this->registry->isClassLoaded('repCache')) {
            $classToLoad = IPSLib::loadLibrary(IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache');
            $this->registry->setClass('repCache', new $classToLoad());
        }
    }

    public function getPager($link=false) {
        if($link){
            return $this->settings['base_url']."app=portal&module=ajax&section=load&secure_key={$this->member->form_hash}&st=".($this->st + $this->perpage);
        }
        $buildSql = array(
            'select' => 'count(pl.id) as count , user_id, conf_table_id',
            'from' => array('portal_logbook' => 'pl'),
            'limit' => array(0, ($this->perpage - 1)),
            'add_join' => array(array(
                    'select' => '',
                    'from' => array('portal_tables_conf' => 'ct'),
                    'where' => 'pl.conf_table_id=ct.id',
                    'type' => 'inner'
                ),
                array(
                    'select' => '',
                    'from' => array('profile_friends' => 'pf'),
                    'where' => '(pl.user_id = pf.friends_member_id AND (pf.friends_friend_id = ' . (int) $this->memberData['member_id'] . ' OR pl.user_id = ' . (int) $this->memberData['member_id'] . '))',
                    'type' => 'inner',
            )),
        );

        $row = $this->DB->buildAndFetch(
                $buildSql
        );

        $pages = $this->registry->output->generatePagination(array('totalItems' => (int) $row['count'],
            'itemsPerPage' => $this->perpage,
            'currentStartValue' => $this->st,
            'baseUrl' => "app=portal&module=ajax&section=load&secure_key={$this->member->form_hash}",
            'method' => 'nextPrevious',
            'uniqid' => 'finalPage',
                ));

        return $pages;
    }

    public function getStatus($isUserProfile = false) {
        $user_id = (int)$_GET['user'];
        if($user_id == 0) {
            $user_id = (int)$_GET['showuser'];
        }
        if(!$user_id) {
            $user_id = $this->memberData['member_id'];
        }
        $userProfile = '';
        if($isUserProfile){
            $userProfile = "AND (pl.user_id = {$user_id})";
        }
        $buildSql = array(
            'select' => 'pl.id, conf_table_id , user_id, action_id, created_at, text_name',
            'from' => array('portal_logbook' => 'pl'),
            'order' => 'pl.id DESC',
            'where' => ($this->higher ? 'pl.id>' . $this->st : '1') . ' AND (pl.user_id = ' . (int) $this->memberData['member_id'] . " OR pf.friends_id <> 'NULL'" . ") ".$userProfile,
            'add_join' => array(array(
                    'select' => 'ct.table_name, ct.primary_key_name, ct.date_name, ct.user_id_name',
                    'from' => array('portal_tables_conf' => 'ct'),
                    'where' => 'pl.conf_table_id=ct.id',
                    'type' => 'inner'
                ),
                array(
                    'select' => 'pf.friends_id',
                    'from' => array('profile_friends' => 'pf'),
                    'where' => '(pl.user_id = pf.friends_member_id AND (pf.friends_friend_id = ' . (int) $this->memberData['member_id'] . '))',
                    'type' => 'left',
            )),
        );


        if (!$this->higher)
            $buildSql['limit'] = array($this->st, ($this->perpage - 1));
        $this->DB->build(
                $buildSql
        );

        $this->DB->execute();
        
        $tables = array();
        while ($table = $this->DB->fetch()) {
            $tables[] = $table;
        }
        

        //Tenemos la tabla y los campos a consultar, Go go
        $rows = array();
        if (is_array($tables)) {
            foreach ($tables as $_table) {
	            $__row = $this->__row($_table);
	            if(!is_null($__row))
	                $rows[] = $__row;
            }
            return $rows;
        }
    }

    public function status($id = 0) {
    	if($id) {
	    	$where = 'pl.id = ' . (int) $id;
    	}else {
	    	$where = '1';
    	}
        $_table = $this->DB->buildAndFetch(array(
            'select' => 'pl.id, conf_table_id , user_id, action_id, created_at, text_name',
            'from' => array('portal_logbook' => 'pl'),
            'order' => 'pl.id DESC',
            'where' => $where,
            'add_join' => array(array(
                    'select' => 'ct.table_name, ct.primary_key_name, ct.date_name, ct.user_id_name',
                    'from' => array('portal_tables_conf' => 'ct'),
                    'where' => 'pl.conf_table_id=ct.id',
                    'type' => 'inner'
            )),
                ));


        //Tenemos la tabla y los campos a consultar, Go go
        $row = array();
        if (is_array($_table)) {
            $row = $this->__row($_table);
        }
        return $row;
    }
    
    public function getPhotografy($status_id)
    {
        $_table = $this->DB->buildAndFetch(array(
            'select' => 'pl.id, conf_table_id , user_id, action_id, created_at, text_name',
            'from' => array('portal_logbook' => 'pl'),
            'order' => 'pl.id DESC',
            'where' => 'pl.id = ' . (int) $status_id,
            'add_join' => array(array(
                    'select' => 'ct.table_name, ct.primary_key_name, ct.date_name, ct.user_id_name',
                    'from' => array('portal_tables_conf' => 'ct'),
                    'where' => 'pl.conf_table_id=ct.id',
                    'type' => 'inner'
            )),
                ));


        //Tenemos la tabla y los campos a consultar, Go go
        $row = array();
        if (is_array($_table)) {
            $row = $this->__row($_table);
        }
        return $row;
    }

    public function getMetaInfo($url) {
        $html = $this->file_get_contents_curl($url);
        return $this->__meta($html, $url);
    }

    private function file_get_contents_curl($url) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $data = curl_exec($ch);
        curl_close($ch);


        return $data;
    }

    private function __meta($html, $url) {
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $nodes = $doc->getElementsByTagName('title');
        //get and display what you need:
        $title = $nodes->item(0)->nodeValue;

        $metas = $doc->getElementsByTagName('meta');
        $imgs = $doc->getElementsByTagName('img');

        for ($i = 0; $i < $metas->length; $i++) {
            $meta = $metas->item($i);
            if ($meta->getAttribute('name') == 'description')
                $description = utf8_decode($meta->getAttribute('content'));
        }

        $data = array();

        if (isset($title)) {
            $data['title'] = $title;
        }

        if (isset($description)) {
            $data['description'] = $description;
        }

        $data['images'] = array();
        $imgLength = 9;
        if (count($imgs->length) <= 9) {
            $imgLength = $imgs->length;
        }
        for ($i = 0; $i < $imgLength; $i++) {
            $img = $imgs->item($i);
            //TODO: Multiple imágenes
            //$data['images'][] = $img->getAttribute('src');
            $data['images'] = $img->getAttribute('src');
        }

        $data['url'] = $url;


        return $data;
    }

    private function replaceDataTable($field) {
        //Aquí haremos la consulta de cada uno de los campos que se retornaran para ir armando el output
        //print_r($field[0]);
        //table.{tid}
        //table.join{post.pid}
        //table.join{post.post}
        //Primero obtenemos el campo n
        if (count($field)) {
            preg_match('/{\w+}/', $field[0], $match);
            if (count($match)) {
                $match[0] = substr($match[0], 1, strlen($match[0]) - 2);
                $this->_add_joins[$this->_tableName][] = $match[0];
            } else {
                preg_match('/{\w+\.\w+}/', $field[0], $match);
                if (count($match)) {
                    $match[0] = substr($match[0], 1, strlen($match[0]) - 2);
                    $separate = explode('.', $match[0]);
                    $this->_add_joins[$separate[0]][] = $separate[1];
                }
            }
        }
    }

    private function __row($_table) {
        $this->_tableName = $_table['table_name'];
        unset($this->_add_joins);
        //Table text_name tiene los campos a consultar de la base de datos para eso usaremos expresiones regulares para obtener los valores encerrados entre {}
        preg_replace_callback("/(table.\{\w+\})|(table\.join\{\w+\.\w+\})/", array($this, 'replaceDataTable'), $_table['text_name']);

        $this->_add_joins[$_table['table_name']][] = $_table['user_id_name'];
        $this->_add_joins[$_table['table_name']][] = $_table['date_name'];

        //$selectMain = "tn." . join(", tn.", $this->_add_joins[$_table['table_name']]);
        //$selectPost = "post." . join(", post.", $this->_add_joins['post']);
        //Por cada tabla obtenemos el campo a consultar $_table['text_name']

        $__fields = array();
        ;
        foreach ($this->_add_joins as $__tableName => $__tableData) {
            foreach ($__tableData as $__data) {
                $__fields[] = "$__tableName.$__data";
            }
        }

        //Tenemos en __fields los campos para seleccionar de la tabla
        $row = $this->DB->buildAndFetch(array(
            'select' => join(',', $__fields), //"tn.{$_table['user_id_name']} as authorId, tn.{$_table['date_name']} as dateStatus",
            'from' => array($_table['table_name'] => $_table['table_name']),
            'where' => "{$_table['table_name']}.{$_table['primary_key_name']} = {$_table['action_id']}",
            'add_join' => array(
                array(
                    'select' => 'm.member_id, m.members_seo_name, m.members_display_name',
                    'from' => array('members' => 'm'),
                    'where' => "{$_table['table_name']}.{$_table['user_id_name']}=m.member_id"
                )
            ),
                ));
                
        IPSText::getTextClass('bbcode')->parsing_section = 'my_section';
        IPSText::getTextClass('bbcode')->parse_smilies = TRUE;
        IPSText::getTextClass('bbcode')->parse_bbcode = TRUE;
        IPSText::getTextClass('bbcode')->parse_html = FALSE;
        IPSText::getTextClass('bbcode')->parse_nl2br = TRUE;

        IPSText::getTextClass('bbcode')->bypass_badwords = FALSE;
        IPSText::getTextClass('bbcode')->parsing_mgroup = $this->memberData['member_group_id'];
        IPSText::getTextClass('bbcode')->parsing_mgroup_others = $this->memberData['mgroup_others'];
        
        $bindOutput = $_table['text_name'];
        //$row['status_date'] = $row[$_table['']]
        $isPhoto = false;
        if (isset($row)) {
            foreach ($row as $__key => $___data) {
	            if($__key=='entry_image'){
		            $isPhoto['image'] = $___data;
	            }
	            if($__key=='entry') {
		            $isPhoto['caption'] = IPSText::getTextClass('bbcode')->preDisplayParse($___data);
	            }
	            if($__key == 'medium_file_name') {
		            $isPhoto['image'] = $row['directory'] . '/' .$___data;
	            }
	            if($__key == 'entry_status') {
		            if($___data == 'draft') {
			            return null;
		            }
	            }
	            
                $bindOutput = preg_replace("/table\.\{$__key\}/", $___data, $bindOutput);
            }
        }




        $date = $row[$_table['date_name']];

        $text = IPSText::getTextClass('bbcode')->preDisplayParse($bindOutput);

        $row = IPSMember::buildDisplayData(IPSMember::load($row['member_id'], 'all'));
        $row['textStatus'] = $text;
        //$row = IPSMember::buildProfilePhoto($row);
        $row['id'] = $_table['id'];
        $row['date_name'] = $date;
        

        /* Load the class */
        if (!$this->registry->isClassLoaded('repCache')) {
            $classToLoad = IPSLib::loadLibrary(IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache');
            $this->registry->setClass('repCache', new $classToLoad());
        }
        $row['status_id'] = $_table['id'];
        $row['status_date_formatted_short'] = $row['date_name'];


        //Like

        $joins = array(array('select' => 'm.*', 'from' => array('members' => 'm'), 'where' => 'm.member_id=p.user_id', 'type' => 'left'));
        $joins[] = $this->registry->getClass('repCache')->getTotalRatingJoin('id', $row['id'], 'portal');
        $joins[] = $this->registry->getClass('repCache')->getUserHasRatedJoin('id', $row['id'], 'portal');

        $data = $this->DB->buildAndFetch(array('select' => 'p.*',
            'from' => array('portal_logbook' => 'p'),
            'where' => "p.id={$row['id']}",
            'add_join' => $joins
                ));
                



        if ($this->settings['reputation_enabled'] && $this->registry->repCache->isLikeMode()) {
            $row['like'] = $this->registry->repCache->getLikeFormatted(array('app' => 'portal', 'type' => 'id', 'id' => $data['id'], 'rep_like_cache' => $data['rep_like_cache']));
        }

        $countReply = $this->DB->buildAndFetch(array(
            'select' => 'count(lr.log_id) as count',
            'from' => array('log_reply' => 'lr'),
            'where' => 'lr.log_reply_id = ' . $row['id'],
                ));


        $this->DB->build(array(
            'select' => 'lr.*',
            'from' => array('log_reply' => 'lr'),
            'where' => 'lr.log_reply_id = ' . $row['id'],
            'limit' => array(0, 3),
            'order' => 'lr.log_id DESC'
        ));

        $this->DB->execute();

        while ($reply = $this->DB->fetch()) {
            $replys[] = $reply;
        }
        

        if (isset($replys)) {
            foreach ($replys as $reply) {
		        //Like
		
		        $joins = array(array('select' => 'm.*', 'from' => array('members' => 'm'), 'where' => 'm.member_id=lr.log_member_id', 'type' => 'left'));
		        $joins[] = $this->registry->getClass('repCache')->getTotalRatingJoin('log_id', $reply['log_id'], 'portal');
		        $joins[] = $this->registry->getClass('repCache')->getUserHasRatedJoin('log_id', $reply['log_id'], 'portal');
				$data = $this->DB->buildAndFetch(array('select' => 'lr.*',
						'from' => array('log_reply' => 'lr'),
						'where' => "lr.log_id={$reply['log_id']}",
						'add_join' => $joins
				));
		        if($this->settings['reputation_enabled'] && $this->registry->repCache->isLikeMode()){
		            $like = $this->registry->repCache->getLikeFormatted(array('app' => 'portal', 'type' => 'log_id', 'log_id' => $data['log_id'], 'rep_like_cache' => $data['rep_like_cache']));
		        }
		        
                $replyInfo = IPSMember::buildDisplayData(IPSMember::load($reply['log_member_id'], 'all'));
                $row['replies'][] =
                        array(
                            'log_id' => $reply['log_id'],
                            'reply_member_id' => $data['member_id'],
                            'reply_date' => $reply['log_date'],
                            'log_content' => $reply['log_content'],
                            'reply_date_formatted' => $reply['log_date'],
                            '_canDelete' => '1',
                            'reply_status_id' => $row['id'],
                            'member_id' => $reply['log_member_id'],
                            'name' => $replyInfo['name'],
                            'member_group_id' => $replyInfo['member_group_id'],
                            'email' => $replyInfo['email'],
                            'joined' => $replyInfo['joined'],
                            'ip_address' => $replyInfo['ip_address'],
                            'posts' => $replyInfo['posts'],
                            'title' => $replyInfo['title'],
                            'allow_admin_mails' => $replyInfo['allow_admin_mails'],
                            'time_offset' => $replyInfo['time_offset'],
                            'skin' => $replyInfo['skin'],
                            'warn_level' => $replyInfo['warn_level'],
                            'warn_lastwarn' => $replyInfo['warn_lastwarn'],
                            'language' => $replyInfo['language'],
                            'last_post' => $replyInfo['last_post'],
                            'restrict_post' => $replyInfo['restrict_post'],
                            'view_sigs' => $replyInfo['view_sigs'],
                            'view_img' => $replyInfo['view_img'],
                            'bday_day' => $replyInfo['bday_day'],
                            'bday_month' => $replyInfo['bday_month'],
                            'bday_year' => $replyInfo['bday_year'],
                            'msg_count_new' => $replyInfo['msg_count_new'],
                            'msg_count_total' => $replyInfo['msg_count_total'],
                            'msg_count_reset' => $replyInfo['msg_count_reset'],
                            'msg_show_notification' => $replyInfo['msg_show_notification'],
                            'misc' => $replyInfo['misc'],
                            'last_visit' => $replyInfo['last_visit'],
                            'last_activity' => $replyInfo['last_activity'],
                            'dst_in_use' => $replyInfo['dst_in_use'],
                            'coppa_user' => $replyInfo['coppa_user'],
                            'mod_posts' => $replyInfo['mod_posts'],
                            'auto_track' => $replyInfo['auto_track'],
                            'temp_ban' => $replyInfo['temp_ban'],
                            'login_anonymous' => $replyInfo['login_anonymous'],
                            'ignored_users' => $replyInfo['ignored_users'],
                            'mgroup_others' => $replyInfo['mgroup_others'],
                            'org_perm_id' => $replyInfo['org_perm_id'],
                            'member_login_key' => $replyInfo['member_login_key'],
                            'member_login_key_expire' => $replyInfo['member_login_key_expire'],
                            'has_blog' => $replyInfo['has_blog'],
                            'blogs_recache' => $replyInfo['blogs_recache'],
                            'has_gallery' => $replyInfo['has_gallery'],
                            'members_auto_dst' => $replyInfo['members_auto_dst'],
                            'members_display_name' => $replyInfo['members_display_name'],
                            'members_seo_name' => $replyInfo['members_seo_name'],
                            'members_created_remote' => $replyInfo['members_created_remote'],
                            'members_disable_pm' => $replyInfo,
                            'members_l_display_name' => $replyInfo,
                            'members_l_username' => $replyInfo,
                            'failed_logins' => $replyInfo,
                            'failed_login_count' => '0',
                            'members_profile_views' => '0',
                            'members_pass_hash' => '98fdc8bdd1352c66649071b0fb5b248e',
                            'members_pass_salt' => ',CUT;',
                            'member_banned' => '0',
                            'member_uploader' => 'flash',
                            'members_bitoptions' => '0',
                            'fb_uid' => '0',
                            'fb_emailhash' => '',
                            'fb_lastsync' => '0',
                            'members_day_posts' => '0,0',
                            'live_id' => '',
                            'twitter_id' => '',
                            'twitter_token' => '',
                            'twitter_secret' => '',
                            'notification_cnt' => '0',
                            'tc_lastsync' => '0',
                            'fb_session' => '',
                            'fb_token' => '',
                            'ips_mobile_token' => '',
                            'unacknowledged_warnings' => '',
                            'gallery_perms' => '1:1:1',
                            'my_member_id' => '1',
                            'field_1' => '',
                            'field_2' => '',
                            'field_3' => '',
                            'field_4' => '',
                            'field_5' => '',
                            'field_6' => '',
                            'field_7' => '',
                            'field_8' => '',
                            'field_9' => '',
                            'field_10' => '',
                            'field_11' => '',
                            'pp_member_id' => $replyInfo['pp_member_id'],
                            'pp_last_visitors' => '',
                            'pp_rating_hits' => '0',
                            'pp_rating_value' => '0',
                            'pp_rating_real' => '0',
                            'pp_main_photo' => $replyInfo['pp_main_photo'],
                            'pp_main_width' => $replyInfo['pp_main_width'],
                            'pp_main_height' => $replyInfo['pp_main_height'],
                            'pp_thumb_photo' => $replyInfo['pp_thumb_photo'],
                            'pp_thumb_width' => $replyInfo['pp_thumb_width'],
                            'pp_thumb_height' => $replyInfo['pp_thumb_height'],
                            'pp_setting_moderate_comments' => $replyInfo['pp_setting_moderate_comments'],
                            'pp_setting_moderate_friends' => $replyInfo['pp_setting_moderate_friends'],
                            'pp_setting_count_friends' => '1',
                            'pp_setting_count_comments' => '1',
                            'pp_setting_count_visitors' => '1',
                            'pp_about_me' => '',
                            'pp_reputation_points' => '1',
                            'pp_gravatar' => '',
                            'pp_photo_type' => '',
                            'signature' => '',
                            'avatar_location' => '',
                            'avatar_size' => '0',
                            'avatar_type' => '',
                            'pconversation_filters' => '',
                            'fb_photo' => '',
                            'fb_photo_thumb' => '',
                            'fb_bwoptions' => '0',
                            'tc_last_sid_import' => '0',
                            'tc_photo' => '',
                            'tc_bwoptions' => '0',
                            'pp_customization' => '',
                            'pp_profile_update' => '0',
                            'g_id' => '4',
                            'g_view_board' => '1',
                            'g_mem_info' => '1',
                            'g_other_topics' => '1',
                            'g_use_search' => '1',
                            'g_edit_profile' => '1',
                            'g_post_new_topics' => '1',
                            'g_reply_own_topics' => '1',
                            'g_reply_other_topics' => '1',
                            'g_edit_posts' => '1',
                            'g_delete_own_posts' => '1',
                            'g_open_close_posts' => '1',
                            'g_delete_own_topics' => '1',
                            'g_post_polls' => '1',
                            'g_vote_polls' => '1',
                            'g_use_pm' => '1',
                            'g_is_supmod' => '1',
                            'g_access_cp' => '1',
                            'g_title' => 'Administrators',
                            'g_append_edit' => '1',
                            'g_access_offline' => '1',
                            'g_avoid_q' => '1',
                            'g_avoid_flood' => '1',
                            'g_icon' => 'public/style_extra/team_icons/admin.png',
                            'g_attach_max' => '0',
                            'g_max_messages' => '50',
                            'g_max_mass_pm' => '6',
                            'g_search_flood' => '20',
                            'g_edit_cutoff' => '5',
                            'g_promotion' => '-1&-1',
                            'g_hide_from_list' => '0',
                            'g_post_closed' => '1',
                            'g_perm_id' => '4',
                            'g_photo_max_vars' => '500:170:240',
                            'g_dohtml' => '1',
                            'g_edit_topic' => '1',
                            'g_bypass_badwords' => '1',
                            'g_can_msg_attach' => '1',
                            'g_attach_per_post' => '0',
                            'g_topic_rate_setting' => '2',
                            'g_dname_changes' => '3',
                            'g_dname_date' => '30',
                            'g_mod_preview' => '0',
                            'g_rep_max_positive' => '100',
                            'g_rep_max_negative' => '100',
                            'g_signature_limits' => '0:::::',
                            'g_can_add_friends' => '1',
                            'g_hide_online_list' => '0',
                            'g_bitoptions' => '27262912',
                            'g_pm_perday' => '0',
                            'g_mod_post_unit' => '0',
                            'g_ppd_limit' => '0',
                            'g_ppd_unit' => '0',
                            'g_displayname_unit' => '0',
                            'g_sig_unit' => '0',
                            'g_pm_flood_mins' => '0',
                            'g_max_notifications' => '0',
                            'g_max_bgimg_upload' => '0',
                            'g_blog_attach_max' => '0',
                            'g_blog_attach_per_entry' => '0',
                            'g_blog_do_html' => '0',
                            'g_blog_do_commenthtml' => '0',
                            'g_blog_allowpoll' => '1',
                            'g_blog_allowprivate' => '1',
                            'g_blog_allowprivclub' => '1',
                            'g_blog_alloweditors' => '1',
                            'g_blog_allowskinchoose' => '1',
                            'g_blog_preventpublish' => '0',
                            'g_max_diskspace' => '0',
                            'g_max_upload' => '0',
                            'g_max_transfer' => '0',
                            'g_max_views' => '0',
                            'g_create_albums' => '0',
                            'g_album_limit' => '0',
                            'g_img_album_limit' => '0',
                            'g_comment' => '0',
                            'g_edit_own' => '0',
                            'g_del_own' => '0',
                            'g_mod_albums' => '0',
                            'g_img_local' => '0',
                            'g_movies' => '0',
                            'g_movie_size' => '0',
                            'g_gallery_use' => '1',
                            'g_album_private' => '1',
                            'cache_content' => '',
                            'full' => '1',
                            'photoMaxKb' => '500',
                            'photoMaxWidth' => '170',
                            'photoMaxHeight' => '240',
                            'gbw_mod_post_unit_type' => '0',
                            'gbw_ppd_unit_type' => '0',
                            'gbw_displayname_unit_type' => '0',
                            'gbw_sig_unit_type' => '0',
                            'gbw_promote_unit_type' => '0',
                            'gbw_no_status_update' => '0',
                            'gbw_soft_delete' => '1',
                            'gbw_soft_delete_own' => '1',
                            'gbw_soft_delete_own_topic' => '1',
                            'gbw_un_soft_delete' => '1',
                            'gbw_soft_delete_see' => '1',
                            'gbw_soft_delete_topic' => '1',
                            'gbw_un_soft_delete_topic' => '1',
                            'gbw_soft_delete_topic_see' => '1',
                            'gbw_soft_delete_reason' => '1',
                            'gbw_soft_delete_see_post' => '1',
                            'gbw_allow_customization' => '1',
                            'gbw_allow_url_bgimage' => '1',
                            'gbw_allow_upload_bgimage' => '1',
                            'gbw_view_reps' => '1',
                            'gbw_no_status_import' => '1',
                            'gbw_disable_tagging' => '0',
                            'gbw_disable_prefixes' => '0',
                            'gbw_view_last_info' => '1',
                            'gbw_view_online_lists' => '1',
                            'gbw_hide_leaders_page' => '0',
                            '_canBeIgnored' => '',
                            'bw_is_spammer' => '0',
                            'bw_from_sfs' => '0',
                            'bw_vnc_type' => '0',
                            'bw_forum_result_type' => '0',
                            'bw_no_status_update' => '0',
                            'bw_status_email_mine' => '0',
                            'bw_status_email_all' => '0',
                            'bw_disable_customization' => '0',
                            'bw_local_password_set' => '0',
                            'bw_disable_tagging' => '0',
                            'bw_disable_prefixes' => '0',
                            'bw_using_skin_gen' => '0',
                            'bw_disable_gravatar' => '0',
                            'member_rank_img' => 'http://localhost/nsocial/developer/public/style_extra/team_icons/admin.png',
                            'member_rank_img_i' => 'img',
                            'show_warn' => '',
                            'custom_fields' => '',
                            '_has_photo' => '0',
                            'pp_small_photo' => $replyInfo['pp_small_photo'],
                            'pp_small_width' => $replyInfo['pp_small_width'],
                            'pp_small_height' => $replyInfo['pp_small_height'],
                            'pp_mini_photo' => $replyInfo['pp_mini_photo'],
                            'pp_mini_width' => $replyInfo['pp_mini_width'],
                            'pp_mini_height' => $replyInfo['pp_mini_height'],
                            '_online' => '1',
                            '_last_active' => date('D, H:i ', strtotime($reply['log_date'])),
                            '_pp_rating_real' => '0',
                            'members_display_name_short' => 'juliobarreraa',
                            'member_title' => 'Administrator',
                            '_done' => '1',
                            'like' => $like
                );
            }
        }

        $row['status_replies'] = $countReply['count'];
        $row['image'] = $isPhoto['image'];
        $row['caption'] = $isPhoto['caption'];
        if (isset($row['replies'])) {
            $row['replies'] = array_reverse($row['replies']);
        }
        

        return $row;
    }

}

?>
