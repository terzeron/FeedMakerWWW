<?
require_once "oauth/oauth_check.php";
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width" />
        <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
        <link href="/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet"/>
        <link href="/bootstrap-honoka/dist/css/bootstrap.min.css" rel="stylesheet"/>
        <link href="/font-awesome/css/font-awesome.min.css" rel="stylesheet"/>
        <link href="style.css" rel="stylesheet"/>
        <script src="/jquery/dist/jquery.min.js"></script>
        <script src="/jquery-ui/jquery-ui.min.js"></script>
        <script src="/bootstrap/dist/js/bootstrap.min.js"></script>
        <title>FeedMaker 관리</title>
    </head>
    <body>
        <div id="tabs" class="container">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#tab1" data-toggle="tab">실행 결과</a></li>
                <li><a href="#tab2" data-toggle="tab">문제점 조회</a></li>
                <li><a href="#tab3" data-toggle="tab">피드 관리</a></li>
            </ul>

            <div class="tab-content">
                <div id="tab1" class="tab-pane active">
                    <p>
                        <?include("show_log.php");?>
                    </p>
                </div>
                <div id="tab2" class="tab-pane">
                    <p>
                        <?include("find_problems.php");?>
                    </p>
                </div>
                <div id="tab3" class="tab-pane">
                    <p>
                        <?include("add_feed.php");?>
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
