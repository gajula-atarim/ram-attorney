// R.A.M. Management – client revision 1 (Home hero text, About intro / blue section / team).
// Run once with atarim-execute-php. Replaces the Elementor content of the Home (165 FR, 167 EN) and
// About (169 FR, 171 EN) pages with the rebuilt blocks and updates ram-v2.css. The previous page
// content is kept in the ram_client_rev1_backup option and the old stylesheet as
// assets/ram-v2-<stamp>.css. Nothing else changes.

$commit = '__COMMIT__';
$files  = __MANIFEST__;
$shots  = __SHOTS__;
$base   = 'https://raw.githubusercontent.com/gajula-atarim/ram-attorney/' . $commit . '/';
$css    = get_stylesheet_directory() . '/assets/ram-v2.css';
if ( sha1( file_get_contents( $css ) ) !== '__OLD_CSS__' ) {
	return array( 'error' => 'live ram-v2.css is not the expected version' );
}
$got = array();
foreach ( $files as $path => $sha ) {
	$r    = wp_remote_get( $base . $path, array( 'timeout' => 8 ) );
	$body = is_wp_error( $r ) ? '' : wp_remote_retrieve_body( $r );
	if ( sha1( $body ) !== $sha ) {
		return array( 'error' => 'download failed or checksum mismatch: ' . $path );
	}
	$got[ $path ] = $body;
}
// The pages must still hold the blocks this revision was made from (no edits since).
$pages = array( 165 => array( 'home-fr', 'rv-h1' ), 167 => array( 'home-en', 'rv-h1' ), 169 => array( 'about-fr', 'rv-quote-mark' ), 171 => array( 'about-en', 'rv-quote-mark' ) );
$backup = array( 'time' => gmdate( 'c' ) );
foreach ( $pages as $id => $v ) {
	$live = (string) get_post_meta( $id, '_elementor_data', true );
	if ( false === strpos( $live, $v[1] ) ) {
		return array( 'error' => 'page ' . $id . ' was changed since the build; nothing updated' );
	}
	$backup[ $id ] = $live;
}
update_option( 'ram_client_rev1_backup', $backup, false );
$icons = ( get_option( 'ram_v2_build' )['icons'] ?? array() );
foreach ( $pages as $id => $v ) {
	$json = $got[ 'build/elementor/' . $v[0] . '.json' ];
	foreach ( $icons as $n => $i ) {
		$json = str_replace( '"__ICON_' . $n . '_ID__"', (string) $i['id'], $json );
		$json = str_replace( '__ICON_' . $n . '_URL__', $i['url'], $json );
	}
	if ( false !== strpos( $json, '__ICON_' ) || ! is_array( json_decode( $json, true ) ) ) {
		return array( 'error' => 'could not prepare ' . $v[0] );
	}
	update_post_meta( $id, '_elementor_data', wp_slash( $json ) );
	delete_post_meta( $id, '_elementor_css' );
	delete_post_meta( $id, '_elementor_element_cache' );
	clean_post_cache( $id );
}
$stamp = gmdate( 'Ymd-His' );
copy( $css, dirname( $css ) . '/ram-v2-' . $stamp . '.css' );
file_put_contents( $css, $got['theme/ram-hello-child/assets/ram-v2.css'] );
delete_post_meta_by_key( '_elementor_element_cache' );
\Elementor\Plugin::$instance->files_manager->clear_cache();
wp_cache_flush();
$reqs = array();
foreach ( $shots as $u ) {
	$reqs[] = array( 'url' => $u, 'type' => 'GET' );
}
try {
	\WpOrg\Requests\Requests::request_multiple( $reqs, array( 'timeout' => 6, 'connect_timeout' => 4 ) );
} catch ( \Throwable $e ) {
}
return array( 'updated' => array_keys( $pages ), 'css_backup' => 'assets/ram-v2-' . $stamp . '.css' );
