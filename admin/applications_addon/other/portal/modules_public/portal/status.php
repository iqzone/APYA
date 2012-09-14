<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 09-abr-2012 -006  $
 * </pre>
 * @filename            single.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		PRI
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		09-abr-2012
 * @timestamp           20:10:41
 * @version		$Rev:  $
 *
 */

if (!defined('IN_IPB')) {
    print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
    exit();
}

/**
 * Description of single
 *
 * @author juliobarreraa@gmail.com
 */
class public_portal_portal_status extends ipsCommand {
    public function doExecute(ipsRegistry $registry) {
        
        $this->html = $this->registry->output->getTemplate('portal');
        
        $this->lang->loadLanguageFile(array('public_portal'), 'portal');
        
        //Load library status load
        $classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('portal') . '/sources/timeline.php', 'timelineClass', 'portal');
        
        $id = intval($this->request['id']);
        
        $this->timeline = new $classToLoad($id);
        
        $row = $this->timeline->status($id); //Get Status ajax
        if((int)$row['member_id'] == 0) {
	        $this->registry->output->redirectScreen( 'La publicación no existe', $this->settings['board_url'] );
        }
        $output = "";
        $output = $this->html->statusIndividual($row);
        
        $this->registry->output->addContent($output);
        $this->registry->output->setTitle('Portal');
        $this->registry->output->addNavigation('Portal', '');
        $this->registry->output->sendOutput();
    }
}

?>
