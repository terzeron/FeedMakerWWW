<?php
error_reporting(E_ALL ^ E_NOTICE);

$home_dir = "/Users/terzeron";
$work_dir = $home_dir . "/workspace/fma";
$engine_dir = $home_dir . "/workspace/fm";
$www_dir = $home_dir . "/public_html";


function print_line($line)
{
    #$line = preg_replace("/</", "&lt;", $line);
    #$line = preg_replace("/>/", "&gt;", $line);
    $line = preg_replace("/\033\[1;(\d+)m/", "<span class='c$1'>", $line);
    $line = preg_replace("/\033\[0(;0)?m/", "</span>", $line);
    $line = preg_replace("/^==+$/", "<hr/>", $line);
    $line = preg_replace("/^\/(Users|home\d+).*\/(public_html(\/xml\/?)?)?/", "", $line);
    print "<span>" . $line . "</span><br>\n";
}

function txt2html($content)
{
    $lines = explode("\n", $content);
    $div_open = 0;
    foreach ($lines as $line) {
        if (preg_match("/^\s*$/", $line) || $line == "Warning: can't get old list!") {
            continue;
        } elseif (preg_match("/^=====\s+([^=]+)\s+=====\s*$/", $line)) {
	    if ($div_open == 1) {
		print "</div>\n";
		print "</div>\n";
		$div_open = 0;
	    }
            $line = preg_replace("/^=====\s+([^=]+)\s+=====\s*$/", "<div class='panel panel-default'>\n<div class='panel-heading'>$1</div>\n<div class='panel-body'>\n", $line);
	    print_line($line);
	    $div_open = 1;
	} elseif (preg_match("//", $line)) {
            $line = preg_replace("/^---\s+([^\-]+)\s+---\s*$/", "<h5>$1</h5>\n", $line);
	    print_line($line);
	}
    }
}

function read_conf_file($conf_file_path)
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
		if ($dir1 == "." or $dir1 == ".." or !is_dir($category_dir_path)) {
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
			    $conf = read_conf_file($conf_file_path);
			    $id2conf_map[$dir2] = $conf;
			}
		    }
		}
	    }
	}
    }
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
	print "'" . $id . "' : '" . get_name_of_feed($conf) . "', ";
    }
    print "};";
}

?>
