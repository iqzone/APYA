<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 11-abr-2012 -006  $
 * </pre>
 * @filename            install_registerinvitation.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		PRI
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		11-abr-2012
 * @timestamp           18:16:30
 * @version		$Rev:  $
 *
 */

/**
 * Description of install_registerinvitation
 *
 * @author juliobarreraa@gmail.com
 */
class registerinvitation {

    const TBL_FIELDS = 'field_';

    public $registry;
    public $DB;

    public function __construct() {
        $this->registry = ipsRegistry::instance();
        $this->DB = $this->registry->DB();
    }

    public function install() {
        $row = $this->DB->buildAndFetch(array(
            'select' => 'pf_group_id',
            'from' => 'pfields_groups',
            'where' => "pf_group_key = 'register_invitation_unique'",
                ));

        //No existe el campo, vamos a crearlo.
        $pf_group_id = 0;
        if (!$row) {
            $pf_group_id = $this->insertGroupKey();
        } else {
            $pf_group_id = (int) $row['id'];
        }

        unset($row); //bye :)
        if ($pf_group_id) {
            $field_name = self::TBL_FIELDS . $pf_group_id;

            //Insertamos la configuración del campo personalizado, si es que el campo no existe
            $fieldNumberName = self::TBL_FIELDS . $this->insertData($pf_group_id);
            if (!$this->checkField($fieldNumberName)) //Se crea o no se crea la modificaciÃ³n a la tabla
                $this->alterTable($fieldNumberName);
        }
    }

    private function checkField($field) {
        //Si existe el campo $field de $pf_group_id entonces se devuelve true en otro caso false,
        $this->DB->query("SHOW COLUMNS FROM {$this->registry->dbFunctions()->getPrefix()}pfields_content WHERE Field = '$field'");
        $this->DB->execute();
        return ($this->DB->getTotalRows() ? true : false);
    }

    private function alterTable($field) {
        $this->DB->query("ALTER TABLE {$this->registry->dbFunctions()->getPrefix()}pfields_content ADD $field TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL");
        $this->DB->execute();
    }

    private function insertGroupKey() {
        $this->DB->insert('pfields_groups', array('pf_group_name' => 'Register', 'pf_group_key' => 'register_invitation_unique'));
        return $this->DB->getInsertId();
    }

    private function insertData($pf_group_id) {
        $row = $this->DB->buildAndFetch(array(
            'select' => 'pf_id',
            'from' => 'pfields_data',
            'where' => "pf_key = 'number_invitations'",
                ));
        if (!$row) {
            $this->DB->insert('pfields_data', array(
                'pf_title' => 'Invitaciones disponibles',
                'pf_desc' => 'Invitaciones disponibles para un usuario.',
                'pf_content' => '',
                'pf_type' => 'input',
                'pf_not_null' => 0,
                'pf_member_hide' => 1,
                'pf_max_input' => 4,
                'pf_member_edit' => 0,
                'pf_position' => 0,
                'pf_show_on_reg' => 0,
                'pf_input_format' => 'n',
                'pf_admin_only' => 1,
                'pf_topic_format' => '',
                'pf_group_id' => $pf_group_id,
                'pf_icon' => '',
                'pf_key' => 'number_invitations',
                'pf_search_type' => 'exact',
                'pf_filtering' => 0));
             return $this->DB->getInsertId();
        }
    }

    //Sistema de desinstalación, una vez borrado el hook no sucede nada
    public function uninstall() {
        
    }

}

?>