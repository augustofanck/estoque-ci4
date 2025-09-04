<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrdensTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'data_compra'                  => ['type' => 'DATE', 'null' => true],
            'ordem_servico'                => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'nome_cliente'                 => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],

            'valor_venda'                  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'valor_entrada'                => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'forma_pagamento_entrada'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'valor_pago'                   => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'formas_pagamento'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],

            'valor_armacao_1'              => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'valor_armacao_2'              => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'tipo_lente_1'                 => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'tipo_lente_2'                 => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'valor_lente_1'                => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'valor_lente_2'                => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],

            // “consulta” aqui como campo livre para você anotar (ex.: “com consulta / Dr. X / 10-08-2025”)
            'consulta'                     => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],

            'pagamento_laboratorio'        => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'dia_pagamento_laboratorio'    => ['type' => 'DATE', 'null' => true],
            'data_recebimento_laboratorio' => ['type' => 'DATE', 'null' => true],
            'data_entrega_oculos'          => ['type' => 'DATE', 'null' => true],

            'nota_gerada'                  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0], // 0 = não, 1 = sim
            'dia_nota'                     => ['type' => 'DATE', 'null' => true],

            'created_at'                   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'                   => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('ordens');
    }

    public function down()
    {
        $this->forge->dropTable('ordens');
    }
}
