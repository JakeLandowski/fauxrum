<?php
/**
 * 
 */

/**
 * 
 */
class TextMap
{
    private $_signature = 0;
    private $_map = [];
    private $_order = 3; // 3 is the lowest we can go while still making sense

    public function parseText(&$text)
    {
        $text .= ' ';
        $len = strlen($text);
        $grabAmt = 0;
        $gram;
        $charAfter;
        $offset;
        $wrapAmt;

        for($i = 0; $i < $len; $i++)
        {
            $offset = $len - 1 - $i;

            if($offset < $grabAmt)
            {
                $wrapAmt = $grabAmt - $offset - 1;
                $gram = substr($text, $i, $grabAmt - $wrapAmt);
                $gram .= substr($text, 0, $wrapAmt);
                $charAfter = substr($text, $wrapAmt, 1);

            }
            else
            {
                $gram = substr($text, $i, $grabAmt);
                $charAfter = substr($text, $i + $grabAmt, 1);
            }
            
            
            if($grabAmt < $this->_order) 
            {
                $i--;
                $grabAmt++;
            }
            
            if(!array_key_exists($gram, $this->_map))
            {
                $this->_map[$gram] = []; 
            } 
            
            if(!isset($this->_map[$gram][$charAfter]))
            {
                $this->_map[$gram][$charAfter] = 1;
                $this->_signature++;
            } 
            else
            {
                $this->_map[$gram][$charAfter]++;
                $this->_signature++;
            }
        }
    }

    public function generate($length=100)
    {
        $weighted = $this->_weighProbabilities();

        if(empty($this->_map)) return '';

        $seed = '';
        $gram;
        $point;
        
        for($i = 0 - $this->_order; $i < $length; $i++)
        {
            if($i < 0) $gram = substr($seed, 0); // while seed not big enough grab all
            else $gram = substr($seed, $i, $i + $this->_order); // grab order length and move;

                if(!isset($weighted[$gram])) { echo 'No key found' . '<br>'; return $seed; }

            $point = rand(0, $weighted[$gram]['max']);
            $seed .= $this->_findFirstRange($point, $weighted[$gram]['probs']);
        }
        
        return $seed;
    }
    
    private function _weighProbabilities()
    {
        $weighted = [];
        $acc;

        foreach($this->_map as $gram => $charSet)
        {
            $acc = 0;

            foreach($charSet as $char => $count)
            {
                $acc += $count;
                $weighted[$gram]['probs'][$acc] = $char;
            }

            $weighted[$gram]['max'] = $acc;
        }

        return $weighted;
    }
    
    
    private function _findFirstRange(&$index, &$array)
    {
        foreach($array as $range => $element)
        {
            if($index <= $range) return $element;
        }

        return '';
    }
}