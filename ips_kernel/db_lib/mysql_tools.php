<?php

/**
 * @file		mysql_tools.php 	Provides methods to diagnostic a MySQL database
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * @since		Tuesday 1st March 2005 15:40
 * $LastChangedDate: 2012-04-12 11:12:25 -0400 (Thu, 12 Apr 2012) $
 * @version		v3.3.3
 * $Revision: 10587 $
 */

/**
 *
 * @class		db_tools
 * @brief		Provides methods to diagnostic a MySQL database
 * @note		The file is automatically loaded based on the driver value in conf_global.php
 *
 */
class db_tools
{
	/**
	 * Boolean flag that holds data about missing column/table/index
	 *
	 * @var		bool
	 */
	public $has_issues	= false;
	
	/**
	 * Array with an internal mapping of tables/columns
	 *
	 * @var		array
	 */
	protected $_mapping	= array();

	/**
	 * Diagnose table indexes
	 *
	 * @param	array 	$sql_statements		Array of create table/index statements to check
	 * @param	string 	$issues_to_fix		String of the issue to fix, can be set to 'all' to fix everything
	 * @return	@e array
	 *
	 * <b>Example Usage:</b>
	 * @code
	 * // Retrieve results
	 * $results = $this->dbIndexDiag( $sql_statements );
	 * // Retrieve results and fix a index
	 * $results = $this->dbIndexDiag( $sql_statements, $issues_to_fix );
	 * // Retrieve results and fix all indexes
	 * $results = $this->dbIndexDiag( $sql_statements, 'all' );
	 * @endcode
	 */
	public function dbIndexDiag( $sql_statements, $issues_to_fix='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$indexes 		= array();
		$error_count 	= 0;
		$output			= array();

		//-----------------------------------------
		// Do we have SQL statements?
		//-----------------------------------------
		
		if( is_array($sql_statements) && count($sql_statements) )
		{
			//-----------------------------------------
			// Loop over our statements
			//-----------------------------------------
			
			foreach( $sql_statements as $definition )
			{
				//-----------------------------------------
				// Some more per-statement init
				//-----------------------------------------
				
				$table_name		= "";
				$fields_str		= "";
				$primary_key	= "";
				$tablename		= array();
				$fields			= array();
				$final_keys		= array();
				$col_definition	= "";
				$colmatch		= array();
				$final_primary	= array();
				
				/* Remove backticks */
				$definition = str_replace( '`', '', $definition );
				
				$definition	= preg_replace( "#CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+#i", "CREATE TABLE ", $definition );

				//-----------------------------------------
				// Is this a create table statement?
				//-----------------------------------------

		        if ( preg_match( "#CREATE\s+TABLE\s+?(.+?)\s+?\(#ie", $definition, $tablename ) )
		        {
			        $tableName	= $tablename[1];
			        
			        //-----------------------------------------
			        // Does the table have a primary key?
			        //-----------------------------------------
			        
			        if ( preg_match( "#\s+?PRIMARY\s+?KEY\s+?(?:(\w+?)\s*)?\((.*?)\)(?:(?:[,\s+?$])?\((.+?)\))?#is", $definition, $fields ) )
					{
			        	$final_primary	= array();

			        	//-----------------------------------------
			        	// Did we find anything with our regex?
			        	//-----------------------------------------
			        	
				        if( count( $fields ) )
				        {
				        	//-----------------------------------------
				        	// Get the actual key name
				        	//-----------------------------------------
				        	
					        $primary_key	= trim($fields[1]);
					        $primary_fields	= implode( ",", array_map( 'trim', explode( ",", $fields[2] ) ) );

					        //-----------------------------------------
					        // Get the table definition
					        //-----------------------------------------
					        
					        $col_definition = $this->_sqlStripTicks( $definition );

							//-----------------------------------------
							// This is the primary key for this table
							//-----------------------------------------
							
					        $final_primary = array( $primary_key ? $primary_key : $primary_fields, $primary_fields );
	            		}
			        }
			    }

				//-----------------------------------------
				// Now find all non-primary keys
				//-----------------------------------------

		        if ( preg_match_all( "#\s+?(UNIQUE KEY|KEY|INDEX|UNIQUE)\s+?(?:(\w+?)\s+?)?\(\s*(.+?)\s*\)(\n|,\n)#is", $definition, $fields ) )
		        {
		        	//-----------------------------------------
		        	// We got some fields!
		        	//-----------------------------------------

					if( count( $fields[3] ) )
					{
						$i = 0;
						
						//-----------------------------------------
						// Loop over the data from the preg statement
						//-----------------------------------------
						
						foreach( $fields[3] as $index_cols )
						{
							//-----------------------------------------
							// Get index name, column name, and store
							//-----------------------------------------
							
							//$index_cols		= implode( ",", array_map( 'trim', explode( ",", $this->_sqlStripTicks( preg_replace( "#\(([^\(]+?)\)#", "", $index_cols ) ) ) ) );
							$index_cols		= implode( ",", array_map( 'trim', explode( ",", $this->_sqlStripTicks( $index_cols ) ) ) );
							
							$index_name		= $fields[2][$i] ? $fields[2][$i] : $index_cols;

							if( $index_cols != $final_primary[1] )
							{
								$final_keys[]	= array( $index_name, $index_cols, in_array( strtolower($fields[1][$i]), array( 'unique key', 'unique' ) ) ? true : false );
							}
							
							$i++;
						}
					}
				}

				//-----------------------------------------
				// We have some indexes for this table
				//-----------------------------------------
								
			    if( $tableName AND ( $primary_key OR count($final_keys) ) )
			    { 
				    $indexes[] = array( 'table' 	=> $tableName,
				    					'primary'	=> $final_primary,
				    					'index'		=> $final_keys,
				    				  );
			    }
		    }
	    }

		//-----------------------------------------
		// No indexes on this table
		//-----------------------------------------
		
	    if( !count($indexes) )
	    {
		   return false; 
		}

		//-----------------------------------------
		// Loop over the indexes
		//-----------------------------------------

		foreach( $indexes as $data )
		{
			//-----------------------------------------
			// Get table schematics and clean it
			//-----------------------------------------
			
			$row	= ipsRegistry::DB()->getTableSchematic( $data['table'] );
			$tbl	= $this->_sqlStripTicks( $row['Create Table'] );
			
			//-----------------------------------------
			// Start output (one per index)
			//-----------------------------------------

			$output[ $data['table'] ]	= array( 'table'		=> ipsRegistry::$settings['sql_tbl_prefix'].$data['table'],
												 'status'		=> 'ok',
												 'missing'		=> array(),
												);
			
			//-----------------------------------------
			// We had a primary key, so let's look for it
			//-----------------------------------------
			
			if( isset( $data['primary'] ) && is_array($data['primary']) AND count($data['primary']) )
			{
				$index_name = preg_replace( "/[^a-zA-Z0-9]/", '_', $data['primary'][0] );
				$index_cols	= $data['primary'][1];
				$ok			= 0;

				//-----------------------------------------
				// Can we find it...?
				//-----------------------------------------
				
				if ( preg_match( "#\s*PRIMARY\s+?KEY\s*(\(([^\)]+?)\))?#is", $tbl, $matches ) )
				{
					$ok = 1;
					
					if ( $matches[2] )
					{
						/* strip out white space so that def: key, key matches $index_cols which is always key,key */
						$matches[2] = str_replace( ' ', '', $matches[2] );
					}
					
					//-----------------------------------------
					// It is...now is the index right (mulicolumn)?
					//-----------------------------------------

					if ( $index_cols != $matches[2] )
					{
						//-----------------------------------------
						// Break out the real indexes and loop
						//-----------------------------------------

						foreach( explode( ',', $index_cols ) as $mc )
						{
							//-----------------------------------------
							// And make sure it's in the definition
							//-----------------------------------------
							
							if ( strpos( $match[2], $mc ) === false )
							{
								$query_needed	= 'ALTER TABLE ' . ipsRegistry::$settings['sql_tbl_prefix'] . $data['table'] . ' DROP INDEX ' . $index_name . ', ADD INDEX ' . $index_name . ' (' . $index_cols . ')';

								//-----------------------------------------
								// Are we fixing now?
								//-----------------------------------------
								
								if( preg_replace( '#^' . ipsRegistry::$settings['sql_tbl_prefix'] . '(.+?)#', "\\1", $issues_to_fix ) == $data['table'] OR $issues_to_fix == 'all' )
								{
									$r = ipsRegistry::DB()->query( $query_needed );
									$output['ran'][] = array( 'q' => $query_needed, 'status' => $r );
									
									break;
								}
								
								//-----------------------------------------
								// We got issues gomer!
								//-----------------------------------------
								
								$this->has_issues = 1;

								$output[ $data['table'] ]['status']		= 'error';
								$output[ $data['table'] ]['index'][]	= $index_name;
								$output[ $data['table'] ]['missing'][]	= $index_name;
								$output[ $data['table'] ]['fixsql'][]	= $query_needed;

								$ok       = 0;
								
								break;
							}
						}
					}
				}
				else
				{
					//-----------------------------------------
					// Generate query and set the output array
					//-----------------------------------------
					
					$query_needed = "ALTER TABLE " . ipsRegistry::$settings['sql_tbl_prefix'] . "{$data['table']} ADD PRIMARY KEY ({$index_name})";

					//-----------------------------------------
					// Are we fixing now?
					//-----------------------------------------
					
					if( preg_replace( '#^' . ipsRegistry::$settings['sql_tbl_prefix'] . '(.+?)#', "\\1", $issues_to_fix ) == $data['table'] OR $issues_to_fix == 'all' )
					{
						$ok	= 1;

						$r = ipsRegistry::DB()->query( $query_needed );
						$output['ran'][] = array( 'q' => $query_needed, 'status' => $r );
					}
					else
					{
						$this->has_issues = 1;
						
						$output[ $data['table'] ]['status']		= 'error';
						$output[ $data['table'] ]['index'][]	= $index_name;
						$output[ $data['table'] ]['missing'][]	= $index_name;
						$output[ $data['table'] ]['fixsql'][]	= $query_needed;
	
						$error_count++;
					}
				}
				
				//-----------------------------------------
				// Primary key is fine
				//-----------------------------------------
				
				if( $ok )
				{
					$output[ $data['table'] ]['index'][]	= $index_name;
				}
			}

			//-----------------------------------------
			// Got other indexes?
			//-----------------------------------------
			
			if ( isset( $data['index'] ) && is_array( $data['index'] ) and count( $data['index'] ) )
			{
				//-----------------------------------------
				// Loop over the other indexes
				//-----------------------------------------
				
				foreach( $data['index'] as $indexes )
				{
					$index_name	= $indexes[0];
					$_checkName	= $index_name;
					$index_cols	= str_replace( ' ', '', $indexes[1] ? $indexes[1] : $index_name );
					$index_name	= preg_replace( "/[^a-zA-Z0-9]/", '_', $index_name );

					$ok			= 0;

					/* MySql seems to name multi-column indexes by the first field name regardless of what KEY NAME you specify. I know, nice isn't it. */
					if ( strstr( $index_cols, ',' ) AND ! preg_match( "#\n\s*?(?:UNIQUE KEY|KEY|INDEX|UNIQUE)\s+?{$_checkName}\s+?(\(\s*(.+?)\s*\))(\n|,\n)#is", $tbl, $match ) )
					{
						list( $_checkName, ) = explode( ',', $index_cols );
						
						/* Now remove the xxxx(int) if it's there */
						if ( strstr( $_checkName, '(' ) )
						{
							$_checkName = preg_replace( "#^(.*)\(([^\)]+?)\)#", "\\1", $_checkName );
						}
					}
					
					//-----------------------------------------
					// Is the key there?
					//-----------------------------------------
					
					if ( preg_match( "#\n\s*?(?:UNIQUE KEY|KEY|INDEX|UNIQUE)\s+?{$_checkName}\s+?(\(\s*(.+?)\s*\))(\n|,\n)#is", $tbl, $match ) )
					{
						$ok	= 1;
						
						if ( $match[2] )
						{
							/* strip out white space so that def: key, key matches $index_cols which is always key,key */
							$match[2] = str_replace( ' ', '', $match[2] );
						}
					
						//-----------------------------------------
						// It is...now is the index right (mulicolumn)?
						//-----------------------------------------
		
						if ( $index_cols != $match[2] )
						{
							//-----------------------------------------
							// Break out the real indexes and loop
							//-----------------------------------------
							
							foreach( explode( ',', $indexes[1] ) as $mc )
							{
								//-----------------------------------------
								// And make sure it's in the definition
								//-----------------------------------------
								
								if ( strpos( $match[2], str_replace(' ', '', $mc) ) === false )
								{
									$_indexType	= $indexes[2] ? "UNIQUE KEY" : "INDEX";
									
									$query_needed	= 'ALTER TABLE ' . ipsRegistry::$settings['sql_tbl_prefix'] . $data['table'] . ' DROP INDEX ' . $index_name . ', ADD ' . $_indexType . ' ' . $index_name . ' (' . $index_cols . ')';

									//-----------------------------------------
									// Are we fixing now?
									//-----------------------------------------
									
									if( preg_replace( '#^' . ipsRegistry::$settings['sql_tbl_prefix'] . '(.+?)#', "\\1", $issues_to_fix ) == $data['table'] OR $issues_to_fix == 'all' )
									{
										$r = ipsRegistry::DB()->query( $query_needed );
										$output['ran'][] = array( 'q' => $query_needed, 'status' => $r );
										
										continue 2;
									}

									$this->has_issues = 1;

									$output[ $data['table'] ]['status']		= 'error';
									$output[ $data['table'] ]['index'][]	= $index_name;
									$output[ $data['table'] ]['missing'][]	= $index_name;
									$output[ $data['table'] ]['fixsql'][]	= $query_needed;

									$ok       = 0;
									
									break;
								}
							}
						}
					}
					else
					{
						//-----------------------------------------
						// Generate query and set the output array
						//-----------------------------------------
						
						$_indexType	= $indexes[2] ? "UNIQUE KEY" : "INDEX";
						
						$query_needed = 'ALTER TABLE ' . ipsRegistry::$settings['sql_tbl_prefix'] . $data['table'] . ' ADD ' . $_indexType . ' ' . $index_name . ' (' . $index_cols . ')';

						//-----------------------------------------
						// Are we fixing now?
						//-----------------------------------------
						
						if( preg_replace( '#^' . ipsRegistry::$settings['sql_tbl_prefix'] . '(.+?)#', "\\1", $issues_to_fix ) == $data['table'] OR $issues_to_fix == 'all' )
						{
							$r = ipsRegistry::DB()->query( $query_needed );
							$output['ran'][] = array( 'q' => $query_needed, 'status' => $r );
							
							$ok	= 1;
						}
						else
						{
							$output[ $data['table'] ]['status']		= 'error';
							$output[ $data['table'] ]['index'][]	= $index_name;
							$output[ $data['table'] ]['missing'][]	= $index_name;
							$output[ $data['table'] ]['fixsql'][]	= $query_needed;
	
							$error_count++;
						}
					}

					//-----------------------------------------
					// The index is ok
					//-----------------------------------------
					
					if ( $ok )
					{
						$output[ $data['table'] ]['index'][]	= $index_name;
					}
				}
			}
		}
		
		return array( 'error_count'	=> $error_count, 'results' => $output );
	}


	/**
	 * Diagnose table structure
	 *
	 * @param	array 	$sql_statements		Array of create table/columns statements to check
	 * @param	string 	$issues_to_fix		String of the issue to fix, can be set to 'all' to fix everything
	 * @return	@e array
	 *
	 * <b>Example Usage:</b>
	 * @code
	 * // Retrieve results
	 * $results = $this->dbTableDiag( $sql_statements );
	 * // Retrieve results and fix a table/column
	 * $results = $this->dbTableDiag( $sql_statements, $issues_to_fix );
	 * // Retrieve results and fix all tables/columns
	 * $results = $this->dbTableDiag( $sql_statements, 'all' );
	 * @endcode
	 */
	public function dbTableDiag( $sql_statements, $issues_to_fix='' )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
						
		$queries_needed		= array();
		$tables_needed		= array();
		$table_definitions	= array();
		$error_count		= 0;
		$_tablesFull		= array();
		$_tablesAlter		= array();

		//-----------------------------------------
		// Do we have any statements?
		//-----------------------------------------
		
		if( is_array( $sql_statements ) && count( $sql_statements ) )
		{
			//-----------------------------------------
			// Loop over those statements
			//-----------------------------------------
			
			foreach( $sql_statements as $the_table )
			{
				$expected_columns	= array();
				$missing_columns	= array();
				
				/* Remove backticks */
				$the_table = str_replace( '`', '', $the_table );
				
				$the_table	= preg_replace( "#CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+#i", "CREATE TABLE ", $the_table );

				//-----------------------------------------
				// Is this a create table statement?
				//-----------------------------------------
				
				if( preg_match( "#CREATE\s+TABLE\s+?(.+?)\s+?\(#ie", $the_table, $definition ) )
				{
					$tableName	= ipsRegistry::$settings['sql_tbl_prefix'] . $definition[1];
					
					//-----------------------------------------
					// Store the entire table definition
					//-----------------------------------------
					
					$table_definitions[ $tableName ] = str_replace( $definition[1], $tableName, $the_table );
					$_tablesFull[]	= $tableName;
					
					//-----------------------------------------
					// Get the columns
					//-----------------------------------------
					
					$columns_array = explode( "\n", $the_table );

					//-----------------------------------------
					// Get rid of first row ("CREATE TABLE ...")
					//-----------------------------------------
					
					array_shift($columns_array);
					
					//-----------------------------------------
					// Get rid of the junk at the end of each line
					//-----------------------------------------
					
					if ( ( strpos(end($columns_array), ");") === 0 ) OR 
						 ( strpos(end($columns_array), ")") === 0 )  OR
						 ( strpos(end($columns_array), ";") === 0 ) )
					{
						array_pop($columns_array);
					}

					reset($columns_array);
					
					//-----------------------------------------
					// Loop over each supposed "column"
					//-----------------------------------------
					
					foreach( $columns_array as $col )
					{
						//-----------------------------------------
						// Find the column name
						//-----------------------------------------
						
						$temp		= preg_split( "/[\s]+/" , trim($col) );
						$columnName	= $temp[0];
						
						//-----------------------------------------
						// Ignore custom profile fields
						// @link	http://community.invisionpower.com/tracker/issue-27495-database-checker-find-removed-custom-profile-fields
						//-----------------------------------------
						
						if( $tableName == 'pfields_content' AND strpos( $columnName, 'field_' ) === 0 )
						{
							continue;
						}

						//-----------------------------------------
						// If this is a real column, map it
						//-----------------------------------------
						
						if( !in_array( $columnName, array( "PRIMARY", "KEY", "INDEX", "UNIQUE", "", "(", ";", ");" ) ) )
						{
							$expected_columns[]								= $columnName;
							$this->_mapping[ $tableName ][ $columnName ]	= trim( str_replace( ',', ';', $col ) );
						}
					}
				}
				
				//-----------------------------------------
				// This an alter table statement?
				//-----------------------------------------
				
				elseif ( preg_match( "#ALTER\s+TABLE\s+([a-z_]*)\s+ADD\s+([a-z_]*)\s+#is", $the_table, $definition ) )
				{
					//-----------------------------------------
					// If this is truly adding a new column, map it
					//-----------------------------------------

					if( $definition[1] AND $definition[2] AND $definition[2] != 'INDEX' AND strpos($definition[2], 'TYPE') === false AND strpos($definition[2], 'ENGINE') === false )
					{
						$tableName	= ipsRegistry::$settings['sql_tbl_prefix'] . trim( $definition[1] );
						$columnName	= trim($definition[2]);
						$_tablesAlter[]	= $tableName;

						$expected_columns[]								= $columnName;
						$this->_mapping[ $tableName ][ $columnName ]	= $definition[2] . ' ' . str_replace( $definition[0], '', $the_table ) . ";";
					}
				}
				
				//-----------------------------------------
				// We don't care about any other queries
				//-----------------------------------------
				
				else
				{
					continue;
				}

				//-----------------------------------------
				// Don't die on me sarge!
				//-----------------------------------------
				
				ipsRegistry::DB()->return_die = 1;
				
				$tableNoPrefix	= preg_replace( '#^' . ipsRegistry::$settings['sql_tbl_prefix'] . '(.+?)#', "\\1", $tableName );

				//-----------------------------------------
				// If table is missing entirely, we need to build it
				//-----------------------------------------
				
				if ( ! ipsRegistry::DB()->checkForTable( $tableNoPrefix ) )
				{
					//-----------------------------------------
					// Are we fixing now?
					//-----------------------------------------
					
					if( preg_replace( '#^' . ipsRegistry::$settings['sql_tbl_prefix'] . '(.+?)#', "\\1", $issues_to_fix ) == $tableNoPrefix OR $issues_to_fix == 'all' )
					{
						if ( ipsRegistry::$settings['sql_tbl_prefix'] )
						{
							$the_table = preg_replace( "#^CREATE TABLE(?:\s+?)?(\S+)#is", "CREATE TABLE "  . ipsRegistry::$settings['sql_tbl_prefix'] ."\\1 ", $the_table );
						}
						
						$r = ipsRegistry::DB()->query( $the_table );
						$output['ran'][] = array( 'q' => $the_table, 'status' => $r );
						
						continue;
					}

					$output[ $tableName ]	= array( 'key'		=> $tableNoPrefix,
													 'table'	=> $tableName,
													 'status'	=> 'error_table',
													 'fixsql'	=> array( $table_definitions[ $tableName ] )
													);

					//-----------------------------------------
					// Increment error counter
					//-----------------------------------------
					
					$error_count++;
					
					//-----------------------------------------
					// Reset failed status
					//-----------------------------------------
					
					ipsRegistry::DB()->failed = 0;
				}
				
				//-----------------------------------------
				// Table exists...
				//-----------------------------------------
				
				else
				{
					//-----------------------------------------
					// Loop over all the columns
					//-----------------------------------------

					foreach( $expected_columns as $trymeout )
					{
						//-----------------------------------------
						// Does column exist?
						//-----------------------------------------
						
						if( ! ipsRegistry::DB()->checkForField( $trymeout, $tableNoPrefix ) )
						{
							//-----------------------------------------
							// Missing - create "ALTER TABLE" query
							//-----------------------------------------
							
							$query_needed		= "ALTER TABLE " . $tableName . " ADD " . $this->_mapping[ $tableName ][ $trymeout ];

							//-----------------------------------------
							// If this is an autoincrement column, we need
							// to add the primary key, since it won't exist
							//-----------------------------------------
							
							if( strpos( $query_needed, "auto_increment;" ) !== false )
							{
								//-----------------------------------------
								// Cut off the ";", add primary key bit
								//-----------------------------------------
								
								$query_needed = substr( $query_needed, 0, -1 ).", ADD PRIMARY KEY( ". $trymeout . ");";
							}

							//-----------------------------------------
							// Are we fixing now?
							//-----------------------------------------
							
							if( preg_replace( '#^' . ipsRegistry::$settings['sql_tbl_prefix'] . '(.+?)#', "\\1", $issues_to_fix ) == $tableNoPrefix OR $issues_to_fix == 'all' )
							{
								$r = ipsRegistry::DB()->query( $query_needed );
								$output['ran'][] = array( 'q' => $query_needed, 'status' => $r );
								continue;
							}
							
							$missing_columns[]	= $trymeout;

							//-----------------------------------------
							// We only do this once
							//-----------------------------------------
							
							if( !isset($output[ $tableName ]) OR !count($output[ $tableName ]) )
							{
								$output[ $tableName ]	= array( 'key'		=> $tableNoPrefix,
																 'table'	=> $tableName,
																 'status'	=> 'error_column',
																);
							}

							//-----------------------------------------
							// But with each error, add the query
							//-----------------------------------------
							
							$output[ $tableName ]['fixsql'][]	= $query_needed;
							
							//-----------------------------------------
							// Increment error count
							//-----------------------------------------
							
							$error_count++;
						}
					}

					//-----------------------------------------
					// If nothing was wrong, show ok message
					//-----------------------------------------
					
					if( !count( $missing_columns ) )
					{
						$output[] = array( 'key'		=> $tableNoPrefix,
										   'table'		=> $tableName,
										   'column'		=> in_array( $tableName, $_tablesFull ) ? '' : $trymeout,
										   'status'		=> 'ok',
										   'fixsql'		=> '' );
					}
				}
			}
		}
		
		return array( 'error_count'	=> $error_count, 'results' => $output );
	}
	
	/**
	 * Remove ticks from statement
	 *
	 * @param	string 	$data		String to clean
	 * @return	@e string
	 *
	 * <b>Example Usage:</b>
	 * @code
	 * $query = $this->_sqlStripTicks( $data );
	 * @endcode
	 */
	protected function _sqlStripTicks( $data )
	{
		return str_replace( "`", "", $data );
	}
}