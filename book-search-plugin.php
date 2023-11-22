<?php
/*
Plugin Name: Library Book Search Plugin
Description: Custom WordPress plugin for library book search.
Version: 1.0
Author: Mehsi Akbar
*/

class Library_Book_Search_Plugin {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_custom_fields'));
        add_action('save_post', array($this, 'save_custom_fields'));
        add_shortcode('library_book_search', array($this, 'render_search_form'));
        add_action('wp_ajax_library_book_search', array($this, 'ajax_search'));
        add_action('wp_ajax_nopriv_library_book_search', array($this, 'ajax_search'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_jquery'));

    }
    function enqueue_jquery() {
    wp_enqueue_script('jquery');
    
    wp_enqueue_style('custom-styles', plugins_url('style.css', __FILE__), array(), '1.0.0', 'all');
    wp_enqueue_script('custom-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    wp_localize_script('custom-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));



    if (!wp_script_is('jquery-ui', 'enqueued')) {
        wp_enqueue_script('jquery-ui', 'https://code.jquery.com/ui/1.12.1/jquery-ui.js', array('jquery'), '1.12.1', true);

        wp_enqueue_style('jquery-ui-theme', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    }
}


    public function register_post_type() {
        register_post_type('book', array(
            'labels' => array(
                'name' => __('Books'),
                'singular_name' => __('Book'),
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes'),
        ));
    }

    public function register_taxonomies() {
        register_taxonomy('author', 'book', array(
            'label' => __('Authors'),
            'hierarchical' => true,
        ));

        register_taxonomy('publisher', 'book', array(
            'label' => __('Publishers'),
            'hierarchical' => true,
        ));
    }

    public function add_custom_fields() {
        add_meta_box('book_custom_fields', 'Book Custom Fields', array($this, 'render_custom_fields'), 'book', 'normal', 'high');
    }

    public function render_custom_fields($post) {
        $price = get_post_meta($post->ID, '_book_price', true);
        $rating = get_post_meta($post->ID, '_book_rating', true);

        ?>
        <label for="book_price">Price:</label>
        <input type="text" name="book_price" id="book_price" value="<?php echo esc_attr($price); ?>" />

        <label for="book_rating">Rating (1 to 5):</label>
        <input type="number" name="book_rating" id="book_rating" min="1" max="5" value="<?php echo esc_attr($rating); ?>" />
        <?php
    }

    public function save_custom_fields($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        $price = isset($_POST['book_price']) ? sanitize_text_field($_POST['book_price']) : '';
        $rating = isset($_POST['book_rating']) ? intval($_POST['book_rating']) : 0;

        update_post_meta($post_id, '_book_price', $price);
        update_post_meta($post_id, '_book_rating', $rating);
    }
    


public function render_search_form() {
    ob_start(); ?>
  <form id="book-search-form" action="#" method="post">
    <div class="form-row">
        <div class="form-group">
            <label for="book_name">Book Name:</label>
            <input type="text" name="book_name" id="book_name" />
        </div>

        <div class="form-group">
            <label for="author">Author:</label>
            <?php wp_dropdown_categories(array('taxonomy' => 'author', 'name' => 'author', 'show_option_all' => 'All Authors', 'hide_empty' => 0, 'hierarchical' => true)); ?>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="publisher">Publisher:</label>
            <?php wp_dropdown_categories(array('taxonomy' => 'publisher', 'name' => 'publisher', 'show_option_all' => 'All Publishers', 'hide_empty' => 0, 'hierarchical' => true)); ?>
        </div>

        <div class="form-group">
            <label for="book_rating">Rating:</label>
            <input type="number" name="book_rating" id="book_rating" min="1" max="5" />
        </div>
    </div>

   <div id="price-slider-container">
    <label for="price_range">Price Range:</label>
    <div id="price-slider"></div>
    <div id="price-range-display">0 - 100</div>
    <input type="hidden" name="price_range" id="price_range" value="" />
</div>

    <div class="form-row">
        <div class="form-group">
            <input type="submit" value="Search" />
        </div>
    </div>
</form>




    <div id="book-search-results"></div>
    <script>
       
    </script>
    <?php
    return ob_get_clean();
}


    public function ajax_search() {
        $args = array(
            'post_type' => 'book',
            'posts_per_page' => -1,
            's' => isset($_POST['book_name']) ? sanitize_text_field($_POST['book_name']) : '',
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'author',
                    'field' => 'id',
                    'terms' => isset($_POST['author']) ? intval($_POST['author']) : '',
                ),
                array(
                    'taxonomy' => 'publisher',
                    'field' => 'id',
                    'terms' => isset($_POST['publisher']) ? intval($_POST['publisher']) : '',
                ),
            ),
            'meta_query' => array(
                'relation' => 'AND',
                array(
                        'key' => '_book_price',
                        'value' => isset($_POST['price_range']) ? array_map('intval', explode('-', sanitize_text_field($_POST['price_range']))) : '',
                        'type' => 'NUMERIC',
                        'compare' => 'BETWEEN',
                    ),
                array(
                        'key' => '_book_rating',
                        'value' => isset($_POST['book_rating']) ? intval($_POST['book_rating']) : '',
                        'type' => 'NUMERIC',
                        'compare' => '>=',
                    ),
            ),
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) :
    ?>
    <table>
        <thead>
            <tr>
                <th>Sr.No</th>
                <th>Book Name</th>
                <th>Price</th>
                <th>Author</th>
                <th>Publisher</th>
                <th>Rating</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $counter = 1;
            while ($query->have_posts()) : $query->the_post();
                $author_id = get_the_terms(get_the_ID(), 'author');
                $publisher_id = get_the_terms(get_the_ID(), 'publisher');
                $author_link = $author_id ? get_term_link($author_id[0]) : '#';
                $publisher_link = $publisher_id ? get_term_link($publisher_id[0]) : '#';
                $rating = get_post_meta(get_the_ID(), '_book_rating', true);
                $stars = str_repeat('&#9733;', $rating); 

                echo '<tr>';
                echo '<td>' . $counter . '</td>';
                echo '<td><a href="' . get_permalink() . '">' . get_the_title() . '</a></td>';
                echo '<td>' . get_post_meta(get_the_ID(), '_book_price', true) . '</td>';
                echo '<td><a href="' . esc_url($author_link) . '">' . get_the_term_list(get_the_ID(), 'author', '', ', ', '') . '</a></td>';
                echo '<td><a href="' . esc_url($publisher_link) . '">' . get_the_term_list(get_the_ID(), 'publisher', '', ', ', '') . '</a></td>';
                echo '<td>' . $stars . '</td>';
                echo '</tr>';

                $counter++;
            endwhile;
            wp_reset_postdata();
            ?>
        </tbody>
    </table>
    <?php
else :
    echo '<p>No results found</p>';
endif;

die();

    }

    public function add_admin_menu() {
        add_menu_page('Library Book Search Plugin', 'Book Search', 'manage_options', 'library_book_search_menu', array($this, 'admin_page'));
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h2>Library Book Search Plugin</h2>
            <p>Use the following shortcode to display the book search form:</p>
            <code>[library_book_search]</code>
        </div>
        <?php
    }
}

new Library_Book_Search_Plugin();
?>
