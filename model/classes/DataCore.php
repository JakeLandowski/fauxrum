<?php
/**
 *  Simple Data Object to hold whitelisted data. 
 *  To be extended by other classes.  
 */

/**
 *  Simple Data Object to hold whitelisted data. 
 *  To be extended by other classes.
 *  
 *  @author Jacob Landowski
 */
abstract class DataCore
{    
    protected $data = [];

    /**
     *  Initializes the object by adding all of the fields
     *  in given data to internal data by checking the keys
     *  and whitelisting them.
     * 
     *  @param $data The array of data to add to this object
     */
    public function __construct(&$data=[])
    {
        foreach($data as $key => $value)
        {
            $this->setValue($key, $value);
        }
    }

    /** 
     *  Assigns the key and value to the internal data if the key exists
     *  already.
     *  
     *  @param $key   The key to the data, usually the table column name
     *  @param $value The value associated with the key
     */
    public function setValue($key, $value)
    {
        if(array_key_exists($key, $this->data))
        {
            $this->data[$key] = $value;
            return true;
        }

        return false;
    }

    /**
     *  Retrieves the raw data stored internally from the key index.
     *  
     *  @param $key   The key to the data, usually the table column name
     *  @return mixed The data to return from the internal data
     */
    public function getValue($key)
    {
        if(array_key_exists($key, $this->data))
            return $this->data[$key];
        
        return null;
    }

    /**
     *  Retrieves the escaped data stored internally from the key index.
     *  
     *  @param $key   The key to the data, usually the table column name 
     *  @return mixed The data to return from the internal data
     */
    public function displayValue($key)
    {
        $value = $this->getValue($key);

        if(is_string($value)) return htmlspecialchars($value);

        return $value; 
    }
}