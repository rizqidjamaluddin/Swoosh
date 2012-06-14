Hi! Throughout the development of Swoosh, I discovered various tidbits about how things in Flourish
worked, and some random bits of knowledge which may be useful in future code. This is just a
developer scratchpad.


= Flourish Optimizations =

== fDatabase ==

Since I decided to let Swoosh run on just MySQL, that simplified things a lot.

MySQL will always use one of these drivers, in descending priority:
- mysqli
- pdo_mysql
- mysql

*Unescaping data is rarely unnecessary.* Since we're running on MySQL here, pretty much everything
is ready to go from scratch. To be exact, only the Blob type may need unescaping, and even then,
that's only if you're using the PDO driver. This still needs testing, but everything else doesn't
need unescaping at all:

- Strings never need escaping, period. They're just a waste of time.
- Booleans only need it if you store them as something other than what PHP thinks as true/false. So
  if you're just using tinyints, you should be just fine.
- Dates, Times, and Timestamps are just roundabout features. They're passed to strtotime(), only to
  be thrown back into date()
- Floats.
- Integers.
