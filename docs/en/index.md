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

### ManyManyPickerField

*  **Sortable** (`bool`) - Either enables or disables the drag and drop ordering
   selected items.
*  **ExtraFields** (`string`) - This is a string which corresponds to a method
   name on the object being attached across the relationship. This method should
   return a FieldSet, containing fields which correspond to values in the
   `$many_many_extraFields` definition. When a user selects an item, they will
   be shown the form, and then when they add the object the `many_many` link
   table's extra fields will be populated with the values they input.

## Project Links
*  [GitHub Project Page](https://github.com/ajshort/silverstripe-itemsetfield)
*  [Issue Tracker](https://github.com/ajshort/silverstripe-itemsetfield/issues)