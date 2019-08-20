<?php
/**
* Copyright © 2015 Saffron.com. All rights reserved.

* @author Saffron Team <contact@Saffron.com>
*/

namespace Saffron\Bannerslider\Controller\Adminhtml\Banner;

use Magento\Framework\App\Filesystem\DirectoryList;

class ExportCsv extends \Saffron\Bannerslider\Controller\Adminhtml\Banner {
	/**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
	 * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
		\Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        parent::__construct($context, $coreRegistry);
		$this->_fileFactory = $fileFactory;
    }

	public function execute() {
		$fileName = 'banners.csv';
		$content = $this->_view->getLayout()->createBlock('Saffron\Bannerslider\Block\Adminhtml\Banner\Grid')->getCsv();
		return $this->_fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);
	}
}