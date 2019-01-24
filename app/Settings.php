<?php

namespace App;

use Illuminate\Support\Facades\DB;

class Settings
{
    /**
     * Set setting.
     */
    public function set(string $name, $value)
    {
        $account = DB::table('settings')->updateOrInsert(['name' => $name], ['value' => $value]);
    }

    /**
     * Get setting.
     */
    public function get(string $name)
    {
        return DB::table('settings')->where('name', $name)->value('value');
    }
}
