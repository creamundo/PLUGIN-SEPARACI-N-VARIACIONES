<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by James Kemp on 14-May-2025 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Iconic_WSSV_NS\StellarWP\Uplink\Messages;

class Network_Expired extends Message_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get(): string {
		return esc_html__( 'Expired license. Consult your network administrator.', 'iconic-wssv' );
	}
}
