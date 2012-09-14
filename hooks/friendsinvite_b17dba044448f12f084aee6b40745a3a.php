<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 09-may-2012 -006  $
 * </pre>
 * @filename            friendsinvite.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		PRI
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		09-may-2012
 * @timestamp           19:06:06
 * @version		$Rev:  $
 *
 */

/**
 * Description of friendsinvite
 *
 * @author juliobarreraa@gmail.com
 */
class friendsinvite {
    
    //Public
    public $lang;
    protected $DB;
    protected $memberData;
    
    public function __construct() {
        $this->registry = ipsRegistry::instance();
        $this->DB = $this->registry->DB();
        $this->memberData = & $this->registry->member()->fetchMemberData(); //Get member info
    }
    
    public function getOutput(){
        $this->lang = $this->registry->getClass('class_localization'); //Load language
        return $this->registry->output->getTemplate('portal')->hookinvitefriend($this->totalInvite());
    }
    
    private function totalInvite() {
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
        return (int) $row['field_' . $pf_id];

    }
}

?>
