<?php
/**
 * This file is part of the Symple framework
 *
 * Copyright (c) Tyler Holcomb <tyler@tholcomb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tholcomb\Symple\Twig\Tests;

use Pimple\Container;
use Tholcomb\Symple\Core\Cache\SympleCacheInterface;
use Tholcomb\Symple\Core\Tests\FilesystemCacheTestAbstract;
use Tholcomb\Symple\Twig\SympleTwigCache;
use Tholcomb\Symple\Twig\TwigProvider;

class TwigCacheTest extends FilesystemCacheTestAbstract {
	protected function getCache(): SympleCacheInterface
	{
		$c = new Container();
		$c->register(new TwigProvider(), ['twig.cache_path' => $this->path]);
		TwigProvider::addTemplateDir($c, __DIR__ . '/templates');
		$env = TwigProvider::getEnvironment($c);
		$env->setCache($this->path);

		return new SympleTwigCache($env);
	}
}