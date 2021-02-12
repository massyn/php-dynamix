<?php

# TODO
#       we will need to include authentication info too, since the menu may need to be hidden in some case, and the content hook should not be
#       callable if not authorised

# all pages need add_hook.  Specify the tag (this must be unique across every page), and a title that will be used in the menu
add_hook('template','Template Page');

# When the hook is called, there is something that should happen.  tag_content is where it happens.  
function template_content() {
   global $HTML;
   global $CORE;
   #$HTML->add_message('error','this is an error');
   #$HTML->add_message('success','this is a success');
   #$HTML->add_message('warning','this is a warning');
   #$HTML->add_message('info','this is an info');

   return '<h1>Template</h1><p><a href="?_csrf=xxx">non csrf</a> - <a href="?_csrf=%CSRF%">With csrf</a></p>' . $HTML->form(array('title' => 'My form','table' => 'phonebook', 'function' => 'add'),template_schema());
}

# Your solution may need a schema.  If this exists, the database table will be created.
function template_schema() {
   return json_decode('
   {
      "phonebook" : {
         "name"      : { "type" : "text"        },
         "phoneno"   : { "type" : "text"        },
         "notes"     : { "type" : "textarea"    }
      }
   }
   ',TRUE);

        
} 

?>
