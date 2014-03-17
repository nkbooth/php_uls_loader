<?php

/*
 * Implements the functionalist described on n6lhv's page (http://www.n6lhv.net/uls/)
 * in PHP, and hopefully in as small of a file that can be done
 * PHP version by Matt Carlson (KC0UDT)
 * 
 * Licensed under Creative Commons License as per original project
 * http://creativecommons.org/licenses/by/2.0/
 */

require_once('lib/functions.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

$fcc_uls_url = 'http://wireless.fcc.gov/uls/data/complete/';

/*
 * Comment out any files you do not need/want
 */

$files = array(
	//'l_LMpriv',
	//'l_LMcomm',
	'l_LMbcast',
	'l_coast',
	'l_micro',
	'l_paging',
);

foreach($files as $file) {
	downloadFile($fcc_uls_url . $file . '.zip');
	extractZip($file . '.zip');
	processFilesRemoveBlankLines($file);
}

echo('Overall Memory Used: ' . (memory_get_peak_usage(true) / 1024 / 1024) . 'MB');
