<?php


/*
 * This file is part of the Jade.php.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Code Node. 
 */
class Everzet_Jade_Node_CodeNode extends Everzet_Jade_Node_Node
{
    protected $code;
    protected $buffering = false;
    protected $block;

    /**
     * Initialize code node. 
     * 
     * @param   string  $code       code string
     * @param   boolean $buffering  turn on buffering
     * @param   integer $line       source line
     */
    public function __construct($code, $buffering = false, $line = 0)
    {
        parent::__construct($line);

        $this->code         = $code;
        $this->buffering    = $buffering;
    }

    /**
     * Return code string. 
     * 
     * @return  string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Return true if code buffered. 
     * 
     * @return  boolean
     */
    public function isBuffered()
    {
        return $this->buffering;
    }

    /**
     * Set block node. 
     * 
     * @param   Everzet_Jade_Node_BlockNode   $node   child node
     */
    public function setBlock(Everzet_Jade_Node_BlockNode $node)
    {
        $this->block = $node;
    }

    /**
     * Return block node. 
     * 
     * @return  Everzet_Jade_Node_BlockNode
     */
    public function getBlock()
    {
        return $this->block;
    }
}
