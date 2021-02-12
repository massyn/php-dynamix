# php-dynamix
Dynamic database webform engine

## app files

### internal variables
* %CSRF% - will contain the CSRF token value, can be included in templates

### $_SESSION variables
* csrf - the csrf token to be passed to a form
* csrf_valid - identifies if the csrf token passed was valid or not
* hook - the current hook in use (used to keep us on the current page) 

### Schema
* type - data type (text, textarea)

### Data Types
* text
* textarea