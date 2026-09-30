<?php defined( 'ABSPATH' ) || exit; ?>
</main>

<?php $ram_footer = ram_hf_template( 'ram-footer' ); ?>
<?php if ( $ram_footer ) : ?>
<footer class="ram-site-footer ram-hf"><?php echo $ram_footer; // phpcs:ignore WordPress.Security.EscapeOutput -- Elementor render. ?></footer>
<?php else : ?>
<footer class="ram-footer">
	<div class="ram-footer__inner">
		<div class="ram-footer__cols">
			<div class="ram-footer__col ram-footer__brand"><img src="<?php echo esc_url( ram_asset( 'ram-logo-white.png' ) ); ?>" alt="R.A.M. Management" width="180" loading="lazy"><p>RAM Management - Attorneys at Law provides legal and business consulting services.</p></div>
			<div class="ram-footer__col"><div class="ram-footer__title">Pages</div><?php foreach ( ram_nav_items() as $item ) : ?><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a><?php endforeach; ?></div>
			<div class="ram-footer__col"><div class="ram-footer__title">Services</div><span>Corporate and commercial law</span><span>Business growth strategy</span><span>Non-resident entertainer tax</span></div>
			<div class="ram-footer__col ram-footer__contact"><div class="ram-footer__title">Contact</div><span>5165 Queen Mary Road, suite 405<br>Montreal, Quebec H3W 1X7<br>Canada</span><span>Tel: 514.369.4412<br>Fax: 514.489.5155</span><a class="ram-footer__mail" href="mailto:info@rammanagement.ca">info@rammanagement.ca</a></div>
		</div>
		<div class="ram-footer__legal">© <?php echo esc_html( gmdate( 'Y' ) ); ?> R.A.M. Management – Attorneys at Law</div>
	</div>
</footer>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
