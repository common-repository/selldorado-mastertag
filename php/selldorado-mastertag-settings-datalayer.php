<?php
if(! defined('ABSPATH')){
    exit;
}

$options = get_option('selldorado-mastertag-options');
echo "<select name=\"selldorado-mastertag-options[datalayer-only]\">";
echo "<option value=\"No\"";
if ($options['datalayer-only'] == "No") {
    echo " selected=\"selected\"";
}
echo ">".__('No', 'selldorado-mastertag')."</option>";
echo "<option value=\"Yes\"";
if ($options['datalayer-only'] == "Yes") {
    echo " selected=\"selected\"";
}
echo ">".__('Yes', 'selldorado-mastertag')."</option>";
echo "</select>";

