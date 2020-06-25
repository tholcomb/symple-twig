<?php
/**
 * This file is part of the Symple framework
 *
 * Copyright (c) Tyler Holcomb <tyler@tholcomb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tholcomb\Symple\Twig;

use Tholcomb\Symple\Core\Cache\SympleCacheInterface;
use Twig\Environment;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use function Tholcomb\Symple\Core\recursive_rm;

class SympleTwigCache implements SympleCacheInterface {
	private $twig;

	public function __construct(Environment $twig)
	{
		$this->twig = $twig;
	}

	public function clearCache(): void
	{
		$cache = $this->twig->getCache(true);
		if (is_string($cache)) {
			recursive_rm($cache);
		} else {
			throw new \LogicException();
		}
	}

	public function warmCache(): void
	{
		$paths = $this->getPaths($this->twig->getLoader());
		foreach ($paths as $p) {
			foreach (glob($p . DIRECTORY_SEPARATOR . '*.html.twig') as $g) {
				try {
					$this->twig->load(basename($g));
				} catch (\Throwable|\Exception $e) {
					// Do nothing
				}
			}
		}
	}

	public function getCacheLocation(): ?string
	{
		$cache = $this->twig->getCache(true);

		return is_string($cache) ? $cache : null;
	}

	private function getPaths(LoaderInterface $loader): array
	{
		$arr = [];
		if ($loader instanceof ChainLoader) {
			foreach ($loader->getLoaders() as $l) {
				$arr = array_merge($arr, $this->getPaths($l));
			}
		} elseif ($loader instanceof FilesystemLoader) {
			foreach ($loader->getNamespaces() as $ns) {
				foreach ($loader->getPaths($ns) as $p) {
					$arr[] = $p;
				}
			}
		}

		return $arr;
	}
}