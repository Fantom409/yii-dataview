<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\view\events;

/**
 * PageEvent represents events triggered when rendering HTML page.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 * @since 1.0
 */
class PageEvent extends Event
{
    /**
     * @event triggered by [[yii\view\View::beginPage()]].
     */
    const BEGIN = 'view.page.begin';
    /**
     * @event triggered by [[yii\view\View::endPage()]].
     */
    const END = 'view.page.end';
}
