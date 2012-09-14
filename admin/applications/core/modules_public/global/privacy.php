<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Show forum rules
 * Last Updated: $Date: 2011-05-05 12:03:47 +0100 (Thu, 05 May 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage  Forums 
 * @link		http://www.invisionpower.com
 * @version		$Rev: 8644 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_global_privacy extends ipsCommand
{

	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$raw = $this->DB->buildAndFetch( array( 'select' => '*',
												'from'   => 'core_sys_conf_settings',
												'where'  => 'conf_key=\'priv_body\'' ) );

        if ( $this->settings['priv_title'] )
		{
			IPSText::getTextClass( 'bbcode' )->parse_smilies	= 1;
			IPSText::getTextClass( 'bbcode' )->parse_html		= 1;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br		= 1;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section	= 'rules';
			
			$policy	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( ( $raw['conf_value'] ) ? $raw['conf_value'] : $raw['conf_default'] );

			$this->output .= $this->registry->getClass('output')->getTemplate('global_other')->privacyPolicy( $this->settings['priv_title'], $policy );

			$this->registry->output->setTitle( $this->settings['priv_title'] . ' - ' . ipsRegistry::$settings['board_name'] );
			$this->registry->output->addContent( $this->output );
			$this->registry->output->sendOutput();
		}
		else
		{
			$this->registry->getClass('output')->showError( 'page_doesnt_exist', 10335, null, null, 404 );
		}
	}
}