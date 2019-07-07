<?php

namespace Console\App\Helpers;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Compiler
{
	const PHAR_NAME = 'lio.phar';

	public function compile()
	{
		if (file_exists($this->getAppRoot() . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . self::PHAR_NAME)) {
			unlink($this->getAppRoot() . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . self::PHAR_NAME);
		}
		$phar = new \Phar(
			$this->getAppRoot() . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . self::PHAR_NAME,
			0,
			self::PHAR_NAME
		);
		$phar->setSignatureAlgorithm(\Phar::SHA256);

		$finderSort = function (\SplFileInfo $a, \SplFileInfo $b) {
			return strcmp(strtr($a->getRealPath(), '\\', '/'), strtr($b->getRealPath(), '\\', '/'));
		};

		$finder = new Finder();


		$finder
			->files()
			->ignoreVCS(true)
			->name('*.php')
			->notName('Compiler.php')
			->notName('build.php')
			->in($this->getAppRoot())
			->sort($finderSort);

		foreach ($finder as $file) {
			$this->addFile($phar, $file);
		}

		$this->addBin($phar);
		// Stubs
		$phar->setStub($this->getStub());
		$phar->stopBuffering();
		unset($phar);
	}


	private function addBin(\Phar $phar)
	{
		$content = file_get_contents($this->getAppRoot() . '/bin/lio');
		$phar->addFromString('bin/lio', $content);
	}

	private function addFile(\Phar $phar, SplFileInfo $file)
	{
		$path = $file->getRelativePathname();
		$content = file_get_contents((string)$file);
		$phar->addFromString($path, $content);
	}


	private function getAppRoot()
	{
		return dirname(__DIR__, 3);
	}

	private function getStub()
	{
		$stub = <<<'EOF'
			#!/usr/bin/env php
			<?php
			Phar::mapPhar('lio.phar');
EOF;
		return $stub . <<<'EOF'
			require 'phar://lio.phar/bin/lio';
			__HALT_COMPILER();
EOF;

	}
}