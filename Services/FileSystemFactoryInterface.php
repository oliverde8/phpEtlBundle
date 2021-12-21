<?php

namespace Oliverde8\PhpEtlBundle\Services;

use Oliverde8\Component\PhpEtl\Model\File\FileSystemInterface;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;

interface FileSystemFactoryInterface
{
    public function get(EtlExecution $execution): FileSystemInterface;
}
