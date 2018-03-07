<?php

class Condition
{
    private $_expression = 
    [
        columns     => [],
        comparisons => [],
        values      => [],
        logical     => [],
        AND_other    => [],
        OR_other     => []
    ];

    private $_numColumns     = 0;
    private $_numComparisons = 0;
    private $_numValues      = 0;
    private $_numLogical     = 0;
    private $_state = 'columns';
     
  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//

    public function __construct()
    {
        return $this;
    }

    public function col($column)
    {
        if($this->_state == 'comparisons') 
            CustomError::throw("Tried to set column {$column}, expected a comparison.");
        else if($this->_state == 'values') 
            CustomError::throw("Tried to set column {$column}, expected a value.");
        else
        {
            $this->_numColumns++;
            $this->_expression[$this->_state][] = trim($column);
            $this->_state = 'comparisons';
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

    public function getExpression()
    {
        return $this->_expression;
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
        if(!$otherCondition) return $this;
    }

    public function or($otherCondition=null)
    {
        $this->_logical($otherCondition, 'OR');
        if(!$otherCondition) return $this;
    }

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

    private function _logical($other, $type)
    {
        if($this->isComplete())
        {
            if($otherCondition)
            {
                if(!$otherCondition->isComplete())
                {
                    CustomError::throw("Tried to $type incomplete condition: $otherCondition");
                }
                else
                {
                    $this->_numLogical++;
                    $this->_expression["{$type}_other"][] = $otherCondition;
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
            CustomError::throw("Tried to $type an empty condition 
            or incomplete condition with state: $this->_state");
        }
    }

    private function _comparison($comparison)
    {
        if($this->_state == 'columns') 
            CustomError::throw("Tried to set comparison {$comparison}, expected a column.");
        else if($this->_state == 'values') 
            CustomError::throw("Tried to set comparison {$comparison}, expected a value.");
        else
        {
            $this->_numComparisons++;
            $this->_expression[$this->_state][] = trim($comparison);
            $this->_state = 'values';
        }
    }

    private function _val($value)
    {
        if($this->_state == 'columns') 
            CustomError::throw("Tried to set value {$value}, expected a column.");
        else if($this->_state == 'comparisons') 
            CustomError::throw("Tried to set value {$value}, expected a comparison.");
        else
        {
            $this->_numValues++;
            $this->_expression[$this->_state][] = trim($value);
            $this->_state = 'columns';
        }
    }
}