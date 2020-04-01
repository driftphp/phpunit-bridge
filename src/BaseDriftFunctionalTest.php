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

namespace Drift\PHPUnit;

use Exception;
use function Clue\React\Block\await;
use function Clue\React\Block\awaitAll;
use Mmoreram\BaseBundle\Tests\BaseFunctionalTest;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\Process\Process;

/**
 * Class BaseDriftFunctionalTest.
 */
abstract class BaseDriftFunctionalTest extends BaseFunctionalTest
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$container->set('reactphp.event_loop', Factory::create());
    }

    /**
     * Return loop.
     *
     * @return LoopInterface
     */
    protected static function getLoop(): LoopInterface
    {
        return self::$container->get('reactphp.event_loop');
    }

    /**
     * Await.
     *
     * @param PromiseInterface|mixed $promise
     * @param LoopInterface          $loop
     *
     * @return mixed
     *
     * @throws Exception
     */
    protected static function await(
        $promise,
        LoopInterface $loop = null
    ) {
        return await(
            $promise,
            $loop ?? static::getLoop()
        );
    }

    /**
     * Await all.
     *
     * @param array         $promise
     * @param LoopInterface $loop
     *
     * @return array
     *
     * @throws Exception
     */
    protected static function awaitAll(
        array $promises,
        LoopInterface $loop = null
    ) {
        return awaitAll(
            $promises,
            $loop ?? static::getLoop()
        );
    }

    /**
     * Run server.
     *
     * @param string $serverPath
     * @param string $port
     * @param array  $arguments
     *
     * @return Process
     */
    protected static function runServer(
        string $serverPath,
        string $port,
        array $arguments = []
    ): Process {
        $serverPath = rtrim($serverPath, '/');
        $serverPath = realpath($serverPath);
        $serverFile = "$serverPath/server";

        if (!is_file($serverFile)) {
            throw new Exception("Server not found in $serverPath");
        }

        $kernel = self::$kernel;
        $jsonSerializedKernel = json_encode($kernel->toArray());
        $jsonSerializedKernelHash = '/kernel'.rand(1, 99999999999999).'.kernel.json';
        $jsonSerializedKernelPath = $kernel->getProjectDir().$jsonSerializedKernelHash;

        file_put_contents(
            $jsonSerializedKernelPath,
            $jsonSerializedKernel
        );

        $command = [
            'php', $serverFile,
            'run', "0.0.0.0:$port",
            '--adapter='.TestAdapter::class,
        ];
        $command += $arguments;

        $process = new Process($command, null, [
            'KERNEL_SERIALIZED_PATH' => $jsonSerializedKernelPath,
        ]);
        $process->start();

        return $process;
    }
}
