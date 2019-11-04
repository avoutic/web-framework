<?php
# Global configuration
#
$includes=__DIR__.'/../includes/';
$site_includes=__DIR__.'/includes/';
$site_views=__DIR__.'/../views/';
$site_frames=__DIR__.'/../frames/';
$site_templates=__DIR__.'/../templates/';
require_once(__DIR__ . '/../vendor/autoload.php');

require_once($includes.'wf_core.inc.php');

$msg = 'This is a message I need to encrypt, store, decode and verify!';

$encoded = encode_and_auth_string($msg);

print('Encoded: '.$encoded.PHP_EOL);

$encoded = urldecode($encoded);
$decoded = decode_and_verify_string($encoded);

print_r('Decoded: '.$decoded.PHP_EOL);
