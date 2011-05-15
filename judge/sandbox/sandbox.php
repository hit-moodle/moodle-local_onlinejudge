<?php
require_once("../../judgelib.php");

class judge_sandbox extends judge_base
{
    function get_languages()
    {
    	$lang = array();
        // Get local languages. Linux only
        if ($CFG->ostype != 'WINDOWS') 
        {
            $dir = $CFG->dirroot.'/local/onlinejudge2/languages/';
            $files = get_directory_list($dir);
            $names = preg_replace('/\.(\w+)/', '', $files); // Replace file extension with nothing
            foreach ($names as $name) {
                $lang[$name] = get_string('lang'.$name, 'local_onlinejudge2');
            }
        }
        asort($lang);
        return $lang;
    }
}


?>