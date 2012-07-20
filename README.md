moodle-block_activity_publisher
===============================

A block allowing to export and import single activity backups (V1.9)

Versions
========

Moodle 1.9 : Branch MOODLE_19_STABLE

Moodle 2.0 : Not evaluated yet. There might be no need to port this block features to Moodle 2.

Features
========

This block provides a simple and convenient way to export a single activity with all its settings into a single backup package.

The block provides an exporter/inporter couple of features that will produce a small ZIP backup containing sufficiant information to reproduce the module within a distinct environment.
the module is stored with its settings and module scope information. No user information is stored.

When importing a single activity backup, the new activity will be added to section 0.

Technical notes
===============

this block provides special backup overrides to store specifically some kind of plugins such as a Moodle test, aggregating
questions of the question back and question used files.