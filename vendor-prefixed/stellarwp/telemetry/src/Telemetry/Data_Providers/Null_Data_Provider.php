<?php
/**
 * A data provider that provides no data, used for testing.
 *
 * @since   2.1.0
 *
 * @package Iconic_WSSV_NS\StellarWP\Telemetry\Data_Providers;
 *
 * @license GPL-2.0-or-later
 * Modified by James Kemp on 14-May-2025 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Iconic_WSSV_NS\StellarWP\Telemetry\Data_Providers;

use Iconic_WSSV_NS\StellarWP\Telemetry\Contracts\Data_Provider;

/**
 * Class Null_Data_Provider.
 *
 * @since   2.1.0
 *
 * @package Iconic_WSSV_NS\StellarWP\Telemetry\Data_Providers;
 */
class Null_Data_Provider implements Data_Provider {

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.1.0
	 */
	public function get_data(): array {
		return [];
	}
}
