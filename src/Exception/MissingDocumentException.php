<?php
/**
 * MissingDocumentException file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.1
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Elasticsearch\Exception;

use Cake\Core\Exception\Exception;

/**
 * Exception raised when a Document could not be found.
 */
class MissingDocumentException extends Exception
{
    /**
     * @inheritDoc
     */
    protected $_messageTemplate = 'Document class %s could not be found.';
}
