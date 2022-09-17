Data Core
=========

Within web-framework data and database interaction is abstracted where possible. With a little bit of code you can make simple and more complex object types by just providing an ID or another identifier.

The core library has two base classes to build from:

* WebFramework\Core\DataCore, an object abstraction that represents a row from a table
* WebFramework\Core\FactoryCore, a factory abstraction that understands how to work with DataCore objects

DataCore
--------

The base data abstraction is done with `DataCore`. By just specifying the `table_name` and `base_fields` you can already easily instantiate objects based on data in the database.

If we have a table *persons* in our database, with fields like name, address and country, we can easily encapsulate this table with a DataCore abstraction. Let's create *includes/Persons.php* to construct the Person class.

.. code-block:: php

    <?php
    namespace App\Core;

    use WebFramework\Core\DataCore;

    class Person extends DataCore
    {
        static protected string $table_name = 'persons';
        static protected array $base_fields = array('name', 'email', 'country');
    };
    ?>

.. note::

   For encapsulation with DataCore, each table needs a column named `id` with unique, as primary key values.

Our Person object now has basic capabilities. While we can instantiate an DataCore object with `new`, this is not advised for multiple reasons. You should use `Person::get_object_by_id()` and others instead. The main reason is that this gracefully handles non-existing IDs (returning `false`), but also allows intermediate caching and transformations.

So if we instantiate a Person (using our global database information), we can take actions, like these:

.. code-block:: php

    <?php
    use App\Core\Person;
    use WebFramework\Core\WF;

    // Retrieve person with id 5
    $person = Person::get_object_by_id(5);

    // Retrieve base fields as object parameters
    echo 'Name: '.$person->name.PHP_EOL;

    // Update this Person's country
    $result = $person->update(array('country' => 'Belgium'));
    WF::verify($result !== false, 'Failed to update person');
    ?>

.. note::

   What is `WF::verify()`? `WF::verify()` is like `assert()`. It is used to guard code paths that should never occur, unless something is really wrong. But unlike `assert()` our `WF::verify()` cannot be silently ignored due to PHP settings. In addition it can e-mail error reports to you so you see errors even when others encounter them. For a secure-by-default platform, we want to make sure those guards are always there. In addition it can show a debug trace, debug info and e-mail you in case a verify gate fails. In most cases you will use `$this->verify()` instead, when you work with code in objects.

Complex objects
***************

There are a lot of cases where you don't just need to encapsulate a single row from a single table, but data from other tables is required as well. Let's consider that our Person can also own one or more vehicles. We can easily make sure that those vehicles are populated directly at instantiation of a Person.

Let's first create our Vehicle class in *includes/Vehicle.php*:

.. code-block:: php

    <?php
    namespace App\Core;

    use WebFramework\Core\DataCore;

    class Vehicle extends DataCore
    {
        static protected string $table_name = 'vehicles';
        static protected array $base_fields = array('type', 'brand', 'color');
    };
    ?>

And we'll add a method called `fill_complex_fields()` in our Person class:

.. code-block:: php

    function fill_complex_fields()
    {
        $this->vehicles = Vehicle::get_objects(0, -1,
            array('owner_id' => $this->id));
    }

`fill_complex_fields()` is immediately called in the constructor after all base fields have been loaded.

Keep in mind that `Person->fill_complex_fields()` runs on every instantiation. In most cases you want to be able to instantiate a bare Person class as well. So it would be better to just implement a `Person->get_vehicles()` (with optional caching) instead:

.. code-block:: php

    protected ?array $vehicles = null;
    function get_vehicles()
    {
        if ($this->vehicles === null)
            $this->vehicles = Vehicle::get_objects(0, -1, array('owner_id' => $this->id));

        return $this->vehicles;
    }

Object Documentation
--------------------

DataCore Object
***************

.. php:class:: DataCore()

   An object abstration that represents a single row from a table.

   .. php:attr:: protected static string $table_name

      The name of the table in your database

   .. php:attr:: protected static array $base_fields

      An array with fields that should always be loaded into the object

   .. php:method:: get_base_fields (): array

      Retrieve all raw database fields

   .. php:method:: get_field (string $field): mixed

      Retrieve a non-base-field for the object

      :param string $field: The field name in the table

   .. php:method:: update (array $data): void

      Update fields in the database

      :param array $data: Array with field names and values to store

   .. php:method:: update_field (string $field, mixed $value): void

      Update a single field

      :param string $field: Field to update
      :param $value: Value to store

   .. php:method:: decrease_field (string $field, int $value = 1, bool|int $minimum = false): void

      Decrease the value of a field

      :param string $field: Field to update
      :param $value: Decrease by this value
      :param $minimum: If set, value will not reduce below this minimu,

   .. php:method:: increase_field (string $field, int $value = 1): void

      Increase the value of a field

      :param string $field: Field to update
      :param $value: Increase by this value

   .. php:method:: delete(): void

      Delete this item

   .. php:staticmethod:: create (array $fields): object

      Create a new Database entry with these fields

      :param array $fields: Array of all (required) database fields for this table

   .. php:staticmethod:: exists (int $id): bool

      Check if an object with that id exists.

      :param int $id: ID of the object to check

   .. php:staticmethod:: count_objects (array $filter): bool

      Count the number of entries that match the filter

      :param array $filter: Array of fields that should match

   .. php:staticmethod:: get_object_by_id (int $id): object

      Retrieve an object by id

      :param array $id: The id of the object to retrieve

   .. php:staticmethod:: get_object (array $filter): object

      Retrieve a single object based on a filter array. Fails if more than one entries match.

      :param array $filter: Array of fields that should match

   .. php:staticmethod:: get_object_info (array $filter): array

      Retrieve a single object's get_info() based on a filter array. Fails if more than one entries match.

      :param array $filter: Array of fields that should match

   .. php:staticmethod:: get_object_data (string $data_function, array $filter): array

      Retrieve a single object's data via the data_function specified based on a filter array. Fails if more than one entries match.

      :param array $data_function: Data function to call
      :param array $filter: Array of fields that should match

   .. php:staticmethod:: get_object_info_by_id (int $id): object

      Retrieve a single object's get_info() based on id.

      :param array $id: The id of the object to retrieve.

   .. php:staticmethod:: get_object_data_by_id (string $data_function, int $id): array

      Retrieve a single object's data via the data_function specified.

      :param array $data_function: Data function to call
      :param array $id: The id of the object to retrieve

   .. php:staticmethod:: get_objects (int $offset, int $results, array $filter, string $order): array

      Retrieve an array of objects based on a filter array.

      :param array $offset: Start offset for paging
      :param array $results: Amount of objects for paging
      :param array $filter: Array of fields that should match
      :param array $order: String of SQL order

   .. php:staticmethod:: get_objects_info (int $offset, int $results, array $filter, string $order): array

      Retrieve an array filled with objects' get_info() based on a filter array.

      :param array $offset: Start offset for paging
      :param array $results: Amount of objects for paging
      :param array $filter: Array of fields that should match
      :param array $order: String of SQL order

   .. php:staticmethod:: get_objects_data (string $data_function, int $offset, int $results, array $filter, string $order): array

      Retrieve an array filled fill objects' data via the data_function specified based on a filter array.

      :param array $data_function: Data function to call
      :param array $offset: Start offset for paging
      :param array $results: Amount of objects for paging
      :param array $filter: Array of fields that should match
      :param array $order: String of SQL order

