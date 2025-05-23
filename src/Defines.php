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
define('FRAMEWORK_DB_VERSION', 3);

// Format defines for verifying input
//
define('FORMAT_ID', '\d+');
define('FORMAT_USERNAME', '[\w_\-\.]+');
define('FORMAT_PASSWORD', '.*');
define('FORMAT_NAME', '[\w _\-\.\']+');
define('FORMAT_EMAIL', '[\w\._\-+]+@[\w_\-\.]+\.[\w_\-]+');
define('FORMAT_VERIFY_CODE', '[\w]{40}');
define('FORMAT_RETURN_PAGE', '[\/\w\.\-_]+');
define('FORMAT_RETURN_QUERY', '[\w\&~=:\._\-%\(\)\?\/]+');
define('FORMAT_FILE_NAME', '[\w_\-\.]+');
define('FORMAT_FILE_LOCATION', '[\w_\-\.\/]+');
define('FORMAT_BASE64', '[a-zA-Z0-9+\/]+={0,2}');
define('CHAR_FILTER', '"*&<>;\'');
