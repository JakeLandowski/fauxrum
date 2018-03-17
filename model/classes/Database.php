<?php
/**
 *  Library of CRUD Operations for Fauxrum application. 
 */

require_once getenv('HOME') . '/db_configs/fauxrum_config.php';

/**
 *  Contains SELECT, SELECT_ALL, INSERT, UPDATE, DELETE 
 *  functions for basic CRUD operations.
 *  
 *  @author Jacob Landowski
 */
abstract class Database
{
    const ONE = 'one';

    // ~~~~ Database Schema ~~~~ //
    const VALID_ENTRIES = 
    [
        'User' => 
            [ 
                'id'       => 'int', 
                'username' => 'string', 
                'email'    => 'string', 
                'password' => 'string' 
            ],
        'Thread' => 
            [ 
                'id'            => 'int', 
                'title'         => 'string',    
                'owner'         => 'int',
                'replies'       => 'int',
                'views'         => 'int', 
                'created'       => 'string', 
                'bot_generated' => 'int',
                'parsed'        => 'int'
            ],
        'Thread_User_Views' =>
            [
                'id'     => 'int', 
                'thread' => 'int',
                'user'   => 'int'
            ],
        'Post' => 
            [ 
                'id'            => 'int', 
                'thread'        => 'int',   
                'owner'         => 'int', 
                'created'       => 'string', 
                'content'       => 'string', 
                'bot_generated' => 'int', 
                'parsed'        => 'int',
                'is_root_post'  => 'int' 
            ],
        'TextMap' =>
            [ 
                'id'       => 'int', 
                'map_data' => 'lob', 
                'owner'    => 'int' 
            ]
    ];
    
    // ~~~~ Cross Reference from Schema to assign bind type ~~~~ //
    const PDO_PARAMS = 
    [
        'int'    => PDO::PARAM_INT,
        'string' => PDO::PARAM_STR,
        'lob'    => PDO::PARAM_LOB
    ];

  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//

    // ~~~~ USED BY Condition.php ~~~~ //
    /**
     *  Checks if a given column string exists under any of the tables
     *  in the database schema specified in Database:VALID_ENTRIES.
     * 
     *  @param string $column The string to lookup in VALID_ENTRIES
     *                        to check if this column exists in any
     *                        of the database tables.
     * 
     *  @return boolean       True if the column exists under one of 
     *                        tables.
     */
    public static final function isValidColumn($column)
    {
        foreach(Database::VALID_ENTRIES as $table)
        {
            if(array_key_exists($column, $table)) return true;
        }

        return false; 
    }

    // ~~~~ USED BY Condition.php ~~~~ //
    /**
     *  Checks if a given table string exists as a table in the database
     *  schema specified in Database:VALID_ENTRIES.
     * 
     *  @param string $table The string to lookup in VALID_ENTRIES
     *                       to check if this table exists..
     * 
     *  @return boolean      True if the table exists.
     */
    public static final function validateTable(&$table)
    {
        if(!is_string($table))
        {
            CustomError::throw("\"$table\" given is not a string.", 2);
        }
        else if(!array_key_exists(trim($table), Database::VALID_ENTRIES))
        {
            CustomError::throw("\"$table\" is not a valid table in the Database.", 2);
        }
    }

    // ~~~~ SELECT ~~~~ //
    /**
     *  Attempts to perform a SELECT query to the database using the
     *  given table, columns, and additional SELECT options in an options
     *  array. Will automatically bind values and check for errors to ensure
     *  robustness.
     * 
     *  Usage: Database::SELECT($columns = [ strings ], $table = string, OPTIONAL: $options = [ fields ])
     *         Database::SELECT($columns = string, $table = string)
     *         Database::SELECT_ALL($table = string, OPTIONAL: $options = [ fields ])
     *         Database::SELECT_ALL($table = string)
     * 
     *  @param mixed $columns The string or array of strings of columns
     *                        to return on a successful SELECT, will throw
     *                        an error if the column doesn't exist in the   
     *                        table given
     * 
     *  @param string $table  The string of a table to perform SELECT on,
     *                        will throw an error if given an invalid table 
     *  
     *  @param array $options The array of additional parameters to apply to
     *                        the SELECT query:
     *                      
     *                        'fetch' => Database::ONE : will only fetch one 
     *                                   row from the result set if given,  
     *                                   defaults to fetching all rows if omitted
     *                      
     *                        'condition' => The Condition object that specifies
     *                                       conditions for a WHERE clause in the
     *                                       SELECT query, must be an instance of
     *                                       the Condition class or will throw an error
     *          
     *                        'order_by' => The column to order the result set by,
     *                                      must be a valid column or will throw an
     *                                      error
     *  
     *                        'descending' => Boolean that determines if the ordered
     *                                        result set is in descending order
     * 
     *                        'limit_amount' => Positive int that determines the amount
     *                                          of rows to select in the SELECT query, 
     *                                          will also give the total existing rows 
     *                                          in the return value if set
     * 
     *                        'limit_start' => Positive int that determines where to
     *                                         begin the limit, requires a limit_amount
     *                                         to be set to do anything 
     * 
     *  @return array An array of query data: 
     * 
     *                'success' => Boolean if the query was successful
     * 
     *                'row' => The row of data if fetch => Database::ONE was set
     * 
     *                'rows' => The rows of data if fetch was not set
     * 
     *                'num_rows' => The number of rows found in the query
     * 
     *                'total_rows' => The total number of rows in the table 
     *                                if limit_amount was set   
     */
    public static final function SELECT($columns, $table, $options=[])
    {
            //  INITIALIZE OPTIONAL PARAMS
        $fetch        = isset($options['fetch'])      ? $options['fetch']     : null;
        $condition    = isset($options['condition'])  ? $options['condition'] : null;
        $order_by     = isset($options['order_by'])   ? trim($options['order_by']) : null;
        $descending   = isset($options['descending']) && 
                              $options['descending'] ? true : false;
        
        $limit_start  = isset($options['limit_start']) && 
                        is_numeric($options['limit_start']) ? 
                            abs($options['limit_start'])  : null;

        $limit_amount = isset($options['limit_amount']) && 
                        is_numeric($options['limit_amount']) ? 
                            abs($options['limit_amount']) : null;  

        $returnValues = ['success' => false];

        $validLimitStart  = isset($limit_start);
        $validLimitAmount = isset($limit_amount);

        $sql = Database::_buildSelect($columns, $table, $condition, 
                                      $order_by, $validLimitStart, 
                                      $validLimitAmount, $descending);

        $connection = Database::connect();

        try
        {
            $statement = $connection->prepare($sql);

                // IF CONDITION BIND THEM
            if(Database::_conditionGiven($condition))
            {
                Database::_bindConditions($statement, $condition);
                if($condition->getTable() != $table)
                    CustomError::throw("Table for condition doesn't 
                                        match condition for SELECT query.", 2);
            }

            if($validLimitAmount)
                $statement->bindValue(':limit_amount',  $limit_amount,  PDO::PARAM_INT);
            if($validLimitStart)
                $statement->bindValue(':limit_start',  $limit_start,  PDO::PARAM_INT); 

                //  EXECUTE STATEMENT IF SUCCESS SET RESULTS
            if($statement->execute())
            {
                $returnValues['success'] = true;
             
                if(isset($options['fetch']) && $options['fetch'] == Database::ONE)
                    $returnValues['row']  = $statement->fetch(PDO::FETCH_ASSOC);
                else
                    $returnValues['rows'] = $statement->fetchAll(PDO::FETCH_ASSOC);
                
                $returnValues['num_rows'] = $statement->rowCount();

                if(isset($limit_amount))
                {
                    $getTotalRows = 'SELECT found_rows() AS totalRows';
                    $returnValues['total_rows'] = $connection->query($getTotalRows)
                                                             ->fetch(PDO::FETCH_ASSOC)
                                                             ['totalRows'];
                }
            }

            Database::disconnect($connection);
        }
        catch(PDOException $e)
        {
            Database::disconnect($connection);
            CustomError::throw('Query failed: ' . $e->getMessage());
        }

        return $returnValues;
    }

    /**
     * jhkaefwe
     */
    public static final function SELECT_ALL($table, $options=[])
    {
        return Database::SELECT('*', $table, $options);
    }

    // ~~~~ INSERT ~~~~ //
    /**
     *  Attempts to perform an INSERT query to the database using the
     *  given table, columns, and their associated values. Will automatically
     *  bind values and check for errors to ensure robustness.
     * 
     *  Usage: Database::INSERT($table = string, $columns = [ strings ], $values = [ strings ])
     *         Database::INSERT($table = string, $columns = string, $values = string)
     * 
     *  @param string $table  The string of a table to INSERT into,
     *                        will throw an error if given an invalid table 
     * 
     *  @param mixed $columns The string or array of strings of columns
     *                        to insert values into, will throw an error if
     *                        the column doesn't exist in the table given or
     *                        if the number of values don't match
     * 
     *  @param mixed $values  The string or array of strings of values
     *                        to insert into the given columns, will throw
     *                        an error if the number of columns don't match
     * 
     *  @return array An array of query data: 
     * 
     *                'success' => Boolean if the query was successful
     * 
     *                'id' => id of the inserted row if successful  
     * 
     *                'num_rows' => The number of rows affected by the query   
     */
    public static final function INSERT($table, $columns, $values)
    {
        $returnValues = ['success' => false];

        if(!is_array($values)) $values = [$values];
        if(!is_array($columns)) $columns = [$columns];
        if(count($values) != count($columns))
            CustomError::throw("The number of columns and values
                                given in INSERT don't match.");

        $sql = Database::_buildInsert($table, $columns, $values);
    
        $connection = Database::connect();

        try
        {
            $statement = $connection->prepare($sql);

            $type;
            foreach($values as $i => $value)
            {   
                $type = Database::VALID_ENTRIES[$table][$columns[$i]]; 
                $statement->bindValue(':value_' . ($i + 1),  $value,  Database::PDO_PARAMS[$type]);
            }

            if($statement->execute())
            {
                $returnValues['id'] = $connection->lastInsertId();
                $returnValues['success'] = true;
            }

            $returnValues['num_rows'] = $statement->rowCount();
                
            Database::disconnect($connection);

        }
        catch(PDOException $e)
        {
            Database::disconnect($connection);

            if($e->getCode() == 23000)
                $returnValues['duplicate'] = true;
            else 
                CustomError::throw('Query failed: ' . $e->getMessage());
        }

        $returnValues['num_rows'] = $statement->rowCount();

        return $returnValues;
    }

    // ~~~~ DELETE ~~~~ //
    /**
     *  Attempts to perform a DELETE query on the database on the given
     *  table with the required conditions.
     * 
     *  Usage: Database::DELETE($table = string, $condition)
     * 
     *  @param string    $table     The string of a table to INSERT into,
     *                              will throw an error if given an invalid table 
     *  
     *  @param Condition $condition The Condition object that specifies
     *                              conditions for a WHERE clause in the
     *                              DELETE query, must be an instance of
     *                              the Condition class or will throw an error,
     *                              will also throw an error if not given a
     *                              condition  
     * 
     *  @return array An array of query data: 
     * 
     *                'success' => Boolean if the query was successful  
     * 
     *                'num_rows' => The number of rows affected by the query   
     */
    public static final function DELETE($table, $condition)
    {
        $returnValues = ['success' => false];

        $sql = Database::_buildDelete($table, $condition);

        $connection = Database::connect();

        try
        {
            $statement = $connection->prepare($sql);

                // IF CONDITION BIND THEM
            if(Database::_conditionGiven($condition))
            {
                Database::_bindConditions($statement, $condition);
                if($condition->getTable() != $table)
                    CustomError::throw("Table for condition doesn't 
                                        match condition for DELETE query.", 2);
            }
            else
                CustomError::throw("Need to give a condition for DELETE operations.");

            if($statement->execute())
            {
                $returnValues['success'] = true;
            } 

            $returnValues['num_rows'] = $statement->rowCount();
            
            Database::disconnect($connection);
        }
        catch(PDOException $e)
        {
            Database::disconnect($connection); 
            CustomError::throw('Query failed: ' . $e->getMessage());
        }

        return $returnValues;
    }

    // ~~~~ UPDATE ~~~~ //
    /**
     *  Attempts to perform an UPDATE query to the database using the
     *  given table, columns, their associated values and the required
     *  conditions. Will automatically bind values and check for errors 
     *  to ensure robustness.
     * 
     *  Usage: Database::UPDATE($table = string, $columns = [ strings ], $values = [ strings ], $condition = Condition)
     *         Database::UPDATE($table = string, $columns = string, $values = string, $condition = Condition)
     * 
     *  @param string    $table     The string of a table to INSERT into,
     *                              will throw an error if given an invalid table 
     * 
     *  @param mixed     $columns   The string or array of strings of columns
     *                              to insert values into, will throw an error if
     *                              the column doesn't exist in the table given or
     *                              if the number of values don't match
     * 
     *  @param mixed     $values    The string or array of strings of values
     *                              to insert into the given columns, will throw
     *                              an error if the number of columns don't match
     *  
     *  @param Condition $condition The required Condition object that specifies
     *                              conditions for a WHERE clause in the
     *                              UPDATE query, must be an instance of
     *                              the Condition class or will throw an error,
     *                              will also throw an error if not given a
     *                              condition
     * 
     *  @return array An array of query data: 
     * 
     *                'success' => Boolean if the query was successful
     * 
     *                'num_rows' => The number of rows affected by the query   
     */
    public static final function UPDATE($table, $columns, $values, $condition)
    {   
        $returnValues = ['success' => false];

        $sql = Database::_buildUpdate($table, $columns, $values, $condition);
        
        $connection = Database::connect();
        
        try
        {
            $statement = $connection->prepare($sql);
            
            if(!is_array($values)) $values = [$values];
            
            // FOR EACH VALUE BIND THEM
            $type;
            foreach($values as $i => $value)
            {   
                $type = Database::VALID_ENTRIES[$table][$columns[$i]]; 
                $statement->bindValue(':value_' . ($i + 1),  $value,  Database::PDO_PARAMS[$type]); 
            }

                // IF CONDITION BIND THEM
            if(Database::_conditionGiven($condition))
            {
                Database::_bindConditions($statement, $condition);
                if($condition->getTable() != $table)
                    CustomError::throw("Table for condition doesn't 
                                        match condition for UPDATE query.", 2);
            }
            else
                CustomError::throw("Need to give a condition for UPDATE operations.");

            if($statement->execute()) $returnValues['success'] = true;

            $returnValues['num_rows'] = $statement->rowCount();

            Database::disconnect($connection);
        }
        catch(PDOException $e)
        {
            Database::disconnect($connection);

            if($e->getCode() == 23000)
                $returnValues['duplicate'] = true;
            else
                CustomError::throw('Query failed: ' . $e->getMessage());
        }

        $returnValues['num_rows'] = $statement->rowCount();

        return $returnValues; 
    }

  //=========================================================//
 //                   PRIVATE HELPERS                       //
//=========================================================//

    private static final function _validateOrderBy(&$order_by, &$table)
    { 
        if(isset($order_by))
        {
            if(array_key_exists($order_by, Database::VALID_ENTRIES[$table]))
                return true;
            else
                CustomError::throw("Order by \"$order_by\" is not a 
                                    valid column in $table.");
        } 
        
        return false; 
    }

    private static final function _bindConditions(&$statement, &$condition)
    {
        $bindArguments = $condition->getBindsAndValues();
                
        foreach($bindArguments as $args)
        {
            $statement->bindValue($args['bind'],  $args['value'],  Database::PDO_PARAMS[$args['type']]);
        }
    }

    private static final function _validateCondition(&$condition)
    {
        if(isset($condition) && !($condition instanceof Condition))
            CustomError::throw("Given condition \"$condition\" is not a Condition object.", 3);
    }

    private static final function _conditionGiven(&$condition)
    {
        return isset($condition) && !empty($condition) && $condition instanceof Condition;
    }

    private static final function _buildSetValuePairs(&$columns, &$values, &$table)
    {
            //  IF BOTH ARRAY
        if(is_array($columns) && is_array($values))
        {
            if(count($columns) != count($values))
                CustomError::throw("Number of columns in \"$columns\" 
                does not match number of values in\"$values\"");
            else
            {
                $builtPairs = [];
                foreach($columns as $i => $col)
                {
                    if(!array_key_exists(trim($col), Database::VALID_ENTRIES[$table]))
                        CustomError::throw("\"$col\" is not a valid entry for \"$table\"", 2);

                    $builtPairs[] = trim($col) . ' = :value_' . ($i + 1);
                }
                return implode(', ', $builtPairs);
            }
        }
        else  // IF BOTH ATOMIC
        {
            return trim($columns) . ' = :value_1';
        }
    }

    private static final function _buildValues(&$values)
    {
        $builtValues = [];
        $count = count($values);

        for($i = 0; $i < $count; $i++)
        {
            $builtValues[] = ':value_' . ($i + 1);
        }

        return implode(', ', $builtValues);
    }

    private static final function _buildColumns(&$columns, &$table)
    {
        $builtColumns = [];
        foreach($columns as $col)
        {
            if(!array_key_exists(trim($col), Database::VALID_ENTRIES[$table]))
            {
                CustomError::throw("\"$col\" is not a valid column name in \"$table\"", 2);
            }
            $builtColumns[] = $col;
        }

        return implode(', ', $builtColumns); 
    }


  //=========================================================//
 //                 PRIVATE CRUD BUILDERS                   //
//=========================================================//

    // ~~~~ BUILD SELECT ~~~~ //
    private static final function _buildSelect(&$columns, &$table, &$condition, 
                                               &$order_by, &$limit_start, &$limit_amount,
                                               &$descending)
    {
        Database::validateTable($table);
        Database::_validateCondition($condition);
        $table = trim($table);

            //  CHECK ALL COLUMNS GIVEN ARE VALID
            //  AND BUILD THEM INTO STRING
        if(is_array($columns))
        {
            $columns = Database::_buildColumns($columns, $table);
        }
        else if(trim($columns) != '*' && !array_key_exists(trim($columns), Database::VALID_ENTRIES[$table]))
        {
            CustomError::throw("\"$columns\" is not a valid entry for \"$table\"", 2);
        }
        
        $columns = trim($columns);

        $sql = "SELECT " . ($limit_amount ? 'SQL_CALC_FOUND_ROWS' : '') 
                . "$columns FROM $table";

            //  CHECK AND APPEND CONDITION
        if(Database::_conditionGiven($condition))
            $sql .= " WHERE $condition";

            // CHECK AND APPEND ORDER BY
        if(Database::_validateOrderBy($order_by, $table))
        {
            $sql .= " ORDER BY $order_by";
            if($descending) $sql .= ' DESC';
        }

            //  CHECK AND APPEND LIMITS
        if($limit_amount)
        {
            $sql .= " LIMIT :limit_amount";
            if($limit_start) $sql .= " OFFSET :limit_start";
        }

        return $sql .= ';';
    }

    // ~~~~ BUILD INSERT ~~~~ //
    private static final function _buildInsert(&$table, &$columns, &$values)
    {
        Database::validateTable($table);
        $table = trim($table);

        $columnString = $columns;

        if(is_array($columns))
        {
            $columnString = Database::_buildColumns($columns, $table);
        }

        $valuesString = $values;

        if(is_array($values)) $valuesString = Database::_buildValues($values);

        $columnString = trim($columnString);
        $valuesString = trim($valuesString);

        return "INSERT INTO $table ( $columnString ) VALUES ( $valuesString );";
    }

    // ~~~~ BUILD DELETE ~~~~ //
    private static final function _buildDelete(&$table, &$condition)
    {
        Database::validateTable($table);
        Database::_validateCondition($condition);
        $table = trim($table);

        return "DELETE FROM $table" . 
                (Database::_conditionGiven($condition) ? " WHERE $condition;" : ';');
    }

    // ~~~~ BUILD UPDATE ~~~~ //
    private static final function _buildUpdate(&$table, &$columns, &$values, &$condition)
    {
        Database::validateTable($table);
        Database::_validateCondition($condition);
        $table = trim($table);

        $setValues = Database::_buildSetValuePairs($columns, $values, $table);

        return "UPDATE $table SET $setValues" . 
            (Database::_conditionGiven($condition) ? " WHERE $condition;" : ';');
    }

  //=========================================================//
 //                 CONNECTION HANDLING                     //
//=========================================================//

    private static final function connect()
    {
        try
        {
            $connection = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
            $connection->setAttribute(PDO::ATTR_PERSISTENT, true);
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $err)
        {
            CustomError::throw("Connection failed: " . $err->getMessage(), 2);
        }

        return $connection;
    }

    private static final function disconnect(&$connection)
    {
        unset($connection);
    }    
}