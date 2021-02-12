<?php

add_hook('debug','Debug');

function debug_content() {
   global $HTML;
   if (param('debug') != '') {
      $_SESSION['debug'] = param('debug');
   }
   return '<h1>Debug</h1><p><a href="?_csrf=%CSRF%&debug=1">ON</a> - <a href="?_csrf=%CSRF%&debug=0">OFF</a></p>' ;
}

?>
