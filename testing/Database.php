<?php

require_once getenv('HOME') . '/db_configs/fauxrum_config.php';

abstract class Database
{
    const EVERYTHING = 'everything';
    const ONE        = 'one';

    const VALID_ENTRIES = 
    [
        'User'    => [ 'id', 'username', 'email', 'password' ],
        'Thread'  => [ 'id', 'title',    'owner', 'created', 'bot_generated' ],
        'Post'    => [ 'id', 'thread',   'owner', 'created', 'bot_generated', 'content', 'is_root_post' ],
        'TextMap' => [ 'id', 'map_data', 'owner' ]
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
            if(in_array($column, $table)) return true;
        }

        return false; 
    }

    public static final function SELECT($columns, $table, $condition=null, $get=Database::EVERYTHING)
    {
        $sql = Database::buildSelect($columns, $table, $condition);

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

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

    private static final function _conditionGiven(&$condition)
    {
        return isset($condition) && !empty($condition) && $condition instanceof Condition;
    }

    private static final function buildSelect(&$columns, &$table, &$condition)
    {
        Database::validateTable($table);

        if(is_array($columns))
        {
            $columns = Database::buildColumns($columns, $table);
        }
        else if(trim($columns) != '*' && !in_array(trim($columns), Database::VALID_ENTRIES[$table]))
        {
            CustomError::throw("\"$columns\" is not a valid entry for \"$table\"", 2);
        }
        
        $columns   = trim($columns);
        $table     = trim($table);

        if(Database::_conditionGiven($condition))
            $sql .= " WHERE $condition";

        return "SELECT $columns FROM $table" . ';';
    }

    private static final function validateTable(&$table)
    {
        if(!array_key_exists(trim($table), Database::VALID_ENTRIES))
        {
            CustomError::throw("\"$table\" is not a valid table in the Database.", 2);
        }
    }

    private static final function buildColumns(&$columns, &$table)
    {
        foreach($columns as $col)
        {
            if(!in_array(trim($col), Database::VALID_ENTRIES[$table]))
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