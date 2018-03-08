<?php

class Condition
{
    const NUM_UNIQUE_VALUES = 100;

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
        others       => [],
        binds        => []
    ];

    private $_otherJoinType  = '';
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

    public function getValues()
    {
        if(!$this->isComplete() && count($this->_expression['values']) 
                                != count($this->_expression['binds']))
            CustomError::throw("Cannot resolve values, this condition is incomplete, or binds don't match values.");
        else
        {
            $allValues = $this->_expression['values'];

            foreach($this->_expression['others'] as $other)
            {
                $allValues = array_merge($allValues, $other->getValues());
            }

            return $allValues;
        } 
    }

    public function getBinds()
    {
        if(!$this->isComplete() && count($this->_expression['values']) 
                                != count($this->_expression['binds']))
            CustomError::throw("Cannot resolve binds, this condition is incomplete or values don't match binds.");
        else 
            return $this->_expression['binds'];
    }

    public function __toString()
    {
        return $this->_bindChunks($this->_render());
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
                $bind = ':value_' . $numValues++;
                $this->_expression['binds'][] = $bind;
                $str .= $bind;
            }     
            else
            {
                $str .= $chunk; 
            }
        }

        return $str;
    }

    private function _render()
    {
        if(!$this->isComplete()) return '';

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