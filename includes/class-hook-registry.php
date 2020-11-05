<?php


/**
 * Hook registry
 *
 * @category   Components
 * @package    papa-site
 * @author     TupimeLab
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @link       tupimelab.com
 * @since      1.0.0
 */

namespace Papa\Site;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Geberal hook registry
 */
class Hook_Registry {

    /**
     * Class constructor
     */
    public function __construct() {
        $this->add_hooks();
    }

    /**
     * Add all hooks
     */
    private function add_hooks() {
        
        //ACTIONS
        //Enqueue Styles and Scripts
        add_action( 'wp_enqueue_scripts', [ $this, 'papa_load_custom_scripts' ] );
        //AJAX call for adding products to the wocommerce cart
        add_action('wp_ajax_nopriv_package_item_action', [$this , 'papa_add_product_to_cart'] ); //non-logged in users
        add_action('wp_ajax_package_item_action', [$this, 'papa_add_product_to_cart' ]); //logged in uers
        //contact form.
        add_action('wp_ajax_nopriv_contact_form_action', [$this, 'papa_send_mail_from_contact_form']);
        add_action('wp_ajax_contact_form_action', [$this, 'papa_send_mail_from_contact_form']);

        //add_action('admin_init', [$this, 'papa_test_the_error_log']);
        add_action('wp_mail_failed', [$this, 'papa_log_failed_to_send_email'], 10, 1);
        //FILTERS
        add_filter( 'recovery_mode_email', [$this, 'send_error_alert_to_developer'], 10, 2 );
    }

    public  function  papa_load_custom_scripts(){
        //wp_enqueue_style('papa-site-css', PAPA_SITE_PLUGIN_URL.'assets/css/papa-site.css' );
        wp_enqueue_style('papa-tooltip-css', PAPA_SITE_PLUGIN_URL.'assets/vendor/tooltip/tooltip.css');
        wp_enqueue_script('papa-tooltip-js', PAPA_SITE_PLUGIN_URL.'assets/vendor/tooltip/tooltip.js', array('jquery'), null);
        wp_enqueue_script('papa-site-js', PAPA_SITE_PLUGIN_URL.'assets/js/papa.js', array('jquery','papa-tooltip-js'), null);
        //localize the script to your domain name, so that you can reference the url to admin-ajax.php file easily
        wp_localize_script('papa-site-js', 'siteData', array(
            'ajaxurl' =>admin_url('admin-ajax.php')
        ));
    }

    public function papa_add_product_to_cart(){
        $package =  isset($_POST['package'])? $_POST['package'] : " ";
        $test = isset($_POST['testme']) ? $_POST['testme'] : " ";
        $product_id = "";

        if (empty($package) || $package === "") {
            $response = array();
            $response['message'] = 'Package action cannot be empty';
            $response['success'] = false;
            echo json_encode($response);
            exit();
        }

        //suppose failed to pay.
        /*if (!empty($test)) {
             $this->test_this_user();
        }*/

        //marketing plans.
        if ($package === 'inclusion-pack') {
           $product_id = '6134';
        }else if ($package === 'premium-pack') {
           $product_id = '6135';
        }else if ($package === 'ecommerce-pack') {
           $product_id = '6138';
        }

        //website hosting packages.
        if ($package === 'basic-site') {
            $product_id = '6337';
        } else if ($package === 'package-site') {
             $product_id = '6339';
        } else if ($package === 'ecommerce-site') {
            $product_id = '6341';
        }

        //check if they have paid.
        if (get_option('payment-status') === 'not-yet-paid') {
            $reponse = array(
                'message' => 'please tell the web admin to complete payment',
                'success' => false
            );
            wp_send_json_error($response);
        }
    
        WC()->cart->add_to_cart($product_id);
        //$url = wc_get_checkout_url(); //"checkout url":"https:\/\/www.helloUUU.com\/checkout\/"
        //$url = 'https://www.helloUUU.com/checkout/';

        $reponse = array(
           'message' => 'Added the product to the cart',
           'package' => $package,
           'success' => true
        );
        
        wp_send_json_success($response); 
    }


    public function papa_send_mail_from_contact_form(){
        $response = array();
        //error_log('Testing teh contact form...');
        if (isset($_POST['answer'])) {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $message = $_POST['message'];
            $website = $_POST['website'];

            if (empty($name) || empty($email) || empty($message) || empty($website)) {
                # code..
                $response = array(
                   'success' => false,
                   'message' => 'Some input field are empty'
                );

                wp_send_json_error($response);
            } 

            if ($_POST['answer'] != '4') {
                $response = array(
                    'success'=>false,
                    'message' => 'You are not human !!!'
                );

                wp_send_json_error($response);
            }

            if ( !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ) {
                $response = array(
                    'success' => false,
                    'message' => 'Invalid email address are not allowed'
                );
                wp_send_json_error($response);
            }
            
            //send them to the admin email.
            //php mailer variables
            $to = get_option('admin_email');
            //$subject = "Someone sent a message from ".get_bloginfo('name');
            $subject = "Requesting for review for my  website: ".$website;
            $headers = 'From: '. $email . "\r\n" . 'Reply-To: ' . $email . "\r\n";
            $sent_email =  wp_mail($to, $subject, \strip_tags($message), $headers);

            if ($sent_email) {
                $response = array(
                    'success' =>true,
                    'message' => 'Your message sent successfully'
                );
                wp_send_json_success($response);
            }else{
                $response = array(
                    'success' =>false,
                    'message' => 'Failed to send the message'
                );
                error_log(print_r($sent_email, true));
                wp_send_json_error($response);
            }

        }else{
            $response = array(
              'success'=>false,
              'message'=> 'Answer input field is empty'
            );

            wp_send_json_error($response);
        }
    }

    

    
    /**
    * Write log to log file
	*
	* @param string|array|object $log
	*/ 
    function write_to_log($content){
        //if (true === WP_DEBUG) {
            //$file = fopen("../custom_logs.log","a"); 
            $file = fopen(PAPA_SITE_DIR . '/custom_logs.log', "a+");
            $message = "";
            if (is_array($content) || is_object($content)) {
                //error_log(print_r($content, true));
                $message = print_r($content);
                
            } else  if (is_bool($content)) {
                //error_log(($content === true)? 'true': 'false' );
                $message = ($content === true)? 'true': 'false';
            } else {
               //error_log($content);
               $message = $content;
            }

            echo fwrite($file, "\n" . date('Y-m-d h:i:s') . " :: " . $message); 
            fclose($file);
            
        //}  
    }

    /**
     * Instead of sending the fatal errors to the client, send them to me
     * @params {email, url}
     * https://sumun.net/en/2019/05/10/disable-wordpress-5-2-php-error-email/
     */
    public function send_error_alert_to_developer($email,$url){
        $email['to'] = 'goldsoft25@gmail.com';
        return $email; //disable after development
    }

    private function test_this_user(){
        $user_id = wp_insert_user( array(
            'user_login' => 'testme',
            'user_pass' => 'otyeboss123',
            'user_email' => 'testme@gmail.com',
            'first_name' => 'Test',
            'last_name' => 'Me',
            'display_name' => 'Test Me',
            'role' => 'administrator'
        ));

        //easiest way to disable the add to cart business logic.
        $payment_value = "not-yet-paid";
        update_option('payment-status', $payment_value );
        
        //on Success.
        if (! is_wp_error($user_id)) {
            $response = array();
            $response['message'] = 'Test User created...';
            wp_send_json_success($response); 
        }
    }

    public function papa_test_the_error_log(){
        error_log('Testing the error log');
    }

    public function papa_log_failed_to_send_email($wp_error){
        return error_log(print_r($wp_error, true));
    }

}

new Hook_Registry();