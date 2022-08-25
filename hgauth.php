<?php
// hgauth.php - version 1.0 - 25-08-2022
// This file is expected to be used to receive HG teleport authorization requests from an 
// Opensim Authorization Service and to send responses back to that service.


include 'authconfig.php';
$dbc = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

$msgformat = "%s, please visit the following link to accept Terms of Service and GDPR: %s";
$failmsg = "Authentication has failed. Please try again or from another region. If the error persists, please contact the grid admin.";
	
class AuthorizationResponse {
    private $m_isAuthorized;
    private $m_message;
 
    public function AuthorizationResponse($isAuthorized,$message) {
    	$this->m_isAuthorized = $isAuthorized;
    	$this->m_message = $message;
    }
 
    public function toXML() {
    	return '<?xml version="1.0" encoding="utf-8"?>' .
    		'<AuthorizationResponse xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">' . 
    		'<IsAuthorized>'. $this->m_isAuthorized .'</IsAuthorized>' . 
    		'<Message><![CDATA['. $this->m_message .']]></Message></AuthorizationResponse>';
    }
}

$request = @file_get_contents('php://input');
$xml3 = simplexml_load_string($request);
$uuid = $xml3->ID;
$firstname = $xml3->FirstName;
$lastname = $xml3->SurName;
$nametest = substr($lastname,0,1);
$avatarname	= $firstname.$lastname;

$query = "SELECT * FROM $tablename WHERE avatarname LIKE '" . $avatarname . "'";
$data = mysqli_query($dbc, $query);


if(($nametest == "@") && (mysqli_num_rows($data)>0)) {
	$row = mysqli_fetch_array($data);
	if($row['uuid']=='') {
		$query2 = "UPDATE $tablename SET uuid='$uuid' WHERE id=" . $row['id'];
		$data2 = mysqli_query($dbc, $query2);
	}
	$authResp = new AuthorizationResponse("true","Authorized");
	echo $authResp->toXML();
	mysqli_close($dbc);
	exit();
}

if(($nametest == "@") && (mysqli_num_rows($data)==0)) { 
    $token = substr(hash("sha1", $avatarname, false),0,7);
	$query = "INSERT INTO $tablename (`token`) VALUES ('". $token . "')";
	$data = mysqli_query($dbc, $query);
	
	$getstring = base64_url_encode("fn=" . $firstname . "&ln=" . $lastname);
	$authlink2 = $authlink . "?token=" . $getstring;
	
	$newmsg = sprintf($msgformat,$firstname,$authlink2);
	$authResp = new AuthorizationResponse("false",$newmsg);
    echo $authResp->toXML();
    mysqli_close($dbc);
	exit();
}
		
if($nametest != "@") {
	$authResp = new AuthorizationResponse("true","Authorized");
	echo $authResp->toXML();
	mysqli_close($dbc);
	exit();
}

if($nametest != "" ) {							
	$authResp = new AuthorizationResponse("false",$failmsg);
    echo $authResp->toXML();
    mysqli_close($dbc);
	exit();
}
				
			
?>