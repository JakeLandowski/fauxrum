<?php

require_once getenv('HOME') . '/db_configs/fauxrum_config.php';

abstract class Database
{
    const EVERYTHING = 'everything';
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

    public static final function SELECT($columns, $table, $condition=null, $get=Database::EVERYTHING)
    {
        $sql = Database::_buildSelect($columns, $table, $condition);

        echo $sql;

        $connection = Database::connect();

        try
        {
            $statement = $connection->prepare($sql);

            if(Database::_conditionGiven($condition))
            {
                $bindArguments = $condition->getBindsAndValues();
                
                foreach($bindArguments as $args)
                {
                    $statement->bindValue($args['bind'],  $args['value'],  Database::PDO_PARAMS[$args['type']]);
                }
            }

            $statement->execute();

            if($get == Database::EVERYTHING)
                $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            else if($get == Database::ONE)
                $rows = $statement->fetch(PDO::FETCH_ASSOC);

            Database::disconnect($connection);

            return $rows;
        }
        catch(PDOException $e)
        {
            Database::disconnect($connection);
            CustomError::throw('Query failed: ' . $e->getMessage());
        }
    }

    public static final function SELECT_ALL($table, $condition=null, $get=Database::EVERYTHING)
    {
        return Database::SELECT('*', $table, $condition, $get);
    }

    public static final function INSERT($table, $columns, $values)
    {
        if(!is_array($values)) CustomError::throw("\"$values\" given needs to be an array of type and value");

        $sql = Database::_buildInsert($table, $columns, $values);

        echo $sql;

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

            $statement->execute();

            $id = $connection->lastInsertId();

            Database::disconnect($connection);

            return $id; // returns 0 if fail I believe
        }
        catch(PDOException $e)
        {
            Database::disconnect($connection);
            CustomError::throw('Query failed: ' . $e->getMessage());
        }
    }

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

    private static final function _conditionGiven(&$condition)
    {
        return isset($condition) && !empty($condition) && $condition instanceof Condition;
    }

    private static final function _buildSelect(&$columns, &$table, &$condition)
    {
        Database::validateTable($table);

        if(is_array($columns))
        {
            $columns = Database::_buildColumns($columns, $table);
        }
        else if(trim($columns) != '*' && !array_key_exists(trim($columns), Database::VALID_ENTRIES[$table]))
        {
            CustomError::throw("\"$columns\" is not a valid entry for \"$table\"", 2);
        }
        
        $columns   = trim($columns);
        $table     = trim($table);

        return "SELECT $columns FROM $table" . 
                (Database::_conditionGiven($condition) ? " WHERE $condition;" : ';');
    }

    private static final function _buildInsert(&$table, &$columns, &$values)
    {
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
        $table        = trim($table);
        $valuesString = trim($valuesString);

        return "INSERT INTO $table ( $columnString ) VALUES ( $valuesString )" . ';';
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