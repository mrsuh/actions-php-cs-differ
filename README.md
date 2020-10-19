# Action PHP-CS-Differ

https://github.com/FriendsOfPHP/PHP-CS-Fixer/ with diff type only

## Usage
.github/workflows/lint.yml
```yaml
name: Main
on: [push, pull_request]
jobs:
  php-cs-differ:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: PHP CS Differ
        uses: docker://mrsuh/actions-php-cs-differ@master
        with:
          directory: 'src/'
```

## TypeHints

### Resource type hint
https://wiki.php.net/rfc/resource_typehint

If you want to specify a `resource` type hint - use annotations
```php
/**
 * @param resource $resource
 * @return resource
 */
function test($resource) {
    return $resource;
}
```

### Mixed type hint

If you want to specify a `mixed` type hint - use annotations
```php
/**
 * @param mixed $resource
 * @return mixed
 */
function test($mixed) {
    return $mixed;
}
```

### $this return type hint
https://github.com/bmewburn/vscode-intelephense/issues/911

If you want to specify a `$this` type hint - use annotations
```php
class A {
    /**
    * @return $this
    */
    function test() {
        return $this;
    }
}
```