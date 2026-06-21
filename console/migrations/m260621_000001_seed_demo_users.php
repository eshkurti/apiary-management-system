<?php

declare(strict_types=1);

use common\models\Customer;
use common\models\User;
use yii\db\Migration;

/**
 * Seeds three demo users (one per non-admin RBAC role) so the application can be
 * demonstrated without manual registration.
 *
 * The RBAC roles (administrator, headBeekeeper, fieldBeekeeper, customer) already
 * exist from m260615_000001_rbac_seed — this migration only seeds users and their
 * role assignments, it does not (re)create roles.
 *
 * For the customer user it also creates the linked CRM Customer record that
 * VerifyEmailForm::ensureCustomerRecord() would normally create at email
 * verification time.
 */
class m260621_000001_seed_demo_users extends Migration
{
    /**
     * username, email, role.
     * All three share the same demo password and STATUS_ACTIVE.
     */
    private const DEMO_USERS = [
        ['headBeekeeper',  'headbeekeeper@lindenhof.de',  'headBeekeeper'],
        ['fieldBeekeeper', 'fieldbeekeeper@lindenhof.de', 'fieldBeekeeper'],
        ['customer',       'customer@lindenhof.de',       'customer'],
    ];

    private const DEMO_PASSWORD = 'COSD@groupb';

    private const CUSTOMER_EMAIL = 'customer@lindenhof.de';

    public function safeUp(): void
    {
        $auth = Yii::$app->authManager;

        foreach (self::DEMO_USERS as [$username, $email, $roleName]) {
            $user = new User();
            $user->username = $username;
            $user->email = $email;
            $user->status = User::STATUS_ACTIVE;
            $user->verification_token = '';
            $user->setPassword(self::DEMO_PASSWORD);
            $user->generateAuthKey();
            $user->save(false);

            $role = $auth->getRole($roleName);
            if ($role !== null && $auth->getAssignment($roleName, $user->id) === null) {
                $auth->assign($role, $user->id);
            }

            // Mirror VerifyEmailForm::ensureCustomerRecord() for the buyer account.
            if ($email === self::CUSTOMER_EMAIL) {
                $customer = new Customer([
                    'user_id'      => $user->id,
                    'name'         => 'Demo Customer',
                    'email'        => $email,
                    'country'      => 'Germany',
                    'is_active'    => 1,
                    'is_wholesale' => 0,
                ]);
                $customer->save(false);
            }
        }
    }

    public function safeDown(): void
    {
        $auth = Yii::$app->authManager;

        // Remove the linked CRM record first.
        $this->delete('{{%customer}}', ['email' => self::CUSTOMER_EMAIL]);

        foreach (self::DEMO_USERS as [$username, $email, $roleName]) {
            $id = (new \yii\db\Query())
                ->select('id')
                ->from('{{%user}}')
                ->where(['username' => $username])
                ->scalar();

            if ($id !== false && $id !== null) {
                $auth->revokeAll((int) $id);
            }

            $this->delete('{{%user}}', ['username' => $username]);
        }
    }
}
