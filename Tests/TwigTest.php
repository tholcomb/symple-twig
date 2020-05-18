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

use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Tholcomb\Symple\Http\HttpProvider;
use Tholcomb\Symple\Logger\LoggerProvider;
use Tholcomb\Symple\Twig\TwigProvider;
use Tholcomb\Symple\Core\UnregisteredProviderException;

class TwigTest extends TestCase {
	private const ROUTING_ARGS = ['twig.enable_routing' => true];

	private function getContainer(): Container
	{
		$c = new Container();
		$c->register(new TwigProvider());

		return $c;
	}

	public function testTemplateLoading(): void
	{
		$c = $this->getContainer();
		$testDir = __DIR__ . '/templates/';
		$testTemplate = 'test-template.html.twig';
		TwigProvider::addTemplateDir($c, $testDir);
		$env = TwigProvider::getEnvironment($c);
		$this->assertTrue($env->getLoader()->exists('@rw/boilerplate.html.twig'), 'Builtins not loaded');
		$this->assertTrue($env->getLoader()->exists($testTemplate), 'Test template not loaded');

		$c = $this->getContainer();
		$env = TwigProvider::getEnvironment($c);
		$this->assertFalse($env->getLoader()->exists($testTemplate), 'Test template loaded');
		TwigProvider::addTemplateDir($c, __DIR__ . '/templates/');
		$this->assertTrue($env->getLoader()->exists($testTemplate), 'Test template not loaded');
	}

	public function testRoutingExtension(): void
	{
		$c = new Container();
		$c->register(new LoggerProvider());
		$c->register(new HttpProvider());
		$c->register(new TwigProvider(), self::ROUTING_ARGS);
		$env = TwigProvider::getEnvironment($c);
		$this->assertInstanceOf(RoutingExtension::class, $env->getExtension(RoutingExtension::class), 'Did not get RoutingExtension');
	}

	public function testMissingHttp(): void
	{
		$this->expectException(UnregisteredProviderException::class);
		$this->expectExceptionMessageMatches('/' . preg_quote(HttpProvider::class, '/') . '/');

		$c = new Container();
		$c->register(new TwigProvider(), self::ROUTING_ARGS);
		TwigProvider::getEnvironment($c);
	}
}