// R.A.M. Management – install the 2026 design (run once with atarim-execute-php).
// Downloads the build from github.com/gajula-atarim/ram-attorney at a fixed commit, checks every file's
// SHA-1, then: updates the child theme (backups kept), adds the icons, renames the old-design draft
// pages (content untouched), creates the new pages and header/footer templates as Elementor documents,
// links FR/EN in WPML, makes the new French home page the front page, and removes the temporary
// "TEMP patch" images. The new pages stay drafts; ?ram_preview=<token> shows them.

$commit = '__COMMIT__';
$files  = __MANIFEST__;
$base   = 'https://raw.githubusercontent.com/gajula-atarim/ram-attorney/' . $commit . '/';

$got = array();
foreach ( $files as $path => $sha ) {
	$r = wp_remote_get( $base . $path, array( 'timeout' => 30 ) );
	if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
		return array( 'error' => 'download failed: ' . $path );
	}
	$body = wp_remote_retrieve_body( $r );
	if ( sha1( $body ) !== $sha ) {
		return array( 'error' => 'checksum mismatch: ' . $path );
	}
	$got[ $path ] = $body;
}
$log = array();

// 1. Child theme files (CSS first, ram.php before the templates that call it). Each old file is kept as <name>-<stamp>.<ext>.
$theme = get_stylesheet_directory();
$stamp = gmdate( 'Ymd-His' );
foreach ( array( 'assets/ram-v2.css', 'assets/ram.js', 'inc/ram.php', 'header.php', 'footer.php' ) as $rel ) {
	$dest = $theme . '/' . $rel;
	if ( file_exists( $dest ) ) {
		$info = pathinfo( $dest );
		copy( $dest, $info['dirname'] . '/' . $info['filename'] . '-' . $stamp . '.' . $info['extension'] );
	}
	if ( false === file_put_contents( $dest, $got[ 'theme/ram-hello-child/' . $rel ] ) ) {
		return array( 'error' => 'could not write ' . $rel, 'log' => $log );
	}
	$log[] = 'theme: ' . $rel;
}

// 2. Icons as Media Library SVGs (used by the Icon Box and Icon widgets).
$upload = wp_upload_dir();
$icons  = array();
foreach ( $got as $path => $body ) {
	if ( 0 !== strpos( $path, 'build/icons/' ) ) {
		continue;
	}
	$name = basename( $path, '.svg' );                   // ram-icon-waiver
	$file = wp_unique_filename( $upload['path'], $name . '.svg' );
	file_put_contents( $upload['path'] . '/' . $file, $body );
	$id = wp_insert_attachment( array(
		'post_title'     => $name,
		'post_mime_type' => 'image/svg+xml',
		'post_status'    => 'inherit',
		'guid'           => $upload['url'] . '/' . $file,
	), $upload['path'] . '/' . $file );
	update_post_meta( $id, '_elementor_inline_svg', $body );
	$icons[ substr( $name, strlen( 'ram-icon-' ) ) ] = array( 'id' => (int) $id, 'url' => $upload['url'] . '/' . $file );
}
$log[] = 'icons: ' . wp_json_encode( $icons );

$doc = function ( $key ) use ( $got, $icons ) {
	$json = $got[ 'build/elementor/' . $key . '.json' ];
	foreach ( $icons as $n => $i ) {
		$json = str_replace( '"__ICON_' . $n . '_ID__"', (string) $i['id'], $json );
		$json = str_replace( '__ICON_' . $n . '_URL__', $i['url'], $json );
	}
	return json_decode( $json, true );
};

// Saves Elementor data the way the editor does (post content, CSS and version included).
$save = function ( $id, $elements, $type ) {
	update_post_meta( $id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $id, '_elementor_template_type', $type );
	$document = \Elementor\Plugin::$instance->documents->get( $id, false );
	$ok       = $document && $document->save( array( 'elements' => $elements, 'settings' => array() ) );
	if ( ! $ok ) {
		update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
		update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
	}
	return $ok ? 'document' : 'meta';
};

$link = function ( $fr, $en, $type ) {
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $fr, 'element_type' => $type, 'trid' => false, 'language_code' => 'fr', 'source_language_code' => null ) );
	$trid = apply_filters( 'wpml_element_trid', null, $fr, $type );
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $en, 'element_type' => $type, 'trid' => $trid, 'language_code' => 'en', 'source_language_code' => 'fr' ) );
	update_post_meta( $en, '_wpml_post_translation_editor_native', 'yes' );
};

// 3. Old-design draft pages: new address ending and a title note only. Their content is not touched.
$old = array(
	118 => array( 'accueil-ancien', 'Accueil (ancien design)' ),
	119 => array( 'a-propos-ancien', 'À propos (ancien design)' ),
	120 => array( 'nous-joindre-ancien', 'Nous joindre (ancien design)' ),
	18  => array( 'home-old', 'Home (old design)' ),
	19  => array( 'about-old', 'About (old design)' ),
	20  => array( 'contact-old', 'Contact (old design)' ),
);
$was = array();
foreach ( $old as $id => $v ) {
	$p          = get_post( $id );
	$was[ $id ] = array( 'post_name' => $p->post_name, 'post_title' => $p->post_title, 'post_status' => $p->post_status );
	wp_update_post( array( 'ID' => $id, 'post_name' => $v[0], 'post_title' => $v[1] ) );
}
update_option( 'ram_v1_pages', array( 'time' => gmdate( 'c' ), 'pages' => $was, 'page_on_front' => (int) get_option( 'page_on_front' ) ), false );
$log[] = 'old drafts renamed';

// 4. New pages (drafts), French first as the source language.
$pages = array(
	'home'    => array( 'fr' => array( 'Accueil', 'accueil' ), 'en' => array( 'Home', 'home' ) ),
	'about'   => array( 'fr' => array( 'À propos', 'a-propos' ), 'en' => array( 'About', 'about' ) ),
	'contact' => array( 'fr' => array( 'Contact', 'nous-joindre' ), 'en' => array( 'Contact', 'contact' ) ),
);
$new = array();
foreach ( $pages as $key => $langs ) {
	foreach ( $langs as $lang => $v ) {
		$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => $v[0], 'post_name' => $v[1] ), true );
		if ( is_wp_error( $id ) ) {
			return array( 'error' => 'page ' . $key . '-' . $lang . ': ' . $id->get_error_message(), 'log' => $log );
		}
		update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
		update_post_meta( $id, '_ram_v2', '1' );
		$how                      = $save( $id, $doc( $key . '-' . $lang ), 'wp-page' );
		$new[ $key . '-' . $lang ] = array( 'id' => (int) $id, 'slug' => get_post_field( 'post_name', $id ), 'saved' => $how );
	}
	$link( $new[ $key . '-fr' ]['id'], $new[ $key . '-en' ]['id'], 'post_page' );
}

// 5. Header and footer templates for the new design.
foreach ( array( 'header' => 'Header', 'footer' => 'Footer' ) as $key => $label ) {
	$ids = array();
	foreach ( array( 'fr' => '-fr', 'en' => '' ) as $lang => $suffix ) {
		$id = wp_insert_post( array(
			'post_type'   => 'elementor_library',
			'post_status' => 'publish',
			'post_title'  => 'RAM v2 ' . $label . ( 'fr' === $lang ? ' (FR)' : '' ),
			'post_name'   => 'ram-v2-' . $key . $suffix,
		), true );
		if ( is_wp_error( $id ) ) {
			return array( 'error' => 'template ' . $key . '-' . $lang . ': ' . $id->get_error_message(), 'log' => $log );
		}
		wp_set_object_terms( $id, 'section', 'elementor_library_type' );
		$how                      = $save( $id, $doc( $key . '-' . $lang ), 'section' );
		$ids[ $lang ]             = (int) $id;
		$new[ $key . '-' . $lang ] = array( 'id' => (int) $id, 'slug' => get_post_field( 'post_name', $id ), 'saved' => $how );
	}
	$link( $ids['fr'], $ids['en'], 'post_elementor_library' );
}

// 6. The new French home page is the front page (WPML serves its English translation at /en/).
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $new['home-fr']['id'] );

// 7. Preview token for the draft pages.
$token = wp_generate_password( 24, false );
update_option( 'ram_preview_token', $token, false );

// 8. Temporary patch images from the earlier French image work.
$deleted = array();
foreach ( range( 124, 138 ) as $id ) {
	if ( 0 === strpos( get_the_title( $id ), 'TEMP patch (FR image build)' ) && wp_delete_attachment( $id, true ) ) {
		$deleted[] = $id;
	}
}

update_option( 'ram_v2_build', array( 'time' => gmdate( 'c' ), 'commit' => $commit, 'new' => $new, 'icons' => $icons, 'theme_backup_stamp' => $stamp ), false );
\Elementor\Plugin::$instance->files_manager->clear_cache();
wp_cache_flush();

return array( 'log' => $log, 'new' => $new, 'deleted_temp_images' => $deleted, 'preview_token' => $token, 'theme_backup_stamp' => $stamp );
