<?php

use Handlebars\Handlebars;
use Handlebars\Loader\FilesystemLoader;
use Cocur\Slugify\Slugify;

require_once 'app/init.php';
require_once 'vendor/autoload.php';

$data = request_body ();

if (empty($data)) {
    end_response(401, "Request payload not found");
}

if (!property_exists($data, 'company')) {
    end_response(401, "Company is not available in request");
}

if (property_exists($data->company, 'url')) {
    $host_name = parse_url($data->company->url)['host'];
    define('OUTPUT_DIR', SERVER_DOMAIN_DIR . '/' . $host_name . '/');
} else {
    end_response(401, "URL is not sent");
}

$template = validated_template($data->company);
if (!$template['status']) {
    end_response(401, $template['message']);
}

if (!is_dir(OUTPUT_DIR)) {

    // as the directory is not available, checking if the domain is added
    $websites = $api->get_websites_filtered();
    if (empty($websites)) {
        new Logs(json_encode(['time' => date('Y-m-d h:s a'), 'hasError' => true, 'errors' => ["Unable reach panel API."]]), 'system');
        end_response(400, "Unable reach panel API.");
    }

    if (!array_key_exists($host_name, $websites)) {

        if (array_key_exists('HTTP_REFERER', $_SERVER)) {
            $referrer = $_SERVER['HTTP_REFERER'];
        } else {
            $referrer = DEFAULT_REFERRER;
        }
    
        $result = $api->add_website($host_name, SERVER_DOMAIN_DIR, $referrer);
        
        if (!$result['status']) {
            new Logs(json_encode(['time' => date('Y-m-d h:s a'), 'hasError' => true, 'errors' => ['host' => $host_name, 'error' => "Unable to create website"]]), 'system');
            end_response(401, "Unable to create website");
        } else {
            // removing default files
            clean_directory(OUTPUT_DIR);
            add_htaccess(OUTPUT_DIR); 
        }
    
    }

} else if (CLEAN_OUTPUT_DIRECTORY) {
    clean_directory(OUTPUT_DIR);
}

$template_loader = new FilesystemLoader($template['path'], [ 'extension' => 'html' ]);
$partials_loader = new FilesystemLoader($template['components'], [ 'extension' => 'html' ]);

$handlebars = new Handlebars(['loader' => $template_loader, 'partials_loader' => $partials_loader]);

$handlebars->addHelper("if_even", function($template, $context, $args, $source){
    if (($context->lastIndex() % 2) == 1) {
        // odd
        $template->setStopToken('else');
        $buffer = $template->render($context); $template->setStopToken(false); $template->discard();
        return $buffer;
    } else {
        // even
        $template->setStopToken('else'); $template->discard(); $template->setStopToken(false);
        return $template->render($context);
    }
});

$directories = get_template_sub_directories($template['path']);
$_data = get_object_vars($data->company);

$interesting = [];
foreach ($_data as $sub_node => $sub_node_value) {
    if (is_array($sub_node_value)) {
        foreach ($sub_node_value as $sub_node_value_array) {
            if (is_object($sub_node_value_array)) {
                if (in_array($sub_node, $directories)) {
                    array_push($interesting, [$sub_node, $sub_node_value_array]);
                    $unfold = get_object_vars($sub_node_value_array);
                    if (is_array($unfold)) {
                        foreach ($unfold as $unfold_key => $unfold_value) {
                            if (in_array($unfold_key, $directories)) {

                                if (is_array($unfold_value)) {
                                    foreach ($unfold_value as $unfold_value_array) {
                                        if (is_object($unfold_value_array)) {
                                            array_push($interesting, [$unfold_key, $unfold_value_array]);
                                        }
                                    }
                                } else {
                                    array_push($interesting, [$unfold_key, $unfold_value]);
                                }

                            }
                        }
                    }
                }
            }
        }
    }
}

$slugify = new Slugify();

foreach ($interesting as $interesting_value) {
    if (property_exists($interesting_value[1], 'slug')) {
        $file_name = $interesting_value[1]->slug.'.html';
    } else {
        $file_name = ($slugify->slugify($interesting_value[1]->name)).'.html';
    }
    build_html_handlebars ($file_name, $interesting_value[0].'/'.$interesting_value[0], ['company' => $data->company, $interesting_value[0] => $interesting_value[1]], $handlebars);
}

$files = get_template_root_files($template['path']);
foreach ($files as $file) {
    build_html_handlebars ($file, $file, ['company' => $data->company], $handlebars);
}

end_response(200, "Success");
