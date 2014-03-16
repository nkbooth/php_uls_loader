<?php

function extractZip($filename) {
	// List of filenames we are intrested in, from the zip files
	$names = array('counts', 'CP.dat', 'EM.dat', 'EN.dat', 'FR.dat', 'HD.dat', 'LO.dat', 'HS.dat');
	$basename = explode('.', $filename);
	$basename = $basename[0];
	$zip = zip_open($filename);

	if($zip) {
		while($zip_entry = zip_read($zip)) {
			$name = zip_entry_name($zip_entry);
			if(in_array($name, $names)) {
				if(zip_entry_open($zip, $zip_entry, "r")) {
					echo('Writing file ' . $basename . '-' . $name . "\n");
					$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
					file_put_contents($basename . '-' . $name, $buf);
					unset($buf);
				}
			}
		}
	}
}

function downloadFile($url) {
	$filename = explode("/", $url);
	$filename = array_pop($filename);
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