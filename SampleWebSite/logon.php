<?php
$appid = "r9v498fja495j";
$appsecret = "AirconditioningDuctsFor9002";
$idpprovider = "http://localhost:81/";

/* check for php session and create a session if it doesn't already exist */
$session = session_id();
if(empty($session)) { 
    session_start();
}

/* check that the phpsession have been stored in a cookie, if not refresh the page */
$phpsession = $_COOKIE["PHPSESSID"];
if (empty($phpsession)) {
    header("Refresh:0");
    exit;
} 

if(!ctype_alnum($phpsession)) {
    setcookie("PHPSESSID",'');
    header("Refresh:1");
    exit;
}

/* check for logon token from iisidp, if it doesn't exist send user to iisidp for authentication */
if(!empty($_GET['logontoken'])) {
    $logontoken = $_GET['logontoken'];
    setcookie('logontoken','');
} else {
    if(!empty($_COOKIE['logontoken'])) {
        $logontoken = $_COOKIE['logontoken'];
    }
}

function iisidpauthenticate($pidpprovider,$pphpsession,$pappid)
{
   /* Request Authentication */
   setcookie("logontoken",""); // make sure previous logon token is cleared before requesting another.
   header("Refresh:3; url=".$pidpprovider."?session=".$pphpsession."&appid=".$pappid);
   //FAST header("Location: ".$pidpprovider."?session=".$pphpsession."&appid=".$pappid); /* Redirect browser, auth server */
   exit; /* Make sure that code below does not get executed when we redirect. */
}

$username = '';

if(!empty($logontoken)) {
    /* VERIFY logon token */
    // debug output: echo($idpprovider."verify.php?logontoken=".$logontoken."<br />");

    /* Talk with iisidp to verify that the logon token is valid */
    $vfy = file_get_contents($idpprovider."verify.php?logontoken=".$logontoken."&appid=".$appid."&appsecret=".$appsecret); 
    if(empty($vfy)) {
        /* if the response is empty, it means that the logon token is invalid or has expired */
        /* Send user to iisidp to authenticate */
        iisidpauthenticate($idpprovider,$phpsession,$appid);
    } else {
        /* debug output echo ("<pre>Logon details\n".$vfy."\n</pre>"); */
        /* Sign in successful, save logon token in a cookie */
        $ltcookie = setcookie("logontoken",$logontoken);
    }

    /* Store phpsession id in a folder called "loggedonsessions" to track users who have successfully authenticated recently. */
    $lsfile = fopen("C:\\inetpub\\wwwroot\\sssoapp\\loggedonsessions\\".$phpsession.".txt", "w") or die("Unable to open file!");
    if(!empty($vfy))         { fwrite($lsfile, $vfy); }
    fclose($lsfile);

    $username = $vfy;
}
else
{ 
    iisidpauthenticate($idpprovider,$phpsession,$appid);
}

header("Refresh:1; url=/");
?>

<pre><?php
echo "User logon complete<br />";
echo "".$username."<br />";
echo "Session id ".$_COOKIE["PHPSESSID"];
?></pre>