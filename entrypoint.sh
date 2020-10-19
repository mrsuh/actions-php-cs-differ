#/bin/sh

set -e

PHP_FULL_VERSION=$(php -r 'echo phpversion();')

ARGUMENTS="$@"

php /app/php-cs-fixer --version
echo "## Running PHP CS Fixer with arguments «${ARGUMENTS}»"
echo "## PHP Version: ${PHP_FULL_VERSION}"
echo "## Work directory: $(pwd)"

php /app/php-cs-fixer fix --allow-risky=yes --using-cache=no --dry-run --diff-format=udiff --config=/app/config.php --path-mode=intersection ${ARGUMENTS}