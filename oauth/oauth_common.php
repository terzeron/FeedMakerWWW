<?
function read_config($config_file)
{
    $fp = fopen($config_file, "r");
    $config_str = fread($fp, 4096);
    fclose($fp);
    $config = json_decode($config_str, $assoc = true);
    return $config;
}

$config = read_config(dirname(__FILE__) . "/conf.json");

$fb_oauth_config = [
    'app_id' => $config["app_id"],
    'app_secret' => $config["app_secret"],
    'default_graph_version' => 'v2.8',
];


?>
