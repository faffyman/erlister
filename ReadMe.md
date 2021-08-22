# Installation

1. Clone the repository to your local machine
2. Copy the phar file from the dist folder to your PATH
   ```bash
    chmod +x ./dist/erlister.phar 
    cp ./dist/erlister.phar /usr/local/bin/erlister 
   ```
3. Now it should be executable from anywhere

## Using It

You can run `erlister --help` to get instructions at anytime

**Description:**  
Scrapes a web page and *Lists* each *E*xternal *R*esource used by that web page

**Usage:**  
`erlister.phar [options] [--] <url>`  
  
**Arguments:**  
```
url                   *FULL* URL you want to scan - you MUST include the protocol - i.e. http:// or https://
```
  
**Options:**  
  
| shortcut  | full-option      | description |
|-----------|------------------|-------------|
| -d        | --domains-only   | Lists only the external domains according to usage type |
| -h        | --help           | Display help for the given command. When no command is given display help for the scrape command|
| -q        | --quiet          | Do not output any message |
| -V        | --version        | Display this application version |
|           | --ansi           | Force (or disable --no-ansi) ANSI output|
|           |--no-ansi         | disable ANSI output |
| -n        | --no-interaction | Do not ask any interactive question |
| -v vv vvv | --verbose        | Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug |

**Help:**  
Use this tool to scan a web page to find out all the external resources used by that page.  
useful for discovering domains that you may want to dns-prefetch or for approving CSP rules  
results are output to screen grouped according to resource type.  

**Example:**  
`erlister.phar https://www.wikipedia.com --domains-only`

_Results_  

```
┌──────────────────┬────────────────────────────┐
│ TYPE             │ DOMAIN                     │
├──────────────────┼────────────────────────────┤
│ Stylesheet Links │ creativecommons.org        │
│ Stylesheet Links │ upload.wikimedia.org       │
├──────────────────┼────────────────────────────┤
│ Clickable Links  │ ab.wikipedia.org           │
│ Clickable Links  │ ace.wikipedia.org          │
│ Clickable Links  │ af.wikipedia.org           │
│ Clickable Links  │ ak.wikipedia.org           │
│ Clickable Links  │ als.wikipedia.org          │
└──────────────────┴────────────────────────────┘
```

If the `--domains-only` option is used, then the results will show only the individual domains referenced, 
and not the actual resources. This is useful if you are scanning to set CSP Rules 

If you are scanning to allow for DNS prefetching or to get a list fo resources you want to cache; then leave this option off.

### Compile to PHAR

**NB** - A phar is already included in the `/dist` directory - but if you wat to regenerate it follow these steps
_But maybe backup the existing one first ;-)_

1. Ensure that you do have `phar.readonly = Off` set in YOUR _active_ PHP version `ini` file.
2. Change into the `build` directory 
3. Run the compiler script `php ./compiler.php`
4. Wait and watch until it completes.
5. New PHAR should be generated in the dist folder.
6. Now you can copy erlister.phar to you PATH, e.g. /usr/local/bin to make it executable from anywhere.

