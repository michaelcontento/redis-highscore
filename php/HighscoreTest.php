<?php

/**
 * @covers Highscore 
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
