<div id="fb-root"></div>
<?php
// Awesome Facebook Application
// 
// Name: SysSecProject
// 

require_once 'Facebook/src/facebook.php';

// Create our Application instance.
$facebook = new Facebook(array(
	'appId'  => '154808361231346',
	'secret' => '7f9207e5b3bbeaae75b234eb1d47dca8',
	'cookie' => true,
));

/** DB for random numbers **/
try {
	$db=new SQLiteDatabase("db.sqlite",0666,$error); //a database to store the randomnumber
}
catch (Exception $e){
	die($error);
}

$db->query("BEGIN;
                CREATE TABLE users(uid CHAR(20) PRIMARY KEY, key_type CHAR(255), key_value CHAR(255), expiry INTEGER);
        COMMIT;");

// We may or may not have this data based on a $_GET or $_COOKIE based session.
//
// If we get a session here, it means we found a correctly signed session using
// the Application Secret only Facebook and the Application know. We dont know
// if it is still valid until we make an API call using the session. A session
// can become invalid if it has already expired (should not be getting the
// session back in this case) or if the user logged out of Facebook.
$session = $facebook->getSession();

$me = null;
// Session based API call.
if ($session)
{
	try 
	{
		$uid = $facebook->getUser();
		$me = $facebook->api('/me');
	} catch (FacebookApiException $e) 
	{
		error_log($e);
	}
}

// login or logout url will be needed depending on current user state.
if ($me) 
{
	$logoutUrl = $facebook->getLogoutUrl();
} 
else 
{
	$loginUrl = $facebook->getLoginUrl();
}
?>
<script>
	window.fbAsyncInit = function() {
	FB.init({
	  appId   : '<?php echo $facebook->getAppId(); ?>',
	  session : <?php echo json_encode($session); ?>, // don't refetch the session when PHP already has it
	  status  : true, // check login status
	  cookie  : true, // enable cookies to allow the server to access the session
	  xfbml   : true // parse XFBML
	});

	// whenever the user logs in, we refresh the page
	FB.Event.subscribe('auth.login', function() {
	  window.location.reload();
	});
	};

	(function() {
		var e = document.createElement('script');
		e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
		e.async = true;
		document.getElementById('fb-root').appendChild(e);
	}());
</script>
<!-- Allows the user to login/logout //-->
<?php if ($me): ?>
	<a href="<?php echo $logoutUrl; ?>">
		<img src="http://static.ak.fbcdn.net/rsrc.php/z2Y31/hash/cxrz4k7j.gif">
	</a>
<?php else: ?>
<div>
	Without using JavaScript &amp; XFBML:
	<a href="<?php echo $loginUrl; ?>">
		<img src="http://static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif">
	</a>
</div>
<?php endif ?>

<h3>Session</h3>
<?php if ($me): ?>
<?php
/**
 *	Set the cookie for this session
**/
	$randomToken = rand(1, getrandmax());
	setcookie("randomToken", $randomToken, $expire=time()+60);

/********************************************************************
	Insert random key and the user info in to SQLite database table
**********************************************************************/
$db->query("BEGIN;
		INSERT INTO users(uid,key_type,key_value,expiry) VALUES($uid,'random_token',$randomToken,$expire);
	COMMIT;");

//fetch the data 
$result = $db->query("SELECT * FROM users");
while($result->valid()){
	$row = $result->current();
	print_r($row);
	$result->next;
	}
/***********************************************************/
?>
<pre><?php print_r($session); ?></pre>

<h3>You</h3>
<img src="https://graph.facebook.com/<?php echo $uid; ?>/picture">
<?php echo $me['name']; ?>

<h3>Your User Object</h3>
<pre><?php print_r($me); ?></pre>
<?php else: ?>
	<strong><em>You are not Connected.</em></strong>
<?php endif ?>
