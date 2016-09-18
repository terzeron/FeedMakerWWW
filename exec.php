<?php
header("Cache-Control: no-cache; must-revalidate;");
error_reporting(E_ALL);

require_once("common.php");

$message = "";
$dir = "xmls";


function get_feed_list($category_name)
{
    global $work_dir, $dir, $message;

    $feed_list = array();
    $category_path = $work_dir . "/" . $category_name;
    if (is_dir($category_path)) {
        if ($dh = opendir($category_path)) {
            while (($feed_name = readdir($dh))) {
		if ($feed_name == "." or $feed_name == "..") {
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

    $filepath = $work_dir . "/" . $category_name . "/" . $sample_feed . "/conf.xml";  
    $xml_text = "";                                                           
    if (file_exists($filepath)) {
        $fp = fopen($filepath, "r");                                                
        while (!feof($fp)) {                                                      
            $line = fread($fp, 4096);                                               
            $xml_text .= $line;
        }
        fclose($fp);
    } else {
        $message = "can't find such a feed, ${filepath}";
        return -1;
    }

    $message = $xml_text;
    return 0;
}


function save($category_name, $feed_name)
{
    global $work_dir, $dir, $message;

    $filepath = $work_dir . "/" . $category_name . "/" . $feed_name . "/conf.xml";
    if (file_exists($filepath)) {
        $message = "can't overwrite the existing file";
        return -1;
    }

    // save
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

    // git add
    $cmd = "git add $dir/${feed_name}.xml";
    $result = shell_exec($cmd);
    if (preg_match("/Error:/", $result)) {
        $message = "can't execute a 'save' command '$cmd', $result";
        return -1;
    }
    
    return 0;
}


function lint($feed_name)
{
    global $dir, $message;

    // lint
    $cmd = "/usr/bin/xmllint --noout $dir/${feed_name}.xml";
    $result = system($cmd);
    if ($result != "") {
        $message = "can't execute a 'lint' command";
        return -1;
    }

    return 0;
}


function extract_data($category_name, $feed_name)
{
    global $home_dir, $engine_dir, $work_dir, $www_dir, $dir, $message;

    mkdir("${work_dir}/${category_name}/${feed_name}");
    chdir("${work_dir}/${category_name}/${feed_name}");
    if (!rename("${www_dir}/fm/xmls/${feed_name}.xml", "${work_dir}/${category_name}/${feed_name}/conf.xml")) {
        $message = "can't rename the conf file";
        return -1;
    }

    $cmd = "(\
                . /Users/terzeron/.bashrc; \
                pyenv shell v3.5.2; \
                pyenv activate --quiet; \
                is_completed=\$(grep \"<is_completed>true\" conf.xml); \
                recent_collection_list=\$([ -e newlist ] && find newlist -type f -mtime +144); \
                if [ \"\$is_completed\" != \"\" -a \"\$recent_collection_list\" == \"\" ]; then run.sh -c; fi; \
                run.sh) \
                > $work_dir/$category_name/$feed_name/run.log \
                2> $work_dir/$category_name/$feed_name/run.log";
    $result = shell_exec($cmd);
    if (preg_match("/Error:/", $result)) {
        $message = "can't execute a 'extract' command '$cmd', $result";
        return -1;
    }
    $message = $cmd . "," . $result;
    
    return 0;
}


function setacl($category_name, $feed_name, $sample_feed)
{
    global $www_dir, $dir, $message;

    //date_default_timezone_set("Asia/Seoul");
    
    $timestamp = strftime("%y%m%d%H%M%S");
    $cmd = "cp ${www_dir}/.htaccess ${www_dir}/.htaccess.$timestamp";
    $ret = shell_exec($cmd);
    $infile = "${www_dir}/.htaccess";
    $outfile = $infile . ".temp." . $timestamp;

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
    while (!feof($infp)) {
        $content = fgets($infp);
        if (preg_match("/${sample_feed}\\\.xml/", $content)) {
            fputs($outfp, "RewriteRule\t^$feed_name\\.xml\$\txml/$feed_name\\.xml\n");
        }
        fputs($outfp, $content);
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

    // 
    // ACL 설정 제거
    //

    $timestamp = strftime("%y%m%d%H%M%S");
    $cmd = "cp ${www_dir}/.htaccess ${www_dir}/.htaccess.$timestamp";
    $ret = shell_exec($cmd);
    $infile = "${www_dir}/.htaccess";
    $outfile = $infile.".temp.".$timestamp;

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
    
    //
    // 피드 디렉토리 정리
    //
    $cmd = "rm -f ${www_dir}/xml/${sample_feed}.xml; cd ${work_dir}/${category_name}; git rm ${sample_feed}/conf.xml > /dev/null; rm -rf ${sample_feed}";
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

    // 
    // ACL 설정 제거
    //

    $timestamp = strftime("%y%m%d%H%M%S");
    $cmd = "cp ${www_dir}/.htaccess ${www_dir}/.htaccess.$timestamp";
    $ret = shell_exec($cmd);
    $infile = "${www_dir}/.htaccess";
    $outfile = $infile.".temp.".$timestamp;

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
    
    //
    // 피드 디렉토리 정리
    //
    $cmd = "rm -f ${www_dir}/xml/${sample_feed}.xml; cd ${work_dir}/${category_name}; mv ${sample_feed}  _${sample_feed}; cd _${sample_feed}; rm -rf run.log error.log cookie.txt html newlist ${sample_feed}.xml ${sample_feed}.xml.old start_idx.txt";
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
    
    if ($_SERVER["REQUEST_METHOD"] != "POST"){
        $message = "can't accept method '" . $_SERVER["REQUEST_METHOD"] . "'";
        return -1;
    }
    $feed_name = $_POST["feed_name"];
    if (!preg_match("/^[\w_\-\.]*$/", $feed_name)){
        $message = "The feed name must be only alphanumeric word.";
        return -1;
    }
    $category_name = $_POST["category_name"];
    if (!preg_match("/^[\w_\-\.]*$/", $category_name)){
        $message = "The parent name must be only alphanumeric word.";
        return -1;
    }
    $sample_feed = $_POST["sample_feed"];
    if (!preg_match("/^[\w_\-\.]*$/", $sample_feed)){
        $message = "The sample feed name must be only alphanumeric word.";
        return -1;
    }
    $command = $_POST["command"];
    if ($command == "get_feed_list"){
        return get_feed_list($category_name);
    } else if ($command == "get_feed_content"){
        return get_feed_content($category_name, $sample_feed);
    } else if ($command == "save"){
        return save($category_name, $feed_name);
    } else if ($command == "lint"){
        return lint($feed_name);
    } else if ($command == "extract"){
        return extract_data($category_name, $feed_name);
    } else if ($command == "setacl"){
        return setacl($category_name, $feed_name, $sample_feed);
    } else if ($command == "remove"){
        return remove($category_name, $sample_feed);
    } else if ($command == "disable"){
        return disable($category_name, $sample_feed);
    } else {
        $message = "can't identify the command";
        return -1;
    }
    
    return 1;
}


$result = exec_command();
?>
{ "result" : "<?=$result?>", "message" : <?=json_encode($message)?> }
