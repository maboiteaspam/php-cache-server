# PHP cache server

A replacement for redis and consorts cache servers to use in your dev environments.

Hey, it s only for DEV, ok ? It s blocking, slow, wrote in php blah blah ect. But a useful drop-in replacement.

And anyway, it can accept only 1 client at a time.

## Setup

## Usage

It might need some portage to well-known php stacks such symfony.
Or you may use it directly at your convenience.

## Notes

I would not try to use the server with an http (apache nginx ect) server.
Reason is that it writes some logs to stderr, and that might have weird effects under those application servers (never tried yet).

I have avoided that in the client, so you can go ahead.

## misc

Using it to avoid file caching, but more persistent than array cache.
