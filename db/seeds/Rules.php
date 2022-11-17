<?php

use Phinx\Seed\AbstractSeed;

class Rules extends AbstractSeed {

    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run() {


        $data = [
            [
                'company' => '-',
                'host' => '-',
                'meta' => 'settled',
                'subject' => 'faktura-vydana',
                'command' => 'invoice-settled'
            ], [
                'company' => '-',
                'host' => '-',
                'meta' => 'create',
                'subject' => 'banka',
                'command' => 'abraflexi-match-payment'
            ], [
                'company' => '-',
                'host' => '-',
                'meta' => 'penalised',
                'subject' => 'faktura-vydana',
                'command' => 'abraflexi-remind-invoice'
            ]
        ];

        $posts = $this->table('rules');
        $posts->insert($data)
                ->saveData();
    }

}
