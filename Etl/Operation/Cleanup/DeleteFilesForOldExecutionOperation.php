<?php

declare(strict_types=1);

namespace Oliverde8\PhpEtlBundle\Etl\Operation\Cleanup;

use Oliverde8\Component\PhpEtl\ChainOperation\AbstractChainOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\ConfigurableChainOperationInterface;
use Oliverde8\Component\PhpEtl\Item\DataItemInterface;
use Oliverde8\Component\PhpEtl\Item\ItemInterface;
use Oliverde8\Component\PhpEtl\Model\ExecutionContext;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Oliverde8\PhpEtlBundle\Etl\OperationConfig\Cleanup\DeleteFilesForOldExecutionConfig;
use Oliverde8\PhpEtlBundle\Services\ChainWorkDirManager;
use Oliverde8\PhpEtlBundle\Services\FileSystemFactoryInterface;

class DeleteFilesForOldExecutionOperation extends AbstractChainOperation implements ConfigurableChainOperationInterface
{
    public function __construct(
        private readonly DeleteFilesForOldExecutionConfig $config,
        private readonly ChainWorkDirManager $chainWorkDirManager,
        private readonly FileSystemFactoryInterface $fileSystemFactory
    ){}

    protected function processData(DataItemInterface $item, ExecutionContext $context): ItemInterface
    {
        /** @var EtlExecution $entity */
        $entity = $item->getData();

        $fileSystem = $this->fileSystemFactory->get($entity);
        foreach ($fileSystem->listContents("") as $file) {
            if (!in_array($file, ['.', '..'])) {
                $fileSystem->delete($file);
            }
        }
        try {
            $fileSystem->delete("");
        } catch (\Exception $exception){}

        $executionWorkDir = $this->chainWorkDirManager->getLocalTmpWorkDir($entity);
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
