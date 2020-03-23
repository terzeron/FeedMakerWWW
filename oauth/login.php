<?php
require "../vendor/autoload.php";
require "oauth_common.php";

if (!session_id()) {
    session_start();
}

$fb = new \Facebook\Facebook($fb_oauth_config);
$helper = $fb->getRedirectLoginHelper();

$loggedin = false;
try {
    // Get the \Facebook\GraphNodes\GraphUser object for the current user.
    // If you provided a 'default_access_token', the '{access-token}' is optional.
    $response = $fb->get('/me', '{access-token}');
    $me = $response->getGraphUser();
    $loggedin = true;
} catch (\Facebook\Exceptions\FacebookResponseException $e) {
    // When Graph returns an error
    $permissions = ['email'];
    $loginUrl = $helper->getLoginUrl($config["callback_url"], $permissions);
    $loggedin = false;
} catch (\Facebook\Exceptions\FacebookSDKException $e) {
    // When validation fails or other local issues
    $loggedin = false;
    $error = true;
}
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <title>Login</title>
        <link href="/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet"/>
        <link href="/bootstrap-honoka/dist/css/bootstrap.min.css" rel="stylesheet"/>
        <link href="/font-awesome/css/font-awesome.min.css" rel="stylesheet"/>
        <link href="../style.css" rel="stylesheet"/>
    </head>
    <body>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <?if ($loggedin) {?>
                <p>Logged in as <?=$me->getName()?></p>
            <?} else {?>
                <?if (isset($error)) {?>
                    <p>Facebook SDK returned an error: <?=$e->getMessage()?></p>
                <?} else {?>
                    <p>You're not logged in Facebook.</p>
                    <div>
                        <a href="<?=$loginUrl?>">
                            <button class="btn btn-primary btn-block btn-social btn-facebook">
                                <i class="fa fa-facebook"></i> Sign in with Facebook
                            </button>
                        </a>
                    </div>
                <?}?>
            <?}?>
        </div>
    </body>
</html>
