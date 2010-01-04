<?php
# Format defines for verifying input
#
define("FORMAT_USERNAME", '[\w_\-\.]+');
define("FORMAT_PASSWORD", '[\w]{40}');
define("FORMAT_NAME", '[\w _\-\.\']+');
define("FORMAT_EMAIL", '[\w\._\-]+@[\w_\-\.]+\.[\w_\-]+');
define("FORMAT_VERIFY_CODE", '[\w]{40}');
define("FORMAT_RETURN_PAGE", '[\w\&=_\(\)\?]+');
define("FORMAT_FILE_NAME", '[\w_\-\.]+');
define("FORMAT_FILE_LOCATION", '[\w_\-\.\/]+');

# Hash for recognizing empty passwords that are hashed with SHA1
#
define("EMPTY_PASSWORD_HASH_SHA1", 'da39a3ee5e6b4b0d3255bfef95601890afd80709');
?>
