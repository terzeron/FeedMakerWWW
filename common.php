<?php
error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set("Asia/Seoul");

$home_dir = "/home/terzeron";
$work_dir = $home_dir . "/workspace/fma";
$engine_dir = $home_dir . "/workspace/fm";
$www_dir = $home_dir . "/public_html";
$message = "";
$dir = "xmls";

list($id2conf_map, $category_list) = scan_dirs($work_dir);

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

function is_client_local_ip() {
    $ip = getenv('REMOTE_ADDR');
    if (strstr($ip, "192.168.")) {
        return True;
    }
    return False;
}

function get_time_str()
{
    $now = new DateTime();
    return $now->format("YmdHis");
}


function read_feed_conf_file($conf_file_path)
{
    $xml_data = simplexml_load_file($conf_file_path);
    return $xml_data;
}

function get_name_of_feed($conf)
{
    $feed_full_name = (string) $conf->rss->title;
    $feed_name = explode("::", $feed_full_name)[0];
    return $feed_name;
}

function scan_dirs($work_dir)
{
    $id2conf_map = array();
    $category_list = array();
    if (is_dir($work_dir)) {
	    if ($dh = opendir($work_dir)) {
	        while (($dir1 = readdir($dh))) {
		        $category_dir_path = $work_dir . "/" . $dir1;
		        if ($dir1 == "." or $dir1 == ".." or $dir1[0] == "." or !is_dir($category_dir_path) or $dir1 == "test") {
		            continue;
		        }
		        array_push($category_list, $dir1);
		        if ($dh2 = opendir($category_dir_path)) {
		            while (($dir2 = readdir($dh2))) {
			            $conf_file_path = $work_dir . "/" . $dir1 . "/" .  $dir2 . "/conf.xml";
			            if ($dir2 == "." or $dir2 == "..") {
			                continue;
			            }
			            if (is_file($conf_file_path)) {
			                $conf = read_feed_conf_file($conf_file_path);
                            $conf["feed_dir"] = $dir1;
			                $id2conf_map[$dir2] = $conf;
			            }
		            }
		        }
	        }
	    }
    }
    sort($category_list);
    return array($id2conf_map, $category_list);
}

function determine_current_feed_dir($work_dir, $feed_name)
{
    if (!$feed_name) {
	    return "";
    }
    if (is_dir($work_dir)) {
        if ($dh = opendir($work_dir)) {
	        while (($dir = readdir($dh))) {
                if (is_dir($work_dir . "/" . $dir) and $dh2 = opendir($work_dir . "/" . $dir)) {
		            while (($file = readdir($dh2))) {
                        if ($file == $feed_name) {
			                $feed_dir = $dir;
			                break;
                        }
		            }
                }
	        }
        }
    }
    return $feed_dir;
}

function print_id2name_map($id2conf_map)
{
    print "var id2name_map = { ";
    foreach ($id2conf_map as $id => $conf) {
	    print "'" . $id . "' : " . json_encode(get_name_of_feed($conf)) . ", ";
    }
    print "};";
}

$config = read_config(dirname(__FILE__) . "/oauth/conf.json");

$fb_oauth_config = [
    'app_id' => $config["app_id"],
    'app_secret' => $config["app_secret"],
    'default_graph_version' => 'v2.8',
];

?>
