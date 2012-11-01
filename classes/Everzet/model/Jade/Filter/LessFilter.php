<?php

class Everzet_Jade_Filter_LessFilter implements Everzet_Jade_Filter_FilterInterface
{
    /**
     * Filter text.
     *
     * @param   string  $text       text to filter
     * @param   array   $attributes filter options from template
     * @param   string  $indent     indentation string
     *
     * @return  string              filtered text
     */
    public function filter($text, array $attributes, $indent)
    {
        $less = new App_Less_Compiler();
        $strCss = $less->parse( $text );

        if ( $strCss != '' ) {
            $html  = $indent . '<style type="text/css">'."\n".'<!--' . "\n";
            $html .= $strCss;
            $html .= "-->\n".'</style>';
        }
        return $html;
    }
}
