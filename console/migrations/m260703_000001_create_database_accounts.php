<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates least-privilege MariaDB accounts for the Lindenhof deployment.
 *
 * Three purpose-scoped accounts are provisioned on the `apiary` schema so that
 * day-to-day application traffic never runs with schema-owner rights:
 *
 *   - lindenhof_admin@localhost  — ALL PRIVILEGES on apiary.*. Schema owner,
 *     used only for running migrations (this account creates the others).
 *   - lindenhof_app@localhost    — SELECT, INSERT, UPDATE, DELETE on apiary.*
 *     only. No DDL, cannot create users or alter the schema. This is the
 *     account the running application should authenticate as.
 *   - lindenhof_report@localhost — SELECT only on apiary.*. Read-only reporting
 *     account. Not wired into any code yet; provisioned so the grants exist.
 *
 * The passwords below are generated fresh for this migration. They must match
 * the credentials configured on the server in the (gitignored) *-local.php
 * config files; update those out-of-band when applying this migration.
 *
 * Uses raw SQL via createCommand() because account management is DCL, which the
 * Yii schema builder does not model.
 */
class m260703_000001_create_database_accounts extends Migration
{
    /**
     * Fresh random passwords for the three accounts. Not reused from any other
     * credential in this project. Keep these in sync with the server config.
     */
    private const ADMIN_PASSWORD  = 'BE9SrvsQ@f*4xVZhvvFeOYWaXy!h0KXg';
    private const APP_PASSWORD     = '#5d*5c8K82j7K4zmY35etwFiyTXHN7Af';
    private const REPORT_PASSWORD  = 'I^8h2llJMHKoIKY_0M6xaI-4%N=t*q34';

    public function safeUp(): void
    {
        // ── lindenhof_admin — schema owner, migrations only ───────────────
        $this->db->createCommand(
            "CREATE USER IF NOT EXISTS 'lindenhof_admin'@'localhost' "
            . 'IDENTIFIED BY ' . $this->db->quoteValue(self::ADMIN_PASSWORD),
        )->execute();
        $this->db->createCommand(
            "GRANT ALL PRIVILEGES ON `apiary`.* TO 'lindenhof_admin'@'localhost'",
        )->execute();

        // ── lindenhof_app — application DML only, no DDL ──────────────────
        $this->db->createCommand(
            "CREATE USER IF NOT EXISTS 'lindenhof_app'@'localhost' "
            . 'IDENTIFIED BY ' . $this->db->quoteValue(self::APP_PASSWORD),
        )->execute();
        $this->db->createCommand(
            "GRANT SELECT, INSERT, UPDATE, DELETE ON `apiary`.* "
            . "TO 'lindenhof_app'@'localhost'",
        )->execute();

        // ── lindenhof_report — read-only reporting ────────────────────────
        $this->db->createCommand(
            "CREATE USER IF NOT EXISTS 'lindenhof_report'@'localhost' "
            . 'IDENTIFIED BY ' . $this->db->quoteValue(self::REPORT_PASSWORD),
        )->execute();
        $this->db->createCommand(
            "GRANT SELECT ON `apiary`.* TO 'lindenhof_report'@'localhost'",
        )->execute();

        $this->db->createCommand('FLUSH PRIVILEGES')->execute();
    }

    public function safeDown(): void
    {
        $this->db->createCommand(
            "DROP USER IF EXISTS 'lindenhof_app'@'localhost'",
        )->execute();
        $this->db->createCommand(
            "DROP USER IF EXISTS 'lindenhof_report'@'localhost'",
        )->execute();
        $this->db->createCommand(
            "DROP USER IF EXISTS 'lindenhof_admin'@'localhost'",
        )->execute();

        $this->db->createCommand('FLUSH PRIVILEGES')->execute();
    }
}
