<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Credentials extends AbstractMigration {

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
    public function change(): void {

        if (!$this->hasTable('users')) {
            $table = $this->table('changesapi');
            $table
                    ->addColumn('serverurl', 'string', ['limit' => 128])
                    ->addColumn('changeid', 'integer', ['null' => true, 'signed' => false])
                    ->create();
        }

        $table = $this->table('changesapi');
        if ($this->adapter->getAdapterType() != 'sqlite') {
            $table->addColumn('login', 'string', ['null' => false, 'length' => 64])
                    ->addColumn('password', 'string', ['null' => false, 'length' => 64])
                    ->update();
        } else {
            $table->addColumn('login', 'string', ['null' => false])
                    ->addColumn('password', 'string', ['null' => false])
                    ->update();
        }
    }
}
