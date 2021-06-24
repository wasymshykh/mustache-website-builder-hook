<?php

define('DIR', dirname(__DIR__).'/');

// AApanel's protocol
define('SERVER_PROTOCOL', "https");
// A record's ip (website builder server ip)
define('SERVER_IP', '0.0.0.0');
// AApanel's port, default is 8000, please change default port for security purpose
define('SERVER_PORT', "8000");
// AApanel API key, which can be found in Settings, enable API and whitelist your server IP
define('API_KEY', "YOUR_API_KEY");

require_once DIR.'app/functions.php';
require_once DIR.'classes/api.class.php';
require_once DIR.'classes/logs.class.php';

// Timezone setting
define('TIMEZONE', 'Europe/Berlin');
date_default_timezone_set(TIMEZONE);

// here website builder will place files and domain directory will point to this
define('OUTPUT_DIR', '/www/wwwroot/yourbuilder.com/public/');

// in case request doesn't contain referrer, this will be placed in aapanel domain note
define('DEFAULT_REFERRER', 'mydefaultreferrer.com');

// add the host name here which you want to from aapanel websites
$ignore_domains = ['yourbuilder.com'];

$api = new ApiServer();
