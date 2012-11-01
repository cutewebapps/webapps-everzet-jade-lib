<?php


/*
 * This file is part of the Jade.php.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Dumper Interface. 
 */
interface Everzet_Jade_Dumper_DumperInterface
{
    /**
     * Dump node to string.
     * 
     * @param   BlockNode   $node   root node
     *
     * @return  string
     */
    public function dump(Everzet_Jade_Node_BlockNode $node);

    /**
     * Register visitee extension. 
     * 
     * @param   string              $nodeName   name of the visitable node (block, code, comment, doctype, filter, tag, text)
     * @param   Everzet_Jade_Visitor_VisitorInterface    $visitor    visitor object
     */
    public function registerVisitor($nodeName, Everzet_Jade_Visitor_VisitorInterface $visitor);
}
