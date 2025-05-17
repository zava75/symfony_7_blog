-- Проверка наличия базы данных
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_database WHERE datname = 'symfony') THEN
        CREATE DATABASE symfony
            WITH ENCODING 'UTF8'
            LC_COLLATE='en_US.utf8'
            LC_CTYPE='en_US.utf8'
            TEMPLATE=template0;
    END IF;
END $$;

-- Проверка наличия пользователя
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'symfony') THEN
        CREATE USER symfony WITH PASSWORD 'secret';
    END IF;
END $$;

-- Проверка прав доступа
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_catalog.pg_user u
        JOIN pg_catalog.pg_roles r ON r.rolname = u.usename
        WHERE u.usename = 'symfony' AND r.rolname = 'symfony'
    ) THEN
        GRANT ALL PRIVILEGES ON DATABASE symfony TO symfony;
    END IF;
END $$;
