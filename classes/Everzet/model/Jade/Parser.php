<?php


/*
 * This file is part of the Jade.php.
 * (c) 2010 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Jade Parser. 
 */
class Everzet_Jade_Parser
{
    protected $lexer;

    /**
     * Initialize Parser. 
     * 
     * @param   Everzet_Jade_Lexer_LexerInterface  $lexer  lexer object
     */
    public function __construct(Everzet_Jade_Lexer_LexerInterface $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * Parse input returning block node. 
     * 
     * @param   string          $input  jade document
     *
     * @return  Everzet_Jade_Node_BlockNode
     */
    public function parse($input)
    {
        $this->lexer->setInput($input);

        $node = new Everzet_Jade_Node_BlockNode($this->lexer->getCurrentLine());

        while ('eos' !== $this->lexer->predictToken()->type) {
            if ('newline' === $this->lexer->predictToken()->type) {
                $this->lexer->getAdvancedToken();
            } else {
                $node->addChild($this->parseExpression());
            }
        }

        return $node;
    }

    /**
     * Expect given type or throw Exception. 
     * 
     * @param   string  $type   type
     */
    protected function expectTokenType($type)
    {
        if ($type === $this->lexer->predictToken()->type) {
            return $this->lexer->getAdvancedToken();
        } else {
            
            throw new Everzet_Jade_Exception(sprintf('Expected %s, but got %s, line %d', $type,
                    $this->lexer->predictToken()->type, $this->lexer->getCurrentLine() ));
        }
    }
    
    /**
     * Accept given type. 
     * 
     * @param   string  $type   type
     */
    protected function acceptTokenType($type)
    {
        if ($type === $this->lexer->predictToken()->type) {
            return $this->lexer->getAdvancedToken();
        }
    }

    /**
     * Parse current expression & return Everzet_Jade_Node_Node. 
     * 
     * @return  Everzet_Jade_Node_Node
     */
    protected function parseExpression()
    {
        switch ($this->lexer->predictToken()->type) {
            case 'tag':
                return $this->parseTag();
            case 'doctype':
                return $this->parseDoctype();
            case 'filter':
                return $this->parseFilter();
            case 'comment':
                return $this->parseComment();
            case 'text':
                return $this->parseText();
            case 'code':
                return $this->parseCode();
            case 'id':
            case 'class':
                $token = $this->lexer->getAdvancedToken();
                $this->lexer->deferToken($this->lexer->takeToken('tag', 'div'));
                $this->lexer->deferToken($token);

                return $this->parseExpression();
        }
    }

    /**
     * Parse next text token. 
     * 
     * @return  Everzet_Jade_Node_TextNode
     */
    protected function parseText($trim = false)
    {
        $token = $this->expectTokenType('text');
        $value = $trim ? preg_replace('/^ +/', '', $token->value) : $token->value;

        return new Everzet_Jade_Node_TextNode($value, $this->lexer->getCurrentLine());
    }

    /**
     * Parse next code token. 
     * 
     * @return  Everzet_Jade_Node_CodeNode
     */
    protected function parseCode()
    {
        $token  = $this->expectTokenType('code');
        $node   = new Everzet_Jade_Node_CodeNode($token->value, $token->buffer, $this->lexer->getCurrentLine());

        // Skip newlines
        while ('newline' === $this->lexer->predictToken()->type) {
            $this->lexer->getAdvancedToken();
        }

        if ('indent' === $this->lexer->predictToken()->type) {
            $node->setBlock($this->parseBlock());
        }

        return $node;
    }

    /**
     * Parse next commend token. 
     * 
     * @return  Everzet_Jade_Node_CommentNode
     */
    protected function parseComment()
    {
        $token  = $this->expectTokenType('comment');
        $node   = new Everzet_Jade_Node_CommentNode(preg_replace('/^ +| +$/', '', $token->value), $token->buffer, $this->lexer->getCurrentLine());

        // Skip newlines
        while ('newline' === $this->lexer->predictToken()->type) {
            $this->lexer->getAdvancedToken();
        }

        if ('indent' === $this->lexer->predictToken()->type) {
            $node->setBlock($this->parseBlock());
        }

        return $node;
    }

    /**
     * Parse next doctype token. 
     * 
     * @return  DoctypeNode
     */
    protected function parseDoctype()
    {
        $token = $this->expectTokenType('doctype');

        return new Everzet_Jade_Node_DoctypeNode($token->value, $this->lexer->getCurrentLine());
    }

    /**
     * Parse next filter token. 
     * 
     * @return  FilterNode
     */
    protected function parseFilter()
    {
        $block      = null;
        $token      = $this->expectTokenType('filter');
        $attributes = $this->acceptTokenType('attributes');

        if ('text' === $this->lexer->predictToken(2)->type) {
            $block = $this->parseTextBlock();
        } else {
            $block = $this->parseBlock();
        }

        $node = new Everzet_Jade_Node_FilterNode(
            $token->value, null !== $attributes ? $attributes->attributes : array(), $this->lexer->getCurrentLine()
        );
        $node->setBlock($block);

        return $node;
    }

    /**
     * Parse next indented? text token. 
     * 
     * @return  Everzet_Jade_Node_TextToken
     */
    protected function parseTextBlock()
    {
        $node = new Everzet_Jade_Node_TextNode(null, $this->lexer->getCurrentLine());

        $this->expectTokenType('indent');
        while ('text' === $this->lexer->predictToken()->type || 'newline' === $this->lexer->predictToken()->type) {
            if ('newline' === $this->lexer->predictToken()->type) {
                $this->lexer->getAdvancedToken();
            } else {
                $node->addLine($this->lexer->getAdvancedToken()->value);
            }
        }
        $this->expectTokenType('outdent');

        return $node;
    }

    /**
     * Parse indented block token. 
     * 
     * @return  Everzet_Jade_Node_BlockNode
     */
    protected function parseBlock()
    {
        $node = new Everzet_Jade_Node_BlockNode($this->lexer->getCurrentLine());

        $this->expectTokenType('indent');
        while ('outdent' !== $this->lexer->predictToken()->type) {
            if ('newline' === $this->lexer->predictToken()->type) {
                $this->lexer->getAdvancedToken();
            } else {
                $node->addChild($this->parseExpression());
            }
        }
        $this->expectTokenType('outdent');

        return $node;
    }

    /**
     * Parse tag token. 
     * 
     * @return  Everzet_Jade_Node_TagNode
     */
    protected function parseTag()
    {
        $name = $this->lexer->getAdvancedToken()->value;
        $node = new Everzet_Jade_Node_TagNode($name, $this->lexer->getCurrentLine());

        // Parse id, class, attributes token
        while (true) {
            switch ($this->lexer->predictToken()->type) {
                case 'id':
                case 'class':
                    $token = $this->lexer->getAdvancedToken();
                    $node->setAttribute($token->type, $token->value);
                    continue;
                case 'attributes':
                    foreach ($this->lexer->getAdvancedToken()->attributes as $name => $value) {
                        $node->setAttribute($name, $value);
                    }
                    continue;
                default:
                    break(2);
            }
        }

        // Parse text/code token
        switch ($this->lexer->predictToken()->type) {
            case 'text':
                $node->setText($this->parseText(true));
                break;
            case 'code':
                $node->setCode($this->parseCode());
                break;
        }

        // Skip newlines
        while ('newline' === $this->lexer->predictToken()->type) {
            $this->lexer->getAdvancedToken();
        }

        // Tag text on newline
        if ('text' === $this->lexer->predictToken()->type) {
            if ($text = $node->getText()) {
                $text->addLine('');
            } else {
                $node->setText(new Everzet_Jade_Node_TextNode('', $this->lexer->getCurrentLine()));
            }
        }

        // Parse block indentation
        if ('indent' === $this->lexer->predictToken()->type) {
            $node->addChild($this->parseBlock());
        }

        return $node;
    }
}
