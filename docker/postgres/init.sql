-- Runs once when the postgres container's data volume is first initialized
-- (docker-entrypoint-initdb.d). Creates the non-superuser role the Laravel
-- app actually connects as. Postgres RLS is always bypassed by superusers
-- regardless of FORCE ROW LEVEL SECURITY, so the app must never run as the
-- POSTGRES_USER bootstrap account (which initdb always makes a superuser).
--
-- marketplace_app owns the tables it creates via migrations, so it can
-- ENABLE/FORCE ROW LEVEL SECURITY and CREATE POLICY on them without ever
-- needing a superuser connection at runtime.

CREATE ROLE marketplace_app LOGIN PASSWORD 'marketplace_app_secret';

GRANT CONNECT ON DATABASE marketplace TO marketplace_app;
GRANT USAGE, CREATE ON SCHEMA public TO marketplace_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO marketplace_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO marketplace_app;

CREATE DATABASE marketplace_test OWNER marketplace;
GRANT CONNECT ON DATABASE marketplace_test TO marketplace_app;

\connect marketplace_test

GRANT USAGE, CREATE ON SCHEMA public TO marketplace_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO marketplace_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO marketplace_app;
