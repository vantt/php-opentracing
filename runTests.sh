#!/bin/sh

rundocker () {
    tty=
    tty -s && tty=--tty

    docker run \
        $tty \
        --interactive \
        --rm \
        \
        --volume /etc/passwd:/etc/passwd:ro \
        --volume /etc/group:/etc/group:ro \
        \
        --volume $(pwd):/app \
        --workdir /app \
        \
        ${DOCKER_IMAGE} "$@"
}

IMAGES="devops/phpcli:7.0 devops/phpcli:5.6"

for DOCKER_IMAGE in ${IMAGES}; do
    rundocker  sh -c  "
                   rm -rf vendor && rm -f composer.lock &&
                   wget -O composer https://getcomposer.org/composer-2.phar &&
                   chmod +x composer &&
                   ./composer install &&
                   php /app/vendor/phpunit/phpunit/phpunit --configuration /app/phpunit.xml --color=always --testdox --verbose &&
                   rm -rf vendor && rm -f composer.lock && rm composer
               "
done