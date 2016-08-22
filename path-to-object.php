#!/usr/bin/env php
<?php

// Karel Bemelmans
// mail@karelbemelmans.com
// 2016/08/21

/******************************************************************************/
// Main program execution
/******************************************************************************/

// We simply use a global variable to store our data structure in, this isn't
// pretty but it works.
global $root;

// 1. Read the config
parse_config('config.txt');
// print_r($root);

// 2. Process user input
// We fix the case for the input so it matches the current example config.txt
if (3 === $argc) {
  $start_location = ucwords($argv[1]);
  $object         = strtolower($argv[2]);

  find_object($start_location, $object);
}
else {
  printf("Usage: %s start-location object\n\n", $argv[0]);
}

/******************************************************************************/
// Helper functions
/******************************************************************************/

// Parse config file
function parse_config($file) {
  global $root;

  // Clear the current inventory
  $root = new stdClass();
  $root->paths = array(); // Holds paths parsed from the config file
  $root->graph = new Graph(); // The Dijkstra Graph object that does the magic
  $root->items = array(); // An index of all items we know the location of

  // Using very basic regex matching to parse this file.
  // Also assuming the input is safe.
  $handle = fopen($file, "r");

  // Parse input data
  if (!$handle) {
    print "ERROR: error opening the file.";
    return FALSE;
  }

  // Continue parsing the input file
  $i = 0;
  while (($line = fgets($handle)) !== false) {
    $i++;

    // Parse the "location - object" lines in the file
    if (preg_match('/(.*)\s\-\s(.*)/i', $line, $matches)) {

      $location = $matches[1];
      $object   = $matches[2];

      save_object($location, $object);
    }
    else {
      print "Failed to parse line $i\n";
    }
  }

  fclose($handle);

  // Build our Dijkstra graph from our input paths, ready for finding paths
  build_graph();

  return TRUE; // Everything went ok
}

// Save an object to our $root inventory object:
//
// 1. Save the paths we find along the way in $root->graphs
// 2. Save the exact location of the item in $root->items
function save_object($location, $object) {
  global $root;

  // Parse the location pieces
  if ($pieces = explode(':', $location)) {

    $previous = NULL;
    foreach($pieces as $piece) {
      if ($previous) {
        // We register both ways with a weight of 1 for now.
        // If we want to limit the times we go up, we chose a weight higher than 1
        register_path($previous, $piece, 1);
        register_path($piece, $previous, 1);
      }
      $previous = $piece;
    }

    // Add the item to the item list
    reset($pieces);
    $last_location = array_pop($pieces);

    if (!in_array($object, $root->items)) {
      $root->items[$object] = $last_location;
    }
  }
}

// Register that there is a path between $a and $b
function register_path($a, $b, $weight = 1) {
  global $root;

  if (!isset($root->paths[$a])) {
    $root->paths[$a] = array($b => $weight);
  }
  else {
    if (!in_array($b, $root->paths[$a])) {
      $root->paths[$a][$b] = $weight;
    }
  }
}

// Use the $root->paths we build from our configuration file to construct the
// Graph object $root->graph that will run our Dijkstra algoritm.
//
// The add_vertex requires the complete list of paths as input, so we cannot
// call this functions when we are still discovering the config file input.
function build_graph() {
  global $root;

  foreach($root->paths as $location => $connections) {
    $root->graph->add_vertex($location, $connections);
  }
}

// Find a specific object starting from given location
function find_object($current_location, $object) {
  global $root;
  print "Looking for '$object' starting from location '$current_location'...\n";

  // If the item does not exit in the items index it simply does not exist
  if (!isset($root->items[$object])) {
    print "This item could not be found. Program ended.\n\n";
    return;
  }

  // Does the location exist?
  if (!isset($root->paths[$current_location])) {
    print "This location does not exist. Program ended.\n\n";
    return;
  }

  // Get the location where the item resides
  $found_location = $root->items[$object];
  print "Found location of item: $found_location. Now building path how to reach item:\n\n";

  // Use our Dijkstra Graph to find the shortest path, if it exists.
  if (!$path = $root->graph->shortest_path($current_location, $found_location)) {
    print "Object was not found.\n";
    return FALSE;
  }

  // The object was found, print the steps needed to take.
  foreach ($path as $i => $step) {
    if (0 === $i) {
      printf("You are in %s.\n", $current_location);
    }
    else {
      printf("Go to %s.\n", $step);
    }
  }
  printf("Get %s.\n", $object);

  return TRUE;
}

/******************************************************************************/
// External library, pasted inline for ease of use.
/******************************************************************************/

// Code from: https://github.com/mburst/dijkstras-algorithm/blob/master/dijkstras.php

// Bad formatting left on purpose. Lazy coder.

class PriorityQueue extends SplPriorityQueue
{
    public function compare( $priority1, $priority2 )
    {
        if ($priority1 === $priority2) return 0;
        return $priority1 < $priority2 ? 1 : -1;
    }
}
class Graph
{
  private $verticies;
  function __construct()
  {
    $this->verticies = array();
  }
  public function add_vertex( $name, $edges )
  {
    $this->verticies[ $name ] = $edges;
  }
  public function shortest_path( $start, $finish )
  {
    $distances = array();
    $previous = array();
    $nodes = new PriorityQueue();
    foreach ( $this->verticies AS $vertex => $value )
    {
      if ( $vertex === $start )
      {
        $distances[ $vertex ] = 0;
        $nodes->insert( $vertex, 0 );
      }
      else
      {
        $distances[ $vertex ] = PHP_INT_MAX;
        $nodes->insert( $vertex, PHP_INT_MAX );
      }
      $previous[ $vertex ] = null;
    }
    $nodes->top();
    while ( $nodes->valid() )
    {
      $smallest = $nodes->current();
      if ( $smallest === $finish )
      {
        $path = array();
        while ( $previous[ $smallest ] )
        {
          $path[] = $smallest;
          $smallest = $previous[ $smallest ];
        }
        $path[] = $start;
        return array_reverse( $path );
      }
      if ( $smallest === null || $distances[ $smallest ] === PHP_INT_MAX )
      {
        break;
      }
      foreach ( $this->verticies[ $smallest ] AS $neighbor => $value )
      {
        $alt = $distances[ $smallest ] + $this->verticies[ $smallest ][ $neighbor ];
        if ( $alt < $distances[ $neighbor ] )
        {
          $distances[ $neighbor ] = $alt;
          $previous[ $neighbor ] = $smallest;
          $nodes->insert( $neighbor, $alt );
        }
      }
      $nodes->next();
    }
    return $distances;
  }
  public function __toString()
  {
    return print_r( $this->verticies, true );
  }
}
