<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 09-may-2012 -006  $
 * </pre>
 * @filename            load.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		PRI
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		09-may-2012
 * @timestamp           16:29:01
 * @version		$Rev:  $
 *
 */

/**
 * Description of load
 *
 * @author juliobarreraa@gmail.com
 */
class public_portal_ajax_load extends ipsAjaxCommand {

    public function doExecute(ipsRegistry $registry) {
        //Load language
        $this->lang->loadLanguageFile(array('public_portal'), 'portal');

        //Load library status load
        $classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('portal') . '/sources/timeline.php', 'timelineClass', 'portal');

        $st = intval($this->request['st']);

        $this->timeline = new $classToLoad($st);
        $pages = $this->timeline->getPager(true); //Get pagination
        $isuser = false;
        if((int)$_GET['showuser'] || (int)$_GET['user']) {
	        $isuser = true;
        }
        $rows = $this->timeline->getStatus($isuser); //Get Status ajax
        

        $status = base64_encode(utf8_decode($this->registry->getClass('output')->getTemplate('portal')->statusAjax($rows)));
        return $this->returnJsonArray(array('status' => 'success', 'html' => $this->cleanOutput($status), 'pages' => $pages));
    }

}

?>
