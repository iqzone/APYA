<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 02-may-2012 -006  $
 * </pre>
 * @filename            ajaxmember.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		PRI
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		02-may-2012
 * @timestamp           17:42:07
 * @version		$Rev:  $
 *
 */
if (!defined('IN_IPB')) {
    print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
    exit();
}

/**
 * Description of ajaxmember
 *
 * @author juliobarreraa@gmail.com
 */
class public_core_ajax_ajaxmember extends ipsAjaxCommand {

    public function doExecute(ipsRegistry $registry) {
        $this->registry->class_localization->loadLanguageFile(array('public_register'), 'core');
        
        $invite = $this->subInvitation();
        if($invite['customfield']<=0)
        {
            $form_errors['email'][] = $this->lang->words['reg_no_more_invitations'];
        }

        $banfilters = array();

        //-----------------------------------------
        // Load log in handler...
        //-----------------------------------------

        $classToLoad = IPSLib::loadLibrary(IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login');
        $han_login = new $classToLoad($this->registry);
        $han_login->init();

        //Verificamos si existe el correo, si existe entonces informamos lo ocurrido
        $in_email = strtolower(trim($this->request['EmailAddress']));

        //-----------------------------------------
        // Check the email address
        //-----------------------------------------

        $form_errors = array();

        if (!IPSText::checkEmailAddress($in_email)) {
            $form_errors['email'][] = $this->lang->words['reg_error_email_nm'];
        }

        //-----------------------------------------
        // Load ban filters
        //-----------------------------------------

        $this->DB->build(array('select' => '*', 'from' => 'banfilters'));
        $this->DB->execute();

        while ($r = $this->DB->fetch()) {
            $banfilters[$r['ban_type']][] = $r['ban_content'];
        }

        //-----------------------------------------
        // Are they banned [EMAIL]?
        //-----------------------------------------

        if (is_array($banfilters['email']) and count($banfilters['email'])) {
            foreach ($banfilters['email'] as $email) {
                $email = str_replace('\*', '.*', preg_quote($email, "/"));

                if (preg_match("/^{$email}$/i", $in_email)) {
                    $form_errors['email'][] = $this->lang->words['reg_error_email_taken'];
                    break;
                }
            }
        }

        /* Is this email addy taken? */
        if (IPSMember::checkByEmail($in_email) == TRUE) {
            $form_errors['email'][] = $this->lang->words['reg_error_email_taken'];
        }

        if ($han_login->emailExistsCheck(trim(strtolower($in_email)))) {
            $form_errors['email'][] = $this->lang->words['reg_error_email_taken'];
        }

        //-----------------------------------------
        // CHECK 1: Any errors (duplicate names, etc)?
        //-----------------------------------------

        $errorMessages = array();
        if (count($form_errors)) {


            foreach ($form_errors as $errorCat => $errorMessage) {
                foreach ($errorMessage as $error) {
                    $errorMessages['general'][] = $error;
                }
            }

            if (count($errorMessages)) {
                return $this->returnJsonArray(array(
                            'errorMessages' => $errorMessages,
                                )
                );
            }
        }

        /* Build up the hashes */
        $mem_group = $this->settings['member_group'];

        /* Are we asking the member or admin to preview? */
        if ($this->settings['reg_auth_type']) {
            $mem_group = $this->settings['auth_group'];
        }

        $in_password = IPSMember::makePassword();
        /* Create member */
        $member = array(
            'name' => '',
            'password' => $in_password,
            'members_display_name' => '',
            'email' => $in_email,
            'member_group_id' => $mem_group,
            'joined' => time(),
            'time_offset' => $this->request['time_offset'],
            'coppa_user' => 0,
            'members_auto_dst' => intval($this->settings['time_dst_auto_detection']),
            'allow_admin_mails' => intval($this->request['allow_admin_mail']),
        );



        //-----------------------------------------
        // Create the account
        //-----------------------------------------

        $member = IPSMember::create(array('members' => $member, 'pfields_content' => $custom_fields->out_fields), FALSE, FALSE, FALSE);

        //-----------------------------------------
        // Login handler create account callback
        //-----------------------------------------

        /* $han_login->createAccount(array('email' => $member['email'],
          'joined' => $member['joined'],
          'password' => $in_password,
          'ip_address' => $this->member->ip_address,
          'username' => '',
          )); */


        $validate_key = md5(IPSMember::makePassword() . time());
        $time = time();

        $this->DB->insert('validating', array(
            'vid' => $validate_key,
            'member_id' => $member['member_id'],
            'real_group' => $this->settings['member_group'],
            'temp_group' => $this->settings['auth_group'],
            'entry_date' => $time,
            'coppa_user' => 0,
            'new_reg' => 1,
            'ip_address' => $member['ip_address'],
        ));


        //Submit mail
        if ($this->settings['reg_auth_type'] == 'user' OR $this->settings['reg_auth_type'] == 'admin_user') {
            IPSText::getTextClass('email')->getTemplate("reg_validate");

            IPSText::getTextClass('email')->buildMessage(array(
                'THE_LINK' => $this->settings['base_url'] . "app=core&module=global&section=register&do=complete_login&mid=". urlencode($member['member_id']) . "&key={$member['joined']}",//"app=core&module=global&section=register&do=auto_validate&uid=" . urlencode($member['member_id']) . "&aid=" . urlencode($validate_key),
                'NAME' => $member['members_display_name'],
                'MAN_LINK' => $this->settings['base_url'] . "app=core&module=global&section=register&do=05",
                'EMAIL' => $member['email'],
                'ID' => $member['member_id'],
                'CODE' => $in_password,
            ));

            IPSText::getTextClass('email')->subject = sprintf('Registro en Alvarez Puga');
            IPSText::getTextClass('email')->to = $member['email'];

            IPSText::getTextClass('email')->sendMail();

            $this->output = $this->registry->output->getTemplate('register')->showAuthorize($member);
        } else if ($this->settings['reg_auth_type'] == 'admin') {
            $this->output = $this->registry->output->getTemplate('register')->showPreview($member);
        }

        /* Only send new registration email if the member wasn't banned */
        if ($this->settings['new_reg_notify'] AND !$member['member_banned']) {
            $date = $this->registry->class_localization->getDate(time(), 'LONG', 1);

            IPSText::getTextClass('email')->getTemplate('admin_newuser');

            IPSText::getTextClass('email')->buildMessage(array('DATE' => $date,
                'LOG_IN_NAME' => $member['name'],
                'EMAIL' => $member['email'],
                'IP' => $member['ip_address'],
                'DISPLAY_NAME' => $member['members_display_name']));

            IPSText::getTextClass('email')->subject = sprintf($this->lang->words['new_registration_email1'], $this->settings['board_name']);
            IPSText::getTextClass('email')->to = $this->settings['email_in'];
            IPSText::getTextClass('email')->sendMail();
        }
        
        
        $this->subapply($invite);
        
        //No hubo errores
        return $this->returnJsonArray(array(
                    'errorMessages' => null,
                    'invitations' => $invite['customfield'],
                )
        );
    }
    
    private function subInvitation() {
        $row = $this->DB->buildAndFetch(array(
            'select' => 'pg.pf_group_id',
            'from' => array('pfields_groups' => 'pg'),
            'where' => "pg.pf_group_key = 'register_invitation_unique' AND pg.pf_group_name = 'Registro'",
            'add_join' => array(array(
                    'select' => 'pd.pf_id',
                    'from' => array('pfields_data' => 'pd'),
                    'where' => "pg.pf_group_id = pd.pf_group_id AND pd.pf_key = 'number_invitations'",
                    'type' => 'inner',
                ),
            )
                ));
        $pf_id = (int) $row['pf_id'];
        unset($row); //bye
        $row = $this->DB->buildAndFetch(array(
            'select' => 'field_' . $pf_id,
            'from' => array('pfields_content' => 'pc'),
            'where' => "pc.member_id = {$this->memberData['member_id']}",
                ));
        $customfield = (int) $row['field_' . $pf_id];
        unset($row); //bye :/
        if (!$customfield) {
            $customfield = (int) $this->settings['defaultnumberinvitations'];
        }

        return array(
            'customfield' => --$customfield,
            'field' => $pf_id,
        );
    }

    private function subapply($invite) {
        $this->DB->update('pfields_content', array('field_' . $invite['field'] => $invite['customfield']), 'member_id = ' . $this->memberData['member_id']);
    }

}

?>
