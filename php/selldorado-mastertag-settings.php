<?php 
if(! defined('ABSPATH')){
    exit;
}
?>
<div class="wrap">
    <form action="options.php" method="post" >
	<?php settings_fields('selldorado-mastertag-options'); ?>
	<?php do_settings_sections('selldorado-mastertag-options'); ?>
	<?php submit_button(); ?>
  </form>
</div>
