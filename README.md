# PHP Etl Bundle

The Php etl bundle allows the usage of [Olivers PHP Etl](https://github.com/oliverde8/php-etl) library in symfony. 
Add's an integration to easy admin as well to be able see executions history & errors from an interface. 

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

## Creating a ETL

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

## Executing a chain

```sh
./bin/console etl:execute demo '[["test1"],["test2"]]' '{"opt1": "val1"}'
```

## Additional commands

### Get a definition
```sh
./bin/console etl:get-definition demo
```

## TODO
- Separate the easy admin section in an other bundle. 
- Add possibility to create etl chains from the interface 
- Add the possibility to queue an execution. 
