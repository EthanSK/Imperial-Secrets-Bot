<?php

echo "start of facebook bit";


$fb = new Facebook\Facebook([
  'app_id' => 'sorry',
  'app_secret' => 'its a secret',
  'default_graph_version' => 'v2.12',
  ]);

$helper = $fb->getRedirectLoginHelper();

$permissions = [ 'manage_pages', 'publish_pages', 'publish_actions'];

try {
    if (isset($_SESSION['facebook_access_token'])) {
        $accessToken = $_SESSION['facebook_access_token'];
    } else {
        $accessToken = $helper->getAccessToken();
    }
} catch (Facebook\Exceptions\FacebookResponseException $e) {
    // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();

    exit;
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    // When validation fails or other local issues
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}



if (isset($accessToken)) {
    if (isset($_SESSION['facebook_access_token'])) {
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    } else {
        // getting short-lived access token
        $_SESSION['facebook_access_token'] = (string) $accessToken;

        // OAuth 2.0 client handler
        $oAuth2Client = $fb->getOAuth2Client();

        // Exchanges a short-lived access token for a long-lived one
        $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($_SESSION['facebook_access_token']);

        $_SESSION['facebook_access_token'] = (string) $longLivedAccessToken;

        // setting default access token to be used in script
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    }

    // redirect the user back to the same page if it has "code" GET variable
    if (isset($_GET['code'])) {
        header('Location: ./');
    }

    // getting basic info about user
    try {
        $lastSecretAndWhenPosted = file('lastSecretAndWhenPosted.txt', FILE_IGNORE_NEW_LINES);
        //print_r($lastSecretAndWhenPosted);
        $profile_request = $fb->get('/me');
        $profile = $profile_request->getGraphNode()->asArray();
        print_r($profile);

        $pages = $fb->get('/me/accounts');
        $pages = $pages->getGraphEdge()->asArray();
        //print_r($pages);

        foreach ($pages as $keyPage) {
            if ($keyPage['name'] == 'Imperial Secrets') {
                echo "\nfound page";
                if (!isset($theActualSecretBit) || empty($theActualSecretBit)) {
                    echo "no secrets left";
                    break;
                }
                //$getImpSecrets = $fb->get('/impsecrets/promotable_posts?limit=1&fields=scheduled_publish_time,message', $keyPage['access_token']);
                //$getImpSecrets = $getImpSecrets->getGraphEdge()->asArray();
                //print_r($getImpSecrets);

                //$message = $getImpSecrets[0]['message'];
                //$message = substr($message, strpos($message, "#") + 1);
                //echo "\nmessage: " . $message;
                //$latestSecretNumber = explode("_", explode(" ", $message)[0])[1];
                $latestSecretNumber = explode(" ", end($lastSecretAndWhenPosted))[0];
                echo "\nsecret number: " . $latestSecretNumber;

                echo "last secret scheduled for :". explode(" ", end($lastSecretAndWhenPosted))[1];
                $timeToSchedule = explode(" ", end($lastSecretAndWhenPosted))[1] + $timeBetweenEachPost;
                $timeToSchedule = max($timeToSchedule, time() + 610) + $timeToDelay;
                print("time to schedule ". $timeToSchedule);

                if (isset($inResponseTo) && !empty($inResponseTo)) {
                    $inResponseToMessage = "\n\nIn response to #ImperialSecret_".$inResponseTo;
                }
                if (isset($whatCourse) && !empty($whatCourse)) {
                    $whatCourse = " ".$whatCourse;
                }

                if ((isset($whatCourse) && !empty($whatCourse)) || (isset($whatYear) && !empty($whatYear))) {
                    $whatYearAndCourse = "[$whatYear$whatCourse]";
                }

                $newSecretNumber = $latestSecretNumber + 1;

                $messageToPost = "#ImperialSecret_" . $newSecretNumber . "\n\n$whatYearAndCourse\n\n$theActualSecretBit$inResponseToMessage\n\n$anythingToAdd";

                $toPost = array(
                    'message' => $messageToPost,
                    'scheduled_publish_time' => $timeToSchedule,
                    'published' => 0,

                );
                echo "\n\nto post: ";
                print_r($toPost);
                if ($enablePosting) {
                    echo "\nposting\n";
                    $post = $fb->post('/' . $keyPage['id'] . '/feed', $toPost, $keyPage['access_token']);
                    $post = $post->getGraphNode()->asArray();
                    print_r($post);
                    $timeToAddToFile = $timeToSchedule - $timeToDelay;
                    file_put_contents("lastSecretAndWhenPosted.txt", $newSecretNumber  . " ". $timeToAddToFile. "\n", FILE_APPEND);
                    $postSuccessful = true;
                }
            }
        }
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        // When Graph returns an error
        echo 'Graph returned an error: ' . $e->getMessage();
        //session_destroy();
        // redirecting user back to app login page
        //header("Location: ./");
        exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }


    try {

        //££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££££
    } catch (Facebook\Exceptions\FacebookResponseException $e) {
        // When Graph returns an error
        echo 'Graph returned an error: ' . $e->getMessage();
        echo("<pre>");
        print_r($e->getMessage());
        echo("</pre>");




        exit;
    } catch (Facebook\Exceptions\FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    // Now you can redirect to another page and use the access token from $_SESSION['facebook_access_token']
} else {
    // replace your website URL same as added in the developers.facebook.com/apps e.g. if you used http instead of https and you used non-www version or www version of your website then you must add the same here
    $loginUrl = $helper->getLoginUrl('http://imperialbot.com/imperialsecrets/', $permissions);
    echo '<a id = "login" href="   ' . $loginUrl . '   ">Log in with Facebook!</a>';
}
echo "loaded imperial secrets bot";

?>


<head>
  <!--<meta http-equiv="refresh" content="1000;url=http://imperialbot.com/imperialsecrets"> -->

<script>


      var elm=document.getElementById('login');
      document.location.href = elm.href;

</script>
