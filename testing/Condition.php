<?php

class Condition
{
    const VALID_TYPES =
    [
        'string', 'int', 'lob'
    ];

    private $_expression = 
    [
        columns      => [],
        comparisons  => [],
        values       => [],
        logical      => [],
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
            $this->_expression['columns'][] = trim($column);
            $this->_state = 'comparisons';
            // $this->_expression['logical'][] = '';
            return $this; 
        }
    }

    public function equals($type, $value)
    {
        $this->_comparison('=');
        $this->_val($type, $value);
        return $this;
    }

    public function notEquals($type, $value)
    {
        $this->_comparison('<>');
        $this->_val($type, $value);
        return $this;
    }

    public function lessThan($type, $value)
    {
        $this->_comparison('<');
        $this->_val($type, $value);
        return $this;
    }

    public function greaterThan($type, $value)
    {
        $this->_comparison('>');
        $this->_val($type, $value);
        return $this;
    }

    public function lessThanOrEquals($type, $value)
    {
        $this->_comparison('<=');
        $this->_val($type, $value);
        return $this;
    }

    public function greaterThanOrEquals($type, $value)
    {
        $this->_comparison('>=');
        $this->_val($type, $value);
        return $this;
    }

    public function like($value)
    {
        $this->_comparison('LIKE');
        $this->_val('string', $value);
        return $this;
    }

    public function getExpression()
    {
        if(!$this->isComplete())
            CustomError::throw("Cannot resolve expression, this condition is incomplete.");
        else 
            return $this->_expression;
    }

    public function __toString()
    {
        if(!$this->isComplete()) return '';

        $str = '(';
        $count = count($this->_expression['columns']);

        for($i = 0; $i < $count; $i++)
        {
            $str .= $this->_expression['columns'][$i] . ' ';
            $str .= $this->_expression['comparisons'][$i] . ' ';
            $str .= ':' . $this->_expression['values'][$i]['value'];
            if($i > 0) $str .= $this->_expression['logical'][$i - 1];
        }

        $str .= ')'; 

        foreach($this->_expression['AND_other'] as $other)
        {
            $str .= ' AND ' . $other;
        }

        foreach($this->_expression['OR_other'] as $other)
        {
            $str .= ' OR ' . $other;
        }

        return $str;
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
    }

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

    private function _logical($other, $type)
    {
        if($this->isComplete())
        {
            if($other)
            {
                if(!$other->isComplete())
                {
                    CustomError::throw("Tried to $type incomplete condition: $other");
                }
                else
                {
                    // $this->_numLogical++;
                    $this->_expression["{$type}_other"][] = $other;
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
            $this->_expression['comparisons'][] = trim($comparison);
            $this->_state = 'values';
        }
    }

    private function _val($type, $value)
    {
        if($this->_state == 'columns') 
            CustomError::throw("Tried to set value $type {$value}, expected a column.");
        else if($this->_state == 'comparisons') 
            CustomError::throw("Tried to set value $type {$value}, expected a comparison.");
        else if(!in_array($type, Condition::VALID_TYPES))
            CustomError::throw("Invalid type $type given for value.");
        else
        {
            $this->_numValues++;
            $this->_expression['values'][] = ['type' => strtolower(trim($type)), 
                                              'value' => trim($value)];
            $this->_state = 'columns';
        }
    }
}