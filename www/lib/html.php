<?php
class HTML {
    public $content = '';
    public $message = array(
        'error' => '',
        'success' => '',
        'warning' => '',
        'info' => ''
    );
    public $title = '';
    public $menu = array();
    public $resources = 'resources';    # HTML path to the resources
    public $description = '';
    
    function add_title($content) {
        $this->title = $content;
    }

    function add_message($type,$content) {

        $this->message[$type] .= $content;
    }

    function add_content($content) {
        $this->content .= $content;
    }

    function add_menu($link,$text) {
        array_push($this->menu,array('link' => $link, 'text' => $text));
    }

    function render() {

        $out = '
        <!doctype html>
        <html lang="en">
          <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
            <meta name="description" content="' . $this->description . '">
            <meta name="generator" content="massyn dynamix">
            <title>' . $this->title . '</title>
        
            <!-- Bootstrap core CSS -->
        <link href="' . $this->resources . '/bootstrap-4.0.0/css/bootstrap.min.css" rel="stylesheet"  crossorigin="anonymous">
        
            <!-- Favicons -->
        
        <meta name="theme-color" content="#563d7c">
        
        
            <style>
              .bd-placeholder-img {
                font-size: 1.125rem;
                text-anchor: middle;
                -webkit-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
                user-select: none;
              }
        
              @media (min-width: 768px) {
                .bd-placeholder-img-lg {
                  font-size: 3.5rem;
                }
              }
            </style>
            <!-- Custom styles for this template -->
            <style>
            body {
                padding-top: 5rem;
              }
              .starter-template {
                padding: 3rem 1.5rem;
                text-align: center;
              }
              </style>
          </head>
          <body>
            <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
          <a class="navbar-brand" href="#">' . $this->title . '</a>
          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
        
          <div class="collapse navbar-collapse" id="navbarsExampleDefault">
            <ul class="navbar-nav mr-auto">
              <li class="nav-item active">
                <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
              </li>';
              # -- generate the menu
              # TODO - by order
              # TODO - filter by authorisation
              foreach ($this->menu as $m) {

                $out .= '<li class="nav-item"><a class="nav-link" href="' . $m['link'] . '">' . $m['text'] . '</a></li>';
    
            }

              $out .= '
              
            </ul>
            
          </div>
        </nav>
        
        <main role="main" class="container">
        <div class="starter-template">
  ';

        # == show the messages, if any
        if($this->message['error'] != '') {
            $out .= '<div class="alert alert-danger" role="alert">'  . $this->message['error'] . '</div>';
        }
        if($this->message['success'] != '') {
            $out .= '<div class="alert alert-success" role="alert">'  . $this->message['success'] . '</div>';
        }
        if($this->message['warning'] != '') {
            $out .= '<div class="alert alert-warning" role="alert">'  . $this->message['warning'] . '</div>';
        }
        if($this->message['info'] != '') {
            $out .= '<div class="alert alert-info" role="alert">'  . $this->message['info'] . '</div>';
        }
        

        $out .= $this->content;

        $out .= '
        </div>

</main><!-- /.container -->
<script src="' . $this->resources . '/jquery/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
<script src="' . $this->resources . '/bootstrap-4.0.0/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>';

        return $out;
    }

    function form($meta,$schema) {
      $html = '';
      # == set the form title
      if (isset($meta['title'])) {
         $html .= "<h2>" . $meta['title'] . "</h2>\n";
      }
      if(!isset($meta['function'])) {
         $this->add_message('error','<b>ERROR</b> - You need to set the function field');
      }
      if(!isset($meta['table'])) {
         $this->add_message('error','<b>ERROR</b> - You need to set the table field');
      }
      $html .= "<form method=\"post\">\n";

      foreach ($this->CORE->flattenSchema($schema[$meta['table']]) as $field => $d) {
         $value = '';    // TODO - read the variables for edit forms

         $html .= "<div class=\"form-group row\">";
         $html .= "\n<label for=\"$field\" class=\"col-sm-2 col-form-label\">" . $d['desc'] . "</label>";
         $html .= "<div class=\"col-sm-10\">";
            
         if ($d['required']) {
               $RI = " is-invalid";
               $RR = " required";
         } else {
               $RI = "";
               $RR = "";
         }

         if($d['type'] == 'text') {
            $html .= "<input type=\"text\" class=\"form-control$RI\" id=\"$field\" placeholder=\"" . $d['desc'] . "\" name=\"$field\" value=\"" . htmlentities($value) . "\" autocomplete=\"off\"$RR>\n";
         } elseif($d['type'] == 'textarea') {
            $html .= "<textarea class=\"form-control$RI\" id=\"$field\" placeholder=\"" . $d['desc'] . "\" name=\"$field\"$RR>" . htmlentities($value) . "</textarea>\n";
         } else {
            $html .= "TODO";
         }
            
         if ($d['helptext'] != '') {
            $html .= "<small id=\"" . $field . "Help\" class=\"form-text text-muted\">" . $d['helptext'] . "</small>\n";
         }

         $html .= "</div>\n";
         $html .= "</div>\n";
      }
      $html .= "<button type=\"submit\" class=\"btn btn-primary\">" . $meta['function'] . "</button>\n";
      $html .= "<input type=\"hidden\" id=\"_func\" name=\"_func\" value=\"" . $meta['function'] . "\">\n";

      // inject the csrf token (if there is one)
      if(isset($_SESSION['csrf'])) {
         $html .= "<input type=\"hidden\" id=\"_csrf\" name=\"_csrf\" value=\"" . $_SESSION['csrf'] . "\">\n";
      }

      $html .= "</form>";

      return $html;
   }
}