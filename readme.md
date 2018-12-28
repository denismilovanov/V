Backend of the Tinder-like dating service.

The aim is to find matching users, sort them by weights, and show to user.

Weights depend on common friends and communities, distance between users, popularity, friendliness.

Exactly, the straight `ORDER BY` is a BAD solution.

Let's maintain list of matching users in PostgreSQL arrays (`integer[]`), splitted by weight level (`weight = 23.1 => weight_level = 23`).

Extension `intarray` is used for fast array operations (`icount`, `&`).

Tables with lists are distributed among N databases (`user_id % N`), and filled up in batch mode with data taken from the main database and its replicas through `postgres_fdw`.

Laravel 5.0 (Lumen), PostgreSQL 9.4, RabbitMQ 3.5.
