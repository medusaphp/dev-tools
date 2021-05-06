# Medusa Dev

## debuglog function for bashrc

```bash
function debuglog() {
  local _file=~/app_debug_logs/$(date +"log_%Y-%m-%d.log")
  [ -f "$_file" ] || touch $_file
  tail -f $_file
}

```
##Examples:
```
integrated counter:  
debugLog('get config call: >>$i++<<');

result:
3bdf1d7a -> [0,000000ms +0,000000ms: ] Get config 1
3bdf1d7a -> [0,595947ms +0,595947ms: ] Get config 2
 
```
