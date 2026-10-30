<?php
/**
 * Settings page for CB Subscribe Box (Settings → Subscribe Box).
 *
 * Lets you style the box from the admin: colors, corner radius, and the
 * default text (placeholder / button label / success message). Values are
 * stored in the `cbsub_settings` option and applied via CSS variables on
 * the front end. Shortcode attributes still win over the saved text.
 */

defined( 'ABSPATH' ) || exit;

final class CB_Subscribe_Settings {

	const OPTION = 'cbsub_settings';

	/** Defaults — also the fallback on the front end. */
	public static function defaults() {
		return [
			// Colors.
			'box_bg'        => '#faf7f2',
			'input_text'    => '#3a3a3a',
			'placeholder'   => '#b5aca0',
			'btn_bg'        => '#4f7df9',
			'btn_hover'     => '#3f6ae0',
			'btn_text'      => '#ffffff',
			// Corner radius (px).
			'box_radius'    => 12,
			'btn_radius'    => 10,
			// Icon.
			'icon'          => 'mailbox',
			// Text.
			'text_placeholder' => 'Email',
			'text_button'      => 'SUBSCRIBE',
			'text_success'     => "You're in! Check your inbox. 📬",
		];
	}

	public static function get() {
		return wp_parse_args( get_option( self::OPTION, [] ), self::defaults() );
	}

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'register' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
	}

	public static function menu() {
		add_options_page(
			'Subscribe Box',
			'Subscribe Box',
			'manage_options',
			'cb-subscribe-box',
			[ __CLASS__, 'page' ]
		);
	}

	public static function register() {
		register_setting( 'cbsub_settings_group', self::OPTION, [
			'type'              => 'array',
			'sanitize_callback' => [ __CLASS__, 'sanitize' ],
			'default'           => self::defaults(),
		] );
	}

	public static function sanitize( $in ) {
		$d   = self::defaults();
		$out = [];

		foreach ( [ 'box_bg', 'input_text', 'placeholder', 'btn_bg', 'btn_hover', 'btn_text' ] as $k ) {
			$c         = isset( $in[ $k ] ) ? sanitize_hex_color( $in[ $k ] ) : '';
			$out[ $k ] = $c ? $c : $d[ $k ];
		}

		foreach ( [ 'box_radius', 'btn_radius' ] as $k ) {
			$out[ $k ] = isset( $in[ $k ] ) ? max( 0, min( 60, (int) $in[ $k ] ) ) : $d[ $k ];
		}

		$icon_keys  = class_exists( 'CB_Subscribe_Icons' ) ? CB_Subscribe_Icons::keys() : [ 'mailbox' ];
		$out['icon'] = ( isset( $in['icon'] ) && in_array( $in['icon'], $icon_keys, true ) ) ? $in['icon'] : $d['icon'];

		foreach ( [ 'text_placeholder', 'text_button', 'text_success' ] as $k ) {
			$v         = isset( $in[ $k ] ) ? sanitize_text_field( $in[ $k ] ) : '';
			$out[ $k ] = ( '' !== $v ) ? $v : $d[ $k ];
		}

		return $out;
	}

	public static function assets( $hook ) {
		if ( 'settings_page_cb-subscribe-box' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	/* ---------------------------------------------------------------- */
	/*  Page                                                            */
	/* ---------------------------------------------------------------- */

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = self::get();
		?>
		<div class="wrap">
			<style>.cbsub-icon-swatch{width:22px;height:22px;}</style>
			<h1>Subscribe Box</h1>
			<p>Style the <code>[cb_subscribe]</code> box. Changes preview live below; shortcode attributes (e.g. <code>button="Join"</code>) still override the saved text on individual pages.</p>

			<div style="display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start;">

			<form method="post" action="options.php" style="flex:1 1 420px;max-width:560px;">
				<?php settings_fields( 'cbsub_settings_group' ); ?>

				<h2 class="title">Colors</h2>
				<table class="form-table" role="presentation">
					<?php
					self::color_row( 'box_bg',      'Box background',      $s );
					self::color_row( 'input_text',  'Input text',          $s );
					self::color_row( 'placeholder', 'Placeholder text',    $s );
					self::color_row( 'btn_bg',      'Button',              $s );
					self::color_row( 'btn_hover',   'Button hover',        $s );
					self::color_row( 'btn_text',    'Button text',         $s );
					?>
				</table>

				<h2 class="title">Corner radius</h2>
				<table class="form-table" role="presentation">
					<?php
					self::number_row( 'box_radius', 'Box radius (px)',    $s );
					self::number_row( 'btn_radius', 'Button radius (px)', $s );
					?>
				</table>

				<h2 class="title">Icon</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Button icon</th>
						<td>
							<div class="cbsub-icon-grid" style="display:flex;flex-wrap:wrap;gap:10px;">
								<?php foreach ( CB_Subscribe_Icons::all() as $key => $icon ) : ?>
									<label style="display:flex;flex-direction:column;align-items:center;gap:6px;border:1px solid #dcdcde;border-radius:8px;padding:10px 14px;cursor:pointer;<?php echo ( $s['icon'] === $key ) ? 'border-color:#4f7df9;box-shadow:0 0 0 1px #4f7df9;' : ''; ?>">
										<input type="radio" class="cbsub-icon-radio" name="<?php echo esc_attr( self::OPTION ); ?>[icon]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $s['icon'], $key ); ?> style="margin:0 0 2px;" />
										<span style="color:#3a3a3a;"><?php echo CB_Subscribe_Icons::render( $key, 'cbsub-icon-swatch' ); ?></span>
										<span style="font-size:12px;color:#646970;"><?php echo esc_html( $icon['label'] ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
							<p class="description">More icons can be added later — this list will grow without needing a plugin update.</p>
						</td>
					</tr>
				</table>

				<h2 class="title">Text</h2>
				<table class="form-table" role="presentation">
					<?php
					self::text_row( 'text_placeholder', 'Placeholder',     $s );
					self::text_row( 'text_button',      'Button label',    $s );
					self::text_row( 'text_success',     'Success message', $s );
					?>
				</table>

				<?php submit_button(); ?>
			</form>

			<div style="flex:1 1 360px;max-width:520px;position:sticky;top:60px;">
				<h2 class="title">Preview</h2>
				<div id="cbsub-preview-band" style="background:#8fb8c2;padding:24px;border-radius:14px;">
					<div id="cbsub-preview" style="display:flex;align-items:center;gap:8px;padding:6px;box-shadow:0 1px 3px rgba(0,0,0,.12);background:<?php echo esc_attr( $s['box_bg'] ); ?>;border-radius:<?php echo (int) $s['box_radius']; ?>px;">
						<span id="cbsub-preview-ph" style="flex:1;padding:10px 12px;font-size:15px;color:<?php echo esc_attr( $s['placeholder'] ); ?>;"><?php echo esc_html( $s['text_placeholder'] ); ?></span>
						<span id="cbsub-preview-btn" style="display:inline-flex;align-items:center;gap:8px;font-size:14px;font-weight:700;letter-spacing:.04em;padding:12px 18px;background:<?php echo esc_attr( $s['btn_bg'] ); ?>;color:<?php echo esc_attr( $s['btn_text'] ); ?>;border-radius:<?php echo (int) $s['btn_radius']; ?>px;">
							<span id="cbsub-preview-label"><?php echo esc_html( $s['text_button'] ); ?></span>
							<span id="cbsub-preview-icon" style="display:inline-flex;width:18px;height:18px;"><?php echo CB_Subscribe_Icons::render( $s['icon'], '' ); ?></span>
						</span>
					</div>
				</div>
				<p class="description" style="margin-top:8px;">The teal band is just the preview backdrop — on the site the box sits on your page background (or pass <code>bg="#8fb8c2"</code> in the shortcode for a band like this).</p>
			</div>

			</div>
		</div>

		<script>
		var CBSUB_ICONS = <?php
			$icon_svgs = [];
			foreach ( CB_Subscribe_Icons::keys() as $k ) {
				$icon_svgs[ $k ] = CB_Subscribe_Icons::render( $k, '' );
			}
			echo wp_json_encode( $icon_svgs );
		?>;
		jQuery(function($){
			var prev = {
				box:   $('#cbsub-preview'),
				ph:    $('#cbsub-preview-ph'),
				btn:   $('#cbsub-preview-btn'),
				label: $('#cbsub-preview-label'),
				icon:  $('#cbsub-preview-icon')
			};

			function apply(id, val){
				switch(id){
					case 'box_bg':        prev.box.css('background', val); break;
					case 'input_text':    /* only visible when typing on the front end */ break;
					case 'placeholder':   prev.ph.css('color', val); break;
					case 'btn_bg':        prev.btn.css('background', val); break;
					case 'btn_text':      prev.btn.css('color', val); break;
					case 'box_radius':    prev.box.css('border-radius', parseInt(val || 0, 10) + 'px'); break;
					case 'btn_radius':    prev.btn.css('border-radius', parseInt(val || 0, 10) + 'px'); break;
					case 'text_placeholder': prev.ph.text(val); break;
					case 'text_button':      prev.label.text(val); break;
					case 'icon':             prev.icon.html(CBSUB_ICONS[val] || ''); break;
				}
			}

			$('.cbsub-icon-radio').on('change', function(){
				apply('icon', $(this).val());
				$('.cbsub-icon-grid label').css({'border-color':'#dcdcde','box-shadow':'none'});
				$(this).closest('label').css({'border-color':'#4f7df9','box-shadow':'0 0 0 1px #4f7df9'});
			});

			$('.cbsub-color').wpColorPicker({
				change: function(e, ui){ apply($(this).data('key'), ui.color.toString()); },
				clear:  function(){ apply($(this).data('key'), ''); }
			});

			$('.cbsub-field').on('input change', function(){
				apply($(this).data('key'), $(this).val());
			});

			// Hover swatch on the preview button.
			var hover = $('[data-key="btn_hover"]');
			prev.btn.on('mouseenter', function(){ $(this).css('background', hover.val()); })
			        .on('mouseleave', function(){ $(this).css('background', $('[data-key="btn_bg"]').val()); });
		});
		</script>
		<?php
	}

	private static function color_row( $key, $label, $s ) {
		printf(
			'<tr><th scope="row"><label for="cbsub-%1$s">%2$s</label></th>
			 <td><input type="text" id="cbsub-%1$s" class="cbsub-color" data-key="%1$s" name="%3$s[%1$s]" value="%4$s" /></td></tr>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( self::OPTION ),
			esc_attr( $s[ $key ] )
		);
	}

	private static function number_row( $key, $label, $s ) {
		printf(
			'<tr><th scope="row"><label for="cbsub-%1$s">%2$s</label></th>
			 <td><input type="number" min="0" max="60" step="1" id="cbsub-%1$s" class="small-text cbsub-field" data-key="%1$s" name="%3$s[%1$s]" value="%4$s" /></td></tr>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( self::OPTION ),
			(int) $s[ $key ]
		);
	}

	private static function text_row( $key, $label, $s ) {
		printf(
			'<tr><th scope="row"><label for="cbsub-%1$s">%2$s</label></th>
			 <td><input type="text" id="cbsub-%1$s" class="regular-text cbsub-field" data-key="%1$s" name="%3$s[%1$s]" value="%4$s" /></td></tr>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( self::OPTION ),
			esc_attr( $s[ $key ] )
		);
	}
}

CB_Subscribe_Settings::init();
