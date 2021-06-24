<?php

class Logs
{
    private $text;
    
    public function __construct($text, $file = 'cron') {
        $this->text = $text;
        $this->store($file);
    }

    private function store($file)
    {
        $log_dir_path = DIR.'logs/';
        $log_file_path = $log_dir_path.$file.'.log';
        if (!file_exists($log_dir_path)) {
            mkdir($log_dir_path);
        }
        $f = fopen($log_file_path, "a") or die();
        $d = $this->text."\n";
        fwrite($f, $d);
        fclose($f);
    }

}
