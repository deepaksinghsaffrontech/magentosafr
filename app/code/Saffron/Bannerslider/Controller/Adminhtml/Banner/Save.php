<?php
/**
* Copyright © 2015 Saffron.com. All rights reserved.

* @author Saffron Team <contact@Saffron.com>
*/

namespace Saffron\Bannerslider\Controller\Adminhtml\Banner;

use Magento\Framework\App\Filesystem\DirectoryList;

class Save extends \Saffron\Bannerslider\Controller\Adminhtml\Banner {
	/**
	 * @var \Magento\Framework\View\Result\PageFactory
	 */
	public function execute() {
		if ($data = $this->getRequest()->getPostValue()) {
			$model = $this->_objectManager->create('Saffron\Bannerslider\Model\Banner');
			$storeViewId = $this->getRequest()->getParam("store");
			
			if ($id = $this->getRequest()->getParam('banner_id')) {
				$model->load($id);
			}
			if (in_array('0', $data['stores'])) {
				$data['store_id'] = '0';
			}else{
				$data['store_id'] = implode(',',$data['stores']);
			}
			
			/**
			 * Save image upload
			 */
			try {
				$uploader = $this->_objectManager->create(
					'Magento\MediaStorage\Model\File\Uploader',
					['fileId' => 'image']
				);
				$uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);

				/** @var \Magento\Framework\Image\Adapter\AdapterInterface $imageAdapter */
				$imageAdapter = $this->_objectManager->get('Magento\Framework\Image\AdapterFactory')->create();

				$uploader->addValidateCallback('banner_image', $imageAdapter, 'validateUploadFile');
				$uploader->setAllowRenameFiles(true);
				$uploader->setFilesDispersion(true);

				/** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
				$mediaDirectory = $this->_objectManager->get('Magento\Framework\Filesystem')
				                       ->getDirectoryRead(DirectoryList::MEDIA);
				$result = $uploader->save($mediaDirectory->getAbsolutePath(\Saffron\Bannerslider\Model\Banner::BASE_MEDIA_PATH));
				$data['image'] = \Saffron\Bannerslider\Model\Banner::BASE_MEDIA_PATH . $result['file'];
			} catch (\Exception $e) {
				if ($e->getCode() == 0) {
					$this->messageManager->addError($e->getMessage());
				}
				if (isset($data['image']) && isset($data['image']['value'])) {
					if (isset($data['image']['delete'])) {
						$data['image'] = null;
						$data['delete_image'] = true;
					} else if (isset($data['image']['value'])) {
						$data['image'] = $data['image']['value'];
					} else {
						$data['image'] = null;
					}
				}
			}
            
			
			try {
				$uploader = $this->_objectManager->create(
					'Magento\MediaStorage\Model\File\Uploader',
					['fileId' => 'title2']
				);
				$uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);

				/** @var \Magento\Framework\Image\Adapter\AdapterInterface $imageAdapter */
				$imageAdapter = $this->_objectManager->get('Magento\Framework\Image\AdapterFactory')->create();

				$uploader->addValidateCallback('banner_image', $imageAdapter, 'validateUploadFile');
				$uploader->setAllowRenameFiles(true);
				$uploader->setFilesDispersion(true);

				/** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
				$mediaDirectory = $this->_objectManager->get('Magento\Framework\Filesystem')
				                       ->getDirectoryRead(DirectoryList::MEDIA);
				$result = $uploader->save($mediaDirectory->getAbsolutePath(\Saffron\Bannerslider\Model\Banner::BASE_MEDIA_PATH));
				$data['title2'] = \Saffron\Bannerslider\Model\Banner::BASE_MEDIA_PATH . $result['file'];
			} catch (\Exception $e) {
				if ($e->getCode() == 0) {
					$this->messageManager->addError($e->getMessage());
				}
				if (isset($data['title2']) && isset($data['title2']['value'])) {
					if (isset($data['title2']['delete'])) {
						$data['title2'] = null;
						$data['delete_image'] = true;
					} else if (isset($data['title2']['value'])) {
						$data['title2'] = $data['title2']['value'];
					} else {
						$data['title2'] = null;
					}
				}
			}

			
			$model->setData($data)
			      ->setStoreViewId($storeViewId);

			try {
				$model->save();

				$this->messageManager->addSuccess(__('The banner has been saved.'));
				$this->_getSession()->setFormData(false);

				if ($this->getRequest()->getParam('back') === 'edit') {
					$this->_redirect(
						'*/*/edit',
						[
							'banner_id' => $model->getId(),
							'_current' => true,
							'store' => $storeViewId,
							'current_slider_id' => $this->getRequest()->getParam('current_slider_id'),
							'saveandclose' => $this->getRequest()->getParam('saveandclose'),
						]
					);

					return;
				} elseif ($this->getRequest()->getParam('back') === "new") {
					$this->_redirect('*/*/new', array('_current' => true));
					return;
				}
				$this->_redirect('*/*/');
				return;
			} catch (\Magento\Framework\Model\Exception $e) {
				$this->messageManager->addError($e->getMessage());
			} catch (\RuntimeException $e) {
				$this->messageManager->addError($e->getMessage());
			} catch (\Exception $e) {
				$this->messageManager->addError($e->getMessage());
				$this->messageManager->addException($e, __('Something went wrong while saving the banner.'));
			}

			$this->_getSession()->setFormData($data);
			$this->_redirect('*/*/edit', array('banner_id' => $this->getRequest()->getParam('banner_id')));
			return;
		}
		$this->_redirect('*/*/');
	}
}
