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

  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//

    public static final function SELECT($columns, $table, $condition='', $get=Database::EVERYTHING)
    {
        Database::validateTable($table);
        $sql = Database::buildSelect($columns, $table, $condition);        

        $connection = Database::connect();

        try
        {
            $statement = $connection->prepare($sql);
            // $statement->bindValue(':start',  $start,  PDO::PARAM_INT);
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
            die('Query failed: ' . $e->getMessage());
        }
    }

    public static final function SELECT_ALL($table, $condition='', $get=Database::EVERYTHING)
    {
        return Database::SELECT('*', $table, $condition, $get);
    }

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

    private static final function buildSelect(&$columns, &$table, &$condition)
    {
        Database::validateTable($table);

        if(is_array($columns))
        {
            $columns = Database::buildColumns($columns, $table);
        }

        else if(trim($columns) != '*' && !in_array(trim($columns), Database::VALID_ENTRIES[$table]))
        {
            die("$columns is not a valid entry for $table");
        }
        
        $columns   = trim($columns);
        $table     = trim($table);
        $condition = trim($condition);

        return "SELECT $columns FROM $table" . (empty($condition) ? '' : " WHERE $condition") . ';';
    }

    private static final function validateTable(&$table)
    {
        if(!array_key_exists(trim($table), Database::VALID_ENTRIES))
        {
            die("$table is not a valid table in the Database.");
        }
    }

    private static final function buildColumns(&$columns, &$table)
    {
        $columnString = '';
        foreach($columns as $col)
        {
            if(!in_array(trim($col), Database::VALID_ENTRIES[$table]))
            {
                die("$col is not a valid column name in $table");
            }
            
            $columnString .= $col . ' ';
        }
        return $columnString; 
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
            die("Connection failed: " . $err->getMessage());
        }

        return $connection;
    }

    private static final function disconnect(&$connection)
    {
        unset($connection);
    }    
}