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
class timeline {
    //Private
    private $timeline;
    //Protected
    protected $registry;
    protected $memberData;
    protected $DB;
    //Public
    public $lang;
    
    public function __construct() {
        $this->registry = ipsRegistry::instance();
        $this->settings = & $this->registry->fetchSettings(); //Get settings timeline_max_status
        $this->memberData = & $this->registry->member()->fetchMemberData(); //This member data 
        $this->lang = $this->registry->getClass('class_localization'); //Load language
        $this->DB = $this->registry->DB();
        //Obsolete
        $this->member		=  $this->registry->member();
		/* AJAX Class */
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$this->ajax  = new $classToLoad();
    }
    
    public function getOutput() {
        $this->lang->loadLanguageFile(array('public_profile', 'public_portal'), 'members');
        $classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'portal' ) . '/sources/timeline.php', 'timelineClass', 'portal' );
        $this->timeline = new $classToLoad(0);
        $pages = $this->timeline->getPager();
        $isuser= false;
        if((int)$_GET['showuser']) {
            $isuser = true;
        }
        
        $rows = $this->timeline->getStatus($isuser);
        return $this->registry->output->getTemplate('portal')->globalTimeline($rows, $pages);
    }    
}

?>
