<?php
define('OAUTH2_CLIENT_ID', '');
define('OAUTH2_CLIENT_SECRET', '');

$apiURLBase = 'https://prepiam.toronto.ca.ibm.com/idaas/oidc/endpoint/default/';

session_start();
// Start the login process by sending the user to IBMid's authorization page
if(get('action') == 'login') {
  unset($_SESSION['access_token']);
  $params = array(
    'client_id' => OAUTH2_CLIENT_ID,
    'redirect_url' => 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
    'scope' => 'openid',
    'response_type' => 'code',
  );
  // Redirect the user to IBMid's authorization page
  header('Location: ' . $apiURLBase . 'authorize' . '?' . http_build_query($params));
  die();
}

if(get('code')) {
  // Exchange the auth code for a token
  $token = apiRequest($apiURLBase.'token', array(
    'client_id' => OAUTH2_CLIENT_ID,
    'client_secret' => OAUTH2_CLIENT_SECRET,
    'redirect_url' => 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
    'code' => get('code'),
    'grant_type' => 'authorization_code',
    'scope' => 'openid',
  ));
  $_SESSION['access_token'] = $token->access_token;
  header('Location: ' . $_SERVER['PHP_SELF']);
}

if(session('access_token')) {
  $user = apiRequest($apiURLBase . 'userinfo', 'user');
  // $intro = apiRequest($apiURLBase . 'introspect', array(
  //   'token' => $_SESSION['access_token'],
  //   'client_id' => OAUTH2_CLIENT_ID,
  //   'client_secret' => OAUTH2_CLIENT_SECRET,
  // ));
  echo '<h3>Logged In</h3>';
  echo '<h4>' . $user->sub . '</h4>';
  // echo '<pre>';
  // print_r($intro);
  // echo '</pre>';
} else {
  echo '<h3>Not logged in</h3>';
  echo '<p><a href="?action=login">Log In</a></p>';
}

function apiRequest($url, $post=FALSE, $headers=array()) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  
  if($post){
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
  }
  
  $headers[] = 'Accept: application/json';

  if($post == 'user'){
    $headers[] = 'Authorization: Bearer ' . session('access_token');
  }

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $response = curl_exec($ch);
  return json_decode($response);
}
function get($key, $default=NULL) {
  return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
}
function session($key, $default=NULL) {
  return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
}