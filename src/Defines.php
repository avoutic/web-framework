<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Defined versions
define('FRAMEWORK_VERSION', 9);

// Format defines for verifying input
//
define('FORMAT_ID', '\d+');
define('FORMAT_USERNAME', '[\w_\-\.]+');
define('FORMAT_RETURN_PAGE', '[\/\w\.\-_]+');

if (!defined('STDOUT'))
{
    define('STDOUT', fopen('php://stdout', 'w'));
}
