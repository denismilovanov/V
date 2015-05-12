create role vmeste_test with password '1gH&61_#:lJ' login;
create database logs_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
create database vmeste_test lc_ctype='ru_RU.UTF-8' lc_collate='ru_RU.UTF-8' template=template0 owner vmeste_test;
