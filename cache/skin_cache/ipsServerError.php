<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Database driver error template
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		3.0
 * @version		$Revision: 10721 $
 *
 */
 
class ipsDriverErrorTemplate
{
	/**
	* Constructor
	*
	* @access	public
	* @return	@e void
	*/	
	public function __construct()
	{
	}
	
	/**
	* Show the database error
	*
	* @access	public
	* @param	boolean		Show error in template or not
	* @param	string		Error message (only shown/needed if $showError is true)
	* @return	@e void
	*/	
	public function showError( $showError=false, $errorMessage='' )
	{
		$errorBlock	= '';
		$name       = $_SERVER['HTTP_HOST'];
		
		$errorMessage = nl2br( trim( $errorMessage ) );

		return <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1" />
		<meta http-equiv="Pragma" content="no-cache" />
		<meta http-equiv="Cache-Control" content="no-cache" />
		<meta http-equiv="Expires" content="Fri, 01 January 1999 01:00:00 GMT" />
		<title>{$name} Driver Server Level Error</title>
		<style type='text/css'>
			body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,form,fieldset,input,textarea,p,blockquote,th,td { margin:0; padding:0; } 
			table {	border-collapse:collapse; border-spacing:0; }
			fieldset,img { border:0; }
			address,caption,cite,code,dfn,em,strong,th,var { font-style:normal; font-weight:normal; }
			ol,ul { list-style:none; }
			caption,th { text-align:left; }
			h1,h2,h3,h4,h5,h6 { font-size:100%;	font-weight:normal; }
			q:before,q:after { content:''; }
			abbr,acronym { border:0; }
			hr { display: none; }
			address{ display: inline; }
			body {
				font-family: arial, tahoma, sans-serif;
				font-size: 0.8em;
				width: 100%;
			}
			
			h1 {
				font-family: arial, tahoma, "times new roman", serif;
				font-size: 1.9em;
				color: #fff;
			}
			h2 {
				font-size: 1.6em;
				font-weight: normal;
				margin: 0 0 8px 0;
				clear: both;
			}
			a {
				color: #3e70a8;
			}
			
				a:hover {
					color: #3d8ce4;
				}
				
				a.cancel {
					color: #ad2930;
				}
			#branding {
				background: #484848;
				padding: 8px;
			}
			
			#content {
				clear: both;
				overflow: hidden;
				padding: 20px 15px 0px 15px;
			}
			
			* #content {
				height: 1%;
			}
			
			.message {
				border-width: 1px;
				border-style: solid;
				border-color: #d7d7d7;
				background-color: #f5f5f5;
				padding: 7px 30px 7px 30px;
				margin: 0 0 10px 0;
				clear: both;
			}
			
				.message.error {
					background-color: #f5bbbb;
					border-color: #deb7b7;
					color: #281b1b;
					font-size: 1.3em;
					font-weight: bold;
				}
				
				.message.error p.desc {
					font-size: 0.8em;
					font-weight: normal;
				}
				
				.message.unspecific {
					background-color: #f3f3f3;
					border-color: #d4d4d4;
					color: #515151;
					font-size: 14px;
					font-weight: normal;
					line-height: 150%;
				}
			.footer {
				text-align: center;
				font-size: 1.5em;
			}
		</style>
	</head>
	<body id='ipboard_body'>
		<div id='header'>
			<div id='branding'>
				<h1>SQL Error</h1>
			</div>
		</div>
		<div id='content'>
			<div class='message error'>
				An error occured with the SQL server:
				<p class='message unspecific'>$errorMessage</p>
				<p class='desc'>
					This is not a problem with IP.Board but rather with your SQL server. Please contact your host and copy the message shown above.
				</p>
			</div>
			
			<p class='message unspecific footer'>
				&laquo;<a href='/index.php' title='Go to home page'>Return to the index</a>
			</p>
		</div>
	</body>
</html>
EOF;
	}
}