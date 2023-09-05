# Transactional inbox AKA Idempotent consumer

The package is implementation of [Transactional inbox AKA Idempotent consumer](https://microservices.io/patterns/data/transactional-outbox.html) for the project ThirstyX.

Read [this nice explanation](https://softwaremill.com/microservices-101/) of the pattern.

## Installation

The package intended only for the project ThirstyX, it is not in Packagist.

Create folder named **packages** in the same level where resides microservice folders.

Get into it and run `git clone git@github.com:showers-and-bs/transactional-inbox.git`.

The folder structure should look like this:

<pre>
<code>...
&#9500;&#9472;&#9472; packages
&#9474;   &#9492;&#9472;&#9472; transactional-inbox
&#9474;       &#9492;&#9472;&#9472; composer.json
&#9500;&#9472;&#9472; content-service
&#9474;   &#9492;&#9472;&#9472; composer.json
&#9500;&#9472;&#9472; member-service
&#9474;   &#9492;&#9472;&#9472; composer.json
...</code>
</pre>


Now you’ll have to make a slight adjustment to your composer.json file of the main app.

For local development add the following "repositories" key below the "scripts" section.

```json
    "repositories": [
        {
            "type": "path",
            "url": "../packages/transactional-inbox",
            "options": {
                "symlink": true,
                "versions": {
                    "showers-and-bs/transactional-inbox": "dev-master"
                }
            }
        }
    ],
```

You can now require your local package in the Laravel application using chosen namespace of the package.

```sh
composer require showers-and-bs/transactional-inbox:dev-master
```

## Usage

> To be described

