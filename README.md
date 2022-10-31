![Insitaction](https://www.insitaction.com/wp-content/uploads/2022/09/logo.png)
# Doctrine logger

Doctrine logger is a symfony bundle which allows to log all doctrine transactions.

## Installation:
```bash
composer require insitaction/doctrine-logger-bundle
```

## Usage:
Use Loggeable annotation to log all entity transaction
```php
/**
 * @Loggeable
 * @ORM\Entity
 */
class User
{

...

}
```
