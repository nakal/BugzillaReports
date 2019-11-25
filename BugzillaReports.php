<?php
/**
 * The bugzilla report objects
 */

/**
 * Copyright (C) 2008 - Ian Homer & bemoko
 *
 * This program is free software; you can redistribute it and/or modify it 
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) 
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but 
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY 
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for    
 * more details.
 * 
 * You should have received a copy of the GNU General Public License along 
 * with this program; if not, see <http://www.gnu.org/licenses>.
 */

class BugzillaReports extends BMWExtension {

  # The handle on the query object
  var $query;

  # Default max rows for a report       
  var $maxrowsFromConfig;
  var $maxrowsFromConfigDefault=100;
  
  var $dbdriverDefault="mysql";

  # Default max rows which are used for aggregation of a bar chart report
  var $maxrowsForBarChartFromConfig;
  var $maxrowsForBarChartFromConfigDefault=500;

  # Output raw HTML (i.e. not Wiki output)
  var $rawHTML;

  public $dbuser,$bzserver,$interwiki;
  public $database,$host,$password;
  public $dbdriver;
  public $dbencoding;
  public $instanceNameSpace;
  
  function __construct( &$parser ) {
    $this->parser =& $parser; 
  }

  /**
   * Register the function hook
   */
  public static function parserFirstCallInit(Parser $parser) {
    $parser->setHook('bugzilla', 'BugzillaReports::parserHook');
    $parser->setFunctionHook('bugzilla', 'BugzillaReports::parserFunctionHook');
    return true;
  }

  /**
   * Call to render the bugzilla report
   */
  public static function parserHook( $input, array $args, Parser $parser, PPFrame $frame ) {
    $parserArgs = [&$parser];
    foreach ($args as $k => $v) {
      $parserArgs[] = $k."=".$v;
    }
    return call_user_func_array('BugzillaReports::parserFunctionHook', $parserArgs);
  }

  public static function parserFunctionHook( Parser &$parser ) {
    $args = func_get_args();
    array_shift($args);
    $bugzillaReport = new BugzillaReports( $parser );
    return array( $parser->recursiveTagParse( $bugzillaReport->render($args) ), 'noparse' => true, 'isHTML' => true);
  }

  public function render($args) {   
    global $wgBugzillaReports;
    global $wgDBserver,$wgDBname,$wgDBuser,$wgDBpassword;

    # Initialise query
    $this->dbdriver=$this->getProperty("dbdriver",$this->dbdriverDefault);
    $connector;
    switch ($this->dbdriver) {
      case "pg" :
        $connector=new BPGConnector($this);       
        break;
      default :
        $connector=new BMysqlConnector($this);
    }
    
    $this->query=new BugzillaQuery($connector);

    #
    # Process arguments from default setting across all the wiki
    #
    $this->extractOptions(explode("|",$this->getProperty("default")));
    #
    # Process arguments for this particular query
    #
    $this->extractOptions($args);

    if ($this->query->get("instance") != null) {
      $this->instanceNameSpace=$this->query->get("instance");
    }
    
    #
    # Allow the user to specify alternate DB connection info by name
    # in his query.
    #
    
    if ($this->query->get("bzalternateconfig") != null) {
#
# The user has asked for an alternate BZ iestall to be queried.
#
      $alternateConfigName = $this->query->get("bzalternateconfig");
      $bzAlternateConfigs = $this->getProperty("bzAlternateConfigs");
      if (is_array($bzAlternateConfigs["$alternateConfigName"])) {
#
# We appear to have an array...set values.
#
        $this->dbuser=$bzAlternateConfigs["$alternateConfigName"]["user"];
        $this->bzserver=$bzAlternateConfigs["$alternateConfigName"]["bzserver"];
        $this->database=$bzAlternateConfigs["$alternateConfigName"]["database"];
        $this->host=$bzAlternateConfigs["$alternateConfigName"]["host"];
        $this->password=$bzAlternateConfigs["$alternateConfigName"]["password"];
      }
    } else {
      #
      # Use the defaults from LocalConfig
      #
      $this->dbuser=$this->getProperty("user",$wgDBuser);
      $this->bzserver=$this->getProperty("bzserver", null);
      $this->database=$this->getProperty("database");
      $this->host=$this->getProperty("host");
      $this->password=$this->getProperty("password");
    }
    
    $this->interwiki=$this->getProperty("interwiki", null);
    $this->dbencoding=$this->getProperty("dbencoding", "utf8");
    $this->maxrowsFromConfig=
      $this->getProperty("maxrows",$this->maxrowsFromConfigDefault);
    $this->maxrowsForBarChartFromConfig=
      $this->getProperty("maxrowsbar",
        $this->maxrowsForBarChartFromConfigDefault);    
    if ($this->query->get("disablecache") != null) {
      #
      # Extension parameter take priority on disable cache configuration
      #
      if ($this->query->get("disablecache") == "1") {
        $this->disableCache();  
      }
    } elseif ($this->getProperty("disablecache")=="1") {
      #
      # ... then it's the LocalSettings property
      #
      $this->disableCache();
      
    }

    /**
     * Add CSS and Javascript to output
     */
    $this->parser->getOutput()->addModules('ext.bugzillareports');

    $this->debug && $this->debug("Rendering BugzillaReports");
    return $this->query->render().$this->getWarnings();
  }
  
  protected function disableCache() {
    $this->debug && $this->debug("Disabling parser cache for this page");
    $this->parser->disableCache();
  }

  #
  # Set value - implementation of the abstract function from BMWExtension
  #
  protected function set($name,$value) {
    # debug variable is store on this object
    if ($name=="debug") {
      $this->$name=$value;
    } else {
      $this->query->set($name,$value);
    }
  }
  
  protected function getParameterRegex($name) {
    if ($name=="debug") {
      return "/^[12]$/";
    } else {
      return $this->query->getParameterRegex($name);
    }   
  }

  function getProperty($name,$default="") {
    global $wgBugzillaReports;
    $value;
    if ($this->instanceNameSpace != null &&
      array_key_exists($this->instanceNameSpace.":".$name,$wgBugzillaReports)) {
      $value=$wgBugzillaReports[$this->instanceNameSpace.":".$name];  
    } elseif (array_key_exists($name,$wgBugzillaReports)) {
      $value=$wgBugzillaReports[$name];
    } else {
      $value=$default;
    }
    $this->debug &&
      $this->debug("Env property $name=$value");
    return $value;
  }

    public function getErrorMessage($key) {
    $args = func_get_args();
    array_shift( $args ); 
    return '<strong class="error">BugzillaReports : '. 
      wfMsgForContent($key,$args).'</strong>';  
  }
  
  public function setRawHTML($bool) {
    $this->rawHTML=$bool;
  }
}
?>
