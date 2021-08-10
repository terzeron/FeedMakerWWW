<?php
require "common.php";
require "vendor/autoload.php";

if (!is_client_local_ip()) {
    require dirname(__FILE__) . "/oauth/oauth_check.php";
}

$markdown_text = file_get_contents("$work_dir/logs/find_problems.log");
$parsedown = new Parsedown();
$html_content = $parsedown->text($markdown_text);
?>
<!DOCTYPE HTML>
<html>
    <?include("header.html");?>

    <body>
        <?include("menu.html");?>
        <div class="card">
            <div class="card-header">
                문제점
            </div>
            <div class="card-body"> 
               <?=$html_content?>
            </div>
        </div>
    </body>
</html>
