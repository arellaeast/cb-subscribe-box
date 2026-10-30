<?php
/**
 * Subscribe Box Shortcode (loaded by cb-subscribe-box.php)
 *
 * Registers [cb_subscribe], an AJAX subscribe form styled as a rounded
 * pill: off-white email input with a blue SUBSCRIBE button + mailbox icon.
 *
 * Usage:
 *   [cb_subscribe]
 *   [cb_subscribe button="Join the Crew" placeholder="Your email" list="daily"]
 *   [cb_subscribe bg="#8fb8c2"]   (adds the teal wrapper band from the mockup)
 *   [cb_subscribe icon="crossbones"]  (override the button icon per box)
 *
 * Integration:
 *   - Fires `do_action( 'cb_subscribe_new_email', $email, $list )` on success.
 *     Hook the Resend Connector into this to push the address to a Resend
 *     Audience (or hand off to n8n).
 *   - If the official Newsletter plugin (TNP) is active, the address is also
 *     registered there automatically.
 *   - As a safety net, every address is stored in {$wpdb->prefix}cb_subscribers.
 */

defined( 'ABSPATH' ) || exit;

final class CB_Subscribe_Shortcode {

	const NONCE  = 'cb_subscribe_nonce';
	const ACTION = 'cb_subscribe_submit';
	const TABLE  = 'cb_subscribers';

	public static function init() {
		add_shortcode( 'cb_subscribe', [ __CLASS__, 'render' ] );
		add_action( 'wp_ajax_' . self::ACTION, [ __CLASS__, 'handle' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, [ __CLASS__, 'handle' ] );
	}

	/* ---------------------------------------------------------------- */
	/*  Shortcode output                                                */
	/* ---------------------------------------------------------------- */

	public static function render( $atts ) {
		$s = class_exists( 'CB_Subscribe_Settings' )
			? CB_Subscribe_Settings::get()
			: [ 'text_placeholder' => 'Email', 'text_button' => 'SUBSCRIBE', 'text_success' => "You're in! Check your inbox. 📬", 'icon' => 'mailbox' ];

		$atts = shortcode_atts(
			[
				'placeholder' => $s['text_placeholder'],
				'button'      => $s['text_button'],
				'success'     => $s['text_success'],
				'icon'        => isset( $s['icon'] ) ? $s['icon'] : 'mailbox',
				'list'        => 'default',
				'bg'          => '',        // e.g. #8fb8c2 to reproduce the teal band
				'max_width'   => '440px',
			],
			$atts,
			'cb_subscribe'
		);

		$id    = 'cbsub-' . wp_generate_uuid4();
		$nonce = wp_create_nonce( self::NONCE );

		ob_start();
		self::styles_once();
		?>
		<div class="cbsub-wrap" <?php echo $atts['bg'] ? 'style="background:' . esc_attr( $atts['bg'] ) . ';padding:14px;border-radius:14px;"' : ''; ?>>
			<form id="<?php echo esc_attr( $id ); ?>" class="cbsub" style="max-width:<?php echo esc_attr( $atts['max_width'] ); ?>" novalidate>
				<input type="email" name="cbsub_email" class="cbsub__input"
					placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
					autocomplete="email" required />
				<!-- honeypot -->
				<input type="text" name="cbsub_web" class="cbsub__hp" tabindex="-1" autocomplete="off" aria-hidden="true" />
				<button type="submit" class="cbsub__btn">
					<span class="cbsub__btn-label"><?php echo esc_html( $atts['button'] ); ?></span>
					<?php echo class_exists( 'CB_Subscribe_Icons' ) ? CB_Subscribe_Icons::render( $atts['icon'] ) : ''; ?>
				</button>
			</form>
			<p class="cbsub__msg" role="status" aria-live="polite"></p>
		</div>
		<script>
		(function(){
			var form = document.getElementById(<?php echo wp_json_encode( $id ); ?>);
			if (!form) return;
			var wrap = form.parentElement,
				msg  = wrap.querySelector('.cbsub__msg'),
				btn  = form.querySelector('.cbsub__btn');

			form.addEventListener('submit', function(e){
				e.preventDefault();
				var email = form.cbsub_email.value.trim();
				if (!email || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
					show('Please enter a valid email address.', true);
					return;
				}
				btn.disabled = true;
				btn.classList.add('is-busy');

				var body = new URLSearchParams({
					action:  <?php echo wp_json_encode( self::ACTION ); ?>,
					nonce:   <?php echo wp_json_encode( $nonce ); ?>,
					list:    <?php echo wp_json_encode( $atts['list'] ); ?>,
					email:   email,
					web:     form.cbsub_web.value
				});

				fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: body.toString()
				})
				.then(function(r){ return r.json(); })
				.then(function(res){
					if (res && res.success) {
						form.style.display = 'none';
						show(<?php echo wp_json_encode( $atts['success'] ); ?>, false);
					} else {
						show((res && res.data && res.data.message) || 'Something went wrong — try again.', true);
					}
				})
				.catch(function(){ show('Network error — try again.', true); })
				.finally(function(){
					btn.disabled = false;
					btn.classList.remove('is-busy');
				});
			});

			function show(text, isError){
				msg.textContent = text;
				msg.classList.toggle('is-error', !!isError);
				msg.classList.add('is-visible');
			}
		})();
		</script>
		<?php
		return ob_get_clean();
	}

	/** Print the stylesheet once per page, no matter how many boxes render. */
	private static function styles_once() {
		static $done = false;
		if ( $done ) { return; }
		$done = true;

		$s = class_exists( 'CB_Subscribe_Settings' ) ? CB_Subscribe_Settings::get() : [];
		$v = function ( $key, $fallback ) use ( $s ) {
			return isset( $s[ $key ] ) ? $s[ $key ] : $fallback;
		};
		?>
		<style>
		.cbsub-wrap{
			--cbsub-box-bg:      <?php echo esc_html( $v( 'box_bg', '#faf7f2' ) ); ?>;
			--cbsub-input-text:  <?php echo esc_html( $v( 'input_text', '#3a3a3a' ) ); ?>;
			--cbsub-placeholder: <?php echo esc_html( $v( 'placeholder', '#b5aca0' ) ); ?>;
			--cbsub-btn-bg:      <?php echo esc_html( $v( 'btn_bg', '#4f7df9' ) ); ?>;
			--cbsub-btn-hover:   <?php echo esc_html( $v( 'btn_hover', '#3f6ae0' ) ); ?>;
			--cbsub-btn-text:    <?php echo esc_html( $v( 'btn_text', '#ffffff' ) ); ?>;
			--cbsub-box-radius:  <?php echo (int) $v( 'box_radius', 12 ); ?>px;
			--cbsub-btn-radius:  <?php echo (int) $v( 'btn_radius', 10 ); ?>px;
		}
		.cbsub{
			display:flex;align-items:center;gap:8px;
			background:var(--cbsub-box-bg);
			border-radius:var(--cbsub-box-radius);
			padding:6px;
			box-shadow:0 1px 3px rgba(0,0,0,.12);
			margin:0 auto;
		}
		.cbsub__input{
			flex:1 1 auto;min-width:0;border:0;outline:0;background:transparent;
			font-size:15px;color:var(--cbsub-input-text);padding:10px 12px;
		}
		.cbsub__input::placeholder{color:var(--cbsub-placeholder);}
		.cbsub__btn{
			flex:0 0 auto;display:inline-flex;align-items:center;gap:8px;
			background:var(--cbsub-btn-bg);color:var(--cbsub-btn-text);
			border:0;cursor:pointer;
			font-size:14px;font-weight:700;letter-spacing:.04em;
			padding:12px 18px;border-radius:var(--cbsub-btn-radius);
			transition:background .15s ease,transform .1s ease;
		}
		.cbsub__btn:hover{background:var(--cbsub-btn-hover);}
		.cbsub__btn:active{transform:scale(.98);}
		.cbsub__btn:focus-visible{outline:2px solid #1d3fae;outline-offset:2px;}
		.cbsub__btn:disabled{opacity:.6;cursor:wait;}
		.cbsub__btn.is-busy .cbsub__btn-label::after{content:'…';}
		.cbsub__icon{width:18px;height:18px;}
		.cbsub__hp{position:absolute !important;left:-9999px !important;opacity:0;height:0;width:0;}
		.cbsub__msg{
			display:none;margin:8px 4px 0;font-size:14px;color:#2e6b2e;text-align:center;
		}
		.cbsub__msg.is-visible{display:block;}
		.cbsub__msg.is-error{color:#b3402e;}
		@media (max-width:420px){
			.cbsub{flex-wrap:wrap;}
			.cbsub__btn{width:100%;justify-content:center;}
		}
		</style>
		<?php
	}

	/* ---------------------------------------------------------------- */
	/*  AJAX handler                                                    */
	/* ---------------------------------------------------------------- */

	public static function handle() {
		check_ajax_referer( self::NONCE, 'nonce' );

		// Honeypot: bots fill it, humans never see it.
		if ( ! empty( $_POST['web'] ) ) {
			wp_send_json_success(); // pretend it worked, drop silently
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$list  = isset( $_POST['list'] ) ? sanitize_key( wp_unslash( $_POST['list'] ) ) : 'default';

		if ( ! is_email( $email ) ) {
			wp_send_json_error( [ 'message' => 'Please enter a valid email address.' ] );
		}

		// Light rate limit: 5 attempts / 10 min per IP.
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'cbsub_rl_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= 5 ) {
			wp_send_json_error( [ 'message' => 'Too many attempts — try again in a few minutes.' ] );
		}
		set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );

		// 1) Safety-net store (idempotent).
		self::store( $email, $list );

		// 2) If the official Newsletter (TNP) plugin is around, register there too.
		if ( class_exists( 'NewsletterSubscription' ) ) {
			try {
				$sub        = NewsletterSubscription::instance()->get_default_subscription();
				$sub->data->email = $email;
				NewsletterSubscription::instance()->subscribe2( $sub );
			} catch ( Throwable $e ) {
				error_log( 'cb_subscribe: TNP handoff failed: ' . $e->getMessage() );
			}
		}

		// 3) Let the Resend Connector / n8n hook in.
		do_action( 'cb_subscribe_new_email', $email, $list );

		wp_send_json_success();
	}

	/* ---------------------------------------------------------------- */
	/*  Storage                                                         */
	/* ---------------------------------------------------------------- */

	public static function maybe_create_table() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		if ( get_option( 'cbsub_table_ver' ) === '1' ) {
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta(
			"CREATE TABLE {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				email VARCHAR(190) NOT NULL,
				list VARCHAR(64) NOT NULL DEFAULT 'default',
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY email_list (email, list)
			) {$wpdb->get_charset_collate()};"
		);
		update_option( 'cbsub_table_ver', '1' );
	}

	private static function store( $email, $list ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->prefix}" . self::TABLE . ' (email, list, created_at) VALUES (%s, %s, %s)',
				$email,
				$list,
				current_time( 'mysql', true )
			)
		);
	}
}

CB_Subscribe_Shortcode::init();
