<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$text = "Hash Tables are symbol tables that turn a key into a 32-bit integer representation of that key in order to use it as an index in a plain array structure. This gives you constant access times, which is extremely good speed. The downside is that in order to hash an object you have made, you need to override and define a solid hashing function that can uniquely convert your object into a number, it also needs to do so consistently, and you waste a bit of memory initially. One way to do this is to accumulate your object's fields and multiply them by prime numbers, giving them more chances of dividing into unique indices later. Another factor in creating unique hashes is trying to make use of all of an int's 32 bits, and not disregarding the least significant or most significant bits. Once you have your hashing function, you then just modulus by the container size, or better yet, the largest prime of that size, this is how you convert your large hash to a usable index. There are 2 type of hash table storage techniques, separate chaining, where each slot of the table is a linked list, and when multiple objects fall on the same index (a collision) you instead append it to the linked list, and dynamically grow that slot. The second type is linear probing, where you place the element at the next available slot when it falls on an already filled slot. One part that was confusing to me was at the start of the chapter, the author gives examples of hashing functions that involves a variable R, but it never explains what R represents afaik, so I have no idea how to interpret the algorithm it gives. int hash = (((day * R + month) % M) * R + year) % M; Also as far as separate chaining go, I know one benefit is how easy it can grow without having to resize, but wouldn't another good option be just more hash tables in each index instead of linked lists? Since it's standard to have excess storage anyway and you can resize them along with resizing the primary array, this would be really fast if you had a lot of collisions.";

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


require_once "../runTimeStart.php";

$markov = new MarkovMap;
$markov->parseText($text);
echo $markov->generate();

require_once "../runTimeEnd.php";