<?php
/*
Process windows authentication, the iisidp will redirect browsers and operating systems
that are not compatible with windows authentication to the basic auth page or digest page. 
*/
$user_agent = $_SERVER['HTTP_USER_AGENT'];
function getOS() { 
    global $user_agent;
    $os_platform  = "Unknown OS Platform";
    $os_array     = array(
                          '/windows nt 10/i'      =>  'Windows 10',
                          '/windows nt 6.3/i'     =>  'Windows 8.1',
                          '/windows nt 6.2/i'     =>  'Windows 8',
                          '/windows nt 6.1/i'     =>  'Windows 7',
                          '/windows nt 6.0/i'     =>  'Windows Vista',
                          '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
                          '/windows nt 5.1/i'     =>  'Windows XP',
                          '/windows xp/i'         =>  'Windows XP',
                          '/windows nt 5.0/i'     =>  'Windows 2000',
                          '/windows me/i'         =>  'Windows ME',
                          '/win98/i'              =>  'Windows 98',
                          '/win95/i'              =>  'Windows 95',
                          '/win16/i'              =>  'Windows 3.11',
                          '/macintosh|mac os x/i' =>  'Mac OS X',
                          '/mac_powerpc/i'        =>  'Mac OS 9',
                          '/linux/i'              =>  'Linux',
                          '/ubuntu/i'             =>  'Ubuntu',
                          '/iphone/i'             =>  'iPhone',
                          '/ipod/i'               =>  'iPod',
                          '/ipad/i'               =>  'iPad',
                          '/android/i'            =>  'Android',
                          '/blackberry/i'         =>  'BlackBerry',
                          '/webos/i'              =>  'Mobile'
                    );
    foreach ($os_array as $regex => $value)
        if (preg_match($regex, $user_agent))
            $os_platform = $value;
    return $os_platform;
}

function getBrowser() {
    global $user_agent;
    $browser        = "Unknown Browser";
    $browser_array = array(
                            '/Mozilla.5.. .Windows NT 10/i' => 'Internet Explorer', 
                            '/msie/i'      => 'Internet Explorer',
                            '/firefox/i'   => 'Firefox',
                            '/safari/i'    => 'Safari',
                            '/chrome/i'    => 'Chrome',
                            '/edge/i'      => 'Edge',
                            '/opera/i'     => 'Opera',
                            '/netscape/i'  => 'Netscape',
                            '/maxthon/i'   => 'Maxthon',
                            '/konqueror/i' => 'Konqueror',
                            '/mobile/i'    => 'Handheld Browser'
                     );
    foreach ($browser_array as $regex => $value)
        if (preg_match($regex, $user_agent))
            $browser = $value;
    return $browser;
}

$user_os        = getOS();
$user_browser   = getBrowser();

/* if the session id isn't present, show the default page */
$session = $_GET['session'];
$appid = $_GET['appid'];
if(empty($session)) {
    ?><h1>iisidp - test page</h1><pre><?php
    $device_details = "<strong>Browser: </strong>".$user_browser."<br /><strong>Operating System: </strong>".$user_os."";
    print_r($device_details);
    echo("<br />".$_SERVER['HTTP_USER_AGENT']."<br /><br /><br />");
    echo("<a href='/winauth'>Test windows authentication</a><br />");
    echo("<a href='/basic'>Test basic authentication</a><br />");
    ?></pre><?php
} else {
    /* if the session variable is present we will proceed to process the authentication */
    if(stripos($user_os, 'Windows') !== false) {
        if(stripos($user_browser, 'Internet Explorer') !== false ||
           stripos($user_browser, 'Chrome') !== false            ||
           stripos($user_browser, 'Edge') !== false             ) {
            /* Send to windows authentication page */
            header("Refresh:3; url=http://localhost:81/winauth/?session=".$session."&appid=".$appid);
            //FAST header("Location: http://localhost:81/winauth/?session=".$session."&appid=".$appid); 
            exit;
        }
    } 
    /* Send to basic authentication page */
    //SLOW header("Refresh:3; url=http://localhost:81/basic/?session=".$session."&appid=".$appid);
    header("Location: http://localhost:81/basic/?session=".$session."&appid=".$appid); 
    exit;
}
?>