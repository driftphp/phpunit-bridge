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
use React\EventLoop\LoopInterface;
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
        $this->assertInstanceof(LoopInterface::class, $this->getLoop());
        $aService = self::get(Service::class);
        $aValuePromise = $aService->getAValue();
        $this->assertEquals('a value', $this->await($aValuePromise));

        $this->assertEquals([
            'a value',
            'a value',
        ], $this->awaitAll([
            $aService->getAValue(),
            $aService->getAValue(),
        ]));
    }

    /**
     * Test server creation.
     */
    public function testServerCreation()
    {
        $process = $this->runServer(
            __DIR__.'/../vendor/bin',
            '8532'
        );

        usleep(500000);
        $aResult = file_get_contents('http://127.0.0.1:8532/a/route');
        $this->assertEquals('A great response!', $aResult);
        $this->assertContains('8532', $process->getOutput());
        $this->assertContains('/a/route', $process->getOutput());
        $this->assertEmpty($process->getErrorOutput());
    }

    /**
     * Test silent server creation.
     */
    public function testSilentServerCreation()
    {
        $process = $this->runServer(
            __DIR__.'/../vendor/bin',
            '8532',
            [
                '--silent',
            ]
        );

        usleep(500000);
        $aResult = file_get_contents('http://127.0.0.1:8532/a/route');
        $this->assertEquals('A great response!', $aResult);
        $this->assertContains('8532', $process->getOutput());
        $this->assertNotContains('/a/route', $process->getOutput());
        $this->assertEmpty($process->getErrorOutput());
    }

    /**
     * Test wrong server path.
     */
    public function testWrongServerPath()
    {
        $this->expectException(\Exception::class);
        $this->runServer(
            __DIR__.'/../vendor/non-existing-bin',
            '8532'
        );
    }
}
