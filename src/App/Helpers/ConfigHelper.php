<?php

namespace Console\App\Helpers;

use InvalidArgumentException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ConfigHelper
{
	const LAMP_IO_CONFIG = 'lamp.io.yaml';

	/**
	 * @var array
	 */
	protected $config = [];

	protected $appPath;

	public function __construct(string $pwd)
	{
		try {
			$this->appPath = $pwd . DIRECTORY_SEPARATOR;
			$this->config = Yaml::parseFile($this->appPath . self::LAMP_IO_CONFIG);
		} catch (ParseException $parseException) {
			echo $parseException->getMessage();
			file_put_contents($pwd . DIRECTORY_SEPARATOR . self::LAMP_IO_CONFIG, '');
		}
	}

	public function get(string $keys)
	{
		$config = $this->config;
		$keys = explode('.', $keys);
		foreach ($keys as $arg) {
			if (isset($config[$arg])) {
				$config = $config[$arg];
			} else {
				throw new InvalidArgumentException('Key not exists, ' . $arg);
			}
		}
		return $config;
	}

	public static function arraySet(array &$array, $key, $value)
	{
		if (is_null($key)) return $array = $value;
		$keys = explode('.', $key);
		while (count($keys) > 1) {
			$key = array_shift($keys);
			if (!isset($array[$key]) || !is_array($array[$key])) {
				$array[$key] = [];
			}
			$array =& $array[$key];
		}
		$array[array_shift($keys)] = $value;
		return $array;
	}

	public function set(string $keys, $value)
	{
		$keys = [$keys => $value];
		foreach ($keys as $key => $val) {
			self::arraySet($this->config, $key, $val);
		}
	}

	public function save()
	{
		$yaml = Yaml::dump($this->config);
		file_put_contents($this->appPath . self::LAMP_IO_CONFIG, $yaml);
	}


}