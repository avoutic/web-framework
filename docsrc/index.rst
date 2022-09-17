.. web-framework documentation master file, created by
   sphinx-quickstart on Fri Nov  1 13:09:09 2019.
   You can adapt this file completely to your liking, but it should at least
   contain the root `toctree` directive.

web-framework: Simple Secure PHP Framework
==========================================

.. toctree::
   :maxdepth: 2
   :hidden:
   :caption: Concepts:

   data-core
   sender-core
   accounts
   tasks
   routing

Secure-by-default
    web-framework is built from the ground up as a secure PHP framework in it's default state. You have to enable individual input variables with an allowed regex for them to be available to your actions.

Core database abstraction
    Objects are automatically constructed from database tables and standard operations are available in code. Complex queries can still be built in SQL from the ground up.

First steps
-----------

If you have never used web-framework before, please have a look at our `Installation Guide <https://github.com/avoutic/web-framework/blob/master/INSTALL.md>`_.
