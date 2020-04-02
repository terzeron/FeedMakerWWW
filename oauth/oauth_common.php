<?php
function read_config($config_file)
{
    $fp = fopen($config_file, "r");
    $config_str = fread($fp, 4096);
    fclose($fp);
    $config = json_decode($config_str, $assoc = true);
    return $config;
}

function is_logged_in()
{
    global $_SESSION;
    return isset($_SESSION) and array_key_exists("fb_access_token", $_SESSION);
}

function is_admin()
{
    global $_SESSION;
    return is_logged_in() and $_SESSION["user_mail"] == $_SESSION["admin_email"];
}

$config = read_config(dirname(__FILE__) . "/conf.json");

$fb_oauth_config = [
    'app_id' => $config["app_id"],
    'app_secret' => $config["app_secret"],
    'default_graph_version' => 'v2.8',
];
?>
