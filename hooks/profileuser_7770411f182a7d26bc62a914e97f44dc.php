<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 22-jun-2012 -006  $
 * </pre>
 * @filename            profileuser.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		design
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		22-jun-2012
 * @timestamp           14:21:48
 * @version		$Rev:  $
 *
 */
class profileuser {
    //Public
    public $lang;
    
    public function __construct() {
        $this->registry = ipsRegistry::instance();
    }
    
    public function getOutput(){
        $this->lang = $this->registry->getClass('class_localization'); //Load language
        return $this->registry->output->getTemplate('portal')->hookprofileuser();
    }    
}
?>
