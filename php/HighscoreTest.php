<?php

/**
 * Copyright 2011 Michael Contento <michaelcontento@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * @author  Michael Contento <michaelcontento@gmail.com>
 * @link    https://github.com/michaelcontento/redis-highscore
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache
 * @covers  Highscore 
 */
class HighscoreTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Highscore
     */
    private $_highscore;
    
    /**
     * @var Predis\Client
     */
    private $_redis;

    /**
     * @var array
     */
    private $_usedPredisMethods = array(
        'zadd',
        'zscore',
        'zrem',
        'zremrangebyscore',
        'zincrby', 
        'zcount',
        'zrank', 
        'zrange', 
        'zrangebyscore'
    );

    public function setUp()
    {
        $this->_redis = $this->getMock(
            'Predis\Client', 
            $this->_usedPredisMethods
        );
        $this->_highscore = new Highscore(
            $this->_redis, 
            'unittest'
        );
    }

    private function _configureRedis($method, array $arguments, $result=null)
    {
        $mockMethod = $this->_redis
            ->expects($this->once())
            ->method($method)
            ->will($this->returnValue($result));

        array_map(
            array($this, 'equalTo'), 
            $arguments
        );

        call_user_func_array(
            array($mockMethod, 'with'), 
            $arguments
        );
    }

    public function testUserIdIsTrimmed()
    {
        $this->_configureRedis('zrem', array('unittest', 'userId'));
        $this->_highscore->remove(' userId ');
    }

    public function testUserIdCanBeNumericButAreConvertedToString()
    {
        $this->_configureRedis('zrem', array('unittest', '12'));
        $this->_highscore->remove(12);
    }

    public function testUserIdCanBeNumericButAreConvertedToStringAsFloat()
    {
        $this->_configureRedis('zrem', array('unittest', '1.2'));
        $this->_highscore->remove(1.2);
    }

    public function testUserIdMustBeNotEmpty()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->_highscore->remove('');
    }

    public function testNamespaceIsTrimmed()
    {
        $obj = new Highscore($this->_redis, ' trimmed ');
        $this->assertEquals('trimmed', $obj->getNamespace());
    }

    public function testNamespaceMustBeNotEmpty()
    {
        $this->setExpectedException('InvalidArgumentException');
        $obj = new Highscore($this->_redis, '');
    }

    public function testNamespaceCanBeNumericButAreConvertedToString()
    {
        $obj = new Highscore($this->_redis, 12);
        $this->assertTrue('12' === $obj->getNamespace());

        $obj = new Highscore($this->_redis, 1.2);
        $this->assertTrue('1.2' === $obj->getNamespace());
    }

    public function testExceptionOnInvalidNamespaceArgumentArray()
    {
        $this->setExpectedException('InvalidArgumentException');
        $obj = new Highscore($this->_redis, array());
    }

    public function testExceptionOnInvalidNamespaceArgumentObject()
    {
        $this->setExpectedException('InvalidArgumentException');
        $obj = new Highscore($this->_redis, new stdClass());
    }

    public function testRedisObjectCanBeAccessed()
    {
        $this->assertEquals($this->_redis, $this->_highscore->getRedis());
    }

    public function testSet()
    {
        $this->_configureRedis(
            'zadd', 
            array('unittest', 42, 'userId')
        );

        $this->_highscore->set('userId', 42);
    }

    public function testRemove()
    {
        $this->_configureRedis(
            'zrem', 
            array('unittest', 'userId')
        );

        $result = $this->_highscore->remove('userId');
        $this->assertEquals($this->_highscore, $result);
    }

    public function testClear()
    {
        $this->_configureRedis(
            'zremrangebyscore', 
            array('unittest', '-inf', '+inf')
        );

        $result = $this->_highscore->clear('userId');
        $this->assertEquals($this->_highscore, $result);
    }

    public function testIncrement()
    {
        $this->_configureRedis(
            'zincrby', 
            array('unittest', 42, 'userId'),
            42
        );

        $result = $this->_highscore->increment('userId', 42);
        $this->assertEquals(42, $result);
    }

    public function testDecrement()
    {
        $this->_configureRedis(
            'zincrby', 
            array('unittest', -42, 'userId'),
            42
        );

        $result = $this->_highscore->decrement('userId', 42);
        $this->assertEquals(42, $result);
    }

    public function testRank()
    {
        $this->_configureRedis(
            'zrank', 
            array('unittest', 'userId'),
            0
        );

        $result = $this->_highscore->rank('userId');
        $this->assertEquals(1, $result);
    }

    public function testListByRank()
    {
        $this->_configureRedis(
            'zrange', 
            array(
                'unittest', 
                0, 
                9, 
                array('withscores' => true)
            ),
            array(
                array(
                    'userA', 
                    12
                ), 
                array(
                    'userB', 
                    34
                )
            )
        );

        $expected = array(
            array(
                'userId' => 'userA',
                'score'  => 12,
                'rank'   => 1
            ),
            array(
                'userId' => 'userB',
                'score'  => 34,
                'rank'   => 2
            )
        );
        $result = $this->_highscore->listByRank(10, 1);
        $this->assertEquals($expected, $result);
    }

    public function testListByScore()
    {
        $this->_configureRedis(
            'zrangebyscore', 
            array(
                'unittest', 
                '-inf', 
                '+inf',
                array(
                    'withscores' => true, 
                    'limit' => array(0, 10)
                )
            ),
            array(
                array(
                    'userA', 
                    12
                ), 
                array(
                    'userB', 
                    34
                )
            )
        );

        $expected = array(
            array(
                'userId' => 'userA',
                'score'  => 12,
                'rank'   => 1
            ),
            array(
                'userId' => 'userB',
                'score'  => 34,
                'rank'   => 2
            )
        );
        $result = $this->_highscore->listByScore(10);
        $this->assertEquals($expected, $result);
    }

    public function testListByScoreWithStart()
    {
        $this->_configureRedis(
            'zrangebyscore', 
            array(
                'unittest', 
                1, 
                '+inf',
                array(
                    'withscores' => true, 
                    'limit' => array(0, 10)
                )
            ),
            array(
                array(
                    'userA', 
                    12
                ), 
                array(
                    'userB', 
                    34
                )
            )
        );

        $expected = array(
            array(
                'userId' => 'userA',
                'score'  => 12,
                'rank'   => 1
            ),
            array(
                'userId' => 'userB',
                'score'  => 34,
                'rank'   => 2
            )
        );
        $result = $this->_highscore->listByScore(10, 1);
        $this->assertEquals($expected, $result);
    }

    public function testCountByScore()
    {
        $this->_configureRedis(
            'zcount', 
            array('unittest', 12, 34),
            42
        );

        $result = $this->_highscore->countByScore(12, 34);
        $this->assertEquals(42, $result);
    }

    public function testCount()
    {
        $this->_configureRedis(
            'zcount', 
            array('unittest', '-inf', '+inf'),
            42
        );

        $result = $this->_highscore->count();
        $this->assertEquals(42, $result);
    }

    public function testCountViaCountableInterface()
    {
        $this->_configureRedis(
            'zcount', 
            array('unittest', '-inf', '+inf'),
            42
        );

        $this->assertEquals(42, count($this->_highscore));
    }

    public function testGet()
    {
        $this->_configureRedis(
            'zscore', 
            array('unittest', 'userId'), 
            42
        );

        $result = $this->_highscore->get('userId');
        $this->assertEquals(42, $result);
    }
}
