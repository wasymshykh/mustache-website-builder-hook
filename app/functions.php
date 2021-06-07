<?php

function request_body ()
{
    $json_encoded_unfiltered = file_get_contents("php://input");
    if (mb_check_encoding($json_encoded_unfiltered, 'UTF-8')) {
        $json_encoded_unfiltered = utf8_encode($json_encoded_unfiltered);   
    }
    return json_decode($json_encoded_unfiltered);
}

function end_response ($status_code, $message)
{
    http_response_code($status_code);
    echo json_encode(['message' => $message]);
    die();
}

function validated_template ($company)
{
    if (!property_exists($company, 'template')) {
        return ['status' => false, 'message' => 'template is not sent!'];
    }

    $template_name = $company->template;
    $template_path =  DIR . '/templates/'.$template_name;
    $components_path =  $template_path.'/_components';
    if (!file_exists($template_path)) {
        return ['status' => false, 'message' => 'template does not exists'];
    }
    
    if (!file_exists($components_path)) {
        return ['status' => false, 'message' => '_components directory does not exists'];
    }

    return ['status' => true, 'name' => $template_name, 'path' => $template_path, 'components' => $components_path];
}

function get_template_sub_directories (string $directory)
{
    $directories = ['/'];
    $dirs = [];
    $ignore = ['.', '..', '_components'];

    while (!empty($directories)) {
        $_dir = array_pop($directories);
        $file_names = scandir($directory . $_dir);

        foreach ($file_names as $file_name) {
            if (!in_array($file_name, $ignore)) {
                $file_path = $directory . $_dir  . $file_name;
                // check if file is an actual directory
                if (filetype($file_path) === 'dir') {
                    array_push($directories, $_dir . $file_name . '/');
                    array_push($dirs, $file_name);
                }
            }
        }
    }

    return $dirs;
}

function build_html_handlebars ($file_name, $template_file, $model, $handlebars)
{
    $html_content = $handlebars->render(str_replace ('.html', '', $template_file), $model);
    $output_directory = OUTPUT_DIR;
    if (!is_dir($output_directory)) {
        mkdir($output_directory);
    }
    file_put_contents(OUTPUT_DIR . $file_name, $html_content);
}

function get_template_root_files (string $directory)
{
    $ignore = ['.', '..'];

    $files = [];

    $file_names = scandir($directory . '/');

    foreach ($file_names as $file_name) {
        if (!in_array($file_name, $ignore)) {
            $file_path = $directory . '/'  . $file_name;
            if (filetype($file_path) !== 'dir' && pathinfo($file_path)['extension'] === 'html') {
                // if file has .html extention
                array_push($files, $file_name);
            }
        }
    }

    return $files;
}

function clean_directory($directory)
{
    if (is_file($directory)) {
        return unlink($directory);
    } else if (is_dir($directory)) {
        $scan = glob(rtrim($directory,'/').'/*');
        foreach($scan as $index => $path) {
            clean_directory($path);
        }
        return @rmdir($directory);
    }
}
