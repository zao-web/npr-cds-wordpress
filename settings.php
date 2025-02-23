<?php
/**
 * NPR API Settings Page and related control methods
 *
 * Also includes the cron jobs.
 */


/**
 * Push/Pull URLs:
 * Production: https://content.api.npr.org/
 * Staging: https://stage-content.api.npr.org/
 */

/**
 * add the options page
 *
 * @see npr_cds_publish_meta_box_prompt
 */
function npr_cds_add_options_page(): void {
	add_options_page( 'NPR CDS', 'NPR CDS', 'manage_options', 'npr_cds', 'npr_cds_options_page' );
}
add_action( 'admin_menu', 'npr_cds_add_options_page' );

function npr_cds_options_page(): void {
	?>
		<style>
			h1 {
				line-height: 1.25;
			}
			.npr-settings-group {
				display: none;
				padding: 1rem;
				border-bottom: 0.125em solid #808080;
				border-left: 0.125em solid #808080;
				border-right: 0.125em solid #808080;
				margin-right: 0.5rem;
			}
			.npr-settings-group.active {
				display: block;
			}
			.npr-settings-group .form-table td input[type="text"] {
				display: inline-block;
				max-width: 100%;
				width: 66%;
			}
			.npr-selector {
				display: grid;
				grid-template-columns: 1fr 1fr 1fr;
				align-content: center;
				justify-content: center;
				margin-right: 0.5rem;
			}
			.npr-selector div {
				background-color: #ffffff;
				border-top: 0.125em solid #808080;
				border-bottom: 0.125em solid #808080;
				border-left: 0.125em solid #808080;
				border-right: 0.125em solid #808080;
				transition: opacity 0.2s;
				text-align: center;
				font-size: 1.25em;
				padding: 0.5rem;
			}
			.npr-selector div:hover {
				opacity: 0.75;
				cursor: pointer;
			}
			.npr-selector div.active {
				color: #135e96;
				border-bottom: 0.125em solid transparent;
				border-top: 0.125em solid #135e96;
				background-color: #f0f0f1;
			}
			.npr-cds-query h4 {
				margin: 0;
				text-align: right;
			}
			.npr-cds-query {
				display: grid;
				grid-template-columns: 10rem auto;
				gap: 1rem;
				align-items: center;
				padding-bottom: 1rem;
				border-bottom: 1px solid #808080;
				margin-bottom: 1rem;
			}
			@media screen and (max-width: 500px) {
				.npr-cds-query h4 {
					text-align: left;
				}
				.npr-cds-query {
					grid-template-columns: 1fr;
				}
				.npr-settings-group .form-table td input[type="text"] {
					width: 100%;
				}
			}
		</style>
		<h1>NPR Content Distribution Service Settings</h1>
		<div class="npr-selector">
			<div data-tab="npr-general">General Settings</div>
			<div data-tab="npr-multi">Get Multi Settings</div>
			<div data-tab="npr-fields">Push Field Mapping</div>
		</div>
		<div class="npr-settings-group" data-tab="npr-general-tab">
			<form action="options.php" method="post">
				<?php
					settings_fields( 'npr_cds' );
					do_settings_sections( 'npr_cds' );
					submit_button(); ?>
			</form>
		</div>
		<div class="npr-settings-group" data-tab="npr-multi-tab">
			<form action="options.php" method="post">
				<?php
					settings_fields( 'npr_cds_get_multi_settings' );
					do_settings_sections( 'npr_cds_get_multi_settings' );
					submit_button();
				?>
			</form>
		</div>
		<div class="npr-settings-group" data-tab="npr-fields-tab">
			<form action="options.php" method="post">
				<?php
					settings_fields( 'npr_cds_push_mapping' );
					do_settings_sections( 'npr_cds_push_mapping' );
					submit_button();
				?>
			</form>
		</div>
		<script>
			const nprSections = document.querySelectorAll('.npr-selector > div');
			const nprGroups = document.querySelectorAll('.npr-settings-group');
			const hashMark = window.location.hash;
			if ( hashMark === '#npr-general' || hashMark === '#npr-multi' || hashMark === '#npr-fields' ) {
				let tabId = hashMark.replace('#', '');
				document.querySelector('[data-tab="'+tabId+'-tab"]').classList.add('active');
				document.querySelector('[data-tab="'+tabId+'"]').classList.add('active');
			} else {
				document.querySelector('[data-tab="npr-general-tab"]').classList.add('active');
				document.querySelector('[data-tab="npr-general"]').classList.add('active');
				window.location.assign('#npr-general');
			}
			Array.from(nprSections).forEach((ns) => {
				ns.addEventListener('click', (evt) => {
					console.log(evt.target);
					let tab = evt.target.getAttribute('data-tab');
					Array.from(nprSections).forEach((nse) => {
						nse.classList.remove('active');
					});
					evt.target.classList.add('active');
					Array.from(nprGroups).forEach((ng) => {
						ng.classList.remove('active');
					});
					document.querySelector('[data-tab="'+tab+'-tab"]').classList.add('active');
					window.location.assign('#'+tab);
				});
			});
		</script>
	<?php
}

function npr_cds_settings_init(): void {
	// NPR CDS Settings Group
	add_settings_section( 'npr_cds_settings', 'General Settings', 'npr_cds_settings_callback', 'npr_cds' );

	add_settings_field( 'npr_cds_token', 'CDS Token', 'npr_cds_token_callback', 'npr_cds', 'npr_cds_settings' );
	register_setting( 'npr_cds', 'npr_cds_token' );

	add_settings_field( 'npr_cds_pull_url', 'Pull URL', 'npr_cds_pull_url_callback', 'npr_cds', 'npr_cds_settings' );
	register_setting( 'npr_cds', 'npr_cds_pull_url', [ 'sanitize_callback' => 'npr_cds_validation_callback_pull_url' ] );

	add_settings_field( 'npr_cds_push_url', 'Push URL', 'npr_cds_push_url_callback', 'npr_cds', 'npr_cds_settings' );
	register_setting( 'npr_cds', 'npr_cds_push_url', [ 'sanitize_callback' => 'npr_cds_validation_callback_pull_url' ] );

	add_settings_field( 'npr_cds_org_id', 'Org ID', 'npr_cds_org_id_callback', 'npr_cds', 'npr_cds_settings' );
	register_setting( 'npr_cds', 'npr_cds_org_id', [ 'sanitize_callback' => 'npr_cds_validation_callback_org_id' ] );

	add_settings_field( 'npr_cds_prefix', 'Document Prefix', 'npr_cds_prefix_callback', 'npr_cds', 'npr_cds_settings' );
	register_setting( 'npr_cds', 'npr_cds_prefix', [ 'sanitize_callback' => 'npr_cds_validation_callback_prefix' ] );

	add_settings_field( 'npr_cds_query_use_featured', 'Theme uses Featured Image', 'npr_cds_query_use_featured_callback', 'npr_cds', 'npr_cds_settings' );
	register_setting( 'npr_cds_settings', 'npr_cds_query_use_featured', [ 'sanitize_callback' => 'npr_cds_validation_callback_checkbox' ] );

	add_settings_field( 'npr_cds_pull_post_type', 'NPR Pull Post Type', 'npr_cds_pull_post_type_callback', 'npr_cds', 'npr_cds_settings' );
	register_setting( 'npr_cds', 'npr_cds_pull_post_type' );

	add_settings_field( 'npr_cds_push_post_type', 'NPR Push Post Type', 'npr_cds_push_post_type_callback', 'npr_cds', 'npr_cds_settings' );
	register_setting( 'npr_cds', 'npr_cds_push_post_type' );

	add_settings_field( 'npr_cds_image_width', 'Max Image Width', 'npr_cds_image_width_callback', 'npr_cds', 'npr_cds_settings' );
	register_setting( 'npr_cds', 'npr_cds_image_width' );

	add_settings_field( 'npr_cds_image_quality', 'Image Quality', 'npr_cds_image_quality_callback', 'npr_cds', 'npr_cds_settings' );
	register_setting( 'npr_cds', 'npr_cds_image_quality' );

	add_settings_field( 'npr_cds_image_format', 'Image Format', 'npr_cds_image_format_callback', 'npr_cds', 'npr_cds_settings' );
	register_setting( 'npr_cds', 'npr_cds_image_format' );

	// NPR CDS Get Multi Settings Group
	add_settings_section( 'npr_cds_get_multi_settings', 'Multiple Get Settings', 'npr_cds_get_multi_settings_callback', 'npr_cds_get_multi_settings' );

	add_settings_field( 'npr_cds_num', 'Number of things to get', 'npr_cds_num_multi_callback', 'npr_cds_get_multi_settings', 'npr_cds_get_multi_settings' );
	register_setting( 'npr_cds_get_multi_settings', 'npr_cds_num', [ 'type' => 'integer' ] );

	$num = get_option( 'npr_cds_num', 5 );
	for ( $i = 0; $i < $num; $i++ ) {
		add_settings_field( 'npr_cds_query_' . $i, 'Query ' . $i, 'npr_cds_query_callback', 'npr_cds_get_multi_settings', 'npr_cds_get_multi_settings', $i );
		register_setting( 'npr_cds_get_multi_settings', 'npr_cds_query_' . $i, [ 'type' => 'array', 'default' => [ 'filters' => '', 'sorting' => '', 'publish' => '', 'category' => '', 'tags' => '' ] ] );
	}

	add_settings_field( 'npr_cds_query_run_multi', 'Run the queries on saving changes', 'npr_cds_query_run_multi_callback', 'npr_cds_get_multi_settings', 'npr_cds_get_multi_settings' );
	register_setting( 'npr_cds_get_multi_settings', 'npr_cds_query_run_multi', [ 'sanitize_callback' => 'npr_cds_validation_callback_checkbox' ] );

	add_settings_field( 'npr_cds_query_multi_cron_interval', 'Interval to run Get Multi cron', 'npr_cds_query_multi_cron_interval_callback', 'npr_cds_get_multi_settings', 'npr_cds_get_multi_settings' );
	register_setting( 'npr_cds_get_multi_settings', 'npr_cds_query_multi_cron_interval', [ 'type' => 'integer' ] );

	// NPR CDS Push Settings Group
	add_settings_section( 'npr_cds_push_settings', 'Metadata Settings', 'npr_cds_push_settings_callback', 'npr_cds_push_mapping' );

	add_settings_field( 'npr_cds_push_use_custom_map', 'Use Custom Settings', 'npr_cds_use_custom_mapping_callback', 'npr_cds_push_mapping', 'npr_cds_push_settings' );
	register_setting( 'npr_cds_push_mapping', 'npr_cds_push_use_custom_map', [ 'sanitize_callback' => 'npr_cds_validation_callback_checkbox' ] );

	add_settings_field( 'npr_cds_mapping_title', 'Story Title', 'npr_cds_mapping_title_callback', 'npr_cds_push_mapping', 'npr_cds_push_settings' );
	register_setting( 'npr_cds_push_mapping', 'npr_cds_mapping_title' );

	add_settings_field( 'npr_cds_mapping_body', 'Story Body', 'npr_cds_mapping_body_callback', 'npr_cds_push_mapping', 'npr_cds_push_settings' );
	register_setting( 'npr_cds_push_mapping', 'npr_cds_mapping_body' );

	add_settings_field( 'npr_cds_mapping_byline', 'Story Byline', 'npr_cds_mapping_byline_callback', 'npr_cds_push_mapping', 'npr_cds_push_settings' );
	register_setting( 'npr_cds_push_mapping', 'npr_cds_mapping_byline' );

	add_settings_field( 'npr_cds_mapping_media_credit', 'Media Credit Field', 'npr_cds_mapping_media_credit_callback', 'npr_cds_push_mapping', 'npr_cds_push_settings' );
	register_setting( 'npr_cds_push_mapping', 'npr_cds_mapping_media_credit' );

	add_settings_field( 'npr_cds_mapping_media_agency', 'Media Agency Field', 'npr_cds_mapping_media_agency_callback', 'npr_cds_push_mapping', 'npr_cds_push_settings' );
	register_setting( 'npr_cds_push_mapping', 'npr_cds_mapping_media_agency' );
}
add_action( 'admin_init', 'npr_cds_settings_init' );

/**
 * Settings group callback functions
 */
function npr_cds_settings_callback() { }

function npr_cds_push_settings_callback(): void { ?>
	<p>Use this page to map your custom WordPress Meta fields to fields sent to the NPR CDS, and vice versa. Clicking the <strong>Use Custom Settings</strong> checkbox will enable these mappings. If you wish to use the default mapping for a field, select &mdash; default &mdash; and we will use the obvious WordPress field.</p>
	<p>Select for the Meta fields for the <strong><?php echo npr_cds_get_push_post_type(); ?></strong> post type.</p> <?php
 }

function npr_cds_get_multi_settings_callback(): void {
	$run_multi = get_option( 'npr_cds_query_run_multi' );

	$num = get_option( 'npr_cds_num', 5 );
	$enable = false;
	for ( $i = 0; $i < $num; $i++ ) {
		$option = get_option( 'npr_cds_query_' . $i );
		if ( !empty( $option['filters'] ) || !empty( $options['sorting'] ) ) {
			$enable = true;
		}
	}
	if ( $run_multi && $enable ) {
		NPR_CDS::cron_pull();
	}

	//change the cron timer
	if ( wp_next_scheduled( 'npr_cds_hourly_cron' ) ) {
		wp_clear_scheduled_hook( 'npr_cds_hourly_cron' );
	}
	npr_cds_error_log( 'NPR CDS plugin: updating the npr_cds_hourly_cron event timer' );
	wp_schedule_event( time(), 'ds_interval', 'npr_cds_hourly_cron');
	?>
	<p>Create an NPR CDS query. Enter your queries into one of the rows below to have stories on that query automatically publish to your site. Please note, you do not need to include your CDS token in the query.</p><?php
}

/**
 * Add cron intervals
 */
function npr_cds_add_cron_interval( $schedules ): array {
	$ds_interval = get_option( 'npr_cds_query_multi_cron_interval' );
	//if for some reason we don't get a number in the option, use 60 minutes as the default.
	if ( !is_numeric( $ds_interval ) || $ds_interval < 1 ) {
		$ds_interval = 60;
		update_option( 'npr_cds_query_multi_cron_interval', 60 );
	}
	$new_interval = $ds_interval * 60;
	$schedules['ds_interval'] = [
	  'interval' => $new_interval,
	  'display' => __( 'DS Cron, run Once every ' . $ds_interval . ' minutes' )
	];
	return $schedules;
}
add_filter( 'cron_schedules', 'npr_cds_add_cron_interval' );

/**
 * NPR General Settings Group Callbacks
 */
function npr_cds_token_callback(): void {
	$option = get_option( 'npr_cds_token' );
	echo npr_cds_esc_html( '<p><input type="text" value="' . $option . '" name="npr_cds_token" /></p><p><em>This is a bearer token provided by NPR. If you do not already have one, you can request one through <a href="https://studio.npr.org/">NPR Studio</a>.</em></p>' );
}

function npr_cds_pull_url_callback(): void {
	$option = get_option( 'npr_cds_pull_url' );
	$output = '<p><label><input type="radio" name="npr_cds_pull_url" value="https://stage-content.api.npr.org"' . ( $option == 'https://stage-content.api.npr.org' ? ' checked="checked"' : '' ) . ' /> Staging</label></p>';
	$output .= '<p><label><input type="radio" name="npr_cds_pull_url" value="https://content.api.npr.org"' . ( $option == 'https://content.api.npr.org' ? ' checked="checked"' : '' ) . ' /> Production</label></p>';
	$output .= '<p><label><input type="radio" name="npr_cds_pull_url" value="other"' . ( $option !== 'https://stage-content.api.npr.org' && $option !== 'https://content.api.npr.org' ? ' checked="checked"' : '' ) . ' /> Other</label> <input type="text" name="npr_cds_pull_url_other" id="npr_cds_pull_url_other" value="' . ( $option !== 'https://stage-content.api.npr.org' && $option !== 'https://content.api.npr.org' ? $option : '' ) . '" placeholder="Type other URL here" /></p>';
	echo npr_cds_esc_html( $output );
}

function npr_cds_push_url_callback(): void {
	$option = get_option( 'npr_cds_push_url' );
	$output = '<p><label><input type="radio" name="npr_cds_push_url" value="https://stage-content.api.npr.org"' . ( $option == 'https://stage-content.api.npr.org' ? ' checked="checked"' : '' ) . ' /> Staging</label></p>';
	$output .= '<p><label><input type="radio" name="npr_cds_push_url" value="https://content.api.npr.org"' . ( $option == 'https://content.api.npr.org' ? ' checked="checked"' : '' ) . ' /> Production</label></p>';
	$output .= '<p><label><input type="radio" name="npr_cds_push_url" value="other"' . ( $option !== 'https://stage-content.api.npr.org' && $option !== 'https://content.api.npr.org' ? ' checked="checked"' : '' ) . ' /> Other</label> <input type="text" name="npr_cds_push_url_other" id="npr_cds_push_url_other" value="' . ( $option !== 'https://stage-content.api.npr.org' && $option !== 'https://content.api.npr.org' ? $option : '' ) . '" placeholder="Type other URL here" /></p>';
	echo npr_cds_esc_html( $output );
}

function npr_cds_org_id_callback(): void {
	$option = get_option( 'npr_cds_org_id' );
	echo npr_cds_esc_html( '<input type="text" value="' . $option . '" name="npr_cds_org_id" />' );
}

function npr_cds_prefix_callback(): void {
	$option = get_option( 'npr_cds_prefix' );
	echo npr_cds_esc_html( '<p><input type="text" value="' . $option . '" name="npr_cds_prefix" placeholder="callletters" /></p><p><em>When given write permission to the CDS, NPR will assign a code that will be prefixed on all of your document IDs (e.g. "kuhf-12345").</em></p>' );
}

function npr_cds_query_use_featured_callback(): void {
	$use_featured = get_option( 'npr_cds_query_use_featured' );
	$check_box_string = '<input id="npr_cds_query_use_feature" name="npr_cds_query_use_featured" type="checkbox" value="true"' .
		( $use_featured ? ' checked="checked"' : '' ) . ' />';

	echo npr_cds_esc_html( '<p>' . $check_box_string . " If your theme uses the featured image, checking this box will remove the lead image from imported posts.</p>" );
}

function npr_cds_pull_post_type_callback(): void {
	$post_types = get_post_types();
	npr_cds_show_post_types_select( 'npr_cds_pull_post_type', $post_types );
}

function npr_cds_push_post_type_callback(): void {
	$post_types = get_post_types();
	npr_cds_show_post_types_select( 'npr_cds_push_post_type', $post_types );
	echo npr_cds_esc_html( '<p><em>If you change the Push Post Type setting remember to update the mappings for CDS Fields at <a href="' . admin_url( 'options-general.php?page=npr_cds#npr-fields' ) . '">NPR CDS Field Mapping</a> tab.</em></p>' );
}

function npr_cds_image_format_callback(): void {
	npr_cds_show_post_types_select( 'npr_cds_image_format', [ 'jpeg', 'png', 'webp' ] );
}

function npr_cds_image_quality_callback(): void {
	$option = get_option( 'npr_cds_image_quality', 75 );
	echo npr_cds_esc_html( '<p><input type="number" value="' . $option . '" name="npr_cds_image_quality" min="1" max="100" /></p><p><em>Set the quality level of the images from the NPR CDS (default: 75).</em></p>' );
}

function npr_cds_image_width_callback(): void {
	$option = get_option( 'npr_cds_image_width', 1200 );
	echo npr_cds_esc_html( '<p><input type="number" value="' . $option . '" name="npr_cds_image_width" min="500" max="3000" /></p><p><em>Maximum width of images pulled in from the NPR CDS (default: 1200).</em></p>' );
}

/**
 * NPR Get Multi Settings Group Callbacks
 */
function npr_cds_num_multi_callback(): void {
	$option = get_option( 'npr_cds_num' );
	echo npr_cds_esc_html( '<p><input type="number" value="' . $option . '" name="npr_cds_num" /></p><p><em>Increase the number of queries by changing the number in the field above, to a maximum of 10.</em></p>' );
}

function npr_cds_query_callback( $i ): void {
	if ( is_integer( $i ) ) {
		$optionType = get_option( 'npr_cds_pull_post_type', 'post' );
		$query = get_option( 'npr_cds_query_' . $i );

		$output = '<div class="npr-cds-query"><h4>Filters</h4><div><p><input type="text" value="' . $query['filters'] . '" name="npr_cds_query_' . $i . '[filters]" placeholder="profileIds=renderable&collectionIds=1002" /></p>' .
			'<p><em>A list of available filtering options can be found <a href="https://npr.github.io/content-distribution-service/querying/filtering.html">in the CDS documentation</a></em></p></div>' .
			'<h4>Sorting</h4><div><p><input type="text" value="' . $query['sorting'] . '" name="npr_cds_query_' . $i . '[sorting]" placeholder="sort=<type>[:<direction>]" /></p>' .
			'<p><em>A list of available sorting query parameters can be found <a href="https://npr.github.io/content-distribution-service/querying/sorting.html">in the CDS documentation</a></em></p></div>' .
			'<h4>Publish or Save as Draft?</h4> ' .
				'<div><select id="npr_cds_query_' . $i . '[publish]" name="npr_cds_query_' . $i . '[publish]">' .
					'<option value="Publish"' . ( $query['publish'] == 'Publish' ? ' selected' : '' ) . '>Publish</option>' .
					'<option value="Draft"' . ( $query['publish'] == 'Draft' ? ' selected' : '' ) . '>Draft</option>' .
				'</select></div>';
		if ( $optionType == 'post' ) {
			$args = [
				'show_option_none'	=> __( 'Select category', '' ),
				'name'				=> 'npr_query_' . $i . '[category]',
				'hierarchical'		=> true,
				'show_count'		=> 0,
				'orderby'			=> 'name',
				'echo'				=> 0,
				'selected'			=> ( !empty( $query['category'] ) ? (int)$query['category'] : 0 ),
				'hide_empty'		=> 0,
				'multiple'			=> true
			];
			$select = wp_dropdown_categories( $args );
			$output .= '<h4>Add Category</h4><div>' . $select . '</div>';
		}
		$output .= '<h4>Add Tags</h4><div><p><input type="text" value="' . $query['tags'] . '" name="npr_cds_query_' . $i . '[tags]" placeholder="pepperoni,pineapple,mozzarella" /></p>' .
			'<p><em>Add tag(s) to each story pulled from NPR (comma separated).</em></p>';
		echo npr_cds_esc_html( $output );
	}
}

function npr_cds_query_run_multi_callback(): void {
	$run_multi = get_option( 'npr_cds_query_run_multi' );
	$num = get_option( 'npr_cds_num', 5 );
	$enable = false;
	for ( $i = 0; $i < $num; $i++ ) {
		$option = get_option( 'npr_cds_query_' . $i );
		if ( !empty( $option['filters'] ) || !empty( $options['sorting'] ) ) {
			$enable = true;
		}
	}
	if ( $enable ) {
		$check_box_string = '<p><input id="npr_cds_query_run_multi" name="npr_cds_query_run_multi" type="checkbox" value="true"' . ( $run_multi ? ' checked="checked"' : '' ) . ' /></p>';
	} else {
		$check_box_string = '<p><input id="npr_cds_query_run_multi" name="npr_cds_query_run_multi" type="checkbox" value="true" disabled /> <em>Add filters or sorting to the queries above to enable this option</em></p>';
	}
	echo npr_cds_esc_html( $check_box_string );
}

function npr_cds_query_multi_cron_interval_callback(): void {
	$option = get_option( 'npr_cds_query_multi_cron_interval' );
	echo npr_cds_esc_html( '<p><input type="number" value="' . $option . '" name="npr_cds_query_multi_cron_interval" id="npr_cds_query_multi_cron_interval" /></p><p><em>How often, in minutes, should the Get Multi function run?  (default = 60)</em></p>' );
}

/**
 * NPR Push Settings Group Callbacks
 */
function npr_cds_use_custom_mapping_callback(): void {
	$use_custom = get_option( 'npr_cds_push_use_custom_map' );
	$check_box_string = '<input id="npr_cds_push_use_custom_map" name="npr_cds_push_use_custom_map" type="checkbox" value="true"' .
		( $use_custom ? ' checked="checked"' : '' ) . ' />';
	echo npr_cds_esc_html( $check_box_string );
}

function npr_cds_mapping_title_callback(): void {
	$push_post_type = npr_cds_get_push_post_type();
	$keys = npr_cds_get_post_meta_keys( $push_post_type );
	npr_cds_show_keys_select( 'npr_cds_mapping_title', $keys );
}

function npr_cds_mapping_body_callback(): void {
	$push_post_type = npr_cds_get_push_post_type();
	$keys = npr_cds_get_post_meta_keys( $push_post_type );
	npr_cds_show_keys_select( 'npr_cds_mapping_body', $keys );
}

function npr_cds_mapping_byline_callback(): void {
	$push_post_type = npr_cds_get_push_post_type();
	$keys = npr_cds_get_post_meta_keys( $push_post_type );
	npr_cds_show_keys_select( 'npr_cds_mapping_byline', $keys );
}

function npr_cds_mapping_media_credit_callback(): void {
	$keys = npr_cds_get_post_meta_keys( 'attachment' );
	npr_cds_show_keys_select( 'npr_cds_mapping_media_credit', $keys );
}

function npr_cds_mapping_media_agency_callback(): void {
	$keys = npr_cds_get_post_meta_keys( 'attachment' );
	npr_cds_show_keys_select( 'npr_cds_mapping_media_agency', $keys );
}

/**
 * create the select widget where the Id is the value in the array
 *
 * @param string $field_name
 * @param array $keys - an array like (1=>'Value1', 2=>'Value2', 3=>'Value3');
 */
function npr_cds_show_post_types_select( string $field_name, array $keys ): void {
	$selected = get_option( $field_name );

	echo npr_cds_esc_html( '<div><select id="' . $field_name . '" name="' . $field_name . '">' );

	echo '<option value=""> &mdash; Select &mdash; </option>';
	foreach ( $keys as $key ) {
		$option_string = "\n<option  ";
		if ( $key == $selected ) {
			$option_string .= " selected ";
		}
		$option_string .=   "value='" . esc_attr( $key ) . "'>" . esc_html( $key ) . " </option>";
		echo npr_cds_esc_html( $option_string );
	}
	echo "</select> </div>";
}

/**
 * checkbox validation callback
 */
function npr_cds_validation_callback_checkbox( $value ): bool {
	return (bool) $value;
}

/**
 * Prefix validation callback. We only want to save the prefix without the hyphen
 */
function npr_cds_validation_callback_prefix( $value ): string {
	$value = strtolower( $value );
	preg_match( '/([a-z0-9]+)/', $value, $match );
	if ( !empty( $match ) ) {
		return $match[1];
	}
	add_settings_error(
		'npr_cds_prefix',
		'prefix-is-invalid',
		esc_html( $value ) . __( ' is not a valid value for the NPR CDS Prefix. It can only contain lowercase alphanumeric characters.' )
	);
	return '';
}

/**
 * URL validation callbacks for the CDS URLs
 */
function npr_cds_validation_callback_pull_url( string $value ): string {
	if ( $value == 'https://stage-content.api.npr.org' || $value == 'https://content.api.npr.org' ) {
		return esc_attr( $value );
	} elseif ( $value == 'other' ) {
		$value = rtrim( $_POST['npr_cds_pull_url_other'], "/" );
		if ( !preg_match( '/https:\/\/[a-z0-9\.\-]+/', $value ) ) {
			add_settings_error(
				'npr_cds_pull_url',
				'not-https-url',
				esc_html( $value ) . __( ' is not a valid value for the NPR CDS Pull URL. It must be a URL starting with <code>https</code>.' )
			);
			$value = '';
		}
	}
	return esc_attr( $value );
}
function npr_cds_validation_callback_push_url( string $value ): string {
	if ( $value == 'https://stage-content.api.npr.org' || $value == 'https://content.api.npr.org' ) {
		return esc_attr( $value );
	} elseif ( $value == 'other' ) {
		$value = $_POST['npr_cds_push_url_other'];
		if ( !preg_match( '/https:\/\/[a-z0-9\.\-]+/', $value ) ) {
			add_settings_error(
				'npr_cds_push_url',
				'not-https-url',
				esc_html( $value ) . __( ' is not a valid value for the NPR CDS Push URL. It must be a URL starting with <code>https</code>.' )
			);
			$value = '';
		}
	}
	return esc_attr( $value );
}

function npr_cds_validation_callback_org_id( $value ): string {
	if ( preg_match( '/^[0-9]{1,4}$/', $value ) ) {
		$value = 's' . $value;
	}
	return esc_attr( $value );
}

/**
 * Create the select widget of all meta fields
 *
 * @param string $field_name
 * @param array $keys
 */
function npr_cds_show_keys_select( string $field_name, array $keys ): void {

	$selected = get_option( $field_name );

	echo npr_cds_esc_html( "<div><select id=" . $field_name . " name=" . $field_name . ">" );

	echo '<option value="#NONE#"> &mdash; default &mdash; </option>';
	foreach ( $keys as $key ) {
		$option_string = "\n<option  ";
		if ($key == $selected) {
			$option_string .= " selected ";
		}
		$option_string .=   "value='" . esc_attr( $key ) . "'>" . esc_html( $key ) . " </option>";
		echo npr_cds_esc_html( $option_string );
	}
	echo "</select> </div>";
}

function npr_cds_get_push_post_type() {
	return get_option( 'npr_cds_push_post_type', 'post' );
}