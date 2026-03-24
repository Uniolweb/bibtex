<?php

defined('TYPO3') or die();

$extkey = 'bibtex';
$extensionName = 'Bibtex';
$pluginSignature = $extkey . '_bibtex';

$plugins = [
    // name
    'Bibtex' => [
        'label' => 'Bibtex',
        'icon'  => 'bibtex-plugin',
        'flexform' => 'flexform_btex.xml',
        'preview' => Uniolweb\Bibtex\Backend\PreviewRenderer::class,
        'excludelist' => 'recursive,select_key,pages'
    ],
];

foreach ($plugins as $name => $plugin) {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        // extension_key or ExtensionName
        $extkey,
        // plugin name
        $name,
        // label
        $plugin['label'],
        // icon
        $plugin['icon']
    );

    $pluginSignature = $extkey . '_' . mb_strtolower($name);

    // add flexform
    $flexform = $plugin['flexform'] ?? '';
    if ($flexform) {
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            $pluginSignature,
            'FILE:EXT:' . $extkey . '/Configuration/FlexForms/' . $flexform
        );
    }
    // do not show "Record storage page" configuration for plugin in form
    // https://stackoverflow.com/questions/39386018/typo3-hide-plugin-mode-and-record-storage-page-in-a-plugin/39387488
    $excludeList = $plugin['excludelist'] ?? '';
    if ($excludeList) {
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature]
            = $excludeList;
    }
    $previewRenderer = $plugin['preview'] ?? '';
    if ($previewRenderer) {
        // set PreviewRenderer for page layout in BE
        // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/10.3/Feature-78450-IntroducePreviewRendererPattern.html
        $GLOBALS['TCA']['tt_content']['types']['list']['previewRenderer'][$pluginSignature] = $previewRenderer;
    }
}
