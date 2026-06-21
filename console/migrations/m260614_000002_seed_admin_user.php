<?php

declare(strict_types=1);

use common\models\User;
use yii\db\Migration;

/**
 * Creates the administrator user account BEFORE the RBAC seed migration runs.
 *
 * m260615_000001_rbac_seed hard-assigns the administrator role to user_id = 1.
 * By seeding the admin account here (one step earlier), the admin user is
 * guaranteed to receive id = 1 on a clean install, so the assignment lands on
 * the correct person instead of whichever demo user happens to be inserted first.
 *
 * m260621_000003_seed_admin_user acts as a safety net for existing environments
 * (e.g. the university server) where this migration runs after the user table
 * already has rows.
 */
class m260614_000002_seed_admin_user extends Migration
{
    private const ADMIN_USERNAME = 'admin';
    private const ADMIN_EMAIL    = 'admin@lindenhof.de';
    private const ADMIN_PASSWORD = 'COSD@groupb';

    public function safeUp(): void
    {
        // Skip if already present (e.g. existing server environments).
        if (User::findOne(['username' => self::ADMIN_USERNAME]) !== null) {
            return;
        }

        $user                     = new User();
        $user->username           = self::ADMIN_USERNAME;
        $user->email              = self::ADMIN_EMAIL;
        $user->status             = User::STATUS_ACTIVE;
        $user->verification_token = '';
        $user->setPassword(self::ADMIN_PASSWORD);
        $user->generateAuthKey();
        $user->save(false);
    }

    public function safeDown(): void
    {
        $this->delete('{{%user}}', ['username' => self::ADMIN_USERNAME]);
    }
}
