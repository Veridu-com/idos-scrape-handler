Operation manual
=================

# Configuration

You need to set some environment variables in order to configure the Scraper daemon, such as in the following example:

* `IDOS_VERSION`: indicates the version of idOS API to use (default: '1.0');
* `IDOS_DEBUG`: indicates whether to enable debugging (default: false);
* `IDOS_LOG_FILE`: is the path for the generated log file (default: 'log/cra.log');
* `IDOS_GEARMAN_SERVERS`: a list of gearman servers that the daemon will register on (default: 'localhost:4730');

You may also set these variables using a `.env` file in the project root.

# Running

In order to start the Scraper daemon you should run in the terminal:

```
./scrape-cli.php cra:daemon [-d] [-l path/to/log/file] handlerPublicKey handlerPrivateKey functionName serverList
```

* `handlerPublicKey`: public key of the handler registered within idOS
* `handlerPrivateKey`: private key of the handler registered within idOS
* `functionName`: gearman function name
* `serverList`: a list of the gearman servers
* `-d`: enable debug mode
* `-l`: the path for the log file

Example:

```
./scrape-cli.php scrape:daemon -d -l log/scrape.log ef970ffad1f1253a2182a88667233991 213b83392b80ee98c8eb2a9fed9bb84d scrape localhost
```
