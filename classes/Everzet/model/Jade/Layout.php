<?php

class Everzet_Jade_Layout extends App_Layout
{
    /**
     * @return App_Layout
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function getExtension()
    {
        return 'jade';
    }
    
    
    public function errorHandler( $str )
    {
        $error = error_get_last();
        if ($error && $error["type"] == E_USER_ERROR || $error["type"] == E_ERROR) {
            
            $confException = App_Application::getInstance()->getConfig()->exceptions;
            if ( is_object( $confException ) && $confException->render ) {
                // if normal fatal error rendering is configured
                return file_get_contents( CWA_APPLICATION_DIR.'/'.$confException->render  );
            }
            return ( "\nJade fatal error: $error[message] in $error[file] on line $error[line]\n" );
        }
        return $str;
    }    
    
    /* view must be initialized */
    public function render()
    {
        $this->view->render();
        if ( $this->isEnabled() ) {
            
            $strCacheDir = App_Application::getInstance()->getConfig()->cache_dir.'/jade';
            $dir = new Sys_Dir( $strCacheDir ); if ( !$dir->exists() ) $dir->create( '', true );

            $dumper = new Everzet_Jade_Dumper_PHPDumper();
            $dumper
                ->registerVisitor('tag', new Everzet_Jade_Visitor_AutotagsVisitor())
                ->registerFilter('javascript', new Everzet_Jade_Filter_JavaScriptFilter())
                ->registerFilter('cdata', new Everzet_Jade_Filter_CDATAFilter())
                ->registerFilter('php', new Everzet_Jade_Filter_PHPFilter())
                ->registerFilter('less', new Everzet_Jade_Filter_LessFilter())
                ->registerFilter('style', new Everzet_Jade_Filter_CSSFilter());

            // Initialize parser & Jade
            $parser = new Everzet_Jade_Parser(new Everzet_Jade_Lexer_Lexer());
            $jade   = new Everzet_Jade_Jade($parser, $dumper, $strCacheDir );

            ob_start( array( $this, 'errorHandler' ));
            // Parse a template (both string & file containers)
            
            $arrPaths = $this->getPath();
            if ( !is_array( $arrPaths )) $arrPaths = array( $arrPaths );

            $bSuccess = false;
            foreach ( $arrPaths as $strPath ) {
                if ( file_exists( $strPath ) ) {
                    require $jade->cache( $strPath );$bSuccess = true; break;
                }
            }
            if ( !$bSuccess ) {
                throw new App_Exception( 'Jade layout was not found at '.implode( ",", $arrPaths ));
            }
            
            $strContents = ob_get_contents();
            ob_end_clean();
            return $strContents;        

        } else {
            return $this->view->getContents();
        }
        
    }

}
