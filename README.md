# mediawiki-bugzillareports

_Bugzilla Reports_ is a
[MediaWiki extension](https://www.mediawiki.org/wiki/Manual:Extensions) that
integrates the [Bugzilla](https://www.bugzilla.org/) bug tracker in
[MediaWiki](https://www.mediawiki.org/).

## Origins of this project

This project was forked from its
[original repository](https://code.google.com/p/bugzillareports/)
which the author has not maintained for a long time and which will
probably disappear soon, because the platform closes its doors.

The original extension
[is documented here](https://www.mediawiki.org/wiki/Extension:Bugzilla_Reports).
The documentation can be still applied to the fork in this repository, because
only small changes have been made to keep the project up-to-date with the
latest software versions.

## Supported versions

* [MediaWiki](https://www.mediawiki.org/) 1.31 (probably a few older versions, too)
* [Bugzilla](https://www.bugzilla.org/) 5.0 (version 4.4 works with
  [this older revision](https://github.com/nakal/mediawiki-bugzillareports/tree/17361a2439d5afdbb213ffc1c4575277b77f52ed))

## Activating the extension

Add the lines below somewhere at the end of your `LocalSettings.php`, given
you installed (or checked out) this extension in the path
_MediaWikiRoot_`extensions/BugzillaReports`.

```
require_once("$IP/extensions/BugzillaReports/BugzillaReports.php");
$wgBugzillaReports = array(
	  'host'        => "localhost",
	  'database'    => "bugs",
	  'user'        => "bugzilla",
	  'password'    => "MYSECRETPASSWORD",
	  'disablecache' => 1,
	  'bzserver'    => "http://www.example.com/bugzilla"
	  );
```

You will need to adapt the configuration in $wgBugzillaReports to
match your [Bugzilla](https://www.bugzilla.org/) database settings.

## Example

This snippet includes all _unresolved_ bugs from project `MyProject` with at
least _normal priority_ and sorted by their _bug ID_. The columns to view are
specified in the first line. When there are no matching bugs, the message
"No matching bugs found." is displayed.

```
{{#bugzilla:columns=id,component,priority,status,to,blocks,depends,summary
 |product=MyProject
 |status=!RESOLVED
 |priority=!(Low,Lowest)
 |sort=id
 |noresultsmessage="No matching bugs found."
}}
```
