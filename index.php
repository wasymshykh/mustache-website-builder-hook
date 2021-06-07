<?php

use Handlebars\Handlebars;
use Handlebars\Loader\FilesystemLoader;

define('DIR', __DIR__);

// change the default output directory to any location, make sure location path ends with '/'
    // in case url is not defined in request, it will generate static files in default directory
$default_output_directory = DIR . "/output/";

// setting for cleaning output directory before generating static files again
define('CLEAN_OUTPUT_DIRECTORY', false);

require 'vendor/autoload.php';
require 'app/functions.php';

$data = request_body ();

if (empty($data)) {
    end_response(401, "Request payload not found");
}

if (!property_exists($data, 'company')) {
    end_response(401, "Company is not available in request");
}

if (property_exists($data->company, 'url')) {
    $default_output_directory = DIR . '/' . parse_url($data->company->url)['host'] . '/';
}
define('OUTPUT_DIR', $default_output_directory);

$template = validated_template($data->company);
if (!$template['status']) {
    end_response(401, $template['message']);
}

if (!is_dir(OUTPUT_DIR)) {
    mkdir(OUTPUT_DIR);
} else if (CLEAN_OUTPUT_DIRECTORY) {
    clean_directory(OUTPUT_DIR);
}

$template_loader = new FilesystemLoader($template['path'], [ 'extension' => 'html' ]);
$partials_loader = new FilesystemLoader($template['components'], [ 'extension' => 'html' ]);

$handlebars = new Handlebars(['loader' => $template_loader, 'partials_loader' => $partials_loader]);


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

foreach ($interesting as $interesting_value) {
    $file_name = strtolower(str_replace(' ', '-', $interesting_value[1]->name)) .'.html';
    build_html_handlebars ($file_name, $interesting_value[0].'/'.$interesting_value[0], ['company' => $data->company, $interesting_value[0] => $interesting_value[1]], $handlebars);
}

$files = get_template_root_files($template['path']);
foreach ($files as $file) {
    build_html_handlebars ($file, $file, ['company' => $data->company], $handlebars);
}

end_response(200, "Success");
