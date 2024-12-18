<?php
// HGAuth version 1.0.4
// Github: https://github.com/cuga-rajal/hgauth
//
// This file is expected to be used to present an authorization from on a web browser
// to a user who has attempted to HG teleport to an Opensim region.
//
// Make sure your date.timezone is set in php.ini

$tokenerror = "You have accessed this page differently than intended. Please use the link provided from your Opensim Viewer.";
$alreadyreg = "It appears that your avatar is already authorized with the system. You may now HG teleport.";
$regmsg = "<strong>Welcome</strong><br /><br />To accept the Terms of Service and GDPR at our grid, please complete the following form.";
$confirmyes = "Thank you for authorizing your avatar. You may now teleport to reach the target region.";
$confirmno = "We are sorry to see you go.";
$ajaxerror = "An error occurred. Please make sure nothing has been altered from the original link.";

session_start();

include 'authconfig.php';
$dbc = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

// Handle AJAX connection
if((isset($_REQUEST["CONFIRMAUTH"])) && (isset($_REQUEST["token"]))) {
	if((! isset($_SESSION['id'])) || ($_SESSION['id'] != session_id())) {       // If they are not sending the session cookie
		header('Status: 204');                                                  // terminate silently
	} else if( $_REQUEST["CONFIRMAUTH"] == "YES") {
		$token = mysqli_real_escape_string($dbc,$_REQUEST['token']);
		$query = "SELECT * FROM $tablename WHERE token='$token'";
		$data = mysqli_query($dbc, $query);
		if(mysqli_num_rows($data)==0) {
			header('Status: 204');   
			exit();
		} else {
			$row = mysqli_fetch_array($data);
			if($row['confirmtime'] == NULL) {
				$confirmtime = date("Y/m/d H:i:s",time());
				$query = "UPDATE $tablename SET confirmtime='$confirmtime' WHERE token='$token'";
				if($data = mysqli_query($dbc, $query)) { $xml = "<status>1</status>"; }                  // If query successful report success
				else { $xml = "<status>0</status><msg>malformed query error</msg>"; }                                   // If malformed query, report it as a problem
				if(mysqli_affected_rows($dbc)==0) { $xml = "<status>0</status><msg>no rows were updated</msg>"; }      // But if no rows were updated, report it as a problem
			} else {
				$xml = "<status>1</status>"; 
			}
			
			header('Content-type: text/xml');
			echo "<data>\n$xml</data>\n";
		}
	} 
	exit(0);
}
// END of AJAX section

$skip = FALSE;

// Make sure URL has a "token" in the query string. This is an encoded string of name/value pairs
if((! isset($_REQUEST['token'])) || ((isset($_REQUEST['token'])) && ($_REQUEST['token']==''))) {  
	$message = $tokenerror;
	$skip = TRUE;
	goto postcheck;
}

$t = base64_url_decode($_REQUEST['token']);

// $t is the decoded query string with a list of name/value pairs
// Check that $t has the expected name/value pairs
if((strpos($t, "t=")===false) || (strlen($t)!=9)) {
	$message = $tokenerror;
	$skip = TRUE;
	goto postcheck;
}

// extract and sanitize the name/value pairs
$token = mysqli_real_escape_string($dbc, substr(base64_url_decode($_REQUEST['token']), 2));

// It's possible for someone to alter the query string. Check that the token exists in the database
$query = "SELECT * FROM $tablename WHERE token='$token'";
$data = mysqli_query($dbc, $query);
if(mysqli_num_rows($data)==0) {
	$message = $tokenerror;
	$skip = TRUE;
	goto postcheck;
} else {
	$row = mysqli_fetch_array($data);
	$uuid = $row['uuid'];
}

// Now that we know the sent avatar name hasn't been altered, check if it's already registered
$query = "SELECT * FROM $tablename WHERE token='$token' AND confirmtime IS NOT NULL";
$data = mysqli_query($dbc, $query);

if(mysqli_num_rows($data)>0) {  // already registered
	$row = mysqli_fetch_array($data);
	if($row['avatarname'] != '') { $message = $row['avatarname'] . ", " . $alreadyreg; }
	else {  $message = $alreadyreg; }
	$skip = TRUE;
} else {   // Avatar name doesn't exist, proceed with registration
	$message = $regmsg;
}

postcheck:

// Prevent hand-crafted forms from being able to submit
$_SESSION['id'] = session_id();

// Adjust the following HTML text as needed
?>
<!doctype html>
<html lang="en">

<head>
<title>Authorize HG Avatar</title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>
<div id="content">
<h2>Authorize HG Avatar</h2>
<hr/>
<div id="main">
<?php echo $message; 

if(! $skip) { ?>
<br /><br />
First paragrapgh explains why this web form is being presented.
<br />
<br />
Second paragraph describes GDPR-related data being collected.
<br />
<br />
Third paragraph describes usage of collected data (or if none, state so.)
<br />
<br />
4th paragraph explains call-to-action options, clicking Yes or No buttons below. Example:
<br />
<br />
Clicking the Yes button below indicates acceptance of the Terms of Service and, if applicable, the GDPR.
<br />
<div id="formdata">
<form action="" method="post">
<input type="hidden" name="token" id="token" value="<?php echo $token; ?>">
<ul>
<li>I agree to the Terms of Service</li>
<li>I confirm I am 18 years of age or older</li>
<li>If I live in the EU, I give you my permission to collect and use my data as indicated above</li>
</ul>
<br />
<input id="b1" type="button" value="YES" name="CONFIRMAUTH" style="font-size:140%" onclick="sendAjax()" />&nbsp;&nbsp;&nbsp;&nbsp;
<input id="b2" type="button" value="NO" name="CONFIRMAUTH" style="font-size:140%" onclick="rejected()" />
</form>
</div>
<?php } ?>
</div>
</div> <!-- the last </div> on the page -->

<script type="text/javascript">

function sendAjax() {
	$('#b1, #b2').attr('disabled', true);
	req = new XMLHttpRequest();
	req.open("GET", "<?php echo $_SERVER['PHP_SELF']; ?>?token=<?php echo $token; ?>&CONFIRMAUTH=YES", true);
	req.onreadystatechange = function() {
		if ((req.readyState == 4) && (req.status == 200)) {
			xml = req.responseText;
			if($(xml).find("status").text()=='1') {
				$('#main').html("<strong><?php echo $confirmyes; ?></strong>");
			} else if($(xml).find("status").text()=='0') {
				$('#formdata').html("<?php echo $ajaxerror; ?>");
			}
		}
	}
	req.send();
}

function rejected() {
	$('#formdata').html("<strong><?php echo $confirmno; ?></strong>");
}

</script>

</body>
</html>


