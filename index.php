<?php
require "common.php";
require "oauth/oauth_common.php";
if (!session_id()) {
    session_start();
}
if (!is_admin() and !is_client_local_ip()) {
    require "oauth/oauth_check.php";
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
