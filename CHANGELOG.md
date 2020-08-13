# Changelog

### 1.0.6

* Fix:    If the CSS output directory didn't already exist (as may often happen when CI deploying to a new folder), the CSS compilation would fail.
* Fix:    Sourcemap parameters weren't passed to the CSS AutoPrefixer so they wouldn't be honoured if AutoPrefixer was used.

### 1.0.5

* Compatibility:  Postcss won't accept options before arguments anymore

### 1.0.4

* Bugfix:  Restored from 1.0.2: If an output filename was given and the file didn't exist already, the command wouldn't create it.

### 1.0.3

* Added:   Autoprefixer option. This just runs CLI postcss, so it requires `npm install -g postcss-cli autoprefixer`

### 1.0.2

* Bugfix:  If an output filename was given and the file didn't exist already, the command wouldn't create it.

### 1.0.1

* Added:   CLI options to pass through to sassc.
* Added:   Detecting errors in compilation and outputting them, setting failure exit code if necessary.

### 1.0.0

* Allows command line compilation of SCSS to CSS
