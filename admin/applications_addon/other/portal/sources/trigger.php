<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 28-mar-2012 -006  $
 * </pre>
 * @filename            Trigger.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		PRI
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		28-mar-2012
 * @timestamp           15:36:45
 * @version		$Rev:  $
 *
 */

/**
 * Description of Trigger
 *
 * @author juliobarreraa@gmail.com
 */
class trigger {

    public $DB;
    public $registry;
    private $_name,
            $_table,
            $_conf_table_id,
            $_when,
            $_action,
            $_booleanValue,
            $_remove,
            $_text_name;

    public function __construct(ipsRegistry $registry) {
        $this->DB = $registry->DB();
        $this->registry = $registry;
    }

    public function __init($name, $table, $params, $when = 'AFTER', $action = 'INSERT', $booleanValue = true, $remove = true) {
        //Inicializamos los valores
        $this->_name = trim($name);
        $this->_table = trim($table);
        $this->_conf_table_id = trim($conf_table_id);
        $this->_when = trim($when);
        $this->_action = trim($action);
        $this->_booleanValue = $booleanValue;
        $this->_remove = $remove;

        $this->_primaryKey = trim($params['primaryKey']);
        $this->_userId = trim($params['userId']);
        $this->_createdAt = trim($params['createdAt']);
        $this->_text_name = trim($params['textName']);
        return $this->checkFields();
    }

    /*
      $name = (string) nombre del trigger
      $when = (string) momento de ejecucion (AFTER, BEFORE)
      $action = (string) accion (INSERT, UPDATE, SELECT)
      $table  = (string) sobre la que actúa el trigger
     */

    public function createTrigger($confTableId) {
        $this->_conf_table_id = $confTableId;
        if ($this->_remove)
            $this->clearTrigger();

        $newOldPrimaryKeyName = ($this->_booleanValue ? 'NEW' : 'OLD') . '.' . $this->_primaryKey;
        $newOldUserId = ($this->_booleanValue ? 'NEW' : 'OLD') . '.' . $this->_userId;
        $newOldCreatedAt = ($this->_booleanValue ? 'NEW' : 'OLD') . '.' . $this->_createdAt;
        //$newOldTextName = ($this->_booleanValue ? 'NEW' : 'OLD') . '.' . $this->_text_name;
        $sql = <<<EOF
        
            CREATE TRIGGER sFGdy9Gc_{$this->_name} $this->_when $this->_action ON {$this->DB->obj['sql_tbl_prefix']}$this->_table FOR EACH ROW BEGIN
                            INSERT INTO {$this->DB->obj['sql_tbl_prefix']}portal_logbook 
                            SET 
                                conf_table_id = $this->_conf_table_id,
                                action_id = $newOldPrimaryKeyName,
                                user_id = $newOldUserId,      
                                created_at = $newOldCreatedAt;
                    END
        
EOF;
        ipsRegistry::DB()->allow_sub_select = 1;
        //aqui la parte para crear el trigger en la BD
        $this->DB->query($sql);
        return true;
    }

    private function clearTrigger() {
        //Borramos si existe un trigger creado con anterioridad, para evitar errores futuros.
        $sql = <<<EOF
   DROP TRIGGER IF EXISTS sFGdy9Gc_{$this->_name};
EOF;
        $this->DB->query($sql);
    }

    public function checkFields() {
        $_fields = array(
                    $this->_primaryKey => $this->_primaryKey,
                    $this->_userId => $this->_userId,
                    $this->_createdAt => $this->_createdAt,
                    //$this->_text_name => $this->_text_name,
        );
        $this->DB->query("SHOW COLUMNS FROM {$this->registry->dbFunctions()->getPrefix()}$this->_table WHERE Field = '{$this->_primaryKey}' OR Field = '{$this->_userId}' OR Field = '{$this->_createdAt}'");//'/* OR Field  = '{$this->_text_name}'");
        $this->DB->execute();
        if ($this->DB->getTotalRows() != count($_fields)) {
            return false;
        }
        return true;
    }

}

?>
