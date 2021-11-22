<?php

function extractZip($filename) {
    //$names = array('counts', 'CP.dat', 'EM.dat', 'EN.dat', 'FR.dat', 'HD.dat', 'LO.dat', 'HS.dat');
    $names = array('counts', 'AM.dat', 'CO.dat', 'EN.dat', 'HD.dat', 'HS.dat', 'LA.dat', 'SC.dat', 'SF.dat');
    $basename = explode('.', $filename);
    $basename = $basename[0];

    $zip = new ZipArchive();
    $res = $zip->open($filename);

    if ($res === TRUE) {
        foreach ($names as $name) {
            if ($zip->locateName($name, ZipArchive::FL_NOCASE) !== false) {
                echo ('Writing file ' . $name . "\n");
                $zip->extractTo('./', $name);
                if (rename($name, $basename . '-' . $name)) {
                    echo('-Renamed ' . $name . ' to ' . $basename . '-' . $name . "\n");
                } else {
                    echo('Failed to rename files, exiting.');
                    exit();
                }
            }
            else {
                echo('Unable to find ' . $name . ', continuing...' . "\n");
            }
        }
        $zip->close();
        echo('Extraction of ' . $basename . ' successful.' . "\n");
    } else {
        echo('Could not extract ' . $basename . '.  Stopping process.');
        exit();
    }
}

function processFilesRemoveBlankLines($base) {
    // $names = array('CP', 'EM', 'EN', 'FR', 'HD', 'LO', 'HS');
    $names = array('AM', 'CO', 'EN', 'HD', 'HS', 'LA', 'SC', 'SF');

    foreach ($names as $name) {
        if(file_exists($base.'-'.$name.'.dat')) {
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

function importFiles($base, $licenseDB, $applicationDB=null){
    $names = array('AM', 'CO', 'EN', 'HD', 'HS', 'LA', 'SC', 'SF');
    $inquiries = new sqlInquiries;

    foreach ($names as $name){
        echo "Opening $base-$name-new.dat\n";
        
        $importer = getFileContents($base, $name);
        
        if($base = 'l_amat') {
            $db = $licenseDB;
        } elseif ($base = 'a_amat') {
            $db = $applicationDB;
        }

        switch ($name) {
            case "AM":
                $inquiries->createAMTemp($db);

                $insertSQL = $db->prepare($inquiries->amTempInserter);
                $insertSQL->bind_param("sisssssissssssssss", $recordType, $uniqueSystemIdentifier, $ulsFileNum, $ebfNumber, $callsign, $operatorClass, $groupCode, $regionCode, $trusteeCallsign, $trusteeIndicator, $physicianCertification, $veSignature, $systematicCallsignChange, $vanityCallsignChange, $vanityRelationship, $previousCallsign, $previousOperatorClass, $trusteeName);
    
                foreach($importer as $imp){
                    $recordType = $imp[0];
                    $uniqueSystemIdentifier = $imp[1];
                    $ulsFileNum = $imp[2];
                    $ebfNumber = $imp[3];
                    $callsign = $imp[4];
                    $operatorClass = $imp[5];
                    $groupCode = $imp[6];
                    $regionCode = $imp[7];
                    $trusteeCallsign = $imp[8];
                    $trusteeIndicator = $imp[9];
                    $physicianCertification = $imp[10];
                    $veSignature = $imp[11];
                    $systematicCallsignChange = $imp[12];
                    $vanityCallsignChange = $imp[13];
                    $vanityRelationship = $imp[14];
                    $previousCallsign = $imp[15];
                    $previousOperatorClass = $imp[16];
                    $trusteeName = $imp[17];
                    
                    $insertSQL->execute();
                }
                $insertSQL->close();
    
                mysqli_query($db, $inquiries->amUpdater);
                
                if(!$db->error){
                    echo "- Imported AM table: ".$db->info.PHP_EOL;
                } else {
                    echo "- Error importing AM table: ".$db->error.PHP_EOL;
                }
                break;

            case "CO":
                echo "Do CO things here.".PHP_EOL;
                break;
            case "EN":
                echo "Do EN things here.".PHP_EOL;
                break;
            case "HD":
                echo "Do HD things here.".PHP_EOL;
                break;
            case "HS":
                echo "Do HS things here.".PHP_EOL;
                break;
            case "LA":
                echo "Do LA things here.".PHP_EOL;
                break;
            case "SC":
                echo "Do SC things here.".PHP_EOL;
                break;
            case "SF":
                echo "Do SF things here.".PHP_EOL;
                break;
        }
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
                } else {
                    $length = (int) (($bytes_transferred / $filesize) * 100);
                    printf("\r[%-100s] %d%% (%2d/%2d kb)", str_repeat("=", $length) . ">", $length, ($bytes_transferred / 1024), $filesize / 1024);
                }
            }
            break;
    }
}

function getFileContents($base, $name) {
    $data = file_get_contents($base.'-'.$name.'-new.dat');
        $fp = fopen("php://temp",'r+');
        fputs($fp, $data);
        rewind($fp);
        $importer = [];
        while (($csv = fgetcsv($fp,0,"|")) !== FALSE) {
            $importer[] = $csv;
        }
        fclose($fp);

        return $importer;
}


class sqlInquiries {
    public $amTempInserter = "INSERT INTO AM_TEMP (record_type, unique_system_identifier, uls_file_num, ebf_number, callsign, operator_class, group_code, region_code, trustee_callsign, trustee_indicator, physician_certification, ve_signature, systematic_callsign_change, vanity_callsign_change, vanity_relationship, previous_callsign, previous_operator_class, trustee_name) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
    public $amUpdater = "REPLACE INTO PUBACC_AM (record_type, unique_system_identifier, uls_file_num, ebf_number, callsign, operator_class, group_code, region_code, trustee_callsign, trustee_indicator, physician_certification, ve_signature, systematic_callsign_change, vanity_callsign_change, vanity_relationship, previous_callsign, previous_operator_class, trustee_name) SELECT * FROM AM_TEMP;";

    function createAMTemp($con){
        mysqli_query($con, "CREATE TEMPORARY TABLE AM_TEMP LIKE PUBACC_AM;");
    }
}
