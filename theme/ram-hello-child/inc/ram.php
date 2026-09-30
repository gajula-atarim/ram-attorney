<?php
/**
 * R.A.M. Management – Hello Elementor child theme setup.
 * Header, footer and the contact form come from this child theme; page content is built in Elementor.
 */

defined( 'ABSPATH' ) || exit;

// Recipient for the contact form. Change here (or in Contact > Contact Forms) when the final address is known.
const RAM_CONTACT_RECIPIENT = 'test@gmail.com';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'script', 'style', 'gallery', 'caption' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();

	wp_enqueue_style( 'ram-fonts', 'https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500&family=Source+Serif+4:opsz,wght@8..60,400;8..60,500;8..60,600&display=swap', array(), null );
	// Load after Hello Elementor's reset/theme styles so ours win on equal specificity.
	$deps = array( 'ram-fonts' );
	foreach ( array( 'hello-elementor', 'hello-elementor-theme-style', 'hello-elementor-header-footer' ) as $handle ) {
		if ( wp_style_is( $handle, 'registered' ) ) {
			$deps[] = $handle;
		}
	}
	wp_enqueue_style( 'ram-site', $uri . '/assets/site.css', $deps, filemtime( $dir . '/assets/site.css' ) );
	// Styles for the native Elementor widgets (icons, icon lists, map, slider dots).
	wp_enqueue_style( 'ram-extra', $uri . '/assets/ram-extra.css', array( 'ram-site' ), filemtime( $dir . '/assets/ram-extra.css' ) );
	// Header and footer built in Elementor (Templates > Saved Templates > RAM Header / RAM Footer).
	wp_enqueue_style( 'ram-hf', $uri . '/assets/ram-hf.css', array( 'ram-extra' ), filemtime( $dir . '/assets/ram-hf.css' ) );
	wp_enqueue_script( 'ram-site', $uri . '/assets/ram.js', array(), filemtime( $dir . '/assets/ram.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );

	// The pages are hand-built; block theme global styles would only fight the design.
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}, 20 );

// The design sets its own fonts and colours; keep Elementor's default kit fonts/colours out of the way.
add_filter( 'pre_option_elementor_disable_color_schemes', function () {
	return 'yes';
} );
add_filter( 'pre_option_elementor_disable_typography_schemes', function () {
	return 'yes';
} );

// SVG icons: Elementor's "Enable Unfiltered File Uploads" only covers Elementor's own uploader.
// While that setting is on, let users who can upload media add SVGs to the Media Library as well.
function ram_svg_uploads_allowed() {
	return current_user_can( 'upload_files' ) && '1' === (string) get_option( 'elementor_unfiltered_files_upload' );
}
add_filter( 'upload_mimes', function ( $mimes ) {
	if ( ram_svg_uploads_allowed() ) {
		$mimes['svg'] = 'image/svg+xml';
	}
	return $mimes;
} );
add_filter( 'wp_check_filetype_and_ext', function ( $data, $file, $filename ) {
	if ( ram_svg_uploads_allowed() && 'svg' === strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
		$data['ext']  = 'svg';
		$data['type'] = 'image/svg+xml';
	}
	return $data;
}, 10, 3 );

add_filter( 'wp_resource_hints', function ( $urls, $relation ) {
	if ( 'preconnect' === $relation ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}, 10, 2 );

/* ---------- Languages (WPML) ---------- */

// Current language code ("en" when WPML is off).
function ram_lang() {
	$lang = apply_filters( 'wpml_current_language', null );
	return $lang ? $lang : 'en';
}

// "EN / FR" switch in the header, linked to the translation of the current page (or that language's home page).
add_shortcode( 'ram_lang_switch', function () {
	$langs = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0, 'orderby' => 'code', 'order' => 'asc' ) );
	if ( empty( $langs ) || count( $langs ) < 2 ) {
		// WPML not set up yet: keep the original static switch.
		return '<span class="is-active">EN</span><span>/</span><a href="#" lang="fr">FR</a>';
	}
	$out = array();
	foreach ( $langs as $code => $lang ) {
		$label = esc_html( strtoupper( $code ) );
		$name  = esc_attr( $lang['native_name'] );
		if ( $lang['active'] ) {
			$out[] = '<span class="is-active" lang="' . esc_attr( $code ) . '" aria-current="true">' . $label . '</span>';
		} else {
			$out[] = '<a href="' . esc_url( $lang['url'] ) . '" lang="' . esc_attr( $code ) . '" hreflang="' . esc_attr( $code ) . '" title="' . $name . '" aria-label="' . $name . '">' . $label . '</a>';
		}
	}
	return implode( '<span aria-hidden="true">/</span>', $out );
} );

/* ---------- Contact Form 7 ---------- */

add_filter( 'wpcf7_autop_or_not', '__return_false' );

function ram_cf7_form_markup() {
	return '<div class="ram-field ram-ico-name"><label><span class="ram-sr">Name</span>[text* your-name autocomplete:name placeholder "Name"]</label></div>
<div class="ram-row"><div class="ram-field ram-ico-email"><label><span class="ram-sr">Email</span>[email* your-email autocomplete:email placeholder "Email"]</label></div><div class="ram-field ram-ico-phone"><label><span class="ram-sr">Phone</span>[tel your-phone autocomplete:tel placeholder "Phone"]</label></div></div>
<div class="ram-field ram-field-ta ram-ico-msg"><label><span class="ram-sr">Message</span>[textarea* your-message x6 placeholder "Message"]</label></div>
[submit class:ram-submit "Send message →"]';
}

// Create the site's contact form once, the first time Contact Form 7 is available.
add_action( 'init', function () {
	if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
		return;
	}
	$id = (int) get_option( 'ram_cf7_id' );
	if ( $id && get_post( $id ) ) {
		return;
	}

	$form  = WPCF7_ContactForm::get_template( array( 'title' => 'RAM Contact Form' ) );
	$props = $form->get_properties();

	$props['form'] = ram_cf7_form_markup();
	$props['mail'] = array_merge( $props['mail'], array(
		'recipient'          => RAM_CONTACT_RECIPIENT,
		'subject'            => '[_site_title] website inquiry from [your-name]',
		'additional_headers' => 'Reply-To: [your-email]',
		'body'               => "Name: [your-name]\nEmail: [your-email]\nPhone: [your-phone]\n\nMessage:\n[your-message]\n\n--\nSent from the contact form on [_site_url]",
	) );
	$props['mail_2']['active'] = false;

	$form->set_properties( $props );
	$id = $form->save();
	if ( $id ) {
		update_option( 'ram_cf7_id', (int) $id );
	}
}, 20 );

// Each language uses its own translation of the form (Contact > Contact Forms, linked in WPML).
add_shortcode( 'ram_contact_form', function () {
	$id = (int) get_option( 'ram_cf7_id' );
	if ( $id ) {
		$id = (int) apply_filters( 'wpml_object_id', $id, 'wpcf7_contact_form', true );
	}
	if ( ! $id || ! shortcode_exists( 'contact-form-7' ) ) {
		return '';
	}
	return do_shortcode( '[contact-form-7 id="' . $id . '" html_class="ram-form"]' );
} );

/* ---------- Header helpers ---------- */

/**
 * Rendered Elementor template (Templates > Saved Templates) by slug, e.g. "ram-header".
 * "<slug>-<lang>" (e.g. "ram-header-fr") is used for that language when it exists; otherwise "<slug>".
 * Returns '' when Elementor is off or the template is missing, so header.php / footer.php
 * fall back to the built-in markup.
 */
function ram_hf_template( $slug ) {
	if ( ! did_action( 'elementor/loaded' ) ) {
		return '';
	}
	$template = get_page_by_path( $slug . '-' . ram_lang(), OBJECT, 'elementor_library' );
	if ( ! $template || 'publish' !== $template->post_status ) {
		$template = get_page_by_path( $slug, OBJECT, 'elementor_library' );
	}
	if ( ! $template || 'publish' !== $template->post_status ) {
		return '';
	}
	return (string) \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template->ID, true );
}

// Mobile menu button used by the RAM Header template (a Shortcode block).
add_shortcode( 'ram_menu_toggle', function () {
	return '<button class="ram-menu-toggle" type="button" aria-expanded="false" aria-controls="ram-menu"><span class="ram-sr">Menu</span><span class="ram-menu-toggle__bars" aria-hidden="true"></span></button>';
} );

// Current year for the footer copyright line.
add_shortcode( 'ram_year', function () {
	return esc_html( gmdate( 'Y' ) );
} );

// Page URL in the current language (the translation when there is one).
function ram_page_url( $path ) {
	$page = get_page_by_path( $path );
	if ( ! $page ) {
		return home_url( '/' . $path . '/' );
	}
	return get_permalink( apply_filters( 'wpml_object_id', $page->ID, 'page', true ) );
}

function ram_nav_items() {
	$fr    = 'fr' === ram_lang();
	$about = get_page_by_path( 'about' );
	$cont  = get_page_by_path( 'contact' );
	return array(
		array( 'label' => $fr ? 'Accueil' : 'Home', 'url' => apply_filters( 'wpml_home_url', home_url( '/' ) ), 'active' => is_front_page() ),
		array( 'label' => $fr ? 'À propos' : 'About', 'url' => ram_page_url( 'about' ), 'active' => $about && is_page( apply_filters( 'wpml_object_id', $about->ID, 'page', true ) ) ),
		array( 'label' => $fr ? 'Nous joindre' : 'Contact', 'url' => ram_page_url( 'contact' ), 'active' => $cont && is_page( apply_filters( 'wpml_object_id', $cont->ID, 'page', true ) ) ),
	);
}

function ram_asset( $file ) {
	// Media library uploads (September 2026).
	return content_url( '/uploads/2026/09/' . $file );
}
