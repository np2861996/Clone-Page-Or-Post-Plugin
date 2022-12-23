<?php if (!defined('ABSPATH') && !current_user_can('manage_options')) {
    exit;
} 
$this->custom_assets();
?>
<div class="wrap clone_page_post_settings">
<?php 
$msg = isset($_GET['msg']) ? intval($_GET['msg']) : '';
if (current_user_can('manage_options') && isset($_POST['submit_clone_page_post']) && wp_verify_nonce(sanitize_text_field($_POST['clone_page_post_nonce_field']), 'clone_page_post_action')):
    _e('<div class="saving-txt"><strong>Saving Please wait...</strong></div>','clone-page-post-page');
        $clone_page_post_options = array(
            "clone_page_post_editor" => sanitize_text_field(htmlentities($_POST["clone_page_post_editor"])),
            "clone_page_post_status" => sanitize_text_field(htmlentities($_POST["clone_page_post_status"])),
            "clone_page_post_redirect" => sanitize_text_field(htmlentities($_POST["clone_page_post_redirect"])),
            "clone_page_post_suffix" => sanitize_text_field(htmlentities($_POST["clone_page_post_suffix"]))
        );
       
        $saveSettings = update_option('clone_page_post_options', $clone_page_post_options);
        if ($saveSettings) {
            clone_page_post_class::cpop_redirect('options-general.php?page=clone_page_post_settings&msg=1');
        } else {
            clone_page_post_class::cpop_redirect('options-general.php?page=clone_page_post_settings&msg=2');
        }
endif;

$opt = get_option('clone_page_post_options');
if (!empty($msg) && $msg == 1):
    _e('<div class="updated settings-error notice is-dismissible" id="setting-error-settings_updated"> 
    <p><strong>Settings saved.</strong></p><button class="notice-dismiss button-custom-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice</span></button></div>','clone-page-post-page');
elseif (!empty($msg) && $msg == 2):
  _e('<div class="error settings-error notice is-dismissible" id="setting-error-settings_updated"> 
  <p><strong>Settings not saved.</strong></p><button class="notice-dismiss button-custom-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice</span></button></div>','clone-page-post-page');
endif;
?> 
<div id="poststuff">
<div id="post-body" class="metabox-holder columns-2">
<div id="post-body-content" style="position: relative;">
<form action="" method="post" name="clone_page_post_form">
<?php  wp_nonce_field('clone_page_post_action', 'clone_page_post_nonce_field'); ?>
<table class="form-table">
<tbody>
<tr>
<th scope="row"><label for="clone_page_post_editor"><?php _e('Choose Editor', 'clone-page-post-page'); ?></label></th>
<td>
    <select id="clone_page_post_editor" name="clone_page_post_editor">
        <option value="all" <?php echo (isset($opt['clone_page_post_editor']) && $opt['clone_page_post_editor'] == 'all') ? "selected = 'selected'" : ''; ?>><?php _e('All Editors', 'clone-page-post-page'); ?></option>
    	<option value="classic" <?php echo (isset($opt['clone_page_post_editor']) && $opt['clone_page_post_editor'] == 'classic') ? "selected = 'selected'" : ''; ?>><?php _e('Classic Editor', 'clone-page-post-page'); ?></option>
    	<option value="gutenberg" <?php echo (isset($opt['clone_page_post_editor']) && $opt['clone_page_post_editor'] == 'gutenberg') ? "selected = 'selected'" : ''; ?>><?php _e('Gutenberg Editor', 'clone-page-post-page'); ?></option>
        </select>
    <p><?php _e('Please select which editor you are using.<strong> Default: </strong> Classic Editor', 'clone-page-post-page'); ?></p>
</td>
</tr>	
<tr>
<th scope="row"><label for="clone_page_post_status"><?php _e('clone page or Post Status', 'clone-page-post-page'); ?></label></th>
<td>
    <select id="clone_page_post_status" name="clone_page_post_status">
    	<option value="draft" <?php echo($opt['clone_page_post_status'] == 'draft') ? "selected = 'selected'" : ''; ?>><?php _e('Draft', 'clone-page-post-page'); ?></option>
    	<option value="publish" <?php echo($opt['clone_page_post_status'] == 'publish') ? "selected = 'selected'" : ''; ?>><?php _e('Publish', 'clone-page-post-page'); ?></option>
    	<option value="private" <?php echo($opt['clone_page_post_status'] == 'private') ? "selected = 'selected'" : ''; ?>><?php _e('Private', 'clone-page-post-page'); ?></option>
    	<option value="pending" <?php echo($opt['clone_page_post_status'] == 'pending') ? "selected = 'selected'" : ''; ?>><?php _e('Pending', 'clone-page-post-page'); ?></option>
        </select>
    <p><?php _e('Please select any post status you want to assign for clone page or post.<strong> Default: </strong> Draft','clone-page-post-page'); ?></p>
</td>
</tr>
<tr>
<th scope="row"><label for="clone_page_post_redirect"><?php _e('Redirect to after click on <strong>Clone This Link</strong>', 'clone-page-post-page'); ?></label></th>
<td><select id="clone_page_post_redirect" name="clone_page_post_redirect">
	<option value="to_list" <?php echo($opt['clone_page_post_redirect'] == 'to_list') ? "selected = 'selected'" : ''; ?>><?php _e('To All Posts List', 'clone-page-post-page'); ?></option>
	<option value="to_page" <?php echo($opt['clone_page_post_redirect'] == 'to_page') ? "selected = 'selected'" : ''; ?>><?php _e('To Clone Edit Screen', 'clone-page-post-page'); ?></option>
    </select>
    <p><?php  _e('Please select any post redirection, redirect you to selected after click on clone this link.<strong> Default: </strong>To All Posts List','clone-page-post-page'); ?></p>
</td>
</tr>
<tr>
<th scope="row"><label for="clone_page_post_suffix"><?php _e('Clone Page or Post Suffix', 'clone-page-post-page'); ?></label></th>
<td>
 <input type="text" class="regular-text" value="<?php echo !empty($opt['clone_page_post_suffix']) ? esc_attr($opt['clone_page_post_suffix']) : ''; ?>" id="clone_page_post_suffix" name="clone_page_post_suffix">
    <p><?php _e('Add a suffix for duplicate or clone post as Copy, Clone etc. It will show after title.', 'clone-page-post-page'); ?></p>
</td>
</tr>
</tbody></table>
<p class="submit"><input type="submit" value="<?php _e('Save Changes','clone-page-post-page'); ?>" class="button button-primary" id="submit" name="submit_clone_page_post"></p>
</form>
</div>
</div>
</div>
</div>
