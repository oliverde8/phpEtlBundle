# PHP Etl Bundle

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/oliverde8/phpEtlBundle/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/oliverde8/phpEtlBundle/?branch=main)
[![Build Status](https://scrutinizer-ci.com/g/oliverde8/phpEtlBundle/badges/build.png?b=main)](https://scrutinizer-ci.com/g/oliverde8/phpEtlBundle/build-status/main)
[![Code Coverage](https://scrutinizer-ci.com/g/oliverde8/phpEtlBundle/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/oliverde8/phpEtlBundle/?branch=main)
[![Latest Stable Version](https://poser.pugx.org/oliverde8/php-etl-bundle/v)](//packagist.org/packages/oliverde8/php-etl-bundle) 
[![Total Downloads](https://poser.pugx.org/oliverde8/php-etl-bundle/downloads)](//packagist.org/packages/oliverde8/php-etl-bundle) 
[![Latest Unstable Version](https://poser.pugx.org/oliverde8/php-etl-bundle/v/unstable)](//packagist.org/packages/oliverde8/php-etl-bundle) 
[![License](https://poser.pugx.org/oliverde8/php-etl-bundle/license)](//packagist.org/packages/oliverde8/php-etl-bundle)

The Php etl bundle allows the usage of [Oliver's PHP Etl](https://github.com/oliverde8/php-etl) library in symfony. 
Add's an integration to easy admin as well in order to see a list of the executions:

![List of etl executions](docs/etl-execution-list.png)

And also a details on each execution:

![List of etl executions](docs/etl-execution-details.png)

## Installation

1. Install using composer

2. in `/config/` create a directory `etl`

3. Enable bundle: 
```php
    \Oliverde8\PhpEtlBundle\Oliverde8PhpEtlBundle::class => ['all' => true],
```

4. Add to easy admin
```angular2html
yield MenuItem::linkToCrud('Etl Executions', 'fas fa-list', EtlExecution::class);
```

## Usage

### Creating an ETL chain

First read the documentation of the [PHP ETL](https://github.com/oliverde8/php-etl) 

Each chain is declare in a single file. The name of the chain is the name of the file created in `/config/etl/`. 
**Example:**
```yaml
chain:
  "Dummy Step":
    operation: rule-engine-transformer
    options:
      add: true
      columns:
        test:
          rules:
            - get : {field: [0, 'uid']}
```

### Executing a chain

```sh
./bin/console etl:execute demo '[["test1"],["test2"]]' '{"opt1": "val1"}'
```

The first argument is the input, depending on your chain it can be empty. The second are parameters that 
will be available in the context of each link in the chain. 

### Additional commands

#### Get a definition
```sh
./bin/console etl:get-definition demo
```

## TODO
- Separate the easy admin section in an other bundle. Maybe not necessery.
- Add possibility to create etl chains definitions from the interface.  
- Add the possibility to queue an execution. 
