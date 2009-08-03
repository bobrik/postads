<?php
/*
Plugin Name: PostAds
Plugin URI: http://bobrik.name/
Description: Add and manage ads to your posts
Version: 0.2
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
	return $text.((empty($ads) || !is_single()) ? '' : "\n"."<div class='postads'>\n\n".$ads."\n\n</div>");
}

function postads_styles()
{
?>
<style type="text/css"><!--
.postads {display:block; font-size:90%; color:#666666; border:1px solid #ddd; padding:4px; background-image:url(<?php echo get_option('siteurl').'/wp-content/plugins/postads/'; ?>ads.png);background-position:bottom right; background-repeat:no-repeat;}
.postads p {margin:4px;}
--></style>
<?php
}

add_action('edit_form_advanced', 'postads_post_options');
add_action('draft_post', 'postads_store_post_options');
add_action('publish_post', 'postads_store_post_options');
add_action('save_post', 'postads_store_post_options');

add_filter('wp_head', 'postads_styles');
add_filter('the_content', 'postads_add_content', 5);

?>