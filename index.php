<?php

/*
 * Implements the functionalist described on n6lhv's page (http://www.n6lhv.net/uls/)
 * in PHP, and hopefully in as small of a file that can be done
 * PHP version by Matt Carlson (KC0UDT)
 * 
 * Original site seems down as of 2015-08-22, alternate link:
 * https://web.archive.org/web/20150216181139/http://n6lhv.net/uls/
 * 
 * ULS Data File Format: http://wireless.fcc.gov/uls/data/documentation/pa_ddef41.pdf
 * 
 * Licensed under Creative Commons License as per original project
 * http://creativecommons.org/licenses/by/2.0/
 */

require_once('lib/functions.php');
require_once('lib/databaseConnect.php');

ini_set('memory_limit', '10G');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$fcc_uls_url = 'https://data.fcc.gov/download/pub/uls/complete/';
// $fcc_uls_url = 'http://wireless.fcc.gov/uls/data/complete/';

/*
 * Comment out any files you do not need/want
 */

$files = array(
	//'l_LMpriv',
	//'l_LMcomm',
	//'l_LMbcast',
	//'l_coast',
	//'l_micro',
	//'l_paging',
    //  'a_amat',
	'l_amat'
);

foreach($files as $file) {
	downloadFile($fcc_uls_url . $file . '.zip');
	extractZip($file . '.zip');
	processFilesRemoveBlankLines($file);
	openFile($file,$con);
}

echo('Overall Memory Used: ' . (memory_get_peak_usage(true) / 1024 / 1024) . 'MB'.PHP_EOL);