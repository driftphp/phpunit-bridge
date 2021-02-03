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

use function Clue\React\Block\await;
use function Clue\React\Block\awaitAll;
use Exception;
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
    public static function setUpBeforeClass() : void
    {
        parent::setUpBeforeClass();

        static::$container->set('reactphp.event_loop', Factory::create());
    }

    /**
     * @return LoopInterface
     */
    protected static function getLoop(): LoopInterface
    {
        return self::$container->get('reactphp.event_loop');
    }

    /**
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
     * @param array         $promises
     * @param LoopInterface|null $loop
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
     * @param string $serverPath
     * @param string $port
     * @param array  $arguments
     *
     * @return Process
     *
     * @throws Exception
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
        $command = array_values(array_merge($command, $arguments));

        $environmentVars = $_ENV;
        $environmentVars['KERNEL_SERIALIZED_PATH'] = $jsonSerializedKernelPath;
        $environmentVars['APP_DEBUG'] = '0';

        $process = new Process($command, null, $environmentVars);
        $process->start();

        return $process;
    }
}
