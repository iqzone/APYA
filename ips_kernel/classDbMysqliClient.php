<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * MySQL Database Driver :: MySQLi client
 * Last Updated: $Date: 2012-05-25 13:17:47 -0400 (Fri, 25 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Kernel
 * @link		http://www.invisionpower.com
 * @since		Monday 28th February 2005 16:46
 * @version		$Revision: 10798 $
 */

class db_driver_mysql extends db_main_mysql implements interfaceDb
{
	/**
	 * Connection failed flag
	 *
	 * @var 		boolean
	 */
	protected $connect_failed		= false;

	/**
	 * constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		if( !defined('MYSQLI_USED') )
		{
			define( 'MYSQLI_USED', 1 );
		}

		//--------------------------------------
		// Set up any required connect vars here
		//--------------------------------------

     	$this->connect_vars['mysql_tbl_type'] = "";
	}

    /**
	 * Connect to database server
	 *
	 * @return	@e boolean
	 */
	public function connect()
	{
		//-----------------------------------------
     	// Done SQL prefix yet?
     	//-----------------------------------------

     	$this->_setPrefix();

    	//-----------------------------------------
    	// Load query file
    	//-----------------------------------------

    	$this->_loadCacheFile();

     	//-----------------------------------------
     	// Connect
     	//-----------------------------------------

		/* Did we add a port inline? */
		if ( ! $this->obj['sql_port'] AND strstr( $this->obj['sql_host'], ':' ) )
		{
			list( $host, $port ) = explode( ':', $this->obj['sql_host'] );

			$this->obj['sql_host'] = $host;
			$this->obj['sql_port'] = intval( $port );
		}
		
		/* Persistent connection */
		if( $this->obj['persistent'] )
		{
			$this->obj['sql_host'] = 'p:' . $this->obj['sql_host'];
		}
		
		/* Use socket if specified */
		if( $this->obj['sql_socket'] )
		{
			if( $this->obj['sql_port'] )
			{
				$this->connection_id = @mysqli_connect( $this->obj['sql_host'],
														  $this->obj['sql_user'],
														  $this->obj['sql_pass'],
														  $this->obj['sql_database'],
														  $this->obj['sql_port'],
														  $this->obj['sql_socket']
														);
			}
			else
			{
				$this->connection_id = @mysqli_connect( $this->obj['sql_host'],
														  $this->obj['sql_user'],
														  $this->obj['sql_pass'],
														  $this->obj['sql_database'],
														  null,
														  $this->obj['sql_socket']
														);
			}
		}
		else
		{
			if( $this->obj['sql_port'] )
			{
				$this->connection_id = @mysqli_connect( $this->obj['sql_host'],
														  $this->obj['sql_user'],
														  $this->obj['sql_pass'],
														  $this->obj['sql_database'],
														  $this->obj['sql_port']
														);
			}
			else
			{
				$this->connection_id = @mysqli_connect( $this->obj['sql_host'],
														  $this->obj['sql_user'],
														  $this->obj['sql_pass'],
														  $this->obj['sql_database']
														);
			}

		}

		if ( ! $this->connection_id )
		{
			$this->usingClass     = 'db_driver_mysql';
			$this->connect_failed = true;
			$this->throwFatalError();
			return FALSE;
		}

        mysqli_autocommit( $this->connection_id, TRUE );

     	//-----------------------------------------
     	// Remove sensitive data
     	//-----------------------------------------

		unset( $this->obj['sql_host'] );
		unset( $this->obj['sql_user'] );
		unset( $this->obj['sql_pass'] );

     	//-----------------------------------------
     	// If there's a charset set, run it
     	//-----------------------------------------

     	if( $this->obj['sql_charset'] )
     	{
     		$this->query( "SET NAMES '{$this->obj['sql_charset']}'" );
     	}

		parent::connect();

        return TRUE;
    }

    /**
	 * Close database connection
	 *
	 * @return	@e boolean
	 */
	public function disconnect()
	{
    	if ( $this->connection_id )
    	{
        	return @mysqli_close( $this->connection_id );
        }
    }

    /**
	 * Execute a direct database query
	 *
	 * @param	string		Database query
	 * @param	boolean		[Optional] Do not convert table prefix
	 * @return	@e resource
	 */
	public function query( $the_query, $bypass=false )
	{
    	//-----------------------------------------
        // Debug?
        //-----------------------------------------

        if ( $this->obj['debug'] OR ( $this->obj['use_debug_log'] AND $this->obj['debug_log'] ) OR ( $this->obj['use_bad_log'] AND $this->obj['bad_log'] ) )
        {
    		IPSDebug::startTimer();
			$_MEMORY = IPSDebug::getMemoryDebugFlag();
    	}

		//-----------------------------------------
		// Stop sub selects? (UNION)
		//-----------------------------------------

		if ( ! IPS_DB_ALLOW_SUB_SELECTS )
		{
			# On the spot allowance?
			if ( ! $this->allow_sub_select )
			{
				$_tmp = strtolower( $this->_removeAllQuotes($the_query) );

				if ( preg_match( "#(?:/\*|\*/)#i", $_tmp ) )
				{
					$this->throwFatalError( "You are not allowed to use comments in your SQL query.\nAdd \ipsRegistry::DB()->allow_sub_select=1; before any query construct to allow them" );
					return false;
				}

				if ( preg_match( "#[^_a-zA-Z]union[^_a-zA-Z]#s", $_tmp ) )
				{
					$this->throwFatalError( "UNION query joins are not allowed.\nAdd \ipsRegistry::DB()->allow_sub_select=1; before any query construct to allow them" );
					return false;
				}
				else if ( preg_match_all( "#[^_a-zA-Z](select)[^_a-zA-Z]#s", $_tmp, $matches ) )
				{
					if ( count( $matches ) > 1 )
					{
						$this->throwFatalError( "SUB SELECT query joins are not allowed.\nAdd \ipsRegistry::DB()->allow_sub_select=1; before any query construct to allow them" );
						return false;
					}
				}
			}
		}

    	//-----------------------------------------
    	// Run the query
    	//-----------------------------------------
    	$this->_tmpQ    = substr( $the_query, 0, 100 ) . '...';
        $this->query_id = mysqli_query($this->connection_id, $the_query );

      	//-----------------------------------------
      	// Reset array...
      	//-----------------------------------------

      	$this->resetDataTypes();
      	$this->allow_sub_select = false;

        if (! $this->query_id )
        {
            $this->throwFatalError("mySQL query error: $the_query");
        }

        //-----------------------------------------
        // Logging?
        //-----------------------------------------

		if ( ( $this->obj['use_debug_log'] AND $this->obj['debug_log'] ) OR ( $this->obj['use_bad_log'] AND $this->obj['bad_log'] ) OR ( $this->obj['use_slow_log'] AND $this->obj['slow_log'] ) )
		{
			$endtime  = IPSDebug::endTimer();
			$_data    = '';

			if ( preg_match( "/^(?:\()?select/i", $the_query ) )
        	{
        		$eid  = mysqli_query($this->connection_id, "EXPLAIN {$the_query}");
        		$_bad = false;

				while( $array = mysqli_fetch_array($eid) )
				{
					$array['extra'] = isset( $array['extra'] ) ? $array['extra'] : '';

					$_data .= "\n+------------------------------------------------------------------------------+";
					$_data .= "\n|Table: ". $array['table'];
					$_data .= "\n|Type: ". $array['type'];
					$_data .= "\n|Possible Keys: ". $array['possible_keys'];
					$_data .= "\n|Key: ". $array['key'];
					$_data .= "\n|Key Len: ". $array['key_len'];
					$_data .= "\n|Ref: ". $array['ref'];
					$_data .= "\n|Rows: ". $array['rows'];
					$_data .= "\n|Extra: ". $array['Extra'];
					//$_data .= "\n+------------------------------------------------------------------------------+";

					if ( ( $this->obj['use_bad_log'] AND $this->obj['bad_log'] ) AND ( stristr( $array['Extra'], 'filesort' ) OR stristr( $array['Extra'], 'temporary' ) ) )
					{
						$this->writeDebugLog( $the_query, $_data, $endtime, $this->obj['bad_log'], TRUE );
					}

					if ( ( $this->obj['use_slow_log'] AND $this->obj['slow_log'] ) AND $endtime >= $this->obj['use_slow_log'] )
					{
						$this->writeDebugLog( $the_query, $_data, $endtime, $this->obj['slow_log'], TRUE );
					}
				}

				if ( $this->obj['use_debug_log'] AND $this->obj['debug_log'] )
				{
					$this->writeDebugLog( $the_query, $_data, $endtime );
				}
			}
			else
			{
				if ( $this->obj['use_debug_log'] AND $this->obj['debug_log'] )
				{
					$this->writeDebugLog( $the_query, $_data, $endtime );
				}
			}
		}

        //-----------------------------------------
        // Debugging?
        //-----------------------------------------

        if ($this->obj['debug'])
        {
        	$endtime    = IPSDebug::endTimer();
        	$memoryUsed = IPSDebug::setMemoryDebugFlag( '', $_MEMORY );
			$memory     = '';
        	$shutdown   = $this->is_shutdown ? 'SHUTDOWN QUERY: ' : '';

        	if ( preg_match( "/^(?:\()?select/i", $the_query ) )
        	{
        		$eid = mysqli_query( $this->connection_id, "EXPLAIN {$the_query}" );

        		$this->debug_html .= "<table width='95%' border='1' cellpadding='6' cellspacing='0' bgcolor='#FFE8F3' align='center'>
										   <tr>
										   	 <td colspan='8' style='font-size:14px' bgcolor='#FFC5Cb'><b>{$shutdown}Select Query</b></td>
										   </tr>
										   <tr>
										    <td colspan='8' style='font-family:courier, monaco, arial;font-size:14px;color:black'>$the_query</td>
										   </tr>
										   <tr bgcolor='#FFC5Cb'>
											 <td><b>table</b></td><td><b>type</b></td><td><b>possible_keys</b></td>
											 <td><b>key</b></td><td><b>key_len</b></td><td><b>ref</b></td>
											 <td><b>rows</b></td><td><b>Extra</b></td>
										   </tr>\n";
				while( $array = mysqli_fetch_array($eid) )
				{
					$type_col = '#FFFFFF';

					if ($array['type'] == 'ref' or $array['type'] == 'eq_ref' or $array['type'] == 'const')
					{
						$type_col = '#D8FFD4';
					}
					else if ($array['type'] == 'ALL')
					{
						$type_col = '#FFEEBA';
					}

					$this->debug_html .= "<tr bgcolor='#FFFFFF'>
											 <td>$array[table]&nbsp;</td>
											 <td bgcolor='$type_col'>$array[type]&nbsp;</td>
											 <td>$array[possible_keys]&nbsp;</td>
											 <td>$array[key]&nbsp;</td>
											 <td>$array[key_len]&nbsp;</td>
											 <td>$array[ref]&nbsp;</td>
											 <td>$array[rows]&nbsp;</td>
											 <td>$array[Extra]&nbsp;</td>
										   </tr>\n";
				}

				$this->sql_time += $endtime;

				if ($endtime > 0.1)
				{
					$endtime = "<span style='color:red'><b>$endtime</b></span>";
				}

				if ( $memoryUsed )
				{
					$memory = '<br />Memory Used: ' . IPSLib::sizeFormat( $memoryUsed, TRUE );
				}

				$this->debug_html .= "<tr>
										  <td colspan='8' bgcolor='#FFD6DC' style='font-size:14px'><b>MySQL time</b>: $endtime{$memory}</b></td>
										  </tr>
										  </table>\n<br />\n";
			}
			else
			{
			  $this->debug_html .= "<table width='95%' border='1' cellpadding='6' cellspacing='0' bgcolor='#FEFEFE'  align='center'>
										 <tr>
										  <td style='font-size:14px' bgcolor='#EFEFEF'><b>{$shutdown}Non Select Query</b></td>
										 </tr>
										 <tr>
										  <td style='font-family:courier, monaco, arial;font-size:14px'>$the_query</td>
										 </tr>
										 <tr>
										  <td style='font-size:14px' bgcolor='#EFEFEF'><b>MySQL time</b>: $endtime</span></td>
										 </tr>
										</table><br />\n\n";
			}
		}

		$this->query_count++;

        $this->obj['cached_queries'][] = $the_query;

        return $this->query_id;
    }

    /**
	 * Retrieve number of rows affected by last query
	 *
	 * @return	@e integer
	 */
	public function getAffectedRows()
	{
		return mysqli_affected_rows( $this->connection_id );
	}

    /**
	 * Retrieve number of rows in result set
	 *
	 * @param	resource	[Optional] Query id
	 * @return	@e integer
	 */
	public function getTotalRows( $query_id=null )
	{
		if ( ! $query_id )
   		{
    		$query_id = $this->query_id;
    	}

        return mysqli_num_rows( $query_id );
    }

    /**
	 * Retrieve latest autoincrement insert id
	 *
	 * @return	@e integer
	 */
	public function getInsertId()
	{
		return mysqli_insert_id($this->connection_id);
	}

    /**
	 * Retrieve the current thread id
	 *
	 * @return	@e integer
	 */
	public function getThreadId()
	{
		return mysqli_thread_id($this->connection_id);
	}

    /**
	 * Free result set from memory
	 *
	 * @param	resource	[Optional] Query id
	 * @return	@e void
	 */
	public function freeResult( $query_id=null )
	{
   		if ( ! $query_id )
   		{
    		$query_id = $this->query_id;
    	}

    	@mysqli_free_result( $query_id );
    }

    /**
	 * Retrieve row from database
	 *
	 * @param	resource	[Optional] Query result id
	 * @return	@e mixed
	 */
	public function fetch( $query_id=null )
	{
		//$_MEMORY = IPSDebug::getMemoryDebugFlag();

    	if ( ! $query_id )
    	{
    		$query_id = $this->query_id;
    	}

        $this->record_row = mysqli_fetch_assoc( $query_id );

/*if ( is_array( $this->record_row ) )
{
$tmp = $this->record_row;
$boo = array_shift( $tmp );
}

$poo = IPSDebug::setMemoryDebugFlag( 'Fetch Row: ' . $boo . ' ' . $this->_tmpQ, $_MEMORY );
$this->_tmpT += $poo;*/
        return $this->record_row;
    }

	/**
	 * Return the number calculated rows (as if there was no limit clause)
	 *
	 * @param	string 		[ alias name for the count(*) ]
	 * @return	@e integer
	 */
	public function fetchCalculatedRows( $alias='count' )
	{
		$calcRowsHandle = mysqli_query( $this->connection_id, "SELECT FOUND_ROWS() as " . $alias );
		
		$this->obj['cached_queries'][] = "SELECT FOUND_ROWS() as " . $alias;
		
		$val = mysqli_fetch_assoc( $calcRowsHandle );

		return intval( $val[ $alias ] );
	}

    /**
	 * Get array of fields in result set
	 *
	 * @param	resource	[Optional] Query id
	 * @return	@e array
	 */
	public function getResultFields( $query_id=null )
	{
    	$fields = array();

   		if ( !$query_id )
   		{
    		$query_id = $this->query_id;
    	}

		while( $field = mysqli_fetch_field($query_id) )
		{
            $fields[] = $field;
		}

		return $fields;
	}

    /**
	 * Get array of table names in database
	 *
	 * @return	@e array
	 */
	public function getTableNames()
	{
	    if ( is_array( $this->cached_tables ) AND count( $this->cached_tables ) )
	    {
		    return $this->cached_tables;
	    }

	    $current			= $this->return_die;
	    $this->return_die 	= true;

	    $qid = $this->query( "SHOW TABLES FROM `{$this->obj['sql_database']}`" );

	    $this->return_die 	= $current;

	    if( $qid AND $this->getTotalRows($qid) )
	    {
			while( $result = mysqli_fetch_array($qid) )
			{
				$this->cached_tables[] = $result[0];
			}
		}

		mysqli_free_result($qid);

		return $this->cached_tables;
   	}

    /**
	 * Retrieve SQL server version
	 *
	 * @return	@e string
	 */
	public function getSqlVersion()
	{
		if ( ! $this->sql_version and ! $this->true_version )
		{
			$version = mysqli_get_server_info($this->connection_id);

			$this->true_version = $version;
			$tmp                = explode( '.', preg_replace( "#[^\d\.]#", "\\1", $version ) );

			$this->sql_version = sprintf('%d%02d%02d', $tmp[0], $tmp[1], $tmp[2] );
   		}

   		return $this->sql_version;
	}

    /**
	 * Escape strings for DB insertion
	 *
	 * @param	string		Text to escape
	 * @return	@e string
	 */
	public function addSlashes( $t )
	{
		return @mysqli_real_escape_string( $this->connection_id, $t );
	}

    /**
	 * Get SQL error number
	 *
	 * @return	@e mixed
	 */
	protected function _getErrorNumber()
	{
	    if( $this->connect_failed )
	    {
		    return mysqli_connect_errno();
	    }
	    else
	    {
    		return mysqli_errno( $this->connection_id );
		}
	}

    /**
	 * Get SQL error message
	 *
	 * @return	@e string
	 */
	protected function _getErrorString()
	{ 
	    if( $this->connect_failed )
	    {
		    return mysqli_connect_error( );
	    }
	    else
	    {
    		return mysqli_error( $this->connection_id );
		}
	}
	
	/**
	 * Test to see whether a table exists
	 *
	 * @param	string		Table name
	 * @return	@e boolean
	 */
	public function checkForTable( $table )
	{
		//$table_names = $this->getTableNames();

		//$return = false;

		//if ( in_array( strtolower( trim( $this->obj['sql_tbl_prefix'] . $table ) ), array_map( 'strtolower', $table_names ) ) )
		//{
		//    $return = true;
		//}

		//unset($table_names);
		
		$table = trim( $this->obj['sql_tbl_prefix'] . $table );
		
		$qid = $this->query( "SHOW TABLES FROM `{$this->obj['sql_database']}` LIKE '{$table}'" );

		if( ! $qid OR ! $this->getTotalRows($qid) )
		{
			return false;
		}
		else
		{
			while( $result = mysqli_fetch_array($qid) )
			{
				if( $result[0] == $table )
				{
					mysqli_free_result($qid);

					return true;
				}
			}
	    }

	    return false;
	}
} // end class
