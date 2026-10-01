// R.A.M. Management – use UAE's Site Logo and Navigation Menu widgets in the UAE header and footer.
// Run once with atarim-execute-php. Creates the French and English WordPress menus (Appearance > Menus,
// linked in WPML), puts the new header/footer blocks into the four UAE templates (old blocks kept in the
// ram_uae_widgets_backup option), updates the child theme's ram-v2.css and ram.js (old files kept as
// <name>-<stamp>.<ext>), then checks the result.

$commit = '__COMMIT__';
$files  = __MANIFEST__;   // path => sha1 of the new file
$old    = __OLD__;        // theme file => sha1 of the live file this change was made from
$shots  = __SHOTS__;      // screenshot requests made after the response is sent
$base   = 'https://raw.githubusercontent.com/gajula-atarim/ram-attorney/' . $commit . '/';
$theme  = get_stylesheet_directory();

foreach ( $old as $rel => $sha ) {
	if ( sha1( file_get_contents( $theme . '/' . $rel ) ) !== $sha ) {
		return array( 'error' => 'live ' . $rel . ' is not the expected version' );
	}
}
$got = array();
foreach ( $files as $path => $sha ) {
	$r    = wp_remote_get( $base . $path, array( 'timeout' => 15 ) );
	$body = is_wp_error( $r ) ? '' : wp_remote_retrieve_body( $r );
	if ( sha1( $body ) !== $sha ) {
		return array( 'error' => 'download failed or checksum mismatch: ' . $path );
	}
	$got[ $path ] = $body;
}

// 1. Menus, one per language, with the three pages.
$menus = array(
	'fr' => array( 'RAM Menu (FR)', array( 165 => 'Accueil', 169 => 'À propos', 173 => 'Contact' ) ),
	'en' => array( 'RAM Menu', array( 167 => 'Home', 171 => 'About', 175 => 'Contact' ) ),
);
$menu_ids = array();
foreach ( $menus as $lang => $m ) {
	$obj = wp_get_nav_menu_object( $m[0] );
	$id  = $obj ? (int) $obj->term_id : (int) wp_create_nav_menu( $m[0] );
	if ( ! $id ) {
		return array( 'error' => 'could not create menu ' . $m[0] );
	}
	if ( ! wp_get_nav_menu_items( $id ) ) {
		$pos = 1;
		foreach ( $m[1] as $page => $title ) {
			$item = wp_update_nav_menu_item( $id, 0, array(
				'menu-item-title'     => $title,
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $page,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-position'  => $pos++,
			) );
			do_action( 'wpml_set_element_language_details', array( 'element_id' => $item, 'element_type' => 'post_nav_menu_item', 'trid' => false, 'language_code' => $lang, 'source_language_code' => null ) );
		}
	}
	$menu_ids[ $lang ] = $id;
}
$ttid = function ( $term_id ) {
	$t = get_term( $term_id, 'nav_menu' );
	return (int) $t->term_taxonomy_id;
};
do_action( 'wpml_set_element_language_details', array( 'element_id' => $ttid( $menu_ids['fr'] ), 'element_type' => 'tax_nav_menu', 'trid' => false, 'language_code' => 'fr', 'source_language_code' => null ) );
$trid = apply_filters( 'wpml_element_trid', null, $ttid( $menu_ids['fr'] ), 'tax_nav_menu' );
do_action( 'wpml_set_element_language_details', array( 'element_id' => $ttid( $menu_ids['en'] ), 'element_type' => 'tax_nav_menu', 'trid' => $trid, 'language_code' => 'en', 'source_language_code' => 'fr' ) );

// The widget's menu list is keyed by slug or by ID depending on the UAE version.
$nav_src = file_get_contents( WP_PLUGIN_DIR . '/header-footer-elementor/inc/widgets-manager/widgets/navigation-menu/navigation-menu.php' );
$by_slug = (bool) preg_match( '/function get_available_menus\(\).*?\$menu->slug/s', $nav_src );
$menu_key = array();
foreach ( $menu_ids as $lang => $id ) {
	$menu_key[ $lang ] = $by_slug ? get_term( $id, 'nav_menu' )->slug : (string) $id;
}

// 2. Header and footer blocks into the UAE templates (French 187/189, English 188/190).
$targets = array( 187 => 'uae-header-fr', 188 => 'uae-header-en', 189 => 'uae-footer-fr', 190 => 'uae-footer-en' );
$backup  = array( 'time' => gmdate( 'c' ) );
foreach ( $targets as $id => $doc ) {
	if ( 'elementor-hf' !== get_post_type( $id ) ) {
		return array( 'error' => 'template ' . $id . ' missing' );
	}
	$backup[ $id ] = get_post_meta( $id, '_elementor_data', true );
}
update_option( 'ram_uae_widgets_backup', $backup, false );
foreach ( $targets as $id => $doc ) {
	$lang = substr( $doc, -2 );
	$json = str_replace( '"__MENU__"', wp_json_encode( $menu_key[ $lang ] ), $got[ 'build/elementor/' . $doc . '.json' ] );
	if ( ! is_array( json_decode( $json, true ) ) ) {
		return array( 'error' => 'bad JSON for ' . $doc );
	}
	update_post_meta( $id, '_elementor_data', wp_slash( $json ) );
	delete_post_meta( $id, '_elementor_css' );
	delete_post_meta( $id, '_elementor_element_cache' );
}

// 3. Theme files.
$stamp = gmdate( 'Ymd-His' );
foreach ( array( 'assets/ram-v2.css', 'assets/ram.js' ) as $rel ) {
	$dest = $theme . '/' . $rel;
	$info = pathinfo( $dest );
	copy( $dest, $info['dirname'] . '/' . $info['filename'] . '-' . $stamp . '.' . $info['extension'] );
	file_put_contents( $dest, $got[ 'theme/ram-hello-child/' . $rel ] );
}

\Elementor\Plugin::$instance->files_manager->clear_cache();
wp_cache_flush();

// 4. Check: each template prints the UAE widgets with its own language's menu.
$check = array();
foreach ( $targets as $id => $doc ) {
	$lang = substr( $doc, -2 );
	do_action( 'wpml_switch_language', $lang );
	$html = (string) \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $id, false );
	preg_match_all( '/<a[^>]+class="hfe-menu-item"[^>]*>([^<]+)</', $html, $items );
	preg_match_all( '/<a[^>]+href="([^"]+)"[^>]*class="hfe-menu-item"|<a[^>]+class="hfe-menu-item"[^>]*href="([^"]+)"/', $html, $hrefs );
	$check[ $doc ] = array(
		'site_logo' => false !== strpos( $html, 'hfe-site-logo-img' ),
		'menu'      => array_map( 'trim', $items[1] ),
		'links'     => array_values( array_filter( array_merge( $hrefs[1], $hrefs[2] ) ) ),
	);
}
do_action( 'wpml_switch_language', apply_filters( 'wpml_default_language', null ) );

// 5. After the response: request the comparison screenshots.
register_shutdown_function( function () use ( $shots ) {
	if ( function_exists( 'fastcgi_finish_request' ) ) {
		fastcgi_finish_request();
	}
	ignore_user_abort( true );
	@set_time_limit( 300 );
	foreach ( $shots as $url ) {
		wp_remote_get( $url, array( 'timeout' => 90 ) );
	}
} );

return array( 'menus' => $menu_ids, 'menu_key' => $menu_key, 'check' => $check, 'theme_backup_stamp' => $stamp );
