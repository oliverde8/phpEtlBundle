<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Services;

use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class ChainWorkDirManager
{
    public function __construct(
        private readonly string $tmpBaseDir,
        private readonly Filesystem $tmpFileSystem
    ) {
    }

    /**
     * @param EtlExecution $execution
     * @param bool $createIfMissing
     * @return string
     *
     * @throws IOException if directory can't be created.
     */
    public function getLocalTmpWorkDir(EtlExecution $execution, $createIfMissing = true): string
    {
        $currentTime = $execution->getCreateTime()->format("y/m/d");
        $dir = $this->tmpBaseDir . "/" . $currentTime . "/id-" . $execution->getId();

        if ($createIfMissing) {
            $this->tmpFileSystem->mkdir($dir);
        }

        return $dir;
    }
}
