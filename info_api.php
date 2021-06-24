<?php

require_once 'app/init.php';

if (isset($_POST['domain']) && !empty($_POST['domain']) && is_string($_POST['domain']) && !empty(normal_text($_POST['domain']))) {
    
    $domain = parse_url(normal_text($_POST['domain']));

    if (array_key_exists('host', $domain)) {

        $host_name = $domain['host'];
        
        $websites = $api->get_websites_filtered();
        if (empty($websites)) {
            new Logs(json_encode(['time' => date('Y-m-d h:s a'), 'hasError' => true, 'errors' => ["Unable reach panel API."]]), 'info');
            end_response(400, ['type' => 'error', 'message_type' => 'unreachable', 'message' => 'Panel API is unreachable']);
        }

        if (array_key_exists($host_name, $websites)) {

            $website = $websites[$host_name];

            // checking for A record
            $dns_records = dns_get_record($website['name'], DNS_A);
            $a_record_exists = false;
            if (!empty($dns_records) && is_array($dns_records)) {
                foreach ($dns_records as $dns_record) {
                    if ($dns_record['ip'] === SERVER_IP) {
                        $a_record_exists = true; break;
                    }
                }
            }

            if ($a_record_exists) {
                end_response(200, ['type' => 'success', 'message_type' => 'success', 'message' => 'Domain is available and active']);
            } else {
                end_response(400, ['type' => 'error', 'message_type' => 'no_arecord', 'message' => 'Domain A record is not set']);
            }

        } else {
            end_response(400, ['type' => 'error', 'message_type' => 'no_exist', 'message' => 'Domain name does not exists']);
        }

    } else {
        end_response(400, ['type' => 'error', 'message_type' => 'illegal_domain', 'message' => 'Domain name is in incorrect format']);
    }

}

end_response(400, ['type' => 'error', 'message_type' => 'invalid_request', 'message' => 'Request is invalid']);
