<?php
/*
Plugin Name: PostAds
Plugin URI: http://bobrik.name/code/wordpress/postads/
Description: Add and manage ads of your posts
Version: 0.6
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
	$defaults = array(
		'postads_css_source' => 'default',
		'postads_own_css' => '',
		'postads_rss' => 'disabled',
		'postads_single' => 'enabled',
		'postads_index' => 'disabled',
		'postads_hide_registered' => 'enabled',
		'postads_max_per_post' => 5
	);
	foreach ($defaults as $option => $value)
	if (!get_option($option))
		update_option($option, $value);
}

function postads_post_options()
{
	global $post;
	$display_options = array(
		'postads_index' => 'Show ads on main page',
		'postads_single' => 'Show on post page',
		'postads_rss' => 'Show in RSS',
		'postads_hide_registered' => 'Hide for registered'
	);
	echo '<div class="postbox"><h3>Post Ads</h3>'.
		'<div class="inside">'.
		'<p><textarea name="postads_text" style="width:100%">'.get_post_meta($post->ID, 'postads_text', true).'</textarea></p>'.
		'<h5>Display settings</h5>'.
		'<p><input type="radio" name="postads_display_type" id="postads_display_default" value="default"'.(get_post_meta($post->ID, 'postads_display_type', true) == 'custom' ? '':' checked="checked"').' onChange="document.getElementById(\'postads_custom\').style.display=\'none\'" /> <label for="postads_display_default">Default settings</label><br/>'.
		'<input type="radio" name="postads_display_type" id="postads_display_custom" value="custom" onChange="document.getElementById(\'postads_custom\').style.display=\'\'"'.(get_post_meta($post->ID, 'postads_display_type', true) == 'custom' ? ' checked="checked"':'').' /> <label for="postads_display_custom">Custom settings</label></p>'.
		'<p id="postads_custom" style="'.(get_post_meta($post->ID, 'postads_display_type', true) == 'custom' ? '':'display:none;').'padding-left: 10px;">';
		foreach ($display_options as $id => $text)
		{
			echo '<input type="checkbox" id="'.$id.'" name="'.$id.'"';
			if (get_post_meta($post->ID, $id, true) == 'enabled')
				echo ' checked="checked"';
			echo '> <label for="'.$id.'">'.$text.'</label><br/>';
		}
		echo '</p>'.
		'<h5>Max post ads count</h5>'.
		'<p><input type="radio" name="postads_ads_count_type" id="postads_ads_count_default"'.(get_post_meta($post->ID, 'postads_ads_count_type', true) == 'custom' ? '':' checked="checked"').' value="default" /> <label for="postads_ads_count_default">Default ('.get_option('postads_max_per_post').')</label><br/>'.
		'<input type="radio" name="postads_ads_count_type" id="postads_ads_count_custom"'.(get_post_meta($post->ID, 'postads_ads_count_type', true) == 'custom' ? ' checked="checked"':'').' value="custom" /> <label for="postads_ads_count_custom">Special for post: </label> <input type="text" size="2" name="postads_ads_count" value="'.get_post_meta($post->ID, 'postads_ads_count', true).'" />'.
		'</p>'.
		'</div></div>';

}

function postads_store_post_options($post_id)
{
	foreach (array('postads_text', 'postads_display_type', 'postads_ads_count_type', 'postads_ads_count') as $option)
		if (isset($_POST[$option]))
			update_post_meta($post_id, $option, $_POST[$option]);
	foreach (array('postads_index', 'postads_single', 'postads_rss', 'postads_hide_registered') as $option)
		if (isset($_POST[$option]))
			update_post_meta($post_id, $option, 'enabled');
		else
			update_post_meta($post_id, $option, 'disabled');
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
	$custom_display_options = get_post_meta($post->ID, 'postads_display_type', true) == 'custom' ? true : false;
	$ads = get_post_meta($post->ID, 'postads_text', true);
	if (($custom_display_options && get_post_meta($post->ID, 'postads_hide_registered', true) == 'enabled') || get_option('postads_hide_registered') == 'enabled')
	{
		global $user_ID;
		get_currentuserinfo();
		if ($user_ID)
			return false;
	}
	if (empty($ads))
		return false;
	if (is_feed())
		if (!check_post_option($custom_display_options, 'postads_rss'))
			return false;
		else
			return true;
	if (is_home())
		if (!check_post_option($custom_display_options, 'postads_index'))
			return false;
		else
			return true;
	if (is_single())
		if (!check_post_option($custom_display_options, 'postads_single'))
			return false;
		else
			return true;
	return true;
}

function check_post_option($custom_display_options, $option)
{
	// returns false to show ads, true to hide
	if (($custom_display_options && get_post_meta($post->ID, $option, true) != '' && get_post_meta($post->ID, $option, true) != 'enabled') || get_option($option) != 'enabled')
		return false;
	else
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

function postads_columns($defaults)
{
	$defaults['postads'] = 'Post Ads';
	return $defaults;
}

function postads_custom_column($column_name)
{
	global $post;
	if ($column_name == 'postads')
	{
		$text = str_replace("\r", '', get_post_meta($post->ID, 'postads_text', true));
		if (!trim($text))
			$count = 0;
		else
			$count = count(explode("\n\n", $text));
		if (get_post_meta($post->ID, 'postads_ads_count_type', true) == 'custom')
			$maxcount = get_post_meta($post->ID, 'postads_ads_count', true);
		else
			$maxcount = get_option('postads_max_per_post');
		if ($maxcount < $count)
			$color = '#cc0000';
		elseif ($count < $maxcount)
			$color = '#3465a4';
		else
			$color = '#4e9a06';
		echo '<p style="color:'.$color.'">';
		echo 'Current: '.$count.', Max: '.$maxcount;
		echo '</p>';
	}
}

function postads_config() {
	if (isset($_POST['stage']) && $_POST['stage'] == 'process')
	{
		if (function_exists('current_user_can') && !current_user_can('manage_options'))
			die(__('Cheatin&#8217; uh?'));
		update_option('postads_max_per_post', $_POST['postads_max_per_post']);
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
			<th scope="row">Max post ads count per post</th>
			<td>
				<input type="text" size="2" name="postads_max_per_post" value="<?php echo get_option('postads_max_per_post'); ?>" />
			</td>
		</tr>
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
add_action('manage_posts_custom_column', 'postads_custom_column');

add_action('admin_menu', 'postads_options_page');

add_filter('wp_head', 'postads_styles');
add_filter('the_content', 'postads_add_content', 5);
add_filter('manage_posts_columns', 'postads_columns');

?>