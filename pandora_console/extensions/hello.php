<?php

/* You can safely delete this file */

function hello_extension_main () {
	/* Here you can do almost all you want! */
	echo 'Hello world!';
}

/* This adds a option in the operation menu */
add_operation_menu_option ('Hello plugin!');

/* This sets the function to be called when the extension is selected in the operation menu */
add_extension_main_function ('hello_extension_main');
?>
