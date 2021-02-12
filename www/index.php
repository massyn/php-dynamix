<?php

$currDir = dirname(__FILE__);

$app_path = "{$currDir}/app/";
$lib_path = "{$currDir}/lib/";
$cfg_path = "{$currDir}/cfg/";

# == import all our key classes
include "$lib_path/html.php";
include "$lib_path/db.php";
include "$lib_path/core.php";
include "$cfg_path/database.php";       # -- contains the database credentials

# == setup the core class
$CORE = new CORE();

# == setup the HTML engine
$HTML = new HTML();
$HTML->CORE = $CORE; 



# == grab the application meta data
include "$cfg_path/application.php";    # -- contains the application meta data
$APP = application_config();
$HTML->add_title($APP['title']);

# == do the database connection
$DB = new DB();

if(!$DB->connect(database_config())) {        # -- database_config comes from the database.php file
        # -- this means we could not connect
        $HTML->add_message('error',$DB->message);
} else {
        # -- setup the session table
        if($DB->create_table('_session')) {
                $DB->create_field('_session','sessionid','text');
                $DB->create_field('_session','sessiondata','textarea');
        }

        # -- check and set the session cookie
        if(isset($_COOKIE[$APP['cookie']])) {
                $cookie = $_COOKIE[$APP['cookie']];
        } else {
                $cookie = bin2hex(random_bytes(30));
        }
        if(PHP_VERSION_ID < 70300) {
                setcookie($APP['cookie'], $cookie, time() + $APP['session_timeout'], dirname($_SERVER['PHP_SELF']), $_SERVER['SERVER_NAME'], isset($_SERVER["HTTPS"]), TRUE);
        } else {
                setcookie($APP['cookie'], $cookie, [
                        'expires'       => time() + $APP['session_timeout'],
                        'path'          => dirname($_SERVER['PHP_SELF']),
                        'domain'        => $_SERVER['SERVER_NAME'],
                        'secure'        => isset($_SERVER["HTTPS"]),
                        'httponly'      => true,
                        'samesite'      => 'Strict',
                ]);
        }

        # -- we do a fake $_SESSION setup - this is by design, to allow the app to run in a load-balanced environment
        $x = $DB->select('_session',array('sessionid' => $cookie));
        if(isset($x[0])) {
                $_SESSION = json_decode($x[0]['sessiondata'],TRUE);
        } else {
                $_SESSION = array();
        }
        
        # -- do CSRF
	if(isset($_SESSION['csrf'])) {
		if(param('_csrf') == $_SESSION['csrf']) {
			$_SESSION['csrf_valid'] = 1;
		} else {
			$_SESSION['csrf_valid'] = 0;		
		}
	} else {
		$_SESSION['csrf_valid'] = 0;
	}
        $_SESSION['csrf'] = bin2hex(random_bytes(10));  // -- reset the token


        # == start including all custom hookable files
        foreach (array_slice(scandir($app_path), 2) as $key => $value) {
                if (strpos($value,'.php') > 0) {
                        include "$app_path/$value";
                }
        }

        # -- save the session back to the db
        $DB->update('_session',array('sessionid' => $cookie),array('sessiondata' => json_encode($_SESSION)));

        # -- do some housekeeping on the session table
        $DB->housekeeping('_session',$APP['session_timeout']);

} # -- end of successful db connection


# =================== debug stuff =================== #
if(isset($_SESSION['debug']) && $_SESSION['debug'] == 1) {
   $HTML->content .= '<h2>Debug</h2><p><table border=1>
      <tr><th>PHP_VERSION_ID</th><td>' . PHP_VERSION_ID . '</td></tr>
      <tr><th>Cookie</th><td>'. $cookie . '</td></tr>
      <tr><th>$_SERVER[\'SERVER_NAME\']</th><td>'. $_SERVER['SERVER_NAME'] . '</td></tr>
      <tr><th>$_SERVER[\'HTTP_HOST\']</th><td>'. $_SERVER['HTTP_HOST'] . '</td></tr>
      <tr><th>$_SERVER[\'REQUEST_URI\']</th><td>'. $_SERVER['REQUEST_URI'] . '</td></tr>
      <tr><th>$_SERVER[\'PHP_SELF\']</th><td>'. $_SERVER['PHP_SELF'] . '</td></tr>';

   foreach ($_SESSION as $key=>$val) {
      
   }
   $HTML->content .= "</table>";
} # =================== end of debug stuff =================== #

print $HTML->render();

exit(0);

function param($tag) {
        if(isset($_POST[$tag])) {
		return $_POST[$tag];
	} elseif (isset($_GET[$tag])) {
		return $_GET[$tag];
	} else {
		return '';
	}
}

function add_hook($tag,$title = "blank") {
        global $HTML;
        global $DB;

        $hook = param('_hook');
        if($hook == '' && isset($_SESSION['hook'])) {
                $hook = $_SESSION['hook'];
        }

        # -- is there a schema?  If so, create the table
        $s = "${tag}_schema";
        if(is_callable($s)) {
                $schema = $s();

                $DB->create_schema($schema);
        }

        # -- if the hook matches, run it
        if($hook == $tag) {
                $proc = "${tag}_content";

                if(is_callable($proc)) {
                        $HTML->add_content(str_replace('%CSRF%',$_SESSION['csrf'],$proc()));
                        $_SESSION['hook'] = $tag;
                } else {
                        $HTML->add_error("ERROR - $proc is not callable");
                }
        }

        # -- menu stuff
        $HTML->add_menu("?_hook=$tag",$title);

}
?>
