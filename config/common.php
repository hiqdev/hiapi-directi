<?php
/**
 * hiAPI Directi plugin
 *
 * @link      https://github.com/hiqdev/hiapi-directi
 * @package   hiapi-directi
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

$definitions = [
    'directiTool' => [
        '__class' => \hiapi\directi\DirectiTool::class,
    ],
];

return class_exists(Yiisoft\Factory\Definitions\Reference::class) ? $definitions : ['container' => ['definitions' => $definitions]];
