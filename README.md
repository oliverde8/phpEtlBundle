# PHP Etl Bundle

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/oliverde8/phpEtlBundle/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/oliverde8/phpEtlBundle/?branch=main)
[![Build Status](https://scrutinizer-ci.com/g/oliverde8/phpEtlBundle/badges/build.png?b=main)](https://scrutinizer-ci.com/g/oliverde8/phpEtlBundle/build-status/main)
[![Code Coverage](https://scrutinizer-ci.com/g/oliverde8/phpEtlBundle/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/oliverde8/phpEtlBundle/?branch=main)
[![Latest Stable Version](https://poser.pugx.org/oliverde8/php-etl-bundle/v)](//packagist.org/packages/oliverde8/php-etl-bundle) 
[![Total Downloads](https://poser.pugx.org/oliverde8/php-etl-bundle/downloads)](//packagist.org/packages/oliverde8/php-etl-bundle) 
[![Latest Unstable Version](https://poser.pugx.org/oliverde8/php-etl-bundle/v/unstable)](//packagist.org/packages/oliverde8/php-etl-bundle) 
[![License](https://poser.pugx.org/oliverde8/php-etl-bundle/license)](//packagist.org/packages/oliverde8/php-etl-bundle)

The Php etl bundle allows the usage of [Oliver's PHP Etl](https://github.com/oliverde8/php-etl) library in symfony. 

You should also check the [PHP ETL's Easy Admin Bundle](https://github.com/oliverde8/phpEtlEasyadminBundle) to have interfaces.

## Installation

1. Install using composer

2. in `/config/` create a directory `etl`

3. Enable bundle: 
```php
    \Oliverde8\PhpEtlBundle\Oliverde8PhpEtlBundle::class => ['all' => true],
```

4. Optional: Enable queue if you wish to allow users from the easy admin panel to do executions.
```yaml
framework:
  messenger:
    routing:
        "Oliverde8\PhpEtlBundle\Message\EtlExecutionMessage": async
```

5. Optional: Enable creation of individual files for each log by editing the monolog.yaml
```yaml
etl:
    type: service
    id: Oliverde8\PhpEtlBundle\Services\ChainExecutionLogger
    level: debug
    channels: ["!event"]
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

## Observability — live execution graph

The bundle exposes a framework-agnostic **live execution graph**: the chain's
topology plus per-operation state (items in/out, time, async in flight) and a
streaming log tail. It is designed to be reused by any Symfony frontend
(EasyAdmin, Sylius, a custom admin) — all the logic lives here, the frontend
only mounts routes, loads the assets and renders one Twig partial.

### How it works

* `Graph\ChainGraphBuilder` turns a chain processor into a `{nodes, edges}` topology.
* `Graph\RunStateNormalizer` turns the persisted/live `OperationState` into a
  node-keyed state map (both use the same dotted-path node ids, incl. split branches).
* `Controller\ExecutionObservabilityController` serves three read-only JSON
  endpoints (guarded by `EtlExecutionVoter::VIEW`):
  * `GET .../etl/executions/{id}/graph` — topology + last persisted state
  * `GET .../etl/executions/{id}/state` — latest run-state (poll fallback)
  * `GET .../etl/executions/{id}/logs?offset=` — incremental log tail
* `Resources/public/{js,css}` ship a dependency-light Cytoscape widget (Cytoscape
  and dagre are vendored under `Resources/public/vendor`), and
  `@Oliverde8PhpEtl/observability/graph.html.twig` renders the container.

### Wiring it into a frontend

1. Mount the routes (any prefix; put them behind your admin firewall):
   ```yaml
   # config/routes/oliverde8_etl.yaml
   oliverde8_php_etl_observability:
       resource: '@Oliverde8PhpEtlBundle/Controller/'
       type: attribute
       prefix: /admin
   ```
2. Publish the assets: `bin/console assets:install public`.
3. Load the assets on the page that shows the graph and render the partial:
   ```twig
   <link rel="stylesheet" href="/bundles/oliverde8phpetl/css/execution-graph.css">
   <script src="/bundles/oliverde8phpetl/vendor/cytoscape.min.js"></script>
   <script src="/bundles/oliverde8phpetl/vendor/dagre.min.js"></script>
   <script src="/bundles/oliverde8phpetl/vendor/cytoscape-dagre.min.js"></script>
   <script src="/bundles/oliverde8phpetl/js/execution-graph.js"></script>

   {% include '@Oliverde8PhpEtl/observability/graph.html.twig' with { execution: execution } %}
   ```
   (The EasyAdmin bundle does exactly this for you on the execution detail page.)

### Real-time updates (optional Mercure)

The graph degrades gracefully by design:

| Setup | Behaviour |
|---|---|
| `symfony/mercure-bundle` installed + hub configured | **live push** over Mercure (SSE) |
| running execution, no Mercure | polls `/state` and `/logs` |
| finished execution | fully static graph from persisted state |

Nothing is required to get the static/poll graph. Install `symfony/mercure-bundle`
to light up real-time — the bundle then auto-registers a Mercure publisher
(`MercureExecutionStatePublisher`) and streams state + logs from the worker as the
chain runs; otherwise a no-op publisher is used. Pass the hub's public URL + topic
to the partial via a `mercure: {url, topic}` variable to enable the client side.

> Real-time only applies to executions run asynchronously (a messenger worker);
> with the `sync` transport the chain runs in-request and the graph is static.

### Adding your own chain operation

To add your own chain operation you need 2 classes. The operation itself that we will call 
`MyVendor\Etl\Operation\OurTestOperation`, and a `MyVendor\Etl\OperationFactory\OurTestOperationFactory` factory 
to create it. The factory allows us to configure the operation and inject service to our operation.

All operations needs to implement `DataChainOperationInterface`; they can extend `AbstractChainOperation`. 

All factories needs to extend `Oliverde8\Component\PhpEtl\Builder\Factories\AbstractFactory`. 

The operation is a Model and not a service, you therefore need to add the path to the exclusions so that it's not
made a service by symfony: 
```yaml
App\:
  resource: '../src/'
  exclude:
    - '../src/Etl/Operation'
```

Factories needs to be tagged `etl.operation-factory\ . To remove the need to tag all your factories you can add 
the following line your your services.yaml file
```yaml
    MyVendor\Etl\OperationFactory\:
        resource: '../src/Etl/OperationFactory/'
        tags: ['etl.operation-factory']
```

For more information on how the etl works and how to create operations check the [Php Etl Documentation](https://github.com/oliverde8/php-etl#creating-you-own-operations)
