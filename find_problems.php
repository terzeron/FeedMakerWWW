<?
require_once "oauth/oauth_check.php";
require_once "common.php";

$content = file_get_contents("$work_dir/logs/find_problems.log");
?>
<div>
    <?php
    txt2html($content);
    ?>
    <?include("diff.html");?>
</div>
    
