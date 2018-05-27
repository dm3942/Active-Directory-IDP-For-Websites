<html>
<title></title>
<header>
</header>
<body>
<h1>SSO web app</h1>
<?php
/* check that the phpsession have been stored in a cookie, if not refresh the page */
$phpsession = $_COOKIE["PHPSESSID"];
if (empty($phpsession)) {
    ?><a href='/logon.php'>Click here to SIGN IN</a></body></html><?php
    exit;
}

/* Verify user has logged on */
if (ctype_alnum($phpsession) && file_exists("C:\\inetpub\\wwwroot\\sssoapp\\loggedonsessions\\".$phpsession.".txt")) {
    /* Logon session found */
    $iisidpuser = file_get_contents("C:\\inetpub\\wwwroot\\sssoapp\\loggedonsessions\\".$phpsession.".txt"); 
    ?><pre>Welcome <?php echo explode("=",explode("\n",$iisidpuser)[0])[1]; ?></pre>
    <a href='/logout.php'>Logout</a><?php
} else {
    /* No logon session found */
    ?><a href='/logon.php'>Click here to LOGON</a></body></html><?php
    exit;
}

?>
</body>
</html>