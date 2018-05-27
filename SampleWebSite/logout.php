<?php
$appid = "r9v498fja495j";
$appsecret = "AirconditioningDuctsFor9002";
$idpprovider = "http://localhost:81/";

/* Logout by destroying the php session and logontoken */
$logontoken = '';
if( !empty($_COOKIE['logontoken']) ) { 
    $logontoken = $_COOKIE['logontoken'];
    setcookie("logontoken",''); 
}

if( !empty($_COOKIE["PHPSESSID"]) ) {
    $phpsession = $_COOKIE["PHPSESSID"];
    setcookie("PHPSESSID",''); 
    session_destroy();
    /* Delete session from logged on users. */
    unlink("C:\\inetpub\\wwwroot\\sssoapp\\loggedonsessions\\".$phpsession.".txt");
    /* TODO optionally notify iisidp */
    if(ctype_alnum(str_replace('-','',$logontoken)))
    {
        // send web required to iisidp provide
        // $idpprovider."logout.php?logontoken=".$logontoken."&appid=".$appid."&appsecret=".$appsecret;
    }
}

?>
<h1>SSO web app</h1>
<pre>Logout complete.</pre>
<a href="/">Return to home page</a>