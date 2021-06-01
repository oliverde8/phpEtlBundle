<?php


namespace Oliverde8\PhpEtlBundle\Services;


use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class ChainWorkDirManager
{
    /** @var string  */
    protected $baseDir;

    /** @var Filesystem */
    protected $fileSystem;

    /**
     * ChainWorkDirManager constructor.
     * @param string $directory
     */
    public function __construct(string $baseDir, Filesystem $filesystem)
    {
        $this->baseDir = $baseDir;
        $this->fileSystem = $filesystem;
    }

    /**
     * @param EtlExecution $execution
     * @return string
     *
     * @throws IOException if directory can't be created.
     */
    public function getWorkDir(EtlExecution $execution): string
    {
        $dir = $this->baseDir . "/" . $execution->getStartTime()->format("y/m/d") . "/id-" . $execution->getId() . "/";
        $this->fileSystem->mkdir($dir);

        return $dir;
    }

    public function listFiles(EtlExecution $execution): array
    {
        $files = [];
        $dir = $this->getWorkDir($execution);

        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $files[] = $entry;
            }
        }

        return $files;
    }
}