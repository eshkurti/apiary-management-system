<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Company profile — single-row table holding the legal Tierhalter identity.
 * Used in Bestandsbuch export headers under EU Regulation 2019/6 Art. 108(2)(f).
 *
 * @property int    $id
 * @property string $company_name
 * @property string $keeper_name
 * @property string $address
 * @property string $postcode
 * @property string $city
 * @property string|null $phone
 * @property string|null $email
 * @property int    $updated_at
 * @property int    $updated_by
 */
class CompanyProfile extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%company_profile}}';
    }

    public function behaviors(): array
    {
        return [
            [
                'class'              => TimestampBehavior::class,
                'createdAtAttribute' => false,
                'updatedAtAttribute' => 'updated_at',
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['company_name', 'keeper_name', 'address', 'postcode', 'city'], 'required'],
            [['company_name', 'keeper_name'], 'string', 'max' => 150],
            [['address'], 'string', 'max' => 255],
            [['postcode'], 'string', 'max' => 20],
            [['city'], 'string', 'max' => 100],
            [['phone'], 'string', 'max' => 50],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 150],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'company_name' => 'Company Name',
            'keeper_name'  => 'Tierhalter (Keeper Name)',
            'address'      => 'Street Address',
            'postcode'     => 'Postcode',
            'city'         => 'City',
            'phone'        => 'Phone',
            'email'        => 'Email',
        ];
    }

    /**
     * Returns the single company profile row, creating a default if none exists.
     */
    public static function getInstance(): self
    {
        // Single-row table: return the existing row whatever its primary key,
        // rather than assuming id = 1 (the seeded row may not be id 1).
        $instance = self::find()->orderBy(['id' => SORT_ASC])->one();
        if ($instance === null) {
            $instance = new self([
                'company_name' => 'Honigmanufaktur Lindenhof',
                'keeper_name'  => '',
                'address'      => '',
                'postcode'     => '',
                'city'         => '',
            ]);
        }
        return $instance;
    }
}
