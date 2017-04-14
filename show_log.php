<?
require_once "oauth/oauth_check.php";
require_once "common.php";
$content = file_get_contents("$work_dir/logs/all.log");
?>
<div>
    <?
    txt2html($content);
    ?>
</div>
