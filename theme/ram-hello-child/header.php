<?php defined( 'ABSPATH' ) || exit; ?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php $ram_fr = 'fr' === ram_lang(); ?>
<a class="ram-skip" href="#main"><?php echo $ram_fr ? 'Aller au contenu' : 'Skip to content'; ?></a>

<?php $ram_header = ram_hf_template( 'ram-header' ); ?>
<?php if ( $ram_header ) : ?>
<header class="ram-site-header ram-hf"><?php echo $ram_header; // phpcs:ignore WordPress.Security.EscapeOutput -- Elementor render. ?></header>
<?php else : ?>
<div class="ram-topbar">
	<div class="ram-topbar__inner">
		<div class="ram-topbar__group"><span><?php echo $ram_fr ? 'Tél. :' : 'Tel:'; ?> 514.369.4412</span><span>info@rammanagement.ca</span></div>
		<span><?php echo $ram_fr ? '5165, chemin Queen-Mary, bureau 405, Montréal' : '5165 Queen Mary Road, suite 405, Montreal'; ?></span>
	</div>
</div>

<header class="ram-header">
	<div class="ram-header__inner">
		<a class="ram-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( ram_asset( 'ram-logo-transparent.png' ) ); ?>" alt="<?php echo $ram_fr ? 'R.A.M. Management – Avocats' : 'R.A.M. Management – Attorneys at Law'; ?>" width="160" height="52"></a>
		<button class="ram-menu-toggle" type="button" aria-expanded="false" aria-controls="ram-menu"><span class="ram-sr">Menu</span><span class="ram-menu-toggle__bars" aria-hidden="true"></span></button>
		<div class="ram-header__menu" id="ram-menu">
			<nav class="ram-nav" aria-label="<?php echo $ram_fr ? 'Principal' : 'Main'; ?>">
				<?php foreach ( ram_nav_items() as $item ) : ?>
					<a href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $item['active'] ? ' class="is-active" aria-current="page"' : ''; ?>><?php echo esc_html( $item['label'] ); ?></a>
				<?php endforeach; ?>
			</nav>
			<span class="ram-lang"><?php echo do_shortcode( '[ram_lang_switch]' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the shortcode. ?></span>
			<a class="ram-btn ram-btn--gold ram-header__cta" href="<?php echo esc_url( ram_page_url( 'contact' ) ); ?>"><?php echo $ram_fr ? 'Contactez-nous →' : 'Contact us →'; ?></a>
		</div>
	</div>
</header>
<?php endif; ?>

<main id="main">
