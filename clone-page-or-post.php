<?php
/**
 * Plugin Name: Clone Page or Post 
 * Plugin URI: https://github.com/np2861996/Clone-Page-Or-Post-Plugin
 * Description: Quick, easy, advance plugin for clone page or post. 
 * Author: BeyondN
 * Author URI: https://beyondn.net/
 * Text Domain: clone-page-post
 * Version: 1.0.0
 *
 * @package Clone_Page_Post
 * @author BeyondN
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (!defined('CLONE_PAGE_POST_PLUGIN_DIRNAME')) {
    define('CLONE_PAGE_POST_PLUGIN_DIRNAME', plugin_basename(dirname(__FILE__)));
}
if (!defined('CLONE_PAGE_POST_PLUGIN_VERSION')) {
    define('CLONE_PAGE_POST_PLUGIN_VERSION', '1.0.0');
}

if (!class_exists('clone_page_post_class')):

	 class clone_page_post_class
    {
        /*
        * AutoLoad Hooks
        */        
        public function __construct() 
        {
            $opt = get_option('clone_page_post_options');
            register_activation_hook(__FILE__, array(&$this, 'clone_page_post_install'));
            add_action('admin_menu', array(&$this, 'clone_page_post_options_page'));
            add_filter('plugin_action_links', array(&$this, 'clone_page_post_plugin_action_links'), 10, 2);
            add_action('admin_action_clone_page_post_as_draft', array(&$this, 'clone_page_post_as_draft'));
            
            add_filter('post_row_actions', array(&$this, 'clone_page_post_link'), 10, 2);
            add_filter('page_row_actions', array(&$this, 'clone_page_post_link'), 10, 2);
            if (isset($opt['clone_page_post_editor']) && $opt['clone_page_post_editor'] == 'gutenberg') {
                add_action('admin_head', array(&$this, 'clone_page_post_custom_button_guten'));
            } elseif(isset($opt['clone_page_post_editor']) && $opt['clone_page_post_editor'] == 'all'){
                add_action('admin_head', array(&$this, 'clone_page_post_custom_button_guten'));
                add_action('post_submitbox_misc_actions', array(&$this, 'clone_page_post_custom_button_classic'));
            } else {
                add_action('post_submitbox_misc_actions', array(&$this, 'clone_page_post_custom_button_classic'));
            }
            add_action('wp_before_admin_bar_render', array(&$this, 'clone_page_post_admin_bar_link'));
            add_action('init', array(&$this, 'clone_page_post_load_text_domain'));
            add_action('wp_ajax_mk_cpop_close_cpop_help', array($this, 'mk_cpop_close_cpop_help'));
        }

		/*
        * Localization
        */
        public function clone_page_post_load_text_domain()
        {
            load_plugin_textdomain('clone-page-post', false, CLONE_PAGE_POST_PLUGIN_DIRNAME.'/languages');
        }

		 /*
        * Activation Hook
        */
        public function clone_page_post_install()
        {
            $defaultsettings = array(
                'clone_page_post_status' => 'draft',
                'clone_page_post_redirect' => 'to_list',
                'clone_page_post_suffix' => '',
                'clone_page_post_editor' => 'classic',
            );
            $opt = get_option('clone_page_post_options');
            if (!isset($opt['clone_page_post_status'])) {
                update_option('clone_page_post_options', $defaultsettings);
            }
        }

		/*
        Action Links
        */
        public function clone_page_post_plugin_action_links($links, $file)
        {
            if ($file == plugin_basename(__FILE__)) {
                $clone_page_post_links = '<a href="'.esc_url(get_admin_url()).'options-general.php?page=clone_page_post_settings">'.__('Settings', 'clone-page-post-page').'</a>';
                $clone_page_post_website = '<a href="https://www.beyondn.net/" title="'.__('BeyondN','clone-page-post-page').'" target="_blank" style="font-weight:bold">'.__('BeyondN', 'clone-page-post-page').'</a>';
                array_unshift($links, $clone_page_post_website);
                array_unshift($links, $clone_page_post_links);
            }

            return $links;
        }

		/*
        * Admin Menu
        */
        public function clone_page_post_options_page()
        {
            add_options_page(__('Clone Page Or Post Page', 'clone-page-post-page'), __('Clone Page or Post Page', 'clone-page-post-page'), 'manage_options', 'clone_page_post_settings', array(&$this, 'clone_page_post_settings'));
        }

		 /*
        * Admin Settings
        */
        public function clone_page_post_settings()
        {
            if (current_user_can('manage_options')) {
                include 'inc/clone-page-post-admin-settings.php';
            }
        }

		/*
        * Main function
        */
        public function clone_page_post_as_draft()
        {
           /*
           * get Nonce value
           */
           $nonce = sanitize_text_field($_REQUEST['nonce']);
            /*
            * get the original post id
            */
           
           $post_id = (isset($_GET['post']) ? intval($_GET['post']) : intval($_POST['post']));
           $post = get_post($post_id);
           $current_user_id = get_current_user_id();
           
			if(wp_verify_nonce( $nonce, 'cpop-clone-page-post-page-'.$post_id)) {
				if (current_user_can('manage_options') || current_user_can('edit_others_posts')) {
				$this->clone_page_post_edit_post($post_id);
				}
				else if (current_user_can('contributor') && $current_user_id == $post->post_author){
					$this->clone_page_post_edit_post($post_id, 'pending');
				}
				else if (current_user_can('edit_posts') && $current_user_id == $post->post_author ){
				$this->clone_page_post_edit_post($post_id);
				}
				else {
					wp_die(__('Unauthorized Access.','clone-page-post-page'));
				}
			}
			
			else {
				wp_die(__('Security check issue, Please try again.','clone-page-post-page'));
			} 
          
        }


		 /**
         * Clone edit post
         */
        public function clone_page_post_edit_post($post_id,$post_status_update='')
        {
            global $wpdb;
            $opt = get_option('clone_page_post_options');
            $suffix = isset($opt['clone_page_post_suffix']) && !empty($opt['clone_page_post_suffix']) ? ' -- '.esc_attr($opt['clone_page_post_suffix']) : '';
                if($post_status_update == ''){
                    $post_status = !empty($opt['clone_page_post_status']) ? esc_attr($opt['clone_page_post_status']) : 'draft';
                }else{
                    $post_status =  $post_status_update;
                }
            $redirectit = !empty($opt['clone_page_post_redirect']) ? esc_attr($opt['clone_page_post_redirect']) : 'to_list';
            if (!(isset($_GET['post']) || isset($_POST['post']) || (isset($_REQUEST['action']) && 'clone_page_post_as_draft' == sanitize_text_field($_REQUEST['action'])))) {
                wp_die(__('No post to clone has been supplied!','clone-page-post-page'));
            }
            $returnpage = '';            
            /*
            * and all the original post data then
            */
            $post = get_post($post_id);
            /*
            * if you don't want current user to be the new post author,
            * then change next couple of lines to this: $new_post_author = $post->post_author;
            */
            $current_user = wp_get_current_user();
            $new_post_author = $current_user->ID;
            /*
            * if post data exists, create the post clone
            */
            if (isset($post) && $post != null) {
                /*
                * new post data array
                */
                $args = array(
                     'comment_status' => $post->comment_status,
                     'ping_status' => $post->ping_status,
                     'post_author' => $new_post_author,
                     'post_content' => (isset($opt['clone_page_post_editor']) && $opt['clone_page_post_editor'] == 'gutenberg') ? wp_slash($post->post_content) : $post->post_content,
                     'post_excerpt' => $post->post_excerpt,
                     'post_parent' => $post->post_parent,
                     'post_password' => $post->post_password,
                     'post_status' => $post_status,
                     'post_title' => $post->post_title.$suffix,
                     'post_type' => $post->post_type,
                     'to_ping' => $post->to_ping,
                     'menu_order' => $post->menu_order,
                 );
                /*
                * insert the post by wp_insert_post() function
                */
                $new_post_id = wp_insert_post($args);
	            if(is_wp_error($new_post_id)){
		            wp_die(__($new_post_id->get_error_message(),'clone-page-post-page'));
	            }
               
                /*
                * get all current post terms ad set them to the new post draft
                */
                $taxonomies = array_map('sanitize_text_field',get_object_taxonomies($post->post_type));
                if (!empty($taxonomies) && is_array($taxonomies)):
                 foreach ($taxonomies as $taxonomy) {
                     $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
                     wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
                 }
                endif;
                /*
                * clone all post meta
                */
	            $post_meta = get_post_meta( $post_id );
	            if ( ! empty( $post_meta ) ) {
		            foreach ( $post_meta as $key => $item ) {
			            $meta_value = current( $item );
			            $update = $wpdb->update(
							"{$wpdb->prefix}postmeta",
				            [ 'meta_value' => $meta_value ],
							[ 'post_id' => $new_post_id, 'meta_key' => $key, ]
			            );
						if(!$update || $update < 1){
							$wpdb->insert( "{$wpdb->prefix}postmeta",
								[ 'meta_key' => $key, 'meta_value' => $meta_value, 'post_id' => $new_post_id ]
							);
						}
		            }
	            }
                    
                    if(is_plugin_active( 'elementor/elementor.php' )){
                        $css = Elementor\Core\Files\CSS\Post::create( $new_post_id );
                        $css->update();
                        } 
                /*
                * finally, redirecting to your choice
                */
                if ($post->post_type != 'post'){
                    $returnpage = '?post_type='.$post->post_type;
                }
                if (!empty($redirectit) && $redirectit == 'to_list'){
                    wp_redirect(esc_url_raw(admin_url('edit.php'.$returnpage))); 
                } elseif (!empty($redirectit) && $redirectit == 'to_page'){
                    wp_redirect(esc_url_raw(admin_url('post.php?action=edit&post='.$new_post_id))); 
                } else {
                    wp_redirect(esc_url_raw(admin_url('edit.php'.$returnpage)));
                }
             exit;
            } 
            else {
                wp_die(__('Error! Post creation failed, could not find original post: ','clone-page-post-page').$post_id);
            }
          }

		/*
         * Add the clone link to action list for post_row_actions
         */
        public function clone_page_post_link($actions, $post)
        {
            $opt = get_option('clone_page_post_options');
            $post_status = !empty($opt['clone_page_post_status']) ? esc_attr($opt['clone_page_post_status']) : 'draft';
            if (current_user_can('edit_posts')) {
                $actions['clone'] = isset($post) ? '<a href="admin.php?action=clone_page_post_as_draft&amp;post='.intval($post->ID).'&amp;nonce='.wp_create_nonce( 'cpop-clone-page-post-page-'.intval($post->ID) ).'" title="'.__('Clone this as ', 'clone-page-post-page').$post_status.'" rel="permalink">'.__('Clone', 'clone-page-post-page').'</a>' : '';
            }
            
            return $actions;
        }

		/*
          * Add the Clone link to edit screen - classic editor
          */
		  public function clone_page_post_custom_button_classic()
		  {
			  global $post;
			  $opt = get_option('clone_page_post_options');
			  $post_status = !empty($opt['clone_page_post_status']) ? esc_attr($opt['clone_page_post_status']) : 'draft';
			  $html = '<div id="major-publishing-actions">';
			  $html .= '<div id="export-action">';
			  $html .= isset($post) ? '<a href="admin.php?action=clone_page_post_as_draft&amp;post='.intval($post->ID).'&amp;nonce='.wp_create_nonce( 'cpop-clone-page-post-page-'.$post->ID ).'" title="'.__('Clone this as ','clone-page-post-page').$post_status.'" rel="permalink">'.__('Clone', 'clone-page-post-page').'</a>' :'';
			  $html .= '</div>';
			  $html .= '</div>';
			  $content = apply_filters('wpautop', $html);
			  $content = str_replace(']]>', ']]>', $content);
			  echo wp_kses_post($content);
		  }

		   /*
         * Add the clone link to edit screen - gutenberg
         */
        public function clone_page_post_custom_button_guten()
        {
            global $post;
            if ($post) {
                $opt = get_option('clone_page_post_options');
                $post_status = !empty($opt['clone_page_post_status']) ? esc_attr($opt['clone_page_post_status']) : 'draft';
                if (isset($opt['clone_page_post_editor']) && ($opt['clone_page_post_editor'] == 'gutenberg' || $opt['clone_page_post_editor'] == 'all')) {
                    wp_enqueue_style('cpop-main-style', plugin_dir_url(__FILE__) . 'css/cpop_gutenberg.css');
                    wp_register_script( "clone_page_post_script", plugins_url( '/js/editor-script.js', __FILE__ ), array( 'wp-edit-post', 'wp-plugins', 'wp-i18n', 'wp-element' ), CLONE_PAGE_POST_PLUGIN_VERSION);
                    wp_localize_script( 'clone_page_post_script', 'cpop_params', array(
                        'cpop_post_id' => intval($post->ID),
                        'cpopnonce' => wp_create_nonce( 'cpop-clone-page-post-page-'.intval($post->ID)),
                        'cpop_post_text' => __("Clone",'clone-page-post-page'),
                        'cpop_post_title'=> __('Clone this as ','clone-page-post-page').$post_status,
                        'cpop_clone_link' => "admin.php?action=clone_page_post_as_draft"
                        )
                    );        
                    wp_enqueue_script( 'cpop_clone_post_script' );
                }
            }
        }

		 /*
        * Admin Bar clone This Link
        */
        public function clone_page_post_admin_bar_link()
        {
            global $wp_admin_bar, $post;
            $opt = get_option('clone_page_post_options');
            $post_status = !empty($opt['clone_page_post_status']) ? esc_attr($opt['clone_page_post_status']) : 'draft';
            $current_object = get_queried_object();
            if (empty($current_object)) {
                return;
            }
            if (!empty($current_object->post_type)
            && ($post_type_object = get_post_type_object($current_object->post_type))
            && ($post_type_object->show_ui || $current_object->post_type == 'attachment')) {
                $wp_admin_bar->add_menu(array(
                'parent' => 'edit',
                'id' => 'clone_page_post_this',
                'title' => __('Clone This as ', 'clone-page-post-page').$post_status,
                'href' => isset($post) ? esc_url_raw(admin_url().'admin.php?action=clone_page_post_as_draft&amp;post='.intval($post->ID).'&amp;nonce='.wp_create_nonce( 'dt-clone-page-post-page-'.intval($post->ID))) :'',
                ));
            }
        }

		/*
         * Redirect function
        */
        public static function cpop_redirect($url)
        {        
            $web_url = esc_url_raw($url);
            wp_register_script( 'cpop-setting-redirect', '');
            wp_enqueue_script( 'cpop-setting-redirect' );
            wp_add_inline_script(
            	'cpop-setting-redirect',
            	' window.location.href="'.$web_url.'" ;'
        	);
        }

		/*
         Close Help
        */
        public function mk_cpop_close_cpop_help() {
            $nonce = sanitize_text_field($_REQUEST['nonce']);
            if (wp_verify_nonce($nonce, 'nc_help_desk')) {
            if (false === ($mk_fm_close_fm_help_c = get_option('mk_fm_close_fm_help_c'))) {
                $set = update_option('mk_fm_close_fm_help_c', 'done');
                if ($set) {
                    echo esc_html('ok');
                } else {
                    echo esc_html('oh');
                }
            } else {
                echo esc_html('ac');
            }
        }else {
            echo esc_html('ac');
        }
            die;
        }

		/*
        Custom Assets
        */
        public function custom_assets() {
            wp_enqueue_style( 'clone-page-post-page', plugins_url( '/css/clone_page_post.css', __FILE__ ) );
            wp_enqueue_script( 'clone-page-post-page', plugins_url( '/js/clone_page_post.js', __FILE__ ) );
            wp_localize_script( 'clone-page-post-page', 'dt_params', array(
                'ajax_url' => admin_url( 'admin-ajax.php'),
                'nonce' => wp_create_nonce( 'nc_help_desk' ))
            );
        }

}
new clone_page_post_class();
endif;