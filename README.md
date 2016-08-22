# Fun with PHP

## Requirements

PHP 5.3 or later.

## How to run

  `./path-to-object.php bedroom keys`
  `./path-to-object.php kitchen knife`

## Short explanation about my code:

  1. Parse the configuration file
  2. Convert the parsed data to a Graph object that implements the Dijkstra shortest path algoritm
  3. Add some glue code (I left some commented debug statements on purpose)

## Bonus question:

Bonus 1: This code already supports this. Just add multiple config.txt entries with different paths.
Bonus 2: Adjust the weights on line 97 and 98, make the upwards path a higher value.

## Limitations

This Dijkstra PHP Class gives a strange result when there is no path found. This could be handled better, but seeing that is not the point of this exercise I will not dive deeper into this.
