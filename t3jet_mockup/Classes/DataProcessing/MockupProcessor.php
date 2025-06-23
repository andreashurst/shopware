<?php
namespace T3jet\T3jetMockup\DataProcessing;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

class MockupProcessor implements DataProcessorInterface
{

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        $processedData['plugin'] = [];
        $processedData['plugin']['tx_t3jet_mockup'] = [];
        $processedData['plugin']['tx_t3jet_mockup']['mockup'] = 'responsive';
        if (isset($_GET['mockup'])) $processedData['plugin']['tx_t3jet_mockup']['mockup'] = strtolower(str_replace("-", "", str_replace("_", "", $_GET['mockup'])));
        $processedData['plugin']['tx_t3jet_mockup']['url'] = strtolower(strtok('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], '?'));
        if (isset($_GET['url'])) $processedData['plugin']['tx_t3jet_mockup']['url'] = strtolower($_GET['url']);
        return $processedData;
    }
}
