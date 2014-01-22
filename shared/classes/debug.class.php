<?php
/* Inbound Now Debug Class
*
*  This class enabled users to dequeue third party javascript from pages to stop JS errors
*/

if (!defined('INBOUND_CLASS_URL'))
    define('INBOUND_CLASS_URL', plugin_dir_url(__FILE__));

  //update_option( 'inbound_global_dequeue', "" );
  /*
  $global_array = get_option( 'inbound_global_dequeue' );
  print_r($global_array);
  /**/

if (!class_exists('InboundDebugScripts')) {
  class InboundDebugScripts {
    static $add_debug;

  /*  Contruct
   *  --------------------------------------------------------- */
    static function init() {
      self::$add_debug = true;

      add_action('wp_enqueue_scripts', array(__CLASS__, 'inbound_kill_bogus_scripts'), 100);
      add_action('wp_enqueue_scripts', array(__CLASS__, 'inbound_compatibilities'), 101);
      add_action('admin_enqueue_scripts', array(__CLASS__, 'inbound_compatibilities'), 101);
      add_action('admin_enqueue_scripts', array(__CLASS__, 'inbound_kill_bogus_admin_scripts'), 100);
      add_action('wp_ajax_inbound_dequeue_js', array(__CLASS__, 'inbound_dequeue_js'));
      add_action('wp_ajax_nopriv_inbound_dequeue_js', array(__CLASS__, 'inbound_dequeue_js'));
      add_action('wp_ajax_inbound_dequeue_admin_js', array(__CLASS__, 'inbound_dequeue_admin_js'));
      add_action('wp_ajax_nopriv_inbound_dequeue_admin_js', array(__CLASS__, 'inbound_dequeue_admin_js'));
    }

    static function inbound_dequeue_js() {
      if ( ! self::$add_debug )
      return;

          // Post Values
          $post_id = (isset( $_POST['post_id'] )) ? $_POST['post_id'] : "";
          $the_script = (isset( $_POST['the_script'] )) ? $_POST['the_script'] : "";
          $status = (isset( $_POST['status'] )) ? $_POST['status'] : "";
          $admin_screen = (isset( $_POST['admin_screen'] )) ? $_POST['admin_screen'] : "";

          /* Store Script Data to Post */
        $script_data = get_post_meta( $post_id, 'inbound_dequeue_js', TRUE );
        $script_data = json_decode($script_data,true);
          if(is_array($script_data)) {

            if($status === 'off') {
              // add or remove from list
              $script_data[$the_script] = $status;
            } else {
              unset($script_data[$the_script]);
            }

        } else {
          // Create the first item in array
          if($status === 'off') {
          $script_data[$the_script] = $status;
          }
        }
          $script_save = json_encode($script_data);

          update_post_meta( $post_id, 'inbound_dequeue_js', $script_save );

          // Set global option inbound_global_dequeue_js

        $output =  array('encode'=> $script_save );

        echo json_encode($output,JSON_FORCE_OBJECT);
        wp_die();
     }

     static function inbound_dequeue_admin_js() {
       if ( ! self::$add_debug )
       return;

           // Post Values
           $post_id = (isset( $_POST['post_id'] )) ? $_POST['post_id'] : "";
           $the_script = (isset( $_POST['the_script'] )) ? $_POST['the_script'] : "";
           $status = (isset( $_POST['status'] )) ? $_POST['status'] : "";
           $admin_screen = (isset( $_POST['admin_screen'] )) ? $_POST['admin_screen'] : "";

           /* Store Script Data to Post */
         $script_data = get_option( 'inbound_global_dequeue' );

         if(is_array($script_data)) {

             if($status === 'off') {
               // add or remove from list
               $script_data[$the_script] = $admin_screen;
             } else {
               unset($script_data[$the_script]);
             }

         } else {
           // Create the first item in array
           if($status === 'off') {
           $script_data[$the_script] = $admin_screen;
           }
         }
           update_option( 'inbound_global_dequeue', $script_data );

           // Set global option inbound_global_dequeue_js

         $output =  array('encode'=> $script_data );

         echo json_encode($output,JSON_FORCE_OBJECT);
         wp_die();
      }

    static function script_whitelist() {
        $white_list_scripts =
        array( "admin-bar", // wp core
               "autosave", // wp core
               "common", // wp core
               "media-editor", // wp core
               "post", // wp core
               "thickbox", // wp core
               "utils", // wp core
               "svg-painter", // wp core
               "wp-auth-check", // wp core
               "customize-controls", // wp core
               "plugin-install", // wp core
               "editor", // wp core
               "media-upload", // wp core
               "jquery", // wp core
               "jquery-cookie",
               "jquery-ui-sortable",
               "form-population",
               "jquery-total-storage",
               "inbound-shortcodes-plugins",
               "inbound-shortcodes",
               "store-lead-ajax",
               "cta-view-track",
               "funnel-tracking",
               'cta-admin-bar',
               "inbound-dequeue-scripts");

        return $white_list_scripts;
    }
    // Destroy all bad frontend scripts
    static function inbound_kill_bogus_scripts() {
        if (!isset($_GET['inbound-dequeue-scripts'])) {
          global $wp_scripts, $wp_query;
          $script_list = $wp_scripts->queue; // All enqueued scripts
          $current_page_id = $wp_query->get_queried_object_id();
          $script_data = get_post_meta( $current_page_id , 'inbound_dequeue_js', TRUE );
          $script_data = json_decode($script_data,true);

          $white_list_scripts = self::script_whitelist();

            // dequeue frontent scripts
            foreach ($script_list as $key => $value) {
             if (!in_array($value, $white_list_scripts)) {
               // Kill bad scripts
               if (isset($script_data[$value]) && in_array($script_data[$value], $script_data)) {
                 wp_dequeue_script( $value ); // Kill bad script
               }
             }
            }

        }
    }

    // Destroy all bad admin scripts
    static function inbound_kill_bogus_admin_scripts() {
        if (!isset($_GET['inbound-dequeue-scripts'])) {
            // dequeue admin scripts
            $screen = get_current_screen();

              $array = array('load-qtip' => 'wp-call-to-action');
              //update_option( 'inbound_global_dequeue', $array );
              $global_array = get_option( 'inbound_global_dequeue' );
              //print_r($global_array);

            foreach ($global_array as $key => $value) {
              if ( $screen->id === $value) {
              wp_dequeue_script( $key );
              }
            }
        }
    }

    static function inbound_compatibilities() {

      if (isset($_GET['inbound-dequeue-scripts'])) {

          global $wp_query;
          $current_page_id = $wp_query->get_queried_object_id();
          $global_array = get_option( 'inbound_global_dequeue' );
          if( is_admin() ) {
            global $post;
            $screen = get_current_screen();
            $current =  $screen->id;
            $page_id = (isset($post->ID)) ? $post->ID : '';
          } else {
            $current = '';
            $screen = '';
            $page_id = $current_page_id;
          }

          //show_admin_bar( false );
          wp_enqueue_script('inbound-dequeue-scripts', INBOUND_CLASS_URL . 'js/inbound-dequeue-scripts.js', array( 'jquery' ));
          wp_localize_script( 'inbound-dequeue-scripts' , 'inbound_debug' , array( 'admin_url' => admin_url( 'admin-ajax.php' ), 'admin_screen' => $current, 'page_id' => $page_id));

            global $wp_scripts;

            $scripts_registers = $wp_scripts->registered;
            //echo "<pre>";
            //print_r($scripts_registers);
            //echo $scripts_registers['common']->src;


            $script_list = $wp_scripts->queue; // All enqueued scripts
            $white_list_scripts = self::script_whitelist();
            // TURN OFF ALL OTHER SCRIPTS FOR DISABLING
            foreach ($script_list as $key => $value) {
             //echo $key . $value;
             if (!in_array($value, $white_list_scripts)){
               wp_dequeue_script( $value );
             }

            }
            /*echo "<pre>";
             print_r($wp_scripts->queue);
             echo "</pre>"; */

             echo '<style type="text/css" media="screen">
             #launch-feedback {
             display:none;
           }
             #group{text-align: left;border-bottom: 1px solid #fff;position:relative;margin:0 auto;padding:6px 10px 10px;background-image:linear-gradient(top,rgba(255,255,255,.1),rgba(0,0,0,.1));background-color:#555;width:300px}#group:after{content:" ";position:absolute;z-index:1;top:0;left:0;right:0;bottom:0;border-radius:5px}.switch{margin: 0px;position:relative;border:0;padding:0;width:245px;font-family:helvetica;font-weight:700;font-size:22px;color:#222;text-shadow:0 1px 0 rgba(255,255,255,.3)}.switch legend{float:left;width:40%;padding:7px 10% 3px 0;text-align:left}.switch input{position:absolute;opacity:0}.switch legend:after{content:"";position:absolute;top:0;left:50%;z-index:0;width:50%;height:100%;padding:2px;background-color:#222;border-radius:3px;box-shadow:inset -1px 2px 5px rgba(0,0,0,.8),0 1px 0 rgba(255,255,255,.2)}.switch label{position:relative;z-index:2;float:left;width:25%;margin-top:2px;padding:5px 0 3px;text-align:center;color:#64676b;text-shadow:0 1px 0 #000;cursor:pointer;transition:color 0s ease .1s}.switch input:checked+label{color:#fff}.switch input:focus+label{outline:0}.switch .switch-button{clear:both;position:absolute;top:-1px;left:50%;z-index:1;width:25%;height:100%;margin:2px;background-color:#70c66b;background-image:linear-gradient(top,rgba(255,255,255,.2),rgba(0,0,0,0));border-radius:3px;box-shadow:0 0 0 2px #70c66b,-2px 3px 2px #000;transition:all .3s ease-out}.switch .switch-button:after{content:" ";position:absolute;z-index:1;top:0;left:0;right:0;bottom:0;border-radius:3px;border:1px dashed #fff}#inbound-dequeue-id{display:none}.switch input:last-of-type:checked~.switch-button{left:75%}.switch .switch-button.status-off{background-color:red;box-shadow:0 0 0 3px red,-2px 3px 5px #000}.switch label.turn-on{color:#fff}
           .script-info {padding-left:5px;}
           .debug-plugin-name { font-size:13px; color:#fff; text-shadow:none; padding-bottom: 6px;
display: inline-block; }
           .debug-plugin-name span.debug-head, .debug-script-head {color:#ccc; width: 45px;
display: inline-block;}
           .js-title { display:block; margin-bottom: 7px; color:#fff;}
           .fa-info-circle:before{ content: "\f05a"; font-family: FontAwesome !important; font-style:normal;}
           #group:last-of-type {
            padding-bottom:80px;
            }
            #main-debug-title {
            font-family: helvetica;
            font-weight: 700;
            font-size: 22px;
            color: #222;
            background: #ccc;
            padding: 10px;
            text-align: center;
            text-shadow: 0 1px 0 rgba(255,255,255,.3);
          }

            </style>';

        $script_data = get_post_meta( $current_page_id , 'inbound_dequeue_js', TRUE );
        $script_data = json_decode($script_data,true);


             echo '<div id="inbound-fix-page" class="'.$current_page_id.'" data-page-id="'.$page_id.'" data-admin-screen="'.$current.'" style="position:fixed; right:0px; padding-bottom: 80px; background-color: #555; overflow:auto; height: 100%; top: 32px; background:#fff; border: 1px solid; z-index: 999999999999; line-height: 1;">';
             echo "<div id='main-debug-title'>Turn off Javascript</div>";
             echo "<span id='inbound-dequeue-id'>".$current_page_id."</span>";

             foreach ($script_list as $key => $value) {
              if (!in_array($value, $white_list_scripts)){
              $checked =  "";
              $status_class = "";
                // Kill bad frontend script
                if (isset($script_data[$value]) && in_array($script_data[$value], $script_data)){
                  $checked =  "checked";
                  $status_class =  "status-off";
                  wp_dequeue_script( $value ); // Kill bad script
                }
                // Kill bad admin script
              if (is_array($global_array)) {
                  if (is_admin() && array_key_exists($value, $global_array)) {

                    if ($current === $global_array[$value] ) {
                    $checked =  "checked";
                    $status_class =  "status-off";
                    wp_dequeue_script( $value ); // Kill bad script
                    }
                  }
              }

              $actual_link = $scripts_registers[$value]->src;
              str_replace("?frontend=false", "", $actual_link);
              preg_match('/plugins\/([^\/]+?)\/(?:[^\/]+\/)?(.+)/', $actual_link, $matches);
              preg_match('/themes\/([^\/]+?)\/(?:[^\/]+\/)?(.+)/', $actual_link, $matches_two);

              $name_of_file = (isset($matches_two[1])) ? "<span class='debug-head'>Theme:</span> " . $matches_two[1] : '';
              if ($name_of_file === "") {
                  $name_of_file = (isset($matches[1])) ? "<span class='debug-head'>Plugin:</span> " . $matches[1] : '<span class="debug-head">From:</span> Wordpress Core Script <span style="color:#db3d3d; font-size:12px;">(Don\'t turn off)</span>';
              }


              echo '<div id="group">';
              echo '<span class="debug-plugin-name">'.$name_of_file.'</span>';
              echo "<div title='".$scripts_registers[$value]->src."' class='js-title'><span class='debug-script-head'>Script:</span> ". $value ."<span class='script-info'><i class='fa fa-info-circle'></i></span></div>";
               echo '<fieldset class="switch" id="'.$value.'">
                    <legend>Status:</legend>

                    <input id="'.$value.'-on" name="'.$value.'-status" type="radio" '.$checked.'>
                    <label for="'.$value.'-on" class="turn-on">On</label>

                    <input id="'.$value.'-off" name="'.$value.'-status" type="radio" '.$checked.'>
                    <label for="'.$value.'-off" class="turn-off">Off</label>

                    <span class="switch-button '.$status_class.'"></span>
                  </fieldset>

                  </div>';
              }
             }
             echo "</div>";

             // This will control the dequing
             /*
             foreach ($scripts_queued as $key => $value) {
              //echo $key . $value;
              if (!in_array($value, $white_list_scripts)){
                wp_dequeue_script( $value );
              }

             } */
        }

    }
}
}
/*  Initialize InboundNow Debug
 *  --------------------------------------------------------- */

InboundDebugScripts::init();

?>