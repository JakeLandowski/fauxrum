<?php
/**
 *  Class for representing conditions in SQL WHERE clauses. 
 */

/**
 *  Represents a condition for a WHERE clause and is created
 *  using method chaining. Can also compose together multiple
 *  Condition objects for more complex conditions.
 *  
 *  @author Jacob Landowski
 */
class Condition
{
    private $_expression = 
    [
        'columns'      => [],
        'comparisons'  => [],
        'values'       => [],
        'allValues'    => [],
        'logical'      => [],
        'others'       => [],
        'binds'        => []
    ];

    private $_otherJoinType  = '';
    private $_numColumns     = 0;
    private $_numComparisons = 0;
    private $_numValues      = 0;
    private $_numLogical     = 0;
    private $_state          = 'columns';
    private $_nextType       = '';
    private $_table;
     
  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//

    /**
     *  Starts a Condition object and its method chaining.
     * 
     *  Usage: $condition = (new Condition( string $table ))->col( string $column )->operator( mixed $value );
     *         ~ or ~
     *         $condition = new Condition( string $table); 
     *         $condition->col( string $column )->equals( mixed $value );
     * 
     *  @param string $table The string of a table to create a condition for,
     *                       this is used to automatically set the correct
     *                       bind types for future values given and check columns
     *                       given, will throw an error if given an invalid table 
     * 
     *  @return Condition $this returns this Condition object for further methods   
     */
    public function __construct($table)
    {
        Database::validateTable($table);
        $this->_table = trim($table);
        return $this;
    }

    /**
     *  Chooses a column to compare to a future value. Will throw an error if on
     *  the wrong stage for this Condition, ex: Calling col() twice in a row.
     * 
     *  @param string $column The string of a column to set, throws an error
     *                        if the column is not a valid column for the table
     *                        this condition is configured for
     * 
     *  @return Condition $this returns this Condition object for further methods   
     */
    public function col($column)
    {
        if($this->_state == 'comparisons') 
            CustomError::throw("Tried to set column \"$column\", expected a comparison.", 2);
        else if($this->_state == 'values') 
            CustomError::throw("Tried to set column \"$column\", expected a value.", 2);
        else if(!Database::isValidColumn($column)) 
            CustomError::throw("Tried to set and invalid column \"$column\".", 2);
        else
        {
            $this->_numColumns++;
            $this->_expression['columns'][] = trim($column);
            $this->_state = 'comparisons';
            $this->_nextType = Database::VALID_ENTRIES[$this->_table][$column];
            return $this; 
        }
    }

    /**
     *  Sets the last set column equal to the given value. Throws an error if on
     *  the wrong stage for this Condition, ex: Calling equals() twice in a row.
     * 
     *  @param string $value The value to set the previous column equal to
     * 
     *  @return Condition $this returns this Condition object for further methods   
     */
    public function equals($value)
    {
        $this->_comparison('<=>');
        $this->_val($value);
        return $this;
    }

    /**
     *  Sets the last set column not equal to the given value. Throws an error if on
     *  the wrong stage for this Condition, ex: Calling notEquals() twice in a row.
     * 
     *  @param string $value The value to set the previous column not equal to
     * 
     *  @return Condition $this returns this Condition object for further methods   
     */
    public function notEquals($value)
    {
        $this->_comparison('<>');
        $this->_val($value);
        return $this;
    }

    /**
     *  Sets the last set column to be less than the given value. Throws an error if on
     *  the wrong stage for this Condition, ex: Calling lessThan() twice in a row.
     * 
     *  @param string $value The value to set the previous column less than
     * 
     *  @return Condition $this returns this Condition object for further methods   
     */
    public function lessThan($value)
    {
        $this->_comparison('<');
        $this->_val($value);
        return $this;
    }

    /**
     *  Sets the last set column to be greater than the given value. Throws an error if on
     *  the wrong stage for this Condition, ex: Calling greaterThan() twice in a row.
     * 
     *  @param string $value The value to set the previous column greater than
     * 
     *  @return Condition $this returns this Condition object for further methods   
     */
    public function greaterThan($value)
    {
        $this->_comparison('>');
        $this->_val($value);
        return $this;
    }

    /**
     *  Sets the last set column to be less than or equal to the given value. 
     *  Throws an error if on the wrong stage for this Condition, ex: Calling
     *  lessThanOrEquals() twice in a row.
     * 
     *  @param string $value The value to set the previous column less than or equals to
     * 
     *  @return Condition $this returns this Condition object for further methods   
     */
    public function lessThanOrEquals($value)
    {
        $this->_comparison('<=');
        $this->_val($value);
        return $this;
    }

    /**
     *  Sets the last set column to be greater than or equal to the given value. 
     *  Throws an error if on the wrong stage for this Condition, ex: Calling
     *  greaterThanOrEquals() twice in a row.
     * 
     *  @param string $value The value to set the previous column greater than or equals to
     * 
     *  @return Condition $this returns this Condition object for further methods   
     */
    public function greaterThanOrEquals($value)
    {
        $this->_comparison('>=');
        $this->_val($value);
        return $this;
    }

    /**
     *  Sets the last set column to be LIKE the given value for wildcard searching. 
     *  Throws an error if on the wrong stage for this Condition, ex: Calling
     *  like() twice in a row.
     * 
     *  @param string $value The value to set the previous column to be LIKE
     * 
     *  @return Condition $this returns this Condition object for further methods   
     */
    public function like($value)
    {
        $this->_comparison('LIKE');
        $this->_val($value);
        return $this;
    }

    /**
     *  If given no parameters, sets the stage of this Condition to accept
     *  another column/value pair of conditions to be wrapped within the 
     *  same parenthesis. If given a Condition object, composes that Condition
     *  with this one in separate parenthesis. ANDs the 2 conditions.
     * 
     *  @param Condition $otherCondition
     * 
     *  @return Condition $this returns this Condition object for further methods   
     */
    public function and($otherCondition=null)
    {
        if(isset($otherCondition) && !($otherCondition instanceof Condition))
            CustomError::throw("Value \"$otherCondition\" given for and() needs to 
                                be another Condition object.");
        $this->_logical($otherCondition, 'AND');
        return $this;
    }

    /**
     *  If given no parameters, sets the stage of this Condition to accept
     *  another column/value pair of conditions to be wrapped within the 
     *  same parenthesis. If given a Condition object, composes that Condition
     *  with this one in separate parenthesis. ORs the 2 conditions.
     * 
     *  @param Condition $otherCondition
     * 
     *  @return Condition $this returns this Condition object for further methods   
     */
    public function or($otherCondition=null)
    {
        if(isset($otherCondition) && !($otherCondition instanceof Condition))
            CustomError::throw("Value \"$otherCondition\" given for or() needs to 
                                be another Condition object.");
        $this->_logical($otherCondition, 'OR');
        return $this;
    }

    /**
     *  Returns an array of all compiled values, bind placeholders, and their types
     *  to be used in binding values to a PDO object. Used by Database class.
     * 
     *  @return array The array of associative arrays each holding 'binds', 'value',
     *                and 'type'   
     */
    public function getBindsAndValues()
    {
        if(!$this->_hasBeenRendered())
            CustomError::throw("Cannot resolve values, this condition is incomplete, 
                                or binds don't match values. This is most likely 
                                because the condition hasn't been rendered yet to 
                                create a sql string.", 2);
        else
        {
            $count = count($this->_expression['binds']);

            $args = [];

            for($i = 0; $i < $count; $i++)
            {
                $args[] = 
                        [
                            'bind'  => $this->_expression['binds'][$i],
                            'value' => $this->_expression['allValues'][$i]['value'],
                            'type'  => $this->_expression['allValues'][$i]['type']  
                        ];
            }

            return $args;
        } 
    }

    /**
     *  Created and returns a string to represent this Condition object to be used 
     *  for constructing a sql query. Compiles all composed conditions underneath this
     *  Condition. Used by the Database class.
     * 
     *  @return string The string representing this Condition object   
     */
    public function __toString()
    {
        return $this->_bindChunks($this->_render());
    }

    /**
     *  Gets the table this Condition was configured for.
     * 
     *  @return string The string table this Condition is configured for   
     */
    public function getTable()
    {
        return $this->_table;
    }

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

    private function _isComplete()
    {
        return $this->_numColumns != 0 & 
               $this->_numColumns == $this->_numComparisons &&
               $this->_numComparisons == $this->_numValues &&
               $this->_numLogical == $this->_numColumns - 1;
    }

    private function _hasBeenRendered()
    {
        $numValues = count($this->_expression['allValues']);

        return $this->_isComplete() && $numValues > 0 && 
               $numValues == count($this->_expression['binds']);
    }

    private function _bindChunks($chunks)
    {
        $this->_expression['binds'] = [];
        $numValues = 1;
        $bind;
        $str = '';

        foreach($chunks as $chunk)
        {
            if($chunk == null)
            {
                $bind = ':cond_value_' . $numValues++;
                $this->_expression['binds'][] = $bind;
                $str .= $bind;
            }     
            else
            {
                $str .= $chunk; 
            }
        }

        $this->_compileAllValues();

        return $str;
    }

    private function _compileAllValues()
    {
        $allValues = $this->_expression['values'];

        foreach($this->_expression['others'] as $other)
        {
            $other->_compileAllValues();
            $allValues = array_merge($allValues, $other->_expression['allValues']);
        }

        $this->_expression['allValues'] = $allValues;
    }

    private function _render()
    {
        if(!$this->_isComplete()) 
            CustomError::throw("Tried to render incomplete Condition.");

        $chunks = [];

        $chunks[] = '(';

        $count = count($this->_expression['columns']);

        for($i = 0; $i < $count; $i++)
        { 
            $chunks[] = $this->_expression['columns'][$i] . ' ';
            $chunks[] = $this->_expression['comparisons'][$i] . ' ';
            $chunks[] = null; 
            if($i < $count - 1) $chunks[] = ' ' . $this->_expression['logical'][$i] . ' ';
        }

        $chunks[] = ')'; 

        foreach($this->_expression['others'] as $other)
        {
            $chunks[] = " $other->_otherJoinType ";
            $chunks = array_merge($chunks, $other->_render());
        }

        return $chunks;
    }

    private function _logical($other, $type)
    {
        if($this->_isComplete())
        {
            if($other)
            {
                if(!$other->_isComplete())
                {
                    CustomError::throw("Tried to \"$type\" incomplete condition: \"$other\"");
                }
                else
                {
                    $other->_otherJoinType = $type;
                    $this->_expression['others'][] = $other;
                }
            }
            else
            {
                $this->_numLogical++;
                $this->_expression['logical'][] = "$type";
            }
        }
        else
        {
            CustomError::throw("Tried to \"$type\" an empty condition 
            or incomplete condition with state: \"$this->_state\"", 2);
        }
    }

    private function _comparison($comparison)
    {
        if($this->_state == 'columns') 
            CustomError::throw("Tried to set comparison \"$comparison\", expected a column.", 2);
        else if($this->_state == 'values') 
            CustomError::throw("Tried to set comparison \"$comparison\", expected a value.", 2);
        else
        {
            $this->_numComparisons++;
            $this->_expression['comparisons'][] = trim($comparison);
            $this->_state = 'values';
        }
    }

    private function _val($value)
    {
        if($this->_state == 'columns') 
            CustomError::throw("Tried to set value \"$value\", expected a column.", 2);
        else if($this->_state == 'comparisons') 
            CustomError::throw("Tried to set value \"$value\", expected a comparison.", 2);
        else
        {
            $this->_numValues++;                           
            $this->_expression['values'][] = ['type' => $this->_nextType,  
                                              'value' => trim($value)];
            $this->_state = 'columns';
        }
    }
}