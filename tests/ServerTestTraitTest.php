<?php

/*
 * This file is part of the Drift Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Drift\PHPUnit\Tests;

use Drift\PHPUnit\BaseDriftFunctionalTest;
use Drift\PHPUnit\Tests\Service\Controller;
use Drift\PHPUnit\Tests\Service\Service;
use Mmoreram\BaseBundle\Kernel\DriftBaseKernel;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use function React\Promise\resolve;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class ServerTestTraitTest.
 */
class ServerTestTraitTest extends BaseDriftFunctionalTest
{
    /**
     * Get kernel.
     *
     * @return KernelInterface
     */
    protected static function getKernel(): KernelInterface
    {
        return new DriftBaseKernel(
            [
                FrameworkBundle::class,
            ],
            [
                'parameters' => [
                    'kernel.secret' => 'gdfgfdgd',
                ],
                'framework' => [
                    'test' => true,
                ],
                'services' => [
                    '_defaults' => [
                        'autowire' => true,
                        'autoconfigure' => true,
                        'public' => true,
                    ],
                    Controller::class => [],
                    Service::class => [],
                ],
            ],
            [
                'a_route' => [
                    '/a/route',
                    Controller::class,
                    'a_route',
                ],
            ]
        );
    }

    /**
     * Test that the kernel is properly created.
     */
    public function testKernelCreated()
    {
        $this->assertInstanceof(LoopInterface::class, static::getLoop());
        $aService = self::get(Service::class);
        $aValuePromise = $aService->getAValue();
        $this->assertEquals('a value', static::await($aValuePromise));

        $this->assertEquals([
            'a value',
            'a value',
        ], static::awaitAll([
            $aService->getAValue(),
            $aService->getAValue(),
        ]));
    }

    /**
     * test custom loop.
     */
    public function testCustomLoopAwait()
    {
        $loop = Factory::create();
        $promise = resolve()->then(function () { return '1'; });

        $this->assertEquals('1', static::await($promise, $loop));
    }

    /**
     * test custom loop.
     */
    public function testCustomLoopAwaitAll()
    {
        $loop = Factory::create();
        $promise1 = resolve()->then(function () { return '1'; });
        $promise2 = resolve()->then(function () { return '2'; });

        $this->assertEquals([
            '1',
            '2',
        ], static::awaitAll([
            $promise1,
            $promise2,
        ], $loop));
    }

    /**
     * Test server creation.
     */
    public function testServerCreation()
    {
        $process = static::runServer(
            __DIR__.'/../vendor/bin',
            '8532'
        );

        usleep(500000);
        var_dump($process->getErrorOutput());
        var_dump($process->getOutput());
        $aResult = file_get_contents('http://127.0.0.1:8532/a/route');
        $this->assertEquals('A great response!', $aResult);
        $this->assertStringContainsString('8532', $process->getOutput());
        $this->assertStringContainsString('/a/route', $process->getOutput());
        $this->assertEmpty($process->getErrorOutput());
        $process->stop();
    }

    /**
     * Test silent server creation.
     */
    public function testSilentServerCreation()
    {
        $process = static::runServer(
            __DIR__.'/../vendor/bin',
            '8532',
            [
                '--quiet',
            ]
        );

        usleep(500000);
        $aResult = file_get_contents('http://127.0.0.1:8532/a/route');
        $this->assertEquals('A great response!', $aResult);
        $this->assertStringNotContainsString('8532', $process->getOutput());
        $this->assertStringNotContainsString('/a/route', $process->getOutput());
        $this->assertEmpty($process->getErrorOutput());

        /**
         * Testing that the server is not down.
         */
        $anotherResult = file_get_contents('http://127.0.0.1:8532/a/route');
        $this->assertEquals('A great response!', $anotherResult);
        $process->stop();
    }

    /**
     * Test wrong server path.
     */
    public function testWrongServerPath()
    {
        $this->expectException(\Exception::class);
        static::runServer(
            __DIR__.'/../vendor/non-existing-bin',
            '8532'
        );
    }
}
