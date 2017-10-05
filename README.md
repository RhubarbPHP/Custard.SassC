# Custard.SassC
A custard command for compiling SCSS into CSS using SassC

```
Usage:
  compile:scss [options] <input> <output>

Arguments:
  input                          File or directory to compile
  output                         File or directory to output to

Options:
  -s, --style=STYLE              Output style. Can be: nested, compressed. [default: "compressed"]
  -l, --line-numbers             Emit comments showing original line numbers.
  -i, --import-path=IMPORT-PATH  Set Sass import path.
  -m, --sourcemap                Emit source map.
  -M, --omit-map-comment         Omits the source map url comment.
  -p, --precision=PRECISION      Set the precision for numbers.
  -a, --autoprefix               Run postcss autoprefixer on output CSS files.
  -vv                            Increase output verbosity (displays output from sasscb and autoprefixer).
```

**Note:** The autoprefixer option requires postcss CLI and autoprefixer to be installed globally. 
These are node.js modules and can be installed globally with this command once you have node installed:
`npm install -g postcss-cli autoprefixer`
