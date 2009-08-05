<?php
/*
Plugin Name: PostAds
Plugin URI: http://bobrik.name/
Description: Add and manage ads of your posts
Version: 0.4
Author: Ivan Babrou <ibobrik@gmail.com>
Author URI: http://bobrik.name/

Copyright 2008 Ivan Babroŭ (email : ibobrik@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the license, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; see the file COPYING.  If not, write to
the Free Software Foundation, Inc., 59 Temple Place - Suite 330,
Boston, MA 02111-1307, USA.
*/

function postads_install()
{
	update_option('postads_css_source', 'default');
	update_option('postads_own_css', '');
	update_option('postads_rss', 'disabled');
	update_option('postads_single', 'enabled');
	update_option('postads_index', 'disabled');
	update_option('postads_hide_registered', 'enabled');
}

function postads_post_options()
{
	global $post;
	echo '<div class="postbox"><h3>'.__('Post Ads', 'postads').
		'</h3><div class="inside"><p>'.
		'<textarea name="postads_text" style="width:100%">'.get_post_meta($post->ID, 'postads_text', true).'</textarea>'.
		'</p></div></div>';
}

function postads_store_post_options($post_id)
{
	if (isset($_POST['postads_text'])) // change only if showed
		update_post_meta($post_id, 'postads_text', $_POST['postads_text']);
}

function postads_add_content($text)
{
	global $post;
	$ads = get_post_meta($post->ID, 'postads_text', true);
	return $text.(!postads_need_to_show() ? '' : "\n"."<div class='postads'>\n\n".$ads."\n\n</div>");
}

function postads_need_to_show()
{
	global $post;
	$ads = get_post_meta($post->ID, 'postads_text', true);
	if (empty($ads))
		return false;
	if (is_feed())
		if (get_option('postads_rss') != 'enabled')
			return false;
		else
			return true;
	if (is_home())
		if (get_option('postads_index')  != 'enabled')
			return false;
		else
			return true;
	if (is_single())
		if (get_option('postads_single') != 'enabled')
			return false;
		else
			return true;
	if (get_option('postads_hide_registered') == 'enabled')
	{
		global $user_ID;
		get_currentuserinfo();
		if ($user_ID)
			return false;
	}
	return true;
}

function postads_styles()
{
	if (!postads_need_to_show())
		return;
	if (get_option('postads_css_source') == 'default')
	{
?>
<style type="text/css"><!--
.postads {display:block; font-size:90%; color:#666666; border:1px solid #ddd; padding:4px; background-image:url(<?php echo get_option('siteurl').'/wp-content/plugins/postads/'; ?>ads.png);background-position:bottom right; background-repeat:no-repeat;}
.postads p {margin:4px;}
--></style>
<?php
	} elseif (get_option('postads_css_source') == 'user')
	{
?>
<style type="text/css"><!--
<?php echo get_option('postads_own_css')."\n"; ?>
--></style>
<?php
	}
}

function postads_options_page()
{
	if (function_exists('add_options_page'))
	{
		add_options_page('Post Ads Options', 'Post Ads', 8, __FILE__, 'postads_config');
	}
}

function postads_config() {
	if (isset($_POST['stage']) && $_POST['stage'] == 'process')
	{
		if (function_exists('current_user_can') && !current_user_can('manage_options'))
			die(__('Cheatin&#8217; uh?'));
		update_option('postads_css_source', $_POST['postads_css_source']);
		update_option('postads_own_css', $_POST['postads_own_css']);
		update_option('postads_rss', $_POST['postads_rss'] == 'on' ? 'enabled' : 'disabled');
		update_option('postads_single', $_POST['postads_single'] == 'on' ? 'enabled' : 'disabled');
		update_option('postads_index', $_POST['postads_index'] == 'on' ? 'enabled' : 'disabled');
		update_option('postads_hide_registered', $_POST['postads_hide_registered'] == 'on' ? 'enabled' : 'disabled');
	}

?>
<div class="wrap">
	<h2>Post Ads</h2>
	<form name="form1" method="post" action="">
	<input type="hidden" name="stage" value="process" />
	<table width="100%" cellspacing="2" cellpadding="5" class="form-table">
		<tr valign="baseline">
			<th scope="row">CSS source</th>
			<td>
				<select name="postads_css_source">
					<option value="default"<?php echo (get_option('postads_css_source') == 'default' ? ' selected="selected"':''); ?>>Default</option>
					<option value="user"<?php echo (get_option('postads_css_source') == 'user' ? ' selected="selected"':''); ?>>Provided below</option>
					<option value="system"<?php echo (get_option('postads_css_source') == 'system' ? ' selected="selected"':''); ?>>System (theme)</option>
				</select> (If «System», use <strong>postads</strong> css class in your theme CSS. «Provided below» is optimal)
			</td>
		</tr>
		<tr valign="baseline">
			<th scope="row">User CSS</th>
			<td>
				<textarea name="postads_own_css" rows="6" cols="80"><?php echo get_option('postads_own_css'); ?></textarea>
			</td>
		</tr>
	</table>
	<input type="checkbox" id="postads_index" name="postads_index"<?php echo (get_option('postads_index') == 'enabled') ? ' checked="checked"' : '';?> /> <label for="postads_index">Show ads on main page</label><br/>
	<input type="checkbox" id="postads_single" name="postads_single"<?php echo (get_option('postads_single') == 'enabled') ? ' checked="checked"' : '';?> /> <label for="postads_single">Show on post page</label><br/>
	<input type="checkbox" id="postads_rss" name="postads_rss"<?php echo (get_option('postads_rss') == 'enabled') ? ' checked="checked"' : '';?> /> <label for="postads_rss">Show in RSS</label><br/>
	<input type="checkbox" id="postads_hide_registered" name="postads_hide_registered"<?php echo (get_option('postads_hide_registered') == 'enabled') ? ' checked="checked"' : '';?> /> <label for="postads_hide_registered">Hide for registered</label><br/>
	<p class="submit">
		<input class="button-primary" type="submit" name="Submit" value="<?php _e('Save Changes', 'wpbb-sync'); ?>" />
	</p>
	</form>
</div>
<?php
}

register_activation_hook(__FILE__, 'postads_install');

add_action('edit_form_advanced', 'postads_post_options');
add_action('draft_post', 'postads_store_post_options');
add_action('publish_post', 'postads_store_post_options');
add_action('save_post', 'postads_store_post_options');

add_action('admin_menu', 'postads_options_page');

add_filter('wp_head', 'postads_styles');
add_filter('the_content', 'postads_add_content', 5);

?>
