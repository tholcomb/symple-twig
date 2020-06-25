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

use Pimple\Container;
use Pimple\Exception\FrozenServiceException;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Tholcomb\Symple\Console\ConsoleProvider;
use Tholcomb\Symple\Core\AbstractProvider;
use Tholcomb\Symple\Core\Cache\SympleCacheContainer;
use Tholcomb\Symple\Core\Symple;
use Tholcomb\Symple\Http\HttpProvider;
use Tholcomb\Symple\Core\UnregisteredProviderException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use function Tholcomb\Symple\Core\exists_and_registered;
use function Tholcomb\Symple\Core\isset_and_true;

class TwigProvider extends AbstractProvider {
	public const TWIG_NAMESPACE = 'rw';
	public const KEY_ENVIRONMENT = 'twig.env';
	public const KEY_LOADER = 'twig.loader';
	protected const NAME = 'twig';

	public function register(Container $c)
	{
		parent::register($c);
		$c[self::KEY_ENVIRONMENT] = function ($c) {
			$debug = (Symple::isDebug() === true);
			$env = new Environment($c['twig.loader'], [
				'debug' => $debug,
				'cache' => (!$debug && isset($c['twig.cache_path'])) ? $c['twig.cache_path'] : false,
				'strict_variables' => $debug,
			]);
			if (class_exists(HttpProvider::class)) {
				if (isset_and_true('twig.enable_routing', $c)) {
					HttpProvider::isRegistered($c, true);
					$env->addExtension(new RoutingExtension($c[HttpProvider::KEY_URL_GENERATOR]));
				}
			}

			return $env;
		};
		$c[self::KEY_LOADER] = function ($c) {
			$loader = new FilesystemLoader();
			if (!isset_and_true('twig.disable_builtin', $c)) {
				$loader->setPaths(__DIR__ . '/templates/', self::TWIG_NAMESPACE);
			}

			return $loader;
		};

		if (exists_and_registered(ConsoleProvider::class, $c)) {
			$c->extend(ConsoleProvider::KEY_CACHE_CONTAINER, function (SympleCacheContainer $caches, $c) {
				$caches->addCache(new SympleTwigCache($c[self::KEY_ENVIRONMENT]));

				return $caches;
			});
		}
	}

	public static function getEnvironment(Container $c): Environment
	{
		if (!isset($c[self::KEY_ENVIRONMENT])) throw new UnregisteredProviderException(self::class);
		return $c[self::KEY_ENVIRONMENT];
	}

	public static function addTemplateDir(Container $c, string $dir, ?string $ns = null): void
	{
		if (!isset($c[self::KEY_LOADER])) throw new UnregisteredProviderException(self::class);
		if (!is_dir($dir)) throw new \InvalidArgumentException("'$dir' is not a directory");
		$ext = function (FilesystemLoader $loader) use ($dir, $ns) {
			if ($ns === null) $ns = FilesystemLoader::MAIN_NAMESPACE;
			$loader->addPath($dir, $ns);

			return $loader;
		};
		try {
			$c->extend(self::KEY_LOADER, $ext);
		} catch (FrozenServiceException $e) {
			$ext($c[self::KEY_LOADER]);
		}
	}
}