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
class admin_portal_invitations_index extends ipsCommand {

	protected $registry;

	public function doExecute(ipsRegistry $registry) {

		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------

		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

}

?>
