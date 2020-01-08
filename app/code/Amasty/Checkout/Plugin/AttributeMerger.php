<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Checkout
 */


namespace Amasty\Checkout\Plugin;

use Amasty\Checkout\Model\Field;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\CustomAttributesDataInterface;
use Amasty\Checkout\Api\Data\QuoteCustomFieldsInterface;
use Amasty\Checkout\Api\Data\CustomFieldsConfigInterface;

class AttributeMerger
{
    protected $fieldsConfig = null;

    /**
     * @var Field
     */
    private $fieldSingleton;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LayoutProcessor
     */
    private $layoutProcessorPlugin;

    /**
     * @var \Amasty\Checkout\Model\FieldsDefaultProvider
     */
    private $fieldsDefaultProvider;

    /**
     * @var \Amasty\Checkout\Model\Config
     */
    private $checkoutConfig;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    public function __construct(
        Field $fieldSingleton,
        StoreManagerInterface $storeManager,
        \Amasty\Checkout\Plugin\LayoutProcessor $layoutProcessorPlugin,
        \Amasty\Checkout\Model\FieldsDefaultProvider $fieldsDefaultProvider,
        \Amasty\Checkout\Model\Config $checkoutConfig,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->fieldSingleton = $fieldSingleton;
        $this->storeManager = $storeManager;
        $this->layoutProcessorPlugin = $layoutProcessorPlugin;
        $this->fieldsDefaultProvider = $fieldsDefaultProvider;
        $this->checkoutConfig = $checkoutConfig;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @return array
     */
    public function getDefaultData()
    {
        return $this->fieldsDefaultProvider->getDefaultData();
    }

    /**
     * @return Field[]
     */
    public function getFieldConfig()
    {
        if ($this->fieldsConfig === null) {
            $this->fieldsConfig = $this->fieldSingleton->getConfig(
                $this->storeManager->getStore()->getId()
            );
        }

        return $this->fieldsConfig;
    }

    /**
     * @see \Magento\Checkout\Block\Checkout\AttributeMerger:getFieldConfig to understand wth is going on here
     *
     * @param \Magento\Checkout\Block\Checkout\AttributeMerger $subject
     * @param array $elements
     * @param string $providerName name of the storage container used by UI component
     * @param string $dataScopePrefix
     * @param array $fields
     *
     * @return array|null
     */
    public function beforeMerge(
        \Magento\Checkout\Block\Checkout\AttributeMerger $subject,
        $elements,
        $providerName,
        $dataScopePrefix,
        array $fields = []
    ) {
        if (!$this->checkoutConfig->isEnabled()) {
            return null;
        }

        $defaultData = $this->getDefaultData();
        $fieldConfig = $this->getFieldConfig();
        $inheritedAttributes = $this->fieldSingleton->getInheritedAttributes();

        foreach ($elements as $attributeCode => &$attributeConfig) {
            if (isset($defaultData[$attributeCode])) {
                $attributeConfig['default'] = $defaultData[$attributeCode];
            }

            if (isset($inheritedAttributes[$attributeCode])) {
                $parent = $inheritedAttributes[$attributeCode];

                if (isset($fieldConfig[$parent])) {
                    $attributeConfig['sortOrder'] = $fieldConfig[$parent]->getData('sort_order');
                    $attributeConfig['visible'] = $fieldConfig[$parent]->getData('enabled');

                    if ($fieldConfig[$parent]->getData('label') != $fieldConfig[$parent]->getData('default_label')) {
                        $attributeConfig['label'] = $fieldConfig[$parent]->getData('label');
                    }
                }
            }

            if (isset($fieldConfig[$attributeCode])) {
                $field = $fieldConfig[$attributeCode];

                if (!(int)$field->getData('enabled')) {
                    unset($elements[$attributeCode]);
                    unset($fields[$attributeCode]);
                    if ($attributeCode === 'region') {
                        unset($elements['region_id']);
                        unset($fields['region_id']);
                    }
                    continue;
                }

                /** @var \Amasty\Checkout\Model\Field $field */
                $attributeConfig['sortOrder'] = $field->getData('sort_order');
                $this->layoutProcessorPlugin->setOrder($attributeCode, $field->getData('sort_order'));
                $attributeConfig['visible'] = $field->getData('enabled');
                $attributeConfig['required'] = $field->getData('required');
                $attributeConfig['validation']['required-entry'] = (bool)$field->getData('required');

                $label = $field->getData('label');

                if ($label != $field->getData('default_label')) {
                    $attributeConfig['label'] = $label;
                }
            }
        }
        unset($attributeConfig);
        if (isset($elements['telephone'])) {
            $fields['telephone']['config']['elementTmpl'] = 'Amasty_Checkout/form/element/telephone';
        }

        $elements = $this->addCustomFields($fieldConfig, $elements, $dataScopePrefix);

        return [$elements, $providerName, $dataScopePrefix, $fields];
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\AttributeMerger $subject
     * @param array $config
     *
     * @return array
     */
    public function afterMerge(\Magento\Checkout\Block\Checkout\AttributeMerger $subject, $config)
    {
        if (!$this->checkoutConfig->isEnabled()) {
            return $config;
        }
        $fieldConfig = $this->getFieldConfig();
        $defaultData = $this->getDefaultData();

        foreach ($config as $code => $configItem) {
            if (isset($fieldConfig[$code])) {
                $config[$code]['sortOrder'] = $fieldConfig[$code]->getData('sort_order');
            }
        }

        if (isset($config['postcode']) && isset($fieldConfig['postcode'])) {
            $config['postcode']['skipValidation'] = !$fieldConfig['postcode']->getData('required');
        }
        if (isset($config['region_id'])) {
            $config['region_id']['component'] = 'Amasty_Checkout/js/form/element/region';
            if (!empty($defaultData['region_id'])) {
                $config['region_id']['default'] = $defaultData['region_id'];
            }
        }

        if (isset($config['street']) && $this->checkoutConfig->isAddressSuggestionEnabled()) {
            $config['street']['children'][0]['component'] = 'Amasty_Checkout/js/form/element/autocomplete';
        }

        return $config;
    }

    /**
     * @param array $fieldConfig
     * @param array $elements
     * @param string $dataScopePrefix
     *
     * @return array
     */
    private function addCustomFields($fieldConfig, $elements, $dataScopePrefix)
    {
        if (!strpos($dataScopePrefix, '.custom_attributes') !== false) {
            $countOfCustomFields = CustomFieldsConfigInterface::COUNT_OF_CUSTOM_FIELDS;
            $index = CustomFieldsConfigInterface::CUSTOM_FIELD_INDEX;

            for ($index; $index <= $countOfCustomFields; $index++) {
                $customFieldIndex = 'custom_field_' . $index;

                if (isset($fieldConfig[$customFieldIndex]) && $fieldConfig[$customFieldIndex]->getEnabled() == 1) {
                    $field = $fieldConfig[$customFieldIndex];

                    $customAttributeName = CustomAttributesDataInterface::CUSTOM_ATTRIBUTES
                        . '.' . $customFieldIndex . '_' . $dataScopePrefix;

                    $elements[$customAttributeName] = $field->getData();
                    $elements[$customAttributeName]['visible'] = '1';
                    $elements[$customAttributeName]['formElement'] = 'input';
                    $elements[$customAttributeName]['dataType'] = 'text';
                    $elements[$customAttributeName]['sortOrder'] = $field->getSortOrder();
                    $elements[$customAttributeName]['validation']['required-entry'] = (bool)$field->getRequired();
                }
            }
        }

        return $elements;
    }
}
