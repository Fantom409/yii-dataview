<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\DataView\Tests;

use yii\base\Model;
use Yiisoft\Yii\DataView\DetailView;
use Yiisoft\Arrays\ArrayableInterface;
use Yiisoft\Arrays\ArrayableTrait;

/**
 * @group widgets
 */
class DetailViewTest extends \yii\tests\TestCase
{
    /** @var DetailView */
    public $detailView;

    protected function setUp()
    {
        parent::setUp();

        $this->mockWebApplication();
    }

    public function testAttributeValue()
    {
        $model = new ModelMock();
        $model->id = 'id';

        $this->detailView = $this->app->createObject([
            '__class'    => PublicDetailView::class,
            'model'      => $model,
            'template'   => '{label}:{value}',
            'attributes' => [
                'id',
                [
                    'attribute' => 'id',
                    'value'     => 1,
                ],
                [
                    'attribute' => 'id',
                    'value'     => '1',
                ],
                [
                    'attribute' => 'id',
                    'value'     => $model->getDisplayedId(),
                ],
                [
                    'attribute' => 'id',
                    'value'     => function ($model) {
                        return $model->getDisplayedId();
                    },
                ],
            ],
        ]);

        $this->assertEquals('Id:id', $this->detailView->renderAttribute($this->detailView->attributes[0], 0));
        $this->assertEquals('Id:1', $this->detailView->renderAttribute($this->detailView->attributes[1], 1));
        $this->assertEquals('Id:1', $this->detailView->renderAttribute($this->detailView->attributes[2], 2));
        $this->assertEquals('Id:Displayed id', $this->detailView->renderAttribute($this->detailView->attributes[3], 3));
        $this->assertEquals('Id:Displayed id', $this->detailView->renderAttribute($this->detailView->attributes[4], 4));
        $this->assertEquals(2, $model->getDisplayedIdCallCount());
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/13243
     */
    public function testUnicodeAttributeNames()
    {
        $model = new UnicodeAttributesModelMock();
        $model->ИдентификаторТовара = 'A00001';
        $model->το_αναγνωριστικό_του = 'A00002';

        $this->detailView = $this->app->createObject([
            '__class'    => PublicDetailView::class,
            'model'      => $model,
            'template'   => '{label}:{value}',
            'attributes' => [
                'ИдентификаторТовара',
                'το_αναγνωριστικό_του',
            ],
        ]);

        $this->assertEquals(
            'Идентификатор Товара:A00001',
            $this->detailView->renderAttribute($this->detailView->attributes[0], 0)
        );
        $this->assertEquals(
            'Το Αναγνωριστικό Του:A00002',
            $this->detailView->renderAttribute($this->detailView->attributes[1], 1)
        );
    }

    public function testAttributeVisible()
    {
        $model = new ModelMock();
        $model->id = 'id';

        $this->detailView = $this->app->createObject([
            '__class'    => PublicDetailView::class,
            'model'      => $model,
            'template'   => '{label}:{value}',
            'attributes' => [
                [
                    'attribute' => 'id',
                    'value'     => $model->getDisplayedId(),
                ],
                [
                    'attribute' => 'id',
                    'value'     => $model->getDisplayedId(),
                    'visible'   => false,
                ],
                [
                    'attribute' => 'id',
                    'value'     => $model->getDisplayedId(),
                    'visible'   => true,
                ],
                [
                    'attribute' => 'id',
                    'value'     => function ($model) {
                        return $model->getDisplayedId();
                    },
                ],
                [
                    'attribute' => 'id',
                    'value'     => function ($model) {
                        return $model->getDisplayedId();
                    },
                    'visible' => false,
                ],
                [
                    'attribute' => 'id',
                    'value'     => function ($model) {
                        return $model->getDisplayedId();
                    },
                    'visible' => true,
                ],
            ],
        ]);

        $this->assertEquals([
            0 => [
                'attribute' => 'id',
                'format'    => 'text',
                'label'     => 'Id',
                'value'     => 'Displayed id',
            ],
            2 => [
                'attribute' => 'id',
                'format'    => 'text',
                'label'     => 'Id',
                'value'     => 'Displayed id',
                'visible'   => true,
            ],
            3 => [
                'attribute' => 'id',
                'format'    => 'text',
                'label'     => 'Id',
                'value'     => 'Displayed id',
            ],
            5 => [
                'attribute' => 'id',
                'format'    => 'text',
                'label'     => 'Id',
                'value'     => 'Displayed id',
                'visible'   => true,
            ],
        ], $this->detailView->attributes);
        $this->assertEquals(5, $model->getDisplayedIdCallCount());
    }

    public function testRelationAttribute()
    {
        $model = new ModelMock();
        $model->id = 'model';
        $model->related = new ModelMock();
        $model->related->id = 'related';

        $this->detailView = $this->app->createObject([
            '__class'    => PublicDetailView::class,
            'model'      => $model,
            'template'   => '{label}:{value}',
            'attributes' => [
                'id',
                'related.id',
            ],
        ]);

        $this->assertEquals('Id:model', $this->detailView->renderAttribute($this->detailView->attributes[0], 0));
        $this->assertEquals('Related Id:related', $this->detailView->renderAttribute($this->detailView->attributes[1], 1));

        // test null relation
        $model->related = null;

        $this->detailView = $this->app->createObject([
            '__class'    => PublicDetailView::class,
            'model'      => $model,
            'template'   => '{label}:{value}',
            'attributes' => [
                'id',
                'related.id',
            ],
        ]);

        $this->assertEquals('Id:model', $this->detailView->renderAttribute($this->detailView->attributes[0], 0));
        $this->assertEquals('Related Id:<span class="not-set">(not set)</span>', $this->detailView->renderAttribute($this->detailView->attributes[1], 1));
    }

    public function testArrayableModel()
    {
        $expectedValue = [
            [
                'attribute' => 'id',
                'format'    => 'text',
                'label'     => 'Id',
                'value'     => 1,
            ],
            [
                'attribute' => 'text',
                'format'    => 'text',
                'label'     => 'Text',
                'value'     => 'I`m arrayable',
            ],
        ];

        $model = new ArrayableInterfaceMock();
        $model->id = 1;
        $model->text = 'I`m arrayable';

        $this->detailView = $this->app->createObject([
            '__class' => PublicDetailView::class,
            'model'   => $model,
        ]);

        $this->assertEquals($expectedValue, $this->detailView->attributes);
    }

    public function testObjectModel()
    {
        $expectedValue = [
            [
                'attribute' => 'id',
                'format'    => 'text',
                'label'     => 'Id',
                'value'     => 1,
            ],
            [
                'attribute' => 'text',
                'format'    => 'text',
                'label'     => 'Text',
                'value'     => 'I`m an object',
            ],
        ];

        $model = new ModelMock();
        $model->id = 1;
        $model->text = 'I`m an object';

        $this->detailView = $this->app->createObject([
            '__class' => PublicDetailView::class,
            'model'   => $model,
        ]);

        $this->assertEquals($expectedValue, $this->detailView->attributes);
    }

    public function testArrayModel()
    {
        $expectedValue = [
            [
                'attribute' => 'id',
                'format'    => 'text',
                'label'     => 'Id',
                'value'     => 1,
            ],
            [
                'attribute' => 'text',
                'format'    => 'text',
                'label'     => 'Text',
                'value'     => 'I`m an array',
            ],
        ];

        $model = [
            'id'   => 1,
            'text' => 'I`m an array',
        ];

        $this->detailView = $this->app->createObject([
            '__class' => PublicDetailView::class,
            'model'   => $model,
        ]);

        $this->assertEquals($expectedValue, $this->detailView->attributes);
    }

    public function testOptionsTags()
    {
        $expectedValue = '<tr><th tooltip="Tooltip">Text</th><td class="bg-red">I`m an array</td></tr>';

        $this->detailView = $this->app->createObject([
            '__class' => PublicDetailView::class,
            'model'   => [
                'text' => 'I`m an array',
            ],
            'attributes' => [
                [
                    'attribute'      => 'text',
                    'label'          => 'Text',
                    'contentOptions' => ['class' => 'bg-red'],
                    'captionOptions' => ['tooltip' => 'Tooltip'],
                ],
            ],
        ]);

        foreach ($this->detailView->attributes as $index => $attribute) {
            $a = $this->detailView->renderAttribute($attribute, $index);
            $this->assertEquals($expectedValue, $a);
        }
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/15536
     */
    public function testShouldTriggerInitEvent()
    {
        $initTriggered = false;
        $model = new ModelMock();
        $model->id = 1;
        $model->text = 'I`m an object';

        $this->detailView = $this->app->createObject([
            '__class'        => PublicDetailView::class,
            'model'          => $model,
            'on widget.init' => function () use (&$initTriggered) {
                $initTriggered = true;
            },
        ]);

        $this->assertTrue($initTriggered);
    }
}

/**
 * Helper Class.
 */
class ArrayableInterfaceMock implements ArrayableInterface
{
    use ArrayableTrait;

    public $id;

    public $text;
}

/**
 * Helper Class.
 */
class ModelMock extends Model
{
    public $id;
    public $text;

    private $_related;
    private $_displayedIdCallCount = 0;

    public function getRelated()
    {
        return $this->_related;
    }

    public function setRelated($related)
    {
        $this->_related = $related;
    }

    public function getDisplayedId()
    {
        $this->_displayedIdCallCount++;

        return "Displayed $this->id";
    }

    public function getDisplayedIdCallCount()
    {
        return $this->_displayedIdCallCount;
    }
}

/**
 * Used for testing attributes containing non-English characters.
 */
class UnicodeAttributesModelMock extends Model
{
    /**
     * Product's ID (Russian).
     *
     * @var mixed
     */
    public $ИдентификаторТовара;
    /**
     * ID (Greek).
     *
     * @var mixed
     */
    public $το_αναγνωριστικό_του;
}

class PublicDetailView extends DetailView
{
    public function renderAttribute($attribute, $index)
    {
        return parent::renderAttribute($attribute, $index);
    }
}
