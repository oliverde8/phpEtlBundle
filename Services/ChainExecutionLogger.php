<?php


namespace Oliverde8\PhpEtlBundle\Services;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Oliverde8\PhpEtlBundle\Entity\EtlExecution;

class ChainExecutionLogger extends AbstractProcessingHandler
{
    /** @var ChainWorkDirManager */
    protected $chainWorkDirManager;

    protected $level;

    /** @var EtlExecution */
    protected $currentExecution;

    /** @var AbstractProcessingHandler */
    protected $currentStreamHandler;

    /**
     * ChainExecutionLogger constructor.
     *
     * @param ChainProcessorsManager $chainProcessorManager
     * @param ChainWorkDirManager $chainWorkDirManager
     * @param string $level
     */
    public function __construct(ChainWorkDirManager $chainWorkDirManager, string $level = Logger::DEBUG)
    {
        $this->chainWorkDirManager = $chainWorkDirManager;
        $this->level = $level;
    }


    protected function write(array $record)
    {
        // TODO Optimize this by moving it into isHandling.
        if (is_null($this->currentExecution) || is_null($this->currentStreamHandler)) {
            return;
        }

        $this->currentStreamHandler->write($record);
    }

    /**
     * @param EtlExecution|null $currentExecution
     */
    public function setCurrentExecution(?EtlExecution $currentExecution): void
    {
        if ($this->currentStreamHandler) {
            $this->currentStreamHandler->close();
            $this->currentStreamHandler = null;
        }

        $this->currentExecution = $currentExecution;
        if ($currentExecution) {
            $logFile = $this->chainWorkDirManager->getWorkDir($currentExecution) . "/execution.log";
            $this->currentStreamHandler = new StreamHandler($logFile, $this->level);
        }
    }
}