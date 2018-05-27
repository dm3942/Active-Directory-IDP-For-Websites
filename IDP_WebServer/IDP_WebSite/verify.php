<?php

if(!empty($_GET['logontoken'])) { $logontoken = $_GET['logontoken']; } else { echo "logontoken required."; exit; } ;
if(!empty($_GET['appid']))      { $appid = $_GET['appid'];           } else { echo "appid required."; exit; } ;
if(!empty($_GET['appsecret']))  { $appsecret = $_GET['appsecret'];   } else { echo "appsecret required."; exit; } ;

function checkAppDatabase($pappid, $pappsecret) {
    $fappidfilename = "C:\\inetpub\\wwwroot\\iisidp\\authorizedapps\\".$pappid.".appid";
    $fappidfile = fopen($fappidfilename, "r") or die("App is not registered in \authoizedapps\ folder OR Error unable to open appid file.");
    $fappdata = fread($fappidfile, filesize($fappidfilename));
    try { $fappreturnURL = trim(explode("\n", $fappdata)[0]); } catch (Exception $e) { echo("Unable to read app url from database."); exit; }
    try { $fappsecret = trim(explode("\n", $fappdata)[1]);    } catch (Exception $e) { echo("Unable to read app secret from database."); exit; }
    fclose($fappidfile);
    if($fappsecret === $pappsecret) {
        return true;
    }
    return false; 
}

if(!ctype_alnum($appid)) { echo "Invalid appid."; exit; }

if(checkAppDatabase($appid, $appsecret)) {
    /* Success */
} else {
    echo("Invalid appsecret"); exit; 
}

if(ctype_alnum(str_replace('-','',$logontoken))) {
    /* TODO: check age of file. If more than 1 hour old. Return expired */
    // if age of logon token file < 1 hr {  
        
        $vfy = file_get_contents("C:\\inetpub\\wwwroot\\iisidp\\logontokens\\".$logontoken.".txt");
        echo($vfy);
    // else 
        //echo (''); // Expired
        // delete logon token
    //
} else {
    echo("Invalid, token string.");
}
?>