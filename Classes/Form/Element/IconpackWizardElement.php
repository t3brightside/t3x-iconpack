<?php

declare(strict_types=1);

namespace Quellenform\Iconpack\Form\Element;

/*
 * This file is part of the "iconpack" Extension for TYPO3 CMS.
 *
 * Conceived and written by Stephan Kellermayr
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Quellenform\Iconpack\IconpackFactory;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Link input element.
 *
 * Shows current link and the link popup.
 */
class IconpackWizardElement extends AbstractFormElement
{

    /**
     * @var IconpackFactory
     */
    protected $iconpackFactory;

    /**
     * Container objects give $nodeFactory down to other containers.
     *
     * @param NodeFactory $nodeFactory
     * @param array $data
     */
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->iconpackFactory = GeneralUtility::makeInstance(IconpackFactory::class);
    }

    /**
     * This will render a button, which allows to select an Icon for the header.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        /** @var PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        // Add inline labels
        $pageRenderer->addInlineLanguageLabelFile('EXT:iconpack/Resources/Private/Language/locallang_be.xlf', 'js.');

        $cssFiles = $this->iconpackFactory->queryAssets('css', 'backend');
        foreach ($cssFiles as $cssFile) {
            $pageRenderer->addCssFile($cssFile);
        }

        $languageService = $this->getLanguageService();
        $parameterArray = $this->data['parameterArray'];
        $itemName = $parameterArray['itemFormElName'];
        $resultArray = $this->initializeResultArray();

        $itemValue = $parameterArray['itemFormElValue'];

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $iconMarkup = '';
        if (!empty($itemValue)) {
            $iconMarkup = $this->iconpackFactory->getIconMarkup($itemValue);
            if (empty($iconMarkup)) {
                $iconMarkup = $this->iconFactory->getIcon('default-not-found', Icon::SIZE_SMALL)->render();
            }
        }
        $icon = '<span class="t3js-icon icon icon-size-small">' . $iconMarkup . '</span>';

        $toggleButtonTitle
            = $languageService->sL('LLL:EXT:iconpack/Resources/Private/Language/locallang_be.xlf:js.label.iconNative');

        $fieldId = StringUtility::getUniqueId('formengine-input-');
        $buttonAttributes = [
            'title' => htmlspecialchars($toggleButtonTitle),
            'class' => 'btn btn-default iconpack-icon',
            'data-formengine-input-name' => (string)($itemName ?? ''),
            'id' => $fieldId,
            'data-item-name' => htmlspecialchars($itemName)
        ];
        $expansionHtml = [];
        $expansionHtml[] = '<div class="form-control-wrap">';
        $expansionHtml[] = '<button type="button" ' . GeneralUtility::implodeAttributes($buttonAttributes, true) . '">';
        $expansionHtml[] = $icon;
        $expansionHtml[] = '</button>';
        $expansionHtml[] = '<input type="hidden" name="' . $itemName . '" value="' . htmlspecialchars($itemValue) . '" />';
        $expansionHtml[] = '</div>';
        $fullElement = implode(LF, $expansionHtml);

        $resultArray = [];

        // Add JavaScript module
        $typo3Version = VersionNumberUtility::getCurrentTypo3Version();
        if (version_compare($typo3Version, '12.0.0', '>=')) {
            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create(
                '@quellenform/iconpack-wizard.js'
            )->instance('#' . $fieldId);
        } elseif (version_compare($typo3Version, '11.5.0', '>=')) {
            $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
                'TYPO3/CMS/Iconpack/v11/IconpackWizard'
            )->instance('#' . $fieldId);
        } else {
            $resultArray['requireJsModules'][] = [
                'TYPO3/CMS/Iconpack/v11/IconpackWizard' => '
                function(IconpackWizard) {
                    new IconpackWizard(' . GeneralUtility::quoteJSvalue('#' . $fieldId) . ');
                }
                '
            ];
        }

        $resultArray['html'] = '
            <typo3-formengine-element-iconpack class="formengine-field-item t3js-formengine-field-item" recordFieldId="
                ' . htmlspecialchars($fieldId) . '">
                    ' . $fieldInformationHtml . '
                    ' . $fullElement . '
            </typo3-formengine-element-iconpack>';

        return $resultArray;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
