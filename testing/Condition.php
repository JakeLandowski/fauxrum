<?php

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

    public function __construct($table)
    {
        Database::validateTable($table);
        $this->_table = $table;
        return $this;
    }

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

    public function equals($value)
    {
        $this->_comparison('=');
        $this->_val($value);
        return $this;
    }

    public function notEquals($value)
    {
        $this->_comparison('<>');
        $this->_val($value);
        return $this;
    }

    public function lessThan($value)
    {
        $this->_comparison('<');
        $this->_val($value);
        return $this;
    }

    public function greaterThan($value)
    {
        $this->_comparison('>');
        $this->_val($value);
        return $this;
    }

    public function lessThanOrEquals($value)
    {
        $this->_comparison('<=');
        $this->_val($value);
        return $this;
    }

    public function greaterThanOrEquals($value)
    {
        $this->_comparison('>=');
        $this->_val($value);
        return $this;
    }

    public function like($value)
    {
        $this->_comparison('LIKE');
        $this->_val($value);
        return $this;
    }

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

    public function __toString()
    {
        return $this->_bindChunks($this->_render());
    }

    public function isComplete()
    {
        return $this->_numColumns != 0 & 
               $this->_numColumns == $this->_numComparisons &&
               $this->_numComparisons == $this->_numValues &&
               $this->_numLogical == $this->_numColumns - 1;
    }

    public function and($otherCondition=null)
    {
        $this->_logical($otherCondition, 'AND');
        return $this;
    }

    public function or($otherCondition=null)
    {
        $this->_logical($otherCondition, 'OR');
        return $this;
    }

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

    private function _hasBeenRendered()
    {
        $numValues = count($this->_expression['allValues']);

        return $this->isComplete() && $numValues > 0 && 
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
        if(!$this->isComplete()) 
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
        if($this->isComplete())
        {
            if($other)
            {
                if(!$other->isComplete())
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