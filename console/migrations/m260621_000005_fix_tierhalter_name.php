<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Updates the Tierhalter (keeper) name in the company profile.
 */
class m260621_000005_fix_tierhalter_name extends Migration
{
    public function safeUp(): void
    {
        $this->update('{{%company_profile}}', ['keeper_name' => 'Jürgen Heym'], ['keeper_name' => 'Klaus Müller']);
    }

    public function safeDown(): void
    {
        $this->update('{{%company_profile}}', ['keeper_name' => 'Klaus Müller'], ['keeper_name' => 'Jürgen Heym']);
    }
}
