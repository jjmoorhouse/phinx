<?php
/**
 * Abstract Test Migration Class
 *
 * Migrations used for test data should extend this class.
 *
 * It provides a single method which used to be in a separate trait.
 *
 */

namespace Phinx\Migration;


abstract class TestMigration extends AbstractMigration
{
    protected function insertTestData($table, array $data)
    {
        $rows = array();
        foreach ($data as $row) {
            $rowStr = implode('","',$row);
            $rowStr = str_replace('"NULL"', 'NULL', $rowStr);
            $rows[] = $rowStr;
        }
        $rows = implode('"),("',$rows);
        $this->execute('INSERT INTO `'.$table.'` VALUES ("'.$rows.'")');
    }
}
