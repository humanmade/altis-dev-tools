<?php

namespace Altis\Dev_Tools\Composer;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

class Command_Provider implements CommandProviderCapability {
	public function getCommands() {
		return [
			new Command(),
		];
	}
}
