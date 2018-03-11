<?php

require_once getenv('HOME') . '/db_configs/fauxrum_config.php';

abstract class Database
{
    // const EVERYTHING = 'everything';
    const ONE        = 'one';

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
                'created'       => 'string', 
                'bot_generated' => 'int' 
            ],
        'Post' => 
            [ 
                'id'            => 'int', 
                'thread'        => 'int',   
                'owner'         => 'int', 
                'created'       => 'string', 
                'bot_generated' => 'int', 
                'content'       => 'string', 
                'is_root_post'  => 'int' 
            ],
        'TextMap' =>
            [ 
                'id'       => 'int', 
                'map_data' => 'lob', 
                'owner'    => 'int' 
            ]
    ];
    
    const PDO_PARAMS = 
    [
        'int'    => PDO::PARAM_INT,
        'string' => PDO::PARAM_STR,
        'lob'    => PDO::PARAM_LOB
    ];

  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//

    public static final function isValidColumn($column)
    {
        foreach(Database::VALID_ENTRIES as $table)
        {
            if(array_key_exists($column, $table)) return true;
        }

        return false; 
    }

    public static final function validateTable(&$table)
    {
        if(!array_key_exists(trim($table), Database::VALID_ENTRIES))
        {
            CustomError::throw("\"$table\" is not a valid table in the Database.", 2);
        }
    }

    public static final function SELECT($columns, $table, $options=[])//$condition=null, $get=Database::EVERYTHING)
    {
            //  INITIALIZE OPTIONAL PARAMS
        $fetch        = isset($options['fetch'])      ? $options['fetch']     : null;
        $condition    = isset($options['condition'])  ? $options['condition'] : null;
        $order_by     = isset($options['order_by'])   ? trim($options['order_by']) : null;
        $descending   = isset($options['descending']) && 
                              $options['descending'] ? true : false;
        
        $limit_start  = isset($options['limit_start']) && 
                        is_numeric($options['limit_start']) 
                        ? abs($options['limit_start'])  : null;

        $limit_amount = isset($options['limit_amount']) && 
                        is_numeric($options['limit_amount']) 
                        ? abs($options['limit_amount']) : null;  

        $returnValues = ['success' => false];

        $validOrderBy     = Database::_validateOrderBy($order_by, $table);
        $validLimitStart  = isset($limit_start);
        $validLimitAmount = isset($limit_amount);

        $sql = Database::_buildSelect($columns, $table, $condition, 
                                      $validOrderBy, $validLimitStart, 
                                      $validLimitAmount, $descending);

        echo $sql;

        $connection = Database::connect();

        try
        {
            $statement = $connection->prepare($sql);

                // IF CONDITION BIND THEM
            if(Database::_conditionGiven($condition))
                Database::_bindConditions($statement, $condition);

            if($validOrderBy)
                $statement->bindValue(':order_by',  $order_by,  PDO::PARAM_STR);
            if($validLimitAmount)
                $statement->bindValue(':limit_amount',  $limit_amount,  PDO::PARAM_INT);
            if($validLimitStart)
                $statement->bindValue(':limit_start',  $limit_start,  PDO::PARAM_INT); 

            if($statement->execute())
            {
                $returnValues['success'] = true;
             
                if(isset($options['FETCH']) && $options['FETCH'] == Database::ONE)
                    $returnValues['row']  = $statement->fetch(PDO::FETCH_ASSOC);
                else
                    $returnValues['rows'] = $statement->fetchAll(PDO::FETCH_ASSOC);
                
                $returnValues['num_rows'] = $statement->rowCount();

                if(isset($limit_amount))
                {
                    $getTotalRows = 'SELECT found_rows() AS totalRows';
                    $returnValues['total_rows'] = $connection->query($getTotalRows)
                                                             ->fetch(PDO::FETCH_ASSOC);
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

    public static final function SELECT_ALL($table, $condition=null, $options=[])//$get=Database::EVERYTHING)
    {
        return Database::SELECT('*', $table, $condition, $options);//$get);
    }

    public static final function INSERT($table, $columns, $values)
    {
        $returnValues = ['success' => false];

        if(!is_array($values)) $values = [$values];

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

        return $returnValues;
    }

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
                Database::_bindConditions($statement, $condition);
            else
                CustomError::throw("Need to give a condition for DELETE operations.");

            if($statement->execute())
            {
                $returnValues['success'] = true;
                $returnValues['num_rows'] = $statement->rowCount();
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
                Database::_bindConditions($statement, $condition);

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

        return $returnValues; 
    }

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
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

    private static final function _buildSelect(&$columns, &$table, &$condition, 
                                               &$order_by, &$limit_start, &$limit_amount,
                                               &$descending)
    {
        $table = trim($table);
        Database::validateTable($table);
        Database::_validateCondition($condition);

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
        if($order_by)
        {
            $sql .= " ORDER BY :order_by";
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

    private static final function _buildInsert(&$table, &$columns, &$values)
    {
        $table = trim($table);
        Database::validateTable($table);

        $columnString = $columns;

        if(is_array($columns))
        {
            $columnString = Database::_buildColumns($columns, $table);
        }
        else if(!array_key_exists(trim($columns), Database::VALID_ENTRIES[$table]))
        {
            CustomError::throw("\"$columns\" is not a valid entry for \"$table\"", 2);
        }

        $valuesString = $valuesString;

        if(is_array($values)) $valuesString = Database::_buildValues($values);

        $columnString = trim($columnString);
        $valuesString = trim($valuesString);

        return "INSERT INTO $table ( $columnString ) VALUES ( $valuesString );";
    }

    private static final function _buildDelete(&$table, &$condition)
    {
        $table = trim($table);
        Database::validateTable($table);
        Database::_validateCondition($condition);

        return "DELETE FROM $table" . 
                (Database::_conditionGiven($condition) ? " WHERE $condition;" : ';');
    }

    private static final function _buildUpdate(&$table, &$columns, &$values, &$condition)
    {
        $table = trim($table);
        Database::validateTable($table);
        Database::_validateCondition($condition);

        $setValues = Database::_buildSetValuePairs($columns, $values, $table);

        return "UPDATE $table SET $setValues" . 
            (Database::_conditionGiven($condition) ? " WHERE $condition;" : ';');
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
        foreach($columns as $col)
        {
            if(!array_key_exists(trim($col), Database::VALID_ENTRIES[$table]))
            {
                CustomError::throw("\"$col\" is not a valid column name in \"$table\"", 2);
            }
        }

        return implode(', ', $columns); 
    }

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