<?php

if ( ! defined( 'ABSPATH' ) ) exit; 
/**
 * Skeleton Plugin Post Type Class
 *
 * All functionality pertaining to post types in Skeleton Plugin.
 *
 * @package WordPress
 * @subpackage Skeleton_Plugin
 * @category Plugin
 * @author Nate
 * @since 1.0.0
 */
class Skeleton_Plugin_Post_Type
{
    /**
     * The post type token.
     * @access public
     * @since  1.0.0
     * @var    string
     */
    public $post_type;
    /**
     * The post type singular label.
     * @access public
     * @since  1.0.0
     * @var    string
     */
    public $singular;
    /**
     * The post type plural label.
     * @access public
     * @since  1.0.0
     * @var    string
     */
    public $plural;
    /**
     * The post type args.
     * @access public
     * @since  1.0.0
     * @var    array
     */
    public $args;
    /**
     * The taxonomies for this post type.
     * @access public
     * @since  1.0.0
     * @var    array
     */
    public $taxonomies;

    /**
     * Constructor function.
     * @access public
     * @since 1.0.0
     */
    public function __construct($post_type = 'thing', $singular = '', $plural = '', $args = array(), $taxonomies = array())
    {
        $this->post_type = $post_type;
        $this->singular = $singular;
        $this->plural = $plural;
        $this->args = $args;
        $this->taxonomies = $taxonomies;
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));
        if (is_admin()) {
            global $pagenow;
            add_action('admin_menu', array($this, 'meta_box_setup'), 20);
            add_action('save_post', array($this, 'meta_box_save'));
            add_filter('enter_title_here', array($this, 'enter_title_here'));
            add_filter('post_updated_messages', array($this, 'updated_messages'));
            if ($pagenow == 'edit.php' && isset($_GET['post_type']) && esc_attr($_GET['post_type']) == $this->post_type) {
                add_filter('manage_edit-' . $this->post_type . '_columns', array($this, 'register_custom_column_headings'), 10, 1);
                add_action('manage_posts_custom_column', array($this, 'register_custom_columns'), 10, 2);
            }
        }
        add_action('after_setup_theme', array($this, 'ensure_post_thumbnails_support'));
        add_action('after_theme_setup', array($this, 'register_image_sizes'));
    }

    /**
     * Register the post type.
     * @access public
     * @return void
     */
    public function register_post_type()
    {
        $labels = array(
            'name' => sprintf(_x('%s', 'post type general name', 'skeleton-plugin'), $this->plural),
            'singular_name' => sprintf(_x('%s', 'post type singular name', 'skeleton-plugin'), $this->singular),
            'add_new' => _x('Add New', $this->post_type, 'skeleton-plugin'),
            'add_new_item' => sprintf(__('Add New %s', 'skeleton-plugin'), $this->singular),
            'edit_item' => sprintf(__('Edit %s', 'skeleton-plugin'), $this->singular),
            'new_item' => sprintf(__('New %s', 'skeleton-plugin'), $this->singular),
            'all_items' => sprintf(__('All %s', 'skeleton-plugin'), $this->plural),
            'view_item' => sprintf(__('View %s', 'skeleton-plugin'), $this->singular),
            'search_items' => sprintf(__('Search %a', 'skeleton-plugin'), $this->plural),
            'not_found' => sprintf(__('No %s Found', 'skeleton-plugin'), $this->plural),
            'not_found_in_trash' => sprintf(__('No %s Found In Trash', 'skeleton-plugin'), $this->plural),
            'parent_item_colon' => '',
            'menu_name' => $this->plural,
        );
        $single_slug = apply_filters('skeleton-plugin_single_slug', _x(sanitize_title_with_dashes($this->singular), 'single post url slug', 'skeleton-plugin'));
        $archive_slug = apply_filters('skeleton-plugin_archive_slug', _x(sanitize_title_with_dashes($this->plural), 'post archive url slug', 'skeleton-plugin'));
        $defaults = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => $single_slug),
            'capability_type' => 'post',
            'has_archive' => $archive_slug,
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'),
            'menu_position' => 5,
            'menu_icon' => 'dashicons-smiley',
        );
        $args = wp_parse_args($this->args, $defaults);
        register_post_type($this->post_type, $args);
    }

    /**
     * Register the "thing-category" taxonomy.
     * @access public
     * @since  1.3.0
     * @return void
     */
    public function register_taxonomy()
    {
        $this->taxonomies['thing-category'] = new Skeleton_Plugin_Taxonomy(); 
        $this->taxonomies['thing-category']->register();
    } 

    /**
     * Add custom columns for the "manage" screen of this post type.
     * @access public
     * @param string $column_name
     * @param int $id
     * @since  1.0.0
     * @return void
     */
    public function register_custom_columns($column_name, $id)
    {
        global $post;
        switch ($column_name) {
            case 'image':
                echo $this->get_image($id, 40);
                break;
            default:
                break;
        }
    }

    /**
     * Add custom column headings for the "manage" screen of this post type.
     * @access public
     * @param array $defaults
     * @since  1.0.0
     * @return void
     */
    public function register_custom_column_headings($defaults)
    {
        $new_columns = array('image' => __('Image', 'skeleton-plugin'));
        $last_item = array();
        if (isset($defaults['date'])) {
            unset($defaults['date']);
        }
        if (count($defaults) > 2) {
            $last_item = array_slice($defaults, -1);
            array_pop($defaults);
        }
        $defaults = array_merge($defaults, $new_columns);
        if (is_array($last_item) && 0 < count($last_item)) {
            foreach ($last_item as $k => $v) {
                $defaults[$k] = $v;
                break;
            }
        }
        return $defaults;
    } 

    /**
     * Update messages for the post type admin.
     * @since  1.0.0
     * @param  array $messages Array of messages for all post types.
     * @return array           Modified array.
     */
    public function updated_messages($messages)
    {
        global $post, $post_ID;
        $messages[$this->post_type] = array(
            0 => '',
            1 => sprintf(__('%3$s updated. %sView %4$s%s', 'skeleton-plugin'), '<a href="' . esc_url(get_permalink($post_ID)) . '">', '</a>', $this->singular, strtolower($this->singular)),
            2 => __('Custom field updated.', 'skeleton-plugin'),
            3 => __('Custom field deleted.', 'skeleton-plugin'),
            4 => sprintf(__('%s updated.', 'skeleton-plugin'), $this->singular),
            /* translators: %s: date and time of the revision */
            5 => isset($_GET['revision']) ? sprintf(__('%s restored to revision from %s', 'skeleton-plugin'), $this->singular, wp_post_revision_title((int)$_GET['revision'], false)) : false,
            6 => sprintf(__('%1$s published. %3$sView %2$s%4$s', 'skeleton-plugin'), $this->singular, strtolower($this->singular), '<a href="' . esc_url(get_permalink($post_ID)) . '">', '</a>'),
            7 => sprintf(__('%s saved.', 'skeleton-plugin'), $this->singular),
            8 => sprintf(__('%s submitted. %sPreview %s%s', 'skeleton-plugin'), $this->singular, strtolower($this->singular), '<a target="_blank" href="' . esc_url(add_query_arg('preview', 'true', get_permalink($post_ID))) . '">', '</a>'),
            9 => sprintf(__('%s scheduled for: %1$s. %2$sPreview %s%3$s', 'skeleton-plugin'), $this->singular, strtolower($this->singular),

                '<strong>' . date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)) . '</strong>', '<a target="_blank" href="' . esc_url(get_permalink($post_ID)) . '">', '</a>'),
            10 => sprintf(__('%s draft updated. %sPreview %s%s', 'skeleton-plugin'), $this->singular, strtolower($this->singular), '<a target="_blank" href="' . esc_url(add_query_arg('preview', 'true', get_permalink($post_ID))) . '">', '</a>'),
        );
        return $messages;
    }

    /**
     * Setup the meta box.
     * @access public
     * @since  1.0.0
     * @return void
     */
    public function meta_box_setup()
    {
        add_meta_box($this->post_type . '-data', __('Thing Details', 'skeleton-plugin'), array($this, 'meta_box_content'), $this->post_type, 'normal', 'high');
    }
    

    /**
     * The contents of our meta box.
     * @access public
     * @since  1.0.0
     * @return void
     */
    public function meta_box_content()
    {
        global $post_id;
        $fields = get_post_custom($post_id);
        $field_data = $this->get_custom_fields_settings();
        $html = '';
        $html .= '<input type="hidden" name="skeleton-plugin_' . $this->post_type . '_noonce" id="skeleton-plugin_' . $this->post_type . '_noonce" value="' . wp_create_nonce(plugin_basename(dirname(Skeleton_Plugin()->plugin_path))) . '" />';
        if (0 < count($field_data)) {
            $html .= '<table class="form-table">' . "\n";
            $html .= '<tbody>' . "\n";
            foreach ($field_data as $k => $v) {
                $data = $v['default'];
                if (isset($fields['_' . $k]) && isset($fields['_' . $k][0])) {
                    $data = $fields['_' . $k][0];
                }
                $html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr($k) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr($k) . '" type="text" id="' . esc_attr($k) . '" class="regular-text" value="' . esc_attr($data) . '" />' . "\n";
                $html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
                $html .= '</td></tr>' . "\n";
            }
            $html .= '</tbody>' . "\n";
            $html .= '</table>' . "\n";
        }
        echo $html;
    } 

    /**
     * Save meta box fields.
     * @access public
     * @since  1.0.0
     * @param int $post_id
     * @return int $post_id
     */
    public function meta_box_save($post_id)
    {
        global $post, $messages;

        if (get_post_type() != $this->post_type) {
            return $post_id;
        }
        if (!isset($_POST['skeleton-plugin_' . $this->post_type . '_noonce']) || !wp_verify_nonce($_POST['skeleton-plugin_' . $this->post_type . '_noonce'], plugin_basename(dirname(Skeleton_Plugin()->plugin_path)))) {
            return $post_id;
        }
        if (isset($_POST['post_type']) && 'page' == esc_attr($_POST['post_type'])) {
            if (!current_user_can('edit_page', $post_id)) {
                return $post_id;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return $post_id;
            }
        }
        $field_data = $this->get_custom_fields_settings();
        $fields = array_keys($field_data);
        foreach ($fields as $f) {
            ${$f} = strip_tags(trim($_POST[$f]));

            if ('url' == $field_data[$f]['type']) {
                ${$f} = esc_url(${$f});
            }
            if (get_post_meta($post_id, '_' . $f) == '') {
                add_post_meta($post_id, '_' . $f, ${$f}, true);
            } elseif (${$f} != get_post_meta($post_id, '_' . $f, true)) {
                update_post_meta($post_id, '_' . $f, ${$f});
            } elseif (${$f} == '') {
                delete_post_meta($post_id, '_' . $f, get_post_meta($post_id, '_' . $f, true));
            }
        }
    }

    /**
     * Customise the "Enter title here" text.
     * @access public
     * @since  1.0.0
     * @param string $title
     * @return void
     */
    public function enter_title_here($title)
    {
        if (get_post_type() == $this->post_type) {
            $title = __('Enter the thing title here', 'skeleton-plugin');
        }
        return $title;
    }

    /**
     * Get the settings for the custom fields.
     * @access public
     * @since  1.0.0
     * @return array
     */
    public function get_custom_fields_settings()
    {
        $fields = array();
        $fields['url'] = array(
            'name' => __('URL', 'skeleton-plugin'),
            'description' => __('Enter a URL that applies to this thing (for example: http://domain.com/).', 'skeleton-plugin'),
            'type' => 'url',
            'default' => '',
            'section' => 'info'
        );
        return apply_filters('skeleton-plugin_custom_fields_settings', $fields);
    }
    

    /**
     * Register image sizes.
     * @access public
     * @since  1.0.0
     */
    public function register_image_sizes()
    {
        if (function_exists('add_image_size')) {
            add_image_size($this->post_type . '-thumbnail', 150, 9999); 
        }
    }

    /**
     * Get the image for the given ID.
     * @param  int $id Post ID.
     * @param  mixed $size Image dimension. (default: "thing-thumbnail")
     * @since  1.0.0
     * @return string        <img> tag.
     */
    protected function get_image($id, $size = 'thing-thumbnail')
    {
        $response = '';
        if (has_post_thumbnail($id)) {

            if ((is_int($size) || (0 < intval($size))) && !is_array($size)) {
                $size = array(intval($size), intval($size));
            } elseif (!is_string($size) && !is_array($size)) {
                $size = array(150, 9999);
            }
            $response = get_the_post_thumbnail(intval($id), $size);
        }
        return $response;
    }

    /**
     * Run on activation.
     * @access public
     * @since 1.0.0
     */
    public function activation()
    {
        $this->flush_rewrite_rules();
    } 

    /**
     * Flush the rewrite rules
     * @access public
     * @since 1.0.0
     */
    private function flush_rewrite_rules()
    {
        $this->register_post_type();
        flush_rewrite_rules();
    } 

    /**
     * Ensure that "post-thumbnails" support is available for those themes that don't register it.
     * @access public
     * @since  1.0.0
     */
    public function ensure_post_thumbnails_support()
    {
        if (!current_theme_supports('post-thumbnails')) {
            add_theme_support('post-thumbnails');
        }
    }
}