<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Compare and highlight differences between two strings or files
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Kernel
 * @link		http://www.invisionpower.com
 * @since		Thursday 10th February 2005 (10:47)
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IPS_CLASSES_PATH' ) )
{
	/**
	* Define classes path
	*/
	define( 'IPS_CLASSES_PATH', dirname(__FILE__) );
}

class classDifference
{
	/**
	 * Shell command
	 *
	 * @var 		string
	 */
	public $diff_command = 'diff';
	
	/**
	 * Type of diff to use
	 *
	 * @var 		string	[EXEC, PHP]
	 */
	public $method       = 'EXEC';
	
	/**
	 * Differences found?
	 *
	 * @var 		integer
	 */
	public $diff_found   = 0;
	
	/**
	 * Post process DIFF result?
	 *
	 * @var 		integer
	 */
	public $post_process = 1;
	
	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		//-------------------------------
		// Server?
		//-------------------------------
		
		if( strpos( strtolower( PHP_OS ), 'win' ) === 0 OR ( ! function_exists('exec') ) )
		{
			$this->method = 'PHP';
		}
	}
	
	/**
	 * Retrieve a text string showing the differences between the two supplied strings
	 *
	 * @param	string		Original string
	 * @param	string		New string
	 * @param	string		Inline or unified report (only supported by PHP method)
	 * @return	@e string
	 */
	public function getDifferences( $str1, $str2, $method='inline' )
	{
		$this->diff_found = 0;
		
		if ( $this->method != 'PHP' )
		{
			$str1       = $this->_diffTagSpace($str1);
			$str2       = $this->_diffTagSpace($str2);
			$str1_lines = $this->_diffExplodeStringIntoWords($str1);
			$str2_lines = $this->_diffExplodeStringIntoWords($str2);
		}
		
		if ( $this->method == 'PHP' )
		{
			$diff_res   = $this->_getPhpDiff( $str1, $str2, $method );
		}
		else
		{
			$diff_res   = $this->_getExecDiff( implode( chr(10), $str1_lines ) . chr(10), implode( chr(10), $str2_lines ) . chr(10) );
		}
		
		//-------------------------------
		// Post process?
		//-------------------------------
		
		if ( $this->post_process )
		{
			if ( is_array($diff_res) )
			{
				reset($diff_res);
				$c              = 0;
				$diff_res_array = array();
				
				foreach( $diff_res as $l_val )
				{
					if ( intval($l_val) )
					{
						$c = intval($l_val);
						$diff_res_array[$c]['changeInfo'] = $l_val;
					}
					
					if (substr($l_val,0,1) == '<')
					{
						$diff_res_array[$c]['old'][] = substr($l_val,2);
					}
					
					if (substr($l_val,0,1) == '>')
					{
						$diff_res_array[$c]['new'][] = substr($l_val,2);
					}
				}
	
				$out_str    = '';
				$clr_buffer = '';
				
				for ( $a = -1; $a < count($str1_lines); $a++ )
				{
					if ( is_array( $diff_res_array[$a+1] ) )
					{
						if ( strstr( $diff_res_array[$a+1]['changeInfo'], 'a') )
						{
							$this->diff_found = 1;
							$clr_buffer .= htmlspecialchars($str1_lines[$a]).' ';
						}
	
						$out_str     .= $clr_buffer;
						$clr_buffer   = '';
						
						if (is_array($diff_res_array[$a+1]['old']))
						{
							$this->diff_found = 1;
							$out_str.='<del style="-ips-match:1">'.htmlspecialchars(implode(' ',$diff_res_array[$a+1]['old'])).'</del> ';
						}
						
						if (is_array($diff_res_array[$a+1]['new']))
						{
							$this->diff_found = 1;
							$out_str.='<ins style="-ips-match:1">'.htmlspecialchars(implode(' ',$diff_res_array[$a+1]['new'])).'</ins> ';
						}
						
						$cip = explode(',',$diff_res_array[$a+1]['changeInfo']);
						
						if ( ! strcmp( $cip[0], $a + 1 ) )
						{
							$new_line = intval($cip[1])-1;
							
							if ( $new_line > $a )
							{
								$a = $new_line;
							}
						}
					} 
					else
					{
						$clr_buffer .= htmlspecialchars($str1_lines[$a]).' ';
					}
				}
				
				$out_str .= $clr_buffer;
	
				$out_str  = str_replace('  ',chr(10),$out_str);
				
				$out_str  = $this->_diffTagSpace($out_str,1);
				
				return $out_str;
			}
		}
		else
		{
			return $diff_res;
		}
	}

	/**
	 * Adds space character after HTML tags
	 *
	 * @param	string		String
	 * @param	integer		[Optional][0=reverse, 1=normal]
	 * @return	@e string
	 */
	protected function _diffTagSpace( $str, $rev=0 )
	{
		if ( $rev )
		{
			return str_replace(' &lt;','&lt;',str_replace('&gt; ','&gt;',$str) );
		}
		else
		{
			return str_replace('<',' <',str_replace('>','> ',$str) );
		}
	}
	
	/**
	 * Explodes input string into words
	 *
	 * @access	protected
	 * @param	string		Input string
	 * @return	@e array
	 */
	protected function _diffExplodeStringIntoWords( $str )
	{
		$str_array = $this->_explodeTrim( chr(10), $str );
		$out_array = array();

		reset($str_array);
		
		foreach( $str_array as $low )
		{
			$all_words   = $this->_explodeTrim( ' ', $low, 1 );
			$out_array   = array_merge($out_array, $all_words);
			$out_array[] = '';
			$out_array[] = '';
		}
		
		return $out_array;
	}
	
	/**
	 * Explode into array and trim
	 *
	 * @param	string 		Delimiter
	 * @param	string		String to check
	 * @param	integer		[Optional] Remove blank lines
	 * @return	@e array
	 */
	protected function _explodeTrim( $delim, $str, $remove_blank=0 )
	{
		$tmp   = explode( $delim, trim($str) );
		$final = array();
	
		foreach( $tmp as $i )
		{
			if ( $remove_blank AND ( $i === '' OR $i === NULL ) ) //!$i AND $i !== 0 )
			{
				continue;
			}
			else
			{
				$final[] = trim($i);
			}
		}

		return $final;
	}
	
	/**
	 * Produce differences using PHP
	 *
	 * @param	string		comapre string 1
	 * @param	string		comapre string 2
	 * @return	@e string
	 */
    protected function _getPhpDiff( $str1 , $str2, $method='inline' )
    { 
    	$str1 = explode( "\n", str_replace( "\r\n", "\n", $str1 ) );
    	$str2 = explode( "\n", str_replace( "\r\n", "\n", $str2 ) );
    	
		/* Set include path.. */
		@set_include_path( IPS_KERNEL_PATH . 'PEAR/' );/*noLibHook*/
		
		/* OMG.. too many PHP 5 errors under strict standards */
		$oldReportLevel = error_reporting( 0 );
		error_reporting( $oldReportLevel ^ E_STRICT );
		
    	require_once 'Text/Diff.php';/*noLibHook*/
		require_once 'Text/Diff/Renderer.php';/*noLibHook*/
		
		$diff = new Text_Diff( 'auto', array( $str1, $str2 ) );
		
		if ( $method == 'inline' )
		{
			require_once 'Text/Diff/Renderer/inline.php';/*noLibHook*/
			$renderer = new Text_Diff_Renderer_inline();
		}
		else
		{
			require_once 'Text/Diff/Renderer/unified.php';/*noLibHook*/
			$renderer = new Text_Diff_Renderer_unified();
			$renderer->_leading_context_lines	= 10000;
			$renderer->_trailing_context_lines	= 10000;
		}
		
		$result = $renderer->render($diff);
		
		/* Go back to old reporting level */
		error_reporting( $oldReportLevel | E_STRICT );
		
		$result = str_replace( "<ins>", '<ins style="-ips-match:1">', $result );
		$result = str_replace( "<del>", '<del style="-ips-match:1">', $result );
		
		# Got a match?
		if ( strstr( $result, 'style="-ips-match:1"' ) )
		{
			$this->diff_found = 1;
		}
		
		# No post processing please
		$this->post_process = 0;
		
		# Convert lines to a space, and two spaces to a single line
		//$result = str_replace('  ', chr(10), str_replace( "\n", " ", $result ) );
		//$result = $this->_diffTagSpace($result,1);
		
		return $result;
    }

	/**
	 * Produce differences using unix diff
	 *
	 * @param	string		comapre string 1
	 * @param	string		comapre string 2
	 * @return	@e string
	 */
	protected function _getExecDiff( $str1, $str2 )
	{
		//-------------------------------
		// Write the tmp files
		//-------------------------------
		
		$file1 = IPS_ROOT_PATH . 'uploads/'.time().'-1';
		$file2 = IPS_ROOT_PATH . 'uploads/'.time().'-2';
		
		if ( $FH1 = @fopen( $file1, 'w' ) )
		{
			@fwrite( $FH1, $str1, strlen($str1) );
			@fclose( $FH1 );
		}
		
		if ( $FH2 = @fopen( $file2, 'w' ) )
		{
			@fwrite( $FH2, $str2, strlen($str2) );
			@fclose( $FH2 );
		}
		
		//-------------------------------
		// Check
		//-------------------------------
		
		if ( is_file( $file1 ) and is_file( $file2 ) )
		{
			exec( $this->diff_command.' '.$file1.' '.$file2, $result );
			
			@unlink( $file1 );
			@unlink( $file2 );
			
			return $result;
		}
		else
		{
			return "Error, files not written to disk";
		}
	}

	/**
	 * Format differences for output (common)
	 *
	 * @param	string	Differences report
	 * @param	string	Format method (inline|unified)
	 * @param	bool	Wrap in pre tags (true) or nl2br for output (false)
	 * @return	@e string
	 */
	public function formatDifferenceReport( $result, $method='inline', $pre=true )
	{
		if( $result )
		{
			if( $method == 'unified' )
			{
				$result	= str_replace( array( "\r\n", "\r" ), "\n", $result );
				$result	= preg_replace( '#(^|\n)(\-|\+)([^\n]+?)\n#', "\n\\1\\2\\3\n\n", $result ); 
				$result	= htmlspecialchars( $result );
				$result	= preg_replace( '#^@@([^@]+?)@@#m', '', $result );
				$result	= preg_replace( '#(^|\n)\-([^\n]+?)\n#', "\\1<del>\\2</del>\n", $result );
				$result	= preg_replace( '#(^|\n)\+([^\n]+?)\n#', "\\1<ins>\\2</ins>\n", $result );
				$result	= str_replace( "</ins>\n\n", "</ins>\n", $result );
				$result	= str_replace( "</del>\n", '</del>', $result );
				$result	= str_replace( "\n\n<ins>", "\n<ins>", $result );
				$result	= str_replace( "\n\n<del>", "\n<del>", $result );
				$result	= str_replace( "\n+\n", "\n\n", $result );
				$result	= str_replace( "\n-\n", "\n\n", $result );

				if( $pre )
				{
					$result	= '<pre>' . $result . '</pre>';
				}
				else
				{
					$result	= str_replace( "\t", "&nbsp; &nbsp; ", $result );
					$result	= str_replace( ' ', '&nbsp;', $result );
					$result	= nl2br( $result );
				}
			}
			else
			{
				$result	= str_replace( "\n", "<br>", $result );
				$result	= str_replace( "&gt;&lt;", "&gt;\n&lt;", $result );
				$result	= preg_replace( "#(?<!(\<del|\<ins)) {1}(?!:style)#i", "&nbsp;", $result );

				if( $pre )
				{
					$result	= '<pre>' . $result . '</pre>';
				}
				else
				{
					$result	= str_replace( "\t", "&nbsp; &nbsp; ", $result );
					$result	= str_replace( ' ', '&nbsp;', $result );
					$result	= nl2br( $result );
				}
			}
		}

		return $result;
	}
}