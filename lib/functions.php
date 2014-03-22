<?php

function extractZip($filename) {
	$names = array('counts', 'CP.dat', 'EM.dat', 'EN.dat', 'FR.dat', 'HD.dat', 'LO.dat', 'HS.dat');
	$basename = explode('.', $filename);
	$basename = $basename[0];

	$zip = new ZipArchive();
	$res = $zip->open($filename);

	if($res === TRUE) {
		foreach($names as $name) {
			echo ('Writing file ' . $name . "\n");
			$zip->extractTo('./', $name);
			if(rename($name, $basename . '-' . $name)) {
				echo('-Renamed ' . $name . ' to ' . $basename . '-' . $name . "\n");
			}
			else {
				echo('Failed to rename files, exiting.');
				exit();
			}
		}
		$zip->close();
		echo('Extraction of ' . $basename . ' successful.' . "\n");
	}
	else {
		echo('Could not extract ' . $basename . '.  Stopping process.');
		exit();
	}
}

function processFilesRemoveBlankLines($base) {
	$names = array('CP', 'EM', 'EN', 'FR', 'HD', 'LO', 'HS');

	foreach ( $names as $name ) {
		echo ('- Re-writing ' . $name . '.dat with blank lines removed.' . "\n");
		$fhorig = fopen($base . '-' . $name . '.dat', 'r');
		$fhnew = fopen($base . '-' . $name . '-new.dat', 'w+');

		while ($line = fgets($fhorig)) {
			fwrite($fhnew, rtrim($line) . "\n");
		}

		fclose($fhorig);
		fclose($fhnew);
	}
}

function downloadFile($url) {
	$filename = explode("/", $url);
	$filename = array_pop($filename);

	// Let's check to see what the filesize is, if not different, we won't re-download
	if (file_exists($filename)) {
		echo 'Checking local file ' . $filename . "\n";
		$cur_file = stat($filename);
		$head = array_change_key_case(get_headers($url, TRUE));
		$filesize = $head['content-length'];
		
		if ($filesize == $cur_file["size"]) {
			echo 'Local file is same as remote file, not downloading again.' . "\n";
			return;
		}
	}

	echo "Downloading " . $filename . "\n";
	
	$ctx = stream_context_create();
	stream_context_set_params($ctx, array("notification" => "stream_notification_callback"));
	
	$fp = fopen($url, "r", false, $ctx);
	if (is_resource($fp) && file_put_contents($filename, $fp)) {
		echo " Done!\n";
	}
}

function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
	static $filesize = null;
	
	switch ($notification_code) {
		case STREAM_NOTIFY_RESOLVE :
		case STREAM_NOTIFY_AUTH_REQUIRED :
		case STREAM_NOTIFY_COMPLETED :
		case STREAM_NOTIFY_FAILURE :
		case STREAM_NOTIFY_AUTH_RESULT :
		case STREAM_NOTIFY_MIME_TYPE_IS :
		case STREAM_NOTIFY_CONNECT:
        /* Ignore */
        break;
		
		case STREAM_NOTIFY_REDIRECTED :
			echo "Being redirected to: ", $message, "\n";
			break;
		
		case STREAM_NOTIFY_FILE_SIZE_IS :
			$filesize = $bytes_max;
			echo "Filesize: ", $filesize, "\n";
			break;
		
		case STREAM_NOTIFY_PROGRESS :
			if ($bytes_transferred > 0) {
				if (!isset($filesize)) {
					printf("\rUnknown filesize.. %2d kb done..", $bytes_transferred / 1024);
				}
				else {
					$length = (int) (($bytes_transferred / $filesize) * 100);
					printf("\r[%-100s] %d%% (%2d/%2d kb)", str_repeat("=", $length) . ">", $length, ($bytes_transferred / 1024), $filesize / 1024);
				}
			}
			break;
	}
}
