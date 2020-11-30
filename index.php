<?php

define('DIR', __DIR__);

// change the default output directory to any location, make sure location path ends with '/'
    // in case url is not defined in request, it will generate static files in default directory
$default_output_directory = DIR . "/output/";

// setting for cleaning output directory before generating static files again
define('CLEAN_OUTPUT_DIRECTORY', true);


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

$mustache_engine = new Mustache_Engine(['entity_flags' => ENT_QUOTES,
    'escape' => function($value) { return htmlspecialchars($value, ENT_COMPAT, 'UTF-8');},
    'loader' => new Mustache_Loader_FilesystemLoader($template['path'], ['extension' => '.html'])]);

if (!is_dir(OUTPUT_DIR)) {
    mkdir(OUTPUT_DIR);
} else if (CLEAN_OUTPUT_DIRECTORY) {
    clean_directory(OUTPUT_DIR);
}

// check for files with r.s.t data
$directories = get_template_files($template['path']);

foreach ($directories as $directory => $files) {
    foreach ($files as $file) {
        
        $data_to_pass = $data;
        if ($directory !== '/') {
            $directory = str_replace('/', '', $directory);
            if (array_key_exists($directory, $data->company->category[0])) {                
                $data_to_pass = [$directory => $data->company->category[0]->$directory];
            }
        }

        build_html_file($directory, $file, $data_to_pass, $mustache_engine);
    }
}

end_response(200, "All set!");
