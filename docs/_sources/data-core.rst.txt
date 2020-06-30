Data Core
=========

Within web-framework data is abstracted where possible. With a little bit of code you can make simple and more complex object types by just providing an ID or another identifier.

The core library has two base classes to build from:

* DataCore, an object abstraction that represents a row from a table
* FactoryCore, a factory abstraction that understands how to work with DataCore objects

DataCore
--------

The base data abstraction is done with *DataCore*. By just specifying the `table_name` and `base_fields` you can already easily instantiate objects based on data in the database.

If we have a table *persons* in our database, with fields like name, address and country, we can easily encapsulate this table with a DataCore abstraction. Let's create *includes/persons.inc.php* to construct the Person class.

.. code-block:: php

    <?php
    class Person extends DataCore
    {
        static protected $table_name = 'persons';
        static protected $base_fields = array('name', 'email', 'country');
    };
    ?>

.. note::

   For encapsulation with DataCore, each table needs a column named `id` with unique, as primary key values.

Our Person object now has basic capabilities. So if we instantiate a Person (using our global database information), we can take actions, like these:

.. code-block:: php

    <?php
    // Retrieve person with id 5
    $person = new Person(5);

    // Retrieve base fields as object parameters
    echo 'Name: '.$person->name.PHP_EOL;

    // Update this Person's country
    $result = $person->update(array('country' => 'Belgium'));
    WF::verify($result !== false, 'Failed to update person');
    ?>

.. note::

   What is `WF::verify()`? `WF::verify()` is like `assert()`. It is used to guard code paths that should never occur, unless something is really wrong. But unlike `assert()` our `WF::verify()` cannot be silently ignored due to PHP settings. For a secure-by-default platform, we want to make sure those guards are always there. In addition it can show a debug trace, debug info and e-mail you in case a verify gate fails.

Complex objects
***************

There are a lot of cases where you don't just need to encapsulate a single table, but data from other tables is required as well. Let's consider that our Person can also own one or more vehicles. We can easily make sure that those vehicles are populated directly at instantiation of a Person.

Let's first create our Vehicle class in *includes/vehicles.inc.php*:

.. code-block:: php

    <?php
    class Vehicle extends DataCore
    {
        static protected $table_name = 'vehicles';
        static protected $base_fields = array('type', 'brand', 'color');
    };
    ?>

Now we include this file at the top of *includes/persons.inc.php*:

.. code-block:: php

    require_once(WF::$site_includes.'vehicles.inc.php');

    class Person extends DataCore
    <snip>

And we'll add a method called `fill_complex_fields()` in our Person class:

.. code-block:: php

    function fill_complex_fields()
    {
        $this->vehicles = Vehicles::get_objects(0, -1,
                                       array('owner_id' => $this->id));
    }

`fill_complex_fields()` is immediately called in the constructor after all base fields have been loaded.

Object Documentation
--------------------

DataCore Object
***************

.. php:class:: DataCore()

   An object abstration that represents a single row from a table.

   .. php:attr:: protected static $table_name

      The name of the table in your database

   .. php:attr:: protected static $base_fields

      An array with fields that should always be loaded into the object

   .. php:staticmethod:: exists ($id)

      Check if an object with that id exists.

      :param int $id: ID of the object to check

   .. php:method:: get_field ($field)

      Retrieve a non-base-field for the object

      :param string $field: The field name in the table

   .. php:method:: update ($data)

      Update fields in the database

      :param array $data: Array with field names and values to store

   .. php:method:: update_field ($field, $value)

      Update a single field

      :param string $field: Field to update
      :param $value: Value to store

   .. php:method:: decrease_field ($field, $value = 1, $minimum = false)

      Decrease the value of a field

      :param string $field: Field to update
      :param $value: Decrease by this value
      :param $minimum: If set, value will not reduce below this minimu,


   .. php:method:: increase_field ($field, $value = 1)

      Increase the value of a field

      :param string $field: Field to update
      :param $value: Increase by this value

   .. php:method:: delete()

      Delete this item
