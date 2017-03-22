<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       
 * @since      1.0.0
 *
 * @package    ppwpslack
 * @subpackage ppwpslack/admin
 */
class ppwpslack_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The plugin basename of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_basename The plugin basename of the plugin.
     */
    protected $plugin_basename;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     *
     * @param      string $plugin_name The name of this plugin.
     * @param      string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        $this->plugin_basename = plugin_basename(plugin_dir_path(__DIR__) . $plugin_name . '.php');
    }

    /**
     * Ajax for sending test notification
     */
    public function ppwpslack_test_notification() {

        check_ajax_referer('ppwpslack', 'security');
        $message    = (isset($_POST['message']) && $_POST['message'] != null) ? $_POST['message'] : '';
        $serviceurl = (isset($_POST['serviceurl']) && $_POST['serviceurl'] != null) ? $_POST['serviceurl'] : '';
        $channel    = (isset($_POST['channel']) && $_POST['channel'] != null) ? $_POST['channel'] : '';
        $username   = (isset($_POST['username']) && $_POST['username'] != null) ? $_POST['username'] : '';
        $iconemoji  = (isset($_POST['iconemoji']) && $_POST['iconemoji'] != null) ? $_POST['iconemoji'] : '';

        ppwp_post_to_slack($message, $serviceurl, $channel, $username, $iconemoji);
    }

    /**
     * Ajax for sending test notification
     */
    public function ppwpslack_enable_disable() {

        check_ajax_referer('ppwpslack', 'security');

        $enable      = (isset($_POST['enable']) && $_POST['enable'] != null) ? intval($_POST['enable']) : 0;
        $post_id     = (isset($_POST['postid']) && $_POST['postid'] != null) ? intval($_POST['postid']) : 0;
        $fieldValues = get_post_meta($post_id, '_ppwpslack', true);
        if ($post_id > 0) {
            $fieldValues['enable'] = $enable;

            update_post_meta($post_id, '_ppwpslack', $fieldValues);
        }

        echo $enable;

        wp_die();
    }

    /**
     * Store all enents/hooks to post on slack.
     *
     * @return mixed|void
     */
    public function ppwpslack_events() {

        $events        = array(
            'post_published' => array(
                'title'         => __('Core: When a post is published', $this->plugin_name), //will be shown in post edit screen
                'hook'          => 'publish_post', //as defined by wordpress
                'accepted_args' => 2, //
                'priority'      => 10, //if not used then we will use 10
                'category'      => array(
                    'section' => 'general',
                    'title'   => __('General', $this->plugin_name)
                ),
                'message'       => function ($ID, $post) {
            $author_id = $post->post_author;
            $author    = get_user_by('ID', $author_id);

            $author_name = $author->display_name;
            $author_url  = get_edit_user_link($author_id);

            $title     = $post->post_title;
            $permalink = get_permalink($ID);

            $message = sprintf(__('Post "<%s|%s>" written by User <%s|%s>', $this->plugin_name), $permalink, $title, $author_url, $author_name);

            return $message;
        }
            ),
            'post_deleted' => array(
                'title'         => __('Core: When a post is trashed', $this->plugin_name), //will be shown in post edit screen
                'hook'          => 'wp_trash_post', //action name
                'accepted_args' => 1, //
                'priority'      => 10, //if not used then we will use 10
                'category'      => array(
                    'section' => 'general',
                    'title'   => __('General', $this->plugin_name)
                ),
                'message'       => function ($ID) {
            $permalink = get_permalink($ID);
            $message   = sprintf(__('Post "<%s|%s>" has been trashed.', $this->plugin_name), $permalink, get_the_title($ID));
            return $message;
        }
            ),
            'save_post' => array(
                'title'         => __('Core: When a post is updated', $this->plugin_name), //will be shown in post edit screen
                'hook'          => 'save_post', //action name
                'accepted_args' => 1, //
                'priority'      => 10, //if not used then we will use 10
                'category'      => array(
                    'section' => 'general',
                    'title'   => __('General', $this->plugin_name)
                ),
                'message'       => function ($post_id) {
                
                                   if (wp_is_post_revision($post_id)) return;

                                   $post_title = get_the_title($post_id);
                                   $post_url   = get_permalink($post_id);
                                
                                   $message   = sprintf(__('Post "<%s|%s>" has been updated.', $this->plugin_name), $post_url, $post_title);
                                   return $message;
            
                }),
                        'comment_post' => array(
                'title'         => __('Core: When a comment is given', $this->plugin_name), //will be shown in post edit screen
                'hook'          => 'comment_post', //action name
                'accepted_args' => 2, //
                'priority'      => 10, //if not used then we will use 10
                'category'      => array(
                    'section' => 'general',
                    'title'   => __('General', $this->plugin_name)
                ),
                'message'       => function ($comment_ID, $comment_approved) {
                
                                   $comment_obj  = get_comment( $comment_ID );
                                   $post_url   = get_permalink($comment_obj->comment_post_ID);
                                   $post_title = get_the_title($comment_obj->comment_post_ID);
                                   $message   = sprintf(__('Comment for the Post <%s|%s> is given.', $this->plugin_name), $post_url,$post_title);
                                   return $message;
            
                }),
            
            'user_register' => array(
                'title'         => __('Core: New User Registration', $this->plugin_name), //will be shown in post edit screen
                'hook'          => 'user_register', //action name
                'accepted_args' => 1, //
                'priority'      => 10, //if not used then we will use 10
                'category'      => array(
                    'section' => 'general',
                    'title'   => __('General', $this->plugin_name)
                ),
                'message'       => function ($user_id) {
            $user           = get_userdata($user_id);
            $edit_user_link = get_edit_user_link($user_id);
            $message        = sprintf(__('New User "<%s|%s>" has registered', $this->plugin_name), $edit_user_link, $user->first_name . ' ' . $user->last_name);
            return $message;
        }
            ),
            'buddypress_newpost' => array(
                'title'         => __('Buddpress: New Status', $this->plugin_name), //will be shown in post edit screen
                'hook'          => 'bp_activity_add', //action name ///bp-activity/bp-activity-functions.php :: Trac Source Line: 827
                'accepted_args' => 1, //
                'priority'      => 10, //if not used then we will use 10
                'category'      => array(
                    'section' => 'communication',
                    'title'   => __('Communication', $this->plugin_name)
                ),
                'message'       => function ($r) {
            $user    = get_userdata($r['user_id']);
            //$edit_user_link = get_edit_user_link( $user_id );
            $message = sprintf(__('Buddypress New Status: %s -- by  "<%s|%s>"', $this->plugin_name), $r['content'], $r['primary_link'], $user->first_name . ' ' . $user->last_name);
            return $message;
        }
            ),
            'bbpress_newtopic' => array(
                'title'         => __('bbPress: New Topic', $this->plugin_name), //will be shown in post edit screen
                'hook'          => 'bbp_new_topic',
                'accepted_args' => 5, //
                'priority'      => 10, //if not used then we will use 10
                'category'      => array(
                    'section' => 'communication',
                    'title'   => __('Communication', $this->plugin_name)
                ),
                'message'       => function ($topic_id = 0, $forum_id = 0, $anonymous_data = false, $author_id = 0, $is_edit = false) {

            $post = get_post($topic_id);

            $type      = $post->post_type;
            $permalink = get_permalink($topic_id);
            $user      = get_userdata($author_id);

            //$edit_user_link = bbp_user_profile_url( bbp_get_current_user_id() );

            $message = sprintf(__('bbPRess New Topic: "<%s|%s>" -- by  %s', $this->plugin_name), $permalink, $post->post_title, $user->first_name . ' ' . $user->last_name);
            return $message;
        }
            ),
        );

        return apply_filters('ppwpslack_events', $events);
    }

    /**
     * Add Action for calling Manage events
     */
    public function call_ppwpslack() {

        $this->manage_events();
    }

    /**
     * Handle/Manage all events/hooks thats are stored to post on slack.
     */
    public function manage_events() {

        global $post;


        $posts_per_page = 5;
        $posts_per_page = apply_filters('ppwpslack_count', $posts_per_page);


        $ppwpslack_events = $this->ppwpslack_events();

        $all_events = get_posts(array(
            'post_type'      => 'ppwpslack',
            'nopaging'       => true,
            'posts_per_page' => $posts_per_page,
        ));

        foreach ($all_events as $event) {
            //for each slack
            $setting = get_post_meta($event->ID, '_ppwpslack', true);

            $enable = isset($setting['enable']) ? intval($setting['enable']) : 0;

            if (isset($setting['event']) && $setting['event'] != null && $enable) {

                foreach ($setting['event'] as $targetted_hook => $value) {
                    if ($value == 'on') {
                        $message    = $ppwpslack_events[$targetted_hook]['message']; //it could be a dynamic anon function or  method or string
                        $priority   = isset($ppwpslack_events[$targetted_hook]['priority']) ? intval($ppwpslack_events[$targetted_hook]['priority']) : 10;
                        $arg_number = isset($ppwpslack_events[$targetted_hook]['accepted_args']) ? intval($ppwpslack_events[$targetted_hook]['accepted_args']) : 1;
                        $obj        = $this;

                        $hook_callback = function () use ($setting, $message, $obj) {

                            if (is_callable($message)) {
                                //for function
                                $msg = call_user_func_array($message, func_get_args());
                            } elseif (is_string($message)) {
                                //for string
                                $msg = $message;
                            } else {
                                $msg = '';
                            }
                            //send notification
                            ppwp_post_to_slack($msg, $setting['serviceurl'], $setting['channel'], $setting['username'], $setting['iconemoji']);
                        };
                        add_action($ppwpslack_events[$targetted_hook]['hook'], $hook_callback, $priority, $arg_number);
                    }
                }
            }
        }

        wp_reset_postdata();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles($hook) {

        $screen = get_current_screen();
        wp_register_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/ppwpslack-admin.css', array(), $this->version, 'all');


        if ($screen->id == 'ppwpslack') {
            wp_enqueue_style($this->plugin_name);
        }

        global $post_type;

        if ($hook == 'edit.php') {
            if ('ppwpslack' == $post_type) {
                wp_register_style('switcherycss', plugin_dir_url(__FILE__) . 'css/switchery.min.css', array(), $this->version, 'all');
                wp_enqueue_style('switcherycss');
            }
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        global $post_type;


        wp_register_script($this->plugin_name . 'switcheryjs', plugin_dir_url(__FILE__) . 'js/switchery.js', array('jquery'), $this->version, false);
        wp_register_script($this->plugin_name . 'ppwpslack-admin', plugin_dir_url(__FILE__) . 'js/ppwpslack-admin.js', array('jquery', $this->plugin_name . 'switcheryjs'), $this->version, false);


        if ($post_type == 'ppwpslack') {

            //var_dump('hi there');
            $ppwpslack_translation = array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('ppwpslack'),
                'message' => sprintf(__('Test Message send <%s|ppwpslack> Plugin.', 'ppwpslack'), 'https://www.pluginspal.com/'),
                'success' => __('Test notification sent successfully!', 'ppwpslack')
            );
            wp_localize_script($this->plugin_name . 'ppwpslack-admin', 'ppwpslack', $ppwpslack_translation);
            wp_enqueue_script($this->plugin_name . 'switcheryjs');
            wp_enqueue_script($this->plugin_name . 'ppwpslack-admin');
        }
    }

    /**
     * Register Custom Post Type "ppwpslack"
     */
    public function create_ppwpslack() {

        $labels = array(
            'name'               => _x('Slacks', 'Post Type General Name', $this->plugin_name),
            'singular_name'      => _x('Slack', 'Post Type Singular Name', $this->plugin_name),
            'menu_name'          => __('Slack Notifications', $this->plugin_name),
            'parent_item_colon'  => __('Parent Slacks:', $this->plugin_name),
            'all_items'          => __('All Slacks', $this->plugin_name),
            'view_item'          => __('View Slack', $this->plugin_name),
            'add_new_item'       => __('Add New Slack', $this->plugin_name),
            'add_new'            => __('Add New', $this->plugin_name),
            'edit_item'          => __('Edit Slack', $this->plugin_name),
            'update_item'        => __('Update Slack', $this->plugin_name),
            'search_items'       => __('Search Slack', $this->plugin_name),
            'not_found'          => __('Not found', $this->plugin_name),
            'not_found_in_trash' => __('Not found in Trash', $this->plugin_name),
        );
        $args   = array(
            'label'               => __('ppwpslack', $this->plugin_name),
            'description'         => __('Slack', $this->plugin_name),
            'labels'              => $labels,
            'supports'            => array('title'),
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            //'menu_position'       => 5,
            'menu_icon'           => 'dashicons-list-view',
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'page',
        );
        register_post_type('ppwpslack', $args);
    }

    /**
     * @return mixed
     */
    public function ppwpslack_events_sections() {

        $sections = array(
            'general' => __('General Events', $this->plugin_name),
        );

        return apply_filters('ppwpslack_event_sections', $sections);
    }

    /**
     * @param string $current
     */
    public function page_sections_tab($current = 'general') {
        $tabs   = array();
        $events = $this->ppwpslack_events();

        foreach ($events as $key => $value) {
            if (!array_key_exists($value['category']['section'], $tabs)) {
                $tabs[$value['category']['section']] = $value['category']['title'];
            }
        }

        $html = '<h2 class="nav-tab-wrapper">';
        foreach ($tabs as $key => $value) {
            $class = ($key == $current) ? 'nav-tab-active' : '';
            $html .= '<a class="nav-tab ' . $class . '" href="#">' . $value . '</a>';
        }
        $html .= '</h2>';
        echo $html;
    }

    /**
     * Adding meta box under cbxslack custom post types
     */
    public function add_meta_boxes() {

        add_meta_box(
                'ppwpslackmetabox', __('PP Slack Settings', $this->plugin_name), array($this, 'ppwpslackmetabox_display'), 'ppwpslack', 'normal', 'high'
        );

        add_meta_box(
                'ppwpslackmetaboxevents', __('PP Slack Events', $this->plugin_name), array($this, 'ppwpslackmetaboxevents_display'), 'ppwpslack', 'normal', 'high'
        );

        add_meta_box(
                'ppwpslackmetaboxtest', __('PP Slack Test', $this->plugin_name), array($this, 'ppwpslackmetaboxside_display'), 'ppwpslack', 'side', 'low'
        );
    }

    /**
     * @param $post
     */
    public function ppwpslackmetaboxside_display($post) {

        $fieldValues = get_post_meta($post->ID, '_ppwpslack', true);
        $serviceurl  = (isset($fieldValues['serviceurl']) && $fieldValues['serviceurl'] != null) ? $fieldValues['serviceurl'] : '';
        $channel     = (isset($fieldValues['channel']) && $fieldValues['channel'] != null) ? $fieldValues['channel'] : '';
        $username    = (isset($fieldValues['username']) && $fieldValues['username'] != null) ? $fieldValues['username'] : '';

        $iconemoji = (isset($fieldValues['iconemoji']) && $fieldValues['iconemoji'] != null) ? $fieldValues['iconemoji'] : '';
        $enable    = isset($fieldValues['enable']) ? intval($fieldValues['enable']) : 0; //by default disable



        $cbx_ajax_icon = plugins_url('ultimate-slack-notifications/admin/css/busy.gif');

        echo sprintf('<input class="button-primary ppwpslack_test" type="submit" name="ppwpslack_test" value="%s" data-serviceurl="%s" data-channel="%s" data-username="%s" data-iconemoji="%s"/>', __('Send Test Notification', 'ppwpslack'), $serviceurl, $channel, $username, $iconemoji) . '<span data-busy="0" class="ppwpslack_ajax_icon"><img
                            src="' . $cbx_ajax_icon . '"/></span>';
    }

    /**
     * Displaying Meta boxes Header
     *
     * @param $post
     */
    public function ppwpslackmetaboxevents_display($post) {

        $sections = array();

        $fieldValues = get_post_meta($post->ID, '_ppwpslack', true);

        //saved events
        $ppwpslack_event = isset($fieldValues['event']) ? $fieldValues['event'] : null;

        //all events
        $events = $this->ppwpslack_events();

        foreach ($events as $key => $value) {
            if (!array_key_exists($value['category']['section'], $sections)) {
                $sections[$value['category']['section']] = $value['category']['title'];
            }
        }
        //show tabs
        $this->page_sections_tab('general');
        ?>
        <div id='sections'>
        <?php foreach ($sections as $key => $value) { ?>
                <section class="cbxslackeventsection">
            <?php foreach ($events as $eventkey => $params) { ?>
                <?php if ($params['category']['section'] == $key) { ?>
                            <input id="cbxdynamicsidebarmetabox_fields_events_<?php echo $eventkey; ?>"
                                   type="checkbox"
                                   name="ppwpslackmetabox[event][<?php echo $eventkey; ?>]" <?php (isset($ppwpslack_event[$eventkey])) ? checked($ppwpslack_event[$eventkey], 'on') : '' ?>/>
                    <?php _e($params['title'], $this->plugin_name); ?><br/>
                <?php } ?>
            <?php } ?>
                </section>
            <?php } ?>

        </div>

    <?php
    }

    /**
     * Displaying Meta boxes
     *
     * @param $post
     */
    public function ppwpslackmetabox_display($post) {

        $fieldValues = get_post_meta($post->ID, '_ppwpslack', true);

        wp_nonce_field('ppwpslackmetabox', 'ppwpslackmetabox[nonce]');

        $serviceurl = isset($fieldValues['serviceurl']) ? html_entity_decode($fieldValues['serviceurl']) : '';
        $channel    = isset($fieldValues['channel']) ? html_entity_decode($fieldValues['channel']) : '';
        $username   = isset($fieldValues['username']) ? html_entity_decode($fieldValues['username']) : '';
        $iconemoji  = isset($fieldValues['iconemoji']) ? html_entity_decode($fieldValues['iconemoji']) : '';

        $enable = isset($fieldValues['enable']) ? intval($fieldValues['enable']) : 0;

        echo '<div id="ppwpslackmetabox_wrapper">';
        ?>

        <table class="form-table">
            <tbody>
                <tr valign="top">


                    <th scope="row"><label for="cbxdynamicsidebarmetabox_fields_class"><?php echo __('Slack Enable/Disable', $this->plugin_name) ?></label></th>
                    <td>
            <legend class="screen-reader-text"><span>input type="radio"</span></legend>
            <label title='g:i a'>
                <input type="radio" name="ppwpslackmetabox[enable]" value="0" <?php checked($enable, '0', TRUE); ?>  />
                <span><?php esc_attr_e('No', $this->plugin_name); ?></span>
            </label><br>
            <label title='g:i a'>
                <input type="radio" name="ppwpslackmetabox[enable]" value="1" <?php checked($enable, '1', TRUE); ?> />
                <span><?php esc_attr_e('Yes', $this->plugin_name); ?></span>
            </label>

        </td>
        </tr>

        <tr valign="top">
            <th scope="row"><label
                    for="ppwpslackmetabox_fields_serviceurl"><?php echo __('Service Url', $this->plugin_name); ?></label>
            </th>
            <td>
                <input id="ppwpslackmetabox_fields_serviceurl" class="regular-text" type="text"
                       name="ppwpslackmetabox[serviceurl]" placeholder="incoming-webhook-url"
                       value="<?php echo htmlentities($serviceurl); ?>"/>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label
                    for="ppwpslackmetabox_fields_channel"><?php echo __('Channel Name', $this->plugin_name) ?></label>
            </th>
            <td>
                <input id="ppwpslackmetabox_fields_before_channel" class="regular-text" type="text"
                       name=ppwpslackmetabox[channel]" placeholder="#general"
                       value="<?php echo htmlentities($channel); ?>"/>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label
                    for="cbxdynamicsidebarmetabox_fields_username"><?php echo __('Username', $this->plugin_name) ?></label>
            </th>
            <td>
                <input id="cbxdynamicsidebarmetabox_fields_username" class="regular-text" type="text"
                       name="ppwpslackmetabox[username]" placeholder="username"
                       value="<?php echo htmlentities($username); ?>"/>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label
                    for="cbxdynamicsidebarmetabox_fields_iconemoji"><?php echo __('Icon Emoji', $this->plugin_name); ?>
					<a href='http://www.emoji-cheat-sheet.com/' target='_blank' title='<?php echo __('Get The Icon', $this->plugin_name) ?>'><?php echo __('(Get The Icon)', $this->plugin_name); ?></a>
					</label>
            </th>
            <td>
                <input id="cbxdynamicsidebarmetabox_fields_iconemoji" class="regular-text" type="text"
                       name="ppwpslackmetabox[iconemoji]" placeholder=":rocket:"
                       value="<?php echo htmlentities($iconemoji); ?>"/>
            </td>
        </tr>

        </tbody>
        </table>

        <?php
        echo '</div>';
    }

//end display metabox

    /**
     * Saving post with post meta.
     *
     * @param        int $post_id            The ID of the post being save
     * @param            bool                Whether or not the user has the ability to save this post.
     */
    public function save_post($post_id, $post) {

        $post_type = 'ppwpslack';

        if ($post_type != $post->post_type) {
            return;
        }

        if (!empty($_POST['ppwpslackmetabox'])) {

            $postData = $_POST['ppwpslackmetabox'];

            $saveableData = array();

            if ($this->user_can_save($post_id, 'ppwpslackmetabox', $postData['nonce'])) {

                $saveableData['serviceurl'] = esc_attr($postData['serviceurl']);
                $saveableData['channel']    = esc_attr($postData['channel']);
                $saveableData['username']   = esc_attr($postData['username']);
                $saveableData['iconemoji']  = esc_attr($postData['iconemoji']);
                $saveableData['enable']     = intval($postData['enable']);
                $saveableData['event']      = $postData['event']; //arrat

                update_post_meta($post_id, '_ppwpslack', $saveableData);
            }
        }
    }

    /**
     * Determines whether or not the current user has the ability to save meta data associated with this post.
     *
     * @param $post_id
     * @param $action
     * @param $nonce
     *
     * @return bool
     */
    public function user_can_save($post_id, $action, $nonce) {

        $is_autosave    = wp_is_post_autosave($post_id);
        $is_revision    = wp_is_post_revision($post_id);
        $is_valid_nonce = (isset($nonce) && wp_verify_nonce($nonce, $action));

        // Return true if the user is able to save; otherwise, false.
        return !($is_autosave || $is_revision) && $is_valid_nonce;
    }

// end user_can_save

    /**
     * Listing of posts Column Header
     *
     * @param $columns
     *
     * @return mixed
     */
    public function columns_header($columns) {

        unset($columns['date']);

        $columns['serviceurl'] = __('Service Url', $this->plugin_name);
        $columns['channel']    = __('Slack Channel', $this->plugin_name);
        $columns['username']   = __('Slack username', $this->plugin_name);
        $columns['iconemoji']  = __('Slack Icon', $this->plugin_name);
        $columns['enable']     = __('State', $this->plugin_name);

        //$columns['events']     = __('Events to notify', $this->plugin_name);

        return $columns;
    }

    /**
     * Listing of each row of post type.
     *
     * @param $column
     * @param $post_id
     */
    public function custom_column_row($column, $post_id) {
        $setting = get_post_meta($post_id, '_ppwpslack', true);

        switch ($column) {
            case 'serviceurl':
                echo!empty($setting['serviceurl']) ? sprintf('<a href="%s" target="_blank">%s</a>', esc_url($setting['serviceurl']), esc_html($setting['serviceurl'])) : '';
                break;
            case 'channel':
                echo!empty($setting['channel']) ? $setting['channel'] : '';
                break;
            case 'username':
                echo!empty($setting['username']) ? $setting['username'] : '';
                break;
            case 'iconemoji':
                echo!empty($setting['iconemoji']) ? $setting['iconemoji'] : '';
                break;
            case 'enable':
                //integration of lcswitch https://github.com/LCweb-ita/LC-switch
                $enable = !empty($setting['enable']) ? intval($setting['enable']) : 0;
                echo '<input data-postid="' . $post_id . '" ' . (($enable == 1) ? ' checked="checked" ' : '') . ' type="checkbox"  value="' . $enable . '" class="js-switch cbxslackjs-switch" autocomplete="off" />';
                break;
        }
    }

    public function remove_menus() {

        $button_count = wp_count_posts('ppwpslack');


        //remove add button option if already one button is created //maximum 15
        if ($button_count->publish > 15) {
            do_action('ppwpslack_remove', $this);
        }
    }

    public function ppwpslack_remove_core() {
        remove_submenu_page('edit.php?post_type=ppwpslack', 'post-new.php?post_type=ppwpslack');        //remove add feedback menu

        $result    = stripos($_SERVER['REQUEST_URI'], 'post-new.php');
        $post_type = isset($_REQUEST['post_type']) ? esc_attr($_REQUEST['post_type']) : '';

        if ($result !== false) {
            if ($post_type == 'ppwpslack') {
                wp_redirect(get_option('siteurl') . '/wp-admin/edit.php?post_type=ppwpslack&ppwpslack_error=true');
            }
        }
    }

    /**
     * Showing Admin notice
     *
     */
    function permissions_admin_notice() {
        echo "<div id='permissions-warning' class='error fade'><p><strong>" . sprintf(__('Sorry, you can not create more than 5 slacks in free verion, <a target="_blank" href="%s">Grab Pro</a>', 'ppwpslack'), 'https://www.pluginspal.com/') . "</strong></p></div>";
    }

    /**
     * Admin notice if user try to create new button in free version
     */
    function ppwpslack_notice() {
        if (isset($_GET['ppwpslack_error'])) {
            add_action('admin_notices', array($this, 'permissions_admin_notice'));
        }
    }

    /**
     * Add Setting links in plugin listing
     *
     * @param $links
     *
     * @return mixed
     */
    public function add_ppwpslack_settings_link($links) {
        //$settings_link = '<a href="options-general.php?page=wpfixedverticalfeedbackbutton">'.__('Settings','wpfixedverticalfeedbackbuttonaddon').'</a>';
        //array_unshift($links, $settings_link);
        $support_link = '<a target="_blank" href="#">' . __('Support', 'ppwpslack') . '</a>';
        array_unshift($links, $support_link);

        return $links;
    }

    /**
     * Add support link to plugin description in /wp-admin/plugins.php
     *
     * @param  array  $plugin_meta
     * @param  string $plugin_file
     *
     * @return array
     */
    public function support_link($plugin_meta, $plugin_file) {

        if ($this->plugin_basename == $plugin_file) {
            $plugin_meta[] = sprintf(
                    '<a target="_blank" href="%s">%s</a>', '#', __('Get Pro', 'ppwpslack')
            );
        }

        return $plugin_meta;
    }

}
