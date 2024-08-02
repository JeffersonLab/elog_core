# Prepares a Drupal ^10.2 installation for Electronic Logbook application

## Usage Examples

Install and uninstall using drush command line
```
vendor/bin/drush en elog_core
vendor/bin/drush pmu elog_core
```

For some reason the uninstall won't remove tags even though it will remove logbooks.
We have to do it with drush
```shell
# The shell script contains the commands for convenience
./config-delete.sh

vendor/bin/drush config:delete taxonomy.vocabulary.tags
vendor/bin/drush config:delete field.storage.node.field_tags
```

## Running Tests
```shell
# Specific directory
./vendor/bin/phpunit -c web/core/ --testsuite functional web/modules/custom/elog_core/tests/src/Functional/
./vendor/bin/phpunit -c web/core/ --testsuite unit web/modules/custom/elog_core/tests/src/Unit/


# Using attributes from test class annotations
 ./vendor/bin/phpunit -c web/core/ --testsuite functional --group elog_core

```

## References

[https://www.digitalnadeem.com/drupal/how-to-create-additional-fields-programmatically-in-user-account-and-displaying-same-in-user-registration-page-drupal-9-and-drupal-10/]()

[https://www.digitalnadeem.com/drupal/how-to-create-custom-field-in-drupal-9/]()
