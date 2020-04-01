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

use Drift\HttpKernel\AsyncKernel;
use Drift\Server\Adapter\KernelAdapter;
use Drift\Server\Watcher\ObservableKernel;
use Mmoreram\BaseBundle\Kernel\BaseKernel;

/**
 * Class TestAdapter.
 */
class TestAdapter implements KernelAdapter, ObservableKernel
{
    /**
     * {@inheritdoc}
     */
    public static function buildKernel(string $environment, bool $debug): AsyncKernel
    {
        $kernelFile = $_SERVER['KERNEL_SERIALIZED_PATH'];
        $data = json_decode(file_get_contents($kernelFile), true);

        return BaseKernel::createFromArray($data, $_SERVER['APP_ENV'] ?? 'test', (bool) ($_SERVER['APP_DEBUG'] ?? false));
    }

    /**
     * {@inheritdoc}
     */
    public static function getStaticFolder(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getObservableFolders(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getObservableExtensions(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getIgnorableFolders(): array
    {
        return [];
    }
}
