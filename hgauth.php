<?php
// HGAuth version 1.0.4
// Github: https://github.com/cuga-rajal/hgauth
//
// This file is expected to be used to receive HG teleport authorization requests from an 
// Opensim Authorization Service and to send responses back to that service.


include '/full/path/to/authconfig.php'; // suggest placing this file outside the document root
$dbc = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

$newmsg = "Please visit the following link to accept Terms of Service and GDPR: ";
$failmsg = "Authentication has failed. Please try again or from another region. If the error persists, please contact the grid admin.";

$request = @file_get_contents('php://input');
$xml3 = simplexml_load_string($request);
if($xml3) {
	$uuid = $xml3->ID;
	$firstname = $xml3->FirstName;
	$lastname = $xml3->SurName;
	$nametest = substr($lastname,0,1);
	$avatarname	= $firstname.$lastname;
} else {
	header('Status: 204');   
	exit();
}

$query = "SELECT * FROM $tablename WHERE uuid='$uuid' AND confirmtime IS NOT NULL";

$data = mysqli_query($dbc, $query);

if(mysqli_num_rows($data)>0) {
	if($avatarname=='') {
		echo '<?xml version="1.0" encoding="utf-8"?>' .
    		'<AuthorizationResponse xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">' . 
    		'<IsAuthorized>true</IsAuthorized>' . 
    		'<Message><![CDATA[Authorized]]></Message></AuthorizationResponse>';
		mysqli_close($dbc);
		exit();
	} else if($nametest == "@") {
		$row = mysqli_fetch_array($data);
		if($row['avatarname']=='') {
			$query2 = "UPDATE $tablename SET avatarname='$avatarname' WHERE id=" . $row['id'];
			$data2 = mysqli_query($dbc, $query2);
		}
	}
	echo '<?xml version="1.0" encoding="utf-8"?>' .
    		'<AuthorizationResponse xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">' . 
    		'<IsAuthorized>true</IsAuthorized>' . 
    		'<Message><![CDATA[Authorized]]></Message></AuthorizationResponse>';
	mysqli_close($dbc);
	exit();
} else { // uuid not found

	$token = substr(hash("sha1", $uuid, false),0,7);
	
	$query2 = "SELECT * FROM $tablename WHERE uuid='$uuid' LIMIT 1";
	$data2 = mysqli_query($dbc, $query2);
	if(mysqli_num_rows($data2)==0) {
		$query3 = "INSERT INTO $tablename (token,uuid) VALUES ('" . $token . "','" . $uuid . "')";
		$data3 = mysqli_query($dbc, $query3);
	}
	$getstring = base64_url_encode("t=" . $token);
	$authlink2 = $authlink . "?token=" . $getstring;
	
	echo '<?xml version="1.0" encoding="utf-8"?>' .
    		'<AuthorizationResponse xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">' . 
    		'<IsAuthorized>false</IsAuthorized>' . 
    		'<Message><![CDATA[' . $newmsg . $authlink2 . ']]></Message></AuthorizationResponse>';
    mysqli_close($dbc);
	exit();
}
		
if($nametest != "@") {
	echo '<?xml version="1.0" encoding="utf-8"?>' .
    		'<AuthorizationResponse xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">' . 
    		'<IsAuthorized>true</IsAuthorized>' . 
    		'<Message><![CDATA[Authorized]]></Message></AuthorizationResponse>';
	mysqli_close($dbc);
	exit();
}

if($nametest != "" ) { 
	echo '<?xml version="1.0" encoding="utf-8"?>' .
    		'<AuthorizationResponse xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">' . 
    		'<IsAuthorized>false</IsAuthorized>' . 
    		'<Message><![CDATA[' . $failmsg . ']]></Message></AuthorizationResponse>';
    mysqli_close($dbc);
	exit();
}
				
			
?>