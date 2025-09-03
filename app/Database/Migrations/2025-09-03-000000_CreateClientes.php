<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClientes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nome'              => ['type' => 'VARCHAR', 'constraint' => 150],
            'documento'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'             => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'telefone'          => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'endereco'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'cidade'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'estado'            => ['type' => 'CHAR', 'constraint' => 2, 'null' => true],
            'cep'               => ['type' => 'VARCHAR', 'constraint' => 9, 'null' => true],
            'termino_contrato'  => ['type' => 'DATE', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('documento');
        $this->forge->createTable('clientes');
    }

    public function down()
    {
        $this->forge->dropTable('clientes');
    }
}
