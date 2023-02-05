# 1.1.0

- :star2: Support for php-etl 1.1 new operations has been added.
- :star2: Support for symfony 6.0 has been added.
- :star2: Support for using input parameters in operation options has been added.
- :collision: All operations are no longer built during DI compilation. This should improve peformance of cache warmup.
- :collision: Support for Symfony 4.4 has been dropped.
- :collision: Support for php 7.4 has been dropped.

# 1.0.4
- :wrench: Fix logs not being saved in some conditions.
- 
# 1.0.3
- :wrench: Fix context & other objects being shared between executions when using Messenger.

# 1.0.2
- :wrench: Fix incase of failure etl execution being duplicated and loosing logs and files.

# 1.0.1
- :wrench: Fix etl execution not reloaded from database at end of process. This is necessary if the doctrine memory is flushed in the etl process.

# 1.0.0
- :confetti_ball: :tada: First stable release :tada: :confetti_ball:
- :star2: Added support for php etl 1.0 stable release.
- :star2: Added support for symfony 6
- :star2: Better error management, 
- :star2: File abstraction layer for storing etl related files. (Thanks to elt 1.0)
- :star2: Logger proxy allows all logs written in etl to have etl context data. (Thanks to elt 1.0)

# 1.0.0 Alpha #3
- :star2: Split easy admin section into another bundle! **This is a BC change**

# 1.0.0 Alpha #2

- :star2: Split chain execution into2 public function for more flexibility.
- :star2: Each execution has dedicated folder with it's logs.
- :star2: You can see the logs of the execution in the interface.
- :star2: Added a json editor/viewer to improve usability.
- :star2: You can download all files used during an execution from the interface.
- :star2: Dashboard allow you to monitor & see the executions.
- :star2: User can queue chain executions from the interface
- :star2: Added `etl-clean_old_executions` chain to cleanup old executions.
- :wrench: Fixed deprecation on console commands.
- :wrench: Various fixes and improvements.

# 1.0.0 Alpha #1
- :confetti_ball: :tada: First release :tada: :confetti_ball:
