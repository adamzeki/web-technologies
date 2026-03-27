<?php
/**
 * Plugin Name: Announcement Insert
 * Description: Displays random HTML announcements before post content
 * Version: 1.0
 * Author: Adam Żekieć
 */

function ai_register_menu_page() {
    add_options_page(
        'Announcement Settings',
        'Announcements',
        'manage_options',
        'announcement-settings',
        'ai_render_admin_page'
    );
}
add_action('admin_menu', 'ai_register_menu_page');

function ai_render_admin_page() {
    //deleting
    if (isset($_POST['ai_delete_index'])) {
        $index_to_remove = $_POST['ai_delete_index'];
        $current_list = get_option('ai_announcements_list', []);
        
        if (isset($current_list[$index_to_remove])) {
            unset($current_list[$index_to_remove]);
            $current_list = array_values($current_list); // updating array keys after deletimg
            update_option('ai_announcements_list', $current_list);
            echo '<div class="updated"><p>Announcement deleted/</p></div>';
        }
    }

    //adding
    if (isset($_POST['ai_new_announcement']) && !empty($_POST['ai_new_announcement'])) {
        $new_item = $_POST['ai_new_announcement'];
        $current_list = get_option('ai_announcements_list', []);
        $current_list[] = $new_item;
        update_option('ai_announcements_list', $current_list);
        echo '<div class="updated"><p>Announcement added</p></div>';
    }

    //display
    $list = get_option('ai_announcements_list', []);
    ?>
    <div class="wrap">
        <h1>Announcement Settings</h1>
        
        <form method="post" action="">
            <p><strong>Enter content for new announcement (HTML):</strong></p>
            <textarea name="ai_new_announcement" rows="5" cols="50" class="large-text"></textarea>
            <?php submit_button('Add Announcement');?>
        </form>

        <hr>

        <h2>Current Announcements</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Content (HTML)</th>
                    <th style="width: 100px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list)) : ?>
                    <tr><td colspan="2">No announcements. Add the first one above</td></tr>
                <?php else : ?>
                    <?php foreach ($list as $index => $item) : ?>
                        <tr>
                            <td><code><?php echo esc_html($item); ?></code></td> <!-- displaying raw HTML -->
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="ai_delete_index" value="<?php echo $index; ?>">
                                    <input type="submit" class="button button-link-delete" value="Delete" style="color:red;">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function ai_display_announcement($content) {
    $list = get_option('ai_announcements_list', []);

    if (empty($list)) {
        return $content;
    }

    $random_key = array_rand($list);
    $announcement = $list[$random_key];

    return $announcement . $content;
}
add_filter('the_content', 'ai_display_announcement');

function ai_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'ai_dashboard_widget',
        'Saved Announcements',
        'ai_render_dashboard_widget'
    );
}
add_action('wp_dashboard_setup', 'ai_add_dashboard_widget');

function ai_render_dashboard_widget() {
    $list = get_option('ai_announcements_list', []);

    if (empty($list)) {
        echo '<p>No announcements saved</p>';
    } else {
        echo '<p>Your current announcements:</p>';
        echo '<ul style="list-style: disc; margin-left: 20px;">';
        foreach ($list as $announcement) {
            echo '<li>' . esc_html($announcement) . '</li>'; 
        }
        echo '</ul>';
    }
    
    $url = admin_url('options-general.php?page=announcement-settings');
    echo '<hr><p><a href="' . $url . '" class="button">Manage Announcements</a></p>';
}
?>