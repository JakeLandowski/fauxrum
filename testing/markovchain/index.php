<?php

$text = "Hash Tables are symbol tables that turn a key into a 32-bit integer representation of that key in order to use it as an index in a plain array structure. This gives you constant access times, which is extremely good speed. The downside is that in order to hash an object you have made, you need to override and define a solid hashing function that can uniquely convert your object into a number, it also needs to do so consistently, and you waste a bit of memory initially. One way to do this is to accumulate your object's fields and multiply them by prime numbers, giving them more chances of dividing into unique indices later. Another factor in creating unique hashes is trying to make use of all of an int's 32 bits, and not disregarding the least significant or most significant bits. Once you have your hashing function, you then just modulus by the container size, or better yet, the largest prime of that size, this is how you convert your large hash to a usable index. There are 2 type of hash table storage techniques, separate chaining, where each slot of the table is a linked list, and when multiple objects fall on the same index (a collision) you instead append it to the linked list, and dynamically grow that slot. The second type is linear probing, where you place the element at the next available slot when it falls on an already filled slot. One part that was confusing to me was at the start of the chapter, the author gives examples of hashing functions that involves a variable R, but it never explains what R represents afaik, so I have no idea how to interpret the algorithm it gives. int hash = (((day * R + month) % M) * R + year) % M; Also as far as separate chaining go, I know one benefit is how easy it can grow without having to resize, but wouldn't another good option be just more hash tables in each index instead of linked lists? Since it's standard to have excess storage anyway and you can resize them along with resizing the primary array, this would be really fast if you had a lot of collisions.";
$text2 = "This what they all been waitin' for
I guess so
They been waitin' for this shit for a long time didn’t they
I'ma give it everythin' I got
Ayo Dougie park that X6 around the corner
Ayy I'm just feelin' my vibe right now
I'm feelin' myself
Panda, Panda
Panda, Panda, Panda, Panda, Panda
I got broads in Atlanta
Twistin' dope, lean, and the Fanta
Credit cards and the scammers
Hittin' off licks in the bando
Black X6, Phantom
White X6 looks like a panda
Goin' out like I'm Montana
Hundred killers, hundred hammers
Black X6, Phantom
White X6, panda
Pockets swole, Danny
Sellin' bar, candy
Man I'm the macho like Randy
The choppa go Oscar for Grammy
Bitch nigga pull up ya panty
Hope you killas understand me
I got broads in Atlanta
Twistin' dope, lean, and the Fanta
Credit cards and the scammers
Hittin' off licks in the bando
Black X6, Phantom
White X6 looks like a panda
Goin' out like I'm Montana
Hundred killers, hundred hammers
Black X6, Phantom
White X6, panda
Pockets swole, Danny
Sellin' bar, candy
Man I'm the macho like Randy
The choppa go Oscar for Grammy
Bitch nigga pull up ya panty
Hope you killas understand me
Hey
Panda, Panda
Panda, Panda, Panda, Panda, Panda, Panda, Panda, Panda
I got broads in Atlanta
Twistin' dope, lean, and shit, sippin' Fanta
Credit cards and the scammers
Wake up Versace shit, life Desiigner
Whole bunch of lavish shit
They be askin' 'round town who be clappin' shit
I be pullin' up stuff in the Phantom ship
I got plenty of stuff of Bugatti whip look how I drive this shit
Black X6, Phantom
White X6, killin' on camera
Pop a Perc, I can't stand up
Gorilla, they come and kill you with bananas
Four fillas, they finna pull up in the Phantom
Know niggas, they come and kill you on the camera
Big Rollie, it dancin' bigger than a Pandie
Go Oscar for Grammy, bitch pull up your panty
Fill up I'ma flip it, I got bitches pull up and they get it
I got niggas that's countin' for digits
Say you make you a lot of new money
Know some killers pull off and they in the Wraith
CDG, they pull off and they kill the Bape
Call up Phillip-Phillip, gon' fill the bank
Niggas up in the bank, we gon' drill the bank
Fuck we gon' kill the bank, get it
I got broads, yea I get it
I get cards yea I shitted
This how I live it
Did it all for a ticket
Now Flex drop bombs when he spin it
And Bobby gon' trend it
Jeff The Don doin' business
Zana Ray fuckin' up shit and she doin' her bidnezz
I be gettin' to the chicken
Countin' to the chicken
And all of my niggas gon' split it
Panda, Panda
Panda, Panda, Panda, Panda, Panda
I got broads in Atlanta
Twistin' dope, lean, and the Fanta
Credit cards and the scammers
Hittin' off licks in the bando
Black X6, Phantom
White X6 looks like a panda
Goin' out like I'm Montana
Hundred killers, hundred hammers
Black X6, Phantom
White X6, panda
Pockets swole, Danny
Sellin' bar, candy
Man I'm the macho like Randy
The choppa go Oscar for Grammy
Bitch nigga pull up ya panty
Hope you killas understand me
I got broads in Atlanta
Twistin' dope, lean, and the Fanta
Credit cards and the scammers
Hittin' off licks in the bando
Black X6, Phantom
White X6 looks like a panda
Goin' out like I’m Montana
Hundred killers, hundred hammers
Black X6, Phantom
White X6, panda
Pockets swole, Danny
Sellin' bar, candy
Man I'm the macho like Randy
The choppa go Oscar for Grammy
Bitch nigga pull up ya panty
Hope you killas understand me
Panda, Panda
Panda, Panda, Panda, Panda, Panda";

class MarkovMap
{
    private $_map = [];
    private $_cum = [];
    private $_order = 3; // 3 is the lowest we can go while still making sense

    public function parseText(&$text)
    {
        $len = strlen($text);
        $gram;
        $charAfter;

        for($i = 0; $i < $len - $this->_order; $i++)
        {
            $gram = substr($text, $i, $this->_order);
            $charAfter = substr($text, $i + $this->_order, 1);

            if(!array_key_exists($gram, $this->_map)) 
                $this->_map[$gram] = [];

            if(!isset($this->_map[$gram][$charAfter])) 
                $this->_map[$gram][$charAfter] = 1;
            else
                $this->_map[$gram][$charAfter]++;
        }

        // echo '<pre>';
        // print_r($this->_map);
        // echo '</pre>';
    }

    public function cumulateProbabilities()
    {
        $acc;

        foreach($this->_map as $gram => $charSet)
        {
            $acc = 0;

            foreach($charSet as $char => $count)
            {
                $acc += $count;
                $this->_cum[$gram]['probs'][$acc] = $char;
            }

            $this->_cum[$gram]['max'] = $acc;
        }

        // echo '<pre>';
        // print_r($this->_cum);
        // echo '</pre>';
    } 

    public function generate()
    {
        $seed = array_rand($this->_map);
        $gram;
        $point;
        
        for($i = 0; $i < 2000; $i++)
        {
            $gram = substr($seed, $i, $i + $this->_order);
            $point = rand(0, $this->_cum[$gram]['max']);
            $seed .= $this->findFirstRange($point, $this->_cum[$gram]['probs']);
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

// Script start
$rustart = getrusage();




$markov = new MarkovMap;

$markov->parseText($text2);
$markov->cumulateProbabilities();

echo $markov->generate();

// Script end
function rutime($ru, $rus, $index) {
    return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
     -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
}

$ru = getrusage();
echo "<br/><br/>This process used " . rutime($ru, $rustart, "utime") .
    " ms for its computations\n";
echo "It spent " . rutime($ru, $rustart, "stime") .
    " ms in system calls\n";
