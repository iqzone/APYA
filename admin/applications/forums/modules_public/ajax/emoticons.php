<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Ourputs emoticon list via AJAX (AJAX)
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_ajax_emoticons extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* INIT */
 		$smilie_id        = 0;
 		$editor_id        = IPSText::alphanumericalClean( $this->request['editor_id'] );

		/* Query the emoticons */
 		$this->DB->build( array( 'select' => 'typed, image', 'from' => 'emoticons', 'where' => "emo_set='".$this->registry->output->skin['set_emo_dir']."'" ) );
		$this->DB->execute();
		
		/* Loop through and build output array */
		$rows = array();
		
		if( $this->DB->getTotalRows() )
		{
			while( $r = $this->DB->fetch() )
			{
				$smilie_id++;
				
				if( strstr( $r['typed'], "&quot;" ) )
				{
					$in_delim  = "'";
					$out_delim = '"';
				}
				else
				{
					$in_delim  = '"';
					$out_delim = "'";
				}
				
				$rows[] = array(
								'code'       => stripslashes( $r['typed'] ),
								'image'      => stripslashes( $r['image'] ),
								'in'         => $in_delim,
								'out'        => $out_delim,
								'smilie_id'	 =>	$smilie_id							
							);					
			}
		}
		
		/* Output */
		$this->returnHtml( $this->registry->getClass('output')->getTemplate('legends')->emoticonPopUpList( $editor_id, $rows ) );
	}
}