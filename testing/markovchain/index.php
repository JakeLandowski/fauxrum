<?php
/**
 * 
 */

/**
 * 
 */
class MarkovMap
{
    private $_signature = 0;
    private $_map = [];
    private $_order = 5; // 3 is the lowest we can go while still making sense

    public function parseText(&$text)
    {
        $len = strlen($text);
        $gram;
        $charAfter;
        $offset;
        $wrapAmt;

        for($i = 0; $i < $len; $i++)
        {
            $offset = $len - 1 - $i;

            if($offset < $this->_order)
            {
                $wrapAmt = $this->_order - $offset - 1;
                $gram = substr($text, $i, $this->_order - $wrapAmt);
                $gram .= substr($text, 0, $wrapAmt);
                $charAfter = substr($text, $wrapAmt, 1);
            }
            else
            {
                $gram = substr($text, $i, $this->_order);
                $charAfter = substr($text, $i + $this->_order, 1);
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

    private function _cumulateProbabilities()
    {
        $_cum = [];
        $acc;

        foreach($this->_map as $gram => $charSet)
        {
            $acc = 0;

            foreach($charSet as $char => $count)
            {
                $acc += $count;
                $_cum[$gram]['probs'][$acc] = $char;
            }

            $_cum[$gram]['max'] = $acc;
        }

        return $_cum;
    } 

    public function generate()
    {
        $_cum = $this->_cumulateProbabilities();

        if(empty($this->_map)) return '';

        $seed = array_rand($this->_map);
        $gram;
        $point;
        
        for($i = 0; $i < 2000; $i++)
        {
            $gram = substr($seed, $i, $i + $this->_order);
            if(!isset($_cum[$gram])) { echo 'No key found' . '<br>'; return $seed; }
            $point = rand(0, $_cum[$gram]['max']);
            $seed .= $this->findFirstRange($point, $_cum[$gram]['probs']);
        }

        return $seed;
    }

    private function findFirstRange(&$index, &$array)
    {
        foreach($array as $range => $element)
        {
            if($index <= $range) return $element;
        }

        return '';
    }
}