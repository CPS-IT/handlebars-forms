..  include:: /Includes.rst.txt

..  _migration:

=========
Migration
=========

This page lists required migration steps when upgrading to a new version of
the extension.

..  _version-1.1.0:

Version 1.1.0
=============

..  _migration-1.1-wrapped-current-values:

Wrapped current values
----------------------

Non-stringable values resolved by :typoscript:`HBS_*` content objects are now
provided as current value wrapped in a :php:`CurrentValue` object provided by
EXT:handlebars. This applies to :ref:`stdWrap <custom-co-stdwrap>` as well as to
:typoscript:`if.currentValue`. Arrays still resolve to a comma-separated list of
their scalar values (now including stringable objects), and all other
non-stringable values resolve to an empty string.

Within :typoscript:`stdWrap`, non-stringable objects previously resulted in a
:php:`null` current value (within :typoscript:`if.currentValue`, they caused
errors instead). Since they are now wrapped, checks for :php:`null` values such
as :typoscript:`ifNull` or :typoscript:`if.isNull.current = 1` no longer apply
to them. Use checks for empty values instead, e.g. :typoscript:`ifEmpty` or
:typoscript:`if.isTrue.current = 1`.

Custom PHP code reading :php:`ContentObjectRenderer::getCurrentVal()` (e.g. user
functions) receives the wrapper and must unwrap the value, see
:ref:`stdWrap support <custom-co-stdwrap>`.

In addition, stringable values (scalar values and stringable objects such as
:php:`SafeString`) are no longer converted to strings before being provided as
current value within :typoscript:`stdWrap`. TypoScript is not affected by this,
but custom PHP code reading :php:`ContentObjectRenderer::getCurrentVal()` now
receives the original value, e.g. :php:`true` instead of :php:`'1'` or
:php:`42` instead of :php:`'42'`.
