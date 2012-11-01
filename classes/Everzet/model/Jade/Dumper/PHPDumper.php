<?php

/*
 * This file is part of the Jade.php.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Jade -> PHP template dumper. 
 */
class Everzet_Jade_Dumper_PHPDumper implements Everzet_Jade_Dumper_DumperInterface
{
    protected $doctypes = array(
        '5'             => '<!DOCTYPE html>',
        'xml'           => '<?xml version="1.0" encoding="utf-8" ?>',
        'default'       => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
        'transitional'  => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
        'strict'        => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
        'frameset'      => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
        '1.1'           => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
        'basic'         => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
        'mobile'        => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">'
    );
    protected $selfClosing = array('meta', 'img', 'link', 'br', 'hr', 'input', 'area', 'base');
    protected $codes = array(
        "/^ *if[ \(]+.*\: *$/"        => 'endif',
        "/^ *else *\: *$/"            => 'endif',
        "/^ *else *if[ \(]+.*\: *$/"  => 'endif',
        "/^ *while *.*\: *$/"         => 'endwhile',
        "/^ *for[ \(]+.*\: *$/"       => 'endfor',
        "/^ *foreach[ \(]+.*\: *$/"   => 'endforeach',
        "/^ *switch[ \(]+.*\: *$/"    => 'endswitch',
        "/^ *case *.* *\: *$/"        => 'break'
    );
    protected $nextIsIf = array();
    protected $visitors = array(
        'code'      => array()
      , 'comment'   => array()
      , 'doctype'   => array()
      , 'filter'    => array()
      , 'tag'       => array()
      , 'text'      => array()
    );
    protected $filters = array();

    /**
     * Dump node to string.
     * 
     * @param   Everzet_Jade_Node_BlockNode   $node   root node
     *
     * @return  string
     */
    public function dump(Everzet_Jade_Node_BlockNode $node)
    {
        return $this->dumpNode($node);
    }

    /**
     * Register visitee extension. 
     * 
     * @param   string              $name       name of the visitable node (code, comment, doctype, filter, tag, text)
     * @param   Everzet_Jade_Visitor_VisitorInterface    $visitor    visitor object
     */
    public function registerVisitor($name, Everzet_Jade_Visitor_VisitorInterface $visitor)
    {
        $names = array_keys($this->visitors);

        if (!in_array($name, $names)) {
            throw new InvalidArgumentException(sprintf('Unsupported node type given "%s". Use %s.',
                $name, implode(', ', $names)
            ));
        }

        $this->visitors[$name][] = $visitor;
        return $this;
    }

    /**
     * Register filter on dumper. 
     * 
     * @param   string          $alias  filter alias (:javascript for example)
     * @param   Everzet_Jade_Filter_FilterInterface $filter filter
     */
    public function registerFilter($alias, Everzet_Jade_Filter_FilterInterface $filter)
    {
        if (isset($this->filters[$alias])) {
            throw new InvalidArgumentException(sprintf('Filter with alias %s is already registered', $alias));
        }

        $this->filters[$alias] = $filter;
        return $this;
    }

    /**
     * Dump node to string. 
     * 
     * @param   Everzet_Jade_Node_Node    $node   node to dump
     * @param   integer $level  indentation level
     *
     * @return  string
     */
    protected function dumpNode(Everzet_Jade_Node_Node $node, $level = 0)
    {
        $dumper = 'dump' . basename(str_replace('_', '/', get_class($node)), 'Node');

        return $this->$dumper($node, $level);
    }

    /**
     * Dump block node to string. 
     * 
     * @param   Everzet_Jade_Node_BlockNode   $node   block node
     * @param   integer     $level  indentation level
     *
     * @return  string
     */
    protected function dumpBlock(Everzet_Jade_Node_BlockNode $node, $level = 0)
    {
        $html = '';
        $last = '';

        $childs = $node->getChilds();
        foreach ($childs as $i => $child) {
            if (!empty($html) && !empty($last)) {
                $html .= "\n";
            }

            $this->nextIsIf[$level] = isset($childs[$i + 1]) && ($childs[$i + 1] instanceof Everzet_Jade_Node_CodeNode);
            $last  = $this->dumpNode($child, $level);
            $html .= $last;
        }

        return $html;
    }

    /**
     * Dump doctype node. 
     * 
     * @param   Everzet_Jade_Node_DoctypeNode $node   doctype node
     * @param   integer     $level  indentation level
     *
     * @return  string
     */
    protected function dumpDoctype(Everzet_Jade_Node_DoctypeNode $node, $level = 0)
    {
        foreach ($this->visitors['doctype'] as $visitor) {
            $visitor->visit($node);
        }

        if (!isset($this->doctypes[$node->getVersion()])) {
            throw new Everzet_Jade_Exception(sprintf('Unknown doctype %s', $node->getVersion()));
        }

        return $this->doctypes[$node->getVersion()];
    }

    /**
     * Dump tag node. 
     * 
     * @param   Everzet_Jade_Node_TagNode $node   tag node
     * @param   integer $level  indentation level
     *
     * @return  string
     */
    protected function dumpTag(Everzet_Jade_Node_TagNode $node, $level = 0)
    {
        $html = str_repeat('  ', $level);

        foreach ($this->visitors['tag'] as $visitor) {
            $visitor->visit($node);
        }

        if (in_array($node->getName(), $this->selfClosing)) {
            $html .= '<' . $node->getName();
            $html .= $this->dumpAttributes($node->getAttributes());
            $html .= ' />';

            return $html;
        } else {
            if (count($node->getAttributes())) {
                $html .= '<' . $node->getName();
                $html .= $this->dumpAttributes($node->getAttributes());
                $html .= '>';
            } else {
                $html .= '<' . $node->getName() . '>';
            }

            if ($node->getCode()) {
                if (count($node->getChilds())) {
                    $html .= "\n" . str_repeat('  ', $level + 1) . $this->dumpCode($node->getCode());
                } else {
                    $html .= $this->dumpCode($node->getCode());
                }
            }
            if ($node->getText() && count($node->getText()->getLines())) {
                if (count($node->getChilds())) {
                    $html .= "\n" . str_repeat('  ', $level + 1) . $this->dumpText($node->getText());
                } else {
                    $html .= $this->dumpText($node->getText());
                }
            }

            if (count($node->getChilds())) {
                $html .= "\n";
                $childs = $node->getChilds();
                foreach ($childs as $i => $child) {
                    $this->nextIsIf[$level + 1] = isset($childs[$i + 1]) && ($childs[$i + 1] instanceof Everzet_Jade_Node_CodeNode);
                    $html .= $this->dumpNode($child, $level + 1);
                }
                $html .= "\n" . str_repeat('  ', $level);
            }

            return $html . '</' . $node->getName() . '>';
        }
    }

    /**
     * Dump text node. 
     * 
     * @param   Everzet_Jade_Node_TextNode    $node   text node
     * @param   integer     $level  indentation level
     * 
     * @return  string
     */
    protected function dumpText(Everzet_Jade_Node_TextNode $node, $level = 0)
    {
        $indent = str_repeat('  ', $level);

        foreach ($this->visitors['text'] as $visitor) {
            $visitor->visit($node);
        }

        return $indent . $this->replaceHolders(implode("\n" . $indent, $node->getLines()));
    }

    /**
     * Dump comment node. 
     * 
     * @param   Everzet_Jade_Node_CommentNode $node   comment node
     * @param   integer     $level  indentation level
     * 
     * @return  string
     */
    protected function dumpComment(Everzet_Jade_Node_CommentNode $node, $level = 0)
    {
        foreach ($this->visitors['comment'] as $visitor) {
            $visitor->visit($node);
        }

        if ($node->isBuffered()) {
            $html = str_repeat('  ', $level);

            if ($node->getBlock()) {
                $string = $node->getString();
                $beg    = "<!--\n";
                $end    = "\n" . str_repeat('  ', $level) . '-->';

                if (preg_match('/^\[ *if/', $string)) {
                    $beg = '<!--' . $string . ">\n";
                    $end = "\n" . str_repeat('  ', $level) . '<![endif]-->';
                    $string = '';
                }

                $html .= $beg;
                if ('' !== $string) {
                    $html .= str_repeat('  ', $level + 1) . $string . "\n";
                }
                $html .= $this->dumpBlock($node->getBlock(), $level + 1);
                $html .= $end;
            } else {
                $html = str_repeat('  ', $level) . '<!-- ' . $node->getString() . ' -->';
            }

            return $html;
        } else {
            return '';
        }
    }

    /**
     * Dump code node. 
     * 
     * @param   Everzet_Jade_Node_CodeNode    $node   code node
     * @param   integer     $level  indentation level
     *
     * @return  string
     */
    protected function dumpCode(Everzet_Jade_Node_CodeNode $node, $level = 0)
    {
        $html = str_repeat('  ', $level);

        foreach ($this->visitors['code'] as $visitor) {
            $visitor->visit($node);
        }

        if ($node->getBlock()) {
            if ($node->isBuffered()) {
                $begin = '<?php echo ' . preg_replace('/^ +/', '', $node->getCode()) . " { ?>\n";
            } else {
                $begin = '<?php ' . preg_replace('/^ +/', '', $node->getCode()) . " { ?>\n";
            }
            $end = "\n" . str_repeat('  ', $level) . '<?php } ?>';

            foreach ($this->codes as $regex => $ending) {
                if (preg_match($regex, $node->getCode())) {
                    $begin  = '<?php ' . preg_replace('/^ +| +$/', '', $node->getCode()) . " ?>\n";
                    $end    = "\n" . str_repeat('  ', $level) . '<?php ' . $ending . '; ?>';
                    if ('endif' === $ending && isset($this->nextIsIf[$level]) && $this->nextIsIf[$level]) {
                        $end = '';
                    }
                    break;
                }
            }

            $html .= $begin;
            $html .= $this->dumpNode($node->getBlock(), $level + 1);
            $html .= $end;
        } else {
            if ($node->isBuffered()) {
                $html .= '<?php echo ' . preg_replace('/^ +/', '', $node->getCode()) . ' ?>';
            } else {
                $html .= '<?php ' . preg_replace('/^ +/', '', $node->getCode()) . ' ?>';
            }
        }

        return $html;
    }

    /**
     * Dump filter node. 
     * 
     * @param   Everzet_Jade_Node_FilterNode  $node   filter node
     * @param   integer     $level  indentation level
     * 
     * @return  string
     */
    protected function dumpFilter(Everzet_Jade_Node_FilterNode $node, $level = 0)
    {
        if (!isset($this->filters[$node->getName()])) {
            throw new Everzet_Jade_Exception(sprintf('Filter with alias "%s" is not registered.', $node->getName()));
        }

        $text = '';
        if ($node->getBlock()) {
            $text = $this->dumpNode($node->getBlock(), $level + 1);
        }

        return $this->filters[$node->getName()]->filter($text, $node->getAttributes(), str_repeat('  ', $level));
    }

    /**
     * Dump attributes. 
     * 
     * @param   array   $attributes attributes associative array
     * 
     * @return  string
     */
    protected function dumpAttributes(array $attributes)
    {
        $items = array();

        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $items[] = $key . '="' . $this->replaceHolders(htmlspecialchars(implode(' ', $value)), true) . '"';
            } elseif (true === $value) {
                $items[] = $key . '="' . $key . '"';
            } elseif (false !== $value) {
                $items[] = $key . '="' . $this->replaceHolders(htmlspecialchars($value), true) . '"';
            }
        }

        return count($items) ? ' ' . implode(' ', $items) : '';
    }

    /**
     * Replace tokenized PHP string in text. 
     * 
     * @param   string  $string text
     * @param   boolean $decode decode HTML entitied
     *
     * @return  string
     */
    protected function replaceHolders($string, $decode = false)
    {
        if ( $decode ) {
            return preg_replace_callback("/{{((?!}}).*)}}/simU", 'Everzet_Jade_Dumper_PHPDumper::decode', $string);
        } else {
            return preg_replace_callback("/{{((?!}}).*)}}/simU", 'Everzet_Jade_Dumper_PHPDumper::nodecode', $string);
        }
    }

    public static function decode( $matches )
    {
        return sprintf('<'.'?php echo %s ?'.'>', html_entity_decode($matches[1]) ) ;
    }
    
    public static function nodecode( $matches )
    {
        return sprintf('<'.'?php echo %s ?'.'>', $matches[1] );
    }
}
