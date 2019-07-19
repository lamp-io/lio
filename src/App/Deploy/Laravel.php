<?php

namespace Console\App\Deploy;

class Laravel extends DeployAbstract implements DeployInterface
{
	public function deployApp(string $appId)
	{

	}

	public function isCorrectApp(): bool
	{
		/** TODO add here checking is app corrected */
		return true;
	}

	public function prepareApacheConfig()
	{

	}

	public function deployDb()
	{

	}

}