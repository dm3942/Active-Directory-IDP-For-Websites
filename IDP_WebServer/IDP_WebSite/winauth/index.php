<?php
/* winauth 

   This page won't appear if invalid credentials are supplied
   When invalid credentials are supplied HTTP Error 401.2 - Unauthorized
   is returned.

   When valid credentials are supplied, a logon token is created.
   The logon_token and session are saved to a file called
   logon_token.txt. 
   
   logon_token.txt is used by verify.php to validate the logon_token
   send back to the users browser.

   If saving logon tokens to a file:
    php.ini setting required to enable write to file 
    ; http://php.net/open-basedir
    open_basedir = "C:\inetpub\wwwroot\iisidp\logontokens"
    Then grant the appropriate security permissions to everyone (yes not nice), try
    turning off impersonation for scripts in authentcated directories in php.ini
    Recommended to restrict the permissions to "files and objects in this container"
    http://blog.chrismeller.com/enabling-php-write-access-on-iis

   If using the powershell script to save logon token, make sure it is running
   and is only configured to accept requests from localhost connections.
   Do NOT create a firewall for the PS rest api service running on 8008.
   The PS rest api must run on the iisidp server.
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
Make sure that you have a virtual called 'winauth' with 
windows authentication enabled and anonymous access disabled.
The image below shows an example configuration. Whether 
you are using basic auth or windows authentication, be 
sure to configure SSL.

    </pre>
    <img src='authenticationsettings.png'>
    </body>
    </html><?php
    exit;
}

/* Create a logon token */
function createlogontoken()
{
    if (function_exists('com_create_guid') === true)
        return trim(com_create_guid(), '{}');

    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/* Process Authenication */
$loggedonuser = $_SERVER['LOGON_USER'];
$session = $_GET['session'];
$appid = $_GET['appid'];
if(!empty($loggedonuser)) {
    if(!empty($session)) {
        /* look for the appid and retrieve the return URL */
        $appidfilename = "C:\\inetpub\\wwwroot\\iisidp\\authorizedapps\\".$appid.".appid";
        $appidfile = fopen($appidfilename, "r") or die("ERROR: App is not registered.");
        $appdata = fread($appidfile, filesize($appidfilename)); // return to the company website URL that can handle
        $appreturnURL = trim(explode("\n", $appdata)[0]);
        try { $appsecret = trim(explode("\n", $appdata)[1]); } catch (Exception $e) {  }
        fclose($appidfile);

        /* Create guid and save to logon token file. 
           Originally the logontokens were saved using file operations.
           Later this was changed to use a rest service. The rest service is a standalone powershell script. */
        /* TO DO: Check that the logon token doesn't exist usin it. As there is a very very very small chance of a collision. */
        $logontoken = createlogontoken(); 
        /* $ltfile = fopen("C:\\inetpub\\wwwroot\\iisidp\\logontokens\\".$logontoken.".txt", "w") or die("ERROR: Unable to save logon token.");
        if(!empty($loggedonuser)) { fwrite($ltfile, "loggedonuser:".$loggedonuser."\n"); }
        if(!empty($session))      { fwrite($ltfile, "session:".$session."\n"); }
        fclose($ltfile); */
        $url = 'http://localhost:8008/logontoken='.$logontoken;
        $data = '';
        if(!empty($loggedonuser)) { $data .= "loggedonuser=".$loggedonuser."\n"; }
        if(!empty($session))      { $data .= "session=".$session."\n"; }
        $options = array(
            'http' => array( // use key 'http' even if you send the request to https://...
                'header'  => "Content-type: plain/text\r\n",
                'method'  => 'POST',
                'content' => $data /* http_build_query($data) */
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) { /* Handle error */ }
        
        /* send logon token to webserver. i.e Redirect to http://localhost:82/logon.php?logontoken=49fd9dkfirjxvnakz  */
        header("Refresh:3; url=".$appreturnURL."?logontoken=".$logontoken);
        //FAST header("Location: ".$appreturnURL."?logontoken=".$logontoken); 
        exit;

        ?><pre><?php var_dump($result); ?></pre><?php 

        ?><pre><?php echo("Location: ".$appreturnURL."?logontoken=".$logontoken."\n"); ?></pre><?php    

        ?><pre><?php echo("Webserver session received: ".$session."\n"); ?></pre><?php
    }
    ?><pre><?php echo("Authentication successful for user: ".$loggedonuser."\n"); ?></pre><?php
}
?>
