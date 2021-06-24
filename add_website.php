<?php

require_once 'app/init.php';

$websites = $api->get_websites_filtered();

$host_name = "anywebsite.com";

if (!array_key_exists($host_name, $websites)) {

    if (array_key_exists('HTTP_REFERER', $_SERVER)) {
        $referrer = $_SERVER['HTTP_REFERER'];
    } else {
        $referrer = DEFAULT_REFERRER;
    }

    $result = $api->add_website($host_name, OUTPUT_DIR, $referrer);
    
    if (!$result['status']) {
        end_response(401, "Unable to create website");
    }

} else {
    end_response(200, "Website already exists");
}

end_response(200, "Website created");
