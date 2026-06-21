<?php

declare(strict_types=1);

use common\models\Customer;
use common\models\User;
use yii\db\Migration;

/**
 * One-way cleanup of demo CRM clutter left by the seed/testing phase.
 *
 * Removes the two seeded demo orders (and their line items / stage history),
 * deletes the CRM-only customer records that were never linked to a real user
 * account, normalises the remaining retail demo customer, and seeds a single
 * real wholesale customer (user account + linked CRM record).
 *
 * After this migration two customer user accounts remain:
 *   - customer@lindenhof.de  (retail,    Max Mustermann)
 *   - wholesale@lindenhof.de (wholesale, Bäckerei König)
 */
class m260621_000002_clean_demo_customers extends Migration
{
    /** CRM-only records with no linked user account — safe to drop outright. */
    private const ORPHAN_CUSTOMER_EMAILS = [
        'anna.schmidt@example.de',
        'kontakt@baeckerei-weber.de',
        'markus.becker@example.de',
        'eshkurti@lindenhof.de',
    ];

    private const RETAIL_CUSTOMER_EMAIL = 'customer@lindenhof.de';

    private const WHOLESALE_PASSWORD = 'COSD@groupb';

    public function safeUp(): void
    {
        // 1. Drop every demo order owned by an orphan customer. The order->customer
        //    FK is RESTRICT, so these must go before the customers themselves;
        //    order_item / order_stage_log cascade on order delete.
        $orphanCustomerIds = (new \yii\db\Query())
            ->select('id')
            ->from('{{%customer}}')
            ->where(['email' => self::ORPHAN_CUSTOMER_EMAILS])
            ->column();

        if (!empty($orphanCustomerIds)) {
            $orderIds = (new \yii\db\Query())
                ->select('id')
                ->from('{{%order}}')
                ->where(['customer_id' => $orphanCustomerIds])
                ->column();

            if (!empty($orderIds)) {
                $this->delete('{{%order_stage_log}}', ['order_id' => $orderIds]);
                $this->delete('{{%order_item}}', ['order_id' => $orderIds]);
                $this->delete('{{%order}}', ['id' => $orderIds]);
            }
        }

        // 2. Remove the CRM-only customer records (ignored if not present).
        Customer::deleteAll(['email' => self::ORPHAN_CUSTOMER_EMAILS]);

        // 3. Normalise the remaining retail demo customer.
        $retail = Customer::findOne(['email' => self::RETAIL_CUSTOMER_EMAIL]);
        if ($retail !== null) {
            $retail->name     = 'Max Mustermann';
            $retail->address  = 'Bahnhofstr. 22';
            $retail->postcode = '95028';
            $retail->city     = 'Hof';
            $retail->phone    = '+49 9281 55555';
            // is_wholesale stays 0.
            $retail->save(false);
        }

        // 4. Seed a real wholesale customer user account.
        $user = new User();
        $user->username = 'wholeSaleCustomer';
        $user->email = 'wholesale@lindenhof.de';
        $user->status = 10;
        $user->verification_token = '';
        $user->setPassword(self::WHOLESALE_PASSWORD);
        $user->generateAuthKey();
        $user->save(false);

        // 5. Assign the customer RBAC role to the new user.
        $auth = Yii::$app->authManager;
        $role = $auth->getRole('customer');
        if ($role !== null && $auth->getAssignment('customer', $user->id) === null) {
            $auth->assign($role, $user->id);
        }

        // 6. Create the linked wholesale CRM record.
        $customer = new Customer([
            'user_id'            => $user->id,
            'name'               => 'Bäckerei König',
            'email'              => 'wholesale@lindenhof.de',
            'company'            => 'Bäckerei König',
            'address'            => 'Marktplatz 3',
            'postcode'           => '95032',
            'city'               => 'Hof',
            'country'            => 'Germany',
            'phone'              => '+49 9281 99999',
            'is_wholesale'       => 1,
            'min_order_quantity' => 5,
            'is_active'          => 1,
        ]);
        $customer->save(false);
    }

    public function safeDown(): void
    {
        // One-way cleanup — nothing to revert.
    }
}
