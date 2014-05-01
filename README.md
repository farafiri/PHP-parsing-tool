## This library:

* tries to be easy to use like regular expression (no compiling required, no extra file keeping grammar required)
* is powerful like others grammar compilers
* returns easy to manipulate syntax tree objects
* uses convenient syntax which combines best things from BNF notation (idea) and regular expression (* and + for repetitions)
* allows you to deal with any context free grammar

## Example
Let say you have string with dates in format d.m.y or y-m-d separated by comma.

```php
  $dates = '2012-03-04,2013-02-08,23.06.2012';

  $parser = new \ParserGenerator\Parser('start     :=> datesList.
                                         datesList :=> date "," datesList
                                                   :=> date.
                                         date      :=> year "-" month "-" day
                                                   :=> day "." month "." year.
                                         year      :=> /\d{4}/.
                                         month     :=> /\d{2}/.
                                         day       :=> /\d{2}/.');

  $parsed = $parser->parse($dates);

  //and now you want to get list of years
  foreach($parsed->findAll('year') as $year) {
    echo $year;
  }

  //this time you want to print all months form year 2012
  foreach($parsed->findAll('date') as $date) {
    if ((string) $date->findFirst('year') === '2012') {
      echo $date->findFirst('month');
    }
  }
```

## Branch types

You could declare previous grammar as PEG, it would improve speed x10. You can declare grammar as PEG by adding
However not every grammar can be parsed with PEG packrat algorithm.

```php
  // by adding 'defaultBranchType' with 'PEG' value into options we declare grammar as PEG
  $parser = new \ParserGenerator\Parser('start :=> start "x"
                                               :=> "x".', array('defaultBranchType' => 'PEG'));
  // but PEG grammar cannot be left recursive, call parse will run infinite loop in this case
  //You have 2 solutions now
  //1-st: you can change grammar a bit:
  $parser = new \ParserGenerator\Parser('start :=> "x" start
                                               :=> "x".', array('defaultBranchType' => 'PEG'));

  //2-nd: use default branch type
  $parser = new \ParserGenerator\Parser('start :=> start "x"
                                               :=> "x".');
```

## Symbols
#### "text"
matches text, you can also define it like 'text'. You can use escape sequences so "\n" will match new line
#### /regular|expression/
tries match given regular expression
#### symbolName
will match defined symbol
* whitespace : space, newline or tabulator similar are space, newLine, tab
* text : match any text
* symbol+ : will try to match symbol several times (at least once)
* symbol? : symbol is optional
* symbol* : will try to match symbol several times (symbol is optional)
* ?symbol : lookahead
    extra
* !symbol : negative lookahead
* symbol1+symbol2 : several symbol1 ocurences separated by symbol2 (similar symbol1*symbol2)
* (symbol1 | symbol2) : choice, match symbol1 or symbol2
* string : match






