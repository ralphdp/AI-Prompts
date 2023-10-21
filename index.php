<?php
/*
Plugin Name: AI Prompt Listings
Description: A simple plugin that displays AI prompt listings, and allows for one click copy. To display the prompts use the shortcode: [ai-prompts].
Version: 1.0
Author: Rafael De Paz
*/

function enqueue_load_fa()
{
	wp_enqueue_style("load-fa", "https://use.fontawesome.com/releases/v5.5.0/css/all.css");
}

add_action("wp_enqueue_scripts", "enqueue_load_fa");

// Register the custom post type
function prompts_custom_post_type()
{
	$labels = [
		"name" => _x("AI Prompts", "Post Type General Name", "text_domain"),
		"singular_name" => _x("AI Prompt", "Post Type Singular Name", "text_domain"),
		"menu_name" => __("AI Prompts", "text_domain"),
		"name_admin_bar" => __("AI Prompt", "text_domain"),
		"archives" => __("AI Prompt Archives", "text_domain"),
		"attributes" => __("AI Prompt Attributes", "text_domain"),
		"parent_item_colon" => __("Parent Prompt:", "text_domain"),
		"all_items" => __("All Prompts", "text_domain"),
		"add_new_item" => __("Add New AI Prompt", "text_domain"),
		"add_new" => __("Add New", "text_domain"),
		"new_item" => __("New AI Prompt", "text_domain"),
		"edit_item" => __("Edit AI Prompt", "text_domain"),
		"update_item" => __("Update AI Prompt", "text_domain"),
		"view_item" => __("View AI Prompt", "text_domain"),
		"view_items" => __("View AI Prompts", "text_domain"),
		"search_items" => __("Search AI Prompt", "text_domain"),
		"not_found" => __("Not found", "text_domain"),
		"not_found_in_trash" => __("Not found in Trash", "text_domain"),
		"featured_image" => __("Featured Image", "text_domain"),
		"set_featured_image" => __("Set featured image", "text_domain"),
		"remove_featured_image" => __("Remove featured image", "text_domain"),
		"use_featured_image" => __("Use as featured image", "text_domain"),
		"insert_into_item" => __("Insert into AI Prompt", "text_domain"),
		"uploaded_to_this_item" => __("Uploaded to this AI Prompt", "text_domain"),
		"items_list" => __("AI Prompts list", "text_domain"),
		"items_list_navigation" => __("AI Prompts list navigation", "text_domain"),
		"filter_items_list" => __("Filter AI Prompts list", "text_domain"),
	];
	$args = [
		"label" => __("AI Prompt", "text_domain"),
		"description" => __("Post Type Description", "text_domain"),
		"labels" => $labels,
		"supports" => ["title", "editor", "excerpt", "thumbnail", "revisions", "custom-fields"],
		"taxonomies" => ["category", "post_tag"],
		"hierarchical" => false,
		"public" => true,
		"show_ui" => true,
		"show_in_menu" => true,
		"menu_position" => 4,
		"menu_icon" => "dashicons-clipboard",
		"show_in_admin_bar" => true,
		"show_in_nav_menus" => true,
		"can_export" => true,
		"has_archive" => true,
		"exclude_from_search" => false,
		"publicly_queryable" => true,
		"capability_type" => "post",
	];

	register_post_type("ai-prompts", $args);
}
add_action("init", "prompts_custom_post_type");

// Add custom field to the custom post type
function prompts_add_custom_field()
{
	add_meta_box("prompt_details", "Prompt Details", "prompts_render_custom_field", "ai-prompts", "side", "default");
}
add_action("add_meta_boxes", "prompts_add_custom_field");

// Render the custom field
function prompts_render_custom_field($post)
{
	wp_nonce_field(basename(__FILE__), "prompt_author_nonce");
	$prompt_author = get_post_meta($post->ID, "prompt_author", true);
	echo "<label for='prompt_author_field'></label> <input type='text' id='prompt_author_field' name='prompt_author_field' value='" . esc_attr($prompt_author) . "' size='25' placeholder='Author Name'/>";

	echo "<hr/>";

	wp_nonce_field(basename(__FILE__), "prompt_note_nonce");
	$prompt_note = get_post_meta($post->ID, "prompt_note", true);
	echo "<label for='prompt_note_field'></label> <input type='text' id='prompt_note_field' name='prompt_note_field' value='" . esc_attr($prompt_note) . "' size='25' placeholder='Note'/>";
}

// Save the custom field data
add_action("save_post", "prompts_save_custom_field_data");
function prompts_save_custom_field_data($post_id)
{
	if (!isset($_POST["prompt_author_nonce"])) {
		return;
	}
	if (!wp_verify_nonce($_POST["prompt_author_nonce"], basename(__FILE__))) {
		return;
	}
	if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
		return;
	}
	if (isset($_POST["post_type"]) && "ai-prompts" == $_POST["post_type"]) {
		if (!current_user_can("edit_post", $post_id)) {
			return;
		}
		if (!isset($_POST["prompt_author_field"])) {
			return;
		}
		$prompt_author = sanitize_text_field($_POST["prompt_author_field"]);
		update_post_meta($post_id, "prompt_author", $prompt_author);
	}
	if (!isset($_POST["prompt_note_nonce"])) {
		return;
	}
	if (!wp_verify_nonce($_POST["prompt_note_nonce"], basename(__FILE__))) {
		return;
	}
	if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
		return;
	}
	if (isset($_POST["post_type"]) && "ai-prompts" == $_POST["post_type"]) {
		if (!current_user_can("edit_post", $post_id)) {
			return;
		}
		if (!isset($_POST["prompt_note_field"])) {
			return;
		}
		$prompt_note = sanitize_text_field($_POST["prompt_note_field"]);
		update_post_meta($post_id, "prompt_note", $prompt_note);
	}
}

add_shortcode("ai-prompts", "list_prompts_shortcode");
function list_prompts_shortcode()
{
	ob_start();

	echo "
	<style>
	 .entry{
		  margin:15px 0px 25px 0px;
		padding:15px;
		border:2px #fff solid;
		border-radius:5px;
		-webkit-box-shadow: 0px 0px 15px 0px rgba(0,0,0,0.25);
		-moz-box-shadow: 0px 0px 15px 0px rgba(0,0,0,0.25);
		box-shadow: 0px 0px 15px 0px rgba(0,0,0,0.25);
	 }
	 .entry:hover{
		border:2px #f8f8f8 solid;
	 }
	 .entry div {
		width: 100%;
		height: 300px;
		box-shadow: inset 0px 0px 10px rgba(0,0,0,0.9);
		background-repeat: no-repeat;
		background-position: center bottom;
		border-top-left-radius: 3px;
		border-top-right-radius: 3px;
	 }
	 textarea {
		resize: vertical; /* user can resize vertically, but width is fixed */
	 }
	 button{
		float:right;
		padding:0px;
		font-size:90%; 
	 }
	</style>
	";

	$args = [
		"post_type" => "ai-prompts",
		"posts_per_page" => 5,
		"paged" => get_query_var("paged") ? get_query_var("paged") : 1,
	];
	$prompts = new WP_Query($args);
	if ($prompts->have_posts()) {
		while ($prompts->have_posts()) {
			$prompts->the_post();
			$content = get_the_content();
			$stripped = strip_tags($content, "<p> <a>"); //replace <p> and <a> with whatever tags you want to keep after the strip
			echo "<div class='entry'>";
			echo "<button class='' onclick='copyToClipboard(this)' value='" . $stripped . "'><i class='far fa-copy' title='Copy prompt.'></i></button>";
			the_title("<h2>", "</h2>");
			$featured_img_url = get_the_post_thumbnail_url(get_the_ID(), "full");
			echo "<div style='background-image: url($featured_img_url)'></div>";
			echo "<textarea class='example' id='" . get_the_ID() . "' readonly>";
			echo $stripped;
			echo "</textarea>";
			echo "<small>ID# ";
			the_id();
			echo " - Author: " . get_post_meta(get_the_ID(), "prompt_author", true) . " | " . get_post_meta(get_the_ID(), "prompt_note", true) . "</small></div>";
		}
	} else {
		echo "No prompts added yet.";
	}

	echo "	
	<script>		
		function copyToClipboard(btn) {
			const valueToCopy = btn.value;
			navigator.clipboard.writeText(valueToCopy).then(() => {
			console.log('Copied prompt to clipboard.');
			btn.innerHTML = '<i class=\'fas fa-copy\' title=\'Prompt copied.\'></i>';
		})
		.catch((error) => {
			console.error('Error copying prompt to clipboard.');
		});
	}
	</script>";

	$total_pages = $prompts->max_num_pages;

	if ($total_pages > 1) {
		$current_page = max(1, get_query_var("paged"));
		echo paginate_links([
			"base" => get_pagenum_link(1) . "%_%",
			"format" => "/page/%#%",
			"current" => $current_page,
			"total" => $total_pages,
			"prev_text" => __("« prev"),
			"next_text" => __("next »"),
		]);
	}

	wp_reset_postdata();
	return ob_get_clean();
}
?>
