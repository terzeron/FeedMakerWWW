<?php
require "common.php";

if (!is_client_local_ip()) {
    require dirname(__FILE__) . "/oauth/oauth_check.php";
}

$content = file_get_contents("$work_dir/logs/all.log");
?>
<!DOCTYPE HTML>
<html>
    <?include("header.html");?>

    <body>
        <?include("menu.html");?>
        
        <div class="card">
            <div class="card-header">
                최근 실행 로그
            </div>
            <div class="card-body">
                <?
                txt2html($content);
                ?>
            </div>
        </div>
    </body>
</html>
