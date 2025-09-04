<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropClientTable extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('ordens', [
            'cliente_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
        ]);
    }

    public function down() 
    {
        
    }
}
