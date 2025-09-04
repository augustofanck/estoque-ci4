<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddClienteIdToOrdens extends Migration
{
    public function up()
    {
        // 1) adiciona coluna
        $this->forge->addColumn('ordens', [
            'cliente_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'id',
            ],
        ]);

        // 2) FK
        $this->db->query('ALTER TABLE `ordens`
            ADD CONSTRAINT `fk_ordens_cliente`
            FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`)
            ON UPDATE RESTRICT ON DELETE RESTRICT');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `ordens` DROP FOREIGN KEY `fk_ordens_cliente`');
        $this->forge->dropColumn('ordens', 'cliente_id');
    }
}
