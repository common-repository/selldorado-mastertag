<?php
if(! defined('ABSPATH')){
    exit;
}

$options = get_option('selldorado-mastertag-options');
echo "<input type=\"text\" name=\"selldorado-mastertag-options[mastertag-id]\" value=\"{$options['mastertag-id']}\" />";
