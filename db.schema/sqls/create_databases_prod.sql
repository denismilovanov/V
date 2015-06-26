create role vmeste with password '2gZ&K1_#8lJ' login;
create database logs lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;
create database vmeste lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;
create database gis lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;
create database matches0 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;
create database matches1 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;
create database matches2 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;
create database matches3 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;
create database matches4 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;
create database matches5 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;
create database matches6 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;
create database matches7 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;
create database matches8 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;
create database matches9 lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste;

psql -p 5436 -U postgres -d matches0 -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5436 -U vmeste -d matches0 -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5436 -U postgres -d matches1 -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5436 -U vmeste -d matches1 -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5436 -U postgres -d matches2 -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5436 -U vmeste -d matches2 -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5436 -U postgres -d matches3 -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5436 -U vmeste -d matches3 -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5436 -U postgres -d matches4 -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5436 -U vmeste -d matches4 -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5436 -U postgres -d matches5 -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5436 -U vmeste -d matches5 -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5436 -U postgres -d matches6 -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5436 -U vmeste -d matches6 -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5436 -U postgres -d matches7 -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5436 -U vmeste -d matches7 -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5436 -U postgres -d matches8 -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5436 -U vmeste -d matches8 -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

psql -p 5436 -U postgres -d matches9 -c '\i /home/pg/code/db.schema/matches/queries_before/00_extensions.sql'
psql -p 5436 -U vmeste -d matches9 -h localhost -c '\i /home/pg/code/db.schema/matches/queries_before/01_create_all.sql'

