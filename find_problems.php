<?php
require "common.php";

if (!is_client_local_ip()) {
    require dirname(__FILE__) . "/oauth/oauth_check.php";
}

$problems = file_get_contents("$work_dir/logs/find_problems.log");
$status_json = file_get_contents("status.json");
$list = json_decode($status_json, true);
?>
<!DOCTYPE HTML>
<html>
    <?include("header.html");?>

    <body>
        <?include("menu.html");?>
            
        <!--div class="card"-->
            <?=txt2html($problems)?>

            <table class="col-md-12 col-lg-12 table table-bordered table-condensed cf" id="no-more-tables">
                <thead class="cf">
                    <tr>
                        <th>이름</th>
                        <th>외부</th>
                        <th>내부</th>
                        <th class="narrow">http request</th>
                        <th class="narrow">htaccess</th>
                        <th class="narrow">public_html</th>
                        <th class="narrow">feedmaker</th>
                        <th>last request date</th>
                        <th>관리</th>
                    </tr>
                </thead>
                
                <tbody>
                    <?foreach ($list as $item) {?>
                        <?
                        $feed_alias = $item["feed_alias"];
                        $feed_name = $item["feed_name"];
                        $http_request = ($item["http_request"] ? "O" : "X");
                        $htaccess = ($item["htaccess"] ? "O" : "X");
                        $public_html = ($item["public_html"] ? "O" : "X");
                        $feedmaker = ($item["feedmaker"] ? "O" : "X");
                        $last_request_date = $item["last_request_date"];
                        
                        $conf = $id2conf_map[$feed_name];
                        $name = get_name_of_feed($conf);
                        $feed_dir = $conf["feed_dir"];
                        ?>
                        <tr>
                            <td data-title="이름" class='name'><?=$name?></td>
                            <td data-title="외부" class='external'>
                                <a href='https://terzeron.com/<?=$feed_alias?>.xml' target='_blank'>
                                    <?if ($feed_alias != $feed_name) {?>
                                        <strong><i><?=$feed_alias?></i></strong>
                                    <?} else {?>
                                        <?=$feed_alias?>
                                    <?}?>
                                </a>
                            </td>
                            <td data-title="내부" class='internal'>
                                <a href='https://terzeron.com/xml/<?=$feed_name?>.xml' target='_blank'>
                                    <?if ($feed_alias != $feed_name) {?>
                                        <?=$feed_name?>
                                    <?}?>
                                </a>
                            </td>
                            <td data-title="http request" class='http_request'><?=$http_request?></td>
                            <td data-title="htaccess" class='htaccess'><?=$htaccess?></td>
                            <td data-title="public_html" class='public_html'><?=$public_html?></td>
                            <td data-title="feedmaker" class='feedmaker'><?=$feedmaker?></td>
                            <td data-title="last request date" class='last_request_date'><?=$last_request_date?></td>
                            <td data-title="관리" class='management'>
                                <a href='add_feed.php?feed_dir=<?=$feed_dir?>&feed_name=<?=$feed_name?>'><?=$feed_name?></a>
                            </td>
                        </tr>
                    <?}?>
                </tbody>
            </table>
        <!--/div-->
    </body>
</html>
