<?php

namespace LOCKSSOMatic\ImportExportBundle\Tests\Command;

use LOCKSSOMatic\CoreBundle\Utilities\AbstractTestCase;
use LOCKSSOMatic\CrudBundle\Entity\Au;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-09-21 at 09:12:22.
 */
class GenerateAuidsCommandTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
    }

    public function testExecute() {
        $this->runCommand('lom:generate:auids');
        /** @var Au $au */
        $au = $this->references->getReference('au');
        $this->assertEquals('ca|sfu|test&base_url~http%3A%2F%2Fexample%2Ecom', $au->getAuid());
    }
}