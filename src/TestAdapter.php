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

use Drift\Server\Adapter\DriftKernel\DriftKernelAdapter;
use Mmoreram\BaseBundle\Kernel\BaseKernel;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class TestAdapter.
 */
class TestAdapter extends DriftKernelAdapter
{
    /**
     * @param string $environment
     * @param bool   $debug
     *
     * @return Kernel
     */
    protected static function createKernelByEnvironmentAndDebug(
        string $environment,
        bool $debug
    ): Kernel {
        $kernelFile = $_SERVER['KERNEL_SERIALIZED_PATH'];
        $data = json_decode(file_get_contents($kernelFile), true);

        return BaseKernel::createFromArray($data, $_SERVER['APP_ENV'] ?? 'test', (bool) ($_SERVER['APP_DEBUG'] ?? false));
    }
}
