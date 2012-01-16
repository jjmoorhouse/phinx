<?php

namespace Test\Phinx\Migration;

use Symfony\Component\Console\Output\StreamOutput,
    Phinx\Config\Config,
    Phinx\Migration\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Manager
     */
    private $manager;
    
    protected function setUp()
    {
        $config = new Config($this->getConfigArray());
        $output = new StreamOutput(fopen('php://memory', 'a', false));
        $this->manager = new Manager($config, $output);
    }
    
    protected function tearDown()
    {
        $this->manager = null;
    }
    
    /**
     * Returns a sample configuration array for use with the unit tests.
     *
     * @return array
     */
    public function getConfigArray()
    {
        return array(
            'paths' => array(
                'migrations' => __DIR__ . '/_files/migrations'
            ),
            'environments' => array(
                'default_migration_table' => 'phinxlog',
                'default_database' => 'production',
                'production' => array(
                    'adapter' => 'mysql'
                )
            )
        );
    }
    
    public function testInstantiation()
    {
        $this->assertTrue($this->manager->getOutput() instanceof StreamOutput);
    }
    
    public function testPrintStatusMethod()
    {
        // stub environment
        $envStub = $this->getMock('\Phinx\Migration\Manager\Environment', array(), array('mockenv', array()));
        $envStub->expects($this->once())
                ->method('getVersions')
                ->will($this->returnValue(array('20120111235330', '20120116183504')));
        
        $this->manager->setEnvironments(array('mockenv' => $envStub));                
        $this->manager->printStatus('mockenv');   
        
        rewind($this->manager->getOutput()->getStream());
        $outputStr = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertRegExp('/up  20120111235330  TestMigration/', $outputStr);
        $this->assertRegExp('/up  20120116183504  TestMigration2/', $outputStr);
    }
    
    public function testPrintStatusMethodWithMissingMigrations()
    {
        // stub environment
        $envStub = $this->getMock('\Phinx\Migration\Manager\Environment', array(), array('mockenv', array()));
        $envStub->expects($this->once())
                ->method('getVersions')
                ->will($this->returnValue(array('20120103083300', '20120815145812')));
        
        $this->manager->setEnvironments(array('mockenv' => $envStub));                
        $this->manager->printStatus('mockenv');   
        
        rewind($this->manager->getOutput()->getStream());
        $outputStr = stream_get_contents($this->manager->getOutput()->getStream());
        $this->assertRegExp('/up  20120103083300  \*\* MISSING \*\*/', $outputStr);
        $this->assertRegExp('/up  20120815145812  \*\* MISSING \*\*/', $outputStr);
    }

    public function testGetMigrationsWithDuplicateMigrationVersions()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Duplicate migration - "' . __DIR__ . '/_files/duplicateversions/20120111235330_duplicate_migration_2.php" has the same version as "20120111235330"'
        );
        $config = new Config(array('paths' => array('migrations' => __DIR__ . '/_files/duplicateversions')));
        $output = new StreamOutput(fopen('php://memory', 'a', false));
        $manager = new Manager($config, $output);
        $manager->getMigrations();
    }
    
    public function testGetMigrationsWithDuplicateMigrationNames()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Migration "20120111235331_duplicate_migration_name.php" has the same name as "20120111235330_duplicate_migration_name.php"'
        );
        $config = new Config(array('paths' => array('migrations' => __DIR__ . '/_files/duplicatenames')));
        $output = new StreamOutput(fopen('php://memory', 'a', false));
        $manager = new Manager($config, $output);
        $manager->getMigrations();
    }
    
    public function testGetMigrationsWithInvalidMigrationClassName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Could not find class "InvalidClass" in file "' . __DIR__ . '/_files/invalidclassname/20120111235330_invalid_class.php"'
        );
        $config = new Config(array('paths' => array('migrations' => __DIR__ . '/_files/invalidclassname')));
        $output = new StreamOutput(fopen('php://memory', 'a', false));
        $manager = new Manager($config, $output);
        $manager->getMigrations();
    }
    
    public function testGetMigrationsWithClassThatDoesntExtendAbstractMigration()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'The class "InvalidSuperClass" in file "' . __DIR__ . '/_files/invalidsuperclass/20120111235330_invalid_super_class.php" must extend \Phinx\Migration\AbstractMigration'
        );
        $config = new Config(array('paths' => array('migrations' => __DIR__ . '/_files/invalidsuperclass')));
        $output = new StreamOutput(fopen('php://memory', 'a', false));
        $manager = new Manager($config, $output);
        $manager->getMigrations();
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The environment "invalidenv" does not exist
     */
    public function testGettingAnInvalidEnvironment()
    {
        $this->manager->getEnvironment('invalidenv');
    }
}