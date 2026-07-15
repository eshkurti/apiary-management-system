-- Local development database setup for Honigmanufaktur Lindenhof.
--
-- Run once, as root, before the first `php yii migrate`:
--   mysql -u root -p < local-db-setup.sql
--
-- Creates the `apiary` database and the `apiary` application user with broad
-- privileges (not just scoped to `apiary`.*). The broad grant is deliberate:
-- migration m260703_000001_create_database_accounts runs CREATE USER,
-- GRANT ... WITH GRANT OPTION, and FLUSH PRIVILEGES — three global
-- administrative privileges that a database-scoped grant cannot satisfy, no
-- matter how widely you grant it on `apiary`.* alone. See SETUP_GUIDE.md
-- Step 4 for how this was actually confirmed (three separate "access denied"
-- errors, one per missing privilege, chasing them individually).
--
-- This is a local development convenience, not a security template — it does
-- not reflect how the production server's accounts are provisioned (see
-- "Database access model" in README.md for that).

CREATE DATABASE IF NOT EXISTS apiary CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'apiary'@'localhost' IDENTIFIED BY 'apiary_secret';

GRANT ALL PRIVILEGES ON *.* TO 'apiary'@'localhost' WITH GRANT OPTION;

FLUSH PRIVILEGES;
