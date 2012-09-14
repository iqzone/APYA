<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 11-may-2012 -006  $
 * </pre>
 * @filename            status.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		PRI
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		11-may-2012
 * @timestamp           10:28:29
 * @version		$Rev:  $
 *
 */

/**
 * Description of status
 *
 * @author juliobarreraa@gmail.com
 */
class admin_portal_status_status extends ipsCommand {

    protected $registry;

    public function doExecute(ipsRegistry $registry) {
        $this->registry = $registry;
        $this->registry = $registry;
        //-----------------------------------------
        // Load skin
        //-----------------------------------------

        $this->html = $this->registry->output->loadTemplate('cp_skin_status');
        $this->lang->loadLanguageFile(array('admin_portal', 'portal'));


        //-----------------------------------------
        // Set up stuff
        //-----------------------------------------

        $this->form_code = $this->html->form_code = 'module=status&amp;section=status';

        //-----------------------------------------
        // StRT!
        //-----------------------------------------

        switch ($this->request['do']) {
            case 'addConf':
                $this->_insertIntoDB();
                break;
        }

        //-----------------------------------------
        // Load lang
        //-----------------------------------------

        ipsRegistry::getClass('class_localization')->loadLanguageFile(array('admin_member'), 'members');

        ///-----------------------------------------
        // What to do...
        //-----------------------------------------
        $this->_portalShow();

        //-----------------------------------------
        // Pass to CP output hander
        //-----------------------------------------

        $this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
        $this->registry->getClass('output')->sendOutput();
    }

    /**
     * View a member's details
     *
     * @return	@e void
     * @todo 	[Future] Settings: joined, dst_in_use, coppa_user, auto_track, ignored_users, members_auto_dst, 
     * 				 members_created_remote, members_profile_views, failed_logins, failed_login_count, fb_photo, fb_photo_thumb
     */
    protected function _portalShow() {

        //-----------------------------------------
        // FORM
        //-----------------------------------------

        $form['table'] = $this->registry->output->formSimpleInput('table', isset($_POST['table']) ? $_POST['table'] : '', 20);
        $form['action_id'] = $this->registry->output->formSimpleInput('action_id', isset($_POST['action_id']) ? $_POST['action_id '] : '', 20);
        $form['user_id'] = $this->registry->output->formSimpleInput('user_id', isset($_POST['user_id']) ? $_POST['user_id'] : '', 20);
        $form['created_at'] = $this->registry->output->formSimpleInput('created_at', isset($_POST['created_at']) ? $_POST['created_at'] : '', 20);
        $form['text_id'] = $this->registry->output->formSimpleInput('text_id', isset($_POST['text_id']) ? $_POST['text_id'] : '', 20);
        $form['valueBoolean'] = $this->registry->output->formYesNo('valueBoolean', isset($_POST['valueBoolean']) ? $_POST['valueBoolean'] : '1' );
        $classToLoad = IPSLib::loadLibrary(IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite');
        $editor = new $classToLoad();
        $form['text_id'] = $editor->show('text_id');

        $this->registry->output->html .= $this->html->statusCreateForm($form);
    }

    protected function _insertIntoDB() {
        $action_id = $this->request['action_id'];
        $user_id = $this->request['user_id'];
        $text_id = $this->request['text_id'];
        $created_at = $this->request['created_at'];
        $valueBoolean = $this->request['valueBoolean'];
        $table = $this->request['table'];
        $result = $this->DB->buildAndFetch(array(
            'select' => 'id',
            'from' => array('portal_tables_conf' => 'ptc'),
            'where' => "user_id_name='" . $user_id . "' AND 
                                                    table_name='" . $table . "' AND  
                                                    primary_key_name='" . $action_id . "' AND
                                                    text_name = '" . $text_id . "' AND
                                                    date_name='" . $created_at . "'"
                ));


        //Garantizar que existe la tabla y no fue dada de alta previamente
        if (empty($result['id']) && $this->DB->checkForTable($table)) {
            $classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('portal') . '/sources/trigger.php', 'trigger', 'portal');
            $table_name_trigger = $table . (1 ? '_after' : '_before');
            $params = array(
                'primaryKey' => $action_id,
                'userId' => $user_id,
                'createdAt' => $created_at,
                'textName' => $text_id);
            $trigger = new $classToLoad($this->registry);
            //Validamos que los campos que introdujo el usuario existan
            if (!$trigger->__init($table_name_trigger, $table, $params, 'AFTER', 'INSERT', $valueBoolean)) {
                $this->registry->output->showError(sprintf($this->lang->words['error_save'], $table));
            } else {
                //Insertamos a la tabla ya que hemos validado
                $this->DB->insert('portal_tables_conf', array('user_id_name' => $user_id,
                    'table_name' => $table,
                    'primary_key_name' => $action_id,
                    'date_name' => $created_at,
                    'text_name' => $text_id,
                ));

                //Si se inserto correctamente, entonces creamos el Trigger, borramos antes si existe uno similar
                $trigger->createTrigger($this->DB->getInsertId());
                $this->registry->output->global_message = $this->lang->words['success'];
            }
        } else {
            $this->registry->output->showError(sprintf($this->lang->words['overwrite'], $table));
        }
    }

}

?>
