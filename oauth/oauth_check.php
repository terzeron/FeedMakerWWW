<?php
require "vendor/autoload.php";
require "oauth_common.php";

if (!session_id()) {
    session_start();
}

if (array_key_exists("fb_access_token", $_SESSION)) {
    $accessToken = $_SESSION['fb_access_token'];
}
if (!isset($accessToken)) {
    print("You're not logged in Facebook. Redirecting to " . $config["login_url"]);
    header("Location: " . $config["login_url"]);
    exit(0);
}

//var_dump($fb_oauth_config);
$fb = new Facebook\Facebook($fb_oauth_config);

try {
    // Returns a `Facebook\FacebookResponse` object
    $response = $fb->get('/me?fields=id,name,email', $accessToken);
} catch (Facebook\Exceptions\FacebookResponseException $e) {
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}

$user = $response->getGraphUser();
if ($user['email'] != $config["admin_email"]) {
    print("You're not authorized to this service. Redirecting to " . $config["login_url"]);
    header("Location: " . $config["login_url"]);
}
?>

           
