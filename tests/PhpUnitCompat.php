<?php

namespace Tarantool\Client\Tests;

/**
 * A PHPUnit compatibility layer to run tests written for phpunit v5 with phpunit v4.
 */
trait PhpUnitCompat
{
    /**
     * @param string $originalClassName
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function createMock($originalClassName)
    {
        if (is_callable('parent::createMock')) {
            return parent::createMock($originalClassName);
        }

        return $this->getMockBuilder($originalClassName)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->getMock();
    }
}
