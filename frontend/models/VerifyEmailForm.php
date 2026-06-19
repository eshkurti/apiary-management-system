<?php

declare(strict_types=1);

namespace frontend\models;

use common\models\Customer;
use common\models\User;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\Model;

class VerifyEmailForm extends Model
{
    private User|null $_user = null;
    /**
     * Creates a form model with given token.
     *
     * @param string $token
     * @param array $config name-value pairs that will be used to initialize the object properties
     * @throws InvalidArgumentException if token is empty or not valid
     */
    public function __construct(string $token, array $config = [])
    {
        if ($token === '') {
            throw new InvalidArgumentException('Verify email token cannot be blank.');
        }

        $this->_user = User::findByVerificationToken($token);

        if ($this->_user === null) {
            throw new InvalidArgumentException('Wrong verify email token.');
        }

        parent::__construct($config);
    }

    /**
     * Verify email
     *
     * @return User|null the saved model or null if saving fails
     */
    public function verifyEmail(): User|null
    {
        $user = $this->_user;

        $user->status = User::STATUS_ACTIVE;

        if (!$user->save(false)) {
            return null;
        }

        // Activate the account with the customer role so it can shop (AC-EC-02.5).
        $auth = Yii::$app->authManager;
        if ($auth !== null) {
            $role = $auth->getRole('customer');
            if ($role !== null && $auth->getAssignment('customer', $user->id) === null) {
                $auth->assign($role, $user->id);
            }
        }

        $this->ensureCustomerRecord($user);

        return $user;
    }

    /**
     * Creates the linked CRM Customer record at verification time so a verified
     * buyer has a record before they place their first order. If a customer row
     * already exists for this email (e.g. created by an administrator), it is
     * linked to the user instead of duplicating it. CheckoutController then finds
     * this record rather than creating one lazily.
     */
    private function ensureCustomerRecord(User $user): void
    {
        if (Customer::findOne(['user_id' => $user->id]) !== null) {
            return;
        }

        $existing = Customer::findOne(['email' => $user->email]);
        if ($existing !== null) {
            $existing->user_id = $user->id;
            $existing->save(false);
            return;
        }

        $customer = new Customer([
            'user_id'      => $user->id,
            'name'         => $user->username,
            'email'        => $user->email,
            'country'      => 'Germany',
            'is_active'    => 1,
            'is_wholesale' => 0,
        ]);
        $customer->save(false);
    }
}
