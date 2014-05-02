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
##### "text"
Matches text. You can also use single quotes. You can use escape sequences so "\n" will match new line.

##### /regular|expression/
Matches given regular expression. You can use pattern modifiers.
Grammar like "start :=> /[a-z]/i." will match also upper case letters.
Regular expression cannot be backtracked. They work like fist match is only match
For example: "start :=> /a+/ 'a'.", when we try to parse string "aa" regular expression will capture both characters and string will be not matched.

##### symbolName
will match defined symbol
For example:
```
start  :=> letter digit.
letter :=> /\w/.
digit  :=> /\d/.
```
will match any pair of letter followed by digit.

##### whitespace, space, newLine, tab
whitespace matches space, tabulator or new line character
If ignoreWhitespaces mode is off these symbols work same as /\s/, " ", /\t/, /\n/.
When ignoreWhitespaces mode is on then /\s/, " ", "\t", "\n" won't work and you must whitespace, space etc symbols.
In ignoreWhitespaces mode these symbols check context and not consuming characters from input.
For example sequence: 'a' newLine space space 'b' will match characters 'a' and 'b' separated by at least one space and at least one new line symbol

##### text
match any text

##### symbol+
will try to match symbol several times (at least once)
For example start :=> "a"+. will match "a" "aa" "aaa" but not ""

##### symbol?
symbol is optional
For example start :=> "a"?. wil match "a" and "" but not "aa"

##### symbol*
will try to match symbol several times (symbol is optional)
For example start :=> "a"*. will match "a" "aa" "aaa" and ""

##### symbol++, symbol**, symbol??
same as adequate symbol+, symbol* and symbol* but consumes it in greedy way.
Example:
```php
$nonGreedy = new \ParserGenerator\Parser('start :=> "a"* "a"*.');
$nonGreedy->parse("aaa")->getSubnode(0)->toString(); // "" first "a"* takes nothing
$nonGreedy->parse("aaa")->getSubnode(1)->toString(); // "aaa" so second must consume all left

$greedy = new \ParserGenerator\Parser('start :=> "a"** "a"**.');
$greedy->parse("aaa")->getSubnode(0)->toString(); // "aaa" first "a"** takes all
$greedy->parse("aaa")->getSubnode(1)->toString(); // "" so nothing left for second

$greedy = new \ParserGenerator\Parser('start :=> "a"** "a"+.');
// "aa" "a"** tries to take all but then parsing would fail and he must leave last char for "a"+
$greedy->parse("aaa")->getSubnode(0)->toString();
$greedy->parse("aaa")->getSubnode(1)->toString(); // "a"
```
If 'defaultBranchType' is set to 'PEG' then symbol* is equal to symbol** (always greedy). Same with "+" and "?". In this mode last case will fail (PEG cannot parse it)

##### ?symbol
Lookahead. Check if symbol can be parsed but do not capture it
For example "start   :=> 'a' ?/.{3}/ integer.
             integer :=> /\d+/." will match "a" followed by at least 3 digit number.
##### !symbol
Negative lookahead. Similar to ?symbol but continue parsing only if cannot match symbol

##### symbol1+symbol2
Several symbol1 occurrences separated by symbol2 (similar for *, ++, **)
```php
$parser = new \ParserGenerator\Parser('start :=> word+",".
                                       word  :=> /\w+/.');
foreach($parser->parse("a,bc,d")->getSubnode(0)->getSubnodes() as $subnode) {
  echo $subnode . ' ';
} //prints "a , bc , d "

foreach($parser->parse("a,bc,d")->getSubnode(0)->getMainNodes() as $subnode) {
  echo $subnode . ' ';
} //prints "a bc d "
```
Note that symbol1+ symbol2 is something different than symbol1+symbol2.
This space between + and symbol2 is crucial
"a"+ "b" matches: "aaaab" but not "ababa"
"a"+"b"  matches: "ababa" but not :aaaab"

##### (symbol1 | symbol2)
Choice, match symbol1 or symbol2
For example "start :=> ('a' | 'b') 'c'." will parse strings "ac" and "bc"

##### string
Syntax sugar for regex like: /"([^\\]|\\.)*"/
Matches quoted strings
Example:
```php
$parser = new \ParserGenerator\Parser('start :=> string.');
$stringNode = $parser->parse('"a\tb\"c"')->getSubnode(0);
echo (string) $stringNode; //prints:"a\tb\"c"
echo $stringNode->getValue(); //prints:a    b"c
```

By default string may be quoted by quotation or apostrophe.
string/apostrophe : can be quoted only by apostrophe
string/quotation  : can be quoted only by quotation
string/simple     : can be quoted only by quotation, no characters escaping by \, quotation character by repetition (style used in Pascal or CSV)

##### numbers
Of course you you can use /\d+/ but using build-in toolkit for numbers is much easier and readable
```php
//parser matching only integers from 3 to 17 (inclusive)
$parser = new \ParserGenerator\Parser('start :=> 3..17 .');
$parser->parse('2'); //false
$parser->parse('18'); //false
$parser->parse('12'); //syntax tree object

//parser matching only integers > 0
$parser = new \ParserGenerator\Parser('start :=> 1..infinity .');

//parser matching integers in hex decimal and oct
$parser = new \ParserGenerator\Parser('start :=> -inf..inf/hdo .');
$parser->parse('0x21')->getSubnode(0)->getValue(); // 33
$parser->parse('21')->getSubnode(0)->getValue(); //21
$parser->parse('021')->getSubnode(0)->getValue(); //17

//matching month number with leading 0 for < 10
$parser = new \ParserGenerator\Parser('start :=> 01..12 .');
$parser->parse('4'); //false
$parser->parse('04'); //syntax tree object
```

##### time()
Matching time in given format
```php
$parser = new \ParserGenerator\Parser('start :=> (time(Y-m-d) | time(d.m.Y)) .');
$parser->parse('2017-01-02')->getSubnode(0)->getValue(); // equal to new \DateTime('2017-01-02')
$parser->parse('03.05.2014')->getSubnode(0)->getValue(); // equal to new \DateTime('2014-05-03')
```

##### contain, is
Sometimes you may want to do extra checks on parsed node.
Thanks to these constructs you can check if node contain some text or if matches to an pattern
```php
$parser = new \ParserGenerator\Parser('start   :=> word not is keyword.
                                       word    :=> /\w+/.
                                       keyword :=> ("do" | "while" | "if").');
$parser->parse('do'); //false
$parser->parse('doSomething'); // syntax tree object
```

It is possible to make some basic logic operations on check and put them into braces
```php
$parser = new \ParserGenerator\Parser('start   :=> word not(is keyword or
                                                            is ("p" text /* we don`t want words starting with "p" */) or
                                                            is /./ /* we don`t want one letter words */ ).
                                       word    :=> /\w+/.
                                       keyword :=> ("do" | "while" | "if").');
$parser->parse('do'); //false
$parser->parse('d'); //false
$parser->parse('post'); //false
$parser->parse('doSomething'); // syntax tree object
```






