<?php
/**
 * Standard profiler driver that uses outputs for displaying profiling results.
 *
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magento_Profiler_Driver_Standard implements Magento_Profiler_DriverInterface
{
    /**
     * Storage for timers statistics
     *
     * @var Magento_Profiler_Driver_Standard_Stat
     */
    protected $_stat;

    /**
     * List of profiler driver outputs
     *
     * @var Magento_Profiler_Driver_Standard_OutputInterface[]
     */
    protected $_outputs = array();

    /**
     * Constructor
     *
     * @param array|null $config
     */
    public function __construct(array $config = null)
    {
        $this->_initOutputs($config);
        $this->_initStat($config);
        register_shutdown_function(array($this, 'display'));
    }

    /**
     * Init outputs by configuration
     *
     * @param array|null $config
     */
    protected function _initOutputs(array $config = null)
    {
        if (!$config) {
            return;
        }

        $outputFactory = $this->_getOutputFactory($config);
        foreach ($this->_getOutputConfigs($config) as $code => $outputConfig) {
            $outputConfig = $this->_parseOutputConfig($outputConfig);
            if (false === $outputConfig) {
                continue;
            }
            if (!isset($outputConfig['type']) && !is_numeric($code)) {
                $outputConfig['type'] = $code;
            }
            if (!isset($outputConfig['baseDir']) && isset($config['baseDir'])) {
                $outputConfig['baseDir'] = $config['baseDir'];
            }
            $this->registerOutput($outputFactory->create($outputConfig));
        }
    }

    /**
     * Parses output config
     *
     * @param mixed $outputConfig
     * @return array|bool
     */
    protected function _parseOutputConfig($outputConfig)
    {
        $result = false;
        if (is_array($outputConfig)) {
            $result = $outputConfig;
        } elseif (is_scalar($outputConfig) && $outputConfig) {
            if (is_numeric($outputConfig)) {
                $result = array();
            } else {
                $result = array(
                    'type' => $outputConfig
                );
            }
        }
        return $result;
    }

    /**
     * Get output configs
     *
     * @param array $config
     * @return array
     */
    protected function _getOutputConfigs(array $config = null)
    {
        $result = array();
        if (isset($config['outputs'])) {
            $result = $config['outputs'];
        } elseif (isset($config['output'])) {
            $result[] = $config['output'];
        }
        return $result;
    }

    /**
     * Gets output factory from configuration or create new one
     *
     * @param array|null $config
     * @return Magento_Profiler_Driver_Standard_Output_Factory
     */
    protected function _getOutputFactory(array $config = null)
    {
        if (isset($config['outputFactory'])
            && $config['outputFactory'] instanceof Magento_Profiler_Driver_Standard_Output_Factory
        ) {
            $result = $config['outputFactory'];
        } else {
            $result = new Magento_Profiler_Driver_Standard_Output_Factory();
        }
        return $result;
    }

    /**
     * Init timers statistics object from configuration or create new one
     *
     * @param array $config|null
     */
    protected function _initStat(array $config = null)
    {
        if (isset($config['stat'])
            && $config['stat'] instanceof Magento_Profiler_Driver_Standard_Stat
        ) {
            $this->_stat = $config['stat'];
        } else {
            $this->_stat = new Magento_Profiler_Driver_Standard_Stat();
        }
    }

    /**
     * Clear collected statistics for specified timer or for whole profiler if timer id is omitted
     *
     * @param string|null $timerId
     */
    public function clear($timerId = null)
    {
        $this->_stat->clear($timerId);
    }

    /**
     * Start collecting statistics for specified timer
     *
     * @param string $timerId
     * @param array|null $tags
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function start($timerId, array $tags = null)
    {
        $this->_stat->start($timerId, microtime(true), memory_get_usage(true), memory_get_usage());
    }

    /**
     * Stop recording statistics for specified timer.
     *
     * @param string $timerId
     */
    public function stop($timerId)
    {
        $this->_stat->stop($timerId, microtime(true), memory_get_usage(true), memory_get_usage());
    }

    /**
     * Register profiler output instance to display profiling result at the end of execution
     *
     * @param Magento_Profiler_Driver_Standard_OutputInterface $output
     */
    public function registerOutput(Magento_Profiler_Driver_Standard_OutputInterface $output)
    {
        $this->_outputs[] = $output;
    }

    /**
     * Display collected statistics with registered outputs
     */
    public function display()
    {
        if (Magento_Profiler::isEnabled()) {
            foreach ($this->_outputs as $output) {
                $output->display($this->_stat);
            }
        }
    }
}
