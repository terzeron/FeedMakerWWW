<div>
    <?php
    require_once("common.php");
    $content = file_get_contents("$work_dir/logs/all.log");
    txt2html($content);
    ?>
</div>
