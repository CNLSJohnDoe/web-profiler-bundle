<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\WebProfilerBundle\Tests\Profiler;

use Symfony\Bundle\WebProfilerBundle\Profiler\TemplateManager;
use Symfony\Bundle\WebProfilerBundle\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\Loader\SourceContextLoaderInterface;

/**
 * @author Artur Wielogórski <wodor@wodor.net>
 */
class TemplateManagerTest extends TestCase
{
    /**
     * @var Environment
     */
    protected $twigEnvironment;

    /**
     * @var Profiler
     */
    protected $profiler;

    /**
     * @var TemplateManager
     */
    protected $templateManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->profiler = $this->createMock(Profiler::class);
        $twigEnvironment = $this->mockTwigEnvironment();
        $templates = [
            'data_collector.foo' => ['foo', 'FooBundle:Collector:foo'],
            'data_collector.bar' => ['bar', 'FooBundle:Collector:bar'],
            'data_collector.baz' => ['baz', 'FooBundle:Collector:baz'],
        ];

        $this->templateManager = new TemplateManager($this->profiler, $twigEnvironment, $templates);
    }

    public function testGetNameOfInvalidTemplate()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->templateManager->getName(new Profile('token'), 'notexistingpanel');
    }

    /**
     * if template exists in both profile and profiler then its name should be returned.
     */
    public function testGetNameValidTemplate()
    {
        $this->profiler->expects($this->any())
            ->method('has')
            ->withAnyParameters()
            ->willReturnCallback([$this, 'profilerHasCallback']);

        $this->assertEquals('FooBundle:Collector:foo.html.twig', $this->templateManager->getName(new ProfileDummy(), 'foo'));
    }

    public function profilerHasCallback($panel)
    {
        switch ($panel) {
            case 'foo':
            case 'bar':
                return true;
            default:
                return false;
        }
    }

    public function profileHasCollectorCallback($panel)
    {
        switch ($panel) {
            case 'foo':
            case 'baz':
                return true;
            default:
                return false;
        }
    }

    protected function mockTwigEnvironment()
    {
        $this->twigEnvironment = $this->createMock(Environment::class);

        if (Environment::MAJOR_VERSION > 1) {
            $loader = $this->createMock(LoaderInterface::class);
            $loader
                ->expects($this->any())
                ->method('exists')
                ->willReturn(true);
        } else {
            $loader = $this->createMock(SourceContextLoaderInterface::class);
        }

        $this->twigEnvironment->expects($this->any())->method('getLoader')->willReturn($loader);

        return $this->twigEnvironment;
    }
}

class ProfileDummy extends Profile
{
    public function __construct()
    {
        parent::__construct('token');
    }

    public function hasCollector($name): bool
    {
        switch ($name) {
            case 'foo':
            case 'bar':
                return true;
            default:
                return false;
        }
    }
}
