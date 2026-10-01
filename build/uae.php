// R.A.M. Management – move the 2026 header and footer into UAE (Ultimate Addons for Elementor) templates.
// Run once with atarim-execute-php. Copies the Elementor blocks of the current header/footer Saved
// Templates unchanged into four UAE templates (FR + EN header, FR + EN footer, linked in WPML), shown on
// the entire website, and updates the child theme so it prints them. The Saved Templates are kept as
// the fallback; the old theme file is kept as inc/ram-<stamp>.php.

$commit  = '__COMMIT__';
$new_sha = '__NEW_SHA__';
$old_sha = '__OLD_SHA__';

// 1. Theme file: only replaced when the live copy is the one this change was made from.
$dest = get_stylesheet_directory() . '/inc/ram.php';
$live = file_get_contents( $dest );
if ( sha1( $live ) !== $old_sha && sha1( $live ) !== $new_sha ) {
	return array( 'error' => 'live inc/ram.php differs from the repository copy', 'live_sha' => sha1( $live ) );
}
$r = wp_remote_get( 'https://raw.githubusercontent.com/gajula-atarim/ram-attorney/' . $commit . '/theme/ram-hello-child/inc/ram.php', array( 'timeout' => 30 ) );
$body = is_wp_error( $r ) ? '' : wp_remote_retrieve_body( $r );
if ( sha1( $body ) !== $new_sha ) {
	return array( 'error' => 'download failed or checksum mismatch' );
}

// 2. UAE templates, copied block for block from the Saved Templates.
$plan = array(
	'header' => array( 'fr' => array( 177, 'ram-v2-header-fr', 'RAM Header (FR)', 'ram-uae-header-fr' ), 'en' => array( 179, 'ram-v2-header', 'RAM Header', 'ram-uae-header' ) ),
	'footer' => array( 'fr' => array( 181, 'ram-v2-footer-fr', 'RAM Footer (FR)', 'ram-uae-footer-fr' ), 'en' => array( 183, 'ram-v2-footer', 'RAM Footer', 'ram-uae-footer' ) ),
);
foreach ( $plan as $type => $langs ) {
	foreach ( $langs as $lang => $v ) {
		if ( get_post_field( 'post_name', $v[0] ) !== $v[1] ) {
			return array( 'error' => 'source template ' . $v[0] . ' is not ' . $v[1] );
		}
		if ( get_page_by_path( $v[3], OBJECT, 'elementor-hf' ) ) {
			return array( 'error' => 'UAE template ' . $v[3] . ' already exists' );
		}
	}
}

$stamp = gmdate( 'Ymd-His' );
copy( $dest, dirname( $dest ) . '/ram-' . $stamp . '.php' );

$made = array();
foreach ( $plan as $type => $langs ) {
	$ids = array();
	foreach ( $langs as $lang => $v ) {
		$id = wp_insert_post( array( 'post_type' => 'elementor-hf', 'post_status' => 'publish', 'post_title' => $v[2], 'post_name' => $v[3] ), true );
		if ( is_wp_error( $id ) ) {
			return array( 'error' => $v[3] . ': ' . $id->get_error_message(), 'made' => $made );
		}
		$ids[ $lang ] = (int) $id;
	}
	// WPML: French is the original, English its translation (UAE switches to the page's language).
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $ids['fr'], 'element_type' => 'post_elementor-hf', 'trid' => false, 'language_code' => 'fr', 'source_language_code' => null ) );
	$trid = apply_filters( 'wpml_element_trid', null, $ids['fr'], 'post_elementor-hf' );
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $ids['en'], 'element_type' => 'post_elementor-hf', 'trid' => $trid, 'language_code' => 'en', 'source_language_code' => 'fr' ) );
	update_post_meta( $ids['en'], '_wpml_post_translation_editor_native', 'yes' );

	// Content and display rules, written after the WPML link so nothing is copied across languages.
	foreach ( $langs as $lang => $v ) {
		$id = $ids[ $lang ];
		update_post_meta( $id, 'ehf_template_type', 'type_' . $type );
		update_post_meta( $id, 'ehf_target_include_locations', array( 'rule' => array( 'basic-global' ), 'specific' => array() ) );
		update_post_meta( $id, 'ehf_target_exclude_locations', array() );
		update_post_meta( $id, 'ehf_target_user_roles', array( 'all' ) );
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_template_type', 'wp-post' );
		update_post_meta( $id, '_elementor_version', get_post_meta( $v[0], '_elementor_version', true ) ?: ELEMENTOR_VERSION );
		update_post_meta( $id, '_elementor_data', wp_slash( get_post_meta( $v[0], '_elementor_data', true ) ) );
		$ps = get_post_meta( $v[0], '_elementor_page_settings', true );
		if ( $ps ) {
			update_post_meta( $id, '_elementor_page_settings', $ps );
		}
		$made[ $type . '-' . $lang ] = $id;
	}
}

// 3. Theme file last: until now the site kept using the Saved Templates.
if ( false === file_put_contents( $dest, $body ) ) {
	return array( 'error' => 'could not write inc/ram.php', 'made' => $made );
}
if ( function_exists( 'opcache_invalidate' ) ) {
	opcache_invalidate( $dest, true );
}

\Elementor\Plugin::$instance->files_manager->clear_cache();
wp_cache_flush();

// 4. Check: each UAE template holds exactly its source's blocks and has the right language.
$check = array();
foreach ( $plan as $type => $langs ) {
	foreach ( $langs as $lang => $v ) {
		$id = $made[ $type . '-' . $lang ];
		$check[ $type . '-' . $lang ] = array(
			'id'          => $id,
			'same_blocks' => get_post_meta( $id, '_elementor_data', true ) === get_post_meta( $v[0], '_elementor_data', true ),
			'lang'        => apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $id, 'element_type' => 'post_elementor-hf' ) ),
			'en_of_fr'    => 'fr' === $lang ? (int) apply_filters( 'wpml_object_id', $id, 'elementor-hf', false, 'en' ) : null,
		);
	}
}
update_option( 'ram_uae_build', array( 'time' => gmdate( 'c' ), 'commit' => $commit, 'made' => $made, 'theme_backup' => 'inc/ram-' . $stamp . '.php' ), false );

return array( 'made' => $made, 'check' => $check, 'theme_backup' => 'inc/ram-' . $stamp . '.php' );
