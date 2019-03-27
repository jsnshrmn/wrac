# Wikipedia Ranked Articles by Category (WRAC)

## Installation

- Install docker and docker-compose
- Clone this repository

## Running

- run `docker-compose up` in this directory
- WRAC should now be running at `http://localhost/`

## Troubleshooting

- the php-fpm container may come up before composer finishes installing dependencies. Trying to access this before that's done will result in an error. Wait for it composer to finish, or kill and restart docker-compose.
