<?php
/**
 * 
 */

/**
 * 
 */
class TextMap
{
    private $_id;
    private $_signature = 0;
    private $_lastSignature = 0;
    private $_map = [];
    private $_weighted = [];
    private $_order;
    private $_toMarkLater = ['threads' => [], 'posts' => []];

    public function __construct($order=5)
    {
             if($order < 0)  $order = 0;
        else if($order > 10) $order = 10;
        $this->_order = $order;
    }

    public function markAsParsedLater($which, $id)
    {
        if(array_key_exists($which, $this->_toMarkLater))
        {
            $this->_toMarkLater[$which][] = $id;
        }
        else
        {
            CustomError::throw("$which given in markAsParsedLater() needs 
                               to be \'threads\' or \'posts\'", 2);
        }
    }

    public function getToMarkLater()
    {
        return $this->_toMarkLater;
    }

    public function setId($id)
    {
        $this->_id = $id > 0 ? $id : null;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function parseText(&$text)
    {
        if(!isset($text)) return '';
        $text = trim($text);

            //  If text too small to parse/wrap skip it
        if(!empty($text) && ((strlen($text) * 2) - 1 > $this->_order))
        {
             // Add padding to separate end from start when wrapping
            if(substr($text, -1) != ' ') $text .= ' ';
            $gram;            // The substring key to log "the "he_" "e_r", etc...
            $charAfter;       // The character to log for an N gram
            $grabAmount = 0;  // Amount of text to grab for an N gram, 0 up to order
            $offset = 0;      // When to start wrapping
            $wrapAmount = 0;  // How much to wrap around to the start
            $len = strlen($text);

            for($i = 0; $i < $len; $i++)
            {
                $offset = $len - 1 - $i;

                //~~~ Parsing Stuff ~~~//
                if($offset < $grabAmount) // WRAPPING
                {
                    $wrapAmount = $grabAmount - $offset - 1;
                    $gram = substr($text, $i, $grabAmount - $wrapAmount); // Grab from end
                    $gram .= substr($text, 0, $wrapAmount);     // Grab from start too
                    $charAfter = $this->_filterChar(substr($text, $wrapAmount, 1)); // Grab char following
                    $text[$wrapAmount] = $charAfter; // Erase crappy char to not break later iterations 
                }
                else // NO WRAP
                {
                    $gram = substr($text, $i, $grabAmount); // Grab N Gram key
                    $charAfter = substr($text, $i + $grabAmount, 1); // Grab char following it
                    $text[$i + $grabAmount] = $charAfter; // Erase crappy char to not break later iterations
                }
                
                    // Ramp up grab amount from 0 up to order
                if($grabAmount < $this->_order) 
                {
                    $i--;   // Hold loop position until full order
                    $grabAmount++;
                }
                

                //~~~ Map Stuff ~~~//
                if(!array_key_exists($gram, $this->_map)) // If gram doesn't exist yet
                {
                    $this->_map[$gram] = [];  // Create char map at this gram
                } 
                
                if(!isset($this->_map[$gram][$charAfter])) // if char not found
                {
                    $this->_map[$gram][$charAfter] = 1; // create char at 1
                } 
                else
                {
                    $this->_map[$gram][$charAfter]++; // else increment char count
                }

                if($this->_signature > 2000000000)
                    $this->_signature = -$this->_signature;
                else 
                    $this->_signature++;
            }
        }
    }

    public function parseSentences(&$text)
    {
        $sentences = preg_split('/([^.!?]+[.!?]+)/', $text, -1,  PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    
        foreach($sentences as $sentence)
            $this->parseText($sentence);
    }

    public function generate($size=100, $cap=1000)
    {
        if(empty($this->_map)) return '';

             if($cap < $this->_order * 2) $cap = $this->_order * 2;
        else if($cap > 3000) $cap = 3000;

        if($this->_lastSignature != $this->_signature)
            $this->_weighProbabilities();

        $size -= $this->_order; // offset because $i is offset
        $seed = '';
        $gram;        // Current substring key to lookup char possibilities 
        $newChar;     // New char to append to our string
        $charIndex;   // Random number picked to find a char
        $i = 0 - $this->_order; // Start at negative order to start search at empty string
        
        while($i < $size && $size < $cap)
        {
            if($i < 0) $gram = substr($seed, 0); // while seed not big enough grab all
            else $gram = substr($seed, $i, $i + $this->_order); // grab order length and move;

                if(!isset($this->_weighted[$gram])) { echo 'No key found' . '<br>'; return $seed; }

            $charIndex = rand(0, $this->_weighted[$gram]['max']);
            $newChar = $this->_findFirstRange($charIndex, $this->_weighted[$gram]['probs']);
            if(empty($newChar)) return $seed;

            $seed .= $newChar;

            $i++;

            //  Don't stop generating til you hit punctuation.
            if(($i == $size && $newChar == ' ') || 
               ($i == $size && 
                $newChar != '.' && 
                $newChar != '!' && 
                $newChar != '?'))
            {
                $size++;
            }
        }
        
        return $seed;
    }
    
    /** Original NGram map:
     * 
     * [
     *   'the' = 
     *          [
     *              c = 1,
     *              d = 3, 
     *            ' ' = 7,
     *              e = 2
     *          ]
     * ]
     * 
     * Will turn into weighted map:
     * 
     * [
     *   'the' = 
     *          [
     *              probs = 
     *                      [
     *                          1 = c,
     *                          4 = d, 
     *                         11 = ' ',
     *                         13 = e
     *                      ],
     * 
     *              max = 13
     *          ]
     * ]
     * 
     *  Then pick a random number from 0 => max and find the first
     *  range it falls under for a weighted random choice.
     */
    private function _weighProbabilities()
    {
        $acc;

        foreach($this->_map as $gram => $charSet)
        {
            $acc = 0;

            foreach($charSet as $char => $count)
            {
                $acc += $count;
                $this->_weighted[$gram]['probs'][$acc] = $char;
            }

            $this->_weighted[$gram]['max'] = $acc;
        }

        $this->_lastSignature = $this->_signature;
    }
    
    
    private function _findFirstRange(&$index, &$array)
    {
        foreach($array as $range => $element)
        {
            if($index <= $range) return $element;
        }

        return '';
    }

    private function _filterChar($char)
    {
        if(ctype_cntrl($char)) return ' ';
        if(ord($char) > 126 || ord($char) < 32) return '~';
        if(ctype_print($char)) return $char;
        return '~';
    }
}