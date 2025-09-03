<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusAndSoftDeleteToOrdens extends Migration
{
    public function up()
    {
        $fields = [];

        // Adiciona 'status' se não existir
        if (! $this->db->fieldExists('status', 'ordens')) {
            $fields['status'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'aberta',
                'after'      => 'id', // ajuste a posição se quiser
            ];
        }

        // Adiciona 'deleted_at' se não existir (para SoftDeletes)
        if (! $this->db->fieldExists('deleted_at', 'ordens')) {
            $fields['deleted_at'] = [
                'type' => 'DATETIME',
                'null' => true,
            ];
        }

        // (Opcional) timestamps, caso use useTimestamps no Model
        if (! $this->db->fieldExists('created_at', 'ordens')) {
            $fields['created_at'] = ['type' => 'DATETIME', 'null' => true];
        }
        if (! $this->db->fieldExists('updated_at', 'ordens')) {
            $fields['updated_at'] = ['type' => 'DATETIME', 'null' => true];
        }

        if (! empty($fields)) {
            $this->forge->addColumn('ordens', $fields);
        }

        // Índice para filtrar por status com melhor performance
        $indexes = $this->db->getIndexData('ordens');
        if (! isset($indexes['status'])) {
            $this->db->query('CREATE INDEX idx_ordens_status ON ordens (status)');
        }
    }

    public function down()
    {
        // Remover somente o que foi adicionado aqui
        if ($this->db->fieldExists('status', 'ordens')) {
            $this->forge->dropColumn('ordens', 'status');
        }
        if ($this->db->fieldExists('deleted_at', 'ordens')) {
            $this->forge->dropColumn('ordens', 'deleted_at');
        }
        if ($this->db->fieldExists('created_at', 'ordens')) {
            $this->forge->dropColumn('ordens', 'created_at');
        }
        if ($this->db->fieldExists('updated_at', 'ordens')) {
            $this->forge->dropColumn('ordens', 'updated_at');
        }
    }
}
