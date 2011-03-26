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
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache
 */
class Highscore implements Countable
{
    /**
     * @var string
     */
    const VERSION = '0.0.1';

    /**
     * @var Predis\Client
     */
    private $_redis;

    /**
     * @var string
     */
    private $_namespace;

    /**
     * @param  string    $namespace
     * @return Highscore
     */
    private function _sanitizeNamespace(&$namespace)
    {
        if (is_numeric($namespace)) {
            $namespace = (string) $namespace;
        }

        if (is_string($namespace)) {
            $namespace = trim($namespace);
        }

        if (!is_string($namespace) 
        || strlen($namespace) == 0) {
            throw new InvalidArgumentException(
                'Namespace must be string and length > 0'
            );
        }
    }

    /**
     * @param  mixed     $userId
     * @return Highscore
     */
    private function _sanitizeUserId(&$userId)
    {
        if (is_numeric($userId)) {
            $userId = (string) $userId;
        }

        if (is_string($userId)) {
            $userId = trim($userId);
        }

        if (!is_string($userId) 
        || strlen($userId) == 0) {
            throw new InvalidArgumentException(
                'UserId must be string and length > 0'
            );
        }
    }

    /**
     * @param Prefis\Client $redis
     * @param string        $namespace
     */
    public function __construct(Predis\Client $redis, $namespace)
    {
        $this->_redis     = $redis;
        $this->_namespace = $namespace;
        $this->_sanitizeNamespace($this->_namespace);
    }

    /**
     * @param  string $userId
     * @return double
     */
    public function get($userId)
    {
        $this->_sanitizeUserId($userId);

        return $this->_redis->zscore(
            $this->_namespace, 
            $userId
        );
    }

    /**
     * @param  string $userId
     * @param  double $score
     * @return double 
     */
    public function set($userId, $score)
    {
        $this->_sanitizeUserId($userId);

        $this->_redis->zadd(
            $this->_namespace, 
            (double) $score, 
            $userId
        );
        return $score;
    }

    /**
     * @param  string    $userId
     * @return Highscore
     */
    public function remove($userId)
    {
        $this->_sanitizeUserId($userId);

        $this->_redis->zrem(
            $this->_namespace, 
            $userId
        );
        return $this;
    }

    /**
     * @return Highscore
     */
    public function clear()
    {
        $this->_redis->zremrangebyscore(
            $this->_namespace, 
            '-inf', 
            '+inf'
        );
        return $this;
    }

    /**
     * @param  string $userId
     * @param  double $score
     * @return double
     */
    public function increment($userId, $score)
    {
        $this->_sanitizeUserId($userId);

        return $this->_redis->zincrby(
            $this->_namespace, 
            (double) $score, 
            $userId
        );
    }

    /**
     * @param  string $userId
     * @param  double $score
     * @return double
     */
    public function decrement($userId, $score)
    {
        $this->_sanitizeUserId($userId);

        return $this->increment(
            $userId, 
            -1 * (double) $score
        );
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->_redis->zcount(
            $this->_namespace, 
            '-inf', 
            '+inf'
        );
    }

    /**
     * @param  double $min
     * @param  double $max
     * @return int
     */
    public function countByScore($min, $max)
    {
        return $this->_redis->zcount(
            $this->_namespace, 
            (double) $min, 
            (double) $max
        );
    }

    /**
     * @param  string $userId
     * @return int
     */
    public function rank($userId)
    {
        $this->_sanitizeUserId($userId);

        $zeroBasedRank = $this->_redis->zrank(
            $this->_namespace, 
            $userId
        );
        return $zeroBasedRank + 1;
    }

    /**
     * @param  double $start
     * @param  double $limit
     * @return array
     */
    public function listByRank($start, $limit)
    {
        $start = ((double) $start) - 1;
        $limit = ((double) $limit) - 1;

        $rows = $this->_redis->zrange(
            $this->_namespace,
            $start,
            $start + $limit,
            array(
                'withscores' => true
            )
        );

        $result = array();
        foreach ($rows as $rank => $row) {
            $result[] = array(
                'userId' => (string) $row[0],
                'score'  => (double) $row[1],
                'rank'   => $rank + 1
            );
        }

        return $result;
    }

    /**
     * @param  double $start
     * @param  double $limit
     * @return array
     */
    public function listByScore($start, $limit)
    {
        $rows = $this->_redis->zrangebyscore(
            $this->_namespace,
            (double) $start,
            '+inf',
            array(
                'withscores' => true,
                'limit'      => array(0, (double) $limit)
            )
        );

        $firstRank = $this->rank($rows[0][0]);
        $result = array();

        foreach ($rows as $offset => $row) {
            $result[] = array(
                'userId' => (string) $row[0],
                'score'  => (double) $row[1],
                'rank'   => $firstRank + $offset
            );
        }

        return $result;
    }
}
