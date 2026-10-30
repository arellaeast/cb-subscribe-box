<?php
/**
 * Icon registry for CB Subscribe Box.
 *
 * Centralizes every icon the button can use, so adding a new one later is a
 * single entry here — nothing else in the plugin needs to change. Each icon
 * is plain inline SVG (no external requests, themeable via currentColor).
 */

defined( 'ABSPATH' ) || exit;

final class CB_Subscribe_Icons {

	/**
	 * Registry of available icons.
	 *
	 * 'label' is shown in the admin picker. 'svg' is the inner markup of a
	 * 24x24 viewBox — CB_Subscribe_Shortcode wraps it with the <svg> tag,
	 * class, and shared attributes.
	 */
	public static function all() {
		return [
			'mailbox' => [
				'label' => 'Mailbox',
				'svg'   => '<path d="M22 17a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9.5A5.5 5.5 0 0 1 7.5 4H20a2 2 0 0 1 2 2Z"/>
					<path d="M13 9.5a5.5 5.5 0 0 0-5.5-5.5"/>
					<path d="M2 13h11"/>
					<path d="M16 4h2v4h-2z"/>',
			],
			'crossbones' => [
				'label' => 'Skull & Crossbones',
				'svg'   => '<circle cx="12" cy="8" r="5.5"/>
					<circle cx="9.7" cy="7.3" r="1"/>
					<circle cx="14.3" cy="7.3" r="1"/>
					<path d="M10.3 10.2c.5.6 1.9.6 2.4 0"/>
					<path d="M10 12.5 8.7 14"/>
					<path d="M14 12.5l1.3 1.5"/>
					<path d="m3 18 6.5-4M3 22l6.5-4"/>
					<path d="m21 18-6.5-4M21 22l-6.5-4"/>',
				'fill'  => true,
			],
			'envelope' => [
				'label' => 'Envelope',
				'svg'   => '<rect x="2" y="5" width="20" height="14" rx="2"/>
					<path d="m2 7 10 7 10-7"/>',
			],
			'paper-plane' => [
				'label' => 'Paper Plane',
				'svg'   => '<path d="m22 2-11 11"/>
					<path d="M22 2 15 22l-4-9-9-4Z"/>',
			],
			'arrow' => [
				'label' => 'Arrow',
				'svg'   => '<path d="M5 12h14"/>
					<path d="m13 6 6 6-6 6"/>',
			],
			'none' => [
				'label' => 'No icon',
				'svg'   => '',
			],
		];
	}

	public static function keys() {
		return array_keys( self::all() );
	}

	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : $all['mailbox'];
	}

	/** Full <svg> markup for the given icon key, ready to echo. */
	public static function render( $key, $class = 'cbsub__icon' ) {
		$icon = self::get( $key );
		if ( '' === $icon['svg'] ) {
			return '';
		}
		$fill = ! empty( $icon['fill'] ) ? 'currentColor' : 'none';
		return sprintf(
			'<svg class="%1$s" viewBox="0 0 24 24" fill="%2$s" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%3$s</svg>',
			esc_attr( $class ),
			esc_attr( $fill ),
			$icon['svg'] // trusted, hardcoded markup from this file only.
		);
	}
}
