<?php

declare(strict_types=1);

use common\models\User;
use yii\db\Migration;

/**
 * Seeds the administrator demo account.
 *
 * The RBAC seed (m260615_000001_rbac_seed) hard-assigns the administrator role
 * to user_id = 1. That assignment is left in place, but this migration also
 * performs an explicit role assignment so that the account works regardless of
 * what user_id it receives at runtime.
 */
class m260621_000003_seed_admin_user extends Migration
{
    private const ADMIN_USERNAME = 'admin';
    private const ADMIN_EMAIL    = 'admin@lindenhof.de';
    private const ADMIN_PASSWORD = 'COSD@groupb';

    public function safeUp(): void
    {
        // The admin account is normally created earlier by
        // m260614_000002_seed_admin_user. Only create it here if it is missing,
        // but always (re)assert the role assignment below.
        $user = User::findOne(['username' => self::ADMIN_USERNAME]);
        if ($user === null) {
            $user                     = new User();
            $user->username           = self::ADMIN_USERNAME;
            $user->email              = self::ADMIN_EMAIL;
            $user->status             = User::STATUS_ACTIVE;
            $user->verification_token = '';
            $user->setPassword(self::ADMIN_PASSWORD);
            $user->generateAuthKey();
            $user->save(false);
        }

        $auth = Yii::$app->authManager;
        $role = $auth->getRole('administrator');
        if ($role !== null && $auth->getAssignment('administrator', $user->id) === null) {
            $auth->assign($role, $user->id);
        }
    }

    public function safeDown(): void
    {
        $auth = Yii::$app->authManager;

        $id = (new \yii\db\Query())
            ->select('id')
            ->from('{{%user}}')
            ->where(['username' => self::ADMIN_USERNAME])
            ->scalar();

        if ($id !== false && $id !== null) {
            $auth->revokeAll((int) $id);
        }

        $this->delete('{{%user}}', ['username' => self::ADMIN_USERNAME]);
    }
}
