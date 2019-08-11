<?php

namespace Console\App\Deploy;

interface DeployInterface
{
	/**
	 * @return void
	 */
	public function deployApp();

	public function revert();
}