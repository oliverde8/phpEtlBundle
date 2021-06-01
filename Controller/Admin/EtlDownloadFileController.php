<?php


namespace Oliverde8\PhpEtlBundle\Controller\Admin;

use Oliverde8\PhpEtlBundle\Entity\EtlExecution;
use Oliverde8\PhpEtlBundle\Security\EtlExecutionVoter;
use Oliverde8\PhpEtlBundle\Services\ChainWorkDirManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EtlDownloadFileController extends AbstractController
{
    /** @var ChainWorkDirManager */
    protected $chainWorkDirManager;

    /**
     * EtlDownloadFileController constructor.
     * @param ChainWorkDirManager $chainWorkDirManager
     */
    public function __construct(ChainWorkDirManager $chainWorkDirManager)
    {
        $this->chainWorkDirManager = $chainWorkDirManager;
    }

    /**
     * @Route("/etl/execution/download", name="etl_execution_download_file")
     * @ParamConverter(name="execution", Class="Oliverde8PhpEtlBundle:EtlExecution")
     */
    public function index(EtlExecution $execution, string $filename): Response
    {
        $this->denyAccessUnlessGranted(EtlExecutionVoter::DOWNLOAD, EtlExecution::class);

        //TODO add Acl here for future proofing.
        return $this->file(
            $this->chainWorkDirManager->getWorkDir($execution) . "/" . $filename,
            "execution-{$execution->getName()}-{$execution->getId()}-" . $filename
        );
    }
}
