create extension postgis;
create extension postgis_topology;
create extension postgres_fdw;
create extension intarray;

CREATE SERVER main_server FOREIGN DATA WRAPPER postgres_fdw OPTIONS (host 'localhost', dbname 'vmeste_test', port '5433');
alter server  main_server owner to vmeste_test;
CREATE USER MAPPING FOR vmeste_test SERVER main_server OPTIONS (user 'vmeste_test', password '1gH&61_#:lJ');
