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

require_once __DIR__.'/vendor/autoload.php';
use coviu\Api\Coviu;

global $endpoint;
$endpoint = getenv('COVIU_API_ENDPOINT');
if (!$endpoint) {
  $endpoint = 'https://api.coviu.com/v1';
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

global $grant;
$grant = null;

global $session;

function create_test_session($coviu) {
  date_default_timezone_set('GMT');

  $session_data = array(
    'session_name' => 'A test session with Dr. Who',
    'start_time' => (new \DateTime())->modify('+1 hour')->format(\DateTime::ATOM),
    'end_time' => (new \DateTime())->modify('+2 hours')->format(\DateTime::ATOM),
    'picture' => 'http://www.fillmurray.com/200/300'
  );

  $session = $coviu->sessions->createSession($session_data);
  return $session;
}

function get_sessions_length($coviu) {
  $sessions = $coviu->sessions->getSessions();

  echo "Number of sessions booked: ";
  var_dump(sizeof($sessions["content"]));
}

function add_participant($coviu, $session, $name, $role) {
  $host = array(
    'display_name' => $name,
    'role' => $role,
    'picture' => 'http://fillmurray.com/200/300',
    'state' => 'test-state'
  );

  $participant = $coviu->sessions->addParticipant($session['session_id'], $host);
}

// Set up Coviu environment
$coviu = new Coviu($api_key, $api_key_secret, $grant, $endpoint);

// schedule a new session
$session = create_test_session($coviu);

// add a host and guest participant
add_participant($coviu, $session, "Dr Who", 'host');
add_participant($coviu, $session, "Patient", 'guest');

// print session
echo "New session:\n";
var_dump($coviu->sessions->getSession($session['session_id']));

// print number of all sessions
get_sessions_length($coviu);

