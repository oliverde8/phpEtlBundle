<?php

namespace Oliverde8\PhpEtlBundle\Etl\Operation\Cleanup;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Oliverde8\PhpEtlBundle\Services\ChainWorkDirManager;

class DeleteFilesForOldExecutionOperation extends AbstractChainOperation
{
    /** @var ChainWorkDirManager */
    protected $chainWorkdDirManager;

    /**
     * DeleteFilesForOldExecutionOperation constructor.
     * @param ChainWorkDirManager $chainWorkdDirManager
     */
    public function __construct(ChainWorkDirManager $chainWorkdDirManager)
    {
        $this->chainWorkdDirManager = $chainWorkdDirManager;
    }

    protected function processData(ItemInterface $item, array &$context)
    {
        /** @var EtlExecution $entity */
        $entity = $item->getData();

        $executionWorkDir = $this->chainWorkdDirManager->getWorkDir($entity);
        if (!file_exists($executionWorkDir)) {
            return $item;
        }

        $parentDir = dirname($executionWorkDir);
        $this->deleteDirectory($executionWorkDir);

        while (basename($parentDir) !== 'var' && empty($this->getDirFiles($parentDir))) {
            @rmdir($parentDir);
            $parentDir = dirname($parentDir);
        }

        return $item;
    }
    
    protected function deleteDirectory(string $dir) {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach ($this->getDirFiles($dir) as $item) {
            if (!$this->deleteDirectory("$dir/$item")) {
                return false;
            }
        }

        return rmdir($dir);
    }

    protected function getDirFiles(string $dir)
    {
        if (!file_exists($dir) || !is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            $files[] =$item;
        }

        return $files;
    }

}