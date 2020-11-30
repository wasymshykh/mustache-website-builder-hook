<?php


function request_body ()
{
    return json_decode(file_get_contents("php://input"));
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
    if (!file_exists($template_path)) {
        return ['status' => false, 'message' => 'template does not exists'];
    }

    return ['status' => true, 'name' => $template_name, 'path' => $template_path];
}

function get_template_files (string $directory)
{
    $directories = ['/'];
    $files = [];
    $ignore = ['.', '..'];

    while (!empty($directories)) {
        $_dir = array_pop($directories);
        $file_names = scandir($directory . $_dir);        
        
        foreach ($file_names as $file_name) {
            if (!in_array($file_name, $ignore)) {
                $file_path = $directory . $_dir  . $file_name;
                
                // check if file is an actual directory
                if (filetype($file_path) === 'dir') {
                    array_push($directories, $_dir . $file_name . '/');
                }
                else if (pathinfo($file_path)['extension'] === 'html') {
                    // if file has .html extention
                    if (array_key_exists($_dir, $files)) {
                        array_push($files[$_dir], $file_name);
                    } else {
                        $files[$_dir] = [ $file_name ];
                    }
                }
            }
        }
    }
    
    return $files;
}


function build_html_file($directory, $file, $data, $mustache)
{
    $file_name = $file;
    if ($directory !== '/') {
        $file_name = $directory . '/' . $file;
    }
    
    $html_content = $mustache->loadTemplate(str_replace ('.html', '', $file_name))->render($data);

    $output_directory = DIR . '/output/' . $directory;
    if (!is_dir($output_directory)) {
        mkdir($output_directory);
    }
    file_put_contents(DIR . '/output/' . $file_name, $html_content);
}

