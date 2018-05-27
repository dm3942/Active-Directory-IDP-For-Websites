<?php
/* basic auth

   This page won't appear if invalid credentials are supplied
   When invalid credentials are supplied HTTP Error 401.2 - Unauthorized
   is returned.

   When valid credentials are supplied, a logon token is created.
   The logon_token and session are saved to a file called
   logon_token.txt. 
   
   logon_token.txt is used by verify.php to validate the logon_token
   send back to the users browser.
*/

/* check configuration */
if (empty($_SERVER['LOGON_USER']))
{
    ?><html>
    <title></title>
    <header></header>
    <body>
    <pre>
    
If you see this please check your IIS configuration. 
Make sure that you have a virtual called 'basic' with 
basic authentication enabled and anonymous access disabled.
The image below shows an example configuration. Whether 
you are using basic auth or windows authentication, be 
sure to configure SSL.

    </pre>
    <img src='basicauthentication.png'>
    </body>
    </html><?php
    exit;
}


function createlogontoken()
{
    if (function_exists('com_create_guid') === true)
        return trim(com_create_guid(), '{}');

    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/* Process authenication */
$loggedonuser = $_SERVER['LOGON_USER'];
$session = $_GET['session'];
$appid = $_GET['appid'];
if(!empty($loggedonuser)) {
    if(!empty($session)) {
        /* look for the appid and retrieve the return URL */
        $appidfilename = "C:\\inetpub\\wwwroot\\iisidp\\authorizedapps\\".$appid.".appid";
        $appidfile = fopen($appidfilename, "r") or die("ERROR: App is not registered.");
        $appreturnURL = fread($appidfile, filesize($appidfilename)); // return to the company website URL that can handle
        fclose($appidfile);
        /* create guid and save to logon token file. */
        $logontoken = createlogontoken(); 
        $ltfile = fopen("C:\\inetpub\\wwwroot\\iisidp\\logontokens\\".$logontoken.".txt", "w") or die("ERROR: Unable to save logon token.");
        if(!empty($loggedonuser)) { fwrite($ltfile, "loggedonuser:".$loggedonuser."\n"); }
        if(!empty($session))      { fwrite($ltfile, "session:".$session."\n"); }
        fclose($ltfile);
        /* send logon token to webserver. i.e Redirect to http://localhost:82/logon.php?logontoken=49fd9dkfirjxvnakz  */
        header("Location: ".$appreturnURL."?logontoken=".$logontoken); 
        exit;
        
        ?><pre><?php echo("Location: ".$appreturnURL."?logontoken=".$logontoken."\n"); ?></pre><?php    

        ?><pre><?php echo("Webserver session received: ".$session."\n"); ?></pre><?php
    }
    ?><pre><?php echo("Authentication successful for user: ".$loggedonuser."\n"); ?></pre><?php
}
?>
