<?php
namespace TYPO3\Surf\Task\Php;

/*                                                                        *
 * This script belongs to the TYPO3 project "TYPO3 Surf"                  *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;

/**
 * A task to reset the PHP opcache by executing a prepared script with an HTTP request
 */
class WebOpcacheResetExecuteTask extends \TYPO3\Surf\Domain\Model\Task
{
    /**
     * Execute this task
     *
     * @param \TYPO3\Surf\Domain\Model\Node $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param array $options Supported options: "baseUrl" (required) and "scriptIdentifier" (is passed by the create script task)
     * @return void
     * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
     * @throws \TYPO3\Surf\Exception\TaskExecutionException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = array())
    {
        if (!isset($options['baseUrl'])) {
            throw new \TYPO3\Surf\Exception\InvalidConfigurationException('No "baseUrl" option provided for WebOpcacheResetExecuteTask', 1421932609);
        }
        if (!isset($options['scriptIdentifier'])) {
            throw new \TYPO3\Surf\Exception\InvalidConfigurationException('No "scriptIdentifier" option provided for WebOpcacheResetExecuteTask, make sure to execute "TYPO3\\Surf\\Task\\Php\\WebOpcacheResetCreateScriptTask" before this task or pass one explicitly', 1421932610);
        }

        $retry = 0;
        $retryWait = 1000;
        $initialWait = 0;

        if (isset($options['retry'])) {
            $retry = min(10, (int)$options['retry']);
        }
        if (isset($options['retryWait'])) {
            $retry = (int)$options['retryWait'];
        }
        if (isset($options['initialWait'])) {
            $initialWait = (int)$options['initialWait'];
        }

        $streamContext = null;
        if (isset($options['stream_context']) && is_array($options['stream_context'])) {
            $streamContext = stream_context_create($options['stream_context']);
        }

        $scriptIdentifier = $options['scriptIdentifier'];
        $scriptUrl = rtrim($options['baseUrl'], '/') . '/surf-opcache-reset-' . $scriptIdentifier . '.php';

        if ($initialWait) {
            $deployment->getLogger()->info('Wait "' . $initialWait . '" ms before executing PHP opcache reset script');
            sleep($initialWait);
        }

        for ($retryCount = 0; $retryCount <= $retry; $retryCount++) {
            $result = file_get_contents($scriptUrl, false, $streamContext);
            if ($result === 'success') {
                break;
            }

            $deployment->getLogger()->warning('Executing PHP opcache reset script at "' . $scriptUrl . '" did not return expected result');

            if ($retryCount < $retry) {
                $deployment->getLogger()->warning('Will try again in "' . $retryWait . '" ms');
                sleep($retryWait);
            }
        }
    }
}
