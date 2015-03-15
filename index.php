<?php
header("Access-Control-Allow-Origin: *");

// Composer
require 'vendor/autoload.php';

// Our libs
require_once('TwidditDB.php');
require_once('Reddit.php');
require_once('View.php');
require_once('Auth.php');

// If request is for the public directory, serve static file (for js/css)
if (stristr($_SERVER['REQUEST_URI'], 'public')) {
   return false;
}

$app = new \Slim\Slim();

$app->get('/', function() use ($app) {
   if(!isset($_COOKIE['user'])) {
      $loginpage = new View('signin.phtml');
      $loginpage->render();
   } else {
      $mainpage = new View('main.phtml');
      $mainpage->render();
   }
});

$app->get('/signin', function()  use ($app) {
   $signinpage = new View('signin.phtml');
   $signinpage->render();
});

$app->get('/feed', function() use ($app) {
   $db = TwidditDB::db();
   $username = $_COOKIE['user'];
   $query = "select redditor 
            from followingRedditors 
            where '$username' = userName";
   $result = $db->query($query);
   $users = [];
   foreach ($result as $row) {
      $users[] = $row['redditor'];
   }
   
   $comments = Reddit::getComments($users);

   echo json_encode($comments);
});
$app->get('/subreddits', function() use ($app) {
   $db = TwidditDB::db();
   $username = $_COOKIE['user'];
   $query = "select subreddit 
            from followingSubreddit 
            where '$username' = userName";
   $result = $db->query($query);
   $subreddit = [];
   foreach ($result as $row) {
      $subreddit[] = $row['subreddit'];
   }
   $data = Reddit::getSubredditPosts($subreddit);

   echo json_encode($data);
});

$app->get('/reddit_callback', function() use ($app) {
   $req = $app->request();
   $state = $req->get('state');
   $code = $req->get('code');

   $JSONResponse = Auth::getTokenFromAuthCode($code, $state);
   $response = json_decode($JSONResponse, /* assoc */ true);

   if (array_key_exists('error', $response)) {
      // Do error thing
      echo "OAuth Error: {$response['error']}";
      die();
   }

   Auth::setUserToken($response['access_token'], $response['refresh_token'],
    $response['expires_in']);
   $app->redirect('/');
});

$app->post('/login', function() use ($app) {
   $db = TwidditDB::db();
   $username = $app->request->post('username');
   $password = $app->request->post('password');
    
   $query = "SELECT * FROM  users where userName='$username' and userPassword='$password'";

   if ($db == null) {
      echo 'hi your db is null';
   }
   $result = $db->query($query);

   if($result->rowCount() == 0) {
      $failpage = new View('signin.phtml');
      $failpage->addPageVariable('failure', true);
      $failpage->render();
   } else {
     $cookie_name = 'user';
     $cookie_value = $username;
     setcookie($cookie_name, $cookie_value, time() + 36000); // cookie lasts 60 secs
     $app->redirect('/');
   }
});


$app->post('/signup', function() use ($app) {
   $db = TwidditDB::db();
   $username = $app->request->post('username');
   $password = $app->request->post('password');

   $query = "SELECT * FROM  users where userName='$username'";
   $result = $db->query($query);
   
   if ($result->rowCount() > 0) {
     $failpage = new View('signin.php');
     $failpage->addPageVariable('signupfail', true);
     $failpage->render();
   } else {
     $insert = "INSERT INTO users values('$username', '$password')";
     $result = $db->exec($insert);
     $successpage = new View('signin.php');
     $successpage->addPageVariable('signupsuccess', true);
     $successpage->render(); 
   }
});

$app->run();
