<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


function switch_db($dbdet)
{	
    $dbconfig['dsn']	= '';
		$dbconfig['hostname'] = $dbdet['IP'];
		$dbconfig['username'] = $dbdet['UNAME'];
		$dbconfig['password'] = $dbdet['PWD'];
		$dbconfig['database'] = $dbdet['DB'];
		$dbconfig['dbdriver'] = 'sqlsrv';
		$dbconfig['dbprefix'] = '';
		$dbconfig['pconnect'] = true;
		$dbconfig['db_debug'] = FALSE;
		$dbconfig['cache_on'] = FALSE;
		$dbconfig['cachedir'] = '';
		$dbconfig['char_set'] = 'utf8';
		$dbconfig['dbcollat'] = 'utf8_general_ci';
		$dbconfig['swap_pre'] = '';
		$dbconfig['encrypt'] = FALSE;
		$dbconfig['compress'] = FALSE;
		$dbconfig['stricton'] = FALSE;
		$dbconfig['failover'] = array();
		$dbconfig['save_queries'] = TRUE;
		$dbconfig['port'] = $dbdet['PORT']; //1434, 1433
    return $dbconfig;
}


function isIpOnline($ip, $port = 80, $timeout = 5) {
    // Try to open a socket connection to the IP and port
    $connection = @fsockopen($ip, $port, $errno, $errstr, $timeout);

    if ($connection) {
        // If connection is successful, the IP is online
        fclose($connection);
        return true;
    } else {
        // If connection fails, the IP is offline or unreachable
        return false;
    }
}


function lb($value='')
{
	if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'curl') !== false) {

        // Running from command line
        $lineBreak = "\n";
        
        } else {
        // Running from web browser
        $lineBreak = "<br>";
        
    }

    return $lineBreak;
}


function imgtobin($imageText){
	 $binaryData = ''; 
	if (substr($imageText, 0, 2) == "0x") {
	    // Remove the "0x" prefix and decode the hex to binary
	    $binaryData = hex2bin(substr($imageText, 2));
	} else {
	    // Assume it's base64 and decode it
	    $binaryData = base64_decode($imageText);
	}
	return $binaryData;
}



function compressImage($source, $destination, $quality) {
    // Get image info 
    $imgInfo = getimagesize($source); 
    $mime = $imgInfo['mime']; 

    // Create a new image from file 
    switch($mime){ 
        case 'image/jpeg': 
            $image = imagecreatefromjpeg($source); 
            break; 
        case 'image/png': 
            $image = imagecreatefrompng($source); 
            break; 
        case 'image/gif': 
            $image = imagecreatefromgif($source); 
            break; 
        default: 
            $image = imagecreatefromjpeg($source); 
    } 

    // Save image 
    imagejpeg($image, $destination, $quality); 

    // Return compressed image 
    return $destination; 
}


function resizeImage($source, $destination, $newWidth) {
    // Get image info 
    $imgInfo = getimagesize($source); 
    $mime = $imgInfo['mime']; 
    $width = $imgInfo[0];
    $height = $imgInfo[1];

    // Calculate new height to maintain aspect ratio
    $newHeight = ($height / $width) * $newWidth;

    // Create a new image from file 
    switch($mime){ 
        case 'image/jpeg': 
            $image = imagecreatefromjpeg($source); 
            break; 
        case 'image/png': 
            $image = imagecreatefrompng($source); 
            break; 
        case 'image/gif': 
            $image = imagecreatefromgif($source); 
            break; 
        default: 
            $image = imagecreatefromjpeg($source); 
    }

    // Create a new true color image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    // Copy and resize the old image into the new image
    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Save the new image
    imagejpeg($newImage, $destination, 90);

    // Free up memory
    imagedestroy($image);
    imagedestroy($newImage);

    return $destination;
}
