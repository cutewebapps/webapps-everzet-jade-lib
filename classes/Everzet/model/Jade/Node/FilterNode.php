<?php

/*
 * This file is part of the Jade.php.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Filter Node. 
 */
class Everzet_Jade_Node_FilterNode extends Everzet_Jade_Node_Node
{
    protected $name;
    protected $attributes = array();
    protected $block;

    /**
     * Initialize Filter node. 
     * 
     * @param   string  $name       filter name
     * @param   array   $attributes filter attributes
     * @param   integer $line       source line
     */
    public function __construct($name, array $attributes = array(), $line =  0)
    {
        parent::__construct($line);

        $this->name         = $name;
        $this->attributes   = $attributes;
    }

    /**
     * Set block node to filter. 
     * 
     * @param   Everzet_Jade_Node_BlockNode|Everzet_Jade_Node_TextNode  $node   filtering node
     */
    public function setBlock(Everzet_Jade_Node_Node $node)
    {
        $this->block = $node;
    }

    /**
     * Return block node to filter. 
     * 
     * @return  Everzet_Jade_Node_BlockNode|Everzet_Jade_Node_TextNode
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * Return filter name. 
     * 
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return attributes array 
     * 
     * @return  array               associative array of attributes
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}
