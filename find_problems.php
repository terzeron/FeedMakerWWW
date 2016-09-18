<div>
    <?php
    require_once("common.php");
    $content = file_get_contents("$work_dir/logs/find_problems.log");
    txt2html($content);
    ?>
    <?include("diff.html");?>
</div>
    
