<?php

// YES I KNOW

include_once '../packages/utils/globs.php';
include_once '../packages/utils/Handle.php';
include_once '../packages/utils/MyDB.php';

$db = new MyDB();

$sql   = 'SELECT * FROM `accu_contacts` WHERE `email` = "eventlab@bespired.nl"';
$found = $db->first($sql);

if (! is_null($found)) {
    echo "Already exists in DB. \n";
    exit;
}

$time      = time();
$visitcode = sprintf('%03s%02s', date('z', $time), substr(date('Y', $time), 2, 2));
$visitdate = date('Y-m-d H:i:s', $time);

// -- PROFILE

$payload = [];
$profile = Handle::make(1, 'PUSR', 'profile');

$payload['handle']        = $profile;
$payload['cmne']          = 'OUSR';
$payload['is_contact']    = 1;
$payload['project']       = 'a';
$payload['visitcount']    = 1;
$payload['firstvistcode'] = $visitcode;
$payload['firstvistdate'] = $visitdate;
$payload['firstdevice']   = 'desktop';
$payload['lastvistcode']  = $payload['firstvistcode'];
$payload['lastvistdate']  = $payload['firstvistdate'];
$payload['lastdevice']    = $payload['firstdevice'];
$payload['created_at']    = $visitdate;

$db->insert('profiles', $payload);

// -- CONTACT

$payload = [];
$contact = Handle::make(1, 'CUSR', 'contact');

$payload['handle']     = $contact;
$payload['profile']    = $profile;
$payload['project']    = 'a';
$payload['is_changed'] = 1;
$payload['is_new']     = 1;
$payload['role']       = 'visitor';
$payload['email']      = 'eventlab@bespired.nl';
$payload['firstname']  = 'demo';
$payload['lastname']   = 'bespired';
$payload['blacklist']  = false;
$payload['in_queue']   = false;

$db->insert('accu_contacts', $payload);

// -- TOKEN

$payload = [];
$handle  = Handle::make(1, 'OTKN', 'tkns');
$return  = Handle::make(1, 'TRNC', 'retc');
$token   = str_replace('tkns', 'r32424', $handle) . '-' . explode('-', $return)[2];

$payload['handle']  = $token;
$payload['profile'] = $profile;
$payload['contact'] = $contact;
$payload['project'] = 'a';
$payload['pointer'] = 2;
$payload['token_1'] = $token;

$db->insert('track_tokens', $payload);

// -- THANK YOU

$db->close();
