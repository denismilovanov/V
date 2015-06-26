create role vmeste_test with password '1gH&61_#:lJ' login;
create database logs_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database vmeste_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database gis_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;
create database matches0_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches1_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches2_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches3_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches4_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches5_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches6_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches7_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches8_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database matches9_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;

psql -p 5433 -U postgres -d matches0_test -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5433 -U vmeste_test -d matches0_test -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5433 -U postgres -d matches1_test -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5433 -U vmeste_test -d matches1_test -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5433 -U postgres -d matches2_test -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5433 -U vmeste_test -d matches2_test -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5433 -U postgres -d matches3_test -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5433 -U vmeste_test -d matches3_test -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5433 -U postgres -d matches4_test -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5433 -U vmeste_test -d matches4_test -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5433 -U postgres -d matches5_test -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5433 -U vmeste_test -d matches5_test -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5433 -U postgres -d matches6_test -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5433 -U vmeste_test -d matches6_test -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5433 -U postgres -d matches7_test -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5433 -U vmeste_test -d matches7_test -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5433 -U postgres -d matches8_test -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5433 -U vmeste_test -d matches8_test -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5433 -U postgres -d matches9_test -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5433 -U vmeste_test -d matches9_test -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'
