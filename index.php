<?php
// Github: https://github.com/cuga-rajal/hgauth
//
// This file is expected to be used to present an authorization from on a web browser
// to a user who has attempted to HG teleport to an Opensim region.
//
// Make sure your date.timezone is set in php.ini

$tokenerror = "You have accessed this page differently than intended. Please use the link provided from your Opensim Viewer.";
$alreadyreg = "It appears that your avatar is already authorized with the system. You may now HG teleport.";
$regmsg = "To accept the Terms of Service and GDPR for the Rajal.org grid, please complete the following form.";
$confirmyes = "Thank you for authorizing your avatar. You may now teleport to reach the target region.";
$confirmno = "We are sorry to see you go.";
$ajaxerror = "An error occurred. Please make sure nothing has been altered from the original link.";

session_start();

include 'authconfig.php';
$dbc = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

// Handle AJAX connection
if((isset($_REQUEST["CONFIRMAUTH"])) && (isset($_REQUEST["avatarname"]))) {
	if((! isset($_SESSION['id'])) || ($_SESSION['id'] != session_id())) {       // If they are not sending the session cookie
		header('Status: 204');                                                  // terminate silently
	} else if( $_REQUEST["CONFIRMAUTH"] == "YES") {
		$avatarname = mysqli_real_escape_string($dbc,$_REQUEST['avatarname']);
		$expected = substr(hash("sha1", $avatarname, false),0,7);
		$confirmtime = date("Y/m/d H:i:s",time());
		$query = "UPDATE $tablename SET avatarname='$avatarname', confirmtime='$confirmtime' WHERE token='$expected'";
	    if($data = mysqli_query($dbc, $query)) { $xml = "<status>1</status>"; }                  // If query successful report success
	    else { $xml = "<status>0</status><msg>$query</msg>"; }                                   // If malformed query, report it as a problem
	    if(mysqli_affected_rows($dbc)==0) { $xml = "<status>0</status><msg>$query</msg>"; }      // But if no rows were updated, report it as a problem
   		header('Content-type: text/xml');
   		echo "<data>\n$xml</data>\n";
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
if((strpos($t, "@")===false) || (strpos($t, "fn=")===false) || (strpos($t, "ln=")===false)) {
	$message = $tokenerror;
	$skip = TRUE;
	goto postcheck;
}

// extract and sanitize the name/value pairs
list($sf, $sl) = explode("&", base64_url_decode($_REQUEST['token']));
$firstname = mysqli_real_escape_string($dbc,explode("=", $sf)[1]);
$lastname = mysqli_real_escape_string($dbc,explode("=", $sl)[1]);
$avatarname	= $firstname."".$lastname;
$nametest = substr($lastname,0,1);
$expected = substr(hash("sha1", $avatarname, false),0,7);

// It's possible for someone to alter the query string and still have readable name/value pairs.
// Check that avatar name and stored hash match to make sure there they didn't alter the query string
$query = "SELECT * FROM $tablename WHERE token='$expected'";
$data = mysqli_query($dbc, $query);
if(mysqli_num_rows($data)==0) {
	$message = $tokenerror;
	$skip = TRUE;
	goto postcheck;
}

// Now that we know the sent avatar name hasn't been altered, check if it's already registered
$query = "SELECT * FROM $tablename WHERE avatarname LIKE '$avatarname'";
$data = mysqli_query($dbc, $query);

if(mysqli_num_rows($data)>0) {  // Avatar name exists, they already registered
	$message = $avatarname . ", " . $alreadyreg;
	$skip = TRUE;
} else {   // Avatar name doesn't exist, proceed with registration
	$message = $regmsg;
}

postcheck:

// Prevent hand-crafted forms from being able to submit
$_SESSION['id'] = session_id();

// Adjust the following HTML text as needed
?>
<html>

<head>
<title>Authorize HG Avatar</title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>
<div id="content">
<h2>Authorize HG Avatar</h2>
<hr/>

<?php echo $message; 

if(! $skip) { ?>
<br /><br />
<strong><?php echo $avatarname; ?></strong>
<br /><br />
Our grid requires all people entering the grid to agree to the <a href="http://mydomain/TOS.html" target="_blank">Terms of Service</a> and to be at least 18 years of age.
<br />
<br />
If you are a member of the European Union, <a href="https://gdpr-info.eu/" taget="_blank">GDP Regulations</a>
require you to give us permission to store and use your data, including:
avatar first and last name, avatar UUID, and your IP address, for you and the avatars you may interact with.
<br >
Other activity in Opensim, such as Friendships,
Friendship Requests, Instant Messages, Profiles, and inventory exchanges, may also expose this information,
regardless if you actually travel to a foreign grid.
<br >
Your data will only be used for the purpose of your visits here and your interactions with other users. We will not share it with any 3rd party
unless required to do so by law. 
<br />
<br />
Accepting the agreement below indicates acceptance of the Terms of Service and, if applicable, the GDPR.
<br />
<br />
<div id="formdata">
<form action="" method="post">
<label>Avatar Name : </label><?php echo $row['avatarname']; ?>
<input type="hidden" name="token" id="token" value="<?php echo $arr['token']; ?>">
<ul>
<li>I agree to the Terms of Service</li>
<li>I confirm I am 18 years of age or older</li>
<li>If I live in the EU, I give you my permission to collect and use my data as indicated above</li>
</ul>
<input id="b1" type="button" value="YES" name="CONFIRMAUTH" onclick="sendAjax()" />&nbsp;&nbsp;&nbsp;&nbsp;
<input id="b2" type="button" value="NO" name="CONFIRMAUTH" onclick="rejected()" />
</form>
</div>
<?php } ?>

</div> <!-- the last </div> on the page -->

<script type="text/javascript">


function sendAjax() {
	$('#b1, #b2').attr('disabled', true);
	req = new XMLHttpRequest();
	req.open("GET", "<?php echo $_SERVER['PHP_SELF']; ?>?avatarname=<?php echo $avatarname; ?>&CONFIRMAUTH=YES", true);
	req.onreadystatechange = function() {
		if ((req.readyState == 4) && (req.status == 200)) {
			xml = req.responseText;
			if($(xml).find("status").text()=='1') {
				$('#formdata').html("<strong><?php echo $avatarname; ?></strong><br /><?php echo $confirmyes; ?>");
			} else if($(xml).find("status").text()=='0') {
				$('#formdata').html("<?php echo $ajaxerror; ?>");
			}
		}
	}
	req.send();
}

function rejected() {
	$('#formdata').html("<strong><?php echo $avatarname; ?></strong><br /><?php echo $confirmno; ?>");
}

</script>

</body>
</html>


