# Item Set Field Module

## Introduction

The ItemSetField module provides a number of useful form fields for managing
relationships between objects. All of them allow you to search through candidate
objects to attach across the relationship, and select the ones you want.

## Basic Usage

This module provides a base form field, ItemSetField, which cascades into
several form fields, each for managing a type of relationship:

*  HasOnePickerField - manages a `has_one` relationship.
*  HasManyPickerField - manages a `has_many` relationship.
*  ManyManyPickerField` - manages a `many_many` relationship.

For basic usage, you use each of these form fields as you would any other. Each
form field constructor accepts four arguments:

* `object $parent` - The parent DataObject that the relationship is attached
   to. If you are inside a `getCMSFields` context, this is simply a reference
   to `$this`.
*  `string $name` - The field name, which also _has_ to be the same as the
   relationship name that is being managed.
*  `string $title` _(optional)_ - An optional field title to display.
*  `array $options` _(optional)_ - An optional array of advanced options. Each
   field type can accept several options. See the next section for more info
   on the options available.

### Simple Example

    public static $has_many = array(
        'HasManyRelationshipName' => 'OtherObject'
    );
    
    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Content.HasMany', new HasManyPickerField(
            $this, 'HasManyRelationshipName'
        ));
        return $fields;
    }

## Advanced Options

Each field type can accept a set of advanced options as the fourth constructor
parameter, where each key is an option name and the array values represent the
option values. You can also set option values using `setOption($name, $value)`,
and read them using `getOption($name)`.

Below is a listing of options available one each class:

### All Field Types

*  **Pageable** (`bool`) - Either enables or disables pagination for the list of
   items to display. It is enabled by default.
*  **ItemsPerPage** (`int`) - The number of items to display on each pagination
   page. This defaults to 15.
*  **FilterCallback** (`callback`) - A callback that is passed each object that
   would be included in the results list. If the callback returns FALSE then the
   item will not be included.
*  **PopupWidth** and **PopupHeight** (`int`) - These options control the size
   of any popup dialogs spawned from the field. This defaults to 600x400.

### HasManyPickerField (and children HasOnePickerField and HasManyPickerField)
*  **Searchable** (`bool`) - This can be set to false to disable searching
   through existing items.
*  **Sortable** (`bool`) - Either enables or disables drag and drop ordering of
   selected items. The order is updated when the parent object is saved. _Note_:
   this is not available on HasOnePickerField. This defaults to `false`.
*  **SortableField** (`string`) The database field that stores the sort order.
   For a HasManyPickerField this is just a column on the component object,
   whereas for ManyManyPickerField this must be a field defined in the
   `many_many_extraFields` data for the relationship. This defaults to `Sort`,
   and is only used if the `Sortable` option is enabled.
*  **ShowPickedInSearch** (`bool`) - If this is TRUE, then objects that have
   already been picked will show in search results. It is set to TRUE by
   default.
*  **ExtraFilter** (`string`) - An extra WHERE clause to filter the candidate
   objects shown in the search popup.
*  **AllowCreate** (`bool`) - Controls whether new items can be created with
   this field. Defaults to FALSE.
*  **FieldsMethod** (`string`) - The method name on the object being managed
   which is called to a return a `FieldSet` used to manage the object. This
   defaults to `getFrontEndFields` so that the tabbed interface is not loaded.
*  **ValidatorMethod** (`string`) - The method name which returns a validator
   used in the create and edit forms. Defaults to `getValidator`.

### ManyManyPickerField

*  **ExtraFields** (`string`) - This is a string which corresponds to a method
   name on the object being attached across the relationship. This method should
   return a FieldSet, containing fields which correspond to values in the
   `$many_many_extraFields` definition. When a user selects an item, they will
   be shown the form, and then when they add the object the `many_many` link
   table's extra fields will be populated with the values they input.

## Project Links
*  [GitHub Project Page](https://github.com/ajshort/silverstripe-itemsetfield)
*  [Issue Tracker](https://github.com/ajshort/silverstripe-itemsetfield/issues)