<?php
/*
  Copyright 2015  Silvia Pfeiffer  (email : silviapfeiffer1@gmail.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// authentication helper routines for JWT and oauth2 with the Coviu API

require_once('vendor/autoload.php');

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;


global $endpoint;
$endpoint = getenv('COVIU_API_ENDPOINT');
if (!$endpoint) {
  $endpoint = 'https://api.covi.io';
}

global $api_key;
$api_key = getenv('COVIU_API_KEY');
if (!$api_key) {
  echo("Set COVIU_API_KEY environment variable.");
  exit();
}

global $api_key_secret;
$api_key_secret = getenv('COVIU_API_KEY_SECRET');
if (!$api_key_secret) {
  echo("Set COVIU_API_KEY_SECRET environment variable.");
  exit();
}


function build_auth_header( $api_key, $api_key_secret ) {
  // Construct the HTTP Basic Auth header.
  return ( array('Authorization' => 'Basic '.base64_encode($api_key.':'.$api_key_secret)) );
}

function build_oauth2_auth_header( $access_token ) {
  // Construct the OAuth2 bearer token authorization header from an access token.
  return ( array('Authorization' => 'Bearer '.$access_token) );
}

function get_access_token( $api_key, $api_key_secret ) {
  global $endpoint;

  $data = array('grant_type' => 'client_credentials');

  // Use the api_key and api_key_secret to get an access token and refresh token for the api client.
  $auth_header = build_auth_header( $api_key, $api_key_secret );
  $header = array();
  $header = array_merge( $header, $auth_header );

  $url = $endpoint."/v1/auth/token";

  $response = Requests::post( $url, $header, $data );

  return json_decode($response->body);
}

function refresh_access_token( $api_key, $api_key_secret, $refresh_token ) {
  global $endpoint;

  $data = array('grant_type' => 'refresh_token', 'refresh_token' => $refresh_token);

  // Use the api_key and api_key_secret along with a previous refresh token to refresh an
  // access token, returning a new grant with access token and refresh token.
  $auth_header = build_auth_header( $api_key, $api_key_secret );
  $header = array();
  $header = array_merge( $header, $auth_header );

  $url = $endpoint."/v1/auth/token";

  $response = Requests::post( $url, $header, $data);

  return json_decode($response->body);
}

function get_api_root( $access_token ) {
  global $endpoint;

  // Get the API root resource that includes links to our organisation's collections.
  $auth_header = build_oauth2_auth_header( $access_token );
  $header = array();
  $header = array_merge( $header, $auth_header );

  $url = $endpoint."/v1/";

  $response = Requests::get( $url, $header );

  return json_decode($response->body);
}

function create_subscription( $access_token, $api_root, $body ) {
  global $endpoint;

  $data = $body;

  // Create a new subscription for a user.
  // We need to create a subscription before we sign a session jwt for the user,
  // otherwise coviu will rudely deny access to the session.
  $auth_header = build_oauth2_auth_header( $access_token );
  $header = array('Content-Type' => 'application/json');
  $header = array_merge( $header, $auth_header );

  // POST /v1/orgs/<org id>/subscriptions/
  $url = $endpoint.$api_root->_links->subscriptions->href;

  $response = Requests::post( $url, $header, json_encode($data) );

  return json_decode($response->body);
}

function get_subscriptions( $access_token, $api_root) {
  global $endpoint;

  // Get the first page of subscriptions, leaving the API to choose how many to return.
  $auth_header = build_oauth2_auth_header( $access_token );
  $header = array();
  $header = array_merge( $header, $auth_header );

  // GET /v1/orgs/<org id>/subscriptions/
  $url = $endpoint.$api_root->_links->subscriptions->href;

  $response = Requests::get( $url, $header );

  return json_decode($response->body);
}

function get_subscription_by_ref( $access_token, $api_root, $ref) {
  $subscriptions = get_subscriptions( $access_token, $api_root );

  for ($i=0, $c=count($subscriptions->content); $i<$c; $i++) {
    if ( $ref == $subscriptions->content[$i]->content->remoteRef ) {
      return $subscriptions->content[$i];
    }
  }
  return null;
}

function get_sessions( $access_token, $api_root) {
  global $endpoint;

  // Get the first page of sessions, leaving the API to choose how many to return.
  $auth_header = build_oauth2_auth_header( $access_token );
  $header = array();
  $header = array_merge( $header, $auth_header );

  // GET /v1/orgs/<org id>/sessions/
  $url = $endpoint.$api_root->_links->sessions->href;

  $response = Requests::get( $url, $header );

  return json_decode($response->body);
}

function get_link( $access_token, $page ) {
  global $endpoint;

  // Get a resource identified by HAL link object.
  $auth_header = build_oauth2_auth_header( $access_token );
  $header = array();
  $header = array_merge( $header, $auth_header );

  $url = $endpoint.$page->href;

  $response = Requests::get( $url, $header );

  return json_decode($response->body);
}

function delete_subscription( $access_token, $subscription) {
  global $endpoint;

  // Delete a previously created subscription.
  $auth_header = build_oauth2_auth_header( $access_token );
  $header = array();
  $header = array_merge( $header, $auth_header );

  $url = $endpoint.$subscription->_links->self->href;

  $response = Requests::delete( $url, $header );

  return json_decode($response->body);
}

function delete_subscription_by_id( $access_token, $api_root, $subscriptionId ) {
  global $endpoint;

  // Delete a previously created subscription.
  $auth_header = build_oauth2_auth_header( $access_token );
  $header = array();
  $header = array_merge( $header, $auth_header );

  // DELETE /v1/orgs/<org id>/subscriptions/<subscriptionId>
  $url = $endpoint.$api_root->_links->subscriptions->href.'/'.$subscriptionId;

  $response = Requests::delete( $url, $header );

  return json_decode($response->body);
}

// Recover an access token
$grant = get_access_token( $api_key, $api_key_secret );

// Refresh an access token before grant['expires_in'] has expired.
$grant = refresh_access_token( $api_key, $api_key_secret, $grant->refresh_token);

// Get the root of the api
$api_root = get_api_root($grant->access_token);

// Create a new subscription for one of your users
$subscription_ref = Uuid::uuid4();
$subscription = create_subscription( $grant->access_token,
                                     $api_root,
                                     array('ref' => $subscription_ref->toString(),
                                          'name' => 'Dr. Jane Who',
                                         'email' => 'briely.marum@gmail.com'
                                     ) );

// Get a page of sessions for 
$sessions = get_sessions( $grant->access_token, $api_root );

// Get the first page of subscriptions
$subscriptions = get_subscriptions( $grant->access_token, $api_root );

//var_dump(count($subscriptions->content));


// Delete the entire set of subscriptions traversing forward
for ($i=0, $c=count($subscriptions->content); $i<$c; $i++) {
  delete_subscription($grant->access_token, $subscriptions->content[$i]);
}


/*
// Get the entire set of subscriptions traversing backward
while ($subscriptions->_links->previous) {
  $subscriptions = get_link( $grant->access_token, $subscriptions->_links->previous );
}
*/

// delete the subscription we created. Don't do this if you want your user to access coviu.
delete_subscription( $grant->access_token, $subscription );


// generate a random string for the session Id.
// This only needs to be known to the participants.
$session_id = Uuid::uuid4()->toString();

// Sign a jwt for the owner of the subscription. This lets them into the call straight away.
$token = array(
    'iss'   => $api_key,
    'un'    => 'Dr. Jane Who',
    'ref'   => $subscription_ref,
    'sid'   => $session_id,
    'img'   => 'http://www.fillmurray.com/200/300',
    'email' => 'dr.who@gmail.com',
    'rle'   => 'owner',
    'rtn'   => 'https://coviu.com',
    'nbf'   => time(),
    'exp'   => time() + 60*60
);

$owner = JWT::encode($token, $api_key_secret, 'HS256');

$decoded = JWT::decode($owner, $api_key_secret, array('HS256'));


// Sign a jwt for the guest of the owner.
// This gets them to the right spot, but they still need to be let into the call
// by the owner of a session.
$token = array(
    'iss' => $api_key,
    'un'  => 'Joe Guest',
    'ref' => $subscription_ref,
    'sid' => $session_id,
    'img' => 'http://www.fillmurray.com/200/300',
    'rle' => 'guest',
    'rtn' => 'https://coviu.com',
    'nbf' => time(),
    'exp' => time() + 60*60
);

$guest = JWT::encode($token, $api_key_secret, 'HS256');


printf("send owner to ".$endpoint."/v1/session/".$owner."\n");
printf("send guest to ".$endpoint."/v1/session/".$guest."\n");
