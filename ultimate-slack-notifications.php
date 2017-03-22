<?php
    /**
     * The plugin bootstrap file
     *
     * @link              
     * @since             1.0.0
     * @package           ppwpslack
     *
     * @wordpress-plugin
     * Plugin Name:       Disciple Tools - Slack
     * Plugin URI:        https://pluginspal.com/wordpress-plugins/ultimate-slack-notifications
     * Description:       Disciple Tools Slack Integration. Notify your team members with each WordPress site activities by sending customized messages into Slack channels
     * Version:           1.1.0
     * Author:            PLuginsPal
     * Author URI:        
     * License:           GPL-2.0+
     * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
     * Text Domain:       Ultimate Slack Notifications
     * Domain Path:       /languages
     */

    // If this file is called directly, abort.
    if (!defined('WPINC')) {
        die;
    }

    /**
     * The code that runs during plugin activation.
     * This action is documented in includes/class-ppwpslack-activator.php
     */
    function activate_ppwpslack() {
        require_once plugin_dir_path(__FILE__) . 'includes/class-ppwpslack-activator.php';
        ppwpslack_Activator::activate();
    }

    /**
     * The code that runs during plugin deactivation.
     * This action is documented in includes/class-ppwpslack-deactivator.php
     */
    function deactivate_ppwpslack() {
        require_once plugin_dir_path(__FILE__) . 'includes/class-ppwpslack-deactivator.php';
        ppwpslack_Deactivator::deactivate();
    }

    register_activation_hook(__FILE__, 'activate_ppwpslack');
    register_deactivation_hook(__FILE__, 'deactivate_ppwpslack');

    /**
     * The core plugin class that is used to define internationalization,
     * admin-specific hooks, and public-facing site hooks.
     */
    require plugin_dir_path(__FILE__) . 'includes/class-ppwpslack.php';

    /**
     * Begins execution of the plugin.
     *
     * Since everything within the plugin is registered via hooks,
     * then kicking off the plugin from this point in the file does
     * not affect the page life cycle.
     *
     * @since    1.0.0
     */

    /**
     * Main Method for Posting a notification to Slack from WordPress environment
     *
     * @param $message
     * @param $sevice_url
     * @param $channel
     * @param $username
     * @param $icon_emoji
     *
     * @return bool|WP_Error
     */
    function ppwp_post_to_slack($message, $sevice_url, $channel, $username = 'ppwpslack', $icon_emoji = ':rocket:') {

        $slack_endpoint = $sevice_url;
        $data           = array(
            'payload' => json_encode(array(
                                         "channel"    => $channel,
                                         "text"       => $message,
                                         "username"   => $username,
                                         "icon_emoji" => $icon_emoji
                                     )
            )
        );

        $posting_to_slack = wp_remote_post($slack_endpoint, array(
                                                              'method'      => 'POST',
                                                              'timeout'     => 30,
                                                              'redirection' => 5,
                                                              'httpversion' => '1.0',
                                                              'blocking'    => true,
                                                              'headers'     => array(),
                                                              'body'        => $data,
                                                              'cookies'     => array()
                                                          )
        );

        if (is_wp_error($posting_to_slack)) {
            echo sprintf(__('Error Found ( %s )', $posting_to_slack->get_error_message()));
        } else {
            $status  = intval(wp_remote_retrieve_response_code($posting_to_slack));
            $message = wp_remote_retrieve_body($posting_to_slack);
            if (200 !== $status) {
                return new WP_Error('unexpected_response', $message);
            } else if (200 !== $status) {
                return true;
            }
        }
    }

    function run_ppwpslack() {

        $plugin_base = plugin_basename(__FILE__);

        //var_dump($plugin_base);
        $plugin = new ppwpslack($plugin_base);
        $plugin->run();

    }

    run_ppwpslack();