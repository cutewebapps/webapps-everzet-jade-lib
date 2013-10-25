<?php

/**
 * command-line tool  to check indentation in JADE files
 */

function check_indentation( $strFileName )
{
	$arrLines = explode( "\n", file_get_contents( $strFileName ) );	
	$nErrors = 0;
	foreach ( $arrLines as $iRow => $sRow ) {
		$nSpaces = 0; 
		while( substr( $sRow, $nSpaces, 1 ) == ' ' ) {
			$nSpaces ++ ;
		}
		if ( substr( $sRow, $nSpaces, 1 ) == "\t" ) {
			echo "ROW #$iRow: tabulation met\n";
			$nErrors ++
		}
		if ( $nSpaces % 4 == 0 ) {
			echo "ROW #$iRow: ".$nSpaces."\n";
			$nErrors ++
		}
	}
	if ( $nErrors == "" )
		echo 'OK'."\n";
}

if ( !isset( $argv[1] ) ) die('USAGE: file name as first parameter' );
check_indentation( $argv[1] );
