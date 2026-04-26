<?php
function updateFile($file) {
    $content = file_get_contents($file);
    $content = preg_replace_callback('/\[\'name\'=>(\'.*?\'),\s*\'price\'=>([0-9]+),\s*\'image\'=>(\'.*?\'),\s*\'cat_key\'=>(\'.*?\'),\s*\'sub_cat\'=>(\'.*?\'),\s*\'nbDispo\'=>([0-9]+)\]/', function($m) {
        $hash = crc32(trim($m[1], "'"));
        $note = 5.0 + ($hash % 50) / 10;
        return sprintf("['name'=>%s,'price'=>%s, 'image'=>%s,'cat_key'=>%s,'sub_cat'=>%s,'nbDispo'=>%s, 'note'=>%.1f]", $m[1], $m[2], $m[3], $m[4], $m[5], $m[6], $note);
    }, $content);
    file_put_contents($file, $content);
}

updateFile('c:/xampp/htdocs/BibenQuinc/public/catalogue.php');
updateFile('c:/xampp/htdocs/BibenQuinc/public/pref.php');
echo "Done";
