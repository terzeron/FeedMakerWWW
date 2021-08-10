<?php
require("common.php");

function get_feed_list($category_name)
{
    global $work_dir, $dir, $message;

    $feed_list = array();
    $category_path = $work_dir . "/" . $category_name;
    if (is_dir($category_path)) {
        if ($dh = opendir($category_path)) {
            while (($feed_name = readdir($dh))) {
		        if ($feed_name == "." or $feed_name == ".." or $feed_name[0] == ".") {
		            continue;
		        }
		        $conf_file_path = $category_path . "/" . $feed_name . "/conf.xml";
                if (file_exists($conf_file_path)) {
                    array_push($feed_list, $feed_name);
                }
            }
        }
    }

    $message = $feed_list;
    return 0;
}


function get_feed_content($category_name,  $sample_feed)
{
    global $work_dir, $dir, $message;

    $message = view($sample_feed, $category_name);
    return 0;
}


function view($feed_name, $category_name="")
{
    global $work_dir, $dir;

    if ($category_name == "") {
        if ($dh = opendir($work_dir)) {
            while (($dir_name = readdir($dh))) {
                if (file_exists($work_dir . "/" . $dir_name . "/" . $feed_name) and file_exists($work_dir . "/" . $dir_name . "/" . $feed_name . "/conf.xml")) {
                    $category_name = $dir_name;
                    break;
                }
            }
        }
    }

    $filepath = $work_dir . "/" . $category_name . "/" . $feed_name . "/conf.xml";
    $content = "";                                                           
    if (file_exists($filepath)) {
        $fp = fopen($filepath, "r");                                                
        while (!feof($fp)) {                                                      
            $line = fread($fp, 4096);                                               
            $content .= $line;
        }
        fclose($fp);
    } else {
        $content = "can't find such a feed, ${filepath}";
        return -1;
    }

    return $content;
}


function save($category_name, $feed_name)
{
    global $work_dir, $dir, $message;

    $filepath = $work_dir . "/" . $category_name . "/" . $feed_name . "/conf.xml";
    if (file_exists($filepath)) {
        $message = "can't overwrite the existing file";
        return -1;
    }

    $text = $_POST["xml_text"];
    $fp = fopen("$dir/${feed_name}.xml", "w");
    if (!$fp) {
        $message = "can't open file '$feed_name.xml' for writing";
        return -1;
    }
    $result = fwrite($fp, $text, 4096); 
    if ($result == false) {
        $message = "can't write data to file";
        return -1;
    }
    fclose($fp);
    
    return 0;
}


function lint($feed_name)
{
    global $dir, $message;

    $cmd = "/usr/bin/xmllint --noout $dir/${feed_name}.xml";
    $result = system($cmd);
    if ($result != "") {
        $message = "can't execute a 'lint' command or fail to validate the configuration file";
        return -1;
    }

    return 0;
}

function install($category_name, $feed_name)
{
    global $home_dir, $engine_dir, $work_dir, $www_dir, $dir, $message;

    mkdir("${work_dir}/${category_name}/${feed_name}");
    chdir("${work_dir}/${category_name}/${feed_name}");
    if (!rename("${www_dir}/fm/xmls/${feed_name}.xml", "${work_dir}/${category_name}/${feed_name}/conf.xml")) {
        $message = "can't rename '${www_dir}/fm/xmls/${feed_name}.xml' to '${work_dir}/${category_name}/${feed_name}/conf.xml'";
        return -1;
    }

    $cmd = "cd ${work_dir}/${category_name}/${feed_name}; git add conf.xml > /dev/null; git commit -m 'add some new feeds' > /dev/null";
    $result = shell_exec($cmd);
    if (preg_match("/Error:/", $result)) {
        $message = "can't add new feed configuration file to git repo, '$cmd', $result";
        return -1;
    }

    return 0;
}


function extract_data($category_name, $feed_name)
{
    global $home_dir, $engine_dir, $work_dir, $www_dir, $dir, $message;

    $cmd = "cd ${work_dir}/${category_name}/${feed_name}; bash -c '(. /home/terzeron/.bashrc; . /home/terzeron/workspace/fm/bin/setup.sh; is_completed=\$(grep \"<is_completed>true\" conf.xml); recent_collection_list=\$([ -e newlist ] && find newlist -type f -mtime +144); if [ \"\$is_completed\" != \"\" -a \"\$recent_collection_list\" == \"\" ]; then run.py -c; fi; run.py)'";
    $result = shell_exec($cmd);
    if (preg_match("/Error:/", $result)) {
        $message = "can't extract the feed, '$cmd', $result";
        return -1;
    }
    $message = $cmd . "," . $result;
    
    return 0;
}


function setacl($category_name, $feed_name, $sample_feed)
{
    global $www_dir, $dir, $message;

    $time_str = get_time_str();
    $cmd = "cp ${www_dir}/.htaccess ${www_dir}/.htaccess.$time_str";
    $ret = shell_exec($cmd);
    $infile = "${www_dir}/.htaccess";
    $outfile = $infile . ".temp." . $time_str;

    $infp = fopen($infile, "r");
    if (!$infp) {
        $message = "can't open file '$infile' for reading";
        return -1;
    }
    $outfp = fopen($outfile, "w");
    if (!$outfp) {
        $message = "can't open file '$infile' for writing";
        return -1;
    }
    $did_write = 0;
    while (!feof($infp)) {
        $content = fgets($infp);
        if ($did_write == 0 && preg_match("/${sample_feed}\\\.xml/", $content)) {
            fputs($outfp, "RewriteRule\t^$feed_name\\.xml\$\txml/$feed_name\\.xml\n");
            $did_write = 1;
        }
        fputs($outfp, $content);
        if ($did_write == 0 && preg_match("/^\#.*\(${category_name}\)/", $content)) {
            fputs($outfp, "RewriteRule\t^$feed_name\\.xml\$\txml/$feed_name\\.xml\n");
            $did_write = 1;
        }
    }
    fclose($outfp);
    fclose($infp);

    if (!rename($outfile, $infile)) {
        $message = "can't rename file '$infile' to '$outfile'";
        return -1;
    }
    
    return 0;
}


function remove($category_name, $sample_feed)
{
    global $work_dir, $www_dir, $dir, $message;

    $time_str = get_time_str();
    $cmd = "cp ${www_dir}/.htaccess ${www_dir}/.htaccess.$time_str";
    $ret = shell_exec($cmd);
    $infile = "${www_dir}/.htaccess";
    $outfile = $infile.".temp.".$time_str;

    $infp = fopen($infile,"r");
    if (!$infp) {
        $message = "can't open file '$infile' for reading, $ret";
        return -1;
    }
    $outfp = fopen($outfile, "w");
    if (!$outfp) {
        $message = "can't open file '$infile' for writing, $ret";
        return -1;
    }
    while (!feof($infp)) {
        $content = fgets($infp);
        if (preg_match("/${sample_feed}\\\.xml/", $content)) {
            continue;
        }
        fputs($outfp, $content);
    }
    fputs($outfp, "RewriteRule\t^(xml/)?${sample_feed}\\.xml\$\t- [G]\n");
    fclose($outfp);
    fclose($infp);

    if (!rename($outfile, $infile)) {
        $message = "can't rename file '$infile' to '$outfile'";
        return -1;
    }
    
    $cmd = "rm -rf ${www_dir}/xml/${sample_feed}.xml ${www_dir}/xml/{img,pdf}/${sample_feed}; cd ${work_dir}/${category_name}; git rm ${sample_feed}/conf.xml > /dev/null; git commit -m 'remove unnecessary feeds' > /dev/null; rm -rf ${sample_feed}";
    $result = system($cmd);
    if ($result != "") { 
        $message = "can't clean the feed directory, $result";
        return -1;
    }
    
    return 0;
}


function disable($category_name, $sample_feed)
{
    global $work_dir, $www_dir, $dir, $message;

    $time_str = get_time_str();
    $cmd = "cp ${www_dir}/.htaccess ${www_dir}/.htaccess.$time_str";
    $ret = shell_exec($cmd);
    $infile = "${www_dir}/.htaccess";
    $outfile = $infile.".temp.".$time_str;

    $infp = fopen($infile,"r");
    if (!$infp) {
        $message = "can't open file '$infile' for reading, $ret";
        return -1;
    }
    $outfp = fopen($outfile, "w");
    if (!$outfp) {
        $message = "can't open file '$infile' for writing, $ret";
        return -1;
    }
    while (!feof($infp)) {
        $content = fgets($infp);
        if (preg_match("/${sample_feed}\\\.xml/", $content)) {
            continue;
        }
        fputs($outfp, $content);
    }
    fputs($outfp, "RewriteRule\t^(xml/)?${sample_feed}\\.xml\$\t- [G]\n");
    fclose($outfp);
    fclose($infp);

    if (!rename($outfile, $infile)) {
        $message = "can't rename file '$infile' to '$outfile'";
        return -1;
    }
    
    $cmd = "rm -f ${www_dir}/xml/${sample_feed}.xml; cd ${work_dir}/${category_name}; git mv ${sample_feed} _${sample_feed} > /dev/null; git commit -m 'disable feed '${sample_feed} > /dev/null; cd _${sample_feed}; rm -rf run.log error.log cookie.txt html newlist ${sample_feed}.xml ${sample_feed}.xml.old start_idx.txt; ";
    $result = system($cmd);
    if ($result != "") { 
        $message = "can't clean the feed directory, $result";
        return -1;
    }
    
    return 0;
}


function exec_command()
{
    global $message, $dir;
    
    $feed_name = (array_key_exists("feed_name", $_POST) ? $_POST["feed_name"] : $_GET["feed_name"]);
    if (!preg_match("/^[\w_\-\.]*$/", $feed_name)) {
        $message = "The feed name must be only alphanumeric word.";
        return -1;
    }
    $category_name = (array_key_exists("category_name", $_POST) ? $_POST["category_name"] : $_GET["category_name"]);
    if (!preg_match("/^[\w_\-\.]*$/", $category_name)) {
        $message = "The parent name must be only alphanumeric word.";
        return -1;
    }
    $sample_feed = (array_key_exists("sample_feed", $_POST) ? $_POST["sample_feed"] : $_GET["sample_feed"]);
    if (!preg_match("/^[\w_\-\.]*$/", $sample_feed)) {
        $message = "The sample feed name must be only alphanumeric word.";
        return -1;
    }
    $command = (array_key_exists("command", $_POST) ? $_POST["command"] : $_GET["command"]);
    if ($command == "get_feed_list") {
        return get_feed_list($category_name);
    } else if ($command == "get_feed_content") {
        return get_feed_content($category_name, $sample_feed);
    } else if ($command == "view") {
        print(str_replace("&gt;", "&gt;<br>", htmlentities(view($feed_name))));
        exit(0);
    } else if ($command == "save") {
        return save($category_name, $feed_name);
    } else if ($command == "lint") {
        return lint($feed_name);
    } else if ($command == "install") {
        return install($category_name, $feed_name);
    } else if ($command == "extract") {
        return extract_data($category_name, $feed_name);
    } else if ($command == "setacl") {
        return setacl($category_name, $feed_name, $sample_feed);
    } else if ($command == "remove") {
        return remove($category_name, $sample_feed);
    } else if ($command == "disable") {
        return disable($category_name, $sample_feed);
    } else {
        $message = "can't identify the command '$command'";
        return -1;
    }
    
    return 1;
}


$result = exec_command();
print '{ "result" : "' . $result . '", "message" : ' . json_encode($message) . ' }';
?>

