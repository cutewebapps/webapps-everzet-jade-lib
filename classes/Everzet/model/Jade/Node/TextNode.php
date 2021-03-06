<?php

/*
 * This file is part of the Jade.php.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Text Node. 
 */
class Everzet_Jade_Node_TextNode extends Everzet_Jade_Node_Node
{
    protected $lines = array();

    /**
     * Initialize text node with string. 
     * 
     * @param   string|null $string text
     * @param   integer     $line   source line
     */
    public function __construct($string = null, $line = 0 )
    {
        parent::__construct($line);

        if (!empty($string)) {
            $this->lines = explode("_n", $string);
        }
    }

    /**
     * Add text line to node. 
     * 
     * @param   string  $line   string line
     */
    public function addLine($line)
    {
        $this->lines[] = $line;
    }

    /**
     * Return text lines. 
     * 
     * @return  array           array of strings
     */
    public function getLines()
    {
        return $this->lines;
    }
}
