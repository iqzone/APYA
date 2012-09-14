<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 09-may-2012 -006  $
 * </pre>
 * @filename            timeline.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		PRI
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		09-may-2012
 * @timestamp           15:52:10
 * @version		$Rev:  $
 *
 */

/**
 * Description of timeline
 *
 * @author juliobarreraa@gmail.com
 */
class messages {
    //Protected
    protected $registry;
    protected $memberData;
    protected $DB;
    //Public
    public $lang;
    private $totals;
    
    public function __construct() {
        $this->registry = ipsRegistry::instance();
        $this->settings = & $this->registry->fetchSettings(); //Get settings timeline_max_status
        $this->memberData = & $this->registry->member()->fetchMemberData(); //This member data 
        $this->lang = $this->registry->getClass('class_localization'); //Load language
        $this->DB = $this->registry->DB();
        //Obsolete
        $this->member		=  $this->registry->member();
        //-----------------------------------------
        // Grab class
        //-----------------------------------------

        $classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php', 'messengerFunctions', 'members' );
        $this->messengerFunctions = new $classToLoad( $this->registry );
        /* Messenger Totals */
        $this->_totals = $this->messengerFunctions->buildMessageTotals();
    }
    
    public function getOutput() {
        $this->lang->loadLanguageFile(array('public_profile', 'public_portal'), 'members');
        return $this->registry->output->getTemplate('portal')->hookLeftMessages($this->messengerFunctions->_jumpMenu, $this->messengerFunctions->_dirData, $this->_totals);
    }    
}

?>
