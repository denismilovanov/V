create role vmeste_test with password '1gH&61_#:lJ' login;
create database logs_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database vmeste_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;


create database gis lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;

create database matches0 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches1 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches2 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches3 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches4 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches5 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches6 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches7 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches8 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches9 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;


\c matches0 postgres
\i ~/work/v/db.schema/matches/queries_before/00_extensions.sql
\c matches0 vmeste
\i ~/work/v/db.schema/matches/queries_before/01_create_all.sql
\i ~/work/v/db.schema/matches/functions/get_weight_level.sql

\c matches1 postgres
\i ~/work/v/db.schema/matches/queries_before/00_extensions.sql
\c matches1 vmeste
\i ~/work/v/db.schema/matches/queries_before/01_create_all.sql
\i ~/work/v/db.schema/matches/functions/get_weight_level.sql

\c matches2 postgres
\i ~/work/v/db.schema/matches/queries_before/00_extensions.sql
\c matches2 vmeste
\i ~/work/v/db.schema/matches/queries_before/01_create_all.sql
\i ~/work/v/db.schema/matches/functions/get_weight_level.sql

\c matches3 postgres
\i ~/work/v/db.schema/matches/queries_before/00_extensions.sql
\c matches3 vmeste
\i ~/work/v/db.schema/matches/queries_before/01_create_all.sql
\i ~/work/v/db.schema/matches/functions/get_weight_level.sql

\c matches4 postgres
\i ~/work/v/db.schema/matches/queries_before/00_extensions.sql
\c matches4 vmeste
\i ~/work/v/db.schema/matches/queries_before/01_create_all.sql
\i ~/work/v/db.schema/matches/functions/get_weight_level.sql

\c matches5 postgres
\i ~/work/v/db.schema/matches/queries_before/00_extensions.sql
\c matches5 vmeste
\i ~/work/v/db.schema/matches/queries_before/01_create_all.sql
\i ~/work/v/db.schema/matches/functions/get_weight_level.sql

\c matches6 postgres
\i ~/work/v/db.schema/matches/queries_before/00_extensions.sql
\c matches6 vmeste
\i ~/work/v/db.schema/matches/queries_before/01_create_all.sql
\i ~/work/v/db.schema/matches/functions/get_weight_level.sql

\c matches7 postgres
\i ~/work/v/db.schema/matches/queries_before/00_extensions.sql
\c matches7 vmeste
\i ~/work/v/db.schema/matches/queries_before/01_create_all.sql
\i ~/work/v/db.schema/matches/functions/get_weight_level.sql

\c matches8 postgres
\i ~/work/v/db.schema/matches/queries_before/00_extensions.sql
\c matches8 vmeste
\i ~/work/v/db.schema/matches/queries_before/01_create_all.sql
\i ~/work/v/db.schema/matches/functions/get_weight_level.sql

\c matches9 postgres
\i ~/work/v/db.schema/matches/queries_before/00_extensions.sql
\c matches9 vmeste
\i ~/work/v/db.schema/matches/queries_before/01_create_all.sql
\i ~/work/v/db.schema/matches/functions/get_weight_level.sql
