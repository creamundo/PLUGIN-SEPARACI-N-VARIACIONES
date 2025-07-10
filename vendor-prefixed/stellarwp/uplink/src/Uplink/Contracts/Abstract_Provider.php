<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by James Kemp on 14-May-2025 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Iconic_WSSV_NS\StellarWP\Uplink\Contracts;

use Iconic_WSSV_NS\StellarWP\ContainerContract\ContainerInterface;
use Iconic_WSSV_NS\StellarWP\Uplink\Config;

abstract class Abstract_Provider implements Provider_Interface {

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Constructor for the class.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( $container = null ) {
		$this->container = $container ?: Config::get_container();
	}

}
