<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Meta extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $meta = $this->table('meta');
        $meta->addColumn('uri', 'string', ['limit' => 200,'comment'=>'document uri'])
              ->addColumn('meta', 'string', ['limit' => 40,'comment'=>'metastate'])
              ->addColumn('discovered', 'datetime', ['default' => 'CURRENT_TIMESTAMP','comment'=>'Analysed for the first time'])
              ->addColumn('processed', 'datetime', ['null' => true,'comment'=>'procesed'])
              ->addIndex(['uri'])
              ->addIndex(['discovered'])  
              ->create();
    }
}
