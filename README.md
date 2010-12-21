# SilverStripe Item Set Field Module

This module provides a number of useful form fields for managing relationships
by searching through a list of candidates and selecting the one(s) you want.

## Maintainer Contacts
*  Andrew Short (<andrew@silverstripe.com.au>)
*  Many thanks to Andreas Piening and Hamish Friedlander (the original author).

## Requirements
* SilverStripe 2.4+

## Installation
*  Place this directory in the root of your SilverStripe installation. Ensure
   that the folder name is `itemsetfield`.
*  Regenerate the manifest cache by visiting any page on your site with the
   `?flush` URL parameter set.

## Usage Overview

This module provides three main form fields - `HasOnePickerField`,
`HasManyPickerField` and `ManyManyPickerField` for managing objects acoss
`has_one`, `has_many` and `many_many` relationships respectively.

For basic usage, just use the appropriate form field for your relationship type,
and pass the constructor the parent object as the first argument, the
relationship name as the second and optional field title as the third.

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Content.HasMany', new HasManyPickerField(
            $this, 'HasManyRelationshipName'
        ));
        return $fields;
    }

For more advanced usage all fields accept an array of config options as the
fourth constructor argument. See individual fields for what options are
available.

## Links
*  [GitHub Project Page](https://github.com/ajshort/silverstripe-itemsetfield)
*  [Issue Tracker](https://github.com/ajshort/silverstripe-itemsetfield/issues)