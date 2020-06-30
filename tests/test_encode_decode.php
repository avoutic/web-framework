<?php
# Global configuration
#
require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../includes/wf_core.inc.php');

$framework = new WF();
$framework->init();
$security = $framework->get_security();

$msg = 'This is a message I need to encrypt, store, decode and verify!';

$encoded = $security->encode_and_auth_array(array('msg' => $msg));

print('Encoded: '.$encoded.PHP_EOL);

$decoded = $security->decode_and_verify_array($encoded);

print_r('Decoded: '.$decoded['msg'].PHP_EOL);
