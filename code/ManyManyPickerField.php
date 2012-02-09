<?php
/**
 * An extension to {@link HasManyPickerField} to allow managing many_many
 * relationships.
 */
class ManyManyPickerField extends HasManyPickerField {

	protected $searchFieldClass = 'ManyManyPickerField_SearchField';

	protected $parentField;
	protected $componentField;
	protected $joinTable;

	public function __construct($parent, $name, $title=null, $options=null) {
		parent::__construct($parent, $name, $title, $options);

		$this->originalParent = $parent;
		$this->originalName = $name;

		if (strstr($this->originalName, '.')) {
			$fields = explode('.', $this->originalName);
			$name = array_pop($fields);
			for ($i = 0; $i < count($fields); $i++) {
				$parent = $parent->{$fields[$i]}();
			}
		}

		list($parentClass, $componentClass, $parentField, $componentField, $table) = $parent->many_many($name);

		$this->parentField = $parentField;
		$this->componentField = $componentField;
		$this->joinTable = $table;
		$this->otherClass = ( $parent->class == $parentClass || ClassInfo::is_subclass_of($parent->class, $parentClass)) ? $componentClass : $parentClass;

		if ($name != $this->originalName) {
			$this->parent = $parent;
			$this->name = $name;
			$this->otherClass = $componentClass;
		}
	}

	public function getSortableTable() {
		return $this->joinTable;
	}

	public function getSortableTableIdField() {
		return $this->componentField;
	}

	public function getSortableTableClauseForIds($ids) {
		$field = $this->componentField;

		if (is_array($ids)) {
			$filter = "\"$field\" IN (" . implode(', ', array_map('intval', $ids)) . ')';
		} else {
			$filter = sprintf('"%s" = %d', $field, $ids);
		}

		return "$filter AND {$this->parentField} = {$this->parent->ID}";
	}

	public function getItemsQuery() {
		if ($this->getOption('Sortable')) {
			$sort = sprintf('"%s"."%s" ASC',
				$this->getSortableTable(),
				$this->getOption('SortableField'));
		} else {
			$sort = null;
		}

		return $this->parent->getManyManyComponentsQuery($this->name, null, $sort);
	}

}

class ManyManyPickerField_SearchField extends HasManyPickerField_SearchField {

	public function ItemClass() {
		return 'ManyManyPickerField_Item';
	}

}

class ManyManyPickerField_Item extends ItemSetField_Item {

	public function Choose($data) {
		// If we need to get additional data to populate the extra relationship
		// fields, then an extra fields form.
		if (!$this->parent->parent->getOption('ExtraFields')) {
			return $this->parent->Choose($data, $this->item);
		}

		return $this->AddForm()->forAjaxTemplate();
	}

	/**
	 * @return Form
	 */
	public function AddForm() {
		$method = $this->parent->parent->getOption('ExtraFields');
		$fields = $this->item->$method($this->parent->parent->parent);

		$form = new Form($this, 'AddForm', $fields, new FieldSet(
			new FormAction('doAdd', _t('ItemSetField.ADD', 'Add'))
		));
		$form->loadDataFrom($this->item);

		return $form;
	}

	public function doAdd($data, $form) {
		$field = $this->parent->parent;
		$name  = $field->Name();

		$set   = $field->parent->$name();
		$extra = $field->parent->many_many_extraFields($name);

		$data  = array_intersect_key($form->getData(), $extra);
		$set->add($this->item, $data);

		return $this->parent->parent->FieldHolder();
	}

}
