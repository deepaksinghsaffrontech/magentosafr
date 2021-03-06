<?php
/**
 * Copyright © 2015 Saffron.com. All rights reserved.

 * @author Saffron Team <contact@Saffron.com>
 */

namespace Saffron\Blog\Controller\Adminhtml\Post;

/**
 * Blog post save controller
 */
class Save extends \Saffron\Blog\Controller\Adminhtml\Post
{
	/**
	 * Before model save
	 * @param  \Saffron\Blog\Model\Post $model
	 * @param  \Magento\Framework\App\Request\Http $request
	 * @return void
	 */
	protected function _beforeSave($model, $request)
	{
		if ($links = $request->getParam('links')) {

			foreach (array('post', 'product') as $key) {
				$param = 'related'.$key.'s';
				if (!empty($links[$param])) {
					$ids = array_unique(
						array_map('intval',
							explode('&', $links[$param])
						)
					);
					if (count($ids)) {
						$model->setData('related_'.$key.'_ids', $ids);
					}
				}
			}
		}
	}
	
}
