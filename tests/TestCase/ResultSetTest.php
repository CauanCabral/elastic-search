<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ElasticSearch\Test;

use Cake\Datasource\ConnectionManager;
use Cake\ElasticSearch\Datasource\MappingSchema;
use Cake\ElasticSearch\Document;
use Cake\ElasticSearch\Index;
use Cake\ElasticSearch\ResultSet;
use Cake\TestSuite\TestCase;

class MyTestDocument extends Document
{
}

/**
 * Tests the ResultSet class
 *
 */
class ResultSetTest extends TestCase
{
    public $fixtures = ['plugin.Cake/ElasticSearch.Articles'];

    /**
     * Tests the construction process
     *
     * @return void
     */
    public function testConstructor()
    {
        $elasticaSet = $this->getMockBuilder('Elastica\ResultSet')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = ConnectionManager::get('test');
        $index = $this->getMockBuilder('Cake\ElasticSearch\Index')->getMock();
        $query = $this->getMockBuilder('Cake\ElasticSearch\Query')
            ->setConstructorArgs([$index])
            ->getMock();
        $query->expects($this->once())->method('getRepository')
            ->will($this->returnValue($index));
        $index->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue(__NAMESPACE__ . '\MyTestDocument'));
        $index->method('embedded')
            ->will($this->returnValue([]));
        $index->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));
        $index->method('getSchema')
            ->will($this->returnValue(new MappingSchema($index->getType(), [])));

        return [new ResultSet($elasticaSet, $query), $elasticaSet];
    }

    /**
     * Tests that calling current will wrap the result using the provided entity
     * class
     *
     * @depends testConstructor
     * @return void
     */
    public function testCurrent($resultSets)
    {
        list($resultSet, $elasticaSet) = $resultSets;
        $data = ['foo' => 1, 'bar' => 2];
        $result = $this->getMockBuilder('Elastica\Result')
            ->setMethods(['getId', 'getData', 'getType'])
            ->disableOriginalConstructor()
            ->getMock();
        $result->method('getData')
            ->will($this->returnValue($data));
        $result->method('getId')
            ->will($this->returnValue(99));
        $result->method('getType')
            ->will($this->returnValue('things'));

        $elasticaSet->expects($this->once())
            ->method('current')
            ->will($this->returnValue($result));

        $document = $resultSet->current();
        $this->assertInstanceOf(__NAMESPACE__ . '\MyTestDocument', $document);
        $this->assertSame($data + ['id' => 99], $document->toArray());
        $this->assertFalse($document->isDirty());
        $this->assertFalse($document->isNew());
        $this->assertEquals('things', $document->type());
    }

    /**
     * Tests that calling current will get converted fields
     * basic types
     *
     * @return void
     */
    public function testBasicTypesConversions()
    {
        $connection = ConnectionManager::get('test');

        $elasticaSet = $this->getMockBuilder('Elastica\ResultSet')
            ->disableOriginalConstructor()
            ->getMock();
        $index = $this->getMockBuilder('Cake\ElasticSearch\Index')->getMock();
        $query = $this->getMockBuilder('Cake\ElasticSearch\Query')
            ->setConstructorArgs([$index])
            ->getMock();
        $query->expects($this->once())->method('getRepository')
            ->will($this->returnValue($index));
        $index->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue(__NAMESPACE__ . '\MyTestDocument'));
        $index->method('embedded')
            ->will($this->returnValue([]));
        $index->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $mapping = [
            'foo' => ['type' => 'integer'],
            'bar' => ['type' => 'keyword'],
            'baz' => ['type' => 'date'],
        ];
        $index->method('getSchema')
            ->will($this->returnValue(new MappingSchema($index->getType(), $mapping)));

        $data = ['foo' => '1', 'bar' => 'brazil', 'baz' => '2020-05-22'];
        $result = $this->getMockBuilder('Elastica\Result')
            ->setMethods(['getId', 'getData', 'getType'])
            ->disableOriginalConstructor()
            ->getMock();
        $result->method('getData')
            ->will($this->returnValue($data));
        $result->method('getId')
            ->will($this->returnValue(99));
        $result->method('getType')
            ->will($this->returnValue('things'));
        $elasticaSet->expects($this->once())
            ->method('current')
            ->will($this->returnValue($result));

        $resultSet = new ResultSet($elasticaSet, $query);

        $document = $resultSet->current();
        $this->assertInstanceOf(__NAMESPACE__ . '\MyTestDocument', $document);
        $this->assertTrue(is_integer($document->foo));
        $this->assertSame(1, $document->foo);
        $this->assertTrue(is_string($document->bar));
        $this->assertSame('brazil', $document->bar);
        $this->assertInstanceOf('Cake\I18n\Date', $document->baz);
        $this->assertSame('22/05/2020', $document->baz->format('d/m/Y'));
    }

    /**
     * Tests that the original ResultSet's methods are accessible
     *
     * @return void
     */
    public function testDecoratedMethods()
    {
        $connection = ConnectionManager::get('test');

        $methods = get_class_methods('Elastica\ResultSet');
        $exclude = [
            '__construct', 'offsetSet', 'offsetGet', 'offsetExists', 'offsetUnset',
            'current', 'next', 'key', 'valid', 'rewind', 'create', 'setClass',
        ];
        $methods = array_diff($methods, $exclude);

        $elasticaSet = $this->getMockBuilder('Elastica\ResultSet')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
        $index = $this->getMockBuilder('Cake\ElasticSearch\Index')->getMock();
        $index->method('embedded')
            ->will($this->returnValue([]));
        $index->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));
        $index->method('getSchema')
            ->will($this->returnValue(new MappingSchema($index->getType(), [])));
        $query = $this->getMockBuilder('Cake\ElasticSearch\Query')
            ->setConstructorArgs([$index])
            ->getMock();

        $query->expects($this->once())->method('getRepository')
            ->will($this->returnValue($index));

        $requireParam = ['getAggregation' => 'foo'];
        $resultSet = new ResultSet($elasticaSet, $query);
        foreach ($methods as $method) {
            $expect = $elasticaSet->expects($this->once())->method($method);
            $param = null;

            if (isset($requireParam[$method])) {
                $expect->with($requireParam[$method]);
                $param = $requireParam[$method];
            }

            $return = 'something';
            $expect->will($this->returnValue($return));

            $this->assertSame($return, $resultSet->{$method}($param));
        }
    }

    /**
     * Test serialize/unserialize
     *
     * @return void
     */
    public function testSerialize()
    {
        $index = new Index([
            'name' => 'articles',
            'connection' => ConnectionManager::get('test'),
        ]);

        $resultSet = $index->find()->all();
        $serialized = serialize($resultSet);
        $outcome = unserialize($serialized);

        $this->assertEquals($resultSet->getResults(), $outcome->getResults());
        $this->assertEquals($resultSet->toArray(), $outcome->toArray());
    }
}
